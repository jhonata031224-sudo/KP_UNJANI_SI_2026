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

  // Hanya submenu Danpus yang tidak menggunakan titik/bullet.
  // Menu utama tetap mempertahankan titiknya.
  (function(){
    var style = document.createElement('style');
    style.setAttribute('data-danpus-submenu-fix', 'true');
    style.textContent = '\n      .side-dropdown-menu,\n      .side-dropdown-menu ul,\n      .side-dropdown-menu ol,\n      .side-dropdown-menu li { list-style:none !important; list-style-type:none !important; }\n      .side-dropdown-menu li::marker { content:"" !important; display:none !important; }\n      .side-dropdown-menu a::before,\n      .side-dropdown-menu a::after,\n      .side-dropdown-menu .side-sublink::before,\n      .side-dropdown-menu .side-sublink::after { content:none !important; display:none !important; }\n      .side-dropdown-menu .dot,\n      .side-dropdown-menu .side-sublink .dot { display:none !important; width:0 !important; min-width:0 !important; margin:0 !important; padding:0 !important; }\n      .side-dropdown-menu .side-sublink { padding-left:32px !important; padding-right:12px !important; gap:0 !important; list-style:none !important; background-image:none !important; }\n    ';
    document.head.appendChild(style);
  })();

  // Rapikan sidebar Danpus menjadi menu utama + submenu dropdown.
  (function(){
    function removeSubmenuBullets(link){
      if (!link) return;
      link.style.setProperty('list-style', 'none', 'important');
      link.style.setProperty('list-style-type', 'none', 'important');
      link.style.setProperty('background-image', 'none', 'important');
      var dot = link.querySelector('.dot');
      if (dot) dot.remove();
      Array.prototype.slice.call(link.children).forEach(function(child){
        if (child.tagName === 'SPAN' && !child.textContent.trim() && !child.classList.contains('side-link-label')) child.remove();
      });
    }

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
          removeSubmenuBullets(link);
          menu.appendChild(link);
        });
        group.appendChild(toggle);
        group.appendChild(menu);
        toggle.addEventListener('click', function(){ group.classList.toggle('open'); });
        return group;
      }

      nav.innerHTML = '';
      var label = document.createElement('div');
      label.className = 'side-nav-label';
      label.textContent = 'Menu';
      nav.appendChild(label);
      nav.appendChild(dashboard);
      nav.appendChild(makeGroup('Log Aktivitas', [monitoring, statusSatuan]));
      nav.appendChild(makeGroup('Pelaporan', [laporan, riwayat]));

      nav.querySelectorAll('.side-dropdown-menu a, .side-dropdown-menu .side-sublink').forEach(removeSubmenuBullets);

      function syncGroupState(){
        nav.querySelectorAll('.side-dropdown').forEach(function(group){
          var activeChild = group.querySelector('.side-sublink.active');
          group.classList.toggle('open', !!activeChild);
        });
      }
      syncGroupState();

      function enforcePlainSubmenus(){
        nav.querySelectorAll('.side-dropdown-menu a').forEach(function(link){ removeSubmenuBullets(link); });
      }
      enforcePlainSubmenus();
      setTimeout(enforcePlainSubmenus, 50);
      setTimeout(enforcePlainSubmenus, 250);
      setTimeout(enforcePlainSubmenus, 1000);
      if (window.MutationObserver) {
        var observer = new MutationObserver(function(){ enforcePlainSubmenus(); });
        observer.observe(nav, { childList:true, subtree:true });
      }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initDanpusSidebar);
    else initDanpusSidebar();
  })();

  // Stabilkan dropdown Laporan pada halaman Satlak berbasis laporan-role/satlakduktek.
  // Script ini sengaja hanya aktif bila struktur dropdown Satlak memang ada.
  (function(){
    function initSatlakLaporan(){
      var nav = document.querySelector('.side-nav');
      var dropdown = document.getElementById('laporanDropdown');
      var toggle = document.getElementById('laporanToggle');
      if (!nav || !dropdown || !toggle) return;
      if (dropdown.dataset.stableReady === '1') return;
      dropdown.dataset.stableReady = '1';

      var menu = dropdown.querySelector('.side-dropdown-menu');
      if (!menu) return;

      var css = document.createElement('style');
      css.setAttribute('data-satlak-laporan-fix','true');
      css.textContent = `
        #laporanDropdown { width:100%; margin:0; }
        #laporanDropdown > #laporanToggle,
        #laporanDropdown > .side-dropdown-toggle { width:100%; min-height:40px; display:flex; align-items:center; gap:10px; padding:10px 12px; box-sizing:border-box; border:1px solid transparent; border-radius:9px; background:transparent; color:var(--text-muted); font-family:var(--body); font-size:13.5px; font-weight:500; line-height:1.25; text-align:left; cursor:pointer; }
        #laporanDropdown > #laporanToggle:hover,
        #laporanDropdown > .side-dropdown-toggle:hover { background:var(--hover-tint); color:var(--text); }
        #laporanDropdown > #laporanToggle .side-link-label { flex:1; min-width:0; }
        #laporanDropdown > #laporanToggle .dot { width:6px; height:6px; min-width:6px; border-radius:50%; background:currentColor; opacity:.6; flex:0 0 6px; }
        #laporanDropdown .side-dropdown-arrow { width:14px; height:14px; flex:0 0 14px; margin-left:auto; stroke:currentColor; fill:none; stroke-width:2.2; transform:none; transition:transform .2s ease; }
        #laporanDropdown.open .side-dropdown-arrow { transform:rotate(180deg); }
        #laporanDropdown .side-dropdown-menu { display:flex; flex-direction:column; gap:2px; max-height:0; overflow:hidden; margin:0; padding:0; transition:max-height .22s ease; }
        #laporanDropdown.open .side-dropdown-menu { max-height:220px; margin-top:2px; }
        #laporanDropdown .side-sublink { display:flex; align-items:center; width:100%; min-height:34px; padding:8px 12px 8px 32px !important; box-sizing:border-box; border-radius:8px; color:var(--text-muted); font-family:var(--body); font-size:12.5px !important; font-weight:500; line-height:1.25; text-decoration:none; white-space:nowrap; }
        #laporanDropdown .side-sublink:hover { background:var(--hover-tint); color:var(--text); }
        #laporanDropdown .side-sublink.active { background:var(--gold-dim); color:var(--gold-bright); border-color:var(--border); font-weight:600; }
        #laporanDropdown .side-sublink .dot { display:none !important; }
      `;
      document.head.appendChild(css);

      function sync(){
        var open = dropdown.classList.contains('open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.style.transform = 'none';
        toggle.style.rotate = '0deg';
      }
      toggle.addEventListener('click', function(){ setTimeout(sync, 0); });
      sync();

      // Jika ada script lain yang menyentuh sidebar, jangan biarkan dropdown
      // berubah ukuran/posisi atau kehilangan submenu setelah halaman selesai render.
      if (window.MutationObserver) {
        var observer = new MutationObserver(function(){
          if (!document.getElementById('laporanDropdown')) return;
          sync();
        });
        observer.observe(nav, { childList:true, subtree:true, attributes:true, attributeFilter:['class','style'] });
      }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initSatlakLaporan);
    else initSatlakLaporan();
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
  window.terapkanRowLimit = terapkanRowLimit;

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
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ cleanDecorativeSeparators(document.body); });
    else cleanDecorativeSeparators(document.body);
  })();
</script>
