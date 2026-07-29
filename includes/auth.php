<?php
// Authentication helpers: user storage, login gate.

function load_users(): array {
    return read_json_file(USERS_FILE, []);
}

function save_users(array $users): void {
    write_json_file(USERS_FILE, $users, 0600);
}

function update_users(callable $mutator): array {
    return update_json_file(USERS_FILE, $mutator, 0600);
}

function validate_new_username(string $username): ?string {
    if ($username === '' || strlen($username) > 120) {
        return 'Username must be between 1 and 120 characters.';
    }
    if (preg_match('/[\x00-\x1F\x7F]/', $username)) {
        return 'Username cannot contain control characters.';
    }
    return null;
}

function login_attempts_file(): string {
    return DATA_DIR . '/login_attempts.json';
}

function login_rate_key(): string {
    return hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function login_is_rate_limited(): bool {
    $attempts = read_json_file(login_attempts_file(), []);
    $recent = array_values(array_filter(
        $attempts[login_rate_key()] ?? [],
        fn($timestamp): bool => is_int($timestamp) && $timestamp > time() - 900
    ));
    return count($recent) >= 8;
}

function record_failed_login(): void {
    $key = login_rate_key();
    $cutoff = time() - 900;
    update_json_file(login_attempts_file(), function (array $attempts) use ($key, $cutoff): array {
        foreach ($attempts as $attemptKey => $timestamps) {
            $recent = array_values(array_filter(
                is_array($timestamps) ? $timestamps : [],
                fn($timestamp): bool => is_int($timestamp) && $timestamp > $cutoff
            ));
            if ($recent) {
                $attempts[$attemptKey] = $recent;
            } else {
                unset($attempts[$attemptKey]);
            }
        }
        $attempts[$key][] = time();
        return $attempts;
    });
}

function clear_failed_logins(): void {
    $key = login_rate_key();
    update_json_file(login_attempts_file(), function (array $attempts) use ($key): array {
        unset($attempts[$key]);
        return $attempts;
    });
}

function current_username(): ?string {
    return $_SESSION['username'] ?? null;
}

function ensure_csrf_token(): string {
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_token(): string {
    return $_SESSION['_csrf'] ?? '';
}

function require_csrf(): void {
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '';
    if (!is_string($provided) || !hash_equals(csrf_token(), $provided)) {
        if (str_contains(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/api/')) {
            json_response(['error' => 'Invalid or expired security token. Refresh the page and try again.'], 403);
        }
        http_response_code(403);
        exit('Invalid or expired security token.');
    }
}

/**
 * Call at the top of any page/endpoint that requires a signed-in user.
 * For API endpoints (JSON), pass $isApi = true so it returns 401 JSON
 * instead of redirecting to the login page.
 */
function require_login(bool $isApi = false): void {
    if (!current_username()) {
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not logged in.']);
            exit;
        }
        header('Location: login.php');
        exit;
    }

    // Authentication state is now read-only. Releasing PHP's session lock
    // keeps a slow SMTP request from blocking the user's other requests.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
