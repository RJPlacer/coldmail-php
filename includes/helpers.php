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
