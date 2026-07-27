<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$jobId = preg_replace('/[^a-f0-9]/', '', $input['job_id'] ?? '');
$jobFile = JOBS_DIR . "/$jobId.json";

if ($jobId === '' || !file_exists($jobFile)) {
    json_response(['error' => 'Job not found.'], 404);
}

$fp = fopen($jobFile, 'r+');
flock($fp, LOCK_EX);
$job = json_decode(stream_get_contents($fp), true);

if ($job['owner'] !== current_username()) {
    flock($fp, LOCK_UN);
    fclose($fp);
    json_response(['error' => 'Not your job.'], 403);
}

$job['stop_flag'] = true;
ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($job));
flock($fp, LOCK_UN);
fclose($fp);

json_response(['ok' => true]);
