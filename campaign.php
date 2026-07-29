<?php
require_once __DIR__ . '/config.php';
require_login(false);
$username = current_username();
$jobId = normalized_job_id($_GET['job_id'] ?? null);
$job = $jobId ? load_user_job($username, $jobId) : null;
if (!$job) {
    http_response_code(404);
    exit('Campaign not found.');
}
$results = is_array($job['results'] ?? null) ? $job['results'] : [];
$failedCount = count(array_filter($results, fn($result) => ($result['status'] ?? null) === 'failed'));
function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Campaign Details — Dispatch</title>
<style>
  *{box-sizing:border-box} body{margin:0;background:#eef6ff;color:#0c1f3d;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
  .shell{max-width:1000px;margin:auto;padding:30px 22px 70px}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
  a{color:#0b3f8c;font-weight:650;text-decoration:none}.card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 12px 40px rgba(11,63,140,.08);margin-bottom:18px}
  h1{font-size:24px;margin:0 0 6px}.muted{color:#5c7089}.grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-top:18px}
  .stat{border:1px solid #dbe8f6;border-radius:10px;padding:13px}.stat strong{display:block;font-size:22px}.stat span{font-size:11px;color:#5c7089;text-transform:uppercase}
  .actions{display:flex;gap:9px;flex-wrap:wrap}.btn{border:0;border-radius:9px;padding:10px 15px;background:#1e7fe0;color:#fff;cursor:pointer;font:inherit;font-weight:700}.btn.secondary{background:#fff;color:#0b3f8c;border:1px solid #dbe8f6}
  .table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:12px}th,td{padding:10px;border-bottom:1px solid #dbe8f6;text-align:left}th{color:#5c7089}.error{color:#d24545;max-width:360px;white-space:normal}
  @media(max-width:700px){.grid{grid-template-columns:repeat(2,1fr)}.card{padding:16px}}
</style>
</head>
<body><main class="shell">
  <div class="top"><a href="history.php">← Campaign History</a><a href="index.php">Dispatch</a></div>
  <section class="card">
    <h1><?php echo h($job['subject'] ?? '(no subject)'); ?></h1>
    <div class="muted"><?php echo h($job['created_at'] ?? ''); ?> · <?php echo !empty($job['dry_run']) ? 'Dry run' : 'Live campaign'; ?></div>
    <div class="grid">
      <div class="stat"><strong><?php echo (int)($job['total'] ?? 0); ?></strong><span>Total</span></div>
      <div class="stat"><strong><?php echo (int)($job['sent'] ?? 0); ?></strong><span>Sent</span></div>
      <div class="stat"><strong><?php echo (int)($job['failed'] ?? 0); ?></strong><span>Failed</span></div>
      <div class="stat"><strong><?php echo (int)($job['skipped'] ?? 0); ?></strong><span>Suppressed</span></div>
      <div class="stat"><strong><?php echo h($job['status'] ?? 'unknown'); ?></strong><span>Status</span></div>
    </div>
  </section>
  <section class="card">
    <div class="actions">
      <a class="btn secondary" href="api/download_log.php?job_id=<?php echo h($jobId); ?>&format=csv">Download CSV</a>
      <a class="btn secondary" href="api/download_log.php?job_id=<?php echo h($jobId); ?>">Download JSON</a>
      <?php if ($failedCount): ?><button class="btn" id="retry-btn" onclick="retryFailures()">Retry <?php echo $failedCount; ?> failures</button><?php endif; ?>
    </div>
    <div id="action-status" class="muted" style="margin-top:10px" role="status"></div>
  </section>
  <section class="card table-wrap">
    <table><thead><tr><th>Time</th><th>Email</th><th>Status</th><th>Subject</th><th>Error</th></tr></thead><tbody>
    <?php foreach ($results as $result): ?>
      <tr><td><?php echo h($result['timestamp'] ?? ''); ?></td><td><?php echo h($result['email'] ?? ''); ?></td><td><?php echo h($result['status'] ?? ''); ?></td><td><?php echo h($result['subject'] ?? ''); ?></td><td class="error"><?php echo h($result['error'] ?? ''); ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$results): ?><tr><td colspan="5" class="muted">No results recorded yet.</td></tr><?php endif; ?>
    </tbody></table>
  </section>
</main>
<script>
const CSRF_TOKEN=<?php echo json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
async function retryFailures(){
  const button=document.getElementById('retry-btn'); button.disabled=true;
  try{
    const res=await fetch('api/retry_failed_job.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},body:JSON.stringify({job_id:<?php echo json_encode($jobId); ?>})});
    const data=await res.json();
    if(res.ok){window.location.href='index.php';return}
    document.getElementById('action-status').textContent=data.error||'Could not create retry campaign.';
  }catch(error){
    document.getElementById('action-status').textContent='Could not create retry campaign: '+error.message;
  }
  button.disabled=false;
}
</script>
</body></html>
