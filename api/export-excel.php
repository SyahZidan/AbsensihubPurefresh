<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$admin = require_login('admin');

$type = $_GET['type'] ?? 'attendance';
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

$appName = get_app_name();
$dateStr = date('d-m-Y');

if ($type === 'wage') {
    $filename = "rekap-upah-karyawan-" . date('Y-m-d') . ".xls";
    
    $dateWhere = '';
    $params = [];
    if ($startDate && $endDate) {
        $dateWhere = "AND a.tanggal BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $periode = "Periode: " . date('d M Y', strtotime($startDate)) . " - " . date('d M Y', strtotime($endDate));
    } elseif ($bulan) {
        $dateWhere = "AND DATE_FORMAT(a.tanggal,'%Y-%m') = ?";
        $params[] = $bulan;
        $periode = "Periode Bulan: " . date('F Y', strtotime($bulan . "-01"));
    } else {
        $periode = "Semua Periode";
    }

    $searchWhere = '';
    if ($search !== '') {
        $searchWhere = "AND u.nama LIKE ?";
        $params[] = "%{$search}%";
    }

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
        LEFT JOIN absensi a ON a.user_id = u.id {$dateWhere}
        WHERE u.role = 'karyawan' {$searchWhere}
        GROUP BY u.id, u.nama, u.no_hp
        ORDER BY total DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=" . $filename);
    header("Pragma: no-cache");
    header("Expires: 0");
    
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
                <td colspan="9" class="header-title">REKAP UPAH KARYAWAN</td>
            </tr>
            <tr>
                <td colspan="9" class="header-sub"><?= htmlspecialchars($periode) ?></td>
            </tr>
            <tr>
                <td colspan="9" class="header-sub">Tanggal Cetak: <?= $dateStr ?></td>
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
    exit;

} else {
    $filename = "rekap-absensi-detail-" . date('Y-m-d') . ".xls";
    
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
        $periode = "Periode: " . date('d M Y', strtotime($startDate)) . " - " . date('d M Y', strtotime($endDate));
    } elseif ($startDate !== '') {
        $query .= " AND a.tanggal >= ?";
        $params[] = $startDate;
        $periode = "Periode Mulai: " . date('d M Y', strtotime($startDate));
    } elseif ($endDate !== '') {
        $query .= " AND a.tanggal <= ?";
        $params[] = $endDate;
        $periode = "Periode Sampai: " . date('d M Y', strtotime($endDate));
    } elseif ($bulan !== '') {
        $query .= " AND DATE_FORMAT(a.tanggal, '%Y-%m') = ?";
        $params[] = $bulan;
        $periode = "Periode Bulan: " . date('F Y', strtotime($bulan . "-01"));
    } else {
        $periode = "Semua Periode";
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
    
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=" . $filename);
    header("Pragma: no-cache");
    header("Expires: 0");
    
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
                <td colspan="12" class="header-title"><?= htmlspecialchars($appName) ?></td>
            </tr>
            <tr>
                <td colspan="12" class="header-title">LAPORAN DETAIL ABSENSI & PAYROLL KARYAWAN</td>
            </tr>
            <tr>
                <td colspan="12" class="header-sub"><?= htmlspecialchars($periode) ?></td>
            </tr>
            <tr>
                <td colspan="12" class="header-sub">Tanggal Cetak: <?= $dateStr ?></td>
            </tr>
        </table>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Karyawan</th>
                    <th>Nomor HP</th>
                    <th class="text-center">Tanggal</th>
                    <th class="text-center">Jam Masuk</th>
                    <th class="text-center">Jam Keluar</th>
                    <th class="text-center">Status Absen</th>
                    <th class="text-right">Upah Pokok</th>
                    <th class="text-right">Potongan</th>
                    <th class="text-right">Lembur</th>
                    <th class="text-right">Total Upah</th>
                    <th class="text-center">Status Bayar</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                $sumBase = 0;
                $sumPotongan = 0;
                $sumLembur = 0;
                $sumTotal = 0;
                
                foreach ($rows as $row): 
                    $baseWage = $row['upah_final'] - $row['lembur'] + $row['potongan'];
                    $sumBase += $baseWage;
                    $sumPotongan += $row['potongan'];
                    $sumLembur += $row['lembur'];
                    $sumTotal += $row['upah_final'];
                    $class = ($i % 2 === 0) ? 'class="bg-stripe"' : '';
                ?>
                <tr <?= $class ?>>
                    <td class="text-center"><?= $i++ ?></td>
                    <td class="font-bold"><?= htmlspecialchars($row['nama']) ?></td>
                    <td>'<?= htmlspecialchars($row['no_hp']) ?></td>
                    <td class="text-center"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                    <td class="text-center font-bold" style="color: #0d9488;"><?= htmlspecialchars($row['jam_masuk'] ?? '-') ?></td>
                    <td class="text-center font-bold" style="color: #6366f1;"><?= htmlspecialchars($row['jam_keluar'] ?? '-') ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['status']) ?></td>
                    <td class="text-right"><?= (int)$baseWage ?></td>
                    <td class="text-right" style="color: #dc2626;"><?= (int)$row['potongan'] ?></td>
                    <td class="text-right" style="color: #9333ea;"><?= (int)$row['lembur'] ?></td>
                    <td class="text-right font-bold"><?= (int)$row['upah_final'] ?></td>
                    <td class="text-center font-bold" style="color: <?= $row['status_bayar'] === 'sudah_dibayar' ? '#059669' : '#dc2626' ?>;">
                        <?= $row['status_bayar'] === 'sudah_dibayar' ? 'Lunas' : 'Belum Dibayar' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr class="footer-row">
                    <td colspan="7" class="text-center">TOTAL</td>
                    <td class="text-right"><?= $sumBase ?></td>
                    <td class="text-right" style="color: #dc2626;"><?= $sumPotongan ?></td>
                    <td class="text-right" style="color: #9333ea;"><?= $sumLembur ?></td>
                    <td class="text-right"><?= $sumTotal ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}
