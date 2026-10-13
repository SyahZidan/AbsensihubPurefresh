<?php

function distance_meters(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

function get_settings(): array
{
    $stmt = db()->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
    return $stmt->fetch() ?: [
        'default_kuota' => 26,
        'geofence_radius' => 100,
        'geofence_lat' => -6.2088,
        'geofence_lng' => 106.8456,
    ];
}

function is_inside_geofence(float $lat, float $lng): bool
{
    $settings = get_settings();
    $distance = distance_meters($lat, $lng, (float) $settings['geofence_lat'], (float) $settings['geofence_lng']);

    return $distance <= (float) $settings['geofence_radius'];
}
