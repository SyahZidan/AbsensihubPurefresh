<?php

require_once __DIR__ . '/../includes/auth.php';

verify_csrf();

$nama        = trim($_POST['nama'] ?? '');
$noHp        = trim($_POST['no_hp'] ?? '');
$password    = $_POST['password'] ?? '';
$bankName    = trim($_POST['bank_name'] ?? '');
$norek       = trim($_POST['norek'] ?? '');
$tglLahir    = trim($_POST['tanggal_lahir'] ?? '') ?: null;
$fotoPath    = null;
$fotoKtpPath = null;

$logbookMode = is_logbook_mode();

if ($nama === '' || $noHp === '' || $password === '' || (!$logbookMode && ($bankName === '' || $norek === ''))) {
    flash('error', $logbookMode ? 'Nama, nomor HP, dan password wajib diisi.' : 'Semua kolom termasuk data pembayaran wajib diisi.');
    redirect(route_url('register'));
}

if (!preg_match('/^[0-9]+$/', $noHp)) {
    flash('error', 'Nomor HP hanya boleh berisi angka.');
    redirect(route_url('register'));
}

// Validasi format tanggal lahir
if ($tglLahir !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglLahir)) {
    $tglLahir = null;
}

$stmt = db()->prepare('SELECT id, status FROM users WHERE no_hp = ? LIMIT 1');
$stmt->execute([$noHp]);
$existing = $stmt->fetch();
if ($existing) {
    $msg = match ($existing['status'] ?? '') {
        'menunggu_approval' => 'Nomor HP ini sudah terdaftar dan masih menunggu persetujuan admin.',
        'nonaktif'          => 'Nomor HP ini sudah terdaftar tetapi akun nonaktif. Hubungi admin.',
        default             => 'Nomor HP ini sudah terdaftar. Silakan login jika sudah punya akun.',
    };
    flash('error', $msg);
    redirect(route_url('register'));
}

// Upload foto profil
if (!empty($_FILES['foto_profil']['tmp_name'])) {
    $fotoPath = save_uploaded_photo($_FILES['foto_profil'], 'profil');
}

// Upload foto KTP
if (!empty($_FILES['foto_ktp']['tmp_name'])) {
    $fotoKtpPath = save_uploaded_photo($_FILES['foto_ktp'], 'ktp');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = db()->prepare('INSERT INTO users (nama, no_hp, password, foto_profil, tanggal_lahir, foto_ktp, bank_name, norek, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "karyawan", "menunggu_approval")');

try {
    $stmt->execute([$nama, $noHp, $hashedPassword, $fotoPath, $tglLahir, $fotoKtpPath, $bankName, $norek]);
} catch (PDOException $e) {
    if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        flash('error', 'Nomor HP ini sudah terdaftar. Silakan login jika sudah punya akun.');
        redirect(route_url('register'));
    }
    throw $e;
}

flash('success', 'Registrasi berhasil. Tunggu approval admin.');
redirect(route_url('login'));
