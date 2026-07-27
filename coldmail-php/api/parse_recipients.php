<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$rawText = $input['raw_text'] ?? '';

$parsed = parse_recipients_csv($rawText);
$fieldnames = $parsed['fieldnames'];
$rows = $parsed['rows'];

if (!in_array('email', array_map('trim', $fieldnames))) {
    json_response(['error' => "No 'email' column found. Make sure your first row has a header including 'email'."], 400);
}

json_response([
    'fieldnames' => $fieldnames,
    'count' => count($rows),
    'preview' => array_slice($rows, 0, 5),
]);
