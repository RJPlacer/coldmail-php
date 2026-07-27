<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$lists = load_recipient_lists(current_username());

$summary = [];
foreach ($lists as $name => $rawText) {
    $parsed = parse_recipients_csv($rawText);
    $summary[] = ['name' => $name, 'count' => count($parsed['rows'])];
}

json_response(['lists' => $summary]);
