<?php

require_once __DIR__ . '/../includes/auth.php';

verify_csrf();

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['password_confirm'] ?? '';

if (strlen($password) < 6 || $password !== $confirm) {
    flash('error', 'Password minimal 6 karakter dan harus sama.');
    redirect(route_url('reset-password', ['token' => $token]));
}

ensure_feature_tables();
$stmt = db()->prepare('SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
$stmt->execute([$token]);
$row = $stmt->fetch();

if (!$row) {
    flash('error', 'Link reset tidak valid atau sudah kedaluwarsa.');
    redirect(route_url('forgot-password'));
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$db = db();
$db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, (int) $row['user_id']]);
$db->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([(int) $row['id']]);

flash('success', 'Password berhasil diubah. Silakan login.');
redirect(route_url('login'));
