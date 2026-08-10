<style>
  /* Danpus: ringkasan seluruh satuan tidak lagi menjadi submenu Log Aktivitas. */
  #monitorGroup .side-sub-link[href="#monitoring"]{display:none!important}
</style>
<script>
(function(){
  function removeRingkasanSubmenu(){
    var link=document.querySelector('#monitorGroup .side-sub-link[href="#monitoring"]');
    if(link) link.remove();
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',removeRingkasanSubmenu);
  else removeRingkasanSubmenu();
})();
</script>
