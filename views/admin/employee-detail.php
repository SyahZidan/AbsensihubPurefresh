<?php
$title = 'Detail Karyawan';
$from = $_GET['from'] ?? '';
$active = ($from === 'payment') ? 'admin.payment' : 'admin.employees';

$empId = (int)($_GET['id'] ?? 0);

// Ambil data karyawan
$stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND role = 'karyawan' LIMIT 1");
$stmt->execute([$empId]);
$employee = $stmt->fetch();

if (!$employee) {
    flash('error', 'Karyawan tidak ditemukan.');
    redirect(route_url('admin.employees'));
}

$bulan = $_GET['bulan'] ?? date('Y-m'); // Default ke bulan ini agar sinkron dengan Pembayaran

$paramsStats = [$empId];
$whereStats = "WHERE user_id = ?";
if ($bulan !== '') {
    $whereStats .= " AND DATE_FORMAT(tanggal,'%Y-%m') = ?";
    $paramsStats[] = $bulan;
}

// Ambil statistik upah dan kehadiran
$statsStmt = db()->prepare("SELECT 
    COUNT(*) as total_hadir,
    COALESCE(SUM(upah_final), 0) as total_upah,
    COALESCE(SUM(CASE WHEN status_bayar = 'belum_dibayar' THEN upah_final ELSE 0 END), 0) as upah_unpaid,
    COALESCE(SUM(CASE WHEN status_bayar = 'sudah_dibayar' THEN upah_final ELSE 0 END), 0) as upah_paid
    FROM absensi {$whereStats}");
$statsStmt->execute($paramsStats);
$stats = $statsStmt->fetch();

$paramsLog = [$empId];
$whereLog = "WHERE user_id = ?";
if ($bulan !== '') {
    $whereLog .= " AND DATE_FORMAT(tanggal,'%Y-%m') = ?";
    $paramsLog[] = $bulan;
}

// Ambil log kehadiran
$logStmt = db()->prepare("SELECT * FROM absensi {$whereLog} ORDER BY tanggal DESC");
$logStmt->execute($paramsLog);
$attendances = $logStmt->fetchAll();

// Setup Avatar Gradient
$avatarGradients = ['from-blue-500 to-indigo-600', 'from-emerald-500 to-teal-500', 'from-purple-500 to-pink-500', 'from-amber-500 to-orange-500', 'from-rose-500 to-pink-600'];
$avatarColor = $avatarGradients[crc32($employee['nama']) % count($avatarGradients)];

$backUrl = route_url('admin.employees');
if ($from === 'payment') {
    $backUrl = route_url('admin.payment');
    if ($bulan !== '') {
        $backUrl .= '&bulan=' . urlencode($bulan);
    }
}


require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/admin_sidebar.php';
?>

<!-- Leaflet CSS & JS for Interactive Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<main class="min-h-screen lg:ml-64 bg-slate-50 font-sans">
    <!-- Top Bar -->
    <div class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200/60 bg-white/80 px-6 py-4 backdrop-blur-xl">
        <div class="flex items-center gap-3">
            <button @click="mobileSidebarOpen = true" class="lg:hidden flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-white shadow-md transition hover:bg-slate-800">
                <i data-lucide="menu" class="h-4 w-4"></i>
            </button>
            <a href="<?= $backUrl ?>" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
            </a>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Admin Portal</p>
                <h1 class="text-xl font-extrabold text-slate-900 font-outfit">Detail Karyawan</h1>
            </div>
        </div>
    </div>

    <div class="p-5 lg:p-8 space-y-6">
        <!-- Profile Card -->
        <div class="rounded-3xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
            <!-- Background Glow -->
            <div class="absolute right-0 top-0 -mr-16 -mt-16 h-36 w-36 rounded-full bg-emerald-500/5 blur-3xl"></div>

            <div class="flex items-center gap-5">
                <?php 
                $showProfil = false;
                if (!empty($employee['foto_profil'])) {
                    $fileProfil = dirname(__DIR__, 2) . $employee['foto_profil'];
                    if (file_exists($fileProfil) && is_file($fileProfil)) {
                        $showProfil = true;
                    }
                }
                ?>
                <?php if ($showProfil): ?>
                    <img src="<?= base_url($employee['foto_profil']) ?>" alt="Profil" class="h-20 w-20 rounded-2xl object-cover shadow border border-slate-200/80">
                <?php else: ?>
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br <?= $avatarColor ?> text-3xl font-extrabold text-white shadow-md">
                        <?= strtoupper(substr($employee['nama'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900 font-outfit flex items-center gap-2.5">
                        <?= e($employee['nama']) ?>
                    </h2>
                    <p class="text-sm font-semibold text-slate-400 mt-0.5 flex items-center gap-1.5">
                        <i data-lucide="phone" class="h-4 w-4 text-slate-400"></i> <?= e($employee['no_hp']) ?>
                    </p>
                    <div class="mt-2.5 flex flex-wrap gap-2">
                        <!-- Status Badge -->
                        <?php
                        $statusColor = match($employee['status']) {
                            'aktif'             => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'menunggu_approval' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'nonaktif'          => 'bg-rose-100 text-rose-700 border-rose-200',
                            default             => 'bg-slate-100 text-slate-600 border-slate-200',
                        };
                        $statusLabel = match($employee['status']) {
                            'aktif'             => 'Aktif',
                            'menunggu_approval' => 'Menunggu Approval',
                            'nonaktif'          => 'Suspended',
                            default             => ucfirst($employee['status']),
                        };
                        ?>
                        <span class="rounded-full border px-3 py-1 text-xs font-bold <?= $statusColor ?>"><?= $statusLabel ?></span>
                        <span class="rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Bergabung: <?= format_tanggal_hari($employee['created_at']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Administrative Actions -->
            <div class="flex flex-col sm:flex-row gap-3 min-w-[200px]" id="action-buttons-container">
                <?php if ($employee['status'] === 'menunggu_approval'): ?>
                    <button onclick="changeStatus(<?= $employee['id'] ?>, 'approve', this)" class="flex-1 px-4 py-3 rounded-xl bg-emerald-500 font-bold text-white shadow-lg shadow-emerald-500/20 hover:bg-emerald-600 transition flex items-center justify-center gap-2">
                        <i data-lucide="user-check" class="h-4 w-4"></i> Setujui
                    </button>
                    <button onclick="changeStatus(<?= $employee['id'] ?>, 'suspend', this)" class="flex-1 px-4 py-3 rounded-xl border border-rose-200 bg-rose-50 font-bold text-rose-600 hover:bg-rose-500 hover:text-white transition flex items-center justify-center gap-2">
                        <i data-lucide="ban" class="h-4 w-4"></i> Tolak
                    </button>
                <?php elseif ($employee['status'] === 'aktif'): ?>
                    <button onclick="changeStatus(<?= $employee['id'] ?>, 'suspend', this)" class="flex-1 px-4 py-3 rounded-xl border border-rose-200 bg-rose-50 font-bold text-rose-600 hover:bg-rose-500 hover:text-white transition flex items-center justify-center gap-2">
                        <i data-lucide="ban" class="h-4 w-4"></i> Suspend Akun
                    </button>
                <?php elseif ($employee['status'] === 'nonaktif'): ?>
                    <button onclick="changeStatus(<?= $employee['id'] ?>, 'activate', this)" class="flex-1 px-4 py-3 rounded-xl bg-emerald-500 font-bold text-white shadow-lg shadow-emerald-500/20 hover:bg-emerald-600 transition flex items-center justify-center gap-2">
                        <i data-lucide="user-check" class="h-4 w-4"></i> Aktifkan Kembali
                    </button>
                <?php endif; ?>
                <button onclick="resetPassword(<?= $employee['id'] ?>, this)" class="px-4 py-3 rounded-xl border border-indigo-200 bg-indigo-50 font-bold text-indigo-700 hover:bg-indigo-600 hover:text-white transition flex items-center justify-center gap-2">
                    <i data-lucide="key-round" class="h-4 w-4"></i> Reset Password
                </button>
                <button onclick="deleteEmployee(<?= $employee['id'] ?>, this)" class="px-4 py-3 rounded-xl border border-rose-200 bg-rose-50 font-bold text-rose-600 hover:bg-rose-600 hover:text-white transition flex items-center justify-center gap-2">
                    <i data-lucide="trash-2" class="h-4 w-4"></i> Hapus Karyawan
                </button>
            </div>
        </div>

        <!-- Data Pribadi & KTP -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Data Pribadi -->
            <div class="rounded-3xl border border-slate-200/60 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                        <i data-lucide="user-round" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-400">Data Pribadi</h3>
                        <p class="text-xs text-slate-300 font-medium">Identitas Karyawan</p>
                    </div>
                </div>
                <div class="border-t border-slate-100 pt-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tanggal Lahir</span>
                        <?php if (!empty($employee['tanggal_lahir'])): ?>
                        <span class="font-bold text-slate-900"><?= date('d M Y', strtotime($employee['tanggal_lahir'])) ?></span>
                        <?php else: ?>
                        <span class="text-slate-400 italic text-xs">Belum diisi</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($employee['tanggal_lahir'])): ?>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Usia</span>
                        <?php
                            $dob = new DateTime($employee['tanggal_lahir']);
                            $now = new DateTime();
                            $usia = $dob->diff($now)->y;
                        ?>
                        <span class="font-bold text-slate-900"><?= $usia ?> tahun</span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Bergabung</span>
                        <span class="font-bold text-slate-900"><?= format_tanggal_hari($employee['created_at']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Foto KTP -->
            <div class="rounded-3xl border border-slate-200/60 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <i data-lucide="id-card" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-400">Foto KTP</h3>
                        <p class="text-xs text-slate-300 font-medium">Kartu Tanda Penduduk</p>
                    </div>
                </div>
                <div class="border-t border-slate-100 pt-3">
                    <?php 
                    $showKtp = false;
                    if (!empty($employee['foto_ktp'])) {
                        $fileKtp = dirname(__DIR__, 2) . $employee['foto_ktp'];
                        if (file_exists($fileKtp) && is_file($fileKtp)) {
                            $showKtp = true;
                        }
                    }
                    ?>
                    <?php if ($showKtp): ?>
                    <a href="<?= base_url(e($employee['foto_ktp'])) ?>" target="_blank" class="block group relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 hover:border-indigo-300 transition aspect-[8/5] w-full max-w-md mx-auto">
                        <img src="<?= base_url(e($employee['foto_ktp'])) ?>" alt="Foto KTP <?= e($employee['nama']) ?>" class="w-full h-full object-cover group-hover:object-contain transition-all duration-300">
                        <div class="absolute inset-0 bg-indigo-900/0 group-hover:bg-indigo-900/10 transition flex items-center justify-center pointer-events-none">
                            <span class="opacity-0 group-hover:opacity-100 transition bg-white/95 backdrop-blur-sm rounded-xl px-3 py-1.5 text-xs font-bold text-indigo-700 shadow flex items-center gap-1.5">
                                <i data-lucide="external-link" class="h-3.5 w-3.5"></i> Buka Full
                            </span>
                        </div>
                    </a>
                    <p class="mt-2 text-center text-xs text-slate-400">Klik untuk melihat ukuran penuh</p>
                    <?php else: ?>
                        <?php if (!empty($employee['foto_ktp'])): ?>
                        <div class="flex flex-col items-center justify-center py-8 text-center rounded-2xl border border-rose-100 bg-rose-50/30 w-full max-w-md mx-auto">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-500 mb-3 border border-rose-100">
                                <i data-lucide="image-off" class="h-7 w-7"></i>
                            </div>
                            <p class="text-sm font-semibold text-rose-700 font-outfit">Berkas KTP Rusak / Hilang</p>
                            <p class="text-xs text-rose-400 mt-1 max-w-[240px]">File KTP tidak ditemukan di server. Minta karyawan untuk mengunggah ulang KTP.</p>
                        </div>
                        <?php else: ?>
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-300 mb-3">
                                <i data-lucide="id-card" class="h-7 w-7"></i>
                            </div>
                            <p class="text-sm font-semibold text-slate-400">KTP Belum Diunggah</p>
                            <p class="text-xs text-slate-300 mt-0.5">Karyawan belum menambahkan foto KTP</p>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payment Info & Statistics Grid -->
        <div class="grid gap-6 md:grid-cols-3">
            <!-- Payment Info -->
            <div class="rounded-3xl border border-slate-200/60 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <i data-lucide="wallet" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-400">Informasi Pembayaran</h3>
                        <p class="text-xs text-slate-300 font-medium">Bank / E-Wallet Karyawan</p>
                    </div>
                </div>
                <div class="border-t border-slate-100 pt-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Nama Bank / E-Wallet</span>
                        <span class="font-bold text-slate-900"><?= e($employee['bank_name'] ?: '-') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Nomor Rekening / No. HP</span>
                        <span class="font-bold text-slate-900 font-mono"><?= e($employee['norek'] ?: '-') ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Hari Hadir -->
            <div class="rounded-3xl border border-slate-200/60 bg-white p-5 shadow-sm flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 shadow-sm">
                    <i data-lucide="calendar" class="h-6 w-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-slate-400">Total Kehadiran</h3>
                    <p class="text-2xl font-black text-slate-900 font-outfit mt-0.5"><?= (int)$stats['total_hadir'] ?> <span class="text-sm font-semibold text-slate-400">Hari</span></p>
                </div>
            </div>

            <!-- Stats Upah & Gaji -->
            <div class="rounded-3xl border border-slate-200/60 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <i data-lucide="banknote" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-400">Akumulasi Upah</h3>
                        <p class="text-xs text-slate-300 font-medium">Perhitungan Gaji Keseluruhan</p>
                    </div>
                </div>
                <div class="border-t border-slate-100 pt-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Upah Sudah Dibayar</span>
                        <span class="font-bold text-emerald-600"><?= rupiah((int)$stats['upah_paid']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Upah Belum Dibayar</span>
                        <span class="font-bold text-rose-600"><?= rupiah((int)$stats['upah_unpaid']) ?></span>
                    </div>
                    <div class="flex justify-between border-t border-slate-100 pt-2 font-bold text-slate-900">
                        <span>Total Akumulasi</span>
                        <span><?= rupiah((int)$stats['total_upah']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Work History Log -->
        <div class="rounded-3xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200/60 bg-slate-50/50 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-extrabold text-slate-900 font-outfit">Riwayat Absensi & Kehadiran</h3>
                <div class="flex items-center gap-3">
                    <form method="get" class="flex items-center gap-2">
                        <input type="hidden" name="page" value="admin.employee-detail">
                        <input type="hidden" name="id" value="<?= $empId ?>">
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 shadow-sm text-xs">
                            <i data-lucide="calendar" class="h-3.5 w-3.5 text-slate-400"></i>
                            <input type="month" name="bulan" value="<?= e($bulan) ?>" onchange="this.form.submit()" class="bg-transparent outline-none text-slate-700 font-semibold cursor-pointer">
                        </div>
                        <?php if ($bulan !== ''): ?>
                        <a href="?id=<?= $empId ?>&bulan=" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 transition shadow-sm font-semibold">Semua Bulan</a>
                        <?php endif; ?>
                    </form>
                    <span class="rounded-full bg-slate-100 text-slate-600 px-3 py-1 text-xs font-bold"><?= count($attendances) ?> Kehadiran</span>
                </div>
            </div>

            <?php if (empty($attendances)): ?>
                <div class="p-12 text-center text-slate-400 space-y-3">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-300 mx-auto">
                        <i data-lucide="clipboard-x" class="h-8 w-8"></i>
                    </div>
                    <p class="font-semibold text-slate-500">Belum ada riwayat kehadiran tercatat.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/50 text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4">Tanggal</th>
                                <th class="px-6 py-4">Jam Masuk</th>
                                <th class="px-6 py-4">Jam Keluar</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Upah Final</th>
                                <th class="px-6 py-4">Pembayaran</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($attendances as $row): ?>
                            <?php
                            $rowStatusColor = match($row['status']) {
                                'selesai'   => 'bg-emerald-100 text-emerald-700',
                                'bekerja'   => 'bg-blue-100 text-blue-700',
                                'terlambat' => 'bg-amber-100 text-amber-700',
                                'alpa'      => 'bg-rose-100 text-rose-700',
                                default     => 'bg-slate-100 text-slate-600',
                            };
                            $rowStatusLabel = match($row['status']) {
                                'selesai'   => 'Selesai',
                                'bekerja'   => 'Bekerja',
                                'terlambat' => 'Terlambat',
                                'alpa'      => 'Alpa',
                                default     => ucfirst($row['status']),
                            };
                            $payStatusColor = $row['status_bayar'] === 'sudah_dibayar' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700';
                            $payStatusLabel = $row['status_bayar'] === 'sudah_dibayar' ? 'Lunas' : 'Belum Dibayar';
                            ?>
                            <tr class="hover:bg-slate-50/40 transition">
                                <td class="px-6 py-4 font-semibold text-slate-900"><?= format_tanggal_hari($row['tanggal']) ?></td>
                                <td class="px-6 py-4 font-mono"><?= $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-' ?></td>
                                <td class="px-6 py-4 font-mono">
                                    <?= $row['jam_keluar'] ? date('H:i', strtotime($row['jam_keluar'])) : '-' ?>
                                    <?php if (!empty($row['keterangan_keluar_awal'])): ?>
                                        <?php
                                        $isAuto = str_contains($row['keterangan_keluar_awal'], 'Auto Checkout') || str_contains($row['keterangan_keluar_awal'], 'Lupa Keluar');
                                        $btnLabel = $isAuto ? 'Info · lihat' : 'Keluar awal · lihat';
                                        $modalTitle = $isAuto ? 'Sistem Lupa Keluar' : 'Keluar Lebih Awal';
                                        ?>
                                        <button type="button" onclick="showEarlyCheckout(<?= htmlspecialchars(json_encode([
                                            'nama' => $employee['nama'],
                                            'jam' => $row['jam_keluar'] ? date('H:i', strtotime($row['jam_keluar'])) : '',
                                            'keterangan' => $row['keterangan_keluar_awal'],
                                            'title' => $modalTitle,
                                            'bukti' => !empty($row['bukti_keluar_awal']) ? base_url($row['bukti_keluar_awal']) : '',
                                        ]), ENT_QUOTES, 'UTF-8') ?>)"
                                            class="mt-1 block text-[10px] font-bold <?= $isAuto ? 'text-blue-500 hover:text-blue-700' : 'text-amber-600 hover:text-amber-800' ?> hover:underline text-left"><?= $btnLabel ?></button>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold <?= $rowStatusColor ?>"><?= $rowStatusLabel ?></span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-800"><?= rupiah((int)$row['upah_final']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold <?= $payStatusColor ?>"><?= $payStatusLabel ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick='showLogDetail(<?= json_encode($row) ?>)' class="inline-flex h-8 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 hover:bg-slate-50 shadow-sm transition">
                                        Lihat Detail
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Glassmorphism Detail Modal -->
<div id="detailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm p-4 hidden" onclick="closeModal(event)">
    <div class="relative w-full max-w-2xl transform rounded-3xl border border-white/10 bg-white/95 p-6 shadow-2xl backdrop-blur-xl transition-all flex flex-col max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-500">Rincian Shift Kehadiran</p>
                <h3 id="modalDate" class="text-lg font-extrabold text-slate-950 font-outfit">Detail Absensi</h3>
            </div>
            <button onclick="closeModal(null)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="space-y-6 py-5">
            <!-- Selfie Double Check -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Foto Bukti Selfie</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-2xl bg-slate-50 p-2.5 border border-slate-100 text-center">
                        <p class="text-xs font-bold text-slate-500 mb-2">Selfie Masuk</p>
                        <div class="relative aspect-video rounded-xl bg-slate-200 overflow-hidden border border-slate-100">
                            <img id="selfieMasuk" src="" alt="Selfie Masuk" class="h-full w-full object-cover hidden">
                            <div id="selfieMasukPlaceholder" class="absolute inset-0 flex items-center justify-center text-slate-400 text-xs">Tidak ada foto</div>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-2.5 border border-slate-100 text-center">
                        <p class="text-xs font-bold text-slate-500 mb-2">Selfie Keluar</p>
                        <div class="relative aspect-video rounded-xl bg-slate-200 overflow-hidden border border-slate-100">
                            <img id="selfieKeluar" src="" alt="Selfie Keluar" class="h-full w-full object-cover hidden">
                            <div id="selfieKeluarPlaceholder" class="absolute inset-0 flex items-center justify-center text-slate-400 text-xs">Tidak ada foto</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keluar Awal Info -->
            <div id="detailKeluarAwalSection" class="hidden rounded-2xl border border-amber-200 bg-amber-50 p-4 space-y-2">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-700 font-outfit">Keterangan Keluar Lebih Awal</p>
                <p id="detailKeluarAwalKeterangan" class="text-sm text-slate-800 leading-normal"></p>
                <div id="detailKeluarAwalBuktiWrap" class="mt-2 hidden">
                    <p class="text-xs font-bold text-slate-400 mb-2">Foto Bukti Keluar Awal</p>
                    <img id="detailKeluarAwalBuktiImg" src="" class="max-w-xs rounded-xl border border-slate-200" alt="Bukti Keluar Awal">
                </div>
            </div>

            <!-- Wage Breakdown & Details -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Rincian Upah & Waktu Kerja</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Jam Masuk:</span>
                            <span id="detailMasuk" class="font-mono font-bold text-slate-900">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Jam Keluar:</span>
                            <span id="detailKeluar" class="font-mono font-bold text-slate-900">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Skema Jam:</span>
                            <span id="detailSkema" class="font-bold text-slate-900">-</span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Insentif Lembur:</span>
                            <span id="detailLembur" class="font-bold text-emerald-600">Rp0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Potongan Lambat:</span>
                            <span id="detailPotongan" class="font-bold text-rose-600">Rp0</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200/80 pt-1.5 font-bold">
                            <span class="text-slate-800">Upah Bersih:</span>
                            <span id="detailUpahFinal" class="text-slate-950 text-base font-extrabold">Rp0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map Location GPS Tracking -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Titik Lokasi Absensi</h4>
                <div id="map" class="h-60 w-full rounded-2xl border border-slate-100 shadow-inner z-10"></div>
            </div>
        </div>
    </div>
</div>

<script>
async function changeStatus(id, action, btn) {
    const label = action === 'approve' ? 'menyetujui' : action === 'suspend' ? 'menangguhkan' : 'mengaktifkan';
    const ok = await AppAlert.confirm(`Yakin ${label} karyawan ini?`);
    if (!ok) return;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '...';

    try {
        const r = await fetch('<?= base_url('api/employee-status.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: id, action }),
        });
        const d = await r.json();
        if (d.success) {
            location.reload();
        } else {
            btn.disabled = false;
            btn.innerHTML = orig;
            await AppAlert.error(d.message || 'Gagal');
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = orig;
        await AppAlert.error('Terjadi kesalahan.');
    }
}

async function resetPassword(id, btn) {
    const ok = await AppAlert.confirm('Reset password karyawan ini? Password sementara akan ditampilkan.');
    if (!ok) return;
    const orig = btn.innerHTML;
    btn.disabled = true;
    const fd = new FormData();
    fd.append('user_id', id);
    try {
        const r = await fetch('<?= base_url('api/admin-reset-password.php') ?>', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Password direset',
                html: '<p class="text-slate-600">' + (d.message || '').replace(/</g, '&lt;') + '</p>',
                confirmButtonText: 'OK',
                confirmButtonColor: '#10b981',
            });
        } else {
            await AppAlert.error(d.message);
        }
    } catch (e) {
        await AppAlert.error('Terjadi kesalahan.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

async function deleteEmployee(id, btn) {
    const ok1 = await AppAlert.confirmDanger(
        'Yakin ingin menghapus karyawan ini? Semua riwayat absensi juga akan dihapus.',
        { confirmText: 'Lanjut' }
    );
    if (!ok1) return;
    const ok2 = await AppAlert.confirmDanger(
        'TINDAKAN INI TIDAK BISA DIBATALKAN. Anda benar-benar yakin?',
        { confirmText: 'Ya, hapus permanen' }
    );
    if (!ok2) return;

    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '...';

    try {
        const r = await fetch('<?= base_url('api/employee-status.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: id, action: 'delete' }),
        });
        const d = await r.json();
        if (d.success) {
            await AppAlert.success(d.message);
            location.href = '<?= route_url('admin.employees') ?>';
        } else {
            btn.disabled = false;
            btn.innerHTML = orig;
            await AppAlert.error(d.message || 'Gagal');
            if (d.message && d.message.indexOf('Gagal menghapus') !== -1) {
                location.reload();
            }
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = orig;
        await AppAlert.error('Terjadi kesalahan.');
    }
}

// Modal Detail Logic
let leafletMap = null;
let mapMarkerMasuk = null;
let mapMarkerKeluar = null;

function showLogDetail(data) {
    document.getElementById('modalDate').innerText = formatDate(data.tanggal);
    
    // Selfie
    const imgMasuk = document.getElementById('selfieMasuk');
    const plhMasuk = document.getElementById('selfieMasukPlaceholder');
    if (data.foto_masuk) {
        imgMasuk.src = '<?= base_url() ?>' + data.foto_masuk.replace(/^\/+/, '');
        imgMasuk.classList.remove('hidden');
        plhMasuk.classList.add('hidden');
    } else {
        imgMasuk.classList.add('hidden');
        plhMasuk.classList.remove('hidden');
    }

    const imgKeluar = document.getElementById('selfieKeluar');
    const plhKeluar = document.getElementById('selfieKeluarPlaceholder');
    if (data.foto_keluar) {
        imgKeluar.src = '<?= base_url() ?>' + data.foto_keluar.replace(/^\/+/, '');
        imgKeluar.classList.remove('hidden');
        plhKeluar.classList.add('hidden');
    } else {
        imgKeluar.classList.add('hidden');
        plhKeluar.classList.remove('hidden');
    }

    // Details text
    document.getElementById('detailMasuk').innerText = data.jam_masuk ? data.jam_masuk.substring(0, 5) : '-';
    document.getElementById('detailKeluar').innerText = data.jam_keluar ? data.jam_keluar.substring(0, 5) : '-';
    document.getElementById('detailSkema').innerText = data.skema ? data.skema + ' jam' : '-';
    document.getElementById('detailLembur').innerText = rupiah(data.lembur);
    document.getElementById('detailPotongan').innerText = rupiah(data.potongan);
    document.getElementById('detailUpahFinal').innerText = rupiah(data.upah_final);

    // Keluar Awal Section
    const kaSec = document.getElementById('detailKeluarAwalSection');
    const kaKet = document.getElementById('detailKeluarAwalKeterangan');
    const kaBktWrap = document.getElementById('detailKeluarAwalBuktiWrap');
    const kaBktImg = document.getElementById('detailKeluarAwalBuktiImg');

    if (data.keterangan_keluar_awal) {
        kaKet.innerText = data.keterangan_keluar_awal;
        kaSec.classList.remove('hidden');
        if (data.bukti_keluar_awal) {
            kaBktImg.src = '<?= base_url() ?>' + data.bukti_keluar_awal.replace(/^\/+/, '');
            kaBktWrap.classList.remove('hidden');
        } else {
            kaBktWrap.classList.add('hidden');
        }
    } else {
        kaSec.classList.add('hidden');
    }

    // Show modal
    const modal = document.getElementById('detailModal');
    modal.classList.remove('hidden');

    // Init map
    setTimeout(() => {
        const latMasuk = parseFloat(data.lat_masuk);
        const lngMasuk = parseFloat(data.lng_masuk);
        const latKeluar = parseFloat(data.lat_keluar);
        const lngKeluar = parseFloat(data.lng_keluar);

        const validMasuk = !isNaN(latMasuk) && !isNaN(lngMasuk);
        const validKeluar = !isNaN(latKeluar) && !isNaN(lngKeluar);

        const defaultLat = validMasuk ? latMasuk : -6.2088;
        const defaultLng = validMasuk ? lngMasuk : 106.8456;

        if (!leafletMap) {
            leafletMap = L.map('map').setView([defaultLat, defaultLng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(leafletMap);
        } else {
            leafletMap.setView([defaultLat, defaultLng], 15);
            if (mapMarkerMasuk) leafletMap.removeLayer(mapMarkerMasuk);
            if (mapMarkerKeluar) leafletMap.removeLayer(mapMarkerKeluar);
        }

        const bounds = [];
        if (validMasuk) {
            mapMarkerMasuk = L.marker([latMasuk, lngMasuk])
                .addTo(leafletMap)
                .bindPopup("<b>Absen Masuk</b><br>Lokasi Karyawan")
                .openPopup();
            bounds.push([latMasuk, lngMasuk]);
        }
        if (validKeluar) {
            mapMarkerKeluar = L.marker([latKeluar, lngKeluar])
                .addTo(leafletMap)
                .bindPopup("<b>Absen Keluar</b><br>Lokasi Karyawan");
            bounds.push([latKeluar, lngKeluar]);
        }

        if (bounds.length > 1) {
            leafletMap.fitBounds(bounds, { padding: [30, 30] });
        }
        
        leafletMap.invalidateSize();
    }, 100);
}

function closeModal(event) {
    if (event === null || event.target === document.getElementById('detailModal')) {
        document.getElementById('detailModal').classList.add('hidden');
    }
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    return d.toLocaleDateString('id-ID', options);
}

function rupiah(num) {
    return 'Rp' + parseInt(num).toLocaleString('id-ID');
}

function showEarlyCheckout(data) {
    const buktiHtml = data.bukti
        ? `<img src="${data.bukti}" class="mt-3 w-full rounded-xl border border-slate-200" alt="Bukti">`
        : '<p class="mt-2 text-xs text-slate-400">Tidak ada foto bukti.</p>';
    Swal.fire({
        title: data.title || 'Keluar Lebih Awal',
        html: `<p class="text-sm text-slate-600"><strong>${data.nama}</strong> · ${data.jam || '-'}</p>
               <p class="mt-3 text-left text-sm text-slate-800">${(data.keterangan || '').replace(/</g, '&lt;')}</p>${buktiHtml}`,
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#10b981',
        width: 480,
    });
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
