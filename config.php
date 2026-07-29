<?php
// Central config: session bootstrapping + shared paths.

define('BASE_DIR', __DIR__);
define('DATA_DIR', getenv('DISPATCH_DATA_DIR') ?: BASE_DIR . '/data');
define('JOBS_DIR', DATA_DIR . '/jobs');
define('USERS_FILE', DATA_DIR . '/users.json');
define('MAX_JSON_REQUEST_BYTES', 8 * 1024 * 1024);
define('MAX_RECIPIENT_CSV_BYTES', 5 * 1024 * 1024);
define('MAX_RECIPIENTS', 5000);
define('MAX_SUBJECT_BYTES', 998);
define('MAX_BODY_BYTES', 500000);

if (PHP_SAPI !== 'cli') {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    set_exception_handler(function (Throwable $error): void {
        error_log('Dispatch error: ' . $error);
        http_response_code(500);
        if (str_contains(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/api/')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'An unexpected server error occurred.']);
        } else {
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'An unexpected server error occurred.';
        }
    });
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
} elseif (!isset($_SESSION) || !is_array($_SESSION)) {
    $_SESSION = [];
}

if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0700, true);
}
if (!is_dir(JOBS_DIR)) {
    mkdir(JOBS_DIR, 0700, true);
}

require_once BASE_DIR . '/vendor/phpmailer/src/Exception.php';
require_once BASE_DIR . '/vendor/phpmailer/src/PHPMailer.php';
require_once BASE_DIR . '/vendor/phpmailer/src/SMTP.php';
require_once BASE_DIR . '/includes/auth.php';
require_once BASE_DIR . '/includes/helpers.php';

ensure_csrf_token();
migrate_data_storage_v2();
