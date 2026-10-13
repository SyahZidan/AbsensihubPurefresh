<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/features.php';

verify_csrf();
$admin = require_login('admin');

$absensiId = (int) ($_POST['absensi_id'] ?? 0);
$upahFinal = (int) ($_POST['upah_final'] ?? 0);
$lembur = (int) ($_POST['lembur'] ?? 0);
$potongan = (int) ($_POST['potongan'] ?? 0);
$alasan = trim($_POST['alasan_edit_upah'] ?? '');

function send_response(bool $success, string $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($absensiId <= 0) {
    send_response(false, 'ID absensi tidak valid.');
}

if ($upahFinal < 0 || $lembur < 0 || $potongan < 0) {
    send_response(false, 'Nilai upah, lembur, dan denda tidak boleh negatif.');
}

if (strlen($alasan) < 5) {
    send_response(false, 'Alasan pengubahan upah wajib diisi (minimal 5 karakter).');
}

// Cek apakah data absensi ada
$stmt = db()->prepare('SELECT a.*, u.nama FROM absensi a JOIN users u ON u.id = a.user_id WHERE a.id = ? LIMIT 1');
$stmt->execute([$absensiId]);
$attendance = $stmt->fetch();

if (!$attendance) {
    send_response(false, 'Data absensi tidak ditemukan.');
}

try {
    // Jalankan update
    $stmtUpdate = db()->prepare('UPDATE absensi SET upah_final = ?, lembur = ?, potongan = ?, alasan_edit_upah = ? WHERE id = ?');
    $stmtUpdate->execute([$upahFinal, $lembur, $potongan, $alasan, $absensiId]);

    // Catat log admin
    log_admin_action(
        (int) $admin['id'], 
        'wage_manual_edit', 
        'absensi', 
        $absensiId, 
        "Edit upah manual karyawan {$attendance['nama']} (Tgl: {$attendance['tanggal']}) menjadi Upah Bersih: {$upahFinal}, Lembur: {$lembur}, Potongan: {$potongan}. Alasan: {$alasan}"
    );

    // Kirim notifikasi sistem ke karyawan
    notify_user(
        (int) $attendance['user_id'], 
        'Koreksi Upah Absensi', 
        "Upah Anda pada tanggal " . date('d M Y', strtotime($attendance['tanggal'])) . " dikoreksi oleh admin menjadi " . rupiah($upahFinal) . ". Alasan: {$alasan}",
        'info', 
        route_url('karyawan.history')
    );

    send_response(true, 'Upah absensi berhasil diperbarui secara manual.');
} catch (PDOException $e) {
    send_response(false, 'Gagal menyimpan perubahan ke database: ' . $e->getMessage());
}
