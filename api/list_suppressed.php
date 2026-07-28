<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$list = load_suppression_list();

$out = [];
foreach ($list as $email => $meta) {
    $out[] = [
        'email' => $email,
        'added_by' => $meta['added_by'] ?? '',
        'added_at' => $meta['added_at'] ?? '',
        'reason' => $meta['reason'] ?? '',
    ];
}
usort($out, fn($a, $b) => strcmp($b['added_at'], $a['added_at']));

json_response(['suppressed' => $out]);