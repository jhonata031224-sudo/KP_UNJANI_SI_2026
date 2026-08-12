(function () {
  'use strict';

  function boot() {
    var form = document.getElementById('landingForm');
    var preview = document.getElementById('lpPreview');
    var hero = document.getElementById('lpPreviewHero');
    if (!form || !preview || !hero) return;

    injectPreviewStyles();
    ensurePreviewShell();
    bindInputs();
    render();

    // Pastikan preview kembali ke posisi atas ketika editor dibuka/diubah.
    if (typeof preview.scrollTop === 'number') preview.scrollTop = 0;
  }

  function source(form, name) {
    return form.querySelector('[data-lp="' + name + '"]');
  }

  function value(form, name) {
    var el = source(form, name);
    return el ? String(el.value || '') : '';
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

  function ensurePreviewShell() {
    var preview = document.getElementById('lpPreview');
    var hero = document.getElementById('lpPreviewHero');
    if (!preview || !hero) return;

    if (!preview.querySelector('.lp-preview-header')) {
      var header = document.createElement('div');
      header.className = 'lp-preview-header';
      header.innerHTML =
        '<div class="lp-preview-brand">' +
          '<img src="' + getLogoUrl() + '" alt="Lambang Pussiberad" class="lp-preview-logo">' +
          '<div><strong>SIBER<span>AD</span></strong><small>PUSSIBERAD · TNI AD</small></div>' +
        '</div>' +
        '<nav class="lp-preview-nav" aria-label="Navigasi pratinjau">' +
          '<span class="active">Beranda</span><span>Fitur</span><span>Tentang</span><span>Kontak</span>' +
        '</nav>';
      preview.insertBefore(header, preview.firstChild);
    }

    var content = hero.querySelector('.lp-preview-hero-content');
    if (!content) {
      content = document.createElement('div');
      content.className = 'lp-preview-hero-content';

      var children = Array.prototype.slice.call(hero.children);
      children.forEach(function (child) {
        if (!child.classList.contains('lp-preview-cta')) content.appendChild(child);
      });
      hero.insertBefore(content, hero.firstChild);
    }

    if (!hero.querySelector('.lp-preview-cta')) {
      var cta = document.createElement('button');
      cta.type = 'button';
      cta.className = 'lp-preview-cta';
      cta.textContent = 'SELENGKAPNYA';
      hero.appendChild(cta);
    }
  }

  function getLogoUrl() {
    var logo = document.querySelector('.side-brand img');
    return logo && logo.src ? logo.src : '/images/logo-pussiberad.jpg';
  }

  function render() {
    var form = document.getElementById('landingForm');
    if (!form) return;

    setText('lpPvEyebrow', value(form, 'hero_eyebrow'), 'PUSSIBERAD SISTEM PENDUKUNG OPERASIONAL');
    setText('lpPvJudulAwal', value(form, 'hero_judul_awal'), 'SIBER');
    setText('lpPvJudulAksen', value(form, 'hero_judul_aksen'), 'AD');
    setText('lpPvSubjudul', value(form, 'hero_subjudul'), 'Sistem Informasi Berbasis Elektronik Angkatan Darat');
    setText('lpPvDeskripsi', value(form, 'hero_deskripsi'), 'Mendigitalisasi alur pelaporan kegiatan seluruh Satuan Pelaksana Pusat Siber Angkatan Darat dari input laporan di lapangan, verifikasi berjenjang, hingga visualisasi real-time bagi pengambil keputusan.');
    setText('lpPvTentang', value(form, 'tentang_deskripsi'));
    setText('lpPvMotoJudul', value(form, 'tentang_moto_judul'));
    setText('lpPvMoto', value(form, 'tentang_moto_deskripsi'));
    setText('lpPvAlamat', value(form, 'alamat'));
    setText('lpPvTelepon', value(form, 'telepon_kontak'));
    setText('lpPvEmail', value(form, 'email_kontak'));
    setText('lpPvWebsite', value(form, 'website'));

    var hero = document.getElementById('lpPreviewHero');
    if (hero) {
      hero.classList.add('lp-preview-hero-fixed');
      hero.style.setProperty('position', 'relative', 'important');
      hero.style.setProperty('overflow', 'hidden', 'important');

      ['lpPvEyebrow', 'lpPvJudulAwal', 'lpPvJudulAksen', 'lpPvSubjudul', 'lpPvDeskripsi'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
          el.style.setProperty('visibility', 'visible', 'important');
          el.style.setProperty('opacity', '1', 'important');
          el.style.setProperty('position', 'relative', 'important');
          el.style.setProperty('z-index', '3', 'important');
        }
      });
    }

    renderFeatures(form);
    renderSocial(form);
    renderHeroImage(form);
  }

  function renderFeatures(form) {
    var wrap = document.getElementById('lpPvFitur');
    if (!wrap) return;
    wrap.innerHTML = '';

    for (var i = 0; i < 4; i++) {
      var title = source(form, 'fitur_judul_' + i);
      var desc = source(form, 'fitur_deskripsi_' + i);
      if (!title) continue;

      var card = document.createElement('div');
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
      hero.style.backgroundImage = 'linear-gradient(160deg, rgba(248,250,252,.82), rgba(255,255,255,.64)), url("' + event.target.result + '")';
      hero.style.backgroundSize = 'cover';
      hero.style.backgroundPosition = 'center';
    };
    reader.readAsDataURL(file);
  }

  function bindInputs() {
    var form = document.getElementById('landingForm');
    if (!form || form.dataset.livePreviewBound === '1') return;
    form.dataset.livePreviewBound = '1';

    form.querySelectorAll('[data-lp], [data-lp-image]').forEach(function (el) {
      el.addEventListener('input', render);
      el.addEventListener('change', render);
    });
  }

  function injectPreviewStyles() {
    if (document.getElementById('siberad-live-preview-style-v2')) return;
    var style = document.createElement('style');
    style.id = 'siberad-live-preview-style-v2';
    style.textContent = '\
      .lp-preview{overflow:hidden!important;}\
      .lp-preview-header{display:flex!important;align-items:center;justify-content:space-between;gap:14px;padding:13px 18px;background:#fff;border-bottom:1px solid #e8edf2;min-height:62px;box-sizing:border-box;}\
      .lp-preview-brand{display:flex;align-items:center;gap:9px;min-width:0;}\
      .lp-preview-logo{width:34px;height:34px;border-radius:50%;object-fit:cover;flex:0 0 34px;}\
      .lp-preview-brand strong{display:block;font-family:var(--display,Arial,sans-serif);font-size:17px;line-height:1;text-transform:uppercase;color:#17212b;}\
      .lp-preview-brand strong span{color:#c97a00;}\
      .lp-preview-brand small{display:block;margin-top:3px;font-family:var(--mono,monospace);font-size:7px;letter-spacing:.13em;color:#7b8794;}\
      .lp-preview-nav{display:flex;align-items:center;gap:18px;flex-shrink:0;font-family:var(--mono,monospace);font-size:9px;color:#64748b;}\
      .lp-preview-nav span.active{color:#c97a00;}\
      .lp-preview-hero-fixed{position:relative!important;min-height:390px!important;padding:24px 24px 30px!important;display:flex!important;flex-direction:column!important;align-items:flex-start!important;justify-content:flex-start!important;box-sizing:border-box!important;overflow:hidden!important;background-color:#f5f7f9;}\
      .lp-preview-hero-content{position:relative!important;z-index:3!important;width:100%!important;display:flex!important;flex-direction:column!important;align-items:flex-start!important;justify-content:flex-start!important;}\
      .lp-preview-hero-fixed .lp-eyebrow{display:inline-block!important;visibility:visible!important;opacity:1!important;width:auto;max-width:100%;box-sizing:border-box;padding:7px 11px;border:1px solid rgba(201,122,0,.25);border-radius:8px;background:rgba(255,255,255,.92);font-size:9px!important;line-height:1.4!important;letter-spacing:.16em!important;margin:0 0 15px!important;color:#c97a00!important;white-space:normal!important;}\
      .lp-preview-hero-fixed .lp-h1{display:block!important;visibility:visible!important;opacity:1!important;position:relative!important;z-index:3!important;font-size:42px!important;line-height:.95!important;letter-spacing:-.02em;margin:0 0 12px!important;color:#17212b!important;white-space:normal!important;word-break:break-word!important;text-shadow:0 1px 2px rgba(255,255,255,.5);}\
      .lp-preview-hero-fixed .lp-h1 span,.lp-preview-hero-fixed .lp-h1 em{display:inline!important;visibility:visible!important;opacity:1!important;}\
      .lp-preview-hero-fixed .lp-h1 em{color:#c97a00!important;font-style:normal!important;}\
      .lp-preview-hero-fixed .lp-h2{display:block!important;visibility:visible!important;opacity:1!important;position:relative!important;z-index:3!important;font-size:15px!important;line-height:1.35!important;margin:0 0 11px!important;color:#17212b!important;font-weight:700!important;}\
      .lp-preview-hero-fixed .lp-p{display:block!important;visibility:visible!important;opacity:1!important;position:relative!important;z-index:3!important;font-size:11.5px!important;line-height:1.65!important;max-width:95%;color:#52606d!important;}\
      .lp-preview-cta{position:relative!important;z-index:3!important;margin-top:18px;border:0;border-radius:9px;background:#d98200;color:#111;padding:11px 17px;font-family:var(--mono,monospace);font-size:9px;font-weight:700;letter-spacing:.08em;cursor:default;box-shadow:0 8px 18px rgba(201,122,0,.22);}\
      @media(max-width:760px){.lp-preview-header{padding:11px 13px;}.lp-preview-nav{gap:8px;font-size:8px;}.lp-preview-hero-fixed{min-height:360px!important;padding:20px 18px 26px!important;}.lp-preview-hero-fixed .lp-h1{font-size:34px!important;}.lp-preview-hero-fixed .lp-h2{font-size:13px!important;}}\
    ';
    document.head.appendChild(style);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
