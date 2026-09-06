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

  // ---------- Makna Logo: klik nomor/kartu untuk membuka detail ----------
  function initMaknaLogoDropdown(){
    const overlay=q('#maknaLogoOverlay');
    if(!overlay)return;

    if(!document.getElementById('makna-logo-dropdown-style')){
      const style=document.createElement('style');
      style.id='makna-logo-dropdown-style';
      style.textContent=`
        #maknaLogoOverlay{overflow:visible!important;}
        #maknaLogoOverlay .makna-logo-stage{overflow:visible!important;}
        #maknaLogoOverlay .makna-logo-point{z-index:10!important;isolation:auto!important;}
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
        #maknaLogoOverlay .makna-logo-point.is-expanded .makna-logo-point-card::after{content:'⌃';position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:14px;color:#FF9800;}
        #maknaLogoOverlay .makna-logo-point.is-expanded.is-drop-up .makna-logo-point-card::after{content:'⌄';}
        #maknaLogoOverlay .makna-logo-dropdown-title{font-family:var(--mono);font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#FF9800;margin-bottom:7px;}
        #maknaLogoOverlay .makna-logo-dropdown-desc{font-family:var(--body);font-size:13px;line-height:1.6;color:#465a4d;}
        @media(max-width:760px){
          #maknaLogoOverlay{overflow:auto!important;}
          #maknaLogoOverlay .makna-logo-stage{overflow:visible!important;}
          #maknaLogoOverlay .makna-logo-dropdown,
          #maknaLogoOverlay .makna-logo-point.is-left .makna-logo-dropdown,
          #maknaLogoOverlay .makna-logo-point.is-bottom .makna-logo-dropdown,
          #maknaLogoOverlay .makna-logo-point.is-drop-up .makna-logo-dropdown{
            position:relative!important;top:auto!important;bottom:auto!important;left:auto!important;right:auto!important;width:100%!important;max-width:none!important;
            min-width:0!important;margin-top:8px!important;transform:none!important;
          }
        }
      `;
      document.head.appendChild(style);
    }

    qa('.makna-logo-point',overlay).forEach((point,index)=>{
      const card=q('.makna-logo-point-card',point);
      const title=q('.makna-logo-point-title',point);
      const desc=q('.makna-logo-point-desc',point);
      if(!card)return;

      const number=Number((q('.makna-logo-point-num',point)?.textContent||'').trim())||index+1;
      point.dataset.mlNumber=String(number);
      card.setAttribute('role','button');
      card.setAttribute('tabindex','0');
      card.setAttribute('aria-expanded','false');
      card.setAttribute('aria-label','Lihat makna poin '+number);

      let dropdown=q('.makna-logo-dropdown',point);
      if(!dropdown){
        dropdown=document.createElement('div');
        dropdown.className='makna-logo-dropdown';
        dropdown.innerHTML='<div class="makna-logo-dropdown-title"></div><div class="makna-logo-dropdown-desc"></div>';
        point.appendChild(dropdown);
      }
      text(q('.makna-logo-dropdown-title',dropdown),title?.textContent?.trim()||('Poin '+number));
      text(q('.makna-logo-dropdown-desc',dropdown),desc?.textContent?.trim()||'');

      const toggle=()=>{
        const open=point.classList.contains('is-expanded');
        qa('.makna-logo-point.is-expanded',overlay).forEach(other=>{
          other.classList.remove('is-expanded');
          const otherCard=q('.makna-logo-point-card',other);
          if(otherCard)otherCard.setAttribute('aria-expanded','false');
        });
        if(!open){
          point.classList.add('is-expanded');
          card.setAttribute('aria-expanded','true');
        }
      };
      card.addEventListener('click',toggle);
      card.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();toggle();}});
    });

    const closeAll=()=>qa('.makna-logo-point.is-expanded',overlay).forEach(point=>{
      point.classList.remove('is-expanded');
      const card=q('.makna-logo-point-card',point);
      if(card)card.setAttribute('aria-expanded','false');
    });
    overlay.addEventListener('click',e=>{if(!e.target.closest('.makna-logo-point'))closeAll();});
    document.addEventListener('keydown',e=>{if(e.key==='Escape')closeAll();});
    const close=q('#maknaLogoClose');
    if(close)close.addEventListener('click',closeAll);
  }

  // ---------- Makna Logo: sebar kartu mengisi ruang kosong, anchor logo tetap ----------
  function syncMaknaLogoPrototype(){
    const overlay=q('#maknaLogoOverlay');
    const stage=q('#maknaLogoStage');
    if(!overlay||!stage)return;

    if(!document.getElementById('makna-logo-prototype-override')){
      const style=document.createElement('style');
      style.id='makna-logo-prototype-override';
      style.textContent=`
        #maknaLogoOverlay{background:#fff!important;padding:0!important;}
        #maknaLogoOverlay .makna-logo-stage{
          position:relative!important;width:min(1220px,94vw)!important;height:min(760px,88vh)!important;
          aspect-ratio:auto!important;display:block!important;margin:0 auto!important;overflow:visible!important;
        }
        #maknaLogoOverlay .makna-logo-crest{
          position:absolute!important;z-index:1!important;background:transparent!important;
          width:230px!important;height:230px!important;top:42.76%!important;left:50%!important;
          transform:translate(-50%,-50%)!important;transition:none!important;
        }
        #maknaLogoOverlay .makna-logo-crest img{width:100%!important;height:100%!important;object-fit:contain!important;display:block!important;mix-blend-mode:multiply!important;}
        #maknaLogoOverlay .makna-logo-point{
          position:absolute!important;width:340px!important;max-width:340px!important;height:46px!important;
          transform:translateY(-50%)!important;opacity:1!important;z-index:10!important;
        }
        #maknaLogoOverlay .makna-logo-point-card{
          width:auto!important;min-width:0!important;height:46px!important;flex:1 1 auto!important;
          background:#fffdf7!important;border:1.5px solid #FF9800!important;
          box-shadow:0 10px 28px rgba(0,0,0,.16)!important;cursor:pointer!important;
        }
        #maknaLogoOverlay .makna-logo-point-num{
          width:36px!important;height:36px!important;font-size:15px!important;background:#FF9800!important;
          border:0!important;box-shadow:0 0 0 4px #fff,0 6px 16px rgba(0,0,0,.18)!important;z-index:12!important;
        }
        #maknaLogoOverlay .makna-logo-point-title{
          display:block!important;font-family:var(--mono)!important;font-size:10.5px!important;font-weight:700!important;
          letter-spacing:.08em!important;text-transform:uppercase!important;color:#FF9800!important;margin:0!important;
          white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;
        }
        #maknaLogoOverlay .makna-logo-point-desc{display:none!important;}
        #maknaLogoOverlay .makna-logo-point.is-left .makna-logo-point-card{justify-content:flex-end!important;text-align:right!important;padding:0 18px!important;border-radius:999px!important;}
        #maknaLogoOverlay .makna-logo-point.is-right .makna-logo-point-card{justify-content:flex-start!important;text-align:left!important;padding:0 18px!important;border-radius:999px!important;}
        #maknaLogoOverlay .makna-logo-point.is-left .makna-logo-point-num{margin-left:-15px!important;margin-right:0!important;}
        #maknaLogoOverlay .makna-logo-point.is-right .makna-logo-point-num{margin-right:-15px!important;margin-left:0!important;}
        #maknaLogoOverlay .makna-logo-point.is-bottom{width:260px!important;max-width:260px!important;height:46px!important;left:50%!important;right:auto!important;top:90%!important;transform:translate(-50%,-50%)!important;}
        #maknaLogoOverlay .makna-logo-point.is-bottom .makna-logo-point-card{border-radius:999px!important;justify-content:center!important;text-align:center!important;padding:0 18px!important;}
        #maknaLogoOverlay .makna-logo-lines{z-index:3!important;pointer-events:none!important;overflow:visible!important;}
        #maknaLogoOverlay .makna-logo-lines line,#maknaLogoOverlay .makna-logo-lines polyline{
          stroke:#FF9800!important;stroke-opacity:1!important;stroke-width:1.6px!important;fill:none!important;vector-effect:non-scaling-stroke!important;
        }
        #maknaLogoOverlay .makna-logo-anchor-dot{z-index:11!important;width:8px!important;height:8px!important;background:#FF9800!important;box-shadow:0 0 0 3px #fff,0 1px 3px rgba(0,0,0,.25)!important;}
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
      `;
      document.head.appendChild(style);
    }

    const layout={
      1:{side:'right',x:70,top:18},
      2:{side:'left',x:2,top:28},
      3:{side:'right',x:72,top:36},
      4:{side:'left',x:10,top:46},
      5:{side:'right',x:68,top:52},
      6:{side:'right',x:72,top:66},
      7:{side:'left',x:3,top:64},
      8:{side:'left',x:12,top:80},
      9:{side:'right',x:69,top:81},
      10:{side:'bottom',x:50,top:90}
    };

    const anchors={
      1:[49.23,29.99],2:[44.59,33.38],3:[52.47,40.22],4:[46.98,35.83],5:[52.72,35.89],
      6:[49.43,40.83],7:[49.43,46.79],8:[45.48,52.15],9:[49.30,52.15],10:[53.32,52.15]
    };

    qa('.makna-logo-point',stage).forEach((point,index)=>{
      const number=Number(point.dataset.mlNumber||(q('.makna-logo-point-num',point)?.textContent||'').trim())||index+1;
      const p=layout[number];
      if(!p)return;
      point.dataset.mlNumber=String(number);
      point.classList.remove('is-left','is-right','is-bottom','is-drop-up');
      point.classList.add('is-'+p.side);
      point.style.top=p.top+'%';
      point.style.bottom='auto';
      point.style.left=p.x+'%';
      point.style.right='auto';
      if(p.top>=78)point.classList.add('is-drop-up');

      const a=anchors[number];
      const dot=q('.makna-logo-anchor-dot[data-ml-number="'+number+'"]',stage)||qa('.makna-logo-anchor-dot',stage)[number-1];
      if(a&&dot){
        dot.dataset.mlNumber=String(number);
        dot.style.left=a[0]+'%';
        dot.style.top=a[1]+'%';
      }
    });

    const redraw=()=>{
      const svg=q('#maknaLogoLines',stage);
      const stageRect=stage.getBoundingClientRect();
      if(!svg)return;

      qa('.makna-logo-point',stage).forEach((point,index)=>{
        const number=Number(point.dataset.mlNumber||(q('.makna-logo-point-num',point)?.textContent||'').trim())||index+1;
        const badge=q('.makna-logo-point-num',point);
        const oldLine=svg.querySelector('line[data-ml-line="'+index+'"]');
        const poly=svg.querySelector('polyline[data-ml-line="'+index+'"]');
        const dot=q('.makna-logo-anchor-dot[data-ml-number="'+number+'"]',stage)||qa('.makna-logo-anchor-dot',stage)[index];
        if(!badge||!dot)return;

        let connector=poly;
        if(!connector){
          connector=document.createElementNS('http://www.w3.org/2000/svg','polyline');
          connector.setAttribute('data-ml-line',String(index));
          connector.setAttribute('fill','none');
          connector.setAttribute('stroke','#FF9800');
          if(oldLine)svg.replaceChild(connector,oldLine);
          else svg.appendChild(connector);
        }

        const br=badge.getBoundingClientRect(),dr=dot.getBoundingClientRect();
        const isLeft=point.classList.contains('is-left');
        const isBottom=point.classList.contains('is-bottom');
        const x2=(dr.left+dr.width/2-stageRect.left)/stageRect.width*100;
        const y2=(dr.top+dr.height/2-stageRect.top)/stageRect.height*100;
        const startPxX=isBottom?(br.left+br.width/2):(isLeft?br.left:br.right);
        const x1=(startPxX-stageRect.left)/stageRect.width*100;
        const y1=(br.top+br.height/2-stageRect.top)/stageRect.height*100;

        let points;
        if(isBottom){
          const bendY=y1-8;
          const bendX=x2+(number%2===0?4:-4);
          points=`${x1},${y1} ${x1},${bendY} ${bendX},${bendY} ${x2},${y2}`;
        }else{
          const direction=isLeft?1:-1;
          const spread=number%3===0?12:(number%3===1?18:25);
          const bendX=x1+direction*spread;
          const bendY=y1+(y2-y1)*(number%2===0?0.62:0.38);
          const bendX2=x2+direction*(number%2===0?5:-5);
          points=`${x1},${y1} ${bendX},${y1} ${bendX},${bendY} ${bendX2},${bendY} ${x2},${y2}`;
        }
        connector.setAttribute('points',points);
      });
    };

    requestAnimationFrame(()=>requestAnimationFrame(redraw));
    if(!stage.dataset.mlResizeBound){
      window.addEventListener('resize',redraw);
      stage.dataset.mlResizeBound='1';
    }
  }

  fetch('/landing-config',{headers:{Accept:'application/json'}})
    .then(r=>r.ok?r.json():null)
    .then(d=>{if(d&&d.config)apply(d.config);})
    .catch(()=>{});

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initMaknaLogoDropdown,{once:true});
  else initMaknaLogoDropdown();
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',syncMaknaLogoPrototype,{once:true});
  else syncMaknaLogoPrototype();
})();