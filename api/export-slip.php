<?php

require_once __DIR__ . '/../includes/auth.php';

$user = require_login('karyawan');
$id = (int) ($_GET['absensi_id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Slip tidak valid.');
    redirect(route_url('karyawan.history'));
}

$stmt = db()->prepare('SELECT * FROM absensi WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id, (int) $user['id']]);
$row = $stmt->fetch();

if (!$row) {
    flash('error', 'Data slip tidak ditemukan.');
    redirect(route_url('karyawan.history'));
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Upah <?= e($row['tanggal']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 24px; color: #0f172a; }
        .card { border: 1px solid #cbd5e1; border-radius: 12px; padding: 16px; max-width: 520px; }
        .row { display: flex; justify-content: space-between; margin: 6px 0; font-size: 14px; }
        .total { font-weight: bold; font-size: 18px; margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .muted { color: #64748b; font-size: 12px; }
        @media print { .noprint { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button class="noprint" onclick="window.print()">Cetak / Simpan PDF</button>
    <div class="card">
        <h2 style="margin-top: 0;">Slip Upah Harian</h2>
        <p class="muted"><?= e(get_app_name()) ?></p>
        <div class="row"><span>Nama</span><strong><?= e($user['nama']) ?></strong></div>
        <div class="row"><span>Tanggal</span><strong><?= e($row['tanggal']) ?></strong></div>
        <div class="row"><span>Jam Masuk</span><strong><?= e($row['jam_masuk'] ?? '-') ?></strong></div>
        <div class="row"><span>Jam Keluar</span><strong><?= e($row['jam_keluar'] ?? '-') ?></strong></div>
        <div class="row"><span>Skema</span><strong><?= e($row['skema'] ?? '-') ?></strong></div>
        <div class="row"><span>Lembur</span><strong><?= rupiah((int) $row['lembur']) ?></strong></div>
        <div class="row"><span>Potongan</span><strong><?= rupiah((int) $row['potongan']) ?></strong></div>
        <div class="row total"><span>Total Upah</span><span><?= rupiah((int) $row['upah_final']) ?></span></div>
        <?php if (!empty($row['alasan_edit_upah'])): ?>
        <div class="row" style="background-color: #fef3c7; padding: 10px; border-radius: 8px; margin-top: 10px; flex-direction: column; align-items: flex-start; gap: 4px; border: 1px solid #fde68a;">
            <span style="font-weight: bold; color: #b45309; font-size: 11px; text-transform: uppercase;">Catatan Koreksi Admin:</span>
            <span style="font-size: 13px; color: #78350f; font-style: italic;">"<?= e($row['alasan_edit_upah']) ?>"</span>
        </div>
        <?php endif; ?>
        <p class="muted">Status bayar: <?= e($row['status_bayar']) ?></p>
    </div>
</body>
</html>
