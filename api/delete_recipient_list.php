<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$name = trim($input['name'] ?? '');

$username = current_username();
$lists = load_recipient_lists($username);

if (!isset($lists[$name])) {
    json_response(['error' => 'List not found.'], 404);
}

unset($lists[$name]);
save_recipient_lists($username, $lists);

json_response(['ok' => true]);
