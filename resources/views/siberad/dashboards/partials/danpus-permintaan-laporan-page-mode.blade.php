<style>
body.permintaan-page-mode .pimp-page > *:not(#permintaan-laporan){display:none!important}
body.permintaan-page-mode .pimp-page{max-width:none!important;margin:0!important}
body.permintaan-page-mode #permintaan-laporan{display:block!important;margin:0!important}
body.permintaan-page-mode .content{padding-bottom:40px!important}
</style>
<script>
(function(){
    function initPermintaanPageMode(){
        if(new URLSearchParams(window.location.search).get('section') !== 'permintaan') return;
        document.body.classList.add('permintaan-page-mode');
        var observer=new MutationObserver(function(){
            var section=document.getElementById('permintaan-laporan');
            if(section){
                document.body.classList.add('permintaan-page-mode');
                observer.disconnect();
            }
        });
        observer.observe(document.body,{childList:true,subtree:true});
        setTimeout(function(){observer.disconnect()},3000);
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initPermintaanPageMode,{once:true});
    else initPermintaanPageMode();

    document.addEventListener('click',function(e){
        var link=e.target.closest && e.target.closest('.side-link,.side-sub-link');
        if(!link) return;
        var text=(link.textContent||'').replace(/\s+/g,' ').trim().toLowerCase();
        if(text==='permintaan laporan') return;
        document.body.classList.remove('permintaan-page-mode');
    },true);

    window.addEventListener('popstate',function(){
        if(new URLSearchParams(window.location.search).get('section')!=='permintaan'){
            document.body.classList.remove('permintaan-page-mode');
        }
    });
})();
</script>
