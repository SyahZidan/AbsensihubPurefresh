<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/holidays.php';

verify_csrf();
$admin = require_login('admin');

$tanggal = trim($_POST['tanggal'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');

if ($tanggal === '' || $keterangan === '') {
    flash('error', 'Tanggal dan keterangan wajib diisi.');
    redirect(route_url('admin.holidays'));
}

ensure_holiday_tables();
try {
    $stmt = db()->prepare('INSERT INTO holidays (tanggal, keterangan) VALUES (?, ?)');
    $stmt->execute([$tanggal, $keterangan]);
    log_admin_action((int) $admin['id'], 'holiday_add', 'holidays', (int) db()->lastInsertId(), $keterangan);
    flash('success', 'Hari libur berhasil ditambahkan.');
} catch (PDOException) {
    flash('error', 'Tanggal sudah terdaftar sebagai libur.');
}

redirect(route_url('admin.holidays'));
