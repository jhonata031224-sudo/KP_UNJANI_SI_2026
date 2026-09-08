(function () {
  const q=(s,r=document)=>r.querySelector(s), qa=(s,r=document)=>Array.from(r.querySelectorAll(s));
  const text=(e,v)=>{if(e&&v!=null)e.textContent=v;};
  const attr=(e,n,v)=>{if(e&&v!=null)e.setAttribute(n,v);};

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

  // Panel detail makna logo (ganti dropdown lama yg nempel per-kartu) --
  // klik kartu bernomor manapun buka panel di TENGAH stage (posisi selalu
  // sama), lengkap navigasi Sebelumnya/Selanjutnya + titik-titik halaman.
  // CSS panel ada di welcome.blade.php (.makna-detail*); di sini cuma
  // style interaktif kartu (hover/aktif) yg tetap perlu !important karena
  // menimpa style .makna-logo-point-card dari syncMaknaLogoPrototype().
  function initMaknaLogoDetail(){
    const overlay=q('#maknaLogoOverlay');if(!overlay)return;
    const detail=q('#maknaDetail',overlay),backdrop=q('#maknaDetailBackdrop',overlay);
    const numEl=q('#maknaDetailNum',detail),titleEl=q('#maknaDetailTitle',detail),descEl=q('#maknaDetailDesc',detail);
    const prevBtn=q('#maknaDetailPrev',detail),nextBtn=q('#maknaDetailNext',detail),closeBtn=q('#maknaDetailClose',detail),dotsWrap=q('#maknaDetailDots',detail);
    if(!detail||!backdrop||!numEl||!titleEl||!descEl)return;

    if(!document.getElementById('makna-logo-detail-card-style')){
      const style=document.createElement('style');style.id='makna-logo-detail-card-style';style.textContent=`
        #maknaLogoOverlay .makna-logo-point-card{cursor:pointer!important;transition:border-color .2s ease,box-shadow .2s ease!important;}
        #maknaLogoOverlay .makna-logo-point-card:hover{border-color:#FF9800!important;box-shadow:0 14px 32px rgba(0,0,0,.20)!important;}
        #maknaLogoOverlay .makna-logo-point-card.is-active{border-color:#FF9800!important;box-shadow:0 0 0 3px rgba(255,152,0,.18),0 14px 32px rgba(0,0,0,.22)!important;}
      `;document.head.appendChild(style);
    }

    const points=qa('.makna-logo-point',overlay).map((point,index)=>{
      const number=Number((q('.makna-logo-point-num',point)?.textContent||'').trim())||index+1;
      point.dataset.mlNumber=String(number);
      const card=q('.makna-logo-point-card',point);
      if(card){card.setAttribute('role','button');card.setAttribute('tabindex','0');card.setAttribute('aria-haspopup','dialog');card.setAttribute('aria-label','Lihat makna poin '+number);}
      return{
        number,card,
        title:(q('.makna-logo-point-title',point)?.textContent||'').trim()||('Poin '+number),
        desc:(q('.makna-logo-point-desc',point)?.textContent||'').trim(),
      };
    }).sort((a,b)=>a.number-b.number);
    if(!points.length)return;

    dotsWrap.innerHTML='';
    points.forEach(p=>{const dot=document.createElement('span');dot.className='makna-detail-dot';dot.dataset.mlDot=String(p.number);dotsWrap.appendChild(dot);});
    const dots=qa('.makna-detail-dot',dotsWrap);

    let activeIndex=-1;
    function render(index){
      const p=points[index];if(!p)return;
      activeIndex=index;
      numEl.textContent=String(p.number).padStart(2,'0');
      titleEl.textContent=p.title;
      descEl.textContent=p.desc||'Keterangan belum diisi.';
      points.forEach(o=>o.card&&o.card.classList.toggle('is-active',o===p));
      dots.forEach(d=>d.classList.toggle('is-active',Number(d.dataset.mlDot)===p.number));
    }
    function open(index){render(index);detail.classList.add('open');}
    function close(){detail.classList.remove('open');points.forEach(o=>o.card&&o.card.classList.remove('is-active'));}
    function step(dir){if(activeIndex<0)return;render((activeIndex+dir+points.length)%points.length);}

    points.forEach((p,index)=>{
      if(!p.card)return;
      const trigger=()=>open(index);
      p.card.addEventListener('click',trigger);
      p.card.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();trigger();}});
    });
    closeBtn&&closeBtn.addEventListener('click',close);
    backdrop.addEventListener('click',close);
    prevBtn&&prevBtn.addEventListener('click',()=>step(-1));
    nextBtn&&nextBtn.addEventListener('click',()=>step(1));

    // Capture phase supaya Escape saat panel detail kebuka cuma nutup
    // panel ini dulu (gak sekalian nutup modal Makna Logo di baliknya) --
    // listener Escape utk modal luar (di <script> welcome.blade.php)
    // dipasang di bubble phase, jadi stopPropagation di sini mencegatnya.
    document.addEventListener('keydown',(e)=>{
      if(!detail.classList.contains('open'))return;
      if(e.key==='Escape'){e.stopPropagation();close();}
      else if(e.key==='ArrowLeft')step(-1);
      else if(e.key==='ArrowRight')step(1);
    },true);

    // Kalau modal Makna Logo (overlay luar) ditutup, panel detail ikut
    // direset instan supaya gak "nyangkut" kebuka pas modal dibuka lagi.
    const mo=new MutationObserver(()=>{if(!overlay.classList.contains('open'))close();});
    mo.observe(overlay,{attributes:true,attributeFilter:['class']});
  }

  function syncMaknaLogoPrototype(){
    const overlay=q('#maknaLogoOverlay'),stage=q('#maknaLogoStage');if(!overlay||!stage)return;
    if(!document.getElementById('makna-logo-prototype-override')){
      const style=document.createElement('style');style.id='makna-logo-prototype-override';style.textContent=`
        #maknaLogoOverlay{background:#fff!important;padding:0!important;}
        #maknaLogoOverlay .makna-logo-stage{position:relative!important;width:min(1320px,96vw)!important;height:min(790px,90vh)!important;aspect-ratio:auto!important;display:block!important;margin:0 auto!important;overflow:visible!important;}
        #maknaLogoOverlay .makna-logo-crest{position:absolute!important;z-index:1!important;background:transparent!important;width:300px!important;height:300px!important;top:45%!important;left:50%!important;transform:translate(-50%,-50%)!important;transition:none!important;}
        #maknaLogoOverlay .makna-logo-crest img{width:100%!important;height:100%!important;object-fit:contain!important;display:block!important;mix-blend-mode:multiply!important;}
        #maknaLogoOverlay .makna-logo-point{position:absolute!important;height:48px!important;transform:translateY(-50%)!important;opacity:1!important;z-index:20!important;}
        #maknaLogoOverlay .makna-logo-point-card{width:100%!important;min-width:0!important;height:48px!important;flex:1 1 auto!important;box-sizing:border-box!important;background:#fffdf7!important;border:1.5px solid #FF9800!important;box-shadow:0 10px 28px rgba(0,0,0,.16)!important;cursor:pointer!important;}
        #maknaLogoOverlay .makna-logo-point-num{width:38px!important;height:38px!important;flex:0 0 38px!important;font-size:15px!important;background:#FF9800!important;border:0!important;box-shadow:0 0 0 4px #fff,0 6px 16px rgba(0,0,0,.18)!important;z-index:12!important;}
        #maknaLogoOverlay .makna-logo-point-title{display:block!important;font-family:var(--mono)!important;font-size:10.5px!important;font-weight:700!important;letter-spacing:.08em!important;text-transform:uppercase!important;color:#FF9800!important;margin:0!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;max-width:100%!important;}
        #maknaLogoOverlay .makna-logo-point-desc{display:none!important;}
        #maknaLogoOverlay .makna-logo-point.is-left .makna-logo-point-card{justify-content:flex-end!important;text-align:right!important;padding:0 18px!important;border-radius:999px!important;}
        #maknaLogoOverlay .makna-logo-point.is-right .makna-logo-point-card{justify-content:flex-start!important;text-align:left!important;padding:0 18px!important;border-radius:999px!important;}
        #maknaLogoOverlay .makna-logo-point.is-left .makna-logo-point-num{margin-left:-15px!important;margin-right:0!important;}
        #maknaLogoOverlay .makna-logo-point.is-right .makna-logo-point-num{margin-right:-15px!important;margin-left:0!important;}
        #maknaLogoOverlay .makna-logo-point.is-bottom{height:48px!important;left:60%!important;right:auto!important;top:91%!important;transform:translate(-50%,-50%)!important;}
        #maknaLogoOverlay .makna-logo-point.is-bottom .makna-logo-point-card{border-radius:999px!important;justify-content:center!important;text-align:center!important;padding:0 18px!important;}
        #maknaLogoOverlay .makna-logo-lines{z-index:5!important;pointer-events:none!important;overflow:visible!important;}
        #maknaLogoOverlay .makna-logo-lines line,#maknaLogoOverlay .makna-logo-lines polyline{stroke:#FF9800!important;stroke-opacity:1!important;stroke-width:1.6px!important;fill:none!important;vector-effect:non-scaling-stroke!important;}
        #maknaLogoOverlay .makna-logo-anchor-dot{z-index:15!important;width:8px!important;height:8px!important;background:#FF9800!important;box-shadow:0 0 0 1.5px rgba(255,255,255,.9),0 1px 3px rgba(0,0,0,.35)!important;}
        #maknaLogoOverlay .makna-logo-eyebrow{top:28px!important;left:32px!important;padding:9px 20px!important;border-radius:999px!important;background:#fff!important;border:1.5px solid #FF9800!important;color:#FF9800!important;font-family:var(--mono)!important;font-size:12.5px!important;font-weight:700!important;letter-spacing:.16em!important;box-shadow:none!important;}
        #maknaLogoOverlay .makna-logo-close{top:24px!important;right:32px!important;width:44px!important;height:44px!important;border-radius:12px!important;background:#f4f2ea!important;box-shadow:none!important;}
        @media(max-width:760px){#maknaLogoOverlay{align-items:flex-start!important;padding:78px 16px 32px!important;overflow:auto!important;}#maknaLogoOverlay .makna-logo-stage{width:100%!important;height:auto!important;min-height:0!important;display:flex!important;flex-direction:column!important;align-items:center!important;gap:22px!important;}#maknaLogoOverlay .makna-logo-crest-wrap{position:relative!important;width:min(46vw,190px)!important;height:min(46vw,190px)!important;margin:0 auto!important;flex-shrink:0!important;}#maknaLogoOverlay .makna-logo-crest{position:absolute!important;top:0!important;left:0!important;width:100%!important;height:100%!important;transform:none!important;margin:0!important;}#maknaLogoOverlay .makna-logo-anchor-dot{width:7px!important;height:7px!important;box-shadow:0 0 0 1.5px rgba(255,255,255,.9),0 1px 2px rgba(0,0,0,.3)!important;}#maknaLogoOverlay .makna-logo-point{position:relative!important;inset:auto!important;width:100%!important;max-width:100%!important;height:auto!important;transform:none!important;}#maknaLogoOverlay .makna-logo-point.is-bottom{width:100%!important;max-width:100%!important;left:auto!important;top:auto!important;transform:none!important;}}
      `;document.head.appendChild(style);
    }
    const layout={1:{side:'right',x:71,top:15,width:360},2:{side:'left',x:8,top:15,width:340},3:{side:'right',x:71,top:31,width:360},4:{side:'left',x:8,top:31,width:340},5:{side:'right',x:71,top:47,width:360},6:{side:'right',x:71,top:63,width:360},7:{side:'left',x:8,top:47,width:340},8:{side:'left',x:8,top:63,width:340},9:{side:'left',x:8,top:79,width:340},10:{side:'right',x:71,top:79,width:360}};
    const anchors={1:[50.00,27.58],2:[42.73,34.11],3:[52.01,38.44],4:[47.50,38.49],5:[52.73,44.51],6:[49.78,43.22],7:[49.77,51.44],8:[45.70,59.36],9:[49.71,59.34],10:[54.14,59.60]};
    qa('.makna-logo-point',stage).forEach((point,index)=>{const number=Number(point.dataset.mlNumber||(q('.makna-logo-point-num',point)?.textContent||'').trim())||index+1;const p=layout[number];if(!p)return;point.dataset.mlNumber=String(number);point.classList.remove('is-left','is-right','is-bottom','is-drop-up');point.classList.add('is-'+p.side);point.style.top=p.top+'%';point.style.bottom='auto';point.style.left=p.x+'%';point.style.right='auto';point.style.width=p.width+'px';point.style.maxWidth=p.width+'px';if(p.top>=78)point.classList.add('is-drop-up');const a=anchors[number];const dot=q('.makna-logo-anchor-dot[data-ml-number="'+number+'"]',stage)||qa('.makna-logo-anchor-dot',stage)[number-1];if(a&&dot){dot.dataset.mlNumber=String(number);dot.style.left=a[0]+'%';dot.style.top=a[1]+'%';}});
    const redraw=()=>{const svg=q('#maknaLogoLines',stage);if(!svg)return;const isMobileView=window.matchMedia('(max-width:760px)').matches;const stageRect=stage.getBoundingClientRect();qa('.makna-logo-point',stage).forEach((point,index)=>{svg.querySelectorAll('[data-ml-line="'+index+'"]').forEach(el=>el.remove());if(isMobileView&&!point.classList.contains('is-expanded'))return;const number=Number(point.dataset.mlNumber||(q('.makna-logo-point-num',point)?.textContent||'').trim())||index+1;const badge=q('.makna-logo-point-num',point),dot=q('.makna-logo-anchor-dot[data-ml-number="'+number+'"]',stage)||qa('.makna-logo-anchor-dot',stage)[index];if(!badge||!dot)return;const br=badge.getBoundingClientRect(),dr=dot.getBoundingClientRect();const x2=(dr.left+dr.width/2-stageRect.left)/stageRect.width*100,y2=(dr.top+dr.height/2-stageRect.top)/stageRect.height*100;if(isMobileView){const bx=(br.left+br.width/2-stageRect.left)/stageRect.width*100,by=(br.top+br.height/2-stageRect.top)/stageRect.height*100;const poly=document.createElementNS('http://www.w3.org/2000/svg','polyline');poly.setAttribute('data-ml-line',String(index));poly.setAttribute('points',x2+','+y2+' '+x2+','+by+' '+bx+','+by);poly.setAttribute('stroke','#FF9800');poly.setAttribute('fill','none');svg.appendChild(poly);}else{const isLeft=point.classList.contains('is-left'),isBottom=point.classList.contains('is-bottom');const startPxX=isBottom?(br.left+br.width/2):(isLeft?br.left:br.right);const x1=(startPxX-stageRect.left)/stageRect.width*100,y1=(br.top+br.height/2-stageRect.top)/stageRect.height*100;const line=document.createElementNS('http://www.w3.org/2000/svg','line');line.setAttribute('data-ml-line',String(index));line.setAttribute('x1',x1);line.setAttribute('y1',y1);line.setAttribute('x2',x2);line.setAttribute('y2',y2);line.setAttribute('stroke','#FF9800');line.setAttribute('fill','none');svg.appendChild(line);}});};
    requestAnimationFrame(()=>requestAnimationFrame(redraw));if(!stage.dataset.mlResizeBound){window.addEventListener('resize',redraw);stage.dataset.mlResizeBound='1';}
    if(!overlay.dataset.mlOpenObserverBound){
      const redrawWhenOpen=()=>{
        if(!overlay.classList.contains('open'))return;
        requestAnimationFrame(()=>requestAnimationFrame(redraw));
        setTimeout(redraw,400);
      };
      const mo=new MutationObserver(redrawWhenOpen);
      mo.observe(overlay,{attributes:true,attributeFilter:['class'],subtree:true});
      overlay.addEventListener('transitionend',(e)=>{if(e.target===overlay&&e.propertyName==='opacity')redrawWhenOpen();});
      overlay.dataset.mlOpenObserverBound='1';
    }
  }
  fetch('/landing-config',{headers:{Accept:'application/json'}}).then(r=>r.ok?r.json():null).then(d=>{if(d&&d.config)apply(d.config);}).catch(()=>{});
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initMaknaLogoDetail,{once:true});else initMaknaLogoDetail();
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',syncMaknaLogoPrototype,{once:true});else syncMaknaLogoPrototype();
})();
