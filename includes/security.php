<?php

function login_rate_limit_key(string $noHp): string
{
    return 'login_' . md5($noHp . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
}

/** @return array{allowed: bool, retry_after: int} */
function check_login_rate_limit(string $noHp, int $maxAttempts = 5, int $windowSeconds = 900): array
{
    $key = login_rate_limit_key($noHp);
    $now = time();

    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }

    $bucket = $_SESSION['rate_limits'][$key] ?? ['count' => 0, 'reset' => $now + $windowSeconds];

    if ($now > $bucket['reset']) {
        $bucket = ['count' => 0, 'reset' => $now + $windowSeconds];
    }

    if ($bucket['count'] >= $maxAttempts) {
        return ['allowed' => false, 'retry_after' => max(1, $bucket['reset'] - $now)];
    }

    return ['allowed' => true, 'retry_after' => 0];
}

function record_failed_login(string $noHp): void
{
    $key = login_rate_limit_key($noHp);
    $now = time();
    $bucket = $_SESSION['rate_limits'][$key] ?? ['count' => 0, 'reset' => $now + 900];
    if ($now > $bucket['reset']) {
        $bucket = ['count' => 0, 'reset' => $now + 900];
    }
    $bucket['count']++;
    $_SESSION['rate_limits'][$key] = $bucket;
}

function clear_login_rate_limit(string $noHp): void
{
    unset($_SESSION['rate_limits'][login_rate_limit_key($noHp)]);
}
