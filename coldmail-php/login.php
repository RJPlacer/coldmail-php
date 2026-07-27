<?php
require_once __DIR__ . '/config.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $users = load_users();

    if (isset($users[$username]) && password_verify($password, $users[$username]['password_hash'])) {
        // Regenerate session id on login to avoid session fixation.
        session_regenerate_id(true);
        $_SESSION['username'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Incorrect username or password.';
        http_response_code(401);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in — AlfaDevs Dispatch</title>
<style>
  :root {
    --blue-1: #eaf5ff;
    --blue-2: #cfe6ff;
    --blue-accent: #1e7fe0;
    --blue-deep: #0b3f8c;
    --navy: #0c1f3d;
    --muted: #5c7089;
    --danger: #d24545;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: radial-gradient(circle at 20% 15%, #ffffff 0%, var(--blue-1) 35%, var(--blue-2) 100%);
    color: var(--navy);
  }
  .card {
    width: 100%;
    max-width: 380px;
    background: #ffffff;
    border-radius: 20px;
    padding: 40px 36px;
    box-shadow: 0 20px 60px rgba(11, 63, 140, 0.15);
  }
  .logo-row {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 28px;
  }
  .logo-row img { height: 34px; }
  h1 {
    font-size: 20px;
    font-weight: 700;
    text-align: center;
    margin: 0 0 4px;
    color: var(--navy);
  }
  .sub {
    text-align: center;
    color: var(--muted);
    font-size: 13px;
    margin-bottom: 28px;
  }
  label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 6px;
    margin-top: 16px;
  }
  label:first-of-type { margin-top: 0; }
  input {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1.5px solid #dce6f2;
    background: #f7fafd;
    font-size: 14px;
    font-family: inherit;
    color: var(--navy);
  }
  input:focus {
    outline: none;
    border-color: var(--blue-accent);
    background: #fff;
  }
  button {
    width: 100%;
    margin-top: 24px;
    padding: 13px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--blue-accent), var(--blue-deep));
    color: white;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.02em;
    cursor: pointer;
    transition: opacity 0.15s ease;
  }
  button:hover { opacity: 0.92; }
  .error {
    background: #fdecec;
    color: var(--danger);
    border: 1px solid #f5c6c6;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 16px;
  }
  .footnote {
    text-align: center;
    font-size: 12px;
    color: var(--muted);
    margin-top: 24px;
  }
</style>
</head>
<body>
  <div class="card">
    <div class="logo-row">
      <img src="static/logo.png" alt="AlfaDevs">
      <h1 style="color: #1dc2fc;">AlfaDevs</h1>
    </div>
    <div class="sub">Sign in to send team email campaigns</div>

    <?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <label>Username</label>
      <input type="text" name="username" autocomplete="username" required autofocus>
      <label>Password</label>
      <input type="password" name="password" autocomplete="current-password" required>
      <button type="submit">Sign In</button>
    </form>

    <div class="footnote">No account? Ask a teammate to add you with manage_users.php</div>
  </div>
</body>
</html>
