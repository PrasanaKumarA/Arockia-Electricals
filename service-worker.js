// ============================================================
// Arockia Electricals - Service Worker (PWA)
// Cache-first for assets, network-first for PHP pages
// ============================================================

const CACHE_NAME = 'arockia-v1.0.0';
const STATIC_CACHE = [
    '/Arockia-Electricals/',
    '/Arockia-Electricals/auth/login.php',
    '/Arockia-Electricals/assets/css/custom.css',
    '/Arockia-Electricals/assets/js/app.js',
    '/Arockia-Electricals/assets/images/logo.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
    'https://code.jquery.com/jquery-3.7.1.min.js',
];

// Install - Cache static assets
self.addEventListener('install', event => {
    console.log('[SW] Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(STATIC_CACHE))
            .then(() => self.skipWaiting())
    );
});

// Activate - Cleanup old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
        )).then(() => self.clients.claim())
    );
});

// Fetch strategy
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Cache-first for static assets (CSS, JS, images, fonts)
    if (event.request.destination === 'style' ||
        event.request.destination === 'script' ||
        event.request.destination === 'image' ||
        event.request.destination === 'font') {
        event.respondWith(
            caches.match(event.request).then(cached => {
                return cached || fetch(event.request).then(response => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    return response;
                });
            })
        );
        return;
    }

    // Network-first for PHP pages
    if (url.pathname.endsWith('.php') || url.pathname === '/Arockia-Electricals/') {
        event.respondWith(
            fetch(event.request).then(response => {
                return response;
            }).catch(() => {
                return caches.match(event.request).then(cached => {
                    return cached || caches.match('/Arockia-Electricals/auth/login.php');
                });
            })
        );
        return;
    }

    // Default: network-first
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});
