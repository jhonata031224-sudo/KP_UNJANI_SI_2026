(function () {
  const q=(s,r=document)=>r.querySelector(s), qa=(s,r=document)=>Array.from(r.querySelectorAll(s));
  const text=(e,v)=>{if(e&&v!=null)e.textContent=v;};
  const attr=(e,n,v)=>{if(e&&v!=null)e.setAttribute(n,v);};

  function apply(cfg){
    if(cfg.meta){
      document.title=cfg.meta.title||document.title;
      const m=q('meta[name="description"]');
      if(m&&cfg.meta.description)m.content=cfg.meta.description;
    }
    const brand=cfg.brand||{};
    qa('.logo-text b').forEach(el=>{
      el.innerHTML='';
      el.append(document.createTextNode(brand.name||'SIBER'));
      const s=document.createElement('span');
      s.textContent=brand.accent||'AD';
      el.append(s);
    });
    qa('.logo-text small').forEach(el=>text(el,brand.tagline||'Pussiberad · TNI AD'));
    if(cfg.logo_url){
      qa('.logo-badge img,.hero-crest .mark-plate img,#loader .mark-plate img,#tentang-pussiberad img').forEach(img=>attr(img,'src',cfg.logo_url));
      const f=q('link[rel="icon"]');
      if(f)f.href=cfg.logo_url;
    }
    if(Array.isArray(cfg.nav))qa('.nav-links a').forEach((el,i)=>{
      const x=cfg.nav[i];
      if(x){text(el,x.label);attr(el,'href',x.url||'#');}
    });
    const hero=cfg.hero||{},hb=q('.hero-actions .btn-primary');
    text(hb,hero.button_label||'Selengkapnya');
    attr(hb,'href',hero.button_url||'#fitur');
    const crest=q('.hero-crest-caption');
    if(crest)crest.innerHTML=(hero.crest_caption||'Pusat Siber Angkatan Darat')+(hero.crest_motto?('<br><b>“'+hero.crest_motto+'”</b>'):'');
    if(Array.isArray(cfg.stats))qa('.stats-grid .stat').forEach((el,i)=>{
      const x=cfg.stats[i];
      if(x){text(q('.stat-num',el),x.number);text(q('.stat-label',el),x.label);}
    });
    const fs=cfg.features_section||{},fr=q('#fitur');
    if(fr){text(q('.section-head .eyebrow',fr),fs.eyebrow);text(q('.section-head h3',fr),fs.title);text(q('.section-head p',fr),fs.description);}
    const as=cfg.about_section||{},ar=q('#tentang-pussiberad');
    if(ar){text(q('.eyebrow',ar),as.eyebrow);text(q('.about-top h3',ar),as.title);}
    if(cfg.footer){
      text(q('.footer-desc'),cfg.footer.description);
      const c=q('.footer-bottom');
      if(c&&cfg.footer.copyright)text(c,cfg.footer.copyright);
    }
    if(cfg.colors)Object.entries(cfg.colors).forEach(([k,v])=>{if(v)document.documentElement.style.setProperty('--'+k,v);});
    if(cfg.background_url){
      const bg=q('.hero-stats-bg');
      if(bg)bg.style.backgroundImage="linear-gradient(115deg,var(--hero-ov-1) 0%,var(--hero-ov-2) 32%,var(--hero-ov-3) 58%,var(--hero-ov-4) 100%),linear-gradient(to top,var(--hero-ov-top) 0%,var(--hero-ov-top-fade) 26%),url('"+cfg.background_url.replace(/'/g,"\\'")+"')";
    }
  }

  function initMaknaLogoDropdown(){
    const overlay=q('#maknaLogoOverlay');
    if(!overlay)return;
    if(!document.getElementById('makna-logo-dropdown-style')){
      const style=document.createElement('style');style.id='makna-logo-dropdown-style';
      style.textContent=`
        #maknaLogoOverlay,#maknaLogoOverlay .makna-logo-stage{overflow:visible!important;}
        #maknaLogoOverlay .makna-logo-point{z-index:20!important;isolation:auto!important;}
        #maknaLogoOverlay .makna-logo-point.is-expanded{z-index:999999!important;}
        #maknaLogoOverlay .makna-logo-point-card{cursor:pointer;position:relative;transition:border-color .2s ease,box-shadow .2s ease;}
        #maknaLogoOverlay .makna-logo-point-card:hover{border-color:#FF9800!important;box-shadow:0 14px 32px rgba(0,0,0,.20)!important;}
        #maknaLogoOverlay .makna-logo-point.is-expanded .makna-logo-point-card{border-color:#FF9800!important;}
        #maknaLogoOverlay .makna-logo-point-title,#maknaLogoOverlay .makna-logo-point-desc{display:none!important;}
        #maknaLogoOverlay .makna-logo-dropdown{
          position:absolute!important;top:calc(100% + 12px)!important;left:0!important;right:auto!important;
          width:100%!important;min-width:300px!important;max-width:390px!important;padding:15px 17px!important;
          background:#fff!important;color:#1b2620!important;border:1.5px solid #FF9800!important;border-radius:14px!important;
          box-shadow:0 20px 44px rgba(0,0,0,.24)!important;opacity:0;visibility:hidden!important;
          transform:translateY(-6px);transition:opacity .2s ease,transform .2s ease,visibility .2s ease!important;
          z-index:1000000!important;pointer-events:none!important;
        }
        #maknaLogoOverlay .makna-logo-point.is-left .makna-logo-dropdown{left:auto!important;right:0!important;}
        #maknaLogoOverlay .makna-logo-point.is-bottom .makna-logo-dropdown{left:50%!important;right:auto!important;transform:translate(-50%,-6px);}
        #maknaLogoOverlay .makna-logo-point.is-drop-up .makna-logo-dropdown{top:auto!important;bottom:calc(100% + 12px)!important;transform:translateY(6px)!important;}
        #maknaLogoOverlay .makna-logo-point.is-expanded .makna-logo-dropdown{opacity:1;visibility:visible!important;transform:translateY(0)!important;pointer-events:auto!important;display:block!important;}
        #maknaLogoOverlay .makna-logo-point.is-expanded.is-bottom .makna-logo-dropdown{transform:translate(-50%,0)!important;}
        #maknaLogoOverlay .makna-logo-point.is-expanded.is-drop-up .makna-logo-dropdown{transform:translateY(0)!important;}
        #maknaLogoOverlay .makna-logo-point.is-expanded .makna-logo-point-card::after{content:'⌃';position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:14px;color:#FF9800;}
        #maknaLogoOverlay .makna-logo-point.is-expanded.is-drop-up .makna-logo-point-card::after{content:'⌄';}
        #maknaLogoOverlay .makna-logo-dropdown-title{font-family:var(--mono);font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#FF9800;margin-bottom:7px;}
        #maknaLogoOverlay .makna-logo-dropdown-desc{font-family:var(--body);font-size:13px;line-height:1.6;color:#465a4d;}
        @media(max-width:760px){
          #maknaLogoOverlay{overflow:auto!important;}
          #maknaLogoOverlay .makna-logo-stage{overflow:visible!important;}
          #maknaLogoOverlay .makna-logo-dropdown,#maknaLogoOverlay .makna-logo-point.is-left .makna-logo-dropdown,#maknaLogoOverlay .makna-logo-point.is-bottom .makna-logo-dropdown,#maknaLogoOverlay .makna-logo-point.is-drop-up .makna-logo-dropdown{
            position:relative!important;top:auto!important;bottom:auto!important;left:auto!important;right:auto!important;width:100%!important;max-width:none!important;min-width:0!important;margin-top:8px!important;transform:none!important;
          }
        }
      `;document.head.appendChild(style);
    }
    qa('.makna-logo-point',overlay).forEach((point,index)=>{
      const card=q('.makna-logo-point-card',point),title=q('.makna-logo-point-title',point),desc=q('.makna-logo-point-desc',point);
      if(!card)return;
      const number=Number((q('.makna-logo-point-num',point)?.textContent||'').trim())||index+1;
      point.dataset.mlNumber=String(number);
      card.setAttribute('role','button');card.setAttribute('tabindex','0');card.setAttribute('aria-expanded','false');card.setAttribute('aria-label','Lihat makna poin '+number);
      let dropdown=q('.makna-logo-dropdown',point);
      if(!dropdown){
        dropdown=document.createElement('div');dropdown.className='makna-logo-dropdown';
        dropdown.innerHTML='<div class="makna-logo-dropdown-title"></div><div class="makna-logo-dropdown-desc"></div>';point.appendChild(dropdown);
      }
      text(q('.makna-logo-dropdown-title',dropdown),title?.textContent?.trim()||('Poin '+number));
      text(q('.makna-logo-dropdown-desc',dropdown),desc?.textContent?.trim()||'');
      const toggle=()=>{
        const open=point.classList.contains('is-expanded');
        qa('.makna-logo-point.is-expanded',overlay).forEach(other=>{other.classList.remove('is-expanded');const c=q('.makna-logo-point-card',other);if(c)c.setAttribute('aria-expanded','false');});
        if(!open){point.classList.add('is-expanded');card.setAttribute('aria-expanded','true');}
      };
      card.addEventListener('click',toggle);
      card.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();toggle();}});
    });
    const closeAll=()=>qa('.makna-logo-point.is-expanded',overlay).forEach(point=>{point.classList.remove('is-expanded');const card=q('.makna-logo-point-card',point);if(card)card.setAttribute('aria-expanded','false');});
    overlay.addEventListener('click',e=>{if(!e.target.closest('.makna-logo-point'))closeAll();});
    document.addEventListener('keydown',e=>{if(e.key==='Escape')closeAll();});
    const close=q('#maknaLogoClose');if(close)close.addEventListener('click',closeAll);
  }

  function syncMaknaLogoPrototype(){
    const overlay=q('#maknaLogoOverlay'),stage=q('#maknaLogoStage');
    if(!overlay||!stage)return;
    if(!document.getElementById('makna-logo-prototype-override')){
      const style=document.createElement('style');style.id='makna-logo-prototype-override';
      style.textContent=`
        #maknaLogoOverlay{background:#fff!important;padding:0!important;}
        #maknaLogoOverlay .makna-logo-stage{position:relative!important;width:min(1320px,96vw)!important;height:min(790px,90vh)!important;aspect-ratio:auto!important;display:block!important;margin:0 auto!important;overflow:visible!important;}
        #maknaLogoOverlay .makna-logo-crest{position:absolute!important;z-index:1!important;background:transparent!important;width:300px!important;height:300px!important;top:45%!important;left:50%!important;transform:translate(-50%,-50%)!important;transition:none!important;}
        #maknaLogoOverlay .makna-logo-crest img{width:100%!important;height:100%!important;object-fit:contain!important;display:block!important;mix-blend-mode:multiply!important;}
        #maknaLogoOverlay .makna-logo-point{position:absolute!important;height:48px!important;transform:translateY(-50%)!important;opacity:1!important;z-index:20!important;}
        #maknaLogoOverlay .makna-logo-point-card{width:100%!important;min-width:0!important;height:48px!important;flex:1 1 auto!important;box-sizing:border-box!important;background:#fffdf7!important;border:1.5px solid #FF9800!important;box-shadow:0 10px 28px rgba(0,0,0,.16)!important;cursor:pointer!important;}
        #maknaLogoOverlay .makna-logo-point-num{width:38px!important;height:38px!important;flex:0 0 38px!important;font-size:15px!important;background:#FF9800!important;border:0!important;box-shadow:0 0 0 4px #fff,0 6px 16px rgba(0,0,0,.18)!important;z-index:12!important;}
        #maknaLogoOverlay .makna-logo-point-title{display:block!important;font-family:var(--mono)!important;font-size:10.5px!important;font-weight:700!important;letter-spacing:.08em!important;text-transform:uppercase!important;color:#FF9800!important;margin:0!important;white-space:nowrap!important;overflow:visible!important;text-overflow:clip!important;}
        #maknaLogoOverlay .makna-logo-point-desc{display:none!important;}
        #maknaLogoOverlay .makna-logo-point.is-left .makna-logo-point-card{justify-content:flex-end!important;text-align:right!important;padding:0 18px!important;border-radius:999px!important;}
        #maknaLogoOverlay .makna-logo-point.is-right .makna-logo-point-card{justify-content:flex-start!important;text-align:left!important;padding:0 18px!important;border-radius:999px!important;}
        #maknaLogoOverlay .makna-logo-point.is-left .makna-logo-point-num{margin-left:-15px!important;margin-right:0!important;}
        #maknaLogoOverlay .makna-logo-point.is-right .makna-logo-point-num{margin-right:-15px!important;margin-left:0!important;}
        #maknaLogoOverlay .makna-logo-point.is-bottom{height:48px!important;left:60%!important;right:auto!important;top:91%!important;transform:translate(-50%,-50%)!important;}
        #maknaLogoOverlay .makna-logo-point.is-bottom .makna-logo-point-card{border-radius:999px!important;justify-content:center!important;text-align:center!important;padding:0 18px!important;}
        #maknaLogoOverlay .makna-logo-lines{z-index:5!important;pointer-events:none!important;overflow:visible!important;}
        #maknaLogoOverlay .makna-logo-lines line,#maknaLogoOverlay .makna-logo-lines polyline{stroke:#FF9800!important;stroke-opacity:1!important;stroke-width:1.6px!important;fill:none!important;vector-effect:non-scaling-stroke!important;}
        #maknaLogoOverlay .makna-logo-anchor-dot{z-index:15!important;width:8px!important;height:8px!important;background:#FF9800!important;box-shadow:0 0 0 3px #fff,0 1px 3px rgba(0,0,0,.25)!important;}
        #maknaLogoOverlay .makna-logo-eyebrow{top:28px!important;left:32px!important;padding:9px 20px!important;border-radius:999px!important;background:#fff!important;border:1.5px solid #FF9800!important;color:#FF9800!important;font-family:var(--mono)!important;font-size:12.5px!important;font-weight:700!important;letter-spacing:.16em!important;box-shadow:none!important;}
        #maknaLogoOverlay .makna-logo-close{top:24px!important;right:32px!important;width:44px!important;height:44px!important;border-radius:12px!important;background:#f4f2ea!important;box-shadow:none!important;}
        @media(max-width:760px){
          #maknaLogoOverlay{align-items:flex-start!important;padding:78px 16px 32px!important;overflow:auto!important;}
          #maknaLogoOverlay .makna-logo-stage{width:100%!important;height:auto!important;min-height:0!important;display:flex!important;flex-direction:column!important;align-items:center!important;gap:22px!important;}
          #maknaLogoOverlay .makna-logo-crest{position:relative!important;top:auto!important;left:auto!important;width:min(46vw,190px)!important;height:min(46vw,190px)!important;transform:none!important;margin:0 auto!important;}
          #maknaLogoOverlay .makna-logo-lines,#maknaLogoOverlay .makna-logo-anchor-dot{display:none!important;}
          #maknaLogoOverlay .makna-logo-point{position:relative!important;inset:auto!important;width:100%!important;max-width:100%!important;height:auto!important;transform:none!important;}
          #maknaLogoOverlay .makna-logo-point.is-bottom{width:100%!important;max-width:100%!important;left:auto!important;top:auto!important;transform:none!important;}
        }
      `;document.head.appendChild(style);
    }
    const layout={
      1:{side:'right',x:70,top:15,width:350},
      2:{side:'left',x:4,top:24,width:300},
      3:{side:'right',x:72,top:33,width:360},
      4:{side:'left',x:9,top:43,width:390},
      5:{side:'right',x:69,top:50,width:390},
      6:{side:'right',x:73,top:64,width:350},
      7:{side:'left',x:7,top:63,width:370},
      8:{side:'left',x:14,top:79,width:340},
      9:{side:'right',x:69,top:79,width:320},
      10:{side:'bottom',x:60,top:91,width:300}
    };
    const anchors={1:[49.23,29.99],2:[44.59,33.38],3:[52.47,40.22],4:[46.98,35.83],5:[52.72,35.89],6:[49.43,40.83],7:[49.43,46.79],8:[45.48,52.15],9:[49.30,52.15],10:[53.32,52.15]};
    qa('.makna-logo-point',stage).forEach((point,index)=>{
      const number=Number(point.dataset.mlNumber||(q('.makna-logo-point-num',point)?.textContent||'').trim())||index+1;
      const p=layout[number];if(!p)return;
      point.dataset.mlNumber=String(number);point.classList.remove('is-left','is-right','is-bottom','is-drop-up');point.classList.add('is-'+p.side);
      point.style.top=p.top+'%';point.style.bottom='auto';point.style.left=p.x+'%';point.style.right='auto';point.style.width=p.width+'px';point.style.maxWidth=p.width+'px';
      if(p.top>=78)point.classList.add('is-drop-up');
      const a=anchors[number];
      const dot=q('.makna-logo-anchor-dot[data-ml-number="'+number+'"]',stage)||qa('.makna-logo-anchor-dot',stage)[number-1];
      if(a&&dot){dot.dataset.mlNumber=String(number);dot.style.left=a[0]+'%';dot.style.top=a[1]+'%';}
    });
    const redraw=()=>{
      const svg=q('#maknaLogoLines',stage);if(!svg)return;
      const stageRect=stage.getBoundingClientRect();
      qa('.makna-logo-point',stage).forEach((point,index)=>{
        const number=Number(point.dataset.mlNumber||(q('.makna-logo-point-num',point)?.textContent||'').trim())||index+1;
        const badge=q('.makna-logo-point-num',point),dot=q('.makna-logo-anchor-dot[data-ml-number="'+number+'"]',stage)||qa('.makna-logo-anchor-dot',stage)[index];
        if(!badge||!dot)return;
        const br=badge.getBoundingClientRect(),dr=dot.getBoundingClientRect();
        const isLeft=point.classList.contains('is-left'),isBottom=point.classList.contains('is-bottom');
        const x2=(dr.left+dr.width/2-stageRect.left)/stageRect.width*100,y2=(dr.top+dr.height/2-stageRect.top)/stageRect.height*100;
        const startPxX=isBottom?(br.left+br.width/2):(isLeft?br.left:br.right);
        const x1=(startPxX-stageRect.left)/stageRect.width*100,y1=(br.top+br.height/2-stageRect.top)/stageRect.height*100;
        let route;
        if(number===10){
          route=[[x1,y1],[x2,y2]];
        }else if([2,3,6,7,8,9].includes(number)){
          const direction=isLeft?1:-1;
          const bendX=x1+direction*5;
          route=[[x1,y1],[bendX,y1],[x2,y2]];
        }else{
          route=[[x1,y1],[x2,y2]];
        }
        svg.querySelectorAll('[data-ml-line="'+index+'"]').forEach(el=>el.remove());
        if(route.length===2){
          const line=document.createElementNS('http://www.w3.org/2000/svg','line');
          line.setAttribute('data-ml-line',String(index));line.setAttribute('x1',route[0][0]);line.setAttribute('y1',route[0][1]);line.setAttribute('x2',route[1][0]);line.setAttribute('y2',route[1][1]);line.setAttribute('stroke','#FF9800');line.setAttribute('fill','none');svg.appendChild(line);
        }else{
          const poly=document.createElementNS('http://www.w3.org/2000/svg','polyline');
          poly.setAttribute('data-ml-line',String(index));poly.setAttribute('points',route.map(p=>p.join(',')).join(' '));poly.setAttribute('stroke','#FF9800');poly.setAttribute('fill','none');svg.appendChild(poly);
        }
      });
    };
    requestAnimationFrame(()=>requestAnimationFrame(redraw));
    if(!stage.dataset.mlResizeBound){window.addEventListener('resize',redraw);stage.dataset.mlResizeBound='1';}
  }

  fetch('/landing-config',{headers:{Accept:'application/json'}}).then(r=>r.ok?r.json():null).then(d=>{if(d&&d.config)apply(d.config);}).catch(()=>{});
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initMaknaLogoDropdown,{once:true});else initMaknaLogoDropdown();
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',syncMaknaLogoPrototype,{once:true});else syncMaknaLogoPrototype();
})();