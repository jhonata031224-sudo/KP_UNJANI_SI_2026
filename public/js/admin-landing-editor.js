(function () {
  const form = document.getElementById('landingForm');
  if (!form || form.dataset.landingEditorReady === '1') return;
  form.dataset.landingEditorReady = '1';

  const get = (o, path, fallback = '') => path.split('.').reduce((v, k) => v && v[k] !== undefined ? v[k] : undefined, o) ?? fallback;
  let config = {};

  function addField(parent, label, path, value, type = 'text', extra = {}) {
    const wrap = document.createElement('div');
    wrap.className = extra.full === false ? 'form-field' : 'form-field full';
    const id = 'lpManaged_' + path.replace(/[^a-z0-9]/gi, '_');
    const lab = document.createElement('label'); lab.htmlFor = id; lab.textContent = label;
    const input = type === 'textarea' ? document.createElement('textarea') : document.createElement('input');
    input.id = id;
    if (type !== 'textarea') input.type = type; else input.rows = extra.rows || 3;
    input.value = value ?? '';
    input.dataset.landingPath = path;
    wrap.append(lab, input); parent.appendChild(wrap); return input;
  }

  function addCard(panel, title, fields) {
    const card = document.createElement('div'); card.className = 'lp-card';
    const titleEl = document.createElement('div'); titleEl.className = 'lp-card-title'; titleEl.textContent = title; card.appendChild(titleEl);
    const grid = document.createElement('div'); grid.className = 'form-grid';
    fields.forEach(field => addField(grid, field[1], field[0], get(config, field[0]), field[2] || 'text', field[3] || {}));
    card.appendChild(grid); panel.appendChild(card); return card;
  }

  function addLogoField(panel) {
    const card = document.createElement('div'); card.className = 'lp-card lp-card-compact';
    card.innerHTML = '<div class="lp-card-title">Logo Landing Page</div><div class="form-grid"><div class="form-field full"><label for="lpManagedLogo">Logo</label><input id="lpManagedLogo" name="logo_file" type="file" accept="image/png,image/jpeg,image/webp"><small style="display:block;margin-top:6px;color:var(--text-muted);">Logo ini digunakan pada header, hero, loader, favicon, dan bagian Tentang. JPG, PNG, WEBP · maksimal 5 MB.</small></div></div>';
    panel.appendChild(card);
  }

  function addNavFields(panel) {
    const card = document.createElement('div'); card.className = 'lp-card lp-card-compact';
    const title = document.createElement('div'); title.className = 'lp-card-title'; title.textContent = 'Navigasi Landing Page'; card.appendChild(title);
    const grid = document.createElement('div'); grid.className = 'form-grid';
    for (let i = 0; i < 4; i++) {
      addField(grid, `Menu ${i + 1} — Label`, `nav.${i}.label`, get(config, `nav.${i}.label`), 'text', { full: false });
      addField(grid, `Menu ${i + 1} — Link`, `nav.${i}.url`, get(config, `nav.${i}.url`), 'text', { full: false });
    }
    card.appendChild(grid); panel.appendChild(card);
  }

  function addStatsFields(panel) {
    const card = document.createElement('div'); card.className = 'lp-card lp-card-compact';
    const title = document.createElement('div'); title.className = 'lp-card-title'; title.textContent = 'Statistik Beranda'; card.appendChild(title);
    const grid = document.createElement('div'); grid.className = 'form-grid';
    for (let i = 0; i < 4; i++) {
      addField(grid, `Statistik ${i + 1} — Angka`, `stats.${i}.number`, get(config, `stats.${i}.number`), 'text', { full: false });
      addField(grid, `Statistik ${i + 1} — Label`, `stats.${i}.label`, get(config, `stats.${i}.label`), 'text', { full: false });
    }
    card.appendChild(grid); panel.appendChild(card);
  }

  function addLogoPreviewListener() {
    const input = document.getElementById('lpManagedLogo'); if (!input) return;
    input.addEventListener('change', function () {
      const file = input.files && input.files[0]; if (!file) return;
      const reader = new FileReader();
      reader.onload = function (e) {
        const iframe = document.getElementById('lpLiveLandingFrame'); if (!iframe || !iframe.contentDocument) return;
        iframe.contentDocument.querySelectorAll('.logo-badge img,.hero-crest .mark-plate img,#loader .mark-plate img,#tentang-pussiberad img').forEach(img => img.src = e.target.result);
      };
      reader.readAsDataURL(file);
    });
  }

  function syncPayload() {
    const hidden = document.getElementById('landingContentPayload'); if (!hidden) return;
    const clone = JSON.parse(JSON.stringify(config || {}));
    form.querySelectorAll('[data-landing-path]').forEach(input => {
      const parts = input.dataset.landingPath.split('.'); let cur = clone;
      parts.forEach((part, index) => {
        if (index === parts.length - 1) cur[part] = input.value;
        else { if (!cur[part]) cur[part] = /^\d+$/.test(parts[index + 1]) ? [] : {}; cur = cur[part]; }
      });
    });
    hidden.value = JSON.stringify(clone);
  }

  function buildEditor() {
    // Jangan buat tab baru. Setting ditempatkan langsung pada tab yang relevan.
    form.querySelectorAll('[data-lp-tab="lengkap"], [data-lp-tab-panel="lengkap"]').forEach(el => el.remove());
    const beranda = form.querySelector('[data-lp-tab-panel="beranda"]');
    const fitur = form.querySelector('[data-lp-tab-panel="fitur"]');
    const tentang = form.querySelector('[data-lp-tab-panel="tentang"]');
    const kontak = form.querySelector('[data-lp-tab-panel="kontak"]');
    if (!beranda || !fitur || !tentang || !kontak) return;

    // BERANDA: logo, identitas, navigasi, hero, tombol, SEO, background, statistik.
    const heroGrid = beranda.querySelector('.form-grid');
    if (heroGrid) {
      addField(heroGrid, 'Nama Brand', 'brand.name', get(config, 'brand.name', 'SIBER'), 'text', { full: false });
      addField(heroGrid, 'Teks Aksen Brand', 'brand.accent', get(config, 'brand.accent', 'AD'), 'text', { full: false });
      addField(heroGrid, 'Tagline Brand', 'brand.tagline', get(config, 'brand.tagline', 'Pussiberad · TNI AD'));
      addField(heroGrid, 'Teks Tombol Hero', 'hero.button_label', get(config, 'hero.button_label', 'Selengkapnya'), 'text', { full: false });
      addField(heroGrid, 'Link Tombol Hero', 'hero.button_url', get(config, 'hero.button_url', '#fitur'), 'text', { full: false });
      addField(heroGrid, 'Caption Lambang', 'hero.crest_caption', get(config, 'hero.crest_caption', 'Pusat Siber Angkatan Darat'), 'text', { full: false });
      addField(heroGrid, 'Moto Lambang', 'hero.crest_motto', get(config, 'hero.crest_motto', 'Satria Yudha Waskita'), 'text', { full: false });
      addField(heroGrid, 'Judul Browser / SEO', 'meta.title', get(config, 'meta.title'), 'text');
      addField(heroGrid, 'Deskripsi SEO', 'meta.description', get(config, 'meta.description'), 'textarea', { rows: 3 });
    }
    addLogoField(beranda);
    addNavFields(beranda);
    addStatsFields(beranda);

    // FITUR: heading section diletakkan bersama empat kartu fitur yang sudah ada.
    addCard(fitur, 'Judul Section Fitur', [
      ['features_section.eyebrow', 'Label kecil section'],
      ['features_section.title', 'Judul section'],
      ['features_section.description', 'Deskripsi section', 'textarea', { rows: 3 }]
    ]);

    // TENTANG: heading section diletakkan bersama profil/moto.
    addCard(tentang, 'Judul Section Tentang', [
      ['about_section.eyebrow', 'Label kecil section'],
      ['about_section.title', 'Judul section']
    ]);

    // KONTAK: footer ikut dikelola di tab yang sama karena tampil di area kontak.
    addCard(kontak, 'Footer Landing Page', [
      ['footer.description', 'Deskripsi footer', 'textarea', { rows: 3 }],
      ['footer.copyright', 'Teks copyright / footer bawah']
    ]);

    const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'landing_content'; hidden.id = 'landingContentPayload'; form.appendChild(hidden);
    form.querySelectorAll('[data-landing-path]').forEach(input => input.addEventListener('input', syncPayload));
    form.addEventListener('submit', syncPayload);
    addLogoPreviewListener();
  }

  fetch('/landing-config', { headers: { Accept: 'application/json' } })
    .then(response => response.ok ? response.json() : {})
    .then(data => { config = data.config || {}; buildEditor(); })
    .catch(() => buildEditor());
})();
