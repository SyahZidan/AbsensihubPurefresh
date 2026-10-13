<?php

require_once __DIR__ . '/../includes/auth.php';

verify_csrf();
$user = require_login('karyawan');

$nama      = trim($_POST['nama'] ?? '');
$noHp      = trim($_POST['no_hp'] ?? '');
$bankName  = trim($_POST['bank_name'] ?? '');
$norek     = trim($_POST['norek'] ?? '');
$password  = $_POST['password'] ?? '';
$tglLahir  = trim($_POST['tanggal_lahir'] ?? '') ?: null;

if ($nama === '' || $noHp === '' || $bankName === '' || $norek === '') {
    flash('error', 'Nama Lengkap, Nomor HP, Bank, dan Nomor Rekening wajib diisi.');
    redirect(route_url('karyawan.profile'));
}

// Validasi format tanggal lahir
if ($tglLahir !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglLahir)) {
    $tglLahir = null;
}

// Validasi Nomor HP unik (tidak boleh sama dengan karyawan lain)
$stmt = db()->prepare('SELECT id FROM users WHERE no_hp = ? AND id != ? LIMIT 1');
$stmt->execute([$noHp, $user['id']]);
if ($stmt->fetch()) {
    flash('error', 'Nomor HP sudah digunakan oleh pengguna lain.');
    redirect(route_url('karyawan.profile'));
}

// Proses upload foto profil baru jika ada
$fotoPath = $user['foto_profil'];
if (!empty($_FILES['foto_profil']['tmp_name'])) {
    $newProfil = save_uploaded_photo($_FILES['foto_profil'], 'profil');
    if ($newProfil) {
        // Hapus foto profil lama jika ada untuk menghemat ruang
        if ($user['foto_profil']) {
            $oldFile = dirname(__DIR__) . $user['foto_profil'];
            if (file_exists($oldFile) && is_file($oldFile)) {
                @unlink($oldFile);
            }
        }
        $fotoPath = $newProfil;
    } else {
        flash('error', 'Format foto profil harus JPG, JPEG, PNG, atau WEBP, atau file rusak.');
        redirect(route_url('karyawan.profile'));
    }
}

// Proses upload foto KTP baru jika ada
$fotoKtpPath = $user['foto_ktp'] ?? null;
if (!empty($_FILES['foto_ktp']['tmp_name'])) {
    $newKtp = save_uploaded_photo($_FILES['foto_ktp'], 'ktp');
    if ($newKtp) {
        // Hapus foto KTP lama jika ada
        if (!empty($user['foto_ktp'])) {
            $oldKtp = dirname(__DIR__) . $user['foto_ktp'];
            if (file_exists($oldKtp) && is_file($oldKtp)) {
                @unlink($oldKtp);
            }
        }
        $fotoKtpPath = $newKtp;
    } else {
        flash('error', 'Format foto KTP harus JPG, JPEG, PNG, atau WEBP, atau file rusak.');
        redirect(route_url('karyawan.profile'));
    }
}

// Proses update ke database
try {
    if ($password !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE users SET nama = ?, no_hp = ?, password = ?, foto_profil = ?, tanggal_lahir = ?, foto_ktp = ?, bank_name = ?, norek = ? WHERE id = ?');
        $stmt->execute([$nama, $noHp, $hashedPassword, $fotoPath, $tglLahir, $fotoKtpPath, $bankName, $norek, $user['id']]);
    } else {
        $stmt = db()->prepare('UPDATE users SET nama = ?, no_hp = ?, foto_profil = ?, tanggal_lahir = ?, foto_ktp = ?, bank_name = ?, norek = ? WHERE id = ?');
        $stmt->execute([$nama, $noHp, $fotoPath, $tglLahir, $fotoKtpPath, $bankName, $norek, $user['id']]);
    }

    flash('success', 'Profil berhasil diperbarui.');
} catch (PDOException $e) {
    flash('error', 'Gagal memperbarui profil: ' . $e->getMessage());
}

redirect(route_url('karyawan.profile'));
