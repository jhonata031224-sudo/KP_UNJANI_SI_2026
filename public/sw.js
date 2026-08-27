// Service worker SIBERAD.
// File ini jalan TERPISAH dari tab browser -- browser/OS yang menjaganya
// tetap "hidup" di background (selama izin notifikasi masih aktif), itu
// sebabnya push notification bisa muncul walau tab SIBERAD sudah ditutup.
//
// PENTING: file ini WAJIB ada persis di /sw.js (root domain), bukan di
// dalam folder /js/ -- scope service worker dibatasi ke folder tempat file
// ini berada. Kalau ditaruh di /js/sw.js, dia cuma bisa "menjaga" halaman
// di bawah /js/, bukan seluruh SIBERAD.

self.addEventListener('install', function (event) {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});

// Event ini yang dipicu browser tiap kali push service (FCM/Mozilla push
// service/dst) mengirim data dari server SIBERAD -- termasuk saat tab
// SIBERAD sedang tertutup sepenuhnya.
self.addEventListener('push', function (event) {
  var payload = { title: 'SIBERAD', body: 'Ada pembaruan baru.', url: '/dashboard' };
  if (event.data) {
    try {
      payload = Object.assign(payload, event.data.json());
    } catch (e) {
      payload.body = event.data.text() || payload.body;
    }
  }

  var options = {
    body: payload.body,
    icon: '/images/logo-pussiberad.png',
    badge: '/images/logo-pussiberad.png',
    data: { url: payload.url || '/dashboard' },
    tag: payload.notification_id || undefined,
  };

  event.waitUntil(self.registration.showNotification(payload.title, options));
});

// Diklik dari notification tray OS -> fokuskan tab SIBERAD yang sudah
// terbuka kalau ada, atau buka tab baru kalau belum ada sama sekali.
self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = (event.notification.data && event.notification.data.url) || '/dashboard';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (var i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if ('focus' in client) {
          client.navigate(url);
          return client.focus();
        }
      }
      if (self.clients.openWindow) return self.clients.openWindow(url);
    })
  );
});
