(function () {
  'use strict';

  function installStyles() {
    if (document.getElementById('role-access-layout-style')) return;
    var style = document.createElement('style');
    style.id = 'role-access-layout-style';
    style.textContent = [
      '[data-tab-panel="role-akses"] .role-access-table-wrap{width:100%;max-width:1280px;margin:0 auto;overflow:hidden;box-sizing:border-box;border:1px solid var(--border-soft);border-radius:14px;background:var(--panel);box-shadow:0 8px 28px rgba(15,23,42,.06)}',
      '[data-tab-panel="role-akses"] .role-access-table{width:100%;max-width:100%;min-width:0;table-layout:fixed;border-collapse:separate;border-spacing:0}',
      '[data-tab-panel="role-akses"] .role-access-table th{padding:13px 16px;text-align:left;background:var(--panel-alt);border-bottom:1px solid var(--border-soft);color:var(--text-muted);font-family:var(--mono);font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;box-sizing:border-box}',
      '[data-tab-panel="role-akses"] .role-access-table td{padding:16px;border-bottom:1px solid var(--border-soft);vertical-align:top;background:var(--panel);box-sizing:border-box;min-width:0}',
      '[data-tab-panel="role-akses"] .role-access-table tbody tr:last-child td{border-bottom:0}',
      '[data-tab-panel="role-akses"] .role-access-table tbody tr:hover td{background:var(--hover-tint)}',
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(1),[data-tab-panel="role-akses"] .role-access-table td:nth-child(1){width:23%;padding-left:22px;padding-right:14px}',
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(2),[data-tab-panel="role-akses"] .role-access-table td:nth-child(2){width:27%;padding-left:14px;padding-right:14px}',
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(3),[data-tab-panel="role-akses"] .role-access-table td:nth-child(3){width:50%;padding-left:14px;padding-right:22px}',
      '[data-tab-panel="role-akses"] .role-access-role{font-weight:700;color:var(--text);font-size:13px;line-height:1.45}',
      '[data-tab-panel="role-akses"] .role-access-code{display:inline-flex;margin-top:6px;padding:4px 8px;border:1px solid var(--border-soft);border-radius:7px;background:var(--panel-alt);color:var(--gold-bright);font-family:var(--mono);font-size:9px;letter-spacing:.07em}',
      '[data-tab-panel="role-akses"] .role-access-desc{margin:0;color:var(--text-muted);font-size:12px;line-height:1.6;overflow-wrap:anywhere}',
      '[data-tab-panel="role-akses"] .role-access-form{padding:0!important;width:auto!important;max-width:max-content;min-width:0}',
      '[data-tab-panel="role-akses"] .role-access-access-layout{display:flex;align-items:flex-start;justify-content:flex-start;gap:14px;min-width:0}',
      '[data-tab-panel="role-akses"] .role-access-permissions{display:flex;flex:0 1 auto;flex-direction:column;align-items:flex-start;gap:9px;margin:0;min-width:0;width:max-content;max-width:100%}',
      '[data-tab-panel="role-akses"] .role-access-permission{display:flex;align-items:flex-start;gap:7px;min-width:0;color:var(--text-muted);font-size:11.5px;line-height:1.4;cursor:pointer;white-space:normal;overflow-wrap:anywhere}',
      '[data-tab-panel="role-akses"] .role-access-permission input{margin:2px 0 0;flex:0 0 auto;accent-color:var(--gold-bright)}',
      '[data-tab-panel="role-akses"] .role-access-save{width:auto;max-width:220px;min-width:178px;flex:0 0 auto;box-sizing:border-box;text-align:center;justify-content:center;line-height:1.35;white-space:normal;word-break:normal;align-self:start}',
      '@media(max-width:1000px){[data-tab-panel="role-akses"] .role-access-table-wrap{max-width:100%}[data-tab-panel="role-akses"] .role-access-access-layout{gap:12px}[data-tab-panel="role-akses"] .role-access-save{min-width:170px;max-width:200px}}',
      '@media(max-width:760px){[data-tab-panel="role-akses"] .role-access-table th,[data-tab-panel="role-akses"] .role-access-table td{padding:11px 10px}[data-tab-panel="role-akses"] .role-access-table th:nth-child(1),[data-tab-panel="role-akses"] .role-access-table td:nth-child(1){width:28%;padding-left:12px;padding-right:9px}[data-tab-panel="role-akses"] .role-access-table th:nth-child(2),[data-tab-panel="role-akses"] .role-access-table td:nth-child(2){width:28%;padding-left:9px;padding-right:9px}[data-tab-panel="role-akses"] .role-access-table th:nth-child(3),[data-tab-panel="role-akses"] .role-access-table td:nth-child(3){width:44%;padding-left:9px;padding-right:12px}[data-tab-panel="role-akses"] .role-access-access-layout{flex-direction:column;gap:12px}[data-tab-panel="role-akses"] .role-access-form{width:100%!important;max-width:none}[data-tab-panel="role-akses"] .role-access-permissions{width:100%;max-width:none}[data-tab-panel="role-akses"] .role-access-save{width:100%;max-width:none;min-width:0}}'
    ].join('');
    document.head.appendChild(style);
  }

  function initRoleAccessTable() {
    var section = document.querySelector('[data-tab-panel="role-akses"]');
    if (!section || section.dataset.roleAccessTableReady === '1') return;

    var panels = Array.prototype.filter.call(section.children, function (el) {
      return el.classList && el.classList.contains('panel');
    });
    if (!panels.length) return;

    var rows = [];
    panels.forEach(function (panel) {
      var head = panel.querySelector('.panel-head');
      var form = panel.querySelector('form');
      if (!head || !form) return;

      var title = head.querySelector('h3');
      var description = head.querySelector('p');
      var badge = title && title.querySelector('.badge');
      var roleName = title ? Array.prototype.filter.call(title.childNodes, function (n) {
        return n.nodeType === 3;
      }).map(function (n) { return n.textContent; }).join(' ').trim() : '';
      var code = badge ? badge.textContent.trim() : '';

      var permissionWrap = form.querySelector('div[style*="flex-wrap"]');
      if (permissionWrap) {
        permissionWrap.classList.add('role-access-permissions');
        permissionWrap.removeAttribute('style');
      }
      form.classList.add('role-access-form');
      Array.prototype.forEach.call(form.querySelectorAll('label'), function (label) {
        label.classList.add('role-access-permission');
        label.removeAttribute('style');
      });
      var save = form.querySelector('button[type="submit"]');
      if (save) save.classList.add('role-access-save');

      rows.push({ panel: panel, form: form, roleName: roleName, code: code, description: description ? description.textContent.trim() : 'Tidak ada deskripsi.', save: save });
    });

    if (!rows.length) return;

    var wrap = document.createElement('div');
    wrap.className = 'role-access-table-wrap';
    var table = document.createElement('table');
    table.className = 'role-access-table';
    table.setAttribute('aria-label', 'Role dan hak akses');
    table.innerHTML = '<thead><tr><th>Role / Satuan</th><th>Deskripsi</th><th>Hak Akses Modul &amp; Aksi</th></tr></thead>';
    var tbody = document.createElement('tbody');

    rows.forEach(function (item, index) {
      var tr = document.createElement('tr');

      var roleCell = document.createElement('td');
      roleCell.innerHTML = '<div class="role-access-role"></div>' + (item.code ? '<span class="role-access-code"></span>' : '');
      roleCell.querySelector('.role-access-role').textContent = item.roleName;
      if (item.code) roleCell.querySelector('.role-access-code').textContent = item.code;

      var descCell = document.createElement('td');
      var desc = document.createElement('p');
      desc.className = 'role-access-desc';
      desc.textContent = item.description;
      descCell.appendChild(desc);

      var accessCell = document.createElement('td');
      var formId = 'role-access-form-' + index;
      item.form.id = formId;

      var accessLayout = document.createElement('div');
      accessLayout.className = 'role-access-access-layout';
      accessLayout.appendChild(item.form);
      if (item.save) {
        item.save.setAttribute('form', formId);
        accessLayout.appendChild(item.save);
      }
      accessCell.appendChild(accessLayout);

      tr.appendChild(roleCell);
      tr.appendChild(descCell);
      tr.appendChild(accessCell);
      tbody.appendChild(tr);
    });

    table.appendChild(tbody);
    wrap.appendChild(table);
    rows.forEach(function (item) { item.panel.remove(); });
    section.appendChild(wrap);
    section.dataset.roleAccessTableReady = '1';
    installStyles();
  }

  function initActiveSessionsRealtime() {
    var section = document.querySelector('[data-tab-panel="sesi-aktif"]');
    if (!section || section.dataset.sessionRealtimeReady === '1') return;

    var tableWrap = section.querySelector('.tbl-wrap');
    var table = tableWrap && tableWrap.querySelector('table');
    if (!table) return;

    section.dataset.sessionRealtimeReady = '1';

    function refreshSessions() {
      if (document.hidden) return;

      fetch(window.location.href, {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
          'Accept': 'text/html',
          'X-Requested-With': 'XMLHttpRequest'
        }
      }).then(function (response) {
        if (!response.ok) throw new Error('Gagal mengambil sesi aktif.');
        return response.text();
      }).then(function (html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var remoteTbody = doc.querySelector('[data-tab-panel="sesi-aktif"] .tbl-wrap table tbody');
        var currentTbody = table.querySelector('tbody');
        if (!remoteTbody || !currentTbody) return;

        var nextHtml = remoteTbody.innerHTML;
        if (nextHtml !== currentTbody.innerHTML) {
          currentTbody.innerHTML = nextHtml;
        }
      }).catch(function () {
        // Kegagalan satu polling tidak mengganggu halaman Admin.
      });
    }

    refreshSessions();
    window.setInterval(refreshSessions, 3000);
  }

  function run() {
    initRoleAccessTable();
    initActiveSessionsRealtime();
    document.addEventListener('click', function (e) {
      var link = e.target.closest && e.target.closest('[data-tab-link="role-akses"]');
      if (link) window.setTimeout(initRoleAccessTable, 0);
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run, { once: true });
  else run();
})();