<?php
$title = 'Absen Masuk';
$active = 'absen';
require_once __DIR__ . '/../../includes/geofence.php';
require_once __DIR__ . '/../../includes/quota.php';
require_once __DIR__ . '/../../includes/leaves.php';
require_once __DIR__ . '/../../includes/holidays.php';
require_once __DIR__ . '/../../includes/attendance-rules.php';
$settings = get_settings();

if (is_holiday()) {
    flash('error', holiday_message());
    redirect(route_url('karyawan.home'));
}

if (is_on_approved_leave((int) $user['id'])) {
    flash('error', leave_block_message());
    redirect(route_url('karyawan.home'));
}

if (is_blocked_by_daily_quota((int) $user['id'])) {
    flash('error', daily_quota_full_message());
    redirect(route_url('karyawan.home'));
}

if (is_checkin_closed()) {
    flash('error', latest_checkin_message());
    redirect(route_url('karyawan.home'));
}

$quota = daily_quota_status();

require __DIR__ . '/../partials/header.php';
?>
<main class="phone-shell min-h-screen bg-slate-50 pb-28">
    <!-- Top Header -->
    <div class="bg-gradient-to-br from-slate-900 to-slate-800 px-5 pt-8 pb-6">
        <a href="<?= route_url('karyawan.home') ?>" class="mb-4 flex items-center gap-2 text-sm text-slate-400 hover:text-white transition">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali
        </a>
        <h1 class="text-xl font-extrabold text-white font-outfit">Absen Masuk</h1>
        <p class="mt-1 text-xs text-slate-400">Ambil foto wajah dan pastikan berada dalam area geofence</p>
    </div>

    <div class="px-5 -mt-1 space-y-4">
        <?php if (!$quota['unlimited']): ?>
        <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-xs text-indigo-800">
            <strong>Slot masuk hari ini:</strong> <?= $quota['used'] ?> / <?= $quota['limit'] ?> terisi
            <?php if ($quota['remaining'] > 0): ?>
            · sisa <strong><?= $quota['remaining'] ?></strong> slot
            <?php else: ?>
            · <strong class="text-rose-600">kuota penuh</strong>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Geofence Radar Card -->
        <div class="hidden rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden" id="geofenceCardOld">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="radar" class="h-4 w-4 text-blue-500"></i>
                    <span class="text-sm font-semibold text-slate-800">Status Lokasi</span>
                </div>
                <span id="geoStatusBadgeOld" class="rounded-full px-2.5 py-0.5 text-xs font-bold bg-slate-100 text-slate-500">Mendeteksi...</span>
            </div>
            <div class="p-5">
                <!-- Radar Visual -->
                <div class="flex justify-center mb-4">
                    <div class="relative flex h-32 w-32 items-center justify-center">
                        <!-- Radar rings -->
                        <div class="absolute inset-0 rounded-full border-2 border-slate-200 animate-ping opacity-20"></div>
                        <div class="absolute inset-3 rounded-full border-2 border-slate-200"></div>
                        <div class="absolute inset-6 rounded-full border-2 border-slate-200"></div>
                        <!-- Center point -->
                        <div id="radarCenterOld" class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 shadow-md">
                            <i data-lucide="map-pin" class="h-5 w-5 text-slate-500"></i>
                        </div>
                        <!-- User dot (positioned by JS) -->
                        <div id="userDot" class="absolute hidden h-4 w-4 rounded-full border-2 border-white bg-blue-500 shadow-lg shadow-blue-500/50"></div>
                    </div>
                </div>
                <!-- Distance info -->
                <div class="text-center">
                    <p class="text-3xl font-extrabold font-outfit" id="distanceTextOld">—</p>
                    <p class="text-xs text-slate-400 mt-0.5" id="distanceLabelOld">dari titik absen</p>
                </div>
                <div class="mt-3 flex items-center justify-center gap-2 text-xs" id="distanceStatusOld">
                    <span class="text-slate-400">Mendeteksi lokasi...</span>
                </div>
            </div>
        </div>

        <!-- Camera Form -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="camera" class="h-4 w-4 text-emerald-500"></i>
                <span class="text-sm font-semibold text-slate-800">Foto Selfie</span>
            </div>

            <form data-camera-form action="<?= base_url('api/absen-masuk.php') ?>" method="post">
                <?= csrf_field() ?>
                <div class="camera-preview relative bg-slate-900 flex items-center justify-center overflow-hidden" style="min-height:52vw;max-height:56vh;">
                    <video autoplay playsinline muted class="h-full w-full object-cover"></video>
                    <!-- Face guide oval -->
                    <div class="pointer-events-none absolute flex items-center justify-center">
                        <div class="h-52 w-40 rounded-[50%] border-4 border-white/60 shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]"></div>
                    </div>
                    <!-- Corner guides -->
                    <div class="pointer-events-none absolute top-4 left-4 h-8 w-8 border-t-2 border-l-2 border-white/70 rounded-tl-lg"></div>
                    <div class="pointer-events-none absolute top-4 right-4 h-8 w-8 border-t-2 border-r-2 border-white/70 rounded-tr-lg"></div>
                    <div class="pointer-events-none absolute bottom-4 left-4 h-8 w-8 border-b-2 border-l-2 border-white/70 rounded-bl-lg"></div>
                    <div class="pointer-events-none absolute bottom-4 right-4 h-8 w-8 border-b-2 border-r-2 border-white/70 rounded-br-lg"></div>
                    <div id="geoBadgeWrap" class="absolute bottom-3 right-3 flex min-h-[76px] min-w-[76px] flex-col items-center justify-center rounded-2xl border-4 border-white bg-white/95 px-2 py-2 text-center shadow-xl">
                        <i id="geoBadgeIcon" data-lucide="map-pin" class="h-6 w-6 text-slate-400"></i>
                        <p id="distanceText" class="mt-0.5 text-base font-extrabold leading-none text-slate-500 font-outfit">--</p>
                        <p id="distanceStatus" class="mt-1 text-[10px] font-semibold leading-tight text-slate-400">Mendeteksi lokasi</p>
                    </div>
                </div>

                <div class="p-5">
                    <!-- Status bar -->
                    <div class="flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-3 text-sm" id="cameraStatusWrap">
                        <div class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></div>
                        <p data-camera-status class="text-slate-500 text-xs">Menyiapkan kamera dan lokasi...</p>
                    </div>

                    <input type="hidden" name="photo_data">
                    <input type="hidden" name="lat">
                    <input type="hidden" name="lng">
                    <canvas class="hidden"></canvas>

                    <button type="button" data-capture disabled
                        id="captureBtn"
                        class="btn-press mt-4 flex w-full items-center justify-center gap-3 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-4 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        <i data-lucide="camera" class="h-5 w-5"></i>
                        Ambil Foto & Absen Masuk
                    </button>
                </div>
            </form>
        </div>

        <!-- Tips -->
        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-xs text-blue-700">
            <div class="flex items-start gap-2">
                <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-blue-500"></i>
                <div>
                    <p class="font-semibold mb-1">Tips absen sukses:</p>
                    <ul class="space-y-0.5 text-blue-600">
                        <li>• Wajah harus terlihat jelas dan terang</li>
                        <li>• Pastikan GPS/lokasi aktif di HP</li>
                        <li>• Radius geofence: <strong><?= (int)$settings['geofence_radius'] ?> meter</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../partials/employee_nav.php'; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>

<script>
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
    const badgeWrap = document.getElementById('geoBadgeWrap');
    const badgeIcon = document.getElementById('geoBadgeIcon');
    const badge = document.getElementById('geoStatusBadgeOld');
    const center = document.getElementById('radarCenterOld');
    const dText = document.getElementById('distanceText');
    const dStat = document.getElementById('distanceStatus');

    const inside = distance <= GEO_RADIUS;
    window.insideGeofence = inside;
    updateCaptureButton();

    dText.textContent = distance < 1000 ? Math.round(distance) + ' m' : (distance/1000).toFixed(1) + ' km';
    dText.className = 'mt-0.5 text-base font-extrabold leading-none font-outfit ' + (inside ? 'text-emerald-500' : 'text-rose-500');
    badgeWrap.className = 'absolute bottom-3 right-3 flex min-h-[76px] min-w-[76px] flex-col items-center justify-center rounded-2xl border-4 border-white bg-white/95 px-2 py-2 text-center shadow-xl ' + (inside ? 'ring-2 ring-emerald-300' : 'ring-2 ring-rose-300');
    badgeIcon.className = 'h-6 w-6 ' + (inside ? 'text-emerald-500' : 'text-rose-500');

    if (inside) {
        badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-bold bg-emerald-100 text-emerald-700';
        badge.textContent = '✓ Dalam Area';
        center.className = 'relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 shadow-md';
        center.querySelector('i').className = 'h-5 w-5 text-emerald-500';
        dStat.innerHTML = '<i data-lucide="check-circle" class="h-4 w-4 text-emerald-500"></i><span class="font-semibold text-emerald-600">Siap absen! Anda dalam jangkauan.</span>';
    } else {
        badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-bold bg-rose-100 text-rose-600';
        badge.textContent = '✗ Di Luar Area';
        center.className = 'relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 shadow-md';
        center.querySelector('i').className = 'h-5 w-5 text-rose-500';
        const sisa = Math.round(distance - GEO_RADIUS);
        dStat.innerHTML = `<i data-lucide="alert-circle" class="h-4 w-4 text-rose-400"></i><span class="text-rose-500">Masih <strong>${sisa} m</strong> dari batas area</span>`;
    }
    dStat.textContent = inside ? 'Dalam area' : 'Di luar area';
    dStat.className = 'mt-1 text-[10px] font-semibold leading-tight ' + (inside ? 'text-emerald-600' : 'text-rose-600');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

if (navigator.geolocation) {
    navigator.geolocation.watchPosition(pos => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
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
