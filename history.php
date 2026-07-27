<?php
require_once __DIR__ . '/config.php';
require_login(false);
$username = current_username();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>History — AlfaDevs Dispatch</title>
<link rel="icon" type="image/x-icon" href="static/favicon.ico">
<style>
  :root {
    --ink: #0c1f3d; --paper: #eef6ff; --panel: #ffffff; --line: #dbe8f6;
    --accent: #1e7fe0; --accent-dark: #0b3f8c; --warn: #d24545; --muted: #5c7089;
    --mono: 'JetBrains Mono', 'SF Mono', Consolas, monospace;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    background: radial-gradient(circle at 15% 0%, #ffffff 0%, var(--paper) 40%, #d8ebff 100%);
    background-attachment: fixed;
    color: var(--ink);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }
  .shell { max-width: 920px; margin: 0 auto; padding: 32px 24px 80px; }
  header.top {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 8px; padding-bottom: 20px;
  }
  header.top .brand-lockup { display: flex; align-items: center; gap: 12px; }
  header.top .brand-lockup img { height: 28px; }
  header.top .divider { width: 1px; height: 22px; background: var(--line); }
  header.top .product-name { font-size: 18px; font-weight: 700; color: var(--ink); letter-spacing: -0.01em; }
  .back-link {
    font-size: 13px; font-weight: 600; color: var(--accent-dark); text-decoration: none;
  }
  .back-link:hover { text-decoration: underline; }
  h1 { font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 6px; }
  .subtitle { color: var(--muted); font-size: 14px; margin: 0 0 24px; }
  .panel {
    background: var(--panel); border-radius: 18px; box-shadow: 0 12px 40px rgba(11, 63, 140, 0.08);
    padding: 12px; overflow-x: auto;
  }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--line); white-space: nowrap; }
  th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); font-weight: 700; }
  tr:last-child td { border-bottom: none; }
  .subject-cell { white-space: normal; max-width: 320px; font-weight: 600; }
  .badge {
    display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.03em;
  }
  .badge.done { background: #e8f5ee; color: #1a7a4c; }
  .badge.stopped { background: #fff4e5; color: #a15c00; }
  .badge.sending { background: #e8f2ff; color: var(--accent-dark); }
  .badge.error { background: #fdecec; color: var(--warn); }
  .badge.dry { background: #f0f0f5; color: var(--muted); }
  .dl-link { color: var(--accent-dark); font-weight: 600; text-decoration: none; font-size: 12px; }
  .dl-link:hover { text-decoration: underline; }
  .empty-state { padding: 60px 20px; text-align: center; color: var(--muted); }
</style>
</head>
<body>
<div class="shell">
  <header class="top">
    <div class="brand-lockup">
      <img src="static/logo.png" alt="AlfaDevs">
      <div class="product-name" style="color: #1dc2fc;">AlfaDevs</div>
      <div class="divider"></div>
      <div class="product-name">History</div>
    </div>
    <a href="index.php" class="back-link">← Back to Dispatch</a>
  </header>

  <h1>Campaign History</h1>
  <p class="subtitle">Every send you've started, whether it finished, and the results log for each — for <strong><?php echo htmlspecialchars($username); ?></strong>.</p>

  <div class="panel">
    <div id="history-table-wrap"></div>
  </div>
</div>

<script>
function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function badgeFor(job) {
  if (job.dry_run) return '<span class="badge dry">Dry run</span>';
  const map = {done: 'done', stopped: 'stopped', sending: 'sending', error: 'error', queued: 'sending'};
  const cls = map[job.status] || 'dry';
  return `<span class="badge ${cls}">${escapeHtml(job.status)}</span>`;
}

function formatDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  if (isNaN(d)) return iso;
  return d.toLocaleString();
}

async function loadHistory() {
  const wrap = document.getElementById('history-table-wrap');
  try {
    const res = await fetch('api/list_history.php');
    const data = await res.json();
    if (!res.ok) {
      wrap.innerHTML = `<div class="empty-state">Could not load history.</div>`;
      return;
    }
    if (!data.jobs.length) {
      wrap.innerHTML = `<div class="empty-state">No campaigns sent yet — once you send (or dry-run) something from Dispatch, it'll show up here.</div>`;
      return;
    }
    let html = `<table>
      <tr>
        <th>Date</th>
        <th>Subject</th>
        <th>Mode</th>
        <th>Recipients</th>
        <th>Sent</th>
        <th>Failed</th>
        <th>Status</th>
        <th>Log</th>
      </tr>`;
    data.jobs.forEach(job => {
      html += `<tr>
        <td>${formatDate(job.created_at)}</td>
        <td class="subject-cell">${escapeHtml(job.subject)}</td>
        <td>${badgeFor(job)}</td>
        <td>${job.total}</td>
        <td>${job.sent}</td>
        <td>${job.failed}</td>
        <td><span class="badge ${job.status}">${escapeHtml(job.status)}</span></td>
        <td><a class="dl-link" href="api/download_log.php?job_id=${encodeURIComponent(job.job_id)}">Download</a></td>
      </tr>`;
    });
    html += '</table>';
    wrap.innerHTML = html;
  } catch (e) {
    wrap.innerHTML = `<div class="empty-state">Error loading history: ${escapeHtml(e.message)}</div>`;
  }
}

loadHistory();
</script>
</body>
</html>
