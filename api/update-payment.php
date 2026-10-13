<?php

require_once __DIR__ . '/../includes/auth.php';

verify_csrf();
$admin = require_login('admin');

$id = (int) ($_POST['id'] ?? $_POST['absensi_id'] ?? 0);
$ids = $_POST['ids'] ?? $_POST['absensi_ids'] ?? [];
$userId = (int) ($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';
$status = $_POST['status'] ?? 'sudah_dibayar';

if ($status === 'lunas') {
    $status = 'sudah_dibayar';
} elseif ($status === 'belum') {
    $status = 'belum_dibayar';
}

function send_response(bool $success, string $message) {
    $isJson = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    if ($isJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    } else {
        flash($success ? 'success' : 'error', $message);
        $referer = $_SERVER['HTTP_REFERER'] ?? route_url('admin.payment');
        redirect($referer);
    }
}

if (!in_array($status, ['belum_dibayar', 'sudah_dibayar'], true)) {
    send_response(false, 'Status pembayaran tidak valid.');
}

if ($action === 'pay_all' && $userId > 0) {
    $stmt = db()->prepare('UPDATE absensi SET status_bayar = "sudah_dibayar" WHERE user_id = ? AND upah_final > 0 AND status_bayar = "belum_dibayar"');
    $stmt->execute([$userId]);
    notify_user($userId, 'Pembayaran upah', 'Semua upah Anda ditandai sudah dibayar.', 'success', route_url('karyawan.history'));
    log_admin_action((int) $admin['id'], 'payment_pay_all', 'users', $userId, 'Pembayaran massal');
    send_response(true, 'Semua upah karyawan berhasil ditandai Lunas.');
} elseif (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("UPDATE absensi SET status_bayar = ? WHERE id IN ($placeholders)");
    $params = array_merge([$status], array_map('intval', $ids));
    $stmt->execute($params);
    log_admin_action((int) $admin['id'], 'payment_bulk_' . $status, 'absensi', null, count($ids) . ' item');
    send_response(true, count($ids) . ' transaksi berhasil diperbarui.');
} elseif ($id > 0) {
    $rowQ = db()->prepare('SELECT user_id FROM absensi WHERE id = ? LIMIT 1');
    $rowQ->execute([$id]);
    $row = $rowQ->fetch();
    $stmt = db()->prepare('UPDATE absensi SET status_bayar = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
    if ($row && $status === 'sudah_dibayar') {
        notify_user((int) $row['user_id'], 'Pembayaran upah', 'Satu transaksi upah Anda sudah dibayar.', 'success', route_url('karyawan.history'));
    }
    log_admin_action((int) $admin['id'], 'payment_single_' . $status, 'absensi', $id, 'Update pembayaran');
    send_response(true, 'Status pembayaran berhasil diperbarui.');
} else {
    send_response(false, 'Aksi tidak valid atau data kosong.');
}
