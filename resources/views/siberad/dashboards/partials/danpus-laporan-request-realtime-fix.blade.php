<style>
/* Narrow fix: only synchronizes the Danpus request activity timeline. */
.danpus-realtime-flow-fix{position:absolute;z-index:30;width:4px;border-radius:999px;pointer-events:none;background:linear-gradient(180deg,transparent 0%,var(--p-green,#16834b) 16%,var(--p-green,#16834b) 84%,transparent 100%);box-shadow:0 0 9px color-mix(in srgb,var(--p-green,#16834b) 60%,transparent);transform-origin:top center;animation:danpusRealtimeFlowFix .72s cubic-bezier(.2,.82,.2,1) forwards}
.danpus-activity-item.realtime-fix-enter{animation:danpusRealtimeStepFix .72s cubic-bezier(.2,.82,.2,1)}
.danpus-activity-item.realtime-fix-current .danpus-activity-dot{animation:danpusRealtimeDotFix .85s ease-out 1}
@keyframes danpusRealtimeFlowFix{0%{transform:scaleY(0);opacity:.1}14%{opacity:1}100%{transform:scaleY(1);opacity:1}}
@keyframes danpusRealtimeStepFix{0%{transform:translateY(8px);opacity:.55}55%{transform:translateY(-2px);opacity:1}100%{transform:translateY(0);opacity:1}}
@keyframes danpusRealtimeDotFix{0%{transform:scale(.72);box-shadow:0 0 0 0 color-mix(in srgb,var(--p-green,#16834b) 45%,transparent)}55%{transform:scale(1.18);box-shadow:0 0 0 9px transparent}100%{transform:scale(1);box-shadow:none}}
@media(prefers-reduced-motion:reduce){.danpus-realtime-flow-fix,.danpus-activity-item.realtime-fix-enter,.danpus-activity-item.realtime-fix-current .danpus-activity-dot{animation:none!important}}
</style>
<script>
(function(){
    const endpoint='{{ route('laporan.log-aktivitas.realtime') }}';
    let busy=false;

    function findDropdown(requestId){
        const id=String(requestId||'');
        if(!id)return null;
        const direct=[...document.querySelectorAll('.danpus-report-dropdown')].find(el=>String(el.dataset.permintaanId||'')===id);
        if(direct)return direct;
        const nested=[...document.querySelectorAll('[data-permintaan-id]')].find(el=>String(el.dataset.permintaanId||'')===id);
        return nested?.closest('.danpus-report-dropdown')||null;
    }

    function setState(item,kind,label){
        ['is-done','is-current','is-revisi','is-menunggu','is-rejected','is-approved'].forEach(c=>item.classList.remove(c));
        const dot=item.querySelector('.danpus-activity-dot');
        const state=item.querySelector('.danpus-activity-state');
        if(kind==='done')item.classList.add('is-done');
        if(kind==='current')item.classList.add('is-current');
        if(kind==='revisi')item.classList.add('is-revisi');
        if(kind==='menunggu')item.classList.add('is-menunggu');
        if(kind==='rejected')item.classList.add('is-rejected');
        if(kind==='approved')item.classList.add('is-approved');
        if(dot){
            if(kind==='rejected')dot.innerHTML='<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>';
            else dot.innerHTML='<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';
        }
        if(state)state.textContent=label;
    }

    function clearFlow(log){log.querySelectorAll('.danpus-realtime-flow-fix').forEach(el=>el.remove());}

    function animate(log,items,fromIndex,toIndex){
        if(fromIndex<0||fromIndex===toIndex)return;
        const from=items[fromIndex]?.querySelector('.danpus-activity-dot');
        const to=items[toIndex]?.querySelector('.danpus-activity-dot');
        if(!from||!to)return;
        clearFlow(log);
        items.forEach(item=>item.classList.remove('realtime-fix-enter','realtime-fix-current'));
        const target=items[toIndex];
        target?.classList.add('realtime-fix-enter','realtime-fix-current');
        const lr=log.getBoundingClientRect(),fr=from.getBoundingClientRect(),tr=to.getBoundingClientRect();
        const x=(fr.left+fr.width/2)-lr.left-2;
        const start=Math.min(fr.top+fr.height/2,tr.top+tr.height/2)-lr.top;
        const end=Math.max(fr.top+fr.height/2,tr.top+tr.height/2)-lr.top;
        const flow=document.createElement('span');
        flow.className='danpus-realtime-flow-fix';
        flow.style.left=x+'px';
        flow.style.top=start+'px';
        flow.style.height=Math.max(22,end-start)+'px';
        log.appendChild(flow);
        window.setTimeout(()=>{flow.remove();target?.classList.remove('realtime-fix-enter','realtime-fix-current');},900);
    }

    function syncOne(s){
        const dropdown=findDropdown(s.id); if(!dropdown)return;
        const log=dropdown.querySelector('.danpus-activity-log'); if(!log)return;
        /* Do not assume the timeline items are direct children. The current
           markup can contain wrappers, which was why the previous realtime
           watcher could update the status text but leave the timeline dot
           one step behind until a full refresh. */
        const items=[...log.querySelectorAll('.danpus-activity-item')];
        if(items.length<3)return;

        const status=String(s.status||'');
        const latestStatus=String(s.laporan_status||'').toLowerCase();
        const hasFinal=!!s.laporan_id;
        const revisi=latestStatus.includes('revisi')&&!hasFinal;
        const cancelled=status==='Dibatalkan';
        const late=!cancelled&&!!s.terlambat;
        let current=0, finalKind=null, finalText='Menunggu';

        if(status==='Belum dikerjakan')current=0;
        else if(status==='Sedang dikerjakan')current=2;
        else if(status==='Menunggu pemeriksaan')current=3;
        else if(status==='Selesai'){
            current=4;
            if(latestStatus.includes('tolak')){finalKind='rejected';finalText='Selesai · Ditolak';}
            else {finalKind='approved';finalText='Selesai · Disetujui';}
        }else if(cancelled)current=s.ditinjau_at?2:1;
        if(revisi)current=2;
        if(late&&!hasFinal&&status!=='Menunggu pemeriksaan'&&status!=='Selesai')current=s.ditinjau_at?2:1;

        const signature=[status,current,latestStatus,hasFinal?1:0,revisi?1:0,cancelled?1:0,late?1:0].join('|');
        const previousSignature=log.dataset.realtimeFixSignature||'';
        const previousIndex=log.dataset.realtimeFixCurrent===''||log.dataset.realtimeFixCurrent==null?-1:Number(log.dataset.realtimeFixCurrent);
        const shouldAnimate=!!previousSignature&&previousSignature!==signature&&previousIndex!==current;

        items.forEach((item,index)=>{
            const isFinal=index===items.length-1;
            if(cancelled&&index>=current){setState(item,'rejected','Dibatalkan');return;}
            if(late&&index===current){setState(item,'rejected','Terlambat');return;}
            if(finalKind&&isFinal){setState(item,finalKind,finalText);return;}
            if(index<current){setState(item,'done','Selesai');return;}
            if(index===current){
                if(revisi)setState(item,'revisi','Revisi');
                else if(status==='Menunggu pemeriksaan')setState(item,'menunggu','Menunggu');
                else if(status==='Selesai')setState(item,'done','Selesai');
                else setState(item,'current','Sedang diproses');
                return;
            }
            setState(item,'waiting','Menunggu');
        });

        if(s.ditinjau_at){
            const date=items[1]?.querySelector('.danpus-activity-date');
            if(date)date.textContent=s.ditinjau_at;
        }
        if(cancelled&&s.dibatalkan_at){
            const date=items[current]?.querySelector('.danpus-activity-date');
            if(date)date.textContent=s.dibatalkan_at;
        }
        if(shouldAnimate)animate(log,items,previousIndex,current);
        log.dataset.realtimeFixSignature=signature;
        log.dataset.realtimeFixCurrent=String(current);
    }

    function poll(){
        if(busy)return;busy=true;
        fetch(endpoint+'?reports=0&requests=1&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}})
            .then(r=>r.ok?r.json():null)
            .then(data=>{if(data?.request_states)Object.values(data.request_states).forEach(syncOne);})
            .catch(function(){}).finally(function(){busy=false;});
    }

    function start(){
        if(!document.querySelector('.danpus-activity-log'))return;
        poll();
        window.setInterval(poll,1200);
        document.addEventListener('visibilitychange',function(){if(!document.hidden)poll();});
        window.addEventListener('focus',poll);
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
</script>
