const VERSION = 'v2';
const CORE_CACHE = `imanage-core-${VERSION}`;
const IMAGE_CACHE = `imanage-images-${VERSION}`;
const API_CACHE = `imanage-api-${VERSION}`;
const CORE_ASSETS = [
  '/',
  '/manifest.json',
  '/css/style.css',
  '/css/gallery.css',
  '/js/app.js',
  '/offline.html'
];
// Max entries for runtime caches
const MAX_IMAGES = 60; // thumbnails & viewed images
const MAX_API = 40; // metadata responses

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CORE_CACHE)
      .then(cache => cache.addAll(CORE_ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(k => ![CORE_CACHE, IMAGE_CACHE, API_CACHE].includes(k)).map(k => caches.delete(k))
    )).then(() => self.clients.claim())
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

  // Image caching (cache-first)
  if (url.pathname.startsWith('/uploads/') || url.pathname.includes('/public/uploads/')) {
    event.respondWith(
      caches.open(IMAGE_CACHE).then(cache => cache.match(req).then(match => {
        if (match) return match;
        return fetch(req).then(res => {
          if (res.ok) {
            cache.put(req, res.clone());
            enforceLimit(cache, MAX_IMAGES);
          }
          return res;
        });
      }))
    );
    return;
  }

  // API responses (network-first with fallback & stale-while-revalidate flavor)
  if (url.pathname.endsWith('api.php')) {
    event.respondWith(
      fetch(req).then(res => {
        if (res.ok) {
          const clone = res.clone();
          caches.open(API_CACHE).then(cache => {
            cache.put(req, clone);
            enforceLimit(cache, MAX_API);
          });
        }
        return res;
      }).catch(() => caches.open(API_CACHE).then(cache => cache.match(req)))
    );
    return;
  }

  // Other assets (network-first, fallback to cache)
  event.respondWith(
    fetch(req).then(res => {
      if (res.ok) {
        const clone = res.clone();
        caches.open(CORE_CACHE).then(cache => cache.put(req, clone));
      }
      return res;
    }).catch(() => caches.match(req))
  );
});

function enforceLimit(cache, max) {
  cache.keys().then(keys => {
    if (keys.length > max) {
      const toDelete = keys.slice(0, keys.length - max);
      toDelete.forEach(k => cache.delete(k));
    }
  });
}
