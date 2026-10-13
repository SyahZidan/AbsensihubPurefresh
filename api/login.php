<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

verify_csrf();

$noHp = trim($_POST['no_hp'] ?? '');
$password = $_POST['password'] ?? '';

if ($noHp === '' || !preg_match('/^[0-9]+$/', $noHp)) {
    flash('error', 'Nomor HP hanya boleh berisi angka.');
    redirect(route_url('login'));
}

$rate = check_login_rate_limit($noHp);
if (!$rate['allowed']) {
    $mins = (int) ceil($rate['retry_after'] / 60);
    flash('error', "Terlalu banyak percobaan login. Coba lagi dalam {$mins} menit.");
    redirect(route_url('login'));
}

$stmt = db()->prepare('SELECT * FROM users WHERE no_hp = ? LIMIT 1');
$stmt->execute([$noHp]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'] ?? '')) {
    record_failed_login($noHp);
    flash('error', 'Nomor HP atau password salah.');
    redirect(route_url('login'));
}

if ($user['status'] !== 'aktif') {
    flash('error', 'Akun belum aktif. Tunggu approval admin.');
    redirect(route_url('login'));
}

clear_login_rate_limit($noHp);
login_user($user);
redirect(route_url($user['role'] === 'admin' ? 'admin.dashboard' : 'karyawan.home'));
