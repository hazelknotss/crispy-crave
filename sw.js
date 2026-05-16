/**
 * Minimal service worker: enables “Add to Home Screen” / PWA install on many browsers,
 * light offline fallback to the cached home page.
 */
const CACHE_NAME = 'crispy-crave-pwa-v1';

const PRECACHE_REL = [
  './index.php',
  './css/style.css',
  './manifest.php',
  './images/official_logo.png',
  './images/logo.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches
      .open(CACHE_NAME)
      .then((cache) =>
        Promise.all(
          PRECACHE_REL.map((rel) => {
            const url = new URL(rel, self.location).href;
            return cache.add(url).catch(() => {});
          })
        )
      )
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
        )
      )
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }
  event.respondWith(
    fetch(event.request)
      .then((res) => res)
      .catch(() =>
        caches.match(event.request).then((cached) => {
          if (cached) {
            return cached;
          }
          return caches.match(new URL('./index.php', self.location));
        })
      )
  );
});
