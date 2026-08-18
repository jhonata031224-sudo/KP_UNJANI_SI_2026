<script>
(function () {
    var endpoint = '{{ route('laporan.log-aktivitas.realtime') }}';
    var polling = false;
    var initialRenderComplete = false;
    var pollTimer = null;

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function highlightRow(row) {
        if (!row) return;
        row.classList.remove('is-realtime-updated');
        void row.offsetWidth;
        row.classList.add('is-realtime-updated');
        window.setTimeout(function () { row.classList.remove('is-realtime-updated'); }, 1400);
    }

    // DANPUS: satu Log Aktivitas = satu permintaan/perihal.
    // Semua checkpoint dari permintaan yang sama hanya menambah chip progres.
    function findCurrentRow(section, row) {
        if (!section || !row) return null;
        var permintaanId = row.getAttribute('data-permintaan-id');
        if (permintaanId) {
            return section.querySelector('tr[data-permintaan-id="' + permintaanId + '"]');
        }
        return section.querySelector('tr[data-laporan-id="' + row.getAttribute('data-laporan-id') + '"]');
    }

    function findCurrentDetails(section, row) {
        var current = findCurrentRow(section, row);
        return current ? current.closest('.danpus-report-dropdown') : null;
    }

    function createDropdownForRow(row) {
        var details = document.createElement('details');
        details.className = 'danpus-report-dropdown is-realtime-new';
        var permintaanId = row.getAttribute('data-permintaan-id');
        if (permintaanId) details.dataset.permintaanId = permintaanId;

        var summary = document.createElement('summary');
        var main = document.createElement('div');
        main.className = 'danpus-report-summary-main';
        var chevron = document.createElement('span');
        chevron.className = 'danpus-report-chevron';
        var subject = document.createElement('span');
        subject.className = 'danpus-report-subject';
        subject.textContent = row.getAttribute('data-perihal') || 'Laporan tanpa perihal';
        main.appendChild(chevron);
        main.appendChild(subject);
        summary.appendChild(main);
        details.appendChild(summary);

        var content = document.createElement('div');
        content.className = 'danpus-report-content';
        content.appendChild(row);
        details.appendChild(content);
        return details;
    }

    function statusClass(status) {
        var value = String(status || '').toLowerCase();
        if (value === 'progres') return 'blue';
        if (value.indexOf('tolak') !== -1) return 'bad';
        if (value.indexOf('setuj') !== -1 || value.indexOf('diterima') !== -1) return 'ok';
        if (value.indexOf('revisi') !== -1) return 'revisi';
        return 'wait';
    }

    function getProgressValues(row) {
        var raw = row.getAttribute('data-progress-history') || '';
        var values = raw.split(',').map(function (v) { return String(v).trim(); }).filter(Boolean);
        var current = row.getAttribute('data-progres');
        if (current !== null && current !== '') values.push(String(current));
        return Array.from(new Set(values));
    }

    function setProgressValues(row, values) {
        var normalized = [];
        (values || []).forEach(function (value) {
            var v = String(value).replace(/[^0-9]/g, '');
            if (v === '') return;
            if (!normalized.includes(v)) normalized.push(v);
        });
        row.setAttribute('data-progress-history', normalized.join(','));

        var cell = row.children[3];
        if (!cell) return;
        cell.innerHTML = '';

        var status = row.getAttribute('data-laporan-status') || '';
        var latest = row.getAttribute('data-progres') || '';
        var pill = document.createElement('span');
        pill.className = 'status-pill ' + statusClass(status);
        pill.textContent = String(status).toLowerCase() === 'progres'
            ? 'Progres · ' + latest + '%'
            : status;
        cell.appendChild(pill);

        if (normalized.length > 1 && String(status).toLowerCase() === 'progres') {
            var history = document.createElement('div');
            history.className = 'danpus-progress-history';
            normalized.forEach(function (value) {
                var chip = document.createElement('span');
                chip.className = 'danpus-progress-chip' + (String(value) === String(latest) ? ' is-current' : '');
                chip.textContent = value + '%';
                history.appendChild(chip);
            });
            cell.appendChild(history);
        }
    }

    function updateExistingRow(existing, latestRow, progressHistory) {
        var oldProgress = existing.getAttribute('data-progres');
        var oldStatus = existing.getAttribute('data-laporan-status');
        var oldUpdated = existing.getAttribute('data-updated');
        var latestProgress = latestRow.getAttribute('data-progres');
        var latestStatus = latestRow.getAttribute('data-laporan-status');
        var latestUpdated = latestRow.getAttribute('data-updated');
        var changed = oldUpdated !== latestUpdated || oldProgress !== latestProgress || oldStatus !== latestStatus;

        existing.setAttribute('data-laporan-id', latestRow.getAttribute('data-laporan-id') || '');
        existing.setAttribute('data-updated', latestUpdated || '');
        existing.setAttribute('data-progres', latestProgress || '');
        existing.setAttribute('data-kendala', latestRow.getAttribute('data-kendala') || '');
        existing.setAttribute('data-laporan-status', latestStatus || '');
        existing.setAttribute('data-satuan-nama', latestRow.getAttribute('data-satuan-nama') || '');
        existing.setAttribute('data-perihal', latestRow.getAttribute('data-perihal') || '');

        // Jangan replace seluruh row. Perihal, deadline, aksi, dan dropdown tetap satu.
        setProgressValues(existing, progressHistory);

        var newDetail = latestRow.querySelector('.detail-btn');
        var oldDetail = existing.querySelector('.detail-btn');
        if (newDetail && oldDetail) {
            Array.prototype.slice.call(newDetail.attributes).forEach(function (attr) {
                if (attr.name !== 'class' && attr.name !== 'type') oldDetail.setAttribute(attr.name, attr.value);
            });
        }

        var details = existing.closest('.danpus-report-dropdown');
        if (details) {
            var subject = details.querySelector('.danpus-report-subject');
            if (subject) subject.textContent = latestRow.getAttribute('data-perihal') || subject.textContent;
        }

        if (changed) highlightRow(existing);
        return changed;
    }

    function groupRowsByActivity(html) {
        var temp = document.createElement('tbody');
        temp.innerHTML = html || '';
        var rows = Array.prototype.slice.call(temp.children).filter(function (el) {
            return el.tagName === 'TR' && el.getAttribute('data-laporan-id');
        });
        var groups = {};

        rows.forEach(function (row) {
            var key = row.getAttribute('data-permintaan-id') || ('laporan-' + row.getAttribute('data-laporan-id'));
            if (!groups[key]) groups[key] = [];
            groups[key].push(row);
        });

        return Object.keys(groups).map(function (key) {
            var group = groups[key].sort(function (a, b) {
                return Number(a.getAttribute('data-laporan-id')) - Number(b.getAttribute('data-laporan-id'));
            });
            var latest = group[group.length - 1];
            var progressHistory = group.map(function (row) { return row.getAttribute('data-progres'); })
                .filter(function (v) { return v !== null && v !== ''; });
            latest.setAttribute('data-progress-history', Array.from(new Set(progressHistory)).join(','));
            return { key: key, row: latest, history: Array.from(new Set(progressHistory)) };
        });
    }

    function upsertRows(rowsBySatuan) {
        Object.keys(rowsBySatuan || {}).forEach(function (satuanId) {
            var section = document.getElementById('satlak-' + satuanId);
            if (!section) return;

            var cleanWrap = section.querySelector('.clean-table-wrap');
            var activities = groupRowsByActivity(rowsBySatuan[satuanId]);

            activities.forEach(function (activity) {
                var row = activity.row;
                var existing = findCurrentRow(section, row);

                if (!existing && !initialRenderComplete) return;

                if (!existing) {
                    setProgressValues(row, activity.history);
                    if (cleanWrap && cleanWrap.querySelector('.danpus-report-dropdown-list')) {
                        cleanWrap.querySelector('.danpus-report-dropdown-list').insertBefore(createDropdownForRow(row), cleanWrap.querySelector('.danpus-report-dropdown-list').firstChild);
                    } else {
                        var tbody = cleanWrap && cleanWrap.querySelector('tbody');
                        if (tbody) tbody.insertBefore(row, tbody.firstChild);
                    }
                    return;
                }

                updateExistingRow(existing, row, activity.history);
            });
        });
    }

    function updateStats(stats) {
        Object.keys(stats || {}).forEach(function (satuanId) {
            var s = stats[satuanId];
            if (!s) return;
            setText('satlakTotalOverview-' + satuanId, s.total);
            setText('satlakTotalMonitoring-' + satuanId, s.total);
            setText('satlakDiterima-' + satuanId, s.diterima);
            setText('satlakDitolak-' + satuanId, s.ditolak);
            setText('satlakMenunggu-' + satuanId, s.menunggu);
        });
    }

    function poll() {
        if (polling) return;
        polling = true;
        fetch(endpoint + '?since=0&realtime=1&_=' + Date.now(), {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
        })
        .then(function (response) {
            if (response.status === 401) {
                if (window.siberadTampilkanSesiBerakhir) window.siberadTampilkanSesiBerakhir();
                return null;
            }
            if (response.status === 419 || !response.ok) return null;
            return response.json();
        })
        .then(function (data) {
            if (!data) return;
            updateStats(data.stats);
            setText('kpiTotalLaporan', data.total_laporan);
            setText('kpiDisetujuiLaporan', data.total_disetujui);
            setText('kpiDitolakLaporan', data.total_ditolak);
            upsertRows(data.rows || {});
            initialRenderComplete = true;
        })
        .catch(function () {})
        .finally(function () { polling = false; });
    }

    function scheduleNextPoll() {
        if (pollTimer) window.clearTimeout(pollTimer);
        pollTimer = window.setTimeout(function () { poll(); scheduleNextPoll(); }, 2000);
    }

    function start() {
        poll();
        scheduleNextPoll();
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) { poll(); scheduleNextPoll(); }
        });
        window.addEventListener('focus', function () { poll(); scheduleNextPoll(); });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
</script>
<style>
#monitoring tr.is-realtime-updated,
[id^="satlak-"] tr.is-realtime-updated { animation: satlakRowRealtimeUpdated 1.4s ease; }
@keyframes satlakRowRealtimeUpdated { 0% { background: rgba(59, 130, 246, .12); } 100% { background: transparent; } }

.danpus-progress-history {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-left: 7px;
    vertical-align: middle;
    flex-wrap: wrap;
}
.danpus-progress-chip {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border: 1px solid #d7dee8;
    border-radius: 999px;
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.3;
}
.danpus-progress-chip.is-current {
    background: #e8f7ef;
    border-color: #9bd4b4;
    color: #087443;
}
</style>