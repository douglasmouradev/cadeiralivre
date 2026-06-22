self.addEventListener('install', (e) => {
  e.waitUntil(caches.open('cadeiralivre-v1').then((c) => c.addAll(['/assets/css/app.css', '/assets/js/app.js'])));
});
self.addEventListener('fetch', (e) => {
  e.respondWith(caches.match(e.request).then((r) => r || fetch(e.request)));
});
