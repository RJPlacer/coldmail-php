<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$settings = load_smtp_settings(current_username());

json_response(['settings' => $settings]);
