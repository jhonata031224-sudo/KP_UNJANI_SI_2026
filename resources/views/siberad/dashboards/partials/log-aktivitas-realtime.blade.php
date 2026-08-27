<style>
/* Baris laporan baru yang disisipkan live (lihat insertNewRows di bawah) --
   fade+slide halus dari atas, senada sama animasi .tab-panel.active di
   dash-styles.blade.php (fadeIn .25s), bukan efek flash/kedip. */
@keyframes siberadRowIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
.siberad-row-in{animation:siberadRowIn .35s ease}
</style>
<script>
(function () {
    const endpoint = '{{ route('laporan.log-aktivitas.realtime') }}';
    let busy = false;
    let timer = null;
    let sinceId = 0;
    let requestsSinceId = 0;
    const text = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value; };
    function updateStats(stats){ Object.entries(stats||{}).forEach(([id,s])=>{ text(`satlakTotalOverview-${id}`,s.total); text(`satlakTotalMonitoring-${id}`,s.total); text(`satlakDiterima-${id}`,s.diterima); text(`satlakDitolak-${id}`,s.ditolak); text(`satlakMenunggu-${id}`,s.menunggu); }); }
    // Baris laporan baru per satuan (data.rows, sudah dirender server pakai
    // partial yang SAMA dengan render awal -- laporan-pimpinan-row.blade.php)
    // disisipkan ke tabel "Aktivitas <Satuan>" (#satlak-{id}) masing-masing.
    // Dedup pakai data-laporan-id supaya aman kalau ada overlap antar poll.
    function insertNewRows(rowsBySatuan){
        if(!rowsBySatuan) return 0;
        let totalInserted = 0;
        Object.keys(rowsBySatuan).forEach(function(satuanId){
            const html = rowsBySatuan[satuanId];
            if(!html) return;
            const tbody = document.querySelector('#satlak-'+satuanId+' .clean-table tbody');
            if(!tbody) return;
            const temp = document.createElement('tbody');
            temp.innerHTML = html;
            const rows = Array.prototype.slice.call(temp.children);
            if(!rows.length) return;
            Array.prototype.slice.call(tbody.querySelectorAll('tr')).forEach(function(r){ if(r.querySelector('.empty-state')) r.remove(); });
            rows.reverse().forEach(function(row){
                // ID kosong (checkpoint progres yang belum "resmi" jadi
                // laporan tersendiri, lihat data-laporan-id di
                // laporan-pimpinan-row.blade.php) sengaja DILEWATI di sini
                // -- tanpa ID beneran, tidak ada cara aman buat dedup
                // terhadap baris yang sudah dirender waktu page-load,
                // jadi berisiko nyisipin baris DUPLIKAT.
                const id = row.getAttribute('data-laporan-id');
                if(!id || tbody.querySelector('[data-laporan-id="'+id+'"]')) return;
                row.classList.add('siberad-row-in');
                tbody.insertBefore(row, tbody.firstChild);
                totalInserted++;
            });
        });
        return totalInserted;
    }
    // Tab "Permintaan Laporan" milik Pimpinan sendiri (bukan yang diterima
    // Satlak) -- Danpus & Wadan bisa sama-sama buka dashboard & sama-sama
    // bikin permintaan, jadi permintaan baru dari SESI LAIN perlu muncul
    // live juga di sini. Tiap item = 2 <tr> (baris utama + baris task
    // checklist yang awalnya hidden), makanya diproses per-PASANGAN, bukan
    // per-baris kayak insertNewRows() di atas.
    function insertNewRequests(html){
        if(!html) return 0;
        const tbody = document.querySelector('#permintaan-laporan .request-table tbody');
        if(!tbody) return 0;
        const temp = document.createElement('tbody');
        temp.innerHTML = html;
        const rows = Array.prototype.slice.call(temp.children);
        if(!rows.length) return 0;
        Array.prototype.slice.call(tbody.querySelectorAll('tr')).forEach(function(r){ if(r.querySelector('.empty-state')) r.remove(); });
        const pairs = [];
        for(let i=0;i<rows.length;i+=2){ if(rows[i+1]) pairs.push([rows[i],rows[i+1]]); }
        let inserted = 0;
        pairs.reverse().forEach(function(pair){
            const id = pair[0].getAttribute('data-permintaan-id');
            if(id && tbody.querySelector('.request-row[data-permintaan-id="'+id+'"]')) return;
            pair[0].classList.add('siberad-row-in');
            const frag = document.createDocumentFragment();
            frag.appendChild(pair[0]);
            frag.appendChild(pair[1]);
            tbody.insertBefore(frag, tbody.firstChild);
            inserted++;
        });
        return inserted;
    }
    function poll(){
        if(busy)return; busy=true;
        fetch(endpoint+'?since='+sinceId+'&requests_since='+requestsSinceId+'&realtime=1&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}})
        .then(r=>r.ok?r.json():null).then(data=>{
            if(!data)return;
            const isFirstPoll = sinceId===0;
            if(typeof data.latest_id==='number'&&data.latest_id>sinceId)sinceId=data.latest_id;
            updateStats(data.stats);
            text('kpiTotalLaporan',data.total_laporan); text('kpiDisetujuiLaporan',data.total_disetujui); text('kpiDitolakLaporan',data.total_ditolak);
            const inserted = insertNewRows(data.rows);
            // Toast cuma buat laporan yang beneran BARU masuk selagi dashboard
            // terbuka -- bukan pas render pertama kali (isFirstPoll, since=0
            // bakal narik SEMUA laporan yang sudah ada, itu bukan "baru").
            if(!isFirstPoll && inserted>0 && window.siberadShowToast){
                window.siberadShowToast('success', inserted===1 ? 'Ada 1 laporan baru masuk.' : 'Ada '+inserted+' laporan baru masuk.');
            }
            const isFirstRequestsPoll = requestsSinceId===0;
            if(typeof data.requests_latest_id==='number'&&data.requests_latest_id>requestsSinceId)requestsSinceId=data.requests_latest_id;
            const insertedRequests = insertNewRequests(data.requests_new_html);
            if(!isFirstRequestsPoll && insertedRequests>0 && window.siberadShowToast){
                window.siberadShowToast('success', insertedRequests===1 ? 'Ada permintaan laporan baru dibuat.' : 'Ada '+insertedRequests+' permintaan laporan baru dibuat.');
            }
        })
        .catch(()=>{}).finally(()=>{busy=false;});
    }
    function schedule(){ clearTimeout(timer); timer=setTimeout(()=>{poll();schedule();},2000); }
    function start(){ setTimeout(()=>{poll();schedule();},150); document.addEventListener('visibilitychange',()=>{if(!document.hidden){poll();schedule();}}); window.addEventListener('focus',()=>{poll();schedule();}); }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',start,{once:true}); else start();
})();
</script>
