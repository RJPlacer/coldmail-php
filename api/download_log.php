<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$jobId = preg_replace('/[^a-f0-9]/', '', $_GET['job_id'] ?? '');
$jobFile = JOBS_DIR . "/$jobId.json";

if ($jobId === '' || !file_exists($jobFile)) {
    json_response(['error' => 'Job not found.'], 404);
}

$job = json_decode(file_get_contents($jobFile), true);

if ($job['owner'] !== current_username()) {
    json_response(['error' => 'Not your job.'], 403);
}

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="log_' . $jobId . '.json"');
echo json_encode($job['results'], JSON_PRETTY_PRINT);
