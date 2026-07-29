<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$jobId = normalized_job_id($input['job_id'] ?? null);
$jobId = $jobId ?? '';
$jobFile = JOBS_DIR . "/$jobId.json";

if ($jobId === '' || !file_exists($jobFile)) {
    json_response(['error' => 'Job not found.'], 404);
}

$fp = fopen($jobFile, 'r+');
if ($fp === false || !flock($fp, LOCK_EX)) {
    json_response(['error' => 'Could not open job.'], 500);
}
$job = json_decode(stream_get_contents($fp), true);

if (!is_array($job)) {
    flock($fp, LOCK_UN);
    fclose($fp);
    json_response(['error' => 'Job data is corrupted.'], 500);
}
if (($job['owner'] ?? null) !== current_username()) {
    flock($fp, LOCK_UN);
    fclose($fp);
    json_response(['error' => 'Not your job.'], 403);
}

$smtpSettings = load_smtp_settings(current_username());
if (!$smtpSettings || validate_smtp_settings($smtpSettings) !== null) {
    flock($fp, LOCK_UN);
    fclose($fp);
    json_response(['error' => 'Your saved SMTP settings are missing or invalid.'], 400);
}

// Remove credentials written by older versions whenever a legacy job is used.
foreach (['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'use_ssl', 'from_name'] as $legacyKey) {
    unset($job[$legacyKey]);
}

// Job already finished/stopped — nothing to do.
if (in_array($job['status'], ['done', 'stopped', 'error'])) {
    flock($fp, LOCK_UN);
    fclose($fp);
    json_response(summarize($job));
}

if ($job['stop_flag']) {
    $job['status'] = 'stopped';
    write_job($fp, $job);
    json_response(summarize($job));
}

if ($job['index'] >= $job['total']) {
    $job['status'] = 'done';
    write_job($fp, $job);
    json_response(summarize($job));
}

$job['status'] = 'sending';
$row = $job['recipients'][$job['index']];
$toAddr = $row['email'] ?? '';

$subject = render_merge_tags($job['subject'], $row);
$mainBodyText = render_merge_tags($job['body'], $row);
$footerText = '';
if (!empty($job['unsubscribe_line'])) {
    $senderFields = [
        'smtp_user' => $smtpSettings['smtp_user'],
        'from_name' => $smtpSettings['from_name'] ?? '',
    ];
    $footerText = render_merge_tags($job['unsubscribe_line'], array_merge($senderFields, $row));
}
$unsubscribeUrl = unsubscribe_url($job['owner'], $toAddr);

$entry = [
    'email' => $toAddr,
    'subject' => $subject,
    'status' => null,
    'error' => null,
    'timestamp' => date('c'),
];

$isSuppressed = isset(load_suppressions($job['owner'])[strtolower($toAddr)])
    || is_suppressed($toAddr);
if ($isSuppressed) {
    $entry['status'] = 'suppressed';
    $job['skipped'] = (int)($job['skipped'] ?? 0) + 1;
} else {
    try {
        if ($job['dry_run']) {
            $entry['status'] = 'dry-run-ok';
        } else {
            send_dispatch_email($smtpSettings, $toAddr, $subject, $mainBodyText, $footerText, $unsubscribeUrl);
            $entry['status'] = 'sent';
        }
        $job['sent'] += 1;
    } catch (\Throwable $e) {
        $entry['status'] = 'failed';
        $entry['error'] = $e->getMessage();
        $job['failed'] += 1;
    }
}

$job['results'][] = $entry;
$job['index'] += 1;

if ($job['index'] >= $job['total']) {
    $job['status'] = 'done';
}

write_job($fp, $job);
json_response(summarize($job));

// --- helpers local to this endpoint ---

function write_job($fp, array $job): void {
    // job may still hold recipients array; keep it (needed for subsequent calls)
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($job));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function summarize(array $job): array {
    return [
        'status' => $job['status'],
        'total' => $job['total'],
        'sent' => $job['sent'],
        'failed' => $job['failed'],
        'skipped' => (int)($job['skipped'] ?? 0)
            + (int)($job['suppressed'] ?? 0)
            + (int)($job['skipped_suppressed'] ?? 0),
        'progress' => $job['index'],
        'results' => array_slice($job['results'], -20),
        'error' => $job['error'] ?? null,
    ];
}
