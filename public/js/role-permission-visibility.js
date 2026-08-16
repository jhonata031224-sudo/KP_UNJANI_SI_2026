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

  function installLayoutFix() {
    if (document.getElementById('role-access-final-layout-fix')) return;
    var style = document.createElement('style');
    style.id = 'role-access-final-layout-fix';
    style.textContent = [
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(1),[data-tab-panel="role-akses"] .role-access-table td:nth-child(1){width:24%!important}',
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(2),[data-tab-panel="role-akses"] .role-access-table td:nth-child(2){width:26%!important}',
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(3),[data-tab-panel="role-akses"] .role-access-table td:nth-child(3){width:38%!important}',
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(4),[data-tab-panel="role-akses"] .role-access-table td:nth-child(4){width:12%!important;text-align:center!important;vertical-align:middle!important}',
      '[data-tab-panel="role-akses"] .role-access-table th:nth-child(4){text-align:center!important}',
      '[data-tab-panel="role-akses"] .role-access-save{width:auto!important;min-width:82px!important;max-width:100%!important;margin:0 auto!important;padding:7px 14px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;white-space:nowrap!important;font-size:10px!important;line-height:1.35!important;box-sizing:border-box!important}'
    ].join('');
    document.head.appendChild(style);
  }

  function filterRow(row) {
    var codeEl = row.querySelector('.role-access-code');
    if (!codeEl) return;

    var code = codeEl.textContent.trim().toUpperCase();
    var allowed = ALLOWED[code];
    if (!allowed) return;

    var labels = row.querySelectorAll('.role-access-permission');
    labels.forEach(function (label) {
      var input = label.querySelector('input[name="permissions[]"]');
      if (!input) return;
      var visible = allowed.indexOf(input.value) !== -1;
      label.hidden = !visible;
      if (!visible) input.disabled = true;
    });
  }

  function compactSaveButtons(section) {
    section.querySelectorAll('.role-access-table tbody tr').forEach(function (row) {
      var button = row.querySelector('td:nth-child(4) button[type="submit"], td:nth-child(4) .role-access-save');
      if (!button) return;
      button.classList.add('role-access-save');
      button.textContent = 'Simpan';
      button.setAttribute('aria-label', 'Simpan hak akses');
    });
  }

  function run(section) {
    if (!section) return;
    installLayoutFix();
    section.querySelectorAll('.role-access-table tbody tr').forEach(filterRow);
    compactSaveButtons(section);
  }

  function init() {
    var section = document.querySelector('[data-tab-panel="role-akses"]');
    if (!section) return;
    run(section);

    if (section.dataset.roleAccessFinalFixObserver === '1') return;
    section.dataset.roleAccessFinalFixObserver = '1';
    var observer = new MutationObserver(function () {
      run(section);
    });
    observer.observe(section, { childList: true, subtree: true });

    document.addEventListener('click', function (e) {
      if (e.target.closest && e.target.closest('[data-tab-link="role-akses"]')) {
        window.setTimeout(function () { run(section); }, 50);
        window.setTimeout(function () { run(section); }, 250);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
