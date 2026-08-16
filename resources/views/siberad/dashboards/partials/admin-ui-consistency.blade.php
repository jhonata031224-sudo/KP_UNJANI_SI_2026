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
/* Role & Hak Akses: proporsi kolom dibuat lebih seimbang agar tabel pas di viewport. */
.main .content [data-tab-panel="role-akses"] .role-akses-table-panel{overflow:hidden !important;width:100% !important;box-sizing:border-box !important;}
.main .content [data-tab-panel="role-akses"] .role-akses-table-wrap{overflow-x:hidden !important;width:100% !important;max-width:100% !important;box-sizing:border-box !important;}
.main .content [data-tab-panel="role-akses"] .role-akses-table{width:100% !important;min-width:0 !important;max-width:100% !important;table-layout:fixed !important;box-sizing:border-box !important;}
.main .content [data-tab-panel="role-akses"] .role-akses-table th,.main .content [data-tab-panel="role-akses"] .role-akses-table td{min-width:0 !important;box-sizing:border-box !important;}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(1),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(1){width:23% !important;padding-left:16px !important;padding-right:12px !important;}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(2),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(2){width:25% !important;padding-left:12px !important;padding-right:12px !important;}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(3),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(3){width:37% !important;padding-left:12px !important;padding-right:10px !important;}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(4),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(4){width:15% !important;padding-left:8px !important;padding-right:12px !important;}
.main .content [data-tab-panel="role-akses"] .role-akses-table .role-akses-checks{display:flex !important;flex-direction:column !important;align-items:flex-start !important;gap:9px !important;width:100% !important;}
.main .content [data-tab-panel="role-akses"] .role-akses-table .role-akses-check{display:flex !important;width:100% !important;max-width:100% !important;white-space:normal !important;min-width:0 !important;}
.main .content [data-tab-panel="role-akses"] .role-akses-action-head,.main .content [data-tab-panel="role-akses"] .role-akses-action{text-align:center !important;vertical-align:middle !important;}
.main .content [data-tab-panel="role-akses"] .role-akses-action button,.main .content [data-tab-panel="role-akses"] .role-akses-action .btn{max-width:100% !important;box-sizing:border-box !important;white-space:normal !important;}

/* Backup Database: judul kolom Aksi dan rangkaian tombol dimulai pada garis yang sama. */
.main .content [data-tab-panel="backup"] .dtbl th:last-child,
.main .content [data-tab-panel="backup"] .dtbl td:last-child{ text-align:left !important; vertical-align:middle !important; }
.main .content [data-tab-panel="backup"] .dtbl th:last-child{ padding-left:14px !important; padding-right:14px !important; }
.main .content [data-tab-panel="backup"] .dtbl td:last-child{ padding-left:14px !important; padding-right:14px !important; }
.main .content [data-tab-panel="backup"] .backup-extra-actions{ display:flex !important;align-items:center !important;justify-content:flex-start !important;gap:8px !important;flex-wrap:wrap !important;width:100% !important;margin:0 !important; }
.main .content [data-tab-panel="backup"] .backup-extra-actions > a,
.main .content [data-tab-panel="backup"] .backup-extra-actions > form{ margin:0 !important; }
.main .content [data-tab-panel="backup"] .backup-extra-actions > a,
.main .content [data-tab-panel="backup"] .backup-extra-actions > form > button{ min-width:74px !important;box-sizing:border-box !important;text-align:center !important; }
@media (max-width:700px){
  .main .content [data-tab-panel="backup"] .backup-extra-actions{ gap:6px !important; }
  .main .content [data-tab-panel="backup"] .backup-extra-actions > a,
  .main .content [data-tab-panel="backup"] .backup-extra-actions > form > button{ min-width:68px !important; }
}
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
  function initBackupActions(){
    var panel=document.querySelector('[data-tab-panel="backup"]');if(!panel||panel.dataset.backupRestoreUiBound==='1')return;panel.dataset.backupRestoreUiBound='1';
    var tokenInput=document.querySelector('input[name="_token"]');var token=tokenInput?tokenInput.value:'';
    var links=panel.querySelectorAll('table tbody tr a[href*="/admin/backup/"][href$="/download"]');
    links.forEach(function(downloadLink){
      var cell=downloadLink.closest('td');if(!cell||cell.querySelector('.backup-extra-actions'))return;
      var downloadHref=downloadLink.href;var viewHref=downloadHref.replace(/\/download(?:\?.*)?$/,'/view');var restoreHref=downloadHref.replace(/\/download(?:\?.*)?$/,'/restore');
      var fileName=(downloadHref.match(/\/backup\/([^/]+)\/download/)||[])[1]||'backup ini';try{fileName=decodeURIComponent(fileName);}catch(e){}
      var wrap=document.createElement('div');wrap.className='btn-row backup-extra-actions';wrap.style.flexWrap='wrap';wrap.style.justifyContent='center';wrap.style.gap='6px';
      downloadLink.style.margin='0';downloadLink.textContent='Unduh';wrap.appendChild(downloadLink);
      var view=document.createElement('a');view.className='btn btn-sm';view.href=viewHref;view.target='_blank';view.rel='noopener';view.textContent='Lihat';wrap.appendChild(view);
      var form=document.createElement('form');form.method='POST';form.action=restoreHref;form.style.margin='0';form.addEventListener('submit',function(e){var ok=window.confirm('Restore backup ini akan mengganti data database saat ini. Sistem akan membuat safety backup otomatis sebelum proses. Lanjutkan?');if(!ok){e.preventDefault();return false;}return true;});
      if(token){var csrf=document.createElement('input');csrf.type='hidden';csrf.name='_token';csrf.value=token;form.appendChild(csrf);}
      var restore=document.createElement('button');restore.type='submit';restore.className='btn btn-sm btn-ghost-red';restore.textContent='Restore';form.appendChild(restore);wrap.appendChild(form);
      cell.innerHTML='';cell.appendChild(wrap);
    });
  }
  function boot(){initBackupActions();}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
  document.addEventListener('click',function(e){if(e.target.closest('[data-tab-link="backup"]'))window.setTimeout(initBackupActions,50);});
})();
</script>