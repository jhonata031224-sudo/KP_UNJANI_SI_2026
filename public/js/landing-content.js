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

  function syncMaknaLogoPrototype(){
    const overlay=q('#maknaLogoOverlay'),stage=q('#maknaLogoStage');if(!overlay||!stage)return;
    if(!document.getElementById('makna-logo-prototype-override')){
      const style=document.createElement('style');style.id='makna-logo-prototype-override';style.textContent=`
        #maknaLogoOverlay{background:var(--panel-2)!important;padding:0!important;}
        #maknaLogoOverlay .makna-logo-stage{position:relative!important;width:min(1320px,96vw)!important;height:min(790px,90vh)!important;aspect-ratio:auto!important;display:block!important;margin:0 auto!important;overflow:visible!important;}
        #maknaLogoOverlay .makna-logo-crest{position:absolute!important;z-index:1!important;background:#fffdf7!important;border-radius:50%!important;overflow:hidden!important;box-shadow:0 0 0 8px rgba(255,152,0,.08),0 22px 55px rgba(0,0,0,.45)!important;width:300px!important;height:300px!important;top:45%!important;left:50%!important;transform:translate(-50%,-50%)!important;transition:none!important;}
        html[data-theme="light"] #maknaLogoOverlay .makna-logo-crest{background:var(--panel-2)!important;border-radius:0!important;overflow:visible!important;box-shadow:none!important;}
        html[data-theme="light"] #maknaLogoOverlay .makna-logo-crest img{mix-blend-mode:multiply!important;}
        #maknaLogoOverlay .makna-logo-crest img{width:100%!important;height:100%!important;object-fit:contain!important;display:block!important;mix-blend-mode:multiply!important;}
        #maknaLogoOverlay .makna-logo-point{position:absolute!important;height:auto!important;z-index:20!important;}
        #maknaLogoOverlay .makna-logo-point-card{display:block!important;width:100%!important;min-width:0!important;height:auto!important;box-sizing:border-box!important;background:transparent!important;border:none!important;box-shadow:none!important;cursor:default!important;padding:0!important;}
        #maknaLogoOverlay .makna-logo-point-num{display:none!important;}
        #maknaLogoOverlay .makna-logo-point-title{display:block!important;font-family:var(--display)!important;font-size:13px!important;font-weight:700!important;letter-spacing:.02em!important;text-transform:uppercase!important;color:var(--text)!important;margin:0!important;white-space:normal!important;line-height:1.35!important;}
        #maknaLogoOverlay .makna-logo-point-desc{display:block!important;font-family:var(--body)!important;font-size:12.5px!important;font-weight:400!important;line-height:1.6!important;color:var(--text-muted)!important;margin:4px 0 0!important;white-space:normal!important;text-align:justify!important;text-align-last:left!important;}
        #maknaLogoOverlay .makna-logo-point.is-left .makna-logo-point-card{text-align:right!important;}
        #maknaLogoOverlay .makna-logo-point.is-right .makna-logo-point-card{text-align:left!important;}
        #maknaLogoOverlay .makna-logo-point.is-left .makna-logo-point-desc{text-align-last:right!important;}
        #maknaLogoOverlay .makna-logo-point.is-bottom .makna-logo-point-desc{text-align-last:center!important;}
        #maknaLogoOverlay .makna-logo-point.is-bottom{height:auto!important;left:60%!important;right:auto!important;top:91%!important;transform:translate(-50%,-50%)!important;}
        #maknaLogoOverlay .makna-logo-point.is-bottom .makna-logo-point-card{text-align:center!important;}
        #maknaLogoOverlay .makna-logo-lines{z-index:5!important;pointer-events:none!important;overflow:visible!important;}
        #maknaLogoOverlay .makna-logo-lines line,#maknaLogoOverlay .makna-logo-lines polyline{stroke:#FF9800!important;stroke-opacity:1!important;stroke-width:2px!important;fill:none!important;stroke-linecap:round!important;stroke-linejoin:round!important;vector-effect:non-scaling-stroke!important;}
        #maknaLogoOverlay .makna-logo-anchor-dot{z-index:15!important;width:8px!important;height:8px!important;background:#FF9800!important;box-shadow:0 0 0 1.5px rgba(255,255,255,.9),0 1px 3px rgba(0,0,0,.35)!important;}
        #maknaLogoOverlay .makna-logo-eyebrow{top:28px!important;left:32px!important;padding:9px 20px!important;border-radius:999px!important;background:var(--panel-2)!important;border:1.5px solid #FF9800!important;color:#FF9800!important;font-family:var(--mono)!important;font-size:12.5px!important;font-weight:700!important;letter-spacing:.16em!important;box-shadow:none!important;}
        #maknaLogoOverlay .makna-logo-close{top:24px!important;right:32px!important;}
        @media(max-width:760px){#maknaLogoOverlay{align-items:flex-start!important;padding:78px 16px 32px!important;overflow:auto!important;}#maknaLogoOverlay .makna-logo-stage{width:100%!important;height:auto!important;min-height:0!important;display:flex!important;flex-direction:column!important;align-items:center!important;gap:22px!important;}#maknaLogoOverlay .makna-logo-crest-wrap{position:relative!important;width:min(46vw,190px)!important;height:min(46vw,190px)!important;margin:0 auto!important;flex-shrink:0!important;}#maknaLogoOverlay .makna-logo-crest{position:absolute!important;top:0!important;left:0!important;width:100%!important;height:100%!important;transform:none!important;margin:0!important;}#maknaLogoOverlay .makna-logo-anchor-dot{width:7px!important;height:7px!important;box-shadow:0 0 0 1.5px rgba(255,255,255,.9),0 1px 2px rgba(0,0,0,.3)!important;}#maknaLogoOverlay .makna-logo-point{position:relative!important;inset:auto!important;width:100%!important;max-width:100%!important;height:auto!important;transform:none!important;}#maknaLogoOverlay .makna-logo-point.is-bottom{width:100%!important;max-width:100%!important;left:auto!important;top:auto!important;transform:none!important;}}
      `;document.head.appendChild(style);
    }
    // Baris 1 (poin 1 Bintang & 2 Perisai) SENGAJA agak diturunin (top:16,
    // bukan 10 spt baris lain kalau diteruskan polanya) -- di top:10 posisinya
    // sejajar sama pill "MAKNA LOGO" & tombol X di pojok atas, kelihatan
    // numpuk. Baris 2-5 tidak diikutkan turun spy gak mepet ke tepi bawah.
    //
    // Semua judul & keterangan SATU kolom lurus, rata sejajar (efek tangga
    // yang sebelumnya digeser ke teks keterangan sudah dihapus lagi -- user
    // maunya keterangan selalu rata sama judulnya, gak digeser sama sekali).
    const layout={1:{side:'right',x:69,top:16,width:390},2:{side:'left',x:8,top:16,width:370},3:{side:'right',x:69,top:30,width:390},4:{side:'left',x:8,top:30,width:370},5:{side:'right',x:69,top:50,width:390},6:{side:'right',x:69,top:70,width:390},7:{side:'left',x:8,top:50,width:370},8:{side:'left',x:8,top:70,width:370},9:{side:'left',x:8,top:90,width:370},10:{side:'right',x:69,top:90,width:390}};
    const anchors={1:[50.00,27.58],2:[45.81,30.30],3:[52.01,38.44],4:[47.50,38.49],5:[52.73,44.51],6:[49.78,43.22],7:[49.77,51.44],8:[45.70,59.36],9:[49.71,59.34],10:[54.14,59.60]};
    qa('.makna-logo-point',stage).forEach((point,index)=>{const number=Number(point.dataset.mlNumber||(q('.makna-logo-point-num',point)?.textContent||'').trim())||index+1;const p=layout[number];if(!p)return;point.dataset.mlNumber=String(number);point.dataset.mlBaseTop=String(p.top);point.classList.remove('is-left','is-right','is-bottom','is-drop-up');point.classList.add('is-'+p.side);point.style.top=p.top+'%';point.style.bottom='auto';point.style.left=p.x+'%';point.style.right='auto';point.style.width=p.width+'px';point.style.maxWidth=p.width+'px';if(p.top>=78)point.classList.add('is-drop-up');const a=anchors[number];const dot=q('.makna-logo-anchor-dot[data-ml-number="'+number+'"]',stage)||qa('.makna-logo-anchor-dot',stage)[number-1];if(a&&dot){dot.dataset.mlNumber=String(number);dot.style.left=a[0]+'%';dot.style.top=a[1]+'%';}});
    // Dorong-otomatis kalau ketabrak: mulai dari posisi DASAR tiap baris
    // (dataset.mlBaseTop, dari `layout` di atas), lalu kalau kotak berikutnya
    // (yg lebih rendah) ternyata tumpang-tindih sama kotak di atasnya --
    // krn keterangannya panjang & makan banyak baris -- baris itu didorong
    // turun secukupnya. Jauh lebih akurat drpd nebak persen top manual per
    // baris, karena based on tinggi KONTEN ASLI yg ke-render (bisa beda2
    // tergantung device/lebar layar), bukan estimasi. Selalu direset ke
    // posisi dasar dulu tiap dipanggil supaya gak "ngambang" makin lama makin
    // turun tiap kali redraw() jalan ulang (resize dsb).
    const resolveVerticalOverlap=()=>{
      const stageRect=stage.getBoundingClientRect();const minGap=14;
      ['left','right'].forEach(side=>{
        const pts=qa('.makna-logo-point.is-'+side,stage).filter(p=>p.dataset.mlBaseTop!=null)
          .sort((a,b)=>parseFloat(a.dataset.mlBaseTop)-parseFloat(b.dataset.mlBaseTop));
        pts.forEach(p=>{p.style.top=p.dataset.mlBaseTop+'%';});
        let prevBottom=null;
        pts.forEach(p=>{
          let r=p.getBoundingClientRect();
          if(prevBottom!==null&&r.top<prevBottom+minGap){
            const shiftPx=(prevBottom+minGap)-r.top;
            p.style.top=(parseFloat(p.style.top)+shiftPx/stageRect.height*100)+'%';
            r=p.getBoundingClientRect();
          }
          prevBottom=r.bottom;
        });
      });
    };
    // Garis "siku pendek" (bukan siku panjang spt versi sebelumnya yg bikin
    // ruas vertikal nembus tengah lambang & numpuk sesama garis) -- ruas
    // UTAMA (anchor->dekat teks) tetap DIAGONAL LURUS & unik per titik
    // (jalur terpendek, gak mungkin numpuk), cuma di ujung paling deket teks
    // ditambah 1 "kait" siku pendek (panjang `hook`) biar tetep kelihatan
    // bersudut kayak referensi, tanpa bikin ruas panjang yg bisa nabrak.
    // Nempel ke .makna-logo-point-title (bukan seluruh card) biar titik
    // sambung garis stabil di dekat judul, gak ikut turun kalau keterangannya
    // panjang/banyak baris.
    // PENTING: garis & endcap di-UPDATE (bukan dihapus+dibikin ulang) tiap
    // redraw() -- sebelumnya selalu remove()+createElement() ulang, jadi
    // elemennya "lahir" SETELAH .open sudah aktif dan langsung nongol dalam
    // kondisi opacity:1 (gak sempet transisi dari 0->1 sama sekali, krn
    // transition CSS cuma jalan kalau ada elemen YANG SAMA berubah state,
    // bukan elemen baru yg baru muncul). Sekarang elemen dibikin SEKALI aja
    // (kalau belum ada), lalu tiap redraw cuma update posisinya -- opacity
    // masuk/keluarnya jadi murni dikontrol CSS lewat class .open (sama kayak
    // .makna-logo-point/.makna-logo-anchor-dot), jadi animasinya konsisten
    // tiap kali modal dibuka-tutup, gak cuma sekali doang.
    // Garis "ditarik" beneran lewat JS (titik ujung polyline digeser
    // bertahap tiap frame dari titik penanda -> judul) -- bukan pakai trik
    // CSS stroke-dasharray/dashoffset lagi, karena itu kebentur bug render:
    // dikombinasikan sama vector-effect:non-scaling-stroke di dalam SVG yg
    // viewBox-nya diregangkan gak proporsional (preserveAspectRatio="none",
    // stage ~1320x790 dari viewBox 100x100), dash-pattern-nya jadi kelihatan
    // putus-putus/berantakan, bukan satu garis mulus yg tumbuh. Animasi
    // manual begini gak kena masalah itu sama sekali krn cuma gambar ulang
    // garis pendek yg makin panjang, sama persis kayak garis biasa.
    function animateLineDraw(poly,endcap,x2,y2,bx,by,x1,y1,index){
      const seg1=Math.hypot(bx-x2,by-y2),seg2=Math.hypot(x1-bx,y1-by),total=seg1+seg2;
      const t0=performance.now()+250+index*50,duration=550;
      endcap.style.opacity='0';
      const step=(now)=>{
        let t=(now-t0)/duration;
        if(t<0){requestAnimationFrame(step);return;}
        if(t>1)t=1;
        const eased=1-Math.pow(1-t,3),dist=eased*total;
        if(dist<=seg1){const f=seg1?dist/seg1:1;poly.setAttribute('points',x2+','+y2+' '+(x2+(bx-x2)*f)+','+(y2+(by-y2)*f));}
        else{const f2=seg2?(dist-seg1)/seg2:1;poly.setAttribute('points',x2+','+y2+' '+bx+','+by+' '+(bx+(x1-bx)*f2)+','+(by+(y1-by)*f2));}
        if(t<1)requestAnimationFrame(step);
        else{poly.dataset.mlDrawn='1';delete poly.dataset.mlDrawing;endcap.style.transition='opacity .25s ease';endcap.style.opacity='1';}
      };
      requestAnimationFrame(step);
    }
    const redraw=()=>{const svg=q('#maknaLogoLines',stage);if(!svg)return;resolveVerticalOverlap();const stageRect=stage.getBoundingClientRect();const hook=3;qa('.makna-logo-point',stage).forEach((point,index)=>{const number=Number(point.dataset.mlNumber||(q('.makna-logo-point-num',point)?.textContent||'').trim())||index+1;const label=q('.makna-logo-point-title',point),dot=q('.makna-logo-anchor-dot[data-ml-number="'+number+'"]',stage)||qa('.makna-logo-anchor-dot',stage)[index];if(!label||!dot)return;const lr=label.getBoundingClientRect(),dr=dot.getBoundingClientRect();const x2=(dr.left+dr.width/2-stageRect.left)/stageRect.width*100,y2=(dr.top+dr.height/2-stageRect.top)/stageRect.height*100;const isLeft=point.classList.contains('is-left'),isBottom=point.classList.contains('is-bottom');const gapPx=9;const startPxX=isBottom?(lr.left+lr.width/2):(isLeft?(lr.right+gapPx):(lr.left-gapPx));const startPxY=isBottom?(lr.top-gapPx):(lr.top+lr.height/2);const x1=(startPxX-stageRect.left)/stageRect.width*100,y1=(startPxY-stageRect.top)/stageRect.height*100;let bx=x1,by=y1;if(isBottom)by=y1-hook;else bx=isLeft?(x1+hook):(x1-hook);
      let poly=svg.querySelector('polyline[data-ml-line="'+index+'"]');
      if(!poly){poly=document.createElementNS('http://www.w3.org/2000/svg','polyline');poly.setAttribute('data-ml-line',String(index));poly.setAttribute('stroke','#FF9800');poly.setAttribute('fill','none');poly.setAttribute('points',x2+','+y2+' '+x2+','+y2);svg.appendChild(poly);}
      let endcap=stage.querySelector('[data-ml-endcap="'+index+'"]');
      if(!endcap){endcap=document.createElement('div');endcap.className='makna-logo-line-endcap';endcap.dataset.mlEndcap=String(index);stage.appendChild(endcap);}
      endcap.style.left=x1+'%';endcap.style.top=y1+'%';
      if(poly.dataset.mlDrawn==='1'){
        // Udah pernah kelar ditarik sebelumnya (mis. reposisi krn resize) --
        // langsung update posisi akhirnya aja, gak perlu animasi ulang.
        poly.setAttribute('points',x2+','+y2+' '+bx+','+by+' '+x1+','+y1);
      } else if(!overlay.classList.contains('open')){
        // redraw() ini kepanggil pas modal LAGI KETUTUP (mis. "priming"
        // sekali di awal load halaman, atau resize saat overlay tertutup) --
        // JANGAN animasi di sini, cukup taruh di titik nol-panjang, biar
        // gak "diam-diam selesai duluan" di belakang layar sebelum user
        // sempat buka modalnya (itu penyebab bug "buka pertama gak jalan
        // animasinya" -- animasinya udah kelar duluan pas priming ini).
        poly.setAttribute('points',x2+','+y2+' '+x2+','+y2);
      } else if(!poly.dataset.mlDrawing){
        poly.dataset.mlDrawing='1';
        animateLineDraw(poly,endcap,x2,y2,bx,by,x1,y1,index);
      }
    });};
    requestAnimationFrame(()=>requestAnimationFrame(redraw));if(!stage.dataset.mlResizeBound){window.addEventListener('resize',redraw);stage.dataset.mlResizeBound='1';}
    if(!overlay.dataset.mlOpenObserverBound){
      const svgEl=q('#maknaLogoLines',stage);
      const redrawWhenOpen=()=>{
        if(!overlay.classList.contains('open')){
          // Modal ditutup -- reset semua garis balik ke "belum digambar"
          // (titik nol-panjang di posisi penanda) biar pas dibuka lagi
          // animasinya replay dari awal, bukan langsung nongol penuh krn
          // nyisa state kelar dari sesi sebelumnya.
          if(svgEl)qa('polyline[data-ml-line]',svgEl).forEach(p=>{delete p.dataset.mlDrawn;delete p.dataset.mlDrawing;const first=(p.getAttribute('points')||'0,0').split(' ')[0];p.setAttribute('points',first+' '+first);});
          qa('[data-ml-endcap]',stage).forEach(e=>{e.style.opacity='0';});
          return;
        }
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
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',syncMaknaLogoPrototype,{once:true});else syncMaknaLogoPrototype();
})();
