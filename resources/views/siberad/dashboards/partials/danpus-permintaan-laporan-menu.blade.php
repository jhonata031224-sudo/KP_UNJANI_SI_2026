<style>
.danpus-request-menu{position:relative;display:flex;align-items:center;gap:10px;padding:10px 12px;margin:3px 0;border:1px solid transparent;border-radius:9px;color:var(--text-muted);font-family:var(--body);font-size:13.5px;font-weight:600;text-decoration:none;box-sizing:border-box;transition:background .15s ease,color .15s ease}.danpus-request-menu:hover{background:var(--hover-tint);color:var(--text)}.danpus-request-menu.active{background:var(--gold-dim,rgba(201,122,0,.1));color:var(--gold-bright);font-weight:700}.danpus-request-menu .request-icon{width:17px;height:17px;display:grid;place-items:center;flex:0 0 auto}.danpus-request-menu .request-text{flex:1}.sidebar.collapsed .danpus-request-menu{justify-content:center;padding:10px}.sidebar.collapsed .danpus-request-menu .request-text{display:none}
</style>
<script>
(function(){
  function addPermintaanMenu(){
    const sidebar=document.querySelector('.sidebar');
    const nav=sidebar?.querySelector('.side-nav');
    if(!nav||nav.querySelector('.danpus-request-menu'))return;
    const link=document.createElement('a');
    link.className='danpus-request-menu';
    link.href='{{ route('permintaan-laporan.index') }}';
    link.innerHTML='<span class="request-icon" aria-hidden="true">▣</span><span class="request-text">Permintaan Laporan</span>';
    if(window.location.pathname==='/permintaan-laporan')link.classList.add('active');
    const reportGroup=nav.querySelector('#reportGroup,.side-nav-group:last-of-type');
    if(reportGroup)nav.insertBefore(link,reportGroup);else nav.appendChild(link);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',addPermintaanMenu);else addPermintaanMenu();
})();
</script>
