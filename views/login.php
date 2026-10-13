<?php $title = 'Login'; require __DIR__ . '/partials/header.php'; ?>
<main class="flex min-h-screen items-center justify-center bg-gradient-to-b from-slate-900 to-slate-950 px-5 relative overflow-hidden font-sans">
    <!-- Decorative background glow -->
    <div class="absolute top-[-10%] right-[-10%] h-[300px] w-[300px] rounded-full bg-emerald-500/10 blur-[80px]"></div>
    <div class="absolute bottom-[-10%] left-[-10%] h-[300px] w-[300px] rounded-full bg-teal-500/10 blur-[80px]"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo Header -->
        <div class="flex flex-col items-center mb-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-white shadow-xl shadow-emerald-500/20 mb-3">
                <i data-lucide="building-2" class="h-6 w-6"></i>
            </div>
            <h2 class="text-xs uppercase tracking-[0.2em] font-bold text-slate-400">Portal Karyawan & Admin</h2>
            <h1 class="text-2xl font-extrabold text-white font-outfit mt-1"><?= e(get_app_name()) ?></h1>
        </div>

        <form action="<?= base_url('api/login.php') ?>" method="post" class="rounded-3xl border border-white/10 bg-white/[0.03] p-8 shadow-2xl backdrop-blur-md">
            <?= csrf_field() ?>
            <div>
                <p class="text-xs uppercase tracking-[.2em] text-emerald-400 font-bold">Masuk</p>
                <h2 class="mt-1 text-xl font-bold text-white font-outfit">Silakan masukkan akun Anda</h2>
            </div>
            
            <div class="mt-6">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Nomor HP</label>
                <div class="relative mt-2">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i data-lucide="phone" class="h-4 w-4"></i>
                    </div>
                    <input type="tel" name="no_hp" required inputmode="numeric" pattern="[0-9]+" placeholder="081234567890" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full rounded-xl border border-white/10 bg-white/5 pl-10 pr-4 py-3.5 text-white outline-none focus:border-emerald-500 focus:bg-white/[0.07] transition-all text-sm">
                </div>
            </div>

            <div class="mt-4" x-data="{ showPassword: false }">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Password</label>
                <div class="relative mt-2">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i data-lucide="key-round" class="h-4 w-4"></i>
                    </div>
                    <input :type="showPassword ? 'text' : 'password'" name="password" required placeholder="••••••" class="w-full rounded-xl border border-white/10 bg-white/5 pl-10 pr-10 py-3.5 text-white outline-none focus:border-emerald-500 focus:bg-white/[0.07] transition-all text-sm">
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-white transition-colors">
                        <i data-lucide="eye" class="h-4.5 w-4.5" x-show="!showPassword"></i>
                        <i data-lucide="eye-off" class="h-4.5 w-4.5" x-show="showPassword" style="display: none;"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-press w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-500/20 hover:opacity-95 active:scale-98 transition-all mt-6">
                Masuk Sekarang
            </button>
            
            <p class="mt-3 text-center text-xs">
                <a class="text-slate-400 hover:text-emerald-400" href="<?= route_url('forgot-password') ?>">Lupa password?</a>
            </p>
            <p class="mt-3 text-center text-xs text-slate-400">
                Belum punya akun karyawan? <a class="font-bold text-emerald-400 hover:underline" href="<?= route_url('register') ?>">Daftar Disini</a>
            </p>
        </form>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
