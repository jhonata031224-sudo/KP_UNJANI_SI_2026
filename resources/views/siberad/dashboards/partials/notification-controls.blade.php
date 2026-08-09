<script>
(function () {
  'use strict';

  function initNotificationControls() {
    var menu = document.getElementById('notifMenu');
    var dropdown = document.getElementById('notifDropdown');
    if (!menu || !dropdown) return;

    var storageKey = 'siberad-dismissed-notifications-{{ auth()->id() }}';
    var dismissed = [];
    try {
      dismissed = JSON.parse(localStorage.getItem(storageKey) || '[]');
      if (!Array.isArray(dismissed)) dismissed = [];
    } catch (e) { dismissed = []; }

    var notifications = @json(auth()->user()?->unreadNotifications?->map(function ($n) {
      return ['id' => $n->id, 'message' => $n->data['pesan'] ?? 'Status laporan diperbarui.', 'time' => optional($n->created_at)->diffForHumans()];
    })->values() ?? []);

    var items = Array.prototype.slice.call(dropdown.querySelectorAll('.profile-dropdown-item'));
    var visibleItems = [];

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
      visibleItems.push(item);

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

    var header = dropdown.querySelector('.profile-dropdown-head');
    if (header && !header.querySelector('.siberad-notif-close')) {
      var closePanel = document.createElement('button');
      closePanel.type = 'button';
      closePanel.className = 'siberad-notif-close';
      closePanel.setAttribute('aria-label', 'Tutup notifikasi');
      closePanel.title = 'Tutup notifikasi';
      closePanel.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>';
      closePanel.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeNotificationPanel();
      });
      header.appendChild(closePanel);
    }

    var style = document.createElement('style');
    style.setAttribute('data-notification-controls', 'true');
    style.textContent = `
      #notifDropdown .profile-dropdown-head{position:relative;padding-right:70px!important;}
      .siberad-notif-remove,.siberad-notif-close{display:flex;align-items:center;justify-content:center;border:0;background:transparent;color:var(--text-dim);cursor:pointer;border-radius:6px;padding:4px;line-height:0;transition:.15s ease;}
      .siberad-notif-remove{position:absolute;right:10px;top:50%;transform:translateY(-50%);width:26px;height:26px;}
      .siberad-notif-close{position:absolute;right:10px;top:50%;transform:translateY(-50%);width:28px;height:28px;}
      .siberad-notif-remove svg,.siberad-notif-close svg{width:16px;height:16px;}
      .siberad-notif-remove:hover,.siberad-notif-close:hover{background:var(--hover-tint);color:var(--red);}
      .siberad-notif-item{transition:opacity .15s ease,transform .15s ease;}
      #notifDropdown .siberad-notif-empty-runtime{text-align:center;padding:22px 10px;color:var(--text-dim);font-size:12px;}
    `;
    document.head.appendChild(style);

    function closeNotificationPanel() {
      dropdown.classList.remove('open');
      var button = document.getElementById('notifBtn');
      if (button) {
        button.classList.remove('open');
        button.setAttribute('aria-expanded', 'false');
      }
    }

    function refreshNotificationState() {
      var remaining = dropdown.querySelectorAll('.siberad-notif-item');
      var dot = document.querySelector('#notifBtn .siberad-notif-dot');
      if (!remaining.length) {
        if (dot) dot.remove();
        var oldEmpty = dropdown.querySelector('.siberad-notif-empty-runtime');
        if (!oldEmpty) {
          var empty = document.createElement('div');
          empty.className = 'siberad-notif-empty-runtime';
          empty.textContent = 'Belum ada notifikasi saat ini.';
          dropdown.appendChild(empty);
        }
      }
    }

    refreshNotificationState();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotificationControls);
  } else {
    initNotificationControls();
  }
})();
</script>
