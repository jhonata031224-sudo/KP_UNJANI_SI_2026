<script>
(function () {
    const endpoint = '{{ route('laporan.log-aktivitas.realtime') }}';
    let busy = false;
    let timer = null;
    let sinceId = 0;

    const text = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value; };
    function allRows(html) {
        const tbody = document.createElement('tbody'); tbody.innerHTML = html || '';
        return [...tbody.children].filter(el => el.matches('tr[data-permintaan-id], tr[data-laporan-id]'));
    }
    function rowToEntry(row) {
        return {
            laporanId: String(row.dataset.laporanId || ''),
            progres: String(row.dataset.progres ?? '').trim(),
            tanggal: row.querySelector('.detail-btn')?.dataset.tanggal || ''
        };
    }
    function groups(html) {
        const map = new Map();
        allRows(html).forEach(row => {
            const key = row.dataset.permintaanId ? `request:${row.dataset.permintaanId}` : `laporan:${row.dataset.laporanId}`;
            if (!map.has(key)) map.set(key, []); map.get(key).push(row);
        });
        return [...map.values()].map(rows => {
            rows.sort((a,b) => Number(a.dataset.laporanId||0)-Number(b.dataset.laporanId||0));
            return { rows, latest: rows[rows.length-1], requestId: rows[0].dataset.permintaanId||'', entries: rows.filter(r=>r.dataset.laporanId).map(rowToEntry) };
        });
    }
    function findDetails(requestId, laporanId, subject) {
        const dropdowns = [...document.querySelectorAll('.danpus-report-dropdown')];
        let found = dropdowns.find(d => requestId && d.querySelector(`tr[data-permintaan-id="${requestId}"]`));
        if (found) return found;
        found = dropdowns.find(d => laporanId && d.querySelector(`tr[data-laporan-id="${laporanId}"]`));
        if (found) return found;
        if (subject) {
            const wanted = subject.trim();
            found = dropdowns.find(d => { const s=d.querySelector('.danpus-report-subject, summary'); return s && s.textContent.trim().includes(wanted); });
        }
        return found || null;
    }
    function findLaporanDibuatCard(details) {
        if (!details) return null;
        return [...details.querySelectorAll('.danpus-activity-card')].find(card => {
            const title=card.querySelector('.danpus-activity-stage');
            return title && title.textContent.trim().toLowerCase()==='laporan dibuat';
        }) || null;
    }
    function removeExternalHistories() {
        document.querySelectorAll('.danpus-inline-progress-history, .danpus-progress-history, .danpus-progress-branch').forEach(el => {
            if (!el.closest('.danpus-activity-card')) el.remove();
        });
    }
    function readExistingHistory(card) {
        const history=card?.querySelector(':scope > .danpus-inline-progress-history');
        return history ? [...history.querySelectorAll('[data-laporan-id]')].map(el=>({laporanId:el.dataset.laporanId,progres:el.dataset.progres,tanggal:el.dataset.tanggal||''})) : [];
    }
    function openProgressDetail(details, laporanId) {
        if (!details) return;
        const nodes = [...details.querySelectorAll('.danpus-progress-node')];
        const node = nodes.reverse().find(n => n.dataset.laporanId && n.dataset.laporanId===String(laporanId));
        const button = node?.querySelector('.detail-btn');
        if (button) button.click();
    }
    function createProgressItem(entry, latestEntry, oldIds, details) {
        const item=document.createElement('div');
        const isLatest=entry.laporanId===latestEntry.laporanId;
        item.className='danpus-snake-progress-item'+(isLatest?' latest':'');
        item.dataset.laporanId=entry.laporanId;
        item.dataset.progres=entry.progres;
        item.dataset.tanggal=entry.tanggal;
        const dot=document.createElement('span'); dot.className='danpus-snake-progress-dot';
        const body=document.createElement('span'); body.className='danpus-snake-progress-body';
        const valueText=document.createElement('strong'); valueText.textContent=`${entry.progres}%`;
        const label=document.createElement('small'); label.textContent=isLatest?'Terbaru':'Tercatat';
        body.append(valueText,label);
        if(entry.tanggal){ const dateEl=document.createElement('small'); dateEl.className='danpus-snake-progress-date'; dateEl.textContent=entry.tanggal; body.appendChild(dateEl); }
        const detail=document.createElement('button'); detail.type='button'; detail.className='danpus-snake-detail'; detail.textContent='Detail';
        detail.addEventListener('click',event=>{ event.preventDefault(); event.stopPropagation(); openProgressDetail(details,entry.laporanId); });
        item.append(dot,body,detail);
        if(!oldIds.has(entry.laporanId)) item.classList.add('is-progress-added');
        return item;
    }
    function snakeColumns() {
        if (window.innerWidth <= 430) return 2;
        if (window.innerWidth <= 700) return 3;
        if (window.innerWidth <= 1000) return 5;
        return 7;
    }
    function renderHistory(details, incomingEntries) {
        const card=findLaporanDibuatCard(details); if(!card) return;
        const old=readExistingHistory(card);
        const merged=new Map();
        old.concat(incomingEntries||[]).forEach(e=>{ if(e.laporanId) merged.set(e.laporanId,e); });
        const entries=[...merged.values()].sort((a,b)=>Number(a.laporanId)-Number(b.laporanId));
        if(!entries.length) return;
        const oldIds=new Set(old.map(e=>e.laporanId)); const latestEntry=entries[entries.length-1];
        const history=card.querySelector(':scope > .danpus-inline-progress-history'); if(history) history.remove();
        const next=document.createElement('div'); next.className='danpus-inline-progress-history realtime-history'; next.dataset.danpusInlineProgressHistory='1';
        const label=document.createElement('div'); label.className='danpus-inline-progress-label';
        label.innerHTML=`<span><b>Riwayat progres</b><em>Realtime</em></span><span class="danpus-inline-progress-count">${entries.length} update</span>`;
        next.appendChild(label);
        const board=document.createElement('div'); board.className='danpus-snake-board';
        const columns=snakeColumns();
        for(let start=0,rowIndex=0;start<entries.length;start+=columns,rowIndex++){
            const row=document.createElement('div'); row.className='danpus-snake-row '+(rowIndex%2?'reverse':'forward');
            entries.slice(start,start+columns).forEach(entry=>row.appendChild(createProgressItem(entry,latestEntry,oldIds,details)));
            board.appendChild(row);
            if(start+columns<entries.length){ const turn=document.createElement('div'); turn.className='danpus-snake-turn '+(rowIndex%2?'turn-left':'turn-right'); board.appendChild(turn); }
        }
        next.appendChild(board); card.appendChild(next);
        const added=[...next.querySelectorAll('.is-progress-added')]; if(added.length) requestAnimationFrame(()=>added.forEach(el=>el.classList.add('is-progress-visible')));
    }
    function applyGroup(group){
        const latest=group.latest; const details=findDetails(group.requestId,latest.dataset.laporanId,latest.dataset.perihal);
        if(!details)return;
        renderHistory(details,group.entries); removeExternalHistories();
    }
    function upsert(rowsBySatuan){ Object.entries(rowsBySatuan||{}).forEach(([,html])=>{ groups(html).forEach(applyGroup); }); }
    function updateStats(stats){ Object.entries(stats||{}).forEach(([id,s])=>{ text(`satlakTotalOverview-${id}`,s.total); text(`satlakTotalMonitoring-${id}`,s.total); text(`satlakDiterima-${id}`,s.diterima); text(`satlakDitolak-${id}`,s.ditolak); text(`satlakMenunggu-${id}`,s.menunggu); }); }
    function poll(){
        if(busy)return; busy=true;
        fetch(endpoint+'?since='+sinceId+'&realtime=1&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}})
        .then(r=>r.ok?r.json():null).then(data=>{ if(!data)return; if(typeof data.latest_id==='number'&&data.latest_id>sinceId)sinceId=data.latest_id; updateStats(data.stats); text('kpiTotalLaporan',data.total_laporan); text('kpiDisetujuiLaporan',data.total_disetujui); text('kpiDitolakLaporan',data.total_ditolak); upsert(data.rows||{}); removeExternalHistories(); })
        .catch(()=>{}).finally(()=>{busy=false;});
    }
    function schedule(){ clearTimeout(timer); timer=setTimeout(()=>{poll();schedule();},2000); }
    function start(){ removeExternalHistories(); setTimeout(()=>{poll();schedule();},150); document.addEventListener('visibilitychange',()=>{if(!document.hidden){poll();schedule();}}); window.addEventListener('focus',()=>{poll();schedule();}); }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',start,{once:true}); else start();
})();
</script>
<style>
.danpus-progress-branch,.danpus-progress-history{display:none!important}
.danpus-report-dropdown > .danpus-inline-progress-history,.danpus-report-content > .danpus-inline-progress-history{display:none!important}
.danpus-inline-progress-history{position:relative;margin:15px 0 3px;padding:16px 16px 20px;border:1px solid color-mix(in srgb,var(--p-accent) 20%,var(--p-border));border-radius:16px;background:linear-gradient(145deg,color-mix(in srgb,var(--p-surface) 97%,var(--p-accent)),var(--p-surface-2));overflow:visible}
.danpus-inline-progress-label{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;color:var(--p-muted);font-size:11px;font-weight:800}.danpus-inline-progress-label span:first-child{display:flex;align-items:center;gap:8px}.danpus-inline-progress-label span:first-child::before{content:"";width:8px;height:8px;border-radius:50%;background:var(--p-green);box-shadow:0 0 0 4px color-mix(in srgb,var(--p-green) 12%,transparent)}
.danpus-inline-progress-label em{font-style:normal;font-size:9px;padding:3px 8px;border-radius:999px;color:var(--p-green);background:color-mix(in srgb,var(--p-green) 9%,var(--p-surface));border:1px solid color-mix(in srgb,var(--p-green) 22%,var(--p-border))}.danpus-inline-progress-count{opacity:.7}
.danpus-snake-board{position:relative;display:flex;flex-direction:column;gap:0;width:100%;padding:2px 4px 4px;box-sizing:border-box}
.danpus-snake-row{position:relative;display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:10px;min-height:118px;align-items:start}.danpus-snake-row.reverse{direction:rtl}.danpus-snake-row.reverse .danpus-snake-progress-item{direction:ltr}
.danpus-snake-row::before{content:"";position:absolute;left:4%;right:4%;top:47px;height:0;border-top:2px dotted color-mix(in srgb,var(--p-green) 45%,var(--p-border));z-index:0}
.danpus-snake-progress-item{position:relative;z-index:2;min-width:0;min-height:96px;padding:12px 8px 10px;box-sizing:border-box;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;gap:6px;border:1px solid color-mix(in srgb,var(--p-muted) 20%,var(--p-border));border-radius:15px;background:var(--p-surface);color:var(--p-text);box-shadow:0 4px 12px rgba(15,23,42,.06);text-align:center;transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease}.danpus-snake-progress-item:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(15,23,42,.09)}
.danpus-snake-progress-item.latest{border-color:#78a9ff;box-shadow:0 0 0 3px rgba(59,130,246,.08),0 7px 18px rgba(59,130,246,.10)}
.danpus-snake-progress-dot{width:9px;height:9px;flex:0 0 auto;border-radius:50%;background:var(--p-green);box-shadow:0 0 0 4px color-mix(in srgb,var(--p-green) 10%,transparent)}.danpus-snake-progress-item.latest .danpus-snake-progress-dot{background:#168f5a;box-shadow:0 0 0 4px rgba(22,143,90,.10)}
.danpus-snake-progress-body{display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:0;line-height:1.1;text-align:center}.danpus-snake-progress-body strong{font-family:var(--mono);font-size:13px;font-weight:900;white-space:nowrap}.danpus-snake-progress-body small{margin-top:4px;color:var(--p-muted);font-size:9px;line-height:1;white-space:nowrap}.danpus-snake-progress-body small.danpus-snake-progress-date{margin-top:3px;font-size:8.5px;line-height:1.2;white-space:normal;opacity:.85}
.danpus-snake-detail{display:inline-flex;align-items:center;justify-content:center;min-width:52px;height:28px;padding:0 11px;border:1px solid var(--p-border);border-radius:9px;background:var(--p-surface);color:var(--p-text);font:700 11px/1 var(--font-sans,system-ui,sans-serif);cursor:pointer;transition:background .18s ease,border-color .18s ease,transform .18s ease}.danpus-snake-detail:hover{background:var(--p-surface-2);border-color:var(--p-accent);transform:translateY(-1px)}
.danpus-snake-turn{height:22px;width:22px;box-sizing:border-box;z-index:1;border-bottom:2px dotted color-mix(in srgb,var(--p-green) 45%,var(--p-border));margin-top:-2px;margin-bottom:-2px}.danpus-snake-turn.turn-right{align-self:flex-end;border-right:2px dotted color-mix(in srgb,var(--p-green) 45%,var(--p-border));border-radius:0 0 11px 0}.danpus-snake-turn.turn-left{align-self:flex-start;border-left:2px dotted color-mix(in srgb,var(--p-green) 45%,var(--p-border));border-radius:0 0 0 11px}
.danpus-snake-progress-item.is-progress-added{opacity:0;transform:translateY(13px) scale(.78)}.danpus-snake-progress-item.is-progress-added.is-progress-visible{animation:danpusSnakeAdd .65s cubic-bezier(.2,.85,.2,1) forwards}.danpus-inline-progress-history.realtime-history{animation:danpusHistoryFlash .65s ease}
@keyframes danpusSnakeAdd{0%{opacity:0;transform:translateY(13px) scale(.78);filter:blur(2px)}55%{opacity:1;transform:translateY(-2px) scale(1.04);filter:blur(0)}100%{opacity:1;transform:translateY(0) scale(1)}}@keyframes danpusHistoryFlash{0%{box-shadow:0 0 0 0 rgba(59,130,246,.16)}100%{box-shadow:0 0 0 14px transparent}}
@media(max-width:1000px){.danpus-snake-row{grid-template-columns:repeat(5,minmax(0,1fr))}}@media(max-width:700px){.danpus-snake-row{grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.danpus-snake-progress-item{min-height:94px;padding:10px 5px;border-radius:12px}}@media(max-width:430px){.danpus-snake-row{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(prefers-reduced-motion:reduce){.danpus-snake-progress-item.is-progress-added.is-progress-visible,.danpus-inline-progress-history.realtime-history{animation:none!important}}
</style>