<?php
$title = 'Reset Password';
$token = trim($_GET['token'] ?? '');
require __DIR__ . '/partials/header.php';
?>
<main class="flex min-h-screen items-center justify-center bg-gradient-to-b from-slate-900 to-slate-950 px-5">
    <div class="w-full max-w-md rounded-3xl border border-white/10 bg-white/[0.03] p-8 shadow-2xl backdrop-blur-md">
        <h1 class="text-xl font-bold text-white font-outfit">Reset Password</h1>
        <p class="mt-2 text-sm text-slate-400">Masukkan password baru Anda.</p>
        <?php if ($token === ''): ?>
        <p class="mt-4 text-sm text-rose-400">Token tidak valid. Minta link baru dari halaman lupa password.</p>
        <?php else: ?>
        <form action="<?= base_url('api/reset-password.php') ?>" method="post" class="mt-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <input type="password" name="password" required minlength="6" placeholder="Password baru min. 6 karakter" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white text-sm outline-none focus:border-emerald-500">
            <input type="password" name="password_confirm" required minlength="6" placeholder="Ulangi password" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white text-sm outline-none focus:border-emerald-500">
            <button type="submit" class="btn-press w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/20 hover:opacity-95 active:scale-98 transition-all">Simpan Password</button>
        </form>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
