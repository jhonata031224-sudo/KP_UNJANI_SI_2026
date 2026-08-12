(function () {
  'use strict';

  function boot() {
    var form = document.getElementById('landingForm');
    var preview = document.getElementById('lpPreview');
    if (!form || !preview) return;

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

    function valueOf(selector) {
      var el = form.querySelector(selector);
      return el ? String(el.value || '') : '';
    }

    function setValuePreview(dataName, targetId, fallback) {
      var target = document.getElementById(targetId);
      var source = form.querySelector('[data-lp="' + dataName + '"]');
      if (!target || !source) return;
      var value = String(source.value || '');
      target.textContent = value.trim() ? value : (fallback || '');
      target.style.opacity = value.trim() ? '1' : '.45';
    }

    function renderFeatures() {
      var wrap = document.getElementById('lpPvFitur');
      if (!wrap) return;
      wrap.innerHTML = '';

      for (var i = 0; i < 4; i++) {
        var title = form.querySelector('[data-lp="fitur_judul_' + i + '"]');
        var desc = form.querySelector('[data-lp="fitur_deskripsi_' + i + '"]');
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
        var platform = form.querySelector('[data-lp="sosial_platform_' + i + '"]');
        if (!platform) break;
        var label = form.querySelector('[data-lp="sosial_label_' + i + '"]');
        var url = form.querySelector('[data-lp="sosial_url_' + i + '"]');
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
      if (!input || !hero || !input.files || !input.files[0]) return;

      var file = input.files[0];
      if (!file.type || file.type.indexOf('image/') !== 0) return;

      var reader = new FileReader();
      reader.onload = function (event) {
        hero.style.backgroundImage = 'linear-gradient(160deg, color-mix(in srgb, var(--panel-2) 85%, transparent), color-mix(in srgb, var(--bg-deep) 75%, transparent)), url("' + event.target.result + '")';
        hero.style.backgroundSize = 'cover';
        hero.style.backgroundPosition = 'center';
      };
      reader.readAsDataURL(file);
    }

    function render() {
      Object.keys(textMap).forEach(function (name) {
        var fallback = name === 'hero_judul_awal' ? 'SIBER' : (name === 'hero_judul_aksen' ? 'AD' : '');
        setValuePreview(name, textMap[name], fallback);
      });

      renderFeatures();
      renderSocial();
      renderHeroImage();

      // Pastikan judul selalu terlihat walaupun style tema dashboard berubah.
      var h1 = preview.querySelector('.lp-h1');
      var h2 = preview.querySelector('.lp-h2');
      if (h1) {
        h1.style.display = 'block';
        h1.style.visibility = 'visible';
        h1.style.color = 'var(--text)';
      }
      if (h2) {
        h2.style.display = 'block';
        h2.style.visibility = 'visible';
        h2.style.color = 'var(--text)';
      }
    }

    // input = realtime ketika mengetik, change = fallback untuk select/file.
    form.querySelectorAll('[data-lp], [data-lp-image]').forEach(function (el) {
      el.addEventListener('input', render);
      el.addEventListener('change', render);
    });

    // Jalankan setelah semua script inline editor selesai menginisialisasi DOM.
    render();
    window.requestAnimationFrame(render);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
