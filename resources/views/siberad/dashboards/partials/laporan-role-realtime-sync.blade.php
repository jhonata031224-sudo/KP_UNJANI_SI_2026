<style>
/* Baris tabel yang disisipkan/diperbarui live lewat syncBody() -- fade+slide
   halus buat baris BARU, kedip warna gold sebentar buat baris yang cuma
   BERUBAH (mis. status laporan), senada sama animasi .tab-panel.active
   (fadeIn .25s) yang sudah dipakai di seluruh dashboard. */
@keyframes siberadRowIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
@keyframes siberadRowUpdate{0%{background:var(--gold-dim)}100%{background:transparent}}
.siberad-row-in{animation:siberadRowIn .35s ease}
.siberad-row-updated{animation:siberadRowUpdate 1.2s ease}
</style>
<script>
(function(){
    const endpoint='{{ route('laporan.log-aktivitas.realtime') }}';
    const requestEndpoint='{{ route('permintaan-laporan.realtime') }}';
    let busy=false;
    let timer=null;
    // Render awal tabel Riwayat/Masuk/Monitoring pakai markup inline di
    // laporan-role.blade.php (bukan partial yang sama persis dengan yang
    // dirender endpoint realtime) -- poll PERTAMA dipakai buat "menyamakan"
    // baseline tanpa animasi, biar beda format HTML kecil (bukan perubahan
    // data beneran) tidak keliru kelihatan kayak semua baris "berubah" &
    // ikut kedip pas dashboard baru dibuka.
    let animateSync=false;

    window.siberadOpenEditProgres=function(btn){
        const form=document.getElementById('kirimLaporanForm');
        const modal=document.getElementById('kirimLaporanModal');
        if(!form||!modal||!btn?.dataset.updateUrl)return;
        form.dataset.mode='edit';
        form.action=btn.dataset.updateUrl;
        let method=form.querySelector('input[name="_method"]');
        if(!method){method=document.createElement('input');method.type='hidden';method.name='_method';form.appendChild(method);}
        method.value='PATCH';
        ['perihal','proyek'].forEach(function(name){const el=form.querySelector('[name="'+name+'"]');if(el)el.readOnly=true;});
        const tujuan=form.querySelector('[name="tujuan_satuan_id"]');if(tujuan){tujuan.value=btn.dataset.tujuanSatuanId||'';tujuan.disabled=true;}
        const set=(name,value)=>{const el=form.querySelector('[name="'+name+'"]');if(el)el.value=value??'';};
        set('perihal',btn.dataset.perihal);set('proyek',btn.dataset.proyek);set('prioritas',btn.dataset.prioritas);set('deskripsi',btn.dataset.deskripsi);set('kendala',btn.dataset.kendala);set('lampiran','');set('progres',btn.dataset.progres||'0');
        const progres=form.querySelector('[name="progres"]');if(progres)progres.min='0';
        const hint=document.getElementById('progresHint');if(hint)hint.textContent='Mengedit checkpoint progres yang sudah dikirim. Perihal, kategori, dan tujuan tidak bisa diubah lewat form ini.';
        const title=document.getElementById('kirimLaporanTitle');if(title)title.textContent='Edit Update Progres';
        const desc=document.getElementById('kirimLaporanDesc');if(desc)desc.textContent='Perbarui data checkpoint progres yang sudah kamu kirim.';
        const submit=document.getElementById('kirimLaporanSubmitBtn');if(submit)submit.textContent='Simpan Perubahan';
        modal.classList.add('open');
    };

    function bindInitialEditButtons(){
        document.querySelectorAll('#riwayat .edit-progres-btn').forEach(function(btn){
            if(btn.getAttribute('onclick'))return;
            if(btn.dataset.editInitBound==='1')return;
            btn.dataset.editInitBound='1';
            btn.addEventListener('click',function(){window.siberadOpenEditProgres(btn);});
        });
    }

    function stat(label,value){
        document.querySelectorAll('#dashboard .stat-card .lbl').forEach(function(el){
            if((el.textContent||'').trim().toLowerCase()!==label.toLowerCase())return;
            const valueEl=el.closest('.stat-card')?.querySelector('.val');
            if(valueEl)valueEl.textContent=String(value ?? 0);
        });
    }

    function syncChart(stats){
        const canvas=document.getElementById('laporanChart');
        if(!canvas||!window.Chart)return;
        const chart=Chart.getChart(canvas);
        if(!chart)return;
        const data=chart.data?.datasets?.[0]?.data;
        if(!data)return;
        data[0]=Number(stats.disetujui||0);data[1]=Number(stats.ditolak||0);data[2]=Number(stats.terlambat||0);data[3]=Number(stats.dibatalkan||0);
        chart.update('none');
    }

    function rowKey(row){
        const cells=Array.from(row.cells||[]).slice(0,5).map(function(cell){return (cell.textContent||'').replace(/\s+/g,' ').trim();}).join('|');
        return [row.dataset.search||'',row.dataset.prioritas||'',cells].join('§');
    }

    function buildRows(html){
        const holder=document.createElement('tbody');
        holder.innerHTML=String(html||'').trim();
        return Array.from(holder.children);
    }

    function syncBody(selector,html){
        const body=document.querySelector(selector);
        if(!body||typeof html!=='string')return;

        const freshRows=buildRows(html);
        const existingRows=Array.from(body.children);
        const existingById=new Map();
        const existingByKey=new Map();
        const used=new Set();

        existingRows.forEach(function(row){
            if(row.matches('tr[data-laporan-id]')){
                const id=String(row.dataset.laporanId||'');
                if(id)existingById.set(id,row);
            }
            if(row.matches('tr[data-search]')){
                const key=rowKey(row);
                if(!existingByKey.has(key))existingByKey.set(key,[]);
                existingByKey.get(key).push(row);
            }
        });

        const ordered=[];
        freshRows.forEach(function(fresh){
            let current=null;
            const id=fresh.dataset?.laporanId ? String(fresh.dataset.laporanId) : '';
            if(id)current=existingById.get(id)||null;

            if(!current && fresh.matches('tr[data-search]')){
                const bucket=existingByKey.get(rowKey(fresh));
                while(bucket?.length){
                    const candidate=bucket.shift();
                    if(!used.has(candidate)){current=candidate;break;}
                }
            }

            if(current){
                used.add(current);
                if(current.outerHTML!==fresh.outerHTML){
                    if(animateSync)fresh.classList.add('siberad-row-updated');
                    current.replaceWith(fresh);
                    current=fresh;
                }
                ordered.push(current);
            }else{
                if(animateSync)fresh.classList.add('siberad-row-in');
                ordered.push(fresh);
            }
        });

        existingRows.forEach(function(row){
            if(!used.has(row) && !ordered.includes(row))row.remove();
        });

        ordered.forEach(function(row,index){
            const target=body.children[index];
            if(target!==row)body.insertBefore(row,target||null);
        });
    }

    function syncReports(data){
        if(data.sent_html!==undefined)syncBody('#riwayat .dtbl tbody',data.sent_html);
        if(data.incoming_html!==undefined)syncBody('#masuk .dtbl tbody',data.incoming_html);
        if(data.monitoring_html)syncBody('#monitoring .dtbl tbody',data.monitoring_html);
        bindInitialEditButtons();
        const stats=data.role_stats||{};
        stat('Laporan Masuk',stats.masuk);stat('Disetujui',stats.disetujui);stat('Ditolak',stats.ditolak);stat('Terlambat',stats.terlambat);stat('Dibatalkan',stats.dibatalkan);syncChart(stats);
        animateSync=true;
    }

    // Item BARU (belum pernah tampil) sengaja BUKAN tugas fungsi ini --
    // partials/permintaan-laporan-realtime.blade.php yang polling & nge-insert
    // item baru itu (sekalian nge-toast "Permintaan laporan baru masuk.").
    // Kalau di sini juga ikut nge-insert item baru, dua poller yang jalan
    // independen (2500ms vs 3000ms) jadi rebutan siapa duluan yang nganggap
    // suatu ID "baru" -- akibatnya toast itu jadi tidak bisa diandalkan
    // (kadang kepicu, kadang enggak, tergantung timing). Fungsi ini HANYA
    // menyegarkan (update konten) & menghapus (kalau sudah tidak lagi masuk
    // hasil query aktif, misal permintaannya selesai/dibatalkan) item yang
    // SUDAH ada di DOM.
    function syncRequestList(){
        fetch(requestEndpoint+'?since=0&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r=>r.ok?r.json():null).then(data=>{
                if(!data)return;
                const list=document.querySelector('#permintaan-laporan .deadline-sender-list');if(!list||typeof data.items_html!=='string')return;
                const incoming=document.createElement('div');incoming.innerHTML=data.items_html;
                const fresh=[...incoming.children];const freshById=new Map(fresh.map(el=>[String(el.dataset.realtimePermintaanId||''),el]));const existing=[...list.querySelectorAll('[data-realtime-permintaan-id]')];
                existing.forEach(function(item){const id=String(item.dataset.realtimePermintaanId||'');const replacement=freshById.get(id);if(replacement){item.replaceWith(replacement);}else if(id)item.remove();});
                window.siberadBindPermintaanDetailButtons&&window.siberadBindPermintaanDetailButtons();
                window.siberadRebindPermintaanActions&&window.siberadRebindPermintaanActions();
            }).catch(function(){});
    }

    function poll(){
        if(busy)return;busy=true;
        fetch(endpoint+'?reports=1&requests=0&since=0&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}})
            .then(r=>r.ok?r.json():null).then(data=>{if(data)syncReports(data);}).catch(function(){}).finally(function(){busy=false;});
    }

    function start(){
        if(!document.getElementById('riwayat')&&!document.getElementById('masuk'))return;
        bindInitialEditButtons();
        poll();timer=window.setInterval(poll,2500);
        // syncRequestList() sengaja DIPISAH dari siklus poll() 2500ms di atas
        // (dulu dipanggil bareng di situ) -- endpoint yang sama JUGA sudah
        // dipoll independen tiap 3000ms oleh permintaan-laporan-realtime.blade.php
        // (yang nanganin item BARU+toast). Kalau syncRequestList() (yang cuma
        // nanganin update/hapus item LAMA, lihat komentar di atas) ikut nempel
        // di 2500ms, query ke endpoint yang sama jadi 2x lipat buat data yang
        // tumpang-tindih. Interval lebih santai di sini masih cukup responsif
        // buat update status (bukan insert-baru yang butuh terasa instan).
        syncRequestList();window.setInterval(syncRequestList,6000);
        document.addEventListener('visibilitychange',function(){if(!document.hidden)poll();});window.addEventListener('focus',poll);
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
</script>
