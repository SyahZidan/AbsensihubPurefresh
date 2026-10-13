<?php
/**
 * Backup otomatis rekap bulan lalu. Cron contoh (harian jam 02:00):
 * php "C:\xampp\htdocs\app - Cursor\scripts\backup-cron.php"
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Jalankan via CLI saja.');
}

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/features.php';

$bulan = date('Y-m', strtotime('first day of last month'));
$dir = __DIR__ . '/../public/uploads/backups/';
$filepath = $dir . "rekap-upah-{$bulan}.xls";

if (file_exists($filepath)) {
    echo "Sudah ada: {$filepath}\n";
    exit(0);
}

if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$stmt = db()->prepare("
    SELECT u.id, u.nama, u.no_hp,
        COALESCE(SUM(CASE WHEN a.jam_masuk IS NOT NULL THEN 1 ELSE 0 END),0) AS total_hadir,
        COALESCE(SUM(a.lembur),0) AS lembur,
        COALESCE(SUM(a.potongan),0) AS potongan,
        COALESCE(SUM(a.upah_final),0) AS total,
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

$html = '<html><body><table border="1"><tr><th>Nama</th><th>HP</th><th>Hadir</th><th>Lembur</th><th>Potongan</th><th>Total</th><th>Terbayar</th><th>Belum</th></tr>';
foreach ($rows as $r) {
    $html .= '<tr><td>' . htmlspecialchars($r['nama']) . '</td><td>' . htmlspecialchars($r['no_hp']) . '</td>';
    $html .= '<td>' . (int) $r['total_hadir'] . '</td><td>' . (int) $r['lembur'] . '</td><td>' . (int) $r['potongan'] . '</td>';
    $html .= '<td>' . (int) $r['total'] . '</td><td>' . (int) $r['terbayar'] . '</td><td>' . (int) $r['belum_bayar'] . '</td></tr>';
}
$html .= '</table></body></html>';

file_put_contents($filepath, $html);
echo "Backup dibuat: {$filepath}\n";
