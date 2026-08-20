<script>
(function () {
    const endpoint = '{{ route('laporan.log-aktivitas.realtime') }}';
    let busy = false;
    let timer = null;
    let sinceId = 0;
    function markRowBoundaries(board) {
        const items = [...board.querySelectorAll('.danpus-snake-progress-item')];
        items.forEach((item, i) => {
            const prev = items[i - 1], next = items[i + 1];
            item.classList.toggle('is-row-start', !prev || item.offsetTop > prev.offsetTop + 1);
            item.classList.toggle('is-row-end', !!next && next.offsetTop > item.offsetTop + 1);
        });
    }
    const SVG_NS = 'http://www.w3.org/2000/svg';
    const DOT_SIDE_GAP = 10;
    function measureTextRight(el, fallbackRect) {
        if (!el || !el.textContent.trim()) return fallbackRect.right;
        const range = document.createRange();
        range.selectNodeContents(el);
        const r = range.getBoundingClientRect();
        return r.width ? Math.max(fallbackRect.right, r.right) : fallbackRect.right;
    }
    function drawRowConnectors(board) {
        let svg = board.querySelector(':scope > .danpus-snake-connectors');
        if (!svg) {
            svg = document.createElementNS(SVG_NS, 'svg');
            svg.setAttribute('class', 'danpus-snake-connectors');
            board.insertBefore(svg, board.firstChild);
        }
        while (svg.firstChild) svg.removeChild(svg.firstChild);
        const items = [...board.querySelectorAll('.danpus-snake-progress-item')];
        const boardRect = board.getBoundingClientRect();
        svg.setAttribute('width', boardRect.width);
        svg.setAttribute('height', boardRect.height);
        svg.setAttribute('viewBox', `0 0 ${boardRect.width} ${boardRect.height}`);
        items.forEach((item, i) => {
            if (!item.classList.contains('is-row-end')) return;
            const next = items[i + 1]; if (!next) return;
            const fromDot = item.querySelector('.danpus-snake-progress-dot');
            const toDot = next.querySelector('.danpus-snake-progress-dot');
            if (!fromDot || !toDot) return;
            const a = fromDot.getBoundingClientRect(), b = toDot.getBoundingClientRect();
            if (!a.width || !b.width) return;
            const itemRect = item.getBoundingClientRect();
            const nextItemRect = next.getBoundingClientRect();
            // Jalur cuma lewat CELAH KOSONG ASLI antar baris (bawah baris atas
            // s/d atas baris bawah -- ruang gap:22px yang memang kosong, bukan
            // area di atas baris atas), keluar/masuk dari SAMPING tiap dot
            // (bukan atas/bawah), jadi gak pernah nyentuh dot lain di barisnya
            // sendiri ataupun tombol Detail siapapun (yang selalu nempel di
            // atas dot, di luar jalur ini sama sekali).
            // Turunnya digeser secukupnya ke kanan -- pas ngelewatin lebar ASLI
            // teks tanggal (diukur langsung dari node teksnya, bukan dari lebar
            // kolom grid) -- supaya gak nabrak teks tanggal yang lebih lebar
            // dari dot itu sendiri, tanpa perlu lompat jauh ke tepi board (yang
            // bikin jarak kanan jomplang dibanding jarak kiri).
            const dateEl = item.querySelector('.danpus-snake-progress-date');
            const clearRight = measureTextRight(dateEl, a);
            const startX = a.right - boardRect.left, startY = a.top + a.height / 2 - boardRect.top;
            const departX = clearRight - boardRect.left + DOT_SIDE_GAP;
            const gapTop = itemRect.bottom - boardRect.top;
            const gapBottom = nextItemRect.top - boardRect.top;
            const gapY = gapBottom > gapTop ? (gapTop + gapBottom) / 2 : gapTop + 4;
            const approachX = b.left - boardRect.left - DOT_SIDE_GAP;
            const endY = b.top + b.height / 2 - boardRect.top;
            const endX = b.left - boardRect.left;
            const pending = item.classList.contains('is-pending');
            const path = document.createElementNS(SVG_NS, 'path');
            path.setAttribute('d', `M ${startX} ${startY} L ${departX} ${startY} L ${departX} ${gapY} L ${approachX} ${gapY} L ${approachX} ${endY} L ${endX} ${endY}`);
            path.setAttribute('class', 'danpus-snake-hook' + (pending ? ' is-pending' : ''));
            svg.appendChild(path);
        });
    }
    function updateRowLayout(board) { markRowBoundaries(board); drawRowConnectors(board); }
    const rowObserver = ('ResizeObserver' in window) ? new ResizeObserver(entries => {
        entries.forEach(entry => updateRowLayout(entry.target));
    }) : null;

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
    function dedupeProgressEntries(entries) {
        const byProgress = new Map();
        (entries || []).forEach(entry => {
            if (!entry || !entry.laporanId) return;
            const progress = String(entry.progres ?? '').trim();
            const key = progress === '' ? `laporan:${entry.laporanId}` : `progres:${progress}`;
            const current = byProgress.get(key);
            if (!current || Number(entry.laporanId) > Number(current.laporanId)) byProgress.set(key, entry);
        });
        return [...byProgress.values()].sort((a, b) => Number(a.laporanId) - Number(b.laporanId));
    }
    function groups(html) {
        const map = new Map();
        allRows(html).forEach(row => {
            const key = row.dataset.permintaanId ? `request:${row.dataset.permintaanId}` : `laporan:${row.dataset.laporanId}`;
            if (!map.has(key)) map.set(key, []); map.get(key).push(row);
        });
        return [...map.values()].map(rows => {
            rows.sort((a,b) => Number(a.dataset.laporanId||0)-Number(b.dataset.laporanId||0));
            return { rows, latest: rows[rows.length-1], requestId: rows[0].dataset.permintaanId||'', entries: dedupeProgressEntries(rows.filter(r=>r.dataset.laporanId).map(rowToEntry)) };
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
        return history ? dedupeProgressEntries([...history.querySelectorAll('[data-laporan-id]')].map(el=>({laporanId:el.dataset.laporanId,progres:el.dataset.progres,tanggal:el.dataset.tanggal||''}))) : [];
    }
    function openProgressDetail(details, laporanId) {
        if (!details || !laporanId) return;
        const id = String(laporanId);
        // Jangan mencari tombol Detail di card riwayat itu sendiri karena akan
        // memicu click handler yang sama berulang-ulang. Cari tombol Detail
        // asli pada baris laporan sumber yang memiliki ID checkpoint tersebut.
        const rows = [...details.querySelectorAll('tr[data-laporan-id]')];
        const row = rows.reverse().find(r => String(r.dataset.laporanId || '') === id);
        const button = row?.querySelector('.detail-btn') || null;
        if (button) {
            button.click();
            return;
        }
        // Fallback untuk markup detail lama yang tidak membungkus tombol di TR.
        const candidates = [...details.querySelectorAll('[data-laporan-id]')]
            .filter(el => !el.closest('.danpus-snake-progress-item') && String(el.dataset.laporanId || '') === id);
        for (const candidate of candidates) {
            const fallback = candidate.querySelector('.detail-btn');
            if (fallback) { fallback.click(); return; }
        }
    }
    function createProgressItem(entry, latestEntry, oldIds, details) {
        const item=document.createElement('div');
        const isLatest=entry.laporanId===latestEntry.laporanId;
        item.className='danpus-snake-progress-item'+(isLatest?' latest':'');
        item.dataset.laporanId=entry.laporanId;
        item.dataset.progres=entry.progres;
        item.dataset.tanggal=entry.tanggal;
        const detail=document.createElement('button'); detail.type='button'; detail.className='danpus-snake-detail'; detail.textContent='Detail';
        detail.addEventListener('click',event=>{ event.preventDefault(); event.stopPropagation(); openProgressDetail(details,entry.laporanId); });
        const dot=document.createElement('span'); dot.className='danpus-snake-progress-dot'; dot.textContent=`${entry.progres}%`;
        const body=document.createElement('span'); body.className='danpus-snake-progress-body';
        if(entry.tanggal){ const dateEl=document.createElement('small'); dateEl.className='danpus-snake-progress-date'; dateEl.textContent=entry.tanggal; body.appendChild(dateEl); }
        item.append(detail,dot,body);
        if(!oldIds.has(entry.laporanId)) item.classList.add('is-progress-added');
        return item;
    }
    function renderHistory(details, incomingEntries) {
        const card=findLaporanDibuatCard(details); if(!card) return;
        const old=readExistingHistory(card);
        const merged=new Map();
        old.concat(incomingEntries||[]).forEach(e=>{ if(e.laporanId) merged.set(e.laporanId,e); });
        const entries=dedupeProgressEntries([...merged.values()]);
        if(!entries.length) return;
        const oldIds=new Set(old.map(e=>e.laporanId)); const latestEntry=entries[entries.length-1];
        const history=card.querySelector(':scope > .danpus-inline-progress-history'); if(history) history.remove();
        const next=document.createElement('div'); next.className='danpus-inline-progress-history realtime-history'; next.dataset.danpusInlineProgressHistory='1';
        const label=document.createElement('div'); label.className='danpus-inline-progress-label';
        label.innerHTML=`<span><b>Riwayat progres</b><em>Realtime</em></span><span class="danpus-inline-progress-count">${entries.length} update</span>`;
        next.appendChild(label);
        const board=document.createElement('div'); board.className='danpus-snake-board';
        entries.forEach(entry=>board.appendChild(createProgressItem(entry,latestEntry,oldIds,details)));
        next.appendChild(board); card.appendChild(next);
        updateRowLayout(board);
        if (rowObserver) rowObserver.observe(board);
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
.danpus-inline-progress-history:has(> .danpus-snake-board){padding-top:14px}
.danpus-inline-progress-history:has(> .danpus-snake-board) > .danpus-inline-progress-label{display:none}
.danpus-inline-progress-label{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;color:var(--p-muted);font-size:11px;font-weight:800}.danpus-inline-progress-label span:first-child{display:flex;align-items:center;gap:8px}.danpus-inline-progress-label span:first-child::before{content:"";width:8px;height:8px;border-radius:50%;background:var(--p-green);box-shadow:0 0 0 4px color-mix(in srgb,var(--p-green) 12%,transparent)}
.danpus-inline-progress-label em{font-style:normal;font-size:9px;padding:3px 8px;border-radius:999px;color:var(--p-green);background:color-mix(in srgb,var(--p-green) 9%,var(--p-surface));border:1px solid color-mix(in srgb,var(--p-green) 22%,var(--p-border))}.danpus-inline-progress-count{opacity:.7}
.danpus-snake-board{position:relative;display:grid;grid-template-columns:repeat(6,1fr);gap:22px 0;width:100%;padding:14px 6px 6px;box-sizing:border-box}
.danpus-snake-progress-item{position:relative;width:100%;min-width:0;padding-top:26px;box-sizing:border-box;display:flex;flex-direction:column;align-items:center;gap:6px;text-align:center}
.danpus-snake-progress-item:not(.is-row-start)::before{content:"";position:absolute;top:39px;left:calc(-50% + 15px);width:calc(100% - 30px);height:4px;background:var(--p-green);z-index:0}
.danpus-snake-progress-item.is-pending:not(.is-row-start)::before{background:var(--p-border)}
.danpus-snake-connectors{position:absolute;inset:0;width:100%;height:100%;overflow:visible;pointer-events:none;z-index:0}
.danpus-snake-hook{fill:none;stroke:var(--p-green);stroke-width:4;stroke-linecap:round;stroke-linejoin:round}
.danpus-snake-hook.is-pending{stroke:var(--p-border)}
@media(max-width:900px){.danpus-snake-board{grid-template-columns:repeat(4,1fr)}}
@media(max-width:700px){.danpus-snake-board{grid-template-columns:repeat(3,1fr)}}
@media(max-width:430px){.danpus-snake-board{grid-template-columns:repeat(2,1fr)}}
.danpus-snake-progress-dot{position:relative;z-index:1;width:30px;height:30px;flex:0 0 auto;display:flex;align-items:center;justify-content:center;border-radius:50%;background:var(--p-green);border:none;color:#fff;font:800 10px/1 var(--mono,ui-monospace,monospace);box-shadow:0 2px 6px rgba(15,23,42,.18)}
.danpus-snake-progress-item.is-pending .danpus-snake-progress-dot{background:var(--p-border);color:var(--p-muted)}
.danpus-snake-progress-body{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;width:100%;box-sizing:border-box;line-height:1.15}
.danpus-snake-progress-body small.danpus-snake-progress-date{display:block;width:100%;color:var(--p-muted);font-size:8.5px;line-height:1.3;white-space:normal;word-break:normal;overflow-wrap:break-word;text-align:center;opacity:.85}
.danpus-snake-detail{position:absolute;top:0;left:50%;transform:translateX(-50%);z-index:2;-webkit-appearance:none;appearance:none;display:inline-flex;align-items:center;justify-content:center;min-width:40px;height:20px;padding:0 8px;border:1px solid var(--p-border);border-radius:6px;background:var(--p-surface);color:var(--p-text);font:700 9px/1 var(--font-sans,system-ui,sans-serif);cursor:pointer;box-shadow:0 3px 8px rgba(15,23,42,.1);transition:background .18s ease,border-color .18s ease,transform .18s ease}
.danpus-snake-detail::before{content:"";position:absolute;top:100%;left:50%;transform:translateX(-50%);width:0;height:0;border:6px solid transparent;border-top-color:var(--p-border);transition:border-top-color .18s ease}
.danpus-snake-detail::after{content:"";position:absolute;top:100%;left:50%;transform:translateX(-50%) translateY(-1px);width:0;height:0;border:5px solid transparent;border-top-color:var(--p-surface);transition:border-top-color .18s ease}
.danpus-snake-detail:hover{background:var(--p-surface-2);border-color:var(--p-accent);transform:translateX(-50%) translateY(-1px)}
.danpus-snake-detail:hover::before{border-top-color:var(--p-accent)}
.danpus-snake-detail:hover::after{border-top-color:var(--p-surface-2)}
.danpus-snake-progress-item.is-progress-added{opacity:1;transform:none}
.danpus-snake-progress-item.is-progress-added.is-progress-visible{animation:none}
.danpus-inline-progress-history.realtime-history{animation:none;box-shadow:none}</style>