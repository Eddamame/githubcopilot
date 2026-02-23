<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

/**
 * Attempt to log in a user.
 *
 * @return bool True on success, false on failure.
 */
function login(string $username, string $password): bool {
    $user = get_user($username);
    if ($user === null) {
        return false;
    }
    if (!password_verify($password, $user['password'])) {
        return false;
    }

    // Prevent session fixation
    session_regenerate_id(true);

    // Store safe (non-password) user data in session
    $_SESSION['user'] = [
        'username'      => $user['username'],
        'full_name'     => $user['full_name'],
        'email'         => $user['email'],
        'phone'         => $user['phone'],
        'profile_photo' => $user['profile_photo'],
        'pets'          => $user['pets'],
    ];

    return true;
}

/**
 * Log out the current user and destroy the session.
 */
function logout(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }
}

/**
 * Generate a CSRF token, store it in the session, and return it.
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify that the supplied token matches the session token.
 */
function verify_csrf_token(string $token): bool {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
