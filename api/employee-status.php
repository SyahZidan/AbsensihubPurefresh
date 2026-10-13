<?php

require_once __DIR__ . '/../includes/auth.php';

verify_csrf();
$admin = require_login('admin');

$userId = (int) ($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';

function send_response(bool $success, string $message) {
    $isJson = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    if ($isJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    } else {
        flash($success ? 'success' : 'error', $message);
        redirect(route_url('admin.employees'));
    }
}

if ($userId <= 0) {
    send_response(false, 'ID Karyawan tidak valid.');
}

if ($userId === $admin['id']) {
    send_response(false, 'Anda tidak dapat mengubah status Anda sendiri.');
}

$stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || $user['role'] === 'admin') {
    send_response(false, 'Karyawan tidak ditemukan.');
}

if ($action === 'approve' || $action === 'activate') {
    $action = 'aktif';
} elseif ($action === 'suspend') {
    $action = 'nonaktif';
}

if ($action === 'delete') {
    // Cek apakah ada upah belum dibayar
    $stmtUnpaid = db()->prepare("SELECT COUNT(*) AS unpaid FROM absensi WHERE user_id = ? AND status_bayar = 'belum_dibayar' AND upah_final > 0");
    $stmtUnpaid->execute([$userId]);
    $hasUnpaid = (int) ($stmtUnpaid->fetch()['unpaid'] ?? 0) > 0;

    if ($hasUnpaid) {
        // Otomatis suspend
        $stmtSuspend = db()->prepare("UPDATE users SET status = 'nonaktif' WHERE id = ?");
        $stmtSuspend->execute([$userId]);
        notify_user((int) $userId, 'Status akun diperbarui', 'Akun Anda dinonaktifkan karena masih ada upah belum dibayar.', 'warning', route_url('karyawan.home'));
        log_admin_action((int) $admin['id'], 'employee_suspend', 'users', $userId, 'Auto suspend saat delete');
        send_response(false, 'Gagal menghapus! Karyawan ' . $user['nama'] . ' masih memiliki upah yang belum dibayar. Status otomatis diubah menjadi Nonaktif.');
    } else {
        $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        log_admin_action((int) $admin['id'], 'employee_delete', 'users', $userId, 'Hapus karyawan');
        send_response(true, 'Karyawan ' . $user['nama'] . ' berhasil dihapus.');
    }
} elseif (in_array($action, ['aktif', 'nonaktif', 'menunggu_approval'], true)) {
    $stmt = db()->prepare('UPDATE users SET status = ? WHERE id = ?');
    $stmt->execute([$action, $userId]);
    
    $statusText = [
        'aktif' => 'diaktifkan',
        'nonaktif' => 'dinonaktifkan',
        'menunggu_approval' => 'ditangguhkan'
    ][$action];

    notify_user((int) $userId, 'Status akun diperbarui', 'Status akun Anda sekarang: ' . $action . '.', $action === 'aktif' ? 'success' : 'warning', route_url('karyawan.home'));
    log_admin_action((int) $admin['id'], 'employee_status_' . $action, 'users', $userId, 'Status user diperbarui');
    
    send_response(true, 'Status karyawan ' . $user['nama'] . ' berhasil ' . $statusText . '.');
} else {
    send_response(false, 'Aksi tidak dikenal.');
}
