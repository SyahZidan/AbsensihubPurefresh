<?php $notifCount = is_logbook_mode() ? 0 : unread_notifications_count((int) $user['id']); ?>
<nav class="fixed bottom-4 left-1/2 z-40 w-[90%] max-w-[420px] -translate-x-1/2">
    <div class="employee-bottom-nav relative px-3 pb-3 pt-4 text-xs bg-white/95 backdrop-blur-md border border-slate-200/80 rounded-2xl shadow-xl">
        <a href="<?= route_url('karyawan.checkin') ?>" class="absolute left-1/2 -top-6 flex -translate-x-1/2 flex-col items-center">
            <span class="employee-fab flex h-14 w-14 items-center justify-center rounded-full border-[4px] border-white bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-lg shadow-emerald-500/30 transition-transform active:scale-95 duration-200">
                <i data-lucide="camera" class="h-6 w-6"></i>
            </span>
            <span class="text-[11px] font-bold mt-1 <?= $active === 'absen' ? 'text-emerald-600' : 'text-slate-500' ?>">Absen</span>
        </a>

        <div class="grid <?= is_logbook_mode() ? 'grid-cols-4' : 'grid-cols-5' ?> items-end">
            <a class="flex flex-col items-center gap-1 text-center transition-all duration-200 <?= $active === 'home' ? 'font-bold text-emerald-500 scale-105' : 'text-slate-400 hover:text-slate-600' ?>" href="<?= route_url('karyawan.home') ?>">
                <i data-lucide="home" class="h-5 w-5"></i>
                <span>Home</span>
            </a>

            <a class="flex flex-col items-center gap-1 text-center transition-all duration-200 <?= $active === 'history' ? 'font-bold text-emerald-500 scale-105' : 'text-slate-400 hover:text-slate-600' ?>" href="<?= route_url('karyawan.history') ?>">
                <i data-lucide="calendar" class="h-5 w-5"></i>
                <span>Riwayat</span>
            </a>

            <span></span>

            <?php if (!is_logbook_mode()): ?>
            <a class="relative flex flex-col items-center gap-1 text-center transition-all duration-200 <?= $active === 'leaves' ? 'font-bold text-emerald-500 scale-105' : 'text-slate-400 hover:text-slate-600' ?>" href="<?= route_url('karyawan.leaves') ?>">
                <i data-lucide="file-check" class="h-5 w-5"></i>
                <span>Izin</span>
                <?php if ($notifCount > 0): ?>
                <span class="absolute -right-1 -top-1 rounded-full bg-rose-500 px-1.5 text-[10px] font-bold leading-4 text-white"><?= min($notifCount, 9) ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>

            <a class="flex flex-col items-center gap-1 text-center transition-all duration-200 <?= $active === 'profile' ? 'font-bold text-emerald-500 scale-105' : 'text-slate-400 hover:text-slate-600' ?>" href="<?= route_url('karyawan.profile') ?>">
                <i data-lucide="user" class="h-5 w-5"></i>
                <span>Profil</span>
            </a>
        </div>
    </div>
</nav>
