<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$name = trim($input['name'] ?? '');
$rawText = $input['raw_text'] ?? '';

if ($name === '') {
    json_response(['error' => 'Please enter a name for this list.'], 400);
}
if (strlen($name) > 80) {
    json_response(['error' => 'List name is too long (max 80 characters).'], 400);
}

$parsed = parse_recipients_csv($rawText);
if (empty($parsed['rows'])) {
    json_response(['error' => 'No valid recipients found to save — validate the list first.'], 400);
}

$username = current_username();
$lists = load_recipient_lists($username);
$lists[$name] = $rawText;
save_recipient_lists($username, $lists);

json_response(['ok' => true, 'name' => $name, 'count' => count($parsed['rows'])]);
