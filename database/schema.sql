CREATE DATABASE IF NOT EXISTS absensi_umkm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE absensi_umkm;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    no_hp VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NULL,
    foto_profil VARCHAR(255) NULL,
    tanggal_lahir DATE NULL,
    foto_ktp VARCHAR(255) NULL,
    bank_name VARCHAR(50) NULL,
    norek VARCHAR(50) NULL,
    role ENUM('admin', 'karyawan') NOT NULL DEFAULT 'karyawan',
    status ENUM('aktif', 'menunggu_approval', 'nonaktif') NOT NULL DEFAULT 'menunggu_approval',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME NULL,
    jam_keluar TIME NULL,
    foto_masuk VARCHAR(255) NULL,
    foto_keluar VARCHAR(255) NULL,
    lat_masuk DECIMAL(10, 7) NULL,
    lng_masuk DECIMAL(10, 7) NULL,
    lat_keluar DECIMAL(10, 7) NULL,
    lng_keluar DECIMAL(10, 7) NULL,
    skema VARCHAR(10) NULL,
    upah_final INT NOT NULL DEFAULT 0,
    potongan INT NOT NULL DEFAULT 0,
    lembur INT NOT NULL DEFAULT 0,
    keterangan_keluar_awal TEXT NULL,
    bukti_keluar_awal VARCHAR(255) NULL,
    status ENUM('belum_absen', 'bekerja', 'selesai', 'terlambat', 'alpa') NOT NULL DEFAULT 'belum_absen',
    status_bayar ENUM('belum_dibayar', 'sudah_dibayar') NOT NULL DEFAULT 'belum_dibayar',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_absensi_user_tanggal (user_id, tanggal),
    CONSTRAINT fk_absensi_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    default_kuota INT NOT NULL DEFAULT 26,
    geofence_radius INT NOT NULL DEFAULT 100,
    geofence_lat DECIMAL(10, 7) NOT NULL DEFAULT -6.2088000,
    geofence_lng DECIMAL(10, 7) NOT NULL DEFAULT 106.8456000,
    upah_per_jam INT NOT NULL DEFAULT 10000,
    multiplier_lembur DECIMAL(3, 2) NOT NULL DEFAULT 1.50,
    potongan_terlambat INT NOT NULL DEFAULT 5000,
    app_name VARCHAR(100) NOT NULL DEFAULT 'Absensi Hub',
    jam_masuk_default TIME NOT NULL DEFAULT '08:00:00',
    kuota_karyawan_harian INT NOT NULL DEFAULT 0,
    jam_skema_b TIME NOT NULL DEFAULT '13:00:00',
    jam_lembur_flat TIME NOT NULL DEFAULT '21:30:00',
    menit_toleransi_terlambat INT NOT NULL DEFAULT 10,
    jam_batas_keluar_awal TIME NOT NULL DEFAULT '17:00:00',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id INT NULL,
    detail TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL UNIQUE,
    keterangan VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quota_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal DATE NOT NULL,
    alasan VARCHAR(50) NOT NULL DEFAULT 'kuota_penuh',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_quota_blocks_tanggal (tanggal),
    CONSTRAINT fk_quota_blocks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

INSERT INTO users (nama, no_hp, password, foto_profil, tanggal_lahir, foto_ktp, bank_name, norek, role, status) VALUES
('Admin Utama', '081200000000', '$2y$10$80oOd5eeKQ4CEbsB1h35ie/k.nb6p1JHSNeUV89PQsYaAAFZb4fdm', NULL, NULL, NULL, 'BCA', '1234567890', 'admin', 'aktif'),
('Budi Santoso', '081234567890', '$2y$10$80oOd5eeKQ4CEbsB1h35ie/k.nb6p1JHSNeUV89PQsYaAAFZb4fdm', NULL, '1995-05-15', NULL, 'BCA', '9876543210', 'karyawan', 'aktif'),
('Siti Aminah', '081234567891', '$2y$10$80oOd5eeKQ4CEbsB1h35ie/k.nb6p1JHSNeUV89PQsYaAAFZb4fdm', NULL, '1998-03-22', NULL, 'Mandiri', '1122334455', 'karyawan', 'aktif'),
('Bayu Aji', '081234567800', '$2y$10$80oOd5eeKQ4CEbsB1h35ie/k.nb6p1JHSNeUV89PQsYaAAFZb4fdm', NULL, NULL, NULL, 'BRI', '5566778899', 'karyawan', 'menunggu_approval')
ON DUPLICATE KEY UPDATE nama = VALUES(nama);

INSERT INTO settings (id, default_kuota, geofence_radius, geofence_lat, geofence_lng)
VALUES (1, 26, 100, -6.2088000, 106.8456000)
ON DUPLICATE KEY UPDATE default_kuota = VALUES(default_kuota);
