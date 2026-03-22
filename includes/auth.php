<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/user.php';
require_once __DIR__ . '/crypto.php';

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function login_user(string $identifier, string $password): bool {
    $user = get_user_by_email($identifier);
    if (!$user) return false;
    $plain = decrypt_password($user['password_enc']);
    if ($plain !== $password) return false;
    start_session();
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['user_nom'] = $user['nom'];
    return true;
}

function logout_user(): void {
    start_session();
    $_SESSION = [];
    session_destroy();
}

function is_logged_in(): bool {
    start_session();
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    return get_user($_SESSION['user_id']);
}

function require_login(string $redirect = '/index.php'): void {
    if (!is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}

// ADMIN AUTH
function login_admin(string $user, string $pass): bool {
    if ($user !== ADMIN_USER || $pass !== ADMIN_PASS) return false;
    start_session();
    session_regenerate_id(true);
    $_SESSION['admin_logged'] = true;
    return true;
}

function logout_admin(): void {
    start_session();
    unset($_SESSION['admin_logged']);
    session_destroy();
}

function is_admin_logged(): bool {
    start_session();
    return !empty($_SESSION['admin_logged']);
}

function require_admin(string $redirect = '/admin/index.php'): void {
    if (!is_admin_logged()) {
        header('Location: ' . $redirect);
        exit;
    }
}
