<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$jobId = normalized_job_id($_GET['job_id'] ?? null);
$jobId = $jobId ?? '';
$job = $jobId === '' ? null : load_user_job(current_username(), $jobId);
if (!$job) {
    json_response(['error' => 'Job not found.'], 404);
}

$results = is_array($job['results'] ?? null) ? $job['results'] : [];
$format = $_GET['format'] ?? 'json';

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="campaign_' . $jobId . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['timestamp', 'email', 'status', 'subject', 'error'], ',', '"', '');
    foreach ($results as $result) {
        $row = [];
        foreach (['timestamp', 'email', 'status', 'subject', 'error'] as $key) {
            $value = (string)($result[$key] ?? '');
            if (preg_match('/^[=+\-@]/', $value)) {
                $value = "'" . $value;
            }
            $row[] = $value;
        }
        fputcsv($output, $row, ',', '"', '');
    }
    fclose($output);
    exit;
}

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="log_' . $jobId . '.json"');
echo json_encode($results, JSON_PRETTY_PRINT);
