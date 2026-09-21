/*
 * Pratinjau Langsung — Admin > Pengaturan Umum.
 *
 * Menampilkan landing page ASLI ("/?lp_preview=1") di dalam iframe, diskalakan
 * supaya lebar desktop-nya (1440px) muat utuh di kartu, lalu menyuntikkan isi
 * form (termasuk yang belum disimpan) ke DOM iframe secara langsung.
 *
 * Karena yang tampil adalah halaman aslinya, tampilan (tema gelap/terang, hero,
 * lambang, statistik, fitur, tentang, footer, modal Makna Logo) otomatis sama
 * dengan landing page sekarang. Modul ini cuma bertugas: (1) memuat & menskalakan
 * iframe, (2) menyalin nilai form ke elemen yang sama di landing.
 *
 * Selector landing yang dipakai dikumpulkan di bagian "target" per fungsi sync
 * di bawah -- kalau struktur landing berubah, cukup betulkan selector-nya di sini.
 *
 * Field berbasis config (brand, nav, statistik, judul section, login, footer
 * dst.) disinkronkan oleh admin-landing-editor.js ke iframe yang SAMA
 * (#lpLiveLandingFrame) -- jangan dobel di sini.
 */
(function () {
  'use strict';
  if (window.lpLivePreview) return;

  var FRAME_ID = 'lpLiveLandingFrame';
  var VIEWPORT_ID = 'lpLiveViewport';
  var PANEL_SELECTOR = '[data-tab-panel="pengaturan-umum"]';
  var DESIGN_WIDTH = 1440; // lebar layar desktop yang disimulasikan
  var EDIT_SCOPE = '#landingForm, #lpLandingModalBackdrop';

  var FOCUS_TARGETS = {
    beranda: '.hero-stats-bg',
    fitur: '#fitur',
    tentang: '#tentang-pussiberad',
    kontak: 'footer'
  };

  // Ikon footer -- SAMA dengan $sosialIcons di welcome.blade.php.
  var SOSIAL_ICONS = {
    instagram: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1"/></svg>',
    tiktok: '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 3h-3.1v12.4a2.7 2.7 0 1 1-1.9-2.6V9.6a5.8 5.8 0 1 0 5 5.7V9.4a7.9 7.9 0 0 0 4.4 1.3V7.6c-2.2-.2-4-1.9-4.4-4.1z"/></svg>',
    youtube: '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22.5 7.2c-.3-1.1-1.1-1.9-2.1-2.2C18.6 4.5 12 4.5 12 4.5s-6.6 0-8.4.5c-1 .3-1.8 1.1-2.1 2.2C1 9 1 12 1 12s0 3 .5 4.8c.3 1.1 1.1 1.9 2.1 2.2 1.8.5 8.4.5 8.4.5s6.6 0 8.4-.5c1-.3 1.8-1.1 2.1-2.2.5-1.8.5-4.8.5-4.8s0-3-.5-4.8zM9.8 15.3V8.7l6 3.3-6 3.3z"/></svg>',
    x: '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 3H22l-7.5 8.6L23 21h-6.6l-5.2-6.6L5.2 21H2l8.1-9.3L2 3h6.7l4.7 6 5.5-6z"/></svg>',
    facebook: '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-8h2.7l.4-3.1h-3.1V8c0-.9.3-1.5 1.6-1.5h1.7V3.7C16.5 3.6 15.6 3.5 14.6 3.5c-2.4 0-4 1.5-4 4.1v2.3H7.9V13h2.7v8h2.9z"/></svg>',
    wikipedia: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.8 5.7 3.8 9s-1.3 6.5-3.8 9c-2.5-2.5-3.8-5.7-3.8-9s1.3-6.5 3.8-9z"/></svg>'
  };

  var frame = null;
  var viewport = null;
  var ready = false;        // dokumen landing di iframe sudah siap disuntik
  var timer = 0;
  var currentFocus = null;  // nama section yang sedang disorot ('beranda', ...)
  var pendingFocus = null;
  var maknaOpenedByUs = false;
  var lastBgKey = '';

  // ------------------------------------------------------------------ util

  function byId(id) { return document.getElementById(id); }

  // Query dari `document`, BUKAN dari <form>: panel section yang sedang dibuka
  // di modal dipindah keluar dari <form> (lihat lpOpenModal di admin.blade.php).
  function field(name) { return document.querySelector('[data-lp="' + name + '"]'); }

  function val(name) {
    var el = field(name);
    return el ? String(el.value == null ? '' : el.value) : '';
  }

  function iframeDoc() {
    try { return frame && (frame.contentDocument || frame.contentWindow.document); } catch (e) { return null; }
  }

  function setText(el, value) {
    if (el && el.textContent !== value) el.textContent = value;
  }

  function safe(fn, d) {
    try { fn(d); } catch (e) { if (window.console && console.warn) console.warn('[lpLivePreview]', e); }
  }

  function cssString(value) {
    return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/[\r\n]+/g, '');
  }

  // ---------------------------------------------------------- layout & fit

  function fit() {
    if (!frame || !viewport) return;
    var width = viewport.clientWidth;
    if (width < 50) return; // tab masih tersembunyi; dihitung ulang saat dibuka

    var scale = Math.min(1, width / DESIGN_WIDTH);
    var height = Math.round(Math.max(460, Math.min(820, window.innerHeight * 0.74)));

    viewport.style.height = height + 'px';
    frame.style.width = DESIGN_WIDTH + 'px';
    frame.style.height = Math.ceil(height / scale) + 'px';
    frame.style.transform = 'scale(' + scale + ')';
  }

  // ------------------------------------------------------------- sinkronisasi

  function syncTheme(d) {
    var light = document.documentElement.getAttribute('data-theme') === 'light';
    if (light) d.documentElement.setAttribute('data-theme', 'light');
    else d.documentElement.removeAttribute('data-theme');
  }

  function syncHero(d) {
    var inner = d.querySelector('.hero-inner');
    if (!inner) return;

    setText(inner.querySelector('.eyebrow'), val('hero_eyebrow'));

    var h1 = inner.querySelector('h1');
    if (h1) {
      var em = h1.querySelector('em');
      Array.prototype.slice.call(h1.childNodes).forEach(function (n) {
        if (n.nodeType === 3) h1.removeChild(n);
      });
      h1.insertBefore(d.createTextNode(val('hero_judul_awal')), h1.firstChild);
      setText(em, val('hero_judul_aksen'));
    }

    setText(inner.querySelector('h2'), val('hero_subjudul'));
    setText(inner.querySelector(':scope > div:first-child > p'), val('hero_deskripsi'));

    // Merek di footer memakai judul yang sama (welcome.blade.php: .footer-brand b).
    var footBrand = d.querySelector('.footer-brand b');
    if (footBrand) {
      var accent = footBrand.querySelector('span');
      Array.prototype.slice.call(footBrand.childNodes).forEach(function (n) {
        if (n.nodeType === 3) footBrand.removeChild(n);
      });
      footBrand.insertBefore(d.createTextNode(val('hero_judul_awal')), footBrand.firstChild);
      setText(accent, val('hero_judul_aksen'));
    }
  }

  function syncFitur(d) {
    var cards = d.querySelectorAll('#fitur .feature-card');
    for (var i = 0; i < cards.length; i++) {
      var judul = field('fitur_judul_' + i);
      if (!judul) continue;
      var deskripsi = field('fitur_deskripsi_' + i);
      setText(cards[i].querySelector('h4'), judul.value);
      setText(cards[i].querySelector('p'), deskripsi ? deskripsi.value : '');
    }
  }

  function syncTentang(d) {
    var about = d.getElementById('tentang-pussiberad');
    if (!about) return;

    // Paragraf: meniru welcome.blade.php PERSIS -- explode("\n\n") lalu trim().
    // Isi textarea disimpan server apa adanya dengan pemisah baris CRLF (bukan
    // LF), jadi jeda paragraf "\r\n\r\n" TIDAK terpecah oleh explode("\n\n") dan
    // landing menampilkannya sebagai satu paragraf. Pratinjau ikut begitu supaya
    // sama dengan hasil setelah disimpan.
    var box = about.querySelector('.about-top > div:last-child');
    if (box) {
      var template = box.querySelector('p');
      if (template) {
        var raw = val('tentang_deskripsi').replace(/\r?\n/g, '\r\n');
        var parts = raw.split('\n\n');
        var old = box.querySelectorAll('p');
        for (var k = 0; k < old.length; k++) if (old[k] !== template) old[k].parentNode.removeChild(old[k]);
        setText(template, parts[0].trim());
        var anchor = template;
        for (var i = 1; i < parts.length; i++) {
          var p = template.cloneNode(false);
          p.textContent = parts[i].trim();
          anchor.parentNode.insertBefore(p, anchor.nextSibling);
          anchor = p;
        }
      }
    }

    var vals = about.querySelectorAll('#tentangIdentitasGrid .about-value');
    setText(vals[0], val('tentang_nama_resmi'));
    setText(vals[1], val('tentang_nama_lama'));
    setText(vals[2], val('tentang_fungsi_utama'));

    setText(about.querySelector('.moto-panel h3'), val('tentang_moto_judul'));
    setText(about.querySelector('.moto-desc'), val('tentang_moto_deskripsi'));
  }

  function footerList(d, id, index) {
    return d.getElementById(id) || d.querySelectorAll('footer .footer-links')[index] || null;
  }

  function syncKontak(d) {
    var ul = footerList(d, 'footerKontakList', 2);
    if (!ul) return;

    var alamat = val('alamat');
    var telepon = val('telepon_kontak');
    var website = val('website').trim();
    ul.innerHTML = '';

    // Sama dengan landing: baris yang kosong tidak ditampilkan sama sekali.
    if (alamat) { var a = d.createElement('li'); a.textContent = alamat; ul.appendChild(a); }
    if (telepon) { var t = d.createElement('li'); t.textContent = telepon; ul.appendChild(t); }
    if (website) {
      var w = d.createElement('li');
      var link = d.createElement('a');
      link.href = website;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.textContent = website.replace(/\/+$/, '').replace(/^https?:\/\//, '');
      w.appendChild(link);
      ul.appendChild(w);
    }
  }

  function syncSosial(d) {
    var ul = footerList(d, 'footerSosialList', 1);
    if (!ul) return;
    ul.innerHTML = '';

    for (var i = 0; ; i++) {
      var platform = field('sosial_platform_' + i);
      if (!platform) break;
      var url = (field('sosial_url_' + i) || {}).value;
      url = url ? String(url).trim() : '';
      if (!url) continue; // landing hanya menampilkan yang URL-nya terisi

      var label = field('sosial_label_' + i);
      var li = d.createElement('li');
      var link = d.createElement('a');
      link.href = url;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.innerHTML = SOSIAL_ICONS[platform.value] || '';
      link.appendChild(d.createTextNode(label ? label.value : ''));
      li.appendChild(link);
      ul.appendChild(li);
    }
  }

  function syncMakna(d) {
    var points = d.querySelectorAll('#maknaLogoStage .makna-logo-point');
    for (var i = 0; i < points.length; i++) {
      var judul = field('makna_logo_judul_' + i);
      if (!judul) continue;
      var ket = field('makna_logo_keterangan_' + i);
      setText(points[i].querySelector('.makna-logo-point-title'), judul.value || ('Poin ' + (i + 1)));
      setText(points[i].querySelector('.makna-logo-point-desc'), ket ? ket.value : '');
    }
  }

  // Latar hero. Sumber kebenarannya adalah kotak pratinjau kecil di editor
  // (#lpHeroImagePreviewImg / #lpHeroVideoPreviewVideo): berisi file tersimpan,
  // atau file yang baru dipilih (data URL / blob), dan disembunyikan bila kosong.
  function shown(frameEl) { return !!frameEl && frameEl.style.display !== 'none'; }

  function currentMediaUrl(imgId, frameId) {
    var el = byId(imgId);
    if (!el || !shown(byId(frameId))) return '';
    return el.getAttribute('src') || '';
  }

  function syncBackground(d) {
    var bg = d.querySelector('.hero-stats-bg');
    if (!bg) return;

    var checked = document.querySelector('[data-lp-bg-type-radio]:checked');
    var type = checked ? checked.value : 'gambar';
    var blurEl = field('hero_blur_level');
    var overlayEl = field('hero_overlay_intensity');
    var blur = Math.max(0, Math.min(20, parseInt(blurEl ? blurEl.value : 0, 10) || 0));
    var overlay = Math.max(0, Math.min(100, parseInt(overlayEl ? overlayEl.value : 100, 10) || 0));
    var imageUrl = currentMediaUrl('lpHeroImagePreviewImg', 'lpHeroImagePreviewFrame');
    var videoUrl = currentMediaUrl('lpHeroVideoPreviewVideo', 'lpHeroVideoPreviewFrame');

    // Aturan yang sama dengan welcome.blade.php: video dipakai bila tipe = video
    // DAN videonya ada; selain itu gambar dipakai bila ada.
    var useVideo = type === 'video' && !!videoUrl;
    var useImage = !useVideo && !!imageUrl;

    bg.style.setProperty('--hero-overlay-alpha', String(overlay / 100));

    var key = [useVideo ? 'v' : 'i', useImage ? imageUrl.length + imageUrl.slice(-40) : '-', blur].join('|');
    if (key !== lastBgKey || !d.getElementById('lp-live-bg')) {
      lastBgKey = key;
      var style = d.getElementById('lp-live-bg');
      if (!style) {
        style = d.createElement('style');
        style.id = 'lp-live-bg';
        d.head.appendChild(style);
      }
      style.textContent =
        // landing-content.js juga menaruh gambar latar di elemen ini; selalu
        // tertutup layer ::before, jadi aman dimatikan supaya draf tampil murni.
        '.hero-stats-bg{background-image:none !important;}' +
        '.hero-stats-bg::before{' +
          (useImage
            ? 'background-image:url("' + cssString(imageUrl) + '") !important;background-size:cover !important;background-position:center 58% !important;background-repeat:no-repeat !important;'
            : 'background-image:none !important;') +
          'filter:blur(' + blur + 'px) !important;}' +
        '.hero-bg-video{filter:blur(' + blur + 'px) !important;}';
    }

    var serverStage = d.getElementById('heroVideoStage');
    var ownStage = d.getElementById('lpLiveVideoStage');

    if (!useVideo) {
      if (serverStage) serverStage.style.display = 'none';
      if (ownStage) ownStage.parentNode.removeChild(ownStage);
      return;
    }

    // Video yang dipilih sama dengan yang tersimpan -> pakai stage asli landing
    // (lengkap dengan crossfade loop-nya).
    if (serverStage && serverStage.getAttribute('data-hero-video-src') === videoUrl) {
      serverStage.style.display = '';
      if (ownStage) ownStage.parentNode.removeChild(ownStage);
      return;
    }

    if (serverStage) serverStage.style.display = 'none';
    if (!ownStage) {
      ownStage = d.createElement('div');
      ownStage.id = 'lpLiveVideoStage';
      ownStage.className = 'hero-bg-video-stage';
      var video = d.createElement('video');
      video.className = 'hero-bg-video hero-bg-video-a';
      video.muted = true;
      video.loop = true;
      video.autoplay = true;
      video.setAttribute('playsinline', '');
      ownStage.appendChild(video);
      bg.insertBefore(ownStage, bg.firstChild);
    }
    var v = ownStage.querySelector('video');
    if (ownStage.getAttribute('data-src') !== videoUrl) {
      ownStage.setAttribute('data-src', videoUrl);
      v.src = videoUrl;
      var played = v.play && v.play();
      if (played && played.catch) played.catch(function () {});
    }
  }

  function run() {
    timer = 0;
    var d = iframeDoc();
    if (!ready || !d || !d.body) return;
    if (!field('hero_judul_awal')) return; // form editor belum ada di halaman
    safe(syncTheme, d);
    safe(syncHero, d);
    safe(syncFitur, d);
    safe(syncTentang, d);
    safe(syncKontak, d);
    safe(syncSosial, d);
    safe(syncMakna, d);
    safe(syncBackground, d);
    if (pendingFocus !== null) {
      var name = pendingFocus;
      pendingFocus = null;
      focus(name);
    }
  }

  function schedule(delay) {
    if (timer) clearTimeout(timer);
    timer = setTimeout(run, typeof delay === 'number' ? delay : 60);
  }

  // ------------------------------------------------------------ sorotan section

  function setMakna(open) {
    var d = iframeDoc();
    if (!ready || !d) return;
    var overlay = d.getElementById('maknaLogoOverlay');
    if (!overlay) return;
    var isOpen = overlay.classList.contains('open');
    if (open && !isOpen) {
      var trigger = d.getElementById('maknaLogoTrigger');
      if (trigger) { trigger.click(); maknaOpenedByUs = true; }
    } else if (!open && isOpen && maknaOpenedByUs) {
      var close = d.getElementById('maknaLogoClose');
      if (close) close.click();
      maknaOpenedByUs = false;
    } else if (!open) {
      maknaOpenedByUs = false;
    }
  }

  function focus(name) {
    var d = iframeDoc();
    if (!ready || !d) { pendingFocus = name || ''; return; }
    currentFocus = name || null;

    var prev = d.querySelector('.lp-live-focus');
    if (prev) prev.classList.remove('lp-live-focus');
    if (!name || !FOCUS_TARGETS[name]) return;

    var el = d.querySelector(FOCUS_TARGETS[name]);
    if (!el) return;
    el.classList.add('lp-live-focus');

    var win = frame.contentWindow;
    var top = name === 'beranda' ? 0 : Math.max(0, el.getBoundingClientRect().top + (win.pageYOffset || 0) - 76);
    if (win.scrollTo) win.scrollTo({ top: top, behavior: 'smooth' });
  }

  // ---------------------------------------------------------- dokumen iframe

  function prepareDoc(d) {
    if (d.getElementById('lp-live-style')) return;
    var style = d.createElement('style');
    style.id = 'lp-live-style';
    style.textContent =
      '.lp-live-focus{outline:2px solid #FF9800;outline-offset:-2px;transition:outline-color .2s ease;}' +
      'html{scroll-behavior:smooth;}';
    d.head.appendChild(style);

    // Pratinjau tidak boleh berpindah halaman (mis. jadi menampilkan dashboard
    // di dalam iframe): tautan non-hash diblokir, tautan "#login" tidak
    // membuka modal login, dan semua form dicegah submit.
    d.addEventListener('click', function (e) {
      var link = e.target && e.target.closest ? e.target.closest('a') : null;
      if (!link) return;
      var href = link.getAttribute('href') || '';
      if (href === '#login') { e.preventDefault(); e.stopImmediatePropagation(); return; }
      if (href.charAt(0) !== '#') e.preventDefault();
    }, true);
    d.addEventListener('submit', function (e) { e.preventDefault(); e.stopImmediatePropagation(); }, true);
  }

  function onFrameLoad() {
    var d = iframeDoc();
    ready = !!(d && d.querySelector && d.querySelector('.hero-stats-bg'));
    if (!ready) return;
    lastBgKey = '';
    maknaOpenedByUs = false;
    prepareDoc(d);
    schedule(0);
    // landing-content.js menerapkan config-nya secara async (fetch) SETELAH load;
    // sinkron ulang supaya draf tidak tertimpa.
    setTimeout(run, 900);
    setTimeout(run, 2600);
    fit();
  }

  // ------------------------------------------------------------ aktivasi & event

  function activate() {
    frame = byId(FRAME_ID);
    viewport = byId(VIEWPORT_ID);
    if (!frame || !viewport) return;
    if (!frame.getAttribute('src')) {
      // Iframe (halaman publik lengkap dgn JS-nya) sengaja baru dimuat saat tab
      // Pengaturan Umum dibuka, bukan tiap dashboard dibuka.
      frame.addEventListener('load', onFrameLoad);
      frame.setAttribute('src', frame.getAttribute('data-src') || '/?lp_preview=1');
    }
    fit();
  }

  function panelActive() {
    var p = document.querySelector(PANEL_SELECTOR);
    return !!p && p.classList.contains('active');
  }

  function bind() {
    var panel = document.querySelector(PANEL_SELECTOR);
    if (!panel || !byId(FRAME_ID)) return;

    if (panelActive()) activate();
    document.addEventListener('click', function (e) {
      if (e.target && e.target.closest && e.target.closest('[data-tab-link="pengaturan-umum"]')) setTimeout(activate, 0);
    });
    if (window.MutationObserver) {
      new MutationObserver(function () { if (panelActive()) activate(); })
        .observe(panel, { attributes: true, attributeFilter: ['class'] });
    }

    function inScope(t) { return t && t.closest && t.closest(EDIT_SCOPE); }
    document.addEventListener('input', function (e) { if (inScope(e.target)) schedule(); }, true);
    document.addEventListener('change', function (e) { if (inScope(e.target)) schedule(); }, true);
    // Tombol tambah/hapus baris (mis. sosial media) tidak memicu input/change.
    document.addEventListener('click', function (e) { if (inScope(e.target)) schedule(180); }, true);

    // Fokus ke field -> gulir iframe ke section terkait; field Makna Logo ->
    // buka modal Makna Logo di pratinjau supaya perubahan teksnya kelihatan.
    document.addEventListener('focusin', function (e) {
      var t = e.target;
      if (!inScope(t) || !t.closest) return;
      var pane = t.closest('[data-lp-tab-panel]');
      var name = pane ? pane.getAttribute('data-lp-tab-panel') : null;
      var isMakna = !!(t.getAttribute && /^makna_logo_/.test(t.getAttribute('data-lp') || ''));
      if (isMakna) { setMakna(true); return; }
      setMakna(false);
      if (name && name !== currentFocus) focus(name);
    });

    // Modal editor dibuka/ditutup -> sorot section yang sedang diedit.
    var backdrop = byId('lpLandingModalBackdrop');
    if (backdrop && window.MutationObserver) {
      new MutationObserver(function () {
        if (backdrop.classList.contains('is-open')) {
          var body = byId('lpLandingModalBody');
          var pane = body && body.querySelector('[data-lp-tab-panel]');
          if (pane) focus(pane.getAttribute('data-lp-tab-panel'));
        } else {
          setMakna(false);
          focus(null);
        }
      }).observe(backdrop, { attributes: true, attributeFilter: ['class'] });
    }

    // Gambar/video latar yang baru dipilih/dihapus diperbarui oleh kode editor
    // lewat kotak pratinjau kecil -- pantau kotak itu.
    if (window.MutationObserver) {
      var mo = new MutationObserver(function () { schedule(); });
      ['lpHeroImagePreviewImg', 'lpHeroImagePreviewFrame', 'lpHeroVideoPreviewVideo', 'lpHeroVideoPreviewFrame'].forEach(function (id) {
        var el = byId(id);
        if (el) mo.observe(el, { attributes: true, attributeFilter: ['src', 'style'] });
      });
      // Ganti tema admin -> iframe ikut.
      new MutationObserver(function () { schedule(0); })
        .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    }

    if (window.ResizeObserver && viewport) new ResizeObserver(fit).observe(viewport);
    window.addEventListener('resize', fit, { passive: true });
  }

  window.lpLivePreview = { activate: activate, refresh: schedule, focus: focus, fit: fit };

  function boot() {
    frame = byId(FRAME_ID);
    viewport = byId(VIEWPORT_ID);
    bind();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
})();
