/*
 * Menyamarkan alamat di address bar: setelah halaman dashboard termuat,
 * alamat yang terlihat hanya domain saja (tanpa /dashboard, ?query, maupun #menu).
 *
 * Murni tampilan di browser -- route, sesi, dan keamanan server TIDAK berubah.
 * Refresh pada alamat polos "/" bagi pengguna yang sudah login otomatis
 * diarahkan kembali ke dashboard (lihat route '/' di routes/web.php).
 */
(function () {
  'use strict';
  if (window.__siberadUrlMasked) return;
  if (window.location.pathname !== '/dashboard') return;
  if (!window.history || typeof window.history.replaceState !== 'function') return;

  window.__siberadUrlMasked = true;

  var MASK = '/';
  var nativeReplace = window.history.replaceState;
  var nativePush = window.history.pushState;

  function mask() {
    try {
      var loc = window.location;
      if (loc.pathname !== MASK || loc.search || loc.hash) {
        nativeReplace.call(window.history, window.history.state, '', MASK);
      }
    } catch (e) {}
  }

  // Kode lain di aplikasi (mis. filter log aktivitas yang menaruh ?log_dari=...)
  // tetap boleh memanggil replaceState/pushState; alamat tampilannya dipaksa tetap polos.
  window.history.replaceState = function (state, title) {
    return nativeReplace.call(window.history, state, title, MASK);
  };
  window.history.pushState = function (state, title) {
    return nativePush.call(window.history, state, title, MASK);
  };

  mask();
  window.addEventListener('load', mask);
  window.addEventListener('hashchange', mask);
  window.addEventListener('popstate', mask);
  window.addEventListener('pageshow', mask);
  // Klik menu/tautan "#" bisa menambah hash sesaat -- bersihkan setelah selesai diproses.
  document.addEventListener('click', function () { window.setTimeout(mask, 0); }, true);
})();
