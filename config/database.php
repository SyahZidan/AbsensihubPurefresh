<?php

/**
 * Muat .env ke memori (tanpa putenv — banyak hosting gratis memblokir putenv).
 */
function load_app_env(string $path): array
{
    $vars = [];
    if (!is_readable($path)) {
        return $vars;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        return $vars;
    }

    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    foreach (preg_split('/\R/', $content) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");
        if ($key !== '') {
            $vars[$key] = $value;
        }
    }

    return $vars;
}

function env(string $key, ?string $default = null): ?string
{
    static $appEnv = null;
    if ($appEnv === null) {
        $appEnv = load_app_env(__DIR__ . '/../.env');
    }

    if (array_key_exists($key, $appEnv)) {
        return $appEnv[$key];
    }

    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    return $default;
}

function is_local_db_host(string $host): bool
{
    return in_array(strtolower($host), ['127.0.0.1', 'localhost', '::1'], true);
}

function is_local_request(): bool
{
    $httpHost = strtolower($_SERVER['HTTP_HOST'] ?? '');

    if (str_contains($httpHost, 'infinityfree')
        || str_contains($httpHost, 'free.nf')
        || str_contains($httpHost, 'epizy.com')
        || str_contains($httpHost, 'byetcluster')) {
        return false;
    }

    if (PHP_SAPI === 'cli') {
        return true;
    }

    if (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
        return true;
    }

    return str_contains($httpHost, 'localhost')
        || str_starts_with($httpHost, '127.0.0.1');
}

$envFile = __DIR__ . '/../.env';
$parsedEnv = load_app_env($envFile);
$isLocal = is_local_request();

if ($isLocal) {
    // Localhost/XAMPP: pakai MySQL lokal meskipun .env berisi kredensial InfinityFree
    $envHost = $parsedEnv['DB_HOST'] ?? '';

    if ($envHost !== '' && is_local_db_host($envHost)) {
        $host = $envHost;
        $port = $parsedEnv['DB_PORT'] ?? '3306';
        $database = $parsedEnv['DB_NAME'] ?? 'absensi_umkm';
        $username = $parsedEnv['DB_USER'] ?? 'root';
        $password = $parsedEnv['DB_PASS'] ?? '';
    } else {
        $host = $parsedEnv['DB_HOST_LOCAL'] ?? '127.0.0.1';
        $port = $parsedEnv['DB_PORT_LOCAL'] ?? $parsedEnv['DB_PORT'] ?? '3306';
        $database = $parsedEnv['DB_NAME_LOCAL'] ?? 'absensi_umkm';
        $username = $parsedEnv['DB_USER_LOCAL'] ?? 'root';
        $password = $parsedEnv['DB_PASS_LOCAL'] ?? '';
    }
} else {
    $host = $parsedEnv['DB_HOST'] ?? 'sql303.infinityfree.com';
    $port = $parsedEnv['DB_PORT'] ?? '3306';
    $database = $parsedEnv['DB_NAME'] ?? 'if0_42012557_purefresh';
    $username = $parsedEnv['DB_USER'] ?? 'if0_42012557';
    $password = $parsedEnv['DB_PASS'] ?? null;

    if ($password === null || $password === '') {
        http_response_code(500);
        $envPath = realpath($envFile) ?: $envFile;

        if (!is_readable($envFile)) {
            echo 'Database belum siap: file <strong>.env</strong> tidak ditemukan.<br><br>';
            echo 'Upload file <code>.env</code> ke <code>htdocs/</code> (sejajar folder <code>config/</code> dan <code>public/</code>).';
        } elseif (!array_key_exists('DB_PASS', $parsedEnv)) {
            echo 'Database belum siap: baris <code>DB_PASS=...</code> tidak ada di file .env.<br><br>';
            echo 'Tambahkan di <code>' . htmlspecialchars($envPath) . '</code>:<br>';
            echo '<pre>DB_PASS=password_mysql_anda</pre>';
        } else {
            echo 'Database belum siap: <code>DB_PASS</code> kosong di file .env.<br><br>';
            echo 'Isi password MySQL dari vPanel di <code>' . htmlspecialchars($envPath) . '</code>';
        }
        exit;
    }
}

$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

try {
    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    if ($isLocal) {
        echo 'Database lokal belum siap: ' . htmlspecialchars($e->getMessage());
        echo '<br><br>Pastikan MySQL XAMPP jalan dan database <code>absensi_umkm</code> sudah di-import dari <code>database/schema.sql</code>.';
    } else {
        echo 'Database belum siap: ' . htmlspecialchars($e->getMessage());
    }
    exit;
}
