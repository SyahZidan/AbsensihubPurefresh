<?php $title = 'Lupa Password'; require __DIR__ . '/partials/header.php'; ?>
<main class="flex min-h-screen items-center justify-center bg-gradient-to-b from-slate-900 to-slate-950 px-5">
    <div class="w-full max-w-md rounded-3xl border border-white/10 bg-white/[0.03] p-8 shadow-2xl backdrop-blur-md">
        <h1 class="text-xl font-bold text-white font-outfit">Lupa Password</h1>
        <p class="mt-2 text-sm text-slate-400">Masukkan nomor HP terdaftar. Kami akan buatkan link reset sekali pakai (berlaku 1 jam).</p>
        <form action="<?= base_url('api/forgot-password.php') ?>" method="post" class="mt-6 space-y-4">
            <?= csrf_field() ?>
            <input name="no_hp" required placeholder="081234567890" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white text-sm outline-none focus:border-emerald-500">
            <button type="submit" class="btn-press w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/20 hover:opacity-95 active:scale-98 transition-all">Kirim Link Reset</button>
        </form>
        <a href="<?= route_url('login') ?>" class="mt-4 block text-center text-sm text-emerald-400 hover:text-emerald-300">Kembali ke login</a>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
