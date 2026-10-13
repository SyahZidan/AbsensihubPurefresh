<?php

function ensure_holiday_tables(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tanggal DATE NOT NULL UNIQUE,
            keterangan VARCHAR(120) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $ready = true;
}

function is_holiday(?string $tanggal = null): bool
{
    if (is_logbook_mode()) {
        return false;
    }

    ensure_holiday_tables();
    $tanggal = $tanggal ?? current_date();
    $stmt = db()->prepare('SELECT id FROM holidays WHERE tanggal = ? LIMIT 1');
    $stmt->execute([$tanggal]);
    return (bool) $stmt->fetch();
}

function holiday_message(): string
{
    return 'Hari ini adalah hari libur. Absensi dan perhitungan upah tidak tersedia.';
}

function fetch_holidays(int $limit = 50): array
{
    ensure_holiday_tables();
    $stmt = db()->prepare('SELECT * FROM holidays ORDER BY tanggal DESC LIMIT ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}
