<?php
/**
 * Manage team login accounts for Dispatch.
 *
 * Usage:
 *   php manage_users.php add <username>
 *   php manage_users.php remove <username>
 *   php manage_users.php list
 */

require_once __DIR__ . '/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function prompt_hidden(string $label): string {
    echo $label;
    // Try to hide input on unix-like systems; falls back to visible input on Windows CLI.
    if (stripos(PHP_OS, 'WIN') === false) {
        system('stty -echo');
        $value = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        $value = trim(fgets(STDIN));
    }
    return $value;
}

function cmd_add(string $username): void {
    if (($error = validate_new_username($username)) !== null) {
        echo $error . "\n";
        return;
    }
    $users = load_users();
    if (isset($users[$username])) {
        echo "User '$username' already exists. Remove first if you want to reset their password.\n";
        return;
    }
    $pw = prompt_hidden("Set password for '$username': ");
    $pwConfirm = prompt_hidden("Confirm password: ");
    if ($pw !== $pwConfirm) {
        echo "Passwords did not match. Try again.\n";
        return;
    }
    if (strlen($pw) < 10) {
        echo "Password should be at least 10 characters.\n";
        return;
    }
    $added = false;
    update_users(function (array $currentUsers) use ($username, $pw, &$added): array {
        if (!isset($currentUsers[$username])) {
            $currentUsers[$username] = ['password_hash' => password_hash($pw, PASSWORD_DEFAULT)];
            $added = true;
        }
        return $currentUsers;
    });
    if (!$added) {
        echo "User '$username' was added by another process.\n";
        return;
    }
    echo "Added user '$username'.\n";
}

function cmd_remove(string $username): void {
    $users = load_users();
    if (!isset($users[$username])) {
        echo "No such user '$username'.\n";
        return;
    }
    update_users(function (array $currentUsers) use ($username): array {
        unset($currentUsers[$username]);
        return $currentUsers;
    });
    echo "Removed user '$username'.\n";
}

function cmd_list(): void {
    $users = load_users();
    if (empty($users)) {
        echo "No users yet. Add one with: php manage_users.php add <username>\n";
        return;
    }
    echo "Team members with login access:\n";
    foreach (array_keys($users) as $u) {
        echo "  - $u\n";
    }
}

$args = $argv;
array_shift($args); // script name

if (count($args) < 1) {
    echo "Usage:\n  php manage_users.php add <username>\n  php manage_users.php remove <username>\n  php manage_users.php list\n";
    exit(1);
}

$action = $args[0];
if ($action === 'add' && count($args) === 2) {
    cmd_add($args[1]);
} elseif ($action === 'remove' && count($args) === 2) {
    cmd_remove($args[1]);
} elseif ($action === 'list') {
    cmd_list();
} else {
    echo "Usage:\n  php manage_users.php add <username>\n  php manage_users.php remove <username>\n  php manage_users.php list\n";
}
