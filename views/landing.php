<?php
$title = 'Absensi Hub - Logbook';
$repoUrl = project_repo_url();
require __DIR__ . '/partials/header.php';
?>
<main class="min-h-screen bg-slate-50 text-slate-900 font-sans">
    <section class="mx-auto flex min-h-screen max-w-6xl flex-col px-5 py-6 sm:px-6 lg:px-8">
        <nav class="flex items-center justify-between border-b border-slate-200 pb-5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-white">
                    <i data-lucide="scan-face" class="h-5 w-5"></i>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-emerald-600"><?= e(app_config('project_stage', 'Logbook')) ?></p>
                    <h1 class="text-lg font-extrabold leading-none text-slate-950 font-outfit"><?= e(get_app_name()) ?></h1>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($repoUrl !== ''): ?>
                <a href="<?= e($repoUrl) ?>" target="_blank" class="hidden items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-100 sm:flex">
                    <i data-lucide="github" class="h-4 w-4"></i>
                    <span>Repository</span>
                </a>
                <?php endif; ?>
                <a class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-slate-700 sm:px-5" href="<?= route_url('login') ?>">
                    <i data-lucide="log-in" class="h-4 w-4"></i>
                    <span>Masuk</span>
                </a>
            </div>
        </nav>

        <div class="grid flex-1 items-center gap-10 py-10 lg:grid-cols-[1.05fr_.95fr] lg:py-14">
            <div class="space-y-7">
                <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 text-xs font-bold text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>Framework sudah dipilih, fitur inti sedang dipadatkan</span>
                </div>

                <div class="space-y-4">
                    <h2 class="max-w-3xl text-4xl font-extrabold leading-tight tracking-normal text-slate-950 font-outfit sm:text-5xl">
                        Prototype absensi web untuk Pure Fresh.
                    </h2>
                    <p class="max-w-2xl text-base leading-7 text-slate-600">
                        Fokus minggu ini adalah menata ulang proyek agar sesuai logbook: stack utama sudah ditentukan, repository disiapkan sebagai bukti pengerjaan, dan fitur yang ditampilkan diprioritaskan pada absensi masuk-keluar, validasi lokasi, face capture, rekap kehadiran, serta hitung upah dasar.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                            <i data-lucide="code-2" class="h-4 w-4"></i>
                        </div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Framework</p>
                        <p class="mt-1 text-sm font-bold leading-5 text-slate-900"><?= e(app_config('framework_summary', 'PHP Native')) ?></p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <i data-lucide="map-pin-check" class="h-4 w-4"></i>
                        </div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Scope inti</p>
                        <p class="mt-1 text-sm font-bold leading-5 text-slate-900">GPS, kamera, rekap, upah</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <i data-lucide="git-branch" class="h-4 w-4"></i>
                        </div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Bukti</p>
                        <p class="mt-1 text-sm font-bold leading-5 text-slate-900"><?= $repoUrl !== '' ? 'Repo GitHub tertaut' : 'Repo GitHub belum ditautkan' ?></p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700" href="<?= route_url('login') ?>">
                        <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                        <span>Buka Portal</span>
                    </a>
                    <a class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-100" href="<?= route_url('register') ?>">
                        <i data-lucide="user-plus" class="h-4 w-4"></i>
                        <span>Daftar Karyawan</span>
                    </a>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Checkpoint Logbook</p>
                        <h3 class="mt-1 text-xl font-extrabold text-slate-950 font-outfit">Minggu Ini</h3>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700"><?= date('d M Y') ?></span>
                </div>

                <div class="space-y-3">
                    <?php
                    $items = [
                        ['icon' => 'check-circle-2', 'title' => 'Stack dipilih', 'desc' => app_config('framework_summary', 'PHP Native, MySQL, Tailwind, Alpine')],
                        ['icon' => 'folder-git-2', 'title' => 'Bukti repo', 'desc' => $repoUrl !== '' ? $repoUrl : 'URL repository bisa diisi setelah GitHub dibuat'],
                        ['icon' => 'list-checks', 'title' => 'Fitur inti', 'desc' => 'Login, absensi, geolocation, face capture, rekap, dan upah'],
                        ['icon' => 'minus-circle', 'title' => 'Fitur tambahan', 'desc' => 'Izin, kuota, libur, audit, dan pembayaran disimpan untuk tahap berikutnya'],
                    ];
                    foreach ($items as $item):
                    ?>
                    <div class="flex gap-3 rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-700 shadow-sm">
                            <i data-lucide="<?= e($item['icon']) ?>" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-extrabold text-slate-900"><?= e($item['title']) ?></p>
                            <p class="mt-0.5 break-words text-xs leading-5 text-slate-500"><?= e($item['desc']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <footer class="border-t border-slate-200 py-5 text-center text-xs text-slate-500">
            <?= e(get_app_name()) ?> untuk <?= e(app_config('partner_name', 'Pure Fresh')) ?>. Prototype tugas kuliah berbasis proposal awal.
        </footer>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
