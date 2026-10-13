<?php
require_once __DIR__ . '/../../includes/features.php';
$menus = [
    'admin.dashboard' => ['label' => 'Dashboard', 'icon' => 'layout-dashboard'],
    'admin.employees' => ['label' => 'Karyawan', 'icon' => 'users'],
    'admin.attendance' => ['label' => 'Absensi', 'icon' => 'calendar-check'],
    'admin.wage' => ['label' => 'Rekap Upah', 'icon' => 'receipt'],
    'admin.settings' => ['label' => 'Pengaturan', 'icon' => 'settings'],
];
if (!is_logbook_mode()) {
    $menus = array_slice($menus, 0, 3, true) + [
        'admin.quota-report' => ['label' => 'Laporan Kuota', 'icon' => 'bar-chart-3'],
        'admin.wage' => ['label' => 'Rekap Upah', 'icon' => 'receipt'],
        'admin.payment' => ['label' => 'Pembayaran', 'icon' => 'credit-card'],
        'admin.leaves' => ['label' => 'Izin/Cuti', 'icon' => 'file-check-2'],
        'admin.logs' => ['label' => 'Audit Log', 'icon' => 'scroll-text'],
        'admin.holidays' => ['label' => 'Hari Libur', 'icon' => 'calendar-off'],
        'admin.settings' => ['label' => 'Pengaturan', 'icon' => 'settings'],
    ];
}
$adminNotifCount = 0;
if (!is_logbook_mode() && !empty($user['id']) && ($user['role'] ?? '') === 'admin') {
    $adminNotifCount = unread_notifications_count((int) $user['id']);
}
?>
<!-- Backdrop (Mobile only) -->
<div x-show="mobileSidebarOpen" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="mobileSidebarOpen = false" 
     class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
     style="display: none;">
</div>

<aside :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 w-64 glass-sidebar p-6 flex flex-col justify-between text-slate-300 z-50 transition-transform duration-300 ease-in-out lg:flex shadow-2xl shadow-slate-950/20 overflow-y-auto">
    <div class="shrink-0">
        <div class="mb-10 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-white shadow-lg shadow-emerald-500/20">
                    <i data-lucide="building-2" class="h-5 w-5"></i>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] font-semibold text-slate-400">UMKM Portal</p>
                    <h1 class="text-lg font-extrabold text-white tracking-tight leading-none mt-0.5 font-outfit"><?= e(get_app_name()) ?></h1>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?php if (!is_logbook_mode()): ?>
                <a href="<?= route_url('admin.leaves') ?>" 
                   x-data="{ notifCount: <?= (int) $adminNotifCount ?> }"
                   @click="notifCount = 0"
                   class="relative flex h-9 w-9 items-center justify-center rounded-lg bg-white/5 text-slate-300 hover:bg-white/10 hover:text-white" title="Notifikasi">
                    <i data-lucide="bell" class="h-4 w-4"></i>
                    <?php if ($adminNotifCount > 0): ?>
                    <span x-show="notifCount > 0" class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white"><?= min($adminNotifCount, 9) ?><?= $adminNotifCount > 9 ? '+' : '' ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
                <button @click="mobileSidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white transition">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
        </div>
        <nav class="space-y-1.5">
            <?php foreach ($menus as $key => $item): ?>
                <a href="<?= route_url($key) ?>" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-all duration-200 <?= $active === $key ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/10 text-emerald-400 border-l-4 border-emerald-400 pl-3 font-semibold shadow-sm' : 'text-slate-400 hover:bg-white/5 hover:text-white border-l-4 border-transparent' ?>">
                    <i data-lucide="<?= $item['icon'] ?>" class="h-4.5 w-4.5"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
    
    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 flex flex-col gap-3 shrink-0 mt-8">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white font-bold">
                <?= strtoupper(substr($user['nama'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                <p class="font-semibold text-white text-sm truncate leading-none mb-1"><?= e($user['nama'] ?? 'Admin') ?></p>
                <span class="text-xs text-slate-400 capitalize">Administrator</span>
            </div>
        </div>
        <a class="flex items-center justify-center gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 py-2.5 text-center text-xs font-semibold text-rose-400 transition-all hover:bg-rose-500 hover:text-white" href="<?= base_url('api/logout.php') ?>">
            <i data-lucide="log-out" class="h-4 w-4"></i>
            <span>Keluar Portal</span>
        </a>
    </div>
</aside>

