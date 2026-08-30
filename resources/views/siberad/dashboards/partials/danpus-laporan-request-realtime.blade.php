<style>
/* Realtime transition is intentionally scoped to the Danpus activity timeline. */
.danpus-activity-log.realtime-state-changing{position:relative}
.danpus-activity-item.realtime-state-enter{animation:danpusActivityStepIn .65s cubic-bezier(.2,.82,.2,1)}
.danpus-activity-item.realtime-state-current .danpus-activity-dot{animation:danpusActivityDotPulse .8s ease-out 2}
.danpus-activity-item.realtime-state-current .danpus-activity-state{animation:danpusActivityStatePulse .65s ease-out}
.danpus-realtime-flow{position:absolute;z-index:20;width:3px;border-radius:999px;pointer-events:none;background:linear-gradient(180deg,transparent 0%,var(--p-green,#16834b) 18%,var(--p-green,#16834b) 82%,transparent 100%);box-shadow:0 0 7px color-mix(in srgb,var(--p-green,#16834b) 55%,transparent);transform-origin:top center;animation:danpusRealtimeFlow .65s cubic-bezier(.2,.8,.2,1) forwards}
@keyframes danpusActivityStepIn{0%{transform:translateX(-10px);opacity:.35}55%{transform:translateX(2px);opacity:1}100%{transform:translateX(0);opacity:1}}
@keyframes danpusActivityDotPulse{0%{transform:scale(.72);box-shadow:0 0 0 0 color-mix(in srgb,var(--p-green,#16834b) 40%,transparent)}55%{transform:scale(1.16);box-shadow:0 0 0 9px transparent}100%{transform:scale(1);box-shadow:none}}
@keyframes danpusActivityStatePulse{0%{opacity:.45;transform:translateY(4px)}100%{opacity:1;transform:translateY(0)}}
@keyframes danpusRealtimeFlow{0%{transform:scaleY(0);opacity:.2}15%{opacity:1}100%{transform:scaleY(1);opacity:1}}
@media(prefers-reduced-motion:reduce){.danpus-activity-item.realtime-state-enter,.danpus-activity-item.realtime-state-current .danpus-activity-dot,.danpus-activity-item.realtime-state-current .danpus-activity-state,.danpus-realtime-flow{animation:none!important}}
</style>
<script>
(function(){
    const endpoint='{{ route('laporan.log-aktivitas.realtime') }}';
    let busy=false;

    // Dulu findDropdown() nge-scan ULANG SELURUH dokumen (2x querySelectorAll)
    // buat TIAP item di request_states -- poller ini paling sering di seluruh
    // sistem (1200ms), jadi makin banyak permintaan yang pernah dibuat Danpus/
    // Wadan, makin berat (O(jumlah item x ukuran DOM) tiap 1.2 detik). Sekarang
    // di-cache SEKALI per siklus poll jadi Map, lookup per item jadi O(1).
    function buildDropdownIndex(){
        const index=new Map();
        document.querySelectorAll('.danpus-report-dropdown').forEach(el=>{
            const id=String(el.dataset.permintaanId||'');
            if(id)index.set(id,el);
        });
        document.querySelectorAll('[data-permintaan-id]').forEach(el=>{
            const id=String(el.dataset.permintaanId||'');
            if(!id||index.has(id))return;
            const dropdown=el.closest('.danpus-report-dropdown');
            if(dropdown)index.set(id,dropdown);
        });
        return index;
    }

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
            dot.innerHTML=rejected
                ?'<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>'
                :'<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';
        }
        if(state)state.textContent=stateText;
    }

    function clearFlow(log){
        log.querySelectorAll('.danpus-realtime-flow').forEach(el=>el.remove());
    }

    function animateTransition(log,items,previousIndex,currentIndex){
        if(previousIndex<0 || previousIndex===currentIndex || !items.length)return;
        const fromIndex=Math.min(previousIndex,currentIndex);
        const toIndex=Math.max(previousIndex,currentIndex);
        const from=items[fromIndex]?.querySelector('.danpus-activity-dot');
        const to=items[toIndex]?.querySelector('.danpus-activity-dot');
        if(!from||!to)return;

        clearFlow(log);
        log.classList.add('realtime-state-changing');
        items.forEach(item=>item.classList.remove('realtime-state-enter','realtime-state-current'));
        const target=items[currentIndex];
        if(target){
            target.classList.add('realtime-state-enter','realtime-state-current');
            window.setTimeout(()=>target.classList.remove('realtime-state-enter','realtime-state-current'),850);
        }

        const logRect=log.getBoundingClientRect();
        const fromRect=from.getBoundingClientRect();
        const toRect=to.getBoundingClientRect();
        const x=((fromRect.left+fromRect.width/2)-logRect.left)-1.5;
        const start=Math.min(fromRect.top+fromRect.height/2,toRect.top+toRect.height/2)-logRect.top;
        const end=Math.max(fromRect.top+fromRect.height/2,toRect.top+toRect.height/2)-logRect.top;
        const flow=document.createElement('span');
        flow.className='danpus-realtime-flow';
        flow.style.left=x+'px';
        flow.style.top=start+'px';
        flow.style.height=Math.max(18,end-start)+'px';
        log.appendChild(flow);
        window.setTimeout(()=>{flow.remove();log.classList.remove('realtime-state-changing');},900);
    }

    function syncOne(s,dropdownIndex){
        const dropdown=dropdownIndex.get(String(s.id||''));
        if(!dropdown)return;
        const log=dropdown.querySelector('.danpus-activity-log');
        if(!log)return;
        // Jangan asumsikan timeline item selalu anak langsung -- markup bisa
        // punya wrapper di antaranya, makanya query pakai descendant biasa
        // (bukan :scope >), biar dot gak pernah ketinggalan satu langkah
        // dari teks status sampai halaman di-refresh manual.
        const items=[...log.querySelectorAll('.danpus-activity-item')];
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
            // Tahap 0 ("Permintaan Terkirim") itu FAKTA yang udah kejadian
            // begitu Pimpinan mengirim permintaan -- selalu langsung "Selesai",
            // bukan tahap yang sedang aktif. Tahap yang beneran masih berjalan
            // adalah tahap 1 ("Permintaan Ditinjau"), sama seperti override
            // late/cancelled di bawah yang sudah benar pakai 1 (bukan 0).
            current=1;
        }else if(status==='Sedang dikerjakan'){
            current=2;
        }else if(status==='Menunggu pemeriksaan'){
            current=3;
        }else if(status==='Selesai'){
            current=4;
            if(latestStatus.includes('tolak')){finalKind='rejected';finalText='Ditolak';}
            else {finalKind='approved';finalText='Disetujui';}
        }else if(cancelled){
            current=s.ditinjau_at?2:1;
        }

        if(revisi){current=2;}
        if(late && !hasFinal && status!=='Menunggu pemeriksaan' && status!=='Selesai'){
            current=s.ditinjau_at?2:1;
        }

        const transitionSignature=[status,current,latestStatus,hasFinal?1:0,revisi?1:0,cancelled?1:0,late?1:0].join('|');
        const previousSignature=log.dataset.realtimeSignature||'';
        const previousIndex=log.dataset.realtimeCurrent===''||log.dataset.realtimeCurrent==null?-1:Number(log.dataset.realtimeCurrent);
        const shouldAnimate=!!previousSignature && previousSignature!==transitionSignature && previousIndex!==current;

        items.forEach(function(item,index){
            const isFinal=index===items.length-1;
            if(cancelled && index>=current){setItemState(item,'rejected','Dibatalkan');return;}
            // Terlambat = terminal (deadline lewat, laporan gak masuk) -> cat
            // merah/silang X-nya diterusin sampai tahap "Laporan Selesai",
            // konsisten sama Dibatalkan (index>=current), bukan cuma 1 tahap.
            if(late && index>=current){setItemState(item,'rejected','Terlambat');return;}
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

        if(shouldAnimate)animateTransition(log,items,previousIndex,current);
        log.dataset.realtimeSignature=transitionSignature;
        log.dataset.realtimeCurrent=String(current);
    }

    function poll(){
        if(busy)return;
        busy=true;
        fetch(endpoint+'?reports=0&requests=1&requests_new=0&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}})
            .then(r=>r.ok?r.json():null)
            .then(data=>{
                if(!data?.request_states)return;
                const dropdownIndex=buildDropdownIndex();
                Object.values(data.request_states).forEach(s=>syncOne(s,dropdownIndex));
            })
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
