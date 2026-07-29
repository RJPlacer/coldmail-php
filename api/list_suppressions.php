<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$suppressions = array_values(load_suppressions(current_username()));
usort($suppressions, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
json_response(['suppressions' => $suppressions]);
