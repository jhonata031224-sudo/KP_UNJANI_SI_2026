<script>
  const menuBtn=document.getElementById('menuBtn');const sidebar=document.getElementById('sidebar');
  if(menuBtn&&sidebar&&!menuBtn.dataset.uiBound){menuBtn.dataset.uiBound='1';let suppressNextClick=false,suppressTimer=0;function toggleMobileSidebar(e){e.preventDefault();e.stopPropagation();sidebar.classList.toggle('open')}if(window.PointerEvent){menuBtn.addEventListener('pointerup',function(e){suppressNextClick=true;window.clearTimeout(suppressTimer);suppressTimer=setTimeout(function(){suppressNextClick=false},500);toggleMobileSidebar(e)},{passive:false});menuBtn.addEventListener('click',function(e){if(suppressNextClick){suppressNextClick=false;window.clearTimeout(suppressTimer);e.preventDefault();e.stopPropagation();return}if(e.detail===0)toggleMobileSidebar(e)})}else menuBtn.addEventListener('click',toggleMobileSidebar)}
  if(sidebar)document.addEventListener('click',e=>{if(window.innerWidth<=900&&sidebar.classList.contains('open')&&!sidebar.contains(e.target)&&e.target!==menuBtn)sidebar.classList.remove('open')});
  (function(){var style=document.createElement('style');style.textContent='.side-dropdown-menu,.side-dropdown-menu ul,.side-dropdown-menu ol,.side-dropdown-menu li{list-style:none!important}.side-dropdown-menu li::marker{content:""!important;display:none!important}.side-dropdown-menu a::before,.side-dropdown-menu a::after,.side-dropdown-menu .side-sublink::before,.side-dropdown-menu .side-sublink::after{content:none!important;display:none!important}.side-dropdown-menu .dot,.side-dropdown-menu .side-sublink .dot{display:none!important;width:0!important;min-width:0!important;margin:0!important;padding:0!important}.side-dropdown-menu .side-sublink{padding-left:32px!important;padding-right:12px!important;gap:0!important;list-style:none!important;background-image:none!important}';document.head.appendChild(style)})();
  (function(){function clean(link){if(!link)return;link.style.setProperty('list-style','none','important');link.style.setProperty('background-image','none','important');var dot=link.querySelector('.dot');if(dot)dot.remove()}function init(){var nav=document.querySelector('.side-nav');if(!nav||!nav.querySelector('[data-tab-link="laporan-monitoring"]')||nav.dataset.dropdownReady==='1')return;nav.dataset.dropdownReady='1';var byTab={};Array.prototype.forEach.call(nav.querySelectorAll(':scope > a[data-tab-link]'),function(l){byTab[l.getAttribute('data-tab-link')]=l});var items=[byTab.ringkasan,byTab.laporan,byTab['laporan-monitoring'],byTab.riwayat,byTab['status-satuan']];if(items.some(function(x){return !x}))return;function group(label,children){var g=document.createElement('div'),t=document.createElement('button'),m=document.createElement('div');g.className='side-dropdown';t.type='button';t.className='side-link side-dropdown-toggle';t.innerHTML='<span class="dot"></span><span class="side-link-label"></span><svg class="side-dropdown-arrow" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"></path></svg>';t.querySelector('.side-link-label').textContent=label;m.className='side-dropdown-menu';children.forEach(function(l){l.classList.add('side-sublink');l.classList.remove('active');clean(l);m.appendChild(l)});g.appendChild(t);g.appendChild(m);t.addEventListener('click',function(){g.classList.toggle('open')});return g}nav.innerHTML='';var label=document.createElement('div');label.className='side-nav-label';label.textContent='Menu';nav.appendChild(label);nav.appendChild(byTab.ringkasan);nav.appendChild(group('Log Aktivitas',[byTab['laporan-monitoring'],byTab['status-satuan']]));nav.appendChild(group('Pelaporan',[byTab.laporan,byTab.riwayat]));nav.querySelectorAll('.side-dropdown-menu a').forEach(clean)}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init()})();
  function terapkanRowLimitWrap(wrap){if(!wrap)return;var limit=parseInt(wrap.getAttribute('data-row-limit'),10)||5,table=wrap.querySelector('table');if(!table)return;wrap.style.maxHeight='';wrap.classList.remove('tbl-scroll');var thead=table.querySelector('thead'),rows=Array.prototype.filter.call(table.querySelectorAll('tbody tr'),function(r){return r.style.display!=='none'});if(rows.length<=limit)return;var h=thead?thead.offsetHeight:0;for(var i=0;i<limit;i++)h+=rows[i].offsetHeight;if(h>0){wrap.style.maxHeight=h+'px';wrap.classList.add('tbl-scroll')}}window.terapkanRowLimitWrap=terapkanRowLimitWrap;function terapkanRowLimit(panel){if(panel)panel.querySelectorAll('[data-row-limit]').forEach(terapkanRowLimitWrap)}
  const ADMIN_ACTIVE_TAB_KEY='siberad-admin-active-tab';const links=document.querySelectorAll('[data-tab-link]'),panels=document.querySelectorAll('[data-tab-panel]');function activateAdminTab(target,skipSave){var p=document.querySelector('[data-tab-panel="'+target+'"]');if(!p)return false;links.forEach(l=>l.classList.remove('active'));panels.forEach(x=>x.classList.remove('active'));document.querySelectorAll('[data-tab-link="'+target+'"]').forEach(l=>l.classList.add('active'));document.querySelectorAll('.side-nav-group').forEach(function(g){g.classList.remove('has-active-child')});document.querySelectorAll('[data-tab-link="'+target+'"]').forEach(function(l){var g=l.closest('.side-nav-group');if(g){g.classList.add('has-active-child');if(typeof window.siberadMarkAdminGroupOpen==='function')window.siberadMarkAdminGroupOpen(g);if(sidebar&&!sidebar.classList.contains('collapsed')&&!g.classList.contains('open')){g.classList.add('open');if(typeof window.siberadRepositionSubnavFlyouts==='function')window.siberadRepositionSubnavFlyouts()}}});p.classList.add('active');terapkanRowLimit(p);if(!skipSave)try{sessionStorage.setItem(ADMIN_ACTIVE_TAB_KEY,target)}catch(e){}return true}links.forEach(l=>l.addEventListener('click',function(e){e.preventDefault();activateAdminTab(l.getAttribute('data-tab-link'));if(window.innerWidth<=900&&sidebar)sidebar.classList.remove('open');window.scrollTo({top:0,behavior:'smooth'})}));try{var savedTab=sessionStorage.getItem(ADMIN_ACTIVE_TAB_KEY);if(savedTab)activateAdminTab(savedTab,true)}catch(e){}document.querySelectorAll('.tab-panel.active').forEach(terapkanRowLimit);
  (function(){var btn=document.getElementById('themeToggleBtn');if(!btn||btn.dataset.uiBound)return;btn.dataset.uiBound='1';function apply(t){if(t==='light')document.documentElement.setAttribute('data-theme','light');else document.documentElement.removeAttribute('data-theme');btn.setAttribute('aria-pressed',t==='light'?'true':'false')}var saved='dark';try{saved=localStorage.getItem('siberad-theme')||'dark'}catch(e){}apply(saved);btn.addEventListener('click',function(){var n=document.documentElement.getAttribute('data-theme')==='light'?'dark':'light';try{localStorage.setItem('siberad-theme',n)}catch(e){}apply(n)})})();
  (function(){function clean(root){if(!root)return;var w=document.createTreeWalker(root,NodeFilter.SHOW_TEXT),n,a=[];while(n=w.nextNode())a.push(n);a.forEach(function(t){if(t.nodeValue&&t.nodeValue.trim())t.nodeValue=t.nodeValue.replace(/\s*\/\/\s*/g,' ').replace(/(^|\s)[-—](?=\s|$)/g,'$1').replace(/ {2,}/g,' ')})}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){clean(document.body)});else clean(document.body)})();
  (function(){var form=document.getElementById('landingForm'),fake=document.getElementById('lpPreview');if(!fake)return;var frame=fake.closest('.lp-browser-frame');if(!frame)return;var iframe=document.createElement('iframe');iframe.id='lpLiveLandingFrame';iframe.title='Pratinjau landing page SIBERAD';iframe.src='/';iframe.loading='eager';iframe.style.cssText='display:block;width:1440px;max-width:none;height:900px;min-height:700px;border:0;background:#151e19;';fake.style.display='none';frame.style.overflow='auto';frame.style.position='relative';frame.appendChild(iframe);var ready=false,pending=null;function val(name,fallback){if(!form)return fallback||'';var e=form.querySelector('[data-lp="'+name+'"]');return e?e.value:(fallback||'')}function txt(e,v){if(e)e.textContent=v||''}function doc(){try{return iframe.contentDocument||iframe.contentWindow.document}catch(e){return null}}function update(){var d=doc();if(!d||!ready)return;var theme=document.documentElement.getAttribute('data-theme');if(theme==='light')d.documentElement.setAttribute('data-theme','light');else d.documentElement.removeAttribute('data-theme');txt(d.querySelector('.hero .eyebrow'),val('hero_eyebrow','PUSSIBERAD // SISTEM PENDUKUNG OPERASIONAL'));var h1=d.querySelector('.hero h1'),em=h1&&h1.querySelector('em');if(h1){Array.prototype.slice.call(h1.childNodes).forEach(function(n){if(n.nodeType===3)n.nodeValue=''});h1.insertBefore(d.createTextNode(val('hero_judul_awal')),em||null)}txt(em,val('hero_judul_aksen'));txt(d.querySelector('.hero h2'),val('hero_subjudul'));txt(d.querySelector('.hero > .wrap .hero-inner > div:first-child > p'),val('hero_deskripsi'));var cards=d.querySelectorAll('.feature-grid .feature-card');if(form)form.querySelectorAll('[data-lp^="fitur_judul_"]').forEach(function(x){var m=x.dataset.lp.match(/_(\d+)$/);if(!m||!cards[+m[1]])return;txt(cards[+m[1]].querySelector('h4'),x.value);var ds=form.querySelector('[data-lp="fitur_deskripsi_'+m[1]+'"]');if(ds)txt(cards[+m[1]].querySelector('p'),ds.value)});var about=d.querySelector('#tentang-pussiberad');if(about){var ps=about.querySelectorAll('.about-top > div:last-child p'),parts=val('tentang_deskripsi').split(/\n\s*\n/).map(function(x){return x.trim()}).filter(Boolean);parts.forEach(function(x,i){if(ps[i])txt(ps[i],x)});txt(about.querySelector('.moto-panel h3'),val('tentang_moto_judul'));txt(about.querySelector('.moto-desc'),val('tentang_moto_deskripsi'))}var footer=d.querySelector('footer');if(footer){var items=footer.querySelectorAll('.footer-links li');if(items[0])items[0].textContent=val('alamat');if(items[1])items[1].textContent=val('telepon_kontak');if(items[2]){var a=items[2].querySelector('a');if(a){a.href=val('website')||'#';a.textContent=val('website').replace(/^https?:\/\//,'').replace(/\/$/,'')}}}}iframe.addEventListener('load',function(){ready=true;update()});if(form)form.querySelectorAll('[data-lp]').forEach(function(e){e.addEventListener('input',update);e.addEventListener('change',update)});window.addEventListener('resize',function(){iframe.style.height=Math.max(700,Math.min(1100,window.innerHeight-170))+'px'});})();
  (function(){var frame=document.querySelector('.lp-browser-frame'),iframe=document.getElementById('lpLiveLandingFrame');if(!frame||!iframe)return;var scale=.75;function strip(){var t=document.querySelector('.lp-zoom-toolbar');if(t)t.remove();frame.querySelectorAll('.siberad-preview-zoombar,.lpv4-zoom-controls').forEach(function(x){if(x.parentElement)x.parentElement.remove()})}function fit(){var h=Math.max(700,parseFloat(iframe.style.height)||900);iframe.style.width='1440px';iframe.style.height=h+'px';iframe.style.transform='scale('+scale+')';iframe.style.transformOrigin='top left';iframe.style.marginBottom=h*(scale-1)+'px'}strip();fit();window.addEventListener('resize',fit,{passive:true});iframe.addEventListener('load',function(){setTimeout(fit,0);setTimeout(strip,50)})})();
</script>
@if(isset($pengaturan))
<script src="{{ asset('js/admin-landing-editor.js') }}"></script>
<script src="{{ asset('js/role-access-layout.js') }}"></script>
@endif

<style>
.backup-action-row{display:flex;align-items:center;gap:18px;flex-wrap:wrap;padding:18px 22px}
.backup-action-row .backup-create-form,.backup-action-row .backup-upload-form{margin:0;padding:0;display:flex;align-items:center}
.backup-action-row .btn{height:44px;min-height:44px;box-sizing:border-box;display:inline-flex;align-items:center;}
.backup-upload-btn{white-space:nowrap}
.backup-upload-trigger{display:none !important;}
.backup-date-filter{display:flex;align-items:center;margin-left:44px;}
.backup-date-filter-input{cursor:pointer;}
@media(max-width:760px){.backup-date-filter{margin-left:18px;}}
@media(max-width:520px){.backup-action-row{align-items:stretch}.backup-action-row .backup-create-form,.backup-action-row .backup-upload-form{width:100%}.backup-action-row .btn{width:100%;justify-content:center}.backup-date-filter{width:100%;margin-left:0;}.backup-date-filter-input{width:100%;}}
</style>
<script>
(function(){
  function initBackupUpload(){
    var section=document.querySelector('[data-tab-panel="backup"]');
    if(!section||section.dataset.backupUploadReady==='1')return;
    var createForm=section.querySelector('form[action*="/admin/backup"]');
    if(!createForm)return;
    var createButton=createForm.querySelector('button[type="submit"]');
    if(!createButton)return;
    section.dataset.backupUploadReady='1';
    var row=document.createElement('div');
    row.className='backup-action-row';
    createForm.classList.add('backup-create-form');
    createForm.parentNode.insertBefore(row,createForm);
    row.appendChild(createForm);

    var uploadForm=document.createElement('form');
    uploadForm.className='backup-upload-form';
    uploadForm.method='POST';
    uploadForm.action='{{ route('admin.backup.upload') }}';
    uploadForm.enctype='multipart/form-data';
    var csrf=document.createElement('input');
    csrf.type='hidden';csrf.name='_token';csrf.value='{{ csrf_token() }}';
    var input=document.createElement('input');
    input.type='file';input.name='backup_file';input.accept='.sql,.sqlite';input.hidden=true;
    var button=document.createElement('button');
    button.type='button';button.className='btn backup-upload-btn';button.textContent='UPLOAD BACKUP';
    button.addEventListener('click',function(){input.click()});
    input.addEventListener('change',function(){if(!input.files||!input.files.length)return;button.disabled=true;button.textContent='MENGUNGGAH...';uploadForm.submit()});
    uploadForm.appendChild(csrf);uploadForm.appendChild(input);uploadForm.appendChild(button);row.appendChild(uploadForm);

    var filterWrap=document.createElement('div');
    filterWrap.className='backup-date-filter';
    var filterInput=document.createElement('input');
    filterInput.type='date';
    filterInput.id='backupDateFilter';
    filterInput.className='btn backup-date-filter-input';
    filterInput.setAttribute('aria-label','Filter Tanggal');
    filterWrap.appendChild(filterInput);
    row.appendChild(filterWrap);

    function applyDateFilter(){
      var table=section.querySelector('.tbl-wrap table');
      if(!table)return;
      var val=filterInput.value;
      var rows=table.querySelectorAll('tbody tr[data-tanggal]');
      rows.forEach(function(tr){
        tr.style.display=(!val||tr.getAttribute('data-tanggal')===val)?'':'none';
      });
      var wrap=table.closest('[data-row-limit]');
      if(wrap&&window.terapkanRowLimitWrap)window.terapkanRowLimitWrap(wrap);
    }
    filterInput.addEventListener('change',applyDateFilter);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initBackupUpload);else initBackupUpload();
})();
</script>

<style>
/* Role & Hak Akses: judul kolom Aksi benar-benar di tengah dan tombol simpan dibuat lebih ringkas. */
.main .content [data-tab-panel="role-akses"] .role-akses-action-head,
.main .content [data-tab-panel="role-akses"] .role-akses-action {
  text-align:center !important;
  vertical-align:middle !important;
}
.main .content [data-tab-panel="role-akses"] .role-akses-action button,
.main .content [data-tab-panel="role-akses"] .role-akses-action .btn {
  width:220px !important;
  min-width:0 !important;
  max-width:100% !important;
  margin-left:auto !important;
  margin-right:auto !important;
  justify-content:center !important;
  padding:8px 12px !important;
  font-size:10.5px !important;
  white-space:nowrap !important;
  box-sizing:border-box !important;
}
@media(max-width:640px){
  .main .content [data-tab-panel="role-akses"] .role-akses-action button,
  .main .content [data-tab-panel="role-akses"] .role-akses-action .btn {
    width:100% !important;
    max-width:220px !important;
  }
}
</style>

<!-- Loaded-source override: the admin page actually includes this partial, while the earlier UI JS file is not loaded here. -->
<style id="admin-landing-tabs-boxed-v2">
  .main .content #landingForm .lp-tabs{display:flex!important;flex-wrap:wrap!important;align-items:center!important;gap:10px!important;margin:0 0 6px!important;padding:0 0 16px!important;border-bottom:1px solid var(--border-soft)!important;}
  .main .content #landingForm .lp-tabs .lp-tab{box-sizing:border-box!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:7px!important;width:132px!important;min-width:132px!important;height:48px!important;min-height:48px!important;padding:0 16px!important;margin:0!important;border-radius:12px!important;border:1px solid var(--border-soft)!important;background:var(--panel-alt)!important;color:var(--text-muted)!important;font-family:var(--body)!important;font-size:12.5px!important;font-weight:600!important;letter-spacing:.02em!important;line-height:1!important;white-space:nowrap!important;cursor:pointer!important;}
  .main .content #landingForm .lp-tabs .lp-tab svg{width:15px!important;height:15px!important;flex:0 0 auto!important;}
  .main .content #landingForm .lp-tabs .lp-tab:hover{color:var(--text)!important;border-color:var(--border-strong)!important;background:var(--panel)!important;}
  .main .content #landingForm .lp-tabs .lp-tab.active{background:var(--gold-dim)!important;border-color:var(--gold)!important;color:var(--gold-bright)!important;}
  @media(max-width:700px){
    .main .content #landingForm .lp-tabs{gap:8px!important;}
    .main .content #landingForm .lp-tabs .lp-tab{width:calc(50% - 4px)!important;min-width:0!important;height:44px!important;min-height:44px!important;padding:0 10px!important;}
  }
  @media(max-width:430px){
    .main .content #landingForm .lp-tabs .lp-tab{width:100%!important;}
  }
</style>
