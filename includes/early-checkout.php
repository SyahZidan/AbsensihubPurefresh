<?php

function ensure_early_checkout_columns(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $cols = db()->query('SHOW COLUMNS FROM absensi')->fetchAll(PDO::FETCH_COLUMN);
    $add = [
        'keterangan_keluar_awal' => 'ALTER TABLE absensi ADD COLUMN keterangan_keluar_awal TEXT NULL',
        'bukti_keluar_awal' => 'ALTER TABLE absensi ADD COLUMN bukti_keluar_awal VARCHAR(255) NULL',
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

    $ready = true;
}

function jam_batas_keluar_awal(): string
{
    $settings = get_settings();
    $jam = $settings['jam_batas_keluar_awal'] ?? '17:00:00';

    return strlen($jam) >= 5 ? substr($jam, 0, 8) : '17:00:00';
}

function is_early_checkout(?string $time = null): bool
{
    if (is_logbook_mode()) {
        return false;
    }

    $time = $time ?? date('H:i:s');
    $batas = jam_batas_keluar_awal();

    return strcmp(substr($time, 0, 8), $batas) < 0;
}

function early_checkout_message(): string
{
    $jam = substr(jam_batas_keluar_awal(), 0, 5);

    return "Absen keluar sebelum pukul {$jam} wajib mengisi keterangan dan melampirkan bukti.";
}

function require_early_checkout_proof(array $file, string $redirectRoute): string
{
    $path = save_uploaded_photo($file, 'bukti-keluar');
    if ($path === null) {
        flash('error', 'Bukti foto wajib dilampirkan untuk absen keluar sebelum jam batas.');
        redirect(route_url($redirectRoute));
    }

    return $path;
}
