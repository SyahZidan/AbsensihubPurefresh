document.querySelectorAll('[data-camera-form]').forEach((form) => {
    const video = form.querySelector('video');
    const canvas = form.querySelector('canvas');
    const photoInput = form.querySelector('input[name="photo_data"]');
    const latInput = form.querySelector('input[name="lat"]');
    const lngInput = form.querySelector('input[name="lng"]');
    const button = form.querySelector('[data-capture]');
    const status = form.querySelector('[data-camera-status]');

    if (!navigator.mediaDevices || !navigator.geolocation) {
        status.textContent = 'Kamera atau lokasi tidak tersedia di browser ini.';
        return;
    }

    navigator.geolocation.getCurrentPosition((position) => {
        latInput.value = position.coords.latitude;
        lngInput.value = position.coords.longitude;
    }, () => {
        status.textContent = 'Aktifkan izin lokasi untuk absen.';
    });

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
        .then((stream) => {
            video.srcObject = stream;
            status.textContent = 'Kamera siap. Ambil foto saat wajah terlihat jelas.';
            window.cameraReady = true;
            if (window.onCameraReady) {
                window.onCameraReady();
            } else {
                button.disabled = false;
            }
        })
        .catch(() => {
            status.textContent = 'Tidak bisa membuka kamera.';
        });

    button.addEventListener('click', () => {
        if (typeof window.beforeCaptureSubmit === 'function' && window.beforeCaptureSubmit(form) === false) {
            return;
        }

        // Disable button to prevent double submission and provide visual feedback
        button.disabled = true;
        
        // Show loading spinner on the button
        button.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Memproses...
        `;

        if (status) {
            status.textContent = 'Memproses foto dan mengirim absensi...';
            const statusWrap = document.getElementById('cameraStatusWrap');
            if (statusWrap) {
                statusWrap.className = 'flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-700';
                const pulseDot = statusWrap.querySelector('.animate-pulse');
                if (pulseDot) {
                    pulseDot.className = 'h-2 w-2 rounded-full bg-blue-500 animate-pulse';
                }
            }
        }

        // Delay slightly to allow the DOM to render the loading state
        setTimeout(() => {
            const context = canvas.getContext('2d');
            
            // Set maximum dimensions for the attendance selfie (e.g., 500px is more than enough for clear facial features)
            const MAX_DIM = 500;
            let w = video.videoWidth || 640;
            let h = video.videoHeight || 480;
            
            if (w > h) {
                if (w > MAX_DIM) {
                    h = Math.round((h * MAX_DIM) / w);
                    w = MAX_DIM;
                }
            } else {
                if (h > MAX_DIM) {
                    w = Math.round((w * MAX_DIM) / h);
                    h = MAX_DIM;
                }
            }
            
            canvas.width = w;
            canvas.height = h;
            
            // Mirror the captured image to match the visual preview (mirror mode)
            context.translate(w, 0);
            context.scale(-1, 1);
            context.drawImage(video, 0, 0, w, h);
            
            // Compress image using JPEG format with 0.70 quality
            // This drastically reduces payload size from ~500KB+ to ~20-30KB,
            // which uploads virtually instantly even on InfinityFree
            photoInput.value = canvas.toDataURL('image/jpeg', 0.70);
            
            form.submit();
        }, 100);
    });
});
