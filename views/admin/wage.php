<?php
$title = 'Rekap Upah';
$active = 'admin.wage';

$search     = trim($_GET['q'] ?? '');
$tgl_dari   = $_GET['dari'] ?? '';
$tgl_sampai = $_GET['sampai'] ?? '';
$bulan      = $_GET['bulan'] ?? '';

if ($tgl_dari && $tgl_sampai) {
    $bulan = '';
} elseif (!$tgl_dari && !$tgl_sampai && !$bulan) {
    $bulan = date('Y-m');
}

// Build date filter
$dateWhere = '';
$params = [];
if ($tgl_dari && $tgl_sampai) {
    $dateWhere = "AND a.tanggal BETWEEN ? AND ?";
    $params[] = $tgl_dari;
    $params[] = $tgl_sampai;
} elseif ($bulan) {
    $dateWhere = "AND DATE_FORMAT(a.tanggal,'%Y-%m') = ?";
    $params[] = $bulan;
}

$searchWhere = '';
if ($search !== '') {
    $searchWhere = "AND u.nama LIKE ?";
    $params[] = "%{$search}%";
}

$rows = db()->prepare("
    SELECT u.id, u.nama, u.no_hp,
        COUNT(a.id) AS hari,
        COALESCE(SUM(CASE WHEN a.jam_masuk IS NOT NULL THEN 1 ELSE 0 END),0) AS total_hadir,
        COALESCE(SUM(a.lembur),0)         AS lembur,
        COALESCE(SUM(a.potongan),0)       AS potongan,
        COALESCE(SUM(a.upah_final),0)     AS total,
        COALESCE(SUM(CASE WHEN a.status_bayar='sudah_dibayar' THEN a.upah_final ELSE 0 END),0) AS terbayar,
        COALESCE(SUM(CASE WHEN a.status_bayar='belum_dibayar' THEN a.upah_final ELSE 0 END),0) AS belum_bayar
    FROM users u
    LEFT JOIN absensi a ON a.user_id = u.id {$dateWhere}
    WHERE u.role = 'karyawan' {$searchWhere}
    GROUP BY u.id, u.nama, u.no_hp
    ORDER BY total DESC
");
$rows->execute($params);
$rows = $rows->fetchAll();

$grand_total   = array_sum(array_column($rows, 'total'));
$grand_terbayar = array_sum(array_column($rows, 'terbayar'));
$grand_belum   = array_sum(array_column($rows, 'belum_bayar'));

require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/admin_sidebar.php';
?>
<main class="min-h-screen lg:ml-64">
    <!-- Top Bar -->
    <div class="sticky top-0 z-30 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/60 bg-white/80 px-6 py-4 backdrop-blur-xl">
        <div class="flex items-center gap-3">
            <button @click="mobileSidebarOpen = true" class="lg:hidden flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white shadow-md transition hover:bg-slate-800">
                <i data-lucide="menu" class="h-5 w-5"></i>
            </button>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Admin Portal</p>
                <h1 class="text-xl font-extrabold text-slate-900 font-outfit">Rekap Upah Karyawan</h1>
            </div>
        </div>
        <!-- Export buttons grouped on the right -->
        <div class="flex items-center gap-2">
            <a href="<?= base_url('api/export-excel.php') ?>?type=wage&bulan=<?= urlencode($bulan) ?>&dari=<?= urlencode($tgl_dari) ?>&sampai=<?= urlencode($tgl_sampai) ?>&q=<?= urlencode($search) ?>"
               class="flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-600">
                <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
                <span>Export Excel</span>
            </a>
            <a href="<?= base_url('api/export-slips-batch.php') ?>?bulan=<?= e($bulan ?? date('Y-m')) ?>"
               target="_blank"
               class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <i data-lucide="file-text" class="h-4 w-4"></i>
                <span>Slip PDF Batch</span>
            </a>
        </div>
    </div>

    <div class="p-5 lg:p-8">
        <!-- Grand Total Cards -->
        <?php if (is_logbook_mode()): ?>
        <div class="grid gap-4 sm:grid-cols-3 mb-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Upah Periode</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900 font-outfit"><?= rupiah((int)$grand_total) ?></p>
            </div>
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-500">Total Karyawan</p>
                <p class="mt-2 text-3xl font-extrabold text-indigo-700 font-outfit"><?= count($rows) ?> orang</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-500">Total Kehadiran</p>
                <p class="mt-2 text-3xl font-extrabold text-emerald-700 font-outfit"><?= array_sum(array_column($rows, 'total_hadir')) ?> hari</p>
            </div>
        </div>
        <?php else: ?>
        <div class="grid gap-4 sm:grid-cols-3 mb-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Upah Periode</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900 font-outfit"><?= rupiah((int)$grand_total) ?></p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-500">Sudah Terbayar</p>
                <p class="mt-2 text-3xl font-extrabold text-emerald-700 font-outfit"><?= rupiah((int)$grand_terbayar) ?></p>
            </div>
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-500">Belum Dibayar</p>
                <p class="mt-2 text-3xl font-extrabold text-rose-700 font-outfit"><?= rupiah((int)$grand_belum) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <form method="get" class="flex flex-wrap gap-3 mb-5">
            <input type="hidden" name="page" value="admin.wage">
            <div class="flex flex-1 min-w-[180px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
                <i data-lucide="search" class="h-4 w-4 shrink-0 text-slate-400"></i>
                <input name="q" value="<?= e($search) ?>" placeholder="Cari nama karyawan..." class="w-full bg-transparent text-sm outline-none">
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
                <i data-lucide="calendar" class="h-4 w-4 text-slate-400"></i>
                <input type="month" name="bulan" value="<?= e($bulan) ?>" class="bg-transparent text-sm outline-none text-slate-700">
            </div>
            <span class="flex items-center text-xs text-slate-400 font-medium">atau rentang:</span>
            <input type="date" name="dari"   value="<?= e($tgl_dari) ?>"   placeholder="Dari"   class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm shadow-sm outline-none text-slate-700">
            <input type="date" name="sampai" value="<?= e($tgl_sampai) ?>" placeholder="Sampai" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm shadow-sm outline-none text-slate-700">
            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-slate-700">Filter</button>
            <a href="<?= route_url('admin.wage') ?>" class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 shadow-sm transition hover:bg-slate-50">
                <i data-lucide="x" class="h-4 w-4"></i> Reset
            </a>
        </form>

        <!-- Wage Table -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-xs uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3.5 font-semibold">Karyawan</th>
                            <th class="px-4 py-3.5 font-semibold text-right">Hari Hadir</th>
                            <th class="px-4 py-3.5 font-semibold text-right">Lembur</th>
                            <th class="px-4 py-3.5 font-semibold text-right">Potongan</th>
                            <th class="px-4 py-3.5 font-semibold text-right">Total Upah</th>
                            <?php if (!is_logbook_mode()): ?>
                            <th class="px-4 py-3.5 font-semibold text-right">Terbayar</th>
                            <th class="px-4 py-3.5 font-semibold text-right">Sisa</th>
                            <?php endif; ?>
                            <th class="px-4 py-3.5 font-semibold text-center">Riwayat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($rows as $row): ?>
                        <tr class="group transition-colors hover:bg-slate-50/60">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 text-xs font-extrabold text-white">
                                        <?= strtoupper(substr($row['nama'],0,1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold"><a href="<?= route_url('admin.employee-detail', ['id' => $row['id']]) ?>" class="text-slate-800 hover:text-emerald-600 transition-colors"><?= e($row['nama']) ?></a></p>
                                        <p class="text-xs text-slate-400"><?= e($row['no_hp']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <span class="inline-flex items-center justify-center rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-bold text-emerald-700">
                                    <?= (int)$row['total_hadir'] ?> hari
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right font-semibold text-purple-600"><?= $row['lembur']>0 ? rupiah((int)$row['lembur']) : '—' ?></td>
                            <td class="px-4 py-4 text-right font-semibold text-rose-500"><?= $row['potongan']>0 ? '- '.rupiah((int)$row['potongan']) : '—' ?></td>
                            <td class="px-4 py-4 text-right font-extrabold text-slate-900 text-base"><?= rupiah((int)$row['total']) ?></td>
                            <?php if (!is_logbook_mode()): ?>
                            <td class="px-4 py-4 text-right font-semibold text-emerald-600"><?= rupiah((int)$row['terbayar']) ?></td>
                            <td class="px-4 py-4 text-right">
                                <?php if ($row['belum_bayar'] > 0): ?>
                                <span class="font-bold text-rose-600"><?= rupiah((int)$row['belum_bayar']) ?></span>
                                <?php else: ?>
                                <span class="text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td class="px-4 py-4 text-center">
                                <a href="<?= route_url('admin.attendance', ['karyawan_id'=>$row['id']]) ?>"
                                   class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition">
                                    <i data-lucide="list" class="h-3 w-3"></i> Detail
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <!-- Grand Total Row -->
                        <tr class="border-t-2 border-slate-200 bg-slate-50/80 font-bold">
                            <td class="px-5 py-4 text-slate-700">TOTAL</td>
                            <td class="px-4 py-4 text-right text-slate-700"><?= array_sum(array_column($rows,'total_hadir')) ?> hari</td>
                            <td class="px-4 py-4 text-right text-purple-600"><?= rupiah((int)array_sum(array_column($rows,'lembur'))) ?></td>
                            <td class="px-4 py-4 text-right text-rose-500">- <?= rupiah((int)array_sum(array_column($rows,'potongan'))) ?></td>
                            <td class="px-4 py-4 text-right text-slate-900 text-base"><?= rupiah((int)$grand_total) ?></td>
                            <?php if (!is_logbook_mode()): ?>
                            <td class="px-4 py-4 text-right text-emerald-600"><?= rupiah((int)$grand_terbayar) ?></td>
                            <td class="px-4 py-4 text-right text-rose-600"><?= rupiah((int)$grand_belum) ?></td>
                            <?php endif; ?>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
