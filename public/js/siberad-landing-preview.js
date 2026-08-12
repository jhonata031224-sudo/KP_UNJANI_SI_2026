(function () {
  'use strict';

  function boot() {
    var form = document.getElementById('landingForm');
    var preview = document.getElementById('lpPreview');
    if (!form || !preview) return;

    injectPreviewStyles();
    ensurePreviewShell();

    var textMap = {
      hero_eyebrow: 'lpPvEyebrow',
      hero_judul_awal: 'lpPvJudulAwal',
      hero_judul_aksen: 'lpPvJudulAksen',
      hero_subjudul: 'lpPvSubjudul',
      hero_deskripsi: 'lpPvDeskripsi',
      tentang_deskripsi: 'lpPvTentang',
      tentang_moto_judul: 'lpPvMotoJudul',
      tentang_moto_deskripsi: 'lpPvMoto',
      alamat: 'lpPvAlamat',
      telepon_kontak: 'lpPvTelepon',
      email_kontak: 'lpPvEmail',
      website: 'lpPvWebsite'
    };

    function source(name) {
      return form.querySelector('[data-lp="' + name + '"]');
    }

    function value(name) {
      var el = source(name);
      return el ? String(el.value || '') : '';
    }

    function setText(targetId, text, fallback) {
      var target = document.getElementById(targetId);
      if (!target) return;
      var clean = String(text || '').trim();
      target.textContent = clean || (fallback || '');
      target.style.opacity = clean ? '1' : '.45';
      target.style.visibility = 'visible';
      target.style.display = '';
    }

    function ensurePreviewShell() {
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

      var hero = document.getElementById('lpPreviewHero');
      if (hero && !hero.querySelector('.lp-preview-cta')) {
        var cta = document.createElement('button');
        cta.type = 'button';
        cta.className = 'lp-preview-cta';
        cta.textContent = 'SELENGKAPNYA';
        hero.appendChild(cta);
      }
    }

    function getLogoUrl() {
      var dashboardLogo = document.querySelector('.side-brand img');
      return dashboardLogo && dashboardLogo.src ? dashboardLogo.src : '/images/logo-pussiberad.jpg';
    }

    function renderFeatures() {
      var wrap = document.getElementById('lpPvFitur');
      if (!wrap) return;
      wrap.innerHTML = '';

      for (var i = 0; i < 4; i++) {
        var title = source('fitur_judul_' + i);
        var desc = source('fitur_deskripsi_' + i);
        if (!title) continue;

        var card = document.createElement('div');
        card.className = 'lp-feature-card';

        var titleEl = document.createElement('b');
        titleEl.textContent = String(title.value || '').trim() || ('Judul fitur ' + (i + 1));
        var descEl = document.createElement('span');
        descEl.textContent = String(desc ? desc.value || '' : '').trim() || 'Deskripsi fitur';

        card.appendChild(titleEl);
        card.appendChild(descEl);
        wrap.appendChild(card);
      }
    }

    function renderSocial() {
      var wrap = document.getElementById('lpPvSosial');
      if (!wrap) return;
      wrap.innerHTML = '';

      for (var i = 0; i < 20; i++) {
        var platform = source('sosial_platform_' + i);
        if (!platform) break;
        var label = source('sosial_label_' + i);
        var url = source('sosial_url_' + i);
        var labelText = String(label ? label.value || '' : '').trim();
        var urlText = String(url ? url.value || '' : '').trim();
        if (!labelText && !urlText) continue;

        var chip = document.createElement('span');
        chip.className = 'lp-sosial-chip';
        chip.textContent = labelText || urlText;
        wrap.appendChild(chip);
      }
    }

    function renderHeroImage() {
      var input = form.querySelector('[data-lp-image="hero_image"]');
      var hero = document.getElementById('lpPreviewHero');
      if (!input || !hero) return;

      if (!input.files || !input.files[0]) return;
      var file = input.files[0];
      if (!file.type || file.type.indexOf('image/') !== 0) return;

      var reader = new FileReader();
      reader.onload = function (event) {
        hero.style.backgroundImage = 'linear-gradient(135deg, rgba(248,250,252,.78), rgba(255,255,255,.62)), url("' + event.target.result + '")';
        hero.style.backgroundSize = 'cover';
        hero.style.backgroundPosition = 'center';
      };
      reader.readAsDataURL(file);
    }

    function render() {
      setText(textMap.hero_eyebrow, value('hero_eyebrow'), 'PUSSIBERAD SISTEM PENDUKUNG OPERASIONAL');
      setText(textMap.hero_judul_awal, value('hero_judul_awal'), 'SIBER');
      setText(textMap.hero_judul_aksen, value('hero_judul_aksen'), 'AD');
      setText(textMap.hero_subjudul, value('hero_subjudul'), 'Sistem Informasi Berbasis Elektronik Angkatan Darat');
      setText(textMap.hero_deskripsi, value('hero_deskripsi'), 'Mendigitalisasi alur pelaporan kegiatan seluruh Satuan Pelaksana Pusat Siber Angkatan Darat dari input laporan di lapangan, verifikasi berjenjang, hingga visualisasi real-time bagi pengambil keputusan.');
      setText(textMap.tentang_deskripsi, value('tentang_deskripsi'));
      setText(textMap.tentang_moto_judul, value('tentang_moto_judul'));
      setText(textMap.tentang_moto_deskripsi, value('tentang_moto_deskripsi'));
      setText(textMap.alamat, value('alamat'));
      setText(textMap.telepon_kontak, value('telepon_kontak'));
      setText(textMap.email_kontak, value('email_kontak'));
      setText(textMap.website, value('website'));

      var hero = document.getElementById('lpPreviewHero');
      if (hero) {
        hero.classList.add('lp-preview-hero-fixed');
        var h1 = hero.querySelector('.lp-h1');
        var h2 = hero.querySelector('.lp-h2');
        var eyebrow = hero.querySelector('.lp-eyebrow');
        if (eyebrow) { eyebrow.style.display = 'inline-block'; eyebrow.style.visibility = 'visible'; }
        if (h1) { h1.style.display = 'block'; h1.style.visibility = 'visible'; h1.style.opacity = '1'; }
        if (h2) { h2.style.display = 'block'; h2.style.visibility = 'visible'; h2.style.opacity = '1'; }
      }

      renderFeatures();
      renderSocial();
      renderHeroImage();
    }

    form.querySelectorAll('[data-lp], [data-lp-image]').forEach(function (el) {
      el.addEventListener('input', render);
      el.addEventListener('change', render);
    });

    render();
    window.requestAnimationFrame(render);
  }

  function injectPreviewStyles() {
    if (document.getElementById('siberad-live-preview-style')) return;
    var style = document.createElement('style');
    style.id = 'siberad-live-preview-style';
    style.textContent = '\
      .lp-preview{overflow:hidden;}\
      .lp-preview-header{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:13px 18px;background:#fff;border-bottom:1px solid #e8edf2;min-height:62px;box-sizing:border-box;}\
      .lp-preview-brand{display:flex;align-items:center;gap:9px;min-width:0;}\
      .lp-preview-logo{width:34px;height:34px;border-radius:50%;object-fit:cover;flex:0 0 34px;}\
      .lp-preview-brand strong{display:block;font-family:var(--display,Arial,sans-serif);font-size:17px;line-height:1;text-transform:uppercase;color:#17212b;letter-spacing:.02em;}\
      .lp-preview-brand strong span{color:#c97a00;}\
      .lp-preview-brand small{display:block;margin-top:3px;font-family:var(--mono,monospace);font-size:7px;letter-spacing:.13em;color:#7b8794;}\
      .lp-preview-nav{display:flex;align-items:center;gap:18px;flex-shrink:0;font-family:var(--mono,monospace);font-size:9px;color:#64748b;}\
      .lp-preview-nav span.active{color:#c97a00;}\
      .lp-preview-hero-fixed{min-height:360px;padding:26px 24px 28px!important;display:flex;flex-direction:column;align-items:flex-start;justify-content:center;box-sizing:border-box;background-color:#f5f7f9;}\
      .lp-preview-hero-fixed .lp-eyebrow{display:inline-block!important;width:auto;max-width:100%;box-sizing:border-box;padding:7px 11px;border:1px solid rgba(201,122,0,.25);border-radius:8px;background:rgba(255,255,255,.9);font-size:9px!important;letter-spacing:.16em!important;margin-bottom:15px!important;color:#c97a00!important;white-space:normal;}\
      .lp-preview-hero-fixed .lp-h1{font-size:42px!important;line-height:.95!important;letter-spacing:-.02em;margin:0 0 12px!important;color:#17212b!important;white-space:normal;word-break:break-word;}\
      .lp-preview-hero-fixed .lp-h1 em{color:#c97a00!important;}\
      .lp-preview-hero-fixed .lp-h2{font-size:15px!important;line-height:1.35!important;margin:0 0 11px!important;color:#17212b!important;font-weight:700!important;}\
      .lp-preview-hero-fixed .lp-p{font-size:11.5px!important;line-height:1.65!important;max-width:95%;color:#52606d!important;}\
      .lp-preview-cta{margin-top:18px;border:0;border-radius:9px;background:#d98200;color:#111;padding:11px 17px;font-family:var(--mono,monospace);font-size:9px;font-weight:700;letter-spacing:.08em;cursor:default;box-shadow:0 8px 18px rgba(201,122,0,.22);}\
      @media(max-width:760px){.lp-preview-header{padding:11px 13px;}.lp-preview-nav{gap:8px;font-size:8px;}.lp-preview-hero-fixed{min-height:320px;padding:22px 18px!important;}.lp-preview-hero-fixed .lp-h1{font-size:34px!important;}.lp-preview-hero-fixed .lp-h2{font-size:13px!important;}}\
    ';
    document.head.appendChild(style);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
