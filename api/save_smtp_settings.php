<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();

$settings = [
    'smtp_host' => trim($input['smtp_host'] ?? ''),
    'smtp_port' => (int)($input['smtp_port'] ?? 587),
    'use_ssl' => !empty($input['use_ssl']),
    'smtp_user' => trim($input['smtp_user'] ?? ''),
    'smtp_pass' => $input['smtp_pass'] ?? '',
    'from_name' => trim($input['from_name'] ?? ''),
];

if ($settings['smtp_host'] === '' || $settings['smtp_user'] === '' || $settings['smtp_pass'] === '') {
    json_response(['error' => 'Host, email address, and app password are all required.'], 400);
}

save_smtp_settings(current_username(), $settings);

json_response(['ok' => true]);
