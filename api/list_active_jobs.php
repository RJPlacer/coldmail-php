<?php
require_once __DIR__ . '/../config.php';
require_login(true);

json_response(['jobs' => list_user_active_jobs(current_username())]);
