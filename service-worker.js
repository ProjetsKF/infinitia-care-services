var CACHE_NAME = "infinitia-care-static-v6";
var CACHE_PREFIX = "infinitia-care-static-";
var APP_SCOPE = self.registration.scope;
var OFFLINE_URL = new URL("offline.php", APP_SCOPE).toString();
var ASSETS_PATH = new URL("assets/", APP_SCOPE).pathname;

var PRECACHE_URLS = [
    OFFLINE_URL,
    new URL("assets/css/style.css", APP_SCOPE).toString(),
    new URL("assets/js/pwa.js", APP_SCOPE).toString(),
    new URL("assets/images/pwa/icon-192.png", APP_SCOPE).toString(),
    new URL("assets/images/pwa/icon-512.png", APP_SCOPE).toString(),
    new URL("assets/images/pwa/icon-maskable-192.png", APP_SCOPE).toString(),
    new URL("assets/images/pwa/icon-maskable-512.png", APP_SCOPE).toString()
];

self.addEventListener("install", function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(PRECACHE_URLS);
        }).then(function () {
            return self.skipWaiting();
        })
    );
});

self.addEventListener("activate", function (event) {
    event.waitUntil(
        caches.keys().then(function (cacheNames) {
            return Promise.all(cacheNames.map(function (cacheName) {
                if (cacheName.indexOf(CACHE_PREFIX) === 0 && cacheName !== CACHE_NAME) {
                    return caches.delete(cacheName);
                }
                return Promise.resolve(false);
            }));
        }).then(function () {
            return self.clients.claim();
        })
    );
});

function isSafeStaticAsset(url) {
    if (url.search !== "" || url.pathname.indexOf(ASSETS_PATH) !== 0) {
        return false;
    }
    return /\.(?:css|js|png|jpg|jpeg|gif|webp|svg|ico|woff|woff2|ttf)$/i.test(url.pathname);
}

function cacheFirst(request) {
    return caches.match(request).then(function (cachedResponse) {
        if (cachedResponse) {
            return cachedResponse;
        }
        return fetch(request).then(function (networkResponse) {
            if (networkResponse && networkResponse.ok && networkResponse.type === "basic") {
                caches.open(CACHE_NAME).then(function (cache) {
                    cache.put(request, networkResponse.clone());
                });
            }
            return networkResponse;
        });
    });
}

self.addEventListener("fetch", function (event) {
    var request = event.request;
    var requestUrl;

    // Les POST, formulaires et autres écritures restent entièrement gérés par le réseau.
    if (request.method !== "GET") {
        return;
    }

    requestUrl = new URL(request.url);

    // Aucune ressource externe n'est placée dans le cache de l'application.
    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    if (request.mode === "navigate") {
        // Les pages HTML, notamment les espaces privés, ne sont jamais mises en cache.
        event.respondWith(fetch(request).catch(function () {
            return caches.match(OFFLINE_URL);
        }));
        return;
    }

    // Seuls les fichiers statiques sans paramètres du dossier assets sont cacheables.
    if (isSafeStaticAsset(requestUrl)) {
        event.respondWith(cacheFirst(request));
    }
});
