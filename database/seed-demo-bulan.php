<?php
/**
 * Generate: php database/seed-demo-bulan.php
 * Output: database/seed-demo-1bulan.sql (UTF-8)
 */

$hash = '$2y$10$80oOd5eeKQ4CEbsB1h35ie/k.nb6p1JHSNeUV89PQsYaAAFZb4fdm';
$lat = -6.2241263;
$lng = 106.6530524;

$employees = [
    ['nama' => 'Andi Pratama', 'hp' => '081910010001', 'bank' => 'BCA', 'norek' => '1001001001', 'pattern' => 'full'],
    ['nama' => 'Dewi Lestari', 'hp' => '081910010002', 'bank' => 'Mandiri', 'norek' => '2002002002', 'pattern' => 'late'],
    ['nama' => 'Rudi Hartono', 'hp' => '081910010003', 'bank' => 'BRI', 'norek' => '3003003003', 'pattern' => 'sparse'],
    ['nama' => 'Sari Wulandari', 'hp' => '081910010004', 'bank' => 'BNI', 'norek' => '4004004004', 'pattern' => 'overtime'],
    ['nama' => 'Fajar Nugroho', 'hp' => '081910010005', 'bank' => 'BCA', 'norek' => '5005005005', 'pattern' => 'mixed'],
    ['nama' => 'Maya Indira', 'hp' => '081910010006', 'bank' => 'GoPay', 'norek' => '081910010006', 'pattern' => 'irregular'],
    ['nama' => 'Hendra Wijaya', 'hp' => '081910010007', 'bank' => 'OVO', 'norek' => '081910010007', 'pattern' => 'good'],
    ['nama' => 'Putri Anggraini', 'hp' => '081910010008', 'bank' => 'Dana', 'norek' => '081910010008', 'pattern' => 'rare'],
    ['nama' => 'Bambang Susilo', 'hp' => '081910010009', 'bank' => 'Mandiri', 'norek' => '9009009009', 'pattern' => 'average'],
    ['nama' => 'Lina Kartika', 'hp' => '081910010010', 'bank' => 'BRI', 'norek' => '1010101010', 'pattern' => 'early_leave'],
];

function weekdays_april_2026(): array
{
    $days = [];
    $start = new DateTime('2026-04-01');
    $end = new DateTime('2026-04-30');
    while ($start <= $end) {
        if ((int) $start->format('N') <= 5) {
            $days[] = $start->format('Y-m-d');
        }
        $start->modify('+1 day');
    }
    return $days;
}

function should_attend(string $pattern, int $dayNum, int $empIndex): bool
{
    return match ($pattern) {
        'full' => true,
        'late' => $dayNum % 7 !== 0,
        'sparse' => in_array($dayNum % 10, [1, 3, 5, 8], true),
        'overtime' => $dayNum % 5 !== 0,
        'mixed' => $dayNum % 3 !== 0,
        'irregular' => ($dayNum + $empIndex) % 4 !== 0,
        'good' => $dayNum % 9 !== 0 && $dayNum % 11 !== 0,
        'rare' => in_array($dayNum % 15, [2, 5, 9, 12], true),
        'average' => ($dayNum + $empIndex) % 5 !== 0,
        'early_leave' => $dayNum % 6 !== 0,
        default => true,
    };
}

function shift_for_day(string $pattern, int $dayNum, int $seed): array
{
    $r = ($dayNum + $seed) % 11;
    $masuk = match ($pattern) {
        'full' => '08:00:00',
        'good' => $r < 8 ? '08:00:00' : '08:18:00',
        'late' => $r < 4 ? '08:05:00' : ($r < 7 ? '08:22:00' : '08:35:00'),
        'overtime' => $r < 2 ? '08:00:00' : '08:10:00',
        'early_leave' => $r < 3 ? '08:00:00' : '08:15:00',
        'irregular' => ['07:55:00', '08:40:00', '09:10:00', '08:00:00'][$r % 4],
        default => $r < 6 ? '08:00:00' : ($r < 9 ? '08:12:00' : '08:25:00'),
    };
    $keluar = match ($pattern) {
        'full', 'good' => '17:00:00',
        'overtime' => $r < 3 ? '22:15:00' : ($r < 6 ? '18:30:00' : '17:05:00'),
        'early_leave' => $r < 4 ? '15:30:00' : ($r < 7 ? '16:45:00' : '17:10:00'),
        'late' => '17:05:00',
        'irregular' => ['16:00:00', '17:00:00', '17:30:00', '15:00:00'][$r % 4],
        default => $r < 2 ? '18:00:00' : '17:00:00',
    };
    return [$masuk, $keluar];
}

function sql_str(?string $v): string
{
    return $v === null ? 'NULL' : "'" . str_replace("'", "''", $v) . "'";
}

$days = weekdays_april_2026();
$userRows = [];
$absensiRows = [];

foreach ($employees as $i => $emp) {
    $hp = $emp['hp'];
    $userRows[] = sprintf(
        "(%s, %s, %s, NULL, %s, %s, 'karyawan', 'aktif', '2026-03-15 08:00:00')",
        sql_str($emp['nama']),
        sql_str($hp),
        sql_str($hash),
        sql_str($emp['bank']),
        sql_str($emp['norek'])
    );

    foreach ($days as $date) {
        $dayNum = (int) date('j', strtotime($date));
        if (!should_attend($emp['pattern'], $dayNum, $i)) {
            continue;
        }
        [$masuk, $keluar] = shift_for_day($emp['pattern'], $dayNum, $i + 1);

        $inMin = (int) substr($masuk, 0, 2) * 60 + (int) substr($masuk, 3, 2);
        $outMin = (int) substr($keluar, 0, 2) * 60 + (int) substr($keluar, 3, 2);
        $late = $inMin > 490;
        $potongan = $late ? 5000 : 0;
        $base = $inMin <= 780 ? 70000 : 60000;
        $lembur = 0;
        if ($outMin >= 1290) {
            $base = $inMin <= 780 ? 130000 : 120000;
        } elseif ($outMin > $inMin + 480) {
            $lembur = (int) floor((($outMin - $inMin - 480) / 60) * 15000);
        }
        $total = $base + $lembur - $potongan;
        $skema = $inMin <= 780 ? 'A' : 'B';
        $status = $late ? 'terlambat' : 'selesai';
        $statusBayar = ($dayNum % 5 === 0) ? 'sudah_dibayar' : 'belum_dibayar';
        $ketAwal = null;
        $buktiAwal = null;
        if ($outMin < 1020 && $emp['pattern'] === 'early_leave') {
            $ketAwal = 'Izin pulang lebih awal - keperluan keluarga';
            $buktiAwal = '/public/uploads/demo-bukti.jpg';
        }

        $uidExpr = "(SELECT id FROM users WHERE no_hp = " . sql_str($hp) . " LIMIT 1)";
        $fotoM = '/public/uploads/demo-masuk-' . str_replace('-', '', $date) . '-' . substr($hp, -4) . '.jpg';
        $fotoK = '/public/uploads/demo-keluar-' . str_replace('-', '', $date) . '-' . substr($hp, -4) . '.jpg';

        $absensiRows[] = sprintf(
            "(%s, '%s', '%s', '%s', %s, %s, %.7f, %.7f, %.7f, %.7f, '%s', %d, %d, %d, %s, %s, '%s', '%s', '%s')",
            $uidExpr,
            $date,
            $masuk,
            $keluar,
            sql_str($fotoM),
            sql_str($fotoK),
            $lat,
            $lng,
            $lat,
            $lng,
            $skema,
            $total,
            $potongan,
            $lembur,
            sql_str($ketAwal),
            sql_str($buktiAwal),
            $status,
            $statusBayar,
            $date . ' ' . $masuk
        );
    }
}

$sql = <<<'HDR'
-- phpMyAdmin: pilih database di kiri, tab SQL, paste semua, Go
-- 10 karyawan demo April 2026 | Password: 123456 | HP: 081910010001 s/d 010

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE absensi
  ADD COLUMN IF NOT EXISTS keterangan_keluar_awal TEXT NULL,
  ADD COLUMN IF NOT EXISTS bukti_keluar_awal VARCHAR(255) NULL;

DELETE FROM notifications WHERE user_id IN (SELECT id FROM users WHERE no_hp LIKE '081910010%');
DELETE FROM leave_requests WHERE user_id IN (SELECT id FROM users WHERE no_hp LIKE '081910010%');
DELETE FROM quota_blocks WHERE user_id IN (SELECT id FROM users WHERE no_hp LIKE '081910010%');
DELETE FROM password_resets WHERE user_id IN (SELECT id FROM users WHERE no_hp LIKE '081910010%');
DELETE FROM absensi WHERE user_id IN (SELECT id FROM users WHERE no_hp LIKE '081910010%');
DELETE FROM users WHERE no_hp LIKE '081910010%';

SET FOREIGN_KEY_CHECKS = 1;

HDR;

$sql .= "INSERT INTO users (nama, no_hp, password, foto_profil, bank_name, norek, role, status, created_at) VALUES\n";
$sql .= implode(",\n", $userRows) . ";\n\n";

$sql .= "INSERT INTO absensi (user_id, tanggal, jam_masuk, jam_keluar, foto_masuk, foto_keluar, lat_masuk, lng_masuk, lat_keluar, lng_keluar, skema, upah_final, potongan, lembur, keterangan_keluar_awal, bukti_keluar_awal, status, status_bayar, created_at) VALUES\n";
$sql .= implode(",\n", $absensiRows) . ";\n\n";

$sql .= <<<'TAIL'
SELECT u.nama, u.no_hp, COUNT(a.id) AS hari_masuk,
  SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END) AS terlambat,
  SUM(a.upah_final) AS total_upah
FROM users u
LEFT JOIN absensi a ON a.user_id = u.id AND a.tanggal BETWEEN '2026-04-01' AND '2026-04-30'
WHERE u.no_hp LIKE '081910010%'
GROUP BY u.id, u.nama, u.no_hp
ORDER BY u.no_hp;

TAIL;

$out = __DIR__ . '/seed-demo-1bulan.sql';
file_put_contents($out, $sql);
echo "Written: {$out} (" . strlen($sql) . " bytes)\n";
