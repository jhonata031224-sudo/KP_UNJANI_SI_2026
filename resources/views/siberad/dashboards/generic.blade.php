<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — SIBERAD</title>
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo-pussiberad.jpg') }}">
@include('siberad.dashboards.partials.dash-styles')
<style>
  main{max-width:760px;margin:56px auto;padding:0 24px;position:relative;z-index:1;}
  header.topbar-simple{
    background:rgba(6,20,13,.82);backdrop-filter:blur(12px);border-bottom:1px solid var(--border-soft);
    padding:18px 32px;display:flex;justify-content:space-between;align-items:center;
  }
  header.topbar-simple .logo{font-family:var(--display);font-weight:700;font-size:18px;letter-spacing:.03em;text-transform:uppercase;display:flex;align-items:center;gap:11px;}
  header.topbar-simple .logo img{width:34px;height:34px;border-radius:50%;object-fit:cover;border:1px solid var(--border-strong);}
  header.topbar-simple .logo span{color:var(--gold-bright);}
  form.logout button{width:auto;padding:9px 16px;}
  .card{padding:34px;}
  h1{font-family:var(--display);font-size:28px;font-weight:700;margin-bottom:6px;letter-spacing:.01em;}
  .meta{color:var(--text-muted);font-size:14px;margin-bottom:8px;line-height:1.7;}
</style>
</head>
<body>
  <header class="topbar-simple">
    <div class="logo"><img src="{{ asset('images/logo-pussiberad.jpg') }}" alt="Lambang Pussiberad">SIBER<span>AD</span></div>
    <form class="logout" method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit">Keluar</button>
    </form>
  </header>

  <main>
    @include('siberad.dashboards.partials.pengumuman-banner')
    <div class="card hud-panel">
      <div class="eyebrow">SIBERAD // DASHBOARD</div>
      <h1 style="margin-top:14px;">Selamat datang, {{ $user->name }}</h1>
      <span class="badge">{{ $satuan?->kategori ?? '—' }}</span>
      <p class="meta" style="margin-top:14px;">
        Anda login sebagai <strong style="color:var(--text);">{{ $satuan?->nama ?? 'Tidak diketahui' }}</strong>
        ({{ $satuan?->kode }}) &middot; Jabatan: {{ $user->jabatan ?? '-' }}
      </p>

      <div class="stat-grid" style="margin-top:24px;">
        <div class="stat-card">
          <div class="lbl">Satuan</div>
          <div class="val" style="font-size:16px;">{{ $satuan?->nama ?? '-' }}</div>
        </div>
        <div class="stat-card">
          <div class="lbl">Kategori</div>
          <div class="val" style="font-size:16px;text-transform:capitalize;">{{ $satuan?->kategori ?? '-' }}</div>
        </div>
        <div class="stat-card">
          <div class="lbl">NRP / Username</div>
          <div class="val" style="font-size:16px;">{{ $user->username }}</div>
        </div>
        <div class="stat-card">
          <div class="lbl">Jabatan</div>
          <div class="val" style="font-size:16px;">{{ $user->jabatan ?? '-' }}</div>
        </div>
      </div>

      <p class="meta" style="margin-top:26px;margin-bottom:0;">
        Halaman peran untuk satuan ini belum dibuat secara khusus — modul laporan, verifikasi
        berjenjang, dan visualisasi akan dikembangkan menyusul (mengikuti DANPUS, WADAN,
        Satuan Pelaksanaan Penangkalan, dan Satuan Pelaksanaan Siber Sosial yang sudah tersedia).
      </p>
    </div>
  </main>
</body>
</html>
