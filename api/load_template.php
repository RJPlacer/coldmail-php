<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$name = trim($input['name'] ?? '');

$templates = load_message_templates(current_username());

if (!isset($templates[$name])) {
    json_response(['error' => 'Template not found.'], 404);
}

json_response(['name' => $name, 'template' => $templates[$name]]);
