<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$email = trim($input['email'] ?? '');

if ($email === '') {
    json_response(['error' => 'Missing email.'], 400);
}

remove_suppressed_email($email);

json_response(['ok' => true]);