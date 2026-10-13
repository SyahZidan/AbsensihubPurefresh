<?php
$title = 'Hari Libur';
$active = 'admin.holidays';

require_once __DIR__ . '/../../includes/holidays.php';
$rows = fetch_holidays(100);

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
                <h1 class="text-base sm:text-xl font-extrabold text-slate-900 font-outfit">Kalender Hari Libur</h1>
            </div>
        </div>
    </div>
    <div class="p-3 sm:p-5 lg:p-8 max-w-2xl space-y-4 sm:space-y-6">
        <form action="<?= base_url('api/holiday-save.php') ?>" method="post" class="ui-card p-4 sm:p-5 space-y-3 sm:space-y-4 rounded-xl sm:rounded-2xl">
            <?= csrf_field() ?>
            <h2 class="text-sm sm:text-base font-bold text-slate-900">Tambah Hari Libur</h2>
            <div class="grid gap-2.5 sm:gap-3 sm:grid-cols-2">
                <label class="text-xs sm:text-sm font-semibold text-slate-700">
                    Tanggal
                    <input type="date" name="tanggal" required class="ui-input mt-1 block w-full px-2.5 py-1.5 sm:px-3 sm:py-2 text-xs sm:text-sm rounded-lg sm:rounded-xl">
                </label>
                <label class="text-xs sm:text-sm font-semibold text-slate-700 sm:col-span-2">
                    Keterangan
                    <input type="text" name="keterangan" required placeholder="Libur nasional / tutup pabrik" class="ui-input mt-1 block w-full px-2.5 py-1.5 sm:px-3 sm:py-2 text-xs sm:text-sm rounded-lg sm:rounded-xl">
                </label>
            </div>
            <button type="submit" class="rounded-lg sm:rounded-xl bg-slate-900 px-4 py-2 sm:px-5 sm:py-2.5 text-xs sm:text-sm font-bold text-white shadow transition hover:bg-slate-800">Simpan</button>
        </form>

        <div class="ui-card overflow-hidden rounded-xl sm:rounded-2xl">
            <div class="divide-y divide-slate-100">
                <?php foreach ($rows as $row): ?>
                <div class="flex items-center justify-between px-4 py-2.5 sm:px-5 sm:py-3 transition-colors hover:bg-slate-50/50">
                    <div>
                        <p class="font-semibold text-slate-800 text-xs sm:text-sm"><?= date('d M Y', strtotime($row['tanggal'])) ?></p>
                        <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5"><?= e($row['keterangan']) ?></p>
                    </div>
                    <form action="<?= base_url('api/holiday-delete.php') ?>" method="post" data-swal-confirm="Hapus hari libur ini?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="text-rose-600 text-xs font-bold hover:underline">Hapus</button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                <p class="px-5 py-8 text-center text-xs sm:text-sm text-slate-400">Belum ada hari libur.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
