<?php
$title = 'Absen Keluar';
$active = 'absen';
require_once __DIR__ . '/../../includes/geofence.php';
require_once __DIR__ . '/../../includes/wage-calculator.php';
require_once __DIR__ . '/../../includes/early-checkout.php';
ensure_early_checkout_columns();
$settings = get_settings();
$jamBatasKeluar = jam_batas_keluar_awal();

// Check today's attendance
$today = current_date();
$stmt = db()->prepare('SELECT * FROM absensi WHERE user_id=? AND tanggal=? LIMIT 1');
$stmt->execute([$user['id'], $today]);
$attendance = $stmt->fetch();

// Live estimate
$estimate = null;
if ($attendance && $attendance['jam_masuk'] && !$attendance['jam_keluar']) {
    $estimate = calculate_wage($attendance['jam_masuk'], date('H:i:s'));
}

require __DIR__ . '/../partials/header.php';
?>
<main class="phone-shell min-h-screen bg-slate-50 pb-28">
    <!-- Top Header -->
    <div class="bg-gradient-to-br from-rose-900 to-slate-900 px-5 pt-8 pb-6">
        <a href="<?= route_url('karyawan.home') ?>" class="mb-4 flex items-center gap-2 text-sm text-rose-300 hover:text-white transition">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali
        </a>
        <h1 class="text-xl font-extrabold text-white font-outfit">Absen Keluar</h1>
        <p class="mt-1 text-xs text-rose-200/70">Foto keluar untuk menutup shift dan menghitung upah final</p>
    </div>

    <div class="px-5 -mt-1 space-y-4">

        <!-- Wage Preview Card -->
        <?php if ($attendance && $attendance['jam_masuk'] && !$attendance['jam_keluar']): ?>
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="calculator" class="h-4 w-4 text-amber-500"></i>
                <span class="text-sm font-semibold text-slate-800">Estimasi Upah Shift Ini</span>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="rounded-xl bg-slate-50 p-4 text-center">
                        <p class="text-xs text-slate-400 mb-1">Masuk pukul</p>
                        <p class="text-xl font-extrabold text-slate-900 font-mono"><?= e($attendance['jam_masuk']) ?></p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 text-center">
                        <p class="text-xs text-slate-400 mb-1">Durasi kerja</p>
                        <p class="text-xl font-extrabold text-slate-900 font-mono" id="workDuration">—</p>
                    </div>
                </div>
                <?php if ($estimate): ?>
                <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 p-4 text-center">
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-500 mb-1">Estimasi Upah Saat Ini</p>
                    <p class="text-4xl font-extrabold text-amber-700 font-outfit" id="liveWage"><?= rupiah($estimate['total']) ?></p>
                    <?php if ($estimate['lembur'] > 0): ?>
                    <p class="mt-1 text-xs text-purple-600 font-semibold">
                        Termasuk bonus lembur <span id="liveLembur"><?= rupiah($estimate['lembur']) ?></span>
                    </p>
                    <?php endif; ?>
                    <p class="mt-2 text-xs text-amber-500 italic">*Akan dihitung final saat keluar</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($attendance && $attendance['jam_keluar']): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center">
            <i data-lucide="check-circle-2" class="mx-auto mb-2 h-10 w-10 text-emerald-500"></i>
            <p class="font-bold text-emerald-800">Anda sudah absen keluar hari ini</p>
            <p class="text-sm text-emerald-600 mt-1">Keluar: <?= e($attendance['jam_keluar']) ?> · <?= rupiah((int)$attendance['upah_final']) ?></p>
            <a href="<?= route_url('karyawan.home') ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-emerald-600">
                <i data-lucide="home" class="h-4 w-4"></i> Ke Beranda
            </a>
        </div>
        <?php else: ?>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-center">
            <i data-lucide="alert-triangle" class="mx-auto mb-2 h-10 w-10 text-amber-500"></i>
            <p class="font-bold text-amber-800">Anda belum absen masuk hari ini</p>
            <a href="<?= route_url('karyawan.checkin') ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-amber-600">
                Absen Masuk Dulu
            </a>
        </div>
        <?php endif; ?>

        <?php if ($attendance && $attendance['jam_masuk'] && !$attendance['jam_keluar']): ?>

        <!-- Geofence Card -->
        <div class="hidden rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="radar" class="h-4 w-4 text-blue-500"></i>
                    <span class="text-sm font-semibold text-slate-800">Status Lokasi</span>
                </div>
                <span id="geoStatusBadgeOld" class="rounded-full px-2.5 py-0.5 text-xs font-bold bg-slate-100 text-slate-500">Mendeteksi...</span>
            </div>
            <div class="px-5 py-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl" id="radarBoxOld">
                        <div class="relative flex h-full w-full items-center justify-center rounded-2xl bg-slate-100">
                            <div class="absolute inset-0 rounded-2xl border-2 border-slate-200 animate-ping opacity-20"></div>
                            <i data-lucide="map-pin" class="h-7 w-7 text-slate-400" id="radarIconOld"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-3xl font-extrabold font-outfit" id="distanceTextOld">—</p>
                        <p class="text-xs text-slate-400" id="distanceLabelOld">dari titik absen · radius <?= (int)$settings['geofence_radius'] ?>m</p>
                        <div class="mt-1.5 flex items-center gap-1.5 text-xs" id="distanceStatusOld">
                            <span class="text-slate-400">Mendeteksi lokasi GPS...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Camera Form -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="camera" class="h-4 w-4 text-rose-500"></i>
                <span class="text-sm font-semibold text-slate-800">Foto Selfie Keluar</span>
            </div>
            <form data-camera-form action="<?= base_url('api/absen-keluar.php') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div id="earlyCheckoutSection" class="border-b border-amber-200 bg-amber-50">
                    <div class="px-5 py-4 border-b border-amber-200/80 flex items-start gap-3">
                        <i data-lucide="file-warning" class="h-5 w-5 text-amber-600 shrink-0 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-bold text-amber-900">Keluar Sebelum Pukul <?= e(substr($jamBatasKeluar, 0, 5)) ?></p>
                            <p class="text-xs text-amber-700 mt-0.5">Wajib isi keterangan alasan dan lampirkan bukti (foto dokumen / kondisi kerja).</p>
                        </div>
                    </div>
                    <div class="p-5 space-y-4">
                        <label class="block text-sm font-semibold text-slate-700">
                            Keterangan / Alasan
                            <textarea name="keterangan_keluar_awal" id="keteranganKeluarAwal" rows="3" maxlength="500"
                                placeholder="Contoh: izin pulang lebih awal karena ada keperluan keluarga"
                                class="mt-1.5 w-full rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-amber-400"></textarea>
                        </label>
                        <div class="block text-sm font-semibold text-slate-700">
                            Bukti Foto
                            <div class="mt-1.5 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-amber-200 bg-white px-3 py-3 text-xs font-bold text-amber-800 transition hover:border-amber-300 hover:bg-amber-50">
                                    <i data-lucide="camera" class="h-4 w-4"></i>
                                    Kamera
                                    <input type="file" name="bukti_keluar_awal_camera" id="buktiKeluarAwalCamera" accept="image/*" capture="environment" class="hidden">
                                </label>
                                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-amber-200 bg-white px-3 py-3 text-xs font-bold text-amber-800 transition hover:border-amber-300 hover:bg-amber-50">
                                    <i data-lucide="image" class="h-4 w-4"></i>
                                    Galeri
                                    <input type="file" name="bukti_keluar_awal_gallery" id="buktiKeluarAwalGallery" accept="image/*" class="hidden">
                                </label>
                            </div>
                            <span id="buktiKeluarAwalStatus" class="mt-1 block text-xs text-slate-500">Foto surat izin, chat atasan, atau bukti lainnya.</span>
                        </div>
                    </div>
                </div>

                <div class="camera-preview relative bg-slate-900 flex items-center justify-center overflow-hidden" style="min-height:52vw;max-height:56vh;">
                    <video autoplay playsinline muted class="h-full w-full object-cover"></video>
                    <div class="pointer-events-none absolute flex items-center justify-center">
                        <div class="h-52 w-40 rounded-[50%] border-4 border-rose-400/80 shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]"></div>
                    </div>
                    <!-- Corner guides -->
                    <div class="pointer-events-none absolute top-4 left-4 h-8 w-8 border-t-2 border-l-2 border-rose-400/70 rounded-tl-lg"></div>
                    <div class="pointer-events-none absolute top-4 right-4 h-8 w-8 border-t-2 border-r-2 border-rose-400/70 rounded-tr-lg"></div>
                    <div class="pointer-events-none absolute bottom-4 left-4 h-8 w-8 border-b-2 border-l-2 border-rose-400/70 rounded-bl-lg"></div>
                    <div class="pointer-events-none absolute bottom-4 right-4 h-8 w-8 border-b-2 border-r-2 border-rose-400/70 rounded-br-lg"></div>
                    <div id="geoBadgeWrap" class="absolute bottom-3 right-3 flex min-h-[76px] min-w-[76px] flex-col items-center justify-center rounded-2xl border-4 border-white bg-white/95 px-2 py-2 text-center shadow-xl">
                        <i id="geoBadgeIcon" data-lucide="map-pin" class="h-6 w-6 text-slate-400"></i>
                        <p id="distanceText" class="mt-0.5 text-base font-extrabold leading-none text-slate-500 font-outfit">--</p>
                        <p id="distanceStatus" class="mt-1 text-[10px] font-semibold leading-tight text-slate-400">Mendeteksi lokasi</p>
                    </div>
                </div>
                <div class="p-5">
                    <div class="flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-3">
                        <div class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></div>
                        <p data-camera-status class="text-slate-500 text-xs">Menyiapkan kamera dan lokasi...</p>
                    </div>
                    <input type="hidden" name="photo_data">
                    <input type="hidden" name="lat">
                    <input type="hidden" name="lng">
                    <canvas class="hidden"></canvas>
                    <button type="button" data-capture disabled
                        id="captureBtn"
                        class="btn-press mt-4 flex w-full items-center justify-center gap-3 rounded-2xl bg-gradient-to-r from-rose-500 to-pink-500 px-4 py-4 text-sm font-bold text-white shadow-lg shadow-rose-500/25 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        <i data-lucide="log-out" class="h-5 w-5"></i>
                        Ambil Foto & Absen Keluar
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/../partials/employee_nav.php'; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>

<script>
<?php if ($attendance && $attendance['jam_masuk'] && !$attendance['jam_keluar']): ?>
const masukTime = new Date('<?= $today ?> <?= $attendance['jam_masuk'] ?>');
function updateDuration() {
    const diff = Math.floor((new Date() - masukTime) / 1000);
    const h = Math.floor(diff / 3600);
    const m = Math.floor((diff % 3600) / 60);
    const s = diff % 60;
    const el = document.getElementById('workDuration');
    if (el) el.textContent = `${h}j ${m}m ${s}s`;
}
updateDuration();
setInterval(updateDuration, 1000);
<?php endif; ?>

const JAM_BATAS_KELUAR = '<?= e(substr($jamBatasKeluar, 0, 5)) ?>';

function isEarlyCheckoutNow() {
    if (<?= is_logbook_mode() ? 'true' : 'false' ?>) return false;
    const now = new Date();
    const [h, m] = JAM_BATAS_KELUAR.split(':').map(Number);
    const batas = new Date();
    batas.setHours(h, m, 0, 0);
    return now < batas;
}

function toggleEarlyCheckoutSection() {
    const section = document.getElementById('earlyCheckoutSection');
    const keterangan = document.getElementById('keteranganKeluarAwal');
    const buktiCamera = document.getElementById('buktiKeluarAwalCamera');
    const buktiGallery = document.getElementById('buktiKeluarAwalGallery');
    if (!section) return;
    const early = isEarlyCheckoutNow();
    section.classList.toggle('hidden', !early);
    if (keterangan) keterangan.required = early;
    if (buktiCamera) buktiCamera.required = false;
    if (buktiGallery) buktiGallery.required = false;
}

window.beforeCaptureSubmit = function () {
    if (!isEarlyCheckoutNow()) return true;
    const keterangan = document.getElementById('keteranganKeluarAwal');
    const buktiCamera = document.getElementById('buktiKeluarAwalCamera');
    const buktiGallery = document.getElementById('buktiKeluarAwalGallery');
    if (!keterangan?.value.trim()) {
        AppAlert.error('Isi keterangan alasan pulang lebih awal.', 'Keterangan wajib');
        return false;
    }
    if (!buktiCamera?.files?.length && !buktiGallery?.files?.length) {
        AppAlert.error('Lampirkan foto bukti (surat izin, chat, atau dokumen lain).', 'Bukti wajib');
        return false;
    }
    return true;
};

document.querySelectorAll('#buktiKeluarAwalCamera, #buktiKeluarAwalGallery').forEach(input => {
    input.addEventListener('change', () => {
        const otherId = input.id === 'buktiKeluarAwalCamera' ? 'buktiKeluarAwalGallery' : 'buktiKeluarAwalCamera';
        const other = document.getElementById(otherId);
        if (other && input.files?.length) other.value = '';

        const status = document.getElementById('buktiKeluarAwalStatus');
        if (!status) return;
        status.textContent = input.files?.length
            ? `Bukti dipilih: ${input.files[0].name}`
            : 'Foto surat izin, chat atasan, atau bukti lainnya.';
        status.className = input.files?.length
            ? 'mt-1 block text-xs font-semibold text-emerald-600'
            : 'mt-1 block text-xs text-slate-500';
    });
});

toggleEarlyCheckoutSection();
setInterval(toggleEarlyCheckoutSection, 30000);

const GEO_LAT    = <?= (float)($settings['geofence_lat'] ?? 0) ?>;
const GEO_LNG    = <?= (float)($settings['geofence_lng'] ?? 0) ?>;
const GEO_RADIUS = <?= (int)($settings['geofence_radius'] ?? 100) ?>;

window.cameraReady = false;
window.insideGeofence = false;

function updateCaptureButton() {
    const btn = document.getElementById('captureBtn');
    if (btn) {
        btn.disabled = !(window.cameraReady && window.insideGeofence);
    }
}

window.onCameraReady = function() {
    window.cameraReady = true;
    updateCaptureButton();
};

function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function updateGeofenceUI(distance) {
    const badge = document.getElementById('geoStatusBadgeOld');
    const badgeWrap = document.getElementById('geoBadgeWrap');
    const dText = document.getElementById('distanceText');
    const dStat = document.getElementById('distanceStatus');
    const box   = document.getElementById('radarBoxOld');
    const icon  = document.getElementById('geoBadgeIcon');

    const inside = distance <= GEO_RADIUS;
    window.insideGeofence = inside;
    updateCaptureButton();

    dText.textContent = distance < 1000 ? Math.round(distance) + ' m' : (distance/1000).toFixed(1) + ' km';
    dText.className = 'mt-0.5 text-base font-extrabold leading-none font-outfit ' + (inside ? 'text-emerald-500' : 'text-rose-500');
    badgeWrap.className = 'absolute bottom-3 right-3 flex min-h-[76px] min-w-[76px] flex-col items-center justify-center rounded-2xl border-4 border-white bg-white/95 px-2 py-2 text-center shadow-xl ' + (inside ? 'ring-2 ring-emerald-300' : 'ring-2 ring-rose-300');

    if (inside) {
        badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-bold bg-emerald-100 text-emerald-700';
        badge.textContent = '✓ Dalam Area';
        if (box) box.querySelector('div').className = 'relative flex h-full w-full items-center justify-center rounded-2xl bg-emerald-50';
        if (icon) icon.className = 'h-7 w-7 text-emerald-500';
        dStat.innerHTML = '<i data-lucide="check-circle" class="h-4 w-4 text-emerald-500"></i><span class="font-semibold text-emerald-600">Siap absen keluar!</span>';
    } else {
        badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-bold bg-rose-100 text-rose-600';
        badge.textContent = '✗ Di Luar Area';
        if (box) box.querySelector('div').className = 'relative flex h-full w-full items-center justify-center rounded-2xl bg-rose-50';
        if (icon) icon.className = 'h-7 w-7 text-rose-400';
        const sisa = Math.round(distance - GEO_RADIUS);
        dStat.innerHTML = `<i data-lucide="alert-circle" class="h-4 w-4 text-rose-400"></i><span class="text-rose-500">Masih <strong>${sisa} m</strong> dari batas area</span>`;
    }
    if (icon) icon.className = 'h-6 w-6 ' + (inside ? 'text-emerald-500' : 'text-rose-500');
    dStat.textContent = inside ? 'Dalam area' : 'Di luar area';
    dStat.className = 'mt-1 text-[10px] font-semibold leading-tight ' + (inside ? 'text-emerald-600' : 'text-rose-600');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

if (navigator.geolocation) {
    navigator.geolocation.watchPosition(pos => {
        const lat = pos.coords.latitude, lng = pos.coords.longitude;
        const dist = haversine(lat, lng, GEO_LAT, GEO_LNG);

        document.querySelectorAll('[name="lat"]').forEach(el => el.value = lat);
        document.querySelectorAll('[name="lng"]').forEach(el => el.value = lng);

        updateGeofenceUI(dist);
    }, err => {
        const dText = document.getElementById('distanceText');
        const dStat = document.getElementById('distanceStatus');
        const icon = document.getElementById('geoBadgeIcon');
        if (dText) dText.textContent = '--';
        if (icon) icon.className = 'h-6 w-6 text-rose-500';
        if (dStat) {
            dStat.textContent = 'GPS error';
            dStat.className = 'mt-1 text-[10px] font-semibold leading-tight text-rose-600';
        }
    }, { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 });
}
</script>
