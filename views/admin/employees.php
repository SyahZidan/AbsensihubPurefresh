<?php
$title = 'Manajemen Karyawan';
$active = 'admin.employees';

$search = trim($_GET['q'] ?? '');
$filter = $_GET['status'] ?? 'all';

$where = "WHERE role='karyawan'";
$params = [];
if ($search !== '') {
    $where .= " AND (nama LIKE ? OR no_hp LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($filter !== 'all') {
    $where .= " AND status = ?";
    $params[] = $filter === 'pending' ? 'menunggu_approval' : ($filter === 'suspended' ? 'nonaktif' : $filter);
}

$stmt = db()->prepare("SELECT u.*, 
    (SELECT COUNT(*) FROM absensi a WHERE a.user_id=u.id) AS total_hadir,
    (SELECT COALESCE(SUM(upah_final),0) FROM absensi a WHERE a.user_id=u.id) AS total_upah,
    (SELECT COALESCE(SUM(upah_final),0) FROM absensi a WHERE a.user_id=u.id AND status_bayar='belum_dibayar') AS upah_unpaid
    FROM users u {$where} ORDER BY u.created_at DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$counts = [
    'all'       => db()->query("SELECT COUNT(*) FROM users WHERE role='karyawan'")->fetchColumn(),
    'aktif'     => db()->query("SELECT COUNT(*) FROM users WHERE role='karyawan' AND status='aktif'")->fetchColumn(),
    'pending'   => db()->query("SELECT COUNT(*) FROM users WHERE role='karyawan' AND status='menunggu_approval'")->fetchColumn(),
    'suspended' => db()->query("SELECT COUNT(*) FROM users WHERE role='karyawan' AND status='nonaktif'")->fetchColumn(),
];

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
                <h1 class="text-base sm:text-xl font-extrabold text-slate-900 font-outfit">Manajemen Karyawan</h1>
            </div>
        </div>
    </div>

    <div class="p-3 sm:p-5 lg:p-8">
        <!-- Filter Tabs & Search -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full">
            <!-- Status tabs -->
            <div class="flex overflow-x-auto scrollbar-none rounded-xl border border-slate-200 bg-white shadow-sm text-xs sm:text-sm whitespace-nowrap w-full sm:w-auto divide-x divide-slate-100">
                <?php
                $tabs = ['all'=>'Semua','aktif'=>'Aktif','pending'=>'Pending','suspended'=>'Suspended'];
                $tabColors = ['all'=>'bg-slate-900 text-white','aktif'=>'bg-emerald-500 text-white','pending'=>'bg-amber-500 text-white','suspended'=>'bg-rose-500 text-white'];
                foreach ($tabs as $key => $label): ?>
                <a href="?page=admin.employees&status=<?= $key ?>&q=<?= urlencode($search) ?>"
                   class="flex items-center gap-1.5 px-3 py-2 sm:px-4 sm:py-2.5 font-semibold transition <?= $filter === $key ? $tabColors[$key] : 'text-slate-500 hover:bg-slate-50' ?>">
                    <?= $label ?>
                    <span class="rounded-full <?= $filter === $key ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-500' ?> px-1.5 py-0.5 text-[9px] sm:text-[10px] font-bold"><?= $counts[$key] ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Search -->
            <form method="get" class="flex flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 sm:px-4 sm:py-2.5 shadow-sm w-full sm:w-auto">
                <input type="hidden" name="page" value="admin.employees">
                <i data-lucide="search" class="h-3.5 w-3.5 sm:h-4 sm:w-4 shrink-0 text-slate-400"></i>
                <input name="q" value="<?= e($search) ?>" placeholder="Cari nama atau no HP..." class="w-full bg-transparent text-xs sm:text-sm outline-none placeholder:text-slate-400">
                <input type="hidden" name="status" value="<?= e($filter) ?>">
            </form>
        </div>

        <!-- Employee Grid Cards -->
        <?php if ($rows): ?>
        <div class="mt-4 sm:mt-5 grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($rows as $emp): ?>
            <?php
            $statusColor = match($emp['status']) {
                'aktif'             => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                'menunggu_approval' => 'bg-amber-100 text-amber-700 border-amber-200',
                'nonaktif'          => 'bg-rose-100 text-rose-700 border-rose-200',
                default             => 'bg-slate-100 text-slate-600 border-slate-200',
            };
            $statusLabel = match($emp['status']) {
                'aktif'             => 'Aktif',
                'menunggu_approval' => 'Pending',
                'nonaktif'          => 'Suspended',
                default             => ucfirst($emp['status']),
            };
            $avatarGradients = ['from-blue-500 to-indigo-600','from-emerald-500 to-teal-500','from-purple-500 to-pink-500','from-amber-500 to-orange-500','from-rose-500 to-pink-600'];
            $avatarColor = $avatarGradients[crc32($emp['nama']) % count($avatarGradients)];

            $showProfil = false;
            if (!empty($emp['foto_profil'])) {
                $fileProfil = dirname(__DIR__, 2) . $emp['foto_profil'];
                if (file_exists($fileProfil) && is_file($fileProfil)) {
                    $showProfil = true;
                }
            }
            ?>
            <div class="group flex flex-col rounded-xl sm:rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm transition-all duration-200 hover:shadow-md hover:-translate-y-0.5" id="emp-card-<?= (int)$emp['id'] ?>">
                <!-- Content Area -->
                <div class="flex justify-between gap-4 items-start">
                    <!-- Left Side: Profile & Details -->
                    <div class="flex items-start gap-3.5 min-w-0 flex-1">
                        <?php if ($showProfil): ?>
                            <div class="relative shrink-0">
                                <img src="<?= base_url($emp['foto_profil']) ?>" alt="<?= e($emp['nama']) ?>" class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl object-cover shadow border border-slate-200/80" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div style="display:none;" class="flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-2xl bg-gradient-to-br <?= $avatarColor ?> text-2xl sm:text-3xl font-extrabold text-white shadow-md">
                                    <?= strtoupper(substr($emp['nama'],0,1)) ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex h-16 w-16 sm:h-20 sm:w-20 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br <?= $avatarColor ?> text-2xl sm:text-3xl font-extrabold text-white shadow-md">
                                <?= strtoupper(substr($emp['nama'],0,1)) ?>
                            </div>
                        <?php endif; ?>

                        <div class="min-w-0 flex-1 space-y-1">
                            <p class="truncate font-bold text-slate-900 text-base sm:text-lg leading-tight" title="<?= e($emp['nama']) ?>"><?= e($emp['nama']) ?></p>
                            <p class="flex items-center gap-1.5 text-xs sm:text-sm text-slate-500">
                                <i data-lucide="phone" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i> 
                                <span class="truncate"><?= e($emp['no_hp']) ?></span>
                            </p>
                            <p class="flex items-center gap-1.5 text-xs text-slate-400">
                                <i data-lucide="calendar-plus" class="h-3.5 w-3.5 shrink-0"></i>
                                <span class="truncate">Join: <?= date('d M Y', strtotime($emp['created_at'])) ?></span>
                            </p>
                        </div>
                    </div>

                    <!-- Right Side: Status & Stats -->
                    <div class="flex flex-col items-end text-right shrink-0">
                        <span class="rounded-full border px-2.5 py-1 text-[10px] sm:text-[11px] font-bold <?= $statusColor ?> mb-2.5 leading-none shrink-0"><?= e($statusLabel) ?></span>
                        <div class="space-y-1 text-right text-xs sm:text-sm">
                            <div class="flex justify-end items-center gap-1.5">
                                <span class="text-slate-400 text-[10px] sm:text-xs">Hadir:</span>
                                <span class="font-bold text-slate-700"><?= (int)$emp['total_hadir'] ?> hari</span>
                            </div>
                            <div class="flex justify-end items-center gap-1.5">
                                <span class="text-slate-400 text-[10px] sm:text-xs">Total Upah:</span>
                                <span class="font-bold text-slate-700"><?= rupiah((int)$emp['total_upah']) ?></span>
                            </div>
                            <div class="flex justify-end items-center gap-1.5">
                                <span class="text-slate-400 text-[10px] sm:text-xs">Belum Bayar:</span>
                                <span class="font-bold <?= $emp['upah_unpaid'] > 0 ? 'text-rose-600' : 'text-emerald-600' ?>"><?= rupiah((int)$emp['upah_unpaid']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-4 flex gap-2">
                    <a href="<?= route_url('admin.employee-detail', ['id' => $emp['id']]) ?>"
                       class="flex-1 text-center rounded-lg sm:rounded-xl bg-gradient-to-r from-slate-900 to-slate-800 hover:from-emerald-600 hover:to-teal-600 py-2.5 sm:py-3 text-xs font-bold text-white shadow transition-all duration-200 hover:shadow-lg hover:scale-[1.01]">
                        <i data-lucide="external-link" class="inline h-3.5 w-3.5 mr-1"></i>Detail Karyawan
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="mt-8 sm:mt-10 flex flex-col items-center justify-center py-12 sm:py-16 text-center">
            <div class="flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-2xl sm:rounded-3xl bg-slate-100 mb-4">
                <i data-lucide="users" class="h-8 w-8 sm:h-10 sm:w-10 text-slate-300"></i>
            </div>
            <p class="text-sm sm:text-base font-bold text-slate-500">Tidak ada karyawan ditemukan</p>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Coba ubah filter atau kata kunci pencarian</p>
        </div>
        <?php endif; ?>
    </div>
</main>

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
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
