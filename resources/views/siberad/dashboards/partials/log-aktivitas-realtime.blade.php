<script>
(function () {
    var endpoint = '{{ route('laporan.log-aktivitas.realtime') }}';
    var polling = false;
    var initialRenderComplete = false;

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function statusClass(status) {
        var value = String(status || '').toLowerCase();
        if (value.indexOf('tolak') !== -1) return 'bad';
        if (value.indexOf('setuj') !== -1 || value.indexOf('diterima') !== -1) return 'ok';
        if (value.indexOf('revisi') !== -1) return 'revisi';
        if (value.indexOf('progres') !== -1 || value.indexOf('sedang') !== -1) return 'blue';
        return 'wait';
    }

    function statusLabel(item) {
        if (item && item.is_progres) return 'Progres · ' + item.progres + '%';
        return item.status || '-';
    }

    function highlightRow(row, className) {
        row.classList.remove('is-realtime-new', 'is-realtime-updated');
        row.classList.add(className);
        window.setTimeout(function () {
            row.classList.remove(className);
        }, 1800);
    }

    function upsertRows(rowsBySatuan, items) {
        var changedMeta = {};
        (items || []).forEach(function (item) {
            changedMeta[String(item.laporan_id)] = item;
        });

        Object.keys(rowsBySatuan || {}).forEach(function (satuanId) {
            var tbody = document.getElementById('satlakLaporanBody-' + satuanId);
            if (!tbody) return;

            var emptyRow = document.getElementById('satlakLaporanEmpty-' + satuanId);
            if (emptyRow) emptyRow.remove();

            var temp = document.createElement('tbody');
            temp.innerHTML = rowsBySatuan[satuanId] || '';
            var rows = Array.prototype.slice.call(temp.children);

            rows.forEach(function (row) {
                var laporanId = row.getAttribute('data-laporan-id');
                if (!laporanId) return;

                var existing = tbody.querySelector('tr[data-laporan-id="' + laporanId + '"]');
                var changed = !!existing && existing.getAttribute('data-updated') !== row.getAttribute('data-updated');
                var isNew = !existing;

                if (!existing && !initialRenderComplete) {
                    return;
                }

                if (!existing) {
                    row.classList.add('is-realtime-new');
                    tbody.insertBefore(row, tbody.firstChild);
                    window.setTimeout(function () {
                        row.classList.remove('is-realtime-new');
                    }, 1800);
                    return;
                }

                if (!changed) return;

                var oldProgress = existing.getAttribute('data-progres');
                var newProgress = row.getAttribute('data-progres');
                var oldStatus = existing.getAttribute('data-laporan-status');
                var meta = changedMeta[String(laporanId)] || {};

                existing.replaceWith(row);
                highlightRow(row, 'is-realtime-updated');

                if (initialRenderComplete && String(oldProgress) !== String(newProgress) && meta.is_progres) {
                    var sender = row.getAttribute('data-satuan-nama') || 'Satuan';
                    var subject = row.getAttribute('data-perihal') || meta.perihal || 'Laporan';
                    if (window.siberadShowToast) {
                        window.siberadShowToast('success', 'Progres ' + newProgress + '% masuk dari ' + sender + ': ' + subject);
                    }
                } else if (initialRenderComplete && oldStatus !== row.getAttribute('data-laporan-status') && window.siberadShowToast) {
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

        fetch(endpoint + '?since=0&realtime=1', {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
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
        .finally(function () {
            polling = false;
        });
    }

    function start() {
        if (!document.querySelector('[id^="satlakLaporanBody-"]')) return;
        poll();
        window.setInterval(poll, 2000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
<style>
#monitoring tr.is-realtime-updated,
[id^="satlak-"] tr.is-realtime-updated {
    animation: satlakRowRealtimeUpdated 1.8s ease;
}
#monitoring tr.is-realtime-new,
[id^="satlak-"] tr.is-realtime-new {
    animation: satlakRowRealtimeNew 1.8s ease;
}
@keyframes satlakRowRealtimeUpdated {
    0% { background: rgba(245, 158, 11, .22); }
    100% { background: transparent; }
}
@keyframes satlakRowRealtimeNew {
    0% { background: rgba(59, 130, 246, .18); }
    100% { background: transparent; }
}
</style>
