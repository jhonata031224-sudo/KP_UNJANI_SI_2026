<style>
.danpus-request-menu{position:relative;display:flex;align-items:center;gap:10px;padding:9px 12px 9px 17px;margin:1px 0;border-radius:0 9px 9px 0;color:var(--text-muted);font-family:var(--body);font-size:13px;font-weight:500;line-height:normal;text-decoration:none;box-sizing:border-box;transition:background .15s ease,color .15s ease}.danpus-request-menu:hover{background:var(--hover-tint);color:var(--text)}.danpus-request-menu.active{background:var(--gold-dim,rgba(201,122,0,.1));color:var(--gold-bright,var(--p-accent));font-weight:600}.danpus-request-menu.active:before{content:"";position:absolute;left:-1px;top:8px;bottom:8px;width:2px;border-radius:2px;background:var(--gold-bright,var(--p-accent))}.danpus-request-menu .request-icon{width:5px;height:5px;border-radius:50%;background:currentColor;opacity:.5;flex:0 0 auto}.danpus-request-menu.active .request-icon{background:var(--gold-bright,var(--p-accent));opacity:1;box-shadow:0 0 0 3px rgba(201,122,0,.15)}
.sidebar.collapsed .danpus-request-menu{justify-content:center;padding:10px}.sidebar.collapsed .danpus-request-menu .request-text{display:none}
</style>
<script>
(function(){
  const requestUrl='{{ route('permintaan-laporan.index') }}';
  const isRequestPage=window.location.pathname.replace(/\/+$/,'')==='/permintaan-laporan';

  function text(el){return (el?.textContent||'').replace(/\s+/g,' ').trim().toLowerCase();}

  function addPermintaanToPelaporan(){
    const sidebar=document.querySelector('.sidebar');
    const groups=Array.from(sidebar?.querySelectorAll('.side-nav-group')||[]);
    const pelaporan=groups.find(g=>text(g.querySelector('.side-nav-group-title'))==='pelaporan');
    if(!pelaporan)return;

    const subnav=pelaporan.querySelector('.side-subnav > div')||pelaporan.querySelector('.side-subnav');
    if(!subnav)return;
    if(subnav.querySelector('.danpus-request-menu'))return;

    const link=document.createElement('a');
    link.className='danpus-request-menu'+(isRequestPage?' active':'');
    link.href=requestUrl;
    link.innerHTML='<span class="request-icon" aria-hidden="true"></span><span class="request-text">Permintaan Laporan</span>';

    // Letakkan sebagai submenu Pelaporan, setelah Riwayat/Laporan yang sudah ada.
    subnav.appendChild(link);
  }

  function init(){addPermintaanToPelaporan();}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
})();
</script>
