<?php

require_once __DIR__ . '/../includes/auth.php';

$admin = require_login('admin');

$userId = (int) ($_GET['user_id'] ?? $_GET['karyawan_id'] ?? 0);
$startDate = $_GET['start_date'] ?? $_GET['dari'] ?? '';
$endDate = $_GET['end_date'] ?? $_GET['sampai'] ?? '';
$statusBayar = $_GET['status_bayar'] ?? $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$bulan = $_GET['bulan'] ?? '';

if ($statusBayar === 'lunas') {
    $statusBayar = 'sudah_dibayar';
} elseif ($statusBayar === 'belum') {
    $statusBayar = 'belum_dibayar';
}

$query = "SELECT a.*, u.nama, u.no_hp FROM absensi a JOIN users u ON u.id = a.user_id WHERE a.upah_final >= 0";
$params = [];

if ($userId > 0) {
    $query .= " AND a.user_id = ?";
    $params[] = $userId;
}

if ($startDate !== '' && $endDate !== '') {
    $query .= " AND a.tanggal BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
} elseif ($startDate !== '') {
    $query .= " AND a.tanggal >= ?";
    $params[] = $startDate;
} elseif ($endDate !== '') {
    $query .= " AND a.tanggal <= ?";
    $params[] = $endDate;
} elseif ($bulan !== '') {
    $query .= " AND DATE_FORMAT(a.tanggal, '%Y-%m') = ?";
    $params[] = $bulan;
}

if ($statusBayar !== '' && $statusBayar !== 'all') {
    $query .= " AND a.status_bayar = ?";
    $params[] = $statusBayar;
}

if ($search !== '') {
    $query .= " AND u.nama LIKE ?";
    $params[] = "%{$search}%";
}

$query .= " ORDER BY a.tanggal DESC, a.jam_masuk DESC";

$stmt = db()->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Generate CSV
$filename = "rekap-upah-" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 support
fputs($output, "\xEF\xBB\xBF");

// Header row
fputcsv($output, ['Nama', 'Nomor HP', 'Tanggal', 'Jam Masuk', 'Jam Keluar', 'Skema', 'Upah Pokok', 'Potongan', 'Lembur', 'Total Upah', 'Status Absen', 'Status Bayar']);

foreach ($rows as $row) {
    // Determine base wage from final, overtime and potongan
    $baseWage = $row['upah_final'] - $row['lembur'] + $row['potongan'];
    
    fputcsv($output, [
        $row['nama'],
        "'" . $row['no_hp'], // Single quote to prevent excel stripping leading zero
        $row['tanggal'],
        $row['jam_masuk'] ?? '-',
        $row['jam_keluar'] ?? '-',
        $row['skema'] ?? '-',
        $baseWage,
        $row['potongan'],
        $row['lembur'],
        $row['upah_final'],
        $row['status'],
        $row['status_bayar'] === 'sudah_dibayar' ? 'Sudah Dibayar' : 'Belum Dibayar'
    ]);
}

fclose($output);
exit;
