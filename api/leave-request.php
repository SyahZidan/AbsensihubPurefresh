<?php

require_once __DIR__ . '/../includes/auth.php';

verify_csrf();
$user = require_login('karyawan');

$jenis = trim($_POST['jenis'] ?? 'izin');
$tanggalMulai = trim($_POST['tanggal_mulai'] ?? '');
$tanggalSelesai = trim($_POST['tanggal_selesai'] ?? '');
$alasan = trim($_POST['alasan'] ?? '');

if (!in_array($jenis, ['izin', 'sakit', 'cuti'], true) || $tanggalMulai === '' || $tanggalSelesai === '' || $alasan === '') {
    flash('error', 'Data pengajuan belum lengkap.');
    redirect(route_url('karyawan.leaves'));
}

if ($tanggalMulai > $tanggalSelesai) {
    flash('error', 'Tanggal mulai harus sebelum tanggal selesai.');
    redirect(route_url('karyawan.leaves'));
}

$stmt = db()->prepare('
    INSERT INTO leave_requests (user_id, jenis, tanggal_mulai, tanggal_selesai, alasan, status)
    VALUES (?, ?, ?, ?, ?, "menunggu")
');
$stmt->execute([(int) $user['id'], $jenis, $tanggalMulai, $tanggalSelesai, $alasan]);
$leaveId = (int) db()->lastInsertId();

$admins = db()->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($admins as $adminId) {
    notify_user((int) $adminId, 'Pengajuan izin baru', "{$user['nama']} mengajukan {$jenis} ({$tanggalMulai} s/d {$tanggalSelesai}).", 'warning', route_url('admin.leaves'));
}

flash('success', 'Pengajuan izin/cuti berhasil dikirim.');
redirect(route_url('karyawan.leaves'));
