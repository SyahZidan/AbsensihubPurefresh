<?php

require_once __DIR__ . '/../includes/auth.php';

$page = $_GET['page'] ?? 'login';
$publicPages = ['login', 'register', 'forgot-password', 'reset-password'];

// Cek apakah user sudah login
$user = current_user();

if ($user) {
    auto_checkout_hanging_attendances();
}

// Jika request ke home atau default, alihkan langsung ke login (atau dashboard jika sudah login)
if ($page === 'home') {
    if ($user) {
        redirect(route_url($user['role'] === 'admin' ? 'admin.dashboard' : 'karyawan.home'));
    }
    redirect(route_url('login'));
}

// Jika user sudah login dan mencoba akses login → arahkan ke portal masing-masing
if ($user && $page === 'login') {
    redirect(route_url($user['role'] === 'admin' ? 'admin.dashboard' : 'karyawan.home'));
}

// Batasi akses modul non-inti saat logbook mode aktif
if (is_logbook_mode()) {
    if ($page === 'karyawan.leaves') {
        redirect(route_url('karyawan.home'));
    }
    if (in_array($page, ['admin.leaves', 'admin.payment', 'admin.logs', 'admin.quota-report', 'admin.holidays'], true)) {
        redirect(route_url('admin.dashboard'));
    }
}

$routes = [
    'login'                => __DIR__ . '/../views/login.php',
    'register'             => __DIR__ . '/../views/register.php',
    'forgot-password'      => __DIR__ . '/../views/forgot-password.php',
    'reset-password'       => __DIR__ . '/../views/reset-password.php',
    'karyawan.home'        => __DIR__ . '/../views/karyawan/home.php',
    'karyawan.checkin'     => __DIR__ . '/../views/karyawan/checkin.php',
    'karyawan.checkout'    => __DIR__ . '/../views/karyawan/checkout.php',
    'karyawan.history'     => __DIR__ . '/../views/karyawan/history.php',
    'karyawan.leaves'      => __DIR__ . '/../views/karyawan/leaves.php',
    'karyawan.profile'     => __DIR__ . '/../views/karyawan/profile.php',
    'admin.dashboard'      => __DIR__ . '/../views/admin/dashboard.php',
    'admin.employees'      => __DIR__ . '/../views/admin/employees.php',
    'admin.employee-detail'=> __DIR__ . '/../views/admin/employee-detail.php',
    'admin.attendance'     => __DIR__ . '/../views/admin/attendance.php',
    'admin.wage'           => __DIR__ . '/../views/admin/wage.php',
    'admin.payment'        => __DIR__ . '/../views/admin/payment.php',
    'admin.leaves'         => __DIR__ . '/../views/admin/leaves.php',
    'admin.logs'           => __DIR__ . '/../views/admin/logs.php',
    'admin.settings'       => __DIR__ . '/../views/admin/settings.php',
    'admin.quota-report'   => __DIR__ . '/../views/admin/quota-report.php',
    'admin.holidays'       => __DIR__ . '/../views/admin/holidays.php',
];

if (!isset($routes[$page])) {
    http_response_code(404);
    $view = __DIR__ . '/../views/404.php';
} else {
    $view = $routes[$page];
}

if (!in_array($page, $publicPages, true)) {
    $requiredRole = str_starts_with($page, 'admin.') ? 'admin' : 'karyawan';
    $user = require_login($requiredRole);
}

require $view;
