<style>
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
}
/* Admin juga mengikuti perilaku profil role lain: keterangan tambahan di Profil Saya tidak ditampilkan. */
.profile-modal-card .profile-help-text,
.profile-modal-card .profile-form-notice { display:none !important; }
@media (max-width:900px){
  .main > .content,.main .content{padding-left:20px !important;padding-right:20px !important;}
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
