<?php
require_once __DIR__ . '/config.php';

$token = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? input_string($_POST, 'token')
    : (is_string($_GET['token'] ?? null) ? $_GET['token'] : '');
$subscription = verify_unsubscribe_token($token);
$success = false;
$error = null;

if (!$subscription) {
    http_response_code(400);
    $error = 'This unsubscribe link is invalid.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    add_suppression($subscription['username'], $subscription['email'], 'unsubscribe-link');
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Unsubscribe — AlfaDevs Dispatch</title>
<style>
  * { box-sizing:border-box; }
  body { margin:0; min-height:100vh; display:grid; place-items:center; padding:20px;
    background:#eef6ff; color:#0c1f3d; font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
  .card { width:min(100%,480px); background:#fff; border-radius:18px; padding:34px;
    box-shadow:0 16px 48px rgba(11,63,140,.12); text-align:center; }
  h1 { font-size:24px; margin:0 0 10px; }
  p { color:#5c7089; line-height:1.6; }
  .email { color:#0c1f3d; font-weight:700; overflow-wrap:anywhere; }
  button { border:0; border-radius:10px; padding:12px 22px; color:#fff; cursor:pointer;
    background:linear-gradient(135deg,#1e7fe0,#0b3f8c); font:inherit; font-weight:700; }
  .success { color:#1a7a4c; }
  .error { color:#d24545; }
</style>
</head>
<body>
<main class="card">
<?php if ($success): ?>
  <h1 class="success">You’re unsubscribed</h1>
  <p><span class="email"><?php echo htmlspecialchars($subscription['email'], ENT_QUOTES, 'UTF-8'); ?></span> will be excluded from future campaigns sent through this account.</p>
<?php elseif ($error): ?>
  <h1 class="error">Link unavailable</h1>
  <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
<?php else: ?>
  <h1>Unsubscribe from future emails?</h1>
  <p>Confirm that <span class="email"><?php echo htmlspecialchars($subscription['email'], ENT_QUOTES, 'UTF-8'); ?></span> should not receive future campaigns from this sender.</p>
  <form method="POST">
    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
    <button type="submit">Confirm unsubscribe</button>
  </form>
<?php endif; ?>
</main>
</body>
</html>
