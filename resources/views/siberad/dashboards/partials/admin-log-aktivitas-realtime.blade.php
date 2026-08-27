<script>
(function () {
  var tbody = document.getElementById('tblLogAktivitas')?.querySelector('tbody');
  if (!tbody) return;
  var url = @json(route('admin.laporan.aktivitas-terbaru'));

  var ids = Array.prototype.map.call(tbody.querySelectorAll('tr[data-log-id]'), function (tr) {
    return parseInt(tr.getAttribute('data-log-id'), 10);
  });
  var lastId = ids.length ? Math.max.apply(null, ids) : 0;

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str ?? '-';
    return div.innerHTML;
  }

  // Tab ini defaultnya nampilin rentang "kemarin-hari ini", tapi admin bisa
  // ganti ke rentang tanggal LAMPAU buat nelusuri histori -- kalau lagi
  // begitu, jangan sisipin aktivitas yang baru saja terjadi (itu di LUAR
  // rentang yang lagi dia lihat, malah bikin bingung).
  function tanggalLokalHariIni() {
    // toISOString() pakai UTC -- di WIB (UTC+7) tengah malam s/d jam 7 pagi,
    // itu masih nunjuk tanggal KEMARIN secara UTC walau sudah tanggal baru
    // secara lokal. Geser dulu pakai offset timezone browser biar tanggal
    // yang diambil beneran tanggal lokal, bukan UTC.
    var d = new Date();
    return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
  }

  function sedangLihatHariIni() {
    var sampai = document.getElementById('logSampaiInput');
    if (!sampai || !sampai.value) return true;
    return sampai.value >= tanggalLokalHariIni();
  }

  // Tab admin lain (display:none, BUKAN di-unmount) tidak perlu ikut nge-
  // fetch data yang tidak kelihatan -- hemat network/CPU selagi admin lagi
  // buka tab LAIN (mis. Dashboard), poll otomatis lanjut lagi begitu admin
  // balik ke tab ini (delay paling lama cuma 1 siklus interval).
  function tabIniAktif() {
    var panel = document.querySelector('[data-tab-panel="log-aktivitas"]');
    return !panel || panel.classList.contains('active');
  }

  function ambilAktivitasBaru() {
    if (!tabIniAktif() || !sedangLihatHariIni()) return;
    fetch(url + '?after_id=' + lastId, { headers: { Accept: 'application/json' } })
      .then(function (res) { return res.ok ? res.json() : null; })
      .then(function (data) {
        if (!data || !data.log || !data.log.length) return;

        Array.prototype.slice.call(tbody.querySelectorAll('tr')).forEach(function (r) {
          if (r.querySelector('.empty-state')) r.remove();
        });

        // API balikin urutan terbaru dulu; balik supaya yang paling baru
        // tetap tampil paling atas tabel secara kronologis.
        data.log.slice().reverse().forEach(function (l) {
          if (tbody.querySelector('[data-log-id="' + l.id + '"]')) return;
          var tr = document.createElement('tr');
          tr.setAttribute('data-log-id', l.id);
          tr.setAttribute('data-filter-value', l.kategori || '');
          tr.classList.add('siberad-row-in');
          tr.innerHTML =
            '<td style="white-space:nowrap;">' + escapeHtml(l.waktu) + '</td>' +
            '<td>' + escapeHtml(l.pengguna) + '</td>' +
            '<td><span class="badge">' + escapeHtml(l.aksi) + '</span></td>' +
            '<td style="color:var(--text-muted);">' + escapeHtml(l.deskripsi) + '</td>' +
            '<td style="color:var(--text-dim);">' + escapeHtml(l.ip) + '</td>';
          tbody.insertBefore(tr, tbody.firstChild);
        });

        // Tanpa ini, baris baru tetap kelihatan walau lagi ada kata kunci
        // pencarian/filter kategori aktif yang seharusnya menyembunyikannya.
        if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblLogAktivitas');

        lastId = data.max_id;
      })
      .catch(function () {});
  }

  setInterval(ambilAktivitasBaru, 5000);
})();
</script>
