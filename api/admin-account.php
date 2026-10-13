<?php

require_once __DIR__ . '/../includes/auth.php';

verify_csrf();
$admin = require_login('admin');

$action = $_POST['action'] ?? '';

if ($action === 'update_phone') {
    $noHp = trim($_POST['no_hp'] ?? '');

    if ($noHp === '' || !preg_match('/^[0-9]+$/', $noHp)) {
        flash('error', 'Nomor telepon admin hanya boleh berisi angka.');
        redirect(route_url('admin.settings'));
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE no_hp = ? AND id != ? LIMIT 1');
    $stmt->execute([$noHp, $admin['id']]);
    if ($stmt->fetch()) {
        flash('error', 'Nomor telepon ini sudah digunakan akun lain.');
        redirect(route_url('admin.settings'));
    }

    $stmt = db()->prepare('UPDATE users SET no_hp = ? WHERE id = ? AND role = "admin"');
    $stmt->execute([$noHp, $admin['id']]);

    log_admin_action((int) $admin['id'], 'admin_phone_update', 'users', (int) $admin['id'], 'Ubah nomor telepon admin');
    flash('success', 'Nomor telepon admin berhasil diperbarui.');
    redirect(route_url('admin.settings'));
}

if ($action === 'update_password') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
        flash('error', 'Password lama, password baru, dan konfirmasi wajib diisi.');
        redirect(route_url('admin.settings'));
    }

    if (!password_verify($oldPassword, $admin['password'] ?? '')) {
        flash('error', 'Password lama admin tidak sesuai.');
        redirect(route_url('admin.settings'));
    }

    if (strlen($newPassword) < 6) {
        flash('error', 'Password baru minimal 6 karakter.');
        redirect(route_url('admin.settings'));
    }

    if ($newPassword !== $confirmPassword) {
        flash('error', 'Konfirmasi password baru tidak sama.');
        redirect(route_url('admin.settings'));
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = db()->prepare('UPDATE users SET password = ? WHERE id = ? AND role = "admin"');
    $stmt->execute([$hashedPassword, $admin['id']]);

    log_admin_action((int) $admin['id'], 'admin_password_update', 'users', (int) $admin['id'], 'Ubah password admin');
    flash('success', 'Password admin berhasil diperbarui.');
    redirect(route_url('admin.settings'));
}

flash('error', 'Aksi akun admin tidak valid.');
redirect(route_url('admin.settings'));
