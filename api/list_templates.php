<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$templates = load_message_templates(current_username());

$names = array_keys($templates);
json_response(['templates' => $names]);
