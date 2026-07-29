<?php
require_once __DIR__ . '/../config.php';
require_login(true);

json_input();
$settings = load_smtp_settings(current_username());
if (!$settings || ($error = validate_smtp_settings($settings)) !== null) {
    json_response(['error' => $error ?? 'SMTP settings are missing.'], 400);
}
try {
    test_smtp_connection($settings);
    json_response(['ok' => true, 'message' => 'SMTP connection and authentication succeeded.']);
} catch (Throwable $error) {
    json_response(['error' => 'SMTP test failed: ' . $error->getMessage()], 400);
}
