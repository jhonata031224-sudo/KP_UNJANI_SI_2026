(function () {
  'use strict';
  if (window.__SIBERAD_ADMIN_REPORT_CENTER__) return;
  window.__SIBERAD_ADMIN_REPORT_CENTER__ = true;

  var ENDPOINTS = {
    users: '/admin/laporan/report-center/pengguna',
    activities: '/admin/laporan/report-center/aktivitas',
    userExcel: '/admin/laporan/export/pengguna',
    activityExcel: '/admin/laporan/export/aktivitas',
    userPdf: '/admin/laporan/cetak/pengguna',
    activityPdf: '/admin/laporan/cetak/aktivitas'
  };

  var state = { kind: 'users', rows: [], loaded: false };

  function normalize(value) {
    return String(value || '').replace(/\s+/g, ' ').trim().toLowerCase();
  }

  function escapeHtml(value) {
    return String(value == null ? '-' : value).replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  function isReportButton(el) {
    var text = normalize(el.textContent);
    return text.indexOf('export pengguna') !== -1 ||
      text.indexOf('export aktivitas') !== -1 ||
      text.indexOf('cetak pengguna') !== -1 ||
      text.indexOf('cetak aktivitas') !== -1;
  }

  function findReportRoot() {
    var heading = Array.prototype.slice.call(document.querySelectorAll('h1,h2,h3,h4,strong'))
      .find(function (el) { return normalize(el.textContent) === 'laporan pengguna & aktivitas'; });
    if (!heading) return null;

    var root = heading;
    for (var depth = 0; root && depth < 8; depth += 1, root = root.parentElement) {
      var buttons = Array.prototype.slice.call(root.querySelectorAll('a,button')).filter(isReportButton);
      if (buttons.length >= 4) return root;
    }
    return heading.parentElement;
  }

  function injectStyles() {
    if (document.getElementById('siberadAdminReportCenterStyle')) return;
    var style = document.createElement('style');
    style.id = 'siberadAdminReportCenterStyle';
    style.textContent = [
      '.siberad-report-center{display:block;margin-top:18px;border:1px solid var(--border-soft);border-radius:14px;background:var(--panel);box-shadow:0 10px 28px rgba(15,23,42,.07);overflow:hidden}',
      '.siberad-report-tabs{display:flex;gap:6px;padding:14px 16px 0;border-bottom:1px solid var(--border-soft);background:var(--panel-alt)}',
      '.siberad-report-tab{border:1px solid transparent;border-bottom:0;background:transparent;color:var(--text-muted);padding:11px 15px;border-radius:10px 10px 0 0;font:700 12px var(--body);cursor:pointer}',
      '.siberad-report-tab.active{background:var(--panel);color:var(--gold-bright);border-color:var(--border-soft)}',
      '.siberad-report-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:17px 18px 12px}',
      '.siberad-report-head h3{margin:0;font:700 17px var(--display);color:var(--text)}',
      '.siberad-report-head p{margin:4px 0 0;font-size:11.5px;color:var(--text-muted);line-height:1.5}',
      '.siberad-report-actions{display:flex;gap:8px;position:relative;flex-shrink:0}',
      '.siberad-report-download{height:36px;border:1px solid var(--gold);background:var(--gold);color:#111827;border-radius:9px;padding:0 12px;font:800 11px var(--body);cursor:pointer}',
      '.siberad-report-menu{position:absolute;right:0;top:42px;z-index:100;padding:6px;min-width:175px;border:1px solid var(--border-soft);border-radius:10px;background:var(--panel);box-shadow:0 14px 30px rgba(15,23,42,.13);display:none}',
      '.siberad-report-menu.open{display:block}',
      '.siberad-report-menu button{display:block;width:100%;border:0;background:transparent;color:var(--text);text-align:left;padding:9px 10px;border-radius:8px;font:500 11px var(--body);cursor:pointer}',
      '.siberad-report-menu button:hover{background:var(--gold-dim)}',
      '.siberad-report-filter{display:flex;flex-wrap:wrap;align-items:center;gap:9px;padding:0 18px 14px}',
      '.siberad-report-search{position:relative;flex:1 1 260px;min-width:220px}',
      '.siberad-report-search input,.siberad-report-date{width:100%;height:38px;box-sizing:border-box;border:1px solid var(--border);border-radius:9px;background:var(--panel);color:var(--text);font:400 12px var(--body);padding:8px 11px}',
      '.siberad-report-search input{padding-left:35px}',
      '.siberad-report-date{flex:0 0 150px}',
      '.siberad-report-filter svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:16px;height:16px;fill:none;stroke:var(--text-dim);pointer-events:none}',
      '.siberad-report-count{margin-left:auto;font-size:10.5px;color:var(--text-dim);white-space:nowrap}',
      '.siberad-report-table-wrap{margin:0 18px 18px;border:1px solid var(--border-soft);border-radius:11px;overflow:auto}',
      '.siberad-report-table{width:100%;min-width:760px;border-collapse:collapse}',
      '.siberad-report-table th{background:var(--panel-alt);color:var(--text-muted);padding:10px 11px;border-bottom:1px solid var(--border-soft);text-align:left;font:700 9.5px var(--mono);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}',
      '.siberad-report-table td{padding:10px 11px;border-bottom:1px solid var(--border-soft);color:var(--text);font:400 11.5px var(--body);vertical-align:top}',
      '.siberad-report-table tr:last-child td{border-bottom:0}',
      '.siberad-report-table td.muted{color:var(--text-muted)}',
      '.siberad-report-loading{padding:30px 18px;text-align:center;color:var(--text-muted);font-size:12px}',
      '.siberad-report-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:0 18px 18px;color:var(--text-dim);font-size:10px}',
      '.siberad-report-print{height:36px;border:1px solid var(--border);background:var(--panel);color:var(--text);border-radius:9px;padding:0 12px;font:700 11px var(--body);cursor:pointer}',
      '@media(max-width:760px){.siberad-report-head{flex-direction:column}.siberad-report-actions{width:100%}.siberad-report-download{width:100%}.siberad-report-filter{flex-direction:column;align-items:stretch}.siberad-report-count{margin-left:0}.siberad-report-date{width:100%;flex-basis:auto}.siberad-report-table{min-width:680px}}'
    ].join('');
    document.head.appendChild(style);
  }

  function createCenter(root) {
    var existing = document.getElementById('siberadAdminReportCenter');
    if (existing) return existing;

    var buttons = Array.prototype.slice.call(root.querySelectorAll('a,button')).filter(isReportButton);
    if (!buttons.length) return null;

    var toolbar = buttons[0].parentElement;
    for (var i = 0; i < 6 && toolbar && toolbar !== root; i += 1, toolbar = toolbar.parentElement) {
      var count = Array.prototype.slice.call(toolbar.querySelectorAll('a,button')).filter(isReportButton).length;
      if (count >= 4) break;
    }
    if (toolbar) toolbar.style.display = 'none';

    var center = document.createElement('div');
    center.id = 'siberadAdminReportCenter';
    center.className = 'siberad-report-center';
    center.innerHTML = [
      '<div class="siberad-report-tabs">',
      '<button type="button" class="siberad-report-tab active" data-report-kind="users">Data Pengguna</button>',
      '<button type="button" class="siberad-report-tab" data-report-kind="activities">Aktivitas Sistem</button>',
      '</div>',
      '<div class="siberad-report-head">',
      '<div><h3 id="siberadReportTitle">Data Pengguna</h3><p id="siberadReportSubtitle">Daftar pengguna sistem yang dapat dilihat dan diunduh.</p></div>',
      '<div class="siberad-report-actions">',
      '<button type="button" class="siberad-report-download" id="siberadReportDownload">Unduh ▾</button>',
      '<div class="siberad-report-menu" id="siberadReportMenu">',
      '<button type="button" data-report-download="excel">Excel (.xlsx)</button>',
      '<button type="button" data-report-download="pdf">PDF / Cetak</button>',
      '</div></div></div>',
      '<div class="siberad-report-filter">',
      '<div class="siberad-report-search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg><input id="siberadReportSearch" type="search" placeholder="Cari pengguna..."></div>',
      '<input class="siberad-report-date" id="siberadReportFrom" type="date" hidden>',
      '<input class="siberad-report-date" id="siberadReportTo" type="date" hidden>',
      '<span class="siberad-report-count" id="siberadReportCount">Memuat data...</span>',
      '</div>',
      '<div class="siberad-report-table-wrap"><div id="siberadReportBody" class="siberad-report-loading">Memuat data laporan...</div></div>',
      '<div class="siberad-report-footer"><span>Data ditampilkan langsung dari database sistem.</span><button type="button" class="siberad-report-print" id="siberadReportPrint">Cetak / PDF</button></div>'
    ].join('');

    root.insertAdjacentElement('beforeend', center);
    return center;
  }

  function render() {
    var body = document.getElementById('siberadReportBody');
    var search = document.getElementById('siberadReportSearch');
    if (!body || !search) return;
    var q = normalize(search.value);
    var rows = state.rows.filter(function (row) {
      return !q || Object.keys(row).some(function (key) { return normalize(row[key]).indexOf(q) !== -1; });
    });

    document.getElementById('siberadReportCount').textContent = rows.length.toLocaleString('id-ID') + ' data ditampilkan';

    if (!rows.length) {
      // .empty-state -- kelas "1 sistem" yang sama dipakai empty-state
      // lain di seluruh dashboard (lihat dash-styles.blade.php), bukan
      // teks polos custom sendiri lagi biar konsisten persis.
      body.className = 'empty-state';
      body.innerHTML = '<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Tidak ada data yang cocok dengan pencarian.</div>';
      return;
    }

    body.className = '';
    var html = '';
    if (state.kind === 'users') {
      html += '<table class="siberad-report-table"><thead><tr><th>No</th><th>Nama</th><th>Username</th><th>Email</th><th>Satuan</th><th>Jabatan</th><th>Dibuat</th></tr></thead><tbody>';
      rows.forEach(function (row, index) {
        html += '<tr><td class="muted">' + (index + 1) + '</td><td><strong>' + escapeHtml(row.name) + '</strong></td><td>' + escapeHtml(row.username) + '</td><td>' + escapeHtml(row.email) + '</td><td>' + escapeHtml(row.satuan) + '</td><td>' + escapeHtml(row.jabatan) + '</td><td>' + escapeHtml(row.created) + '</td></tr>';
      });
      html += '</tbody></table>';
    } else {
      html += '<table class="siberad-report-table"><thead><tr><th>Waktu</th><th>Pengguna</th><th>Satuan</th><th>Aksi</th><th>Deskripsi</th></tr></thead><tbody>';
      rows.forEach(function (row) {
        html += '<tr><td class="muted">' + escapeHtml(row.waktu) + '</td><td><strong>' + escapeHtml(row.pengguna) + '</strong></td><td>' + escapeHtml(row.satuan) + '</td><td>' + escapeHtml(row.aksi) + '</td><td>' + escapeHtml(row.deskripsi) + '</td></tr>';
      });
      html += '</tbody></table>';
    }
    body.innerHTML = html;
  }

  function load() {
    var body = document.getElementById('siberadReportBody');
    if (!body) return;
    body.className = 'siberad-report-loading';
    body.textContent = 'Memuat data laporan...';

    var params = new URLSearchParams();
    if (state.kind === 'activities') {
      var from = document.getElementById('siberadReportFrom').value;
      var to = document.getElementById('siberadReportTo').value;
      if (from) params.set('dari', from);
      if (to) params.set('sampai', to);
    }

    fetch((state.kind === 'users' ? ENDPOINTS.users : ENDPOINTS.activities) + '?' + params.toString(), {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin'
    }).then(function (response) {
      if (!response.ok) throw new Error('HTTP ' + response.status);
      return response.json();
    }).then(function (data) {
      state.rows = Array.isArray(data.rows) ? data.rows : [];
      state.loaded = true;
      render();
    }).catch(function () {
      body.className = 'siberad-report-loading';
      body.textContent = 'Data laporan gagal dimuat. Silakan coba lagi.';
    });
  }

  function setKind(kind) {
    state.kind = kind;
    document.querySelectorAll('#siberadAdminReportCenter .siberad-report-tab').forEach(function (tab) {
      tab.classList.toggle('active', tab.getAttribute('data-report-kind') === kind);
    });
    var title = document.getElementById('siberadReportTitle');
    var subtitle = document.getElementById('siberadReportSubtitle');
    var search = document.getElementById('siberadReportSearch');
    var from = document.getElementById('siberadReportFrom');
    var to = document.getElementById('siberadReportTo');
    if (kind === 'users') {
      title.textContent = 'Data Pengguna';
      subtitle.textContent = 'Daftar pengguna sistem yang dapat dilihat dan diunduh.';
      search.placeholder = 'Cari pengguna...';
      from.hidden = true;
      to.hidden = true;
    } else {
      title.textContent = 'Aktivitas Sistem';
      subtitle.textContent = 'Riwayat aktivitas pengguna dan perubahan yang tercatat di sistem.';
      search.placeholder = 'Cari aktivitas, pengguna, atau aksi...';
      from.hidden = false;
      to.hidden = false;
    }
    document.getElementById('siberadReportMenu').classList.remove('open');
    load();
  }

  function wire(center) {
    center.querySelectorAll('[data-report-kind]').forEach(function (tab) {
      tab.addEventListener('click', function () { setKind(tab.getAttribute('data-report-kind')); });
    });
    document.getElementById('siberadReportSearch').addEventListener('input', render);
    document.getElementById('siberadReportFrom').addEventListener('change', load);
    document.getElementById('siberadReportTo').addEventListener('change', load);

    document.getElementById('siberadReportDownload').addEventListener('click', function (event) {
      event.stopPropagation();
      document.getElementById('siberadReportMenu').classList.toggle('open');
    });
    document.addEventListener('click', function () {
      var menu = document.getElementById('siberadReportMenu');
      if (menu) menu.classList.remove('open');
    });

    center.querySelectorAll('[data-report-download]').forEach(function (button) {
      button.addEventListener('click', function () {
        var isPdf = button.getAttribute('data-report-download') === 'pdf';
        var url = state.kind === 'users'
          ? (isPdf ? ENDPOINTS.userPdf : ENDPOINTS.userExcel)
          : (isPdf ? ENDPOINTS.activityPdf : ENDPOINTS.activityExcel);
        window.open(url, '_blank', 'noopener');
      });
    });

    document.getElementById('siberadReportPrint').addEventListener('click', function () {
      window.open(state.kind === 'users' ? ENDPOINTS.userPdf : ENDPOINTS.activityPdf, '_blank', 'noopener');
    });
  }

  function init() {
    var root = findReportRoot();
    if (!root) return;
    injectStyles();
    var center = createCenter(root);
    if (!center || center.dataset.bound === '1') return;
    center.dataset.bound = '1';
    wire(center);
    load();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }

  var observer = new MutationObserver(init);
  observer.observe(document.body, { childList: true, subtree: true });
  window.setTimeout(function () { observer.disconnect(); }, 12000);
})();
