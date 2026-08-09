<script>
  // Toggle sidebar di mobile
  const menuBtn = document.getElementById('menuBtn');
  const sidebar = document.getElementById('sidebar');
  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (window.innerWidth <= 900 && sidebar.classList.contains('open') &&
          !sidebar.contains(e.target) && e.target !== menuBtn) {
        sidebar.classList.remove('open');
      }
    });
  }

  // Rapikan sidebar Danpus menjadi menu utama + submenu dropdown.
  // Hanya dijalankan pada halaman yang memiliki tab monitoring Danpus,
  // sehingga sidebar role lain tidak ikut berubah.
  (function(){
    function initDanpusSidebar(){
      var nav = document.querySelector('.side-nav');
      if (!nav || !nav.querySelector('[data-tab-link="laporan-monitoring"]')) return;
      if (nav.dataset.dropdownReady === '1') return;
      nav.dataset.dropdownReady = '1';

      var links = Array.prototype.slice.call(nav.querySelectorAll(':scope > a[data-tab-link]'));
      var byTab = {};
      links.forEach(function(link){ byTab[link.getAttribute('data-tab-link')] = link; });

      var dashboard = byTab.ringkasan;
      var laporan = byTab.laporan;
      var monitoring = byTab['laporan-monitoring'];
      var riwayat = byTab.riwayat;
      var statusSatuan = byTab['status-satuan'];
      if (!dashboard || !laporan || !monitoring || !riwayat || !statusSatuan) return;

      function makeIcon(){
        var svg = document.createElementNS('http://www.w3.org/2000/svg','svg');
        svg.setAttribute('viewBox','0 0 24 24');
        svg.setAttribute('class','side-dropdown-arrow');
        svg.setAttribute('aria-hidden','true');
        var path = document.createElementNS('http://www.w3.org/2000/svg','path');
        path.setAttribute('d','M6 9l6 6 6-6');
        svg.appendChild(path);
        return svg;
      }

      function makeGroup(label, children){
        var group = document.createElement('div');
        group.className = 'side-dropdown';

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'side-link side-dropdown-toggle';
        toggle.innerHTML = '<span class="dot"></span><span class="side-link-label"></span>';
        toggle.querySelector('.side-link-label').textContent = label;
        toggle.appendChild(makeIcon());

        var menu = document.createElement('div');
        menu.className = 'side-dropdown-menu';

        children.forEach(function(link){
          link.classList.add('side-sublink');
          link.classList.remove('active');
          var dot = link.querySelector('.dot');
          if (dot) dot.remove();
          menu.appendChild(link);
        });

        group.appendChild(toggle);
        group.appendChild(menu);

        toggle.addEventListener('click', function(){
          group.classList.toggle('open');
        });
        return group;
      }

      // Bersihkan posisi awal lalu susun kembali tanpa mengubah target tab.
      nav.innerHTML = '';
      var label = document.createElement('div');
      label.className = 'side-nav-label';
      label.textContent = 'Menu';
      nav.appendChild(label);
      nav.appendChild(dashboard);
      nav.appendChild(makeGroup('Pantauan Aktivitas Satlak', [monitoring, statusSatuan]));
      nav.appendChild(makeGroup('Pelaporan', [laporan, riwayat]));

      // Pastikan submenu terbuka jika halaman sedang berada pada salah satu tab anak.
      function syncGroupState(){
        nav.querySelectorAll('.side-dropdown').forEach(function(group){
          var activeChild = group.querySelector('.side-sublink.active');
          group.classList.toggle('open', !!activeChild);
        });
      }
      syncGroupState();
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initDanpusSidebar);
    } else {
      initDanpusSidebar();
    }
  })();

  // Batasi tinggi tabel supaya hanya menampilkan N baris pertama secara utuh;
  // sisa datanya bisa di-scroll di dalam card-nya sendiri (bukan scroll halaman).
  function terapkanRowLimitWrap(wrap) {
    if (!wrap) return;
    var limit = parseInt(wrap.getAttribute('data-row-limit'), 10) || 5;
    var table = wrap.querySelector('table');
    if (!table) return;
    wrap.style.maxHeight = '';
    wrap.classList.remove('tbl-scroll');
    var thead = table.querySelector('thead');
    var rows = Array.prototype.filter.call(table.querySelectorAll('tbody tr'), function (r) {
      return r.style.display !== 'none';
    });
    if (rows.length <= limit) return;
    var height = thead ? thead.offsetHeight : 0;
    for (var i = 0; i < limit; i++) height += rows[i].offsetHeight;
    if (height <= 0) return;
    wrap.style.maxHeight = height + 'px';
    wrap.classList.add('tbl-scroll');
  }
  window.terapkanRowLimitWrap = terapkanRowLimitWrap;

  function terapkanRowLimit(panel) {
    if (!panel) return;
    panel.querySelectorAll('[data-row-limit]').forEach(terapkanRowLimitWrap);
  }

  // Tab switching sederhana (prototype — belum tersambung ke backend laporan)
  const links = document.querySelectorAll('[data-tab-link]');
  const panels = document.querySelectorAll('[data-tab-panel]');
  links.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const target = link.getAttribute('data-tab-link');
      links.forEach(l => l.classList.remove('active'));
      panels.forEach(p => p.classList.remove('active'));
      link.classList.add('active');
      const targetPanel = document.querySelector(`[data-tab-panel="${target}"]`);
      if (targetPanel) {
        targetPanel.classList.add('active');
        terapkanRowLimit(targetPanel);
      }
      if (window.innerWidth <= 900 && sidebar) sidebar.classList.remove('open');
      window.scrollTo({top:0, behavior:'smooth'});
    });
  });

  document.querySelectorAll('.tab-panel.active').forEach(terapkanRowLimit);

  // Ganti tema (dark / light) — 1 tombol, tersimpan di localStorage, berlaku di semua halaman.
  (function(){
    var THEME_KEY = 'siberad-theme';
    var btn = document.getElementById('themeToggleBtn');
    function applyTheme(theme){
      if (theme === 'light') document.documentElement.setAttribute('data-theme', 'light');
      else document.documentElement.removeAttribute('data-theme');
      if (btn) btn.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
    }
    var saved = 'dark';
    try { saved = localStorage.getItem(THEME_KEY) || 'dark'; } catch (e) {}
    applyTheme(saved);
    if (btn) {
      btn.addEventListener('click', function(){
        var current = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
        var next = current === 'light' ? 'dark' : 'light';
        try { localStorage.setItem(THEME_KEY, next); } catch (e) {}
        applyTheme(next);
      });
    }
  })();

  // Bersihkan pemisah dekoratif dari teks yang tampil di seluruh dashboard role.
  (function(){
    function cleanDecorativeSeparators(root) {
      if (!root) return;
      var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
      var nodes = [];
      var node;
      while (node = walker.nextNode()) nodes.push(node);
      nodes.forEach(function(textNode){
        if (!textNode.nodeValue || !textNode.nodeValue.trim()) return;
        var value = textNode.nodeValue;
        value = value.replace(/\s*\/\/\s*/g, ' ');
        value = value.replace(/(^|\s)[-—](?=\s|$)/g, '$1');
        textNode.nodeValue = value.replace(/ {2,}/g, ' ');
      });
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function(){ cleanDecorativeSeparators(document.body); });
    } else {
      cleanDecorativeSeparators(document.body);
    }
  })();
</script>
