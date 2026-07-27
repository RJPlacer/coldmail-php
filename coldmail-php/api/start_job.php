<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$config = json_input();

$parsed = parse_recipients_csv($config['raw_recipients'] ?? '');
$rows = $parsed['rows'];

if (empty($rows)) {
    json_response(['error' => 'No valid recipients found.'], 400);
}

if (empty($config['smtp_host']) || empty($config['smtp_user']) || empty($config['smtp_pass'])) {
    json_response(['error' => 'Missing SMTP configuration.'], 400);
}

if (empty($config['subject']) || empty($config['body'])) {
    json_response(['error' => 'Subject and body are required.'], 400);
}

$jobId = bin2hex(random_bytes(16));

$job = [
    'smtp_host' => $config['smtp_host'],
    'smtp_port' => (int)$config['smtp_port'],
    'smtp_user' => $config['smtp_user'],
    'smtp_pass' => $config['smtp_pass'],
    'use_ssl' => !empty($config['use_ssl']),
    'from_name' => $config['from_name'] ?? '',
    'subject' => $config['subject'],
    'body' => $config['body'],
    'unsubscribe_line' => $config['unsubscribe_line'] ?? '',
    'dry_run' => !empty($config['dry_run']),
    'recipients' => $rows,
    'index' => 0,
    'sent' => 0,
    'failed' => 0,
    'total' => count($rows),
    'status' => 'queued',
    'stop_flag' => false,
    'results' => [],
    'owner' => current_username(),
    'created_at' => date('c'),
];

file_put_contents(JOBS_DIR . "/$jobId.json", json_encode($job));

json_response(['job_id' => $jobId, 'total' => count($rows)]);
