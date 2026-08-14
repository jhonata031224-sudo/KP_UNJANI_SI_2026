<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Pengguna & Aktivitas — {{ $pengaturan->singkatan ?? 'SIBERAD' }}</title>
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0c1210;color:#e7efe9;margin:0;padding:32px;}
  h1{font-size:20px;margin:0 0 4px;}
  p.sub{color:#9fb0a6;font-size:13px;margin:0 0 24px;}
  .toolbar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;align-items:end;}
  .toolbar form{display:flex;gap:10px;flex-wrap:wrap;align-items:end;}
  .field{display:flex;flex-direction:column;gap:4px;font-size:12px;}
  input,button,a.btn{font-family:inherit;font-size:12.5px;}
  input{background:#111a17;border:1px solid #24352d;color:#e7efe9;border-radius:6px;padding:8px 10px;}
  .btn{display:inline-flex;align-items:center;gap:6px;background:#1c2c25;border:1px solid #2c4438;color:#e7efe9;border-radius:6px;padding:8px 14px;text-decoration:none;cursor:pointer;}
  .btn.primary{background:#d4a94f;color:#1a1206;border-color:#d4a94f;font-weight:600;}
  table{width:100%;border-collapse:collapse;margin-top:8px;font-size:12.5px;}
  th,td{text-align:left;padding:9px 10px;border-bottom:1px solid #202f27;}
  th{color:#9fb0a6;font-weight:600;text-transform:uppercase;font-size:10.5px;letter-spacing:.04em;}
  .panel{background:#111a17;border:1px solid #202f27;border-radius:10px;padding:18px;margin-bottom:24px;}

  /* Dropdown pilihan jenis (Daftar Pengguna / Log Aktivitas) untuk tombol
     Export & Cetak, supaya 1 tombol tapi hasilnya tetap dokumen terpisah. */
  .dropdown{position:relative;display:inline-block;}
  .dropdown-menu{display:none;position:absolute;top:calc(100% + 6px);left:0;min-width:200px;background:#111a17;border:1px solid #2c4438;border-radius:8px;padding:6px;box-shadow:0 12px 28px -10px rgba(0,0,0,.55);z-index:20;}
  .dropdown.open .dropdown-menu{display:block;}
  .dropdown-menu a{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:6px;color:#e7efe9;text-decoration:none;font-size:12.5px;}
  .dropdown-menu a:hover{background:#1c2c25;}
  .dropdown-menu .dropdown-label{padding:6px 10px 2px;font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#9fb0a6;}
</style>
</head>
<body>
  <h1>Laporan Pengguna &amp; Aktivitas</h1>
  <p class="sub">{{ $pengaturan->nama_instansi }} — SIBERAD</p>

  <div class="toolbar">
    <div class="dropdown" data-dropdown>
      <button type="button" class="btn primary" data-dropdown-toggle>Export Excel ▾</button>
      <div class="dropdown-menu">
        <div class="dropdown-label">Pilih data</div>
        <a href="{{ route('admin.laporan.export-pengguna') }}">👤 Daftar Pengguna</a>
        <a href="{{ route('admin.laporan.export-aktivitas') }}">📋 Log Aktivitas</a>
      </div>
    </div>
    <div class="dropdown" data-dropdown>
      <button type="button" class="btn" data-dropdown-toggle>Cetak / Simpan PDF ▾</button>
      <div class="dropdown-menu">
        <div class="dropdown-label">Pilih data</div>
        <a href="{{ route('admin.laporan.cetak', 'pengguna') }}" target="_blank">👤 Daftar Pengguna</a>
        <a href="{{ route('admin.laporan.cetak', 'aktivitas') }}" target="_blank">📋 Log Aktivitas</a>
      </div>
    </div>
    <form method="GET" action="{{ route('admin.laporan.index') }}">
      <div class="field"><label>Dari</label><input type="date" name="dari" value="{{ $dari }}"></div>
      <div class="field"><label>Sampai</label><input type="date" name="sampai" value="{{ $sampai }}"></div>
      <button class="btn" type="submit">Terapkan</button>
    </form>
  </div>

  <div class="panel">
    <h3>Daftar Pengguna ({{ $semuaPengguna->count() }})</h3>
    <table>
      <thead><tr><th>Nama</th><th>Username</th><th>Satuan</th><th>Jabatan</th></tr></thead>
      <tbody>
        @foreach($semuaPengguna as $u)
        <tr><td>{{ $u->name }}</td><td>{{ $u->username }}</td><td>{{ $u->satuan->nama ?? '-' }}</td><td>{{ $u->jabatan ?? '-' }}</td></tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="panel">
    <h3>
      Log Aktivitas (<span id="log-count">{{ $log->count() }}</span>)
      <span id="live-indicator" style="display:inline-flex;align-items:center;gap:5px;font-size:10.5px;font-weight:600;color:#7fd99a;margin-left:10px;vertical-align:middle;">
        <span style="width:7px;height:7px;border-radius:50%;background:#7fd99a;display:inline-block;animation:pulse-live 1.5s infinite;"></span>
        LIVE
      </span>
    </h3>
    <table>
      <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Deskripsi</th><th>IP</th></tr></thead>
      <tbody id="log-tbody">
        @foreach($log as $l)
        <tr data-log-id="{{ $l->id }}">
          <td>{{ $l->created_at?->translatedFormat('d M Y H:i:s') }}</td>
          <td>{{ $l->nama_pengguna ?? '-' }}</td>
          <td>{{ $l->aksi }}</td>
          <td>{{ $l->deskripsi }}</td>
          <td>{{ $l->ip_address }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <style>
    @keyframes pulse-live { 0%,100%{opacity:1;} 50%{opacity:.25;} }
    tr.log-baru { animation: highlight-baru 2.5s ease-out; }
    @keyframes highlight-baru { 0%{background:#2c4438;} 100%{background:transparent;} }
  </style>
  <script>
    (function () {
      // Dropdown pilihan jenis data untuk tombol Export & Cetak.
      document.querySelectorAll('[data-dropdown]').forEach(function (dd) {
        var toggle = dd.querySelector('[data-dropdown-toggle]');
        toggle.addEventListener('click', function (e) {
          e.stopPropagation();
          var sudahTerbuka = dd.classList.contains('open');
          document.querySelectorAll('[data-dropdown].open').forEach(function (o) { o.classList.remove('open'); });
          if (!sudahTerbuka) dd.classList.add('open');
        });
      });
      document.addEventListener('click', function () {
        document.querySelectorAll('[data-dropdown].open').forEach(function (o) { o.classList.remove('open'); });
      });
    })();
  </script>
  <script>
    (function () {
      var tbody = document.getElementById('log-tbody');
      var countEl = document.getElementById('log-count');
      var url = @json(route('admin.laporan.aktivitas-terbaru'));

      var ids = Array.from(tbody.querySelectorAll('tr[data-log-id]')).map(function (tr) {
        return parseInt(tr.getAttribute('data-log-id'), 10);
      });
      var lastId = ids.length ? Math.max.apply(null, ids) : 0;

      function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str ?? '-';
        return div.innerHTML;
      }

      function ambilAktivitasBaru() {
        fetch(url + '?after_id=' + lastId, { headers: { 'Accept': 'application/json' } })
          .then(function (res) { return res.ok ? res.json() : null; })
          .then(function (data) {
            if (!data || !data.log || !data.log.length) return;

            // API mengembalikan urutan terbaru dulu; balik supaya yang paling
            // baru tetap tampil paling atas tabel secara kronologis.
            data.log.slice().reverse().forEach(function (l) {
              var tr = document.createElement('tr');
              tr.setAttribute('data-log-id', l.id);
              tr.classList.add('log-baru');
              tr.innerHTML =
                '<td>' + escapeHtml(l.waktu) + '</td>' +
                '<td>' + escapeHtml(l.pengguna) + '</td>' +
                '<td>' + escapeHtml(l.aksi) + '</td>' +
                '<td>' + escapeHtml(l.deskripsi) + '</td>' +
                '<td>' + escapeHtml(l.ip) + '</td>';
              tbody.insertBefore(tr, tbody.firstChild);
            });

            lastId = data.max_id;
            countEl.textContent = tbody.querySelectorAll('tr[data-log-id]').length;
          })
          .catch(function () { /* diamkan saja kalau sempat gagal sekali poll, coba lagi nanti */ });
      }

      setInterval(ambilAktivitasBaru, 5000);
    })();
  </script>
</body>
</html>
