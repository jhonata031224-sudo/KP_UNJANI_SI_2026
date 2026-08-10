<script>
(function () {
  'use strict';

  function initNotificationControls() {
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
      button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg><span class="siberad-notif-dot"></span>';
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
        .siberad-notif-dot{position:absolute;top:6px;right:6px;width:7px;height:7px;border-radius:50%;background:var(--red);box-shadow:0 0 0 2px var(--panel,#0c2417);pointer-events:none;}
        #notifDropdown{position:absolute!important;z-index:100004!important;min-width:280px;right:0;top:calc(100% + 8px);pointer-events:auto!important;}
        #notifDropdown .notif-head{display:flex;align-items:center;min-height:22px;}
        #notifDropdown .siberad-notif-list{display:flex;flex-direction:column;}
        #notifDropdown .siberad-notif-item{position:relative;padding:11px 38px 11px 12px;border-bottom:1px solid var(--border-soft);}
        #notifDropdown .siberad-notif-item:last-child{border-bottom:0;}
        #notifDropdown .siberad-notif-item p{margin:0;font-size:12px;line-height:1.45;color:var(--text);}
        #notifDropdown .siberad-notif-item small{display:block;margin-top:3px;font-size:10px;color:var(--text-dim);}
        #notifDropdown .siberad-notif-remove{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:24px;height:24px;border:0;border-radius:5px;background:transparent;color:var(--text-dim);cursor:pointer;padding:4px;}
        #notifDropdown .siberad-notif-empty-runtime{padding:18px 12px;text-align:center;color:var(--text-muted);font-size:12px;line-height:1.5;}
      `;
      document.head.appendChild(style);
    }

    var notifications = @json(auth()->user()?->unreadNotifications?->map(function ($n) {
      return ['id' => $n->id, 'message' => $n->data['pesan'] ?? 'Status laporan diperbarui.', 'time' => optional($n->created_at)->diffForHumans()];
    })->values() ?? []);

    var storageKey = 'siberad-dismissed-notifications-{{ auth()->id() }}';
    var dismissed = [];
    try { dismissed = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch (e) {}
    if (!Array.isArray(dismissed)) dismissed = [];

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
            event.preventDefault(); event.stopPropagation();
            dismissed.push(String(notification.id));
            dismissed = dismissed.filter(function (id, index, arr) { return arr.indexOf(id) === index; });
            try { localStorage.setItem(storageKey, JSON.stringify(dismissed)); } catch (e) {}
            render();
          });
          item.appendChild(remove); list.appendChild(item);
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
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initNotificationControls);
  else initNotificationControls();
})();
</script>
