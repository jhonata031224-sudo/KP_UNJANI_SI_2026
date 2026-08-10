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
    // Pakai flag yang sama dengan partials/notification-controls.blade.php:
    // kalau tombol notifikasi itu sudah dibuat & di-bind di sana (yang jalan
    // lebih dulu lewat pengumuman-banner.blade.php), jangan bind listener klik
    // lagi di sini. Dua listener pada tombol yang sama akan saling
    // membatalkan (buka lalu langsung tertutup lagi di klik yang sama).
    if (!button || !dropdown || button.dataset.notifBound === '1') return;
    button.dataset.notifBound = '1';

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

  var originalConfirm = window.confirm;
  window.confirm = function (message) {
    if (message === 'Keluar dari akun SIBERAD?') return true;
    return originalConfirm.call(window, message);
  };

  initNotifications();
  initLogoutConfirm();

  // =========================================================
  // GLOBAL LAPORAN: aksi hanya dari detail + form catatan penolakan
  // =========================================================
  (function initReportActions() {
    var isDanpus = !!document.querySelector('.pimp-hero h1') &&
      /Komandan Pusat/i.test(document.querySelector('.pimp-hero h1').textContent || '');

    // Base/warna tombol #detailActions & #reportDetailModal .modal-actions sudah
    // didefinisikan sekali di partials/profile-enhancements.blade.php (selalu ikut
    // ter-include lewat pengumuman-banner di setiap view yang memuat file ini).
    // Jangan duplikasi rule itu di sini lagi.
    function injectStyles() {
      if (document.getElementById('globalReportActionStyles')) return;
      var style = document.createElement('style');
      style.id = 'globalReportActionStyles';
      style.textContent = `
        .report-reject-overlay { position:fixed; inset:0; z-index:1200; display:none; align-items:center; justify-content:center; padding:20px; background:rgba(0,0,0,.55); }
        .report-reject-overlay.open { display:flex; }
        .report-reject-card { width:min(560px,100%); background:var(--panel,#fff); color:var(--text,#17212b); border:1px solid var(--border-soft,#ddd); border-radius:14px; padding:22px; box-shadow:0 25px 70px rgba(0,0,0,.25); }
        .report-reject-card h3 { margin:0 0 6px; font-family:var(--display); font-size:19px; }
        .report-reject-card p { margin:0 0 16px; font-size:12px; color:var(--text-muted); line-height:1.6; }
        .report-reject-card label { display:block; font-size:11px; font-weight:800; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:7px; }
        .report-reject-card textarea { width:100%; min-height:130px; box-sizing:border-box; resize:vertical; padding:10px 11px; border:1px solid var(--border); border-radius:8px; background:var(--panel-alt); color:var(--text); font:inherit; font-size:13px; }
        .report-reject-card .reject-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:16px; }
        .report-reject-card .reject-actions button { border:1px solid var(--border); border-radius:8px; padding:8px 13px; background:var(--panel-alt); color:var(--text); cursor:pointer; }
        .report-reject-card .reject-actions .confirm-reject { border-color:var(--red); color:var(--red); }
        .report-detail-note { margin-right:auto; font-size:11px; color:var(--text-muted); }
        .report-rejection-note { border-color:rgba(198,40,40,.3)!important; background:rgba(181,52,47,.08)!important; }
      `;
      document.head.appendChild(style);
    }

    function ensureRejectModal() {
      var existing = document.getElementById('reportRejectOverlay');
      if (existing) return existing;
      var overlay = document.createElement('div');
      overlay.className = 'report-reject-overlay';
      overlay.id = 'reportRejectOverlay';
      overlay.innerHTML = '<div class="report-reject-card" role="dialog" aria-modal="true" aria-labelledby="reportRejectTitle">' +
        '<h3 id="reportRejectTitle">Catatan Penolakan</h3>' +
        '<p>Berikan catatan atau keterangan alasan laporan ditolak. Catatan ini wajib diisi dan akan tersimpan bersama status laporan.</p>' +
        '<form id="reportRejectForm" method="POST">' +
        '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
        '<input type="hidden" name="_method" value="PATCH">' +
        '<input type="hidden" name="status" value="Ditolak">' +
        '<label for="reportRejectNote">Catatan / Keterangan <span style="color:var(--red)">*</span></label>' +
        '<textarea id="reportRejectNote" name="catatan" required maxlength="5000" placeholder="Tuliskan alasan atau keterangan penolakan..."></textarea>' +
        '<div class="reject-actions"><button type="button" id="cancelReportReject">Batal</button><button type="submit" class="confirm-reject">Tolak Laporan</button></div>' +
        '</form></div>';
      document.body.appendChild(overlay);
      overlay.addEventListener('click', function(e){ if(e.target === overlay) closeRejectModal(); });
      document.getElementById('cancelReportReject')?.addEventListener('click', closeRejectModal);
      document.addEventListener('keydown', function(e){ if(e.key === 'Escape' && overlay.classList.contains('open')) closeRejectModal(); });
      return overlay;
    }

    function closeRejectModal() {
      var overlay = document.getElementById('reportRejectOverlay');
      if (!overlay) return;
      overlay.classList.remove('open');
      var form = document.getElementById('reportRejectForm');
      if (form) form.reset();
    }

    function openRejectModal(sourceForm) {
      var overlay = ensureRejectModal();
      var form = document.getElementById('reportRejectForm');
      if (!form) return;
      form.action = sourceForm.getAttribute('action') || '';
      var sourceStatus = sourceForm.querySelector('input[name="status"]');
      var targetStatus = form.querySelector('input[name="status"]');
      if (targetStatus) targetStatus.value = sourceStatus?.value || 'Ditolak';
      var note = document.getElementById('reportRejectNote');
      if (note) note.value = '';
      overlay.classList.add('open');
      setTimeout(function(){ note?.focus(); }, 50);
    }

    function getActionsContainer() {
      var actions = document.getElementById('detailActions');
      if (actions) return actions;
      var modal = document.getElementById('reportDetailModal');
      if (!modal) return null;
      actions = modal.querySelector('.modal-actions');
      if (!actions) {
        actions = document.createElement('div');
        actions.className = 'modal-actions';
        modal.querySelector('.report-modal-card')?.appendChild(actions);
      }
      return actions;
    }

    function populateDetailActions(detailButton) {
      var actions = getActionsContainer();
      if (!actions) return;
      var container = detailButton.closest('.review-actions, .action-row');
      var forms = container ? Array.prototype.slice.call(container.querySelectorAll('form')) : [];
      actions.innerHTML = '';

      if (!forms.length || detailButton.dataset.readonly === '1') {
        var readonly = document.createElement('span');
        readonly.className = 'report-detail-note';
        readonly.textContent = detailButton.dataset.readonly === '1'
          ? 'Mode pemantauan — detail ini hanya untuk melihat aktivitas laporan.'
          : 'Tidak ada tindakan yang tersedia untuk laporan ini.';
        actions.appendChild(readonly);
        return;
      }

      var note = document.createElement('span');
      note.className = 'report-detail-note';
      note.textContent = 'Tindak lanjuti laporan dari detail ini.';
      actions.appendChild(note);

      forms.forEach(function(originalForm){
        var statusInput = originalForm.querySelector('input[name="status"]');
        var status = statusInput ? String(statusInput.value || '').toLowerCase() : '';
        var isReject = status.indexOf('tolak') !== -1;
        var isRevise = status.indexOf('revisi') !== -1;
        if (isDanpus && isRevise) return;

        if (isReject) {
          var rejectButton = document.createElement('button');
          rejectButton.type = 'button';
          rejectButton.className = 'reject';
          rejectButton.textContent = 'Tolak';
          rejectButton.addEventListener('click', function(){ openRejectModal(originalForm); });
          actions.appendChild(rejectButton);
          return;
        }

        var clone = originalForm.cloneNode(true);
        clone.style.display = 'inline-flex';
        var button = clone.querySelector('button[type="submit"]');
        if (button) {
          button.classList.remove('approve','revise','reject');
          if (status.indexOf('diterima') !== -1 || status.indexOf('setujui') !== -1 || status.indexOf('setuj') !== -1) button.classList.add('approve');
          else if (isRevise) button.classList.add('revise');
        }
        actions.appendChild(clone);
      });
    }

    function renameStatusMenu() {
      document.querySelectorAll('.side-sub-link').forEach(function(link){
        if (link.textContent.trim() === 'Status Laporan') link.textContent = 'Riwayat Laporan';
      });
      document.querySelectorAll('h2').forEach(function(h){
        if (h.textContent.trim() === 'Status Laporan') h.textContent = 'Riwayat Laporan';
      });
    }

    injectStyles();
    renameStatusMenu();

    document.querySelectorAll('.review-actions form, .action-row form').forEach(function(form){
      form.style.display = 'none';
    });

    document.addEventListener('click', function(e){
      var detailButton = e.target.closest('.review-actions .detail-btn, .action-row .detail-btn');
      if (!detailButton) return;
      setTimeout(function(){ populateDetailActions(detailButton); }, 0);
    });
  })();
})();
</script>
