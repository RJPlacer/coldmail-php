<?php
// Recipient parsing + merge-tag rendering helpers.

function ensure_directory(string $dir): void {
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException("Could not create data directory.");
    }
}

function json_lock_file(string $file): string {
    return $file . '.lock';
}

function read_json_file(string $file, array $default = []): array {
    if (!file_exists($file)) {
        return $default;
    }
    ensure_directory(dirname($file));
    $lock = fopen(json_lock_file($file), 'c+');
    if ($lock === false) {
        throw new RuntimeException('Could not open data lock.');
    }
    try {
        if (!flock($lock, LOCK_SH)) {
            throw new RuntimeException('Could not lock data file.');
        }
        $raw = file_get_contents($file);
        $data = $raw === false ? null : json_decode($raw, true);
        return is_array($data) ? $data : $default;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function update_json_file(string $file, callable $mutator, int $mode = 0600): array {
    ensure_directory(dirname($file));
    $lock = fopen(json_lock_file($file), 'c+');
    if ($lock === false) {
        throw new RuntimeException('Could not open data lock.');
    }
    try {
        if (!flock($lock, LOCK_EX)) {
            throw new RuntimeException('Could not lock data file.');
        }
        $current = [];
        if (file_exists($file)) {
            $raw = file_get_contents($file);
            $decoded = $raw === false ? null : json_decode($raw, true);
            $current = is_array($decoded) ? $decoded : [];
        }
        $updated = $mutator($current);
        if (!is_array($updated)) {
            throw new RuntimeException('Data update did not return an array.');
        }
        $json = json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $temp = $file . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($temp, $json, LOCK_EX) === false) {
            throw new RuntimeException('Could not write data file.');
        }
        chmod($temp, $mode);
        if (!@rename($temp, $file)) {
            // Windows cannot always replace an existing file with rename().
            if (file_put_contents($file, $json, LOCK_EX) === false) {
                @unlink($temp);
                throw new RuntimeException('Could not replace data file.');
            }
            @unlink($temp);
        }
        chmod($file, $mode);
        return $updated;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function write_json_file(string $file, array $data, int $mode = 0600): void {
    update_json_file($file, fn(array $unused): array => $data, $mode);
}

/**
 * Parse pasted CSV text. First row must be a header row and must include
 * an "email" column. Returns ['fieldnames' => [...], 'rows' => [...]].
 */
function parse_recipients_csv(string $rawText): array {
    if (strlen($rawText) > MAX_RECIPIENT_CSV_BYTES) {
        return [
            'fieldnames' => [],
            'rows' => [],
            'has_email_column' => false,
            'error' => 'Recipient data is too large (maximum 5 MB).',
        ];
    }
    $rawText = trim($rawText);
    if ($rawText === '') {
        return ['fieldnames' => [], 'rows' => [], 'has_email_column' => false];
    }

    $lines = preg_split('/\r\n|\r|\n/', $rawText);
    $handle = fopen('php://temp', 'r+');
    foreach ($lines as $line) {
        fwrite($handle, $line . "\n");
    }
    rewind($handle);

    $header = fgetcsv($handle, null, ',', '"', '');
    if ($header === false) {
        fclose($handle);
        return ['fieldnames' => [], 'rows' => [], 'has_email_column' => false];
    }
    $header = array_map('trim', $header);
    if (isset($header[0])) {
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    }
    if (in_array('', $header, true) || count(array_unique(array_map('strtolower', $header))) !== count($header)) {
        fclose($handle);
        return [
            'fieldnames' => $header,
            'rows' => [],
            'has_email_column' => false,
            'error' => 'CSV headers must be non-empty and unique.',
        ];
    }

    // Find whichever column case-insensitively matches "email" (so Email,
    // EMAIL, etc. all work) and normalize it to the literal key 'email'.
    $emailColIndex = null;
    foreach ($header as $i => $h) {
        if (strtolower($h) === 'email') {
            $emailColIndex = $i;
            break;
        }
    }

    $rows = [];
    $exclusions = [];
    $seenEmails = [];
    $invalidCount = 0;
    $duplicateCount = 0;
    $limitExceeded = false;
    while (($data = fgetcsv($handle, null, ',', '"', '')) !== false) {
        if (count($data) === 1 && trim($data[0]) === '') {
            continue; // skip blank lines
        }
        $row = [];
        foreach ($header as $i => $key) {
            $row[$key] = isset($data[$i]) ? trim($data[$i]) : '';
        }
        // Always expose a normalized lowercase 'email' key, regardless of
        // the original header's casing (Email, EMAIL, etc.), without
        // clobbering the original-cased key used for display/merge tags.
        if ($emailColIndex !== null) {
            $row['email'] = isset($data[$emailColIndex]) ? trim($data[$emailColIndex]) : '';
        }
        $email = trim((string)($row['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalidCount++;
            if (count($exclusions) < MAX_RECIPIENTS) {
                $exclusions[] = ['email' => $email, 'reason' => $email === '' ? 'missing-email' : 'invalid-email'];
            }
            continue;
        }
        $normalizedEmail = strtolower($email);
        if (isset($seenEmails[$normalizedEmail])) {
            $duplicateCount++;
            if (count($exclusions) < MAX_RECIPIENTS) {
                $exclusions[] = ['email' => $email, 'reason' => 'duplicate'];
            }
            continue;
        }
        $seenEmails[$normalizedEmail] = true;
        if (count($rows) >= MAX_RECIPIENTS) {
            $limitExceeded = true;
            break;
        }
        $rows[] = $row;
    }
    fclose($handle);

    if ($limitExceeded) {
        return [
            'fieldnames' => $header,
            'rows' => [],
            'has_email_column' => $emailColIndex !== null,
            'error' => 'A campaign can contain at most 5,000 valid recipients.',
        ];
    }
    return [
        'fieldnames' => $header,
        'rows' => $rows,
        'has_email_column' => $emailColIndex !== null,
        'invalid_count' => $invalidCount,
        'duplicate_count' => $duplicateCount,
        'exclusions' => $exclusions,
    ];
}

/**
 * Replace {{field}} merge tags in a template string with values from $row.
 */
function render_merge_tags(string $template, array $row): string {
    $result = $template;
    foreach ($row as $key => $value) {
        $result = str_replace('{{' . $key . '}}', $value, $result);
        $result = str_replace('{{ ' . $key . ' }}', $value, $result);
    }
    return $result;
}

function json_input(): array {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        json_response(['error' => 'Method not allowed.'], 405);
    }
    require_csrf();
    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > MAX_JSON_REQUEST_BYTES) {
        json_response(['error' => 'Request is too large.'], 413);
    }
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_response(['error' => 'Invalid JSON request.'], 400);
    }
    return $data;
}

function input_string(array $input, string $key, string $default = ''): string {
    return isset($input[$key]) && is_string($input[$key]) ? $input[$key] : $default;
}

function normalized_job_id($value): ?string {
    return is_string($value) && preg_match('/^[a-f0-9]{32}$/', $value) ? $value : null;
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validate_smtp_settings(array $settings): ?string {
    $host = trim((string)($settings['smtp_host'] ?? ''));
    $port = (int)($settings['smtp_port'] ?? 0);
    $user = trim((string)($settings['smtp_user'] ?? ''));

    if ($host === '' || $user === '' || empty($settings['smtp_pass'])) {
        return 'Host, email address, and app password are all required.';
    }
    if (!filter_var($user, FILTER_VALIDATE_EMAIL)) {
        return 'The SMTP username must be a valid email address.';
    }
    if ($port < 1 || $port > 65535) {
        return 'SMTP port must be between 1 and 65535.';
    }
    if (strcasecmp($host, 'localhost') === 0 || str_ends_with(strtolower($host), '.local')) {
        return 'Local SMTP hosts are not allowed.';
    }
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $publicIp = filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
        if ($publicIp === false) {
            return 'Private or reserved SMTP addresses are not allowed.';
        }
    } elseif (!preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i', $host)) {
        return 'SMTP host is not valid.';
    }
    return null;
}

// --- Saved recipient lists (per user) ---

function safe_username_slug(string $username): string {
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);
}

function private_user_file(string $subdirectory, string $username): string {
    $dir = DATA_DIR . '/' . $subdirectory;
    ensure_directory($dir);
    return $dir . '/user_' . hash('sha256', $username) . '.json';
}

function migrate_data_storage_v2(): void {
    $marker = DATA_DIR . '/.storage_v2';
    if (file_exists($marker)) {
        return;
    }

    $users = read_json_file(USERS_FILE, []);
    foreach (['lists', 'smtp', 'templates'] as $subdirectory) {
        $legacyGroups = [];
        foreach (array_keys($users) as $username) {
            $legacyGroups[safe_username_slug((string)$username)][] = (string)$username;
        }
        foreach ($legacyGroups as $legacySlug => $usernames) {
            $legacy = DATA_DIR . '/' . $subdirectory . '/' . $legacySlug . '.json';
            if (!file_exists($legacy)) {
                continue;
            }
            $legacyData = read_json_file($legacy, []);
            foreach ($usernames as $username) {
                $destination = private_user_file($subdirectory, $username);
                if (!file_exists($destination)) {
                    write_json_file($destination, $legacyData);
                }
            }
            // Every account in a legacy collision now has an independent copy.
            unlink($legacy);
            @unlink(json_lock_file($legacy));
        }
    }

    // Older job records duplicated the SMTP password. New send requests load
    // credentials from the owner's settings file instead.
    $migrationComplete = true;
    foreach (glob(JOBS_DIR . '/*.json') as $jobFile) {
        $fp = fopen($jobFile, 'r+');
        if ($fp === false || !flock($fp, LOCK_EX)) {
            if (is_resource($fp)) {
                fclose($fp);
            }
            $migrationComplete = false;
            continue;
        }
        $job = json_decode(stream_get_contents($fp), true);
        if (is_array($job)) {
            foreach (['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'use_ssl', 'from_name'] as $key) {
                unset($job[$key]);
            }
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($job, JSON_UNESCAPED_SLASHES));
            fflush($fp);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    if ($migrationComplete) {
        file_put_contents($marker, date('c'), LOCK_EX);
        chmod($marker, 0600);
    }
}

function recipient_lists_file(string $username): string {
    return private_user_file('lists', $username);
}

function load_recipient_lists(string $username): array {
    return read_json_file(recipient_lists_file($username), []);
}

function save_recipient_lists(string $username, array $lists): void {
    write_json_file(recipient_lists_file($username), $lists);
}

function upsert_recipient_list(string $username, string $name, string $rawText): void {
    update_json_file(recipient_lists_file($username), function (array $lists) use ($name, $rawText): array {
        $lists[$name] = $rawText;
        return $lists;
    });
}

function remove_recipient_list(string $username, string $name): bool {
    $removed = false;
    update_json_file(recipient_lists_file($username), function (array $lists) use ($name, &$removed): array {
        if (array_key_exists($name, $lists)) {
            unset($lists[$name]);
            $removed = true;
        }
        return $lists;
    });
    return $removed;
}

// --- Saved SMTP settings (per user) ---

function smtp_settings_file(string $username): string {
    return private_user_file('smtp', $username);
}

function load_smtp_settings(string $username): ?array {
    $file = smtp_settings_file($username);
    if (!file_exists($file)) {
        return null;
    }
    return read_json_file($file, []);
}

function save_smtp_settings(string $username, array $settings): void {
    write_json_file(smtp_settings_file($username), $settings);
}

function public_smtp_settings(?array $settings): ?array {
    if (!$settings) {
        return null;
    }
    unset($settings['smtp_pass']);
    $settings['has_password'] = true;
    return $settings;
}

// --- Suppression list and recipient auditing ---

function suppressions_file(string $username): string {
    return private_user_file('suppressions', $username);
}

function load_suppressions(string $username): array {
    return read_json_file(suppressions_file($username), []);
}

function add_suppression(string $username, string $email, string $reason = 'manual'): void {
    $normalized = strtolower(trim($email));
    if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('A valid email address is required.');
    }
    update_json_file(suppressions_file($username), function (array $suppressions) use ($normalized, $email, $reason): array {
        $suppressions[$normalized] = [
            'email' => trim($email),
            'reason' => $reason,
            'created_at' => date('c'),
        ];
        return $suppressions;
    });
}

function remove_suppression(string $username, string $email): bool {
    $normalized = strtolower(trim($email));
    $removed = false;
    update_json_file(suppressions_file($username), function (array $suppressions) use ($normalized, &$removed): array {
        if (array_key_exists($normalized, $suppressions)) {
            unset($suppressions[$normalized]);
            $removed = true;
        }
        return $suppressions;
    });
    return $removed;
}

function previously_contacted_emails(string $username): array {
    $contacted = [];
    foreach (glob(JOBS_DIR . '/*.json') as $file) {
        $fp = fopen($file, 'r');
        if ($fp === false || !flock($fp, LOCK_SH)) {
            if (is_resource($fp)) fclose($fp);
            continue;
        }
        $job = json_decode(stream_get_contents($fp), true);
        flock($fp, LOCK_UN);
        fclose($fp);
        if (!is_array($job) || ($job['owner'] ?? null) !== $username || !empty($job['dry_run'])) {
            continue;
        }
        foreach (is_array($job['results'] ?? null) ? $job['results'] : [] as $result) {
            if (($result['status'] ?? null) === 'sent' && is_string($result['email'] ?? null)) {
                $contacted[strtolower($result['email'])] = true;
            }
        }
    }
    return $contacted;
}

function audit_recipients_for_user(string $rawText, string $username, bool $excludePreviouslyContacted = false): array {
    $parsed = parse_recipients_csv($rawText);
    if (!empty($parsed['error']) || empty($parsed['has_email_column'])) {
        return $parsed;
    }
    $suppressions = load_suppressions($username);
    $sharedSuppressions = load_suppression_list();
    $contacted = previously_contacted_emails($username);
    $approved = [];
    $suppressedCount = 0;
    $previouslyContactedCount = 0;
    $exclusions = $parsed['exclusions'] ?? [];

    foreach ($parsed['rows'] as $row) {
        $email = strtolower((string)$row['email']);
        if (isset($suppressions[$email]) || is_suppressed($email, $sharedSuppressions)) {
            $suppressedCount++;
            if (count($exclusions) < MAX_RECIPIENTS) {
                $exclusions[] = ['email' => $row['email'], 'reason' => 'suppressed'];
            }
            continue;
        }
        if (isset($contacted[$email])) {
            $previouslyContactedCount++;
            if ($excludePreviouslyContacted) {
                if (count($exclusions) < MAX_RECIPIENTS) {
                    $exclusions[] = ['email' => $row['email'], 'reason' => 'previously-contacted'];
                }
                continue;
            }
        }
        $approved[] = $row;
    }
    $parsed['rows'] = $approved;
    $parsed['suppressed_count'] = $suppressedCount;
    $parsed['previously_contacted_count'] = $previouslyContactedCount;
    $parsed['exclusions'] = $exclusions;
    return $parsed;
}

function app_secret(): string {
    $file = DATA_DIR . '/app_secret.key';
    $fp = fopen($file, 'c+');
    if ($fp === false) {
        throw new RuntimeException('Could not initialize the application secret.');
    }
    try {
        if (!flock($fp, LOCK_EX)) {
            throw new RuntimeException('Could not initialize the application secret.');
        }
        $secret = trim(stream_get_contents($fp));
        if (strlen($secret) < 32) {
            $secret = bin2hex(random_bytes(32));
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $secret);
            fflush($fp);
            chmod($file, 0600);
        }
        return $secret;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

function base64url_encode(string $value): string {
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base64url_decode(string $value): string|false {
    return base64_decode(strtr($value, '-_', '+/'), true);
}

function unsubscribe_token(string $username, string $email): string {
    $payload = base64url_encode(json_encode([
        'o' => hash('sha256', $username),
        'e' => strtolower($email),
    ], JSON_THROW_ON_ERROR));
    $signature = base64url_encode(hash_hmac('sha256', $payload, app_secret(), true));
    return $payload . '.' . $signature;
}

function verify_unsubscribe_token(string $token): ?array {
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return null;
    [$payload, $signature] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', $payload, app_secret(), true));
    if (!hash_equals($expected, $signature)) return null;
    $decoded = base64url_decode($payload);
    $data = $decoded === false ? null : json_decode($decoded, true);
    if (!is_array($data)
        || !is_string($data['o'] ?? null)
        || !is_string($data['e'] ?? null)
        || !filter_var($data['e'], FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    $username = null;
    foreach (array_keys(load_users()) as $candidate) {
        if (hash_equals($data['o'], hash('sha256', (string)$candidate))) {
            $username = (string)$candidate;
            break;
        }
    }
    return $username === null ? null : ['username' => $username, 'email' => strtolower($data['e'])];
}

function dispatch_base_url(): string {
    $configured = trim((string)getenv('DISPATCH_BASE_URL'));
    if ($configured !== '') return rtrim($configured, '/');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[a-z0-9.-]+(?::[0-9]{1,5})?$/i', $host)) {
        $host = 'localhost';
    }
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $basePath = str_contains($script, '/api/')
        ? substr($script, 0, strpos($script, '/api/'))
        : rtrim(dirname($script), '/.');
    return ($https ? 'https' : 'http') . '://' . $host . $basePath;
}

function unsubscribe_url(string $username, string $email): string {
    return dispatch_base_url() . '/unsubscribe.php?token=' . rawurlencode(unsubscribe_token($username, $email));
}

// --- SMTP and message rendering ---

function configured_mailer(array $smtpSettings): \PHPMailer\PHPMailer\PHPMailer {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtpSettings['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtpSettings['smtp_user'];
    $mail->Password = $smtpSettings['smtp_pass'];
    $mail->Port = (int)$smtpSettings['smtp_port'];
    $mail->Timeout = 30;
    $mail->Timelimit = 30;
    $mail->SMTPSecure = !empty($smtpSettings['use_ssl'])
        ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    return $mail;
}

function test_smtp_connection(array $smtpSettings): void {
    $mail = configured_mailer($smtpSettings);
    try {
        if (!$mail->smtpConnect()) {
            throw new RuntimeException('SMTP connection failed.');
        }
    } finally {
        $mail->smtpClose();
    }
}

function send_dispatch_email(
    array $smtpSettings,
    string $toAddress,
    string $subject,
    string $mainBodyText,
    string $footerText,
    ?string $unsubscribeUrl = null
): void {
    $mail = configured_mailer($smtpSettings);
    $mail->setFrom($smtpSettings['smtp_user'], $smtpSettings['from_name'] ?? '');
    $mail->addAddress($toAddress);
    $mail->isHTML(true);
    $mail->Subject = $subject;

    $logoCid = 'alfadevslogo';
    $logoPath = BASE_DIR . '/static/logo.png';
    $hasLogo = file_exists($logoPath);
    if ($hasLogo) {
        $mail->addEmbeddedImage($logoPath, $logoCid, 'logo.png');
    }

    $mail->Body = build_dispatch_email_html(
        $mainBodyText,
        $footerText,
        $hasLogo ? $logoCid : null,
        $unsubscribeUrl
    );
    $plainText = $mainBodyText . ($footerText !== '' ? "\n\n" . $footerText : '');
    if ($unsubscribeUrl) {
        $plainText .= "\n\nUnsubscribe: " . $unsubscribeUrl;
    }
    $mail->AltBody = $plainText;
    $mail->send();
}

function build_dispatch_email_html(
    string $mainBodyText,
    string $footerText,
    ?string $logoCid,
    ?string $unsubscribeUrl = null
): string {
    $mainBodyHtml = nl2br(htmlspecialchars($mainBodyText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $footerHtml = $footerText !== ''
        ? nl2br(htmlspecialchars($footerText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))
        : '';
    if ($unsubscribeUrl) {
        $safeUrl = htmlspecialchars($unsubscribeUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $footerHtml .= ($footerHtml !== '' ? '<br>' : '')
            . '<a href="' . $safeUrl . '" style="color:#0b3f8c;text-decoration:underline;">Unsubscribe from future emails</a>';
    }

    $logoBlock = $logoCid
        ? '<img src="cid:' . htmlspecialchars($logoCid, ENT_QUOTES, 'UTF-8') . '" alt="AlfaDevs" width="120" style="display:block;border:0;outline:none;text-decoration:none;height:auto;">'
        : '<span style="font-size:18px;font-weight:700;color:#0c1f3d;">AlfaDevs</span>';
    $footerBlock = $footerHtml !== ''
        ? '<tr><td style="padding:18px 32px;background:#f7fafd;border-top:1px solid #dbe8f6;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#5c7089;">' . $footerHtml . '</td></tr>'
        : '';

    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0; padding:0; background-color:#eef6ff;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef6ff; padding:32px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 8px 24px rgba(11,63,140,0.08);">
        <tr><td style="padding:24px 32px; border-bottom:1px solid #dbe8f6;">{$logoBlock}</td></tr>
        <tr><td style="padding:32px; font-family:Arial,Helvetica,sans-serif; font-size:15px; line-height:1.7; color:#0c1f3d;">{$mainBodyHtml}</td></tr>
        {$footerBlock}
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

// --- Saved message templates (per user) ---

function message_templates_file(string $username): string {
    return private_user_file('templates', $username);
}

function load_message_templates(string $username): array {
    $file = message_templates_file($username);
    if (!file_exists($file)) {
        return [];
    }
    return read_json_file($file, []);
}

function save_message_templates(string $username, array $templates): void {
    write_json_file(message_templates_file($username), $templates);
}

function upsert_message_template(string $username, string $name, array $template): void {
    update_json_file(message_templates_file($username), function (array $templates) use ($name, $template): array {
        $templates[$name] = $template;
        return $templates;
    });
}

function remove_message_template(string $username, string $name): bool {
    $removed = false;
    update_json_file(message_templates_file($username), function (array $templates) use ($name, &$removed): array {
        if (array_key_exists($name, $templates)) {
            unset($templates[$name]);
            $removed = true;
        }
        return $templates;
    });
    return $removed;
}

// --- Shared suppression / opt-out list (team-wide, not per-user) ---

function suppression_list_file(): string {
    $dir = DATA_DIR . '/suppression';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    return $dir . '/list.json';
}

function load_suppression_list(): array {
    $file = suppression_list_file();
    if (!file_exists($file)) {
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function is_suppressed(string $email, ?array $list = null): bool {
    $email = strtolower(trim($email));
    if ($email === '') {
        return false;
    }
    if ($list === null) {
        $list = load_suppression_list();
    }
    return isset($list[$email]);
}

/**
 * Add an email to the shared suppression list. Locks the file so
 * concurrent adds from different team members don't clobber each other.
 */
function add_suppressed_email(string $email, string $addedBy, string $reason = ''): void {
    $email = strtolower(trim($email));
    $file = suppression_list_file();
    if (!file_exists($file)) {
        file_put_contents($file, '{}');
    }
    $fp = fopen($file, 'r+');
    flock($fp, LOCK_EX);
    $list = json_decode(stream_get_contents($fp), true);
    if (!is_array($list)) {
        $list = [];
    }
    $list[$email] = [
        'added_by' => $addedBy,
        'added_at' => date('c'),
        'reason' => $reason,
    ];
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($list, JSON_PRETTY_PRINT));
    flock($fp, LOCK_UN);
    fclose($fp);
}

function remove_suppressed_email(string $email): void {
    $email = strtolower(trim($email));
    $file = suppression_list_file();
    if (!file_exists($file)) {
        return;
    }
    $fp = fopen($file, 'r+');
    flock($fp, LOCK_EX);
    $list = json_decode(stream_get_contents($fp), true);
    if (!is_array($list)) {
        $list = [];
    }
    unset($list[$email]);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($list, JSON_PRETTY_PRINT));
    flock($fp, LOCK_UN);
    fclose($fp);
}

// --- Campaign history (derived from job files already on disk) ---

function list_user_jobs(string $username): array {
    $jobs = [];
    foreach (glob(JOBS_DIR . '/*.json') as $file) {
        $fp = fopen($file, 'r+');
        if ($fp === false || !flock($fp, LOCK_EX)) {
            if (is_resource($fp)) {
                fclose($fp);
            }
            continue;
        }
        $job = json_decode(stream_get_contents($fp), true);
        if (!is_array($job) || ($job['owner'] ?? null) !== $username) {
            flock($fp, LOCK_UN);
            fclose($fp);
            continue;
        }
        $hadLegacyCredentials = false;
        foreach (['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'use_ssl', 'from_name'] as $key) {
            if (array_key_exists($key, $job)) {
                unset($job[$key]);
                $hadLegacyCredentials = true;
            }
        }
        if ($hadLegacyCredentials) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($job, JSON_UNESCAPED_SLASHES));
            fflush($fp);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        $jobId = basename($file, '.json');
        $jobs[] = [
            'job_id' => $jobId,
            'subject' => is_string($job['subject'] ?? null) ? $job['subject'] : '',
            'total' => (int)($job['total'] ?? 0),
            'sent' => (int)($job['sent'] ?? 0),
            'failed' => (int)($job['failed'] ?? 0),
            'skipped' => (int)($job['skipped'] ?? 0)
                + (int)($job['suppressed'] ?? 0)
                + (int)($job['skipped_suppressed'] ?? 0),
            'status' => in_array($job['status'] ?? '', ['queued', 'sending', 'done', 'stopped', 'error'], true)
                ? $job['status']
                : 'unknown',
            'dry_run' => !empty($job['dry_run']),
            'created_at' => is_string($job['created_at'] ?? null) ? $job['created_at'] : '',
        ];
    }
    usort($jobs, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
    return $jobs;
}

function list_user_active_jobs(string $username): array {
    $jobs = [];
    foreach (glob(JOBS_DIR . '/*.json') as $file) {
        $fp = fopen($file, 'r');
        if ($fp === false || !flock($fp, LOCK_SH)) {
            if (is_resource($fp)) {
                fclose($fp);
            }
            continue;
        }
        $job = json_decode(stream_get_contents($fp), true);
        flock($fp, LOCK_UN);
        fclose($fp);

        if (!is_array($job)
            || ($job['owner'] ?? null) !== $username
            || !empty($job['stop_flag'])
            || !in_array($job['status'] ?? '', ['queued', 'sending'], true)
            || (int)($job['index'] ?? 0) >= (int)($job['total'] ?? 0)) {
            continue;
        }
        $jobs[] = [
            'job_id' => basename($file, '.json'),
            'subject' => is_string($job['subject'] ?? null) ? $job['subject'] : '',
            'total' => (int)($job['total'] ?? 0),
            'sent' => (int)($job['sent'] ?? 0),
            'failed' => (int)($job['failed'] ?? 0),
            'skipped' => (int)($job['skipped'] ?? 0),
            'progress' => (int)($job['index'] ?? 0),
            'status' => $job['status'],
            'dry_run' => !empty($job['dry_run']),
            'delay_seconds' => max(0.0, min(3600.0, (float)($job['delay_seconds'] ?? 5))),
            'created_at' => is_string($job['created_at'] ?? null) ? $job['created_at'] : '',
            'results' => array_slice(is_array($job['results'] ?? null) ? $job['results'] : [], -20),
        ];
    }
    usort($jobs, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
    return $jobs;
}

function load_user_job(string $username, string $jobId): ?array {
    $normalized = normalized_job_id($jobId);
    if ($normalized === null) return null;
    $file = JOBS_DIR . '/' . $normalized . '.json';
    if (!file_exists($file)) return null;
    $fp = fopen($file, 'r');
    if ($fp === false || !flock($fp, LOCK_SH)) {
        if (is_resource($fp)) fclose($fp);
        return null;
    }
    $job = json_decode(stream_get_contents($fp), true);
    flock($fp, LOCK_UN);
    fclose($fp);
    return is_array($job) && ($job['owner'] ?? null) === $username ? $job : null;
}
