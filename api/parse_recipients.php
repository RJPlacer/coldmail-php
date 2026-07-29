<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$rawText = input_string($input, 'raw_text');

$excludePreviously = !empty($input['exclude_previously_contacted']);
$parsed = audit_recipients_for_user($rawText, current_username(), $excludePreviously);
$fieldnames = $parsed['fieldnames'];
$rows = $parsed['rows'];

if (!empty($parsed['error'])) {
    json_response(['error' => $parsed['error']], 413);
}
if (!$parsed['has_email_column']) {
    json_response(['error' => "No 'email' column found. Make sure your first row has a header including a column named 'email' (any capitalization works, e.g. 'Email' or 'EMAIL')."], 400);
}

json_response([
    'fieldnames' => $fieldnames,
    'count' => count($rows),
    'preview' => array_slice($rows, 0, 5),
    'invalid_count' => (int)($parsed['invalid_count'] ?? 0),
    'duplicate_count' => (int)($parsed['duplicate_count'] ?? 0),
    'suppressed_count' => (int)($parsed['suppressed_count'] ?? 0),
    'previously_contacted_count' => (int)($parsed['previously_contacted_count'] ?? 0),
    'exclusions' => $parsed['exclusions'] ?? [],
]);
