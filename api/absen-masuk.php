<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/geofence.php';
require_once __DIR__ . '/../includes/wage-calculator.php';
require_once __DIR__ . '/../includes/quota.php';
require_once __DIR__ . '/../includes/leaves.php';
require_once __DIR__ . '/../includes/holidays.php';
require_once __DIR__ . '/../includes/attendance-rules.php';

verify_csrf();

$user = require_login('karyawan');
$lat = (float) ($_POST['lat'] ?? 0);
$lng = (float) ($_POST['lng'] ?? 0);
$photoData = $_POST['photo_data'] ?? '';

if ($lat === 0.0 || $lng === 0.0 || !is_inside_geofence($lat, $lng)) {
    flash('error', 'Lokasi di luar radius geofence.');
    redirect(route_url('karyawan.checkin'));
}

$today = current_date();
$now = date('H:i:s');

if (is_checkin_closed($now)) {
    flash('error', latest_checkin_message());
    redirect(route_url('karyawan.home'));
}

if (is_holiday($today)) {
    flash('error', holiday_message());
    redirect(route_url('karyawan.home'));
}

if (is_on_approved_leave((int) $user['id'], $today)) {
    flash('error', leave_block_message());
    redirect(route_url('karyawan.home'));
}

$stmt = db()->prepare('SELECT id FROM absensi WHERE user_id = ? AND tanggal = ? LIMIT 1');
$stmt->execute([$user['id'], $today]);
if ($stmt->fetch()) {
    flash('error', 'Kamu sudah absen masuk hari ini.');
    redirect(route_url('karyawan.home'));
}

if (is_blocked_by_daily_quota((int) $user['id'], $today)) {
    log_quota_block_for_user((int) $user['id']);
    flash('error', daily_quota_full_message());
    redirect(route_url('karyawan.home'));
}

$settings = get_settings();
$photoPath = require_attendance_photo($photoData, 'masuk', 'karyawan.checkin');
$status = is_late_checkin($now, $settings) ? 'terlambat' : 'bekerja';

$stmt = db()->prepare(
    'INSERT INTO absensi (user_id, tanggal, jam_masuk, foto_masuk, lat_masuk, lng_masuk, status, status_bayar)
     VALUES (?, ?, ?, ?, ?, ?, ?, "belum_dibayar")'
);
$stmt->execute([$user['id'], $today, $now, $photoPath, $lat, $lng, $status]);

$msg = $status === 'terlambat'
    ? 'Absen masuk berhasil (tercatat terlambat).'
    : 'Absen masuk berhasil.';
flash('success', $msg);
redirect(route_url('karyawan.home'));
