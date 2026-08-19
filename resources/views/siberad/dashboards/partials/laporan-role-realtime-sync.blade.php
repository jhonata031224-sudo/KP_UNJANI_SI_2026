<script>
(function(){
    const endpoint='{{ route('laporan.log-aktivitas.realtime') }}';
    const requestEndpoint='{{ route('permintaan-laporan.realtime') }}';
    let busy=false;
    let timer=null;

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

    function replaceBody(selector,html){
        const body=document.querySelector(selector);if(!body||typeof html!=='string')return;
        const next=document.createElement('tbody');next.innerHTML=html;body.replaceWith(next);
    }

    function syncReports(data){
        if(data.sent_html!==undefined)replaceBody('#riwayat .dtbl tbody',data.sent_html);
        if(data.incoming_html!==undefined)replaceBody('#masuk .dtbl tbody',data.incoming_html);
        const stats=data.role_stats||{};
        stat('Laporan Masuk',stats.masuk);stat('Disetujui',stats.disetujui);stat('Ditolak',stats.ditolak);stat('Terlambat',stats.terlambat);stat('Dibatalkan',stats.dibatalkan);syncChart(stats);
    }

    function syncRequestList(){
        fetch(requestEndpoint+'?since=0&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r=>r.ok?r.json():null).then(data=>{
                if(!data)return;
                const list=document.querySelector('#permintaan-laporan .deadline-sender-list');if(!list||typeof data.items_html!=='string')return;
                const incoming=document.createElement('div');incoming.innerHTML=data.items_html;

                // permintaan-laporan-realtime-items.blade.php merender per
                // permintaan: <article data-realtime-permintaan-id="X">
                // diikuti (opsional) modal Lihat Detail / Update Progres
                // sebagai <div> terpisah (sibling, bukan child). Modal-modal
                // itu TIDAK punya data-realtime-permintaan-id, jadi harus
                // dikelompokkan manual per artikel sebelum disisipkan --
                // kalau tidak, modal ikut hilang saat refresh dan tombol
                // Lihat Detail/Update Progres jadi tidak berfungsi.
                const groups=new Map();
                let currentId=null;
                [...incoming.children].forEach(function(node){
                    if(node.hasAttribute && node.hasAttribute('data-realtime-permintaan-id')){
                        currentId=String(node.dataset.realtimePermintaanId||'');
                        groups.set(currentId,{article:node,modals:[]});
                    }else if(currentId && node.classList && (node.classList.contains('request-detail-modal')||node.classList.contains('progress-update-modal'))){
                        groups.get(currentId).modals.push(node);
                    }
                });

                function placeModals(id,modals){
                    document.querySelectorAll('.request-detail-modal[id$="-'+id+'"],.progress-update-modal[id$="-'+id+'"]').forEach(function(old){old.remove();});
                    modals.forEach(function(m){document.body.appendChild(m);});
                }

                const existing=[...list.querySelectorAll('[data-realtime-permintaan-id]')];const seen=new Set();
                existing.forEach(function(item){
                    const id=String(item.dataset.realtimePermintaanId||'');
                    const group=groups.get(id);
                    if(group){item.replaceWith(group.article);placeModals(id,group.modals);seen.add(id);}
                    else if(id){placeModals(id,[]);item.remove();}
                });
                [...groups.entries()].reverse().forEach(function(entry){
                    const id=entry[0],group=entry[1];
                    if(!id||seen.has(id)||list.querySelector('[data-realtime-permintaan-id="'+id+'"]'))return;
                    list.insertBefore(group.article,list.firstChild);
                    placeModals(id,group.modals);
                });
            }).catch(function(){});
    }

    function poll(){
        if(busy)return;busy=true;
        fetch(endpoint+'?reports=1&requests=0&since=0&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}})
            .then(r=>r.ok?r.json():null).then(data=>{if(data)syncReports(data);}).catch(function(){}).finally(function(){busy=false;});
        syncRequestList();
    }

    function start(){
        if(!document.getElementById('riwayat')&&!document.getElementById('masuk'))return;
        poll();timer=window.setInterval(poll,2500);
        document.addEventListener('visibilitychange',function(){if(!document.hidden)poll();});window.addEventListener('focus',poll);
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
</script>
