<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$sourceJobId = normalized_job_id($input['job_id'] ?? null);
$source = $sourceJobId ? load_user_job(current_username(), $sourceJobId) : null;
if (!$source) {
    json_response(['error' => 'Campaign not found.'], 404);
}

$failedEmails = [];
foreach (is_array($source['results'] ?? null) ? $source['results'] : [] as $result) {
    if (($result['status'] ?? null) === 'failed' && is_string($result['email'] ?? null)) {
        $failedEmails[strtolower($result['email'])] = true;
    }
}
$recipients = array_values(array_filter(
    is_array($source['recipients'] ?? null) ? $source['recipients'] : [],
    fn($row): bool => is_array($row) && isset($failedEmails[strtolower((string)($row['email'] ?? ''))])
));
if (!$recipients) {
    json_response(['error' => 'This campaign has no failed recipients to retry.'], 400);
}

$jobId = bin2hex(random_bytes(16));
$job = [
    'subject' => $source['subject'] ?? '',
    'body' => $source['body'] ?? '',
    'unsubscribe_line' => $source['unsubscribe_line'] ?? '',
    'delay_seconds' => (float)($source['delay_seconds'] ?? 5),
    'dry_run' => false,
    'recipients' => $recipients,
    'index' => 0,
    'sent' => 0,
    'failed' => 0,
    'skipped' => 0,
    'total' => count($recipients),
    'status' => 'queued',
    'stop_flag' => false,
    'results' => [],
    'owner' => current_username(),
    'created_at' => date('c'),
    'retry_of' => $sourceJobId,
];
write_json_file(JOBS_DIR . '/' . $jobId . '.json', $job);
json_response(['ok' => true, 'job_id' => $jobId, 'total' => count($recipients)]);
