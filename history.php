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
    display:none; font-size: 13px; font-weight: 600; color: var(--accent-dark); text-decoration: none;
  }
  .back-link:hover { text-decoration: underline; }
  .desktop-nav { display:flex; align-items:center; gap:6px; margin-left:auto; margin-right:14px; }
  .desktop-nav a {
    color:var(--muted); text-decoration:none; font-size:13px; font-weight:650;
    padding:8px 10px; border-radius:8px;
  }
  .desktop-nav a:hover { background:#fff; color:var(--accent-dark); }
  .desktop-nav a.active { color:var(--accent-dark); background:#fff; }
  h1 { font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 6px; }
  .subtitle { color: var(--muted); font-size: 14px; margin: 0 0 24px; }
  .panel {
    background: var(--panel); border-radius: 18px; box-shadow: 0 12px 40px rgba(11, 63, 140, 0.08);
    padding: 12px; overflow-x: auto;
    -webkit-overflow-scrolling: touch;
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
  .history-toolbar { display:flex; gap:12px; margin-bottom:14px; }
  .history-toolbar input, .history-toolbar select {
    border:1.5px solid var(--line); border-radius:10px; padding:10px 12px;
    background:#fff; color:var(--ink); font:inherit; font-size:13px;
  }
  .history-toolbar input { flex:1; min-width:0; }
  .history-toolbar select { min-width:170px; }
  .history-count { color:var(--muted); font-size:12px; margin:0 0 8px 2px; }
  .history-pagination { display:flex; justify-content:flex-end; align-items:center; gap:10px; margin-top:14px; }
  .history-pagination button {
    border:1.5px solid var(--line); background:#fff; color:var(--accent-dark);
    border-radius:8px; padding:8px 12px; font:inherit; font-size:12px; font-weight:700; cursor:pointer;
  }
  .history-pagination button:disabled { color:var(--muted); cursor:not-allowed; opacity:.6; }
  .history-pagination span { color:var(--muted); font-size:12px; }

  /* Filters panel */
  .filters-panel {
    background: var(--panel); border-radius: 18px; box-shadow: 0 12px 40px rgba(11, 63, 140, 0.08);
    padding: 22px 24px; margin-bottom: 18px;
  }
  .filters-row {
    display: flex; gap: 14px; flex-wrap: wrap;
  }
  .filters-row > div {
    flex: 1 1 160px; min-width: 140px;
  }
  .filters-row label {
    display: block; font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: var(--muted); margin-bottom: 6px;
  }
  .filters-row input[type=text], .filters-row input[type=date], .filters-row select {
    width: 100%; padding: 10px 12px; border: 1.5px solid var(--line); background: #f7fafd;
    color: var(--ink); font-family: inherit; font-size: 13px; border-radius: 9px;
  }
  .filters-row input:focus, .filters-row select:focus { outline: none; border-color: var(--accent); background: #fff; }
  .filters-actions {
    display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; flex-wrap: wrap;
  }
  .btn {
    display: inline-block; padding: 10px 18px; background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    color: white; border: none; font-size: 12.5px; font-weight: 700; letter-spacing: 0.02em;
    cursor: pointer; border-radius: 9px; transition: opacity 0.15s ease;
  }
  .btn:hover { opacity: 0.9; }
  .btn.secondary {
    background: transparent; color: var(--ink); border: 1.5px solid var(--line);
  }
  .btn.secondary:hover { border-color: var(--accent); color: var(--accent-dark); opacity: 1; }
  .filters-hint { font-size: 12px; color: var(--muted); margin-top: 10px; }

  /* Hamburger menu */
  .menu-wrap { position: relative; }
  .hamburger-btn {
    display: flex; align-items: center; justify-content: center;
    width: 38px; height: 38px; border-radius: 8px;
    border: 1.5px solid var(--line); background: #fff;
    cursor: pointer; color: var(--ink);
  }
  .hamburger-btn:hover { border-color: var(--accent); }
  .menu-dropdown {
    position: absolute; top: calc(100% + 8px); right: 0;
    min-width: 230px; background: #fff; border: 1px solid var(--line);
    border-radius: 12px; box-shadow: 0 12px 32px rgba(11,63,140,0.18);
    padding: 8px; display: none; z-index: 50;
  }
  .menu-dropdown.open { display: block; }
  .menu-dropdown .menu-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 8px; font-size: 13px; font-weight: 600;
    color: var(--ink); text-decoration: none;
  }
  .menu-dropdown .menu-item:hover { background: var(--paper); color: var(--accent-dark); }
  .menu-dropdown .menu-user { padding: 10px 12px 4px; font-size: 12px; color: var(--muted); }
  .menu-dropdown .menu-divider { height: 1px; background: var(--line); margin: 6px 4px; }
  .menu-dropdown .menu-item.danger { color: var(--warn); }
  .menu-dropdown .menu-item.danger:hover { background: #fdecec; }
  .menu-dropdown .menu-item.active { background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    color: white;
    border-color: transparent;
    box-shadow: 0 6px 16px rgba(11, 63, 140, 0.25); }
  /* ============ Mobile responsive ============ */
  @media (max-width: 720px) {
    .shell { padding: 18px 14px 60px; }
    header.top { flex-wrap: wrap; row-gap: 8px; }
    header.top .brand-lockup img { height: 24px; }
    header.top .product-name { font-size: 15px; }
    h1 { font-size: 20px; }
    .panel { padding: 16px; border-radius: 14px; }
    .desktop-nav { display:none; }
    .back-link { display:inline-flex; }
    .history-toolbar { flex-direction:column; }
    .history-toolbar select { width:100%; }
    table { font-size: 12px; }
    th, td { padding: 9px 10px; }
    .subject-cell { max-width: 200px; }
    .empty-state { padding: 40px 14px; font-size: 13px; }
    .filters-panel { padding: 16px; border-radius: 14px; }
    .filters-row { flex-direction: column; }
    .filters-row > div { min-width: 0; }
    .filters-actions { flex-direction: column-reverse; }
    .filters-actions .btn { width: 100%; text-align: center; }
  }
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
    <nav class="desktop-nav" aria-label="Primary navigation">
      <a href="index.php">Dispatch</a>
      <a href="history.php" class="active" aria-current="page">History</a>
      <a href="settings.php">Settings</a>
    </nav>
    <div class="right menu-wrap" style="gap:12px; display:flex; align-items:center;">
      <a href="index.php" class="back-link">← Back to Dispatch</a>
      <button class="hamburger-btn" id="menu-toggle" aria-label="Open menu" aria-expanded="false">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>
      <div class="menu-dropdown" id="menu-dropdown">
        <a href="history.php" class="menu-item active">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          History
        </a>
        <a href="settings.php" class="menu-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09A1.65 1.65 0 0 0 19.4 15z"></path></svg>
          Settings
        </a>
        <a href="suppression.php" class="menu-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
          Suppression List
        </a>
        <div class="menu-divider"></div>
        <div class="menu-user">Signed in as <strong><?php echo htmlspecialchars($username); ?></strong></div>
        <form method="POST" action="logout.php" style="margin:0;">
          <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <button type="submit" class="menu-item danger" style="width:100%;border:0;background:none;cursor:pointer;font:inherit;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Log out
          </button>
        </form>
      </div>
    </div>
  </header>

  <h1>Campaign History</h1>
  <p class="subtitle">Every send you've started, whether it finished, and the results log for each — for <strong><?php echo htmlspecialchars($username); ?></strong>.</p>

  <div class="filters-panel">
    <div class="filters-row">
      <div>
        <label>Search subject</label>
        <input type="text" id="filter-subject" placeholder="e.g. Quick question">
      </div>
      <div>
        <label>Mode</label>
        <select id="filter-mode">
          <option value="">All</option>
          <option value="dry">Dry run</option>
          <option value="live">Live send</option>
        </select>
      </div>
      <div>
        <label>Status</label>
        <select id="filter-status">
          <option value="">All</option>
          <option value="done">Done</option>
          <option value="stopped">Stopped</option>
          <option value="sending">Sending</option>
          <option value="queued">Queued</option>
          <option value="error">Error</option>
        </select>
      </div>
      <div>
        <label>From date</label>
        <input type="date" id="filter-from">
      </div>
      <div>
        <label>To date</label>
        <input type="date" id="filter-to">
      </div>
    </div>
    <div class="filters-actions">
      <button class="btn secondary" onclick="clearFilters()">Clear filters</button>
      <button class="btn" onclick="downloadAll()">Download All (Word)</button>
    </div>
    <div class="filters-hint">"Download All" bundles the full results log of every campaign that matches the filters above into one Word (.docx) document.</div>
  </div>

  <div class="panel">
    <div id="history-table-wrap" aria-live="polite"><div class="empty-state">Loading campaign history…</div></div>
  </div>
  <div class="history-count" id="history-count" aria-live="polite"></div>
  <div class="history-pagination" id="history-pagination"></div>
</div>

<script>
let allJobs = [];

function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = String(s ?? '');
  return d.innerHTML;
}

let historyPage = 1;
const HISTORY_PAGE_SIZE = 20;

function statusBadge(job) {
  const map = {done: 'done', stopped: 'stopped', sending: 'sending', error: 'error', queued: 'sending'};
  const cls = map[job.status] || 'dry';
  return `<span class="badge ${cls}">${escapeHtml(job.status)}</span>`;
}

function modeBadge(job) {
  return job.dry_run
    ? '<span class="badge dry">Dry run</span>'
    : '<span class="badge sending">Live</span>';
}

function formatDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  if (isNaN(d)) return iso;
  return d.toLocaleString();
}

function applyFilters(jobs) {
  const subject = document.getElementById('filter-subject').value.trim().toLowerCase();
  const mode = document.getElementById('filter-mode').value;
  const status = document.getElementById('filter-status').value;
  const from = document.getElementById('filter-from').value;
  const to = document.getElementById('filter-to').value;

  return jobs.filter(job => {
    if (subject && !(job.subject || '').toLowerCase().includes(subject)) return false;
    if (mode === 'dry' && !job.dry_run) return false;
    if (mode === 'live' && job.dry_run) return false;
    if (status && job.status !== status) return false;
    if (job.created_at) {
      const day = job.created_at.slice(0, 10);
      if (from && day < from) return false;
      if (to && day > to) return false;
    }
    return true;
  });
}

function renderJobs(jobs) {
  const wrap = document.getElementById('history-table-wrap');
  const count = document.getElementById('history-count');
  const pagination = document.getElementById('history-pagination');
  if (!allJobs.length) {
    wrap.innerHTML = `<div class="empty-state">No campaigns sent yet — once you send (or dry-run) something from Dispatch, it'll show up here.</div>`;
    count.textContent = '';
    pagination.innerHTML = '';
    return;
  }
  if (!jobs.length) {
    wrap.innerHTML = `<div class="empty-state">No campaigns match these filters.</div>`;
    count.textContent = `0 of ${allJobs.length} campaigns`;
    pagination.innerHTML = '';
    return;
  }
  const totalPages = Math.max(1, Math.ceil(jobs.length / HISTORY_PAGE_SIZE));
  historyPage = Math.min(historyPage, totalPages);
  const start = (historyPage - 1) * HISTORY_PAGE_SIZE;
  const pageJobs = jobs.slice(start, start + HISTORY_PAGE_SIZE);
  count.textContent = `Showing ${start + 1}–${start + pageJobs.length} of ${jobs.length} matching campaign${jobs.length === 1 ? '' : 's'}`;
  pagination.innerHTML = jobs.length > HISTORY_PAGE_SIZE
    ? `<button type="button" onclick="changeHistoryPage(-1)" ${historyPage === 1 ? 'disabled' : ''}>Previous</button>
       <span>Page ${historyPage} of ${totalPages}</span>
       <button type="button" onclick="changeHistoryPage(1)" ${historyPage === totalPages ? 'disabled' : ''}>Next</button>`
    : '';
  let html = `<table>
    <tr>
      <th>Date</th>
      <th>Subject</th>
      <th>Mode</th>
      <th>Recipients</th>
      <th>Sent</th>
      <th>Failed</th>
      <th>Suppressed</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>`;
  pageJobs.forEach(job => {
    html += `<tr>
      <td>${escapeHtml(formatDate(job.created_at))}</td>
      <td class="subject-cell">${escapeHtml(job.subject)}</td>
      <td>${modeBadge(job)}</td>
      <td>${job.total}</td>
      <td>${job.sent}</td>
      <td>${job.failed}</td>
      <td>${job.skipped || 0}</td>
      <td>${statusBadge(job)}</td>
      <td>${['queued', 'sending'].includes(job.status) ? '<a class="dl-link" href="index.php">Resume</a> · ' : ''}<a class="dl-link" href="campaign.php?job_id=${encodeURIComponent(job.job_id)}">View</a></td>
    </tr>`;
  });
  html += '</table>';
  wrap.innerHTML = html;
}

function refreshView() {
  renderJobs(applyFilters(allJobs));
}

function changeHistoryPage(direction) {
  historyPage += direction;
  refreshView();
  document.querySelector('.filters-panel').scrollIntoView({behavior: 'smooth', block: 'start'});
}

function clearFilters() {
  document.getElementById('filter-subject').value = '';
  document.getElementById('filter-mode').value = '';
  document.getElementById('filter-status').value = '';
  document.getElementById('filter-from').value = '';
  document.getElementById('filter-to').value = '';
  historyPage = 1;
  refreshView();
}

function downloadAll() {
  const subject = document.getElementById('filter-subject').value.trim();
  const mode = document.getElementById('filter-mode').value;
  const status = document.getElementById('filter-status').value;
  const from = document.getElementById('filter-from').value;
  const to = document.getElementById('filter-to').value;

  const params = new URLSearchParams();
  if (subject) params.set('subject', subject);
  if (mode) params.set('mode', mode);
  if (status) params.set('status', status);
  if (from) params.set('from', from);
  if (to) params.set('to', to);

  window.location.href = 'api/download_history.php?' + params.toString();
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
    allJobs = data.jobs;
    refreshView();

    ['filter-subject', 'filter-mode', 'filter-status', 'filter-from', 'filter-to'].forEach(id => {
      const el = document.getElementById(id);
      const handleFilter = () => {
        historyPage = 1;
        refreshView();
      };
      el.addEventListener('input', handleFilter);
      el.addEventListener('change', handleFilter);
    });
  } catch (e) {
    wrap.innerHTML = `<div class="empty-state">Error loading history: ${escapeHtml(e.message)}</div>`;
  }
}

loadHistory();

(function () {
  const btn = document.getElementById('menu-toggle');
  const menu = document.getElementById('menu-dropdown');
  if (!btn || !menu) return;
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = menu.classList.toggle('open');
    btn.setAttribute('aria-expanded', isOpen);
  });
  document.addEventListener('click', (e) => {
    if (!menu.contains(e.target) && e.target !== btn) {
      menu.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    }
  });
})();
</script>
</body>
</html>
