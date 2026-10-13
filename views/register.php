<?php
$title = 'Daftar Karyawan';
$logbookMode = is_logbook_mode();
require __DIR__ . '/partials/header.php';
?>
<main class="flex min-h-screen items-center justify-center bg-gradient-to-b from-slate-900 to-slate-950 px-5 py-12 relative overflow-hidden font-sans">
    <!-- Decorative background glow -->
    <div class="absolute top-[-10%] right-[-10%] h-[300px] w-[300px] rounded-full bg-emerald-500/10 blur-[80px]"></div>
    <div class="absolute bottom-[-10%] left-[-10%] h-[300px] w-[300px] rounded-full bg-teal-500/10 blur-[80px]"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo Header -->
        <div class="flex flex-col items-center mb-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-white shadow-xl shadow-emerald-500/20 mb-3">
                <i data-lucide="building-2" class="h-6 w-6"></i>
            </div>
            <h2 class="text-xs uppercase tracking-[0.2em] font-bold text-slate-400">Portal Karyawan</h2>
            <h1 class="text-2xl font-extrabold text-white font-outfit mt-1"><?= e(get_app_name()) ?></h1>
        </div>

        <form action="<?= base_url('api/register.php') ?>" method="post" enctype="multipart/form-data" class="rounded-3xl border border-white/10 bg-white/[0.03] p-8 shadow-2xl backdrop-blur-md">
            <?= csrf_field() ?>
            <div>
                <p class="text-xs uppercase tracking-[.2em] text-emerald-400 font-bold">Registrasi</p>
                <h2 class="mt-1 text-xl font-bold text-white font-outfit">Buat Akun Karyawan Baru</h2>
            </div>
            
            <!-- Nama Lengkap -->
            <div class="mt-5">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Nama Lengkap</label>
                <div class="relative mt-2">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i data-lucide="user" class="h-4 w-4"></i>
                    </div>
                    <input name="nama" required placeholder="Nama Lengkap Anda" class="w-full rounded-xl border border-white/10 bg-white/5 pl-10 pr-4 py-3 text-white outline-none focus:border-emerald-500 focus:bg-white/[0.07] transition-all text-sm">
                </div>
            </div>

            <!-- Nomor HP -->
            <div class="mt-4">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Nomor HP</label>
                <div class="relative mt-2">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i data-lucide="phone" class="h-4 w-4"></i>
                    </div>
                    <input type="tel" name="no_hp" required inputmode="numeric" pattern="[0-9]+" placeholder="081234567890" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full rounded-xl border border-white/10 bg-white/5 pl-10 pr-4 py-3 text-white outline-none focus:border-emerald-500 focus:bg-white/[0.07] transition-all text-sm">
                </div>
            </div>

            <!-- Password -->
            <div class="mt-4" x-data="{ showPassword: false }">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Password</label>
                <div class="relative mt-2">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i data-lucide="key-round" class="h-4 w-4"></i>
                    </div>
                    <input :type="showPassword ? 'text' : 'password'" name="password" required placeholder="••••••" class="w-full rounded-xl border border-white/10 bg-white/5 pl-10 pr-10 py-3 text-white outline-none focus:border-emerald-500 focus:bg-white/[0.07] transition-all text-sm">
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-white transition-colors">
                        <i data-lucide="eye" class="h-4.5 w-4.5" x-show="!showPassword"></i>
                        <i data-lucide="eye-off" class="h-4.5 w-4.5" x-show="showPassword" style="display: none;"></i>
                    </button>
                </div>
            </div>

            <?php if ($logbookMode): ?>
            <input type="hidden" name="bank_name" value="">
            <input type="hidden" name="norek" value="">
            <?php else: ?>
            <!-- Bank / E-Wallet -->
            <div x-data="{ selectedBank: '', customBank: '' }" class="mt-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Metode Pembayaran (Bank / E-Wallet)</label>
                    <div class="relative mt-2">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i data-lucide="wallet" class="h-4 w-4"></i>
                        </div>
                        <select :name="selectedBank !== 'Lainnya' ? 'bank_name' : ''" 
                                x-model="selectedBank"
                                required 
                                class="w-full rounded-xl border border-white/10 bg-slate-900 pl-10 pr-4 py-3 text-white outline-none focus:border-emerald-500 focus:bg-slate-800 transition-all text-sm appearance-none">
                            <option value="" disabled selected class="text-slate-500">Pilih Bank / E-Wallet</option>
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
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Nama Bank / E-Wallet Manual</label>
                    <div class="relative mt-2">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i data-lucide="edit-3" class="h-4 w-4"></i>
                        </div>
                        <input type="text" 
                               :name="selectedBank === 'Lainnya' ? 'bank_name' : ''" 
                               x-model="customBank"
                               placeholder="Masukkan nama bank/e-wallet" 
                               :required="selectedBank === 'Lainnya'"
                               class="w-full rounded-xl border border-white/10 bg-white/5 pl-10 pr-4 py-3 text-white outline-none focus:border-emerald-500 focus:bg-white/[0.07] transition-all text-sm">
                    </div>
                </div>
            </div>

            <!-- Nomor Rekening / E-Wallet -->
            <div class="mt-4">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Nomor Rekening / No. E-Wallet</label>
                <div class="relative mt-2">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i data-lucide="credit-card" class="h-4 w-4"></i>
                    </div>
                    <input name="norek" required placeholder="Contoh: 1234567890" class="w-full rounded-xl border border-white/10 bg-white/5 pl-10 pr-4 py-3 text-white outline-none focus:border-emerald-500 focus:bg-white/[0.07] transition-all text-sm">
                </div>
            </div>
            <?php endif; ?>

            <!-- Foto Profil -->
            <div class="mt-4">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Foto Profil</label>
                <div class="relative mt-2">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i data-lucide="image" class="h-4 w-4"></i>
                    </div>
                    <input name="foto_profil" type="file" accept="image/*" class="w-full rounded-xl border border-white/10 bg-white/5 pl-10 pr-4 py-2.5 text-white outline-none focus:border-emerald-500 focus:bg-white/[0.07] transition-all text-xs file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-500/20 file:text-emerald-400 hover:file:bg-emerald-500/30">
                </div>
            </div>

            <?php if (!$logbookMode): ?>
            <!-- Tanggal Lahir -->
            <div class="mt-4">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Tanggal Lahir</label>
                <div class="relative mt-2">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i data-lucide="calendar" class="h-4 w-4"></i>
                    </div>
                    <input name="tanggal_lahir" type="date" class="w-full rounded-xl border border-white/10 bg-white/5 pl-10 pr-4 py-3 text-white outline-none focus:border-emerald-500 focus:bg-white/[0.07] transition-all text-sm [color-scheme:dark]">
                </div>
                <p class="mt-1 text-[10px] text-slate-500">Opsional — dapat dilengkapi nanti</p>
            </div>

            <!-- Foto KTP -->
            <div class="mt-4">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Foto KTP</label>
                <div class="relative mt-2">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i data-lucide="id-card" class="h-4 w-4"></i>
                    </div>
                    <input name="foto_ktp" type="file" accept="image/*" class="w-full rounded-xl border border-white/10 bg-white/5 pl-10 pr-4 py-2.5 text-white outline-none focus:border-emerald-500 focus:bg-white/[0.07] transition-all text-xs file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-500/20 file:text-indigo-400 hover:file:bg-indigo-500/30">
                </div>
                <p class="mt-1 text-[10px] text-slate-500">Opsional — pastikan foto jelas dan terbaca</p>
            </div>
            <?php endif; ?>

            <button class="btn-press mt-6 w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 transition-all hover:opacity-90 hover:scale-[1.01]">
                Kirim Registrasi
            </button>
            
            <p class="mt-5 text-center text-xs text-slate-400">
                Sudah terdaftar? <a class="font-bold text-emerald-400 hover:underline" href="<?= route_url('login') ?>">Login Disini</a>
            </p>
        </form>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
