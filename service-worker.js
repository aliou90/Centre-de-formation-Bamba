const cacheName = 'bamba-formation-cache-v1';
const filesToCache = [
  // Ajout des fichiers critiques (indépendants pour le bon chargement et la mise en cache)
  // Fichiers PHP et txt
  //'/index.php',

  // Fichier CSS 
  '/assets/css/bootstrap.min.css',
  '/assets/css/bootstrap.min.css.map',
  '/assets/css/all.min.css', // Font Awesome
  '/assets/css/introjs.min.css',
  '/assets/css/introjs.min.css.map',
  '/assets/css/styles.css',

  // Fichiers JS
  '/assets/js/bootstrap.bundle.min.js',
  '/assets/js/bootstrap.bundle.min.js.map',
  '/assets/js/bootstrap.min.js',
  '/assets/js/bootstrap.min.js.map',
  '/assets/js/all.min.js',  // Font Awesome
  '/assets/js/introjs.min.js',
  '/assets/js/jquery.min.js',
  '/assets/js/jquery.min.map',
  '/assets/js/popper.min.js',
  '/assets/js/popper.min.js.map',
  '/assets/js/script.js',
  '/assets/js/wavesurfer.min.js',
  '/assets/js/wavesurfer.min.js.map',
  
  // FONTS
  '/assets/webfonts/AlMushafQuran.ttf',
  '/assets/webfonts/Neirizi.ttf',
  '/assets/webfonts/LateefRegular.ttf',
  '/assets/webfonts/AlMushafQuran.ttf',
  '/assets/webfonts/fa-solid-900.ttf',    // font Awesome
  '/assets/webfonts/fa-solid-900.woff2',  // font Awesome

  // LOGOS
  '/assets/images/logos/logo.png',

  // ICONES & ICÔNES
  '/assets/images/icons/icon-192x192.png',
  '/assets/images/icons/icon-512x512.png',
  '/assets/images/icons/icon-offline.png',
  '/assets/images/icons/icon-online.png',
  '/assets/images/icons/icon-whatsapp.png',
  '/assets/images/icons/icon-mail.png',
  '/assets/images/icons/om.png',
  '/assets/images/icons/wave.png',
  '/assets/images/icons/icon-pwa-install.png',
  
  '/assets/images/covers/defaultPage.png',
  
  // Ajoute d'autres fichiers critiques si nécessaires
];

// Installation et mise en cache initiale
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(cacheName).then(async (cache) => {
      console.log("Caching files...");
      for (let file of filesToCache) {
        try {
          await cache.add(file);
          console.log(`✅ Cached: ${file}`);
        } catch (e) {
          console.warn(`❌ Failed to cache: ${file}`, e);
        }
      }
    })
  );
});

// Activation du service worker
self.addEventListener('activate', (event) => {
  const cacheWhitelist = [cacheName];
  event.waitUntil(
    caches.keys().then((cacheNames) =>
      Promise.all(
        cacheNames.map((key) => {
          if (!cacheWhitelist.includes(key)) {
            console.log(`Deleting old cache: ${key}`);
            return caches.delete(key);
          }
        })
      )
    )
  );
  self.clients.claim();
});

// Interception des requêtes réseau avec priorité au cache
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    caches.open(cacheName).then((cache) =>
      cache.match(event.request).then((response) => {
        const fetchPromise = fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.ok) {
            cache.put(event.request, networkResponse.clone());
          }
          return networkResponse;
        });
        return response || fetchPromise;
      })
    )
  );
});
