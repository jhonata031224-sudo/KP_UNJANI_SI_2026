<style>
/* Admin: sidebar + header memakai warna surface/menu (#11181F), bukan hitam pekat. */
body:has(.side-nav-group) .sidebar,
body:has(.side-nav-group) .side-brand,
body:has(.side-nav-group) .side-foot,
body:has(.side-nav-group) .topbar,
body:has(.side-nav-group) header.topbar-simple {
  background:var(--panel) !important;
  backdrop-filter:none !important;
  -webkit-backdrop-filter:none !important;
}

body:has(.side-nav-group) .sidebar {
  border-right-color:var(--border-soft) !important;
}

body:has(.side-nav-group) .side-brand,
body:has(.side-nav-group) .topbar {
  border-bottom-color:var(--border-soft) !important;
}

/* Konsistensi layout: area konten mengikuti lebar area utama sampai sisi kanan topbar/profil. */
.main > .content,
.main .content {
  width:100% !important;
  max-width:none !important;
  margin-left:0 !important;
  margin-right:0 !important;
  box-sizing:border-box !important;
  padding-left:32px !important;
  padding-right:32px !important;
  overflow-x:hidden !important;
}
.main .content > .container,
.main .content > .page,
.main .content > .pimp-page,
.main .content > .report-page {
  width:100% !important;
  max-width:none !important;
  margin-left:0 !important;
  margin-right:0 !important;
  box-sizing:border-box !important;
  min-width:0 !important;
}
/* Grafik Admin mengikuti lebar kartu/kolom yang tersedia. Tidak boleh memaksa
   halaman melebar dan menimbulkan horizontal scrolling saat sidebar terbuka. */
.main .content canvas {
  display:block !important;
  width:100% !important;
  max-width:100% !important;
  height:auto !important;
  box-sizing:border-box !important;
}
.main .content :has(> canvas),
.main .content :has(> .chart-container),
.main .content :has(> .chart-wrap) {
  min-width:0 !important;
  max-width:100% !important;
  box-sizing:border-box !important;
  overflow:hidden !important;
}
.main .content [style*="min-width"] {
  min-width:0 !important;
}
/* Grid/flex children tidak boleh mempertahankan lebar intrinsik grafik. */
.main .content .grid,
.main .content [class*="grid"],
.main .content [class*="chart"],
.main .content [class*="stat"] {
  min-width:0 !important;
  box-sizing:border-box !important;
}
/* Admin juga mengikuti perilaku profil role lain: keterangan tambahan di tab
   Foto Profil tidak ditampilkan. Di-scope ke #profilePhotoView saja (bukan
   seluruh .profile-modal-card) supaya tidak ikut menyembunyikan isi Bantuan
   & Panduan, yang juga memakai class .profile-help-text. */
#profilePhotoView .profile-help-text,
.profile-modal-card .profile-form-notice { display:none !important; }
@media (max-width:1200px){
  .main .content [class*="grid"] { grid-template-columns:repeat(2,minmax(0,1fr)) !important; }
}
@media (max-width:900px){
  .main > .content,.main .content{padding-left:20px !important;padding-right:20px !important;}
  .main .content [class*="grid"] { grid-template-columns:1fr !important; }
}
@media (max-width:600px){
  .main > .content,.main .content{padding-left:14px !important;padding-right:14px !important;}
}
/* Tombol Tutup pada header notifikasi. */
#notifDropdown .notif-head{display:flex !important;align-items:center !important;justify-content:space-between !important;gap:12px !important;}
#notifDropdown .siberad-notif-close-text{border:0;background:transparent;color:var(--text-muted);font:500 12px var(--body);padding:4px 0;cursor:pointer;white-space:nowrap;}
#notifDropdown .siberad-notif-close-text:hover{color:var(--gold-bright);}
</style>

<script>
(function(){
  function addNotificationCloseText(){
    var dropdown=document.getElementById('notifDropdown');
    if(!dropdown) return;
    var header=dropdown.querySelector('.notif-head') || dropdown.querySelector('.profile-dropdown-head');
    if(!header || header.querySelector('.siberad-notif-close-text')) return;
    var close=document.createElement('button');
    close.type='button';
    close.className='siberad-notif-close-text';
    close.textContent='Tutup';
    close.setAttribute('aria-label','Tutup notifikasi');
    close.addEventListener('click',function(e){
      e.preventDefault();e.stopPropagation();
      dropdown.classList.remove('open');
      var btn=document.getElementById('notifBtn');
      if(btn){btn.classList.remove('open');btn.setAttribute('aria-expanded','false');}
    });
    header.appendChild(close);
  }
  function init(){
    addNotificationCloseText();
    var observer=new MutationObserver(addNotificationCloseText);
    observer.observe(document.body,{childList:true,subtree:true});
    window.setTimeout(function(){observer.disconnect();addNotificationCloseText();},3000);
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init);
  else init();
})();
</script>

<script>
(function(){
  function initMobileSidebar(){
    var menuBtn=document.getElementById('menuBtn');
    var sidebar=document.getElementById('sidebar');
    if(!menuBtn||!sidebar||menuBtn.dataset.mobileSidebarWideBound==='1') return;

    menuBtn.dataset.mobileSidebarWideBound='1';
    var suppressNextClick=false;
    var suppressTimer=0;

    function openSidebar(e){
      if(e){e.preventDefault();e.stopPropagation();}
      sidebar.classList.remove('collapsed');
      sidebar.classList.add('open');
    }

    if(window.PointerEvent){
      menuBtn.addEventListener('pointerup',function(e){
        if(window.innerWidth>900) return;
        suppressNextClick=true;
        window.clearTimeout(suppressTimer);
        suppressTimer=window.setTimeout(function(){suppressNextClick=false;},500);
        openSidebar(e);
      },{passive:false});

      menuBtn.addEventListener('click',function(e){
        if(window.innerWidth>900) return;
        if(suppressNextClick){
          suppressNextClick=false;
          window.clearTimeout(suppressTimer);
          e.preventDefault();
          e.stopPropagation();
          return;
        }
        if(e.detail===0) openSidebar(e);
      });
    }else{
      menuBtn.addEventListener('click',function(e){
        if(window.innerWidth<=900) openSidebar(e);
      });
    }

    document.addEventListener('click',function(e){
      if(window.innerWidth<=900 &&
         sidebar.classList.contains('open') &&
         !sidebar.contains(e.target) &&
         e.target!==menuBtn){
        sidebar.classList.remove('open');
        sidebar.classList.add('collapsed');
      }
    });
  }

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initMobileSidebar);
  else initMobileSidebar();
})();
</script>