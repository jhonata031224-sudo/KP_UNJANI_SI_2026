<script>
(function () {
    var endpoint = '{{ route('laporan.log-aktivitas.realtime') }}';
    var lastSeen = 0;
    var polling = false;
    var initial = true;

    function existingLatestId() {
        var max = 0;
        document.querySelectorAll('[id^="satlakLaporanBody-"] tr[data-laporan-id]').forEach(function (tr) {
            var id = parseInt(tr.getAttribute('data-laporan-id') || '0', 10);
            if (id > max) max = id;
        });
        return max;
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function insertRows(satuanId, html) {
        if (!html) return;
        var tbody = document.getElementById('satlakLaporanBody-' + satuanId);
        if (!tbody) return;
        var emptyRow = document.getElementById('satlakLaporanEmpty-' + satuanId);
        if (emptyRow) emptyRow.remove();
        var temp = document.createElement('tbody');
        temp.innerHTML = html;
        var rows = Array.prototype.slice.call(temp.children);
        rows.reverse().forEach(function (row) {
            row.classList.add('is-realtime-new');
            tbody.insertBefore(row, tbody.firstChild);
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

    function poll(isInitial) {
        if (polling) return;
        polling = true;
        var since = isInitial ? 0 : lastSeen;
        fetch(endpoint + '?since=' + encodeURIComponent(since), {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (response.status === 401) {
                if (window.siberadTampilkanSesiBerakhir) window.siberadTampilkanSesiBerakhir();
                return null;
            }
            if (response.status === 419 || !response.ok) return null;
            return response.json();
        }).then(function (data) {
            if (!data) return;

            if (!isInitial && data.items && data.items.length) {
                Object.keys(data.rows || {}).forEach(function (satuanId) {
                    insertRows(satuanId, data.rows[satuanId]);
                });
            }

            updateStats(data.stats);
            setText('kpiTotalLaporan', data.total_laporan);
            setText('kpiDisetujuiLaporan', data.total_disetujui);
            setText('kpiDitolakLaporan', data.total_ditolak);

            if (!isInitial && window.siberadShowToast) {
                (data.items || []).forEach(function (item) {
                    var pesan = item.is_progres
                        ? 'Progres ' + item.progres + '% masuk dari ' + item.satuan_nama + ': ' + item.perihal
                        : 'Laporan baru dari ' + item.satuan_nama + ': ' + item.perihal;
                    window.siberadShowToast('success', pesan);
                });
            }

            lastSeen = Math.max(lastSeen, parseInt(data.latest_id || 0, 10));
            if (isInitial) lastSeen = Math.max(lastSeen, existingLatestId());
            initial = false;
        }).catch(function () {}).finally(function () {
            polling = false;
        });
    }

    function start() {
        if (!document.querySelector('[id^="satlakLaporanBody-"]')) return;
        poll(true);
        window.setInterval(function () { poll(false); }, 3000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
<style>
#monitoring tr.is-realtime-new, [id^="satlak-"] tr.is-realtime-new { animation: satlakRowHighlight 1.8s ease; }
@keyframes satlakRowHighlight {
    0% { background: rgba(59,130,246,.18); }
    100% { background: transparent; }
}
</style>
