<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= asset_url('js/app-alerts.js') ?>"></script>
<script src="<?= asset_url('js/csrf-fetch.js') ?>"></script>
<script src="<?= asset_url('js/camera.js') ?>"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        lucide.createIcons();
    });

    // Global Loader & Submit Button Disabler
    document.addEventListener("submit", function(e) {
        // Cek jika submit dibatalkan oleh validator JavaScript eksternal
        if (e.defaultPrevented) return;

        const loader = document.getElementById("global-loader");
        if (loader) {
            loader.classList.remove("hidden");
        }

        // Cari form dan matikan (disable) semua tombol submit
        const form = e.target;
        const submitButtons = form.querySelectorAll("button[type='submit'], input[type='submit']");
        submitButtons.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = "0.7";
            btn.style.cursor = "not-allowed";
        });
    });

    // Global Link Click Loader (Untuk navigasi halaman yang memakan waktu)
    document.addEventListener("click", function(e) {
        // Cari elemen <a> terdekat jika yang diklik adalah ikon/teks di dalamnya
        const link = e.target.closest("a");
        if (!link) return;

        // Skip jika link:
        // 1. Membuka di tab baru (target="_blank")
        // 2. Merupakan anchor link internal (href="#")
        // 3. Merupakan kode Javascript (href="javascript:...")
        // 4. Diklik dengan menahan tombol Ctrl/Cmd (buka tab baru latar belakang)
        const href = link.getAttribute("href");
        if (!href 
            || link.getAttribute("target") === "_blank" 
            || href.startsWith("#") 
            || href.startsWith("javascript:")
            || e.metaKey 
            || e.ctrlKey 
            || e.shiftKey
        ) {
            return;
        }

        // Beri jeda 50ms untuk memastikan klik tidak dibatalkan oleh script lain (e.g. alert confirmation)
        setTimeout(() => {
            if (e.defaultPrevented) return;

            const loader = document.getElementById("global-loader");
            if (loader) {
                loader.classList.remove("hidden");
            }
        }, 50);
    });
</script>
</body>
</html>
