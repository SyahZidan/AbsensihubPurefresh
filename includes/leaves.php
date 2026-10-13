<?php

require_once __DIR__ . '/features.php';

function is_on_approved_leave(int $userId, ?string $tanggal = null): bool
{
    if (is_logbook_mode()) {
        return false;
    }

    ensure_feature_tables();
    $tanggal = $tanggal ?? current_date();

    $stmt = db()->prepare("
        SELECT id FROM leave_requests
        WHERE user_id = ? AND status = 'disetujui'
          AND ? BETWEEN tanggal_mulai AND tanggal_selesai
        LIMIT 1
    ");
    $stmt->execute([$userId, $tanggal]);
    return (bool) $stmt->fetch();
}

function leave_block_message(): string
{
    return 'Anda sedang dalam periode izin/cuti yang disetujui. Tidak dapat absen masuk hari ini.';
}
