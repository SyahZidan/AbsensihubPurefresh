<?php

require_once __DIR__ . '/../includes/auth.php';

require_login('admin');

$bulan = $_GET['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    flash('error', 'Format bulan tidak valid.');
    redirect(route_url('admin.wage'));
}

$stmt = db()->prepare("
    SELECT a.*, u.nama, u.no_hp
    FROM absensi a
    JOIN users u ON u.id = a.user_id
    WHERE DATE_FORMAT(a.tanggal, '%Y-%m') = ? AND a.jam_keluar IS NOT NULL
    ORDER BY u.nama ASC, a.tanggal ASC
");
$stmt->execute([$bulan]);
$rows = $stmt->fetchAll();

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Upah Batch <?= e($bulan) ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; color: #0f172a; }
        .slip { page-break-after: always; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 24px; max-width: 480px; }
        .slip:last-child { page-break-after: auto; }
        .row { display: flex; justify-content: space-between; margin: 4px 0; font-size: 14px; }
        .total { font-weight: bold; font-size: 16px; border-top: 1px solid #e2e8f0; margin-top: 8px; padding-top: 8px; }
        @media print { .noprint { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button class="noprint" onclick="window.print()">Cetak semua slip / Simpan PDF</button>
    <h1 class="noprint">Slip Upah — <?= e(date('F Y', strtotime($bulan . '-01'))) ?> (<?= count($rows) ?> slip)</h1>
    <?php foreach ($rows as $row): ?>
    <div class="slip">
        <h2 style="margin:0 0 8px;"><?= e(get_app_name()) ?></h2>
        <p style="margin:0 0 12px; color:#64748b; font-size:12px;">Slip upah harian</p>
        <div class="row"><span>Nama</span><strong><?= e($row['nama']) ?></strong></div>
        <div class="row"><span>Tanggal</span><strong><?= e($row['tanggal']) ?></strong></div>
        <div class="row"><span>Masuk – Keluar</span><strong><?= e(substr($row['jam_masuk'] ?? '-', 0, 5)) ?> – <?= e(substr($row['jam_keluar'] ?? '-', 0, 5)) ?></strong></div>
        <div class="row"><span>Lembur</span><strong><?= rupiah((int) $row['lembur']) ?></strong></div>
        <div class="row"><span>Potongan</span><strong><?= rupiah((int) $row['potongan']) ?></strong></div>
        <div class="row total"><span>Total</span><span><?= rupiah((int) $row['upah_final']) ?></span></div>
        <?php if (!empty($row['alasan_edit_upah'])): ?>
        <div class="row" style="background-color: #fef3c7; padding: 8px; border-radius: 6px; margin-top: 8px; flex-direction: column; align-items: flex-start; gap: 2px; border: 1px solid #fde68a;">
            <span style="font-weight: bold; color: #b45309; font-size: 10px; text-transform: uppercase;">Catatan Koreksi Admin:</span>
            <span style="font-size: 12px; color: #78350f; font-style: italic;">"<?= e($row['alasan_edit_upah']) ?>"</span>
        </div>
        <?php endif; ?>
        <p style="font-size:11px;color:#64748b;margin-top:8px;">Status bayar: <?= e($row['status_bayar']) ?></p>
    </div>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
    <p>Tidak ada data slip untuk bulan ini.</p>
    <?php endif; ?>
</body>
</html>
