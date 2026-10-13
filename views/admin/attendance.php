<?php
$title = 'Monitoring Absensi';
$active = 'admin.attendance';

require_once __DIR__ . '/../../includes/quota.php';
require_once __DIR__ . '/../../includes/pagination.php';
$quotaToday = daily_quota_status();

$search   = trim($_GET['q'] ?? '');
$tanggal  = $_GET['tanggal'] ?? '';
$kid      = (int)($_GET['karyawan_id'] ?? 0);
$page     = (int)($_GET['p'] ?? 1);
$pag = paginate_params($page, 25);

$where  = "WHERE 1=1";
$params = [];

if ($search !== '') {
    $where .= " AND u.nama LIKE ?";
    $params[] = "%{$search}%";
}
if ($tanggal !== '') {
    $where .= " AND a.tanggal = ?";
    $params[] = $tanggal;
}
if ($kid > 0) {
    $where .= " AND a.user_id = ?";
    $params[] = $kid;
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM absensi a JOIN users u ON u.id = a.user_id {$where}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$stmt = db()->prepare("SELECT a.*, u.nama, u.no_hp
    FROM absensi a
    JOIN users u ON u.id = a.user_id
    {$where}
    ORDER BY a.tanggal DESC, a.jam_masuk DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$paginationQuery = array_filter(['q' => $search, 'tanggal' => $tanggal, 'karyawan_id' => $kid ?: null]);
$paginationHtml = pagination_links($totalRows, $pag['page'], $pag['per_page'], 'admin.attendance', $paginationQuery);

// Quick summary for filtered result
$total_hadir  = count(array_filter($rows, fn($r) => $r['jam_masuk']));
$total_upah   = array_sum(array_column($rows, 'upah_final'));

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
                <h1 class="text-xl font-extrabold text-slate-900 font-outfit">Monitoring Absensi</h1>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= base_url('api/export-excel.php') ?>?<?= http_build_query(array_filter(['q'=>$search,'tanggal'=>$tanggal,'karyawan_id'=>$kid?:null])) ?>"
               class="flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-600">
                <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
                <span>Export Excel</span>
            </a>
            <a href="<?= base_url('api/export-csv.php') ?>?<?= http_build_query(array_filter(['q'=>$search,'tanggal'=>$tanggal,'karyawan_id'=>$kid?:null])) ?>"
               class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <i data-lucide="file-text" class="h-4 w-4"></i>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    <div class="p-5 lg:p-8">
        <!-- Filters -->
        <form method="get" class="flex flex-wrap gap-3">
            <input type="hidden" name="page" value="admin.attendance">
            <div class="flex flex-1 min-w-[200px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
                <i data-lucide="search" class="h-4 w-4 shrink-0 text-slate-400"></i>
                <input name="q" value="<?= e($search) ?>" placeholder="Cari nama karyawan..." class="w-full bg-transparent text-sm outline-none placeholder:text-slate-400">
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
                <i data-lucide="calendar" class="h-4 w-4 text-slate-400"></i>
                <input type="date" name="tanggal" value="<?= e($tanggal) ?>" class="bg-transparent text-sm outline-none text-slate-700">
            </div>
            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-slate-700">
                Filter
            </button>
            <?php if ($search || $tanggal || $kid): ?>
            <a href="<?= route_url('admin.attendance') ?>" class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 shadow-sm transition hover:bg-slate-50">
                <i data-lucide="x" class="h-4 w-4"></i> Reset
            </a>
            <?php endif; ?>
        </form>

        <!-- Summary Chips -->
        <div class="mt-4 flex flex-wrap gap-3">
            <?php if (!is_logbook_mode() && !$quotaToday['unlimited']): ?>
            <div class="flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm">
                <i data-lucide="users" class="h-4 w-4 text-indigo-500"></i>
                <span class="font-semibold text-indigo-700">Kuota hari ini: <?= $quotaToday['used'] ?> / <?= $quotaToday['limit'] ?> terisi</span>
                <?php if ($quotaToday['is_full']): ?>
                <span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">PENUH</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 text-sm">
                <i data-lucide="check-circle" class="h-4 w-4 text-emerald-500"></i>
                <span class="font-semibold text-emerald-700"><?= $total_hadir ?> Record Hadir</span>
            </div>
            <div class="flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-1.5 text-sm">
                <i data-lucide="coins" class="h-4 w-4 text-amber-500"></i>
                <span class="font-semibold text-amber-700">Total <?= rupiah((int)$total_upah) ?></span>
            </div>
        </div>

        <!-- Table -->
        <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-xs uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3 font-semibold">Karyawan</th>
                            <th class="px-4 py-3 font-semibold">Tanggal</th>
                            <th class="px-4 py-3 font-semibold">Masuk</th>
                            <th class="px-4 py-3 font-semibold">Keluar</th>
                            <th class="px-4 py-3 font-semibold">Durasi</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Lembur</th>
                            <th class="px-4 py-3 font-semibold">Potongan</th>
                            <th class="px-4 py-3 font-semibold">Upah</th>
                            <th class="px-4 py-3 font-semibold">Lokasi</th>
                            <th class="px-4 py-3 font-semibold">Foto</th>
                            <th class="px-4 py-3 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($rows as $row): ?>
                        <?php
                        // Calculate duration
                        $dur = '';
                        if ($row['jam_masuk'] && $row['jam_keluar']) {
                            $diff = strtotime($row['jam_keluar']) - strtotime($row['jam_masuk']);
                            $h = floor($diff/3600); $m = floor(($diff%3600)/60);
                            $dur = "{$h}j {$m}m";
                        }
                        $statusColor = match($row['status'] ?? '') {
                            'bekerja'   => 'bg-blue-100 text-blue-700',
                            'selesai'   => 'bg-emerald-100 text-emerald-700',
                            'terlambat' => 'bg-amber-100 text-amber-700',
                            'alpa'      => 'bg-rose-100 text-rose-700',
                            default     => 'bg-slate-100 text-slate-500',
                        };
                        ?>
                        <tr class="transition-colors hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-slate-200 to-slate-300 text-xs font-bold text-slate-600">
                                        <?= strtoupper(substr($row['nama'],0,1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800 leading-tight"><?= e($row['nama']) ?></p>
                                        <p class="text-[10px] text-slate-400"><?= e($row['no_hp']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?= format_tanggal_hari($row['tanggal']) ?></td>
                            <td class="px-4 py-3 font-mono text-slate-700"><?= e($row['jam_masuk'] ?? '—') ?></td>
                            <td class="px-4 py-3 font-mono text-slate-700">
                                <?= e($row['jam_keluar'] ?? '—') ?>
                                <?php if (!empty($row['keterangan_keluar_awal'])): ?>
                                    <?php
                                    $isAuto = str_contains($row['keterangan_keluar_awal'], 'Auto Checkout') || str_contains($row['keterangan_keluar_awal'], 'Lupa Keluar');
                                    $btnLabel = $isAuto ? 'Info · lihat' : 'Keluar awal · lihat';
                                    $modalTitle = $isAuto ? 'Sistem Lupa Keluar' : 'Keluar Lebih Awal';
                                    ?>
                                    <button type="button" onclick="showEarlyCheckout(<?= htmlspecialchars(json_encode([
                                        'nama' => $row['nama'],
                                        'jam' => $row['jam_keluar'],
                                        'keterangan' => $row['keterangan_keluar_awal'],
                                        'title' => $modalTitle,
                                        'bukti' => !empty($row['bukti_keluar_awal']) ? base_url($row['bukti_keluar_awal']) : '',
                                    ]), ENT_QUOTES, 'UTF-8') ?>)"
                                        class="mt-1 block text-[10px] font-bold <?= $isAuto ? 'text-blue-500 hover:text-blue-700' : 'text-amber-600 hover:text-amber-800' ?> hover:underline"><?= $btnLabel ?></button>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs"><?= $dur ?: '—' ?></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $statusColor ?>"><?= e($row['status'] ?? '—') ?></span>
                            </td>
                            <td class="px-4 py-3 text-purple-600 font-semibold"><?= $row['lembur'] > 0 ? rupiah((int)$row['lembur']) : '—' ?></td>
                            <td class="px-4 py-3 text-rose-500"><?= $row['potongan'] > 0 ? rupiah((int)$row['potongan']) : '—' ?></td>
                            <td class="px-4 py-3 font-bold text-slate-900"><?= rupiah((int)$row['upah_final']) ?></td>
                            <td class="px-4 py-3">
                                <?php if ($row['lat_masuk'] && $row['lng_masuk']): ?>
                                <a href="https://maps.google.com/?q=<?= e($row['lat_masuk']) ?>,<?= e($row['lng_masuk']) ?>" target="_blank"
                                   class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-100 transition">
                                     <i data-lucide="map-pin" class="h-3 w-3"></i> Maps
                                </a>
                                <?php else: ?><span class="text-slate-300">—</span><?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($row['foto_masuk'] ?? null): ?>
                                <button onclick="showPhoto('<?= base_url(e($row['foto_masuk'])) ?>', '<?= base_url(e($row['foto_keluar'] ?? '')) ?>')"
                                    class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition">
                                    <i data-lucide="camera" class="h-3 w-3"></i> Lihat
                                </button>
                                <?php else: ?><span class="text-slate-300">—</span><?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($row['jam_masuk'] && $row['jam_keluar']): ?>
                                <button onclick="openEditWageModal(<?= htmlspecialchars(json_encode([
                                    'id' => $row['id'],
                                    'nama' => $row['nama'],
                                    'tanggal' => date('d M Y', strtotime($row['tanggal'])),
                                    'upah_final' => (int) $row['upah_final'],
                                    'lembur' => (int) $row['lembur'],
                                    'potongan' => (int) $row['potongan'],
                                    'alasan_edit_upah' => $row['alasan_edit_upah'] ?? '',
                                ]), ENT_QUOTES, 'UTF-8') ?>)"
                                    class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600 hover:bg-emerald-500 hover:text-white shadow-sm transition">
                                    <i data-lucide="edit-3" class="h-3 w-3"></i> Edit Gaji
                                </button>
                                <?php else: ?>
                                <span class="text-slate-300">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$rows): ?>
                        <tr><td colspan="12" class="px-6 py-14 text-center text-slate-400">
                            <i data-lucide="calendar-x" class="mx-auto mb-3 h-10 w-10 text-slate-200"></i>
                            <p class="font-semibold">Tidak ada data absensi</p>
                        </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?= $paginationHtml ?>
        <p class="mt-2 text-center text-xs text-slate-400">Menampilkan <?= count($rows) ?> dari <?= $totalRows ?> record</p>
    </div>
</main>

<!-- Photo Modal -->
<div id="photoModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/70 backdrop-blur-sm" onclick="this.classList.add('hidden');this.classList.remove('flex')">
    <div class="relative mx-4 flex max-w-2xl w-full gap-3" onclick="event.stopPropagation()">
        <div class="flex-1" id="photoMasukWrap">
            <p class="mb-2 text-center text-xs font-bold uppercase tracking-wider text-white/60">Foto Masuk</p>
            <img id="photoMasuk" src="" class="w-full rounded-2xl object-cover shadow-2xl" onerror="this.parentElement.classList.add('hidden')">
        </div>
        <div class="flex-1" id="photoKeluarWrap">
            <p class="mb-2 text-center text-xs font-bold uppercase tracking-wider text-white/60">Foto Keluar</p>
            <img id="photoKeluar" src="" class="w-full rounded-2xl object-cover shadow-2xl" onerror="this.parentElement.classList.add('hidden')">
        </div>
        <button onclick="document.getElementById('photoModal').classList.add('hidden');document.getElementById('photoModal').classList.remove('flex')"
            class="absolute -top-4 -right-4 flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-xl hover:bg-slate-100">
            <i data-lucide="x" class="h-5 w-5 text-slate-800"></i>
        </button>
    </div>
</div>

<!-- Edit Wage Modal -->
<div id="editWageModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/40 backdrop-blur-sm p-4" onclick="closeEditWageModal(event)">
    <div class="relative w-full max-w-md transform rounded-3xl border border-white/10 bg-white/95 p-6 shadow-2xl backdrop-blur-xl transition-all" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-500 font-outfit">Koreksi Gaji Karyawan</p>
                <h3 id="modalEmployeeName" class="text-lg font-extrabold text-slate-950 font-outfit">Nama Karyawan</h3>
                <p id="modalAttendanceDate" class="text-xs text-slate-400 mt-0.5">Tanggal</p>
            </div>
            <button onclick="document.getElementById('editWageModal').classList.add('hidden');document.getElementById('editWageModal').classList.remove('flex')" class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- Form -->
        <form id="editWageForm" onsubmit="submitEditWage(event)" class="mt-4 space-y-4">
            <input type="hidden" name="absensi_id" id="editWageAbsensiId">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Upah Pokok / Total Bersih (Rp)</label>
                <div class="flex items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition">
                    <span class="bg-white border-r border-slate-200 px-3 py-2.5 text-xs text-slate-400 font-bold">Rp</span>
                    <input type="number" name="upah_final" id="editWageUpahFinal" required min="0" class="flex-1 bg-transparent px-3 py-2.5 text-sm outline-none text-slate-800 font-bold">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Lembur (Rp)</label>
                    <div class="flex items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition">
                        <span class="bg-white border-r border-slate-200 px-2 py-2 text-xs text-slate-400">Rp</span>
                        <input type="number" name="lembur" id="editWageLembur" required min="0" class="flex-1 bg-transparent px-2 py-2 text-xs outline-none text-slate-800">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Denda Potongan (Rp)</label>
                    <div class="flex items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition">
                        <span class="bg-white border-r border-slate-200 px-2 py-2 text-xs text-slate-400">Rp</span>
                        <input type="number" name="potongan" id="editWagePotongan" required min="0" class="flex-1 bg-transparent px-2 py-2 text-xs outline-none text-slate-800">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Alasan Koreksi <span class="text-rose-500">*</span></label>
                <textarea name="alasan_edit_upah" id="editWageAlasan" required placeholder="Masukkan alasan mengapa gaji diubah (misal: penyesuaian orderan ramai / denda dispensasi / bonus dll)..." rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition text-slate-800"></textarea>
                <p class="mt-1 text-[10px] text-slate-400">Alasan ini wajib diisi dan akan terlihat di dashboard karyawan & slip PDF mereka.</p>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="editWageSubmitBtn" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 transition hover:opacity-90">
                <i data-lucide="save" class="h-4 w-4"></i> Simpan Perubahan
            </button>
        </form>
    </div>
</div>

<script>
function showEarlyCheckout(data) {
    const buktiHtml = data.bukti
        ? `<img src="${data.bukti}" class="mt-3 w-full rounded-xl border border-slate-200" alt="Bukti">`
        : '<p class="mt-2 text-xs text-slate-400">Tidak ada foto bukti.</p>';
    Swal.fire({
        title: data.title || 'Keluar Lebih Awal',
        html: `<p class="text-sm text-slate-600"><strong>${data.nama}</strong> · ${data.jam || '-'}</p>
               <p class="mt-3 text-left text-sm text-slate-800">${(data.keterangan || '').replace(/</g, '&lt;')}</p>${buktiHtml}`,
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#10b981',
        width: 480,
    });
}

function showPhoto(masuk, keluar) {
    document.getElementById('photoMasuk').src = masuk;
    document.getElementById('photoKeluar').src = keluar;
    document.getElementById('photoMasukWrap').classList.remove('hidden');
    document.getElementById('photoKeluarWrap').classList.remove('hidden');
    const m = document.getElementById('photoModal');
    m.classList.remove('hidden'); m.classList.add('flex');
}

function openEditWageModal(data) {
    document.getElementById('editWageAbsensiId').value = data.id;
    document.getElementById('modalEmployeeName').innerText = data.nama;
    document.getElementById('modalAttendanceDate').innerText = data.tanggal;
    document.getElementById('editWageUpahFinal').value = data.upah_final;
    document.getElementById('editWageLembur').value = data.lembur;
    document.getElementById('editWagePotongan').value = data.potongan;
    document.getElementById('editWageAlasan').value = data.alasan_edit_upah || '';

    const modal = document.getElementById('editWageModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditWageModal(event) {
    if (event === null || event.target === document.getElementById('editWageModal')) {
        document.getElementById('editWageModal').classList.add('hidden');
        document.getElementById('editWageModal').classList.remove('flex');
    }
}

async function submitEditWage(event) {
    event.preventDefault();
    const btn = document.getElementById('editWageSubmitBtn');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Memproses...';

    const form = document.getElementById('editWageForm');
    const formData = new FormData(form);

    try {
        const r = await fetch('<?= base_url('api/update-attendance-wage.php') ?>', {
            method: 'POST',
            body: formData
        });
        const d = await r.json();

        if (d.success) {
            await AppAlert.success(d.message);
            location.reload();
        } else {
            await AppAlert.error(d.message);
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    } catch (e) {
        await AppAlert.error('Terjadi kesalahan koneksi.');
        btn.disabled = false;
        btn.innerHTML = origText;
    }
}
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
