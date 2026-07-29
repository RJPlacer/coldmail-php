<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$name = trim(input_string($input, 'name'));

$username = current_username();
if (!remove_message_template($username, $name)) {
    json_response(['error' => 'Template not found.'], 404);
}

json_response(['ok' => true]);
