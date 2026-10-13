<?php
$title = 'Pengaturan Sistem';
$active = 'admin.settings';
require_once __DIR__ . '/../../includes/geofence.php';
require_once __DIR__ . '/../../includes/features.php';
ensure_settings_extra_columns();
$settings = get_settings();

// Check auto-backup for last month
$lastMonth = date('Y-m', strtotime('first day of last month'));
$backupDir = __DIR__ . '/../../public/uploads/backups/';
$backupFile = $backupDir . "rekap-upah-" . $lastMonth . ".xls";
if (!file_exists($backupFile)) {
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
    }   
    
    $stmtB = db()->prepare("
        SELECT u.id, u.nama, u.no_hp,
            COUNT(a.id) AS hari,
            COALESCE(SUM(CASE WHEN a.jam_masuk IS NOT NULL THEN 1 ELSE 0 END),0) AS total_hadir,
            COALESCE(SUM(a.lembur),0)         AS lembur,
            COALESCE(SUM(a.potongan),0)       AS potongan,
            COALESCE(SUM(a.upah_final),0)     AS total,
            COALESCE(SUM(CASE WHEN a.status_bayar='sudah_dibayar' THEN a.upah_final ELSE 0 END),0) AS terbayar,
            COALESCE(SUM(CASE WHEN a.status_bayar='belum_dibayar' THEN a.upah_final ELSE 0 END),0) AS belum_bayar
        FROM users u
        LEFT JOIN absensi a ON a.user_id = u.id AND DATE_FORMAT(a.tanggal,'%Y-%m') = ?
        WHERE u.role = 'karyawan'
        GROUP BY u.id, u.nama, u.no_hp
        ORDER BY total DESC
    ");
    $stmtB->execute([$lastMonth]);
    $rowsB = $stmtB->fetchAll();
    
    $appName = !empty($settings['app_name']) ? $settings['app_name'] : 'Absensi Hub';
    $dateStr = date('d-m-Y H:i:s');
    $periode = "Periode Bulan: " . date('F Y', strtotime($lastMonth . "-01"));
    
    ob_start();
    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .header-table { margin-bottom: 20px; }
            .header-title { font-size: 18px; font-weight: bold; color: #0f172a; }
            .header-sub { font-size: 12px; color: #64748b; }
            .data-table { border-collapse: collapse; width: 100%; }
            .data-table th { background-color: #10b981; color: #ffffff; font-weight: bold; border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
            .data-table td { border: 1px solid #cbd5e1; padding: 8px; font-size: 13px; color: #334155; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .bg-stripe { background-color: #f8fafc; }
            .font-bold { font-weight: bold; }
            .footer-row { background-color: #f1f5f9; font-weight: bold; }
        </style>
    </head>
    <body>
        <table class="header-table" border="0">
            <tr>
                <td colspan="9" class="header-title"><?= htmlspecialchars($appName) ?></td>
            </tr>
            <tr>
                <td colspan="9" class="header-title">AUTO-BACKUP REKAP UPAH BULANAN (OTOMATIS)</td>
            </tr>
            <tr>
                <td colspan="9" class="header-sub"><?= htmlspecialchars($periode) ?></td>
            </tr>
            <tr>
                <td colspan="9" class="header-sub">Waktu Backup: <?= $dateStr ?></td>
            </tr>
        </table>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Karyawan</th>
                    <th>Nomor HP</th>
                    <th class="text-center">Hari Hadir</th>
                    <th class="text-right">Lembur</th>
                    <th class="text-right">Potongan</th>
                    <th class="text-right">Total Upah</th>
                    <th class="text-right">Terbayar</th>
                    <th class="text-right">Sisa (Belum Dibayar)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                $sumHadir = 0;
                $sumLembur = 0;
                $sumPotongan = 0;
                $sumTotal = 0;
                $sumTerbayar = 0;
                $sumBelum = 0;
                
                foreach ($rowsB as $row): 
                    $sumHadir += $row['total_hadir'];
                    $sumLembur += $row['lembur'];
                    $sumPotongan += $row['potongan'];
                    $sumTotal += $row['total'];
                    $sumTerbayar += $row['terbayar'];
                    $sumBelum += $row['belum_bayar'];
                    $class = ($i % 2 === 0) ? 'class="bg-stripe"' : '';
                ?>
                <tr <?= $class ?>>
                    <td class="text-center"><?= $i++ ?></td>
                    <td class="font-bold"><?= htmlspecialchars($row['nama']) ?></td>
                    <td>'<?= htmlspecialchars($row['no_hp']) ?></td>
                    <td class="text-center"><?= (int)$row['total_hadir'] ?> hari</td>
                    <td class="text-right"><?= (int)$row['lembur'] ?></td>
                    <td class="text-right"><?= (int)$row['potongan'] ?></td>
                    <td class="text-right font-bold"><?= (int)$row['total'] ?></td>
                    <td class="text-right" style="color: #059669;"><?= (int)$row['terbayar'] ?></td>
                    <td class="text-right" style="color: #dc2626;"><?= (int)$row['belum_bayar'] ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="footer-row">
                    <td colspan="3" class="text-center">TOTAL</td>
                    <td class="text-center"><?= $sumHadir ?> hari</td>
                    <td class="text-right"><?= $sumLembur ?></td>
                    <td class="text-right"><?= $sumPotongan ?></td>
                    <td class="text-right"><?= $sumTotal ?></td>
                    <td class="text-right" style="color: #059669;"><?= $sumTerbayar ?></td>
                    <td class="text-right" style="color: #dc2626;"><?= $sumBelum ?></td>
                </tr>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    $htmlContent = ob_get_clean();
    file_put_contents($backupFile, $htmlContent);
}

// List backup files
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (preg_match('/^rekap-upah-(\d{4}-\d{2})\.xls$/', $file, $matches)) {
            $backups[] = [
                'filename' => $file,
                'bulan' => $matches[1],
                'size' => filesize($backupDir . $file),
                'created_at' => date('d M Y H:i', filemtime($backupDir . $file))
            ];
        }
    }
}
usort($backups, fn($a, $b) => strcmp($b['bulan'], $a['bulan']));

require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/admin_sidebar.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<main class="relative z-0 min-h-screen lg:ml-64">
    <!-- Top Bar -->
    <div class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200/60 bg-white/80 px-4 py-3 sm:px-6 sm:py-4 backdrop-blur-xl">
        <div class="flex items-center gap-2.5 sm:gap-3">
            <button @click="mobileSidebarOpen = true" class="lg:hidden flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center rounded-xl bg-slate-900 text-white shadow-md transition hover:bg-slate-800">
                <i data-lucide="menu" class="h-4 w-4 sm:h-5 sm:w-5"></i>
            </button>
            <div>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-widest text-slate-400">Admin Portal</p>
                <h1 class="text-base sm:text-xl font-extrabold text-slate-900 font-outfit">Pengaturan Sistem</h1>
            </div>
        </div>
    </div>

    <div class="p-3 sm:p-5 lg:p-8">
        <div class="grid gap-6 lg:grid-cols-3 items-start max-w-6xl">
            <!-- Left Column: Settings Form -->
            <div class="lg:col-span-2">
                <form id="settingsForm" action="<?= base_url('api/settings.php') ?>" method="post" class="space-y-4 sm:space-y-6">
                    <?= csrf_field() ?>

            <!-- Section 1: Pengaturan Upah & Uang (Finansial) -->
            <div class="rounded-xl sm:rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
                <div class="mb-4 sm:mb-5 flex items-center gap-2.5 sm:gap-3">
                    <div class="flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-lg sm:rounded-xl bg-amber-100 text-amber-600">
                        <i data-lucide="coins" class="h-4 w-4 sm:h-5 sm:w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">Pengaturan Upah & Uang</h2>
                        <p class="text-[11px] sm:text-xs text-slate-400">Atur nominal upah skema kerja flat, tarif lembur, dan denda</p>
                    </div>
                </div>
                <!-- 15 Skema Kerja & Upah -->
                <div class="mb-4 rounded-xl bg-slate-50 p-4 border border-slate-200">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-emerald-600 mb-3 font-outfit flex items-center gap-1.5">
                        <i data-lucide="clock" class="h-4 w-4"></i> 4 Skema Jam Wajib (Default Reguler)
                    </h3>
                    <div class="grid gap-3 sm:grid-cols-4">
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold text-slate-700">08.00 - 17.00 (Rp)</span>
                            <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition">
                                <span class="border-r border-slate-200 px-2 py-2 text-xs text-slate-400 font-bold">Rp</span>
                                <input type="number" name="upah_08_17" value="<?= e($settings['upah_08_17'] ?? 90000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-2 text-xs font-bold outline-none text-slate-800">
                            </div>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold text-slate-700">09.00 - 17.00 (Rp)</span>
                            <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition">
                                <span class="border-r border-slate-200 px-2 py-2 text-xs text-slate-400 font-bold">Rp</span>
                                <input type="number" name="upah_09_17" value="<?= e($settings['upah_09_17'] ?? 80000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-2 text-xs font-bold outline-none text-slate-800">
                            </div>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold text-slate-700">10.00 - 17.00 (Rp)</span>
                            <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition">
                                <span class="border-r border-slate-200 px-2 py-2 text-xs text-slate-400 font-bold">Rp</span>
                                <input type="number" name="upah_10_17" value="<?= e($settings['upah_10_17'] ?? 70000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-2 text-xs font-bold outline-none text-slate-800">
                            </div>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold text-slate-700">12.30 - 17.30 (Rp)</span>
                            <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition">
                                <span class="border-r border-slate-200 px-2 py-2 text-xs text-slate-400 font-bold">Rp</span>
                                <input type="number" name="upah_13_17" value="<?= e($settings['upah_13_17'] ?? 60000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-2 text-xs font-bold outline-none text-slate-800">
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-4 rounded-xl bg-amber-50/50 p-4 border border-amber-200/60 space-y-4">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-amber-700 font-outfit flex items-center gap-1.5">
                        <i data-lucide="zap" class="h-4 w-4 text-amber-600"></i> 16 Skema Lembur (Input Manual Nominal)
                    </h3>

                    <!-- Skema Jam 8 -->
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Kelompok Masuk 08.00</p>
                        <div class="grid gap-2 sm:grid-cols-4">
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">08.00 - 19.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_08_19" value="<?= e($settings['upah_08_19'] ?? 100000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">08.00 - 20.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_08_20" value="<?= e($settings['upah_08_20'] ?? 110000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">08.00 - 21.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_08_21" value="<?= e($settings['upah_08_21'] ?? 120000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">08.00 - 22.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_08_22" value="<?= e($settings['upah_08_22'] ?? 130000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Skema Jam 9 -->
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Kelompok Masuk 09.00</p>
                        <div class="grid gap-2 sm:grid-cols-4">
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">09.00 - 19.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_09_19" value="<?= e($settings['upah_09_19'] ?? 90000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">09.00 - 20.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_09_20" value="<?= e($settings['upah_09_20'] ?? 100000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">09.00 - 21.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_09_21" value="<?= e($settings['upah_09_21'] ?? 110000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">09.00 - 22.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_09_22" value="<?= e($settings['upah_09_22'] ?? 120000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Skema Jam 10 -->
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Kelompok Masuk 10.00</p>
                        <div class="grid gap-2 sm:grid-cols-4">
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">10.00 - 19.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_10_19" value="<?= e($settings['upah_10_19'] ?? 80000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">10.00 - 20.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_10_20" value="<?= e($settings['upah_10_20'] ?? 90000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">10.00 - 21.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_10_21" value="<?= e($settings['upah_10_21'] ?? 100000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">10.00 - 22.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_10_22" value="<?= e($settings['upah_10_22'] ?? 110000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Skema Jam 12.30 -->
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Kelompok Masuk 12.30 (Jam 12.30)</p>
                        <div class="grid gap-2 sm:grid-cols-4">
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">12.30 - 19.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_13_19" value="<?= e($settings['upah_13_19'] ?? 70000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">12.30 - 20.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_13_20" value="<?= e($settings['upah_13_20'] ?? 80000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">12.30 - 21.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_13_21" value="<?= e($settings['upah_13_21'] ?? 90000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-semibold text-slate-600">12.30 - 22.00 (Rp)</span>
                                <div class="flex items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-amber-400 transition">
                                    <span class="border-r border-slate-200 px-2 py-1.5 text-xs text-slate-400 font-bold">Rp</span>
                                    <input type="number" name="upah_13_22" value="<?= e($settings['upah_13_22'] ?? 100000) ?>" min="0" step="1000" class="w-full bg-transparent px-2 py-1.5 text-xs font-bold outline-none text-slate-800">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 sm:gap-5 sm:grid-cols-2">
                    <label class="block hidden">
                        <input type="hidden" name="upah_skema_1" value="<?= e($settings['upah_skema_1'] ?? 70000) ?>">
                        <input type="hidden" name="upah_skema_2" value="<?= e($settings['upah_skema_2'] ?? 60000) ?>">
                        <input type="hidden" name="upah_skema_3" value="<?= e($settings['upah_skema_3'] ?? 130000) ?>">
                        <input type="hidden" name="upah_skema_4" value="<?= e($settings['upah_skema_4'] ?? 120000) ?>">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Upah Per Jam Dasar (Rp)</span>
                        <div class="flex items-center overflow-hidden rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition">
                            <span class="border-r border-slate-200 bg-white px-2.5 py-2.5 sm:px-3 sm:py-3 text-xs sm:text-sm text-slate-400">Rp</span>
                            <input type="number" name="upah_per_jam" value="<?= e($settings['upah_per_jam'] ?? 10000) ?>"
                                   min="1000" step="500"
                                   class="flex-1 bg-transparent px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm outline-none text-slate-800">
                        </div>
                        <p class="mt-1 text-[11px] sm:text-xs text-slate-400">Upah dasar per jam kerja (digunakan untuk lembur non-flat)</p>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Potongan Terlambat (Rp)</span>
                        <div class="flex items-center overflow-hidden rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition">
                            <span class="border-r border-slate-200 bg-white px-2.5 py-2.5 sm:px-3 sm:py-3 text-xs sm:text-sm text-slate-400">Rp</span>
                            <input type="number" name="potongan_terlambat" value="<?= e($settings['potongan_terlambat'] ?? 5000) ?>"
                                   min="0" step="500"
                                   class="flex-1 bg-transparent px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm outline-none text-slate-800">
                        </div>
                        <p class="mt-1 text-[11px] sm:text-xs text-slate-400">Denda potong gaji jika terdeteksi terlambat masuk kerja</p>
                    </label>
                </div>
            </div>

            <!-- Section 2: Pengaturan Waktu & Jadwal (Waktu) -->
            <div class="rounded-xl sm:rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
                <div class="mb-4 sm:mb-5 flex items-center gap-2.5 sm:gap-3">
                    <div class="flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-lg sm:rounded-xl bg-indigo-100 text-indigo-600">
                        <i data-lucide="clock" class="h-4 w-4 sm:h-5 sm:w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">Pengaturan Waktu & Jadwal</h2>
                        <p class="text-[11px] sm:text-xs text-slate-400">Atur jam masuk target shift, batas pulang, dan toleransi keterlambatan</p>
                    </div>
                </div>
                <div class="grid gap-3 sm:gap-5 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Target Masuk Shift Pagi</span>
                        <input type="time" name="jam_masuk_default" value="<?= e(substr($settings['jam_masuk_default'] ?? '10:00:00', 0, 5)) ?>"
                               class="w-full border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm rounded-lg sm:rounded-xl outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800">
                        <p class="mt-1 text-[11px] sm:text-xs text-slate-400">Target jam masuk shift Pagi (Default: 10:00)</p>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Target Masuk Shift Siang</span>
                        <input type="time" name="jam_skema_b" value="<?= e(substr($settings['jam_skema_b'] ?? '13:00:00', 0, 5)) ?>"
                               class="w-full border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm rounded-lg sm:rounded-xl outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800">
                        <p class="mt-1 text-[11px] sm:text-xs text-slate-400">Target jam masuk shift Siang (Default: 13:00)</p>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Batas Keluar Siang / Sore</span>
                        <input type="time" name="jam_batas_keluar_awal" value="<?= e(substr($settings['jam_batas_keluar_awal'] ?? '17:00:00', 0, 5)) ?>"
                               class="w-full border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm rounded-lg sm:rounded-xl outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800">
                        <p class="mt-1 text-[11px] sm:text-xs text-slate-400">Keluar sebelum jam ini terdeteksi pulang awal dan butuh bukti (Default: 17:00)</p>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Batas Masuk Malam (Lembur Flat)</span>
                        <input type="time" name="jam_lembur_flat" value="<?= e(substr($settings['jam_lembur_flat'] ?? '21:30:00', 0, 5)) ?>"
                               class="w-full border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm rounded-lg sm:rounded-xl outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800">
                        <p class="mt-1 text-[11px] sm:text-xs text-slate-400">Batas checkout malam untuk mendapatkan upah lembur flat malam (Default: 21:30)</p>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Toleransi Terlambat (menit)</span>
                        <input type="number" name="menit_toleransi_terlambat" value="<?= e($settings['menit_toleransi_terlambat'] ?? 15) ?>"
                               min="0" max="120" class="w-full border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm rounded-lg sm:rounded-xl outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800">
                        <p class="mt-1 text-[11px] sm:text-xs text-slate-400">Toleransi keterlambatan dalam menit (misal: 15 menit)</p>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Jam Kerja Normal Harian</span>
                        <div class="flex items-center overflow-hidden rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition">
                            <span class="border-r border-slate-200 bg-white px-2.5 py-2.5 sm:px-3 sm:py-3 text-sm text-slate-400"><i data-lucide="clock" class="h-3.5 w-3.5 sm:h-4 sm:w-4"></i></span>
                            <input type="number" name="default_kuota" value="<?= e($settings['default_kuota']) ?>"
                                   min="1" max="24" step="0.5"
                                   class="flex-1 bg-transparent px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm outline-none text-slate-800">
                            <span class="px-2.5 text-xs sm:text-sm text-slate-400">jam</span>
                        </div>
                        <p class="mt-1 text-[11px] sm:text-xs text-slate-400">Durasi jam kerja standar sebelum pemicu lembur per jam dihitung</p>
                    </label>
                </div>
            </div>

            <!-- Section 2: Geofence Settings -->
            <div class="rounded-xl sm:rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
                <div class="mb-4 sm:mb-5 flex items-center gap-2.5 sm:gap-3">
                    <div class="flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-lg sm:rounded-xl bg-blue-100 text-blue-600">
                        <i data-lucide="map-pin" class="h-4 w-4 sm:h-5 sm:w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">Pengaturan Geofence</h2>
                        <p class="text-[11px] sm:text-xs text-slate-400">Tentukan lokasi dan radius absen</p>
                    </div>
                </div>

                <div class="grid gap-3 sm:gap-5 grid-cols-2 sm:grid-cols-3 mb-4 sm:mb-5">
                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Latitude</span>
                        <input type="text" name="geofence_lat" id="inputLat" value="<?= e($settings['geofence_lat']) ?>"
                               class="w-full rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Longitude</span>
                        <input type="text" name="geofence_lng" id="inputLng" value="<?= e($settings['geofence_lng']) ?>"
                               class="w-full rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800">
                    </label>
                    <label class="block col-span-2 sm:col-span-1">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Radius (meter)</span>
                        <div class="flex items-center overflow-hidden rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition">
                            <input type="number" name="geofence_radius" id="inputRadius" value="<?= e($settings['geofence_radius']) ?>"
                                   min="10" step="10"
                                   class="flex-1 bg-transparent px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm outline-none text-slate-800" oninput="updateCircle()">
                            <span class="px-2.5 text-xs sm:text-sm text-slate-400">m</span>
                        </div>
                    </label>
                </div>

                <!-- Tips -->
                <div class="mb-3 sm:mb-4 flex items-start gap-2 rounded-lg sm:rounded-xl border border-blue-100 bg-blue-50 p-3 sm:p-4 text-[11px] sm:text-xs text-blue-700">
                    <i data-lucide="info" class="mt-0.5 h-3.5 w-3.5 sm:h-4 sm:w-4 shrink-0 text-blue-500"></i>
                    <span><strong>Tip:</strong> Klik peta untuk memilih koordinat. Lingkaran hijau = radius geofence aktif.</span>
                </div>

                <!-- Button get current location -->
                <button type="button" onclick="getMyLocation()"
                    class="mb-3 sm:mb-4 flex items-center gap-1.5 sm:gap-2 rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 sm:px-4 sm:py-2.5 text-xs sm:text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                    <i data-lucide="crosshair" class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-emerald-500"></i> Gunakan Lokasi Saya
                </button>

                <!-- Leaflet Map -->
                <div class="geofence-map-wrapper rounded-xl sm:rounded-2xl border border-slate-200 shadow-inner overflow-hidden">
                    <div id="geofence-map" class="h-48 sm:h-72 w-full"></div>
                </div>
            </div>

            <!-- Section 3: App Info -->
            <div class="rounded-xl sm:rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
                <div class="mb-4 sm:mb-5 flex items-center gap-2.5 sm:gap-3">
                    <div class="flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-lg sm:rounded-xl bg-purple-100 text-purple-600">
                        <i data-lucide="building-2" class="h-4 w-4 sm:h-5 sm:w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">Informasi UMKM & Pembatasan</h2>
                        <p class="text-[11px] sm:text-xs text-slate-400">Nama usaha dan batas operasional karyawan harian</p>
                    </div>
                </div>
                <div class="grid gap-3 sm:gap-5 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Nama Perusahaan / UMKM</span>
                        <input type="text" name="app_name" value="<?= e($settings['app_name'] ?? 'Absensi Hub') ?>"
                               class="w-full rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Kuota Karyawan Harian (slot kerja)</span>
                        <div class="flex items-center overflow-hidden rounded-lg sm:rounded-xl border border-emerald-200 bg-emerald-50/50 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition">
                            <span class="border-r border-emerald-200 bg-white px-2.5 py-2.5 sm:px-3 sm:py-3 text-sm text-emerald-600"><i data-lucide="users" class="h-3.5 w-3.5 sm:h-4 sm:w-4"></i></span>
                            <input type="number" name="kuota_karyawan_harian" value="<?= e($settings['kuota_karyawan_harian'] ?? 0) ?>"
                                   min="0" step="1"
                                   class="flex-1 bg-transparent px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm outline-none text-slate-800">
                            <span class="px-2.5 text-xs sm:text-sm text-slate-500">orang / hari</span>
                        </div>
                        <p class="mt-1 text-[11px] sm:text-xs text-slate-500">Kuota absen masuk harian. Isi 0 jika tidak dibatasi.</p>
                    </label>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex">
                <button type="submit"
                    class="w-full sm:w-auto sm:ml-auto flex items-center justify-center gap-2 rounded-lg sm:rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-6 py-3 sm:px-8 sm:py-3.5 text-sm sm:text-base font-bold text-white shadow-lg shadow-emerald-500/25 transition hover:opacity-90 hover:scale-[1.02]">
                    <i data-lucide="save" class="h-4 w-4 sm:h-5 sm:w-5"></i>
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>

    <!-- Right Column: Live Simulation Card -->
    <div class="lg:col-span-1 lg:sticky lg:top-24 space-y-4">
        <div class="rounded-xl sm:rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shadow-sm border border-emerald-100">
                    <i data-lucide="calculator" class="h-4.5 w-4.5"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 font-outfit">Simulasi Upah Real-time</h2>
                    <p class="text-[10px] text-slate-400">Estimasi upah harian berdasarkan input Anda</p>
                </div>
            </div>
            
            <div class="border-t border-slate-100 pt-3 space-y-4 max-h-[60vh] overflow-y-auto pr-1">
                <!-- Kelompok 08.00 -->
                <div class="space-y-2">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kelompok Masuk 08.00</h3>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between items-center p-2 rounded-lg bg-slate-50 border border-slate-100">
                            <div>
                                <p class="font-bold text-slate-700">08.00 - 17.00 (Wajib)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-08-17-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-slate-950 font-outfit text-sm" id="sim-08-17">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">08.00 - 19.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-08-19-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-08-19">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">08.00 - 20.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-08-20-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-08-20">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">08.00 - 21.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-08-21-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-08-21">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">08.00 - 22.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-08-22-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-08-22">Rp -</span>
                        </div>
                    </div>
                </div>

                <!-- Kelompok 09.00 -->
                <div class="space-y-2">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider pt-2 border-t border-slate-100/50">Kelompok Masuk 09.00</h3>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between items-center p-2 rounded-lg bg-slate-50 border border-slate-100">
                            <div>
                                <p class="font-bold text-slate-700">09.00 - 17.00 (Wajib)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-09-17-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-slate-950 font-outfit text-sm" id="sim-09-17">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">09.00 - 19.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-09-19-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-09-19">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">09.00 - 20.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-09-20-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-09-20">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">09.00 - 21.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-09-21-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-09-21">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">09.00 - 22.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-09-22-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-09-22">Rp -</span>
                        </div>
                    </div>
                </div>

                <!-- Kelompok 10.00 -->
                <div class="space-y-2">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider pt-2 border-t border-slate-100/50">Kelompok Masuk 10.00</h3>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between items-center p-2 rounded-lg bg-slate-50 border border-slate-100">
                            <div>
                                <p class="font-bold text-slate-700">10.00 - 17.00 (Wajib)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-10-17-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-slate-950 font-outfit text-sm" id="sim-10-17">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">10.00 - 19.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-10-19-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-10-19">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">10.00 - 20.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-10-20-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-10-20">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">10.00 - 21.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-10-21-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-10-21">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">10.00 - 22.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-10-22-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-10-22">Rp -</span>
                        </div>
                    </div>
                </div>

                <!-- Kelompok 12.30 -->
                <div class="space-y-2">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider pt-2 border-t border-slate-100/50">Kelompok Masuk 12.30</h3>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between items-center p-2 rounded-lg bg-slate-50 border border-slate-100">
                            <div>
                                <p class="font-bold text-slate-700">12.30 - 17.30 (Wajib)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-13-17-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-slate-950 font-outfit text-sm" id="sim-13-17">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">12.30 - 19.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-13-19-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-13-19">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">12.30 - 20.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-13-20-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-13-20">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">12.30 - 21.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-13-21-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-13-21">Rp -</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-amber-50/20 border border-amber-100/30">
                            <div>
                                <p class="font-bold text-slate-700">12.30 - 22.00 (Lembur)</p>
                                <p class="text-[9px] text-rose-500 mt-0.5">Jika lambat: <span id="sim-13-22-late">Rp -</span></p>
                            </div>
                            <span class="font-bold text-amber-700 font-outfit text-sm" id="sim-13-22">Rp -</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-slate-100 pt-3 text-[10px] text-slate-400 space-y-1.5 leading-relaxed">
                <p>&bull; <strong>Denda Keterlambatan</strong> dipotong otomatis dari total upah harian skema yang bersangkutan jika karyawan terlambat masuk.</p>
            </div>
        </div>
    </div>
</div>

<div class="mt-5 sm:mt-8 grid gap-4 sm:gap-6 max-w-3xl lg:grid-cols-2">
            <form action="<?= base_url('api/admin-account.php') ?>" method="post" class="rounded-xl sm:rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_phone">
                <div class="mb-4 sm:mb-5 flex items-center gap-2.5 sm:gap-3">
                    <div class="flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-lg sm:rounded-xl bg-cyan-100 text-cyan-600">
                        <i data-lucide="phone" class="h-4 w-4 sm:h-5 sm:w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">Nomor Telepon Admin</h2>
                        <p class="text-[11px] sm:text-xs text-slate-400">Nomor ini dipakai untuk login admin</p>
                    </div>
                </div>
                <label class="block">
                    <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Nomor Telepon</span>
                    <input type="tel" name="no_hp" value="<?= e($user['no_hp'] ?? '') ?>" required inputmode="numeric" pattern="[0-9]+"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                           class="w-full rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800">
                </label>
                <button type="submit" class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg sm:rounded-xl bg-slate-900 px-4 py-3 text-xs sm:text-sm font-bold text-white shadow transition hover:bg-slate-700">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Simpan Nomor Admin
                </button>
            </form>

            <form action="<?= base_url('api/admin-account.php') ?>" method="post" class="rounded-xl sm:rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_password">
                <div class="mb-4 sm:mb-5 flex items-center gap-2.5 sm:gap-3">
                    <div class="flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-lg sm:rounded-xl bg-rose-100 text-rose-600">
                        <i data-lucide="key-round" class="h-4 w-4 sm:h-5 sm:w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">Password Admin</h2>
                        <p class="text-[11px] sm:text-xs text-slate-400">Masukkan password lama sebelum mengganti</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Password Lama</span>
                        <input type="password" name="old_password" required autocomplete="current-password"
                               class="w-full rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Password Baru</span>
                        <input type="password" name="new_password" required minlength="6" autocomplete="new-password"
                               class="w-full rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs sm:text-sm font-semibold text-slate-700">Konfirmasi Password Baru</span>
                        <input type="password" name="confirm_password" required minlength="6" autocomplete="new-password"
                               class="w-full rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800">
                    </label>
                </div>
                <button type="submit" class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg sm:rounded-xl bg-rose-600 px-4 py-3 text-xs sm:text-sm font-bold text-white shadow transition hover:bg-rose-700">
                    <i data-lucide="key-round" class="h-4 w-4"></i>
                    Ubah Password Admin
                </button>
            </form>
        </div>

        <!-- Section 4: Backup & Rekap Bulanan -->
        <div class="mt-5 sm:mt-8 rounded-xl sm:rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm max-w-3xl">
            <div class="mb-4 sm:mb-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <div class="flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-lg sm:rounded-xl bg-emerald-100 text-emerald-600">
                        <i data-lucide="archive" class="h-4 w-4 sm:h-5 sm:w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">Backup & Rekap Bulanan</h2>
                        <p class="text-[11px] sm:text-xs text-slate-400">Unduh/buat rekap upah & absensi bulanan (.xls)</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="month" id="manualBackupMonth" class="flex-1 sm:flex-none rounded-lg sm:rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs sm:text-sm outline-none focus:border-emerald-400 text-slate-800">
                    <button type="button" onclick="generateBackup(this)" class="whitespace-nowrap rounded-lg sm:rounded-xl bg-slate-900 px-3 py-2 sm:px-4 text-xs sm:text-sm font-bold text-white shadow transition hover:bg-slate-700">
                        Generate
                    </button>
                </div>
            </div>

            <!-- Backups list -->
            <div class="overflow-hidden rounded-xl border border-slate-100 bg-slate-50/50">
                <?php if (empty($backups)): ?>
                    <div class="flex flex-col items-center justify-center py-6 sm:py-10 text-center">
                        <i data-lucide="folder-open" class="mb-2 h-6 w-6 sm:h-8 sm:w-8 text-slate-300"></i>
                        <p class="text-xs sm:text-sm font-semibold text-slate-500">Belum ada file backup</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($backups as $b): ?>
                            <div class="flex items-center justify-between p-3 sm:p-4 transition-colors hover:bg-slate-100/50" id="backup-row-<?= e($b['filename']) ?>">
                                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                                    <div class="hidden sm:flex h-9 w-9 items-center justify-center rounded-xl bg-white text-emerald-500 shadow-sm border border-slate-100 shrink-0">
                                        <i data-lucide="file-spreadsheet" class="h-4.5 w-4.5"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 text-xs sm:text-sm truncate">Rekap - <?= date('M Y', strtotime($b['bulan'] . "-01")) ?></p>
                                        <p class="text-[11px] sm:text-xs text-slate-400 truncate"><?= e($b['created_at']) ?> &bull; <?= round($b['size'] / 1024, 1) ?> KB</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                                    <a href="<?= base_url('public/uploads/backups/' . e($b['filename'])) ?>" download class="flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white transition shadow-sm" title="Unduh File">
                                        <i data-lucide="download" class="h-3.5 w-3.5 sm:h-4 sm:w-4"></i>
                                    </a>
                                    <button type="button" onclick="deleteBackup('<?= e($b['filename']) ?>')" class="flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white transition shadow-sm" title="Hapus Backup">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5 sm:h-4 sm:w-4"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let map, marker, circle;

function initGeofenceMap() {
    const initLat = parseFloat(document.getElementById('inputLat').value) || -6.2;
    const initLng = parseFloat(document.getElementById('inputLng').value) || 106.816;
    const initRad = parseInt(document.getElementById('inputRadius').value, 10) || 100;

    map = L.map('geofence-map', { scrollWheelZoom: true }).setView([initLat, initLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    marker = L.marker([initLat, initLng], { draggable: true }).addTo(map);
    circle = L.circle([initLat, initLng], {
        radius: initRad,
        color: '#10b981',
        fillColor: '#10b981',
        fillOpacity: 0.15,
        weight: 2
    }).addTo(map);

    marker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        updateInputs(pos.lat, pos.lng);
        circle.setLatLng([pos.lat, pos.lng]);
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        circle.setLatLng(e.latlng);
        updateInputs(e.latlng.lat, e.latlng.lng);
    });

    requestAnimationFrame(() => map.invalidateSize());
    window.addEventListener('resize', () => map.invalidateSize());
}

function updateInputs(lat, lng) {
    document.getElementById('inputLat').value = lat.toFixed(7);
    document.getElementById('inputLng').value = lng.toFixed(7);
}

function updateCircle() {
    if (!circle) return;
    const r = parseInt(document.getElementById('inputRadius').value, 10) || 100;
    circle.setRadius(r);
}

function getMyLocation() {
    if (!navigator.geolocation) {
        AppAlert.error('Geolocation tidak didukung browser ini.');
        return;
    }
    navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        map.setView([lat, lng], 16);
        marker.setLatLng([lat, lng]);
        circle.setLatLng([lat, lng]);
        updateInputs(lat, lng);
        map.invalidateSize();
    }, () => AppAlert.error('Gagal mendapatkan lokasi. Pastikan izin lokasi diaktifkan.'));
}

// Helper functions for real-time wage simulation
function timeToMinutes(timeStr) {
    if (!timeStr) return 0;
    const parts = timeStr.split(':');
    const hour = parseInt(parts[0], 10) || 0;
    const minute = parseInt(parts[1], 10) || 0;
    return (hour * 60) + minute;
}

function minutesToTime(minutes) {
    const hours = Math.floor(minutes / 60) % 24;
    const mins = Math.floor(minutes % 60);
    return String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
}

function formatRupiah(num) {
    return 'Rp ' + num.toLocaleString('id-ID');
}

function runSimulation() {
    const form = document.getElementById('settingsForm');
    if (!form) return;

    const denda = parseInt(form.querySelector('input[name="potongan_terlambat"]').value, 10) || 0;

    const keys = [
        '08_17', '08_19', '08_20', '08_21', '08_22',
        '09_17', '09_19', '09_20', '09_21', '09_22',
        '10_17', '10_19', '10_20', '10_21', '10_22',
        '13_17', '13_19', '13_20', '13_21', '13_22'
    ];

    keys.forEach(k => {
        const inputEl = form.querySelector('input[name="upah_' + k + '"]');
        if (inputEl) {
            const val = parseInt(inputEl.value, 10) || 0;
            const displayEl = document.getElementById('sim-' + k.replace('_', '-'));
            const displayLateEl = document.getElementById('sim-' + k.replace('_', '-') + '-late');
            
            if (displayEl) displayEl.innerText = formatRupiah(val);
            if (displayLateEl) displayLateEl.innerText = formatRupiah(Math.max(0, val - denda));
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initGeofenceMap();
    
    // Bind real-time simulation
    const form = document.getElementById('settingsForm');
    if (form) {
        form.addEventListener('input', runSimulation);
        form.addEventListener('change', runSimulation);
        runSimulation();
    }
});

async function generateBackup(btn) {
    const month = document.getElementById('manualBackupMonth').value;
    if (!month) {
        AppAlert.info('Silakan pilih bulan terlebih dahulu.', 'Pilih bulan');
        return;
    }

    const orig = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Memproses...';

    const formData = new FormData();
    formData.append('bulan', month);

    try {
        const r = await fetch('<?= base_url('api/generate-backup.php') ?>', { method: 'POST', body: formData });
        const d = await r.json();
        if (d.success) {
            await AppAlert.success(d.message);
            location.reload();
        } else {
            await AppAlert.error(d.message);
            btn.disabled = false;
            btn.innerText = orig;
        }
    } catch (e) {
        await AppAlert.error('Terjadi kesalahan.');
        btn.disabled = false;
        btn.innerText = orig;
    }
}

async function deleteBackup(filename) {
    const ok = await AppAlert.confirm('Yakin ingin menghapus file backup ini?');
    if (!ok) return;

    try {
        const r = await fetch('<?= base_url('api/delete-backup.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ filename: filename }),
        });
        const d = await r.json();
        if (d.success) {
            await AppAlert.success(d.message);
            location.reload();
        } else {
            await AppAlert.error(d.message);
        }
    } catch (e) {
        await AppAlert.error('Terjadi kesalahan.');
    }
}
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
