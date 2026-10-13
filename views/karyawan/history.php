<?php
$title = 'Riwayat Kehadiran';
$active = 'history';
require_once __DIR__ . '/../../includes/geofence.php';
$settings = get_settings();

// Ambil riwayat absensi 30 hari terakhir
$stmt = db()->prepare('SELECT * FROM absensi WHERE user_id = ? ORDER BY tanggal DESC LIMIT 30');
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

// Hitung total ringkasan bulan ini
$totalUpahBelumDibayar = 0;
$totalHadir = 0;
$totalTerlambat = 0;

foreach ($rows as $row) {
    if ($row['status'] !== 'belum_absen' && $row['status'] !== 'alpa') {
        $totalHadir++;
    }
    if ($row['status'] === 'terlambat') {
        $totalTerlambat++;
    }
    if ($row['status_bayar'] === 'belum_dibayar') {
        $totalUpahBelumDibayar += (int)$row['upah_final'];
    }
}

require __DIR__ . '/../partials/header.php';
?>
<!-- Leaflet.js for Interactive Maps -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<main class="phone-shell min-h-screen bg-slate-50 pb-28">
    <!-- Top Header -->
    <div class="bg-gradient-to-br from-indigo-900 to-slate-900 px-5 pt-8 pb-6 text-white">
        <h1 class="text-xl font-extrabold font-outfit">Riwayat Kerja</h1>
        <p class="mt-1 text-xs text-indigo-200/70">Pantau kehadiran, selfie absen, dan rincian upah Anda</p>
    </div>

    <div class="px-5 -mt-4 space-y-4">
        <!-- Quick Stats Cards (Glassmorphism) -->
        <div class="grid grid-cols-3 gap-3">
            <div class="backdrop-blur-md bg-white/70 border border-slate-200/50 rounded-2xl p-3 shadow-sm text-center">
                <span class="inline-flex items-center justify-center p-1.5 rounded-lg bg-emerald-100 text-emerald-600 mb-1">
                    <i data-lucide="calendar-check" class="h-4 w-4"></i>
                </span>
                <p class="text-[10px] text-slate-400 font-medium">Hadir</p>
                <p class="text-base font-extrabold text-slate-800 font-mono mt-0.5"><?= $totalHadir ?></p>
            </div>
            <div class="backdrop-blur-md bg-white/70 border border-slate-200/50 rounded-2xl p-3 shadow-sm text-center">
                <span class="inline-flex items-center justify-center p-1.5 rounded-lg bg-amber-100 text-amber-600 mb-1">
                    <i data-lucide="clock-alert" class="h-4 w-4"></i>
                </span>
                <p class="text-[10px] text-slate-400 font-medium">Terlambat</p>
                <p class="text-base font-extrabold text-slate-800 font-mono mt-0.5"><?= $totalTerlambat ?></p>
            </div>
            <div class="backdrop-blur-md bg-white/70 border border-slate-200/50 rounded-2xl p-3 shadow-sm text-center">
                <span class="inline-flex items-center justify-center p-1.5 rounded-lg bg-indigo-100 text-indigo-600 mb-1">
                    <i data-lucide="wallet" class="h-4 w-4"></i>
                </span>
                <p class="text-[10px] text-slate-400 font-medium"><?= is_logbook_mode() ? 'Total Upah' : 'Belum Cair' ?></p>
                <p class="text-xs font-bold text-slate-800 font-mono mt-1 select-all"><?= rupiah(is_logbook_mode() ? array_sum(array_column($rows, 'upah_final')) : $totalUpahBelumDibayar) ?></p>
            </div>
        </div>

        <!-- History Title -->
        <h2 class="text-sm font-bold text-slate-700 font-outfit uppercase tracking-wider mt-2">Daftar Kehadiran</h2>

        <!-- Attendance List -->
        <div class="space-y-3">
            <?php foreach ($rows as $row): 
                $dateFormatted = date('d F Y', strtotime($row['tanggal']));
                $dayName = date('l', strtotime($row['tanggal']));
                $indonesianDays = [
                    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                ];
                $dayDisplay = $indonesianDays[$dayName] ?? $dayName;
                
                // Color mapping for Status Kehadiran
                $statusColor = 'bg-slate-100 text-slate-600 border-slate-200';
                $statusText = 'Belum Absen';
                if ($row['status'] === 'bekerja') {
                    $statusColor = 'bg-blue-50 text-blue-700 border-blue-100';
                    $statusText = 'Bekerja';
                } elseif ($row['status'] === 'selesai') {
                    $statusColor = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                    $statusText = 'Selesai';
                } elseif ($row['status'] === 'terlambat') {
                    $statusColor = 'bg-amber-50 text-amber-700 border-amber-100 animate-pulse';
                    $statusText = 'Terlambat';
                } elseif ($row['status'] === 'alpa') {
                    $statusColor = 'bg-rose-50 text-rose-700 border-rose-100';
                    $statusText = 'Alpa';
                }

                // Payment Status Badge
                $payBadge = '<span class="rounded-full px-2 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-700">Menunggu Cair</span>';
                if ($row['status_bayar'] === 'sudah_dibayar') {
                    $payBadge = '<span class="rounded-full px-2 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-700">Sudah Cair</span>';
                }
            ?>
                <article onclick="openDetailModal(<?= htmlspecialchars(json_encode($row)) ?>)" 
                    class="btn-press cursor-pointer border border-slate-200/70 bg-white hover:bg-slate-50 rounded-2xl p-4 shadow-sm transition-all duration-200 flex items-center justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-slate-800 font-outfit"><?= $dayDisplay ?>, <?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                            <?= $payBadge ?>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-slate-400 font-mono">
                            <span class="flex items-center gap-1"><i data-lucide="log-in" class="h-3 w-3 text-slate-300"></i><?= $row['jam_masuk'] ?? '--:--' ?></span>
                            <span class="text-slate-300">•</span>
                            <span class="flex items-center gap-1"><i data-lucide="log-out" class="h-3 w-3 text-slate-300"></i><?= $row['jam_keluar'] ?? '--:--' ?></span>
                        </div>
                    </div>
                    
                    <div class="text-right flex flex-col items-end gap-1">
                        <span class="text-sm font-extrabold text-slate-800 font-mono"><?= rupiah((int)$row['upah_final']) ?></span>
                        <span class="border rounded-full px-2 py-0.5 text-[10px] font-bold <?= $statusColor ?>"><?= $statusText ?></span>
                        <?php if (!empty($row['jam_keluar'])): ?>
                        <a href="<?= base_url('api/export-slip.php?absensi_id=' . (int) $row['id']) ?>" target="_blank" onclick="event.stopPropagation()" class="text-[10px] font-semibold text-indigo-600 hover:text-indigo-800">Slip PDF</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (!$rows): ?>
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center">
                    <i data-lucide="calendar-x" class="mx-auto h-10 w-10 text-slate-300 mb-2"></i>
                    <p class="text-sm text-slate-400 font-medium">Belum ada riwayat absensi kerja</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Details Modal (Premium Glassmorphism Backdrop) -->
<div id="detailModal" class="fixed inset-0 z-50 flex items-end justify-center hidden">
    <!-- Backdrop Blur with Fade-in -->
    <div onclick="closeDetailModal()" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"></div>
    
    <!-- Slide Up Panel -->
    <div class="relative z-10 w-full max-w-md rounded-t-[2.5rem] border-t border-white/20 bg-white/95 backdrop-blur-xl p-6 shadow-2xl shadow-slate-950/20 max-h-[85vh] overflow-y-auto transition-transform duration-300 transform translate-y-full flex flex-col">
        <!-- Grabber bar -->
        <div class="mx-auto w-12 h-1.5 rounded-full bg-slate-200/80 mb-5 shrink-0"></div>

        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-lg font-extrabold text-slate-950 font-outfit" id="mDate">25 May 2026</h3>
                <p class="text-xs text-slate-400 mt-0.5" id="mDay">Senin · Shift Harian</p>
            </div>
            <button onclick="closeDetailModal()" class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="space-y-4 pb-4">
            <!-- Wage Breakdown Grid -->
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                    <i data-lucide="calculator" class="h-3.5 w-3.5 text-indigo-500"></i> Rincian Upah Shift
                </h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Total Upah Masuk & Jam</span>
                        <span class="font-semibold text-slate-700 font-mono" id="mWageBase">Rp 0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Insentif Lembur</span>
                        <span class="font-semibold text-emerald-600 font-mono" id="mWageLembur">+Rp 0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Potongan Terlambat</span>
                        <span class="font-semibold text-rose-600 font-mono" id="mWagePotongan">-Rp 0</span>
                    </div>
                    <div class="h-px bg-slate-200/60 my-2"></div>
                    <div class="flex justify-between items-center pt-1">
                        <span class="font-bold text-slate-800">Total Upah Final</span>
                        <span class="text-lg font-extrabold text-indigo-600 font-mono" id="mWageTotal">Rp 0</span>
                    </div>
                </div>
            </div>

            <!-- Catatan Koreksi Upah -->
            <div id="mReasonContainer" class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4 text-xs text-amber-800 hidden">
                <p class="font-bold flex items-center gap-1.5 mb-1 text-amber-700">
                    <i data-lucide="alert-triangle" class="h-4 w-4 shrink-0 text-amber-600"></i>
                    Catatan Koreksi Upah Admin:
                </p>
                <p class="pl-5 leading-normal" id="mReasonText"></p>
            </div>

            <!-- Time Grid -->
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-3">
                    <p class="text-[10px] font-medium text-slate-400 mb-1 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Jam Masuk
                    </p>
                    <p class="text-base font-extrabold text-slate-800 font-mono" id="mTimeIn">--:--</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-3">
                    <p class="text-[10px] font-medium text-slate-400 mb-1 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Jam Keluar
                    </p>
                    <p class="text-base font-extrabold text-slate-800 font-mono" id="mTimeOut">--:--</p>
                </div>
            </div>

            <!-- Photos Carousel -->
            <div>
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5 flex items-center gap-1.5">
                    <i data-lucide="image" class="h-3.5 w-3.5 text-indigo-500"></i> Bukti Foto Kehadiran
                </h4>
                <div class="grid grid-cols-2 gap-3">
                    <div class="relative rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 aspect-[3/4]">
                        <img id="mImgIn" class="h-full w-full object-cover hidden" alt="Selfie Masuk" />
                        <div id="mImgInFallback" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 p-2 text-center">
                            <i data-lucide="camera-off" class="h-8 w-8 text-slate-300 mb-1"></i>
                            <span class="text-[10px] font-semibold">Tidak ada foto masuk</span>
                        </div>
                        <div class="absolute bottom-2 left-2 right-2 bg-slate-900/60 backdrop-blur-md px-2 py-0.5 rounded-full text-center">
                            <span class="text-[9px] font-bold text-white">MASUK</span>
                        </div>
                    </div>
                    
                    <div class="relative rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 aspect-[3/4]">
                        <img id="mImgOut" class="h-full w-full object-cover hidden" alt="Selfie Keluar" />
                        <div id="mImgOutFallback" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 p-2 text-center">
                            <i data-lucide="camera-off" class="h-8 w-8 text-slate-300 mb-1"></i>
                            <span class="text-[10px] font-semibold">Tidak ada foto keluar</span>
                        </div>
                        <div class="absolute bottom-2 left-2 right-2 bg-slate-900/60 backdrop-blur-md px-2 py-0.5 rounded-full text-center">
                            <span class="text-[9px] font-bold text-white">KELUAR</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leaflet map space -->
            <div>
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 flex items-center gap-1.5">
                    <i data-lucide="map" class="h-3.5 w-3.5 text-indigo-500"></i> Peta Lokasi Absen
                </h4>
                <div class="rounded-2xl overflow-hidden border border-slate-200">
                    <div id="modalMap" class="h-40 w-full bg-slate-100 z-10"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/employee_nav.php'; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>

<script>
let detailMap = null;
let geofenceCircle = null;
let markerIn = null;
let markerOut = null;

// Settings Geofence
const OFFICE_LAT = <?= (float)$settings['geofence_lat'] ?>;
const OFFICE_LNG = <?= (float)$settings['geofence_lng'] ?>;
const OFFICE_RADIUS = <?= (int)$settings['geofence_radius'] ?>;

function rupiah(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function openDetailModal(data) {
    const modal = document.getElementById('detailModal');
    const panel = modal.querySelector('.relative.z-10');

    // Parse data tanggal
    const d = new Date(data.tanggal);
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    
    document.getElementById('mDate').textContent = `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
    document.getElementById('mDay').textContent = `${days[d.getDay()]} · ${data.status.toUpperCase()}`;

    // Perhitungan base wage
    const uFinal = parseInt(data.upah_final || 0);
    const uLembur = parseInt(data.lembur || 0);
    const uPotongan = parseInt(data.potongan || 0);
    const uBase = uFinal - uLembur + uPotongan;

    document.getElementById('mWageBase').textContent = rupiah(uBase);
    document.getElementById('mWageLembur').textContent = '+ ' + rupiah(uLembur);
    document.getElementById('mWagePotongan').textContent = '- ' + rupiah(uPotongan);
    document.getElementById('mWageTotal').textContent = rupiah(uFinal);

    // Tampilkan alasan edit upah jika ada
    const reasonCont = document.getElementById('mReasonContainer');
    const reasonText = document.getElementById('mReasonText');
    if (data.alasan_edit_upah && data.alasan_edit_upah.trim() !== '') {
        reasonText.textContent = data.alasan_edit_upah;
        reasonCont.classList.remove('hidden');
    } else {
        reasonCont.classList.add('hidden');
    }

    document.getElementById('mTimeIn').textContent = data.jam_masuk || '--:--';
    document.getElementById('mTimeOut').textContent = data.jam_keluar || '--:--';

    // Preview foto
    const imgIn = document.getElementById('mImgIn');
    const fbIn = document.getElementById('mImgInFallback');
    if (data.foto_masuk) {
        imgIn.src = '<?= base_url() ?>' + data.foto_masuk.replace(/^\/+/, '');
        imgIn.classList.remove('hidden');
        fbIn.classList.add('hidden');
    } else {
        imgIn.classList.add('hidden');
        fbIn.classList.remove('hidden');
    }

    const imgOut = document.getElementById('mImgOut');
    const fbOut = document.getElementById('mImgOutFallback');
    if (data.foto_keluar) {
        imgOut.src = '<?= base_url() ?>' + data.foto_keluar.replace(/^\/+/, '');
        imgOut.classList.remove('hidden');
        fbOut.classList.add('hidden');
    } else {
        imgOut.classList.add('hidden');
        fbOut.classList.remove('hidden');
    }

    // Tampilkan modal
    modal.classList.remove('hidden');
    setTimeout(() => {
        panel.classList.remove('translate-y-full');
    }, 50);

    // Render Map Leaflet.js
    setTimeout(() => {
        initModalMap(data);
    }, 300);
}

function initModalMap(data) {
    const mapContainer = document.getElementById('modalMap');
    
    // Hapus instance map lama jika ada
    if (detailMap) {
        detailMap.remove();
        detailMap = null;
    }

    // Pusatkan peta ke kantor/geofence pusat
    detailMap = L.map('modalMap', {
        zoomControl: false,
        attributionControl: false
    }).setView([OFFICE_LAT, OFFICE_LNG], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(detailMap);

    // Geofence Circle
    geofenceCircle = L.circle([OFFICE_LAT, OFFICE_LNG], {
        color: '#6366f1',
        fillColor: '#818cf8',
        fillOpacity: 0.15,
        radius: OFFICE_RADIUS
    }).addTo(detailMap);

    // Custom Icons
    const officeIcon = L.divIcon({
        html: '<div class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-500 text-white border border-white shadow-md"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M15 3v18"/></svg></div>',
        className: '',
        iconSize: [24, 24]
    });

    const inIcon = L.divIcon({
        html: '<div class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500 text-white border border-white shadow-md"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg></div>',
        className: '',
        iconSize: [24, 24]
    });

    const outIcon = L.divIcon({
        html: '<div class="flex h-6 w-6 items-center justify-center rounded-full bg-rose-500 text-white border border-white shadow-md"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg></div>',
        className: '',
        iconSize: [24, 24]
    });

    // Office marker
    L.marker([OFFICE_LAT, OFFICE_LNG], { icon: officeIcon }).addTo(detailMap).bindPopup('Pusat Kantor');

    const bounds = L.latLngBounds([[OFFICE_LAT, OFFICE_LNG]]);

    // Marker Masuk
    if (data.lat_masuk && data.lng_masuk) {
        const latIn = parseFloat(data.lat_masuk);
        const lngIn = parseFloat(data.lng_masuk);
        markerIn = L.marker([latIn, lngIn], { icon: inIcon }).addTo(detailMap).bindPopup('Absen Masuk');
        bounds.extend([latIn, lngIn]);
    }

    // Marker Keluar
    if (data.lat_keluar && data.lng_keluar) {
        const latOut = parseFloat(data.lat_keluar);
        const lngOut = parseFloat(data.lng_keluar);
        markerOut = L.marker([latOut, lngOut], { icon: outIcon }).addTo(detailMap).bindPopup('Absen Keluar');
        bounds.extend([latOut, lngOut]);
    }

    // Fit map bounds to show office, in-marker and out-marker nicely
    detailMap.fitBounds(bounds, { padding: [30, 30] });
}

function closeDetailModal() {
    const modal = document.getElementById('detailModal');
    const panel = modal.querySelector('.relative.z-10');

    panel.classList.add('translate-y-full');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
</script>
