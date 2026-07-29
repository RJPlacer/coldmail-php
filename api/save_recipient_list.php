<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$name = trim(input_string($input, 'name'));
$rawText = input_string($input, 'raw_text');

if ($name === '') {
    json_response(['error' => 'Please enter a name for this list.'], 400);
}
if (strlen($name) > 80) {
    json_response(['error' => 'List name is too long (max 80 characters).'], 400);
}

$parsed = parse_recipients_csv($rawText);
if (!empty($parsed['error'])) {
    json_response(['error' => $parsed['error']], 413);
}
if (empty($parsed['rows'])) {
    json_response(['error' => 'No valid recipients found to save — validate the list first.'], 400);
}

$username = current_username();
upsert_recipient_list($username, $name, $rawText);

json_response(['ok' => true, 'name' => $name, 'count' => count($parsed['rows'])]);
