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
<link rel="icon" type="image/x-icon" href="static/favicon.ico">
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
  .table-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
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
  .feed .line.suppressed { color: var(--muted); font-style: italic; }
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

  .saved-manager {
    border: 1.5px solid var(--line);
    border-radius: 14px;
    background: linear-gradient(180deg, #f7fafd, #ffffff);
    padding: 18px 20px;
    margin-bottom: 20px;
  }
  .saved-manager .sm-title { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--accent-dark); margin-bottom: 4px; cursor: pointer; user-select: none; }
  .saved-manager .sm-title:hover { opacity: 0.85; }
  .saved-manager .sm-title .sm-chevron { margin-left: auto; transition: transform 0.2s ease; flex-shrink: 0; }
  .saved-manager .sm-body { overflow: hidden; }
  .saved-manager .sm-body.collapsed { display: none; }
  .saved-manager .sm-desc { font-size: 12.5px; color: var(--muted); margin-bottom: 16px; line-height: 1.5; }
  .saved-manager .sm-row { display: flex; gap: 12px; align-items: flex-end; }
  .saved-manager .sm-row + .sm-row { margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--line); }
  .saved-manager .sm-row > div:first-child { flex: 1; }
  .saved-manager .sm-actions { display: flex; gap: 8px; flex: 0 0 auto; }
  @media (max-width: 720px) {
    .saved-manager .sm-row { flex-direction: column; align-items: stretch; }
    .saved-manager .sm-actions .btn { flex: 1; }
  }

  .app-footer-v2 { margin-top: 36px; padding: 28px 24px; border-top: 1px solid var(--line); text-align: center; }
  .gt-title { font-size: 15px; font-weight: 800; color: var(--ink); margin-bottom: 18px; }
  .gt-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 28px; margin-bottom: 16px; }
  .gt-item { display: flex; flex-direction: column; align-items: center; gap: 8px; font-size: 12.5px; color: var(--muted); }
  .gt-item svg { color: var(--accent); }
  .gt-item a { color: var(--accent-dark); text-decoration: none; font-weight: 600; }
  .gt-item a:hover { text-decoration: underline; }
  .gt-site { margin-bottom: 10px; font-size: 12.5px; }
  .gt-site a { color: var(--accent-dark); font-weight: 700; text-decoration: none; }
  .gt-site a:hover { text-decoration: underline; }
  .gt-copy { font-size: 11.5px; color: var(--muted); }

  /* ============ Mobile responsive ============ */
  @media (max-width: 720px) {
    .shell { padding: 18px 14px 60px; }

    /* Prevent iOS Safari from auto-zooming when an input is focused */
    input[type=text], input[type=password], input[type=number], textarea, select {
      font-size: 16px;
    }

    header.top {
      flex-wrap: wrap;
      row-gap: 10px;
    }
    header.top .brand-lockup { flex-wrap: wrap; }
    header.top .brand-lockup img { height: 24px; }
    header.top .product-name { font-size: 15px; }
    header.top .right {
      width: 100%;
      flex-wrap: wrap;
      justify-content: flex-end;
      gap: 8px;
    }
    header.top .user-chip { order: 3; width: 100%; margin-top: 2px; }

    h1 { font-size: 22px; }
    .subtitle { margin-bottom: 20px; }

    .steps-nav {
      gap: 6px;
      font-size: 10.5px;
    }
    .steps-nav .step { padding: 9px 4px; }

    .panel { padding: 18px; border-radius: 14px; }

    .row {
      flex-direction: column;
      gap: 0;
    }
    .row > div { flex: none; }

    .btn { width: 100%; text-align: center; padding: 13px 20px; }
    .btn-row {
      flex-direction: column-reverse;
      gap: 10px;
    }
    .btn-row > div:empty { display: none; }

    /* file upload row */
    div[style*="display:flex"][style*="align-items:center"][style*="gap:12px"] {
      flex-wrap: wrap;
    }

    .stat-grid { gap: 8px; }
    .stat { padding: 10px; }
    .stat .num { font-size: 20px; }
    .stat .lbl { font-size: 9.5px; }

    table.preview { font-size: 11px; }
    table.preview th, table.preview td {
      padding: 6px 8px;
      max-width: 120px;
    }

    .feed { max-height: 200px; font-size: 11px; }

    footer.app-footer {
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;
    }
  }

  @media (max-width: 420px) {
    h1 { font-size: 19px; }
    .steps-nav .step { font-size: 9.5px; padding: 8px 3px; }
    .stat .num { font-size: 17px; }
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
      <div class="product-name">Dispatch</div>
    </div>
    <div class="menu-wrap">
      <button class="hamburger-btn" id="menu-toggle" aria-label="Open menu" aria-expanded="false">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>
      <div class="menu-dropdown" id="menu-dropdown">
        <a href="history.php" class="menu-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          History
        </a>
        <a href="settings.php" class="menu-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
          Settings
        </a>
        <a href="suppression.php" class="menu-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
          Suppression List
        </a>
        <div class="menu-divider"></div>
        <div class="menu-user">Signed in as <strong><?php echo htmlspecialchars($username); ?></strong></div>
        <a href="logout.php" class="menu-item danger">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          Log out
        </a>
      </div>
    </div>
  </header>
  <p class="subtitle">Runs on your own server. SMTP credentials are saved once in Settings, not re-entered every time.</p>

  <div id="error-banner"></div>
  <div id="no-smtp-banner" class="notice warn" style="display:none;">
    No SMTP settings saved yet. <a href="settings.php" style="color:inherit; font-weight:700;">Set them up in Settings</a> before sending.
  </div>

  <div class="steps-nav">
    <div class="step active" data-step="2">1 · Recipients</div>
    <div class="step" data-step="3">2 · Compose</div>
    <div class="step" data-step="4">3 · Send</div>
  </div>

  <!-- STEP 2: Recipients -->
  <div class="panel active" id="panel-2">
    <h2>Recipients</h2>

    <div class="notice">
      <strong>Saved lists</strong> — save a recipient list once, then just pick it from the dropdown next time instead of retyping it.
    </div>

    <div class="saved-manager">
      <div class="sm-title" onclick="toggleSavedManager('saved-lists-body', this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
        Saved lists
        <svg class="sm-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="sm-body" id="saved-lists-body">
        <div class="sm-desc">Save a recipient list once, then just pick it from the dropdown next time instead of retyping it.</div>

        <div class="sm-row">
          <div>
            <label style="margin-top:0;">Choose a saved list</label>
            <select id="saved-lists-select"><option value="">— Select a saved list —</option></select>
          </div>
          <div class="sm-actions">
            <button class="btn secondary" onclick="loadSavedList()">Load</button>
            <button class="btn danger" onclick="deleteSavedList()">Delete</button>
          </div>
        </div>

        <div class="sm-row">
          <div>
            <label style="margin-top:0;">Save current list as</label>
            <input type="text" id="save-list-name" placeholder="e.g. Warm leads — July">
          </div>
          <div class="sm-actions">
            <button class="btn secondary" onclick="saveCurrentList()">Save this list</button>
          </div>
        </div>
      </div>
    </div>
    <div class="saved-manager" style="border-color:#f3d9d9; background:linear-gradient(180deg,#fdf5f5,#ffffff); display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
      <div>
        <div class="sm-title" style="color:var(--warn); cursor:default; margin-bottom:2px;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
          Suppression list <span style="font-weight:400; text-transform:none; font-size:11.5px; color:var(--muted);">(shared team-wide)</span>
        </div>
        <div class="sm-desc" style="margin-bottom:0;">Anyone here is skipped automatically on every send, by every team member. Add someone the moment they ask to unsubscribe.</div>
      </div>
      <a href="suppression.php" class="btn secondary" style="white-space:nowrap; flex:0 0 auto;">Manage suppression list →</a>
    </div>
    <div class="notice" style="margin-top:20px;">
      Paste CSV data below. First row must be a header row and must include an <strong>email</strong> column.
      Any other columns (e.g. <span style="font-family:var(--mono)">first_name, company</span>) can be used as merge tags in your message.
    </div>
    <label>Upload a file (CSV or Excel)</label>
    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
      <label for="file-upload" class="btn secondary" style="cursor:pointer; margin:0;">Choose file…</label>
      <input type="file" id="file-upload" accept=".csv,.xlsx,.xls,.txt" style="display:none;">
      <span id="file-upload-name" style="font-size:13px; color:var(--muted);">No file chosen</span>
    </div>
    <div class="hint">Upload a .csv or .xlsx export straight from Sheets/Excel — it'll fill in the box below automatically.</div>

    <div style="text-align:center; margin:16px 0; color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">— or paste it directly —</div>

    <label>CSV Data</label>
    <textarea id="raw_recipients" rows="10" placeholder="email,first_name,company
jane@acme.com,Jane,Acme Inc
bob@widgets.com,Bob,Widgets Co"></textarea>
    <div class="hint">Tip: export contacts from Sheets/Excel as CSV, then paste the full contents here.</div>

    <button class="btn secondary" style="margin-top:16px" onclick="parseRecipients()">Validate list</button>

    <div id="recipients-summary"></div>

    <div class="btn-row">
      <div></div>
      <button class="btn" id="to-step-3" onclick="goStep(3)" disabled>Next: Compose →</button>
    </div>
  </div>

  <!-- STEP 3: Compose -->
  <div class="panel" id="panel-3">
    <h2>Compose Message</h2>

    <div class="notice">
      <strong>Saved templates</strong> — save a subject/body once, then reuse it next time instead of rewriting it.
    </div>

    <div class="saved-manager">
      <div class="sm-title" onclick="toggleSavedManager('saved-templates-body', this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
        Saved Templates
        <svg class="sm-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="sm-body" id="saved-templates-body">
        <div class="sm-row">
          <div>
            <label style="margin-top:0;">Choose a saved template</label>
            <select id="saved-templates-select"><option value="">— Select a saved template —</option></select>
          </div>
          <div class="sm-actions">
            <button class="btn secondary" onclick="loadSavedTemplate()">Load</button>
            <button class="btn danger" onclick="deleteSavedTemplate()">Delete</button>
          </div>
        </div>

        <div class="sm-row">
          <div>
            <label>Save current message as</label>
            <input type="text" id="save-template-name" placeholder="e.g. Warm intro v1">
          </div>
          <div class="sm-actions">
            <button class="btn secondary" onclick="saveCurrentTemplate()">Save this template</button>
          </div>
        </div>
      </div>
    </div>

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
        <div class="stat"><div class="num" id="stat-suppressed">0</div><div class="lbl">Suppressed</div></div>
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

  <footer class="app-footer-v2">
    <div class="gt-title">Get in Touch</div>
    <div class="gt-grid">
      <div class="gt-item">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"></path><path d="M4 6l8 7 8-7"></path></svg>
        <div><a href="mailto:leiprtla@gmail.com">leiprtla@gmail.com</a><br><a href="mailto:bphildavid@gmail.com">bphildavid@gmail.com</a></div>
      </div>
      <div class="gt-item">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
        <a href="https://facebook.com/AlfaDevs" target="_blank" rel="noopener">facebook.com/AlfaDevs</a>
      </div>
      <div class="gt-item">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.98.34 1.94.63 2.87a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.21-1.2a2 2 0 0 1 2.11-.45c.93.29 1.89.5 2.87.63A2 2 0 0 1 22 16.92z"></path></svg>
        <div>+63 9690603058 / 9458544797</div>
      </div>
      <div class="gt-item">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
        <div>Philippines</div>
      </div>
    </div>
    <div class="gt-site">
      <a href="https://alfadevs.leiprtla.workers.dev" target="_blank" rel="noopener">alfadevs.leiprtla.workers.dev</a>
    </div>
    <div class="gt-copy">AlfaDevs Dispatch — internal team tool · © <?php echo date('Y'); ?> AlfaDevs. All rights reserved.</div>
  </footer>

</div>

<script>
let parsedCount = 0;
let currentJobId = null;
let sendLoopActive = false;
let stoppedByUser = false;

function toggleSavedManager(bodyId, headerEl) {
  const body = document.getElementById(bodyId);
  if (!body) return;
  const collapsed = body.classList.toggle('collapsed');
  const chevron = headerEl.querySelector('.sm-chevron');
  if (chevron) chevron.style.transform = collapsed ? 'rotate(-90deg)' : 'rotate(0deg)';
}

function showError(msg) {
  const banner = document.getElementById('error-banner');
  banner.textContent = msg;
  banner.style.display = 'block';
  setTimeout(() => banner.style.display = 'none', 6000);
}

let smtpConfig = null; // loaded from Settings

async function loadSmtpConfig() {
  try {
    const res = await fetch('api/get_smtp_settings.php');
    const data = await res.json();
    smtpConfig = data.settings || null;
  } catch (e) {
    smtpConfig = null;
  }
  document.getElementById('no-smtp-banner').style.display = smtpConfig ? 'none' : 'block';
}
loadSmtpConfig();
refreshSavedLists();
refreshSavedTemplates();

function goStep(n) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + n).classList.add('active');
  document.querySelectorAll('.steps-nav .step').forEach(s => {
    const step = parseInt(s.dataset.step);
    s.classList.toggle('active', step === n);
    s.classList.toggle('done', step < n);
  });
  if (n === 4) buildReview();
  if (n === 2) { refreshSavedLists(); }
  if (n === 3) refreshSavedTemplates();
  window.scrollTo({top: 0, behavior: 'smooth'});
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

async function refreshSavedTemplates() {
  try {
    const res = await fetch('api/list_templates.php');
    const data = await res.json();
    if (!res.ok) return;
    const select = document.getElementById('saved-templates-select');
    const current = select.value;
    select.innerHTML = '<option value="">— Select a saved template —</option>' +
      data.templates.map(name => `<option value="${escapeHtml(name)}">${escapeHtml(name)}</option>`).join('');
    if (current) select.value = current;
  } catch (e) {
    // silent — convenience feature only
  }
}

async function loadSavedTemplate() {
  const name = document.getElementById('saved-templates-select').value;
  if (!name) {
    showError('Pick a saved template first.');
    return;
  }
  try {
    const res = await fetch('api/load_template.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({name})
    });
    const data = await res.json();
    if (!res.ok) {
      showError(data.error || 'Could not load template.');
      return;
    }
    document.getElementById('subject').value = data.template.subject || '';
    document.getElementById('body').value = data.template.body || '';
    if (data.template.unsubscribe_line) {
      document.getElementById('unsubscribe_line').value = data.template.unsubscribe_line;
    }
  } catch (e) {
    showError('Error contacting server: ' + e.message);
  }
}

async function saveCurrentTemplate() {
  const name = document.getElementById('save-template-name').value.trim();
  if (!name) {
    showError('Enter a name for this template first.');
    return;
  }
  const config = {
    name,
    subject: document.getElementById('subject').value,
    body: document.getElementById('body').value,
    unsubscribe_line: document.getElementById('unsubscribe_line').value,
  };
  try {
    const res = await fetch('api/save_template.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(config)
    });
    const data = await res.json();
    if (!res.ok) {
      showError(data.error || 'Could not save template.');
      return;
    }
    document.getElementById('save-template-name').value = '';
    refreshSavedTemplates();
  } catch (e) {
    showError('Error contacting server: ' + e.message);
  }
}

async function deleteSavedTemplate() {
  const select = document.getElementById('saved-templates-select');
  const name = select.value;
  if (!name) {
    showError('Pick a saved template first.');
    return;
  }
  if (!confirm(`Delete saved template "${name}"? This can't be undone.`)) return;
  try {
    const res = await fetch('api/delete_template.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({name})
    });
    const data = await res.json();
    if (!res.ok) {
      showError(data.error || 'Could not delete template.');
      return;
    }
    refreshSavedTemplates();
  } catch (e) {
    showError('Error contacting server: ' + e.message);
  }
}

document.querySelectorAll('.steps-nav .step').forEach(s => {
  s.addEventListener('click', () => goStep(parseInt(s.dataset.step)));
});

document.getElementById('file-upload').addEventListener('change', handleFileUpload);

async function handleFileUpload(e) {
  const file = e.target.files[0];
  if (!file) return;

  document.getElementById('file-upload-name').textContent = file.name;
  const name = file.name.toLowerCase();

  try {
    if (name.endsWith('.csv') || name.endsWith('.txt')) {
      const text = await file.text();
      document.getElementById('raw_recipients').value = text;
      parseRecipients();
    } else if (name.endsWith('.xlsx') || name.endsWith('.xls')) {
      if (typeof XLSX === 'undefined') {
        showError('Excel support failed to load — try a .csv file instead, or refresh the page.');
        return;
      }
      const buf = await file.arrayBuffer();
      const workbook = XLSX.read(buf, {type: 'array'});
      const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
      const csv = XLSX.utils.sheet_to_csv(firstSheet);
      document.getElementById('raw_recipients').value = csv;
      parseRecipients();
    } else {
      showError('Please upload a .csv or .xlsx file.');
    }
  } catch (err) {
    showError('Could not read that file: ' + err.message);
  }
}

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
    if (data.suppressed_count > 0) {
      html += `<div class="notice warn">${data.suppressed_count} of these are on the suppression list and will be skipped automatically when you send.</div>`;
    }
    html += '<div class="table-scroll"><table class="preview"><tr>' + data.fieldnames.map(f => `<th>${f}</th>`).join('') + '</tr>';
    data.preview.forEach(row => {
      html += '<tr>' + data.fieldnames.map(f => `<td>${(row[f]||'')}</td>`).join('') + '</tr>';
    });
    html += '</table></div>';
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
  const host = smtpConfig ? smtpConfig.smtp_host : '(not set)';
  const user = smtpConfig ? smtpConfig.smtp_user : '(not set)';
  document.getElementById('review-summary').innerHTML = `
    <div class="table-scroll">
    <table class="preview">
      <tr><th>SMTP host</th><td>${escapeHtml(host)}</td></tr>
      <tr><th>Sending as</th><td>${escapeHtml(user)}</td></tr>
      <tr><th>Recipients</th><td>${parsedCount}</td></tr>
      <tr><th>Delay between sends</th><td>${delay}s</td></tr>
      <tr><th>Mode</th><td>${dryRun ? 'DRY RUN (no emails sent)' : 'LIVE SEND'}</td></tr>
    </table>
    </div>
  `;
  const warning = document.getElementById('live-warning');
  if (!dryRun) {
    warning.style.display = 'block';
    document.getElementById('live-count').textContent = parsedCount;
  } else {
    warning.style.display = 'none';
  }
  document.getElementById('send-btn').disabled = !smtpConfig;
}

async function startSend() {
  if (!smtpConfig) {
    showError('No SMTP settings saved yet — set them up in Settings first.');
    return;
  }
  const config = {
    smtp_host: smtpConfig.smtp_host,
    smtp_port: smtpConfig.smtp_port,
    smtp_user: smtpConfig.smtp_user,
    smtp_pass: smtpConfig.smtp_pass,
    use_ssl: !!smtpConfig.use_ssl,
    from_name: smtpConfig.from_name,
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
    document.getElementById('stat-suppressed').textContent = data.skipped_suppressed || 0;
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
  document.getElementById('stat-suppressed').textContent = data.suppressed || 0;
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
