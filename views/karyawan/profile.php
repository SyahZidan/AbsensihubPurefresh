<?php $title = 'Profil Saya'; $active = 'profile'; require __DIR__ . '/../partials/header.php'; ?>
<main class="phone-shell pb-28 min-h-screen bg-slate-50">

    <!-- Hero Header Profile -->
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-5 pt-8 pb-16">
        <!-- Decorative blobs -->
        <div class="absolute top-0 right-0 h-32 w-32 rounded-full bg-emerald-500/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-24 w-24 rounded-full bg-teal-500/10 blur-2xl"></div>

        <div class="relative flex flex-col items-center text-center">
            <!-- Profile Avatar Frame -->
            <div class="relative">
                <div class="h-24 w-24 overflow-hidden rounded-full border-4 border-white/10 bg-slate-800 shadow-xl relative z-10 flex items-center justify-center">
                    <?php 
                    $showProfil = false;
                    if (!empty($user['foto_profil'])) {
                        $fileProfil = dirname(__DIR__, 2) . $user['foto_profil'];
                        if (file_exists($fileProfil) && is_file($fileProfil)) {
                            $showProfil = true;
                        }
                    }
                    ?>
                    <?php if ($showProfil): ?>
                        <img class="h-full w-full object-cover" src="<?= base_url(e($user['foto_profil'])) ?>" alt="Foto profil">
                    <?php else: ?>
                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-tr from-emerald-500 to-teal-400 text-white font-extrabold text-2xl font-outfit uppercase">
                            <?= substr(e($user['nama']), 0, 2) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Mini Badge Status -->
                <span class="absolute bottom-1 right-1 z-20 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 border-2 border-slate-900 shadow-md">
                    <span class="h-1.5 w-1.5 rounded-full bg-white animate-pulse"></span>
                </span>
            </div>
            
            <h1 class="mt-3 text-xl font-extrabold text-white font-outfit"><?= e($user['nama']) ?></h1>
            <p class="text-xs text-slate-400 mt-0.5"><?= e($user['no_hp']) ?></p>
            <?php if (!empty($user['tanggal_lahir'])): ?>
            <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                <i data-lucide="cake" class="h-3 w-3"></i>
                <?= date('d M Y', strtotime($user['tanggal_lahir'])) ?>
            </p>
            <?php endif; ?>
            <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-emerald-400 uppercase">
                <i data-lucide="check" class="h-3 w-3"></i> <?= e($user['status']) ?>
            </span>

            <!-- KTP Badge -->
            <?php if (!empty($user['foto_ktp'])): ?>
            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-indigo-500/20 px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-indigo-400 uppercase">
                <i data-lucide="id-card" class="h-3 w-3"></i> KTP Terdaftar
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Form Card (Pull Up) -->
    <div class="-mt-8 relative z-10 px-5 space-y-4">
        <form action="<?= base_url('api/update-profile.php') ?>" method="post" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            <?= csrf_field() ?>
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-1">
                <i data-lucide="user-cog" class="h-4.5 w-4.5 text-slate-500"></i>
                <h2 class="text-sm font-bold text-slate-800">Edit Data Profil</h2>
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Nama Lengkap</label>
                <div class="relative mt-1.5">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i data-lucide="user" class="h-4 w-4"></i>
                    </div>
                    <input type="text" name="nama" value="<?= e($user['nama']) ?>" required
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2.5 text-slate-800 outline-none focus:border-emerald-400 focus:bg-white transition-all text-sm">
                </div>
            </div>

            <!-- Nomor HP -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Nomor HP</label>
                <div class="relative mt-1.5">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i data-lucide="phone" class="h-4 w-4"></i>
                    </div>
                    <input type="text" name="no_hp" value="<?= e($user['no_hp']) ?>" required
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2.5 text-slate-800 outline-none focus:border-emerald-400 focus:bg-white transition-all text-sm">
                </div>
            </div>

            <!-- Tanggal Lahir -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Tanggal Lahir</label>
                <div class="relative mt-1.5">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i data-lucide="calendar" class="h-4 w-4"></i>
                    </div>
                    <input type="date" name="tanggal_lahir"
                           value="<?= e($user['tanggal_lahir'] ?? '') ?>"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2.5 text-slate-800 outline-none focus:border-emerald-400 focus:bg-white transition-all text-sm">
                </div>
            </div>

            <!-- Bank / E-Wallet -->
            <?php 
            $predefinedBanks = ['BCA', 'Mandiri', 'BRI', 'BNI', 'OVO', 'GoPay', 'Dana', 'LinkAja'];
            $currBank = $user['bank_name'] ?? '';
            $isCustomBank = !empty($currBank) && !in_array($currBank, $predefinedBanks);
            $selectedSelectVal = '';
            if ($currBank !== '') {
                $selectedSelectVal = $isCustomBank ? 'Lainnya' : $currBank;
            }
            ?>
            <div x-data="{ selectedBank: '<?= e($selectedSelectVal) ?>', customBank: '<?= $isCustomBank ? e($currBank) : '' ?>' }">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Metode Pembayaran (Bank / E-Wallet)</label>
                    <div class="relative mt-1.5">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="wallet" class="h-4 w-4"></i>
                        </div>
                        <select :name="selectedBank !== 'Lainnya' ? 'bank_name' : ''" 
                                x-model="selectedBank"
                                required 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-8 py-2.5 text-slate-800 outline-none focus:border-emerald-400 focus:bg-white transition-all text-sm appearance-none">
                            <option value="" disabled>Pilih Bank / E-Wallet</option>
                            <option value="BCA">BCA</option>
                            <option value="Mandiri">Mandiri</option>
                            <option value="BRI">BRI</option>
                            <option value="BNI">BNI</option>
                            <option value="OVO">OVO</option>
                            <option value="GoPay">GoPay</option>
                            <option value="Dana">Dana</option>
                            <option value="LinkAja">LinkAja</option>
                            <option value="Lainnya">Lainnya (Isi Manual)</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                            <i data-lucide="chevron-down" class="h-4 w-4"></i>
                        </div>
                    </div>
                </div>

                <!-- Custom Bank Input -->
                <div x-show="selectedBank === 'Lainnya'" x-transition class="mt-3" style="display: none;">
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Nama Bank / E-Wallet Manual</label>
                    <div class="relative mt-1.5">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <i data-lucide="edit-3" class="h-4 w-4"></i>
                        </div>
                        <input type="text" 
                               :name="selectedBank === 'Lainnya' ? 'bank_name' : ''" 
                               x-model="customBank"
                               placeholder="Masukkan nama bank/e-wallet" 
                               :required="selectedBank === 'Lainnya'"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2.5 text-slate-800 outline-none focus:border-emerald-400 focus:bg-white transition-all text-sm">
                    </div>
                </div>
            </div>

            <!-- Nomor Rekening / E-Wallet -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Nomor Rekening / No. E-Wallet</label>
                <div class="relative mt-1.5">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i data-lucide="credit-card" class="h-4 w-4"></i>
                    </div>
                    <input type="text" name="norek" value="<?= e($user['norek'] ?? '') ?>" required
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2.5 text-slate-800 outline-none focus:border-emerald-400 focus:bg-white transition-all text-sm">
                </div>
            </div>

            <!-- Foto Profil -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Ubah Foto Profil</label>
                <div class="relative mt-1.5">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i data-lucide="image" class="h-4 w-4"></i>
                    </div>
                    <input type="file" name="foto_profil" accept="image/*"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2 text-slate-800 outline-none focus:border-emerald-400 focus:bg-white transition-all text-xs file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200">
                </div>
            </div>

            <!-- Foto KTP -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                    Foto KTP
                    <?php if (!empty($user['foto_ktp'])): ?>
                    <span class="ml-1 text-indigo-500 font-normal normal-case">(sudah ada — ganti jika perlu)</span>
                    <?php endif; ?>
                </label>
                <?php 
                $showKtp = false;
                if (!empty($user['foto_ktp'])) {
                    $fileKtp = dirname(__DIR__, 2) . $user['foto_ktp'];
                    if (file_exists($fileKtp) && is_file($fileKtp)) {
                        $showKtp = true;
                    }
                }
                ?>
                <?php if ($showKtp): ?>
                <div class="mt-1.5 mb-2">
                    <a href="<?= base_url(e($user['foto_ktp'])) ?>" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-100 transition">
                        <i data-lucide="eye" class="h-3.5 w-3.5"></i> Lihat KTP Saat Ini
                    </a>
                </div>
                <?php elseif (!empty($user['foto_ktp'])): ?>
                <div class="mt-1.5 mb-2">
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600">
                        <i data-lucide="alert-triangle" class="h-3.5 w-3.5 text-rose-500"></i> Berkas KTP Hilang / Rusak (Harap Unggah Ulang)
                    </span>
                </div>
                <?php endif; ?>
                <div class="relative mt-1.5">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i data-lucide="id-card" class="h-4 w-4"></i>
                    </div>
                    <input type="file" name="foto_ktp" accept="image/*"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2 text-slate-800 outline-none focus:border-indigo-400 focus:bg-white transition-all text-xs file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200">
                </div>
                <p class="mt-1 text-[10px] text-slate-400">Pastikan foto KTP jelas dan terbaca</p>
            </div>

            <!-- Kata Sandi Baru -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Kata Sandi Baru</label>
                <div class="relative mt-1.5">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i data-lucide="key-round" class="h-4 w-4"></i>
                    </div>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2.5 text-slate-800 outline-none focus:border-emerald-400 focus:bg-white transition-all text-sm">
                </div>
                <p class="mt-1 text-[10px] text-slate-400">Biarkan kosong jika tidak ingin mengganti kata sandi Anda</p>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="btn-press mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/20 transition-all hover:opacity-90">
                <i data-lucide="save" class="h-4.5 w-4.5"></i>
                Simpan Perubahan
            </button>
        </form>

        <!-- Logout Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <a href="<?= base_url('api/logout.php') ?>" 
               class="btn-press flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50/50 py-3 text-sm font-bold text-rose-600 hover:bg-rose-50 transition-all">
                <i data-lucide="log-out" class="h-4.5 w-4.5"></i>
                Keluar Dari Akun
            </a>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../partials/employee_nav.php'; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
