<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sesi Berakhir — {{ ($pengaturan->hero_judul_awal ?? '') . ($pengaturan->hero_judul_aksen ?? 'SIBERAD') }}</title>
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo-pussiberad.jpg') }}">
<style>
  :root{
    --bg:#06090C; --bg-deep:#04070A; --panel:#11181F; --panel-alt:#0D141A;
    --border:rgba(217,146,11,.22); --border-strong:rgba(217,146,11,.42);
    --gold:#FF9800; --gold-bright:#FF9800;
    --text:#F5F1E8; --text-muted:#A9A39A;
    --display:'Rajdhani', sans-serif; --body:'Inter', sans-serif;
  }
  *{box-sizing:border-box;}
  body{
    margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
    background:radial-gradient(circle at 50% 20%, var(--bg) 0%, var(--bg-deep) 100%);
    color:var(--text); font-family:var(--body), sans-serif; padding:24px;
  }
  .card{
    max-width:440px; width:100%; text-align:center; background:var(--panel);
    border:1px solid var(--border); border-radius:16px; padding:40px 32px;
    box-shadow:0 25px 70px rgba(0,0,0,.45);
  }
  .code{
    font-family:var(--display), sans-serif; font-size:56px; font-weight:700;
    color:var(--gold-bright); letter-spacing:.04em; margin:0;
  }
  h1{
    font-family:var(--display), sans-serif; font-size:20px; font-weight:600;
    margin:8px 0 12px; color:var(--text);
  }
  p{ color:var(--text-muted); font-size:14px; line-height:1.6; margin:0 0 24px; }
  a.btn{
    display:inline-block; background:var(--gold); color:#171106; font-weight:700;
    text-decoration:none; padding:12px 28px; border-radius:10px; font-size:14px;
    letter-spacing:.02em; transition:background .15s ease;
  }
  a.btn:hover{ background:var(--gold-bright); }
  .hint{ margin-top:16px; font-size:12px; color:var(--text-muted); }
</style>
</head>
<body>
  <div class="card">
    <p class="code">419</p>
    <h1>Sesi Kamu Sudah Berakhir</h1>
    <p>Halaman ini terbuka terlalu lama atau sesi login sudah tidak valid. Silakan masuk kembali untuk melanjutkan.</p>
    <a class="btn" href="{{ url('/') }}">Masuk Kembali</a>
    <p class="hint">Kamu akan otomatis diarahkan dalam <span id="countdown">8</span> detik...</p>
  </div>
  <script>
    var seconds = 8;
    var el = document.getElementById('countdown');
    var timer = setInterval(function () {
      seconds -= 1;
      if (el) el.textContent = seconds;
      if (seconds <= 0) {
        clearInterval(timer);
        window.location.href = "{{ url('/') }}";
      }
    }, 1000);
  </script>
</body>
</html>
