<?php
// Script migrasi — hanya untuk development lokal. Hapus dari production.

$isLocal = PHP_SAPI === 'cli'
    || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');

if (!$isLocal) {
    http_response_code(403);
    exit('Akses ditolak.');
}

require_once __DIR__ . '/../includes/auth.php';
require_login('admin');

try {
    $db = db();
    echo "<h3>Memulai Migrasi Database Online...</h3>";
    
    // 1. Periksa apakah kolom 'password', 'bank_name', dan 'norek' ada di tabel 'users'
    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('password', $columns)) {
        echo "Menambahkan kolom 'password' ke tabel 'users'...<br>";
        $db->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) NULL");
        echo "Kolom 'password' berhasil ditambahkan!<br>";
    } else {
        echo "Kolom 'password' sudah ada di tabel 'users'.<br>";
    }

    if (!in_array('bank_name', $columns)) {
        echo "Menambahkan kolom 'bank_name' ke tabel 'users'...<br>";
        $db->exec("ALTER TABLE users ADD COLUMN bank_name VARCHAR(50) NULL");
        echo "Kolom 'bank_name' berhasil ditambahkan!<br>";
    } else {
        echo "Kolom 'bank_name' sudah ada di tabel 'users'.<br>";
    }

    if (!in_array('norek', $columns)) {
        echo "Menambahkan kolom 'norek' ke tabel 'users'...<br>";
        $db->exec("ALTER TABLE users ADD COLUMN norek VARCHAR(50) NULL");
        echo "Kolom 'norek' berhasil ditambahkan!<br>";
    } else {
        echo "Kolom 'norek' sudah ada di tabel 'users'.<br>";
    }

    if (!in_array('tanggal_lahir', $columns)) {
        echo "Menambahkan kolom 'tanggal_lahir' ke tabel 'users'...<br>";
        $db->exec("ALTER TABLE users ADD COLUMN tanggal_lahir DATE NULL AFTER foto_profil");
        echo "Kolom 'tanggal_lahir' berhasil ditambahkan!<br>";
    } else {
        echo "Kolom 'tanggal_lahir' sudah ada di tabel 'users'.<br>";
    }

    if (!in_array('foto_ktp', $columns)) {
        echo "Menambahkan kolom 'foto_ktp' ke tabel 'users'...<br>";
        $db->exec("ALTER TABLE users ADD COLUMN foto_ktp VARCHAR(255) NULL AFTER tanggal_lahir");
        echo "Kolom 'foto_ktp' berhasil ditambahkan!<br>";
    } else {
        echo "Kolom 'foto_ktp' sudah ada di tabel 'users'.<br>";
    }

    // 1.5. Periksa apakah kolom 'kuota_karyawan_harian' ada di tabel 'settings'
    $columnsSettings = $db->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('kuota_karyawan_harian', $columnsSettings)) {
        echo "Menambahkan kolom 'kuota_karyawan_harian' ke tabel 'settings'...<br>";
        $db->exec("ALTER TABLE settings ADD COLUMN kuota_karyawan_harian INT NOT NULL DEFAULT 0");
        echo "Kolom 'kuota_karyawan_harian' berhasil ditambahkan!<br>";
    } else {
        echo "Kolom 'kuota_karyawan_harian' sudah ada di tabel 'settings'.<br>";
    }
    
    // 2. Ambil semua user untuk diperiksa dan di-update password-nya
    $stmt = $db->query("SELECT id, no_hp, password FROM users");
    $users = $stmt->fetchAll();
    
    $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updatedCount = 0;
    
    foreach ($users as $user) {
        $pwd = trim($user['password'] ?? '');
        
        // Kita cek apakah password saat ini cocok dengan '123456'
        // Jika tidak cocok, maka kita update dengan hash baru dari '123456'
        $isCorrectDefault = password_verify('123456', $pwd);
        
        if (!$isCorrectDefault) {
            $newHash = password_hash('123456', PASSWORD_DEFAULT);
            $updateStmt->execute([$newHash, $user['id']]);
            $updatedCount++;
            echo "User ID {$user['id']} (No HP: {$user['no_hp']}) -> Password disetel ulang menjadi '123456'.<br>";
        } else {
            echo "User ID {$user['id']} (No HP: {$user['no_hp']}) -> Sudah menggunakan password '123456' (Dilewati).<br>";
        }
    }
    
    echo "<br><b>Migrasi Selesai!</b> Berhasil meng-update {$updatedCount} user.<br>";
    echo "<span style='color: red; font-weight: bold;'>PENTING: Segera hapus file 'database/migrate_infinity.php' dari hosting Anda setelah selesai login untuk keamanan!</span>";

} catch (Exception $e) {
    echo "<span style='color: red;'>Terjadi kesalahan: " . htmlspecialchars($e->getMessage()) . "</span>";
}
