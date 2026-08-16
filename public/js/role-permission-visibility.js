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

  function run() {
    var section = document.querySelector('[data-tab-panel="role-akses"]');
    if (!section) return;
    section.querySelectorAll('.role-access-table tbody tr').forEach(filterRow);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run, { once: true });
  } else {
    run();
  }
})();
