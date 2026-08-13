(function () {
  const form = document.getElementById('landingForm');
  if (!form || form.dataset.completeEditorReady === '1') return;
  form.dataset.completeEditorReady = '1';

  const get = (o, path, fallback = '') => path.split('.').reduce((v, k) => v && v[k] !== undefined ? v[k] : undefined, o) ?? fallback;
  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  let config = {};

  function addField(parent, label, path, value, type = 'text') {
    const wrap = document.createElement('div'); wrap.className = 'form-field full';
    const id = 'lpComplete_' + path.replace(/[^a-z0-9]/gi, '_');
    const lab = document.createElement('label'); lab.htmlFor = id; lab.textContent = label;
    const input = type === 'textarea' ? document.createElement('textarea') : document.createElement('input');
    input.id = id; input.type = type === 'textarea' ? '' : type; input.rows = 3; input.value = value ?? ''; input.dataset.landingPath = path;
    wrap.append(lab, input); parent.appendChild(wrap); return input;
  }

  function buildEditor() {
    const tabs = form.querySelector('.lp-tabs'); if (!tabs) return;
    const tab = document.createElement('button'); tab.type = 'button'; tab.className = 'lp-tab'; tab.dataset.lpTab = 'lengkap'; tab.textContent = 'Lengkap';
    tabs.appendChild(tab);
    const panel = document.createElement('div'); panel.className = 'lp-tab-panel'; panel.dataset.lpTabPanel = 'lengkap';
    panel.innerHTML = '<p class="lp-tab-desc">Semua elemen landing page yang sebelumnya masih statis. Perubahan di sini berlaku ke halaman publik setelah disimpan.</p>';
    const sections = [
      ['Branding & Identitas', [['brand.name','Nama brand'],['brand.accent','Teks aksen brand'],['brand.tagline','Tagline brand'],['meta.title','Judul browser / SEO'],['meta.description','Deskripsi SEO']]],
      ['Navigasi', [['nav.0.label','Menu 1'],['nav.0.url','Link menu 1'],['nav.1.label','Menu 2'],['nav.1.url','Link menu 2'],['nav.2.label','Menu 3'],['nav.2.url','Link menu 3'],['nav.3.label','Menu 4'],['nav.3.url','Link menu 4']]],
      ['Hero & Tombol', [['hero.button_label','Teks tombol hero'],['hero.button_url','Link tombol hero'],['hero.crest_caption','Caption lambang'],['hero.crest_motto','Moto lambang']]],
      ['Statistik', [['stats.0.number','Statistik 1 angka'],['stats.0.label','Statistik 1 label'],['stats.1.number','Statistik 2 angka'],['stats.1.label','Statistik 2 label'],['stats.2.number','Statistik 3 angka'],['stats.2.label','Statistik 3 label'],['stats.3.number','Statistik 4 angka'],['stats.3.label','Statistik 4 label']]],
      ['Judul Section', [['features_section.eyebrow','Label section fitur'],['features_section.title','Judul section fitur'],['features_section.description','Deskripsi section fitur'],['about_section.eyebrow','Label section tentang'],['about_section.title','Judul section tentang']]],
      ['Footer', [['footer.description','Deskripsi footer'],['footer.copyright','Teks copyright / footer bawah']]],
    ];
    sections.forEach(([title, fields]) => {
      const card = document.createElement('div'); card.className = 'lp-card';
      const h = document.createElement('div'); h.className = 'lp-card-title'; h.textContent = title; card.appendChild(h);
      const grid = document.createElement('div'); grid.className = 'form-grid'; card.appendChild(grid);
      fields.forEach(([path, label]) => addField(grid, label, path, get(config, path), /description|deskripsi|copyright/i.test(label) ? 'textarea' : 'text'));
      panel.appendChild(card);
    });
    const assets = document.createElement('div'); assets.className = 'lp-card';
    assets.innerHTML = '<div class="lp-card-title">Branding & Gambar</div><div class="form-grid"><div class="form-field full"><label for="lpCompleteLogo">Logo Landing Page</label><input id="lpCompleteLogo" name="logo_file" type="file" accept="image/png,image/jpeg,image/webp"><small style="display:block;margin-top:6px;color:var(--text-muted);">JPG, PNG, WEBP · maks. 5 MB. Dipakai untuk logo header, hero, loader, favicon, dan bagian Tentang.</small></div></div>';
    panel.appendChild(assets);
    form.insertBefore(panel, form.querySelector('.lp-form-actions'));

    tab.addEventListener('click', () => {
      form.querySelectorAll('[data-lp-tab]').forEach(t => t.classList.toggle('active', t === tab));
      form.querySelectorAll('[data-lp-tab-panel]').forEach(p => p.classList.toggle('active', p === panel));
      document.querySelectorAll('[data-lp-preview-section]').forEach(s => s.classList.remove('is-focus'));
    });

    const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'landing_content'; hidden.id = 'landingContentPayload'; form.appendChild(hidden);
    const sync = () => {
      const clone = JSON.parse(JSON.stringify(config || {}));
      form.querySelectorAll('[data-landing-path]').forEach(input => {
        const parts = input.dataset.landingPath.split('.'); let cur = clone;
        parts.forEach((p, i) => { if (i === parts.length - 1) cur[p] = input.value; else { if (!cur[p]) cur[p] = /^\d+$/.test(parts[i + 1]) ? [] : {}; cur = cur[p]; } });
      });
      hidden.value = JSON.stringify(clone);
    };
    form.querySelectorAll('[data-landing-path]').forEach(i => i.addEventListener('input', sync));
    form.addEventListener('submit', sync);
  }

  fetch('/landing-config', { headers: { 'Accept': 'application/json' } })
    .then(r => r.ok ? r.json() : {})
    .then(data => { config = data.config || {}; buildEditor(); })
    .catch(() => buildEditor());
})();
