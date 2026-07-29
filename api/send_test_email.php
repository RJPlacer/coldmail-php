<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$settings = load_smtp_settings(current_username());
if (!$settings || ($error = validate_smtp_settings($settings)) !== null) {
    json_response(['error' => $error ?? 'SMTP settings are missing.'], 400);
}

$subjectTemplate = trim(input_string($input, 'subject'));
$bodyTemplate = input_string($input, 'body');
$footerTemplate = input_string($input, 'unsubscribe_line');
$testAddress = trim(input_string($input, 'test_email', $settings['smtp_user']));
if ($subjectTemplate === '' || trim($bodyTemplate) === '') {
    json_response(['error' => 'Subject and body are required.'], 400);
}
if (!filter_var($testAddress, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Enter a valid test recipient address.'], 400);
}

$parsed = parse_recipients_csv(input_string($input, 'raw_recipients'));
$row = $parsed['rows'][0] ?? ['email' => $testAddress];
$senderFields = ['smtp_user' => $settings['smtp_user'], 'from_name' => $settings['from_name'] ?? ''];
$subject = '[TEST] ' . render_merge_tags($subjectTemplate, $row);
$body = render_merge_tags($bodyTemplate, $row);
$footer = render_merge_tags($footerTemplate, array_merge($senderFields, $row));

try {
    send_dispatch_email($settings, $testAddress, $subject, $body, $footer, null);
    json_response(['ok' => true, 'message' => 'Test email sent to ' . $testAddress . '.']);
} catch (Throwable $error) {
    json_response(['error' => 'Test email failed: ' . $error->getMessage()], 400);
}
