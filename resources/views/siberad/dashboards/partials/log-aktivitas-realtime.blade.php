<script>
(function () {
    const endpoint = '{{ route('laporan.log-aktivitas.realtime') }}';
    let busy = false;
    let timer = null;

    const text = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value; };
    const progressList = values => {
        const seen = new Set();
        return (values || []).map(v => String(v ?? '').trim()).filter(v => {
            if (!v || seen.has(v)) return false;
            seen.add(v);
            return true;
        });
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
            rows.sort((a, b) => Number(a.dataset.laporanId || 0) - Number(b.dataset.laporanId || 0));
            return {
                rows,
                latest: rows[rows.length - 1],
                requestId: rows[0].dataset.permintaanId || '',
                values: progressList(rows.map(r => r.dataset.progres))
            };
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

    function findLaporanDibuatCard(details) {
        if (!details) return null;
        const stages = [...details.querySelectorAll('.danpus-activity-card')];
        return stages.find(card => {
            const title = card.querySelector('.danpus-activity-stage');
            return title && title.textContent.trim().toLowerCase() === 'laporan dibuat';
        }) || null;
    }

    function cleanupDuplicateHistory(details, keep) {
        if (!details) return;
        details.querySelectorAll('.danpus-inline-progress-history').forEach(history => {
            if (history !== keep) history.remove();
        });
    }

    function renderHistory(details, incomingValues) {
        const card = findLaporanDibuatCard(details);
        if (!card) return;

        let history = card.querySelector(':scope > .danpus-inline-progress-history');
        cleanupDuplicateHistory(details, history);

        const old = history
            ? [...history.querySelectorAll('[data-progress]')].map(x => x.dataset.progress)
            : [];
        const values = progressList(old.concat(incomingValues || []));
        if (!values.length) return;

        const latestValue = values[values.length - 1];
        const oldSet = new Set(old);

        const next = document.createElement('div');
        next.className = 'danpus-inline-progress-history realtime-history';
        next.dataset.danpusInlineProgressHistory = '1';

        const label = document.createElement('div');
        label.className = 'danpus-inline-progress-label';
        label.innerHTML = `<span>Riwayat progres</span><span class="danpus-inline-progress-count">${values.length} update</span>`;
        next.appendChild(label);

        const list = document.createElement('div');
        list.className = 'danpus-inline-progress-list';

        values.forEach((value, index) => {
            const item = document.createElement('div');
            item.className = 'danpus-inline-progress-item' + (index === values.length - 1 ? ' latest' : '');
            item.dataset.progress = value;

            const dot = document.createElement('span');
            dot.className = 'danpus-inline-progress-dot';
            item.appendChild(dot);

            const body = document.createElement('span');
            body.className = 'danpus-inline-progress-body';
            body.innerHTML = `<strong>${value}%</strong><small>${index === values.length - 1 ? 'Terbaru' : 'Tercatat'}</small>`;
            item.appendChild(body);

            if (!oldSet.has(value)) item.classList.add('is-progress-added');
            list.appendChild(item);

            if (index < values.length - 1) {
                const arrow = document.createElement('span');
                arrow.className = 'danpus-inline-progress-arrow';
                list.appendChild(arrow);
            }
        });

        next.appendChild(list);

        if (history) history.replaceWith(next);
        else card.appendChild(next);

        const added = next.querySelector('.is-progress-added');
        if (added) {
            setTimeout(() => added.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' }), 80);
        }
    }

    function replaceLatestRow(details, row) {
        const oldRows = details.querySelectorAll('tr[data-laporan-id]');
        const oldLatest = oldRows[oldRows.length - 1];
        if (oldLatest) oldLatest.replaceWith(row);
        else details.querySelector('.danpus-report-content')?.appendChild(row);
        row.classList.add('is-realtime-updated');
        setTimeout(() => row.classList.remove('is-realtime-updated'), 1800);
    }

    function createDropdown(row) {
        const details = document.createElement('details');
        details.className = 'danpus-report-dropdown is-realtime-new';
        if (row.dataset.permintaanId) details.dataset.permintaanId = row.dataset.permintaanId;
        if (row.dataset.laporanId) details.dataset.laporanId = row.dataset.laporanId;

        const summary = document.createElement('summary');
        summary.innerHTML = '<div class="danpus-report-summary-main"><span class="danpus-report-chevron"></span><span class="danpus-report-subject"></span></div>';
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
            const changed = !current ||
                current.dataset.laporanId !== latest.dataset.laporanId ||
                current.dataset.progres !== latest.dataset.progres ||
                current.dataset.updated !== latest.dataset.updated;

            if (changed) replaceLatestRow(details, latest);
            else if (current) {
                const pill = current.querySelector('.status-pill');
                if (pill && String(current.dataset.laporanStatus || '').toLowerCase().includes('progres')) {
                    pill.textContent = `Progres · ${current.dataset.progres}%`;
                }
                const detail = current.querySelector('.detail-btn');
                if (detail) detail.dataset.progres = current.dataset.progres;
            }

            renderHistory(details, group.values);
            return;
        }

        const list = section?.querySelector('.clean-table-wrap .danpus-report-dropdown-list');
        if (list) list.prepend(createDropdown(latest));
    }

    function upsert(rowsBySatuan) {
        Object.entries(rowsBySatuan || {}).forEach(([satuanId, html]) => {
            const section = document.getElementById(`satlak-${satuanId}`);
            groups(html).forEach(group => applyGroup(group, section));
        });
    }

    function updateStats(stats) {
        Object.entries(stats || {}).forEach(([id, s]) => {
            text(`satlakTotalOverview-${id}`, s.total);
            text(`satlakTotalMonitoring-${id}`, s.total);
            text(`satlakDiterima-${id}`, s.diterima);
            text(`satlakDitolak-${id}`, s.ditolak);
            text(`satlakMenunggu-${id}`, s.menunggu);
        });
    }

    function poll() {
        if (busy) return;
        busy = true;
        fetch(endpoint + '?since=0&realtime=1&_=' + Date.now(), {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache'
            }
        })
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            if (!data) return;
            updateStats(data.stats);
            text('kpiTotalLaporan', data.total_laporan);
            text('kpiDisetujuiLaporan', data.total_disetujui);
            text('kpiDitolakLaporan', data.total_ditolak);
            upsert(data.rows || {});
        })
        .catch(() => {})
        .finally(() => { busy = false; });
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(() => { poll(); schedule(); }, 2000);
    }

    function start() {
        setTimeout(() => { poll(); schedule(); }, 150);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) { poll(); schedule(); }
        });
        window.addEventListener('focus', () => { poll(); schedule(); });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
</script>
<style>
/* Hanya satu riwayat progres: di dalam tahap "Laporan Dibuat". */
.danpus-progress-branch{display:none!important}
.danpus-inline-progress-history{position:relative;margin:14px 0 2px;padding:14px 14px 15px;border:1px solid color-mix(in srgb,var(--p-accent) 22%,var(--p-border));border-radius:14px;background:linear-gradient(135deg,color-mix(in srgb,var(--p-surface) 97%,var(--p-accent)),var(--p-surface-2));overflow:hidden}
.danpus-inline-progress-history::before{content:"";position:absolute;left:-28%;top:0;width:28%;height:2px;background:linear-gradient(90deg,transparent,var(--p-accent),transparent);opacity:.8;animation:danpusProgressFlow 2.8s linear infinite}
.danpus-inline-progress-label{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;font-size:10.5px;font-weight:800;color:var(--p-muted);letter-spacing:.02em}
.danpus-inline-progress-label span:first-child{display:flex;align-items:center;gap:7px}
.danpus-inline-progress-label span:first-child::before{content:"";width:7px;height:7px;border-radius:50%;background:var(--p-green);box-shadow:0 0 0 4px color-mix(in srgb,var(--p-green) 12%,transparent);animation:danpusLivePulse 1.8s ease-in-out infinite}
.danpus-inline-progress-count{opacity:.72}
.danpus-inline-progress-list{display:flex;align-items:center;gap:0;overflow-x:auto;padding:4px 2px 7px;scrollbar-width:thin}
.danpus-inline-progress-item{position:relative;z-index:2;display:inline-flex;align-items:center;gap:8px;min-width:72px;padding:7px 11px;border-radius:999px;border:1px solid color-mix(in srgb,var(--p-muted) 18%,var(--p-border));background:var(--p-surface);color:var(--p-text);white-space:nowrap;box-shadow:0 2px 8px rgba(15,23,42,.05);transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
.danpus-inline-progress-item.latest{border-color:color-mix(in srgb,var(--p-green) 55%,var(--p-border));box-shadow:0 0 0 3px color-mix(in srgb,var(--p-green) 9%,transparent),0 5px 15px color-mix(in srgb,var(--p-green) 12%,transparent)}
.danpus-inline-progress-dot{width:8px;height:8px;border-radius:50%;background:var(--p-green);box-shadow:0 0 0 3px color-mix(in srgb,var(--p-green) 10%,transparent);flex:0 0 auto}
.danpus-inline-progress-item.latest .danpus-inline-progress-dot{background:var(--p-orange);box-shadow:0 0 0 3px var(--p-orange-bg);animation:danpusLivePulse 1.2s ease-in-out infinite}
.danpus-inline-progress-body{display:flex;flex-direction:column;line-height:1.1}
.danpus-inline-progress-body strong{font-family:var(--mono);font-size:11px;font-weight:800}
.danpus-inline-progress-body small{font-size:8px;color:var(--p-muted);margin-top:2px}
.danpus-inline-progress-arrow{position:relative;flex:1 0 24px;min-width:24px;height:2px;margin:0 2px;background:color-mix(in srgb,var(--p-muted) 24%,transparent);overflow:hidden}
.danpus-inline-progress-arrow::after{content:"";position:absolute;left:-45%;top:0;width:45%;height:100%;background:linear-gradient(90deg,transparent,var(--p-accent),transparent);animation:danpusArrowFlow 2.2s linear infinite}
.danpus-inline-progress-item.is-progress-added{animation:danpusProgressAdded .75s cubic-bezier(.2,.8,.2,1)}
.danpus-inline-progress-history.realtime-history{animation:danpusHistoryRefresh .8s ease}
@keyframes danpusProgressFlow{0%{transform:translateX(0)}100%{transform:translateX(460%)}}
@keyframes danpusLivePulse{0%,100%{transform:scale(1);opacity:.72}50%{transform:scale(1.2);opacity:1}}
@keyframes danpusArrowFlow{0%{transform:translateX(0)}100%{transform:translateX(330%)}}
@keyframes danpusProgressAdded{0%{transform:translateX(20px) scale(.72);opacity:0;filter:blur(2px)}55%{transform:translateX(0) scale(1.08);opacity:1;filter:blur(0)}100%{transform:translateX(0) scale(1);opacity:1}}
@keyframes danpusHistoryRefresh{0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--p-accent) 20%,transparent)}100%{box-shadow:0 0 0 12px transparent}}
@media(prefers-reduced-motion:reduce){.danpus-inline-progress-history::before,.danpus-inline-progress-label span:first-child::before,.danpus-inline-progress-arrow::after,.danpus-inline-progress-item.latest .danpus-inline-progress-dot{animation:none}.danpus-inline-progress-item.is-progress-added,.danpus-inline-progress-history.realtime-history{animation:none}}
</style>