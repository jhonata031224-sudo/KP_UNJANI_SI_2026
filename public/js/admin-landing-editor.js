(function () {
  const form=document.getElementById('landingForm');
  if(!form||form.dataset.landingEditorReady==='1')return;
  form.dataset.landingEditorReady='1';
  const get=(o,path,f='')=>path.split('.').reduce((v,k)=>v&&v[k]!==undefined?v[k]:undefined,o)??f;
  let config={};

  function addField(parent,label,path,value,type='text',extra={}){
    const wrap=document.createElement('div');wrap.className=extra.full===false?'form-field':'form-field full';
    const id='lpManaged_'+path.replace(/[^a-z0-9]/gi,'_');const lab=document.createElement('label');lab.htmlFor=id;lab.textContent=label;
    const input=type==='textarea'?document.createElement('textarea'):document.createElement('input');input.id=id;
    if(type!=='textarea')input.type=type;else input.rows=extra.rows||3;input.value=value??'';input.dataset.landingPath=path;wrap.append(lab,input);parent.appendChild(wrap);return input;
  }
  function addCard(panel,title,fields){const card=document.createElement('div');card.className='lp-card';const t=document.createElement('div');t.className='lp-card-title';t.textContent=title;card.appendChild(t);const grid=document.createElement('div');grid.className='form-grid';fields.forEach(f=>addField(grid,f[1],f[0],get(config,f[0]),f[2]||'text',f[3]||{}));card.appendChild(grid);panel.appendChild(card);}
  function addLogoField(panel){const card=document.createElement('div');card.className='lp-card lp-card-compact';card.innerHTML='<div class="lp-card-title">Logo Landing Page</div><div class="form-grid"><div class="form-field full"><label for="lpManagedLogo">Logo</label><input id="lpManagedLogo" name="logo_file" type="file" accept="image/png,image/jpeg,image/webp"><small style="display:block;margin-top:6px;color:var(--text-muted);">Logo digunakan pada header, hero, loader, favicon, dan bagian Tentang. JPG, PNG, WEBP · maksimal 5 MB.</small></div></div>';panel.appendChild(card);}
  function addNavFields(panel){const card=document.createElement('div');card.className='lp-card lp-card-compact';const t=document.createElement('div');t.className='lp-card-title';t.textContent='Navigasi Landing Page';card.appendChild(t);const grid=document.createElement('div');grid.className='form-grid';for(let i=0;i<4;i++){addField(grid,`Menu ${i+1} — Label`,`nav.${i}.label`,get(config,`nav.${i}.label`),'text',{full:false});addField(grid,`Menu ${i+1} — Link`,`nav.${i}.url`,get(config,`nav.${i}.url`),'text',{full:false});}card.appendChild(grid);panel.appendChild(card);}
  function addStatsFields(panel){const card=document.createElement('div');card.className='lp-card lp-card-compact';const t=document.createElement('div');t.className='lp-card-title';t.textContent='Statistik Beranda';card.appendChild(t);const grid=document.createElement('div');grid.className='form-grid';for(let i=0;i<4;i++){addField(grid,`Statistik ${i+1} — Angka`,`stats.${i}.number`,get(config,`stats.${i}.number`),'text',{full:false});addField(grid,`Statistik ${i+1} — Label`,`stats.${i}.label`,get(config,`stats.${i}.label`),'text',{full:false});}card.appendChild(grid);panel.appendChild(card);}

  function styleFilePickers(){
    if(!document.getElementById('landing-file-picker-style')){
      const style=document.createElement('style');style.id='landing-file-picker-style';style.textContent=`
        .landing-file-picker{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:4px}
        .landing-file-picker input[type=file]{position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;overflow:hidden!important;pointer-events:none!important}
        .landing-file-button{display:inline-flex;align-items:center;gap:9px;border:1px solid var(--border-strong,#cbd5e1);background:var(--panel,#fff);color:var(--text,#17212b);border-radius:10px;padding:10px 15px;font:600 13px/1 var(--body,inherit);cursor:pointer;box-shadow:0 1px 2px rgba(15,23,42,.04);transition:.18s ease}
        .landing-file-button:hover{border-color:var(--gold,#c97a00);color:var(--gold,#c97a00);background:var(--gold-dim,rgba(201,122,0,.08));transform:translateY(-1px)}
        .landing-file-button:focus-visible{outline:3px solid rgba(201,122,0,.2);outline-offset:2px}
        .landing-file-button svg{width:17px;height:17px;flex:none}
        .landing-file-name{color:var(--text-muted,#64748b);font-size:12px;min-width:0;max-width:420px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
      `;document.head.appendChild(style);
    }
    form.querySelectorAll('input[type=file]').forEach(input=>{
      if(input.dataset.filePickerReady==='1')return;input.dataset.filePickerReady='1';
      const wrap=document.createElement('div');wrap.className='landing-file-picker';input.parentNode.insertBefore(wrap,input);wrap.appendChild(input);
      const label=document.createElement('label');label.className='landing-file-button';label.htmlFor=input.id;label.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4"></path><path d="m7 9 5-5 5 5"></path><path d="M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"></path></svg><span>Pilih File</span>';wrap.appendChild(label);
      const name=document.createElement('span');name.className='landing-file-name';name.textContent=input.files&&input.files[0]?input.files[0].name:'Belum ada file dipilih';wrap.appendChild(name);
      input.addEventListener('change',()=>{name.textContent=input.files&&input.files[0]?input.files[0].name:'Belum ada file dipilih';});
    });
  }

  function collect(){const clone=JSON.parse(JSON.stringify(config||{}));form.querySelectorAll('[data-landing-path]').forEach(input=>{const parts=input.dataset.landingPath.split('.');let cur=clone;parts.forEach((p,i)=>{if(i===parts.length-1)cur[p]=input.value;else{if(!cur[p])cur[p]=/^\d+$/.test(parts[i+1])?[]:{};cur=cur[p];}});});return clone;}
  function syncPayload(){const hidden=document.getElementById('landingContentPayload');if(hidden)hidden.value=JSON.stringify(collect());}
  function setText(doc,selector,value){const el=doc.querySelector(selector);if(el)el.textContent=value??'';}
  function preview(){const iframe=document.getElementById('lpLiveLandingFrame');if(!iframe||!iframe.contentDocument)return;const doc=iframe.contentDocument,c=collect(),brand=c.brand||{},hero=c.hero||{};
    doc.querySelectorAll('.logo-text b').forEach(el=>{el.innerHTML='';el.appendChild(doc.createTextNode(brand.name||'SIBER'));const s=doc.createElement('span');s.textContent=brand.accent||'AD';el.appendChild(s);});
    doc.querySelectorAll('.logo-text small').forEach(el=>el.textContent=brand.tagline||'Pussiberad · TNI AD');
    doc.querySelectorAll('.nav-links a').forEach((el,i)=>{if(c.nav&&c.nav[i]){el.textContent=c.nav[i].label||'';el.setAttribute('href',c.nav[i].url||'#');}});
    setText(doc,'.hero-actions .btn-primary',hero.button_label||'Selengkapnya');const btn=doc.querySelector('.hero-actions .btn-primary');if(btn)btn.href=hero.button_url||'#fitur';
    setText(doc,'.hero-crest-caption',(hero.crest_caption||'Pusat Siber Angkatan Darat')+'\n“'+(hero.crest_motto||'Satria Yudha Waskita')+'”');
    doc.querySelectorAll('.stats-grid .stat').forEach((el,i)=>{const x=(c.stats||[])[i];if(x){setText(el.querySelector('.stat-num'),x.number);setText(el.querySelector('.stat-label'),x.label);}});
    setText(doc,'#fitur .section-head .eyebrow',get(c,'features_section.eyebrow'));setText(doc,'#fitur .section-head h3',get(c,'features_section.title'));setText(doc,'#fitur .section-head p',get(c,'features_section.description'));
    setText(doc,'#tentang-pussiberad .eyebrow',get(c,'about_section.eyebrow'));setText(doc,'#tentang-pussiberad .about-top h3',get(c,'about_section.title'));
    setText(doc,'footer .footer-desc',get(c,'footer.description'));setText(doc,'footer .footer-bottom',get(c,'footer.copyright'));
  }
  function bindLivePreview(){form.querySelectorAll('[data-landing-path]').forEach(input=>{input.addEventListener('input',()=>{syncPayload();preview();});input.addEventListener('change',()=>{syncPayload();preview();});});form.addEventListener('submit',syncPayload);
    const logo=document.getElementById('lpManagedLogo');if(logo)logo.addEventListener('change',()=>{const f=logo.files&&logo.files[0];if(!f)return;const r=new FileReader();r.onload=e=>{const iframe=document.getElementById('lpLiveLandingFrame');if(iframe&&iframe.contentDocument)iframe.contentDocument.querySelectorAll('.logo-badge img,.hero-crest .mark-plate img,#loader .mark-plate img,#tentang-pussiberad img').forEach(img=>img.src=e.target.result);};r.readAsDataURL(f);});
    const timer=setInterval(()=>{const iframe=document.getElementById('lpLiveLandingFrame');if(iframe&&iframe.contentDocument&&iframe.contentDocument.readyState==='complete'){clearInterval(timer);preview();}},300);setTimeout(()=>clearInterval(timer),15000);
  }

  function buildEditor(){
    form.querySelectorAll('[data-lp-tab="lengkap"],[data-lp-tab-panel="lengkap"]').forEach(el=>el.remove());
    const beranda=form.querySelector('[data-lp-tab-panel="beranda"]'),fitur=form.querySelector('[data-lp-tab-panel="fitur"]'),tentang=form.querySelector('[data-lp-tab-panel="tentang"]'),kontak=form.querySelector('[data-lp-tab-panel="kontak"]');
    if(!beranda||!fitur||!tentang||!kontak)return;
    const grid=beranda.querySelector('.form-grid');if(grid){addField(grid,'Nama Brand','brand.name',get(config,'brand.name','SIBER'),'text',{full:false});addField(grid,'Teks Aksen Brand','brand.accent',get(config,'brand.accent','AD'),'text',{full:false});addField(grid,'Tagline Brand','brand.tagline',get(config,'brand.tagline','Pussiberad · TNI AD'));addField(grid,'Teks Tombol Hero','hero.button_label',get(config,'hero.button_label','Selengkapnya'),'text',{full:false});addField(grid,'Link Tombol Hero','hero.button_url',get(config,'hero.button_url','#fitur'),'text',{full:false});addField(grid,'Caption Lambang','hero.crest_caption',get(config,'hero.crest_caption','Pusat Siber Angkatan Darat'),'text',{full:false});addField(grid,'Moto Lambang','hero.crest_motto',get(config,'hero.crest_motto','Satria Yudha Waskita'),'text',{full:false});addField(grid,'Judul Browser / SEO','meta.title',get(config,'meta.title'));addField(grid,'Deskripsi SEO','meta.description',get(config,'meta.description'),'textarea',{rows:3});}
    addLogoField(beranda);addNavFields(beranda);addStatsFields(beranda);
    addCard(fitur,'Judul Section Fitur',[['features_section.eyebrow','Label kecil section'],['features_section.title','Judul section'],['features_section.description','Deskripsi section','textarea',{rows:3}]]);
    addCard(tentang,'Judul Section Tentang',[['about_section.eyebrow','Label kecil section'],['about_section.title','Judul section']]);
    addCard(kontak,'Footer Landing Page',[['footer.description','Deskripsi footer','textarea',{rows:3}],['footer.copyright','Teks copyright / footer bawah']]);
    const hidden=document.createElement('input');hidden.type='hidden';hidden.name='landing_content';hidden.id='landingContentPayload';form.appendChild(hidden);styleFilePickers();bindLivePreview();
  }
  fetch('/landing-config',{headers:{Accept:'application/json'}}).then(r=>r.ok?r.json():{}).then(d=>{config=d.config||{};buildEditor();}).catch(()=>buildEditor());
})();
