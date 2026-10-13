<?php

require_once __DIR__ . '/../includes/auth.php';

verify_csrf();
$admin = require_login('admin');

header('Content-Type: application/json');

$filename = $_POST['filename'] ?? '';

if (empty($filename)) {
    // Check JSON payload too
    $input = json_decode(file_get_contents('php://input'), true);
    $filename = $input['filename'] ?? '';
}

if (empty($filename)) {
    echo json_encode(['success' => false, 'message' => 'Nama file tidak boleh kosong.']);
    exit;
}

// Prevent directory traversal (only allow pattern rekap-upah-YYYY-MM.xls)
if (!preg_match('/^rekap-upah-\d{4}-\d{2}\.xls$/', $filename)) {
    echo json_encode(['success' => false, 'message' => 'Nama file tidak valid/aman.']);
    exit;
}

$filepath = __DIR__ . '/../public/uploads/backups/' . $filename;

if (file_exists($filepath)) {
    if (unlink($filepath)) {
        echo json_encode(['success' => true, 'message' => 'File backup berhasil dihapus.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus file backup.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'File backup tidak ditemukan.']);
}
