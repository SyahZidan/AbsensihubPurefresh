<?php

require_once __DIR__ . '/../includes/auth.php';

verify_csrf();

$noHp = trim($_POST['no_hp'] ?? '');
$stmt = db()->prepare('SELECT id, nama FROM users WHERE no_hp = ? LIMIT 1');
$stmt->execute([$noHp]);
$user = $stmt->fetch();

if (!$user) {
    flash('error', 'Nomor HP tidak terdaftar.');
    redirect(route_url('forgot-password'));
}

ensure_feature_tables();
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + 3600);

$ins = db()->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
$ins->execute([(int) $user['id'], $token, $expires]);

$link = route_url('reset-password', ['token' => $token]);
flash('success', 'Link reset dibuat. Buka dalam 1 jam: ' . $link);
redirect(route_url('login'));
