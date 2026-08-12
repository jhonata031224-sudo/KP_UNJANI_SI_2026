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
    style.textContent = '\n      /* Hilangkan seluruh jenis bullet/titik dari SEMUA submenu Danpus. */\n      .side-dropdown-menu,\n      .side-dropdown-menu ul,\n      .side-dropdown-menu ol,\n      .side-dropdown-menu li { list-style:none !important; list-style-type:none !important; }\n      .side-dropdown-menu li::marker { content:"" !important; display:none !important; }\n      .side-dropdown-menu a::before,\n      .side-dropdown-menu a::after,\n      .side-dropdown-menu .side-sublink::before,\n      .side-dropdown-menu .side-sublink::after { content:none !important; display:none !important; }\n      .side-dropdown-menu .dot,\n      .side-dropdown-menu .side-sublink .dot { display:none !important; width:0 !important; min-width:0 !important; margin:0 !important; padding:0 !important; }\n      .side-dropdown-menu .side-sublink { padding-left:32px !important; padding-right:12px !important; gap:0 !important; list-style:none !important; background-image:none !important; }\n    ';
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
        if (child.tagName === 'SPAN' && !child.textContent.trim() && !child.classList.contains('side-link-label')) {
          child.remove();
        }
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

      function enforcePlainSubmenus(){ nav.querySelectorAll('.side-dropdown-menu a').forEach(removeSubmenuBullets); }
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

  function terapkanRowLimitWrap(wrap) {
    if (!wrap) return;
    var limit = parseInt(wrap.getAttribute('data-row-limit'), 10) || 5;
    var table = wrap.querySelector('table');
    if (!table) return;
    wrap.style.maxHeight = '';
    wrap.classList.remove('tbl-scroll');
    var thead = table.querySelector('thead');
    var rows = Array.prototype.filter.call(table.querySelectorAll('tbody tr'), function (r) { return r.style.display !== 'none'; });
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

  const ADMIN_ACTIVE_TAB_KEY = 'siberad-admin-active-tab';
  const ADMIN_GROUP_STATE_KEY = 'siberad-admin-group-';
  const links = document.querySelectorAll('[data-tab-link]');
  const panels = document.querySelectorAll('[data-tab-panel]');

  function activateAdminTab(target, skipSave) {
    const targetPanel = document.querySelector(`[data-tab-panel="${target}"]`);
    if (!targetPanel) return false;
    links.forEach(l => l.classList.remove('active'));
    panels.forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.side-nav-group').forEach(g => g.classList.remove('has-active-child'));
    const matchedLinks = document.querySelectorAll(`[data-tab-link="${target}"]`);
    matchedLinks.forEach(l => {
      l.classList.add('active');
      const group = l.closest('.side-nav-group');
      if (group) {
        group.classList.add('has-active-child');
        if (!sidebar || !sidebar.classList.contains('collapsed')) group.classList.add('open');
        try { sessionStorage.setItem(ADMIN_GROUP_STATE_KEY + group.id, 'open'); } catch (e) {}
      }
    });
    targetPanel.classList.add('active');
    terapkanRowLimit(targetPanel);
    if (!skipSave) {
      try { sessionStorage.setItem(ADMIN_ACTIVE_TAB_KEY, target); } catch (e) {}
    }
    return true;
  }

  links.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const target = link.getAttribute('data-tab-link');
      activateAdminTab(target);
      if (window.innerWidth <= 900 && sidebar) sidebar.classList.remove('open');
      window.scrollTo({top:0, behavior:'smooth'});
    });
  });

  (function () {
    let savedTab = null;
    try { savedTab = sessionStorage.getItem(ADMIN_ACTIVE_TAB_KEY); } catch (e) {}
    if (savedTab) activateAdminTab(savedTab, true);
  })();
  document.querySelectorAll('.tab-panel.active').forEach(terapkanRowLimit);

  (function(){
    var THEME_KEY = 'siberad-theme';
    var btn = document.getElementById('themeToggleBtn');
    if (!btn || btn.dataset.uiBound) return;
    btn.dataset.uiBound = '1';
    function applyTheme(theme){
      if (theme === 'light') document.documentElement.setAttribute('data-theme', 'light');
      else document.documentElement.removeAttribute('data-theme');
      btn.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
    }
    var saved = 'dark';
    try { saved = localStorage.getItem(THEME_KEY) || 'dark'; } catch (e) {}
    applyTheme(saved);
    btn.addEventListener('click', function(){
      var current = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
      var next = current === 'light' ? 'dark' : 'light';
      try { localStorage.setItem(THEME_KEY, next); } catch (e) {}
      applyTheme(next);
    });
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

  // -------------------------------------------------------------------------
  // Landing preview: gunakan halaman landing AKTUAL di dalam iframe.
  // Dengan begitu preview tidak lagi meniru layout secara terpisah: CSS,
  // responsive layout, header, hero, fitur, tentang, footer, loader, dll.
  // semuanya berasal dari welcome.blade.php yang dipakai pengunjung.
  // Guard ini membuat script tidak melakukan apa pun di halaman admin lain.
  // -------------------------------------------------------------------------
  (function(){
    var form = document.getElementById('formPengaturanLanding');
    var fakePreview = document.getElementById('lpPreview');
    if (!form || !fakePreview) return;

    var browserFrame = fakePreview.closest('.lp-browser-frame');
    if (!browserFrame) return;

    var iframe = document.createElement('iframe');
    iframe.id = 'lpLiveLandingFrame';
    iframe.title = 'Pratinjau landing page SIBERAD';
    iframe.src = '/?admin_preview=1';
    iframe.setAttribute('loading', 'eager');
    iframe.setAttribute('referrerpolicy', 'same-origin');
    iframe.style.cssText = 'display:block;width:100%;height:900px;min-height:700px;border:0;background:#151e19;';

    fakePreview.style.display = 'none';
    browserFrame.appendChild(iframe);

    var frameReady = false;
    var pendingFileUrl = null;

    function value(name, fallback){
      var el = form.querySelector('[data-lp="' + name + '"]');
      return el ? el.value : (fallback || '');
    }

    function text(el, val){ if (el) el.textContent = val || ''; }

    function frameDoc(){
      try { return iframe.contentDocument || iframe.contentWindow.document; } catch (e) { return null; }
    }

    function setTheme(doc){
      if (!doc) return;
      var theme = document.documentElement.getAttribute('data-theme');
      if (theme === 'light') doc.documentElement.setAttribute('data-theme','light');
      else doc.documentElement.removeAttribute('data-theme');
    }

    function updateLanding(){
      var doc = frameDoc();
      if (!doc || !frameReady) return;
      setTheme(doc);

      var hero = doc.querySelector('.hero');
      var heroBg = doc.querySelector('.hero-stats-bg');
      var eyebrow = doc.querySelector('.hero .eyebrow');
      var h1 = doc.querySelector('.hero h1');
      var h1Em = h1 ? h1.querySelector('em') : null;
      var h2 = doc.querySelector('.hero h2');
      var heroP = doc.querySelector('.hero > .wrap .hero-inner > div:first-child > p');
      text(eyebrow, value('hero_eyebrow'));
      if (h1) {
        var first = value('hero_judul_awal');
        Array.prototype.slice.call(h1.childNodes).forEach(function(n){ if (n.nodeType === 3) n.nodeValue = ''; });
        if (h1.firstChild && h1.firstChild.nodeType === 3) h1.firstChild.nodeValue = first;
        else h1.insertBefore(doc.createTextNode(first), h1Em || null);
      }
      text(h1Em, value('hero_judul_aksen'));
      text(h2, value('hero_subjudul'));
      text(heroP, value('hero_deskripsi'));

      if (heroBg) {
        if (pendingFileUrl) {
          heroBg.style.backgroundImage = "linear-gradient(115deg, var(--hero-ov-1) 0%, var(--hero-ov-2) 32%, var(--hero-ov-3) 58%, var(--hero-ov-4) 100%), linear-gradient(to top, var(--hero-ov-top) 0%, var(--hero-ov-top-fade) 26%), url('" + pendingFileUrl.replace(/'/g, "\\'") + "')";
        }
      }

      var featureCards = doc.querySelectorAll('.feature-grid .feature-card');
      var featureInputs = form.querySelectorAll('[data-lp^="fitur_judul_"]');
      featureInputs.forEach(function(input){
        var match = input.getAttribute('data-lp').match(/_(\d+)$/);
        if (!match) return;
        var i = parseInt(match[1],10);
        var card = featureCards[i];
        if (!card) return;
        text(card.querySelector('h4'), input.value);
        var desc = form.querySelector('[data-lp="fitur_deskripsi_' + i + '"]');
        text(card.querySelector('p'), desc ? desc.value : '');
      });

      var about = doc.querySelector('#tentang-pussiberad');
      if (about) {
        var aboutTextWrap = about.querySelector('.about-top > div:last-child');
        var aboutParas = aboutTextWrap ? aboutTextWrap.querySelectorAll('p') : [];
        var aboutValue = value('tentang_deskripsi');
        var paragraphs = aboutValue.split(/\n\s*\n/).map(function(p){ return p.trim(); }).filter(Boolean);
        if (aboutTextWrap) {
          paragraphs.forEach(function(p, i){
            if (aboutParas[i]) text(aboutParas[i], p);
            else {
              var np = doc.createElement('p');
              np.style.cssText = 'color:var(--text-muted);line-height:1.8;font-size:15px;margin-top:14px;';
              np.textContent = p;
              aboutTextWrap.appendChild(np);
            }
          });
          for (var ai = paragraphs.length; ai < aboutParas.length; ai++) aboutParas[ai].textContent = '';
        }
        var moto = about.querySelector('.moto-panel');
        if (moto) {
          text(moto.querySelector('h3'), value('tentang_moto_judul'));
          text(moto.querySelector('.moto-desc'), value('tentang_moto_deskripsi'));
        }
      }

      var footer = doc.querySelector('footer');
      if (footer) {
        var cols = footer.querySelectorAll('.footer-grid > div');
        var contactCol = cols[3];
        if (contactCol) {
          var items = contactCol.querySelectorAll('.footer-links li');
          var alamat = value('alamat');
          var telepon = value('telepon_kontak');
          var website = value('website');
          if (items[0]) items[0].textContent = alamat;
          if (items[1]) items[1].textContent = telepon;
          if (items[2]) {
            var a = items[2].querySelector('a');
            if (a) { a.href = website || '#'; a.textContent = website.replace(/^https?:\/\//,'').replace(/\/$/,''); }
          }
        }
      }
    }

    function syncTheme(){
      var doc = frameDoc();
      if (doc) setTheme(doc);
    }

    iframe.addEventListener('load', function(){
      frameReady = true;
      syncTheme();
      updateLanding();
    });

    form.querySelectorAll('[data-lp]').forEach(function(el){
      el.addEventListener('input', updateLanding);
      el.addEventListener('change', updateLanding);
    });

    var heroImageInput = form.querySelector('[data-lp-image="hero_image"]');
    if (heroImageInput) {
      heroImageInput.addEventListener('change', function(){
        var file = this.files && this.files[0];
        if (!file) { pendingFileUrl = null; updateLanding(); return; }
        var reader = new FileReader();
        reader.onload = function(e){ pendingFileUrl = e.target.result; updateLanding(); };
        reader.readAsDataURL(file);
      });
    }

    var themeObserver = new MutationObserver(syncTheme);
    themeObserver.observe(document.documentElement, {attributes:true, attributeFilter:['data-theme']});

    // Ukuran preview mengikuti viewport admin dan tetap menjadi satu halaman
    // utuh yang bisa di-scroll di dalam frame, tanpa memengaruhi halaman admin.
    function resizeFrame(){
      var h = Math.max(700, Math.min(1100, window.innerHeight - 170));
      iframe.style.height = h + 'px';
    }
    resizeFrame();
    window.addEventListener('resize', resizeFrame, {passive:true});
  })();
</script>
