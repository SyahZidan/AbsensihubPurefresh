<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}
$admin = require_login('admin');

header('Content-Type: application/json');

// Get targeted month: YYYY-MM
$bulan = $_POST['bulan'] ?? $_GET['bulan'] ?? '';
if (empty($bulan)) {
    // Default to last month
    $bulan = date('Y-m', strtotime('first day of last month'));
}

// Validate month format (YYYY-MM)
if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    echo json_encode(['success' => false, 'message' => 'Format bulan tidak valid. Harus YYYY-MM.']);
    exit;
}

$appName = get_app_name();
$dateStr = date('d-m-Y H:i:s');
$periode = "Periode Bulan: " . date('F Y', strtotime($bulan . "-01"));

// Fetch summary data
$stmt = db()->prepare("
    SELECT u.id, u.nama, u.no_hp,
        COUNT(a.id) AS hari,
        COALESCE(SUM(CASE WHEN a.jam_masuk IS NOT NULL THEN 1 ELSE 0 END),0) AS total_hadir,
        COALESCE(SUM(a.lembur),0)         AS lembur,
        COALESCE(SUM(a.potongan),0)       AS potongan,
        COALESCE(SUM(a.upah_final),0)     AS total,
        COALESCE(SUM(CASE WHEN a.status_bayar='sudah_dibayar' THEN a.upah_final ELSE 0 END),0) AS terbayar,
        COALESCE(SUM(CASE WHEN a.status_bayar='belum_dibayar' THEN a.upah_final ELSE 0 END),0) AS belum_bayar
    FROM users u
    LEFT JOIN absensi a ON a.user_id = u.id AND DATE_FORMAT(a.tanggal,'%Y-%m') = ?
    WHERE u.role = 'karyawan'
    GROUP BY u.id, u.nama, u.no_hp
    ORDER BY total DESC
");
$stmt->execute([$bulan]);
$rows = $stmt->fetchAll();

// Check if directory exists
$dir = __DIR__ . '/../public/uploads/backups/';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$filename = "rekap-upah-" . $bulan . ".xls";
$filepath = $dir . $filename;
log_admin_action((int) $admin['id'], 'backup_generate', 'backups', null, "Generate {$filename}");

ob_start();
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header-table { margin-bottom: 20px; }
        .header-title { font-size: 18px; font-weight: bold; color: #0f172a; }
        .header-sub { font-size: 12px; color: #64748b; }
        .data-table { border-collapse: collapse; width: 100%; }
        .data-table th { background-color: #10b981; color: #ffffff; font-weight: bold; border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
        .data-table td { border: 1px solid #cbd5e1; padding: 8px; font-size: 13px; color: #334155; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bg-stripe { background-color: #f8fafc; }
        .font-bold { font-weight: bold; }
        .footer-row { background-color: #f1f5f9; font-weight: bold; }
    </style>
</head>
<body>
    <table class="header-table" border="0">
        <tr>
            <td colspan="9" class="header-title"><?= htmlspecialchars($appName) ?></td>
        </tr>
        <tr>
            <td colspan="9" class="header-title">AUTO-BACKUP REKAP UPAH BULANAN</td>
        </tr>
        <tr>
            <td colspan="9" class="header-sub"><?= htmlspecialchars($periode) ?></td>
        </tr>
        <tr>
            <td colspan="9" class="header-sub">Waktu Backup: <?= $dateStr ?></td>
        </tr>
    </table>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Karyawan</th>
                <th>Nomor HP</th>
                <th class="text-center">Hari Hadir</th>
                <th class="text-right">Lembur</th>
                <th class="text-right">Potongan</th>
                <th class="text-right">Total Upah</th>
                <th class="text-right">Terbayar</th>
                <th class="text-right">Sisa (Belum Dibayar)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            $sumHadir = 0;
            $sumLembur = 0;
            $sumPotongan = 0;
            $sumTotal = 0;
            $sumTerbayar = 0;
            $sumBelum = 0;
            
            foreach ($rows as $row): 
                $sumHadir += $row['total_hadir'];
                $sumLembur += $row['lembur'];
                $sumPotongan += $row['potongan'];
                $sumTotal += $row['total'];
                $sumTerbayar += $row['terbayar'];
                $sumBelum += $row['belum_bayar'];
                $class = ($i % 2 === 0) ? 'class="bg-stripe"' : '';
            ?>
            <tr <?= $class ?>>
                <td class="text-center"><?= $i++ ?></td>
                <td class="font-bold"><?= htmlspecialchars($row['nama']) ?></td>
                <td>'<?= htmlspecialchars($row['no_hp']) ?></td>
                <td class="text-center"><?= (int)$row['total_hadir'] ?> hari</td>
                <td class="text-right"><?= (int)$row['lembur'] ?></td>
                <td class="text-right"><?= (int)$row['potongan'] ?></td>
                <td class="text-right font-bold"><?= (int)$row['total'] ?></td>
                <td class="text-right" style="color: #059669;"><?= (int)$row['terbayar'] ?></td>
                <td class="text-right" style="color: #dc2626;"><?= (int)$row['belum_bayar'] ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="footer-row">
                <td colspan="3" class="text-center">TOTAL</td>
                <td class="text-center"><?= $sumHadir ?> hari</td>
                <td class="text-right"><?= $sumLembur ?></td>
                <td class="text-right"><?= $sumPotongan ?></td>
                <td class="text-right"><?= $sumTotal ?></td>
                <td class="text-right" style="color: #059669;"><?= $sumTerbayar ?></td>
                <td class="text-right" style="color: #dc2626;"><?= $sumBelum ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
<?php
$htmlContent = ob_get_clean();

if (file_put_contents($filepath, $htmlContent) !== false) {
    echo json_encode([
        'success' => true,
        'message' => 'Backup rekap bulanan berhasil dibuat.',
        'filename' => $filename,
        'url' => base_url('public/uploads/backups/' . $filename)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menulis file backup.']);
}
