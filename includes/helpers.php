<?php

if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input)) {
        $_POST = array_merge($_POST, $input);
    }
}

$app = require __DIR__ . '/../config/app.php';
date_default_timezone_set($app['timezone']);

function app_config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }

    return $config[$key] ?? $default;
}

function is_logbook_mode(): bool
{
    return (bool) app_config('logbook_mode', false);
}

function project_repo_url(): string
{
    return trim((string) app_config('github_repo_url', ''));
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = require __DIR__ . '/../config/database.php';
    }

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah(int|float $amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function current_date(): string
{
    return date('Y-m-d');
}

function format_tanggal_hari(string $dateString): string
{
    if (empty($dateString)) {
        return '';
    }
    $timestamp = strtotime($dateString);
    if (!$timestamp) {
        return $dateString;
    }
    
    $days = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $months = [
        'Jan' => 'Jan',
        'Feb' => 'Feb',
        'Mar' => 'Mar',
        'Apr' => 'Apr',
        'May' => 'Mei',
        'Jun' => 'Jun',
        'Jul' => 'Jul',
        'Aug' => 'Agt',
        'Sep' => 'Sep',
        'Oct' => 'Okt',
        'Nov' => 'Nov',
        'Dec' => 'Des'
    ];
    
    $dayNameEnglish = date('l', $timestamp);
    $dayNameIndo = $days[$dayNameEnglish] ?? $dayNameEnglish;
    
    $d = date('d', $timestamp);
    $m = date('M', $timestamp);
    $y = date('Y', $timestamp);
    
    $mIndo = $months[$m] ?? $m;
    
    return $dayNameIndo . ', ' . $d . ' ' . $mIndo . ' ' . $y;
}

function flash(?string $key = null, ?string $message = null): ?string
{
    if ($key !== null && $message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if ($key === null) {
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function base_url(string $path = ''): string
{
    $baseUrl = rtrim(app_config('base_url', ''), '/');
    
    if ($baseUrl === '') {
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        $appDir = realpath(dirname(__DIR__));
        if ($docRoot && $appDir) {
            $docRootNormalized = strtolower(str_replace('\\', '/', $docRoot));
            $appDirNormalized = strtolower(str_replace('\\', '/', $appDir));
            if (str_starts_with($appDirNormalized, $docRootNormalized)) {
                $baseUrl = substr($appDir, strlen($docRoot));
            }
        }
        $baseUrl = str_replace('\\', '/', rtrim($baseUrl, '/'));
    }
    
    return $baseUrl . '/' . ltrim($path, '/');
}

function route_url(string $page, array $params = []): string
{
    return base_url('?page=' . urlencode($page) . ($params ? '&' . http_build_query($params) : ''));
}

function asset_url(string $path): string
{
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $publicDir = realpath(dirname(__DIR__) . '/public');
    $prefix = ($docRoot && $publicDir && $docRoot === $publicDir) ? 'assets/' : 'public/assets/';

    return base_url($prefix . ltrim($path, '/'));
}

function get_app_name(): string
{
    static $appName = null;
    if ($appName === null) {
        if (is_logbook_mode()) {
            $appName = app_config('name', 'Absensi Hub');
            return $appName;
        }

        try {
            $stmt = db()->query('SELECT app_name FROM settings ORDER BY id ASC LIMIT 1');
            $settings = $stmt->fetch();
            $appName = !empty($settings['app_name']) ? $settings['app_name'] : 'Absensi Hub';
        } catch (Exception $e) {
            $appName = 'Absensi Hub';
        }
    }
    return $appName;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? '';

    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid. Muat ulang halaman.']);
            exit;
        }
        flash('error', 'Sesi kedaluwarsa. Muat ulang halaman dan coba lagi.');
        redirect($_SERVER['HTTP_REFERER'] ?? route_url('login'));
    }
}

function compress_and_save_image(string $sourcePath, string $targetPath, int $maxFileSizeKb = 500, int $maxWidthOrHeight = 1200): bool
{
    if (!extension_loaded('gd') || !function_exists('imagecreatefromjpeg')) {
        // GD library tidak tersedia, copy file secara mentah-mentah
        if ($sourcePath === $targetPath) {
            return true;
        }
        return @copy($sourcePath, $targetPath);
    }

    $imgInfo = @getimagesize($sourcePath);
    if (!$imgInfo) {
        if ($sourcePath === $targetPath) {
            return true;
        }
        return @copy($sourcePath, $targetPath);
    }

    $mime = $imgInfo['mime'];
    $srcWidth = $imgInfo[0];
    $srcHeight = $imgInfo[1];

    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $srcImage = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $srcImage = @imagecreatefrompng($sourcePath);
            break;
        case 'image/webp':
            $srcImage = @imagecreatefromwebp($sourcePath);
            break;
        default:
            if ($sourcePath === $targetPath) {
                return true;
            }
            return @copy($sourcePath, $targetPath);
    }

    if (!$srcImage) {
        if ($sourcePath === $targetPath) {
            return true;
        }
        return @copy($sourcePath, $targetPath);
    }

    // Hitung ukuran baru dengan mempertahankan aspek rasio (max 1200px)
    $newWidth = $srcWidth;
    $newHeight = $srcHeight;

    if ($srcWidth > $maxWidthOrHeight || $srcHeight > $maxWidthOrHeight) {
        if ($srcWidth > $srcHeight) {
            $newWidth = $maxWidthOrHeight;
            $newHeight = (int) ($srcHeight * ($maxWidthOrHeight / $srcWidth));
        } else {
            $newHeight = $maxWidthOrHeight;
            $newWidth = (int) ($srcWidth * ($maxWidthOrHeight / $srcHeight));
        }
    }

    $dstImage = imagecreatetruecolor($newWidth, $newHeight);
    $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

    // Jika targetnya JPEG, isi background dengan warna putih terlebih dahulu (transparansi PNG -> putih)
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $white = imagecolorallocate($dstImage, 255, 255, 255);
        imagefill($dstImage, 0, 0, $white);
    } elseif ($mime === 'image/png' || $mime === 'image/webp') {
        // Pertahankan transparansi hanya jika target bukan JPEG
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
        imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
    $success = false;

    // Kompresi dengan kualitas 75% (sangat hemat ukuran, kualitas tidak ancur)
    if ($ext === 'png') {
        $success = imagepng($dstImage, $targetPath, 6);
    } elseif ($ext === 'webp') {
        $success = imagewebp($dstImage, $targetPath, 75);
    } else {
        $success = imagejpeg($dstImage, $targetPath, 75);
    }

    imagedestroy($srcImage);
    imagedestroy($dstImage);

    // Jika ukuran file hasil kompresi masih di atas maxFileSizeKb, kompres sekali lagi lebih ketat
    if ($success && file_exists($targetPath)) {
        $fileSizeKb = filesize($targetPath) / 1024;
        if ($fileSizeKb > $maxFileSizeKb && $ext !== 'png') {
            $srcImage = ($ext === 'webp') ? @imagecreatefromwebp($targetPath) : @imagecreatefromjpeg($targetPath);
            if ($srcImage) {
                if ($ext === 'webp') {
                    imagewebp($srcImage, $targetPath, 50);
                } else {
                    imagejpeg($srcImage, $targetPath, 50);
                }
                imagedestroy($srcImage);
            }
        }
    }

    return $success;
}

function save_base64_photo(string $data, string $prefix): ?string
{
    if (!str_starts_with($data, 'data:image/')) {
        return null;
    }

    $parts = explode(',', $data, 2);
    if (count($parts) !== 2) {
        return null;
    }

    $decoded = base64_decode($parts[1], true);
    if ($decoded === false || strlen($decoded) < 2048 || strlen($decoded) > 5 * 1024 * 1024) {
        return null;
    }

    $uploadDir = app_config('upload_dir');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $fileName = $prefix . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.jpg';
    $target = $uploadDir . '/' . $fileName;
    
    // Tulis file mentah sementara
    if (file_put_contents($target, $decoded) === false) {
        return null;
    }

    // Kompresi file mentah tersebut in-place (di lokasi yang sama)
    compress_and_save_image($target, $target);

    return '/public/uploads/' . $fileName;
}

function save_uploaded_photo(array $file, string $prefix): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
        return null;
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg');
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return null;
    }

    $uploadDir = app_config('upload_dir');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    // Selalu simpan sebagai .jpg untuk kompresi maksimal (menghindari PNG lossless raksasa)
    $fileName = $prefix . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.jpg';
    $target = $uploadDir . '/' . $fileName;

    // Kompresi langsung dari temporary upload ke target path
    if (!compress_and_save_image($file['tmp_name'], $target)) {
        // Fallback jika kompresi gagal total, gunakan move_uploaded_file biasa
        // Jika fallback, gunakan ekstensi asli agar tidak merusak format file
        $fileNameFallback = $prefix . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetFallback = $uploadDir . '/' . $fileNameFallback;
        if (!move_uploaded_file($file['tmp_name'], $targetFallback)) {
            return null;
        }
        return '/public/uploads/' . $fileNameFallback;
    }

    return '/public/uploads/' . $fileName;
}

function require_attendance_photo(string $photoData, string $prefix, string $redirectRoute): string
{
    $path = save_base64_photo($photoData, $prefix);
    if ($path === null) {
        flash('error', 'Foto selfie wajib diambil sebelum absen.');
        redirect(route_url($redirectRoute));
    }

    return $path;
}

function auto_checkout_hanging_attendances(): void
{
    try {
        $today = date('Y-m-d');
        // Cari absensi hari-hari sebelumnya yang masih 'bekerja' atau 'terlambat' dan belum check-out
        $stmt = db()->prepare("SELECT * FROM absensi WHERE tanggal < ? AND jam_keluar IS NULL AND status IN ('bekerja', 'terlambat')");
        $stmt->execute([$today]);
        $hangings = $stmt->fetchAll();

        if (empty($hangings)) {
            return;
        }

        // Panggil helper/wage-calculator jika belum di-require
        require_once __DIR__ . '/wage-calculator.php';
        require_once __DIR__ . '/geofence.php'; // untuk get_settings

        $settings = get_settings();
        $potonganSet = (int) ($settings['potongan_terlambat'] ?? 5000);

        $stmtUpdate = db()->prepare("
            UPDATE absensi 
            SET jam_keluar = ?, 
                skema = ?, 
                upah_final = ?, 
                potongan = ?, 
                lembur = 0, 
                status = 'selesai', 
                keterangan_keluar_awal = 'Lupa Keluar' 
            WHERE id = ?
        ");

        foreach ($hangings as $row) {
            $jamMasuk = $row['jam_masuk'];
            $in = time_to_minutes($jamMasuk);

            // Tentukan kelompok target jam masuk terdekat (480=08:00, 540=09:00, 600=10:00, 750=12:30)
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

            if ($bestStart === 480) {
                $skema = '08.00 - 17.00';
                $base = (int)($settings['upah_08_17'] ?? 90000);
            } elseif ($bestStart === 540) {
                $skema = '09.00 - 17.00';
                $base = (int)($settings['upah_09_17'] ?? 80000);
            } elseif ($bestStart === 600) {
                $skema = '10.00 - 17.00';
                $base = (int)($settings['upah_10_17'] ?? 70000);
            } else {
                $skema = '12.30 - 17.30';
                $base = (int)($settings['upah_13_17'] ?? 60000);
            }

            // Cek keterlambatan untuk potongan
            $late = is_late_checkin($jamMasuk, $settings);
            $potongan = $late ? $potonganSet : 0;
            $total = max(0, $base - $potongan);

            // Set jam keluar otomatis berdasarkan kuota jam kerja harian setelah jam masuk, tapi tidak lewat 23:59:59
            $kuotaJam = max(1.0, (float) ($settings['default_kuota'] ?? 8.0));
            $masukTs = strtotime($jamMasuk);
            $keluarTs = $masukTs + (int) round($kuotaJam * 3600);
            $jamKeluar = date('H:i:s', $keluarTs);
            if (date('Y-m-d', $keluarTs) !== date('Y-m-d', $masukTs)) {
                $jamKeluar = '23:59:59';
            }

            $stmtUpdate->execute([
                $jamKeluar,
                $skema,
                $total,
                $potongan,
                $row['id']
            ]);
        }
    } catch (Exception $e) {
        // Jangan hentikan aplikasi jika terjadi error pada auto-checkout
        error_log('Error in auto_checkout_hanging_attendances: ' . $e->getMessage());
    }
}



