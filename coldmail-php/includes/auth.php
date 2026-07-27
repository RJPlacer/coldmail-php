<?php
// Authentication helpers: user storage, login gate.

function load_users(): array {
    if (!file_exists(USERS_FILE)) {
        return [];
    }
    $raw = file_get_contents(USERS_FILE);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function save_users(array $users): void {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function current_username(): ?string {
    return $_SESSION['username'] ?? null;
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
}
