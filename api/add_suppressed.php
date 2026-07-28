<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$email = trim($input['email'] ?? '');
$reason = trim($input['reason'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Enter a valid email address.'], 400);
}

add_suppressed_email($email, current_username(), $reason);

json_response(['ok' => true]);