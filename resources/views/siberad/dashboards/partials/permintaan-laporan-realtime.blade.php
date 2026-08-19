<script>
(function(){
    // Catatan penting: dulu file ini juga ikut mengganti/menyisipkan
    // <article> kartu Permintaan Laporan dan mengonversi tombol
    // "Konfirmasi" jadi "Lihat Detail" lewat sistem overlay sendiri
    // (bindDetailButtons/openDetail). Itu berjalan berbarengan dengan
    // laporan-role-realtime-sync.blade.php yang JUGA polling & mengganti
    // <article> yang sama tiap ~2.5 detik. Karena dua script berebut DOM
    // yang sama di interval yang mepet, tombol/modal-nya terus-menerus
    // dibongkar-pasang -- klik yang jatuh persis saat elemen sedang
    // diganti jadi "hilang" (elemen yang diklik sudah tidak ada lagi di
    // DOM saat event click diproses), sehingga tombol terasa tidak
    // merespons. laporan-role-realtime-sync.blade.php sekarang jadi
    // satu-satunya pemilik DOM kartu Permintaan Laporan (insert item baru
    // + update status keduanya sudah ditangani di sana). File ini HANYA
    // dipertahankan untuk toast "permintaan baru masuk" & sinkron angka
    // kartu statistik "Laporan Masuk" -- tidak lagi menyentuh DOM kartu.
    var endpoint='{{ route('permintaan-laporan.realtime') }}';
    var lastSeen=0;
    var polling=false;
    function existingLatestId(){var ids=[];document.querySelectorAll('#permintaan-laporan [data-realtime-permintaan-id]').forEach(function(el){var id=parseInt(el.getAttribute('data-realtime-permintaan-id')||'0',10);if(id)ids.push(id);});return ids.length?Math.max.apply(Math,ids):0;}
    function syncIncomingReportCount(data){if(typeof data.laporan_masuk_count==='undefined')return;var value=String(parseInt(data.laporan_masuk_count||0,10));document.querySelectorAll('.stat-card .lbl').forEach(function(label){if((label.textContent||'').trim().toLowerCase()!=='laporan masuk')return;var card=label.closest('.stat-card');var valueEl=card&&card.querySelector('.val');if(valueEl)valueEl.textContent=value;});}
    function poll(initial){
        if(polling)return;polling=true;
        var since=initial?0:lastSeen;
        fetch(endpoint+'?since='+encodeURIComponent(since),{method:'GET',credentials:'same-origin',cache:'no-store',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(function(response){
                if(response.status===401){if(window.siberadTampilkanSesiBerakhir)window.siberadTampilkanSesiBerakhir();return null;}
                if(response.status===419)return null;
                if(!response.ok)throw new Error('Realtime request failed');
                return response.json();
            })
            .then(function(data){
                if(!data)return;
                syncIncomingReportCount(data);
                var latestId=parseInt(data.latest_id||0,10);
                if(!initial && latestId>lastSeen && window.siberadShowToast)window.siberadShowToast('success','Permintaan laporan baru masuk.');
                lastSeen=Math.max(lastSeen,latestId);
                if(initial)lastSeen=Math.max(lastSeen,existingLatestId());
            })
            .catch(function(){})
            .finally(function(){polling=false;});
    }
    function start(){if(!document.getElementById('permintaan-laporan'))return;poll(true);window.setInterval(function(){poll(false);},3000);}
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
</script>
