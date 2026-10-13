<?php
// Script migrasi kolom tanggal_lahir & foto_ktp — hapus setelah dijalankan.

$isLocal = PHP_SAPI === 'cli'
    || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');

require_once __DIR__ . '/../includes/auth.php';
require_login('admin');

try {
    $db = db();
    echo "<h3>Migrasi: Tambah Kolom tanggal_lahir & foto_ktp</h3>";

    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('tanggal_lahir', $columns)) {
        $db->exec("ALTER TABLE users ADD COLUMN tanggal_lahir DATE NULL AFTER foto_profil");
        echo "✅ Kolom <strong>tanggal_lahir</strong> berhasil ditambahkan!<br>";
    } else {
        echo "ℹ️ Kolom <strong>tanggal_lahir</strong> sudah ada.<br>";
    }

    if (!in_array('foto_ktp', $columns)) {
        $db->exec("ALTER TABLE users ADD COLUMN foto_ktp VARCHAR(255) NULL AFTER tanggal_lahir");
        echo "✅ Kolom <strong>foto_ktp</strong> berhasil ditambahkan!<br>";
    } else {
        echo "ℹ️ Kolom <strong>foto_ktp</strong> sudah ada.<br>";
    }

    echo "<br><b>Migrasi selesai!</b><br>";
    echo "<span style='color:red;font-weight:bold;'>PENTING: Hapus file ini (database/migrate_ktp.php) setelah migrasi berhasil.</span>";

} catch (Exception $e) {
    echo "<span style='color:red;'>Terjadi kesalahan: " . htmlspecialchars($e->getMessage()) . "</span>";
}
