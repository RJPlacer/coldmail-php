<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$jobs = list_user_jobs(current_username());

json_response(['jobs' => $jobs]);
