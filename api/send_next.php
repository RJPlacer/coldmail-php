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
$mainBodyText = render_merge_tags($job['body'], $row);
$footerText = '';
if (!empty($job['unsubscribe_line'])) {
    $senderFields = [
        'smtp_user' => $job['smtp_user'],
        'from_name' => $job['from_name'] ?? '',
    ];
    $footerText = render_merge_tags($job['unsubscribe_line'], array_merge($senderFields, $row));
}
$bodyText = $mainBodyText . ($footerText !== '' ? "\n\n" . $footerText : '');

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
        send_single_email($job, $toAddr, $subject, $mainBodyText, $footerText, $bodyText);
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

function send_single_email(array $job, string $toAddr, string $subject, string $mainBodyText, string $footerText, string $plainTextBody): void {
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

    // Embed the logo directly in the email (not linked from a URL) so it
    // displays correctly for recipients no matter where the app is hosted.
    $logoCid = 'alfadevslogo';
    $logoPath = dirname(__DIR__) . '/static/logo.png';
    $hasLogo = file_exists($logoPath);
    if ($hasLogo) {
        $mail->addEmbeddedImage($logoPath, $logoCid, 'logo.png');
    }

    $mail->Body = build_branded_email_html($mainBodyText, $footerText, $hasLogo ? $logoCid : null);
    $mail->AltBody = $plainTextBody;

    $mail->send();
}

/**
 * Wrap the message body in a simple, email-client-safe branded template.
 * Uses inline styles and a table layout since most email clients strip
 * <style> blocks and don't support modern CSS.
 */
function build_branded_email_html(string $mainBodyText, string $footerText, ?string $logoCid): string {
    $mainBodyHtml = nl2br(htmlspecialchars($mainBodyText));
    $footerHtml = $footerText !== '' ? nl2br(htmlspecialchars($footerText)) : '';

    $logoBlock = $logoCid
        ? '<img src="cid:' . $logoCid . '" alt="AlfaDevs" width="120" style="display:block;border:0;outline:none;text-decoration:none;height:auto;">'
        : '<span style="font-size:18px;font-weight:700;color:#0c1f3d;">AlfaDevs</span>';

    $footerBlock = $footerHtml !== ''
        ? '<tr><td style="padding:18px 32px;background:#f7fafd;border-top:1px solid #dbe8f6;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#5c7089;">' . $footerHtml . '</td></tr>'
        : '';

    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0; padding:0; background-color:#eef6ff;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef6ff; padding:32px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 8px 24px rgba(11,63,140,0.08);">
          <tr>
            <td style="padding:24px 32px; border-bottom:1px solid #dbe8f6;">
              {$logoBlock}
            </td>
          </tr>
          <tr>
            <td style="padding:32px; font-family:Arial,Helvetica,sans-serif; font-size:15px; line-height:1.7; color:#0c1f3d;">
              {$mainBodyHtml}
            </td>
          </tr>
          {$footerBlock}
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
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
