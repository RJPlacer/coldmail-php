<?php
declare(strict_types=1);

$testRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dispatch-tests-' . bin2hex(random_bytes(6));
$dataRoot = $testRoot . DIRECTORY_SEPARATOR . 'data';

function test_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function remove_test_tree(string $root): void {
    if (!is_dir($root)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }
    rmdir($root);
}

try {
    foreach (['jobs', 'lists', 'smtp', 'templates'] as $directory) {
        mkdir($dataRoot . DIRECTORY_SEPARATOR . $directory, 0700, true);
    }

    $users = [
        'alice.smith' => ['password_hash' => 'test'],
        'alice+smith' => ['password_hash' => 'test'],
    ];
    file_put_contents($dataRoot . '/users.json', json_encode($users));

    // These usernames collided under the legacy slug algorithm.
    $legacySettings = [
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => 587,
        'smtp_user' => 'sender@example.com',
        'smtp_pass' => 'secret-value',
        'use_ssl' => false,
        'from_name' => 'Sender',
    ];
    file_put_contents($dataRoot . '/smtp/alice_smith.json', json_encode($legacySettings));
    file_put_contents($dataRoot . '/jobs/0123456789abcdef0123456789abcdef.json', json_encode([
        'owner' => 'alice.smith',
        'smtp_pass' => 'must-be-removed',
        'smtp_host' => 'smtp.example.com',
        'status' => 'done',
    ]));

    putenv('DISPATCH_DATA_DIR=' . $dataRoot);
    require dirname(__DIR__) . '/config.php';

    test_assert(!file_exists($dataRoot . '/smtp/alice_smith.json'), 'Legacy private file was not removed.');
    $aliceFile = smtp_settings_file('alice.smith');
    $otherAliceFile = smtp_settings_file('alice+smith');
    test_assert($aliceFile !== $otherAliceFile, 'Distinct usernames resolved to the same private file.');
    test_assert(file_exists($aliceFile) && file_exists($otherAliceFile), 'Legacy settings were not migrated.');

    $job = json_decode(
        file_get_contents($dataRoot . '/jobs/0123456789abcdef0123456789abcdef.json'),
        true
    );
    test_assert(!array_key_exists('smtp_pass', $job), 'Legacy job still contains an SMTP password.');
    test_assert(!array_key_exists('smtp_host', $job), 'Legacy job still contains SMTP configuration.');

    $publicSettings = public_smtp_settings(load_smtp_settings('alice.smith'));
    test_assert(!array_key_exists('smtp_pass', $publicSettings), 'Public SMTP settings exposed the password.');
    test_assert($publicSettings['has_password'] === true, 'Public SMTP settings omitted password status.');

    $changedSettings = $legacySettings;
    $changedSettings['from_name'] = 'Alice';
    save_smtp_settings('alice.smith', $changedSettings);
    test_assert(
        load_smtp_settings('alice+smith')['from_name'] === 'Sender',
        'Updating one account changed another account.'
    );

    $parsed = parse_recipients_csv("email,name\nvalid@example.com,Alice\nnot-an-email,Bad");
    test_assert(count($parsed['rows']) === 1, 'Invalid email address was accepted.');
    test_assert($parsed['rows'][0]['name'] === 'Alice', 'Valid CSV row was parsed incorrectly.');

    $duplicateHeaders = parse_recipients_csv("email,email\none@example.com,two@example.com");
    test_assert(!empty($duplicateHeaders['error']), 'Duplicate CSV headers were accepted.');

    $duplicateRows = parse_recipients_csv("email\nsame@example.com\nSAME@example.com\nbad-address");
    test_assert(count($duplicateRows['rows']) === 1, 'Duplicate recipient was not removed.');
    test_assert($duplicateRows['duplicate_count'] === 1, 'Duplicate recipient count was incorrect.');
    test_assert($duplicateRows['invalid_count'] === 1, 'Invalid recipient count was incorrect.');

    add_suppression('alice.smith', 'blocked@example.com', 'test');
    $audited = audit_recipients_for_user(
        "email\nallowed@example.com\nblocked@example.com",
        'alice.smith'
    );
    test_assert(count($audited['rows']) === 1, 'Suppressed recipient was not excluded.');
    test_assert($audited['suppressed_count'] === 1, 'Suppression count was incorrect.');
    test_assert(remove_suppression('alice.smith', 'blocked@example.com'), 'Suppression removal failed.');

    $unsubscribeToken = unsubscribe_token('alice.smith', 'person@example.com');
    $verifiedToken = verify_unsubscribe_token($unsubscribeToken);
    test_assert($verifiedToken['email'] === 'person@example.com', 'Unsubscribe token verification failed.');
    test_assert(verify_unsubscribe_token($unsubscribeToken . 'tampered') === null, 'Tampered unsubscribe token was accepted.');
    $emailHtml = build_dispatch_email_html('<script>alert(1)</script>', 'Footer', null, 'https://example.com/unsubscribe');
    test_assert(!str_contains($emailHtml, '<script>'), 'Email body HTML was not escaped.');
    test_assert(str_contains($emailHtml, 'Unsubscribe from future emails'), 'Email omitted unsubscribe link.');

    $tooManyRows = ["email"];
    for ($index = 0; $index <= MAX_RECIPIENTS; $index++) {
        $tooManyRows[] = "person{$index}@example.com";
    }
    $tooManyRecipients = parse_recipients_csv(implode("\n", $tooManyRows));
    test_assert(!empty($tooManyRecipients['error']), 'Recipient limit was not enforced.');

    write_json_file(JOBS_DIR . '/abcdefabcdefabcdefabcdefabcdefab.json', [
        'owner' => 'alice.smith',
        'subject' => 'Resumable campaign',
        'status' => 'sending',
        'index' => 2,
        'total' => 5,
        'sent' => 2,
        'failed' => 0,
        'delay_seconds' => 7,
        'dry_run' => true,
        'created_at' => date('c'),
        'results' => [],
    ]);
    $activeJobs = list_user_active_jobs('alice.smith');
    test_assert(count($activeJobs) === 1, 'Active campaign was not discoverable for resume.');
    test_assert($activeJobs[0]['progress'] === 2, 'Active campaign progress was incorrect.');
    test_assert($activeJobs[0]['delay_seconds'] === 7.0, 'Active campaign delay was not preserved.');

    write_json_file(JOBS_DIR . '/abcdefabcdefabcdefabcdefabcdefab.json', [
        'owner' => 'alice.smith',
        'subject' => 'Stopped campaign',
        'status' => 'sending',
        'stop_flag' => true,
        'index' => 2,
        'total' => 5,
        'sent' => 2,
        'failed' => 0,
        'created_at' => date('c'),
        'results' => [],
    ]);
    test_assert(
        list_user_active_jobs('alice.smith') === [],
        'Stop-flagged campaign was incorrectly offered for resume.'
    );

    upsert_message_template('alice.smith', 'First', ['subject' => 'One', 'body' => 'Body']);
    upsert_message_template('alice.smith', 'Second', ['subject' => 'Two', 'body' => 'Body']);
    $templates = load_message_templates('alice.smith');
    test_assert(isset($templates['First'], $templates['Second']), 'Template update lost existing data.');

    test_assert(
        render_merge_tags('Hello {{ name }}', ['name' => 'Alice']) === 'Hello Alice',
        'Merge-tag rendering failed.'
    );

    echo "All Dispatch tests passed.\n";
} finally {
    remove_test_tree($testRoot);
}
