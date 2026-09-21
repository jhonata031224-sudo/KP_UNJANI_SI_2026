<script>
  const menuBtn=document.getElementById('menuBtn');const sidebar=document.getElementById('sidebar');
  if(menuBtn&&sidebar&&!menuBtn.dataset.uiBound){menuBtn.dataset.uiBound='1';let suppressNextClick=false,suppressTimer=0;function toggleMobileSidebar(e){e.preventDefault();e.stopPropagation();sidebar.classList.toggle('open')}if(window.PointerEvent){menuBtn.addEventListener('pointerup',function(e){suppressNextClick=true;window.clearTimeout(suppressTimer);suppressTimer=setTimeout(function(){suppressNextClick=false},500);toggleMobileSidebar(e)},{passive:false});menuBtn.addEventListener('click',function(e){if(suppressNextClick){suppressNextClick=false;window.clearTimeout(suppressTimer);e.preventDefault();e.stopPropagation();return}if(e.detail===0)toggleMobileSidebar(e)})}else menuBtn.addEventListener('click',toggleMobileSidebar)}
  if(sidebar)document.addEventListener('click',e=>{if(window.innerWidth<=900&&sidebar.classList.contains('open')&&!sidebar.contains(e.target)&&e.target!==menuBtn)sidebar.classList.remove('open')});
  (function(){var style=document.createElement('style');style.textContent='.side-dropdown-menu,.side-dropdown-menu ul,.side-dropdown-menu ol,.side-dropdown-menu li{list-style:none!important}.side-dropdown-menu li::marker{content:""!important;display:none!important}.side-dropdown-menu a::before,.side-dropdown-menu a::after,.side-dropdown-menu .side-sublink::before,.side-dropdown-menu .side-sublink::after{content:none!important;display:none!important}.side-dropdown-menu .dot,.side-dropdown-menu .side-sublink .dot{display:none!important;width:0!important;min-width:0!important;margin:0!important;padding:0!important}.side-dropdown-menu .side-sublink{padding-left:32px!important;padding-right:12px!important;gap:0!important;list-style:none!important;background-image:none!important}';document.head.appendChild(style)})();
  (function(){function clean(link){if(!link)return;link.style.setProperty('list-style','none','important');link.style.setProperty('background-image','none','important');var dot=link.querySelector('.dot');if(dot)dot.remove()}function init(){var nav=document.querySelector('.side-nav');if(!nav||!nav.querySelector('[data-tab-link="laporan-monitoring"]')||nav.dataset.dropdownReady==='1')return;nav.dataset.dropdownReady='1';var byTab={};Array.prototype.forEach.call(nav.querySelectorAll(':scope > a[data-tab-link]'),function(l){byTab[l.getAttribute('data-tab-link')]=l});var items=[byTab.ringkasan,byTab.laporan,byTab['laporan-monitoring'],byTab.riwayat,byTab['status-satuan']];if(items.some(function(x){return !x}))return;function group(label,children){var g=document.createElement('div'),t=document.createElement('button'),m=document.createElement('div');g.className='side-dropdown';t.type='button';t.className='side-link side-dropdown-toggle';t.innerHTML='<span class="dot"></span><span class="side-link-label"></span><svg class="side-dropdown-arrow" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"></path></svg>';t.querySelector('.side-link-label').textContent=label;m.className='side-dropdown-menu';children.forEach(function(l){l.classList.add('side-sublink');l.classList.remove('active');clean(l);m.appendChild(l)});g.appendChild(t);g.appendChild(m);t.addEventListener('click',function(){g.classList.toggle('open')});return g}nav.innerHTML='';var label=document.createElement('div');label.className='side-nav-label';label.textContent='Menu';nav.appendChild(label);nav.appendChild(byTab.ringkasan);nav.appendChild(group('Riwayat Aktivitas',[byTab['laporan-monitoring'],byTab['status-satuan']]));nav.appendChild(group('Pelaporan',[byTab.laporan,byTab.riwayat]));nav.querySelectorAll('.side-dropdown-menu a').forEach(clean)}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init()})();
  function terapkanRowLimitWrap(wrap){if(!wrap)return;var limit=parseInt(wrap.getAttribute('data-row-limit'),10)||5,table=wrap.querySelector('table');if(!table)return;wrap.style.maxHeight='';wrap.classList.remove('tbl-scroll');var thead=table.querySelector('thead'),rows=Array.prototype.filter.call(table.querySelectorAll('tbody tr'),function(r){return r.style.display!=='none'});if(rows.length<=limit)return;var h=thead?thead.offsetHeight:0;for(var i=0;i<limit;i++)h+=rows[i].offsetHeight;if(h>0){wrap.style.maxHeight=h+'px';wrap.classList.add('tbl-scroll')}}window.terapkanRowLimitWrap=terapkanRowLimitWrap;function terapkanRowLimit(panel){if(panel)panel.querySelectorAll('[data-row-limit]').forEach(terapkanRowLimitWrap)}
  const ADMIN_ACTIVE_TAB_KEY='siberad-admin-active-tab';const links=document.querySelectorAll('[data-tab-link]'),panels=document.querySelectorAll('[data-tab-panel]');function activateAdminTab(target,skipSave){var p=document.querySelector('[data-tab-panel="'+target+'"]');if(!p)return false;links.forEach(l=>l.classList.remove('active'));panels.forEach(x=>x.classList.remove('active'));document.querySelectorAll('[data-tab-link="'+target+'"]').forEach(l=>l.classList.add('active'));document.querySelectorAll('.side-nav-group').forEach(function(g){g.classList.remove('has-active-child')});document.querySelectorAll('[data-tab-link="'+target+'"]').forEach(function(l){var g=l.closest('.side-nav-group');if(g){g.classList.add('has-active-child');if(typeof window.siberadMarkAdminGroupOpen==='function')window.siberadMarkAdminGroupOpen(g);if(sidebar&&!sidebar.classList.contains('collapsed')&&!g.classList.contains('open')){g.classList.add('open');if(typeof window.siberadRepositionSubnavFlyouts==='function')window.siberadRepositionSubnavFlyouts()}}});p.classList.add('active');terapkanRowLimit(p);if(!skipSave)try{sessionStorage.setItem(ADMIN_ACTIVE_TAB_KEY,target)}catch(e){}return true}links.forEach(l=>l.addEventListener('click',function(e){e.preventDefault();activateAdminTab(l.getAttribute('data-tab-link'));if(window.innerWidth<=900&&sidebar)sidebar.classList.remove('open');window.scrollTo({top:0,behavior:'smooth'})}));try{var savedTab=sessionStorage.getItem(ADMIN_ACTIVE_TAB_KEY);if(savedTab)activateAdminTab(savedTab,true)}catch(e){}document.querySelectorAll('.tab-panel.active').forEach(terapkanRowLimit);(function(){var noFlash=document.getElementById('siberadNoFlashTab');if(noFlash)noFlash.remove()})();
  // Dipanggil dari lonceng notifikasi (notification-controls.blade.php)
  // buat langsung buka tab Admin yang relevan begitu notifikasi diklik --
  // versi Admin dari window.siberadGoToSection(), tab-nya pakai sistem
  // data-tab-panel (bukan hash section kayak dashboard Satuan/Pimpinan).
  window.siberadGoToSection=function(id){var ok=activateAdminTab(id);if(ok)window.scrollTo({top:0,behavior:'smooth'});return ok;};
  (function(){var btn=document.getElementById('themeToggleBtn');if(!btn||btn.dataset.uiBound)return;btn.dataset.uiBound='1';function apply(t){if(t==='light')document.documentElement.setAttribute('data-theme','light');else document.documentElement.removeAttribute('data-theme');btn.setAttribute('aria-pressed',t==='light'?'true':'false')}var saved='dark';try{saved=localStorage.getItem('siberad-theme')||'dark'}catch(e){}apply(saved);btn.addEventListener('click',function(){var n=document.documentElement.getAttribute('data-theme')==='light'?'dark':'light';try{localStorage.setItem('siberad-theme',n)}catch(e){}apply(n)})})();
  (function(){function clean(root){if(!root)return;var w=document.createTreeWalker(root,NodeFilter.SHOW_TEXT),n,a=[];while(n=w.nextNode())a.push(n);a.forEach(function(t){if(t.nodeValue&&t.nodeValue.trim())t.nodeValue=t.nodeValue.replace(/\s*\/\/\s*/g,' ').replace(/(^|\s)[-—](?=\s|$)/g,'$1').replace(/ {2,}/g,' ')})}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){clean(document.body)});else clean(document.body)})();
</script>
@if(isset($pengaturan))
<script src="{{ asset('js/admin-landing-editor.js') }}"></script>
<script src="{{ asset('js/admin-landing-live-preview.js') }}?v={{ filemtime(public_path('js/admin-landing-live-preview.js')) }}"></script>
<script src="{{ asset('js/role-access-layout.js') }}"></script>
@endif

<style>
.backup-action-row{display:flex;align-items:center;gap:18px;flex-wrap:wrap;padding:18px 22px}
.backup-action-row .backup-create-form,.backup-action-row .backup-upload-form{margin:0;padding:0;display:flex;align-items:center}
.backup-action-row .btn{height:44px;min-height:44px;box-sizing:border-box;display:inline-flex;align-items:center;}
.backup-upload-btn{white-space:nowrap}
.backup-upload-trigger{display:none !important;}
@media(max-width:520px){.backup-action-row{align-items:stretch}.backup-action-row .backup-create-form,.backup-action-row .backup-upload-form{width:100%}.backup-action-row .btn{width:100%;justify-content:center}}
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
    button.type='button';button.className='btn backup-upload-btn';button.textContent='Unggah File Cadangan';
    button.addEventListener('click',function(){input.click()});
    input.addEventListener('change',function(){if(!input.files||!input.files.length)return;button.disabled=true;button.textContent='Mengunggah...';uploadForm.submit()});
    uploadForm.appendChild(csrf);uploadForm.appendChild(input);uploadForm.appendChild(button);row.appendChild(uploadForm);
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
