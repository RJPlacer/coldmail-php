<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$settings = public_smtp_settings(load_smtp_settings(current_username()));

json_response(['settings' => $settings]);
