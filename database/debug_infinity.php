    <?php
    // Script diagnostik — hanya untuk development lokal.

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
        echo "<h2>Diagnostik Database Online InfinityFree</h2>";
        echo "<hr>";

        // 1. Cek Kolom Tabel 'users'
        echo "<h3>1. Struktur Kolom Tabel 'users':</h3>";
        $columnsStmt = $db->query("SHOW COLUMNS FROM users");
        $columns = $columnsStmt->fetchAll();
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        $hasPasswordColumn = false;
        $hasNoHpColumn = false;
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
            if ($col['Field'] === 'password') {
                $hasPasswordColumn = true;
            }
            if ($col['Field'] === 'no_hp') {
                $hasNoHpColumn = true;
            }
        }
        echo "</table>";

        if (!$hasPasswordColumn) {
            echo "<p style='color: red;'><b>PERINGATAN:</b> Kolom 'password' tidak ditemukan di database online! Silakan jalankan migrate_infinity.php terlebih dahulu.</p>";
        }
        if (!$hasNoHpColumn) {
            echo "<p style='color: red;'><b>PERINGATAN:</b> Kolom 'no_hp' tidak ditemukan di database online!</p>";
        }

        // 2. Cek Data Users
        echo "<h3>2. Data Karyawan & Admin Terdaftar:</h3>";
        $usersStmt = $db->query("SELECT * FROM users ORDER BY id ASC");
        $users = $usersStmt->fetchAll();

        if (empty($users)) {
            echo "<p style='color: orange;'>Tidak ada data user di tabel 'users'.</p>";
        } else {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Nama</th><th>No HP</th><th>Role</th><th>Status</th><th>Bank/E-Wallet</th><th>No Rekening</th><th>Password Hash</th><th>Verify '123456'</th></tr>";
            foreach ($users as $u) {
                $pwd = $u['password'] ?? 'NULL';
                $verify = password_verify('123456', $pwd) ? "<span style='color: green;'>TRUE ✓</span>" : "<span style='color: red;'>FALSE ✗</span>";
                $nama = $u['nama'] ?? $u['name'] ?? 'N/A';
                echo "<tr>";
                echo "<td>" . htmlspecialchars($u['id']) . "</td>";
                echo "<td>" . htmlspecialchars($nama) . "</td>";
                echo "<td>" . htmlspecialchars($u['no_hp'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($u['role'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($u['status'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($u['bank_name'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($u['norek'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars(substr($pwd, 0, 15)) . "... (panjang: " . strlen($pwd) . ")</td>";
                echo "<td>" . $verify . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }

        echo "<hr>";
        echo "<p style='color: red; font-weight: bold;'>PENTING: Segera hapus file ini ('database/debug_infinity.php') dari hosting setelah dibaca!</p>";

    } catch (Exception $e) {
        echo "<p style='color: red;'><b>Error:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
