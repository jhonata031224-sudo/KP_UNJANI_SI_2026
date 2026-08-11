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

        // Jika modul permintaan belum dipindahkan oleh partial deadline,
        // tunggu sebentar lalu terapkan mode kembali tanpa menyentuh fungsi lain.
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
})();
</script>
