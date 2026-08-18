<script>
(function () {
    var endpoint = '{{ route('laporan.log-aktivitas.realtime') }}';
    var polling = false;
    var pollTimer = null;

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function highlightRow(row) {
        if (!row) return;
        row.classList.remove('is-realtime-updated', 'is-realtime-new');
        void row.offsetWidth;
        row.classList.add('is-realtime-updated');
        window.setTimeout(function () { row.classList.remove('is-realtime-updated'); }, 1800);
    }

    function uniqueProgress(values) {
        var seen = {};
        return (values || []).map(function (v) { return v == null ? '' : String(v); }).filter(function (v) {
            if (v === '' || seen[v]) return false;
            seen[v] = true;
            return true;
        });
    }

    function makeHistory(values) {
        var history = document.createElement('div');
        history.className = 'danpus-progress-history';
        uniqueProgress(values).forEach(function (value, index, clean) {
            var chip = document.createElement('span');
            chip.className = 'danpus-progress-chip' + (index === clean.length - 1 ? ' latest' : '');
            chip.textContent = value + '%';
            history.appendChild(chip);
        });
        return history;
    }

    // Ini adalah history yang benar-benar ditampilkan DI DALAM isi
    // "Laporan dibuat". Setiap checkpoint baru ditambahkan, bukan mengganti
    // history sebelumnya.
    function renderProgressFlow(details, values) {
        if (!details) return;
        var clean = uniqueProgress(values);
        var content = details.querySelector(':scope > .danpus-report-content');
        if (!content) return;

        var flow = content.querySelector(':scope > .danpus-progress-flow');
        if (!flow) {
            flow = document.createElement('div');
            flow.className = 'danpus-progress-flow';
            flow.setAttribute('data-danpus-progress-flow', '1');
            var label = document.createElement('div');
            label.className = 'danpus-progress-flow-label';
            label.textContent = 'Progres laporan';
            flow.appendChild(label);
            var list = document.createElement('div');
            list.className = 'danpus-progress-flow-list';
            flow.appendChild(list);
            content.insertBefore(flow, content.firstChild);
        }

        var list = flow.querySelector('.danpus-progress-flow-list');
        if (!list) return;
        var oldValues = Array.prototype.slice.call(list.querySelectorAll('.danpus-progress-flow-item')).map(function (el) {
            return el.getAttribute('data-progress');
        });
        var oldLast = oldValues.length ? oldValues[oldValues.length - 1] : null;

        list.innerHTML = '';
        clean.forEach(function (value, index) {
            var item = document.createElement('span');
            item.className = 'danpus-progress-flow-item' + (index === clean.length - 1 ? ' latest' : '');
            item.setAttribute('data-progress', value);
            item.textContent = 'Progres · ' + value + '%';
            if (String(value) !== String(oldLast)) {
                item.classList.add('is-progress-added');
            }
            list.appendChild(item);
            if (index < clean.length - 1) {
                var arrow = document.createElement('span');
                arrow.className = 'danpus-progress-flow-arrow';
                arrow.textContent = '→';
                list.appendChild(arrow);
            }
        });
    }

    function setHistory(details, values) {
        if (!details) return;
        var history = makeHistory(values);
        var summary = details.querySelector(':scope > summary');
        if (summary) {
            var old = summary.querySelector(':scope > .danpus-progress-history');
            if (old) old.replaceWith(history);
            else summary.appendChild(history);
        }
        renderProgressFlow(details, values);
    }

    function updateProgressInsideRow(row) {
        if (!row) return;
        var progress = row.getAttribute('data-progres');
        if (progress === null || progress === '') return;

        row.querySelectorAll('[data-progres]').forEach(function (el) {
            if (el === row) return;
            el.setAttribute('data-progres', progress);
            if (el.matches('.status-pill') || el.classList.contains('status-pill')) {
                var status = row.getAttribute('data-laporan-status') || '';
                if (status.toLowerCase().indexOf('progres') !== -1) el.textContent = 'Progres · ' + progress + '%';
            }
        });

        var pill = row.querySelector('.status-pill');
        if (pill) {
            var statusText = row.getAttribute('data-laporan-status') || '';
            if (statusText.toLowerCase().indexOf('progres') !== -1) pill.textContent = 'Progres · ' + progress + '%';
        }

        var detailButton = row.querySelector('.detail-btn');
        if (detailButton) detailButton.setAttribute('data-progres', progress);
    }

    function findCurrentDetails(section, permintaanId, laporanId) {
        if (!section) return null;
        if (permintaanId) {
            var byRequest = section.querySelector('.danpus-report-dropdown[data-permintaan-id="' + permintaanId + '"]');
            if (byRequest) return byRequest;
        }
        if (laporanId) {
            var byReport = section.querySelector('.danpus-report-dropdown[data-laporan-id="' + laporanId + '"]');
            if (byReport) return byReport;
        }
        return null;
    }

    function findCurrentRow(details, permintaanId, laporanId) {
        if (details) return details.querySelector('tr[data-laporan-id]');
        if (!permintaanId && !laporanId) return null;
        var selector = permintaanId ? 'tr[data-permintaan-id="' + permintaanId + '"]' : 'tr[data-laporan-id="' + laporanId + '"]';
        return document.querySelector(selector);
    }

    function createDropdownForRow(row, progressValues) {
        var details = document.createElement('details');
        details.className = 'danpus-report-dropdown is-realtime-new';
        if (row.getAttribute('data-permintaan-id')) details.dataset.permintaanId = row.getAttribute('data-permintaan-id');
        if (row.getAttribute('data-laporan-id')) details.dataset.laporanId = row.getAttribute('data-laporan-id');

        var summary = document.createElement('summary');
        var main = document.createElement('div');
        main.className = 'danpus-report-summary-main';
        var chevron = document.createElement('span');
        chevron.className = 'danpus-report-chevron';
        var subject = document.createElement('span');
        subject.className = 'danpus-report-subject';
        subject.textContent = row.getAttribute('data-perihal') || row.querySelector('.subject')?.textContent.trim() || 'Laporan tanpa perihal';
        main.appendChild(chevron);
        main.appendChild(subject);
        summary.appendChild(main);
        summary.appendChild(makeHistory(progressValues));
        details.appendChild(summary);

        var content = document.createElement('div');
        content.className = 'danpus-report-content';
        content.appendChild(row);
        details.appendChild(content);
        renderProgressFlow(details, progressValues);
        return details;
    }

    function replaceCurrentRow(details, row) {
        if (!details) return;
        var oldRow = details.querySelector('tr[data-laporan-id]');
        if (oldRow) oldRow.replaceWith(row);
        else details.querySelector('.danpus-report-content')?.appendChild(row);
        details.dataset.laporanId = row.getAttribute('data-laporan-id') || details.dataset.laporanId || '';
        var subject = details.querySelector('.danpus-report-subject');
        if (subject) subject.textContent = row.getAttribute('data-perihal') || row.querySelector('.subject')?.textContent.trim() || subject.textContent;
        updateProgressInsideRow(row);
        highlightRow(row);
    }

    function groupRows(html) {
        var temp = document.createElement('tbody');
        temp.innerHTML = html || '';
        var rows = Array.prototype.slice.call(temp.children).filter(function (el) { return el.matches('tr'); });
        var groups = {};
        rows.forEach(function (row) {
            var key = row.getAttribute('data-permintaan-id') || ('laporan-' + row.getAttribute('data-laporan-id'));
            if (!groups[key]) groups[key] = [];
            groups[key].push(row);
        });
        return Object.keys(groups).map(function (key) {
            var group = groups[key];
            group.sort(function (a, b) { return (Number(a.getAttribute('data-laporan-id')) || 0) - (Number(b.getAttribute('data-laporan-id')) || 0); });
            var latest = group[group.length - 1];
            var values = uniqueProgress(group.map(function (row) { return row.getAttribute('data-progres'); }));
            return { key: key, latest: latest, progress: values };
        });
    }

    function upsertRows(rowsBySatuan) {
        Object.keys(rowsBySatuan || {}).forEach(function (satuanId) {
            var section = document.getElementById('satlak-' + satuanId);
            if (!section) return;
            var cleanWrap = section.querySelector('.clean-table-wrap');
            if (!cleanWrap) return;

            groupRows(rowsBySatuan[satuanId]).forEach(function (item) {
                var row = item.latest;
                var permintaanId = row.getAttribute('data-permintaan-id');
                var laporanId = row.getAttribute('data-laporan-id');
                var details = findCurrentDetails(section, permintaanId, laporanId);

                if (!details) {
                    var list = cleanWrap.querySelector('.danpus-report-dropdown-list');
                    var tbody = cleanWrap.querySelector('tbody');
                    var newDetails = createDropdownForRow(row, item.progress);
                    if (list) list.insertBefore(newDetails, list.firstChild);
                    else if (tbody) {
                        var wrapper = document.createElement('div');
                        wrapper.className = 'danpus-report-dropdown-list';
                        wrapper.appendChild(newDetails);
                        tbody.closest('table')?.replaceWith(wrapper);
                        cleanWrap.dataset.dropdownReady = '1';
                    }
                    updateProgressInsideRow(row);
                    return;
                }

                var current = findCurrentRow(details, permintaanId, laporanId);
                var oldProgress = current ? current.getAttribute('data-progres') : null;
                var oldUpdated = current ? current.getAttribute('data-updated') : null;
                var changed = !current || oldProgress !== row.getAttribute('data-progres') || oldUpdated !== row.getAttribute('data-updated') || current.getAttribute('data-laporan-id') !== laporanId;

                // Satu perihal tetap satu dropdown. Riwayat persen bertambah
                // dan juga langsung terlihat di isi/alur "Laporan dibuat".
                setHistory(details, item.progress);
                if (changed) replaceCurrentRow(details, row);
                else updateProgressInsideRow(current);
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
            method: 'GET', credentials: 'same-origin', cache: 'no-store',
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
        })
        .catch(function () {})
        .finally(function () { polling = false; });
    }

    function scheduleNextPoll() {
        if (pollTimer) window.clearTimeout(pollTimer);
        pollTimer = window.setTimeout(function () { poll(); scheduleNextPoll(); }, 2000);
    }

    function start() {
        setTimeout(function () {
            if (window.siberadRefreshDanpusActivityDropdown) window.siberadRefreshDanpusActivityDropdown();
            poll();
            scheduleNextPoll();
        }, 120);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) { poll(); scheduleNextPoll(); } });
        window.addEventListener('focus', function () { poll(); scheduleNextPoll(); });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
</script>
<style>
#monitoring tr.is-realtime-updated,
[id^="satlak-"] tr.is-realtime-updated { animation: satlakRowRealtimeUpdated 1.8s ease; }
[id^="satlak-"] .danpus-report-dropdown.is-realtime-new { animation: satlakDropdownRealtimeNew 1.8s ease; }
.danpus-progress-flow { margin: 10px 0 12px; padding: 10px 12px; border: 1px solid rgba(59,130,246,.16); border-radius: 10px; background: rgba(59,130,246,.035); }
.danpus-progress-flow-label { font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 7px; opacity: .7; }
.danpus-progress-flow-list { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
.danpus-progress-flow-item { display: inline-flex; align-items: center; padding: 5px 9px; border-radius: 999px; font-size: 12px; font-weight: 700; border: 1px solid rgba(100,116,139,.18); background: #fff; }
.danpus-progress-flow-item.latest { border-color: rgba(16,185,129,.45); }
.danpus-progress-flow-item.is-progress-added { animation: danpusProgressAdded .65s ease; }
.danpus-progress-flow-arrow { opacity: .45; font-weight: 700; }
@keyframes danpusProgressAdded { 0% { transform: scale(.82); opacity: .2; } 65% { transform: scale(1.08); opacity: 1; } 100% { transform: scale(1); } }
@keyframes satlakRowRealtimeUpdated { 0% { background: rgba(245, 158, 11, .22); } 100% { background: transparent; } }
@keyframes satlakDropdownRealtimeNew { 0% { box-shadow: 0 0 0 2px rgba(59, 130, 246, .28); } 100% { box-shadow: none; } }
</style>
