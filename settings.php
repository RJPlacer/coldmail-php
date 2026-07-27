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

  /* ============ Mobile responsive ============ */
  @media (max-width: 720px) {
    .shell { padding: 18px 14px 60px; }

    /* Prevent iOS Safari from auto-zooming when an input is focused */
    input[type=text], input[type=password], input[type=number], select {
      font-size: 16px;
    }

    header.top { flex-wrap: wrap; row-gap: 8px; }
    header.top .brand-lockup img { height: 24px; }
    header.top .product-name { font-size: 15px; }
    header.top .right { width: 100%; }
    h1 { font-size: 20px; }
    .panel { padding: 18px; border-radius: 14px; }
    .row { flex-direction: column; gap: 0; }
    .row > div { flex: none; }
    .btn { width: 100%; text-align: center; padding: 13px 20px; }
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
    <div class="right">
      <a href="index.php" class="back-link">← Back to Dispatch</a>
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
</script>
</body>
</html>
