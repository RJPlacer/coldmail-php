<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$email = trim(input_string($input, 'email'));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Enter a valid email address.'], 400);
}
add_suppression(current_username(), $email, 'manual');
json_response(['ok' => true]);
