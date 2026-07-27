<?php
// Recipient parsing + merge-tag rendering helpers.

/**
 * Parse pasted CSV text. First row must be a header row and must include
 * an "email" column. Returns ['fieldnames' => [...], 'rows' => [...]].
 */
function parse_recipients_csv(string $rawText): array {
    $rawText = trim($rawText);
    if ($rawText === '') {
        return ['fieldnames' => [], 'rows' => []];
    }

    $lines = preg_split('/\r\n|\r|\n/', $rawText);
    $handle = fopen('php://temp', 'r+');
    foreach ($lines as $line) {
        fwrite($handle, $line . "\n");
    }
    rewind($handle);

    $header = fgetcsv($handle);
    if ($header === false) {
        fclose($handle);
        return ['fieldnames' => [], 'rows' => []];
    }
    $header = array_map('trim', $header);

    $rows = [];
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) === 1 && trim($data[0]) === '') {
            continue; // skip blank lines
        }
        $row = [];
        foreach ($header as $i => $key) {
            $row[$key] = isset($data[$i]) ? trim($data[$i]) : '';
        }
        if (!empty($row['email'])) {
            $rows[] = $row;
        }
    }
    fclose($handle);

    return ['fieldnames' => $header, 'rows' => $rows];
}

/**
 * Replace {{field}} merge tags in a template string with values from $row.
 */
function render_merge_tags(string $template, array $row): string {
    $result = $template;
    foreach ($row as $key => $value) {
        $result = str_replace('{{' . $key . '}}', $value, $result);
        $result = str_replace('{{ ' . $key . ' }}', $value, $result);
    }
    return $result;
}

function json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// --- Saved recipient lists (per user) ---

function safe_username_slug(string $username): string {
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);
}

function recipient_lists_file(string $username): string {
    $dir = DATA_DIR . '/lists';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    return $dir . '/' . safe_username_slug($username) . '.json';
}

function load_recipient_lists(string $username): array {
    $file = recipient_lists_file($username);
    if (!file_exists($file)) {
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function save_recipient_lists(string $username, array $lists): void {
    file_put_contents(recipient_lists_file($username), json_encode($lists, JSON_PRETTY_PRINT));
}

// --- Saved SMTP settings (per user) ---

function smtp_settings_file(string $username): string {
    $dir = DATA_DIR . '/smtp';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    return $dir . '/' . safe_username_slug($username) . '.json';
}

function load_smtp_settings(string $username): ?array {
    $file = smtp_settings_file($username);
    if (!file_exists($file)) {
        return null;
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

function save_smtp_settings(string $username, array $settings): void {
    $file = smtp_settings_file($username);
    file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT));
    chmod($file, 0600);
}
