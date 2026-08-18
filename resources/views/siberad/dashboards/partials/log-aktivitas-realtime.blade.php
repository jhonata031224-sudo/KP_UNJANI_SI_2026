<script>
(function () {
    const endpoint = '{{ route('laporan.log-aktivitas.realtime') }}';
    let busy = false;
    let timer = null;

    const text = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    const uniqueProgress = values => {
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
                values: uniqueProgress(rows.map(r => r.dataset.progres))
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
        return [...details.querySelectorAll('.danpus-activity-card')].find(card => {
            const title = card.querySelector('.danpus-activity-stage');
            return title && title.textContent.trim().toLowerCase() === 'laporan dibuat';
        }) || null;
    }

    // Hapus renderer/history lama yang berada di luar tahap Laporan Dibuat.
    // Ini mencegah riwayat tampil dua kali di bawah timeline.
    function removeExternalHistories() {
        document.querySelectorAll('.danpus-inline-progress-history, .danpus-progress-history, .danpus-progress-branch').forEach(el => {
            if (!el.closest('.danpus-activity-card')) el.remove();
        });
    }

    function readExistingHistory(card) {
        const history = card?.querySelector(':scope > .danpus-inline-progress-history');
        if (!history) return [];
        return [...history.querySelectorAll('[data-progress]')].map(el => el.dataset.progress);
    }

    function createProgressItem(value, index, latestValue, oldSet) {
        const item = document.createElement('div');
        item.className = 'danpus-snake-progress-item' + (String(value) === String(latestValue) ? ' latest' : '');
        item.dataset.progress = value;

        const dot = document.createElement('span');
        dot.className = 'danpus-snake-progress-dot';

        const body = document.createElement('span');
        body.className = 'danpus-snake-progress-body';
        body.innerHTML = `<strong>${value}%</strong><small>${String(value) === String(latestValue) ? 'Terbaru' : 'Tercatat'}</small>`;

        item.append(dot, body);
        if (!oldSet.has(String(value))) item.classList.add('is-progress-added');
        return item;
    }

    function renderHistory(details, incomingValues) {
        const card = findLaporanDibuatCard(details);
        if (!card) return;

        const old = readExistingHistory(card);
        const values = uniqueProgress(old.concat(incomingValues || []));
        if (!values.length) return;

        const oldSet = new Set(old.map(String));
        const latestValue = values[values.length - 1];
        let history = card.querySelector(':scope > .danpus-inline-progress-history');

        if (history) history.remove();

        const next = document.createElement('div');
        next.className = 'danpus-inline-progress-history realtime-history';
        next.dataset.danpusInlineProgressHistory = '1';

        const label = document.createElement('div');
        label.className = 'danpus-inline-progress-label';
        label.innerHTML = `<span><b>Riwayat progres</b><em>Realtime</em></span><span class="danpus-inline-progress-count">${values.length} update</span>`;
        next.appendChild(label);

        const board = document.createElement('div');
        board.className = 'danpus-snake-board';

        // Setiap baris membalik arah. Hasilnya: kiri → kanan, turun, kanan → kiri,
        // turun, kiri → kanan, dan seterusnya sampai 100%.
        const columns = Math.max(2, Math.min(7, Math.floor((Math.max(board.clientWidth || card.clientWidth || 900, 560)) / 120)));
        for (let start = 0, rowIndex = 0; start < values.length; start += columns, rowIndex++) {
            const rowValues = values.slice(start, start + columns);
            const row = document.createElement('div');
            row.className = 'danpus-snake-row ' + (rowIndex % 2 ? 'reverse' : 'forward');

            rowValues.forEach((value, localIndex) => {
                row.appendChild(createProgressItem(value, start + localIndex, latestValue, oldSet));
            });
            board.appendChild(row);

            if (start + columns < values.length) {
                const turn = document.createElement('div');
                turn.className = 'danpus-snake-turn ' + (rowIndex % 2 ? 'turn-left' : 'turn-right');
                board.appendChild(turn);
            }
        }

        next.appendChild(board);
        card.appendChild(next);

        const added = next.querySelector('.is-progress-added');
        if (added) {
            requestAnimationFrame(() => {
                added.classList.add('is-progress-visible');
            });
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
            removeExternalHistories();
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
            removeExternalHistories();
        })
        .catch(() => {})
        .finally(() => { busy = false; });
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(() => { poll(); schedule(); }, 2000);
    }

    function start() {
        removeExternalHistories();
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
/* =========================================================
   DANPUS - satu-satunya riwayat progres ada di Laporan Dibuat
   ========================================================= */
.danpus-progress-branch,
.danpus-progress-history { display:none!important; }

/* Riwayat di luar kartu Laporan Dibuat adalah renderer lama. */
.danpus-report-dropdown > .danpus-inline-progress-history,
.danpus-report-content > .danpus-inline-progress-history { display:none!important; }

.danpus-inline-progress-history {
    position:relative;
    margin:15px 0 3px;
    padding:15px 15px 17px;
    border:1px solid color-mix(in srgb,var(--p-accent) 20%,var(--p-border));
    border-radius:16px;
    background:linear-gradient(145deg,color-mix(in srgb,var(--p-surface) 97%,var(--p-accent)),var(--p-surface-2));
    overflow:hidden;
}
.danpus-inline-progress-history::before {
    content:"";
    position:absolute;
    left:-25%; top:0; width:25%; height:2px;
    background:linear-gradient(90deg,transparent,var(--p-accent),transparent);
    animation:danpusSnakeFlow 2.4s linear infinite;
}
.danpus-inline-progress-label {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:13px;
    color:var(--p-muted);
    font-size:11px;
    font-weight:800;
}
.danpus-inline-progress-label span:first-child { display:flex;align-items:center;gap:8px; }
.danpus-inline-progress-label span:first-child::before {
    content:"";
    width:8px;height:8px;border-radius:50%;
    background:var(--p-green);
    box-shadow:0 0 0 4px color-mix(in srgb,var(--p-green) 12%,transparent);
    animation:danpusLivePulse 1.6s ease-in-out infinite;
}
.danpus-inline-progress-label em {
    font-style:normal;
    font-size:9px;
    padding:3px 7px;
    border-radius:999px;
    color:var(--p-green);
    background:color-mix(in srgb,var(--p-green) 9%,var(--p-surface));
    border:1px solid color-mix(in srgb,var(--p-green) 22%,var(--p-border));
}
.danpus-inline-progress-count { opacity:.7; }

/* Snake board: tidak pernah memakai horizontal scrollbar. */
.danpus-snake-board {
    position:relative;
    display:flex;
    flex-direction:column;
    gap:18px;
    width:100%;
    padding:2px 5px 4px;
    box-sizing:border-box;
}
.danpus-snake-row {
    position:relative;
    display:grid;
    grid-template-columns:repeat(7,minmax(0,1fr));
    gap:10px;
    min-height:62px;
    align-items:center;
}
.danpus-snake-row.reverse { direction:rtl; }
.danpus-snake-row.reverse .danpus-snake-progress-item { direction:ltr; }

.danpus-snake-row::before {
    content:"";
    position:absolute;
    left:4%; right:4%; top:50%;
    height:2px;
    transform:translateY(-50%);
    background:linear-gradient(90deg,color-mix(in srgb,var(--p-green) 30%,transparent),color-mix(in srgb,var(--p-accent) 48%,transparent),color-mix(in srgb,var(--p-green) 30%,transparent));
    background-size:200% 100%;
    animation:danpusSnakeLine 2.8s linear infinite;
    z-index:0;
}
.danpus-snake-progress-item {
    position:relative;
    z-index:2;
    min-width:0;
    min-height:58px;
    padding:8px 7px;
    box-sizing:border-box;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:4px;
    border:1px solid color-mix(in srgb,var(--p-muted) 20%,var(--p-border));
    border-radius:14px;
    background:var(--p-surface);
    color:var(--p-text);
    box-shadow:0 3px 10px rgba(15,23,42,.06);
    text-align:center;
    transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease;
}
.danpus-snake-progress-item:hover { transform:translateY(-2px); }
.danpus-snake-progress-item.latest {
    border-color:color-mix(in srgb,var(--p-green) 58%,var(--p-border));
    box-shadow:0 0 0 3px color-mix(in srgb,var(--p-green) 9%,transparent),0 7px 18px color-mix(in srgb,var(--p-green) 12%,transparent);
}
.danpus-snake-progress-dot {
    width:8px;height:8px;border-radius:50%;
    background:var(--p-green);
    box-shadow:0 0 0 3px color-mix(in srgb,var(--p-green) 10%,transparent);
}
.danpus-snake-progress-item.latest .danpus-snake-progress-dot {
    background:var(--p-orange);
    box-shadow:0 0 0 3px var(--p-orange-bg);
    animation:danpusLivePulse 1.1s ease-in-out infinite;
}
.danpus-snake-progress-body {
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    min-width:0;
    line-height:1.1;
}
.danpus-snake-progress-body strong {
    font-family:var(--mono);
    font-size:12px;
    font-weight:900;
    white-space:nowrap;
}
.danpus-snake-progress-body small {
    margin-top:3px;
    color:var(--p-muted);
    font-size:8px;
    line-height:1;
    white-space:nowrap;
}

/* Belokan vertikal di ujung baris. */
.danpus-snake-turn {
    position:absolute;
    width:14px;
    height:18px;
    border-width:0 2px 2px 0;
    border-style:solid;
    border-color:color-mix(in srgb,var(--p-accent) 55%,var(--p-border));
    z-index:1;
    animation:danpusTurnPulse 2s ease-in-out infinite;
}
.danpus-snake-turn.turn-right { right:1.5%; bottom:-10px; transform:rotate(45deg); }
.danpus-snake-turn.turn-left { left:1.5%; bottom:-10px; transform:rotate(225deg); }

.danpus-snake-progress-item.is-progress-added {
    opacity:0;
    transform:translateY(13px) scale(.78);
}
.danpus-snake-progress-item.is-progress-added.is-progress-visible {
    animation:danpusSnakeAdd .75s cubic-bezier(.2,.85,.2,1) forwards;
}
.danpus-inline-progress-history.realtime-history { animation:danpusHistoryFlash .75s ease; }

@keyframes danpusSnakeFlow { 0%{transform:translateX(0)} 100%{transform:translateX(500%)} }
@keyframes danpusSnakeLine { 0%{background-position:0 0} 100%{background-position:200% 0} }
@keyframes danpusLivePulse { 0%,100%{transform:scale(1);opacity:.72} 50%{transform:scale(1.25);opacity:1} }
@keyframes danpusTurnPulse { 0%,100%{opacity:.45} 50%{opacity:1} }
@keyframes danpusSnakeAdd { 0%{opacity:0;transform:translateY(13px) scale(.78);filter:blur(2px)} 55%{opacity:1;transform:translateY(-2px) scale(1.06);filter:blur(0)} 100%{opacity:1;transform:translateY(0) scale(1)} }
@keyframes danpusHistoryFlash { 0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--p-accent) 20%,transparent)} 100%{box-shadow:0 0 0 14px transparent} }

@media(max-width:1000px){
    .danpus-snake-row{grid-template-columns:repeat(5,minmax(0,1fr));}
}
@media(max-width:700px){
    .danpus-snake-row{grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;}
    .danpus-snake-progress-item{min-height:54px;padding:7px 4px;border-radius:12px;}
    .danpus-snake-progress-body strong{font-size:11px;}
}
@media(max-width:430px){
    .danpus-snake-row{grid-template-columns:repeat(2,minmax(0,1fr));}
}
@media(prefers-reduced-motion:reduce){
    .danpus-inline-progress-history::before,
    .danpus-inline-progress-label span:first-child::before,
    .danpus-snake-row::before,
    .danpus-snake-turn,
    .danpus-snake-progress-item.latest .danpus-snake-progress-dot,
    .danpus-snake-progress-item.is-progress-added.is-progress-visible,
    .danpus-inline-progress-history.realtime-history{animation:none!important;}
}
</style>
