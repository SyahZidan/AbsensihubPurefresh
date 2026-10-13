-- Cek struktur tabel holidays dulu (jalankan baris ini saja):
-- SHOW COLUMNS FROM holidays;

-- Jika kolom sudah "keterangan" → TIDAK perlu ALTER apa pun.

-- Jika masih "nama", jalankan:
-- ALTER TABLE holidays CHANGE nama keterangan VARCHAR(120) NOT NULL;

-- Jika tabel belum ada sama sekali:
CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    keterangan VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_holidays_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
