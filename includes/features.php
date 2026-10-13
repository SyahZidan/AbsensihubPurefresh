<?php

function ensure_feature_tables(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS leave_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            jenis ENUM('izin', 'sakit', 'cuti') NOT NULL DEFAULT 'izin',
            tanggal_mulai DATE NOT NULL,
            tanggal_selesai DATE NOT NULL,
            alasan TEXT NOT NULL,
            status ENUM('menunggu', 'disetujui', 'ditolak') NOT NULL DEFAULT 'menunggu',
            catatan_admin VARCHAR(255) NULL,
            reviewed_by INT NULL,
            reviewed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_leave_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_id INT NULL,
            detail TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_logs_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quota_blocks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            tanggal DATE NOT NULL,
            alasan VARCHAR(50) NOT NULL DEFAULT 'kuota_penuh',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_quota_blocks_tanggal (tanggal),
            CONSTRAINT fk_quota_blocks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(120) NOT NULL,
            message VARCHAR(255) NOT NULL,
            type ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
            link VARCHAR(255) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    ensure_settings_extra_columns();
    ensure_absensi_extra_columns();

    $ready = true;
}

function ensure_settings_extra_columns(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $cols = db()->query('SHOW COLUMNS FROM settings')->fetchAll(PDO::FETCH_COLUMN);
    $add = [
        'jam_skema_b' => "ALTER TABLE settings ADD COLUMN jam_skema_b TIME NOT NULL DEFAULT '13:00:00'",
        'jam_lembur_flat' => "ALTER TABLE settings ADD COLUMN jam_lembur_flat TIME NOT NULL DEFAULT '21:30:00'",
        'menit_toleransi_terlambat' => "ALTER TABLE settings ADD COLUMN menit_toleransi_terlambat INT NOT NULL DEFAULT 10",
        'jam_batas_keluar_awal' => "ALTER TABLE settings ADD COLUMN jam_batas_keluar_awal TIME NOT NULL DEFAULT '17:00:00'",
        'upah_skema_1' => "ALTER TABLE settings ADD COLUMN upah_skema_1 INT NOT NULL DEFAULT 70000",
        'upah_skema_2' => "ALTER TABLE settings ADD COLUMN upah_skema_2 INT NOT NULL DEFAULT 60000",
        'upah_skema_3' => "ALTER TABLE settings ADD COLUMN upah_skema_3 INT NOT NULL DEFAULT 130000",
        'upah_skema_4' => "ALTER TABLE settings ADD COLUMN upah_skema_4 INT NOT NULL DEFAULT 120000",
        // 15 Skema Baru
        'upah_08_17' => "ALTER TABLE settings ADD COLUMN upah_08_17 INT NOT NULL DEFAULT 90000",
        'upah_09_17' => "ALTER TABLE settings ADD COLUMN upah_09_17 INT NOT NULL DEFAULT 80000",
        'upah_10_17' => "ALTER TABLE settings ADD COLUMN upah_10_17 INT NOT NULL DEFAULT 70000",
        'upah_13_17' => "ALTER TABLE settings ADD COLUMN upah_13_17 INT NOT NULL DEFAULT 60000",
        // Skema Lembur Jam 8
        'upah_08_19' => "ALTER TABLE settings ADD COLUMN upah_08_19 INT NOT NULL DEFAULT 100000",
        'upah_08_20' => "ALTER TABLE settings ADD COLUMN upah_08_20 INT NOT NULL DEFAULT 110000",
        'upah_08_21' => "ALTER TABLE settings ADD COLUMN upah_08_21 INT NOT NULL DEFAULT 120000",
        'upah_08_22' => "ALTER TABLE settings ADD COLUMN upah_08_22 INT NOT NULL DEFAULT 130000",
        // Skema Lembur Jam 9
        'upah_09_19' => "ALTER TABLE settings ADD COLUMN upah_09_19 INT NOT NULL DEFAULT 90000",
        'upah_09_20' => "ALTER TABLE settings ADD COLUMN upah_09_20 INT NOT NULL DEFAULT 100000",
        'upah_09_21' => "ALTER TABLE settings ADD COLUMN upah_09_21 INT NOT NULL DEFAULT 110000",
        'upah_09_22' => "ALTER TABLE settings ADD COLUMN upah_09_22 INT NOT NULL DEFAULT 120000",
        // Skema Lembur Jam 10
        'upah_10_19' => "ALTER TABLE settings ADD COLUMN upah_10_19 INT NOT NULL DEFAULT 80000",
        'upah_10_20' => "ALTER TABLE settings ADD COLUMN upah_10_20 INT NOT NULL DEFAULT 90000",
        'upah_10_21' => "ALTER TABLE settings ADD COLUMN upah_10_21 INT NOT NULL DEFAULT 100000",
        'upah_10_22' => "ALTER TABLE settings ADD COLUMN upah_10_22 INT NOT NULL DEFAULT 110000",
        // Skema Lembur Jam 1 (13.00)
        'upah_13_19' => "ALTER TABLE settings ADD COLUMN upah_13_19 INT NOT NULL DEFAULT 70000",
        'upah_13_20' => "ALTER TABLE settings ADD COLUMN upah_13_20 INT NOT NULL DEFAULT 80000",
        'upah_13_21' => "ALTER TABLE settings ADD COLUMN upah_13_21 INT NOT NULL DEFAULT 90000",
        'upah_13_22' => "ALTER TABLE settings ADD COLUMN upah_13_22 INT NOT NULL DEFAULT 100000",
    ];

    foreach ($add as $name => $sql) {
        if (!in_array($name, $cols, true)) {
            try {
                db()->exec($sql);
            } catch (Throwable) {
                // ignore if already exists
            }
        }
    }

    $done = true;
}

function ensure_absensi_extra_columns(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $cols = db()->query('SHOW COLUMNS FROM absensi')->fetchAll(PDO::FETCH_COLUMN);
    $add = [
        'alasan_edit_upah' => "ALTER TABLE absensi ADD COLUMN alasan_edit_upah TEXT NULL DEFAULT NULL",
    ];

    foreach ($add as $name => $sql) {
        if (!in_array($name, $cols, true)) {
            try {
                db()->exec($sql);
            } catch (Throwable) {
                // ignore if already exists
            }
        }
    }

    $done = true;
}

function log_quota_block(int $userId, string $alasan = 'kuota_penuh'): void
{
    ensure_feature_tables();
    $tanggal = current_date();
    $check = db()->prepare('SELECT id FROM quota_blocks WHERE user_id = ? AND tanggal = ? LIMIT 1');
    $check->execute([$userId, $tanggal]);
    if ($check->fetch()) {
        return;
    }
    $stmt = db()->prepare('INSERT INTO quota_blocks (user_id, tanggal, alasan) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $tanggal, $alasan]);
}

function notify_user(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
{
    ensure_feature_tables();
    $stmt = db()->prepare('INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $title, $message, $type, $link]);
}

function unread_notifications_count(int $userId): int
{
    ensure_feature_tables();
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function fetch_notifications(int $userId, int $limit = 8): array
{
    ensure_feature_tables();
    $stmt = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function mark_notifications_read(int $userId): void
{
    ensure_feature_tables();
    $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
}

function log_admin_action(int $adminId, string $action, string $targetType, ?int $targetId = null, ?string $detail = null): void
{
    ensure_feature_tables();
    $stmt = db()->prepare('INSERT INTO admin_logs (admin_id, action, target_type, target_id, detail) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$adminId, $action, $targetType, $targetId, $detail]);
}
