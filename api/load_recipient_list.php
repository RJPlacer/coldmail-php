<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$name = trim($input['name'] ?? '');

$lists = load_recipient_lists(current_username());

if (!isset($lists[$name])) {
    json_response(['error' => 'List not found.'], 404);
}

json_response(['name' => $name, 'raw_text' => $lists[$name]]);
