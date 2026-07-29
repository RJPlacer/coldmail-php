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
<title>Settings — AlfaDevs Dispatch</title>
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
  .shell { max-width: 720px; margin: 0 auto; padding: 32px 24px 80px; }
  header.top {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 8px; padding-bottom: 20px;
  }
  header.top .brand-lockup { display: flex; align-items: center; gap: 12px; }
  header.top .brand-lockup img { height: 28px; }
  header.top .divider { width: 1px; height: 22px; background: var(--line); }
  header.top .product-name { font-size: 18px; font-weight: 700; color: var(--ink); letter-spacing: -0.01em; }
  header.top .right { display: flex; align-items: center; gap: 14px; }
  .back-link {
    font-size: 13px; font-weight: 600; color: var(--accent-dark); text-decoration: none;
    display: none; align-items: center; gap: 6px;
  }
  .back-link:hover { text-decoration: underline; }
  .desktop-nav { display:flex; align-items:center; gap:6px; margin-left:auto; margin-right:14px; }
  .desktop-nav a {
    color:var(--muted); text-decoration:none; font-size:13px; font-weight:650;
    padding:8px 10px; border-radius:8px;
  }
  .desktop-nav a:hover { background:#fff; color:var(--accent-dark); }
  .desktop-nav a.active { color:var(--accent-dark); background:#fff; }
  .credential-status { min-height:18px; margin-top:6px; font-size:12px; color:#1a7a4c; }
  .settings-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:24px; }
  .btn.secondary { background:#fff; color:var(--accent-dark); border:1.5px solid var(--line); }
  .suppression-row { display:flex; gap:10px; margin-bottom:16px; }
  .suppression-row input { flex:1; }
  .suppression-list { border:1px solid var(--line); border-radius:10px; overflow:hidden; }
  .suppression-item { display:grid; grid-template-columns:minmax(0,1fr) 130px 90px; gap:10px; align-items:center;
    padding:10px 12px; border-bottom:1px solid var(--line); font-size:12px; }
  .suppression-item:last-child { border-bottom:0; }
  .suppression-item .email { overflow:hidden; text-overflow:ellipsis; }
  .suppression-item button { border:0; background:transparent; color:var(--warn); cursor:pointer; font-weight:700; }
  h1 { font-size: 24px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 6px; }
  .subtitle { color: var(--muted); font-size: 14px; margin: 0 0 24px; }
  .panel {
    background: var(--panel); border-radius: 18px; box-shadow: 0 12px 40px rgba(11, 63, 140, 0.08);
    padding: 32px;
  }
  label {
    display: block; font-size: 12px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.04em; color: var(--muted); margin-bottom: 6px; margin-top: 16px;
  }
  label:first-child { margin-top: 0; }
  input[type=text], input[type=password], input[type=number], select {
    width: 100%; padding: 11px 14px; border: 1.5px solid var(--line); background: #f7fafd;
    color: var(--ink); font-family: inherit; font-size: 14px; border-radius: 10px;
  }
  input:focus, select:focus { outline: none; border-color: var(--accent); background: #fff; }
  .row { display: flex; gap: 16px; }
  .row > div { flex: 1; }
  .hint { font-size: 12px; color: var(--muted); margin-top: 4px; line-height: 1.5; }
  .btn {
    display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    color: white; border: none; font-size: 13px; font-weight: 700; letter-spacing: 0.02em;
    cursor: pointer; border-radius: 10px; transition: opacity 0.15s ease;
  }
  .btn:hover { opacity: 0.9; }
  .notice {
    border-left: 3px solid var(--accent); background: #e8f2ff; border-radius: 0 10px 10px 0;
    padding: 12px 16px; font-size: 13px; line-height: 1.6; margin-bottom: 20px;
  }
  #save-status {
    display: none; margin-top: 16px; padding: 10px 14px; border-radius: 8px; font-size: 13px;
  }
  #save-status.ok { background: #e8f5ee; color: #1a7a4c; display: block; }
  #save-status.error { background: #fdecec; color: var(--warn); display: block; }

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
    .suppression-row { flex-direction:column; }
    .suppression-item { grid-template-columns:minmax(0,1fr) 80px; }
    .suppression-item .reason { display:none; }
    table { font-size: 12px; }
    th, td { padding: 9px 10px; }
    .subject-cell { max-width: 200px; }
    .empty-state { padding: 40px 14px; font-size: 13px; }
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
      <div class="product-name">Settings</div>
    </div>
    <nav class="desktop-nav" aria-label="Primary navigation">
      <a href="index.php">Dispatch</a>
      <a href="history.php">History</a>
      <a href="settings.php" class="active" aria-current="page">Settings</a>
    </nav>
    <div class="right menu-wrap" style="gap:12px; display:flex; align-items:center;">
      <a href="index.php" class="back-link">← Back to Dispatch</a>
      <button class="hamburger-btn" id="menu-toggle" aria-label="Open menu" aria-expanded="false">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>
      <div class="menu-dropdown" id="menu-dropdown">
        <a href="history.php" class="menu-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          History
        </a>
        <a href="settings.php" class="menu-item active">
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

  <h1>SMTP Settings</h1>
  <p class="subtitle">Saved once for <strong><?php echo htmlspecialchars($username); ?></strong> — no need to re-enter this every time you send a campaign.</p>

  <div class="panel">
    <div class="notice">
      Gmail and Outlook both require an <strong>app password</strong>, not your normal login password, once 2FA is on.
      Gmail: Google Account → Security → App Passwords. Outlook: account.microsoft.com/security → App passwords.
    </div>

    <div class="row">
      <div>
        <label for="preset">Provider preset</label>
        <select id="preset">
          <option value="custom">Custom</option>
          <option value="gmail">Gmail (smtp.gmail.com)</option>
          <option value="outlook">Outlook / Office365 (smtp.office365.com)</option>
        </select>
      </div>
      <div>
        <label for="smtp_host">SMTP Host</label>
        <input type="text" id="smtp_host" placeholder="smtp.gmail.com">
      </div>
    </div>
    <div class="row">
      <div>
        <label for="smtp_port">Port</label>
        <input type="number" id="smtp_port" value="587">
      </div>
      <div>
        <label for="use_ssl">Encryption</label>
        <select id="use_ssl">
          <option value="false">STARTTLS (587)</option>
          <option value="true">SSL (465)</option>
        </select>
      </div>
    </div>
    <label for="smtp_user">Email address (username)</label>
    <input type="text" id="smtp_user" placeholder="you@example.com">
    <label for="smtp_pass">App Password</label>
    <input type="password" id="smtp_pass" placeholder="Leave blank to keep the saved password" autocomplete="new-password" aria-describedby="password-status">
    <div class="credential-status" id="password-status" role="status" aria-live="polite"></div>
    <label for="from_name">From Name</label>
    <input type="text" id="from_name" placeholder="Jane Doe">
    <div class="hint">Use your real name, not a team/brand name — this makes a big difference for avoiding spam filters.</div>

    <div class="settings-actions">
      <button class="btn" onclick="saveSettings()">Save Settings</button>
      <button class="btn secondary" id="test-smtp-btn" onclick="testSmtp()">Test SMTP Connection</button>
    </div>
    <div id="save-status" role="status" aria-live="polite"></div>
  </div>

  <h2 style="margin-top:28px;">Suppression List</h2>
  <p class="subtitle">Addresses here are automatically excluded from every future campaign. Confirmed unsubscribe links are added automatically.</p>
  <div class="panel">
    <div class="suppression-row">
      <input type="text" id="suppression-email" placeholder="person@example.com" aria-label="Email address to suppress">
      <button class="btn secondary" onclick="addSuppression()">Suppress address</button>
    </div>
    <div id="suppression-status" class="credential-status" role="status" aria-live="polite"></div>
    <div id="suppression-list" class="suppression-list"><div class="hint" style="padding:16px;">Loading suppressed addresses…</div></div>
  </div>
</div>

<script>
const CSRF_TOKEN = <?php echo json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
function apiFetch(url, options = {}) {
  const headers = new Headers(options.headers || {});
  if ((options.method || 'GET').toUpperCase() !== 'GET') {
    headers.set('X-CSRF-Token', CSRF_TOKEN);
  }
  return window.fetch(url, {...options, headers});
}
function escapeHtml(value) {
  const element = document.createElement('div');
  element.textContent = String(value ?? '');
  return element.innerHTML;
}
document.getElementById('preset').addEventListener('change', (e) => {
  const v = e.target.value;
  if (v === 'gmail') {
    document.getElementById('smtp_host').value = 'smtp.gmail.com';
    document.getElementById('smtp_port').value = 587;
    document.getElementById('use_ssl').value = 'false';
  } else if (v === 'outlook') {
    document.getElementById('smtp_host').value = 'smtp.office365.com';
    document.getElementById('smtp_port').value = 587;
    document.getElementById('use_ssl').value = 'false';
  }
});

async function loadSettings() {
  try {
    const res = await apiFetch('api/get_smtp_settings.php');
    const data = await res.json();
    if (data.settings) {
      document.getElementById('smtp_host').value = data.settings.smtp_host || '';
      const preset = document.getElementById('preset');
      const host = (data.settings.smtp_host || '').toLowerCase();

      if (host === 'smtp.gmail.com') {
          preset.value = 'gmail';
      } else if (host === 'smtp.office365.com') {
          preset.value = 'outlook';
      } else {
          preset.value = 'custom';
      }
      document.getElementById('smtp_port').value = data.settings.smtp_port || 587;
      document.getElementById('use_ssl').value = data.settings.use_ssl ? 'true' : 'false';
      document.getElementById('smtp_user').value = data.settings.smtp_user || '';
      document.getElementById('from_name').value = data.settings.from_name || '';
      document.getElementById('password-status').textContent =
        data.settings.has_password ? 'App password is saved on the server and is not returned to this page.' : '';
    }
  } catch (e) {
    // no saved settings yet — leave fields blank
  }
}

async function saveSettings() {
  const config = {
    smtp_host: document.getElementById('smtp_host').value,
    smtp_port: document.getElementById('smtp_port').value,
    use_ssl: document.getElementById('use_ssl').value === 'true',
    smtp_user: document.getElementById('smtp_user').value,
    smtp_pass: document.getElementById('smtp_pass').value,
    from_name: document.getElementById('from_name').value,
  };
  const statusEl = document.getElementById('save-status');
  try {
    const res = await apiFetch('api/save_smtp_settings.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(config)
    });
    const data = await res.json();
    if (!res.ok) {
      statusEl.className = 'error';
      statusEl.textContent = data.error || 'Could not save settings.';
      return;
    }
    statusEl.className = 'ok';
    statusEl.textContent = 'Settings saved. You can head back to Dispatch now.';
    document.getElementById('smtp_pass').value = '';
    document.getElementById('password-status').textContent = 'App password is saved on the server and is not returned to this page.';
  } catch (e) {
    statusEl.className = 'error';
    statusEl.textContent = 'Error contacting server: ' + e.message;
  }
}

async function testSmtp() {
  const button = document.getElementById('test-smtp-btn');
  const statusEl = document.getElementById('save-status');
  button.disabled = true;
  statusEl.className = '';
  statusEl.style.display = 'block';
  statusEl.textContent = 'Testing SMTP connection…';
  try {
    const res = await apiFetch('api/test_smtp.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({})
    });
    const data = await res.json();
    statusEl.className = res.ok ? 'ok' : 'error';
    statusEl.textContent = res.ok ? data.message : (data.error || 'SMTP test failed.');
  } catch (e) {
    statusEl.className = 'error';
    statusEl.textContent = 'SMTP test failed: ' + e.message;
  } finally {
    button.disabled = false;
  }
}

async function loadSuppressions() {
  const list = document.getElementById('suppression-list');
  try {
    const res = await apiFetch('api/list_suppressions.php');
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Could not load suppressions.');
    if (!data.suppressions.length) {
      list.innerHTML = '<div class="hint" style="padding:16px;">No suppressed addresses yet.</div>';
      return;
    }
    list.innerHTML = data.suppressions.map(item => `
      <div class="suppression-item">
        <div class="email" title="${escapeHtml(item.email)}">${escapeHtml(item.email)}</div>
        <div class="reason">${escapeHtml(item.reason || 'manual')}</div>
        <button type="button" class="remove-suppression" data-email="${escapeHtml(item.email)}">Remove</button>
      </div>`).join('');
    list.querySelectorAll('.remove-suppression').forEach(button => {
      button.addEventListener('click', () => removeSuppression(button.dataset.email));
    });
  } catch (e) {
    list.innerHTML = `<div class="hint" style="padding:16px;color:var(--warn);">${escapeHtml(e.message)}</div>`;
  }
}

async function addSuppression() {
  const input = document.getElementById('suppression-email');
  const status = document.getElementById('suppression-status');
  const email = input.value.trim();
  try {
    const res = await apiFetch('api/add_suppression.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({email})
    });
    const data = await res.json();
    status.textContent = res.ok ? `${email} will be excluded from future campaigns.` : (data.error || 'Could not suppress address.');
    if (res.ok) {
      input.value = '';
      loadSuppressions();
    }
  } catch (e) {
    status.textContent = 'Could not suppress address: ' + e.message;
  }
}

async function removeSuppression(email) {
  if (!confirm(`Allow ${email} to receive future campaigns again?`)) return;
  const status = document.getElementById('suppression-status');
  try {
    const res = await apiFetch('api/remove_suppression.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({email})
    });
    const data = await res.json();
    status.textContent = res.ok ? `${email} was removed from the suppression list.` : (data.error || 'Could not remove address.');
    if (res.ok) loadSuppressions();
  } catch (e) {
    status.textContent = 'Could not remove address: ' + e.message;
  }
}

loadSettings();
loadSuppressions();

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
