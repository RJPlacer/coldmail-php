<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$input = json_input();
$name = trim(input_string($input, 'name'));

if ($name === '') {
    json_response(['error' => 'Please enter a name for this template.'], 400);
}
if (strlen($name) > 80) {
    json_response(['error' => 'Template name is too long (max 80 characters).'], 400);
}

$subject = input_string($input, 'subject');
$body = input_string($input, 'body');
$unsubscribeLine = input_string($input, 'unsubscribe_line');

if (trim($subject) === '' || trim($body) === '') {
    json_response(['error' => 'Subject and body are required to save a template.'], 400);
}
if (strlen($subject) > MAX_SUBJECT_BYTES || strlen($body) > MAX_BODY_BYTES) {
    json_response(['error' => 'Subject or message body is too large.'], 413);
}

$username = current_username();
upsert_message_template($username, $name, [
    'subject' => $subject,
    'body' => $body,
    'unsubscribe_line' => $unsubscribeLine,
]);

json_response(['ok' => true, 'name' => $name]);
