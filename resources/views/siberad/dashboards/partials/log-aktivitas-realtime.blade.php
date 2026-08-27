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
    const longPollEndpoint = '{{ route('laporan.permintaan-laporan.long-poll') }}';
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
    // Baris yang SUDAH tampil di tabel Permintaan Laporan (bukan yang baru
    // masuk -- itu tugas insertNewRequests() di atas) perlu ikut ke-refresh
    // pas satuan tujuan kirim update progres/checklist task, soalnya status
    // (pill "Sedang diproses" -> "Menunggu" dst) & dropdown checklist task
    // dihitung penuh di server (banyak cabang kondisi, sengaja TIDAK
    // diduplikasi ke JS). Dipoll terpisah & lebih santai dari poll() 2000ms
    // di atas karena endpoint ini me-render ULANG partial buat SEMUA item
    // aktif (bukan cuma yang baru lewat cursor), jadi lebih berat kalau
    // disatuin ke siklus 2000ms.
    let requestsFullBusy = false;
    function syncExistingRequests(html){
        if(typeof html !== 'string') return;
        const tbody = document.querySelector('#permintaan-laporan .request-table tbody');
        if(!tbody) return;
        const temp = document.createElement('tbody');
        temp.innerHTML = html;
        const freshRows = Array.prototype.slice.call(temp.children);
        const freshById = new Map();
        for(let i = 0; i < freshRows.length; i += 2){
            const main = freshRows[i], task = freshRows[i + 1];
            if(!main || !task) continue;
            const id = main.getAttribute('data-permintaan-id');
            if(id) freshById.set(id, [main, task]);
        }
        Array.prototype.slice.call(tbody.querySelectorAll(':scope > tr.request-row[data-permintaan-id]')).forEach(function(currentMain){
            const currentTask = currentMain.nextElementSibling;
            if(!currentTask || !currentTask.classList.contains('request-task-row')) return;
            const pair = freshById.get(currentMain.getAttribute('data-permintaan-id'));
            // Item gak ketemu di snapshot (mis. sudah diarsipkan) -- dibiarkan,
            // penghapusannya jadi tanggung jawab mekanisme arsip sendiri,
            // biar gak dobel-hapus dari 2 poller berbeda.
            if(!pair) return;
            const [freshMain, freshTask] = pair;
            if(currentMain.outerHTML === freshMain.outerHTML && currentTask.outerHTML === freshTask.outerHTML) return;
            // Pertahankan status buka/tutup dropdown checklist task -- tanpa
            // ini, dropdown yang lagi dibuka user bakal "nutup sendiri" tiap
            // kali sinkronisasi ini jalan.
            if(!currentTask.hasAttribute('hidden')){
                freshTask.removeAttribute('hidden');
                freshMain.classList.add('open');
            }
            freshMain.classList.add('siberad-row-updated');
            currentMain.replaceWith(freshMain);
            currentTask.replaceWith(freshTask);
        });
    }
    function pollExistingRequests(){
        if(requestsFullBusy) return;
        if(!document.querySelector('#permintaan-laporan .request-table tbody')) return;
        requestsFullBusy = true;
        fetch(endpoint+'?reports=0&requests=1&requests_new=0&requests_full=1&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}})
            .then(r=>r.ok?r.json():null)
            .then(data=>{ if(data) syncExistingRequests(data.requests_full_html); })
            .catch(function(){}).finally(function(){requestsFullBusy=false;});
    }
    // Long-polling: request ini DITAHAN server (bukan langsung dibalas)
    // sampai ada perubahan beneran pada permintaan laporan (satuan tujuan
    // kirim progres/checklist task, dst) atau timeout ~20 detik. Begitu
    // balasannya nyampe, langsung tarik data asli (pollExistingRequests)
    // kalau memang ada perubahan, lalu SEKETIKA nembak long-poll berikutnya
    // -- jadi kerasa instan tanpa nebak-nebak interval kayak polling biasa.
    let requestsVersion = '';
    let longPollRunning = false;
    function longPollTick(){
        if(document.hidden){ longPollRunning = false; return; }
        fetch(longPollEndpoint+'?version='+encodeURIComponent(requestsVersion)+'&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r=>r.ok?r.json():null)
            .then(data=>{
                if(!data){ window.setTimeout(longPollTick,3000); return; }
                if(typeof data.version==='string') requestsVersion = data.version;
                if(data.changed) pollExistingRequests();
                longPollTick();
            })
            .catch(function(){ window.setTimeout(longPollTick,3000); });
    }
    function startLongPoll(){
        if(longPollRunning) return;
        if(!document.querySelector('#permintaan-laporan .request-table tbody')) return;
        longPollRunning = true;
        longPollTick();
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
    function start(){
        setTimeout(()=>{poll();schedule();},150);
        // pollExistingRequests() sendiri dipicu instan lewat long-poll
        // (startLongPoll) -- interval 20 detik di sini cuma jaring pengaman
        // kalau koneksi long-poll-nya diam-diam gagal (mis. dibekukan
        // ekstensi browser tertentu), bukan jalur utama.
        setTimeout(()=>{pollExistingRequests();startLongPoll();window.setInterval(()=>{if(!document.hidden)pollExistingRequests();},20000);},600);
        document.addEventListener('visibilitychange',()=>{if(!document.hidden){poll();schedule();pollExistingRequests();startLongPoll();}});
        window.addEventListener('focus',()=>{poll();schedule();pollExistingRequests();});
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',start,{once:true}); else start();
})();
</script>
