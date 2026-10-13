/**
 * Alert & konfirmasi terpusat (SweetAlert2).
 * Flash session: <script id="app-flash-data" type="application/json">...</script>
 */
(function (global) {
    const confirmColor = '#10b981';
    const cancelColor = '#64748b';
    const dangerColor = '#ef4444';

    function hasSwal() {
        return typeof global.Swal !== 'undefined';
    }

    function fallbackAlert(text) {
        global.alert(text);
        return Promise.resolve({ isConfirmed: true, value: '' });
    }

    function fire(options) {
        if (!hasSwal()) {
            fallbackAlert(options.text || options.title || '');
            return Promise.resolve({ isConfirmed: true, value: options.inputValue || '' });
        }
        return global.Swal.fire(options);
    }

    const AppAlert = {
        success(message, title) {
            title = title || 'Berhasil';
            if (!message) return Promise.resolve();
            return fire({
                icon: 'success',
                title: title,
                text: message,
                confirmButtonText: 'OK',
                confirmButtonColor: confirmColor,
            });
        },

        error(message, title) {
            title = title || 'Gagal';
            if (!message) return Promise.resolve();
            return fire({
                icon: 'error',
                title: title,
                text: message,
                confirmButtonText: 'OK',
                confirmButtonColor: dangerColor,
            });
        },

        info(message, title) {
            title = title || 'Info';
            return fire({
                icon: 'info',
                title: title,
                text: message,
                confirmButtonText: 'OK',
                confirmButtonColor: confirmColor,
            });
        },

        toast(message, type) {
            type = type || 'success';
            if (!message) return Promise.resolve();
            if (!hasSwal()) {
                return fallbackAlert(message);
            }
            const Toast = global.Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: type === 'error' ? 5500 : 4500,
                timerProgressBar: true,
                didOpen: function (toast) {
                    toast.addEventListener('mouseenter', global.Swal.stopTimer);
                    toast.addEventListener('mouseleave', global.Swal.resumeTimer);
                },
            });
            return Toast.fire({
                icon: type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'success',
                title: message,
            });
        },

        confirm(text, options) {
            options = options || {};
            return fire({
                icon: options.icon || 'question',
                title: options.title || 'Konfirmasi',
                text: text,
                showCancelButton: true,
                confirmButtonText: options.confirmText || 'Ya',
                cancelButtonText: options.cancelText || 'Batal',
                confirmButtonColor: options.danger ? dangerColor : confirmColor,
                cancelButtonColor: cancelColor,
                reverseButtons: true,
            }).then(function (r) {
                return r.isConfirmed;
            });
        },

        confirmDanger(text, options) {
            options = options || {};
            return AppAlert.confirm(text, {
                icon: 'warning',
                title: options.title || 'Perhatian',
                confirmText: options.confirmText || 'Ya, lanjutkan',
                danger: true,
            });
        },

        prompt(text, options) {
            options = options || {};
            return fire({
                icon: 'question',
                title: options.title || 'Catatan',
                text: text,
                input: options.input || 'text',
                inputPlaceholder: options.placeholder || 'Opsional…',
                inputValue: options.value || '',
                showCancelButton: true,
                confirmButtonText: options.confirmText || 'Simpan',
                cancelButtonText: 'Batal',
                confirmButtonColor: confirmColor,
                cancelButtonColor: cancelColor,
            }).then(function (r) {
                return r.isConfirmed ? (r.value ?? '') : null;
            });
        },

        fromResponse(d, options) {
            options = options || {};
            if (d && d.success) {
                const msg = d.message || options.successMsg || 'Berhasil';
                if (options.reload) {
                    return AppAlert.success(msg).then(function () {
                        location.reload();
                    });
                }
                if (options.redirect) {
                    return AppAlert.success(msg).then(function () {
                        location.href = options.redirect;
                    });
                }
                return AppAlert.success(msg);
            }
            return AppAlert.error((d && d.message) || options.errorMsg || 'Terjadi kesalahan.');
        },

        initFlash() {
            const el = document.getElementById('app-flash-data');
            if (!el) return;

            let data = {};
            try {
                data = JSON.parse(el.textContent || '{}');
            } catch (e) {
                return;
            }

            const long = function (s) {
                return typeof s === 'string' && s.length > 100;
            };

            if (data.success) {
                if (long(data.success)) {
                    AppAlert.success(data.success);
                } else {
                    AppAlert.toast(data.success, 'success');
                }
            }
            if (data.error) {
                if (long(data.error)) {
                    AppAlert.error(data.error);
                } else {
                    AppAlert.toast(data.error, 'error');
                }
            }
            if (data.warning) {
                AppAlert.toast(data.warning, 'warning');
            }
            if (data.info) {
                AppAlert.info(data.info);
            }
        },
    };

    global.AppAlert = AppAlert;

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form || !form.getAttribute) return;
        const msg = form.getAttribute('data-swal-confirm');
        if (!msg) return;
        if (form.dataset.swalConfirmed === '1') return;

        e.preventDefault();
        AppAlert.confirm(msg).then(function (ok) {
            if (ok) {
                form.dataset.swalConfirmed = '1';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        AppAlert.initFlash();
    });
})(window);
