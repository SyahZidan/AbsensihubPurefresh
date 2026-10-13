<?php
$title = 'Status Pembayaran';
$active = 'admin.payment';

$search  = trim($_GET['q'] ?? '');
$status  = $_GET['status'] ?? 'belum'; // default show unpaid
$bulan   = $_GET['bulan'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';
$sort    = $_GET['sort'] ?? 'tanggal_desc';

// Default ke bulan ini jika pertama kali akses dan filter kosong (khusus status lunas atau semua)
// Untuk status 'belum' (belum dibayar), kita biarkan kosong agar menampilkan semua tagihan yang belum dibayar dari bulan-bulan lalu.
if (!isset($_GET['bulan']) && !isset($_GET['tanggal']) && !isset($_GET['q'])) {
    if ($status !== 'belum') {
        $bulan = date('Y-m');
    }
}

$params = [];
$where  = "WHERE a.upah_final > 0";

if ($search !== '') {
    $where .= " AND u.nama LIKE ?";
    $params[] = "%{$search}%";
}
if ($status !== 'all') {
    $where .= " AND a.status_bayar = ?";
    $params[] = $status === 'belum' ? 'belum_dibayar' : ($status === 'lunas' ? 'sudah_dibayar' : $status);
}
if ($tanggal !== '') {
    $where .= " AND DATE(a.tanggal) = ?";
    $params[] = $tanggal;
} elseif ($bulan !== '') {
    $where .= " AND DATE_FORMAT(a.tanggal,'%Y-%m') = ?";
    $params[] = $bulan;
}

$order_by = "a.tanggal DESC";
if ($sort === 'nama_asc') {
    $order_by = "u.nama ASC";
} elseif ($sort === 'nama_desc') {
    $order_by = "u.nama DESC";
} elseif ($sort === 'tanggal_asc') {
    $order_by = "a.tanggal ASC";
} elseif ($sort === 'upah_desc') {
    $order_by = "a.upah_final DESC";
} elseif ($sort === 'upah_asc') {
    $order_by = "a.upah_final ASC";
}

$stmt = db()->prepare("SELECT a.*, u.nama, u.no_hp, u.bank_name, u.norek FROM absensi a JOIN users u ON u.id=a.user_id {$where} ORDER BY {$order_by} LIMIT 300");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$sumFiltered = 0;
foreach ($rows as $r) {
    $sumFiltered += (int)$r['upah_final'];
}

$where_belum = "WHERE status_bayar='belum_dibayar' AND upah_final>0";
$where_lunas = "WHERE status_bayar='sudah_dibayar' AND upah_final>0";
$params_belum = [];
$params_lunas = [];

if ($tanggal !== '') {
    $where_belum .= " AND DATE(tanggal) = ?";
    $where_lunas .= " AND DATE(tanggal) = ?";
    $params_belum[] = $tanggal;
    $params_lunas[] = $tanggal;
} elseif ($bulan !== '') {
    $where_belum .= " AND DATE_FORMAT(tanggal,'%Y-%m') = ?";
    $where_lunas .= " AND DATE_FORMAT(tanggal,'%Y-%m') = ?";
    $params_belum[] = $bulan;
    $params_lunas[] = $bulan;
}

$total_belum = db()->prepare("SELECT COALESCE(SUM(upah_final),0) AS n FROM absensi {$where_belum}");
$total_belum->execute($params_belum);
$total_belum = $total_belum->fetch()['n'] ?? 0;

$total_lunas = db()->prepare("SELECT COALESCE(SUM(upah_final),0) AS n FROM absensi {$where_lunas}");
$total_lunas->execute($params_lunas);
$total_lunas = $total_lunas->fetch()['n'] ?? 0;

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
                <h1 class="text-xl font-extrabold text-slate-900 font-outfit">Status Pembayaran Upah</h1>
            </div>
        </div>
        <?php if ($status === 'belum' && count($rows) > 0): ?>
        <button id="btnBulkPay" onclick="bulkPay()"
            class="flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-600">
            <i data-lucide="check-check" class="h-4 w-4"></i>
            <span>Bayar Semua (<?= count($rows) ?>)</span>
        </button>
        <?php endif; ?>
    </div>

    <div class="p-5 lg:p-8">
        <!-- Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 mb-6">
            <div class="flex items-center gap-4 rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm cursor-pointer transition hover:shadow-md" onclick="setStatus('belum')">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500 text-white shadow-lg shadow-rose-500/25">
                    <i data-lucide="clock" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-400">Belum Dibayar</p>
                    <p class="text-3xl font-extrabold text-rose-700 font-outfit"><?= rupiah((int)$total_belum) ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm cursor-pointer transition hover:shadow-md" onclick="setStatus('lunas')">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/25">
                    <i data-lucide="check-circle" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Sudah Dibayar</p>
                    <p class="text-3xl font-extrabold text-emerald-700 font-outfit"><?= rupiah((int)$total_lunas) ?></p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <form method="get" class="flex flex-wrap gap-3 mb-5" id="filterForm">
            <input type="hidden" name="page" value="admin.payment">
            <input type="hidden" name="status" id="statusInput" value="<?= e($status) ?>">
            <div class="flex flex-1 min-w-[180px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
                <i data-lucide="search" class="h-4 w-4 shrink-0 text-slate-400"></i>
                <input name="q" value="<?= e($search) ?>" placeholder="Cari nama karyawan..." class="w-full bg-transparent text-sm outline-none">
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
                <i data-lucide="calendar" class="h-4 w-4 text-slate-400"></i>
                <span class="text-xs font-semibold text-slate-400">Bulan:</span>
                <input type="month" name="bulan" id="bulanInput" value="<?= e($bulan) ?>" onchange="if(this.value) document.getElementById('tanggalInput').value=''" class="bg-transparent text-sm outline-none text-slate-700">
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
                <i data-lucide="calendar" class="h-4 w-4 text-slate-400"></i>
                <span class="text-xs font-semibold text-slate-400">Tanggal:</span>
                <input type="date" name="tanggal" id="tanggalInput" value="<?= e($tanggal) ?>" onchange="if(this.value) document.getElementById('bulanInput').value=''" class="bg-transparent text-sm outline-none text-slate-700">
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
                <i data-lucide="arrow-up-down" class="h-4 w-4 text-slate-400"></i>
                <span class="text-xs font-semibold text-slate-400">Urutan:</span>
                <select name="sort" id="sortSelect" onchange="document.getElementById('filterForm').submit()" class="bg-transparent text-sm outline-none text-slate-700 font-semibold cursor-pointer">
                    <option value="tanggal_desc" <?= $sort === 'tanggal_desc' ? 'selected' : '' ?>>Tanggal (Terbaru)</option>
                    <option value="tanggal_asc" <?= $sort === 'tanggal_asc' ? 'selected' : '' ?>>Tanggal (Terlama)</option>
                    <option value="nama_asc" <?= $sort === 'nama_asc' ? 'selected' : '' ?>>Nama Karyawan (A-Z)</option>
                    <option value="nama_desc" <?= $sort === 'nama_desc' ? 'selected' : '' ?>>Nama Karyawan (Z-A)</option>
                    <option value="upah_desc" <?= $sort === 'upah_desc' ? 'selected' : '' ?>>Upah (Terbanyak)</option>
                    <option value="upah_asc" <?= $sort === 'upah_asc' ? 'selected' : '' ?>>Upah (Tersedikit)</option>
                </select>
            </div>
            <div class="flex overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm text-sm">
                <button type="button" onclick="setStatus('belum')" class="px-4 py-2.5 font-semibold transition <?= $status==='belum' ? 'bg-rose-500 text-white' : 'text-slate-500 hover:bg-slate-50' ?>">Belum</button>
                <button type="button" onclick="setStatus('lunas')" class="px-4 py-2.5 font-semibold transition <?= $status==='lunas' ? 'bg-emerald-500 text-white' : 'text-slate-500 hover:bg-slate-50' ?>">Lunas</button>
                <button type="button" onclick="setStatus('all')"   class="px-4 py-2.5 font-semibold transition <?= $status==='all'   ? 'bg-slate-800 text-white'  : 'text-slate-500 hover:bg-slate-50' ?>">Semua</button>
            </div>
            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-slate-700">Filter</button>
            <?php if ($search !== '' || $bulan !== '' || $tanggal !== ''): ?>
            <a href="?page=admin.payment&status=<?= e($status) ?>" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 flex items-center gap-1.5 shadow-sm">
                <i data-lucide="rotate-ccw" class="h-4 w-4"></i> Reset
            </a>
            <?php endif; ?>
        </form>

        <!-- Payment Table -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-xs uppercase tracking-wider text-slate-400">
                            <?php if ($status !== 'lunas'): ?>
                            <th class="px-5 py-3.5"><input type="checkbox" id="selectAll" onchange="toggleAll(this)" class="h-4 w-4 cursor-pointer rounded accent-emerald-500"></th>
                            <?php endif; ?>
                             <th class="px-5 py-3.5 font-semibold cursor-pointer select-none hover:text-slate-600" onclick="toggleSort('nama')">
                                 <div class="flex items-center gap-1">
                                     <span>Karyawan</span>
                                     <?php if ($sort === 'nama_asc'): ?>
                                         <i data-lucide="arrow-up-narrow-wide" class="h-4 w-4"></i>
                                     <?php elseif ($sort === 'nama_desc'): ?>
                                         <i data-lucide="arrow-down-wide-narrow" class="h-4 w-4"></i>
                                     <?php else: ?>
                                         <i data-lucide="chevrons-up-down" class="h-3.5 w-3.5 text-slate-300"></i>
                                     <?php endif; ?>
                                 </div>
                             </th>
                             <th class="px-4 py-3.5 font-semibold">Rekening / E-Wallet</th>
                             <th class="px-4 py-3.5 font-semibold cursor-pointer select-none hover:text-slate-600" onclick="toggleSort('tanggal')">
                                 <div class="flex items-center gap-1">
                                     <span>Tanggal</span>
                                     <?php if ($sort === 'tanggal_asc'): ?>
                                         <i data-lucide="arrow-up-narrow-wide" class="h-4 w-4"></i>
                                     <?php elseif ($sort === 'tanggal_desc'): ?>
                                         <i data-lucide="arrow-down-wide-narrow" class="h-4 w-4"></i>
                                     <?php else: ?>
                                         <i data-lucide="chevrons-up-down" class="h-3.5 w-3.5 text-slate-300"></i>
                                     <?php endif; ?>
                                 </div>
                             </th>
                            <th class="px-4 py-3.5 font-semibold">Shift</th>
                            <th class="px-4 py-3.5 font-semibold text-right">Upah</th>
                            <th class="px-4 py-3.5 font-semibold text-center">Status</th>
                            <th class="px-4 py-3.5 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50" id="paymentTableBody">
                        <?php foreach ($rows as $row): ?>
                        <tr class="group transition-colors hover:bg-slate-50/60" id="row-<?= (int)$row['id'] ?>">
                            <?php if ($status !== 'lunas'): ?>
                            <td class="px-5 py-3.5">
                                <input type="checkbox" name="ids[]" value="<?= (int)$row['id'] ?>" class="pay-checkbox h-4 w-4 cursor-pointer rounded accent-emerald-500">
                            </td>
                            <?php endif; ?>
                            <td class="px-5 py-3.5">
                                 <div class="flex items-center gap-2.5">
                                     <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-slate-300 to-slate-400 text-xs font-bold text-white">
                                         <?= strtoupper(substr($row['nama'],0,1)) ?>
                                     </div>
                                      <div>
                                          <p class="font-semibold"><a href="<?= route_url('admin.employee-detail', ['id' => $row['user_id']]) ?>&from=payment&bulan=<?= e($bulan) ?>" class="text-slate-800 hover:text-emerald-600 transition-colors"><?= e($row['nama']) ?></a></p>
                                          <p class="text-xs text-slate-400"><?= e($row['no_hp']) ?></p>
                                          <?php if (empty($row['jam_keluar']) && $row['tanggal'] < date('Y-m-d')): ?>
                                          <span class="inline-flex items-center gap-1 rounded bg-rose-50 px-1.5 py-0.5 text-[9px] font-bold text-rose-600 border border-rose-100 mt-1">
                                              <i data-lucide="alert-triangle" class="h-3 w-3"></i> Lupa Absen Keluar
                                          </span>
                                          <?php elseif (!empty($row['keterangan_keluar_awal']) && (str_contains($row['keterangan_keluar_awal'], 'Auto Checkout') || str_contains($row['keterangan_keluar_awal'], 'Lupa Keluar'))): ?>
                                          <span class="inline-flex items-center gap-1 rounded bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold text-blue-600 border border-blue-100 mt-1">
                                              <i data-lucide="cpu" class="h-3 w-3"></i> Lupa Keluar
                                          </span>
                                          <?php endif; ?>
                                      </div>
                                 </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center justify-center rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">
                                        <?= e($row['bank_name'] ?? '—') ?>
                                    </span>
                                    <span class="font-mono text-slate-800 cursor-pointer hover:text-emerald-600 transition-colors relative font-semibold"
                                          onclick="copyText('<?= e($row['norek'] ?? '') ?>', this)"
                                          title="Klik untuk salin no rekening">
                                        <?= e($row['norek'] ?? '—') ?>
                                    </span>
                                </div>
                            </td>
                             <td class="px-4 py-3.5 text-slate-600"><?= format_tanggal_hari($row['tanggal']) ?></td>
                            <td class="px-4 py-3.5 text-xs text-slate-500">
                                <span class="font-mono"><?= e($row['jam_masuk'] ?? '—') ?></span>
                                <?php if ($row['jam_keluar']): ?> – <span class="font-mono"><?= e($row['jam_keluar']) ?></span><?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-slate-900 cursor-pointer hover:text-emerald-600 transition-colors relative"
                                 onclick="copyText(<?= (int)$row['upah_final'] ?>, this)"
                                 title="Klik untuk salin upah">
                                 <?= rupiah((int)$row['upah_final']) ?>
                             </td>
                            <td class="px-4 py-3.5 text-center">
                                <?php if ($row['status_bayar'] === 'sudah_dibayar'): ?>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-bold text-emerald-700">
                                    <i data-lucide="check" class="h-3 w-3"></i> Lunas
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-3 py-0.5 text-xs font-bold text-rose-600">
                                    <i data-lucide="clock" class="h-3 w-3"></i> Belum
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <?php if ($row['status_bayar'] !== 'sudah_dibayar'): ?>
                                <button onclick="markPaid(<?= (int)$row['id'] ?>, this)"
                                    class="rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white shadow transition hover:bg-emerald-600">
                                    Tandai Lunas
                                </button>
                                <?php else: ?>
                                <span class="text-slate-300 text-xs">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$rows): ?>
                        <tr><td colspan="8" class="py-14 text-center text-slate-400">
                            <i data-lucide="inbox" class="mx-auto mb-3 h-10 w-10 text-slate-200"></i>
                            <p class="font-semibold">Tidak ada data pembayaran</p>
                        </td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if ($rows): ?>
                    <tfoot>
                        <tr class="bg-slate-50 font-bold border-t-2 border-slate-200">
                            <?php if ($status !== 'lunas'): ?>
                            <td class="px-5 py-4"></td>
                            <?php endif; ?>
                            <td class="px-5 py-4 text-slate-700" colspan="2">Total Akumulasi Terfilter</td>
                            <td class="px-4 py-4 text-slate-500"></td>
                            <td class="px-4 py-4"></td>
                            <td class="px-4 py-4 text-right text-emerald-600 font-extrabold text-base">
                                <?= rupiah($sumFiltered) ?>
                            </td>
                            <td class="px-4 py-4"></td>
                            <td class="px-4 py-4"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Bulk Action Bar -->
        <div id="bulkBar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 hidden items-center gap-3 rounded-2xl bg-slate-900 px-6 py-4 shadow-2xl text-white text-sm">
            <span id="selectedCount" class="font-semibold">0 dipilih</span>
            <button onclick="bulkPay()" class="rounded-xl bg-emerald-500 px-5 py-2 font-bold text-white transition hover:bg-emerald-400">
                Bayar Semua Terpilih
            </button>
        </div>
    </div>
</main>

<script>
function copyText(val, element) {
    if (!val || val === '—') return;
    navigator.clipboard.writeText(val).then(() => {
        const tooltip = document.createElement('span');
        tooltip.innerText = 'Tersalin!';
        tooltip.className = 'absolute bottom-full left-1/2 -translate-x-1/2 mb-1 rounded bg-slate-900 px-2 py-1 text-[10px] font-semibold text-white shadow-md transition-all opacity-100 z-50 pointer-events-none';
        
        element.style.position = 'relative';
        element.appendChild(tooltip);
        
        setTimeout(() => {
            tooltip.style.opacity = '0';
            setTimeout(() => tooltip.remove(), 200);
        }, 1000);
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}

function setStatus(val) {
    document.getElementById('statusInput').value = val;
    document.getElementById('filterForm').submit();
}

function toggleSort(col) {
    const sortSelect = document.getElementById('sortSelect');
    const current = sortSelect.value;
    if (col === 'nama') {
        sortSelect.value = current === 'nama_asc' ? 'nama_desc' : 'nama_asc';
    } else if (col === 'tanggal') {
        sortSelect.value = current === 'tanggal_desc' ? 'tanggal_asc' : 'tanggal_desc';
    }
    document.getElementById('filterForm').submit();
}

function toggleAll(cb) {
    document.querySelectorAll('.pay-checkbox').forEach(c => c.checked = cb.checked);
    updateBulkBar();
}

document.querySelectorAll('.pay-checkbox').forEach(cb => cb.addEventListener('change', updateBulkBar));

function updateBulkBar() {
    const checked = document.querySelectorAll('.pay-checkbox:checked');
    const bar = document.getElementById('bulkBar');
    document.getElementById('selectedCount').textContent = checked.length + ' dipilih';
    if (checked.length > 0) { bar.classList.remove('hidden'); bar.classList.add('flex'); }
    else { bar.classList.add('hidden'); bar.classList.remove('flex'); }
}

async function markPaid(id, btn) {
    const ok = await AppAlert.confirm('Tandai sebagai sudah dibayar?');
    if (!ok) return;
    btn.disabled = true;
    btn.textContent = '...';
    try {
        const r = await fetch('<?= base_url('api/update-payment.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ absensi_id: id, status: 'lunas' }),
        });
        const d = await r.json();
        if (d.success) {
            const row = document.getElementById('row-' + id);
            row.style.opacity = '0';
            row.style.transition = 'opacity 0.3s';
            setTimeout(() => row.remove(), 300);
        } else {
            btn.disabled = false;
            btn.textContent = 'Tandai Lunas';
            await AppAlert.error(d.message);
        }
    } catch (e) {
        btn.disabled = false;
        btn.textContent = 'Tandai Lunas';
        await AppAlert.error('Terjadi kesalahan.');
    }
}

async function bulkPay() {
    const ids = Array.from(document.querySelectorAll('.pay-checkbox:checked')).map(c => parseInt(c.value, 10));
    if (!ids.length) return;
    const ok = await AppAlert.confirm('Tandai ' + ids.length + ' record sebagai lunas?');
    if (!ok) return;

    const bulkBtn = document.getElementById('btnBulkPay');
    if (bulkBtn) bulkBtn.disabled = true;

    try {
        const r = await fetch('<?= base_url('api/update-payment.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ absensi_ids: ids, status: 'lunas' }),
        });
        const d = await r.json();
        if (d.success) {
            location.reload();
        } else {
            if (bulkBtn) bulkBtn.disabled = false;
            await AppAlert.error(d.message || 'Gagal');
        }
    } catch (e) {
        if (bulkBtn) bulkBtn.disabled = false;
        await AppAlert.error('Terjadi kesalahan.');
    }
}
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
