<?php
/**
 * Uji koneksi DB InfinityFree — hapus file ini setelah deploy berhasil.
 */

header('Content-Type: text/plain; charset=utf-8');

$envFile = __DIR__ . '/../../.env';
if (!is_readable($envFile)) {
    http_response_code(500);
    echo "File .env tidak ditemukan.\n";
    echo "Salin .env.infinity.example menjadi .env di root project.\n";
    exit;
}

try {
    $pdo = require __DIR__ . '/../../config/database.php';
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    echo "Koneksi OK\n";
    echo "Database: {$db}\n";
    echo 'Jumlah tabel: ' . count($tables) . "\n";
    if ($tables) {
        echo implode(', ', $tables) . "\n";
    } else {
        echo "\nBelum ada tabel — import database/infinity/schema.sql via phpMyAdmin.\n";
    }
    echo "\nHapus file test-connection.php setelah selesai.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Gagal: ' . $e->getMessage() . "\n";
    echo "\nPeriksa DB_HOST, DB_NAME, DB_USER, DB_PASS di file .env\n";
}
