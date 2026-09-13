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
  var SIBERAD_NOTIFIKASI_AKTIF = @json($modulAktif['notifikasi'] ?? true);

  function initNotificationControls() {
    if (!SIBERAD_NOTIFIKASI_AKTIF) return;
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
      button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg><span class="siberad-notif-badge" style="display:none;"></span>';
      dropdown = document.createElement('div');
      dropdown.className = 'profile-dropdown';
      dropdown.id = 'notifDropdown';
      dropdown.innerHTML = '<div class="profile-dropdown-head notif-head"><div class="profile-dropdown-name">Notifikasi</div></div><div class="siberad-notif-list"></div>';
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
    var style = document.getElementById('siberad-notification-style');
    if (!style) {
      style = document.createElement('style');
      style.id = 'siberad-notification-style';
      style.textContent = `
        .topbar{position:relative!important;z-index:100000!important;}
        .topbar-actions{position:relative!important;z-index:100001!important;pointer-events:auto!important;}
        .topbar-actions>*{pointer-events:auto!important;}
        .notif-menu{position:relative!important;z-index:100002!important;pointer-events:auto!important;}
        .notif-menu>#notifBtn{position:relative!important;z-index:100003!important;display:flex!important;align-items:center!important;justify-content:center!important;cursor:pointer!important;pointer-events:auto!important;}
        .notif-menu>#notifBtn svg{width:19px;height:19px;display:block;pointer-events:none;}
        .siberad-notif-badge{position:absolute;top:2px;right:2px;min-width:15px;height:15px;padding:0 3px;box-sizing:border-box;border-radius:999px;background:var(--red);color:#fff;font-size:9px;font-weight:800;line-height:15px;text-align:center;box-shadow:0 0 0 2px var(--panel,#0c2417);pointer-events:none;}
        #notifDropdown{position:absolute!important;z-index:100004!important;min-width:300px;max-width:340px;right:0;top:calc(100% + 8px);pointer-events:auto!important;padding:0!important;overflow:hidden;}
        @media(max-width:420px){#notifDropdown{position:fixed!important;left:12px!important;right:12px!important;top:90px!important;min-width:0;max-width:none;width:auto;}}
        #notifDropdown .notif-head{display:flex;align-items:center;min-height:22px;padding:14px 16px 12px!important;margin-bottom:0!important;}
        /* ~4 item kelihatan sekaligus (tiap item kira-kira 72px), sisanya discroll. */
        #notifDropdown .siberad-notif-list{display:flex;flex-direction:column;max-height:288px;overflow-y:auto;}
        #notifDropdown .siberad-notif-item{position:relative;padding:12px 40px 12px 14px;box-sizing:border-box;transition:background .15s ease;}
        #notifDropdown .siberad-notif-item:not(:last-child){border-bottom:1px solid var(--border-soft);}
        #notifDropdown .siberad-notif-item:hover{background:var(--hover-tint);}
        /* max-height di-set INLINE (bukan di sini) pas mulai animasi hapus,
           persis sesuai tinggi asli item saat itu -- biar transisinya mulus
           dari ukuran sebenarnya ke 0, bukan dari angka tebakan statis yang
           bisa beda dikit dari tinggi asli & ganggu tampilan normal. */
        #notifDropdown .siberad-notif-item.is-removing{overflow:hidden;transition:background .15s ease,max-height .22s ease,padding .22s ease,opacity .18s ease,border-color .22s ease;max-height:0!important;padding-top:0;padding-bottom:0;opacity:0;border-color:transparent;pointer-events:none;}
        #notifDropdown .siberad-notif-body{min-width:0;}
        #notifDropdown .siberad-notif-item.is-clickable{cursor:pointer;}
        /* Titik penanda kecil di kiri untuk notif yang BELUM dibaca. Yang
           sudah dibaca (is-read) diredupin dikit supaya beda dari yang
           belum, tapi isinya tetap utuh & tetap kelihatan di daftar. */
        #notifDropdown .siberad-notif-item.is-unread{padding-left:26px;}
        #notifDropdown .siberad-notif-item.is-unread::before{content:'';position:absolute;left:12px;top:18px;width:7px;height:7px;border-radius:50%;background:var(--gold-bright,#d4af37);}
        #notifDropdown .siberad-notif-item.is-read p{font-weight:500;color:var(--text-dim);}
        #notifDropdown .siberad-notif-item p{margin:0;font-size:12.5px;font-weight:600;line-height:1.45;color:var(--text);word-break:break-word;}
        #notifDropdown .siberad-notif-item small{display:block;margin-top:4px;font-size:10.5px;font-weight:500;color:var(--text-dim);}
        #notifDropdown .siberad-notif-remove{position:absolute;right:9px;top:50%;transform:translateY(-50%);width:24px;height:24px;border:0;border-radius:7px;background:transparent;color:var(--text-dim);cursor:pointer;padding:4px;display:flex;align-items:center;justify-content:center;box-sizing:border-box;transition:background .15s ease,color .15s ease;}
        #notifDropdown .siberad-notif-remove svg{width:100%;height:100%;display:block;}
        #notifDropdown .siberad-notif-remove:hover{background:rgba(198,40,40,.14);color:var(--red,#c83b3b);}
        #notifDropdown .siberad-notif-empty-runtime{padding:30px 18px 26px;text-align:center;color:var(--text-muted);}
        #notifDropdown .siberad-notif-empty-runtime svg{width:32px;height:32px;stroke:var(--text-dim);margin:0 auto 12px;display:block;}
        #notifDropdown .siberad-notif-empty-runtime p{margin:0;font-size:12px;line-height:1.55;}
        /* Modal "Pengumuman" -- dibuka saat notifikasi tipe pengumuman_admin
           (lihat App\Notifications\PengumumanBroadcastAdmin) diklik. Dibuat
           self-contained di sini (bukan pakai .report-modal punya
           laporan-role/laporan-pimpinan) karena partial ini juga dipasang di
           dashboard Admin, yang tidak punya CSS itu. */
        .siberad-pengumuman-overlay{position:fixed;inset:0;z-index:100210;background:rgba(2,4,6,.6);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease;box-sizing:border-box;}
        :root[data-theme="light"] .siberad-pengumuman-overlay{background:rgba(60,50,20,.35);}
        .siberad-pengumuman-overlay.open{opacity:1;visibility:visible;pointer-events:auto;}
        .siberad-pengumuman-card{width:min(440px,100%);background:var(--panel,var(--p-surface,#fff));border:1px solid var(--border-soft,var(--p-border,#e2e8f0));border-radius:18px;padding:26px 26px 24px;box-shadow:0 25px 70px rgba(0,0,0,.35);box-sizing:border-box;transform:translateY(14px) scale(.97);transition:transform .2s ease;text-align:center;}
        .siberad-pengumuman-overlay.open .siberad-pengumuman-card{transform:translateY(0) scale(1);}
        .siberad-pengumuman-close{position:absolute;top:14px;right:14px;width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:1px solid var(--border-soft,var(--p-border,#e2e8f0));background:transparent;color:var(--text-dim,var(--p-muted,#64748b));cursor:pointer;transition:border-color .2s ease,color .2s ease,transform .2s ease;}
        .siberad-pengumuman-close:hover{border-color:var(--red,#c83b3b);color:var(--red,#c83b3b);transform:rotate(90deg);}
        .siberad-pengumuman-close svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;display:block;}
        .siberad-pengumuman-icon{width:54px;height:54px;margin:0 auto 16px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--gold-dim,rgba(201,122,0,.12));color:var(--gold-bright,var(--p-accent,#c97a00));}
        .siberad-pengumuman-icon svg{width:26px;height:26px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
        .siberad-pengumuman-title{margin:0 0 10px;font-family:var(--display,inherit);font-size:18px;font-weight:800;color:var(--text,var(--p-text,#17212b));line-height:1.35;}
        .siberad-pengumuman-body{margin:0;font-size:13px;line-height:1.7;color:var(--text-muted,var(--p-muted,#64748b));white-space:pre-wrap;text-align:left;}
        .siberad-pengumuman-actions{margin-top:20px;}
        .siberad-pengumuman-actions button{width:100%;border:0;border-radius:10px;padding:11px;font-size:13px;font-weight:700;color:#fff;background:var(--gold-bright,var(--p-accent,#c97a00));cursor:pointer;transition:filter .15s ease,transform .15s ease;}
        .siberad-pengumuman-actions button:hover{filter:brightness(1.08);transform:translateY(-1px);}
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
      if (window.__siberadLoggingOut) return;
      if (window.__siberadSesiBerakhirShown) return;
      window.__siberadSesiBerakhirShown = true;
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
    window.siberadTampilkanSesiBerakhir = tampilkanSesiBerakhir;

    @php
      // PENTING: jangan taruh array literal (yang punya koma) langsung di
      // dalam @json(...) -- directive @json Blade motong argumennya pakai
      // explode(',') mentah (buat pisahin opsi encoding/depth opsional),
      // jadi koma DI DALAM array/closure ikut kepotong dan hasil PHP-nya
      // jadi rusak (ParseError "Unclosed '[' does not match ')'"). Makanya
      // array-nya dirakit dulu di variabel biasa di sini, baru @json()
      // dipanggil dengan satu variabel tunggal (tanpa koma di levelnya).
      $__siberadNotifications = auth()->user()?->notifications?->take(20)?->map(function ($n) {
        return ['id' => $n->id, 'message' => $n->data['pesan'] ?? 'Status laporan diperbarui.', 'time' => optional($n->created_at)->diffForHumans(), 'url' => $n->data['url'] ?? null, 'title' => $n->data['judul'] ?? null, 'tipe' => $n->data['tipe'] ?? null, 'kategori' => $n->data['kategori'] ?? null, 'read' => ! is_null($n->read_at)];
      })->values() ?? [];
    @endphp
    var notifications = @json($__siberadNotifications);

    var list = dropdown.querySelector('.siberad-notif-list');
    if (!list) {
      list = document.createElement('div');
      list.className = 'siberad-notif-list';
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

    // Notif pengumuman kategori 'keterangan' sengaja TIDAK PERNAH dianggap
    // "belum dibaca" -- tidak ada titik oranye & tidak diklik sama sekali
    // (lihat render()), jadi wajar kalau flag `read`-nya di server tetap
    // false selamanya (tidak ada aksi klik yang bisa nge-trigger markAsRead).
    // Kalau tetap dihitung di sini, badge lonceng jadi nyangkut kehitung
    // terus padahal secara visual tidak pernah ditandai belum dibaca.
    function hitungSebagaiUnread(n) {
      if (n.tipe === 'pengumuman_admin' && n.kategori === 'keterangan') return false;
      return !n.read;
    }

    function unreadCount() {
      var count = 0;
      for (var i = 0; i < notifications.length; i++) if (hitungSebagaiUnread(notifications[i])) count++;
      return count;
    }

    function updateBadge() {
      var badge = button.querySelector('.siberad-notif-badge');
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
    window.siberadHapusSemuaNotifikasi = function () {
      if (!notifications.length) {
        window.siberadShowToast && window.siberadShowToast('info', 'Tidak ada notifikasi untuk dihapus.');
        return;
      }
      var overlay = ensureHapusSemuaOverlay();
      overlay.offsetHeight; // force reflow biar animasi open konsisten (lihat pola sesiBerakhirOverlay)
      overlay.classList.add('open');
    };


    // relevan (bukan cuma nampilin pesan doang) -- lihat window.
    // siberadGoToSection() yang diexpose masing-masing dashboard (Satuan:
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
        if (target.pathname !== window.location.pathname) { window.location.href = url; return; }
      } catch (e) { window.location.href = url; return; }
      if (!hashId) return;
      // Bukan section tab biasa -- buka modal "Pengaturan Akun" > tab
      // Ganti Password (lihat window.openProfileModal di laporan-role.
      // blade.php / laporan-pimpinan.blade.php).
      if (hashId === 'profil-password' && typeof window.openProfileModal === 'function') {
        window.openProfileModal('profileSettingsView');
        return;
      }
      if (typeof window.siberadGoToSection === 'function' && window.siberadGoToSection(hashId)) return;
      window.location.href = url;
    }

    // Modal "Pengumuman" -- dibuka begitu notifikasi tipe pengumuman_admin
    // (broadcast manual dari Admin lewat Setelan -> Notifikasi) diklik.
    // Dibuat sekali & dipakai ulang, isinya ditimpa tiap kali dibuka sesuai
    // judul & isi pengumuman yang diklik.
    function ensurePengumumanModal() {
      var overlay = document.getElementById('siberadPengumumanOverlay');
      if (overlay) return overlay;
      overlay = document.createElement('div');
      overlay.className = 'siberad-pengumuman-overlay';
      overlay.id = 'siberadPengumumanOverlay';
      overlay.style.position = 'fixed';
      overlay.innerHTML = '<div class="siberad-pengumuman-card" role="alertdialog" aria-modal="true" aria-labelledby="siberadPengumumanTitle" style="position:relative;">' +
        '<button type="button" class="siberad-pengumuman-close" id="siberadPengumumanClose" aria-label="Tutup"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>' +
        '<div class="siberad-pengumuman-icon"><svg viewBox="0 0 24 24"><path d="M3 11l18-5v14L3 15v-4Z"></path><path d="M7 15v5a2 2 0 0 0 2 2h1"></path></svg></div>' +
        '<h3 class="siberad-pengumuman-title" id="siberadPengumumanTitle"></h3>' +
        '<p class="siberad-pengumuman-body" id="siberadPengumumanBody"></p>' +
        '<div class="siberad-pengumuman-actions"><button type="button" id="siberadPengumumanTutup">Tutup</button></div>' +
        '</div>';
      document.body.appendChild(overlay);
      function tutupOverlay() { overlay.classList.remove('open'); }
      overlay.querySelector('#siberadPengumumanClose').addEventListener('click', tutupOverlay);
      overlay.querySelector('#siberadPengumumanTutup').addEventListener('click', tutupOverlay);
      overlay.addEventListener('click', function (e) { if (e.target === overlay) tutupOverlay(); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && overlay.classList.contains('open')) tutupOverlay(); });
      return overlay;
    }

    function tampilkanPengumuman(judul, pesan) {
      var overlay = ensurePengumumanModal();
      overlay.querySelector('#siberadPengumumanTitle').textContent = judul || 'Pengumuman';
      overlay.querySelector('#siberadPengumumanBody').textContent = pesan || '';
      overlay.offsetHeight; // force reflow biar animasi open konsisten (lihat pola sesiBerakhirOverlay)
      overlay.classList.add('open');
    }

    function render() {
      list.innerHTML = '';
      if (!notifications.length) {
        var empty = document.createElement('div');
        empty.className = 'siberad-notif-empty-runtime';
        empty.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg><p>Belum ada notifikasi saat ini.</p>';
        list.appendChild(empty);
      } else {
        notifications.forEach(function (notification) {
          var item = document.createElement('div');
          item.className = 'siberad-notif-item';
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
          var isKeteranganSaja = isPengumuman && notification.kategori === 'keterangan';
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
            var bukaPengumuman = function () { markNotificationRead(notification.id, item); tampilkanPengumuman(notification.title, notification.message); };
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
            '<div class="siberad-notif-body"><p>' + escapeHtml(notification.message) + '</p><small>' + escapeHtml(notification.time || '') + '</small></div>';
          var remove = document.createElement('button');
          remove.type = 'button';
          remove.className = 'siberad-notif-remove';
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
          notifications = data.notifications;
          render();
        }).catch(function () {}).finally(function () { polling = false; });
      }
      pollTimer = window.setInterval(poll, POLL_INTERVAL_MS);
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initNotificationControls);
  else initNotificationControls();
})();
</script>
