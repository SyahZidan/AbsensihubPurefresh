<?php

require_once __DIR__ . '/geofence.php';

/** Batas jumlah karyawan unik yang boleh absen masuk per hari (0 = tanpa batas). */
function daily_employee_quota(): int
{
    $settings = get_settings();
    return max(0, (int) ($settings['kuota_karyawan_harian'] ?? 0));
}

function daily_checked_in_count(?string $tanggal = null): int
{
    $tanggal = $tanggal ?? current_date();
    $stmt = db()->prepare(
        'SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = ? AND jam_masuk IS NOT NULL'
    );
    $stmt->execute([$tanggal]);
    return (int) $stmt->fetchColumn();
}

function user_has_checked_in_today(int $userId, ?string $tanggal = null): bool
{
    $tanggal = $tanggal ?? current_date();
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM absensi WHERE user_id = ? AND tanggal = ? AND jam_masuk IS NOT NULL'
    );
    $stmt->execute([$userId, $tanggal]);
    return (int) $stmt->fetchColumn() > 0;
}

/** Karyawan ini tidak boleh absen masuk karena kuota harian sudah penuh. */
function is_blocked_by_daily_quota(int $userId, ?string $tanggal = null): bool
{
    if (is_logbook_mode()) {
        return false;
    }

    $limit = daily_employee_quota();
    if ($limit <= 0) {
        return false;
    }

    if (user_has_checked_in_today($userId, $tanggal)) {
        return false;
    }

    return daily_checked_in_count($tanggal) >= $limit;
}

function daily_quota_status(?string $tanggal = null): array
{
    $limit = daily_employee_quota();
    $used = daily_checked_in_count($tanggal);
    $remaining = $limit > 0 ? max(0, $limit - $used) : null;

    return [
        'limit' => $limit,
        'used' => $used,
        'remaining' => $remaining,
        'is_full' => $limit > 0 && $used >= $limit,
        'unlimited' => $limit <= 0,
    ];
}

function daily_quota_full_message(): string
{
    $limit = daily_employee_quota();
    return "Kuota masuk hari ini sudah penuh ({$limit} karyawan). Anda tidak dapat absen, tidak dihitung hadir, dan tidak mendapat upah.";
}

function log_quota_block_for_user(int $userId): void
{
    if (!function_exists('log_quota_block')) {
        require_once __DIR__ . '/features.php';
    }
    log_quota_block($userId, 'kuota_penuh');
}
