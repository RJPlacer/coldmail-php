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

foreach (['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'use_ssl', 'from_name'] as $legacyKey) {
    unset($job[$legacyKey]);
}
$job['stop_flag'] = true;
$job['status'] = 'stopped';
$job['stopped_at'] = date('c');
ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($job));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

json_response([
    'ok' => true,
    'status' => 'stopped',
    'job_id' => $jobId,
]);
