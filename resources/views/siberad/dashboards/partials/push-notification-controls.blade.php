<script>
(function () {
  'use strict';

  var VAPID_PUBLIC_KEY = @json(config('webpush.vapid.publicKey'));
  var SUBSCRIBE_URL = @json(route('push.subscribe'));
  var UNSUBSCRIBE_URL = @json(route('push.unsubscribe'));

  // Browser lama/tidak mendukung Web Push (atau bukan konteks aman/HTTPS) --
  // diam saja, jangan ganggu tampilan sama sekali.
  if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window) || !VAPID_PUBLIC_KEY) {
    return;
  }

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var rawData = window.atob(base64);
    var outputArray = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
    return outputArray;
  }

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
  }

  function postJson(url, body) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
      body: JSON.stringify(body || {}),
    });
  }

  function subscribeKeyPayload(subscription) {
    var json = subscription.toJSON();
    return { endpoint: json.endpoint, keys: json.keys };
  }

  function doSubscribe(registration) {
    return registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
    }).then(function (subscription) {
      return postJson(SUBSCRIBE_URL, subscribeKeyPayload(subscription));
    });
  }

  // Dipanggil dari dialog konfirmasi logout (partials/global-shell-enhancements.blade.php)
  // SEBELUM form logout benar-benar submit -- supaya:
  //   1) baris push_subscriptions milik user yang lagi logout ini kehapus
  //      dari server (postJson ke UNSUBSCRIBE_URL), DAN
  //   2) subscription-nya di level BROWSER (PushManager) juga benar-benar
  //      di-unsubscribe (subscription.unsubscribe()).
  //
  // Awalnya langkah (2) sengaja DILEWATI supaya user berikutnya yang login
  // di device yang sama bisa auto ke-subscribe ulang tanpa prompt izin lagi.
  // Tapi itu artinya endpoint push di browser ini tetap "hidup" walau baris
  // DB-nya sudah terhapus -- kalau penghapusan baris DB gagal/telat karena
  // race condition, network, atau deploy yang belum sinkron, device ini
  // masih bisa kebobolan nerima notifikasi push padahal usernya sudah
  // logout. Supaya jaminannya benar-benar "tidak ada notif di luar sistem
  // kalau belum/sudah-tidak login" (bukan cuma "biasanya tidak ada"), kedua
  // langkah di atas WAJIB dua-duanya, bukan cuma DB.
  //
  // Konsekuensinya: user berikutnya yang login di device ini akan di-
  // subscribe ulang secara otomatis (tetap tanpa prompt izin baru, karena
  // izin notifikasi browser sifatnya per-origin bukan per-subscription --
  // lihat cabang 'granted' di init()), hanya perlu 1 request tambahan ke
  // SUBSCRIBE_URL yang tidak terasa oleh user.
  //
  // Promise SELALU resolve (tidak pernah reject) dan dibatasi waktu tunggu,
  // supaya proses logout tidak pernah nge-hang gara-gara network/push service
  // lambat atau error.
  window.siberadUnsubscribePush = function () {
    return new Promise(function (resolve) {
      var settled = false;
      var finish = function () {
        if (settled) return;
        settled = true;
        resolve();
      };
      setTimeout(finish, 2500); // jaring pengaman, jangan sampai logout ketahan lama

      if (!('serviceWorker' in navigator) || !('PushManager' in window)) return finish();

      navigator.serviceWorker.getRegistration().then(function (registration) {
        if (!registration) return finish();
        return registration.pushManager.getSubscription().then(function (subscription) {
          if (!subscription) return finish();

          // Hapus dulu baris DB-nya (server jadi tidak akan kirim push baru
          // ke endpoint ini), BARU cabut subscription-nya di browser. Urutan
          // ini dijaga supaya kalau salah satu gagal, sisi lain tetap sudah
          // aman: DB dihapus duluan berarti server sudah berhenti mengirim
          // meski unsubscribe browser di bawah ini gagal.
          return postJson(UNSUBSCRIBE_URL, { endpoint: subscription.endpoint })
            .catch(function () {})
            .then(function () {
              return subscription.unsubscribe().catch(function () {});
            })
            .then(finish);
        });
      }).catch(finish);
    });
  };

  // Key localStorage untuk mencatat kapan terakhir subscription dikonfirmasi
  // ke server. Dipakai supaya tiap page load tidak langsung kirim POST ke
  // SUBSCRIBE_URL (boros request), tapi tetap periodik refresh supaya baris
  // push_subscriptions di server tidak stale/hilang karena push service
  // (FCM/Mozilla) mengganti endpoint setelah browser update atau device restart.
  var CONFIRM_INTERVAL_MS = 6 * 60 * 60 * 1000; // 6 jam
  var LAST_CONFIRM_KEY = 'siberad_push_confirmed_at';

  function shouldConfirmToServer() {
    try {
      var last = parseInt(localStorage.getItem(LAST_CONFIRM_KEY) || '0', 10);
      return (Date.now() - last) > CONFIRM_INTERVAL_MS;
    } catch (e) { return true; }
  }

  function markConfirmed() {
    try { localStorage.setItem(LAST_CONFIRM_KEY, String(Date.now())); } catch (e) {}
  }

  function doSubscribeAndConfirm(registration) {
    return doSubscribe(registration).then(function () { markConfirmed(); }).catch(function () {});
  }

  function confirmExistingToServer(existing) {
    return postJson(SUBSCRIBE_URL, subscribeKeyPayload(existing))
      .then(function () { markConfirmed(); })
      .catch(function () {});
  }

  // ------------------------------------------------------------------
  // API manual untuk tombol "Aktifkan Notifikasi" di menu notifikasi
  // (lonceng) -- lihat partials/push-permission-menu.blade.php.
  // ------------------------------------------------------------------
  // init() di bawah SUDAH otomatis manggil Notification.requestPermission()
  // begitu halaman dimuat kalau izinnya masih 'default'. Tapi banyak browser
  // (terutama Chrome versi baru) diam-diam MEMBLOKIR/menahan prompt izin
  // notifikasi yang tidak dipicu langsung oleh interaksi user (klik/tap) --
  // alih-alih prompt native muncul, browser cuma membiarkan izinnya tetap
  // 'default' tanpa menunjukkan apa pun ke user. Akibatnya, buat sebagian
  // pengguna permintaan otomatis itu efektifnya tidak pernah kelihatan.
  // Dua fungsi berikut diexpose ke window supaya tombol manual di menu
  // notifikasi punya cara yang pasti (dipicu klik user) untuk memicu ulang
  // prompt izin tsb, dan supaya UI tombol itu tahu status izin saat ini.
  window.siberadPushPermissionState = function () {
    return ('Notification' in window) ? Notification.permission : 'unsupported';
  };

  window.siberadRequestPushPermission = function () {
    return Notification.requestPermission().then(function (permission) {
      if (permission !== 'granted') return permission;
      return navigator.serviceWorker.register('/sw.js').then(function (registration) {
        return doSubscribeAndConfirm(registration).then(function () { return 'granted'; });
      }).catch(function () { return 'granted'; });
    });
  };

  function init() {
    if (Notification.permission === 'denied') return; // browser sendiri yang blokir prompt ulang, jangan paksa

    navigator.serviceWorker.register('/sw.js').then(function (registration) {
      // Sudah pernah diizinkan sebelumnya (device/browser ini).
      if (Notification.permission === 'granted') {
        registration.pushManager.getSubscription().then(function (existing) {
          if (existing) {
            // Subscription masih ada di browser. Konfirmasi ke server hanya
            // kalau sudah lebih dari CONFIRM_INTERVAL_MS sejak terakhir
            // dikonfirmasi -- supaya baris push_subscriptions di server tidak
            // hilang/kadaluarsa tanpa user harus reload manual, tapi juga
            // tidak membanjiri server dengan POST tiap page load.
            //
            // Catatan: ini WAJIB juga dijalankan pada page load pertama
            // (shouldConfirmToServer() = true karena localStorage kosong)
            // supaya subscription lama yang mungkin sudah ada di browser
            // tapi belum/sudah tidak tersimpan di server (mis. setelah server
            // deploy ulang, atau baris DB terhapus karena 404/410 dari push
            // service) langsung terdaftar ulang tanpa perlu user klik apapun.
            if (shouldConfirmToServer()) {
              confirmExistingToServer(existing);
            }
          } else {
            // Subscription hilang dari browser (mis. browser update, clear
            // site data, atau push service reset endpoint) -- subscribe ulang
            // tanpa perlu minta izin lagi (izin sudah 'granted').
            doSubscribeAndConfirm(registration);
          }
        });
      }

      // Belum pernah ditanya (Notification.permission === 'default') --
      // tetap dicoba minta izin otomatis begitu halaman dimuat (untuk
      // browser yang masih mengizinkan prompt tanpa interaksi user).
      // Untuk browser yang menahan/blokir prompt otomatis ini (lihat
      // catatan di window.siberadRequestPushPermission di atas), user
      // tetap punya jalan pasti lewat tombol "Aktifkan Notifikasi" di
      // menu notifikasi (partials/push-permission-menu.blade.php).
      else if (Notification.permission === 'default') {
        Notification.requestPermission().then(function (permission) {
          if (permission === 'granted') doSubscribeAndConfirm(registration);
        }).catch(function () {});
      }
    }).catch(function () {
      // Gagal daftar service worker (mis. browser lawas) -- diam saja,
      // fitur notifikasi in-app (lonceng) tetap jalan seperti biasa.
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>
