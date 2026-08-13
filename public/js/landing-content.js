(function () {
  const cfg = window.__SIBERAD_LANDING_CONFIG__ || {};
  const q = (s, root = document) => root.querySelector(s);
  const qa = (s, root = document) => Array.from(root.querySelectorAll(s));
  const text = (el, value) => { if (el && value != null) el.textContent = value; };
  const attr = (el, name, value) => { if (el && value != null) el.setAttribute(name, value); };

  if (cfg.meta) {
    if (cfg.meta.title) document.title = cfg.meta.title;
    const meta = q('meta[name="description"]');
    if (meta && cfg.meta.description) meta.setAttribute('content', cfg.meta.description);
  }

  const brand = cfg.brand || {};
  qa('.logo-text b').forEach(el => {
    el.innerHTML = '';
    el.append(document.createTextNode(brand.name || 'SIBER'));
    const span = document.createElement('span');
    span.textContent = brand.accent || 'AD';
    el.append(span);
  });
  qa('.logo-text small').forEach(el => text(el, brand.tagline || 'Pussiberad · TNI AD'));

  if (cfg.logo_url) {
    qa('.logo-badge img, .hero-crest .mark-plate img, #loader .mark-plate img, #tentang-pussiberad img').forEach(img => attr(img, 'src', cfg.logo_url));
    const favicon = q('link[rel="icon"]'); if (favicon) favicon.href = cfg.logo_url;
  }

  if (Array.isArray(cfg.nav)) {
    const links = qa('.nav-links a');
    cfg.nav.slice(0, links.length).forEach((item, i) => { text(links[i], item.label); attr(links[i], 'href', item.url || '#'); });
  }

  const hero = cfg.hero || {};
  const heroButton = q('.hero-actions .btn-primary');
  text(heroButton, hero.button_label || 'Selengkapnya'); attr(heroButton, 'href', hero.button_url || '#fitur');
  const crest = q('.hero-crest-caption');
  if (crest) crest.innerHTML = (hero.crest_caption || 'Pusat Siber Angkatan Darat') + '<br><b>“' + (hero.crest_motto || 'Satria Yudha Waskita') + '”</b>';

  if (Array.isArray(cfg.stats)) {
    qa('.stats-grid .stat').forEach((el, i) => { const item = cfg.stats[i]; if (!item) return; text(q('.stat-num', el), item.number); text(q('.stat-label', el), item.label); });
  }

  const featureSection = cfg.features_section || {};
  const featureRoot = q('#fitur');
  if (featureRoot) { text(q('.section-head .eyebrow', featureRoot), featureSection.eyebrow); text(q('.section-head h3', featureRoot), featureSection.title); text(q('.section-head p', featureRoot), featureSection.description); }

  const aboutSection = cfg.about_section || {};
  const aboutRoot = q('#tentang-pussiberad');
  if (aboutRoot) { text(q('.eyebrow', aboutRoot), aboutSection.eyebrow); text(q('.about-top h3', aboutRoot), aboutSection.title); }

  if (cfg.footer) {
    text(q('.footer-desc'), cfg.footer.description);
    const copy = q('.footer-bottom'); if (copy && cfg.footer.copyright) text(copy, cfg.footer.copyright);
  }

  if (cfg.colors) {
    const root = document.documentElement;
    Object.entries(cfg.colors).forEach(([key, value]) => { if (value) root.style.setProperty('--' + key, value); });
  }

  if (cfg.background_url) {
    const heroBg = q('.hero-stats-bg');
    if (heroBg) heroBg.style.backgroundImage = "linear-gradient(115deg, var(--hero-ov-1) 0%, var(--hero-ov-2) 32%, var(--hero-ov-3) 58%, var(--hero-ov-4) 100%), linear-gradient(to top, var(--hero-ov-top) 0%, var(--hero-ov-top-fade) 26%), url('" + cfg.background_url.replace(/'/g, "\\'") + "')";
  }
})();
