<script>
(function () {
  'use strict';

  var POLL_INTERVAL_MS = 3000;

  // Modul 'notifikasi' bisa dimatikan Admin per satuan lewat Manajemen Role
  // & Hak Akses -- kalau nonaktif, lonceng notifikasi tidak dibuat sama
  // sekali (dan polling tidak jalan) di navbar user tersebut. Endpoint
  // /notifikasi/* sendiri sudah diblokir 403 di sisi server (lihat
  // EnsureModulAktif), ini cuma supaya UI-nya tidak nyoba minta hal yang
  // memang tidak diizinkan.
  var CYCLONE_NOTIFIKASI_AKTIF = @json($modulAktif['notifikasi'] ?? true);

  function initNotificationControls() {
    if (!CYCLONE_NOTIFIKASI_AKTIF) return;
    var actions = document.querySelector('.topbar-actions');
    if (!actions) return;

    var menu = document.getElementById('notifMenu');
    var button = document.getElementById('notifBtn');
    var dropdown = document.getElementById('notifDropdown');

    if (!menu) {
      menu = document.createElement('div');
      menu.className = 'profile-menu notif-menu';
      menu.id = 'notifMenu';
      button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn-icon-toggle';
      button.id = 'notifBtn';
      button.setAttribute('aria-label', 'Notifikasi');
      button.setAttribute('aria-haspopup', 'menu');
      button.setAttribute('aria-expanded', 'false');
      button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg><span class="cyclone-notif-badge" style="display:none;"></span>';
      dropdown = document.createElement('div');
      dropdown.className = 'profile-dropdown';
      dropdown.id = 'notifDropdown';
      dropdown.innerHTML = '<div class="profile-dropdown-head notif-head"><div class="profile-dropdown-name">Notifikasi</div></div><div class="cyclone-notif-list"></div>';
      menu.appendChild(button);
      menu.appendChild(dropdown);
      var profileMenu = document.getElementById('profileMenu');
      if (profileMenu && profileMenu.parentNode === actions) actions.insertBefore(menu, profileMenu);
      else actions.appendChild(menu);
    }

    if (!button) button = menu.querySelector('#notifBtn');
    if (!dropdown) dropdown = menu.querySelector('#notifDropdown');
    if (!button || !dropdown) return;

    menu.classList.add('notif-menu');

    // Keep the entire topbar above dashboard/content layers. The previous
    // implementation only raised the notification element itself, which is
    // ineffective when its parent stacking context is underneath the content.
    var style = document.getElementById('cyclone-notification-style');
    if (!style) {
      style = document.createElement('style');
      style.id = 'cyclone-notification-style';
      style.textContent = `
        .topbar{position:relative!important;z-index:100000!important;}
        .topbar-actions{position:relative!important;z-index:100001!important;pointer-events:auto!important;}
        .topbar-actions>*{pointer-events:auto!important;}
        .notif-menu{position:relative!important;z-index:100002!important;pointer-events:auto!important;}
        .notif-menu>#notifBtn{position:relative!important;z-index:100003!important;display:flex!important;align-items:center!important;justify-content:center!important;cursor:pointer!important;pointer-events:auto!important;}
        .notif-menu>#notifBtn svg{width:19px;height:19px;display:block;pointer-events:none;}
        .cyclone-notif-badge{position:absolute;top:2px;right:2px;min-width:15px;height:15px;padding:0 3px;box-sizing:border-box;border-radius:999px;background:var(--red);color:#fff;font-size:9px;font-weight:800;line-height:15px;text-align:center;box-shadow:0 0 0 2px var(--panel,#0c2417);pointer-events:none;}
        #notifDropdown{position:absolute!important;z-index:100004!important;min-width:300px;max-width:340px;right:0;top:calc(100% + 8px);pointer-events:auto!important;padding:0!important;overflow:hidden;}
        @media(max-width:420px){#notifDropdown{position:fixed!important;left:12px!important;right:12px!important;top:90px!important;min-width:0;max-width:none;width:auto;}}
        #notifDropdown .notif-head{display:flex;align-items:center;min-height:22px;padding:14px 16px 12px!important;margin-bottom:0!important;}
        /* ~4 item kelihatan sekaligus (tiap item kira-kira 72px), sisanya discroll. */
        #notifDropdown .cyclone-notif-list{display:flex;flex-direction:column;max-height:288px;overflow-y:auto;}
        #notifDropdown .cyclone-notif-item{position:relative;padding:12px 40px 12px 14px;box-sizing:border-box;transition:background .15s ease;}
        #notifDropdown .cyclone-notif-item:not(:last-child){border-bottom:1px solid var(--border-soft);}
        #notifDropdown .cyclone-notif-item:hover{background:var(--hover-tint);}
        /* max-height di-set INLINE (bukan di sini) pas mulai animasi hapus,
           persis sesuai tinggi asli item saat itu -- biar transisinya mulus
           dari ukuran sebenarnya ke 0, bukan dari angka tebakan statis yang
           bisa beda dikit dari tinggi asli & ganggu tampilan normal. */
        #notifDropdown .cyclone-notif-item.is-removing{overflow:hidden;transition:background .15s ease,max-height .22s ease,padding .22s ease,opacity .18s ease,border-color .22s ease;max-height:0!important;padding-top:0;padding-bottom:0;opacity:0;border-color:transparent;pointer-events:none;}
        #notifDropdown .cyclone-notif-body{min-width:0;}
        #notifDropdown .cyclone-notif-item.is-clickable{cursor:pointer;}
        /* Titik penanda kecil di kiri untuk notif yang BELUM dibaca. Yang
           sudah dibaca (is-read) diredupin dikit supaya beda dari yang
           belum, tapi isinya tetap utuh & tetap kelihatan di daftar. */
        #notifDropdown .cyclone-notif-item.is-unread{padding-left:26px;}
        #notifDropdown .cyclone-notif-item.is-unread::before{content:'';position:absolute;left:12px;top:18px;width:7px;height:7px;border-radius:50%;background:var(--gold-bright,#d4af37);}
        #notifDropdown .cyclone-notif-item.is-read p{font-weight:500;color:var(--text-dim);}
        #notifDropdown .cyclone-notif-item p{margin:0;font-size:12.5px;font-weight:600;line-height:1.45;color:var(--text);word-break:break-word;}
        #notifDropdown .cyclone-notif-item small{display:block;margin-top:4px;font-size:10.5px;font-weight:500;color:var(--text-dim);}
        #notifDropdown .cyclone-notif-remove{position:absolute;right:9px;top:50%;transform:translateY(-50%);width:24px;height:24px;border:0;border-radius:7px;background:transparent;color:var(--text-dim);cursor:pointer;padding:4px;display:flex;align-items:center;justify-content:center;box-sizing:border-box;transition:background .15s ease,color .15s ease;}
        #notifDropdown .cyclone-notif-remove svg{width:100%;height:100%;display:block;}
        #notifDropdown .cyclone-notif-remove:hover{background:rgba(198,40,40,.14);color:var(--red,#c83b3b);}
        #notifDropdown .cyclone-notif-empty-runtime{padding:30px 18px 26px;text-align:center;color:var(--text-muted);}
        #notifDropdown .cyclone-notif-empty-runtime svg{width:32px;height:32px;stroke:var(--text-dim);margin:0 auto 12px;display:block;}
        #notifDropdown .cyclone-notif-empty-runtime p{margin:0;font-size:12px;line-height:1.55;}
        /* Modal "Pengumuman" -- dibuka saat notifikasi tipe pengumuman_admin
           (lihat App\Notifications\PengumumanBroadcastAdmin) diklik. Dibuat
           self-contained di sini (bukan pakai .report-modal punya
           laporan-role/laporan-pimpinan) karena partial ini juga dipasang di
           dashboard Admin, yang tidak punya CSS itu.
           Kategori 'maintenance' dapat treatment khusus (banner blueprint-grid
           statis + ikon kunci pas + daftar detail) karena satu-satunya
           kategori yang memang bisa diklik/dibuka modalnya (lihat render(),
           'keterangan' tidak pernah clickable) -- jadi modal ini de facto
           SELALU tentang pemeliharaan sistem, dan pantas didesain sesuai itu,
           bukan generik. Sengaja TANPA animasi apa pun (banner, ring, badge)
           -- cukup grafis statis, konsisten dengan gaya HUD dashboard yang
           sudah ada (gelap, aksen amber, label mono uppercase). */
        .cyclone-pengumuman-overlay{position:fixed;inset:0;z-index:100210;background:rgba(2,4,6,.68);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease;box-sizing:border-box;}
        :root[data-theme="light"] .cyclone-pengumuman-overlay{background:rgba(60,50,20,.4);}
        .cyclone-pengumuman-overlay.open{opacity:1;visibility:visible;pointer-events:auto;}
        .cyclone-pengumuman-card{width:min(440px,100%);background:var(--panel,var(--p-surface,#fff));border:1px solid var(--border-soft,var(--p-border,#e2e8f0));border-radius:18px;box-shadow:0 25px 70px rgba(0,0,0,.4);box-sizing:border-box;overflow:hidden;transform:translateY(14px) scale(.97);transition:transform .2s ease;}
        .cyclone-pengumuman-overlay.open .cyclone-pengumuman-card{transform:translateY(0) scale(1);}
        .cyclone-pengumuman-close{position:absolute;top:14px;right:14px;width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.35);background:rgba(6,9,12,.25);backdrop-filter:blur(2px);color:#fff;cursor:pointer;transition:border-color .2s ease,color .2s ease,transform .2s ease,background .2s ease;z-index:3;}
        :root[data-theme="light"] .cyclone-pengumuman-close{border-color:rgba(90,70,10,.25);background:rgba(255,255,255,.55);color:var(--text,#22281f);}
        .cyclone-pengumuman-close:hover{border-color:var(--red,#c83b3b);color:var(--red,#c83b3b);background:rgba(6,9,12,.5);transform:rotate(90deg);}
        :root[data-theme="light"] .cyclone-pengumuman-close:hover{background:rgba(255,255,255,.75);}
        .cyclone-pengumuman-close svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;display:block;}
        /* Banner statis -- gradasi gelap + grid tipis ala blueprint teknik
           (bukan garis diagonal hazard, tidak bergerak) + siku HUD di dua
           sudut + ikon kunci pas raksasa transparan sebagai watermark. */
        .cyclone-pengumuman-banner{position:relative;height:104px;background:linear-gradient(150deg,#0c1116 0%,#171016 60%,#1c1108 100%);overflow:hidden;flex-shrink:0;}
        :root[data-theme="light"] .cyclone-pengumuman-banner{background:linear-gradient(150deg,var(--panel-2,#faf8ef) 0%,var(--gold-dim,rgba(255,152,0,.16)) 100%);}
        .cyclone-pengumuman-banner::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,152,0,.14) 1px,transparent 1px),linear-gradient(90deg,rgba(255,152,0,.14) 1px,transparent 1px);background-size:16px 16px;-webkit-mask-image:linear-gradient(180deg,rgba(0,0,0,.9),transparent 92%);mask-image:linear-gradient(180deg,rgba(0,0,0,.9),transparent 92%);}
        :root[data-theme="light"] .cyclone-pengumuman-banner::before{background-image:linear-gradient(rgba(150,110,10,.16) 1px,transparent 1px),linear-gradient(90deg,rgba(150,110,10,.16) 1px,transparent 1px);}
        .cyclone-pengumuman-banner-icon-bg{position:absolute;right:-14px;top:50%;transform:translateY(-52%) rotate(12deg);width:96px;height:96px;color:rgba(255,152,0,.14);}
        .cyclone-pengumuman-banner-icon-bg svg{width:100%;height:100%;stroke:currentColor;fill:none;stroke-width:1.2;}
        .cyclone-pengumuman-corner{position:absolute;width:16px;height:16px;border-color:rgba(255,152,0,.55);}
        .cyclone-pengumuman-corner.tl{top:10px;left:10px;border-top:1.5px solid;border-left:1.5px solid;}
        .cyclone-pengumuman-corner.br{bottom:10px;right:10px;border-bottom:1.5px solid;border-right:1.5px solid;}
        .cyclone-pengumuman-icon-wrap{position:absolute;left:50%;bottom:-28px;width:60px;height:60px;z-index:2;transform:translateX(-50%);}
        .cyclone-pengumuman-icon-ring{position:absolute;inset:-5px;border-radius:50%;border:1px solid var(--border-soft,rgba(217,146,11,.3));}
        .cyclone-pengumuman-icon{position:relative;width:100%;height:100%;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--panel,#11181f);border:1px solid var(--border,rgba(217,146,11,.3));color:var(--gold-bright,#ff9800);box-shadow:0 6px 18px rgba(0,0,0,.35);}
        .cyclone-pengumuman-icon svg{width:26px;height:26px;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;}
        .cyclone-pengumuman-content{padding:38px 24px 22px;text-align:center;}
        .cyclone-pengumuman-badge{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono,monospace);font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;background:var(--gold-dim,rgba(255,152,0,.14));color:var(--gold-bright,#ff9800);border:1px solid var(--border,rgba(217,146,11,.3));border-radius:999px;padding:5px 12px;}
        .cyclone-pengumuman-badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;}
        .cyclone-pengumuman-title{margin:12px 0 0;font-family:var(--display,inherit);font-size:19px;font-weight:700;color:var(--text,var(--p-text,#17212b));line-height:1.35;}
        .cyclone-pengumuman-details{list-style:none;margin:16px 0 0;padding:0;border-top:1px solid var(--border-soft,rgba(217,146,11,.16));}
        .cyclone-pengumuman-details li{display:flex;align-items:center;justify-content:center;gap:8px;padding:9px 0;border-bottom:1px solid var(--border-soft,rgba(217,146,11,.16));font-size:12.5px;}
        .cyclone-pengumuman-details li svg{width:14px;height:14px;stroke:var(--gold-bright,#ff9800);fill:none;stroke-width:1.8;flex-shrink:0;}
        .cyclone-pengumuman-details .label{color:var(--text-dim,var(--p-muted,#77736c));font-family:var(--mono,monospace);font-size:10.5px;letter-spacing:.06em;text-transform:uppercase;}
        .cyclone-pengumuman-details .value{color:var(--text,var(--p-text,#17212b));font-weight:600;}
        .cyclone-pengumuman-eyebrow{margin:20px 0 8px;font-family:var(--mono,monospace);font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--text-dim,var(--p-muted,#77736c));}
        .cyclone-pengumuman-body-wrap{background:var(--gold-dim,rgba(255,152,0,.08));border:1px solid var(--border-soft,rgba(217,146,11,.16));border-radius:10px;padding:14px 16px;}
        .cyclone-pengumuman-body{margin:0;font-size:13px;line-height:1.7;color:var(--text-muted,var(--p-muted,#64748b));white-space:pre-wrap;text-align:center;}
        .cyclone-pengumuman-actions{padding:20px 24px 24px;}
        .cyclone-pengumuman-actions button{width:100%;display:flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:10px;padding:12px;font-size:13px;font-weight:700;color:#0c1116;background:var(--gold-bright,var(--p-accent,#c97a00));cursor:pointer;transition:filter .15s ease,transform .15s ease;}
        .cyclone-pengumuman-actions button svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round;}
        .cyclone-pengumuman-actions button:hover{filter:brightness(1.08);transform:translateY(-1px);}
      `;
      document.head.appendChild(style);
    }

    var deleteUrlBase = '{{ url('/notifikasi') }}/';
    var readUrlBaseFn = function (id) { return '{{ url('/notifikasi') }}/' + encodeURIComponent(id) + '/baca'; };
    var hapusSemuaUrl = '{{ route('notifikasi.hapus-semua') }}';
    var pollUrl = '{{ route('notifikasi.realtime') }}';
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.content : '{{ csrf_token() }}';
    var pollTimer = null;

    // Sesi habis (baik dipaksa logout oleh Admin, atau expired alami --
    // dua-duanya kelihatan sama persis di sisi klien, 401 Unauthorized, jadi
    // teksnya sengaja netral): begitu ke-detect lewat poll notifikasi (tiap
    // 3 detik), langsung munculin modal blocking, TANPA bisa ditutup lewat
    // klik backdrop atau ngapa-ngapain lagi -- cuma tombol OK / Escape yang
    // sama-sama langsung redirect ke landing page.
    function ensureSesiBerakhirOverlay() {
      var overlay = document.getElementById('sesiBerakhirOverlay');
      if (overlay) return overlay;
      overlay = document.createElement('div');
      overlay.className = 'confirm-overlay';
      overlay.id = 'sesiBerakhirOverlay';
      overlay.innerHTML = '<div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="sesiBerakhirTitle">' +
        '<div class="confirm-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg></div>' +
        '<h3 id="sesiBerakhirTitle">Sesi Anda Telah Berakhir</h3>' +
        '<p>Sesi Anda telah berakhir. Silakan login kembali.</p>' +
        '<div class="confirm-actions"><button type="button" class="btn btn-primary" id="sesiBerakhirOk">OK</button></div>' +
        '</div>';
      document.body.appendChild(overlay);
      function keLanding() { window.location.href = '{{ url('/') }}'; }
      overlay.querySelector('#sesiBerakhirOk').addEventListener('click', keLanding);
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('open')) keLanding();
      });
      return overlay;
    }
    function tampilkanSesiBerakhir() {
      // User memang sengaja logout (lihat global-shell-enhancements.blade.php):
      // 401 dari poller realtime yang masih sempat jalan selama transisi logout
      // itu WAJAR, bukan sesi kadaluarsa -- jangan munculin modal yang bikin
      // kaget sekejap sebelum halaman landing kebuka.
      if (window.__cycloneLoggingOut) return;
      if (window.__cycloneSesiBerakhirShown) return;
      window.__cycloneSesiBerakhirShown = true;
      if (pollTimer) window.clearInterval(pollTimer);
      var overlay = ensureSesiBerakhirOverlay();
      // Overlay ini baru dibuat lewat document.createElement() detik itu
      // juga (bukan markup statis yang sudah ke-render dari awal load
      // halaman kayak modal lain) -- kalau class "open" langsung ditambah
      // di baris yang sama, browser belum sempat "commit" kondisi awalnya
      // (opacity:0) sebelum transisinya jalan, jadi animasinya kepotong
      // dan kelihatan kaku/langsung muncul. Baca offsetHeight dulu buat
      // paksa reflow, biar transisinya beneran ke-animate.
      overlay.offsetHeight;
      overlay.classList.add('open');
    }
    window.cycloneTampilkanSesiBerakhir = tampilkanSesiBerakhir;

    @php
      // PENTING: jangan taruh array literal (yang punya koma) langsung di
      // dalam @json(...) -- directive @json Blade motong argumennya pakai
      // explode(',') mentah (buat pisahin opsi encoding/depth opsional),
      // jadi koma DI DALAM array/closure ikut kepotong dan hasil PHP-nya
      // jadi rusak (ParseError "Unclosed '[' does not match ')'"). Makanya
      // array-nya dirakit dulu di variabel biasa di sini, baru @json()
      // dipanggil dengan satu variabel tunggal (tanpa koma di levelnya).
      $__cycloneNotifications = auth()->user()?->notifications?->take(20)?->map(function ($n) {
        return ['id' => $n->id, 'message' => $n->data['pesan'] ?? 'Status laporan diperbarui.', 'time' => optional($n->created_at)->diffForHumans(), 'url' => $n->data['url'] ?? null, 'title' => $n->data['judul'] ?? null, 'tipe' => $n->data['tipe'] ?? null, 'kategori' => $n->data['kategori'] ?? null, 'read' => ! is_null($n->read_at)];
      })->values() ?? [];

      // Suara notifikasi (diatur Admin lewat menu Lainnya -> Notifikasi,
      // lihat NotifikasiSettingController::updateSuara) -- verifikasi file
      // benar-benar ada di disk dulu, sama seperti pola $notifSoundExists
      // di admin.blade.php, supaya path "dangling" (file terhapus manual
      // di server) tidak bikin browser nyoba fetch audio yang 404.
      $__cyclonePengaturanNotifSound = $pengaturan ?? \App\Models\Pengaturan::current();
      $__cycloneNotifSoundExists = ($__cyclonePengaturanNotifSound->notifikasi_sound_path ?? null)
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($__cyclonePengaturanNotifSound->notifikasi_sound_path);
      $__cycloneNotifSoundUrl = $__cycloneNotifSoundExists
        ? asset('storage/'.$__cyclonePengaturanNotifSound->notifikasi_sound_path)
        : null;
    @endphp
    var notifications = @json($__cycloneNotifications);
    var NOTIF_SOUND_URL = @json($__cycloneNotifSoundUrl);

    // Objek Audio dibuat SEKALI & dipakai ulang tiap ada notifikasi baru
    // (bukan `new Audio()` tiap kali) supaya file-nya sudah ter-preload di
    // browser dan tidak ada jeda saat diputar. Kalau Admin belum
    // mengunggah suara apapun, NOTIF_SOUND_URL null -> variabel ini tetap
    // null & pemutaran di bawah otomatis dilewati (silent, tidak error).
    var notifSoundAudio = NOTIF_SOUND_URL ? new Audio(NOTIF_SOUND_URL) : null;
    if (notifSoundAudio) notifSoundAudio.preload = 'auto';

    // Kumpulan id notifikasi yang SUDAH pernah dilihat klien ini -- dipakai
    // buat bedain notifikasi yang BENAR-BENAR baru (dari poll berikutnya)
    // vs notifikasi lama yang sudah ada sejak halaman pertama dimuat.
    // Baseline diisi dari data awal supaya sound TIDAK bunyi begitu saja
    // pas halaman baru dibuka (yang wajar cuma bunyi utk notif yang
    // datang SETELAH pengguna sedang membuka dashboard).
    var notifKnownIds = {};
    notifications.forEach(function (n) { notifKnownIds[String(n.id)] = true; });

    function mainkanSuaraNotifikasi() {
      if (!notifSoundAudio) return;
      try {
        notifSoundAudio.currentTime = 0;
        var p = notifSoundAudio.play();
        // Browser modern bisa menolak autoplay kalau belum pernah ada
        // interaksi pengguna sama sekali di tab ini -- ditangkap diam-diam
        // (bukan error ke user) karena ini cuma enhancement, bukan fitur
        // krusial yang boleh mengganggu alur lain kalau gagal.
        if (p && typeof p.catch === 'function') p.catch(function () {});
      } catch (e) {}
    }

    var list = dropdown.querySelector('.cyclone-notif-list');
    if (!list) {
      list = document.createElement('div');
      list.className = 'cyclone-notif-list';
      dropdown.appendChild(list);
    }

    var header = dropdown.querySelector('.notif-head') || dropdown.querySelector('.profile-dropdown-head');
    if (header) header.classList.add('notif-head');
    Array.prototype.slice.call(dropdown.children).forEach(function (child) {
      if (child !== header && child !== list) child.remove();
    });

    function escapeHtml(value) {
      var div = document.createElement('div');
      div.textContent = value == null ? '' : String(value);
      return div.innerHTML;
    }

    // Notif pengumuman kategori 'keterangan' TETAP ikut dihitung di angka
    // badge lonceng (jadi bertambah tiap ada notif Keterangan baru) --
    // walaupun secara visual tetap tidak pernah ditandai "belum dibaca"
    // (tidak ada titik oranye) & tetap TIDAK bisa diklik sama sekali
    // (lihat render()). Karena kategori ini memang tidak pernah bisa
    // ditandai dibaca (tidak ada aksi klik yang trigger markAsRead), flag
    // `read`-nya di server tetap false selamanya -- konsekuensinya, angka
    // badge yang berasal dari notif Keterangan tidak akan pernah berkurang
    // sendiri (beda dgn notif biasa yang berkurang saat diklik/dibaca).
    function hitungSebagaiUnread(n) {
      return !n.read;
    }

    function unreadCount() {
      var count = 0;
      for (var i = 0; i < notifications.length; i++) if (hitungSebagaiUnread(notifications[i])) count++;
      return count;
    }

    function updateBadge() {
      var badge = button.querySelector('.cyclone-notif-badge');
      if (!badge) return;
      var count = unreadCount();
      if (count > 0) {
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.style.display = 'block';
      } else {
        badge.style.display = 'none';
      }
    }

    // Optimistic sama seperti removeNotification: begitu notifikasi (yang
    // punya tujuan/url) diklik, badge langsung berkurang di klien & request
    // tandai-dibaca jalan di background -- tapi item-nya SENGAJA tidak
    // dihapus dari daftar, cuma diredupin (class .is-read), biar isinya
    // tetap kelihatan. Kalau request gagal, poll berikutnya (tiap 3 detik)
    // otomatis munculin lagi statusnya sebagai belum dibaca.
    function markNotificationRead(id, itemEl) {
      var target = null;
      for (var i = 0; i < notifications.length; i++) {
        if (String(notifications[i].id) === String(id)) { target = notifications[i]; break; }
      }
      if (!target || target.read) return;
      target.read = true;
      updateBadge();
      if (itemEl) { itemEl.classList.remove('is-unread'); itemEl.classList.add('is-read'); }
      fetch(readUrlBaseFn(id), {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken
        }
      }).then(function (response) {
        if (response.status === 401) tampilkanSesiBerakhir();
      }).catch(function () {});
    }

    // Optimistic: badge langsung berkurang & request hapusnya jalan di
    // background. Item-nya sendiri nggak langsung ilang mendadak -- dikasih
    // animasi fade+collapse dulu (class .is-removing, lihat transition di
    // atas), baru re-render beneran setelah animasinya kelar. Kalau request
    // hapus ternyata gagal, poll berikutnya (tiap 3 detik) otomatis
    // munculin lagi -- nggak perlu penanganan error khusus.
    function removeNotification(id, itemEl) {
      fetch(deleteUrlBase + encodeURIComponent(id), {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken
        }
      }).then(function (response) {
        if (response.status === 401) tampilkanSesiBerakhir();
      }).catch(function () {});
      notifications = notifications.filter(function (n) { return String(n.id) !== String(id); });
      updateBadge();
      if (itemEl) {
        var done = false;
        function finish() {
          if (done) return;
          done = true;
          render();
        }
        // Ukur tinggi ASLI item saat ini dulu & pasang sebagai max-height
        // inline persis segitu (bukan nebak angka statis) -- baru dipaksa
        // reflow (baca offsetHeight) sebelum nambahin .is-removing, biar
        // browser sempat "nyimpen" titik awal itu dan transisinya beneran
        // ke-animate dari tinggi asli turun ke 0, bukan loncat langsung.
        itemEl.style.maxHeight = itemEl.offsetHeight + 'px';
        itemEl.style.overflow = 'hidden';
        itemEl.offsetHeight; // force reflow
        itemEl.addEventListener('transitionend', function handler(event) {
          if (event.propertyName !== 'max-height') return;
          itemEl.removeEventListener('transitionend', handler);
          finish();
        });
        setTimeout(finish, 260); // fallback kalau transitionend nggak sempat kepicu
        requestAnimationFrame(function () { itemEl.classList.add('is-removing'); });
      } else {
        render();
      }
    }

    // Dialog konfirmasi "Hapus Semua" -- dibuat sekali & dipakai ulang,
    // ngikutin pola .confirm-overlay/.confirm-box yang sudah ada di seluruh
    // aplikasi (styling globalnya di partials/dash-styles.blade.php), biar
    // tombolnya konsisten walau dipasang dari 2 partial beda (Admin lewat
    // admin-ui-consistency.blade.php, Pimpinan/Satuan lewat
    // satlak-notification-close-text.blade.php).
    function ensureHapusSemuaOverlay() {
      var overlay = document.getElementById('notifHapusSemuaOverlay');
      if (overlay) return overlay;
      overlay = document.createElement('div');
      overlay.className = 'confirm-overlay';
      overlay.id = 'notifHapusSemuaOverlay';
      overlay.innerHTML = '<div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="notifHapusSemuaTitle">' +
        '<div class="confirm-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg></div>' +
        '<h3 id="notifHapusSemuaTitle">Hapus Semua Notifikasi?</h3>' +
        '<p>Semua notifikasi di daftar ini akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>' +
        '<div class="confirm-actions"><button type="button" class="btn" id="notifHapusSemuaBatal">Batal</button><button type="button" class="btn btn-primary" id="notifHapusSemuaYa">Ya, Hapus Semua</button></div>' +
        '</div>';
      document.body.appendChild(overlay);
      function tutupOverlay() { overlay.classList.remove('open'); }
      overlay.querySelector('#notifHapusSemuaBatal').addEventListener('click', tutupOverlay);
      overlay.addEventListener('click', function (e) { if (e.target === overlay) tutupOverlay(); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && overlay.classList.contains('open')) tutupOverlay(); });
      overlay.querySelector('#notifHapusSemuaYa').addEventListener('click', function () {
        tutupOverlay();
        eksekusiHapusSemua();
      });
      return overlay;
    }

    // Optimistic sama seperti removeNotification/markNotificationRead:
    // daftar & badge langsung dikosongkan di klien, request hapus-semuanya
    // jalan di background. Kalau gagal, poll berikutnya (tiap 3 detik)
    // otomatis munculin lagi notifikasi yang ternyata belum kehapus.
    function eksekusiHapusSemua() {
      notifications = [];
      render();
      fetch(hapusSemuaUrl, {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken
        }
      }).then(function (response) {
        if (response.status === 401) tampilkanSesiBerakhir();
      }).catch(function () {});
    }

    // Dipanggil dari tombol "Hapus Semua" yang dipasang di sebelah "Tutup"
    // (lihat partials/admin-ui-consistency.blade.php untuk Admin &
    // partials/satlak-notification-close-text.blade.php untuk Pimpinan/
    // Satuan) -- diexpose lewat window karena kedua partial itu adalah IIFE
    // terpisah yang tidak punya akses ke closure `notifications` di sini.
    window.cycloneHapusSemuaNotifikasi = function () {
      if (!notifications.length) {
        window.cycloneShowToast && window.cycloneShowToast('info', 'Tidak ada notifikasi untuk dihapus.');
        return;
      }
      var overlay = ensureHapusSemuaOverlay();
      overlay.offsetHeight; // force reflow biar animasi open konsisten (lihat pola sesiBerakhirOverlay)
      overlay.classList.add('open');
    };


    // relevan (bukan cuma nampilin pesan doang) -- lihat window.
    // cycloneGoToSection() yang diexpose masing-masing dashboard (Satuan:
    // laporan-role.blade.php, Pimpinan: laporan-pimpinan.blade.php, Admin:
    // dash-script.blade.php). Selama URL-nya masih di halaman /dashboard
    // yang sama (SPA per-role, semua section sudah ada di DOM), cukup
    // switch tab di tempat tanpa reload -- baru fallback pindah halaman
    // penuh kalau ternyata section-nya tidak ditemukan di DOM saat ini
    // (mis. modul terkait dinonaktifkan Admin utk satuan ini).
    function goToNotifikasi(url) {
      if (!url) return;
      close();
      var hashId = '';
      try {
        var target = new URL(url, window.location.origin);
        hashId = target.hash ? target.hash.slice(1) : '';
        var samaHalaman = target.pathname === window.location.pathname
          || (window.__cycloneUrlMasked && target.pathname === '/dashboard');
        if (!samaHalaman) { window.location.href = url; return; }
      } catch (e) { window.location.href = url; return; }
      if (!hashId) return;
      // Bukan section tab biasa -- buka modal "Pengaturan Akun" > tab
      // Ganti Password (lihat window.openProfileModal di laporan-role.
      // blade.php / laporan-pimpinan.blade.php).
      if (hashId === 'profil-password' && typeof window.openProfileModal === 'function') {
        window.openProfileModal('profileSettingsView');
        return;
      }
      if (typeof window.cycloneGoToSection === 'function' && window.cycloneGoToSection(hashId)) return;
      window.location.href = url;
    }

    // Modal "Pengumuman" -- dibuka begitu notifikasi tipe pengumuman_admin
    // (broadcast manual dari Admin lewat Setelan -> Notifikasi) diklik.
    // Dibuat sekali & dipakai ulang, isinya ditimpa tiap kali dibuka sesuai
    // judul & isi pengumuman yang diklik.
    function ensurePengumumanModal() {
      var overlay = document.getElementById('cyclonePengumumanOverlay');
      if (overlay) return overlay;
      overlay = document.createElement('div');
      overlay.className = 'cyclone-pengumuman-overlay';
      overlay.id = 'cyclonePengumumanOverlay';
      overlay.style.position = 'fixed';
      overlay.innerHTML = '<div class="cyclone-pengumuman-card" role="alertdialog" aria-modal="true" aria-labelledby="cyclonePengumumanTitle" style="position:relative;">' +
        '<button type="button" class="cyclone-pengumuman-close" id="cyclonePengumumanClose" aria-label="Tutup"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>' +
        '<div class="cyclone-pengumuman-banner">' +
          '<div class="cyclone-pengumuman-banner-icon-bg" id="cyclonePengumumanIconBg"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.6 5.6L3 18v3h3l6.1-6.1a4 4 0 0 0 5.6-5.6l-2.8 2.8a2 2 0 1 1-2.8-2.8l2.8-2.8Z"></path></svg></div>' +
          '<span class="cyclone-pengumuman-corner tl"></span><span class="cyclone-pengumuman-corner br"></span>' +
          '<div class="cyclone-pengumuman-icon-wrap">' +
            '<span class="cyclone-pengumuman-icon-ring"></span>' +
            '<div class="cyclone-pengumuman-icon" id="cyclonePengumumanIcon"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.6 5.6L3 18v3h3l6.1-6.1a4 4 0 0 0 5.6-5.6l-2.8 2.8a2 2 0 1 1-2.8-2.8l2.8-2.8Z"></path></svg></div>' +
          '</div>' +
        '</div>' +
        '<div class="cyclone-pengumuman-content">' +
          '<span class="cyclone-pengumuman-badge" id="cyclonePengumumanBadge">Pemeliharaan Sistem</span>' +
          '<h3 class="cyclone-pengumuman-title" id="cyclonePengumumanTitle"></h3>' +
          '<ul class="cyclone-pengumuman-details" id="cyclonePengumumanDetails">' +
            '<li><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg><span class="label">Dikirim</span><span class="value" id="cyclonePengumumanWaktu">-</span></li>' +
            '<li><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg><span class="label">Sumber</span><span class="value">Admin Sistem</span></li>' +
          '</ul>' +
          '<p class="cyclone-pengumuman-eyebrow">Isi Pengumuman</p>' +
          '<div class="cyclone-pengumuman-body-wrap"><p class="cyclone-pengumuman-body" id="cyclonePengumumanBody"></p></div>' +
        '</div>' +
        '<div class="cyclone-pengumuman-actions"><button type="button" id="cyclonePengumumanTutup"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Saya Mengerti</button></div>' +
        '</div>';
      document.body.appendChild(overlay);
      function tutupOverlay() { overlay.classList.remove('open'); }
      overlay.querySelector('#cyclonePengumumanClose').addEventListener('click', tutupOverlay);
      overlay.querySelector('#cyclonePengumumanTutup').addEventListener('click', tutupOverlay);
      overlay.addEventListener('click', function (e) { if (e.target === overlay) tutupOverlay(); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && overlay.classList.contains('open')) tutupOverlay(); });
      return overlay;
    }

    // Ikon & label badge disesuaikan kategori -- 'maintenance' (atau kosong/
    // null, pengumuman lama) pakai tema pemeliharaan (kunci pas di banner &
    // watermark). Kategori lain (kalaupun suatu saat ditambah & dibuat
    // clickable) fallback ke tema pengumuman umum supaya tetap wajar dipakai.
    var IKON_PEMELIHARAAN = '<svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.6 5.6L3 18v3h3l6.1-6.1a4 4 0 0 0 5.6-5.6l-2.8 2.8a2 2 0 1 1-2.8-2.8l2.8-2.8Z"></path></svg>';
    var IKON_PENGUMUMAN = '<svg viewBox="0 0 24 24"><path d="M3 11l18-5v14L3 15v-4Z"></path><path d="M7 15v5a2 2 0 0 0 2 2h1"></path></svg>';

    function tampilkanPengumuman(judul, pesan, kategori, waktu) {
      var overlay = ensurePengumumanModal();
      var isMaintenance = !kategori || kategori === 'maintenance';
      overlay.querySelector('#cyclonePengumumanTitle').textContent = judul || 'Pengumuman';
      overlay.querySelector('#cyclonePengumumanBody').textContent = pesan || '';
      overlay.querySelector('#cyclonePengumumanBadge').textContent = isMaintenance ? 'Pemeliharaan Sistem' : 'Pengumuman';
      overlay.querySelector('#cyclonePengumumanWaktu').textContent = waktu || '-';
      overlay.querySelector('#cyclonePengumumanIcon').innerHTML = isMaintenance ? IKON_PEMELIHARAAN : IKON_PENGUMUMAN;
      overlay.querySelector('#cyclonePengumumanIconBg').innerHTML = isMaintenance ? IKON_PEMELIHARAAN : IKON_PENGUMUMAN;
      overlay.offsetHeight; // force reflow biar animasi open konsisten (lihat pola sesiBerakhirOverlay)
      overlay.classList.add('open');
    }
    // Diekspos ke window (pola sama seperti window.cycloneTampilkanSesiBerakhir
    // di atas) supaya banner Mode Maintenance (lihat partials/
    // maintenance-banner.blade.php) bisa buka modal "Pengumuman" yang SAMA
    // PERSIS ini saat diklik -- satu tampilan konsisten, bukan modal ke-2
    // yang beda gaya.
    window.cycloneTampilkanPengumuman = tampilkanPengumuman;

    function render() {
      list.innerHTML = '';
      if (!notifications.length) {
        var empty = document.createElement('div');
        empty.className = 'cyclone-notif-empty-runtime';
        empty.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg><p>Belum ada notifikasi saat ini.</p>';
        list.appendChild(empty);
      } else {
        notifications.forEach(function (notification) {
          var item = document.createElement('div');
          item.className = 'cyclone-notif-item';
          // Pengumuman broadcast Admin (lihat App\Notifications\
          // PengumumanBroadcastAdmin) punya 2 kategori:
          // - 'maintenance' (atau kategori kosong/null -- pengumuman lama
          //   sebelum kolom kategori ini ada, diperlakukan sama supaya
          //   perilaku yang sudah jalan tidak berubah): tetap ditandai
          //   belum-dibaca & bisa diklik utk buka modal penjelasan.
          // - 'keterangan': sekadar info umum -- TIDAK pernah ditandai
          //   belum-dibaca (tidak ada titik oranye) & TIDAK bisa diklik
          //   sama sekali, cuma tampil sebagai teks biasa.
          var isPengumuman = notification.tipe === 'pengumuman_admin';
          // Notifikasi 'surat_info' (mis. balasan surat sudah di-ACC Danpus,
          // lihat LaporanSuratBalasanDikonfirmasi) diperlakukan sama seperti
          // 'keterangan': sekadar info, tanpa titik belum-dibaca & tidak bisa
          // diklik (memang tidak punya 'url' tujuan).
          var isKeteranganSaja = (isPengumuman && notification.kategori === 'keterangan') || notification.tipe === 'surat_info';
          if (isKeteranganSaja) {
            item.classList.add('is-read');
          } else if (!notification.read) {
            item.classList.add('is-unread');
          } else {
            item.classList.add('is-read');
          }
          if (isPengumuman && !isKeteranganSaja) {
            item.classList.add('is-clickable');
            item.setAttribute('role', 'button');
            item.setAttribute('tabindex', '0');
            var bukaPengumuman = function () { markNotificationRead(notification.id, item); tampilkanPengumuman(notification.title, notification.message, notification.kategori, notification.time); };
            item.addEventListener('click', bukaPengumuman);
            item.addEventListener('keydown', function (event) {
              if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); bukaPengumuman(); }
            });
          } else if (!isPengumuman && notification.url) {
            item.classList.add('is-clickable');
            item.setAttribute('role', 'button');
            item.setAttribute('tabindex', '0');
            item.addEventListener('click', function () { markNotificationRead(notification.id, item); goToNotifikasi(notification.url); });
            item.addEventListener('keydown', function (event) {
              if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); markNotificationRead(notification.id, item); goToNotifikasi(notification.url); }
            });
          }
          item.innerHTML =
            '<div class="cyclone-notif-body"><p>' + escapeHtml(notification.message) + '</p><small>' + escapeHtml(notification.time || '') + '</small></div>';
          var remove = document.createElement('button');
          remove.type = 'button';
          remove.className = 'cyclone-notif-remove';
          remove.setAttribute('aria-label', 'Hapus notifikasi');
          remove.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>';
          remove.addEventListener('click', function (event) {
            event.preventDefault(); event.stopPropagation();
            removeNotification(notification.id, item);
          });
          item.appendChild(remove); list.appendChild(item);
        });
      }
      updateBadge();
    }

    function close() {
      dropdown.classList.remove('open');
      button.classList.remove('open');
      button.setAttribute('aria-expanded', 'false');
    }

    function toggleNotification(event) {
      if (event) { event.preventDefault(); event.stopPropagation(); }
      if (dropdown.classList.contains('open')) close();
      else {
        var profileDropdown = document.getElementById('profileDropdown');
        var profileBtn = document.getElementById('profileMenuBtn');
        if (profileDropdown) profileDropdown.classList.remove('open');
        if (profileBtn) {
          profileBtn.classList.remove('open');
          profileBtn.setAttribute('aria-expanded', 'false');
        }
        dropdown.classList.add('open');
        button.classList.add('open');
        button.setAttribute('aria-expanded', 'true');
      }
    }

    if (!button.dataset.notifBound) {
      button.dataset.notifBound = '1';
      button.addEventListener('click', toggleNotification, false);
    }

    if (!document.documentElement.dataset.notifGlobalBound) {
      document.documentElement.dataset.notifGlobalBound = '1';
      document.addEventListener('click', function (event) {
        var target = event.target;
        var clickedButton = target && target.closest ? target.closest('#notifBtn') : null;
        if (clickedButton) return;
        var currentMenu = document.getElementById('notifMenu');
        if (currentMenu && !currentMenu.contains(target)) {
          var currentDropdown = document.getElementById('notifDropdown');
          if (currentDropdown) currentDropdown.classList.remove('open');
          var currentButton = currentMenu.querySelector('#notifBtn');
          if (currentButton) {
            currentButton.classList.remove('open');
            currentButton.setAttribute('aria-expanded', 'false');
          }
        }
      }, true);
    }

    // ── Popup melayang (toast) untuk notifikasi BARU ─────────────────────
    // Selain badge lonceng & suara, tiap notifikasi yang datang saat
    // dashboard sedang dibuka juga muncul sebagai kartu melayang di pojok
    // atas: bisa diklik (langsung ke surat/halaman terkait), ada tombol
    // tutup, hilang sendiri ~6,5 dtk, dan berhenti menghitung mundur selama
    // disentuh/di-hover. Komponen ini SENGAJA terpisah dari
    // cycloneShowToast (toast sukses/gagal 3 dtk) agar tidak saling ganggu.
    var NTOAST_MAKS = 3;
    var NTOAST_MS = 6500;
    var ntoastTertunda = [];

    function ensureNToastStyle() {
      if (document.getElementById('cyclone-notif-toast-style')) return;
      var st = document.createElement('style');
      st.id = 'cyclone-notif-toast-style';
      st.textContent =
        '.cyclone-ntoast-stack{position:fixed;z-index:200001;top:calc(env(safe-area-inset-top,0px) + 92px);right:20px;width:min(380px,calc(100vw - 24px));display:flex;flex-direction:column;gap:10px;pointer-events:none;}' +
        '@media (max-width:700px){.cyclone-ntoast-stack{left:12px;right:12px;width:auto;top:calc(env(safe-area-inset-top,0px) + 92px);}}' +
        '.cyclone-ntoast{position:relative;overflow:hidden;pointer-events:auto;display:flex;align-items:flex-start;gap:12px;padding:13px 42px 17px 14px;box-sizing:border-box;border-radius:14px;background:var(--panel,#1b2721);border:1px solid var(--border-strong,rgba(212,175,55,.42));box-shadow:0 18px 44px rgba(0,0,0,.45);color:var(--text,#f4f1e6);font-family:var(--body,inherit);opacity:0;transform:translateY(-14px) scale(.97);animation:cycloneNToastIn .38s cubic-bezier(.2,.9,.25,1.15) forwards;-webkit-tap-highlight-color:transparent;}' +
        '.cyclone-ntoast.is-clickable{cursor:pointer;}' +
        '.cyclone-ntoast.is-clickable:active{transform:scale(.985);}' +
        '.cyclone-ntoast.is-leaving{animation:cycloneNToastOut .28s ease forwards;}' +
        '.cyclone-ntoast-icon{flex:0 0 auto;width:34px;height:34px;border-radius:50%;background:var(--gold-dim,rgba(255,152,0,.14));color:var(--gold-bright,#ff9800);display:flex;align-items:center;justify-content:center;}' +
        '.cyclone-ntoast-icon svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:2;}' +
        '.cyclone-ntoast-body{display:flex;flex-direction:column;gap:3px;min-width:0;}' +
        '.cyclone-ntoast-label{font-family:var(--mono,monospace);font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--gold-bright,#ff9800);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}' +
        '.cyclone-ntoast-text{font-size:13.5px;font-weight:600;line-height:1.4;overflow-wrap:anywhere;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;}' +
        '.cyclone-ntoast-time{font-size:11px;color:var(--text-muted,#9fb3a5);}' +
        '.cyclone-ntoast-close{position:absolute;top:8px;right:8px;width:28px;height:28px;border:0;border-radius:8px;background:transparent;color:var(--text-muted,#9fb3a5);cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;}' +
        '.cyclone-ntoast-close:hover{background:var(--gold-dim,rgba(255,152,0,.14));color:var(--gold-bright,#ff9800);}' +
        '.cyclone-ntoast-close svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;pointer-events:none;}' +
        '.cyclone-ntoast-bar{position:absolute;left:0;bottom:0;height:3px;width:100%;background:var(--gold-bright,#ff9800);transform-origin:left;animation:cycloneNToastBar ' + NTOAST_MS + 'ms linear forwards;}' +
        '.cyclone-ntoast:hover .cyclone-ntoast-bar,.cyclone-ntoast:active .cyclone-ntoast-bar{animation-play-state:paused;}' +
        'html[data-theme="light"] .cyclone-ntoast-label{color:#a85a00;}' +
        'html[data-theme="light"] .cyclone-ntoast{box-shadow:0 14px 34px rgba(60,40,0,.22);}' +
        '@keyframes cycloneNToastIn{to{opacity:1;transform:translateY(0) scale(1);}}' +
        '@keyframes cycloneNToastOut{from{opacity:1;transform:translateY(0) scale(1);}to{opacity:0;transform:translateY(-10px) scale(.96);}}' +
        '@keyframes cycloneNToastBar{from{transform:scaleX(1);}to{transform:scaleX(0);}}' +
        '@media (prefers-reduced-motion:reduce){.cyclone-ntoast,.cyclone-ntoast.is-leaving{animation-duration:.01s;}}';
      document.head.appendChild(st);
    }

    function tutupToastNotifikasi(el) {
      if (!el || el.dataset.leaving) return;
      el.dataset.leaving = '1';
      el.classList.add('is-leaving');
      setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 300);
    }

    function tampilkanToastNotifikasi(n) {
      ensureNToastStyle();
      var stack = document.getElementById('cycloneNToastStack');
      if (!stack) {
        stack = document.createElement('div');
        stack.id = 'cycloneNToastStack';
        stack.className = 'cyclone-ntoast-stack';
        stack.setAttribute('role', 'status');
        stack.setAttribute('aria-live', 'polite');
        document.body.appendChild(stack);
      }
      // Batasi tumpukan: yang paling lama disingkirkan dulu.
      var aktif = Array.prototype.slice.call(stack.querySelectorAll('.cyclone-ntoast:not([data-leaving])'));
      while (aktif.length >= NTOAST_MAKS) tutupToastNotifikasi(aktif.pop());

      var isPengumuman = n.tipe === 'pengumuman_admin';
      var isKeteranganSaja = (isPengumuman && n.kategori === 'keterangan') || n.tipe === 'surat_info';
      var aksi = null;
      if (isPengumuman && !isKeteranganSaja) {
        aksi = function () { markNotificationRead(n.id, null); tampilkanPengumuman(n.title, n.message, n.kategori, n.time); };
      } else if (!isPengumuman && n.url) {
        aksi = function () { markNotificationRead(n.id, null); goToNotifikasi(n.url); };
      }

      var el = document.createElement('div');
      el.className = 'cyclone-ntoast' + (aksi ? ' is-clickable' : '');
      el.innerHTML =
        '<span class="cyclone-ntoast-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg></span>' +
        '<span class="cyclone-ntoast-body"><span class="cyclone-ntoast-label"></span><span class="cyclone-ntoast-text"></span><span class="cyclone-ntoast-time"></span></span>' +
        '<button type="button" class="cyclone-ntoast-close" aria-label="Tutup notifikasi"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>' +
        '<span class="cyclone-ntoast-bar"></span>';
      el.querySelector('.cyclone-ntoast-label').textContent = n.title || 'Notifikasi baru';
      el.querySelector('.cyclone-ntoast-text').textContent = n.message || 'Ada pembaruan baru.';
      el.querySelector('.cyclone-ntoast-time').textContent = n.time || 'Baru saja';

      el.querySelector('.cyclone-ntoast-close').addEventListener('click', function (e) {
        e.stopPropagation();
        tutupToastNotifikasi(el);
      });
      if (aksi) {
        el.addEventListener('click', function () { tutupToastNotifikasi(el); aksi(); });
      }
      el.querySelector('.cyclone-ntoast-bar').addEventListener('animationend', function () { tutupToastNotifikasi(el); });

      stack.insertBefore(el, stack.firstChild); // terbaru di paling atas
    }

    // Terima daftar notifikasi baru dari poll (urutan: terbaru dulu).
    function antrikanToastNotifikasi(daftar) {
      if (!daftar || !daftar.length) return;
      // Dropdown lonceng sedang terbuka -> notifikasinya sudah kelihatan di sana.
      if (dropdown.classList.contains('open')) return;
      // Tab sedang di latar belakang: tahan, tampilkan saat pengguna kembali.
      if (document.hidden) {
        ntoastTertunda = daftar.concat(ntoastTertunda).slice(0, NTOAST_MAKS);
        return;
      }
      daftar.slice(0, NTOAST_MAKS).reverse().forEach(tampilkanToastNotifikasi);
    }
    document.addEventListener('visibilitychange', function () {
      if (document.hidden || !ntoastTertunda.length) return;
      var tunda = ntoastTertunda; ntoastTertunda = [];
      tunda.slice(0, NTOAST_MAKS).reverse().forEach(tampilkanToastNotifikasi);
    });
    window.cycloneTampilkanToastNotifikasi = tampilkanToastNotifikasi;

    render();

    // Realtime: poll berkala buat notifikasi baru & sinkron daftar/badge --
    // dipasang di sini (bukan cuma di halaman Permintaan Laporan satuan)
    // biar jalan di navbar SEMUA role. Hasil poll dipakai APA ADANYA buat
    // nimpa `notifications` (bukan digabung/diff) -- otomatis mencerminkan
    // notifikasi baru maupun yang sudah dihapus dari device/tab lain.
    if (!menu.dataset.notifPollBound) {
      menu.dataset.notifPollBound = '1';
      var polling = false;
      function poll() {
        if (polling) return;
        polling = true;
        fetch(pollUrl, {
          method: 'GET',
          credentials: 'same-origin',
          cache: 'no-store',
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
          if (response.status === 401) { tampilkanSesiBerakhir(); throw new Error('unauthenticated'); }
          if (!response.ok) throw new Error('poll failed');
          return response.json();
        }).then(function (data) {
          if (!data || !Array.isArray(data.notifications)) return;

          // Deteksi notifikasi yang BENAR-BENAR baru (id belum pernah
          // tercatat di notifKnownIds) SEBELUM notifications ditimpa --
          // kalau ada minimal satu, mainkan suara sekali (bukan per-item,
          // biar tidak numpuk bunyi berkali-kali kalau beberapa notif
          // masuk bersamaan dalam satu jeda poll 3 detik).
          var adaNotifikasiBaru = false;
          var daftarBaru = [];
          data.notifications.forEach(function (n) {
            if (!notifKnownIds[String(n.id)]) {
              notifKnownIds[String(n.id)] = true;
              adaNotifikasiBaru = true;
              daftarBaru.push(n);
            }
          });
          if (adaNotifikasiBaru) mainkanSuaraNotifikasi();

          notifications = data.notifications;
          render();

          // Popup melayang (setelah render supaya klik di popup bisa
          // menandai-dibaca notifikasi yang sudah ada di daftar).
          if (adaNotifikasiBaru) antrikanToastNotifikasi(daftarBaru);
        }).catch(function () {}).finally(function () { polling = false; });
      }
      // Poll pertama LANGSUNG jalan (dulu murni nunggu interval pertama) --
      // `notifications` di atas udah di-seed dari render server, jadi
      // panggilan langsung ini aman (gak flicker), cuma mempercepat begitu
      // ADA notifikasi baru yang kejadian pas navbar ini baru dimuat (audit
      // polling menyeluruh 2026-09-14).
      poll();
      pollTimer = window.setInterval(poll, POLL_INTERVAL_MS);
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initNotificationControls);
  else initNotificationControls();
})();
</script>
