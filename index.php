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
<title>Dispatch — Bulk Email Sender</title>
<style>
  :root {
    --ink: #0c1f3d;
    --paper: #eef6ff;
    --panel: #ffffff;
    --line: #dbe8f6;
    --accent: #1e7fe0;
    --accent-dark: #0b3f8c;
    --warn: #d24545;
    --muted: #5c7089;
    --mono: 'JetBrains Mono', 'SF Mono', Consolas, monospace;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    background: radial-gradient(circle at 15% 0%, #ffffff 0%, var(--paper) 40%, #d8ebff 100%);
    background-attachment: fixed;
    color: var(--ink);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    -webkit-font-smoothing: antialiased;
  }
  .shell {
    max-width: 920px;
    margin: 0 auto;
    padding: 32px 24px 80px;
  }
  header.top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    padding-bottom: 20px;
  }
  header.top .brand-lockup {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  header.top .brand-lockup img { height: 28px; }
  header.top .divider {
    width: 1px;
    height: 22px;
    background: var(--line);
  }
  header.top .product-name {
    font-size: 18px;
    font-weight: 700;
    color: var(--ink);
    letter-spacing: -0.01em;
  }
  header.top .right {
    display: flex;
    align-items: center;
    gap: 14px;
  }
  header.top .user-chip {
    font-size: 13px;
    color: var(--muted);
    font-weight: 500;
  }
  .logout-btn {
    background: transparent;
    border: 1.5px solid var(--line);
    color: var(--muted);
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    text-decoration: none;
    display: inline-block;
  }
  .logout-btn:hover { border-color: var(--accent); color: var(--accent-dark); }
  h1 {
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.02em;
    margin: 0;
  }
  .subtitle {
    color: var(--muted);
    font-size: 14px;
    margin: 6px 0 28px;
  }
  .steps-nav {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    font-size: 12px;
  }
  .steps-nav .step {
    flex: 1;
    padding: 11px 12px;
    background: var(--panel);
    border: 1.5px solid var(--line);
    border-radius: 10px;
    color: var(--muted);
    cursor: pointer;
    text-align: center;
    font-weight: 600;
    letter-spacing: 0.02em;
    transition: all 0.15s ease;
  }
  .steps-nav .step.active {
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    color: white;
    border-color: transparent;
    box-shadow: 0 6px 16px rgba(11, 63, 140, 0.25);
  }
  .steps-nav .step.done {
    color: var(--accent-dark);
    border-color: #b9d9f8;
  }
  .panel {
    background: var(--panel);
    border-radius: 18px;
    box-shadow: 0 12px 40px rgba(11, 63, 140, 0.08);
    padding: 32px;
    margin-bottom: 20px;
    display: none;
  }
  .panel.active { display: block; }
  .panel h2 {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 700;
    color: var(--accent-dark);
    margin: 0 0 20px;
  }
  label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--muted);
    margin-bottom: 6px;
    margin-top: 16px;
  }
  label:first-child { margin-top: 0; }
  input[type=text], input[type=password], input[type=number], textarea, select {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid var(--line);
    background: #f7fafd;
    color: var(--ink);
    font-family: inherit;
    font-size: 14px;
    border-radius: 10px;
  }
  input:focus, textarea:focus, select:focus {
    outline: none;
    border-color: var(--accent);
    background: #fff;
  }
  textarea { font-family: var(--mono); font-size: 13px; resize: vertical; }
  .row { display: flex; gap: 16px; }
  .row > div { flex: 1; }
  .hint {
    font-size: 12px;
    color: var(--muted);
    margin-top: 4px;
    line-height: 1.5;
  }
  .btn {
    display: inline-block;
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    color: white;
    border: none;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.02em;
    cursor: pointer;
    border-radius: 10px;
    transition: opacity 0.15s ease;
  }
  .btn:hover { opacity: 0.9; }
  .btn:disabled { background: var(--line); color: var(--muted); cursor: not-allowed; opacity: 1; }
  .btn.secondary {
    background: transparent;
    color: var(--ink);
    border: 1.5px solid var(--line);
  }
  .btn.secondary:hover { border-color: var(--accent); color: var(--accent-dark); opacity: 1; }
  .btn.danger {
    background: linear-gradient(135deg, #e15b5b, var(--warn));
  }
  .btn-row { display: flex; gap: 10px; margin-top: 24px; justify-content: space-between; }
  .notice {
    border-left: 3px solid var(--accent);
    background: #e8f2ff;
    border-radius: 0 10px 10px 0;
    padding: 12px 16px;
    font-size: 13px;
    line-height: 1.6;
    margin-bottom: 20px;
  }
  .notice.warn { border-color: var(--warn); background: #fdecec; }
  table.preview {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    font-family: var(--mono);
    margin-top: 14px;
    border-radius: 10px;
    overflow: hidden;
  }
  table.preview th, table.preview td {
    border: 1px solid var(--line);
    padding: 8px 10px;
    text-align: left;
    max-width: 160px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  table.preview th { background: var(--paper); color: var(--muted); }
  .stat-grid { display: flex; gap: 12px; margin: 20px 0; }
  .stat { flex: 1; border: 1.5px solid var(--line); border-radius: 12px; padding: 16px; text-align: center; }
  .stat .num { font-size: 30px; font-weight: 800; font-family: var(--mono); }
  .stat .lbl { font-size: 11px; text-transform: uppercase; color: var(--muted); letter-spacing: 0.06em; margin-top: 4px; }
  .stat.sent .num { color: var(--accent-dark); }
  .stat.failed .num { color: var(--warn); }
  .progress-bar { height: 10px; background: var(--line); border-radius: 6px; overflow: hidden; margin: 16px 0; }
  .progress-bar .fill { height: 100%; background: linear-gradient(90deg, var(--accent), var(--accent-dark)); width: 0%; transition: width 0.3s ease; }
  .feed {
    max-height: 260px; overflow-y: auto; font-family: var(--mono); font-size: 12px;
    border: 1.5px solid var(--line); border-radius: 10px; padding: 10px; background: var(--paper);
  }
  .feed .line { padding: 3px 0; border-bottom: 1px dashed var(--line); }
  .feed .line.sent { color: var(--accent-dark); }
  .feed .line.failed { color: var(--warn); }
  .feed .line.dry-run-ok { color: var(--muted); }
  .preview-box { border: 1.5px solid var(--line); border-radius: 12px; padding: 16px; background: var(--paper); margin-top: 12px; font-size: 14px; line-height: 1.6; }
  .preview-box .ps { font-weight: 700; margin-bottom: 8px; }
  #error-banner {
    display: none; background: linear-gradient(135deg, #e15b5b, var(--warn)); color: white;
    border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px;
  }
  footer.app-footer {
    margin-top: 36px; padding-top: 20px; border-top: 1px solid var(--line);
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
    font-size: 12px; color: var(--muted);
  }
  footer.app-footer a { color: var(--accent-dark); text-decoration: none; font-weight: 600; }
  footer.app-footer a:hover { text-decoration: underline; }
  footer.app-footer .contact-links { display: flex; gap: 16px; }
</style>
</head>
<body>
<div class="shell">
  <header class="top">
    <div class="brand-lockup">
      <img src="static/logo.png" alt="AlfaDevs">
      <div class="product-name" style="color: #1dc2fc;">AlfaDevs</div>
      <div class="divider"></div>
      <div class="product-name">Dispatch</div>
    </div>
    <div class="right">
      <div class="user-chip">Signed in as <strong><?php echo htmlspecialchars($username); ?></strong></div>
      <a href="logout.php" class="logout-btn">Log out</a>
    </div>
  </header>
  <p class="subtitle">Runs on your own server. SMTP credentials are only ever kept in a temporary job file, deleted after each send.</p>

  <div id="error-banner"></div>

  <div class="steps-nav">
    <div class="step active" data-step="1">1 · SMTP Setup</div>
    <div class="step" data-step="2">2 · Recipients</div>
    <div class="step" data-step="3">3 · Compose</div>
    <div class="step" data-step="4">4 · Send</div>
  </div>

  <!-- STEP 1: SMTP -->
  <div class="panel active" id="panel-1">
    <h2>SMTP Connection</h2>
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
    <label>From Name (optional)</label>
    <input type="text" id="from_name" placeholder="Jane Doe">

    <div class="btn-row">
      <div></div>
      <button class="btn" onclick="goStep(2)">Next: Recipients →</button>
    </div>
  </div>

  <!-- STEP 2: Recipients -->
  <div class="panel" id="panel-2">
    <h2>Recipients</h2>

    <div class="notice">
      <strong>Saved lists</strong> — save a recipient list once, then just pick it from the dropdown next time instead of retyping it.
    </div>

    <div class="row" style="align-items:flex-end;">
      <div>
        <label>Saved lists</label>
        <select id="saved-lists-select">
          <option value="">— Select a saved list —</option>
        </select>
      </div>
      <div style="flex:0 0 auto; display:flex; gap:8px;">
        <button class="btn secondary" onclick="loadSavedList()">Load</button>
        <button class="btn danger" onclick="deleteSavedList()">Delete</button>
      </div>
    </div>

    <div class="row" style="margin-top:12px; align-items:flex-end;">
      <div>
        <label>Save current list as</label>
        <input type="text" id="save-list-name" placeholder="e.g. Warm leads — July">
      </div>
      <div style="flex:0 0 auto;">
        <button class="btn secondary" onclick="saveCurrentList()">Save this list</button>
      </div>
    </div>

    <div class="notice" style="margin-top:20px;">
      Paste CSV data below. First row must be a header row and must include an <strong>email</strong> column.
      Any other columns (e.g. <span style="font-family:var(--mono)">first_name, company</span>) can be used as merge tags in your message.
    </div>
    <label>CSV Data</label>
    <textarea id="raw_recipients" rows="10" placeholder="email,first_name,company
jane@acme.com,Jane,Acme Inc
bob@widgets.com,Bob,Widgets Co"></textarea>
    <div class="hint">Tip: export contacts from Sheets/Excel as CSV, then paste the full contents here.</div>

    <button class="btn secondary" style="margin-top:16px" onclick="parseRecipients()">Validate list</button>

    <div id="recipients-summary"></div>

    <div class="btn-row">
      <button class="btn secondary" onclick="goStep(1)">← Back</button>
      <button class="btn" id="to-step-3" onclick="goStep(3)" disabled>Next: Compose →</button>
    </div>
  </div>

  <!-- STEP 3: Compose -->
  <div class="panel" id="panel-3">
    <h2>Compose Message</h2>
    <label>Subject line</label>
    <input type="text" id="subject" placeholder="Quick question, {{first_name}}">

    <label>Body</label>
    <textarea id="body" rows="10" placeholder="Hi {{first_name}},

I noticed {{company}} is...

Best,
Jane"></textarea>
    <div class="hint">Use {{column_name}} to insert any column from your recipient list.</div>

    <label>Unsubscribe / footer line <span style="text-transform:none;font-weight:400">(required for compliance — every commercial email needs a way to opt out)</span></label>
    <textarea id="unsubscribe_line" rows="2">You're receiving this because we thought it'd be relevant. Reply "unsubscribe" and I'll remove you immediately. — Sent from {{smtp_user}}</textarea>

    <div class="row">
      <div>
        <label>Delay between sends (seconds)</label>
        <input type="number" id="delay_seconds" value="5" min="0">
        <div class="hint">Higher delays reduce the chance of being flagged as spam. 3–10s is reasonable for personal SMTP accounts.</div>
      </div>
      <div>
        <label>Mode</label>
        <select id="dry_run">
          <option value="true">Dry run (no emails actually sent)</option>
          <option value="false">Live send</option>
        </select>
      </div>
    </div>

    <button class="btn secondary" style="margin-top:16px" onclick="showPreview()">Preview first email</button>
    <div id="preview-output"></div>

    <div class="btn-row">
      <button class="btn secondary" onclick="goStep(2)">← Back</button>
      <button class="btn" onclick="goStep(4)">Next: Review & Send →</button>
    </div>
  </div>

  <!-- STEP 4: Send -->
  <div class="panel" id="panel-4">
    <h2>Review & Send</h2>
    <div id="review-summary"></div>

    <div class="notice warn" id="live-warning" style="display:none">
      You're about to send <strong id="live-count"></strong> real emails. This can't be undone once started.
      Make sure your list is opt-in or otherwise compliant with anti-spam law in your jurisdiction.
    </div>

    <div class="btn-row">
      <button class="btn secondary" onclick="goStep(3)">← Back</button>
      <button class="btn" id="send-btn" onclick="startSend()">Start Sending</button>
    </div>

    <div id="send-progress" style="display:none; margin-top:24px;">
      <div class="stat-grid">
        <div class="stat"><div class="num" id="stat-total">0</div><div class="lbl">Total</div></div>
        <div class="stat sent"><div class="num" id="stat-sent">0</div><div class="lbl">Sent</div></div>
        <div class="stat failed"><div class="num" id="stat-failed">0</div><div class="lbl">Failed</div></div>
      </div>
      <div class="progress-bar"><div class="fill" id="progress-fill"></div></div>
      <div id="status-text" style="font-family:var(--mono); font-size:12px; color:var(--muted); margin-bottom:10px;"></div>
      <div class="feed" id="feed"></div>
      <div class="btn-row">
        <button class="btn danger" id="stop-btn" onclick="stopSend()">Stop Sending</button>
        <button class="btn secondary" id="download-btn" onclick="downloadLog()" style="display:none">Download Log</button>
      </div>
    </div>
  </div>

  </div>

  <footer class="app-footer">
    <div>AlfaDevs Dispatch — internal team tool</div>
    <div class="contact-links">
      <a href="mailto:alfadevs.team@gmail.com">alfadevs.team@gmail.com</a>
      <span style="color: var(--line)">|</span>
      <a href="#" onclick="return false;" title="Add your website link here">alfadevs.com (placeholder)</a>
    </div>
  </footer>

</div>

<script>
let parsedCount = 0;
let currentJobId = null;
let sendLoopActive = false;
let stoppedByUser = false;

function showError(msg) {
  const banner = document.getElementById('error-banner');
  banner.textContent = msg;
  banner.style.display = 'block';
  setTimeout(() => banner.style.display = 'none', 6000);
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

function goStep(n) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + n).classList.add('active');
  document.querySelectorAll('.steps-nav .step').forEach(s => {
    const step = parseInt(s.dataset.step);
    s.classList.toggle('active', step === n);
    s.classList.toggle('done', step < n);
  });
  if (n === 4) buildReview();
  if (n === 2) refreshSavedLists();
}

async function refreshSavedLists() {
  try {
    const res = await fetch('api/list_recipient_lists.php');
    const data = await res.json();
    if (!res.ok) return;
    const select = document.getElementById('saved-lists-select');
    const current = select.value;
    select.innerHTML = '<option value="">— Select a saved list —</option>' +
      data.lists.map(l => `<option value="${escapeHtml(l.name)}">${escapeHtml(l.name)} (${l.count})</option>`).join('');
    if (current) select.value = current;
  } catch (e) {
    // silent — saved lists are a convenience feature, not critical path
  }
}

async function loadSavedList() {
  const name = document.getElementById('saved-lists-select').value;
  if (!name) {
    showError('Pick a saved list first.');
    return;
  }
  try {
    const res = await fetch('api/load_recipient_list.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({name})
    });
    const data = await res.json();
    if (!res.ok) {
      showError(data.error || 'Could not load list.');
      return;
    }
    document.getElementById('raw_recipients').value = data.raw_text;
    parseRecipients();
  } catch (e) {
    showError('Error contacting server: ' + e.message);
  }
}

async function saveCurrentList() {
  const name = document.getElementById('save-list-name').value.trim();
  const rawText = document.getElementById('raw_recipients').value;
  if (!name) {
    showError('Enter a name for this list first.');
    return;
  }
  try {
    const res = await fetch('api/save_recipient_list.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({name, raw_text: rawText})
    });
    const data = await res.json();
    if (!res.ok) {
      showError(data.error || 'Could not save list.');
      return;
    }
    document.getElementById('save-list-name').value = '';
    refreshSavedLists();
  } catch (e) {
    showError('Error contacting server: ' + e.message);
  }
}

async function deleteSavedList() {
  const select = document.getElementById('saved-lists-select');
  const name = select.value;
  if (!name) {
    showError('Pick a saved list first.');
    return;
  }
  if (!confirm(`Delete saved list "${name}"? This can't be undone.`)) return;
  try {
    const res = await fetch('api/delete_recipient_list.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({name})
    });
    const data = await res.json();
    if (!res.ok) {
      showError(data.error || 'Could not delete list.');
      return;
    }
    refreshSavedLists();
  } catch (e) {
    showError('Error contacting server: ' + e.message);
  }
}

document.querySelectorAll('.steps-nav .step').forEach(s => {
  s.addEventListener('click', () => goStep(parseInt(s.dataset.step)));
});

async function parseRecipients() {
  const raw = document.getElementById('raw_recipients').value;
  try {
    const res = await fetch('api/parse_recipients.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({raw_text: raw})
    });
    const data = await res.json();
    if (!res.ok) {
      showError(data.error || 'Could not parse recipients.');
      document.getElementById('to-step-3').disabled = true;
      return;
    }
    parsedCount = data.count;
    let html = `<div class="notice">Found <strong>${data.count}</strong> valid recipients. Columns: ${data.fieldnames.join(', ')}</div>`;
    html += '<table class="preview"><tr>' + data.fieldnames.map(f => `<th>${f}</th>`).join('') + '</tr>';
    data.preview.forEach(row => {
      html += '<tr>' + data.fieldnames.map(f => `<td>${(row[f]||'')}</td>`).join('') + '</tr>';
    });
    html += '</table>';
    document.getElementById('recipients-summary').innerHTML = html;
    document.getElementById('to-step-3').disabled = data.count === 0;
  } catch (e) {
    showError('Error contacting server: ' + e.message);
  }
}

function showPreview() {
  const subject = document.getElementById('subject').value;
  const body = document.getElementById('body').value;
  document.getElementById('preview-output').innerHTML = `
    <div class="preview-box">
      <div class="ps">Subject: ${escapeHtml(subject) || '(empty)'}</div>
      <div>${escapeHtml(body).replace(/\\n/g, '<br>') || '(empty)'}</div>
    </div>
    <div class="hint">This shows the raw template. Merge tags like {{first_name}} will be filled in per-recipient when sent.</div>
  `;
}

function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function buildReview() {
  const dryRun = document.getElementById('dry_run').value === 'true';
  const delay = document.getElementById('delay_seconds').value;
  const host = document.getElementById('smtp_host').value;
  const user = document.getElementById('smtp_user').value;
  document.getElementById('review-summary').innerHTML = `
    <table class="preview">
      <tr><th>SMTP host</th><td>${escapeHtml(host)}</td></tr>
      <tr><th>Sending as</th><td>${escapeHtml(user)}</td></tr>
      <tr><th>Recipients</th><td>${parsedCount}</td></tr>
      <tr><th>Delay between sends</th><td>${delay}s</td></tr>
      <tr><th>Mode</th><td>${dryRun ? 'DRY RUN (no emails sent)' : 'LIVE SEND'}</td></tr>
    </table>
  `;
  const warning = document.getElementById('live-warning');
  if (!dryRun) {
    warning.style.display = 'block';
    document.getElementById('live-count').textContent = parsedCount;
  } else {
    warning.style.display = 'none';
  }
}

async function startSend() {
  const config = {
    smtp_host: document.getElementById('smtp_host').value,
    smtp_port: document.getElementById('smtp_port').value,
    smtp_user: document.getElementById('smtp_user').value,
    smtp_pass: document.getElementById('smtp_pass').value,
    use_ssl: document.getElementById('use_ssl').value === 'true',
    from_name: document.getElementById('from_name').value,
    subject: document.getElementById('subject').value,
    body: document.getElementById('body').value,
    unsubscribe_line: document.getElementById('unsubscribe_line').value,
    delay_seconds: document.getElementById('delay_seconds').value,
    dry_run: document.getElementById('dry_run').value === 'true',
    raw_recipients: document.getElementById('raw_recipients').value,
  };

  document.getElementById('send-btn').disabled = true;

  try {
    const res = await fetch('api/start_job.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(config)
    });
    const data = await res.json();
    if (!res.ok) {
      showError(data.error || 'Could not start send job.');
      document.getElementById('send-btn').disabled = false;
      return;
    }
    currentJobId = data.job_id;
    stoppedByUser = false;
    document.getElementById('send-progress').style.display = 'block';
    document.getElementById('stat-total').textContent = data.total;
    document.getElementById('stop-btn').style.display = 'inline-block';
    document.getElementById('download-btn').style.display = 'none';
    sendLoopActive = true;
    const delayMs = Math.max(0, parseFloat(config.delay_seconds || 0)) * 1000;
    sendNextLoop(delayMs);
  } catch (e) {
    showError('Error contacting server: ' + e.message);
    document.getElementById('send-btn').disabled = false;
  }
}

async function sendNextLoop(delayMs) {
  if (!sendLoopActive || !currentJobId) return;
  try {
    const res = await fetch('api/send_next.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({job_id: currentJobId})
    });
    const data = await res.json();
    if (!res.ok) {
      showError(data.error || 'Send error.');
      sendLoopActive = false;
      return;
    }
    renderProgress(data);

    if (['done', 'stopped', 'error'].includes(data.status)) {
      sendLoopActive = false;
      document.getElementById('stop-btn').style.display = 'none';
      document.getElementById('download-btn').style.display = 'inline-block';
      if (data.status === 'error') showError('Send job error: ' + data.error);
      return;
    }

    setTimeout(() => sendNextLoop(delayMs), delayMs);
  } catch (e) {
    showError('Error contacting server: ' + e.message);
    sendLoopActive = false;
  }
}

function renderProgress(data) {
  document.getElementById('stat-sent').textContent = data.sent;
  document.getElementById('stat-failed').textContent = data.failed;
  const pct = data.total ? Math.round((data.progress / data.total) * 100) : 0;
  document.getElementById('progress-fill').style.width = pct + '%';
  document.getElementById('status-text').textContent = `Status: ${data.status} — ${data.progress}/${data.total}`;

  const feed = document.getElementById('feed');
  feed.innerHTML = data.results.slice().reverse().map(r =>
    `<div class="line ${r.status}">[${r.status}] ${r.email} — ${escapeHtml(r.subject)}${r.error ? ' — ' + escapeHtml(r.error) : ''}</div>`
  ).join('');
}

async function stopSend() {
  if (!currentJobId) return;
  stoppedByUser = true;
  await fetch('api/stop_job.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({job_id: currentJobId})
  });
  sendLoopActive = false;
  document.getElementById('stop-btn').style.display = 'none';
  document.getElementById('download-btn').style.display = 'inline-block';
}

function downloadLog() {
  if (!currentJobId) return;
  window.location.href = 'api/download_log.php?job_id=' + currentJobId;
}
</script>
</body>
</html>
