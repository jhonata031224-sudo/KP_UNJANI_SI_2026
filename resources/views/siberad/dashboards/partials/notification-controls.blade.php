<script>
(function () {
  'use strict';

  function initNotificationControls() {
    var menu = document.getElementById('notifMenu');
    var dropdown = document.getElementById('notifDropdown');
    var notifBtn = document.getElementById('notifBtn');

    /*
     * Sebagian dashboard (termasuk Satlak KAL dan Satlak Siber Sosial)
     * hanya memiliki tombol tema + profil. Buat komponen notifikasi yang
     * sama secara konsisten di sini supaya seluruh dashboard memakai UI,
     * perilaku, dan empty state yang sama.
     */
    if (!menu || !dropdown || !notifBtn) {
      var actions = document.querySelector('.topbar-actions');
      if (actions && !document.getElementById('notifMenu')) {
        menu = document.createElement('div');
        menu.className = 'profile-menu';
        menu.id = 'notifMenu';

        notifBtn = document.createElement('button');
        notifBtn.type = 'button';
        notifBtn.className = 'btn-icon-toggle';
        notifBtn.id = 'notifBtn';
        notifBtn.setAttribute('aria-label', 'Notifikasi');
        notifBtn.setAttribute('aria-haspopup', 'menu');
        notifBtn.setAttribute('aria-expanded', 'false');
        notifBtn.style.position = 'relative';
        notifBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="stroke:var(--gold-bright) !important;color:var(--gold-bright) !important;"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" style="fill:var(--gold-dim) !important;stroke:var(--gold-bright) !important;"></path><path d="M13.73 21a2 2 0 0 1-3.46 0" style="fill:none !important;stroke:var(--gold-bright) !important;"></path></svg><span class="siberad-notif-dot" style="position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:var(--red);box-shadow:0 0 0 2px var(--panel,#0c2417);"></span>';

        dropdown = document.createElement('div');
        dropdown.className = 'profile-dropdown';
        dropdown.id = 'notifDropdown';
        dropdown.setAttribute('role', 'menu');
        dropdown.setAttribute('aria-label', 'Notifikasi');
        dropdown.innerHTML = '<div class="profile-dropdown-head" style="border-bottom:1px solid var(--border-soft);"><div class="profile-dropdown-name" style="font-size:14px;">Notifikasi</div></div>';

        menu.appendChild(notifBtn);
        menu.appendChild(dropdown);

        var profileMenu = document.getElementById('profileMenu');
        if (profileMenu && profileMenu.parentNode === actions) {
          actions.insertBefore(menu, profileMenu);
        } else {
          actions.appendChild(menu);
        }
      }
    }

    menu = document.getElementById('notifMenu');
    dropdown = document.getElementById('notifDropdown');
    notifBtn = document.getElementById('notifBtn');
    if (!menu || !dropdown || !notifBtn) return;

    var storageKey = 'siberad-dismissed-notifications-{{ auth()->id() }}';
    var dismissed = [];
    try {
      dismissed = JSON.parse(localStorage.getItem(storageKey) || '[]');
      if (!Array.isArray(dismissed)) dismissed = [];
    } catch (e) { dismissed = []; }

    var notifications = @json(auth()->user()?->unreadNotifications?->map(function ($n) {
      return ['id' => $n->id, 'message' => $n->data['pesan'] ?? 'Status laporan diperbarui.', 'time' => optional($n->created_at)->diffForHumans()];
    })->values() ?? []);

    /* Jika komponen baru dibuat oleh script, isi item notifikasi di sini. */
    if (!dropdown.querySelector('.profile-dropdown-item') && notifications.length) {
      notifications.forEach(function (notification) {
        var item = document.createElement('div');
        item.className = 'profile-dropdown-item';
        item.setAttribute('role', 'menuitem');
        item.innerHTML = '<div style="display:flex;flex-direction:column;gap:3px;min-width:0;"><span style="font-size:12px;line-height:1.45;">' + escapeHtml(notification.message) + '</span><small style="font-size:10px;color:var(--text-dim);">' + escapeHtml(notification.time || '') + '</small></div>';
        dropdown.appendChild(item);
      });
    }

    var items = Array.prototype.slice.call(dropdown.querySelectorAll('.profile-dropdown-item'));

    function escapeHtml(value) {
      var div = document.createElement('div');
      div.textContent = value == null ? '' : String(value);
      return div.innerHTML;
    }

    var header = dropdown.querySelector('.profile-dropdown-head');
    if (header) {
      var markReadForm = header.querySelector('form');
      if (markReadForm) markReadForm.remove();
    }

    items.forEach(function (item, index) {
      var notification = notifications[index];
      if (!notification) return;
      if (dismissed.indexOf(String(notification.id)) !== -1) {
        item.remove();
        return;
      }

      item.classList.add('siberad-notif-item');
      item.style.position = 'relative';
      item.style.paddingRight = '38px';

      var closeBtn = document.createElement('button');
      closeBtn.type = 'button';
      closeBtn.className = 'siberad-notif-remove';
      closeBtn.setAttribute('aria-label', 'Hapus notifikasi');
      closeBtn.title = 'Hapus notifikasi';
      closeBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>';
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        dismissed.push(String(notification.id));
        dismissed = dismissed.filter(function (id, i, arr) { return arr.indexOf(id) === i; });
        try { localStorage.setItem(storageKey, JSON.stringify(dismissed)); } catch (err) {}
        item.remove();
        refreshNotificationState();
      });
      item.appendChild(closeBtn);
    });

    if (header && !header.querySelector('.siberad-notif-close')) {
      var closePanel = document.createElement('button');
      closePanel.type = 'button';
      closePanel.className = 'siberad-notif-close';
      closePanel.setAttribute('aria-label', 'Tutup notifikasi');
      closePanel.title = 'Tutup notifikasi';
      closePanel.textContent = 'Tutup';
      closePanel.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeNotificationPanel();
      });
      header.appendChild(closePanel);
    }

    var style = document.querySelector('style[data-notification-controls]');
    if (!style) {
      style = document.createElement('style');
      style.setAttribute('data-notification-controls', 'true');
      style.textContent = `
        #notifDropdown .profile-dropdown-head{position:relative;padding-right:58px!important;}
        .siberad-notif-remove,.siberad-notif-close{display:flex;align-items:center;justify-content:center;border:0;background:transparent;color:var(--text-dim);cursor:pointer;border-radius:6px;line-height:1;transition:.15s ease;}
        .siberad-notif-remove{position:absolute;right:10px;top:50%;transform:translateY(-50%);width:26px;height:26px;padding:4px;}
        .siberad-notif-close{position:absolute;right:10px;top:50%;transform:translateY(-50%);padding:4px 2px;font-size:11px;font-weight:600;}
        .siberad-notif-remove svg{width:16px;height:16px;}
        .siberad-notif-remove:hover,.siberad-notif-close:hover{background:var(--hover-tint);color:var(--red);}
        .siberad-notif-item{transition:opacity .15s ease,transform .15s ease;}
        #notifDropdown .siberad-notif-empty-runtime{text-align:center;padding:22px 10px;color:var(--text-dim);font-size:12px;}
        #notifDropdown .siberad-notif-empty-runtime p{margin:0;font-size:12.5px;line-height:1.6;color:var(--text-muted);}
      `;
      document.head.appendChild(style);
    }

    function closeNotificationPanel() {
      dropdown.classList.remove('open');
      notifBtn.classList.remove('open');
      notifBtn.setAttribute('aria-expanded', 'false');
    }

    function getNotificationDot() {
      var dot = notifBtn.querySelector('.siberad-notif-dot');
      if (dot) return dot;
      var spans = Array.prototype.slice.call(notifBtn.querySelectorAll('span'));
      return spans.find(function (span) {
        var style = span.getAttribute('style') || '';
        return style.indexOf('background:var(--red)') !== -1 || style.indexOf('background: var(--red)') !== -1;
      }) || null;
    }

    function syncNotificationDot() {
      var dot = getNotificationDot();
      if (!dot) return;
      var remaining = dropdown.querySelectorAll('.siberad-notif-item').length;
      dot.style.display = remaining > 0 ? 'block' : 'none';
    }

    function refreshNotificationState() {
      var remaining = dropdown.querySelectorAll('.siberad-notif-item');
      syncNotificationDot();
      var oldEmpty = dropdown.querySelector('.siberad-notif-empty-runtime');
      if (!remaining.length) {
        if (!oldEmpty) {
          var empty = document.createElement('div');
          empty.className = 'siberad-notif-empty-runtime';
          empty.innerHTML = '<p>Belum ada notifikasi saat ini.</p>';
          dropdown.appendChild(empty);
        }
      } else if (oldEmpty) {
        oldEmpty.remove();
      }
    }

    /* Pastikan tombol notifikasi dapat dibuka pada semua dashboard. */
    if (!notifBtn.dataset.notifBound) {
      notifBtn.dataset.notifBound = '1';
      notifBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var isOpen = dropdown.classList.toggle('open');
        notifBtn.classList.toggle('open', isOpen);
        notifBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
      document.addEventListener('click', function (e) {
        if (!menu.contains(e.target)) closeNotificationPanel();
      });
    }

    /* Empty state sengaja TANPA ikon lonceng di dalam popup. */
    refreshNotificationState();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotificationControls);
  } else {
    initNotificationControls();
  }
})();
</script>
