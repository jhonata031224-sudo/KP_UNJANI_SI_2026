(function () {
  const form = document.getElementById('landingForm');
  if (!form || form.dataset.landingEditorReady === '1') return;
  form.dataset.landingEditorReady = '1';

  const get = (o, path, f = '') =>
    path.split('.').reduce((v, k) => (v && v[k] !== undefined ? v[k] : undefined), o) ?? f;

  let config = {};

  // =====================================================================
  // Field/kartu yang di bawah ini ditambahkan lewat JavaScript (bukan
  // ditulis statis di admin.blade.php) karena isinya disimpan dalam satu
  // kolom JSON "landing_content" di database, terpisah dari kolom-kolom
  // Beranda/Fitur/Tentang/Kontak yang sudah ada. Supaya tampilannya tetap
  // KONSISTEN dan RAPI dengan field statis di sekitarnya, semua field baru
  // di sini WAJIB dikelompokkan dalam kartu ".lp-card" berjudul + deskripsi
  // singkat -- persis pola yang sudah dipakai untuk kartu "Fitur 1..4" dan
  // "Sosial Media". Jangan pernah menaruh field lepas langsung ke dalam
  // grid statis yang sudah ada; itu yang bikin tab Beranda dulu jadi satu
  // tumpukan field panjang tanpa pengelompokan dan susah dipahami pemula.
  // =====================================================================

  function addField(parent, label, path, value, type = 'text', extra = {}) {
    const wrap = document.createElement('div');
    wrap.className = extra.full === false ? 'form-field' : 'form-field full';

    const id = 'lpManaged_' + path.replace(/[^a-z0-9]/gi, '_');
    const lab = document.createElement('label');
    lab.htmlFor = id;
    lab.textContent = label;

    const input = type === 'textarea' ? document.createElement('textarea') : document.createElement('input');
    input.id = id;
    if (type !== 'textarea') input.type = type;
    else input.rows = extra.rows || 3;
    input.value = value ?? '';
    input.dataset.landingPath = path;

    wrap.append(lab, input);
    parent.appendChild(wrap);
    return input;
  }

  // Kartu berjudul + (opsional) deskripsi singkat, isinya sebuah form-grid.
  // `desc` sengaja opsional tapi sangat dianjurkan diisi -- ini yang bikin
  // pemula langsung paham fungsi kelompok field tanpa perlu tanya-tanya.
  function addCard(panel, title, fields, desc = '') {
    const card = document.createElement('div');
    card.className = 'lp-card';

    const t = document.createElement('div');
    t.className = 'lp-card-title';
    t.textContent = title;
    card.appendChild(t);

    if (desc) {
      const p = document.createElement('p');
      p.className = 'lp-card-desc';
      p.textContent = desc;
      card.appendChild(p);
    }

    const grid = document.createElement('div');
    grid.className = 'form-grid';
    fields.forEach(f => {
      // tuple: [path, label, type, extraOpts, defaultValue]
      addField(grid, f[1], f[0], get(config, f[0], f[4] ?? ''), f[2] || 'text', f[3] || {});
    });
    card.appendChild(grid);

    panel.appendChild(card);
    return card;
  }

  function addLogoField(panel) {
    // Logo saat ini (kalau sudah pernah diupload) dikirim lewat atribut
    // data-current-logo di #landingForm (lihat admin.blade.php). Kalau
    // kosong berarti belum pernah upload logo -> tampilkan kotak placeholder.
    const currentLogo = form.dataset.currentLogo || '';
    const logoDeleteUrl = form.dataset.logoDeleteUrl || '';
    addCardHtml(panel, `
      <div class="lp-card-title">Logo Landing Page</div>
      <p class="lp-card-desc">Satu logo ini otomatis dipakai di header, hero, layar loading, favicon tab browser, dan bagian Tentang -- tidak perlu upload berkali-kali di tempat lain.</p>
      <div class="form-grid">
        <div class="form-field full">
          <label for="lpManagedLogo" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">Logo</label>
          <div class="lp-hero-image-row">
            <input id="lpManagedLogo" name="logo_file" type="file" accept="image/png,image/jpeg,image/webp" data-has-current="${currentLogo ? '1' : '0'}" data-label-existing="Ganti Logo">
            <img src="${currentLogo}" alt="Logo saat ini" class="lp-current-image" id="lpLogoPreviewImg" style="${currentLogo ? '' : 'display:none'}">
            <div class="lp-image-placeholder" id="lpLogoPreviewPlaceholder" style="${currentLogo ? 'display:none' : ''}">
              <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><circle cx="9" cy="10" r="1.8"></circle><path d="m4.5 18 5-5.5 3 3 3.5-4L20.5 18"></path></svg>
              <span>Belum ada logo</span>
            </div>
            <button type="button" class="btn btn-ghost-red lp-delete-img-btn" id="lpLogoDeleteBtn" style="${currentLogo ? '' : 'display:none'}" onclick="window.bukaHapusLandingGambar(this)" data-action="${logoDeleteUrl}" data-nama="Logo Landing Page">Hapus Logo</button>
          </div>
          <small>Format JPG, PNG, atau WEBP · maksimal 5 MB.</small>
        </div>
      </div>
    `);
  }

  function addNavFields(panel) {
    const card = addCardHtml(panel, `
      <div class="lp-card-title">Navigasi Landing Page</div>
      <p class="lp-card-desc">4 menu yang tampil di bagian atas (header) landing page, urut dari kiri ke kanan.</p>
    `);
    const grid = document.createElement('div');
    grid.className = 'form-grid';
    for (let i = 0; i < 4; i++) {
      addField(grid, `Menu ${i + 1} — Label`, `nav.${i}.label`, get(config, `nav.${i}.label`), 'text', { full: false });
      addField(grid, `Menu ${i + 1} — Link`, `nav.${i}.url`, get(config, `nav.${i}.url`), 'text', { full: false });
    }
    card.appendChild(grid);
  }

  function addStatsFields(panel) {
    const card = addCardHtml(panel, `
      <div class="lp-card-title">Statistik Beranda</div>
      <p class="lp-card-desc">4 angka ringkasan yang tampil di landing page. Catatan: angka untuk statistik berlabel "Akun Terdaftar" selalu dihitung otomatis oleh sistem dan tidak akan mengikuti isian manual di sini.</p>
    `);
    const grid = document.createElement('div');
    grid.className = 'form-grid';
    for (let i = 0; i < 4; i++) {
      addField(grid, `Statistik ${i + 1} — Angka`, `stats.${i}.number`, get(config, `stats.${i}.number`), 'text', { full: false });
      addField(grid, `Statistik ${i + 1} — Label`, `stats.${i}.label`, get(config, `stats.${i}.label`), 'text', { full: false });
    }
    card.appendChild(grid);
  }

  // Helper kecil buat kartu yang headernya campuran teks statis (innerHTML)
  // -- dipakai addLogoField/addNavFields/addStatsFields supaya nggak perlu
  // menulis ulang boilerplate className tiap kali.
  function addCardHtml(panel, headHtml) {
    const card = document.createElement('div');
    card.className = 'lp-card lp-card-compact';
    card.innerHTML = headHtml;
    panel.appendChild(card);
    return card;
  }

  function styleFilePickers() {
    if (!document.getElementById('landing-file-picker-style')) {
      const style = document.createElement('style');
      style.id = 'landing-file-picker-style';
      style.textContent = `
        .landing-file-picker{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:0;min-height:42px}
        .landing-file-picker input[type=file]{position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;overflow:hidden!important;pointer-events:none!important}
        .landing-file-button{display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:7px!important;border:1px solid var(--border-strong,#cbd5e1);background:var(--panel,#fff);color:var(--text,#17212b);min-width:112px!important;height:34px!important;box-sizing:border-box!important;margin:0!important;border-radius:8px;padding:0 12px;font:600 11.5px/34px var(--body,inherit);cursor:pointer;box-shadow:0 1px 2px rgba(15,23,42,.04);transition:.15s ease}
        .landing-file-button:hover{border-color:var(--gold,#c97a00);color:var(--gold,#c97a00);background:var(--gold-dim,rgba(201,122,0,.08))}
        .landing-file-button:focus-visible{outline:3px solid rgba(201,122,0,.16);outline-offset:2px}
        .landing-file-button svg{display:block!important;width:14px!important;height:14px!important;flex:0 0 14px!important;margin:0!important;vertical-align:middle!important}
        .landing-file-button span{display:block!important;height:14px!important;line-height:14px!important;margin:0!important;padding:0!important;white-space:nowrap!important;vertical-align:middle!important}
        .landing-file-name{display:inline-flex!important;align-items:center!important;height:34px!important;min-height:34px!important;margin:0!important;padding:0!important;color:var(--text-muted,#64748b);font-size:11px;min-width:0;max-width:420px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
      `;
      document.head.appendChild(style);
    }

    form.querySelectorAll('input[type=file]').forEach(input => {
      if (input.dataset.filePickerReady === '1') return;
      if (input.dataset.siberadEnhanced === '1' || input.closest('.siberad-file-wrap')) return;
      input.dataset.filePickerReady = '1';

      const wrap = document.createElement('div');
      wrap.className = 'landing-file-picker';
      input.parentNode.insertBefore(wrap, input);
      wrap.appendChild(input);

      // Kalau file ini sudah punya data aktif tersimpan di server (BG/logo
      // yang sedang dipakai sistem), teks tombol diganti dari "Pilih File"
      // generik jadi lebih spesifik ("Ganti Latar"/"Ganti Logo") supaya
      // Admin tahu dia akan MENGGANTI, bukan mengunggah yang pertama kali.
      const hasCurrent = input.dataset.hasCurrent === '1';
      const buttonText = hasCurrent && input.dataset.labelExisting ? input.dataset.labelExisting : 'Pilih File';

      const label = document.createElement('label');
      label.className = 'landing-file-button';
      label.htmlFor = input.id;
      const labelTextSpan = document.createElement('span');
      labelTextSpan.textContent = buttonText;
      label.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4"></path><path d="m7 9 5-5 5 5"></path><path d="M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"></path></svg>';
      label.appendChild(labelTextSpan);
      wrap.appendChild(label);

      const name = document.createElement('span');
      name.className = 'landing-file-name';
      // Sama kayak logika tombol di atas: kalau sudah ada file aktif di
      // server (hasCurrent) dan belum ada file baru yang dipilih user,
      // keterangan "Belum ada file dipilih" disembunyikan -- soalnya
      // sebenarnya SUDAH ada file yang terpakai, cuma belum diganti.
      const updateName = () => {
        const text = input.files && input.files[0] ? input.files[0].name : (hasCurrent ? '' : 'Belum ada file dipilih');
        name.textContent = text;
        // Kalau teksnya kosong, span ini HARUS benar-benar dihapus dari
        // flow (bukan cuma dikosongkan) -- soalnya .landing-file-picker
        // pakai `gap` antar child, jadi span kosong tetap makan lebar
        // semu (gap) di sisi kanan tombol & bikin tombol kelihatan
        // "nempel kiri" alih-alih benar-benar center.
        // .landing-file-name punya display:inline-flex!important di CSS
        // injeksi di atas, jadi override-nya juga wajib pakai !important
        // lewat setProperty -- style.display biasa akan kalah/diabaikan.
        name.style.setProperty('display', text ? 'inline-flex' : 'none', 'important');
      };
      updateName();
      wrap.appendChild(name);

      input.addEventListener('change', updateName);
    });
  }

  function collect() {
    const clone = JSON.parse(JSON.stringify(config || {}));
    form.querySelectorAll('[data-landing-path]').forEach(input => {
      const parts = input.dataset.landingPath.split('.');
      let cur = clone;
      parts.forEach((p, i) => {
        if (i === parts.length - 1) {
          cur[p] = input.value;
        } else {
          if (!cur[p]) cur[p] = /^\d+$/.test(parts[i + 1]) ? [] : {};
          cur = cur[p];
        }
      });
    });
    return clone;
  }

  function syncPayload() {
    const hidden = document.getElementById('landingContentPayload');
    if (hidden) hidden.value = JSON.stringify(collect());
  }

  function setText(doc, selector, value) {
    const el = doc.querySelector(selector);
    if (el) el.textContent = value ?? '';
  }

  // Live preview di sini menyasar iframe #lpLiveLandingFrame. Iframe itu
  // TIDAK ada di admin.blade.php saat ini, jadi fungsi ini otomatis
  // no-op (langsung return karena iframe null) tanpa bikin error --
  // aman dibiarkan menyala untuk saat iframe pratinjau itu ditambahkan lagi.
  function preview() {
    const iframe = document.getElementById('lpLiveLandingFrame');
    if (!iframe || !iframe.contentDocument) return;

    const doc = iframe.contentDocument;
    const c = collect();
    const brand = c.brand || {};
    const hero = c.hero || {};

    doc.querySelectorAll('.logo-text b').forEach(el => {
      el.innerHTML = '';
      el.appendChild(doc.createTextNode(brand.name || 'SIBER'));
      const s = doc.createElement('span');
      s.textContent = brand.accent || 'AD';
      el.appendChild(s);
    });
    doc.querySelectorAll('.logo-text small').forEach(el => { el.textContent = brand.tagline || 'Pussiberad · TNI AD'; });
    doc.querySelectorAll('.nav-links a').forEach((el, i) => {
      if (c.nav && c.nav[i]) {
        el.textContent = c.nav[i].label || '';
        el.setAttribute('href', c.nav[i].url || '#');
      }
    });

    setText(doc, '.hero-actions .btn-primary', hero.button_label || 'Selengkapnya');
    const btn = doc.querySelector('.hero-actions .btn-primary');
    if (btn) btn.href = hero.button_url || '#fitur';

    const crestCaptionEl = doc.querySelector('.hero-crest-caption');
    if (crestCaptionEl) {
      const firstNode = crestCaptionEl.childNodes[0];
      if (firstNode && firstNode.nodeType === 3) firstNode.nodeValue = hero.crest_caption || 'Pusat Siber Angkatan Darat';
      const mottoSpan = crestCaptionEl.querySelector('span');
      if (mottoSpan) mottoSpan.textContent = hero.crest_motto ? '“' + hero.crest_motto + '”' : '';
    }

    doc.querySelectorAll('.stats-grid .stat').forEach((el, i) => {
      const x = (c.stats || [])[i];
      if (x) {
        setText(el.querySelector('.stat-num'), x.number);
        setText(el.querySelector('.stat-label'), x.label);
      }
    });

    setText(doc, '#fitur .section-head .eyebrow', get(c, 'features_section.eyebrow'));
    setText(doc, '#fitur .section-head h3', get(c, 'features_section.title'));
    setText(doc, '#fitur .section-head p', get(c, 'features_section.description'));
    setText(doc, '#tentang-pussiberad .eyebrow', get(c, 'about_section.eyebrow'));
    setText(doc, '#tentang-pussiberad .about-top h3', get(c, 'about_section.title'));
    setText(doc, 'footer .footer-desc', get(c, 'footer.description'));
    setText(doc, 'footer .footer-bottom span:first-child', get(c, 'footer.copyright'));
  }

  function bindLivePreview() {
    form.querySelectorAll('[data-landing-path]').forEach(input => {
      input.addEventListener('input', () => { syncPayload(); preview(); });
      input.addEventListener('change', () => { syncPayload(); preview(); });
    });
    form.addEventListener('submit', syncPayload);

    const logo = document.getElementById('lpManagedLogo');
    if (logo) {
      logo.addEventListener('change', () => {
        const f = logo.files && logo.files[0];
        if (!f) return;
        const r = new FileReader();
        r.onload = e => {
          // Kotak pratinjau kecil di sebelah tombol pilih file: begitu logo
          // dipilih, langsung ganti gambar & sembunyikan placeholder "belum
          // ada logo" -- tanpa perlu submit form dulu.
          const previewImg = document.getElementById('lpLogoPreviewImg');
          const placeholder = document.getElementById('lpLogoPreviewPlaceholder');
          if (previewImg) { previewImg.src = e.target.result; previewImg.style.display = 'block'; }
          if (placeholder) { placeholder.style.display = 'none'; }

          const iframe = document.getElementById('lpLiveLandingFrame');
          if (iframe && iframe.contentDocument) {
            iframe.contentDocument
              .querySelectorAll('.logo-badge img,.hero-crest .mark-plate img,#loader .mark-plate img,#tentang-pussiberad img')
              .forEach(img => { img.src = e.target.result; });
          }
        };
        r.readAsDataURL(f);
      });
    }

    const timer = setInterval(() => {
      const iframe = document.getElementById('lpLiveLandingFrame');
      if (iframe && iframe.contentDocument && iframe.contentDocument.readyState === 'complete') {
        clearInterval(timer);
        preview();
      }
    }, 300);
    setTimeout(() => clearInterval(timer), 15000);
  }

  function buildEditor() {
    form.querySelectorAll('[data-lp-tab="lengkap"],[data-lp-tab-panel="lengkap"]').forEach(el => el.remove());

    const beranda = form.querySelector('[data-lp-tab-panel="beranda"]');
    const fitur = form.querySelector('[data-lp-tab-panel="fitur"]');
    const tentang = form.querySelector('[data-lp-tab-panel="tentang"]');
    const kontak = form.querySelector('[data-lp-tab-panel="kontak"]');
    if (!beranda || !fitur || !tentang || !kontak) return;

    // ----- Tab Beranda: field statis (Label Kecil, Judul, Sub Judul, dst)
    // sudah ada duluan di grid pertama -- JANGAN diutak-atik. Semua field
    // tambahan di bawah ini masing-masing dikelompokkan ke kartu sendiri
    // supaya rapi & mudah dipahami, dipisahkan dari grid statis di atasnya
    // lewat class "lp-dynamic-section" (kasih jarak atas yang jelas).
    const dynamicWrap = document.createElement('div');
    dynamicWrap.className = 'lp-dynamic-section';
    beranda.appendChild(dynamicWrap);

    addCard(dynamicWrap, 'Identitas Brand & Navigasi Header', [
      ['brand.name', 'Nama Brand', 'text', { full: false }, 'SIBER'],
      ['brand.accent', 'Teks Aksen Brand', 'text', { full: false }, 'AD'],
      ['brand.tagline', 'Tagline Brand', 'text', {}, 'Pussiberad · TNI AD'],
    ], 'Nama, aksen huruf, dan tagline kecil yang tampil di pojok kiri atas (header) landing page.');

    addCard(dynamicWrap, 'Tombol & Lambang di Hero', [
      ['hero.button_label', 'Teks Tombol Hero', 'text', { full: false }, 'Selengkapnya'],
      ['hero.button_url', 'Link Tombol Hero', 'text', { full: false }, '#fitur'],
      ['hero.crest_caption', 'Caption Lambang', 'text', { full: false }, 'Pusat Siber Angkatan Darat'],
      ['hero.crest_motto', 'Moto Lambang', 'text', { full: false }, 'Satria Yudha Waskita'],
    ], 'Tombol aksi utama serta teks pendamping lambang yang tampil di bagian paling atas (hero) landing page.');

    addCard(dynamicWrap, 'Judul Browser & SEO', [
      ['meta.title', 'Judul Browser / SEO'],
      ['meta.description', 'Deskripsi SEO', 'textarea', { rows: 3 }],
    ], 'Judul yang muncul di tab browser dan cuplikan hasil pencarian Google. Tidak tampil langsung di halaman landing page.');

    addLogoField(dynamicWrap);
    addNavFields(dynamicWrap);
    addStatsFields(dynamicWrap);

    // ----- Tab Fitur / Tentang / Kontak: cukup 1 kartu tambahan tiap tab,
    // sudah otomatis rapi karena setiap ".lp-card" statis maupun dinamis
    // sama-sama punya margin-bottom sendiri (lihat CSS di admin.blade.php).
    addCard(fitur, 'Judul Section Fitur', [
      ['features_section.eyebrow', 'Label kecil section'],
      ['features_section.title', 'Judul section'],
      ['features_section.description', 'Deskripsi section', 'textarea', { rows: 3 }],
    ], 'Judul & label yang tampil di atas 4 kartu Fitur pada landing page.');

    addCard(tentang, 'Judul Section Tentang', [
      ['about_section.eyebrow', 'Label kecil section'],
      ['about_section.title', 'Judul section'],
    ], 'Judul & label yang tampil di atas bagian Tentang pada landing page.');

    addCard(kontak, 'Footer Landing Page', [
      ['footer.description', 'Deskripsi footer', 'textarea', { rows: 3 }],
      ['footer.copyright', 'Teks copyright / footer bawah'],
    ], 'Teks yang tampil di bagian paling bawah (footer) landing page.');

    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'landing_content';
    hidden.id = 'landingContentPayload';
    form.appendChild(hidden);

    styleFilePickers();
    bindLivePreview();
  }

  fetch('/landing-config', { headers: { Accept: 'application/json' } })
    .then(r => (r.ok ? r.json() : {}))
    .then(d => { config = d.config || {}; buildEditor(); })
    .catch(() => buildEditor());
})();
