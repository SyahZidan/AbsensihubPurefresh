<?php
$title = 'Approval Izin/Cuti';
$active = 'admin.leaves';

mark_notifications_read((int) $user['id']);

$rows = db()->query("
    SELECT lr.*, u.nama
    FROM leave_requests lr
    JOIN users u ON u.id = lr.user_id
    ORDER BY lr.status = 'menunggu' DESC, lr.created_at DESC
    LIMIT 200
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
                <h1 class="text-base sm:text-xl font-extrabold text-slate-900 font-outfit">Approval Izin/Cuti</h1>
            </div>
        </div>
    </div>
    <div class="p-3 sm:p-5 lg:p-8">
        <div class="overflow-hidden rounded-xl sm:rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto scrollbar-thin">
                <table class="w-full text-left text-xs sm:text-sm whitespace-nowrap sm:whitespace-normal">
                    <thead class="bg-slate-50 text-[10px] sm:text-xs uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3">Karyawan</th>
                            <th class="hidden sm:table-cell px-4 py-3">Jenis</th>
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3">Tanggal</th>
                            <th class="hidden md:table-cell px-4 py-3">Alasan</th>
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3">Status</th>
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($rows as $row): ?>
                        <?php $statusClass = match($row['status']) {
                            'disetujui' => 'bg-emerald-100 text-emerald-700',
                            'ditolak' => 'bg-rose-100 text-rose-700',
                            default => 'bg-amber-100 text-amber-700',
                        }; ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3">
                                <div class="font-semibold text-slate-800"><?= e($row['nama']) ?></div>
                                <div class="sm:hidden mt-0.5 text-[10px] font-bold text-slate-400 uppercase tracking-wide"><?= e($row['jenis']) ?></div>
                            </td>
                            <td class="hidden sm:table-cell px-4 py-3 font-medium text-slate-700"><?= strtoupper(e($row['jenis'])) ?></td>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-xs text-slate-600 font-mono">
                                <div><?= e($row['tanggal_mulai']) ?></div>
                                <div class="text-[10px] text-slate-400">s/d <?= e($row['tanggal_selesai']) ?></div>
                            </td>
                            <td class="hidden md:table-cell px-4 py-3 text-xs text-slate-600 max-w-xs truncate" title="<?= e($row['alasan']) ?>"><?= e($row['alasan']) ?></td>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3">
                                <span class="rounded-full px-2 py-0.5 text-[10px] sm:text-xs font-bold <?= $statusClass ?>"><?= e($row['status']) ?></span>
                            </td>
                            <td class="px-3 py-2.5 sm:px-4 sm:py-3">
                                <?php if ($row['status'] === 'menunggu'): ?>
                                <div class="flex gap-1.5 sm:gap-2">
                                    <button onclick="reviewLeave(<?= (int) $row['id'] ?>, 'setuju')" class="rounded-lg bg-emerald-500 px-2.5 py-1 text-[10px] sm:text-xs font-bold text-white shadow transition hover:bg-emerald-600">Setuju</button>
                                    <button onclick="reviewLeave(<?= (int) $row['id'] ?>, 'tolak')" class="rounded-lg bg-rose-500 px-2.5 py-1 text-[10px] sm:text-xs font-bold text-white shadow transition hover:bg-rose-600">Tolak</button>
                                </div>
                                <?php else: ?>
                                <span class="text-xs text-slate-400">Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$rows): ?>
                        <tr><td colspan="6" class="px-4 py-8 sm:py-10 text-center text-slate-400 text-xs sm:text-sm">Belum ada pengajuan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<script>
async function reviewLeave(id, action) {
    const note = await AppAlert.prompt('Catatan admin (opsional):', {
        title: action === 'approve' ? 'Setujui pengajuan' : 'Tolak pengajuan',
        placeholder: 'Contoh: disetujui untuk keperluan keluarga',
    });
    if (note === null) return;

    try {
        const r = await fetch('<?= base_url('api/leave-action.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action, catatan: note }),
        });
        const d = await r.json();
        await AppAlert.fromResponse(d, { reload: d.success });
    } catch (e) {
        await AppAlert.error('Terjadi kesalahan.');
    }
}
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
