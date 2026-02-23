<?php
require_once __DIR__ . '/config.php';

/**
 * Start a secure PHP session.
 */
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,   // set true in production over HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/**
 * Return true if a user is currently logged in.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user']['username']);
}

/**
 * Redirect to index.php if the visitor is not authenticated.
 */
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_PATH_URL() . '/index.php');
        exit;
    }
}

/**
 * Return the absolute URL prefix (helper used by require_login).
 */
function BASE_PATH_URL(): string {
    // Walk up from the current script to find the app root relative to doc-root
    $docRoot  = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
    $appRoot  = rtrim(BASE_PATH, '/');
    $relative = str_replace($docRoot, '', $appRoot);
    return $relative ?: '';
}

/**
 * Return the current logged-in user's data array, or null.
 */
function get_current_user_data(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Store a flash message in the session.
 *
 * @param string $type    Bootstrap contextual class: success|danger|warning|info
 * @param string $message Human-readable message text
 */
function set_flash_message(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message from the session.
 *
 * @return array|null Associative array with 'type' and 'message', or null.
 */
function get_flash_message(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
