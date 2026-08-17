<style>
/* Admin: sidebar + header memakai warna surface/menu (#11181F), bukan hitam pekat. */
body:has(.side-nav-group) .sidebar,
body:has(.side-nav-group) .side-brand,
body:has(.side-nav-group) .side-foot,
body:has(.side-nav-group) .topbar,
body:has(.side-nav-group) header.topbar-simple { background:var(--panel) !important;backdrop-filter:none !important;-webkit-backdrop-filter:none !important; }
body:has(.side-nav-group) .sidebar { border-right-color:var(--border-soft) !important; }
body:has(.side-nav-group) .side-brand,
body:has(.side-nav-group) .topbar { border-bottom-color:var(--border-soft) !important; }
.main > .content,.main .content { width:100% !important;max-width:none !important;margin-left:0 !important;margin-right:0 !important;box-sizing:border-box !important;padding-left:32px !important;padding-right:32px !important;overflow-x:hidden !important; }
.main .content > .container,.main .content > .page,.main .content > .pimp-page,.main .content > .report-page { width:100% !important;max-width:none !important;margin-left:0 !important;margin-right:0 !important;box-sizing:border-box !important;min-width:0 !important; }
.main .content canvas { display:block !important;width:100% !important;max-width:100% !important;height:auto !important;box-sizing:border-box !important; }
.main .content :has(> canvas),.main .content :has(> .chart-container),.main .content :has(> .chart-wrap) { min-width:0 !important;max-width:100% !important;box-sizing:border-box !important;overflow:hidden !important; }
.main .content [style*="min-width"] { min-width:0 !important; }
.main .content .grid,.main .content [class*="grid"],.main .content [class*="chart"],.main .content [class*="stat"] { min-width:0 !important;box-sizing:border-box !important; }
#profilePhotoView .profile-help-text,.profile-modal-card .profile-form-notice { display:none !important; }
@media (max-width:1200px){ .main .content [class*="grid"] { grid-template-columns:repeat(2,minmax(0,1fr)) !important; } }
@media (max-width:900px){ .main > .content,.main .content{padding-left:20px !important;padding-right:20px !important;} .main .content [class*="grid"] { grid-template-columns:1fr !important; } }
@media (max-width:600px){ .main > .content,.main .content{padding-left:14px !important;padding-right:14px !important;} }

/* Role & Hak Akses: setiap pilihan modul ditampilkan satu baris ke bawah. */
.main .content [data-tab-panel="role-akses"] form > div:first-of-type { display:flex !important;flex-direction:column !important;align-items:flex-start !important;gap:10px !important;margin-bottom:14px !important; }
.main .content [data-tab-panel="role-akses"] form > div:first-of-type > label { width:100% !important;box-sizing:border-box !important; }

/* Tombol Tutup pada header notifikasi. */
#notifDropdown .notif-head{display:flex !important;align-items:center !important;justify-content:space-between !important;gap:12px !important;}
#notifDropdown .siberad-notif-close-text{border:0;background:transparent;color:var(--text-muted);font:500 12px var(--body);padding:4px 0;cursor:pointer;white-space:nowrap;}
#notifDropdown .siberad-notif-close-text:hover{color:var(--gold-bright);}
</style>

<style>
/* Role & Hak Akses: layout tabel final dan konsisten. */
.main .content [data-tab-panel="role-akses"] .role-akses-table-panel{overflow:hidden!important;width:100%!important;box-sizing:border-box!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table-wrap{overflow:hidden!important;width:100%!important;max-width:100%!important;box-sizing:border-box!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table{width:100%!important;min-width:0!important;max-width:100%!important;table-layout:fixed!important;border-collapse:collapse!important;box-sizing:border-box!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table th,.main .content [data-tab-panel="role-akses"] .role-akses-table td{min-width:0!important;box-sizing:border-box!important;padding:14px 16px!important;}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(1),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(1){width:24%!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(2),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(2){width:26%!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(3),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(3){width:34%!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(4),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(4){width:16%!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(4){text-align:center!important;vertical-align:middle!important;padding-left:8px!important;padding-right:8px!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(4){text-align:center!important;vertical-align:middle!important;padding-left:8px!important;padding-right:8px!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table .role-akses-checks{display:flex!important;flex-direction:column!important;align-items:flex-start!important;gap:7px!important;width:100%!important;margin:0!important;padding:0!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table .role-akses-check{display:flex!important;align-items:center!important;width:100%!important;max-width:100%!important;white-space:normal!important;min-width:0!important;margin:0!important;padding:0!important;line-height:1.35!important}
.main .content [data-tab-panel="role-akses"] .role-akses-action-head,.main .content [data-tab-panel="role-akses"] .role-akses-action{text-align:center!important;vertical-align:middle!important}
.main .content [data-tab-panel="role-akses"] .role-akses-action button,.main .content [data-tab-panel="role-akses"] .role-akses-action .btn{width:76px!important;max-width:76px!important;min-width:76px!important;height:34px!important;min-height:34px!important;box-sizing:border-box!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;white-space:nowrap!important;overflow:hidden!important;padding:6px 10px!important;margin:0 auto!important;font-size:0!important;line-height:1!important;letter-spacing:0!important;text-align:center!important}
.main .content [data-tab-panel="role-akses"] .role-akses-action button::after,.main .content [data-tab-panel="role-akses"] .role-akses-action .btn::after{content:"Simpan";display:block;font-family:var(--mono);font-size:10px;font-weight:700;letter-spacing:.045em;line-height:1;text-transform:none;}
.main .content [data-tab-panel="role-akses"] .role-akses-action button:focus-visible,.main .content [data-tab-panel="role-akses"] .role-akses-action .btn:focus-visible{outline:2px solid var(--gold-bright);outline-offset:2px}
@media(max-width:760px){
  .main .content [data-tab-panel="role-akses"] .role-akses-table th,.main .content [data-tab-panel="role-akses"] .role-akses-table td{padding:12px 10px!important}
  .main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(1),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(1){width:24%!important}
  .main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(2),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(2){width:26%!important}
  .main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(3),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(3){width:34%!important}
  .main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(4),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(4){width:16%!important;padding-left:6px!important;padding-right:6px!important}
  .main .content [data-tab-panel="role-akses"] .role-akses-action button,.main .content [data-tab-panel="role-akses"] .role-akses-action .btn{width:70px!important;max-width:70px!important;min-width:70px!important;height:32px!important;min-height:32px!important;padding:5px 8px!important}
  .main .content [data-tab-panel="role-akses"] .role-akses-action button::after,.main .content [data-tab-panel="role-akses"] .role-akses-action .btn::after{font-size:9.5px}
}

/* Backup Database */
.main .content [data-tab-panel="backup"] .dtbl th:last-child,
.main .content [data-tab-panel="backup"] .dtbl td:last-child{ text-align:left !important;vertical-align:middle !important; }
.main .content [data-tab-panel="backup"] .dtbl th:last-child,
.main .content [data-tab-panel="backup"] .dtbl td:last-child{ padding-left:14px !important;padding-right:14px !important; }
.main .content [data-tab-panel="backup"] .backup-extra-actions{ display:flex !important;align-items:center !important;justify-content:flex-start !important;gap:8px !important;flex-wrap:wrap !important;width:100% !important;margin:0 !important; }
.main .content [data-tab-panel="backup"] .backup-extra-actions > a,
.main .content [data-tab-panel="backup"] .backup-extra-actions > form{ margin:0 !important; }
.main .content [data-tab-panel="backup"] .backup-extra-actions > a,
.main .content [data-tab-panel="backup"] .backup-extra-actions > form > button{ min-width:74px !important;box-sizing:border-box !important;text-align:center !important;justify-content:center !important; }
.main .content [data-tab-panel="backup"] .backup-upload-panel{display:none !important;}
.main .content [data-tab-panel="backup"] .backup-history-head{display:flex !important;align-items:center !important;justify-content:space-between !important;gap:14px !important;}
.main .content [data-tab-panel="backup"] .backup-upload-trigger{flex-shrink:0;}
@media(max-width:640px){
  .main .content [data-tab-panel="backup"] .backup-extra-actions{gap:6px !important;}
  .main .content [data-tab-panel="backup"] .backup-extra-actions > a,
  .main .content [data-tab-panel="backup"] .backup-extra-actions > form > button{min-width:68px !important;}
}

.siberad-backup-upload-modal{position:fixed;inset:0;z-index:10080;display:flex;align-items:center;justify-content:center;padding:22px;box-sizing:border-box;background:rgba(2,4,6,.64);backdrop-filter:blur(5px);-webkit-backdrop-filter:blur(5px);opacity:0;visibility:hidden;pointer-events:none;transition:opacity .18s ease,visibility .18s ease;}
.siberad-backup-upload-modal.open{opacity:1;visibility:visible;pointer-events:auto;}
.siberad-backup-upload-card{width:430px;max-width:100%;box-sizing:border-box;background:var(--panel);border:1px solid var(--border-soft);border-radius:15px;box-shadow:0 24px 70px rgba(0,0,0,.45);padding:22px;transform:translateY(10px) scale(.98);transition:transform .18s ease;}
.siberad-backup-upload-modal.open .siberad-backup-upload-card{transform:none;}
.siberad-backup-upload-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:18px;}
.siberad-backup-upload-head h3{margin:0;font-family:var(--display);font-size:17px;color:var(--text);}
.siberad-backup-upload-head p{margin:5px 0 0;font-size:12px;line-height:1.6;color:var(--text-muted);}
.siberad-backup-upload-close{width:34px;height:34px;flex:0 0 auto;border:1px solid var(--border-soft);border-radius:9px;background:transparent;color:var(--text-muted);cursor:pointer;font-size:18px;line-height:1;}
.siberad-backup-upload-close:hover{border-color:var(--red);color:var(--red);}
.siberad-backup-upload-drop{border:1px dashed var(--border-strong);border-radius:11px;padding:18px;background:var(--panel-alt);}
.siberad-backup-upload-drop input[type="file"]{width:100%;box-sizing:border-box;color:var(--text);font-size:12px;}
.siberad-backup-upload-meta{margin-top:9px;font-size:11px;color:var(--text-dim);line-height:1.5;}
.siberad-backup-upload-actions{display:flex;justify-content:flex-end;gap:9px;margin-top:18px;}
@media(max-width:600px){.siberad-backup-upload-modal{padding:14px;}.siberad-backup-upload-card{padding:18px;}.siberad-backup-upload-actions{flex-direction:column-reverse;}.siberad-backup-upload-actions .btn{width:100%;}}
</style>

<script>
(function(){
  function addNotificationCloseText(){
    var dropdown=document.getElementById('notifDropdown');if(!dropdown)return;
    var header=dropdown.querySelector('.notif-head')||dropdown.querySelector('.profile-dropdown-head');if(!header||header.querySelector('.siberad-notif-close-text'))return;
    var close=document.createElement('button');close.type='button';close.className='siberad-notif-close-text';close.textContent='Tutup';close.setAttribute('aria-label','Tutup notifikasi');
    close.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();dropdown.classList.remove('open');var btn=document.getElementById('notifBtn');if(btn){btn.classList.remove('open');btn.setAttribute('aria-expanded','false');}});header.appendChild(close);
  }
  function init(){addNotificationCloseText();var observer=new MutationObserver(addNotificationCloseText);observer.observe(document.body,{childList:true,subtree:true});window.setTimeout(function(){observer.disconnect();addNotificationCloseText();},3000);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
</script>

<script>
(function(){
  function initMobileSidebar(){
    var menuBtn=document.getElementById('menuBtn');var sidebar=document.getElementById('sidebar');if(!menuBtn||!sidebar||menuBtn.dataset.mobileSidebarWideBound==='1')return;
    menuBtn.dataset.mobileSidebarWideBound='1';var suppressNextClick=false;var suppressTimer=0;
    function openSidebar(e){if(e){e.preventDefault();e.stopPropagation();}sidebar.classList.remove('collapsed');sidebar.classList.add('open');}
    function closeSidebarMobile(){sidebar.classList.remove('open');sidebar.classList.add('collapsed');document.querySelectorAll('.side-dropdown.open,.side-nav-group.open').forEach(function(group){group.classList.remove('open');});}
    if(window.PointerEvent){
      menuBtn.addEventListener('pointerup',function(e){if(window.innerWidth>900)return;suppressNextClick=true;window.clearTimeout(suppressTimer);suppressTimer=window.setTimeout(function(){suppressNextClick=false;},500);openSidebar(e);},{passive:false});
      menuBtn.addEventListener('click',function(e){if(window.innerWidth>900)return;if(suppressNextClick){suppressNextClick=false;window.clearTimeout(suppressTimer);e.preventDefault();e.stopPropagation();return;}if(e.detail===0)openSidebar(e);});
    }else{menuBtn.addEventListener('click',function(e){if(window.innerWidth<=900)openSidebar(e);});}
    document.addEventListener('click',function(e){if(window.innerWidth<=900&&sidebar.classList.contains('open')&&!sidebar.contains(e.target)&&e.target!==menuBtn)closeSidebarMobile();});
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initMobileSidebar);else initMobileSidebar();
})();
</script>

<script>
(function(){
  function csrfToken(){var input=document.querySelector('input[name="_token"]');return input?input.value:'';}
  void csrfToken;
})();
</script>

<script>
(function(){
  function initBackupUploadModal(){
    var panel=document.querySelector('[data-tab-panel="backup"]');
    if(!panel || panel.dataset.backupModalReady==='1')return;
    var historyHeads=panel.querySelectorAll('.panel-head');
    var historyHead=historyHeads.length?historyHeads[historyHeads.length-1]:null;
    var oldPanel=panel.querySelector('.backup-upload-panel');
    var oldForm=oldPanel?oldPanel.querySelector('form'):null;
    if(!historyHead || !oldForm)return;
    panel.dataset.backupModalReady='1';

    var trigger=document.createElement('button');
    trigger.type='button';trigger.className='btn btn-primary btn-sm backup-upload-trigger';trigger.textContent='Upload Backup';
    historyHead.classList.add('backup-history-head');historyHead.appendChild(trigger);

    var modal=document.createElement('div');
    modal.className='siberad-backup-upload-modal';modal.setAttribute('aria-hidden','true');
    modal.innerHTML='<div class="siberad-backup-upload-card" role="dialog" aria-modal="true" aria-labelledby="siberadBackupUploadTitle">'+
      '<div class="siberad-backup-upload-head"><div><h3 id="siberadBackupUploadTitle">Upload Backup</h3><p>Pilih file backup yang ingin ditambahkan ke riwayat backup.</p></div><button type="button" class="siberad-backup-upload-close" aria-label="Tutup">×</button></div>'+ 
      '<form class="siberad-backup-upload-form" method="POST" enctype="multipart/form-data">'+
      '<div class="siberad-backup-upload-drop"><input type="file" class="siberad-file-input" name="backup_file" accept=".sql,.sqlite" required><div class="siberad-backup-upload-meta">Format: .sql atau .sqlite • Maksimal 50 MB.</div></div>'+ 
      '<div class="siberad-backup-upload-actions"><button type="button" class="btn btn-ghost backup-upload-cancel">Batal</button><button type="submit" class="btn btn-primary">Upload</button></div></form></div>';
    document.body.appendChild(modal);
    if(typeof window.siberadEnhanceFileInputs==='function')window.siberadEnhanceFileInputs(modal);

    var form=modal.querySelector('.siberad-backup-upload-form');form.action=oldForm.action;
    var tokenInput=oldForm.querySelector('input[name="_token"]')||document.querySelector('input[name="_token"]');
    if(tokenInput){var csrf=document.createElement('input');csrf.type='hidden';csrf.name='_token';csrf.value=tokenInput.value;form.insertBefore(csrf,form.firstChild);}

    function close(){modal.classList.remove('open');modal.setAttribute('aria-hidden','true');}
    function open(){modal.classList.add('open');modal.setAttribute('aria-hidden','false');}
    trigger.addEventListener('click',open);
    modal.querySelector('.siberad-backup-upload-close').addEventListener('click',close);
    modal.querySelector('.backup-upload-cancel').addEventListener('click',close);
    modal.addEventListener('click',function(e){if(e.target===modal)close();});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'&&modal.classList.contains('open'))close();});

    form.addEventListener('submit',function(e){
      var file=form.querySelector('input[type="file"]').files[0];
      if(!file){e.preventDefault();window.alert('Pilih file backup terlebih dahulu.');return;}
      var ext=(file.name.split('.').pop()||'').toLowerCase();
      if(['sql','sqlite'].indexOf(ext)===-1){e.preventDefault();window.alert('Format backup hanya boleh .sql atau .sqlite.');return;}
      if(file.size>50*1024*1024){e.preventDefault();window.alert('Ukuran backup maksimal 50 MB.');return;}
      if(!window.confirm('Upload backup ini ke riwayat backup?')){e.preventDefault();return;}
    });
  }
  function boot(){window.setTimeout(initBackupUploadModal,80);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
  document.addEventListener('click',function(e){if(e.target.closest('[data-tab-link="backup"]'))window.setTimeout(initBackupUploadModal,100);});
})();
</script>

<style>
/* Final backup UI override: upload selalu di samping tombol buat backup,
   tabel riwayat konsisten dengan tabel manajemen satuan. */
.main .content [data-tab-panel="backup"] .backup-history-head .backup-upload-trigger{display:none !important;}
.main .content [data-tab-panel="backup"] .dtbl{width:100% !important;table-layout:fixed !important;}
.main .content [data-tab-panel="backup"] .dtbl th:nth-child(1),.main .content [data-tab-panel="backup"] .dtbl td:nth-child(1){width:20% !important;text-align:left !important;padding-left:16px !important;}
.main .content [data-tab-panel="backup"] .dtbl th:nth-child(2),.main .content [data-tab-panel="backup"] .dtbl td:nth-child(2){width:20% !important;}
.main .content [data-tab-panel="backup"] .dtbl th:nth-child(3),.main .content [data-tab-panel="backup"] .dtbl td:nth-child(3){width:20% !important;}
.main .content [data-tab-panel="backup"] .dtbl th:nth-child(4),.main .content [data-tab-panel="backup"] .dtbl td:nth-child(4){width:20% !important;}
.main .content [data-tab-panel="backup"] .dtbl th:nth-child(5),.main .content [data-tab-panel="backup"] .dtbl td:nth-child(5){width:20% !important;text-align:center !important;}
.main .content [data-tab-panel="backup"] .dtbl th:nth-child(2),.main .content [data-tab-panel="backup"] .dtbl th:nth-child(3),.main .content [data-tab-panel="backup"] .dtbl th:nth-child(4){text-align:center !important;}
.main .content [data-tab-panel="backup"] .dtbl td:nth-child(2),.main .content [data-tab-panel="backup"] .dtbl td:nth-child(3),.main .content [data-tab-panel="backup"] .dtbl td:nth-child(4){text-align:center !important;}
.main .content [data-tab-panel="backup"] .backup-extra-actions{justify-content:center !important;}
</style>

<script>
(function(){
  function moveUploadButton(){
    var panel=document.querySelector('[data-tab-panel="backup"]');
    if(!panel)return;
    var createForm=panel.querySelector('form[action$="/admin/backup"]');
    var oldTrigger=panel.querySelector('.backup-history-head .backup-upload-trigger');
    var modal=document.querySelector('.siberad-backup-upload-modal');
    if(!createForm||!modal)return;

    /* Hilangkan trigger lama di header riwayat yang dibuat script sebelumnya. */
    if(oldTrigger)oldTrigger.remove();

    var createButton=createForm.querySelector('button[type="submit"]');
    if(!createButton)return;
    var actions=createForm.querySelector('.backup-create-actions');
    if(!actions){
      actions=document.createElement('div');
      actions.className='backup-create-actions';
      createButton.parentNode.insertBefore(actions,createButton);
      actions.appendChild(createButton);
    }
    if(!actions.querySelector('.backup-upload-trigger')){
      var trigger=document.createElement('button');
      trigger.type='button';
      trigger.className='btn btn-sm backup-upload-trigger';
      trigger.textContent='Upload Backup';
      actions.appendChild(trigger);
      trigger.addEventListener('click',function(){
        modal.classList.add('open');
        modal.setAttribute('aria-hidden','false');
      });
    }
  }
  function boot(){window.setTimeout(moveUploadButton,120);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
  document.addEventListener('click',function(e){if(e.target.closest&&e.target.closest('[data-tab-link="backup"]'))window.setTimeout(moveUploadButton,160);});
})();
</script>


<style>
/* Backup Database: samakan tinggi dan beri jarak antar tombol aksi backup. */
.main .content [data-tab-panel="backup"] form[action$="/admin/backup"]{display:flex!important;align-items:center!important;gap:10px!important;flex-wrap:wrap!important;}
.main .content [data-tab-panel="backup"] form[action$="/admin/backup"] .btn,
.main .content [data-tab-panel="backup"] .backup-upload-trigger{height:44px!important;min-height:44px!important;box-sizing:border-box!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;margin:0!important;line-height:1!important;}
.main .content [data-tab-panel="backup"] .backup-upload-trigger{padding:0 18px!important;}
@media(max-width:640px){
  .main .content [data-tab-panel="backup"] form[action$="/admin/backup"]{gap:10px!important;}
  .main .content [data-tab-panel="backup"] form[action$="/admin/backup"] .btn,
  .main .content [data-tab-panel="backup"] .backup-upload-trigger{height:42px!important;min-height:42px!important;}
}
</style>

<style>
/* Backup Database: remove the duplicate visual upload form and keep one trigger. */
.main .content [data-tab-panel="backup"] .backup-action-row{display:flex!important;align-items:center!important;gap:0!important;flex-wrap:wrap!important;padding:18px 22px!important}
.main .content [data-tab-panel="backup"] .backup-action-row .backup-create-form{display:flex!important;align-items:center!important;margin:0!important;padding:0!important}
.main .content [data-tab-panel="backup"] .backup-action-row .backup-upload-form{display:none!important}
.main .content [data-tab-panel="backup"] .backup-action-row .backup-create-form .backup-create-actions{display:flex!important;align-items:center!important;gap:14px!important;margin:0!important;padding:0!important}
.main .content [data-tab-panel="backup"] .backup-action-row .backup-create-actions>.btn{margin:0!important}
.main .content [data-tab-panel="backup"] .backup-action-row .backup-upload-trigger{display:inline-flex!important;align-items:center!important;justify-content:center!important;height:44px!important;min-height:44px!important;margin:0!important;padding:0 18px!important;white-space:nowrap!important;box-sizing:border-box!important}
@media(max-width:640px){.main .content [data-tab-panel="backup"] .backup-action-row{padding:14px 16px!important}.main .content [data-tab-panel="backup"] .backup-action-row .backup-create-form{width:100%!important}.main .content [data-tab-panel="backup"] .backup-action-row .backup-create-form .backup-create-actions{width:100%!important;gap:10px!important}.main .content [data-tab-panel="backup"] .backup-action-row .backup-create-actions>.btn{flex:1 1 0!important}.main .content [data-tab-panel="backup"] .backup-action-row .backup-upload-trigger{height:42px!important;min-height:42px!important}}
</style>
