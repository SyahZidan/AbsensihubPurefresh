(function () {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta || !meta.content) return;

    const token = meta.content;
    const originalFetch = window.fetch;

    window.fetch = function (url, options) {
        options = options || {};
        const method = (options.method || 'GET').toUpperCase();

        if (method !== 'GET' && method !== 'HEAD') {
            if (options.body instanceof FormData) {
                if (!options.body.has('csrf_token')) {
                    options.body.append('csrf_token', token);
                }
            } else {
                options.headers = options.headers || {};
                if (options.headers instanceof Headers) {
                    if (!options.headers.has('X-CSRF-TOKEN')) {
                        options.headers.set('X-CSRF-TOKEN', token);
                    }
                } else {
                    options.headers['X-CSRF-TOKEN'] = options.headers['X-CSRF-TOKEN'] || token;
                }
            }
        }

        return originalFetch.call(this, url, options);
    };
})();
