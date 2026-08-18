<script>
(function () {
    const endpoint = '{{ route('laporan.log-aktivitas.realtime') }}';
    let busy = false;
    let timer = null;

    const text = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value; };
    const progressList = values => {
        const seen = new Set();
        return (values || []).map(v => String(v ?? '').trim()).filter(v => { if (!v || seen.has(v)) return false; seen.add(v); return true; });
    };
    function allRows(html) {
        const tbody = document.createElement('tbody');
        tbody.innerHTML = html || '';
        return [...tbody.children].filter(el => el.matches('tr[data-permintaan-id], tr[data-laporan-id]'));
    }
    function groups(html) {
        const map = new Map();
        allRows(html).forEach(row => {
            const requestId = row.dataset.permintaanId;
            const key = requestId ? `request:${requestId}` : `laporan:${row.dataset.laporanId}`;
            if (!map.has(key)) map.set(key, []);
            map.get(key).push(row);
        });
        return [...map.values()].map(rows => {
            rows.sort((a,b) => Number(a.dataset.laporanId || 0) - Number(b.dataset.laporanId || 0));
            return { rows, latest: rows[rows.length - 1], requestId: rows[0].dataset.permintaanId || '', values: progressList(rows.map(r => r.dataset.progres)) };
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
            found = dropdowns.find(d => {
                const s = d.querySelector('.danpus-report-subject, summary');
                return s && s.textContent.trim().includes(wanted);
            });
        }
        return found || null;
    }
    function findHistory(details) {
        /* Ambil history yang benar-benar berada di dalam Laporan Dibuat, bukan history wrapper di atas. */
        return details?.querySelector('.danpus-activity-log .danpus-inline-progress-history') || details?.querySelector('.danpus-inline-progress-history');
    }
    function renderHistory(details, incomingValues) {
        if (!details) return;
        const existing = findHistory(details);
        const old = existing ? [...existing.querySelectorAll('[data-progress]')].map(x => x.dataset.progress) : [];
        const values = progressList(old.concat(incomingValues || []));
        if (!values.length) return;

        const history = document.createElement('div');
        history.className = 'danpus-inline-progress-history realtime-history';
        history.dataset.danpusInlineProgressHistory = '1';
        const label = document.createElement('div');
        label.className = 'danpus-inline-progress-label';
        label.innerHTML = `<span>Riwayat progres</span><span class="danpus-inline-progress-count">(${values.length})</span>`;
        history.appendChild(label);
        const list = document.createElement('div');
        list.className = 'danpus-inline-progress-list';
        values.forEach((value, i) => {
            const item = document.createElement('span');
            item.className = 'danpus-inline-progress-item' + (i === values.length - 1 ? ' latest' : '');
            item.dataset.progress = value;
            item.textContent = `Progres · ${value}%`;
            if (!old.includes(value)) item.classList.add('is-progress-added');
            list.appendChild(item);
            if (i < values.length - 1) {
                const arrow = document.createElement('span');
                arrow.className = 'danpus-inline-progress-arrow';
                arrow.textContent = '';
                list.appendChild(arrow);
            }
        });
        history.appendChild(list);
        if (existing) existing.replaceWith(history);
        else {
            const log = details.querySelector('.danpus-activity-log');
            if (log) log.appendChild(history);
        }
        const added = history.querySelector('.is-progress-added');
        if (added) setTimeout(() => added.scrollIntoView({behavior:'smooth', block:'nearest', inline:'center'}), 40);
    }
    function replaceLatestRow(details, row) {
        const oldRows = details.querySelectorAll('tr[data-laporan-id]');
        const oldLatest = oldRows[oldRows.length - 1];
        if (oldLatest) oldLatest.replaceWith(row);
        else details.querySelector('.danpus-report-content')?.appendChild(row);
        row.classList.add('is-realtime-updated');
        setTimeout(() => row.classList.remove('is-realtime-updated'), 1800);
    }
    function updateCurrentProgress(row) {
        if (!row) return;
        const progress = row.dataset.progres;
        const pill = row.querySelector('.status-pill');
        if (pill && String(row.dataset.laporanStatus || '').toLowerCase().includes('progres')) pill.textContent = `Progres · ${progress}%`;
        const detail = row.querySelector('.detail-btn');
        if (detail) detail.dataset.progres = progress;
    }
    function createDropdown(row, values) {
        const details = document.createElement('details');
        details.className = 'danpus-report-dropdown is-realtime-new';
        details.dataset.permintaanId = row.dataset.permintaanId || '';
        details.dataset.laporanId = row.dataset.laporanId || '';
        const summary = document.createElement('summary');
        summary.innerHTML = `<div class="danpus-report-summary-main"><span class="danpus-report-chevron"></span><span class="danpus-report-subject"></span></div>`;
        summary.querySelector('.danpus-report-subject').textContent = row.dataset.perihal || 'Laporan';
        const content = document.createElement('div');
        content.className = 'danpus-report-content';
        content.appendChild(row);
        details.append(summary, content);
        return details;
    }
    function applyGroup(group, section) {
        const latest = group.latest;
        const details = findDetails(group.requestId, latest.dataset.laporanId, latest.dataset.perihal);
        if (details) {
            const current = details.querySelector('tr[data-laporan-id]');
            const changed = !current || current.dataset.laporanId !== latest.dataset.laporanId || current.dataset.progres !== latest.dataset.progres || current.dataset.updated !== latest.dataset.updated;
            if (changed) replaceLatestRow(details, latest);
            else updateCurrentProgress(current);
            /* Render SETELAH row terbaru dipasang, supaya history tidak ikut hilang saat row diganti. */
            renderHistory(details, group.values);
            return;
        }
        const cleanWrap = section?.querySelector('.clean-table-wrap');
        const list = cleanWrap?.querySelector('.danpus-report-dropdown-list');
        if (list) list.prepend(createDropdown(latest, group.values));
    }
    function upsert(rowsBySatuan) {
        Object.entries(rowsBySatuan || {}).forEach(([satuanId, html]) => {
            const section = document.getElementById(`satlak-${satuanId}`);
            groups(html).forEach(group => applyGroup(group, section));
        });
    }
    function updateStats(stats) {
        Object.entries(stats || {}).forEach(([id, s]) => {
            text(`satlakTotalOverview-${id}`, s.total); text(`satlakTotalMonitoring-${id}`, s.total); text(`satlakDiterima-${id}`, s.diterima); text(`satlakDitolak-${id}`, s.ditolak); text(`satlakMenunggu-${id}`, s.menunggu);
        });
    }
    function poll() {
        if (busy) return;
        busy = true;
        fetch(endpoint + '?since=0&realtime=1&_=' + Date.now(), { credentials:'same-origin', cache:'no-store', headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'} })
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            if (!data) return;
            updateStats(data.stats);
            text('kpiTotalLaporan', data.total_laporan); text('kpiDisetujuiLaporan', data.total_disetujui); text('kpiDitolakLaporan', data.total_ditolak);
            upsert(data.rows || {});
        }).catch(() => {}).finally(() => { busy = false; });
    }
    function schedule(){ clearTimeout(timer); timer=setTimeout(()=>{poll();schedule();},2000); }
    function start(){ setTimeout(()=>{poll();schedule();},150); document.addEventListener('visibilitychange',()=>{if(!document.hidden){poll();schedule();}}); window.addEventListener('focus',()=>{poll();schedule();}); }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',start,{once:true}); else start();
})();
</script>
<style>
[id^="satlak-"] tr.is-realtime-updated{animation:danpusRealtimeProgress 1.5s ease}
[id^="satlak-"] .is-progress-added{animation:danpusProgressAdded .8s cubic-bezier(.2,.8,.2,1)}
@keyframes danpusRealtimeProgress{0%{background:rgba(245,158,11,.24)}100%{background:transparent}}
@keyframes danpusProgressAdded{0%{transform:translateX(18px) scale(.72);opacity:0;filter:blur(2px)}55%{transform:translateX(0) scale(1.08);opacity:1;filter:blur(0)}100%{transform:translateX(0) scale(1);opacity:1}}
@media(prefers-reduced-motion:reduce){[id^="satlak-"] .is-progress-added,[id^="satlak-"] tr.is-realtime-updated{animation:none}}
</style>