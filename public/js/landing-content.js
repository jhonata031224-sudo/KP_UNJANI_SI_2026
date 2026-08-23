(function () {
  const q=(s,r=document)=>r.querySelector(s), qa=(s,r=document)=>Array.from(r.querySelectorAll(s));
  const text=(e,v)=>{if(e&&v!=null)e.textContent=v;}; const attr=(e,n,v)=>{if(e&&v!=null)e.setAttribute(n,v);};
  function apply(cfg){
    if(cfg.meta){document.title=cfg.meta.title||document.title;const m=q('meta[name="description"]');if(m&&cfg.meta.description)m.content=cfg.meta.description;}
    const brand=cfg.brand||{};
    qa('.logo-text b').forEach(el=>{el.innerHTML='';el.append(document.createTextNode(brand.name||'SIBER'));const s=document.createElement('span');s.textContent=brand.accent||'AD';el.append(s);});
    qa('.logo-text small').forEach(el=>text(el,brand.tagline||'Pussiberad · TNI AD'));
    if(cfg.logo_url){qa('.logo-badge img,.hero-crest .mark-plate img,#loader .mark-plate img,#tentang-pussiberad img').forEach(img=>attr(img,'src',cfg.logo_url));const f=q('link[rel="icon"]');if(f)f.href=cfg.logo_url;}
    if(Array.isArray(cfg.nav))qa('.nav-links a').forEach((el,i)=>{const x=cfg.nav[i];if(x){text(el,x.label);attr(el,'href',x.url||'#');}});
    const hero=cfg.hero||{},hb=q('.hero-actions .btn-primary');text(hb,hero.button_label||'Selengkapnya');attr(hb,'href',hero.button_url||'#fitur');
    const crest=q('.hero-crest-caption');if(crest)crest.innerHTML=(hero.crest_caption||'Pusat Siber Angkatan Darat')+(hero.crest_motto?('<br><b>“'+hero.crest_motto+'”</b>'):'');
    if(Array.isArray(cfg.stats))qa('.stats-grid .stat').forEach((el,i)=>{const x=cfg.stats[i];if(x){text(q('.stat-num',el),x.number);text(q('.stat-label',el),x.label);}});
    const fs=cfg.features_section||{},fr=q('#fitur');if(fr){text(q('.section-head .eyebrow',fr),fs.eyebrow);text(q('.section-head h3',fr),fs.title);text(q('.section-head p',fr),fs.description);}
    const as=cfg.about_section||{},ar=q('#tentang-pussiberad');if(ar){text(q('.eyebrow',ar),as.eyebrow);text(q('.about-top h3',ar),as.title);}
    if(cfg.footer){text(q('.footer-desc'),cfg.footer.description);const c=q('.footer-bottom');if(c&&cfg.footer.copyright)text(c,cfg.footer.copyright);}
    if(cfg.colors)Object.entries(cfg.colors).forEach(([k,v])=>{if(v)document.documentElement.style.setProperty('--'+k,v);});
    if(cfg.background_url){const bg=q('.hero-stats-bg');if(bg)bg.style.backgroundImage="linear-gradient(115deg,var(--hero-ov-1) 0%,var(--hero-ov-2) 32%,var(--hero-ov-3) 58%,var(--hero-ov-4) 100%),linear-gradient(to top,var(--hero-ov-top) 0%,var(--hero-ov-top-fade) 26%),url('"+cfg.background_url.replace(/'/g,"\\'")+"')";}
  }
  fetch('/landing-config',{headers:{Accept:'application/json'}}).then(r=>r.ok?r.json():null).then(d=>{if(d&&d.config)apply(d.config);}).catch(()=>{});
})();
