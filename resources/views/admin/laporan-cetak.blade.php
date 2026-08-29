<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $jenis === 'pengguna' ? 'Cetak Laporan Pengguna' : 'Cetak Riwayat Aktivitas' }} — {{ ($pengaturan->hero_judul_awal ?? '') . ($pengaturan->hero_judul_aksen ?? 'SIBERAD') }}</title>
<style>
  body{font-family:Georgia,'Times New Roman',serif;color:#111;margin:36px;}
  header{display:flex;align-items:center;gap:14px;border-bottom:2px solid #111;padding-bottom:12px;margin-bottom:18px;}
  header img{height:48px;}
  header h1{font-size:16px;margin:0;}
  header p{font-size:11px;margin:2px 0 0;color:#444;}
  h2{font-size:13px;border-bottom:1px solid #999;padding-bottom:4px;margin-top:26px;}
  table{width:100%;border-collapse:collapse;font-size:10.5px;margin-top:8px;}
  th,td{border:1px solid #999;padding:5px 7px;text-align:left;}
  th{background:#eee;}
  footer{margin-top:30px;font-size:10px;color:#555;}
  .tbl-scroll-wrap{overflow-x:auto;}
  @media print{ .no-print{display:none;} }
  @media screen and (max-width:600px){ body{margin:16px;} header img{height:36px;} }
</style>
</head>
<body>
  <button class="no-print" onclick="window.print()" style="float:right;">Cetak / Simpan PDF</button>
  <header>
    @if($pengaturan->logo_path)
      <img src="{{ asset('storage/'.$pengaturan->logo_path) }}" alt="Logo">
    @endif
    <div>
      <h1>{{ $pengaturan->nama_instansi }}</h1>
      <p>{{ $jenis === 'pengguna' ? 'Laporan Daftar Pengguna Sistem SIBERAD' : 'Laporan Riwayat Aktivitas Sistem SIBERAD' }}</p>
    </div>
  </header>

  @if($jenis === 'pengguna')
  <h2>Daftar Pengguna ({{ $semuaPengguna->count() }})</h2>
  <div class="tbl-scroll-wrap">
  <table>
    <thead><tr><th>#</th><th>Nama</th><th>Username</th><th>Satuan</th><th>Jabatan</th></tr></thead>
    <tbody>
      @foreach($semuaPengguna as $i => $u)
      <tr><td>{{ $i + 1 }}</td><td>{{ $u->name }}</td><td>{{ $u->username }}</td><td>{{ $u->satuan->nama ?? '-' }}</td><td>{{ $u->jabatan ?? '-' }}</td></tr>
      @endforeach
    </tbody>
  </table>
  </div>
  @else
  <h2>Riwayat Aktivitas ({{ $log->count() }})</h2>
  <div class="tbl-scroll-wrap">
  <table>
    <thead><tr><th>Waktu</th><th>Pengguna</th><th>Satuan</th><th>Aksi</th><th>Deskripsi</th></tr></thead>
    <tbody>
      @foreach($log as $l)
      <tr><td>{{ $l->created_at?->format('d/m/Y H:i') }}</td><td>{{ $l->nama_pengguna ?? '-' }}</td><td>{{ $l->user?->satuan?->nama ?? '-' }}</td><td>{{ $l->aksi }}</td><td>{{ $l->deskripsi }}</td></tr>
      @endforeach
    </tbody>
  </table>
  </div>
  @endif

  <footer>Dicetak oleh {{ $dicetakOleh->name }} pada {{ $dicetakPada->translatedFormat('d M Y H:i') }} WIB.</footer>
</body>
</html>
