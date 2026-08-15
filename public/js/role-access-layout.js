(function () {
  'use strict';

  function installStyles() {
    if (document.getElementById('role-access-layout-style')) return;
    var style = document.createElement('style');
    style.id = 'role-access-layout-style';
    style.textContent = [
      '[data-tab-panel="role-akses"] .role-access-table-wrap{width:100%;max-width:100%;overflow-x:hidden;box-sizing:border-box;border:1px solid var(--border-soft);border-radius:14px;background:var(--panel);box-shadow:0 8px 28px rgba(15,23,42,.06)}',
      '[data-tab-panel="role-akses"] .role-access-table{width:100%;max-width:100%;min-width:0;table-layout:fixed;border-collapse:separate;border-spacing:0}',
      '[data-tab-panel="role-akses"] .role-access-table th{padding:13px 18px;text-align:left;background:var(--panel-alt);border-bottom:1px solid var(--border-soft);color:var(--text-muted);font-family:var(--mono);font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;box-sizing:border-box}',
      '[data-tab-panel="role-akses"] .role-access-table td{padding:14px 18px;border-bottom:1px solid var(--border-soft);vertical-align:top;background:var(--panel);box-sizing:border-box;min-width:0}',
      '[data-tab-panel="role-akses"] .role-access-table tbody tr:last-child td{border-bottom:0}',
      '[data-tab-panel="role-akses"] .role-access-table tbody tr:hover td{background:var(--hover-tint)}',
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(1),[data-tab-panel="role-akses"] .role-access-table td:nth-child(1){width:20%;padding-left:22px;padding-right:10px}',
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(2),[data-tab-panel="role-akses"] .role-access-table td:nth-child(2){width:24%;padding-left:10px;padding-right:10px}',
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(3),[data-tab-panel="role-akses"] .role-access-table td:nth-child(3){width:33%;padding-left:10px;padding-right:10px}',
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(4),[data-tab-panel="role-akses"] .role-access-table td:nth-child(4){width:23%;padding-left:10px;padding-right:18px}',
      '[data-tab-panel="role-akses"] .role-access-role{font-weight:700;color:var(--text);font-size:13px;line-height:1.4}',
      '[data-tab-panel="role-akses"] .role-access-code{display:inline-flex;margin-top:6px;padding:4px 8px;border:1px solid var(--border-soft);border-radius:7px;background:var(--panel-alt);color:var(--gold-bright);font-family:var(--mono);font-size:9px;letter-spacing:.07em}',
      '[data-tab-panel="role-akses"] .role-access-desc{margin:0;color:var(--text-muted);font-size:12px;line-height:1.55;overflow-wrap:anywhere}',
      '[data-tab-panel="role-akses"] .role-access-form{padding:0!important}',
      '[data-tab-panel="role-akses"] .role-access-permissions{display:flex;flex-direction:column;align-items:flex-start;gap:9px;margin:0;width:100%}',
      '[data-tab-panel="role-akses"] .role-access-permission{display:flex;align-items:flex-start;width:100%;max-width:100%;box-sizing:border-box;gap:7px;color:var(--text-muted);font-size:11.5px;line-height:1.4;cursor:pointer;white-space:normal;overflow-wrap:anywhere}',
      '[data-tab-panel="role-akses"] .role-access-permission input{margin:2px 0 0;flex:0 0 auto;accent-color:var(--gold-bright)}',
      '[data-tab-panel="role-akses"] .role-access-save{white-space:normal;width:100%;max-width:100%;box-sizing:border-box;text-align:center;justify-content:center;line-height:1.4;word-break:break-word}',
      '[data-tab-panel="role-akses"] .role-access-action{text-align:left!important;vertical-align:middle!important;overflow-wrap:anywhere}',
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(4){text-align:left}',
      '@media(max-width:760px){[data-tab-panel="role-akses"] .role-access-table th,[data-tab-panel="role-akses"] .role-access-table td{padding:9px 10px}[data-tab-panel="role-akses"] .role-access-table th:nth-child(1),[data-tab-panel="role-akses"] .role-access-table td:nth-child(1){padding-left:12px}[data-tab-panel="role-akses"] .role-access-table th:nth-child(4),[data-tab-panel="role-akses"] .role-access-table td:nth-child(4){padding-left:7px}}'
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
    table.innerHTML = '<thead><tr><th>Role / Satuan</th><th>Deskripsi</th><th>Hak Akses Modul</th><th>Aksi</th></tr></thead>';
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
      accessCell.appendChild(item.form);

      var actionCell = document.createElement('td');
      actionCell.className = 'role-access-action';
      if (item.save) {
        item.save.setAttribute('form', formId);
        actionCell.appendChild(item.save);
      }

      tr.appendChild(roleCell);
      tr.appendChild(descCell);
      tr.appendChild(accessCell);
      tr.appendChild(actionCell);
      tbody.appendChild(tr);
    });

    table.appendChild(tbody);
    wrap.appendChild(table);
    rows.forEach(function (item) { item.panel.remove(); });
    section.appendChild(wrap);
    section.dataset.roleAccessTableReady = '1';
    installStyles();
  }

  function run() {
    initRoleAccessTable();
    document.addEventListener('click', function (e) {
      var link = e.target.closest && e.target.closest('[data-tab-link="role-akses"]');
      if (link) window.setTimeout(initRoleAccessTable, 0);
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run, { once: true });
  else run();
})();