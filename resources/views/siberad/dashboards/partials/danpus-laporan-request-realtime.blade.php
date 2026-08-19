<script>
(function(){
    const endpoint='{{ route('laporan.log-aktivitas.realtime') }}';
    let busy=false;

    function setItemState(item,kind,stateText){
        ['is-done','is-current','is-revisi','is-menunggu','is-rejected','is-approved'].forEach(c=>item.classList.remove(c));
        const dot=item.querySelector('.danpus-activity-dot');
        const state=item.querySelector('.danpus-activity-state');
        const rejected=kind==='rejected';
        const approved=kind==='approved';
        if(kind==='done')item.classList.add('is-done');
        if(kind==='current')item.classList.add('is-current');
        if(kind==='revisi')item.classList.add('is-revisi');
        if(kind==='menunggu')item.classList.add('is-menunggu');
        if(rejected)item.classList.add('is-rejected');
        if(approved)item.classList.add('is-approved');
        if(dot){
            if(rejected){dot.innerHTML='<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>';}
            else if(kind==='current'||kind==='revisi'||kind==='menunggu'){dot.innerHTML='<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';}
            else {dot.innerHTML='<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';}
        }
        if(state)state.textContent=stateText;
    }

    function syncOne(s){
        const id=String(s.id||'');
        if(!id)return;
        const dropdown=[...document.querySelectorAll('.danpus-report-dropdown')].find(el=>String(el.dataset.permintaanId||'')===id);
        if(!dropdown)return;
        const log=dropdown.querySelector('.danpus-activity-log');
        if(!log)return;
        const items=[...log.querySelectorAll(':scope > .danpus-activity-item')];
        if(items.length<3)return;

        const status=String(s.status||'');
        const latestStatus=String(s.laporan_status||'').toLowerCase();
        const hasFinal=!!s.laporan_id;
        const revisi=latestStatus.includes('revisi') && !hasFinal;
        const cancelled=status==='Dibatalkan';
        const late=!cancelled && !!s.terlambat;
        let current=0;
        let finalKind=null;
        let finalText='Menunggu';

        if(status==='Belum dikerjakan'){
            current=0;
        }else if(status==='Sedang dikerjakan'){
            current=2;
        }else if(status==='Menunggu pemeriksaan'){
            current=3;
        }else if(status==='Selesai'){
            current=4;
            if(latestStatus.includes('tolak')){finalKind='rejected';finalText='Selesai · Ditolak';}
            else {finalKind='approved';finalText='Selesai · Disetujui';}
        }else if(cancelled){
            current=s.dikerjakan_at?2:1;
        }

        if(revisi){current=2;}
        if(late && !hasFinal && status!=='Menunggu pemeriksaan' && status!=='Selesai'){
            current=s.dikerjakan_at?2:1;
        }

        items.forEach(function(item,index){
            const isFinal=index===items.length-1;
            if(cancelled && index>=current){setItemState(item,'rejected','Dibatalkan');return;}
            if(late && index===current){setItemState(item,'rejected','Terlambat');return;}
            if(finalKind&&isFinal){setItemState(item,finalKind,finalText);return;}
            if(index<current){setItemState(item,'done','Selesai');return;}
            if(index===current){
                if(revisi)setItemState(item,'revisi','Revisi');
                else if(status==='Menunggu pemeriksaan')setItemState(item,'menunggu','Menunggu');
                else if(status==='Selesai')setItemState(item,'done','Selesai');
                else setItemState(item,'current','Sedang diproses');
                return;
            }
            setItemState(item,'waiting','Menunggu');
        });

        if(s.ditinjau_at){
            const date=items[1]?.querySelector('.danpus-activity-date');
            if(date)date.textContent=s.ditinjau_at;
        }
        if(cancelled && s.dibatalkan_at){
            const date=items[current]?.querySelector('.danpus-activity-date');
            if(date)date.textContent=s.dibatalkan_at;
        }
    }

    function poll(){
        if(busy)return;
        busy=true;
        fetch(endpoint+'?reports=0&requests=1&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}})
            .then(r=>r.ok?r.json():null)
            .then(data=>{if(data?.request_states)Object.values(data.request_states).forEach(syncOne);})
            .catch(function(){}).finally(function(){busy=false;});
    }

    function start(){
        if(!document.querySelector('.danpus-report-dropdown'))return;
        poll();
        window.setInterval(poll,2000);
        document.addEventListener('visibilitychange',function(){if(!document.hidden)poll();});
        window.addEventListener('focus',poll);
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
</script>
