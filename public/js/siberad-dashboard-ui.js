(function () {
  'use strict';

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>\'\"]/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[ch];
    });
  }

  function addStyles() {
    if (document.getElementById('siberad-global-ui-style')) return;

    var style = document.createElement('style');
    style.id = 'siberad-global-ui-style';
    style.textContent = `
      .siberad-notif-dot{position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:var(--red);box-shadow:0 0 0 2px var(--panel,#0c2417);}
      .siberad-notif-head{border-bottom:1px solid var(--border-soft);display:flex;justify-content:space-between;align-items:center;gap:8px;}
      .siberad-mark-read{font-size:11px;color:var(--gold-bright);background:none;border:none;cursor:pointer;padding:0;font:inherit;}
      .siberad-notif-item svg{color:var(--gold-bright);flex-shrink:0;margin-top:2px;}
      .siberad-notif-message{font-size:12.5px;line-height:1.5;color:var(--text);}
      .siberad-notif-time{font-size:11px;color:var(--text-dim);margin-top:2px;}
      .siberad-notif-empty{text-align:center;padding:20px 6px 8px;color:var(--text-dim);}
      .siberad-notif-empty svg{margin:0 auto 14px;display:block;}
      .siberad-notif-empty p{margin:0;font-size:12.5px;line-height:1.6;color:var(--text-muted);}

      .side-dropdown .side-sublink{display:flex!important;align-items:center!important;gap:0!important;min-height:32px!important;height:32px!important;padding:7px 12px 7px 32px!important;margin:0!important;font-size:12.5px!important;font-weight:500!important;line-height:1.2!important;text-transform:none!important;}
      .side-dropdown .side-sublink .dot{display:none!important;}
      .side-dropdown .side-dropdown-menu{gap:1px!important;padding:0!important;margin:0!important;}
      .side-dropdown.open .side-dropdown-menu{margin-top:3px!important;}
      .side-dropdown .side-sublink:hover{color:var(--text)!important;background:var(--hover-tint)!important;}
      .side-dropdown .side-sublink.active{color:var(--gold-bright)!important;background:var(--gold-dim)!important;border-color:var(--border)!important;font-weight:600!important;}
      .side-dropdown-toggle{font-size:13.5px!important;font-weight:500!important;}
      .side-dropdown.open .side-dropdown-toggle{color:var(--text)!important;}

      .siberad-logout-overlay{position:fixed;inset:0;z-index:10000;display:none;align-items:center;justify-content:center;padding:24px;background:rgba(18,20,15,.42);backdrop-filter:blur(8px);}
      .siberad-logout-overlay.open{display:flex;}
      .siberad-logout-card{width:min(420px,calc(100vw - 32px));background:var(--panel);border:1px solid var(--border-soft);border-radius:16px;padding:30px 30px 28px;box-shadow:0 24px 70px rgba(0,0,0,.28);text-align:center;}
      .siberad-logout-icon{width:58px;height:58px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;background:var(--red-dim);color:var(--red);}
      .siberad-logout-icon svg{width:28px;height:28px;}
      .siberad-logout-card h3{font-family:var(--display);font-size:21px;font-weight:700;color:var(--text);margin:0 0 10px;}
      .siberad-logout-card p{font-size:13px;line-height:1.65;color:var(--text-muted);margin:0 0 24px;}
      .siberad-logout-actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
      .siberad-logout-actions button{height:42px;border-radius:8px;padding:0 14px;font-family:var(--mono);font-size:11px;font-weight:700;letter-spacing:.06em;cursor:pointer;}
      .siberad-logout-cancel{background:var(--panel-alt);border:1px solid var(--border);color:var(--text);}
      .siberad-logout-confirm{background:transparent;border:1px solid var(--red);color:var(--red);}
      @media(max-width:520px){.siberad-logout-actions{grid-template-columns:1fr;}.siberad-logout-card{padding:25px 20px 22px;}}
    `;
    document.head.appendChild(style);
  }

  /*
   * Role & Hak Akses is intentionally normalized here because the original
   * Blade markup is a set of forms/panels while the desired UI is a single
   * four-column table. Doing the normalization in one place prevents several
   * older CSS patches from fighting each other.
   */
  function installRoleAccessStyles() {
    if (document.getElementById('siberad-role-access-final-style')) return;
    var style = document.createElement('style');
    style.id = 'siberad-role-access-final-style';
    style.textContent = `
      [data-tab-panel="role-akses"] .role-access-table-wrap,
      [data-tab-panel="role-akses"] .role-akses-table-wrap{
        width:100%!important;max-width:100%!important;overflow:hidden!important;
        margin:0!important;box-sizing:border-box!important;
      }
      [data-tab-panel="role-akses"] .role-access-table,
      [data-tab-panel="role-akses"] .role-akses-table{
        width:100%!important;max-width:100%!important;min-width:0!important;
        table-layout:fixed!important;border-collapse:collapse!important;
        border-spacing:0!important;box-sizing:border-box!important;
      }
      [data-tab-panel="role-akses"] .role-access-table thead th,
      [data-tab-panel="role-akses"] .role-akses-table thead th{
        height:42px!important;padding:0 16px!important;box-sizing:border-box!important;
        background:var(--panel-alt)!important;border-bottom:1px solid var(--border-soft)!important;
        color:var(--text-muted)!important;font-family:var(--mono)!important;
        font-size:10px!important;font-weight:700!important;letter-spacing:.08em!important;
        line-height:1!important;text-transform:uppercase!important;text-align:left!important;
        vertical-align:middle!important;
      }
      [data-tab-panel="role-akses"] .role-access-table thead th:nth-child(1),
      [data-tab-panel="role-akses"] .role-akses-table thead th:nth-child(1){width:22%!important}
      [data-tab-panel="role-akses"] .role-access-table thead th:nth-child(2),
      [data-tab-panel="role-akses"] .role-akses-table thead th:nth-child(2){width:25%!important}
      [data-tab-panel="role-akses"] .role-access-table thead th:nth-child(3),
      [data-tab-panel="role-akses"] .role-akses-table thead th:nth-child(3){width:38%!important}
      [data-tab-panel="role-akses"] .role-access-table thead th:nth-child(4),
      [data-tab-panel="role-akses"] .role-akses-table thead th:nth-child(4){width:15%!important;text-align:center!important;padding-left:10px!important;padding-right:10px!important}
      [data-tab-panel="role-akses"] .role-access-table tbody td,
      [data-tab-panel="role-akses"] .role-akses-table tbody td{
        padding:14px 16px!important;box-sizing:border-box!important;min-width:0!important;
        vertical-align:top!important;border-bottom:1px solid var(--border-soft)!important;
        background:var(--panel)!important;
      }
      [data-tab-panel="role-akses"] .role-access-table tbody tr:last-child td,
      [data-tab-panel="role-akses"] .role-akses-table tbody tr:last-child td{border-bottom:0!important}
      [data-tab-panel="role-akses"] .role-access-table tbody tr:hover td,
      [data-tab-panel="role-akses"] .role-akses-table tbody tr:hover td{background:var(--hover-tint)!important}
      [data-tab-panel="role-akses"] .role-access-table tbody td:nth-child(4),
      [data-tab-panel="role-akses"] .role-akses-table tbody td:nth-child(4){
        text-align:center!important;vertical-align:middle!important;padding-left:10px!important;padding-right:10px!important;
      }
      [data-tab-panel="role-akses"] .role-access-role,
      [data-tab-panel="role-akses"] .role-akses-role{font-size:13px!important;font-weight:700!important;line-height:1.45!important;color:var(--text)!important;margin:0!important}
      [data-tab-panel="role-akses"] .role-access-code,
      [data-tab-panel="role-akses"] .role-akses-code{display:inline-flex!important;align-items:center!important;margin-top:6px!important;padding:4px 8px!important;border:1px solid var(--border-soft)!important;border-radius:7px!important;background:var(--panel-alt)!important;color:var(--gold-bright)!important;font-family:var(--mono)!important;font-size:9px!important;letter-spacing:.07em!important;line-height:1!important}
      [data-tab-panel="role-akses"] .role-access-desc,
      [data-tab-panel="role-akses"] .role-akses-desc{margin:0!important;color:var(--text-muted)!important;font-size:12px!important;line-height:1.55!important;overflow-wrap:anywhere!important}
      [data-tab-panel="role-akses"] .role-access-form,
      [data-tab-panel="role-akses"] .role-akses-form{display:contents!important;padding:0!important;margin:0!important}
      [data-tab-panel="role-akses"] .role-access-permissions,
      [data-tab-panel="role-akses"] .role-akses-checks{
        display:flex!important;flex-direction:column!important;align-items:flex-start!important;
        justify-content:flex-start!important;gap:8px!important;width:100%!important;max-width:100%!important;
        margin:0!important;padding:0!important;min-width:0!important;
      }
      [data-tab-panel="role-akses"] .role-access-permission,
      [data-tab-panel="role-akses"] .role-akses-check{
        display:flex!important;align-items:center!important;gap:8px!important;width:100%!important;
        max-width:100%!important;min-width:0!important;margin:0!important;padding:0!important;
        color:var(--text-muted)!important;font-size:12px!important;line-height:18px!important;
        white-space:normal!important;overflow-wrap:anywhere!important;cursor:pointer!important;
      }
      [data-tab-panel="role-akses"] .role-access-permission input,
      [data-tab-panel="role-akses"] .role-akses-check input{margin:0!important;flex:0 0 auto!important;width:15px!important;height:15px!important;accent-color:var(--gold-bright)!important}
      [data-tab-panel="role-akses"] .role-access-permission[hidden],
      [data-tab-panel="role-akses"] .role-akses-check[hidden]{display:none!important}
      [data-tab-panel="role-akses"] .role-access-save,
      [data-tab-panel="role-akses"] .role-akses-action button,
      [data-tab-panel="role-akses"] .role-akses-action .btn,
      [data-tab-panel="role-akses"] .role-access-table tbody td:nth-child(4) button,
      [data-tab-panel="role-akses"] .role-akses-table tbody td:nth-child(4) button{
        display:inline-flex!important;align-items:center!important;justify-content:center!important;
        width:76px!important;min-width:76px!important;max-width:76px!important;height:34px!important;
        min-height:34px!important;margin:0 auto!important;padding:0 10px!important;box-sizing:border-box!important;
        border-radius:8px!important;white-space:nowrap!important;overflow:hidden!important;
        font-family:var(--mono)!important;font-size:10px!important;font-weight:700!important;letter-spacing:.045em!important;
        line-height:1!important;text-align:center!important;text-transform:none!important;
      }
      [data-tab-panel="role-akses"] .role-access-save{font-size:0!important}
      [data-tab-panel="role-akses"] .role-access-save::after{content:'Simpan';font-size:10px!important}
      [data-tab-panel="role-akses"] .role-access-table tbody td:nth-child(4) button,
      [data-tab-panel="role-akses"] .role-akses-table tbody td:nth-child(4) button{font-size:0!important}
      [data-tab-panel="role-akses"] .role-access-table tbody td:nth-child(4) button::after,
      [data-tab-panel="role-akses"] .role-akses-table tbody td:nth-child(4) button::after{content:'Simpan';font-size:10px!important}
      [data-tab-panel="role-akses"] .role-access-action-head,
      [data-tab-panel="role-akses"] .role-akses-action-head,
      [data-tab-panel="role-akses"] .role-access-table thead th:last-child,
      [data-tab-panel="role-akses"] .role-akses-table thead th:last-child{text-align:center!important}
      @media(max-width:760px){
        [data-tab-panel="role-akses"] .role-access-table thead,
        [data-tab-panel="role-akses"] .role-akses-table thead{display:none!important}
        [data-tab-panel="role-akses"] .role-access-table,
        [data-tab-panel="role-akses"] .role-access-table tbody,
        [data-tab-panel="role-akses"] .role-access-table tr,
        [data-tab-panel="role-akses"] .role-access-table td,
        [data-tab-panel="role-akses"] .role-akses-table,
        [data-tab-panel="role-akses"] .role-akses-table tbody,
        [data-tab-panel="role-akses"] .role-akses-table tr,
        [data-tab-panel="role-akses"] .role-akses-table td{display:block!important;width:100%!important}
        [data-tab-panel="role-akses"] .role-access-table tbody tr,
        [data-tab-panel="role-akses"] .role-akses-table tbody tr{padding:8px 0!important;border-bottom:1px solid var(--border-soft)!important}
        [data-tab-panel="role-akses"] .role-access-table tbody td,
        [data-tab-panel="role-akses"] .role-akses-table tbody td{padding:7px 16px!important;border:0!important;background:transparent!important}
        [data-tab-panel="role-akses"] .role-access-table tbody td:nth-child(4),
        [data-tab-panel="role-akses"] .role-akses-table tbody td:nth-child(4){text-align:left!important}
        [data-tab-panel="role-akses"] .role-access-save,
        [data-tab-panel="role-akses"] .role-akses-action button,
        [data-tab-panel="role-akses"] .role-akses-action .btn,
        [data-tab-panel="role-akses"] .role-access-table tbody td:nth-child(4) button,
        [data-tab-panel="role-akses"] .role-akses-table tbody td:nth-child(4) button{margin-left:0!important;margin-right:0!important}
      }
    `;
    document.head.appendChild(style);
  }

  function roleText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
  }

  function normalizeRoleTable(section) {
    if (!section) return null;
    var table = section.querySelector('.role-access-table, .role-akses-table');
    if (!table) return null;
    table.classList.add('role-access-table');
    var headers = table.querySelectorAll('thead th');
    if (headers.length >= 4) {
      headers[0].textContent = 'Role / Satuan';
      headers[1].textContent = 'Deskripsi';
      headers[2].textContent = 'Hak Akses Modul';
      headers[3].textContent = 'Aksi';
    }
    table.querySelectorAll('tbody tr').forEach(function (row) {
      var button = row.querySelector('td:last-child button[type="submit"], td:last-child .btn');
      if (button) {
        button.classList.add('role-access-save');
        button.textContent = 'Simpan';
        button.setAttribute('aria-label', 'Simpan hak akses');
        button.title = 'Simpan hak akses';
      }
      row.querySelectorAll('label').forEach(function (label) {
        label.classList.add('role-access-permission');
        var input = label.querySelector('input');
        if (input) input.removeAttribute('style');
        label.removeAttribute('style');
      });
      row.querySelectorAll('form').forEach(function (form) {
        form.classList.add('role-access-form');
        form.removeAttribute('style');
      });
    });
    return table;
  }

  function buildRoleTableFromPanels(section) {
    if (!section || section.querySelector('.role-access-table, .role-akses-table')) return null;
    var panels = Array.prototype.filter.call(section.children, function (el) {
      return el.classList && el.classList.contains('panel') && !!el.querySelector('form');
    });
    if (!panels.length) return null;

    var wrap = document.createElement('div');
    wrap.className = 'role-access-table-wrap';
    var table = document.createElement('table');
    table.className = 'role-access-table';
    table.setAttribute('aria-label', 'Role dan hak akses');
    table.innerHTML = '<thead><tr><th>Role / Satuan</th><th>Deskripsi</th><th>Hak Akses Modul</th><th>Aksi</th></tr></thead><tbody></tbody>';
    var tbody = table.querySelector('tbody');

    panels.forEach(function (panel) {
      var head = panel.querySelector('.panel-head');
      var form = panel.querySelector('form');
      if (!head || !form) return;
      var title = head.querySelector('h3');
      var description = head.querySelector('p');
      var badge = title ? title.querySelector('.badge') : null;
      var roleName = '';
      if (title) Array.prototype.forEach.call(title.childNodes, function (node) { if (node.nodeType === 3) roleName += ' ' + node.textContent; });
      roleName = roleText(roleName) || 'Role';
      var code = badge ? roleText(badge.textContent).toUpperCase() : '';

      var row = document.createElement('tr');
      var roleCell = document.createElement('td');
      var role = document.createElement('div');
      role.className = 'role-access-role';
      role.textContent = roleName;
      roleCell.appendChild(role);
      if (code) {
        var codeBadge = document.createElement('span');
        codeBadge.className = 'role-access-code';
        codeBadge.textContent = code;
        roleCell.appendChild(codeBadge);
      }

      var descCell = document.createElement('td');
      var desc = document.createElement('p');
      desc.className = 'role-access-desc';
      desc.textContent = description ? roleText(description.textContent) : 'Tidak ada deskripsi.';
      descCell.appendChild(desc);

      var accessCell = document.createElement('td');
      var form = form;
      form.classList.add('role-access-form');
      form.removeAttribute('style');
      var permissionWrap = form.querySelector('div:first-of-type');
      if (permissionWrap) {
        permissionWrap.classList.add('role-access-permissions');
        permissionWrap.removeAttribute('style');
      }
      form.querySelectorAll('label').forEach(function (label) {
        label.classList.add('role-access-permission');
        label.removeAttribute('style');
      });
      accessCell.appendChild(form);

      var actionCell = document.createElement('td');
      var button = form.querySelector('button[type="submit"]');
      if (button) {
        button.classList.add('role-access-save');
        button.textContent = 'Simpan';
        button.setAttribute('aria-label', 'Simpan hak akses');
        button.title = 'Simpan hak akses';
        actionCell.appendChild(button);
      }

      row.appendChild(roleCell);
      row.appendChild(descCell);
      row.appendChild(accessCell);
      row.appendChild(actionCell);
      tbody.appendChild(row);
    });

    if (!tbody.children.length) return null;
    wrap.appendChild(table);
    panels.forEach(function (panel) { panel.remove(); });
    section.appendChild(wrap);
    return table;
  }

  function initRoleAccess() {
    var section = document.querySelector('[data-tab-panel="role-akses"]');
    if (!section) return;
    installRoleAccessStyles();
    var table = normalizeRoleTable(section);
    if (!table) table = buildRoleTableFromPanels(section);
    if (table) normalizeRoleTable(section);
  }

  function initRoleAccessDeferred() {
    initRoleAccess();
    window.setTimeout(initRoleAccess, 50);
    window.setTimeout(initRoleAccess, 250);
    document.querySelectorAll('[data-tab-link="role-akses"]').forEach(function (link) {
      if (link.dataset.roleAccessFixBound === '1') return;
      link.dataset.roleAccessFixBound = '1';
      link.addEventListener('click', function () {
        window.setTimeout(initRoleAccess, 20);
        window.setTimeout(initRoleAccess, 200);
      });
    });
  }

  function initNotifications() {
    var actions = document.querySelector('.topbar-actions');
    if (!actions || document.getElementById('notifMenu')) return;

    var profileMenu = actions.querySelector('#profileMenu');
    if (!profileMenu) return;

    var notifications = Array.isArray(window.__SIBERAD_NOTIFICATIONS__) ? window.__SIBERAD_NOTIFICATIONS__ : [];
    var wrapper = document.createElement('div');
    wrapper.className = 'profile-menu';
    wrapper.id = 'notifMenu';
    wrapper.innerHTML = `
      <button type="button" class="btn-icon-toggle" id="notifBtn" aria-label="Notifikasi" aria-haspopup="menu" aria-expanded="false" style="position:relative;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
          <path d="M13.73 21a2 2 0 0 0-3.46 0"></path>
        </svg>
        ${notifications.length ? '<span class="siberad-notif-dot"></span>' : ''}
      </button>
      <div class="profile-dropdown" id="notifDropdown" role="menu" aria-label="Notifikasi">
        <div class="profile-dropdown-head siberad-notif-head">
          <div class="profile-dropdown-name" style="font-size:14px;">Notifikasi</div>
          ${notifications.length ? '<form method="POST" action="/notifikasi/baca-semua" class="siberad-mark-read-form"><input type="hidden" name="_token" value="' + escapeHtml(window.__SIBERAD_CSRF__) + '"><button type="submit" class="siberad-mark-read">Tandai dibaca</button></form>' : ''}
        </div>
        <div class="siberad-notif-list">
          ${notifications.length ? notifications.map(function (n) {
            return '<div class="profile-dropdown-item siberad-notif-item" style="align-items:flex-start;white-space:normal;cursor:default;">' +
              '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>' +
              '<div><div class="siberad-notif-message">' + escapeHtml(n.message || 'Laporan baru masuk.') + '</div><div class="siberad-notif-time">' + escapeHtml(n.time || '') + '</div></div>' +
              '</div>';
          }).join('') : '<div class="siberad-notif-empty"><svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg><p>Belum ada notifikasi saat ini.</p></div>'}
        </div>
      </div>`;

    actions.insertBefore(wrapper, profileMenu);

    var btn = wrapper.querySelector('#notifBtn');
    var dropdown = wrapper.querySelector('#notifDropdown');
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      document.querySelectorAll('.profile-dropdown.open').forEach(function (el) {
        if (el !== dropdown) el.classList.remove('open');
      });
      var open = dropdown.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!wrapper.contains(e.target)) {
        dropdown.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function initLogoutConfirmation() {
    var forms = document.querySelectorAll('form.logout-form, form[action$="/logout"]');
    if (!forms.length) return;

    var modal = document.getElementById('siberadLogoutModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'siberadLogoutModal';
      modal.className = 'siberad-logout-overlay';
      modal.innerHTML = `
        <div class="siberad-logout-card" role="dialog" aria-modal="true" aria-labelledby="siberadLogoutTitle">
          <div class="siberad-logout-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg></div>
          <h3 id="siberadLogoutTitle">Keluar dari akun?</h3>
          <p>Sesi kamu akan diakhiri dan kamu perlu login kembali untuk mengakses SIBERAD.</p>
          <div class="siberad-logout-actions"><button type="button" class="siberad-logout-cancel">BATAL</button><button type="button" class="siberad-logout-confirm">YA, KELUAR</button></div>
        </div>`;
      document.body.appendChild(modal);
    }

    var pendingForm = null;
    function close() {
      modal.classList.remove('open');
      pendingForm = null;
      document.body.style.overflow = '';
    }
    function open(form) {
      pendingForm = form;
      modal.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    modal.querySelector('.siberad-logout-cancel').onclick = close;
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('open')) close(); });
    modal.querySelector('.siberad-logout-confirm').onclick = function () {
      if (!pendingForm) return close();
      var form = pendingForm;
      pendingForm = null;
      form.dataset.skipLogoutConfirm = '1';
      form.submit();
    };

    forms.forEach(function (form) {
      if (form.dataset.globalLogoutBound === '1') return;
      form.dataset.globalLogoutBound = '1';
      form.addEventListener('submit', function (e) {
        if (form.dataset.skipLogoutConfirm === '1') return;
        e.preventDefault();
        open(form);
      });
    });
  }

  function boot() {
    addStyles();
    initRoleAccessDeferred();
    initNotifications();
    initLogoutConfirmation();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
