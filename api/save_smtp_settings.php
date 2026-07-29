<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();

$existing = load_smtp_settings(current_username()) ?? [];
$submittedPassword = input_string($input, 'smtp_pass');
$settings = [
    'smtp_host' => trim(input_string($input, 'smtp_host')),
    'smtp_port' => (int)input_string($input, 'smtp_port', '587'),
    'use_ssl' => !empty($input['use_ssl']),
    'smtp_user' => trim(input_string($input, 'smtp_user')),
    'smtp_pass' => $submittedPassword !== ''
        ? $submittedPassword
        : (string)($existing['smtp_pass'] ?? ''),
    'from_name' => trim(input_string($input, 'from_name')),
];

if (($validationError = validate_smtp_settings($settings)) !== null) {
    json_response(['error' => $validationError], 400);
}

save_smtp_settings(current_username(), $settings);

json_response(['ok' => true]);
