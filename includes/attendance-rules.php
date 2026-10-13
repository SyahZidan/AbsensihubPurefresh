<?php

function latest_checkin_time(): string
{
    return '13:30:00';
}

function is_checkin_closed(?string $time = null): bool
{
    $time = $time ?? date('H:i:s');

    return strcmp(substr($time, 0, 8), latest_checkin_time()) > 0;
}

function latest_checkin_message(): string
{
    return 'Absen masuk hanya bisa dilakukan sampai pukul ' . substr(latest_checkin_time(), 0, 5) . '.';
}
