{{--
  Baris "Aktifkan Notifikasi" di dalam menu notifikasi (lonceng), berupa
  ajakan singkat untuk mengizinkan notifikasi browser (Web Push) supaya
  bisa muncul walaupun tab/aplikasi Cyclone sedang tidak dibuka.

  Kenapa perlu tombol manual padahal sudah ada auto-request di
  push-notification-controls.blade.php? Karena banyak browser modern diam-
  diam menahan/blokir Notification.requestPermission() yang dipicu otomatis
  saat halaman dimuat (bukan dari klik user) -- lihat catatan di
  window.cycloneRequestPushPermission (push-notification-controls.blade.php).
  Tombol ini memberi jalan pasti berbasis klik user.

  Ditaruh SEBAGAI BARIS TERPISAH di atas daftar notifikasi (bukan ikut
  campur ke header yang sudah dipakai bareng oleh admin-ui-consistency.
  blade.php / satlak-notification-close*.blade.php untuk tombol "Hapus
  Semua"/"Tutup") supaya tidak bentrok dengan variasi markup notif-head
  per role yang sudah ada, dan otomatis konsisten tampil di semua role
  (Admin, Danpus, Pimpinan, Satuan) karena partial ini di-include sekali
  di pengumuman-banner.blade.php, tempat notification-controls.blade.php
  (pemilik #notifDropdown) juga di-include.

  Tidak pernah tampil sama sekali kalau: browser tidak mendukung Web Push,
  fitur push dimatikan Admin lewat Pengaturan -> Notifikasi (VAPID key
  kosong -- lihat push-notification-controls.blade.php), izin browser
  sudah 'granted' (tidak ada lagi yang perlu diaktifkan), atau modul
  notifikasi dimatikan untuk satuan ybs (maka #notifDropdown sendiri
  tidak pernah dibuat).
--}}
<style>
  #notifDropdown .cyclone-push-permission {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--border-soft);
    background: var(--gold-dim, rgba(255,152,0,.08));
  }
  #notifDropdown .cyclone-push-permission-icon {
    flex: 0 0 22px;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gold-bright, #ff9800);
  }
  #notifDropdown .cyclone-push-permission.is-blocked .cyclone-push-permission-icon {
    color: var(--text-dim);
  }
  #notifDropdown .cyclone-push-permission-icon svg { width: 16px; height: 16px; stroke: currentColor; fill: none; }
  #notifDropdown .cyclone-push-permission-body { min-width: 0; flex: 1 1 auto; }
  #notifDropdown .cyclone-push-permission-body p { margin: 0; font-size: 12px; font-weight: 600; color: var(--text); line-height: 1.35; }
  /* Deskripsi panjang disembunyikan di tampilan ringkas -- teks pada judul
     (p) sudah cukup jelas sebagai satu baris. Tetap ada di DOM (bukan
     dihapus) supaya kompatibel dengan script yang sudah mengisi elemen ini. */
  #notifDropdown .cyclone-push-permission-body small { display: none; }
  #notifDropdown .cyclone-push-permission-btn {
    flex: 0 0 auto;
    border: none;
    border-radius: 7px;
    background: var(--gold-bright, #ff9800);
    color: #1a1206;
    font-size: 11px;
    font-weight: 700;
    padding: 6px 12px;
    cursor: pointer;
    white-space: nowrap;
    transition: filter .15s ease;
  }
  #notifDropdown .cyclone-push-permission-btn:hover { filter: brightness(1.08); }
  #notifDropdown .cyclone-push-permission-btn:disabled { opacity: .6; cursor: default; filter: none; }
</style>
<script>
(function () {
  'use strict';

  function buatBaris() {
    var el = document.createElement('div');
    el.className = 'cyclone-push-permission';
    el.id = 'cyclonePushPermission';
    el.innerHTML =
      '<span class="cyclone-push-permission-icon">' +
        '<svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>' +
      '</span>' +
      '<span class="cyclone-push-permission-body">' +
        '<p id="cyclonePushPermissionTitle"></p>' +
        '<small id="cyclonePushPermissionDesc"></small>' +
      '</span>' +
      '<button type="button" class="cyclone-push-permission-btn" id="cyclonePushPermissionBtn">Aktifkan</button>';
    return el;
  }

  // Perbarui isi baris sesuai status izin browser SAAT INI. Dipanggil tiap
  // kali menu notifikasi dibuka (bukan cuma sekali saat halaman dimuat),
  // supaya kalau user mengubah izin lewat pengaturan browser di tab lain
  // lalu kembali & buka lonceng lagi, tampilannya tetap sinkron.
  function sinkronkanTampilan(el) {
    var state = (typeof window.cyclonePushPermissionState === 'function')
      ? window.cyclonePushPermissionState()
      : 'unsupported';

    if (state === 'granted' || state === 'unsupported') {
      el.style.display = 'none';
      return;
    }

    el.style.display = 'flex';
    var judul = el.querySelector('#cyclonePushPermissionTitle');
    var desk = el.querySelector('#cyclonePushPermissionDesc');
    var tombol = el.querySelector('#cyclonePushPermissionBtn');

    if (state === 'denied') {
      el.classList.add('is-blocked');
      judul.textContent = 'Notifikasi diblokir -- aktifkan lewat ikon gembok di address bar';
      desk.textContent = 'Aktifkan lewat ikon gembok/info di address bar browser Anda untuk menerima notifikasi di luar sistem.';
      tombol.style.display = 'none';
      return;
    }

    // state === 'default'
    el.classList.remove('is-blocked');
    judul.textContent = 'Izinkan notifikasi muncul di luar sistem';
    desk.textContent = 'Izinkan notifikasi supaya tetap muncul walau Cyclone sedang tidak dibuka.';
    tombol.style.display = '';
    tombol.disabled = false;
    tombol.textContent = 'Aktifkan';
  }

  function pasangBaris() {
    var dropdown = document.getElementById('notifDropdown');
    if (!dropdown) return;

    var el = document.getElementById('cyclonePushPermission');
    if (!el) {
      el = buatBaris();
      var list = dropdown.querySelector('.cyclone-notif-list');
      if (list && list.parentNode === dropdown) {
        dropdown.insertBefore(el, list);
      } else {
        dropdown.appendChild(el);
      }

      el.querySelector('#cyclonePushPermissionBtn').addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        if (typeof window.cycloneRequestPushPermission !== 'function') return;
        var tombol = event.currentTarget;
        tombol.disabled = true;
        tombol.textContent = 'Memproses...';
        window.cycloneRequestPushPermission().then(function (hasil) {
          if (hasil === 'granted') {
            window.cycloneShowToast && window.cycloneShowToast('success', 'Notifikasi berhasil diaktifkan.');
          } else if (hasil === 'denied') {
            window.cycloneShowToast && window.cycloneShowToast('info', 'Izin notifikasi ditolak.');
          }
          sinkronkanTampilan(el);
        }).catch(function () {
          tombol.disabled = false;
          tombol.textContent = 'Aktifkan';
        });
      });
    }

    sinkronkanTampilan(el);
  }

  // Menu notifikasi (#notifDropdown) dibuat oleh partial lain
  // (notification-controls.blade.php) yang urutan/waktu render-nya tidak
  // selalu pasti relatif terhadap partial ini -- ikuti pola retry yang
  // sudah dipakai partial notifikasi lain di codebase ini (mis.
  // satlak-notification-close-text.blade.php) supaya baris ini tetap
  // terpasang walau #notifDropdown baru muncul belakangan.
  function init() {
    pasangBaris();
    setTimeout(pasangBaris, 100);
    setTimeout(pasangBaris, 400);
    setTimeout(pasangBaris, 1000);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

  // Sinkronkan ulang status setiap kali tombol lonceng diklik/menu dibuka --
  // menutupi kasus user mengizinkan/memblokir notifikasi lewat pengaturan
  // browser di tab lain sementara menu ini sudah pernah dibuka sebelumnya.
  document.addEventListener('click', function (event) {
    var tombolLonceng = event.target && event.target.closest ? event.target.closest('#notifBtn') : null;
    if (!tombolLonceng) return;
    setTimeout(function () {
      var el = document.getElementById('cyclonePushPermission');
      if (el) sinkronkanTampilan(el);
    }, 0);
  }, true);
})();
</script>
