<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$name = trim($input['name'] ?? '');

if ($name === '') {
    json_response(['error' => 'Please enter a name for this template.'], 400);
}
if (strlen($name) > 80) {
    json_response(['error' => 'Template name is too long (max 80 characters).'], 400);
}

$subject = $input['subject'] ?? '';
$body = $input['body'] ?? '';
$unsubscribeLine = $input['unsubscribe_line'] ?? '';

if (trim($subject) === '' || trim($body) === '') {
    json_response(['error' => 'Subject and body are required to save a template.'], 400);
}

$username = current_username();
$templates = load_message_templates($username);
$templates[$name] = [
    'subject' => $subject,
    'body' => $body,
    'unsubscribe_line' => $unsubscribeLine,
];
save_message_templates($username, $templates);

json_response(['ok' => true, 'name' => $name]);
