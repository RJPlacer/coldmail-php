<?php
// Central config: session bootstrapping + shared paths.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_DIR', __DIR__);
define('DATA_DIR', BASE_DIR . '/data');
define('JOBS_DIR', DATA_DIR . '/jobs');
define('USERS_FILE', DATA_DIR . '/users.json');

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
