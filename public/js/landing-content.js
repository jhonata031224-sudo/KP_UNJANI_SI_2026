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

  // ---------- Makna Logo: klik nomor/kartu untuk membuka detail ----------
  function initMaknaLogoDropdown(){
    const overlay=q('#maknaLogoOverlay');
    if(!overlay) return;

    const style=document.createElement('style');
    style.textContent=`
      .makna-logo-point{z-index:20;}
      .makna-logo-point-card{cursor:pointer;position:relative;transition:border-color .2s ease,box-shadow .2s ease;}
      .makna-logo-point-card:hover{border-color:var(--gold-bright);}
      .makna-logo-point.is-expanded .makna-logo-point-card{border-color:var(--gold-bright);}
      .makna-logo-point-title,.makna-logo-point-desc{display:none!important;}
      .makna-logo-dropdown{position:absolute;top:calc(100% + 10px);left:0;width:100%;min-width:280px;padding:14px 16px;background:var(--panel);color:var(--text);border:1px solid var(--gold-bright);border-radius:12px;box-shadow:0 14px 34px rgba(0,0,0,.22);opacity:0;visibility:hidden;transform:translateY(-6px);transition:opacity .2s ease,transform .2s ease,visibility .2s ease;z-index:100;pointer-events:none;}
      .makna-logo-point.is-left .makna-logo-dropdown{left:auto;right:0;}
      .makna-logo-point.is-bottom .makna-logo-dropdown{left:50%;right:auto;transform:translate(-50%,-6px);}
      .makna-logo-point.is-expanded .makna-logo-dropdown{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto;}
      .makna-logo-point.is-expanded .makna-logo-dropdown{display:block;}
      .makna-logo-point.is-expanded .makna-logo-point-card::after{content:'⌃';position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:14px;color:var(--gold-bright);}
      .makna-logo-dropdown-title{font-family:var(--mono);font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--gold-bright);margin-bottom:7px;}
      .makna-logo-dropdown-desc{font-family:var(--body);font-size:13px;line-height:1.6;color:var(--text-muted);}
      @media(max-width:760px){
        .makna-logo-dropdown,.makna-logo-point.is-left .makna-logo-dropdown,.makna-logo-point.is-bottom .makna-logo-dropdown{position:relative;top:auto;left:auto;right:auto;width:100%;min-width:0;margin-top:8px;transform:none!important;}
      }
    `;
    document.head.appendChild(style);

    qa('.makna-logo-point',overlay).forEach((point,index)=>{
      const card=q('.makna-logo-point-card',point);
      const title=q('.makna-logo-point-title',point);
      const desc=q('.makna-logo-point-desc',point);
      if(!card) return;

      card.setAttribute('role','button');
      card.setAttribute('tabindex','0');
      card.setAttribute('aria-expanded','false');
      card.setAttribute('aria-label','Lihat makna poin '+(index+1));

      let dropdown=q('.makna-logo-dropdown',point);
      if(!dropdown){
        dropdown=document.createElement('div');
        dropdown.className='makna-logo-dropdown';
        dropdown.innerHTML='<div class="makna-logo-dropdown-title"></div><div class="makna-logo-dropdown-desc"></div>';
        point.appendChild(dropdown);
      }
      text(q('.makna-logo-dropdown-title',dropdown),title?.textContent?.trim()||('Poin '+(index+1)));
      text(q('.makna-logo-dropdown-desc',dropdown),desc?.textContent?.trim()||'');

      const toggle=()=>{
        const open=point.classList.contains('is-expanded');
        qa('.makna-logo-point.is-expanded',overlay).forEach(other=>{
          other.classList.remove('is-expanded');
          const otherCard=q('.makna-logo-point-card',other);
          if(otherCard) otherCard.setAttribute('aria-expanded','false');
        });
        if(!open){
          point.classList.add('is-expanded');
          card.setAttribute('aria-expanded','true');
        }
      };

      card.addEventListener('click',toggle);
      card.addEventListener('keydown',e=>{
        if(e.key==='Enter'||e.key===' '){e.preventDefault();toggle();}
      });
    });

    const closeAll=()=>qa('.makna-logo-point.is-expanded',overlay).forEach(point=>{
      point.classList.remove('is-expanded');
      const card=q('.makna-logo-point-card',point);
      if(card)card.setAttribute('aria-expanded','false');
    });

    overlay.addEventListener('click',e=>{
      if(!e.target.closest('.makna-logo-point')) closeAll();
    });
    document.addEventListener('keydown',e=>{
      if(e.key==='Escape') closeAll();
    });
    const close=q('#maknaLogoClose');
    if(close)close.addEventListener('click',closeAll);
  }

  fetch('/landing-config',{headers:{Accept:'application/json'}}).then(r=>r.ok?r.json():null).then(d=>{if(d&&d.config)apply(d.config);}).catch(()=>{});
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initMaknaLogoDropdown,{once:true});
  else initMaknaLogoDropdown();
})();
