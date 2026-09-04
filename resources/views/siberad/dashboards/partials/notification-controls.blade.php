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
        #notifDropdown .siberad-notif-item p{margin:0;font-size:12.5px;font-weight:600;line-height:1.45;color:var(--text);word-break:break-word;}
        #notifDropdown .siberad-notif-item small{display:block;margin-top:4px;font-size:10.5px;font-weight:500;color:var(--text-dim);}
        #notifDropdown .siberad-notif-remove{position:absolute;right:9px;top:50%;transform:translateY(-50%);width:24px;height:24px;border:0;border-radius:7px;background:transparent;color:var(--text-dim);cursor:pointer;padding:4px;display:flex;align-items:center;justify-content:center;box-sizing:border-box;transition:background .15s ease,color .15s ease;}
        #notifDropdown .siberad-notif-remove svg{width:100%;height:100%;display:block;}
        #notifDropdown .siberad-notif-remove:hover{background:rgba(198,40,40,.14);color:var(--red,#c83b3b);}
        #notifDropdown .siberad-notif-empty-runtime{padding:30px 18px 26px;text-align:center;color:var(--text-muted);}
        #notifDropdown .siberad-notif-empty-runtime svg{width:32px;height:32px;stroke:var(--text-dim);margin:0 auto 12px;display:block;}
        #notifDropdown .siberad-notif-empty-runtime p{margin:0;font-size:12px;line-height:1.55;}
      `;
      document.head.appendChild(style);
    }

    var deleteUrlBase = '{{ url('/notifikasi') }}/';
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

    var notifications = @json(auth()->user()?->unreadNotifications?->take(20)?->map(function ($n) {
      return ['id' => $n->id, 'message' => $n->data['pesan'] ?? 'Status laporan diperbarui.', 'time' => optional($n->created_at)->diffForHumans()];
    })->values() ?? []);

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

    function updateBadge() {
      var badge = button.querySelector('.siberad-notif-badge');
      if (!badge) return;
      if (notifications.length > 0) {
        badge.textContent = notifications.length > 99 ? '99+' : String(notifications.length);
        badge.style.display = 'block';
      } else {
        badge.style.display = 'none';
      }
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
