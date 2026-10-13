<?php
$title = 'Dashboard Admin';
$active = 'admin.dashboard';
$today = current_date();

require_once __DIR__ . '/../../includes/quota.php';
$quotaToday = daily_quota_status($today);

// KPI Stats
$stats = [
    'total'    => db()->query("SELECT COUNT(*) AS n FROM users WHERE role='karyawan'")->fetch()['n'] ?? 0,
    'aktif'    => db()->query("SELECT COUNT(*) AS n FROM users WHERE role='karyawan' AND status='aktif'")->fetch()['n'] ?? 0,
    'pending'  => db()->query("SELECT COUNT(*) AS n FROM users WHERE role='karyawan' AND status='menunggu_approval'")->fetch()['n'] ?? 0,
    'hadir'    => db()->query("SELECT COUNT(*) AS n FROM absensi WHERE tanggal=CURDATE() AND jam_masuk IS NOT NULL")->fetch()['n'] ?? 0,
    'upah_hari'=> db()->query("SELECT COALESCE(SUM(upah_final),0) AS n FROM absensi WHERE tanggal=CURDATE()")->fetch()['n'] ?? 0,
    'unpaid'   => db()->query("SELECT COALESCE(SUM(upah_final),0) AS n FROM absensi WHERE status_bayar='belum_dibayar' AND upah_final>0")->fetch()['n'] ?? 0,
    'paid_bulan'=> db()->query("SELECT COALESCE(SUM(upah_final),0) AS n FROM absensi WHERE status_bayar='sudah_dibayar' AND MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())")->fetch()['n'] ?? 0,
    'leave_pending' => db()->query("SELECT COUNT(*) AS n FROM leave_requests WHERE status='menunggu'")->fetch()['n'] ?? 0,
    'estimasi_hari' => db()->query("SELECT COALESCE(SUM(upah_final),0) AS n FROM absensi WHERE tanggal=CURDATE()")->fetch()['n'] ?? 0,
];
$logbookMode = is_logbook_mode();
$repoUrl = project_repo_url();

// Chart data - last 7 days
$chart_labels = [];
$chart_hadir  = [];
$chart_upah   = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chart_labels[] = date('d/m', strtotime($d));
    $chart_hadir[]  = db()->prepare("SELECT COUNT(*) AS n FROM absensi WHERE tanggal=? AND jam_masuk IS NOT NULL")->execute([$d]) ? db()->prepare("SELECT COUNT(*) AS n FROM absensi WHERE tanggal=? AND jam_masuk IS NOT NULL")->execute([$d]) : 0;
    $stmt = db()->prepare("SELECT COUNT(*) AS n FROM absensi WHERE tanggal=? AND jam_masuk IS NOT NULL");
    $stmt->execute([$d]);
    $chart_hadir[6 - $i] = (int) $stmt->fetch()['n'];
    $stmt2 = db()->prepare("SELECT COALESCE(SUM(upah_final),0) AS n FROM absensi WHERE tanggal=?");
    $stmt2->execute([$d]);
    $chart_upah[6 - $i] = (int) $stmt2->fetch()['n'];
}
// Re-build clean
$chart_labels = [];
$chart_hadir  = [];
$chart_upah   = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chart_labels[] = date('d/m', strtotime($d));
    $s1 = db()->prepare("SELECT COUNT(*) AS n FROM absensi WHERE tanggal=? AND jam_masuk IS NOT NULL");
    $s1->execute([$d]);
    $chart_hadir[] = (int) $s1->fetch()['n'];
    $s2 = db()->prepare("SELECT COALESCE(SUM(upah_final),0) AS n FROM absensi WHERE tanggal=?");
    $s2->execute([$d]);
    $chart_upah[] = (int) $s2->fetch()['n'];
}

// Recent activity
$recent = db()->query("SELECT a.*, u.nama, u.foto_profil FROM absensi a JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 10")->fetchAll();
// Pending approvals
$pendings = db()->query("SELECT * FROM users WHERE role='karyawan' AND status='menunggu_approval' ORDER BY created_at DESC LIMIT 5")->fetchAll();

require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/admin_sidebar.php';
?>
<main class="min-h-screen lg:ml-64" x-data="dashboard()">
    <!-- Top Bar -->
    <div class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200/60 bg-white/80 px-6 py-4 backdrop-blur-xl">
        <div class="flex items-center gap-3">
            <button @click="mobileSidebarOpen = true" class="lg:hidden flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white shadow-md transition hover:bg-slate-800">
                <i data-lucide="menu" class="h-5 w-5"></i>
            </button>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Admin Portal</p>
                <h1 class="text-xl font-extrabold text-slate-900 font-outfit">Dashboard</h1>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="hidden sm:flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-500">
                <i data-lucide="calendar" class="h-4 w-4 text-emerald-500"></i>
                <span><?= date('d M Y') ?></span>
            </div>
            <?php if ($stats['pending'] > 0): ?>
            <a href="<?= route_url('admin.employees') ?>" class="relative flex items-center gap-2 rounded-full bg-amber-500 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600">
                <i data-lucide="user-check" class="h-4 w-4"></i>
                <span><?= (int)$stats['pending'] ?> Menunggu</span>
            </a>
            <?php endif; ?>
            <?php if (!$logbookMode && $stats['leave_pending'] > 0): ?>
            <a href="<?= route_url('admin.leaves') ?>" class="relative flex items-center gap-2 rounded-full bg-indigo-500 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-indigo-500/25 transition hover:bg-indigo-600">
                <i data-lucide="file-check" class="h-4 w-4"></i>
                <span><?= (int)$stats['leave_pending'] ?> Izin</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="p-5 lg:p-8 space-y-6">
        <!-- KPI Cards Row — 4 columns on large screens -->
        <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">

            <!-- Card 1: Total Karyawan -->
            <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase leading-tight tracking-wider text-slate-400 sm:text-xs">Total Karyawan</p>
                        <p class="mt-2 text-3xl font-extrabold leading-none text-slate-900 font-outfit sm:mt-3 sm:text-4xl"><?= (int)$stats['total'] ?></p>
                        <p class="mt-1.5 text-xs text-slate-500"><span class="font-semibold text-emerald-600"><?= (int)$stats['aktif'] ?> aktif</span> · <?= (int)$stats['pending'] ?> pending</p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-lg shadow-blue-500/25 sm:h-12 sm:w-12 sm:rounded-2xl">
                        <i data-lucide="users" class="h-5 w-5 sm:h-6 sm:w-6"></i>
                    </div>
                </div>
                <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-100 sm:mt-4">
                    <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 transition-all duration-700" style="width:<?= $stats['total'] > 0 ? round(($stats['aktif']/$stats['total'])*100) : 0 ?>%"></div>
                </div>
            </div>

            <!-- Card 2: Hadir Hari Ini -->
            <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase leading-tight tracking-wider text-slate-400 sm:text-xs">Hadir Hari Ini</p>
                        <p class="mt-2 text-3xl font-extrabold leading-none text-slate-900 font-outfit sm:mt-3 sm:text-4xl"><?= (int)$stats['hadir'] ?></p>
                        <?php if (!$quotaToday['unlimited']): ?>
                        <p class="mt-1.5 text-[11px] leading-snug text-slate-500 sm:text-xs">slot: <span class="font-semibold text-indigo-600"><?= $quotaToday['used'] ?> / <?= $quotaToday['limit'] ?></span></p>
                        <?php else: ?>
                        <p class="mt-1.5 text-[11px] leading-snug text-slate-500 sm:text-xs">dari <span class="font-semibold"><?= (int)$stats['aktif'] ?></span> aktif</p>
                        <?php endif; ?>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white shadow-lg shadow-emerald-500/25 sm:h-12 sm:w-12 sm:rounded-2xl">
                        <i data-lucide="check-circle" class="h-5 w-5 sm:h-6 sm:w-6"></i>
                    </div>
                </div>
                <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-100 sm:mt-4">
                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-700" style="width:<?= $stats['aktif'] > 0 ? round(($stats['hadir']/$stats['aktif'])*100) : 0 ?>%"></div>
                </div>
            </div>

            <!-- Card 3: Upah Hari Ini -->
            <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase leading-tight tracking-wider text-slate-400 sm:text-xs">Upah Hari Ini</p>
                        <p class="mt-2 break-words text-xl font-extrabold leading-none text-slate-900 font-outfit sm:mt-3 sm:text-2xl"><?= rupiah((int)$stats['upah_hari']) ?></p>
                        <p class="mt-1.5 text-[11px] leading-snug text-slate-500 sm:text-xs">sudah dihitung</p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-500/25 sm:h-12 sm:w-12 sm:rounded-2xl">
                        <i data-lucide="coins" class="h-5 w-5 sm:h-6 sm:w-6"></i>
                    </div>
                </div>
                <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-100 sm:mt-4">
                    <div class="h-full w-3/4 rounded-full bg-gradient-to-r from-amber-400 to-orange-400"></div>
                </div>
            </div>

            <!-- Card 4: Persetujuan Karyawan Baru -->
            <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase leading-tight tracking-wider text-slate-400 sm:text-xs">Menunggu Approval</p>
                        <p class="mt-2 text-3xl font-extrabold leading-none text-slate-900 font-outfit sm:mt-3 sm:text-4xl"><?= (int)$stats['pending'] ?></p>
                        <p class="mt-1.5 text-xs text-slate-500">pendaftar baru</p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white shadow-lg shadow-purple-500/25 sm:h-12 sm:w-12 sm:rounded-2xl">
                        <i data-lucide="user-check" class="h-5 w-5 sm:h-6 sm:w-6"></i>
                    </div>
                </div>
                <a href="<?= route_url('admin.employees') ?>" class="mt-3 flex items-center gap-1 text-[11px] font-bold text-purple-600 transition hover:text-purple-800 sm:mt-4 sm:text-xs">
                    <span>Kelola Karyawan</span>
                    <i data-lucide="arrow-right" class="h-3 w-3"></i>
                </a>
            </div>
        </div>

        <?php if (!$logbookMode): ?>
        <!-- Second Row: Quick Stats (Terbayar + Avg + Suspended) -->
        <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-3">

            <!-- Estimasi Hari Ini (wide) -->
            <div class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-blue-50 p-4 shadow-sm sm:p-5 flex items-center justify-between gap-4">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-indigo-500 sm:text-xs">Estimasi Biaya Tenaga Kerja</p>
                    <p class="mt-1.5 text-2xl font-extrabold text-indigo-900 font-outfit"><?= rupiah((int)$stats['estimasi_hari']) ?></p>
                    <p class="mt-1 text-[11px] text-indigo-500">Berdasarkan upah yang sudah dihitung hari ini</p>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                    <i data-lucide="bar-chart-2" class="h-6 w-6"></i>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                    <i data-lucide="trending-up" class="h-5 w-5"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-400">Terbayar Bulan Ini</p>
                    <p class="text-lg font-extrabold text-slate-900"><?= rupiah((int)$stats['paid_bulan']) ?></p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-600">
                    <i data-lucide="clock" class="h-5 w-5"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-400">Rata-rata Hadir / Hari (30hr)</p>
                    <?php
                    $avgStmt = db()->query("SELECT AVG(cnt) as avg FROM (SELECT DATE(tanggal) as d, COUNT(*) as cnt FROM absensi WHERE jam_masuk IS NOT NULL AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(tanggal)) t");
                    $avg = round($avgStmt->fetch()['avg'] ?? 0, 1);
                    ?>
                    <p class="text-lg font-extrabold text-slate-900"><?= $avg ?> orang</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Third Row: Chart + Pending Approvals -->
        <div class="grid gap-5 xl:grid-cols-3">

            <!-- Chart Tren 7 Hari -->
            <div class="xl:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Tren Kehadiran & Upah</h2>
                        <p class="text-xs text-slate-400 mt-0.5">7 hari terakhir</p>
                    </div>
                    <div class="flex gap-4 text-xs text-slate-500">
                        <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Hadir</span>
                        <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-400"></span>Upah (rb)</span>
                    </div>
                </div>
                <canvas id="trendChart" height="80"></canvas>
            </div>

            <!-- Pending Approvals -->
            <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-yellow-50 p-5 shadow-sm sm:p-6 flex flex-col">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-bold text-amber-900">Perlu Approval</h2>
                    <span class="rounded-full bg-amber-500 px-3 py-0.5 text-xs font-bold text-white"><?= (int)$stats['pending'] ?></span>
                </div>
                <?php if ($pendings): ?>
                <div class="space-y-3 flex-1">
                    <?php foreach ($pendings as $p): ?>
                    <div class="flex items-center justify-between gap-2 rounded-xl bg-white/80 p-3 shadow-sm">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 font-bold text-amber-700 text-sm">
                                <?= strtoupper(substr($p['nama'],0,1)) ?>
                            </div>
                            <div class="overflow-hidden">
                                <p class="truncate text-sm font-semibold text-slate-800"><?= e($p['nama']) ?></p>
                                <p class="text-xs text-slate-500"><?= e($p['no_hp']) ?></p>
                            </div>
                        </div>
                        <button
                            onclick="approveUser(<?= (int)$p['id'] ?>, this)"
                            class="shrink-0 rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white shadow transition hover:bg-emerald-600">
                            Setujui
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= route_url('admin.employees') ?>" class="mt-4 flex items-center justify-center gap-1 text-xs font-semibold text-amber-700 hover:text-amber-900 transition">
                    <span>Lihat semua karyawan</span>
                    <i data-lucide="arrow-right" class="h-3 w-3"></i>
                </a>
                <?php else: ?>
                <div class="flex flex-1 flex-col items-center justify-center py-6 text-center">
                    <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100">
                        <i data-lucide="check-circle-2" class="h-7 w-7 text-emerald-500"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">Semua beres!</p>
                    <p class="text-xs text-slate-400 mt-1">Tidak ada karyawan yang menunggu persetujuan</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fourth Row: Live Activity Feed -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-2.5">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    </span>
                    <h2 class="text-base font-bold text-slate-900">Aktivitas Terbaru</h2>
                </div>
                <a href="<?= route_url('admin.attendance') ?>" class="flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-50 hover:text-slate-900 transition">
                    <span>Lihat semua</span>
                    <i data-lucide="arrow-right" class="h-3 w-3"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-xs uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3 font-semibold sm:px-6">Karyawan</th>
                            <th class="px-4 py-3 font-semibold hidden sm:table-cell">Tanggal</th>
                            <th class="px-4 py-3 font-semibold">Masuk</th>
                            <th class="px-4 py-3 font-semibold hidden md:table-cell">Keluar</th>
                            <th class="px-4 py-3 font-semibold hidden sm:table-cell">Status</th>
                            <th class="px-4 py-3 font-semibold">Upah</th>
                            <?php if (!$logbookMode): ?>
                            <th class="px-4 py-3 font-semibold">Bayar</th>
                            <?php endif; ?>
                            <th class="px-4 py-3 font-semibold hidden md:table-cell">Foto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($recent as $row): ?>
                        <tr class="group transition-colors hover:bg-slate-50/70">
                            <td class="px-5 py-3.5 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-slate-200 to-slate-300 text-xs font-bold text-slate-600">
                                        <?= strtoupper(substr($row['nama'],0,1)) ?>
                                    </div>
                                    <span class="font-semibold text-slate-800"><?= e($row['nama']) ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-slate-500 hidden sm:table-cell"><?= date('d M', strtotime($row['tanggal'])) ?></td>
                            <td class="px-4 py-3.5">
                                <span class="font-mono text-slate-700"><?= e($row['jam_masuk'] ?? '-') ?></span>
                                <?php if ($row['lat_masuk'] && $row['lng_masuk']): ?>
                                <a href="https://maps.google.com/?q=<?= e($row['lat_masuk']) ?>,<?= e($row['lng_masuk']) ?>" target="_blank" class="ml-1 inline-flex text-blue-400 hover:text-blue-600" title="Lihat lokasi masuk">
                                    <i data-lucide="map-pin" class="h-3 w-3"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 hidden md:table-cell">
                                <span class="font-mono text-slate-700"><?= e($row['jam_keluar'] ?? '-') ?></span>
                                <?php if ($row['lat_keluar'] && $row['lng_keluar']): ?>
                                <a href="https://maps.google.com/?q=<?= e($row['lat_keluar']) ?>,<?= e($row['lng_keluar']) ?>" target="_blank" class="ml-1 inline-flex text-blue-400 hover:text-blue-600">
                                    <i data-lucide="map-pin" class="h-3 w-3"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 hidden sm:table-cell">
                                <?php
                                $statusColor = match($row['status'] ?? '') {
                                    'selesai' => 'bg-emerald-100 text-emerald-700',
                                    'bekerja' => 'bg-blue-100 text-blue-700',
                                    'terlambat' => 'bg-amber-100 text-amber-700',
                                    'alpa'    => 'bg-rose-100 text-rose-700',
                                    default   => 'bg-slate-100 text-slate-600',
                                };
                                ?>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $statusColor ?>"><?= e($row['status'] ?? '-') ?></span>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-slate-800"><?= rupiah((int)$row['upah_final']) ?></td>
                            <?php if (!$logbookMode): ?>
                            <td class="px-4 py-3.5">
                                <?php if ($row['status_bayar'] === 'sudah_dibayar'): ?>
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-700">Lunas</span>
                                <?php else: ?>
                                    <button onclick="markPaid(<?= (int)$row['id'] ?>, this)" class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-600 hover:bg-rose-500 hover:text-white transition">Belum</button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td class="px-4 py-3.5 hidden md:table-cell">
                                <?php if ($row['foto_masuk'] ?? null): ?>
                                <button onclick="showPhoto('<?= base_url(e($row['foto_masuk'])) ?>')" class="rounded-lg bg-slate-100 p-1.5 hover:bg-slate-200 transition">
                                    <i data-lucide="image" class="h-4 w-4 text-slate-500"></i>
                                </button>
                                <?php else: ?>
                                <span class="text-slate-300">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$recent): ?>
                        <tr><td colspan="<?= $logbookMode ? 7 : 8 ?>" class="px-6 py-10 text-center text-sm text-slate-400">Belum ada aktivitas hari ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<!-- Photo Modal -->
<div id="photoModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/70 backdrop-blur-sm" onclick="this.classList.add('hidden'); this.classList.remove('flex')">
    <div class="relative max-h-[90vh] max-w-sm w-full mx-4" onclick="event.stopPropagation()">
        <img id="photoModalImg" src="" class="w-full rounded-2xl shadow-2xl object-cover">
        <button onclick="document.getElementById('photoModal').classList.add('hidden'); document.getElementById('photoModal').classList.remove('flex')" class="absolute -top-4 -right-4 flex h-10 w-10 items-center justify-center rounded-full bg-white text-slate-800 shadow-xl hover:bg-slate-100">
            <i data-lucide="x" class="h-5 w-5"></i>
        </button>
    </div>
</div>

<script>
function showPhoto(url) {
    document.getElementById('photoModalImg').src = url;
    const m = document.getElementById('photoModal');
    m.classList.remove('hidden');
    m.classList.add('flex');
}

async function approveUser(id, btn) {
    btn.disabled = true;
    btn.textContent = '...';
    try {
        const r = await fetch('<?= base_url('api/employee-status.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: id, action: 'approve' }),
        });
        const d = await r.json();
        if (d.success) {
            btn.closest('.flex.items-center.justify-between')?.remove();
        } else {
            btn.disabled = false;
            btn.textContent = 'Setujui';
            await AppAlert.error(d.message);
        }
    } catch (e) {
        btn.disabled = false;
        btn.textContent = 'Setujui';
        await AppAlert.error('Terjadi kesalahan.');
    }
}

async function markPaid(id, btn) {
    const ok = await AppAlert.confirm('Tandai sebagai sudah dibayar?');
    if (!ok) return;
    btn.disabled = true;
    try {
        const r = await fetch('<?= base_url('api/update-payment.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ absensi_id: id, status: 'lunas' }),
        });
        const d = await r.json();
        if (d.success) {
            btn.outerHTML = '<span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-700">Lunas</span>';
        } else {
            btn.disabled = false;
            await AppAlert.error(d.message);
        }
    } catch (e) {
        btn.disabled = false;
        await AppAlert.error('Terjadi kesalahan.');
    }
}

// Chart
const ctx = document.getElementById('trendChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [
            {
                label: 'Hadir',
                data: <?= json_encode($chart_hadir) ?>,
                backgroundColor: 'rgba(16,185,129,0.15)',
                borderColor: '#10b981',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
                type: 'bar',
                yAxisID: 'y',
            },
            {
                label: 'Upah (rb)',
                data: <?= json_encode(array_map(fn($v) => round($v/1000, 1), $chart_upah)) ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245,158,11,0.1)',
                borderWidth: 2,
                type: 'line',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#f59e0b',
                pointRadius: 4,
                yAxisID: 'y2',
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: false }, tooltip: { callbacks: {
            label: (ctx) => {
                if (ctx.datasetIndex === 1) return ' Rp ' + (ctx.parsed.y * 1000).toLocaleString('id-ID');
                return ' ' + ctx.parsed.y + ' orang';
            }
        }}},
        scales: {
            x: { grid: { display: false }, border: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
            y: { position: 'left', grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 }, stepSize: 1 } },
            y2: { position: 'right', grid: { display: false }, border: { display: false }, ticks: { color: '#f59e0b', font: { size: 11 }, callback: v => 'Rp' + v + 'rb' } }
        }
    }
});

function dashboard() { return {}; }
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
