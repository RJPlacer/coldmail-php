<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$name = trim($input['name'] ?? '');

$username = current_username();
$templates = load_message_templates($username);

if (!isset($templates[$name])) {
    json_response(['error' => 'Template not found.'], 404);
}

unset($templates[$name]);
save_message_templates($username, $templates);

json_response(['ok' => true]);
