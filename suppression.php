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
<title>Suppression List — AlfaDevs Dispatch</title>
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
  .back-link { font-size: 13px; font-weight: 600; color: var(--accent-dark); text-decoration: none; }
  .back-link:hover { text-decoration: underline; }
  h1 { font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 6px; }
  .subtitle { color: var(--muted); font-size: 14px; margin: 0 0 24px; }
  .panel {
    background: var(--panel); border-radius: 18px; box-shadow: 0 12px 40px rgba(11, 63, 140, 0.08);
    padding: 32px; margin-bottom: 20px;
  }
  label {
    display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.04em; color: var(--muted); margin-bottom: 6px; margin-top: 16px;
  }
  label:first-child { margin-top: 0; }
  input[type=text] {
    width: 100%; padding: 11px 14px; border: 1.5px solid var(--line); background: #f7fafd;
    color: var(--ink); font-family: inherit; font-size: 14px; border-radius: 10px;
  }
  input:focus { outline: none; border-color: var(--accent); background: #fff; }
  .row { display: flex; gap: 16px; align-items: flex-end; }
  .row > div { flex: 1; }
  .row > .sm-actions { flex: 0 0 auto; }
  .hint { font-size: 12px; color: var(--muted); margin-top: 4px; line-height: 1.5; }
  .btn {
    display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    color: white; border: none; font-size: 13px; font-weight: 700; letter-spacing: 0.02em;
    cursor: pointer; border-radius: 10px; transition: opacity 0.15s ease; white-space: nowrap;
  }
  .btn:hover { opacity: 0.9; }
  .btn.danger { background: linear-gradient(135deg, #e15b5b, var(--warn)); }
  .notice {
    border-left: 3px solid var(--warn); background: #fdecec; border-radius: 0 10px 10px 0;
    padding: 12px 16px; font-size: 13px; line-height: 1.6; margin-bottom: 24px;
  }
  .notice strong { color: var(--warn); }
  .table-scroll { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
  table.preview {
    width: 100%; border-collapse: collapse; font-size: 12px; font-family: var(--mono);
    margin-top: 6px; border-radius: 10px; overflow: hidden;
  }
  table.preview th, table.preview td {
    border: 1px solid var(--line); padding: 8px 10px; text-align: left;
    max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  }
  table.preview th { background: var(--paper); color: var(--muted); }
  .empty-state { padding: 40px 20px; text-align: center; color: var(--muted); font-size: 13px; }
  .count-chip {
    display: inline-block; font-size: 12px; font-weight: 700; color: var(--accent-dark);
    background: var(--paper); border-radius: 999px; padding: 4px 12px; margin-bottom: 16px;
  }

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
  .menu-dropdown .menu-item.active {
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    color: white; border-color: transparent; box-shadow: 0 6px 16px rgba(11, 63, 140, 0.25);
  }

  /* ============ Mobile responsive ============ */
  @media (max-width: 720px) {
    .shell { padding: 18px 14px 60px; }
    input[type=text] { font-size: 16px; }
    header.top { flex-wrap: wrap; row-gap: 8px; }
    header.top .brand-lockup img { height: 24px; }
    header.top .product-name { font-size: 15px; }
    h1 { font-size: 20px; }
    .panel { padding: 18px; border-radius: 14px; }
    .row { flex-direction: column; align-items: stretch; }
    .btn { width: 100%; text-align: center; }
    table.preview { font-size: 11px; }
    table.preview th, table.preview td { padding: 6px 8px; max-width: 140px; }
    .empty-state { padding: 30px 14px; }
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
      <div class="product-name">Suppression List</div>
    </div>
    <div class="right menu-wrap" style="gap:12px; display:flex; align-items:center;">
      <a href="index.php" class="back-link">← Back to Dispatch</a>
      <button class="hamburger-btn" id="menu-toggle" aria-label="Open menu" aria-expanded="false">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>
      <div class="menu-dropdown" id="menu-dropdown">
        <a href="history.php" class="menu-item">History</a>
        <a href="settings.php" class="menu-item">Settings</a>
        <a href="suppression.php" class="menu-item active">Suppression List</a>
        <div class="menu-divider"></div>
        <div class="menu-user">Signed in as <strong><?php echo htmlspecialchars($username); ?></strong></div>
        <a href="logout.php" class="menu-item danger">Log out</a>
      </div>
    </div>
  </header>

  <h1>Suppression List</h1>
  <p class="subtitle">Shared team-wide — anyone here is skipped automatically on every future send, by every team member, no matter whose recipient list they're on.</p>

  <div class="panel">
    <div class="notice">
      <strong>Add someone the moment they ask to unsubscribe.</strong> This list is checked automatically before every send (dry run or live), so there's nothing else you need to do once an address is added here.
    </div>

    <div class="row">
      <div>
        <label>Add an email address</label>
        <input type="text" id="suppress-email-input" placeholder="jane@acme.com">
      </div>
      <div>
        <label>Reason <span style="font-weight:400; text-transform:none;">(optional)</span></label>
        <input type="text" id="suppress-reason-input" placeholder="e.g. Replied asking to unsubscribe">
      </div>
      <div class="sm-actions">
        <button class="btn" onclick="addSuppressedEmail()">Add to list</button>
      </div>
    </div>

    <div id="save-status" style="margin-top:14px;"></div>
  </div>

  <div class="panel">
    <div id="suppression-table-wrap"></div>
  </div>
</div>

<script>
function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function showStatus(msg, isError) {
  const el = document.getElementById('save-status');
  el.textContent = msg;
  el.style.color = isError ? 'var(--warn)' : '#1a7a4c';
  el.style.fontSize = '13px';
  setTimeout(() => { el.textContent = ''; }, 5000);
}

async function refreshSuppressionList() {
  const wrap = document.getElementById('suppression-table-wrap');
  try {
    const res = await fetch('api/list_suppressed.php');
    const data = await res.json();
    if (!res.ok) {
      wrap.innerHTML = `<div class="empty-state">Could not load suppression list.</div>`;
      return;
    }
    if (!data.suppressed.length) {
      wrap.innerHTML = `<div class="empty-state">No one on the suppression list yet.</div>`;
      return;
    }
    let html = `<div class="count-chip">${data.suppressed.length} suppressed</div>`;
    html += `<div class="table-scroll"><table class="preview"><tr><th>Email</th><th>Added by</th><th>Date</th><th>Reason</th><th></th></tr>`;
    data.suppressed.forEach(s => {
      const d = s.added_at ? new Date(s.added_at).toLocaleString() : '—';
      html += `<tr>
        <td>${escapeHtml(s.email)}</td>
        <td>${escapeHtml(s.added_by || '—')}</td>
        <td>${d}</td>
        <td>${escapeHtml(s.reason || '—')}</td>
        <td><button class="btn danger" style="padding:4px 10px; font-size:11px;" onclick="removeSuppressedEmail('${encodeURIComponent(s.email)}')">Remove</button></td>
      </tr>`;
    });
    html += '</table></div>';
    wrap.innerHTML = html;
  } catch (e) {
    wrap.innerHTML = `<div class="empty-state">Error loading suppression list: ${escapeHtml(e.message)}</div>`;
  }
}

async function addSuppressedEmail() {
  const input = document.getElementById('suppress-email-input');
  const reasonInput = document.getElementById('suppress-reason-input');
  const email = input.value.trim();
  const reason = reasonInput.value.trim();
  if (!email) { showStatus('Enter an email address first.', true); return; }
  try {
    const res = await fetch('api/add_suppressed.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({email, reason})
    });
    const data = await res.json();
    if (!res.ok) { showStatus(data.error || 'Could not add to suppression list.', true); return; }
    input.value = '';
    reasonInput.value = '';
    showStatus(`Added ${email} to the suppression list.`, false);
    refreshSuppressionList();
  } catch (e) {
    showStatus('Error contacting server: ' + e.message, true);
  }
}

async function removeSuppressedEmail(encodedEmail) {
  const email = decodeURIComponent(encodedEmail);
  if (!confirm(`Remove ${email} from the suppression list? They'll be emailable again.`)) return;
  try {
    const res = await fetch('api/remove_suppressed.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({email})
    });
    const data = await res.json();
    if (!res.ok) { showStatus(data.error || 'Could not remove.', true); return; }
    refreshSuppressionList();
  } catch (e) {
    showStatus('Error contacting server: ' + e.message, true);
  }
}

refreshSuppressionList();

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
