<?php

require_once __DIR__ . '/../includes/auth.php';

verify_csrf();
$admin = require_login('admin');

header('Content-Type: application/json');

$id = (int) ($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? '');
$catatan = trim($_POST['catatan'] ?? '');

if ($id <= 0 || !in_array($action, ['setuju', 'tolak'], true)) {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
    exit;
}

$stmt = db()->prepare('SELECT lr.*, u.nama FROM leave_requests lr JOIN users u ON u.id = lr.user_id WHERE lr.id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Data pengajuan tidak ditemukan.']);
    exit;
}

if ($row['status'] !== 'menunggu') {
    echo json_encode(['success' => false, 'message' => 'Pengajuan sudah diproses.']);
    exit;
}

$newStatus = $action === 'setuju' ? 'disetujui' : 'ditolak';
$upd = db()->prepare('UPDATE leave_requests SET status = ?, catatan_admin = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
$upd->execute([$newStatus, $catatan, (int) $admin['id'], $id]);

notify_user((int) $row['user_id'], 'Update pengajuan izin', "Pengajuan {$row['jenis']} Anda {$newStatus}.", $newStatus === 'disetujui' ? 'success' : 'danger', route_url('karyawan.leaves'));
log_admin_action((int) $admin['id'], 'leave_' . $newStatus, 'leave_requests', $id, "Karyawan {$row['nama']}");

echo json_encode(['success' => true, 'message' => 'Pengajuan berhasil diproses.']);
