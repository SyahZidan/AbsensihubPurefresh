<?php

function time_to_minutes(string $time): int
{
    [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));
    return ($hour * 60) + $minute;
}

function wage_settings_times(array $settings): array
{
    return [
        'scheme_b_after' => time_to_minutes($settings['jam_skema_b'] ?? '13:00'),
        'flat_after' => time_to_minutes($settings['jam_lembur_flat'] ?? '21:30'),
        'late_grace' => (int) ($settings['menit_toleransi_terlambat'] ?? 10),
    ];
}

/** Terlambat jika masuk lebih dari target jam masuk shift (08.00 / 09.00 / 10.00 / 12.30) yang terdekat + toleransi (menit). */
function is_late_checkin(string $jamMasuk, array $settings, ?int $graceMinutes = null): bool
{
    $grace = $graceMinutes ?? (int) ($settings['menit_toleransi_terlambat'] ?? 15);
    $timeIn = time_to_minutes($jamMasuk);
    
    $target8 = time_to_minutes('08:00');
    $target9 = time_to_minutes('09:00');
    $target10 = time_to_minutes('10:00');
    $target1230 = time_to_minutes('12:30');

    $dist8 = abs($timeIn - $target8);
    $dist9 = abs($timeIn - $target9);
    $dist10 = abs($timeIn - $target10);
    $dist1230 = abs($timeIn - $target1230);

    $minDist = min($dist8, $dist9, $dist10, $dist1230);
    $targetStart = $target9;
    if ($minDist === $dist8) {
        $targetStart = $target8;
    } elseif ($minDist === $dist10) {
        $targetStart = $target10;
    } elseif ($minDist === $dist1230) {
        $targetStart = $target1230;
    }

    return $timeIn > $targetStart + $grace;
}

function calculate_wage(string $jamMasuk, string $jamKeluar): array
{
    $in = time_to_minutes($jamMasuk);
    $out = time_to_minutes($jamKeluar);

    if (!function_exists('get_settings')) {
        require_once __DIR__ . '/geofence.php';
    }
    $settings = get_settings();
    if (function_exists('ensure_settings_extra_columns')) {
        ensure_settings_extra_columns();
        $settings = get_settings();
    }

    $potonganSet = (int) ($settings['potongan_terlambat'] ?? 5000);

    // Definisi 20 Skema Kerja (Termasuk Skema Jam 8)
    $allSchemes = [
        // Skema Jam Wajib
        ['key' => 'upah_08_17', 'name' => '08.00 - 17.00', 'start' => 480, 'end' => 1020, 'type' => 'wajib', 'default' => 90000],
        ['key' => 'upah_09_17', 'name' => '09.00 - 17.00', 'start' => 540, 'end' => 1020, 'type' => 'wajib', 'default' => 80000],
        ['key' => 'upah_10_17', 'name' => '10.00 - 17.00', 'start' => 600, 'end' => 1020, 'type' => 'wajib', 'default' => 70000],
        ['key' => 'upah_13_17', 'name' => '12.30 - 17.30', 'start' => 750, 'end' => 1050, 'type' => 'wajib', 'default' => 60000],

        // Skema Lembur Jam 8
        ['key' => 'upah_08_19', 'name' => '08.00 - 19.00', 'start' => 480, 'end' => 1140, 'type' => 'lembur', 'default' => 100000],
        ['key' => 'upah_08_20', 'name' => '08.00 - 20.00', 'start' => 480, 'end' => 1200, 'type' => 'lembur', 'default' => 110000],
        ['key' => 'upah_08_21', 'name' => '08.00 - 21.00', 'start' => 480, 'end' => 1260, 'type' => 'lembur', 'default' => 120000],
        ['key' => 'upah_08_22', 'name' => '08.00 - 22.00', 'start' => 480, 'end' => 1320, 'type' => 'lembur', 'default' => 130000],

        // Skema Lembur Jam 9
        ['key' => 'upah_09_19', 'name' => '09.00 - 19.00', 'start' => 540, 'end' => 1140, 'type' => 'lembur', 'default' => 90000],
        ['key' => 'upah_09_20', 'name' => '09.00 - 20.00', 'start' => 540, 'end' => 1200, 'type' => 'lembur', 'default' => 100000],
        ['key' => 'upah_09_21', 'name' => '09.00 - 21.00', 'start' => 540, 'end' => 1260, 'type' => 'lembur', 'default' => 110000],
        ['key' => 'upah_09_22', 'name' => '09.00 - 22.00', 'start' => 540, 'end' => 1320, 'type' => 'lembur', 'default' => 120000],

        // Skema Lembur Jam 10
        ['key' => 'upah_10_19', 'name' => '10.00 - 19.00', 'start' => 600, 'end' => 1140, 'type' => 'lembur', 'default' => 80000],
        ['key' => 'upah_10_20', 'name' => '10.00 - 20.00', 'start' => 600, 'end' => 1200, 'type' => 'lembur', 'default' => 90000],
        ['key' => 'upah_10_21', 'name' => '10.00 - 21.00', 'start' => 600, 'end' => 1260, 'type' => 'lembur', 'default' => 100000],
        ['key' => 'upah_10_22', 'name' => '10.00 - 22.00', 'start' => 600, 'end' => 1320, 'type' => 'lembur', 'default' => 110000],

        // Skema Lembur Jam 12.30
        ['key' => 'upah_13_19', 'name' => '12.30 - 19.00', 'start' => 750, 'end' => 1140, 'type' => 'lembur', 'default' => 70000],
        ['key' => 'upah_13_20', 'name' => '12.30 - 20.00', 'start' => 750, 'end' => 1200, 'type' => 'lembur', 'default' => 80000],
        ['key' => 'upah_13_21', 'name' => '12.30 - 21.00', 'start' => 750, 'end' => 1260, 'type' => 'lembur', 'default' => 90000],
        ['key' => 'upah_13_22', 'name' => '12.30 - 22.00', 'start' => 750, 'end' => 1320, 'type' => 'lembur', 'default' => 100000],
    ];

    // Find closest start group (480, 540, 600, or 750)
    $startGroups = [480, 540, 600, 750];
    $bestStart = 480;
    $minStartDiff = abs($in - 480);
    foreach ($startGroups as $st) {
        $diff = abs($in - $st);
        if ($diff < $minStartDiff) {
            $minStartDiff = $diff;
            $bestStart = $st;
        }
    }

    // Filter schemes that match the bestStart
    $matchingSchemes = array_filter($allSchemes, fn($s) => $s['start'] === $bestStart);

    // Pick highest scheme whose end time is <= $out
    $bestScheme = null;
    foreach ($matchingSchemes as $sc) {
        if ($sc['end'] <= $out) {
            if ($bestScheme === null || $sc['end'] > $bestScheme['end']) {
                $bestScheme = $sc;
            }
        }
    }

    // If none (e.g. checkout before mandatory end), default to the wajib scheme
    if ($bestScheme === null) {
        $bestScheme = array_values(array_filter($matchingSchemes, fn($s) => $s['type'] === 'wajib'))[0] ?? reset($matchingSchemes);
    }

    $base = (int) ($settings[$bestScheme['key']] ?? $bestScheme['default']);
    
    // Hitung sisa menit lembur di luar jam skema
    $extraMinutes = 0;
    if ($bestScheme['end'] === 1020 || $bestScheme['end'] === 1050) { // 17:00 atau 17:30
        // Jeda istirahat selesai pada jam 18:00, lembur dihitung setelah itu
        $extraMinutes = max(0, $out - 1080);
    } else {
        $extraMinutes = max(0, $out - $bestScheme['end']);
    }

    $extraLembur = 0;
    $jamLemburTambahan = 0;
    if ($extraMinutes >= 30) {
        $extraLembur = 5000;
        $jamLemburTambahan = 0.5;
    }

    // Upah dasar final sebelum denda
    $finalBase = $base + $extraLembur;

    $late = is_late_checkin($jamMasuk, $settings);
    $deduction = $late ? $potonganSet : 0;
    
    // Hitung total komponen lembur (lembur skema + lembur tambahan)
    $isLembur = ($bestScheme['type'] === 'lembur');
    
    $wajibKey = "upah_13_17";
    if ($bestScheme['start'] === 480) {
        $wajibKey = "upah_08_17";
    } elseif ($bestScheme['start'] === 540) {
        $wajibKey = "upah_09_17";
    } elseif ($bestScheme['start'] === 600) {
        $wajibKey = "upah_10_17";
    }
    
    $wajibBase = (int)($settings[$wajibKey] ?? 60000);
    $lemburSkema = $isLembur ? max(0, $base - $wajibBase) : 0;
    $totalLembur = $lemburSkema + $extraLembur;

    // Jam lembur total (dalam jam)
    $jamLemburSkema = $isLembur ? max(0, ($bestScheme['end'] - 1080) / 60) : 0;
    $totalJamLembur = $jamLemburSkema + $jamLemburTambahan;

    return [
        'skema' => $bestScheme['name'] . ($jamLemburTambahan > 0 ? ' +' . round($jamLemburTambahan, 1) . 'j' : ''),
        'base' => $finalBase,
        'lembur' => max(0, $totalLembur),
        'potongan' => $deduction,
        'jam_lembur' => round($totalJamLembur, 2),
        'total' => max(0, $finalBase - $deduction),
        'terlambat' => $late,
    ];
}
