<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$rawText = $input['raw_text'] ?? '';

$parsed = parse_recipients_csv($rawText);
$fieldnames = $parsed['fieldnames'];
$rows = $parsed['rows'];

if (!$parsed['has_email_column']) {
    json_response(['error' => "No 'email' column found. Make sure your first row has a header including a column named 'email' (any capitalization works, e.g. 'Email' or 'EMAIL')."], 400);
}

$suppressionList = load_suppression_list();
$suppressedCount = 0;
foreach ($rows as $row) {
    if (is_suppressed($row['email'] ?? '', $suppressionList)) {
        $suppressedCount++;
    }
}

json_response([
    'fieldnames' => $fieldnames,
    'count' => count($rows),
    'preview' => array_slice($rows, 0, 5),
    'suppressed_count' => $suppressedCount,
]);
