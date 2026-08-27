<script>
(function () {
  'use strict';

  var VAPID_PUBLIC_KEY = @json(config('webpush.vapid.public_key'));
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

  function setButtonState(button, granted) {
    if (!button) return;
    if (granted) {
      button.textContent = 'Notifikasi Aktif';
      button.classList.add('is-active');
      button.disabled = false;
    } else {
      button.textContent = 'Aktifkan Notifikasi';
      button.classList.remove('is-active');
      button.disabled = false;
    }
  }

  function ensureStyle() {
    if (document.getElementById('siberad-push-style')) return;
    var style = document.createElement('style');
    style.id = 'siberad-push-style';
    style.textContent =
      '.siberad-push-btn{display:inline-flex;align-items:center;gap:6px;height:36px;padding:0 14px;margin-right:8px;border-radius:9px;border:1px solid var(--border-soft);background:var(--panel-alt);color:var(--text-muted);font-family:var(--body);font-size:11.5px;font-weight:700;letter-spacing:.02em;cursor:pointer;transition:border-color .15s ease,color .15s ease,background .15s ease;white-space:nowrap;}' +
      '.siberad-push-btn:hover{border-color:var(--border-strong);color:var(--text);}' +
      '.siberad-push-btn.is-active{border-color:var(--gold,#d9920b);color:var(--gold-bright,#f2b94b);background:var(--gold-dim,rgba(217,146,11,.12));}' +
      '.siberad-push-btn[disabled]{opacity:.6;cursor:default;}' +
      '@media(max-width:640px){.siberad-push-btn{padding:0 10px;font-size:10.5px;}}' +
      '@media(max-width:480px){.siberad-push-btn{padding:0 7px;font-size:9px;margin-right:5px;height:32px;}}';
    document.head.appendChild(style);
  }

  function init() {
    if (Notification.permission === 'denied') return; // browser sendiri yang blokir prompt ulang, jangan paksa

    navigator.serviceWorker.register('/sw.js').then(function (registration) {
      var actions = document.querySelector('.topbar-actions');

      // Sudah pernah diizinkan sebelumnya (device/browser ini) -> pastikan
      // subscription-nya masih tersimpan di server, tanpa nge-prompt lagi
      // (browser tidak akan munculkan dialog izin kalau sudah granted).
      if (Notification.permission === 'granted') {
        registration.pushManager.getSubscription().then(function (existing) {
          if (existing) {
            postJson(SUBSCRIBE_URL, subscribeKeyPayload(existing)).catch(function () {});
          } else {
            doSubscribe(registration).catch(function () {});
          }
        });
        return; // sudah aktif, tidak perlu tampilkan tombol
      }

      // Belum pernah ditanya -- tampilkan tombol, JANGAN auto-prompt saat
      // halaman baru dimuat (banyak browser mem-block/dianggap spam kalau
      // izin notifikasi diminta tanpa interaksi user dulu).
      if (!actions || document.getElementById('pushEnableBtn')) return;
      ensureStyle();

      var button = document.createElement('button');
      button.type = 'button';
      button.id = 'pushEnableBtn';
      button.className = 'siberad-push-btn';
      setButtonState(button, false);

      button.addEventListener('click', function () {
        button.disabled = true;
        button.textContent = 'Meminta izin...';
        Notification.requestPermission().then(function (permission) {
          if (permission !== 'granted') {
            setButtonState(button, false);
            if (permission === 'denied') button.remove();
            return;
          }
          doSubscribe(registration).then(function () {
            setButtonState(button, true);
          }).catch(function () {
            setButtonState(button, false);
          });
        });
      });

      var notifMenu = document.getElementById('notifMenu');
      if (notifMenu) actions.insertBefore(button, notifMenu);
      else actions.insertBefore(button, actions.firstChild);
    }).catch(function () {
      // Gagal daftar service worker (mis. browser lawas) -- diam saja,
      // fitur notifikasi in-app (lonceng) tetap jalan seperti biasa.
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>
