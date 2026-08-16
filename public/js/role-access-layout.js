(function () {
  'use strict';

  var ALLOWED = {
    ADMIN: ['laporan', 'medsos', 'personel', 'monitoring', 'notifikasi'],
    DANPUS: ['laporan', 'medsos', 'monitoring', 'notifikasi'],
    WADAN: ['laporan', 'monitoring', 'notifikasi'],
    SDIR: ['laporan', 'monitoring', 'notifikasi'],
    SATLAKKAL: ['laporan', 'monitoring', 'notifikasi'],
    SATLAKSISOS: ['laporan', 'medsos', 'notifikasi'],
    SATLAKDAK: ['laporan', 'monitoring', 'notifikasi'],
    SATLAKDUKTEK: ['laporan', 'monitoring', 'notifikasi'],
    BINFUNG: ['laporan', 'personel', 'notifikasi'],
    BINUM: ['laporan', 'monitoring', 'notifikasi'],
    DIKLAT: ['laporan', 'notifikasi'],
    BINMAT: ['laporan', 'notifikasi']
  };

  var MODULE_LABEL_TO_KEY = {
    'Kirim & Kelola Laporan': 'laporan',
    'Pelaporan Publikasi': 'medsos',
    'Pelaporan Administrasi Personel': 'personel',
    'Monitoring Laporan & Aktivitas': 'monitoring',
    'Notifikasi': 'notifikasi'
  };

  function normalizeText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
  }

  function allowedModules(roleCode) {
    var code = normalizeText(roleCode).toUpperCase();
    return ALLOWED[code] || Object.keys(MODULE_LABEL_TO_KEY).map(function (label) {
      return MODULE_LABEL_TO_KEY[label];
    });
  }

  function installStyles() {
    if (document.getElementById('role-access-layout-style-v6')) return;
    var style = document.createElement('style');
    style.id = 'role-access-layout-style-v6';
    style.textContent = [
      '[data-tab-panel="role-akses"] .role-access-table-wrap{width:100%;max-width:none;margin:0;overflow:hidden;box-sizing:border-box;border:1px solid var(--border-soft);border-radius:14px;background:var(--panel);box-shadow:0 8px 28px rgba(15,23,42,.06)}',
      '[data-tab-panel="role-akses"] .role-access-table{width:100%;max-width:100%;min-width:0;table-layout:fixed;border-collapse:separate;border-spacing:0}',
      '[data-tab-panel="role-akses"] .role-access-table th{width:25%!important;padding:13px 20px;background:var(--panel-alt);border-bottom:1px solid var(--border-soft);color:var(--text-muted);font-family:var(--mono);font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;text-align:left;box-sizing:border-box}',
      '[data-tab-panel="role-akses"] .role-access-table td{width:25%!important;padding:16px 20px;vertical-align:top;border-bottom:1px solid var(--border-soft);background:var(--panel);box-sizing:border-box;min-width:0}',
      '[data-tab-panel="role-akses"] .role-access-table th:first-child,[data-tab-panel="role-akses"] .role-access-table td:first-child{padding-left:42px}',
      '[data-tab-panel="role-akses"] .role-access-table th:last-child,[data-tab-panel="role-akses"] .role-access-table td:last-child{padding-right:42px}',
      '[data-tab-panel="role-akses"] .role-access-table tbody tr:last-child td{border-bottom:0}',
      '[data-tab-panel="role-akses"] .role-access-table tbody tr:hover td{background:var(--hover-tint)}',
      '[data-tab-panel="role-akses"] .role-access-role{font-weight:700;color:var(--text);font-size:13px;line-height:1.45}',
      '[data-tab-panel="role-akses"] .role-access-code{display:inline-flex;margin-top:6px;padding:4px 8px;border:1px solid var(--border-soft);border-radius:7px;background:var(--panel-alt);color:var(--gold-bright);font-family:var(--mono);font-size:9px;letter-spacing:.07em}',
      '[data-tab-panel="role-akses"] .role-access-desc{margin:0;color:var(--text-muted);font-size:12px;line-height:1.6;overflow-wrap:anywhere}',
      '[data-tab-panel="role-akses"] .role-access-access-layout{display:contents}',
      '[data-tab-panel="role-akses"] .role-access-form{display:contents!important}',
      '[data-tab-panel="role-akses"] .role-access-permissions{display:flex;flex-direction:column;align-items:flex-start;gap:9px;margin:0;min-width:0;width:max-content;max-width:100%}',
      '[data-tab-panel="role-akses"] .role-access-permission{display:flex;align-items:flex-start;gap:7px;min-width:0;color:var(--text-muted);font-size:11.5px;line-height:1.4;cursor:pointer;white-space:normal;overflow-wrap:anywhere}',
      '[data-tab-panel="role-akses"] .role-access-permission input{margin:2px 0 0;flex:0 0 auto;accent-color:var(--gold-bright)}',
      '[data-tab-panel="role-akses"] .role-access-permission[hidden]{display:none!important}',
      '[data-tab-panel="role-akses"] .role-access-save{display:inline-flex;align-items:center;justify-content:center;width:100%;max-width:none;min-width:0;box-sizing:border-box;text-align:center;line-height:1.35;white-space:normal;word-break:normal;margin:0}',
      '@media(max-width:700px){[data-tab-panel="role-akses"] .role-access-table{table-layout:auto}[data-tab-panel="role-akses"] .role-access-table thead{display:none}[data-tab-panel="role-akses"] .role-access-table,[data-tab-panel="role-akses"] .role-access-table tbody,[data-tab-panel="role-akses"] .role-access-table tr,[data-tab-panel="role-akses"] .role-access-table td{display:block;width:100%!important}[data-tab-panel="role-akses"] .role-access-table tbody tr{padding:14px 0;border-bottom:1px solid var(--border-soft)}[data-tab-panel="role-akses"] .role-access-table tbody tr:last-child{border-bottom:0}[data-tab-panel="role-akses"] .role-access-table td{border:0!important;padding:7px 16px;background:transparent!important}[data-tab-panel="role-akses"] .role-access-table td:first-child{padding-left:20px}[data-tab-panel="role-akses"] .role-access-table td:last-child{padding-right:20px}[data-tab-panel="role-akses"] .role-access-save{width:100%;min-width:0}}'
    ].join('');
    document.head.appendChild(style);
  }

  function getRoleCode(row) {
    var code = row.querySelector('.role-access-code');
    if (code) return normalizeText(code.textContent).toUpperCase();
    var badge = row.cells && row.cells[0] ? row.cells[0].querySelector('.badge') : null;
    return badge ? normalizeText(badge.textContent).toUpperCase() : '';
  }

  function applyRolePermissions(form, roleCode) {
    if (!form) return;
    var allowed = allowedModules(roleCode);
    form.querySelectorAll('label').forEach(function (label) {
      var key = MODULE_LABEL_TO_KEY[normalizeText(label.textContent)];
      if (!key) return;
      var input = label.querySelector('input[name="permissions[]"]');
      var visible = allowed.indexOf(key) !== -1;
      label.classList.add('role-access-permission');
      label.hidden = !visible;
      if (input) input.disabled = !visible;
    });
  }

  function normalizeForm(form, saveButton, formId) {
    if (!form) return;
    form.classList.add('role-access-form');
    if (formId) form.id = formId;
    var permissions = form.querySelector('div[style*="flex-wrap"], .role-access-permissions');
    if (permissions) {
      permissions.classList.add('role-access-permissions');
      permissions.removeAttribute('style');
    }
    form.querySelectorAll('label').forEach(function (label) {
      label.classList.add('role-access-permission');
      label.removeAttribute('style');
    });
    var button = saveButton || form.querySelector('button[type="submit"]');
    if (button) {
      button.classList.add('role-access-save');
      if (formId) button.setAttribute('form', formId);
    }
  }

  function buildFreshTable(section) {
    var panels = Array.prototype.filter.call(section.children, function (el) {
      return el.classList && el.classList.contains('panel') && !!el.querySelector('form');
    });
    if (!panels.length) return false;
    var rows = [];
    panels.forEach(function (panel, index) {
      var head = panel.querySelector('.panel-head');
      var form = panel.querySelector('form');
      if (!head || !form) return;
      var title = head.querySelector('h3');
      var description = head.querySelector('p');
      var badge = title ? title.querySelector('.badge') : null;
      var roleName = '';
      if (title) Array.prototype.forEach.call(title.childNodes, function (node) { if (node.nodeType === 3) roleName += ' ' + node.textContent; });
      roleName = roleName.trim();
      var code = badge ? normalizeText(badge.textContent).toUpperCase() : '';
      normalizeForm(form, null, 'role-access-form-' + index);
      applyRolePermissions(form, code);
      rows.push({panel:panel,form:form,save:form.querySelector('button[type="submit"]'),roleName:roleName||'Role',code:code,description:description?normalizeText(description.textContent):'Tidak ada deskripsi.'});
    });
    if (!rows.length) return false;

    var wrap = document.createElement('div');
    wrap.className = 'role-access-table-wrap';
    var table = document.createElement('table');
    table.className = 'role-access-table';
    table.setAttribute('aria-label','Role dan hak akses');
    table.innerHTML = '<thead><tr><th>Role / Satuan</th><th>Deskripsi</th><th>Hak Akses Modul</th><th>Aksi</th></tr></thead>';
    var tbody = document.createElement('tbody');

    rows.forEach(function (item) {
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
      var permissionCell = document.createElement('td');
      permissionCell.appendChild(item.form);
      var actionCell = document.createElement('td');
      if (item.save) actionCell.appendChild(item.save);
      tr.appendChild(roleCell);
      tr.appendChild(descCell);
      tr.appendChild(permissionCell);
      tr.appendChild(actionCell);
      tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    wrap.appendChild(table);
    rows.forEach(function (item) { item.panel.remove(); });
    section.appendChild(wrap);
    return true;
  }

  function normalizeExistingTable(section) {
    var table = section.querySelector('.role-access-table, .role-akses-table');
    if (!table) return false;
    table.classList.add('role-access-table');
    var headers = Array.prototype.map.call(table.querySelectorAll('thead th'), function (th) { return normalizeText(th.textContent).toLowerCase(); });
    if (headers.length === 3 && headers[2].indexOf('hak akses') !== -1) {
      table.querySelectorAll('tbody tr').forEach(function (tr) {
        var access = tr.children[2];
        if (!access) return;
        var form = access.querySelector('form');
        var button = access.querySelector('button');
        if (!form || !button) return;
        var permissionCell = document.createElement('td');
        permissionCell.appendChild(form);
        var actionCell = document.createElement('td');
        actionCell.appendChild(button);
        tr.appendChild(actionCell);
        tr.insertBefore(permissionCell, actionCell);
        access.remove();
      });
      var head = table.tHead && table.tHead.rows[0];
      if (head) {
        head.children[2].textContent = 'Hak Akses Modul';
        var actionHead = document.createElement('th');
        actionHead.textContent = 'Aksi';
        head.appendChild(actionHead);
      }
    }

    var currentHeaders = Array.prototype.map.call(table.querySelectorAll('thead th'), function (th) { return normalizeText(th.textContent).toLowerCase(); });
    if (currentHeaders.length >= 4) {
      var roleIdx = currentHeaders.findIndex(function (h) { return h.indexOf('role') !== -1 || h.indexOf('satuan') !== -1; });
      var permIdx = currentHeaders.findIndex(function (h) { return h.indexOf('hak akses') !== -1 || h.indexOf('akses modul') !== -1; });
      var actionIdx = currentHeaders.findIndex(function (h) { return h.indexOf('aksi') !== -1; });
      table.querySelectorAll('tbody tr').forEach(function (row) {
        var code = getRoleCode(row);
        var form = row.querySelector('form');
        var button = row.querySelector('button[type="submit"]');
        normalizeForm(form, button);
        applyRolePermissions(form, code);
      });
      if (roleIdx >= 0 && permIdx >= 0 && actionIdx >= 0) {
        table.tHead.rows[0].children[roleIdx].textContent = 'Role / Satuan';
        table.tHead.rows[0].children[permIdx].textContent = 'Hak Akses Modul';
        table.tHead.rows[0].children[actionIdx].textContent = 'Aksi';
      }
    }
    return true;
  }

  function initRoleAccessTable() {
    var section = document.querySelector('[data-tab-panel="role-akses"]');
    if (!section) return;
    installStyles();
    if (section.dataset.roleAccessTableReady === '1') return;
    if (section.querySelector('.role-access-table, .role-akses-table')) normalizeExistingTable(section);
    else buildFreshTable(section);
    section.dataset.roleAccessTableReady = '1';
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
      fetch(window.location.href,{method:'GET',credentials:'same-origin',cache:'no-store',headers:{'Accept':'text/html','X-Requested-With':'XMLHttpRequest'}})
        .then(function(response){if(!response.ok)throw new Error('Gagal mengambil sesi aktif.');return response.text();})
        .then(function(html){
          var parser = new DOMParser();
          var doc = parser.parseFromString(html,'text/html');
          var remoteTbody = doc.querySelector('[data-tab-panel="sesi-aktif"] .tbl-wrap table tbody');
          var currentTbody = table.querySelector('tbody');
          if(remoteTbody&&currentTbody&&remoteTbody.innerHTML!==currentTbody.innerHTML) currentTbody.innerHTML=remoteTbody.innerHTML;
        }).catch(function(){});
    }
    refreshSessions();
    window.setInterval(refreshSessions,3000);
  }

  function run() {
    initRoleAccessTable();
    initActiveSessionsRealtime();
    document.addEventListener('click',function(e){
      var link=e.target.closest&&e.target.closest('[data-tab-link="role-akses"]');
      if(link) window.setTimeout(initRoleAccessTable,0);
    });
  }

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',run,{once:true});
  else run();
})();