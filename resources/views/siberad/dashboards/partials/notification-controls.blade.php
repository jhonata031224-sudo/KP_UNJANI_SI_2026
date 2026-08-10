<script>
(function () {
  'use strict';

  function initNotificationControls() {
    var actions = document.querySelector('.topbar-actions');
    if (!actions) return;

    var menu = document.getElementById('notifMenu');
    var button = document.getElementById('notifBtn');
    var dropdown = document.getElementById('notifDropdown');

    /* Pastikan struktur notifikasi selalu lengkap. */
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
      button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg><span class="siberad-notif-dot"></span>';

      dropdown = document.createElement('div');
      dropdown.className = 'profile-dropdown';
      dropdown.id = 'notifDropdown';
      dropdown.setAttribute('role', 'menu');
      dropdown.innerHTML = '<div class="profile-dropdown-head notif-head"><div class="profile-dropdown-name">Notifikasi</div></div><div class="siberad-notif-list"></div>';

      menu.appendChild(button);
      menu.appendChild(dropdown);

      var profileMenu = document.getElementById('profileMenu');
      if (profileMenu && profileMenu.parentNode === actions) actions.insertBefore(menu, profileMenu);
      else actions.appendChild(menu);
    } else {
      /* Jika markup lama hanya memiliki salah satu elemen, lengkapi tanpa membuat menu kedua. */
      button = button || menu.querySelector('#notifBtn');
      dropdown = dropdown || menu.querySelector('#notifDropdown');

      if (!button) {
        button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn-icon-toggle';
        button.id = 'notifBtn';
        button.setAttribute('aria-label', 'Notifikasi');
        button.setAttribute('aria-haspopup', 'menu');
        button.setAttribute('aria-expanded', 'false');
        button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg><span class="siberad-notif-dot"></span>';
        menu.insertBefore(button, menu.firstChild);
      }

      if (!dropdown) {
        dropdown = document.createElement('div');
        dropdown.className = 'profile-dropdown';
        dropdown.id = 'notifDropdown';
        dropdown.setAttribute('role', 'menu');
        dropdown.innerHTML = '<div class="profile-dropdown-head notif-head"><div class="profile-dropdown-name">Notifikasi</div></div><div class="siberad-notif-list"></div>';
        menu.appendChild(dropdown);
      }
    }

    if (!button || !dropdown) return;

    var style = document.getElementById('siberad-notification-style');
    if (!style) {
      style = document.createElement('style');
      style.id = 'siberad-notification-style';
      style.textContent = `
        .notif-menu{position:relative;}
        .notif-menu>#notifBtn{position:relative;z-index:2;}
        .notif-menu>#notifBtn svg{width:19px;height:19px;display:block;}
        .siberad-notif-dot{position:absolute;top:6px;right:6px;width:7px;height:7px;border-radius:50%;background:var(--red);box-shadow:0 0 0 2px var(--panel,#0c2417);}
        #notifDropdown{z-index:1200;}
        #notifDropdown .notif-head{display:flex;align-items:center;justify-content:space-between;min-height:22px;}
        #notifDropdown .siberad-notif-list{display:flex;flex-direction:column;}
        #notifDropdown .siberad-notif-item{position:relative;padding:11px 38px 11px 12px;border-bottom:1px solid var(--border-soft);cursor:default;}
        #notifDropdown .siberad-notif-item:last-child{border-bottom:0;}
        #notifDropdown .siberad-notif-item p{margin:0;font-size:12px;line-height:1.45;color:var(--text);}
        #notifDropdown .siberad-notif-item small{display:block;margin-top:3px;font-size:10px;color:var(--text-dim);}
        #notifDropdown .siberad-notif-remove{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:24px;height:24px;border:0;border-radius:5px;background:transparent;color:var(--text-dim);cursor:pointer;padding:4px;}
        #notifDropdown .siberad-notif-remove:hover{background:var(--hover-tint);color:var(--red);}
        #notifDropdown .siberad-notif-remove svg{width:15px;height:15px;display:block;}
        #notifDropdown .siberad-notif-empty-runtime{padding:18px 12px;text-align:center;color:var(--text-muted);font-size:12px;line-height:1.5;}
      `;
      document.head.appendChild(style);
    }

    var notifications = @json(auth()->user()?->unreadNotifications?->map(function ($n) {
      return ['id' => $n->id, 'message' => $n->data['pesan'] ?? 'Status laporan diperbarui.', 'time' => optional($n->created_at)->diffForHumans()];
    })->values() ?? []);

    var storageKey = 'siberad-dismissed-notifications-{{ auth()->id() }}';
    var dismissed = [];
    try { dismissed = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch (e) { dismissed = []; }
    if (!Array.isArray(dismissed)) dismissed = [];

    var list = dropdown.querySelector('.siberad-notif-list');
    if (!list) {
      list = document.createElement('div');
      list.className = 'siberad-notif-list';
      dropdown.appendChild(list);
    }

    function escapeHtml(value) {
      var div = document.createElement('div');
      div.textContent = value == null ? '' : String(value);
      return div.innerHTML;
    }

    function render() {
      list.innerHTML = '';
      var visible = notifications.filter(function (n) { return dismissed.indexOf(String(n.id)) === -1; });

      if (!visible.length) {
        var empty = document.createElement('div');
        empty.className = 'siberad-notif-empty-runtime';
        empty.textContent = 'Belum ada notifikasi saat ini.';
        list.appendChild(empty);
      } else {
        visible.forEach(function (notification) {
          var item = document.createElement('div');
          item.className = 'siberad-notif-item';
          item.innerHTML = '<p>' + escapeHtml(notification.message) + '</p><small>' + escapeHtml(notification.time || '') + '</small>';

          var remove = document.createElement('button');
          remove.type = 'button';
          remove.className = 'siberad-notif-remove';
          remove.setAttribute('aria-label', 'Hapus notifikasi');
          remove.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>';
          remove.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            dismissed.push(String(notification.id));
            dismissed = dismissed.filter(function (id, index, arr) { return arr.indexOf(id) === index; });
            try { localStorage.setItem(storageKey, JSON.stringify(dismissed)); } catch (e) {}
            render();
          });

          item.appendChild(remove);
          list.appendChild(item);
        });
      }

      var dot = button.querySelector('.siberad-notif-dot');
      if (dot) dot.style.display = visible.length ? 'block' : 'none';
    }

    function close() {
      dropdown.classList.remove('open');
      button.classList.remove('open');
      button.setAttribute('aria-expanded', 'false');
    }

    if (!button.dataset.notifBound) {
      button.dataset.notifBound = '1';
      button.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var open = dropdown.classList.contains('open');
        if (open) close();
        else {
          dropdown.classList.add('open');
          button.classList.add('open');
          button.setAttribute('aria-expanded', 'true');
        }
      });

      document.addEventListener('click', function (event) {
        if (!menu.contains(event.target)) close();
      });
    }

    render();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initNotificationControls);
  else initNotificationControls();
})();
</script>
