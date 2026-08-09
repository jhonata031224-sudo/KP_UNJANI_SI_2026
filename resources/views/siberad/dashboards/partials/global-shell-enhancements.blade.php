<script>
(function () {
  // =========================================================
  // GLOBAL TOPBAR: NOTIFIKASI + KONFIRMASI KELUAR
  // Dipakai sebagai lapisan kompatibilitas untuk dashboard role
  // yang belum memiliki komponen UI terbaru.
  // =========================================================
  var notifications = @json(auth()->user()?->unreadNotifications?->map(function ($n) {
      return [
          'message' => $n->data['pesan'] ?? 'Status laporan diperbarui.',
          'time' => optional($n->created_at)->diffForHumans(),
      ];
  })->values() ?? []);

  function initNotifications() {
    var actions = document.querySelector('.topbar-actions');
    if (!actions) return;

    var menu = document.getElementById('notifMenu');
    if (!menu) {
      menu = document.createElement('div');
      menu.className = 'profile-menu';
      menu.id = 'notifMenu';

      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn-icon-toggle';
      button.id = 'notifBtn';
      button.setAttribute('aria-label', 'Notifikasi');
      button.setAttribute('aria-haspopup', 'menu');
      button.setAttribute('aria-expanded', 'false');
      button.style.position = 'relative';
      button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="var(--gold-bright)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>';

      if (notifications.length) {
        var dot = document.createElement('span');
        dot.style.cssText = 'position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:var(--red);box-shadow:0 0 0 2px var(--panel,#0c2417);';
        button.appendChild(dot);
      }

      var dropdown = document.createElement('div');
      dropdown.className = 'profile-dropdown';
      dropdown.id = 'notifDropdown';
      dropdown.setAttribute('role', 'menu');
      dropdown.setAttribute('aria-label', 'Notifikasi');

      var header = '<div class="profile-dropdown-head" style="border-bottom:1px solid var(--border-soft);display:flex;justify-content:space-between;align-items:center;gap:8px;">' +
        '<div class="profile-dropdown-name" style="font-size:14px;">Notifikasi</div>';
      if (notifications.length) {
        header += '<form method="POST" action="{{ route('notifikasi.baca-semua') }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" style="font-size:11px;color:var(--gold-bright);background:none;border:none;cursor:pointer;">Tandai dibaca</button></form>';
      }
      header += '</div>';

      var body = '';
      if (notifications.length) {
        body = notifications.map(function (n) {
          return '<div class="profile-dropdown-item" style="align-items:flex-start;white-space:normal;cursor:default;">' +
            '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--gold-bright)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:2px;"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>' +
            '<div><div style="font-size:12.5px;line-height:1.5;color:var(--text);">' + escapeHtml(n.message) + '</div>' +
            '<div style="font-size:11px;color:var(--text-dim);margin-top:2px;">' + escapeHtml(n.time || '') + '</div></div></div>';
        }).join('');
      } else {
        body = '<div style="text-align:center;padding:20px 6px 8px;"><svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 14px;display:block;"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg><p style="margin:0;font-size:12.5px;line-height:1.6;color:var(--text-muted);">Belum ada notifikasi saat ini.</p></div>';
      }

      dropdown.innerHTML = header + body;
      menu.appendChild(button);
      menu.appendChild(dropdown);

      var profileMenu = document.getElementById('profileMenu');
      if (profileMenu && profileMenu.parentNode === actions) {
        actions.insertBefore(menu, profileMenu);
      } else {
        actions.appendChild(menu);
      }
    }

    var button = document.getElementById('notifBtn');
    var dropdown = document.getElementById('notifDropdown');
    if (!button || !dropdown || button.dataset.bound === '1') return;
    button.dataset.bound = '1';

    function closeNotif() {
      dropdown.classList.remove('open');
      button.classList.remove('open');
      button.setAttribute('aria-expanded', 'false');
    }
    function openNotif() {
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

    button.addEventListener('click', function (e) {
      e.stopPropagation();
      dropdown.classList.contains('open') ? closeNotif() : openNotif();
    });
    document.addEventListener('click', function (e) {
      if (!menu.contains(e.target)) closeNotif();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeNotif();
    });
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (ch) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch];
    });
  }

  function initLogoutConfirm() {
    var forms = document.querySelectorAll('.logout-form');
    if (!forms.length) return;

    var overlay = document.getElementById('logoutConfirmOverlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'confirm-overlay';
      overlay.id = 'logoutConfirmOverlay';
      overlay.innerHTML = '<div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="globalLogoutConfirmTitle">' +
        '<div class="confirm-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg></div>' +
        '<h3 id="globalLogoutConfirmTitle">Keluar dari akun?</h3>' +
        '<p>Sesi kamu akan diakhiri dan kamu perlu login kembali untuk mengakses SIBERAD.</p>' +
        '<div class="confirm-actions"><button type="button" class="btn" id="globalLogoutCancel">Batal</button><button type="button" class="btn btn-ghost-red" id="globalLogoutConfirm">Ya, Keluar</button></div>' +
        '</div>';
      document.body.appendChild(overlay);
    }

    var cancel = document.getElementById('globalLogoutCancel');
    var confirmBtn = document.getElementById('globalLogoutConfirm');
    var pendingForm = null;

    function closeConfirm() {
      overlay.classList.remove('open');
      pendingForm = null;
    }
    function openConfirm(form) {
      pendingForm = form;
      overlay.classList.add('open');
    }

    forms.forEach(function (form) {
      if (form.dataset.globalLogoutBound === '1') return;
      form.dataset.globalLogoutBound = '1';
      form.addEventListener('submit', function (e) {
        // Hentikan listener lain (termasuk confirm() lama) agar semua dashboard
        // memakai popup konfirmasi yang sama.
        e.preventDefault();
        e.stopImmediatePropagation();
        openConfirm(form);
      });
    });

    cancel?.addEventListener('click', closeConfirm);
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeConfirm();
    });
    confirmBtn?.addEventListener('click', function () {
      if (pendingForm) pendingForm.submit();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && overlay.classList.contains('open')) closeConfirm();
    });
  }

  // laporan-pimpinan lama memiliki listener window.confirm yang dipasang
  // sebelum lapisan ini. Untuk pesan logout lama, biarkan listener tersebut
  // lolos dan kemudian lapisan global mengambil alih submit dengan popup.
  var originalConfirm = window.confirm;
  window.confirm = function (message) {
    if (message === 'Keluar dari akun SIBERAD?') return true;
    return originalConfirm.call(window, message);
  };

  initNotifications();
  initLogoutConfirm();
})();
</script>
