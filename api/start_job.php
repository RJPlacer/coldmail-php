<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$config = json_input();

$excludePreviously = !empty($config['exclude_previously_contacted']);
$parsed = audit_recipients_for_user(
    input_string($config, 'raw_recipients'),
    current_username(),
    $excludePreviously
);
$rows = $parsed['rows'];

if (!empty($parsed['error'])) {
    json_response(['error' => $parsed['error']], 413);
}
if (empty($rows)) {
    json_response(['error' => 'No eligible recipients remain after validation and suppression filters.'], 400);
}

$smtpSettings = load_smtp_settings(current_username());
if (!$smtpSettings || validate_smtp_settings($smtpSettings) !== null) {
    json_response(['error' => 'Your saved SMTP settings are missing or invalid.'], 400);
}

$subject = is_string($config['subject'] ?? null) ? trim($config['subject']) : '';
$body = is_string($config['body'] ?? null) ? $config['body'] : '';
$unsubscribeLine = is_string($config['unsubscribe_line'] ?? null) ? $config['unsubscribe_line'] : '';
$delaySeconds = filter_var($config['delay_seconds'] ?? 5, FILTER_VALIDATE_FLOAT);
$delaySeconds = $delaySeconds === false ? 5.0 : max(0.0, min(3600.0, (float)$delaySeconds));
if ($subject === '' || trim($body) === '') {
    json_response(['error' => 'Subject and body are required.'], 400);
}
if (strlen($subject) > MAX_SUBJECT_BYTES || strlen($body) > MAX_BODY_BYTES) {
    json_response(['error' => 'Subject or message body is too large.'], 413);
}

$jobId = bin2hex(random_bytes(16));

$job = [
    'subject' => $subject,
    'body' => $body,
    'unsubscribe_line' => $unsubscribeLine,
    'delay_seconds' => $delaySeconds,
    'dry_run' => !empty($config['dry_run']),
    'recipients' => $rows,
    'index' => 0,
    'sent' => 0,
    'failed' => 0,
    'skipped' => 0,
    'total' => count($rows),
    'status' => 'queued',
    'stop_flag' => false,
    'results' => [],
    'owner' => current_username(),
    'created_at' => date('c'),
];

write_json_file(JOBS_DIR . "/$jobId.json", $job);

json_response([
    'job_id' => $jobId,
    'total' => count($rows),
    'excluded' => count($parsed['exclusions'] ?? []),
]);
