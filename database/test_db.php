<?php
// Temporary script to debug database columns and connection.

require_once __DIR__ . '/../includes/auth.php';

try {
    $db = db();
    
    // Get currently connected database name
    $dbNameQuery = $db->query("SELECT DATABASE()")->fetchColumn();
    
    echo "<h3>Debug Database Connection</h3>";
    echo "Connected Database Name (dari PDO): <strong>" . htmlspecialchars($dbNameQuery) . "</strong><br>";
    
    // Read from env file
    $envFile = __DIR__ . '/../.env';
    if (is_readable($envFile)) {
        $content = file_get_contents($envFile);
        echo "<br><b>Isi .env (Kunci DB_NAME):</b><br>";
        if (preg_match('/DB_NAME=(.*)/', $content, $matches)) {
            echo "DB_NAME di .env = <code>" . htmlspecialchars(trim($matches[1])) . "</code><br>";
        } else {
            echo "DB_NAME tidak ditemukan di .env<br>";
        }
    } else {
        echo "<br>File .env tidak terbaca atau tidak ditemukan.<br>";
    }
    
    // Check columns of users table
    echo "<br><b>Struktur Kolom Tabel 'users':</b><br>";
    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll();
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        $style = in_array($col['Field'], ['tanggal_lahir', 'foto_ktp']) ? "style='background-color: #d1fae5; font-weight: bold;'" : "";
        echo "<tr {$style}>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<span style='color:red;'>Terjadi kesalahan: " . htmlspecialchars($e->getMessage()) . "</span>";
}
