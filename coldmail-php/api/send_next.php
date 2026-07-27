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
$bodyText = render_merge_tags($job['body'], $row);
if (!empty($job['unsubscribe_line'])) {
    $senderFields = [
        'smtp_user' => $job['smtp_user'],
        'from_name' => $job['from_name'] ?? '',
    ];
    $bodyText .= "\n\n" . render_merge_tags($job['unsubscribe_line'], array_merge($senderFields, $row));
}
$bodyHtml = nl2br(htmlspecialchars($bodyText));

$entry = [
    'email' => $toAddr,
    'subject' => $subject,
    'status' => null,
    'error' => null,
    'timestamp' => date('c'),
];

try {
    if ($job['dry_run']) {
        $entry['status'] = 'dry-run-ok';
    } else {
        send_single_email($job, $toAddr, $subject, $bodyHtml, $bodyText);
        $entry['status'] = 'sent';
    }
    $job['sent'] += 1;
} catch (\Throwable $e) {
    $entry['status'] = 'failed';
    $entry['error'] = $e->getMessage();
    $job['failed'] += 1;
}

$job['results'][] = $entry;
$job['index'] += 1;

if ($job['index'] >= $job['total']) {
    $job['status'] = 'done';
}

write_job($fp, $job);
json_response(summarize($job));

// --- helpers local to this endpoint ---

function send_single_email(array $job, string $toAddr, string $subject, string $bodyHtml, string $bodyText): void {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $job['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $job['smtp_user'];
    $mail->Password = $job['smtp_pass'];
    $mail->Port = $job['smtp_port'];
    $mail->SMTPSecure = $job['use_ssl']
        ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

    $mail->setFrom($job['smtp_user'], $job['from_name'] ?? '');
    $mail->addAddress($toAddr);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $bodyHtml;
    $mail->AltBody = $bodyText;

    $mail->send();
}

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
        'progress' => $job['index'],
        'results' => array_slice($job['results'], -20),
        'error' => $job['error'] ?? null,
    ];
}
