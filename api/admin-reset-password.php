<?php

require_once __DIR__ . '/../includes/auth.php';

verify_csrf();
$admin = require_login('admin');

header('Content-Type: application/json');

$userId = (int) ($_POST['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
    exit;
}

$stmt = db()->prepare("SELECT id, nama, no_hp FROM users WHERE id = ? AND role = 'karyawan' LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Karyawan tidak ditemukan.']);
    exit;
}

$temp = substr(str_shuffle('23456789abcdefghjkmnpqrstuvwxyz'), 0, 8);
$hash = password_hash($temp, PASSWORD_BCRYPT);
db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $userId]);

notify_user($userId, 'Password direset admin', 'Password sementara Anda telah direset. Segera ganti setelah login.', 'warning', route_url('karyawan.profile'));
log_admin_action((int) $admin['id'], 'password_reset_admin', 'users', $userId, 'Reset password karyawan');

echo json_encode([
    'success' => true,
    'message' => 'Password sementara untuk ' . $user['nama'] . ': ' . $temp,
    'temp_password' => $temp,
]);
