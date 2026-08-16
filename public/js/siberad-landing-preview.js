(function () {
  'use strict';

  var zoomMultiplier = 1;
  var fitScale = 1;
  var resizeTimer = null;

  function boot() {
    var form = document.getElementById('landingForm');
    var preview = document.getElementById('lpPreview');
    if (!form || !preview) return;

    injectPreviewStyles();
    buildPreviewShell();
    buildZoomControls();
    bindInputs();
    render();
    requestFit();

    window.addEventListener('resize', requestFit, { passive: true });
  }

  function source(form, name) {
    return form.querySelector('[data-lp="' + name + '"]');
  }

  function value(form, name) {
    var el = source(form, name);
    return el ? String(el.value || '') : '';
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function setText(id, text, fallback) {
    var el = document.getElementById(id);
    if (!el) return;
    var clean = String(text || '').trim();
    el.textContent = clean || (fallback || '');
    el.style.setProperty('display', 'block', 'important');
    el.style.setProperty('visibility', 'visible', 'important');
    el.style.setProperty('opacity', '1', 'important');
  }

  function getLogoUrl() {
    var logo = document.querySelector('.side-brand img');
    return logo && logo.src ? logo.src : '/images/logo-pussiberad.jpg';
  }

  function buildPreviewShell() {
    var preview = document.getElementById('lpPreview');
    if (!preview) return;

    if (preview.dataset.fullLandingShell !== '1') {
      preview.dataset.fullLandingShell = '1';
      preview.innerHTML = '';

      var canvas = document.createElement('div');
      canvas.id = 'lpPreviewCanvas';
      canvas.className = 'lp-preview-canvas';
      canvas.innerHTML =
        '<header class="lp-preview-header">' +
          '<div class="lp-preview-brand">' +
            '<img src="' + escapeHtml(getLogoUrl()) + '" alt="Lambang Pussiberad" class="lp-preview-logo">' +
            '<div><strong>SIBER<span>AD</span></strong><small>PUSSIBERAD · TNI AD</small></div>' +
          '</div>' +
          '<nav class="lp-preview-nav" aria-label="Navigasi pratinjau">' +
            '<span class="active">Beranda</span><span>Fitur</span><span>Tentang</span><span>Kontak</span>' +
          '</nav>' +
        '</header>' +
        '<section class="lp-preview-hero-full" id="lpPreviewHero" data-lp-preview-section="beranda">' +
          '<div class="lp-preview-hero-overlay"></div>' +
          '<div class="lp-preview-hero-content">' +
            '<div class="lp-eyebrow" id="lpPvEyebrow">PUSSIBERAD SISTEM PENDUKUNG OPERASIONAL</div>' +
            '<h1 class="lp-h1"><span id="lpPvJudulAwal">SIBER</span><em id="lpPvJudulAksen">AD</em></h1>' +
            '<h2 class="lp-h2" id="lpPvSubjudul">Sistem Informasi Berbasis Elektronik Angkatan Darat</h2>' +
            '<p class="lp-p" id="lpPvDeskripsi">Mendigitalisasi alur pelaporan kegiatan seluruh Satuan Pelaksana Pusat Siber Angkatan Darat dari input laporan di lapangan, verifikasi berjenjang, hingga visualisasi real-time bagi pengambil keputusan.</p>' +
            '<button type="button" class="lp-preview-cta">SELENGKAPNYA</button>' +
          '</div>' +
        '</section>' +
        '<section class="lp-preview-section" data-lp-preview-section="fitur">' +
          '<div class="lp-section-title">FITUR UNGGULAN</div>' +
          '<div class="lp-features" id="lpPvFitur"></div>' +
        '</section>' +
        '<section class="lp-preview-section lp-about" data-lp-preview-section="tentang">' +
          '<div class="lp-section-title">TENTANG PUSSIBERAD</div>' +
          '<p class="lp-p" id="lpPvTentang"></p>' +
          '<h3 class="lp-moto-title" id="lpPvMotoJudul"></h3>' +
          '<p class="lp-p" id="lpPvMoto"></p>' +
        '</section>' +
        '<footer class="lp-preview-section lp-footer" data-lp-preview-section="kontak">' +
          '<div class="lp-section-title">TERHUBUNG</div>' +
          '<div class="lp-contact-grid">' +
            '<div><b>Alamat</b><span id="lpPvAlamat"></span></div>' +
            '<div><b>Telepon</b><span id="lpPvTelepon"></span></div>' +
            '<div><b>Email</b><span id="lpPvEmail"></span></div>' +
            '<div><b>Website</b><span id="lpPvWebsite"></span></div>' +
          '</div>' +
          '<div class="lp-sosial-list" id="lpPvSosial"></div>' +
        '</footer>';

      preview.appendChild(canvas);
    }

    var hero = document.getElementById('lpPreviewHero');
    if (hero) hero.dataset.lpPreviewSection = 'beranda';
  }

  function buildZoomControls() {
    var preview = document.getElementById('lpPreview');
    if (!preview || preview.querySelector('.lp-preview-zoom')) return;

    var controls = document.createElement('div');
    controls.className = 'lp-preview-zoom';
    controls.innerHTML =
      '<button type="button" data-zoom-action="out" aria-label="Perkecil preview">−</button>' +
      '<button type="button" data-zoom-action="fit" aria-label="Pas ke layar">Fit</button>' +
      '<span data-zoom-label>Fit</span>' +
      '<button type="button" data-zoom-action="in" aria-label="Perbesar preview">+</button>';
    preview.appendChild(controls);

    controls.addEventListener('click', function (event) {
      var button = event.target.closest('[data-zoom-action]');
      if (!button) return;
      var action = button.getAttribute('data-zoom-action');
      if (action === 'fit') {
        zoomMultiplier = 1;
      } else if (action === 'in') {
        zoomMultiplier = Math.min(3, +(zoomMultiplier + 0.25).toFixed(2));
      } else if (action === 'out') {
        zoomMultiplier = Math.max(0.5, +(zoomMultiplier - 0.25).toFixed(2));
      }
      applyScale();
    });
  }

  function requestFit() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      applyScale();
    }, 30);
  }

  function applyScale() {
    var preview = document.getElementById('lpPreview');
    var canvas = document.getElementById('lpPreviewCanvas');
    if (!preview || !canvas) return;

    var isMobile = window.matchMedia && window.matchMedia('(max-width: 760px)').matches;

    canvas.style.transform = 'none';
    canvas.style.height = 'auto';

    // Mobile: pertahankan canvas lebih lebar dari viewport supaya landing page
    // bisa dilihat utuh melalui scrollbar horizontal di dalam preview.
    if (isMobile) {
      var mobileWidth = Math.round(760 * zoomMultiplier);
      canvas.style.setProperty('width', mobileWidth + 'px', 'important');
      canvas.style.setProperty('min-width', mobileWidth + 'px', 'important');
      canvas.style.setProperty('max-width', 'none', 'important');
      preview.style.setProperty('overflow-x', 'auto', 'important');
      preview.style.setProperty('overflow-y', 'auto', 'important');
      preview.style.setProperty('-webkit-overflow-scrolling', 'touch');

      var mobileNaturalHeight = Math.max(canvas.scrollHeight, 1);
      preview.style.setProperty('--lp-scale-width', mobileWidth + 'px');
      preview.style.setProperty('--lp-scale-height', mobileNaturalHeight + 'px');

      var mobileLabel = preview.querySelector('[data-zoom-label]');
      if (mobileLabel) mobileLabel.textContent = zoomMultiplier === 1 ? 'Fit' : Math.round(zoomMultiplier * 100) + '%';
      return;
    }

    // Desktop: pertahankan perilaku fit yang sudah aman dan tidak diubah.
    canvas.style.removeProperty('width');
    canvas.style.removeProperty('min-width');
    canvas.style.removeProperty('max-width');

    var naturalWidth = Math.max(canvas.scrollWidth, 1);
    var naturalHeight = Math.max(canvas.scrollHeight, 1);
    var availableWidth = Math.max(preview.clientWidth - 8, 1);
    var availableHeight = Math.max(preview.clientHeight - 8, 1);

    fitScale = Math.min(availableWidth / naturalWidth, availableHeight / naturalHeight, 1);
    if (!isFinite(fitScale) || fitScale <= 0) fitScale = 1;

    var scale = Math.min(3, Math.max(0.05, fitScale * zoomMultiplier));
    var scaledWidth = naturalWidth * scale;
    var scaledHeight = naturalHeight * scale;

    canvas.style.width = naturalWidth + 'px';
    canvas.style.height = naturalHeight + 'px';
    canvas.style.transform = 'scale(' + scale + ')';
    canvas.style.transformOrigin = 'top left';

    preview.style.setProperty('--lp-scale-width', scaledWidth + 'px');
    preview.style.setProperty('--lp-scale-height', scaledHeight + 'px');
    preview.style.removeProperty('overflow-x');
    preview.style.removeProperty('overflow-y');

    var label = preview.querySelector('[data-zoom-label]');
    if (label) label.textContent = zoomMultiplier === 1 ? 'Fit' : Math.round(zoomMultiplier * 100) + '%';
  }

  function render() {
    var form = document.getElementById('landingForm');
    if (!form) return;

    setText('lpPvEyebrow', value(form, 'hero_eyebrow'), 'PUSSIBERAD SISTEM PENDUKUNG OPERASIONAL');
    setText('lpPvJudulAwal', value(form, 'hero_judul_awal'), 'SIBER');
    setText('lpPvJudulAksen', value(form, 'hero_judul_aksen'), 'AD');
    setText('lpPvSubjudul', value(form, 'hero_subjudul'), 'Sistem Informasi Berbasis Elektronik Angkatan Darat');
    setText('lpPvDeskripsi', value(form, 'hero_deskripsi'), 'Mendigitalisasi alur pelaporan kegiatan seluruh Satuan Pelaksana Pusat Siber Angkatan Darat dari input laporan di lapangan, verifikasi berjenjang, hingga visualisasi real-time bagi pengambil keputusan.');
    setText('lpPvTentang', value(form, 'tentang_deskripsi'), 'Pusat Siber Angkatan Darat mendukung digitalisasi dan pengamanan informasi secara terintegrasi.');
    setText('lpPvMotoJudul', value(form, 'tentang_moto_judul'), 'SATRIA');
    setText('lpPvMoto', value(form, 'tentang_moto_deskripsi'), 'Kesatria atau pejuang yang gagah berani, jujur, dan membela kebenaran.');
    setText('lpPvAlamat', value(form, 'alamat'), 'Alamat belum diisi');
    setText('lpPvTelepon', value(form, 'telepon_kontak'), 'Telepon belum diisi');
    setText('lpPvEmail', value(form, 'email_kontak'), 'Email belum diisi');
    setText('lpPvWebsite', value(form, 'website'), 'Website belum diisi');

    renderFeatures(form);
    renderSocial(form);
    renderHeroImage(form);
    applyScale();
  }

  function renderFeatures(form) {
    var wrap = document.getElementById('lpPvFitur');
    if (!wrap) return;
    wrap.innerHTML = '';

    for (var i = 0; i < 4; i++) {
      var title = source(form, 'fitur_judul_' + i);
      var desc = source(form, 'fitur_deskripsi_' + i);
      if (!title) continue;

      var card = document.createElement('article');
      card.className = 'lp-feature-card';
      var b = document.createElement('b');
      var span = document.createElement('span');
      b.textContent = String(title.value || '').trim() || ('Judul fitur ' + (i + 1));
      span.textContent = String(desc ? desc.value || '' : '').trim() || 'Deskripsi fitur';
      card.appendChild(b);
      card.appendChild(span);
      wrap.appendChild(card);
    }
  }

  function renderSocial(form) {
    var wrap = document.getElementById('lpPvSosial');
    if (!wrap) return;
    wrap.innerHTML = '';

    for (var i = 0; i < 20; i++) {
      var platform = source(form, 'sosial_platform_' + i);
      if (!platform) break;
      var label = source(form, 'sosial_label_' + i);
      var url = source(form, 'sosial_url_' + i);
      var labelText = String(label ? label.value || '' : '').trim();
      var urlText = String(url ? url.value || '' : '').trim();
      if (!labelText && !urlText) continue;

      var chip = document.createElement('span');
      chip.className = 'lp-sosial-chip';
      chip.textContent = labelText || urlText;
      wrap.appendChild(chip);
    }
  }

  function renderHeroImage(form) {
    var input = form.querySelector('[data-lp-image="hero_image"]');
    var hero = document.getElementById('lpPreviewHero');
    if (!input || !hero || !input.files || !input.files[0]) return;
    var file = input.files[0];
    if (!file.type || file.type.indexOf('image/') !== 0) return;

    var reader = new FileReader();
    reader.onload = function (event) {
      hero.style.backgroundImage = 'url("' + event.target.result + '")';
      hero.style.backgroundSize = 'cover';
      hero.style.backgroundPosition = 'center';
    };
    reader.readAsDataURL(file);
  }

  function bindInputs() {
    var form = document.getElementById('landingForm');
    if (!form || form.dataset.livePreviewBoundV3 === '1') return;
    form.dataset.livePreviewBoundV3 = '1';

    form.querySelectorAll('[data-lp], [data-lp-image]').forEach(function (el) {
      el.addEventListener('input', render);
      el.addEventListener('change', render);
    });
  }

  function injectPreviewStyles() {
    if (document.getElementById('siberad-live-preview-style-v3')) return;
    var style = document.createElement('style');
    style.id = 'siberad-live-preview-style-v3';
    style.textContent = '\
      .lp-preview{position:relative!important;height:620px!important;min-height:420px!important;overflow:auto!important;background:#f5f7f9!important;box-sizing:border-box!important;scroll-behavior:smooth;}\
      .lp-preview-canvas{position:relative!important;display:block!important;width:100%;min-width:0;background:#fff;color:#17212b;font-family:var(--body,Arial,sans-serif);transform-origin:top left!important;box-sizing:border-box;will-change:transform;}\
      .lp-preview-header{display:flex!important;align-items:center;justify-content:space-between;gap:20px;padding:20px 30px;background:#fff;border-bottom:1px solid #e5eaf0;min-height:86px;box-sizing:border-box;}\
      .lp-preview-brand{display:flex;align-items:center;gap:14px;min-width:0;}\
      .lp-preview-logo{width:52px;height:52px;border-radius:50%;object-fit:cover;flex:0 0 52px;box-shadow:0 0 0 1px rgba(201,122,0,.18);}\
      .lp-preview-brand strong{display:block;font-family:var(--display,Arial,sans-serif);font-size:24px;line-height:1;text-transform:uppercase;color:#17212b;letter-spacing:.01em;}\
      .lp-preview-brand strong span{color:#c97a00;}\
      .lp-preview-brand small{display:block;margin-top:6px;font-family:var(--mono,monospace);font-size:9px;letter-spacing:.18em;color:#7b8794;}\
      .lp-preview-nav{display:flex;align-items:center;gap:28px;flex-shrink:0;font-family:var(--mono,monospace);font-size:11px;color:#64748b;}\
      .lp-preview-nav span.active{color:#c97a00;}\
      .lp-preview-hero-full{position:relative!important;min-height:520px!important;padding:58px 64px 64px!important;display:flex!important;align-items:flex-start!important;box-sizing:border-box!important;overflow:hidden!important;background:#eef2f5 center/cover no-repeat;}\
      .lp-preview-hero-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(255,255,255,.94) 0%,rgba(255,255,255,.80) 46%,rgba(255,255,255,.56) 100%);z-index:1;}\
      .lp-preview-hero-content{position:relative!important;z-index:3!important;width:min(850px,100%)!important;display:flex!important;flex-direction:column!important;align-items:flex-start!important;}\
      .lp-eyebrow{display:inline-block!important;visibility:visible!important;opacity:1!important;width:auto!important;max-width:100%;box-sizing:border-box;padding:9px 15px;border:1px solid rgba(201,122,0,.28);border-radius:8px;background:rgba(255,255,255,.94);font-family:var(--mono,monospace);font-size:12px!important;line-height:1.4!important;letter-spacing:.18em!important;margin:0 0 22px!important;color:#c97a00!important;white-space:normal!important;}\
      .lp-h1{display:block!important;visibility:visible!important;opacity:1!important;font-family:var(--display,Arial,sans-serif);font-size:76px!important;font-weight:800!important;line-height:.92!important;letter-spacing:-.025em;margin:0 0 18px!important;color:#17212b!important;text-transform:uppercase!important;white-space:normal!important;word-break:break-word!important;text-shadow:0 1px 2px rgba(255,255,255,.65);}\
      .lp-h1 span,.lp-h1 em{display:inline!important;visibility:visible!important;opacity:1!important;}\
      .lp-h1 em{color:#c97a00!important;font-style:normal!important;}\
      .lp-h2{display:block!important;visibility:visible!important;opacity:1!important;font-family:var(--body,Arial,sans-serif);font-size:28px!important;line-height:1.25!important;margin:0 0 17px!important;color:#17212b!important;font-weight:700!important;}\
      .lp-p{display:block!important;visibility:visible!important;opacity:1!important;font-family:var(--body,Arial,sans-serif);font-size:17px!important;line-height:1.7!important;color:#52606d!important;white-space:pre-line!important;margin:0;}\
      .lp-preview-cta{position:relative!important;z-index:3!important;margin-top:28px;border:0;border-radius:11px;background:#d98200;color:#111;padding:16px 28px;font-family:var(--mono,monospace);font-size:13px;font-weight:700;letter-spacing:.09em;cursor:default;box-shadow:0 10px 24px rgba(201,122,0,.24);}\
      .lp-preview-section{padding:48px 56px;background:#fff;border-top:1px solid #e5eaf0;box-sizing:border-box;}\
      .lp-section-title{font-family:var(--mono,monospace);font-size:13px;letter-spacing:.16em;text-transform:uppercase;color:#c97a00;margin-bottom:18px;font-weight:700;}\
      .lp-features{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;background:transparent!important;}\
      .lp-feature-card{background:#f8fafc!important;border:1px solid #e2e8f0;border-radius:12px;padding:24px!important;box-sizing:border-box;}\
      .lp-feature-card b{display:block;font-family:var(--display,Arial,sans-serif);font-size:20px;margin-bottom:9px;color:#17212b;}\
      .lp-feature-card span{display:block;font-family:var(--body,Arial,sans-serif);font-size:14px;color:#64748b;line-height:1.6;}\
      .lp-about{background:#f8fafc!important;}\
      .lp-about .lp-p{max-width:900px;}\
      .lp-moto-title{font-family:var(--display,Arial,sans-serif);font-size:25px;font-weight:800;text-transform:uppercase;margin:24px 0 8px;color:#17212b;}\
      .lp-footer{background:#fff!important;}\
      .lp-contact-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;}\
      .lp-contact-grid>div{padding:17px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;min-width:0;}\
      .lp-contact-grid b{display:block;font-family:var(--mono,monospace);font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:#c97a00;margin-bottom:7px;}\
      .lp-contact-grid span{display:block;font-size:14px;line-height:1.5;color:#52606d;overflow-wrap:anywhere;}\
      .lp-sosial-list{display:flex;flex-wrap:wrap;gap:9px;margin-top:18px;}\
      .lp-sosial-chip{display:inline-flex;align-items:center;font-size:12px;color:#52606d;border:1px solid #e2e8f0;border-radius:999px;padding:7px 12px;background:#fff;}\
      .lp-preview-zoom{position:sticky;bottom:12px;margin:-52px 12px 12px auto;width:max-content;z-index:50;display:flex;align-items:center;gap:4px;padding:5px;border:1px solid rgba(203,213,225,.95);border-radius:10px;background:rgba(255,255,255,.94);box-shadow:0 8px 22px rgba(15,23,42,.15);backdrop-filter:blur(8px);}\
      .lp-preview-zoom button{height:30px;min-width:30px;padding:0 8px;border:1px solid #dbe3eb;border-radius:7px;background:#fff;color:#334155;font-family:var(--mono,monospace);font-size:12px;font-weight:700;cursor:pointer;}\
      .lp-preview-zoom button:hover{border-color:#c97a00;color:#c97a00;}\
      .lp-preview-zoom span{min-width:42px;text-align:center;font-family:var(--mono,monospace);font-size:10px;color:#64748b;}\
      @media(max-width:760px){.lp-preview{height:560px!important;}.lp-preview-header{padding:15px 18px;}.lp-preview-logo{width:40px;height:40px;flex-basis:40px;}.lp-preview-brand strong{font-size:19px;}.lp-preview-nav{gap:10px;font-size:9px;}.lp-preview-hero-full{min-height:450px!important;padding:42px 34px 48px!important;}.lp-h1{font-size:58px!important;}.lp-h2{font-size:21px!important;}.lp-p{font-size:14px!important;}.lp-preview-section{padding:34px 30px;}.lp-contact-grid{grid-template-columns:1fr;}}\
    ';
    document.head.appendChild(style);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
