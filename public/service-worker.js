const CACHE_NAME = 'imanage-core-v1';
const CORE_ASSETS = [
  '/',
  '/manifest.json',
  '/css/style.css',
  '/css/gallery.css',
  '/js/app.js',
  '/offline.html'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(CORE_ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))).then(() => self.clients.claim())
  );
});

// Network falling back to cache for API/image requests; cache falling back to offline page for navigations
self.addEventListener('fetch', event => {
  const req = event.request;
  const url = new URL(req.url);

  // Handle navigation requests (HTML pages)
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() => caches.match('/offline.html'))
    );
    return;
  }

  // For images in uploads: cache-first then network
  if (url.pathname.startsWith('/uploads/') || url.pathname.includes('/public/uploads/')) {
    event.respondWith(
      caches.open('imanage-images').then(cache => cache.match(req).then(match => {
        return match || fetch(req).then(res => {
          if (res.ok) cache.put(req, res.clone());
          return res;
        });
      }))
    );
    return;
  }

  // For other requests: try network first, fall back to cache
  event.respondWith(
    fetch(req).then(res => {
      if (res.ok) {
        const clone = res.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(req, clone));
      }
      return res;
    }).catch(() => caches.match(req))
  );
});
