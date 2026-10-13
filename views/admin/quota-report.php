<?php
$title = 'Laporan Kuota Harian';
$active = 'admin.quota-report';

require_once __DIR__ . '/../../includes/quota.php';
ensure_feature_tables();

$tanggal = $_GET['tanggal'] ?? current_date();
$quota = daily_quota_status($tanggal);

$masuk = db()->prepare("
    SELECT a.jam_masuk, a.status, u.nama, u.no_hp
    FROM absensi a
    JOIN users u ON u.id = a.user_id
    WHERE a.tanggal = ? AND a.jam_masuk IS NOT NULL
    ORDER BY a.jam_masuk ASC
");
$masuk->execute([$tanggal]);
$masukRows = $masuk->fetchAll();

$blocked = db()->prepare("
    SELECT qb.created_at, u.nama, u.no_hp, qb.alasan
    FROM quota_blocks qb
    JOIN users u ON u.id = qb.user_id
    WHERE qb.tanggal = ?
    ORDER BY qb.created_at ASC
");
$blocked->execute([$tanggal]);
$blockedRows = $blocked->fetchAll();

require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/admin_sidebar.php';
?>
<main class="min-h-screen lg:ml-64">
    <!-- Top Bar -->
    <div class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200/60 bg-white/80 px-4 py-3 sm:px-6 sm:py-4 backdrop-blur-xl">
        <div class="flex items-center gap-2.5 sm:gap-3">
            <button @click="mobileSidebarOpen = true" class="lg:hidden flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center rounded-xl bg-slate-900 text-white shadow-md transition hover:bg-slate-800">
                <i data-lucide="menu" class="h-4 w-4 sm:h-5 sm:w-5"></i>
            </button>
            <div>
                <p class="hidden sm:block text-xs font-semibold uppercase tracking-widest text-slate-400">Admin Portal</p>
                <h1 class="text-base sm:text-xl font-extrabold text-slate-900 font-outfit">Laporan Kuota Harian</h1>
            </div>
        </div>
    </div>

    <div class="p-3 sm:p-5 lg:p-8 space-y-4 sm:space-y-6">
        <form method="get" class="flex flex-wrap gap-2.5 items-end bg-white border border-slate-200 p-3 sm:p-4 rounded-xl sm:rounded-2xl max-w-sm sm:max-w-none">
            <input type="hidden" name="page" value="admin.quota-report">
            <label class="block flex-1 sm:flex-none">
                <span class="block text-xs sm:text-sm font-semibold text-slate-700">Tanggal</span>
                <input type="date" name="tanggal" value="<?= e($tanggal) ?>" class="ui-input mt-1 block w-full px-2.5 py-1.5 sm:px-3 sm:py-2 text-xs sm:text-sm rounded-lg sm:rounded-xl">
            </label>
            <button type="submit" class="rounded-lg sm:rounded-xl bg-slate-900 px-4 py-2 sm:px-5 sm:py-2.5 text-xs sm:text-sm font-bold text-white shadow transition hover:bg-slate-800 whitespace-nowrap">Tampilkan</button>
        </form>

        <div class="grid gap-3 sm:gap-4 grid-cols-3">
            <div class="ui-card p-3 sm:p-5 rounded-xl sm:rounded-2xl">
                <p class="text-[10px] sm:text-xs text-slate-400 uppercase tracking-wider">Slot Terisi</p>
                <p class="mt-1 text-base sm:text-3xl font-extrabold text-slate-900"><?= $quota['used'] ?><?= $quota['unlimited'] ? '' : '<span class="text-xs sm:text-lg font-semibold text-slate-400">/' . $quota['limit'] . '</span>' ?></p>
            </div>
            <div class="ui-card p-3 sm:p-5 rounded-xl sm:rounded-2xl">
                <p class="text-[10px] sm:text-xs text-slate-400 uppercase tracking-wider">Sisa Slot</p>
                <p class="mt-1 text-base sm:text-3xl font-extrabold text-indigo-600"><?= $quota['unlimited'] ? '∞' : (int) $quota['remaining'] ?></p>
            </div>
            <div class="ui-card p-3 sm:p-5 rounded-xl sm:rounded-2xl">
                <p class="text-[10px] sm:text-xs text-slate-400 uppercase tracking-wider">Ditolak</p>
                <p class="mt-1 text-base sm:text-3xl font-extrabold text-rose-600"><?= count($blockedRows) ?></p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="ui-card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-3 font-bold text-slate-800">Berhasil masuk (urut jam)</div>
                <div class="max-h-96 overflow-y-auto divide-y divide-slate-50">
                    <?php foreach ($masukRows as $i => $r): ?>
                    <div class="flex items-center justify-between px-5 py-3 text-sm">
                        <span class="font-semibold text-slate-500 w-6">#<?= $i + 1 ?></span>
                        <span class="flex-1 font-semibold text-slate-800"><?= e($r['nama']) ?></span>
                        <span class="font-mono text-slate-600"><?= e(substr($r['jam_masuk'], 0, 5)) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!$masukRows): ?>
                    <p class="px-5 py-8 text-center text-slate-400 text-sm">Belum ada yang masuk.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="ui-card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-3 font-bold text-slate-800">Ditolak — kuota penuh</div>
                <div class="max-h-96 overflow-y-auto divide-y divide-slate-50">
                    <?php foreach ($blockedRows as $r): ?>
                    <div class="px-5 py-3 text-sm">
                        <p class="font-semibold text-slate-800"><?= e($r['nama']) ?></p>
                        <p class="text-xs text-slate-500"><?= e($r['no_hp']) ?> · <?= e(substr($r['created_at'], 11, 5)) ?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!$blockedRows): ?>
                    <p class="px-5 py-8 text-center text-slate-400 text-sm">Tidak ada penolakan tercatat.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
