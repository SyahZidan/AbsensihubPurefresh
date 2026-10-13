<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/geofence.php';
require_once __DIR__ . '/../includes/wage-calculator.php';
require_once __DIR__ . '/../includes/early-checkout.php';

verify_csrf();

$user = require_login('karyawan');
ensure_early_checkout_columns();

$lat = (float) ($_POST['lat'] ?? 0);
$lng = (float) ($_POST['lng'] ?? 0);
$photoData = $_POST['photo_data'] ?? '';

if ($lat === 0.0 || $lng === 0.0 || !is_inside_geofence($lat, $lng)) {
    flash('error', 'Lokasi di luar radius geofence.');
    redirect(route_url('karyawan.checkout'));
}

$today = current_date();
$stmt = db()->prepare('SELECT * FROM absensi WHERE user_id = ? AND tanggal = ? LIMIT 1');
$stmt->execute([$user['id'], $today]);
$attendance = $stmt->fetch();

if (!$attendance || !$attendance['jam_masuk']) {
    flash('error', 'Kamu belum absen masuk.');
    redirect(route_url('karyawan.checkin'));
}

if ($attendance['jam_keluar']) {
    flash('error', 'Kamu sudah absen keluar hari ini.');
    redirect(route_url('karyawan.home'));
}

$now = date('H:i:s');
$keteranganAwal = null;
$buktiAwal = null;
$buktiKeluarAwalFile = [];

foreach (['bukti_keluar_awal_camera', 'bukti_keluar_awal_gallery', 'bukti_keluar_awal'] as $fieldName) {
    if (isset($_FILES[$fieldName]) && ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $buktiKeluarAwalFile = $_FILES[$fieldName];
        break;
    }
}

if (is_early_checkout($now)) {
    $keteranganAwal = trim($_POST['keterangan_keluar_awal'] ?? '');
    if ($keteranganAwal === '') {
        flash('error', 'Keterangan wajib diisi untuk absen keluar sebelum jam ' . substr(jam_batas_keluar_awal(), 0, 5) . '.');
        redirect(route_url('karyawan.checkout'));
    }
    if (strlen($keteranganAwal) > 500) {
        flash('error', 'Keterangan maksimal 500 karakter.');
        redirect(route_url('karyawan.checkout'));
    }
    $buktiAwal = require_early_checkout_proof($buktiKeluarAwalFile, 'karyawan.checkout');
}

$photoPath = require_attendance_photo($photoData, 'keluar', 'karyawan.checkout');
$wage = calculate_wage($attendance['jam_masuk'], $now);

$stmt = db()->prepare(
    'UPDATE absensi SET jam_keluar = ?, foto_keluar = ?, lat_keluar = ?, lng_keluar = ?,
     skema = ?, upah_final = ?, potongan = ?, lembur = ?, status = "selesai",
     keterangan_keluar_awal = ?, bukti_keluar_awal = ? WHERE id = ?'
);
$stmt->execute([
    $now,
    $photoPath,
    $lat,
    $lng,
    $wage['skema'],
    $wage['total'],
    $wage['potongan'],
    $wage['lembur'],
    $keteranganAwal,
    $buktiAwal,
    $attendance['id'],
]);

flash('success', 'Absen keluar berhasil. Upah final: ' . rupiah($wage['total']));
redirect(route_url('karyawan.home'));
