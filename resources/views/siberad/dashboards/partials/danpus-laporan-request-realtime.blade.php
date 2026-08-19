<script>
(function(){
    const endpoint='{{ route('laporan.log-aktivitas.realtime') }}';
    let busy=false;
    let timer=null;

    function setItemState(item,kind,stateText){
        ['is-done','is-current','is-revisi','is-menunggu','is-rejected','is-approved','is-waiting'].forEach(c=>item.classList.remove(c));
        const dot=item.querySelector('.danpus-activity-dot');
        const state=item.querySelector('.danpus-activity-state');
        if(kind==='done')item.classList.add('is-done');
        if(kind==='current')item.classList.add('is-current');
        if(kind==='revisi')item.classList.add('is-revisi');
        if(kind==='menunggu')item.classList.add('is-menunggu');
        if(kind==='rejected')item.classList.add('is-rejected');
        if(kind==='approved')item.classList.add('is-approved');
        if(kind==='waiting')item.classList.add('is-waiting');
        if(dot){
            if(kind==='rejected'){
                dot.innerHTML='<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>';
            }else{
                dot.innerHTML='<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';
            }
        }
        if(state)state.textContent=stateText;
    }

    function getActivityCards(dropdown){
        if(!dropdown)return [];
        // Timeline yang dipakai Danpus menggunakan .danpus-activity-card.
        // Versi lama watcher mencari .danpus-activity-item sehingga selalu
        // berhenti pada `items.length < 3` dan tidak pernah mengubah UI.
        return [...dropdown.querySelectorAll('.danpus-activity-card')];
    }

    function findDropdown(id,s){
        const key=String(id||'');
        let dropdown=[...document.querySelectorAll('.danpus-report-dropdown')]
            .find(el=>String(el.dataset.permintaanId||'')===key);
        if(dropdown)return dropdown;

        // Fallback bila data-permintaan-id berada pada log/tr, bukan <details>.
        const source=[...document.querySelectorAll('[data-permintaan-id]')]
            .find(el=>String(el.dataset.permintaanId||'')===key);
        if(source){
            dropdown=source.closest('.danpus-report-dropdown');
            if(dropdown)return dropdown;
        }

        // Fallback terakhir: cocokkan perihal/subject agar perubahan tetap
        // terlihat walaupun markup dropdown belum sempat diberi dataset.
        const subject=String(s?.perihal||'').trim().toLowerCase();
        if(subject){
            dropdown=[...document.querySelectorAll('.danpus-report-dropdown')].find(el=>
                (el.querySelector('.danpus-report-subject')?.textContent||'').trim().toLowerCase().includes(subject)
            );
        }
        return dropdown||null;
    }

    function syncOne(s){
        const id=String(s.id||'');
        if(!id)return;
        const dropdown=findDropdown(id,s);
        if(!dropdown)return;

        const items=getActivityCards(dropdown);
        if(items.length<3)return;

        const status=String(s.status||'');
        const latestStatus=String(s.laporan_status||'').toLowerCase();
        const hasFinal=!!s.laporan_id;
        const revisi=latestStatus.includes('revisi') && !hasFinal;
        const cancelled=status==='Dibatalkan';
        const late=!cancelled && !!s.terlambat;

        // Urutan kartu timeline Danpus:
        // 0 Permintaan Terkirim
        // 1 Permintaan Ditinjau
        // 2 Laporan Dibuat
        // 3 Laporan Diperiksa/diterima (bila template memilikinya)
        // 4 tahap akhir (bila template memilikinya)
        let current=0;
        let finalKind=null;
        let finalText='Menunggu';

        if(status==='Belum dikerjakan'){
            current=0;
        }else if(status==='Sedang dikerjakan'){
            current=2;
        }else if(status==='Menunggu pemeriksaan'){
            current=Math.min(3,items.length-1);
        }else if(status==='Selesai'){
            current=Math.min(4,items.length-1);
            if(latestStatus.includes('tolak')){finalKind='rejected';finalText='Selesai · Ditolak';}
            else {finalKind='approved';finalText='Selesai · Disetujui';}
        }else if(cancelled){
            current=s.dikerjakan_at?2:1;
        }

        if(revisi)current=Math.min(2,items.length-1);
        if(late && !hasFinal && status!=='Menunggu pemeriksaan' && status!=='Selesai'){
            current=s.dikerjakan_at?2:1;
        }

        items.forEach(function(item,index){
            const title=(item.querySelector('.danpus-activity-stage')?.textContent||'').trim().toLowerCase();
            const isReview=title==='permintaan ditinjau';
            const isCreated=title==='laporan dibuat';
            const isFinal=index===items.length-1;

            if(cancelled && index>=current){
                setItemState(item,'rejected','Dibatalkan');
                return;
            }
            if(late && index===current){
                setItemState(item,'rejected','Terlambat');
                return;
            }
            if(finalKind&&isFinal){
                setItemState(item,finalKind,finalText);
                return;
            }
            if(index<current){
                setItemState(item,'done','Selesai');
                return;
            }
            if(index===current){
                if(revisi)setItemState(item,'revisi','Revisi');
                else if(status==='Menunggu pemeriksaan')setItemState(item,'menunggu','Menunggu pemeriksaan');
                else if(status==='Selesai')setItemState(item,'done','Selesai');
                else setItemState(item,'current',isCreated?'Sedang diproses':'Sedang diproses');
                return;
            }
            // Explicitly normalize the two cards affected by confirmation so
            // the visual flow cannot remain stuck on "Permintaan Ditinjau".
            if(status==='Sedang dikerjakan' && isReview)setItemState(item,'done','Selesai');
            else if(status==='Sedang dikerjakan' && isCreated)setItemState(item,'current','Sedang diproses');
            else setItemState(item,'waiting','Menunggu');
        });

        if(s.ditinjau_at){
            const date=items.find(item=>(item.querySelector('.danpus-activity-stage')?.textContent||'').trim().toLowerCase()==='permintaan ditinjau')?.querySelector('.danpus-activity-date');
            if(date)date.textContent=s.ditinjau_at;
        }
        if(s.dikerjakan_at){
            const date=items.find(item=>(item.querySelector('.danpus-activity-stage')?.textContent||'').trim().toLowerCase()==='laporan dibuat')?.querySelector('.danpus-activity-date');
            if(date)date.textContent=s.dikerjakan_at;
        }
    }

    function poll(){
        if(busy)return;
        busy=true;
        fetch(endpoint+'?reports=0&requests=1&_='+Date.now(),{
            credentials:'same-origin',cache:'no-store',
            headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}
        })
        .then(r=>r.ok?r.json():null)
        .then(data=>{
            if(!data?.request_states)return;
            Object.values(data.request_states).forEach(syncOne);
        })
        .catch(function(){}).finally(function(){busy=false;});
    }

    function schedule(){
        clearTimeout(timer);
        timer=window.setTimeout(function(){poll();schedule();},1500);
    }

    function start(){
        poll();
        schedule();
        document.addEventListener('visibilitychange',function(){if(!document.hidden){poll();schedule();}});
        window.addEventListener('focus',function(){poll();schedule();});
    }

    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});
    else start();
})();
</script>
