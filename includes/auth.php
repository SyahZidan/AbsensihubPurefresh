<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/features.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(?string $role = null): array
{
    ensure_feature_tables();
    $user = current_user();
    if (!$user) {
        flash('error', 'Silakan login dulu.');
        redirect(route_url('login'));
    }

    if ($role !== null && $user['role'] !== $role) {
        redirect(route_url($user['role'] === 'admin' ? 'admin.dashboard' : 'karyawan.home'));
    }

    return $user;
}
