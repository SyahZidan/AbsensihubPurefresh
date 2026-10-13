<?php
$title = 'Audit Log Admin';
$active = 'admin.logs';

$rows = db()->query("
    SELECT l.*, u.nama AS admin_nama
    FROM admin_logs l
    JOIN users u ON u.id = l.admin_id
    ORDER BY l.created_at DESC
    LIMIT 300
")->fetchAll();

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
                <h1 class="text-base sm:text-xl font-extrabold text-slate-900 font-outfit">Audit Log</h1>
            </div>
        </div>
    </div>
    <div class="p-3 sm:p-5 lg:p-8">
        <div class="overflow-hidden rounded-xl sm:rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto scrollbar-thin">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead class="bg-slate-50 text-[10px] sm:text-xs uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3">Waktu</th>
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3">Admin</th>
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3">Aksi</th>
                            <th class="hidden sm:table-cell px-4 py-3">Target</th>
                            <th class="hidden md:table-cell px-4 py-3">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($rows as $row): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-[10px] sm:text-xs font-mono text-slate-500">
                                <div class="font-semibold"><?= date('d/m/y', strtotime($row['created_at'])) ?></div>
                                <div class="text-[10px] text-slate-400"><?= date('H:i:s', strtotime($row['created_at'])) ?></div>
                            </td>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3 font-semibold text-slate-800"><?= e($row['admin_nama']) ?></td>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3">
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] sm:text-xs font-bold text-slate-700"><?= e($row['action']) ?></span>
                                <div class="md:hidden mt-1 text-[10px] text-slate-500 whitespace-normal leading-normal"><?= e($row['detail'] ?? '-') ?></div>
                            </td>
                            <td class="hidden sm:table-cell px-4 py-3 text-xs text-slate-600 font-mono"><?= e($row['target_type']) ?> #<?= (int) $row['target_id'] ?></td>
                            <td class="hidden md:table-cell px-4 py-3 text-xs text-slate-600 whitespace-normal"><?= e($row['detail'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$rows): ?>
                        <tr><td colspan="5" class="px-4 py-8 sm:py-10 text-center text-slate-400 text-xs sm:text-sm">Belum ada audit log.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
