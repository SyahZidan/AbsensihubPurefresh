<?php
$title = 'Izin & Cuti';
$active = 'leaves';

$notifList = fetch_notifications((int) $user['id'], 10);
mark_notifications_read((int) $user['id']);

$leaveStmt = db()->prepare('SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 30');
$leaveStmt->execute([$user['id']]);
$leaveRows = $leaveStmt->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<main class="phone-shell min-h-screen bg-slate-50 pb-28">
    <div class="bg-gradient-to-br from-slate-900 to-slate-800 px-5 pb-6 pt-8 text-white">
        <h1 class="text-xl font-extrabold font-outfit">Izin / Sakit / Cuti</h1>
        <p class="mt-1 text-xs text-slate-300">Ajukan izin dan lihat update dari admin.</p>
    </div>

    <div class="-mt-3 space-y-4 px-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-bold text-slate-900">Ajukan Permohonan</h2>
            <form action="<?= base_url('api/leave-request.php') ?>" method="post" class="space-y-3">
                <?= csrf_field() ?>
                <div class="grid grid-cols-2 gap-3">
                    <label class="text-xs font-semibold text-slate-600">
                        Jenis
                        <select name="jenis" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="cuti">Cuti</option>
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-slate-600">
                        Mulai
                        <input type="date" name="tanggal_mulai" required class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                    </label>
                </div>
                <label class="block text-xs font-semibold text-slate-600">
                    Sampai
                    <input type="date" name="tanggal_selesai" required class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                </label>
                <label class="block text-xs font-semibold text-slate-600">
                    Alasan
                    <textarea name="alasan" rows="3" required class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm"></textarea>
                </label>
                <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-700">
                    Kirim Permohonan
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-bold text-slate-900">Notifikasi</h2>
            <div class="space-y-2">
                <?php foreach ($notifList as $notif): ?>
                <?php $color = match($notif['type']) {
                    'success' => 'bg-emerald-50 border-emerald-100 text-emerald-700',
                    'warning' => 'bg-amber-50 border-amber-100 text-amber-700',
                    'danger' => 'bg-rose-50 border-rose-100 text-rose-700',
                    default => 'bg-slate-50 border-slate-200 text-slate-700',
                }; ?>
                <div class="rounded-xl border px-3 py-2 <?= $color ?>">
                    <p class="text-xs font-bold"><?= e($notif['title']) ?></p>
                    <p class="mt-0.5 text-xs"><?= e($notif['message']) ?></p>
                </div>
                <?php endforeach; ?>
                <?php if (!$notifList): ?>
                <p class="text-xs text-slate-400">Belum ada notifikasi.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-bold text-slate-900">Riwayat Permohonan</h2>
            <div class="space-y-2">
                <?php foreach ($leaveRows as $row): ?>
                <?php $badge = match($row['status']) {
                    'disetujui' => 'bg-emerald-100 text-emerald-700',
                    'ditolak' => 'bg-rose-100 text-rose-700',
                    default => 'bg-amber-100 text-amber-700',
                }; ?>
                <div class="rounded-xl border border-slate-100 p-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-bold text-slate-800"><?= strtoupper(e($row['jenis'])) ?></p>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold <?= $badge ?>"><?= e($row['status']) ?></span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500"><?= e($row['tanggal_mulai']) ?> s/d <?= e($row['tanggal_selesai']) ?></p>
                    <p class="mt-1 text-xs text-slate-600"><?= e($row['alasan']) ?></p>
                    <?php if (!empty($row['catatan_admin'])): ?>
                    <p class="mt-1 text-xs text-slate-500">Catatan admin: <?= e($row['catatan_admin']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (!$leaveRows): ?>
                <p class="text-xs text-slate-400">Belum ada pengajuan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../partials/employee_nav.php'; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
