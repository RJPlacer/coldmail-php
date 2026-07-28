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
    display: inline-flex; align-items: center; gap: 6px;
  }
  .back-link:hover { text-decoration: underline; }
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
    .panel { padding: 8px; border-radius: 14px; }
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
    <div class="right menu-wrap" style="gap:12px; display:flex; align-items:center;">
      <a href="index.php" class="back-link">← Back to Dispatch</a>
      <button class="hamburger-btn" id="menu-toggle" aria-label="Open menu" aria-expanded="false">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>
      <div class="menu-dropdown" id="menu-dropdown">
        <a href="settings.php" class="menu-item active">Settings</a>
        <a href="history.php" class="menu-item">History</a>
        <a href="suppression.php" class="menu-item">Suppression List</a>
        <div class="menu-divider"></div>
        <div class="menu-user">Signed in as <strong><?php echo htmlspecialchars($username); ?></strong></div>
        <a href="logout.php" class="menu-item danger">Log out</a>
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
        <label>Provider preset</label>
        <select id="preset">
          <option value="custom">Custom</option>
          <option value="gmail">Gmail (smtp.gmail.com)</option>
          <option value="outlook">Outlook / Office365 (smtp.office365.com)</option>
        </select>
      </div>
      <div>
        <label>SMTP Host</label>
        <input type="text" id="smtp_host" placeholder="smtp.gmail.com">
      </div>
    </div>
    <div class="row">
      <div>
        <label>Port</label>
        <input type="number" id="smtp_port" value="587">
      </div>
      <div>
        <label>Encryption</label>
        <select id="use_ssl">
          <option value="false">STARTTLS (587)</option>
          <option value="true">SSL (465)</option>
        </select>
      </div>
    </div>
    <label>Email address (username)</label>
    <input type="text" id="smtp_user" placeholder="you@example.com">
    <label>App Password</label>
    <input type="password" id="smtp_pass" placeholder="16-character app password">
    <label>From Name</label>
    <input type="text" id="from_name" placeholder="Jane Doe">
    <div class="hint">Use your real name, not a team/brand name — this makes a big difference for avoiding spam filters.</div>

    <button class="btn" style="margin-top:24px" onclick="saveSettings()">Save Settings</button>
    <div id="save-status"></div>
  </div>
</div>

<script>
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
    const res = await fetch('api/get_smtp_settings.php');
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
      document.getElementById('smtp_pass').value = data.settings.smtp_pass || '';
      document.getElementById('from_name').value = data.settings.from_name || '';
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
    const res = await fetch('api/save_smtp_settings.php', {
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
  } catch (e) {
    statusEl.className = 'error';
    statusEl.textContent = 'Error contacting server: ' + e.message;
  }
}

loadSettings();

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
