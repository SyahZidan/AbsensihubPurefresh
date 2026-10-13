<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/features.php';
require_once __DIR__ . '/../includes/geofence.php';

verify_csrf();
$admin = require_login('admin');
ensure_settings_extra_columns();

$defaultKuota = (float) ($_POST['default_kuota'] ?? 8);
$geofenceRadius = (int) ($_POST['geofence_radius'] ?? 100);
$geofenceLat = (float) ($_POST['geofence_lat'] ?? 0);
$geofenceLng = (float) ($_POST['geofence_lng'] ?? 0);
$upahPerJam = (int) ($_POST['upah_per_jam'] ?? 10000);
$multiplierLembur = (float) ($_POST['multiplier_lembur'] ?? 1.5);
$potonganTerlambat = (int) ($_POST['potongan_terlambat'] ?? 5000);
$appName = trim($_POST['app_name'] ?? 'Absensi Hub');
$jamMasukDefault = trim($_POST['jam_masuk_default'] ?? '10:00');
$kuotaKaryawanHarian = (int) ($_POST['kuota_karyawan_harian'] ?? 0);
$menitToleransi = (int) ($_POST['menit_toleransi_terlambat'] ?? 10);
$jamSkemaB = trim($_POST['jam_skema_b'] ?? '13:00');
$jamLemburFlat = trim($_POST['jam_lembur_flat'] ?? '21:30');
$jamBatasKeluarAwal = trim($_POST['jam_batas_keluar_awal'] ?? '17:00');

$upahSkema1 = (int) ($_POST['upah_skema_1'] ?? 70000);
$upahSkema2 = (int) ($_POST['upah_skema_2'] ?? 60000);
$upahSkema3 = (int) ($_POST['upah_skema_3'] ?? 130000);
$upahSkema4 = (int) ($_POST['upah_skema_4'] ?? 120000);

// 20 Skema Baru (Termasuk Skema Jam 8)
$upah0817 = (int) ($_POST['upah_08_17'] ?? 90000);
$upah0917 = (int) ($_POST['upah_09_17'] ?? 80000);
$upah1017 = (int) ($_POST['upah_10_17'] ?? 70000);
$upah1317 = (int) ($_POST['upah_13_17'] ?? 60000);

$upah0819 = (int) ($_POST['upah_08_19'] ?? 100000);
$upah0820 = (int) ($_POST['upah_08_20'] ?? 110000);
$upah0821 = (int) ($_POST['upah_08_21'] ?? 120000);
$upah0822 = (int) ($_POST['upah_08_22'] ?? 130000);

$upah0919 = (int) ($_POST['upah_09_19'] ?? 90000);
$upah0920 = (int) ($_POST['upah_09_20'] ?? 100000);
$upah0921 = (int) ($_POST['upah_09_21'] ?? 110000);
$upah0922 = (int) ($_POST['upah_09_22'] ?? 120000);

$upah1019 = (int) ($_POST['upah_10_19'] ?? 80000);
$upah1020 = (int) ($_POST['upah_10_20'] ?? 90000);
$upah1021 = (int) ($_POST['upah_10_21'] ?? 100000);
$upah1022 = (int) ($_POST['upah_10_22'] ?? 110000);

$upah1319 = (int) ($_POST['upah_13_19'] ?? 70000);
$upah1320 = (int) ($_POST['upah_13_20'] ?? 80000);
$upah1321 = (int) ($_POST['upah_13_21'] ?? 90000);
$upah1322 = (int) ($_POST['upah_13_22'] ?? 100000);

if ($defaultKuota < 1 || $geofenceRadius < 10 || $geofenceLat === 0.0 || $geofenceLng === 0.0 || $upahPerJam < 0 || $kuotaKaryawanHarian < 0) {
    flash('error', 'Semua kolom pengaturan harus diisi dengan benar.');
    redirect(route_url('admin.settings'));
}

$oldSettings = get_settings();

$stmt = db()->prepare('UPDATE settings SET 
    default_kuota = ?, 
    geofence_radius = ?, 
    geofence_lat = ?, 
    geofence_lng = ?,
    upah_per_jam = ?,
    multiplier_lembur = ?,
    potongan_terlambat = ?,
    app_name = ?,
    jam_masuk_default = ?,
    kuota_karyawan_harian = ?,
    menit_toleransi_terlambat = ?,
    jam_skema_b = ?,
    jam_lembur_flat = ?,
    jam_batas_keluar_awal = ?,
    upah_skema_1 = ?,
    upah_skema_2 = ?,
    upah_skema_3 = ?,
    upah_skema_4 = ?,
    upah_08_17 = ?,
    upah_09_17 = ?,
    upah_10_17 = ?,
    upah_13_17 = ?,
    upah_08_19 = ?,
    upah_08_20 = ?,
    upah_08_21 = ?,
    upah_08_22 = ?,
    upah_09_19 = ?,
    upah_09_20 = ?,
    upah_09_21 = ?,
    upah_09_22 = ?,
    upah_10_19 = ?,
    upah_10_20 = ?,
    upah_10_21 = ?,
    upah_10_22 = ?,
    upah_13_19 = ?,
    upah_13_20 = ?,
    upah_13_21 = ?,
    upah_13_22 = ?
    WHERE id = 1');
$stmt->execute([
    $defaultKuota,
    $geofenceRadius,
    $geofenceLat,
    $geofenceLng,
    $upahPerJam,
    $multiplierLembur,
    $potonganTerlambat,
    $appName,
    $jamMasukDefault,
    $kuotaKaryawanHarian,
    $menitToleransi,
    $jamSkemaB,
    $jamLemburFlat,
    $jamBatasKeluarAwal,
    $upahSkema1,
    $upahSkema2,
    $upahSkema3,
    $upahSkema4,
    $upah0817,
    $upah0917,
    $upah1017,
    $upah1317,
    $upah0819,
    $upah0820,
    $upah0821,
    $upah0822,
    $upah0919,
    $upah0920,
    $upah0921,
    $upah0922,
    $upah1019,
    $upah1020,
    $upah1021,
    $upah1022,
    $upah1319,
    $upah1320,
    $upah1321,
    $upah1322
]);

$changes = [];
if ((int)$oldSettings['upah_skema_1'] !== $upahSkema1) $changes[] = "Upah Skema 1 menjadi " . rupiah($upahSkema1);
if ((int)$oldSettings['upah_skema_2'] !== $upahSkema2) $changes[] = "Upah Skema 2 menjadi " . rupiah($upahSkema2);
if ((int)$oldSettings['upah_skema_3'] !== $upahSkema3) $changes[] = "Upah Skema 3 menjadi " . rupiah($upahSkema3);
if ((int)$oldSettings['upah_skema_4'] !== $upahSkema4) $changes[] = "Upah Skema 4 menjadi " . rupiah($upahSkema4);
if ((int)$oldSettings['upah_per_jam'] !== $upahPerJam) $changes[] = "Upah Dasar Per Jam menjadi " . rupiah($upahPerJam);
if (abs((float)$oldSettings['multiplier_lembur'] - $multiplierLembur) > 0.001) $changes[] = "Multiplier Lembur menjadi {$multiplierLembur}x";
if ((int)$oldSettings['potongan_terlambat'] !== $potonganTerlambat) $changes[] = "Potongan Terlambat menjadi " . rupiah($potonganTerlambat);

if (!empty($changes)) {
    $detail = "Penyesuaian gaji: " . implode(', ', $changes);
    log_admin_action((int) $admin['id'], 'settings_update', 'settings', 1, $detail);
} else {
    log_admin_action((int) $admin['id'], 'settings_update', 'settings', 1, 'Update konfigurasi sistem');
}

flash('success', 'Pengaturan berhasil diperbarui.');
redirect(route_url('admin.settings'));
