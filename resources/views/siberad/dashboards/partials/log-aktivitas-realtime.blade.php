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

    function highlightRow(row, className) {
        row.classList.remove('is-realtime-new', 'is-realtime-updated');
        row.classList.add(className);
        window.setTimeout(function () { row.classList.remove(className); }, 1800);
    }

    function findCurrentRow(section, laporanId) {
        if (!section) return null;
        return section.querySelector('tr[data-laporan-id="' + laporanId + '"]');
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
        subject.textContent = row.getAttribute('data-perihal') || row.querySelector('.subject')?.textContent.trim() || 'Laporan tanpa perihal';
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

    function replaceInsideDropdown(oldRow, newRow) {
        var details = oldRow.closest('.danpus-report-dropdown');
        if (!details) return false;
        newRow.classList.add('is-realtime-updated');
        oldRow.replaceWith(newRow);
        var subject = details.querySelector('.danpus-report-subject');
        if (subject) subject.textContent = newRow.getAttribute('data-perihal') || newRow.querySelector('.subject')?.textContent.trim() || 'Laporan tanpa perihal';
        highlightRow(newRow, 'is-realtime-updated');
        return true;
    }

    function upsertRows(rowsBySatuan, items) {
        var changedMeta = {};
        (items || []).forEach(function (item) {
            changedMeta[String(item.laporan_id)] = item;
        });

        Object.keys(rowsBySatuan || {}).forEach(function (satuanId) {
            var section = document.getElementById('satlak-' + satuanId);
            if (!section) return;

            var cleanWrap = section.querySelector('.clean-table-wrap');
            var hasDropdown = !!cleanWrap?.querySelector('.danpus-report-dropdown');

            var temp = document.createElement('tbody');
            temp.innerHTML = rowsBySatuan[satuanId] || '';
            var rows = Array.prototype.slice.call(temp.children);

            rows.forEach(function (row) {
                var laporanId = row.getAttribute('data-laporan-id');
                if (!laporanId) return;

                var existing = findCurrentRow(section, laporanId);
                var meta = changedMeta[String(laporanId)] || {};
                var oldProgress = existing ? existing.getAttribute('data-progres') : null;
                var newProgress = row.getAttribute('data-progres');
                var oldStatus = existing ? existing.getAttribute('data-laporan-status') : null;
                var newStatus = row.getAttribute('data-laporan-status');
                var oldUpdated = existing ? existing.getAttribute('data-updated') : null;
                var newUpdated = row.getAttribute('data-updated');
                var oldKendala = existing ? existing.getAttribute('data-kendala') : null;
                var newKendala = row.getAttribute('data-kendala');

                var changed = !!existing && (
                    oldUpdated !== newUpdated ||
                    String(oldProgress) !== String(newProgress) ||
                    oldStatus !== newStatus ||
                    oldKendala !== newKendala
                );

                if (!existing && !initialRenderComplete) return;

                if (!existing) {
                    if (hasDropdown && cleanWrap) {
                        var list = cleanWrap.querySelector('.danpus-report-dropdown-list');
                        if (list) {
                            list.insertBefore(createDropdownForRow(row), list.firstChild);
                            return;
                        }
                    }
                    var tbody = cleanWrap?.querySelector('tbody');
                    if (tbody) {
                        row.classList.add('is-realtime-new');
                        tbody.insertBefore(row, tbody.firstChild);
                    }
                    return;
                }

                if (!changed) return;

                if (replaceInsideDropdown(existing, row)) {
                    // dropdown version handled above
                } else {
                    existing.replaceWith(row);
                    highlightRow(row, 'is-realtime-updated');
                }

                if (initialRenderComplete && String(oldProgress) !== String(newProgress) && meta.is_progres) {
                    var sender = row.getAttribute('data-satuan-nama') || 'Satuan';
                    var subjectText = row.getAttribute('data-perihal') || meta.perihal || 'Laporan';
                    if (window.siberadShowToast) window.siberadShowToast('success', 'Progres ' + newProgress + '% masuk dari ' + sender + ': ' + subjectText);
                } else if (initialRenderComplete && oldStatus !== newStatus && window.siberadShowToast) {
                    var senderStatus = row.getAttribute('data-satuan-nama') || 'Satuan';
                    var subjectStatus = row.getAttribute('data-perihal') || meta.perihal || 'Laporan';
                    window.siberadShowToast('success', 'Status laporan diperbarui dari ' + senderStatus + ': ' + subjectStatus);
                }
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
            upsertRows(data.rows || {}, data.items || []);
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
[id^="satlak-"] tr.is-realtime-updated { animation: satlakRowRealtimeUpdated 1.8s ease; }
#monitoring tr.is-realtime-new,
[id^="satlak-"] tr.is-realtime-new { animation: satlakRowRealtimeNew 1.8s ease; }
@keyframes satlakRowRealtimeUpdated { 0% { background: rgba(245, 158, 11, .22); } 100% { background: transparent; } }
@keyframes satlakRowRealtimeNew { 0% { background: rgba(59, 130, 246, .18); } 100% { background: transparent; } }
</style>