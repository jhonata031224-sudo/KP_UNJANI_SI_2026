(function () {
  'use strict';

  var zoom = 1;
  var fitScale = 1;
  var resizeTimer = null;
  var bound = false;

  function $(id) { return document.getElementById(id); }

  function inputValue(form, name, fallback) {
    var el = form && form.querySelector('[data-lp="' + name + '"]');
    var value = el ? String(el.value || '').trim() : '';
    return value || (fallback || '');
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (c) {
      return ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' })[c];
    });
  }

  function boot() {
    var form = $('landingForm');
    var viewport = $('lpPreview');
    if (!form || !viewport) return;

    installCss();
    build();
    bind(form);
    render(form);
    setTimeout(fit, 0);
    setTimeout(fit, 120);
    window.addEventListener('resize', scheduleFit, { passive: true });
  }

  function build() {
    var viewport = $('lpPreview');
    if (!viewport) return;

    viewport.classList.remove('lp-preview-zoomed');
    viewport.innerHTML =
      '<div id="lpPreviewStage" class="lpv4-stage">' +
        '<div id="lpPreviewCanvas" class="lpv4-canvas">' +
          '<header class="lpv4-header">' +
            '<div class="lpv4-brand">' +
              '<img class="lpv4-logo" src="' + escapeHtml(getLogo()) + '" alt="Lambang Pussiberad">' +
              '<div><strong>SIBER<span>AD</span></strong><small>PUSSIBERAD · TNI AD</small></div>' +
            '</div>' +
            '<nav><span class="active">Beranda</span><span>Fitur</span><span>Tentang</span><span>Kontak</span></nav>' +
          '</header>' +
          '<section class="lpv4-hero" id="lpv4Hero">' +
            '<div class="lpv4-overlay"></div>' +
            '<div class="lpv4-hero-content">' +
              '<div class="lpv4-eyebrow" id="lpv4Eyebrow"></div>' +
              '<h1><span id="lpv4Title1"></span><em id="lpv4Title2"></em></h1>' +
              '<h2 id="lpv4Subtitle"></h2>' +
              '<p id="lpv4Description"></p>' +
              '<button type="button">SELENGKAPNYA</button>' +
            '</div>' +
          '</section>' +
          '<section class="lpv4-section">' +
            '<div class="lpv4-kicker">FITUR UNGGULAN</div>' +
            '<div class="lpv4-features" id="lpv4Features"></div>' +
          '</section>' +
          '<section class="lpv4-section lpv4-about">' +
            '<div class="lpv4-kicker">TENTANG PUSSIBERAD</div>' +
            '<p id="lpv4About"></p>' +
            '<div class="lpv4-moto"><b id="lpv4MotoTitle"></b><span id="lpv4Moto"></span></div>' +
          '</section>' +
          '<footer class="lpv4-section lpv4-footer">' +
            '<div class="lpv4-kicker">TERHUBUNG</div>' +
            '<div class="lpv4-contact">' +
              '<div><b>Alamat</b><span id="lpv4Address"></span></div>' +
              '<div><b>Telepon</b><span id="lpv4Phone"></span></div>' +
              '<div><b>Email</b><span id="lpv4Email"></span></div>' +
              '<div><b>Website</b><span id="lpv4Website"></span></div>' +
            '</div>' +
            '<div id="lpv4Social" class="lpv4-social"></div>' +
          '</footer>' +
        '</div>' +
      '</div>' +
      '<div class="lpv4-zoom-controls" role="group" aria-label="Kontrol zoom preview">' +
        '<button type="button" data-v4-zoom="out" aria-label="Perkecil">−</button>' +
        '<button type="button" data-v4-zoom="fit" aria-label="Tampilkan seluruh landing page">Fit</button>' +
        '<span id="lpv4ZoomLabel">Fit</span>' +
        '<button type="button" data-v4-zoom="in" aria-label="Perbesar">+</button>' +
      '</div>';

    viewport.addEventListener('click', function (event) {
      var button = event.target.closest('[data-v4-zoom]');
      if (!button) return;
      var action = button.getAttribute('data-v4-zoom');
      if (action === 'fit') zoom = 1;
      if (action === 'in') zoom = Math.min(3, +(zoom + 0.25).toFixed(2));
      if (action === 'out') zoom = Math.max(0.5, +(zoom - 0.25).toFixed(2));
      applyScale();
    });
  }

  function getLogo() {
    var img = document.querySelector('.side-brand img');
    return img && img.src ? img.src : '/images/logo-pussiberad.jpg';
  }

  function set(id, text) {
    var el = $(id);
    if (el) el.textContent = text || '';
  }

  function render(form) {
    set('lpv4Eyebrow', inputValue(form, 'hero_eyebrow', 'PUSSIBERAD SISTEM PENDUKUNG OPERASIONAL'));
    set('lpv4Title1', inputValue(form, 'hero_judul_awal', 'SIBER'));
    set('lpv4Title2', inputValue(form, 'hero_judul_aksen', 'AD'));
    set('lpv4Subtitle', inputValue(form, 'hero_subjudul', 'Sistem Informasi Berbasis Elektronik Angkatan Darat'));
    set('lpv4Description', inputValue(form, 'hero_deskripsi', 'Mendigitalisasi alur pelaporan kegiatan seluruh Satuan Pelaksana Pusat Siber Angkatan Darat dari input laporan di lapangan, verifikasi berjenjang, hingga visualisasi real-time bagi pengambil keputusan.'));
    set('lpv4About', inputValue(form, 'tentang_deskripsi', 'Pusat Siber Angkatan Darat mendukung digitalisasi dan pengamanan informasi secara terintegrasi.'));
    set('lpv4MotoTitle', inputValue(form, 'tentang_moto_judul', 'SATRIA'));
    set('lpv4Moto', inputValue(form, 'tentang_moto_deskripsi', 'Kesatria atau pejuang yang gagah berani, jujur, dan membela kebenaran.'));
    set('lpv4Address', inputValue(form, 'alamat', 'Alamat belum diisi'));
    set('lpv4Phone', inputValue(form, 'telepon_kontak', 'Telepon belum diisi'));
    set('lpv4Email', inputValue(form, 'email_kontak', 'Email belum diisi'));
    set('lpv4Website', inputValue(form, 'website', 'Website belum diisi'));

    var features = $('lpv4Features');
    if (features) {
      features.innerHTML = '';
      for (var i = 0; i < 4; i++) {
        var title = form.querySelector('[data-lp="fitur_judul_' + i + '"]');
        var desc = form.querySelector('[data-lp="fitur_deskripsi_' + i + '"]');
        if (!title) continue;
        var card = document.createElement('article');
        var b = document.createElement('b');
        var p = document.createElement('span');
        b.textContent = String(title.value || '').trim() || ('Fitur ' + (i + 1));
        p.textContent = String(desc ? desc.value || '' : '').trim() || 'Deskripsi fitur';
        card.appendChild(b); card.appendChild(p); features.appendChild(card);
      }
    }

    var social = $('lpv4Social');
    if (social) {
      social.innerHTML = '';
      for (var j = 0; j < 20; j++) {
        var platform = form.querySelector('[data-lp="sosial_platform_' + j + '"]');
        if (!platform) break;
        var label = form.querySelector('[data-lp="sosial_label_' + j + '"]');
        var url = form.querySelector('[data-lp="sosial_url_' + j + '"]');
        var text = String((label && label.value) || (url && url.value) || '').trim();
        if (!text) continue;
        var chip = document.createElement('span');
        chip.textContent = text;
        social.appendChild(chip);
      }
    }

    var image = form.querySelector('[data-lp-image="hero_image"]');
    var hero = $('lpv4Hero');
    if (image && hero && image.files && image.files[0] && image.files[0].type.indexOf('image/') === 0) {
      var reader = new FileReader();
      reader.onload = function (e) { hero.style.backgroundImage = 'url("' + e.target.result + '")'; };
      reader.readAsDataURL(image.files[0]);
    }

    scheduleFit();
  }

  function bind(form) {
    if (bound) return;
    bound = true;
    form.querySelectorAll('[data-lp], [data-lp-image]').forEach(function (el) {
      el.addEventListener('input', function () { render(form); });
      el.addEventListener('change', function () { render(form); });
    });
  }

  function scheduleFit() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      applyScale();
    }, 20);
  }

  function fit() {
    zoom = 1;
    applyScale();
  }

  function applyScale() {
    var viewport = $('lpPreview');
    var stage = $('lpPreviewStage');
    var canvas = $('lpPreviewCanvas');
    if (!viewport || !stage || !canvas) return;

    // Ukur halaman pada ukuran natural, tanpa transform.
    canvas.style.transform = 'none';
    canvas.style.width = viewport.clientWidth + 'px';
    canvas.style.height = 'auto';
    stage.style.width = viewport.clientWidth + 'px';
    stage.style.height = 'auto';

    var naturalWidth = Math.max(canvas.offsetWidth, 1);
    var naturalHeight = Math.max(canvas.scrollHeight, canvas.offsetHeight, 1);
    var availableWidth = Math.max(viewport.clientWidth, 1);
    var availableHeight = Math.max(viewport.clientHeight, 1);

    fitScale = Math.min(availableWidth / naturalWidth, availableHeight / naturalHeight, 1);
    if (!isFinite(fitScale) || fitScale <= 0) fitScale = 1;

    var scale = Math.min(3, Math.max(0.05, fitScale * zoom));
    var scaledWidth = naturalWidth * scale;
    var scaledHeight = naturalHeight * scale;

    stage.style.width = Math.ceil(scaledWidth) + 'px';
    stage.style.height = Math.ceil(scaledHeight) + 'px';
    canvas.style.width = naturalWidth + 'px';
    canvas.style.height = naturalHeight + 'px';
    canvas.style.transform = 'scale(' + scale + ')';
    canvas.style.transformOrigin = 'top left';

    var isFit = zoom === 1;
    viewport.classList.toggle('lp-preview-zoomed', !isFit);
    var label = $('lpv4ZoomLabel');
    if (label) label.textContent = isFit ? 'Fit' : Math.round(zoom * 100) + '%';
  }

  function installCss() {
    if ($('siberad-live-preview-v4-style')) return;
    var style = document.createElement('style');
    style.id = 'siberad-live-preview-v4-style';
    style.textContent = '\
      #lpPreview.lpv4-viewport,#lpPreview{position:relative!important;width:100%!important;height:620px!important;min-height:420px!important;max-width:100%!important;box-sizing:border-box!important;overflow:hidden!important;overflow-x:hidden!important;overflow-y:hidden!important;background:#eef2f5!important;}\
      #lpPreview.lp-preview-zoomed{overflow:auto!important;overflow-x:auto!important;overflow-y:auto!important;}\
      #lpPreview .lpv4-stage{position:relative!important;margin:0!important;padding:0!important;box-sizing:border-box!important;}\
      #lpPreview .lpv4-canvas{position:relative!important;display:block!important;margin:0!important;padding:0!important;box-sizing:border-box!important;background:#fff;color:#17212b;font-family:var(--body,Arial,sans-serif);transform-origin:top left!important;will-change:transform;}\
      #lpPreview .lpv4-header{height:86px;display:flex;align-items:center;justify-content:space-between;gap:24px;padding:16px 34px;box-sizing:border-box;background:#fff;border-bottom:1px solid #e5eaf0;}\
      #lpPreview .lpv4-brand{display:flex;align-items:center;gap:13px;min-width:0;}\
      #lpPreview .lpv4-logo{width:52px;height:52px;flex:0 0 52px;border-radius:50%;object-fit:cover;}\
      #lpPreview .lpv4-brand strong{display:block;font-size:25px;line-height:1;font-weight:800;color:#17212b;letter-spacing:.01em;}\
      #lpPreview .lpv4-brand strong span{color:#c97a00;}\
      #lpPreview .lpv4-brand small{display:block;margin-top:6px;font:9px var(--mono,monospace);letter-spacing:.18em;color:#7b8794;}\
      #lpPreview .lpv4-header nav{display:flex;gap:26px;white-space:nowrap;font:11px var(--mono,monospace);color:#64748b;}\
      #lpPreview .lpv4-header nav .active{color:#c97a00;}\
      #lpPreview .lpv4-hero{position:relative;min-height:510px;padding:58px 64px 62px;box-sizing:border-box;background:#edf1f4 center/cover no-repeat;overflow:hidden;}\
      #lpPreview .lpv4-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(255,255,255,.95),rgba(255,255,255,.78) 48%,rgba(255,255,255,.55));z-index:1;}\
      #lpPreview .lpv4-hero-content{position:relative;z-index:2;max-width:880px;}\
      #lpPreview .lpv4-eyebrow{display:inline-block!important;padding:9px 14px;margin:0 0 20px;border:1px solid rgba(201,122,0,.28);border-radius:8px;background:rgba(255,255,255,.95);font:12px/1.35 var(--mono,monospace);letter-spacing:.16em;color:#c97a00;visibility:visible!important;opacity:1!important;}\
      #lpPreview .lpv4-hero h1{display:block!important;margin:0 0 16px;font-size:78px;line-height:.92;font-weight:800;letter-spacing:-.03em;color:#17212b;visibility:visible!important;opacity:1!important;}\
      #lpPreview .lpv4-hero h1 em{font-style:normal;color:#c97a00;}\
      #lpPreview .lpv4-hero h2{display:block!important;margin:0 0 16px;font-size:28px;line-height:1.25;color:#17212b;visibility:visible!important;opacity:1!important;}\
      #lpPreview .lpv4-hero p{display:block!important;margin:0;max-width:820px;font-size:17px;line-height:1.65;color:#52606d;visibility:visible!important;opacity:1!important;}\
      #lpPreview .lpv4-hero button{margin-top:26px;border:0;border-radius:10px;background:#d98200;color:#111;padding:15px 27px;font:700 13px var(--mono,monospace);letter-spacing:.08em;}\
      #lpPreview .lpv4-section{padding:42px 56px;box-sizing:border-box;background:#fff;border-top:1px solid #e5eaf0;}\
      #lpPreview .lpv4-kicker{margin-bottom:16px;font:700 12px var(--mono,monospace);letter-spacing:.15em;color:#c97a00;}\
      #lpPreview .lpv4-features{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}\
      #lpPreview .lpv4-features article{padding:20px;border:1px solid #e2e8f0;border-radius:11px;background:#f8fafc;box-sizing:border-box;}\
      #lpPreview .lpv4-features b{display:block;margin-bottom:7px;font-size:18px;color:#17212b;}\
      #lpPreview .lpv4-features span{display:block;font-size:13px;line-height:1.55;color:#64748b;}\
      #lpPreview .lpv4-about{background:#f8fafc;}\
      #lpPreview .lpv4-about>p{max-width:900px;margin:0;font-size:15px;line-height:1.65;color:#52606d;}\
      #lpPreview .lpv4-moto{margin-top:20px;display:flex;flex-direction:column;gap:5px;}\
      #lpPreview .lpv4-moto b{font-size:21px;color:#17212b;}\
      #lpPreview .lpv4-moto span{font-size:14px;line-height:1.55;color:#64748b;}\
      #lpPreview .lpv4-contact{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;}\
      #lpPreview .lpv4-contact>div{padding:15px;border:1px solid #e2e8f0;border-radius:9px;background:#f8fafc;box-sizing:border-box;}\
      #lpPreview .lpv4-contact b{display:block;margin-bottom:6px;font:700 10px var(--mono,monospace);letter-spacing:.1em;text-transform:uppercase;color:#c97a00;}\
      #lpPreview .lpv4-contact span{display:block;font-size:13px;line-height:1.5;color:#52606d;overflow-wrap:anywhere;}\
      #lpPreview .lpv4-social{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;}\
      #lpPreview .lpv4-social span{padding:6px 10px;border:1px solid #e2e8f0;border-radius:999px;font-size:11px;color:#64748b;background:#fff;}\
      #lpPreview .lpv4-zoom-controls{position:absolute;right:12px;bottom:12px;z-index:100;display:flex;align-items:center;gap:4px;padding:5px;border:1px solid #dbe3eb;border-radius:10px;background:rgba(255,255,255,.96);box-shadow:0 8px 22px rgba(15,23,42,.15);}\
      #lpPreview .lpv4-zoom-controls button{height:30px;min-width:30px;padding:0 8px;border:1px solid #dbe3eb;border-radius:7px;background:#fff;color:#334155;font:700 12px var(--mono,monospace);cursor:pointer;}\
      #lpPreview .lpv4-zoom-controls button:hover{border-color:#c97a00;color:#c97a00;}\
      #lpPreview .lpv4-zoom-controls span{min-width:42px;text-align:center;font:10px var(--mono,monospace);color:#64748b;}\
      @media(max-width:760px){#lpPreview{height:560px!important;}.lpv4-header{padding:14px 18px!important;}.lpv4-header nav{gap:10px!important;font-size:9px!important;}.lpv4-hero{padding:42px 32px 48px!important;}.lpv4-hero h1{font-size:58px!important;}.lpv4-hero h2{font-size:21px!important;}.lpv4-hero p{font-size:14px!important;}.lpv4-section{padding:32px 28px!important;}.lpv4-contact{grid-template-columns:1fr!important;}}\
    ';
    document.head.appendChild(style);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
})();
