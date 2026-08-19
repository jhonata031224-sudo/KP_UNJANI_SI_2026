<script>
(function(){
    const endpoint='{{ route('laporan.log-aktivitas.realtime') }}';
    const requestEndpoint='{{ route('permintaan-laporan.realtime') }}';
    let busy=false;
    let timer=null;

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
        data[0]=Number(stats.disetujui||0);
        data[1]=Number(stats.ditolak||0);
        data[2]=Number(stats.terlambat||0);
        data[3]=Number(stats.dibatalkan||0);
        chart.update('none');
    }

    function replaceBody(selector,html){
        const body=document.querySelector(selector);
        if(!body||typeof html!=='string')return;
        const next=document.createElement('tbody');
        next.innerHTML=html;
        body.replaceWith(next);
    }

    function syncReports(data){
        if(data.sent_html!==undefined)replaceBody('#riwayat .dtbl tbody',data.sent_html);
        if(data.incoming_html!==undefined)replaceBody('#masuk .dtbl tbody',data.incoming_html);
        const stats=data.role_stats||{};
        stat('Laporan Masuk',stats.masuk);
        stat('Disetujui',stats.disetujui);
        stat('Ditolak',stats.ditolak);
        stat('Terlambat',stats.terlambat);
        stat('Dibatalkan',stats.dibatalkan);
        syncChart(stats);
    }

    function syncRequestList(){
        fetch(requestEndpoint+'?since=0&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r=>r.ok?r.json():null)
            .then(data=>{
                if(!data)return;
                const list=document.querySelector('#permintaan-laporan .deadline-sender-list');
                if(!list||typeof data.items_html!=='string')return;
                const incoming=document.createElement('div');
                incoming.innerHTML=data.items_html;
                const fresh=[...incoming.children];
                const freshById=new Map(fresh.map(el=>[String(el.dataset.realtimePermintaanId||''),el]));
                const existing=[...list.querySelectorAll('[data-realtime-permintaan-id]')];
                const seen=new Set();
                existing.forEach(function(item){
                    const id=String(item.dataset.realtimePermintaanId||'');
                    const replacement=freshById.get(id);
                    if(replacement){item.replaceWith(replacement);seen.add(id);}
                    else if(id){item.remove();}
                });
                fresh.slice().reverse().forEach(function(item){
                    const id=String(item.dataset.realtimePermintaanId||'');
                    if(!id||seen.has(id)||list.querySelector('[data-realtime-permintaan-id="'+id+'"]'))return;
                    list.insertBefore(item,list.firstChild);
                });
            }).catch(function(){});
    }

    function poll(){
        if(busy)return;
        busy=true;
        fetch(endpoint+'?reports=1&requests=0&since=0&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}})
            .then(r=>r.ok?r.json():null)
            .then(data=>{if(data)syncReports(data);})
            .catch(function(){}).finally(function(){busy=false;});
        syncRequestList();
    }

    function start(){
        if(!document.getElementById('riwayat')&&!document.getElementById('masuk'))return;
        poll();
        timer=window.setInterval(poll,2500);
        document.addEventListener('visibilitychange',function(){if(!document.hidden)poll();});
        window.addEventListener('focus',poll);
    }

    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
</script>
