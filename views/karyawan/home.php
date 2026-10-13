<?php
$title = 'Home Karyawan';
$active = 'home';
require_once __DIR__ . '/../../includes/wage-calculator.php';
require_once __DIR__ . '/../../includes/geofence.php';
require_once __DIR__ . '/../../includes/quota.php';
require_once __DIR__ . '/../../includes/leaves.php';
require_once __DIR__ . '/../../includes/holidays.php';

$today = current_date();
$logbookMode = is_logbook_mode();
$stmt = db()->prepare('SELECT * FROM absensi WHERE user_id = ? AND tanggal = ? LIMIT 1');
$stmt->execute([$user['id'], $today]);
$attendance = $stmt->fetch();

$settings = get_settings();
$quota = daily_quota_status($today);
$kuotaLimit = $quota['limit'];
$totalCheckedIn = $quota['used'];
$isQuotaFull = (!$attendance || !$attendance['jam_masuk']) && $quota['is_full'];
if ($logbookMode) {
    $kuotaLimit = 0;
    $isQuotaFull = false;
}

// Fetch Wage Adjustment Logs (System adjustments & manual adjustments for this user)
$allWageLogs = db()->prepare("
    SELECT * FROM admin_logs 
    WHERE (action = 'settings_update' AND detail LIKE 'Penyesuaian gaji%')
       OR (action = 'wage_manual_edit' AND detail LIKE ?)
    ORDER BY created_at DESC LIMIT 10
");
$allWageLogs->execute(["%karyawan " . $user['nama'] . "%"]);
$wageLogsList = $allWageLogs->fetchAll() ?: [];
$latestWageLog = !empty($wageLogsList) ? $wageLogsList[0] : null;

// Calculate live estimate
$estimate = null;
if ($attendance && $attendance['jam_masuk'] && !$attendance['jam_keluar']) {
    $estimate = calculate_wage($attendance['jam_masuk'], date('H:i:s'));
}

// Wallet summary
$wallet = db()->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN status_bayar='belum_dibayar' THEN upah_final ELSE 0 END),0) AS belum,
        COALESCE(SUM(CASE WHEN status_bayar='sudah_dibayar' THEN upah_final ELSE 0 END),0) AS lunas,
        COALESCE(SUM(upah_final),0) AS total
    FROM absensi WHERE user_id=?
");
$wallet->execute([$user['id']]);
$wallet = $wallet->fetch();

// This month stats
$monthStats = db()->prepare("
    SELECT COUNT(*) AS hari, COALESCE(SUM(lembur),0) AS lembur
    FROM absensi
    WHERE user_id=? AND DATE_FORMAT(tanggal,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m') AND jam_masuk IS NOT NULL
");
$monthStats->execute([$user['id']]);
$monthStats = $monthStats->fetch();

// Recent 5 records
$recentStmt = db()->prepare('SELECT * FROM absensi WHERE user_id=? ORDER BY tanggal DESC LIMIT 5');
$recentStmt->execute([$user['id']]);
$recentList = $recentStmt->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<main class="phone-shell pb-28 min-h-screen bg-slate-50">

    <!-- Hero Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-5 pt-10 pb-20">
        <!-- Decorative blobs -->
        <div class="absolute top-0 right-0 h-40 w-40 rounded-full bg-emerald-500/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-32 w-32 rounded-full bg-teal-500/10 blur-2xl"></div>

        <?php if (!$logbookMode && $latestWageLog): ?>
        <div class="relative z-10 mb-4 flex justify-start">
            <button onclick="openWageLogsModal()" class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-3 py-1 text-[11px] font-semibold text-amber-300 border border-amber-500/20 backdrop-blur-sm transition hover:bg-amber-500/20">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                </span>
                <i data-lucide="info" class="h-3.5 w-3.5"></i>
                <span>Log Penyesuaian Gaji</span>
            </button>
        </div>
        <?php endif; ?>

        <div class="relative flex items-start justify-between">
            <div>
                <p class="text-sm text-slate-400">Selamat datang,</p>
                <h1 class="mt-0.5 text-2xl font-extrabold text-white font-outfit leading-tight"><?= e($user['nama']) ?></h1>
                <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-400">
                    <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                    <?= date('l, d F Y') ?>
                </p>
            </div>
            <a href="<?= base_url('api/logout.php') ?>" class="flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-300 transition hover:bg-white/10">
                <i data-lucide="log-out" class="h-4 w-4"></i>
            </a>
        </div>

        <!-- Status card -->
        <div class="relative mt-5 rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Status Hari Ini</p>
                <?php if ($attendance): ?>
                <?php
                $sc = match($attendance['status']) {
                    'selesai'   => 'bg-emerald-500/20 text-emerald-400',
                    'bekerja'   => 'bg-blue-500/20 text-blue-300',
                    'terlambat' => 'bg-amber-500/20 text-amber-400',
                    'alpa'      => 'bg-rose-500/20 text-rose-300',
                    default     => 'bg-slate-500/20 text-slate-400',
                };
                ?>
                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold <?= $sc ?>"><?= ucfirst(e($attendance['status'])) ?></span>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-xs text-slate-400 mb-1">Masuk</p>
                    <p class="text-xl font-extrabold text-white font-mono"><?= e($attendance['jam_masuk'] ?? '—') ?></p>
                </div>
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-xs text-slate-400 mb-1">Keluar</p>
                    <p class="text-xl font-extrabold text-white font-mono"><?= e($attendance['jam_keluar'] ?? '—') ?></p>
                </div>
            </div>
            <?php if ($attendance && $attendance['jam_masuk'] && !$attendance['jam_keluar']): ?>
            <!-- Live timer -->
            <div class="mt-3 flex items-center gap-2 text-xs text-emerald-400">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                <span>Sedang bertugas · <span id="liveDuration"></span></span>
            </div>
            <?php endif; ?>

            <?php if ($kuotaLimit > 0): ?>
            <div class="mt-3 border-t border-white/10 pt-3 flex items-center justify-between text-xs">
                <span class="text-slate-400 flex items-center gap-1"><i data-lucide="users" class="h-3.5 w-3.5"></i> Slot masuk hari ini</span>
                <span class="font-bold <?= $isQuotaFull ? 'text-rose-400' : 'text-emerald-400' ?>"><?= $totalCheckedIn ?> / <?= $kuotaLimit ?> Karyawan</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pull up content -->
    <div class="-mt-10 relative z-10 px-5 space-y-4">
        <?php if (!$logbookMode && is_holiday($today)): ?>
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
            <strong>Hari libur.</strong> <?= holiday_message() ?>
        </div>
        <?php elseif (!$logbookMode && is_on_approved_leave((int) $user['id'], $today)): ?>
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-800">
            <strong>Izin/cuti disetujui.</strong> <?= leave_block_message() ?>
        </div>
        <?php endif; ?>

        <!-- Upah Live + CTA -->
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between p-4 pb-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Estimasi Upah Sekarang</p>
                    <p class="mt-1 text-3xl font-extrabold text-slate-900 font-outfit">
                        <?= $estimate ? rupiah($estimate['total']) : rupiah((int)($attendance['upah_final'] ?? 0)) ?>
                    </p>
                    <?php if ($estimate && isset($estimate['lembur']) && $estimate['lembur'] > 0): ?>
                    <p class="mt-0.5 text-xs text-purple-600 font-semibold">
                        + <?= rupiah($estimate['lembur']) ?> lembur
                    </p>
                    <?php endif; ?>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-lg shadow-amber-500/25">
                    <i data-lucide="wallet" class="h-7 w-7"></i>
                </div>
            </div>

            <?php if (!$attendance || !$attendance['jam_masuk']): ?>
                <?php if ($isQuotaFull): ?>
                <div class="flex flex-col items-center justify-center gap-2 bg-rose-50 border-t border-rose-100 p-5 text-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 text-rose-600 mb-1">
                        <i data-lucide="alert-triangle" class="h-5.5 w-5.5"></i>
                    </div>
                    <p class="text-sm font-extrabold text-rose-800 font-outfit">Kuota Absen Hari Ini Penuh</p>
                    <p class="text-[11px] text-rose-600 leading-normal max-w-xs">Slot masuk hari ini sudah <strong><?= $kuotaLimit ?> orang</strong>. Anda tidak dapat absen, tidak dihitung hadir, dan tidak mendapat upah.</p>
                </div>
                <?php else: ?>
                <a href="<?= route_url('karyawan.checkin') ?>"
                   class="flex w-full items-center justify-center gap-2 bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-4 text-sm font-bold text-white transition hover:opacity-90">
                    <i data-lucide="log-in" class="h-5 w-5"></i>
                    Absen Masuk Sekarang
                </a>
                <?php endif; ?>
            <?php elseif (!$attendance['jam_keluar']): ?>
            <a href="<?= route_url('karyawan.checkout') ?>"
               class="flex w-full items-center justify-center gap-2 bg-gradient-to-r from-rose-500 to-pink-500 px-4 py-4 text-sm font-bold text-white transition hover:opacity-90">
                <i data-lucide="log-out" class="h-5 w-5"></i>
                Absen Keluar
            </a>
            <?php else: ?>
            <div class="flex items-center justify-center gap-2 bg-emerald-50 px-4 py-3.5 text-sm font-semibold text-emerald-700">
                <i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-500"></i>
                Absensi hari ini sudah selesai 🎉
            </div>
            <?php endif; ?>
        </div>

        <!-- Wallet Summary -->
        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <i data-lucide="piggy-bank" class="h-5 w-5 text-emerald-500"></i>
                <h2 class="font-bold text-slate-900">Dompet Upah Saya</h2>
            </div>
            <?php if ($logbookMode): ?>
            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="rounded-xl bg-emerald-50 p-3">
                    <p class="text-xs text-emerald-600 mb-1">Total Estimasi Upah</p>
                    <p class="text-lg font-extrabold text-emerald-700 leading-tight"><?= rupiah((int)$wallet['total']) ?></p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-xs text-slate-400 mb-1">Hari Hadir Bulan Ini</p>
                    <p class="text-lg font-extrabold text-slate-900 leading-tight"><?= (int)$monthStats['hari'] ?> Hari</p>
                </div>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-xs text-slate-400 mb-1">Total Upah</p>
                    <p class="text-sm font-extrabold text-slate-900 leading-tight"><?= rupiah((int)$wallet['total']) ?></p>
                </div>
                <div class="rounded-xl bg-emerald-50 p-3">
                    <p class="text-xs text-emerald-500 mb-1">Terbayar</p>
                    <p class="text-sm font-extrabold text-emerald-700 leading-tight"><?= rupiah((int)$wallet['lunas']) ?></p>
                </div>
                <div class="rounded-xl bg-rose-50 p-3">
                    <p class="text-xs text-rose-500 mb-1">Pending</p>
                    <p class="text-sm font-extrabold text-rose-700 leading-tight"><?= rupiah((int)$wallet['belum']) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="grid <?= $logbookMode ? 'grid-cols-1' : 'grid-cols-2' ?> gap-3">
            <?php if (!$logbookMode): ?>
            <a href="<?= route_url('karyawan.leaves') ?>" class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-center text-sm font-bold text-indigo-700">
                Ajukan Izin/Cuti
            </a>
            <?php endif; ?>
            <a href="<?= route_url('karyawan.history') ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-bold text-slate-700">
                Lihat Riwayat Absensi
            </a>
        </div>

        <!-- This Month Stats -->
        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
            <div class="mb-3 flex items-center gap-2">
                <i data-lucide="trending-up" class="h-5 w-5 text-blue-500"></i>
                <h2 class="font-bold text-slate-900">Bulan <?= date('F Y') ?></h2>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                        <i data-lucide="calendar-check" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <p class="text-xl font-extrabold text-slate-900"><?= (int)$monthStats['hari'] ?></p>
                        <p class="text-xs text-slate-400">Hari Hadir</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-purple-100 bg-purple-50 p-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                        <i data-lucide="moon" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <p class="text-xl font-extrabold text-purple-900"><?= rupiah((int)$monthStats['lembur']) ?></p>
                        <p class="text-xs text-purple-400">Bonus Lembur</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Riwayat -->
        <?php if ($recentList): ?>
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-900">Riwayat Terbaru</h2>
                <a href="<?= route_url('karyawan.history') ?>" class="flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-900 transition">
                    Lihat semua <i data-lucide="arrow-right" class="h-3 w-3"></i>
                </a>
            </div>
            <div class="divide-y divide-slate-50">
                <?php foreach ($recentList as $r): ?>
                <?php
                $sc2 = match($r['status'] ?? '') {
                    'selesai'   => 'bg-emerald-100 text-emerald-700',
                    'bekerja'   => 'bg-blue-100 text-blue-700',
                    'terlambat' => 'bg-amber-100 text-amber-700',
                    'alpa'      => 'bg-rose-100 text-rose-700',
                    default     => 'bg-slate-100 text-slate-500',
                };
                ?>
                <div class="flex items-center justify-between px-5 py-3.5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full <?= ($logbookMode || $r['status_bayar']==='sudah_dibayar') ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' ?>">
                            <i data-lucide="<?= ($logbookMode || $r['status_bayar']==='sudah_dibayar') ? 'check' : 'clock' ?>" class="h-4 w-4"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800"><?= date('d M Y', strtotime($r['tanggal'])) ?></p>
                            <p class="text-xs text-slate-400"><?= e($r['jam_masuk']??'—') ?> – <?= e($r['jam_keluar']??'—') ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-extrabold text-slate-900"><?= rupiah((int)$r['upah_final']) ?></p>
                        <span class="text-[10px] font-bold <?= $sc2 ?> rounded-full px-2 py-0.5"><?= ucfirst(e($r['status']??'')) ?></span>
                        <?php if (!empty($r['jam_keluar'])): ?>
                        <a href="<?= base_url('api/export-slip.php?absensi_id=' . (int) $r['id']) ?>" target="_blank" class="mt-1 block text-[10px] font-semibold text-indigo-600 hover:text-indigo-800">Slip PDF</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Wage Logs Modal (Premium Glassmorphism Backdrop) -->
<div id="wageLogsModal" class="fixed inset-0 z-50 flex items-end justify-center hidden">
    <div onclick="closeWageLogsModal()" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"></div>
    <div class="relative z-10 w-full max-w-md rounded-t-3xl bg-white p-6 shadow-2xl transition-transform duration-300 translate-y-full border-t border-slate-100">
        <!-- Handle bar for swipe down look -->
        <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-slate-200"></div>

        <div class="mb-5 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-500">
                    <i data-lucide="history" class="h-5 w-5"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 font-outfit text-lg">Log Penyesuaian Gaji</h3>
                    <p class="text-xs text-slate-400">Riwayat pembaruan tarif & koreksi upah</p>
                </div>
            </div>
            <button onclick="closeWageLogsModal()" class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- Logs Container -->
        <div class="max-h-[60vh] overflow-y-auto space-y-3 pr-1">
            <?php if (empty($wageLogsList)): ?>
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 text-slate-400 mb-2">
                        <i data-lucide="info" class="h-6 w-6"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-500">Belum ada riwayat penyesuaian</p>
                </div>
            <?php else: ?>
                <?php foreach ($wageLogsList as $log): ?>
                    <?php
                    $isSystem = ($log['action'] === 'settings_update');
                    $badgeClass = $isSystem ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-purple-50 text-purple-600 border-purple-100';
                    $badgeText = $isSystem ? 'Sistem' : 'Koreksi Gaji';
                    // Format detail string to be cleaner
                    $logDetail = $log['detail'];
                    if ($isSystem) {
                        $logDetail = str_replace("Penyesuaian gaji: ", "", $logDetail);
                    } else {
                        if (preg_match('/Alasan:\s*(.*)$/i', $logDetail, $matches)) {
                            $alasanText = trim($matches[1]);
                            $logDetail = "Koreksi upah harian Anda. Alasan: \"{$alasanText}\"";
                        }
                    }
                    ?>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition hover:bg-slate-50">
                        <div class="flex items-center justify-between mb-2">
                            <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold <?= $badgeClass ?>"><?= $badgeText ?></span>
                            <span class="text-[10px] text-slate-400 font-medium"><?= date('d M Y H:i', strtotime($log['created_at'])) ?></span>
                        </div>
                        <p class="text-xs text-slate-700 leading-normal font-medium"><?= e($logDetail) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/employee_nav.php'; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>

<script>
<?php if ($attendance && $attendance['jam_masuk'] && !$attendance['jam_keluar']): ?>
const masukTime = new Date('<?= $today ?> <?= $attendance['jam_masuk'] ?>');
function updateDuration() {
    const now = new Date();
    const diff = Math.floor((now - masukTime) / 1000);
    const h = Math.floor(diff / 3600);
    const m = Math.floor((diff % 3600) / 60);
    const s = diff % 60;
    const el = document.getElementById('liveDuration');
    if (el) el.textContent = `${h}j ${m}m ${s}s`;
}
updateDuration();
setInterval(updateDuration, 1000);
<?php endif; ?>

function openWageLogsModal() {
    const modal = document.getElementById('wageLogsModal');
    const panel = modal.querySelector('.relative.z-10');
    modal.classList.remove('hidden');
    setTimeout(() => {
        panel.classList.remove('translate-y-full');
    }, 10);
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closeWageLogsModal() {
    const modal = document.getElementById('wageLogsModal');
    const panel = modal.querySelector('.relative.z-10');
    panel.classList.add('translate-y-full');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
</script>
