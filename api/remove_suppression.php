<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$email = trim(input_string($input, 'email'));
if (!remove_suppression(current_username(), $email)) {
    json_response(['error' => 'Suppressed address not found.'], 404);
}
json_response(['ok' => true]);
