{{-- resources/views/welcome.blade.php --}}
{{-- Halaman landing SIBERAD — Sistem Informasi Berbasis Elektronik Angkatan Darat (PUSSIBERAD) --}}
@php
  $lp = $pengaturan->landingConfig();
  // Sengaja TIDAK fallback ke logo/gambar bawaan (images/logo-pussiberad.jpg,
  // images/hero-lapangan-mabesad.jpg) lagi -- kalau Admin menghapus logo/
  // gambar latar beranda di Pengaturan Umum, landing page ini HARUS ikut
  // kosong (konsisten dengan kotak "Belum ada logo"/"Belum ada gambar latar
  // belakang" yang tampil di panel admin), bukan diam-diam balik ke gambar
  // default seolah-olah belum dihapus.
  $lpLogoUrl = $pengaturan->logo_path ? asset('storage/'.$pengaturan->logo_path) : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $lp['meta']['title'] }}</title>
<meta name="description" content="{{ $lp['meta']['description'] }}">
@if ($lpLogoUrl)
<link rel="icon" type="image/jpeg" href="{{ $lpLogoUrl }}">
<link rel="preload" as="image" href="{{ $lpLogoUrl }}" fetchpriority="high">
@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#151e19;
    --bg-deep:#0d120f;
    --panel:#222f29;
    --panel-2:#2b3b33;
    --panel-alt:#1b2721;
    --border:rgba(212,175,55,.22);
    --border-soft:rgba(212,175,55,.13);
    --border-strong:rgba(212,175,55,.42);
    --gold:#EEA23A;
    --gold-bright:#EEA23A;
    --gold-dim:rgba(238,162,58,.14);
    --green:#2f9e63;
    --green-bright:#3fc27d;
    --green-dim:rgba(63,194,125,.14);
    --success:#2f9e63;
    --success-bright:#3fc27d;
    --success-dim:rgba(63,194,125,.14);
    --red:#b5342f;
    --red-dim:rgba(181,52,47,.16);
    --text:#f4f1e6;
    --text-muted:#9fb3a5;
    --text-dim:#6f8577;
    --display:'Rajdhani', sans-serif;
    --mono:'JetBrains Mono', monospace;
    --body:'Inter', sans-serif;

    --header-bg:rgba(13,18,15,.82);
    --overlay-bg:rgba(9,13,11,.72);
    --hero-ov-1:rgba(13,18,15,.97);
    --hero-ov-2:rgba(13,18,15,.93);
    --hero-ov-3:rgba(13,18,15,.72);
    --hero-ov-4:rgba(13,18,15,.9);
    --hero-ov-top:rgba(13,18,15,1);
    --hero-ov-top-fade:rgba(13,18,15,0);
    --chip-bg:transparent;
    --chip-border:transparent;
    --chip-shadow:none;
  }

  html[data-theme="light"]{
    --bg:#f3efe1;
    --bg-deep:#e9e2cd;
    --panel:#ffffff;
    --panel-2:#fffdf7;
    --panel-alt:#f6f1e2;
    --border:rgba(150,110,20,.28);
    --border-soft:rgba(150,110,20,.16);
    --border-strong:rgba(150,110,20,.48);
    --gold:#EEA23A;
    --gold-bright:#EEA23A;
    --gold-dim:rgba(238,162,58,.16);
    --green:#1f7a48;
    --green-bright:#166238;
    --green-dim:rgba(31,122,72,.14);
    --success:#1f7a48;
    --success-bright:#166238;
    --success-dim:rgba(31,122,72,.14);
    --red:#b5342f;
    --red-dim:rgba(181,52,47,.12);
    --text:#1b2620;
    --text-muted:#465a4d;
    --text-dim:#6c7d70;

    --header-bg:rgba(255,253,247,.86);
    --overlay-bg:rgba(233,226,205,.78);
    --hero-ov-1:rgba(243,239,225,.96);
    --hero-ov-2:rgba(243,239,225,.9);
    --hero-ov-3:rgba(243,239,225,.55);
    --hero-ov-4:rgba(243,239,225,.9);
    --hero-ov-top:rgba(243,239,225,1);
    --hero-ov-top-fade:rgba(243,239,225,0);
    --chip-bg:rgba(255,253,247,.72);
    --chip-border:rgba(150,110,20,.18);
    --chip-shadow:0 3px 12px rgba(0,0,0,.07);
  }

  *{margin:0;padding:0;box-sizing:border-box;}

  html{scroll-behavior:smooth;}

  body{
    background:var(--bg);
    color:var(--text);
    font-family:var(--body);
    overflow-x:hidden;
    -webkit-font-smoothing:antialiased;
    background-image:
      radial-gradient(ellipse 90% 55% at 50% -10%, rgba(63,194,125,.10), transparent 60%),
      radial-gradient(ellipse 60% 40% at 100% 20%, rgba(212,175,55,.07), transparent 60%);
    background-attachment:fixed;
  }

  body.is-loading{overflow:hidden;height:100vh;}

  ::selection{background:var(--gold);color:var(--bg-deep);}

  /* subtle hex/topographic texture overlay */
  body::before{
    content:"";
    position:fixed;inset:0;z-index:0;pointer-events:none;
    opacity:.5;
    background-image:
      linear-gradient(rgba(212,175,55,.035) 1px, transparent 1px),
      linear-gradient(90deg, rgba(212,175,55,.035) 1px, transparent 1px);
    background-size:42px 42px;
    mask-image:radial-gradient(ellipse 70% 60% at 50% 0%, #000 0%, transparent 75%);
  }

  /* ================= LOADER ================= */
  #loader{
    position:fixed;inset:0;z-index:100;
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:26px;
    overflow:hidden;
    background:transparent;
  }
  #loader.split{pointer-events:none;}
  #loader.hidden{display:none;}
  #loader.scan .loader-caption{opacity:0;transition:opacity .3s ease;}

  .loader-panel{
    position:absolute;top:0;bottom:0;width:50%;
    background:var(--bg-deep);z-index:1;
    transition:transform .85s cubic-bezier(.76,0,.24,1);
    will-change:transform;
  }
  .loader-panel-left{left:0;}
  .loader-panel-right{right:0;}
  #loader.split .loader-panel-left{transform:translateX(-100%);}
  #loader.split .loader-panel-right{transform:translateX(100%);}

  .loader-scanline{
    position:absolute;left:0;right:0;top:50%;height:2px;z-index:3;
    background:linear-gradient(90deg, transparent, var(--gold), transparent);
    box-shadow:0 0 24px 2px var(--gold-dim);
    opacity:0;transform:translateY(-50%) scaleX(0);
  }
  #loader.scan .loader-scanline{ animation:scanFlash .55s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes scanFlash{
    0%{opacity:0;transform:translateY(-50%) scaleX(0);}
    45%{opacity:1;transform:translateY(-50%) scaleX(1);}
    100%{opacity:0;transform:translateY(-50%) scaleX(1);}
  }

  .mark{
    position:relative;width:clamp(220px, 34vw, 370px);height:clamp(220px, 34vw, 370px);z-index:2;
    transition:opacity .4s ease, transform .4s cubic-bezier(.4,0,.2,1), filter .4s ease;
  }
  .mark.leaving{opacity:0;transform:scale(1.18);filter:blur(5px) brightness(1.5);}

  .mark-plate{
    position:absolute;inset:clamp(24px, 4vw, 40px);
    border-radius:50%;
    background:var(--panel);
    border:1px solid var(--border-strong);
    display:flex;align-items:center;justify-content:center;
    overflow:hidden;
    box-shadow:0 0 0 1px rgba(0,0,0,.3) inset, 0 8px 30px rgba(0,0,0,.5);
  }
  .mark-plate img{width:100%;height:100%;object-fit:cover;}

  .mark-ring{
    position:absolute;inset:0;
    transform-origin:50% 50%;
    animation:ringspin 1.1s linear infinite;
  }
  .mark-ring svg{width:100%;height:100%;display:block;}
  .ring-track{
    fill:none;stroke:var(--border-soft);stroke-width:2.5;
  }
  .ring-arc{
    fill:none;stroke:var(--gold);stroke-width:2.5;stroke-linecap:round;
    stroke-dasharray:100 296.6;
  }
  @keyframes ringspin{ to{ transform:rotate(360deg); } }
  .loader-caption{
    font-family:var(--mono);font-size:11px;letter-spacing:.28em;color:var(--text-dim);
    text-transform:uppercase;
  }

  /* ================= LAYOUT / UTIL ================= */
  .wrap{max-width:1360px;margin:0 auto;padding:0 32px;position:relative;z-index:1;}
  section{position:relative;z-index:1;}

  .eyebrow{
    font-family:var(--mono);
    font-size:12px;letter-spacing:.18em;color:var(--gold-bright);
    text-transform:uppercase;
    display:flex;align-items:center;gap:10px;
  }

  .hud-panel{
    position:relative;background:linear-gradient(180deg, rgba(255,255,255,.02), transparent), var(--panel);
    border:1px solid var(--border-soft);border-radius:12px;
    box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25);
  }
  .hud-panel::before{
    content:"";position:absolute;top:0;left:14px;right:14px;height:1px;
    background:linear-gradient(90deg, transparent, var(--border-strong), transparent);
  }

  .moto-desc{
    color:var(--text-muted);line-height:1.85;font-size:15.5px;max-width:780px;
    padding-left:18px;border-left:2px solid var(--gold-bright);
  }

  /* ================= NAV ================= */
  header{
    position:sticky;top:0;z-index:20;
    background:var(--header-bg);backdrop-filter:blur(12px);
    border-bottom:1px solid var(--border-soft);
  }
  nav{
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 0;
    position:relative;
  }
  .logo{
    display:flex;align-items:center;gap:12px;color:var(--text);text-decoration:none;
  }
  .logo-badge{
    width:42px;height:42px;border-radius:50%;overflow:hidden;flex-shrink:0;
    border:1px solid var(--border-strong);
    box-shadow:0 0 0 3px rgba(212,175,55,.08);
  }
  .logo-badge img{width:100%;height:100%;object-fit:cover;display:block;}
  .logo-text{display:flex;flex-direction:column;line-height:1.15;}
  .logo-text b{
    font-family:var(--display);font-weight:700;font-size:19px;letter-spacing:.03em;color:var(--text);
  }
  .logo-text b span{color:var(--gold-bright);font-size:1em;font-weight:700;}
  .logo-text small{
    font-family:var(--mono);font-size:9.5px;letter-spacing:.12em;color:var(--text-dim);text-transform:uppercase;
  }

  .nav-links{display:flex;gap:30px;list-style:none;}
  .nav-links a{
    color:var(--text-muted);text-decoration:none;font-size:13.5px;
    font-family:var(--mono);letter-spacing:.03em;
    transition:color .2s ease;
    position:relative;
  }
  .nav-links a:hover, .nav-links a:focus-visible, .nav-links a.active{color:var(--gold-bright);}

  .btn-restricted{
    font-family:var(--mono);font-size:11px;letter-spacing:.06em;
    color:var(--text-muted);border:1px solid var(--border);
    padding:0 18px;height:42px;background:transparent;cursor:not-allowed;
    display:flex;align-items:center;gap:8px;position:relative;
    border-radius:8px;text-transform:uppercase;box-sizing:border-box;
  }
  .btn-restricted::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--green-bright);box-shadow:0 0 8px 1px var(--green-dim);}
  .btn-restricted:hover .tooltip{opacity:1;transform:translateY(0);}
  .tooltip{
    position:absolute;top:calc(100% + 8px);right:0;
    background:var(--panel-2);border:1px solid var(--border);
    padding:8px 12px;font-size:11px;color:var(--text-muted);
    white-space:nowrap;opacity:0;transform:translateY(-4px);
    transition:.2s ease;font-family:var(--mono);
    border-radius:8px;z-index:5;
  }

  .nav-menu-btn{
    display:none;align-items:center;justify-content:center;
    width:40px;height:40px;flex-shrink:0;box-sizing:border-box;
    border:1px solid var(--border);border-radius:8px;background:transparent;
    color:var(--text);cursor:pointer;
  }
  .nav-menu-btn svg{width:19px;height:19px;stroke:currentColor;fill:none;stroke-width:2;}

  @media (max-width:900px){
    .nav-links{
      display:none;flex-direction:column;gap:0;
      position:absolute;top:100%;left:0;right:0;
      background:var(--header-bg);backdrop-filter:blur(12px);
      border-bottom:1px solid var(--border-soft);
      box-shadow:0 14px 34px rgba(0,0,0,.25);
      padding:6px 0;
    }
    .nav-links.open{display:flex;}
    .nav-links li{width:100%;}
    .nav-links a{display:block;padding:13px 24px;font-size:14px;}
    .nav-menu-btn{display:inline-flex;}
  }

  .nav-cta{display:flex;align-items:center;gap:12px;}
  .btn-nav{padding:0 20px;height:42px;font-size:12px;box-sizing:border-box;}

  .btn-theme{
    font-family:var(--mono);font-size:11px;letter-spacing:.06em;
    color:var(--text-muted);border:1px solid var(--border);
    width:42px;height:42px;background:transparent;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    border-radius:8px;box-sizing:border-box;flex-shrink:0;
    transition:border-color .2s ease, color .2s ease, transform .2s ease;
  }
  .btn-theme:hover{border-color:var(--gold);color:var(--gold-bright);transform:translateY(-2px);}
  .btn-theme svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:1.8;}
  .btn-theme .icon-sun circle{fill:var(--gold-dim);transition:fill .2s ease;}
  .btn-theme .icon-moon path{fill:var(--gold-dim);transition:fill .2s ease;}
  .btn-theme:hover .icon-sun circle{fill:var(--gold);}
  .btn-theme:hover .icon-moon path{fill:var(--gold);}
  .btn-theme .icon-sun{display:none;}
  html[data-theme="light"] .btn-theme .icon-sun{display:block;}
  html[data-theme="light"] .btn-theme .icon-moon{display:none;}

  @media (max-width:560px){ .btn-restricted{display:none;} }

  /* ================= LOGIN MODAL ================= */
  .login-overlay{
    position:fixed;inset:0;z-index:60;
    background:var(--overlay-bg);backdrop-filter:blur(3px);
    display:flex;align-items:center;justify-content:center;
    padding:24px;
    opacity:0;pointer-events:none;
    transition:opacity .25s ease;
  }
  .login-overlay.open{opacity:1;pointer-events:auto;}
  .login-card{
    position:relative;width:100%;max-width:400px;
    background:var(--panel-2);padding:38px 34px 34px;
    transform:translateY(14px) scale(.98);
    transition:transform .25s ease;
    border-radius:14px;
  }
  .login-overlay.open .login-card{transform:translateY(0) scale(1);}
  .login-close{
    position:absolute;top:14px;right:16px;flex-shrink:0;
    width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;
    border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;
    transition:border-color .2s ease,color .2s ease,transform .2s ease;
  }
  .login-close:hover{border-color:var(--red);color:var(--red);transform:rotate(90deg);}
  .login-close svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;}
  .login-crest{width:84px;height:84px;border-radius:50%;overflow:hidden;border:1px solid var(--border-strong);margin:0 auto 16px;}
  .login-crest img{width:100%;height:100%;object-fit:cover;}
  .login-title{font-family:var(--display);font-size:26px;font-weight:700;margin-top:6px;letter-spacing:.01em;text-align:center;}
  .login-sub{color:var(--text-muted);font-size:13px;margin-top:8px;line-height:1.6;}
  .login-form{display:flex;flex-direction:column;margin-top:24px;}
  .login-label{
    font-family:var(--mono);font-size:10.5px;letter-spacing:.08em;
    color:var(--text-dim);text-transform:uppercase;margin-bottom:6px;
  }
  .login-input{
    font-family:var(--body);font-size:14px;color:var(--text);
    background:var(--bg-deep);border:1px solid var(--border);
    padding:11px 13px;margin-bottom:18px;
    border-radius:8px;
    transition:border-color .2s ease;
  }
  .login-input:focus{outline:none;border-color:var(--gold);}
  .login-input option{background:var(--panel-2);color:var(--text);}
  .login-field{position:relative;margin-bottom:18px;}
  .login-field .login-input{padding-right:42px;width:100%;margin-bottom:0;}
  .field-toggle{
    position:absolute;top:0;bottom:0;right:2px;margin:auto 0;
    height:36px;width:36px;padding:0;line-height:0;box-sizing:border-box;
    display:flex;align-items:center;justify-content:center;
    background:none;border:none;cursor:pointer;color:var(--text-dim);
    transition:color .2s ease;
  }
  .field-toggle:hover{color:var(--gold-bright);}
  .field-toggle svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:1.7;}
  .field-toggle .icon-eye-off{display:none;}
  .field-toggle.is-visible .icon-eye{display:none;}
  .field-toggle.is-visible .icon-eye-off{display:block;}
  .login-error{
    display:block;font-family:var(--mono);font-size:11px;color:#e07a72;
    margin-top:-12px;margin-bottom:14px;
  }
  .login-error.client-error{
    margin-top:2px;margin-bottom:18px;display:flex;align-items:center;gap:6px;
    animation:loginErrorIn .2s ease;
  }
  .login-error.client-error::before{
    content:"";width:14px;height:14px;flex-shrink:0;border-radius:50%;
    background:#e07a72;
    -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='12' y1='8' x2='12' y2='13'/%3E%3Ccircle cx='12' cy='16.5' r='.6' fill='%23000' stroke='none'/%3E%3Ccircle cx='12' cy='12' r='9.3'/%3E%3C/svg%3E") center/contain no-repeat;
    mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='12' y1='8' x2='12' y2='13'/%3E%3Ccircle cx='12' cy='16.5' r='.6' fill='%23000' stroke='none'/%3E%3Ccircle cx='12' cy='12' r='9.3'/%3E%3C/svg%3E") center/contain no-repeat;
  }
  @keyframes loginErrorIn{from{opacity:0;transform:translateY(-3px);}to{opacity:1;transform:translateY(0);}}
  .login-input.field-invalid,.captcha-row .field-invalid{
    border-color:#e07a72 !important;
    box-shadow:0 0 0 3px rgba(224,122,114,.15);
  }
  .login-submit{border:none;cursor:pointer;justify-content:center;margin-top:4px;width:100%;}
  .captcha-row{
    display:grid;grid-template-columns:minmax(0,1fr) 56px minmax(0,1fr);
    align-items:center;gap:6px;margin-bottom:12px;
  }
  .captcha-img{
    width:100%;height:56px;object-fit:contain;background:#0a1a12;
    border-radius:8px;border:1px solid var(--border);
  }
  .captcha-row .captcha-input{
    box-sizing:border-box;height:56px;width:100%;margin-bottom:0;
    padding:0 14px;font-size:12px;
  }
  .captcha-refresh{
    box-sizing:border-box;width:56px;height:56px;flex-shrink:0;border-radius:8px;cursor:pointer;
    background:var(--bg-deep);border:1px solid var(--border);color:var(--text-muted);
    display:flex;align-items:center;justify-content:center;
    transition:background .15s ease,color .15s ease,border-color .15s ease;
  }
  .captcha-refresh svg{width:15px;height:15px;}
  .captcha-refresh:hover{background:var(--gold-dim);border-color:var(--gold);color:var(--gold-bright);}
  .captcha-refresh.spinning svg{animation:captchaRefreshSpin .5s ease;}
  @keyframes captchaRefreshSpin{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}
  .login-foot{
    margin-top:20px;padding-top:16px;border-top:1px solid var(--border-soft);
    font-family:var(--mono);font-size:10.5px;color:var(--text-dim);
    display:flex;align-items:center;gap:8px;letter-spacing:.03em;
  }

  /* ================= HERO ================= */
  /* Sengaja TIDAK fallback ke foto bawaan (images/hero-lapangan-mabesad.jpg)
     lagi -- kalau Admin menghapus Gambar Latar Beranda, hero di sini HARUS
     ikut kosong (cuma gradient overlay polos, tanpa foto sama sekali),
     konsisten dengan kotak "Belum ada gambar latar belakang" di admin. */
  .hero-stats-bg{
    position:relative;overflow:hidden;
    background-image:
      linear-gradient(115deg, var(--hero-ov-1) 0%, var(--hero-ov-2) 32%, var(--hero-ov-3) 58%, var(--hero-ov-4) 100%),
      linear-gradient(to top, var(--hero-ov-top) 0%, var(--hero-ov-top-fade) 26%)
      @if ($pengaturan->hero_image_path)
      , url('{{ asset('storage/'.$pengaturan->hero_image_path) }}')
      @endif
      ;
    background-size:cover;
    background-position:center 58%;
    background-repeat:no-repeat;
  }
  .hero{
    padding:74px 0 70px;
    position:relative;
  }
  .hero-inner{
    display:grid;grid-template-columns:1.15fr .85fr;gap:56px;align-items:center;
  }
  @media (max-width:900px){ .hero-inner{grid-template-columns:1fr;} }

  .hero-badge-row{display:flex;flex-wrap:wrap;gap:10px;margin:18px 0 6px;}
  .pill{
    display:inline-flex;align-items:center;gap:7px;
    font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;color:var(--text-muted);
    border:1px solid var(--border);padding:6px 13px;border-radius:999px;text-transform:uppercase;
    background:rgba(255,255,255,.015);
  }
  .pill .dot{width:5px;height:5px;border-radius:50%;background:var(--gold);}

  .hero h1{
    font-family:var(--display);
    font-size:clamp(50px, 7.4vw, 92px);
    line-height:.95;font-weight:700;
    margin:16px 0 10px;
    letter-spacing:.01em;
    text-transform:uppercase;
  }
  .hero h1 em{color:var(--gold-bright);font-style:normal;}
  .hero h2{
    font-family:var(--body);font-weight:600;
    font-size:clamp(16px,2vw,20px);color:var(--text);
    max-width:540px;margin-bottom:18px;
  }
  .hero p{color:var(--text-muted);max-width:520px;line-height:1.75;font-size:15px;margin-bottom:30px;}

  .hero-actions{display:flex;gap:14px;flex-wrap:wrap;align-items:center;}
  .btn{
    font-family:var(--mono);font-size:13px;letter-spacing:.05em;
    padding:14px 24px;text-decoration:none;display:inline-flex;align-items:center;gap:9px;
    border-radius:9px;text-transform:uppercase;
    transition:transform .2s ease, border-color .2s ease, color .2s ease, box-shadow .2s ease;
  }
  .btn-primary{background:linear-gradient(135deg, var(--gold-bright), var(--gold));color:#241a05;font-weight:700;box-shadow:0 10px 30px -10px rgba(212,175,55,.5);}
  .btn-primary:hover{transform:translateY(-2px);box-shadow:0 14px 34px -8px rgba(212,175,55,.6);}
  .btn-ghost{border:1px solid var(--border);color:var(--text);}
  .btn-ghost:hover{border-color:var(--gold);color:var(--gold-bright);transform:translateY(-2px);}

  .hero-motto{
    margin-top:34px;display:flex;align-items:center;gap:12px;
    font-family:var(--display);font-size:13px;letter-spacing:.22em;color:var(--text-dim);
    text-transform:uppercase;
  }
  .hero-motto::before{content:"";width:26px;height:1px;background:var(--border-strong);}

  .hero-crest{
    position:relative;width:100%;max-width:340px;margin:0 auto;
  }
  .hero-crest-ring{position:relative;aspect-ratio:1;}
  .hero-crest-glow{
    position:absolute;inset:-30px;border-radius:50%;
    background:radial-gradient(circle, rgba(63,194,125,.16), transparent 68%);
    filter:blur(6px);
  }
  .hero-crest .mark-ring{animation-duration:16s;opacity:.85;}
  .hero-crest .mark-plate{inset:22px;box-shadow:0 0 0 1px rgba(0,0,0,.35) inset, 0 20px 50px rgba(0,0,0,.55);}
  .hero-crest-caption{
    margin-top:22px;text-align:center;
    font-family:var(--mono);font-size:11px;color:var(--text-dim);
    letter-spacing:.14em;text-transform:uppercase;line-height:1.8;
  }
  .hero-crest-caption b{color:var(--gold-bright);font-weight:600;}

  /* teks kecil di atas foto hero: chip transparan + lebih tebal biar kebaca.
     Ukuran/padding/border SAMA di kedua tema (cuma warnanya beda via variabel)
     supaya tidak ada pergeseran layout saat ganti tema. */
  .hero-stats-bg .eyebrow{
    background:var(--chip-bg);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
    border:1px solid var(--chip-border);border-radius:7px;
    padding:5px 11px;width:fit-content;font-weight:600;
    box-shadow:var(--chip-shadow);
  }
  .hero-stats-bg .pill{
    background:var(--chip-bg);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
    font-weight:600;box-shadow:var(--chip-shadow);
  }
  .hero-stats-bg .hero-crest-caption{
    display:inline-block;background:var(--chip-bg);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
    border:1px solid var(--chip-border);border-radius:10px;
    padding:10px 18px;box-shadow:var(--chip-shadow);
    font-weight:500;
  }
  .hero-stats-bg .hero-crest-caption b{font-weight:700;}
  .hero-stats-bg .hero-crest{text-align:center;}

  /* ================= STATS ================= */
  .stats{padding:6px 0 84px;}
  .stats-grid{
    display:grid;grid-template-columns:repeat(4,1fr);gap:1px;
    background:var(--border-soft);border:1px solid var(--border-soft);
    border-radius:14px;overflow:hidden;
  }
  @media (max-width:820px){ .stats-grid{grid-template-columns:repeat(2,1fr);} }
  .stat{background:var(--panel);padding:26px 22px;position:relative;text-align:center;}
  .stat-num{font-family:var(--display);font-size:36px;font-weight:700;color:var(--gold-bright);}
  .stat-label{font-family:var(--mono);font-size:11px;color:var(--text-muted);margin-top:8px;line-height:1.5;text-transform:uppercase;letter-spacing:.03em;}

  /* ================= SECTION HEADS ================= */
  .section-head{max-width:660px;margin-bottom:52px;}
  .section-head.center{margin-left:auto;margin-right:auto;text-align:center;}
  .section-head.center .eyebrow{justify-content:center;}
  .section-head h3{
    font-family:var(--display);font-size:clamp(30px,3.6vw,44px);font-weight:700;
    margin-top:14px;text-transform:uppercase;letter-spacing:.01em;
  }
  .section-head p{color:var(--text-muted);margin-top:14px;line-height:1.75;font-size:15px;}
  .section-head p a{color:var(--gold-bright);text-decoration:underline;text-underline-offset:2px;}
  .section-head p a:hover{color:var(--gold);}

  /* ================= PROBLEM ================= */
  .problem{padding:76px 0;border-top:1px solid var(--border-soft);}
  .problem-grid{display:grid;grid-template-columns:.95fr 1.05fr;gap:56px;align-items:start;}
  @media (max-width:820px){ .problem-grid{grid-template-columns:1fr;gap:34px;} }
  .problem-quote{
    font-family:var(--display);font-size:24px;line-height:1.5;font-weight:600;
    border-left:2px solid var(--gold);padding-left:22px;color:var(--text);
  }
  .problem-list{list-style:none;display:flex;flex-direction:column;gap:20px;}
  .problem-list li{
    display:flex;gap:14px;color:var(--text-muted);font-size:14px;line-height:1.65;
  }
  .problem-list li::before{
    content:"";flex-shrink:0;width:8px;height:8px;margin-top:6px;
    background:var(--gold);
    clip-path:polygon(50% 0%,100% 50%,50% 100%,0% 50%);
  }
  .problem-list strong{color:var(--text);}

  /* ================= FEATURES ================= */
  .features{padding:90px 0;border-top:1px solid var(--border-soft);}
  .feature-grid{
    display:grid;grid-template-columns:repeat(4, minmax(0, 1fr));gap:18px;
  }
  @media (max-width:1100px){ .feature-grid{grid-template-columns:repeat(2, minmax(0, 1fr));} }
  @media (max-width:640px){ .feature-grid{grid-template-columns:1fr;} }
  .feature-card{padding:26px 22px;min-height:220px;display:flex;flex-direction:column;align-items:center;text-align:center;transition:border-color .25s ease, transform .25s ease, box-shadow .25s ease;}
  .feature-card:hover{border-color:var(--border-strong);transform:translateY(-4px);box-shadow:0 16px 40px -14px rgba(0,0,0,.5);}
  .feature-icon{
    width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;
    background:var(--gold-dim);border:1px solid var(--border);margin-bottom:16px;color:var(--gold-bright);
  }
  .feature-card h4{font-family:var(--display);font-size:20px;margin:10px 0 10px;font-weight:700;letter-spacing:.01em;}
  .feature-card p{color:var(--text-muted);font-size:13.5px;line-height:1.65;text-align:center;}
  .back-to-top-wrap{display:flex;justify-content:center;margin-top:44px;}
  .btn-top{
    width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg, var(--gold-bright), var(--gold));color:#241a05;border:none;cursor:pointer;
    transition:transform .25s ease, box-shadow .25s ease;
  }
  .btn-top:hover{transform:translateY(-3px);}
  .back-to-top-float{
    position:fixed;right:24px;bottom:24px;z-index:70;
    opacity:0;visibility:hidden;transform:translateY(12px);
    transition:opacity .3s ease, transform .3s ease, visibility .3s ease;
  }
  .back-to-top-float.is-visible{opacity:1;visibility:visible;transform:translateY(0);}
  @media (max-width:640px){ .back-to-top-float{right:16px;bottom:16px;} }

  /* ================= WORKFLOW ================= */
  .workflow{padding:90px 0;border-top:1px solid var(--border-soft);}
  .flow{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;position:relative;margin-top:30px;}
  .flow-line{
    position:absolute;top:19px;left:0;right:0;height:1px;background:var(--border);z-index:0;
  }
  .flow-line-fill{
    position:absolute;top:19px;left:0;height:1px;background:var(--gold);z-index:0;
    width:0%;transition:width 1.8s cubic-bezier(.4,0,.2,1);
  }
  .flow-step{
    position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;
    text-align:center;flex:1;min-width:0;padding:0 6px;
  }
  .flow-dot{
    width:40px;height:40px;border-radius:50%;background:var(--panel);
    border:1px solid var(--border);display:flex;align-items:center;justify-content:center;
    font-family:var(--mono);font-size:12px;color:var(--text-muted);
    transition:border-color .3s ease, color .3s ease, box-shadow .3s ease;
  }
  .flow-step.active .flow-dot{border-color:var(--gold);color:var(--gold-bright);box-shadow:0 0 0 4px var(--gold-dim);}
  .flow-step h5{font-family:var(--body);font-weight:700;font-size:13.5px;margin-top:14px;max-width:130px;}
  .flow-step span{font-family:var(--mono);font-size:11px;color:var(--text-muted);margin-top:6px;max-width:140px;line-height:1.55;}
  @media (max-width:820px){
    .flow{flex-direction:column;align-items:flex-start;gap:28px;}
    .flow-line,.flow-line-fill{display:none;}
    .flow-step{flex-direction:row;text-align:left;gap:16px;padding:0;}
    .flow-step h5,.flow-step span{max-width:none;margin-top:0;}
  }

  /* ================= ABOUT / TENTANG PUSSIBERAD ================= */
  .about{padding:90px 0;border-top:1px solid var(--border-soft);}
  .about-top{display:grid;grid-template-columns:.7fr 1.3fr;gap:56px;align-items:center;margin-bottom:56px;}
  @media (max-width:900px){ .about-top{grid-template-columns:1fr;gap:36px;} }
  .about-crest{
    position:relative;width:100%;max-width:220px;margin:0 auto;
    border-radius:50%;overflow:hidden;border:1px solid var(--border-strong);
    box-shadow:0 0 0 10px rgba(212,175,55,.05), 0 24px 60px rgba(0,0,0,.5);
  }
  .about-crest img{width:100%;display:block;}
  .about-grid{
    display:grid;grid-template-columns:repeat(3,1fr);gap:16px;
  }
  @media (max-width:820px){ .about-grid{grid-template-columns:1fr;} }
  .about-card{padding:24px 22px;}
  .about-label{
    font-family:var(--mono);font-size:11px;letter-spacing:.1em;color:var(--gold-bright);
    text-transform:uppercase;margin-bottom:10px;
  }
  .about-value{font-family:var(--body);font-size:14px;color:var(--text);line-height:1.65;}
  .about-social{display:flex;justify-content:center;margin-top:36px;}
  .ig-link{
    display:inline-flex;align-items:center;gap:10px;
    font-family:var(--mono);font-size:13px;letter-spacing:.04em;color:var(--text);
    border:1px solid var(--border);padding:12px 24px;border-radius:999px;
    text-decoration:none;background:var(--panel);
    transition:border-color .2s ease,color .2s ease,transform .2s ease,background .2s ease;
  }
  .ig-link:hover{border-color:var(--gold);color:var(--gold-bright);transform:translateY(-2px);background:var(--gold-dim);}
  .ig-icon{width:18px;height:18px;color:var(--gold-bright);flex-shrink:0;}

  /* ================= TRUST / SECURITY STRIP ================= */
  .stack{padding:56px 0;border-top:1px solid var(--border-soft);border-bottom:1px solid var(--border-soft);}
  .stack-label{
    text-align:center;font-family:var(--mono);font-size:11px;letter-spacing:.16em;color:var(--text-dim);
    text-transform:uppercase;margin-bottom:24px;
  }
  .stack-row{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:center;}
  .badge{
    display:inline-flex;align-items:center;gap:8px;
    font-family:var(--mono);font-size:12px;color:var(--text-muted);
    border:1px solid var(--border);padding:9px 18px;letter-spacing:.03em;
    border-radius:999px;
  }
  .badge svg{width:14px;height:14px;color:var(--gold-bright);flex-shrink:0;}

  /* ================= FOOTER ================= */
  footer{padding:60px 0 40px;border-top:1px solid var(--border-soft);background:var(--panel);}
  .footer-grid{display:grid;grid-template-columns:1.2fr .85fr .85fr 1fr;gap:36px;margin-bottom:40px;}
  @media (max-width:820px){ .footer-grid{grid-template-columns:1fr;gap:30px;} }
  .footer-brand-row{display:flex;align-items:center;gap:14px;margin-bottom:14px;}
  .footer-crest{width:44px;height:44px;border-radius:50%;overflow:hidden;border:1px solid var(--border-strong);flex-shrink:0;}
  .footer-crest img{width:100%;height:100%;object-fit:cover;}
  .footer-brand b{font-family:var(--display);font-size:21px;letter-spacing:.04em;text-transform:uppercase;line-height:1.2;}
  .footer-brand > span{display:block;font-family:var(--mono);font-size:11px;color:var(--text-dim);letter-spacing:.1em;text-transform:uppercase;margin-top:5px;}
  .footer-desc{font-family:var(--body);font-size:13px;color:var(--text-muted);line-height:1.7;max-width:340px;}
  .footer-col-title{font-family:var(--mono);font-size:11px;letter-spacing:.12em;color:var(--gold-bright);text-transform:uppercase;margin-bottom:16px;}
  .footer-links{list-style:none;display:flex;flex-direction:column;gap:11px;}
  .footer-links li{color:var(--text-muted);font-size:13.5px;line-height:1.5;}
  .footer-links a{color:var(--text-muted);text-decoration:none;font-size:13.5px;transition:color .2s ease;display:flex;align-items:center;gap:9px;}
  .footer-links a svg{flex-shrink:0;opacity:.75;transition:opacity .2s ease;}
  .footer-links a:hover svg{opacity:1;}
  .footer-links a:hover{color:var(--gold-bright);}
  .footer-bottom{
    border-top:1px solid var(--border-soft);padding-top:24px;
    display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;
    font-family:var(--mono);font-size:11.5px;color:var(--text-dim);letter-spacing:.03em;
  }

  [data-reveal]{opacity:0;transform:translateY(18px);transition:opacity .7s ease, transform .7s ease;}
  [data-reveal].in{opacity:1;transform:translateY(0);}

  @media (prefers-reduced-motion: reduce){
    *{animation-duration:.01ms !important;animation-iteration-count:1 !important;transition-duration:.01ms !important;}
  }

  /* ===== toast notifikasi ===== */
  .toast-stack{position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:2000;width:min(400px,86vw);pointer-events:none;height:0;}
  .toast{
    position:absolute;top:0;left:50%;overflow:hidden;
    pointer-events:auto;display:flex;align-items:center;gap:11px;padding:13px 18px 15px;border-radius:11px;
    background:var(--panel);border:1px solid var(--border-soft);box-shadow:0 15px 40px rgba(0,0,0,.35);
    font-family:var(--body);font-size:13px;color:var(--text);width:100%;box-sizing:border-box;
    opacity:0;transform:translate(-50%,-26px) scale(.97);
    transition:top .8s cubic-bezier(.34,1.2,.64,1);
    animation:toastIn .35s cubic-bezier(.34,1.56,.64,1) forwards;
  }
  .toast.leaving{animation:toastOut .4s cubic-bezier(.4,0,.2,1) forwards;}
  .toast.success{border-color:rgba(63,194,125,.4);}
  .toast.success .toast-icon{background:var(--success-dim);color:var(--success-bright);}
  .toast.success .toast-bar{background:var(--success-bright);}
  .toast.error{border-color:rgba(198,40,40,.35);}
  .toast.error .toast-icon{background:var(--red-dim);color:var(--red);}
  .toast.error .toast-bar{background:var(--red);}
  .toast-icon{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
  .toast-icon svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.4;}
  .toast-body{display:flex;flex-direction:column;gap:2px;min-width:0;}
  .toast-label{font-family:var(--mono);font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;}
  .toast.success .toast-label{color:var(--success-bright);}
  .toast.error .toast-label{color:var(--red);}
  .toast-text{font-family:var(--body);font-size:13.5px;font-weight:600;line-height:1.4;color:var(--text);}
  .toast-bar{position:absolute;left:0;bottom:0;height:3px;width:100%;transform-origin:left;animation:toastBar 3s linear forwards;}
  @keyframes toastIn{to{opacity:1;transform:translate(-50%,0) scale(1);}}
  @keyframes toastOut{
    0%{opacity:1;transform:translate(-50%,0) scale(1);}
    35%{opacity:1;transform:translate(-50%,-6px) scale(1.05);}
    100%{opacity:0;transform:translate(-50%,-30px) scale(.9);}
  }
  @keyframes toastBar{from{transform:scaleX(1);}to{transform:scaleX(0);}}
</style>
<script>
  (function(){
    var saved = localStorage.getItem('siberad-theme');
    if(saved === 'light'){ document.documentElement.setAttribute('data-theme','light'); }
  })();
</script>

@vite(['resources/js/app.js'])
</head>
<body class="is-loading">

  <!-- ================= LOADER ================= -->
  <div id="loader">
    <div class="loader-panel loader-panel-left"></div>
    <div class="loader-panel loader-panel-right"></div>
    <div class="loader-scanline" id="loaderScan"></div>
    <div class="mark" id="loaderMark">
      <div class="mark-ring">
        <svg viewBox="0 0 148 148">
          <circle class="ring-track" cx="74" cy="74" r="65"></circle>
          <circle class="ring-arc" cx="74" cy="74" r="65"></circle>
        </svg>
      </div>
      <div class="mark-plate">@if ($lpLogoUrl)<img src="{{ $lpLogoUrl }}" alt="Lambang Pussiberad">@endif</div>
    </div>
    <div class="loader-caption">{{ $lp['loader']['caption'] }}</div>
  </div>

  <!-- ================= NAV ================= -->
  <header>
    <div class="wrap">
      <nav>
        <a class="logo" href="#tentang">
          <span class="logo-badge">@if ($lpLogoUrl)<img src="{{ $lpLogoUrl }}" alt="Lambang Pussiberad">@endif</span>
          <span class="logo-text"><b>{{ $pengaturan->hero_judul_awal }}<span>{{ $pengaturan->hero_judul_aksen }}</span></b><small>{{ $lp['brand']['tagline'] }}</small></span>
        </a>
        <ul class="nav-links" id="navLinks">
          @foreach ($lp['nav'] as $navItem)
            <li><a href="{{ $navItem['url'] }}">{{ $navItem['label'] }}</a></li>
          @endforeach
        </ul>
        <div class="nav-cta">
          <button class="nav-menu-btn" type="button" id="navMenuBtn" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="navLinks">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="7" x2="20" y2="7"></line><line x1="4" y1="12" x2="20" y2="12"></line><line x1="4" y1="17" x2="20" y2="17"></line></svg>
          </button>
          <button class="btn-theme" type="button" id="themeToggle" aria-label="Ganti tema">
            <svg class="icon-moon" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path>
            </svg>
            <svg class="icon-sun" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="4.2"></circle>
              <path d="M12 2.5v2.4M12 19.1v2.4M4.4 4.4l1.7 1.7M17.9 17.9l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.4 19.6l1.7-1.7M17.9 6.1l1.7-1.7"></path>
            </svg>
          </button>
          <a class="btn btn-primary btn-nav" href="#login">{{ $lp['login']['nav_label'] }}</a>
        </div>
      </nav>
    </div>
  </header>

  <!-- ================= LOGIN MODAL ================= -->
  <div class="login-overlay" id="loginOverlay">
    <div class="login-card hud-panel" role="dialog" aria-modal="true" aria-labelledby="loginTitle">
      <button class="login-close" id="loginClose" type="button" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>
      <div class="login-crest">@if ($lpLogoUrl)<img src="{{ $lpLogoUrl }}" alt="Lambang Pussiberad">@endif</div>
      <h3 id="loginTitle" class="login-title">{{ $lp['login']['title'] }}</h3>
      <p class="login-sub">{{ $lp['login']['subtitle'] }}</p>
      <form class="login-form" id="loginForm" method="POST" action="{{ route('login') }}" autocomplete="off">
        @csrf
        <label class="login-label" for="loginUser">NRP / Username</label>
        <input class="login-input" id="loginUser" name="username" type="text" value="{{ old('username') }}" autocomplete="off" placeholder="Masukan NRP atau Username" required>
        <label class="login-label" for="loginPass">Password</label>
        <div class="login-field">
          <input class="login-input" id="loginPass" name="password" type="password" autocomplete="new-password" placeholder="Masukan Password" required>
          <button class="field-toggle" type="button" data-target="loginPass" aria-label="Tampilkan Password">
            <svg class="icon-eye" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3.2"/></svg>
            <svg class="icon-eye-off" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c7 0 10.5 7 10.5 7a13.6 13.6 0 0 1-3.2 4.1M6.6 6.6C3.5 8.5 1.5 12 1.5 12s3.5 7 10.5 7a10.6 10.6 0 0 0 4.2-.85"/><path d="M9.5 9.7a3.2 3.2 0 0 0 4.5 4.5"/></svg>
          </button>
        </div>
        <label class="login-label" for="loginCaptcha">Captcha</label>
        <div class="captcha-row">
          <img id="captchaImg" class="captcha-img" src="{{ route('captcha.image') }}?t={{ microtime(true) }}" alt="Kode captcha">
          <button class="captcha-refresh" type="button" id="captchaRefresh" aria-label="Muat ulang captcha">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 4v5h-5"/></svg>
          </button>
          <input class="login-input captcha-input" id="loginCaptcha" name="captcha" type="text" autocomplete="off" placeholder="Masukan Captcha" required>
        </div>
        <button class="btn btn-primary login-submit" type="submit">{{ $lp['login']['submit_label'] }}</button>
      </form>
      <div class="login-foot">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 3v6c0 5-3.4 8.4-8 11-4.6-2.6-8-6-8-11V5l8-3z"/></svg>
        {{ $lp['login']['footer_note'] }}
      </div>
    </div>
  </div>

  <main>
    <!-- ================= HERO ================= -->
    <div class="hero-stats-bg">
    <section class="hero" id="tentang">
      <div class="wrap hero-inner">
        <div data-reveal>
          <div class="eyebrow">{{ $pengaturan->hero_eyebrow }}</div>
          <h1>{{ $pengaturan->hero_judul_awal }}<em>{{ $pengaturan->hero_judul_aksen }}</em></h1>
          <h2>{{ $pengaturan->hero_subjudul }}</h2>
          <p>{{ $pengaturan->hero_deskripsi }}</p>
          <div class="hero-actions">
            <a class="btn btn-primary" href="{{ $lp['hero']['button_url'] }}">{{ $lp['hero']['button_label'] }}</a>
          </div>
        </div>
        <div data-reveal>
          <div class="hero-crest">
            <div class="hero-crest-ring">
              <div class="hero-crest-glow"></div>
              <div class="mark hero-mark" style="width:100%;height:100%;">
                <div class="mark-ring">
                  <svg viewBox="0 0 148 148">
                    <circle class="ring-track" cx="74" cy="74" r="65"></circle>
                    <circle class="ring-arc" cx="74" cy="74" r="65"></circle>
                  </svg>
                </div>
                <div class="mark-plate">@if ($lpLogoUrl)<img src="{{ $lpLogoUrl }}" alt="Lambang Pussiberad">@endif</div>
              </div>
            </div>
            <div class="hero-crest-caption">
              {{ $lp['hero']['crest_caption'] }}
              @if (!empty($lp['hero']['crest_motto']))
                <br><span style="opacity:.72;">&ldquo;{{ $lp['hero']['crest_motto'] }}&rdquo;</span>
              @endif
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ================= STATS ================= -->
    <section class="stats">
      <div class="wrap">
        <div class="stats-grid" data-reveal>
          @foreach ($lp['stats'] as $stat)
            <div class="stat">
              <div class="stat-num">{{ $stat['number'] }}</div>
              <div class="stat-label">{{ $stat['label'] }}</div>
            </div>
          @endforeach
        </div>
      </div>
    </section>
    </div>

    <!-- ================= FEATURES ================= -->
    <section class="features" id="fitur">
      <div class="wrap">
        <div class="section-head center" data-reveal>
          <div class="eyebrow">{{ $lp['features_section']['eyebrow'] }}</div>
          <h3>{{ $lp['features_section']['title'] }}</h3>
          <p>{{ $lp['features_section']['description'] }}</p>
        </div>
        @php
          $fiturIcons = [
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 14"/></svg>',
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>',
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h8l-1 8 10-12h-8l1-8z"/></svg>',
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 3v6c0 5-3.4 8.4-8 11-4.6-2.6-8-6-8-11V5l8-3z"/><path d="m9 12 2 2 4-4"/></svg>',
          ];
        @endphp
        <div class="feature-grid" data-reveal>
          @foreach (($pengaturan->fitur ?? []) as $i => $fitur)
            <div class="feature-card hud-panel">
              <div class="feature-icon">{!! $fiturIcons[$i] ?? $fiturIcons[0] !!}</div>
              <h4>{{ $fitur['judul'] }}</h4>
              <p>{{ $fitur['deskripsi'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </section>


    <!-- ================= TENTANG PUSSIBERAD ================= -->
    <section class="about" id="tentang-pussiberad">
      <div class="wrap">
        <div class="about-top" data-reveal>
          <div class="about-crest">
            @if ($lpLogoUrl)<img src="{{ $lpLogoUrl }}" alt="Lambang Pussiberad">@endif
          </div>
          <div>
            <div class="eyebrow">{{ $lp['about_section']['eyebrow'] }}</div>
            <h3 style="font-family:var(--display);font-size:clamp(28px,3.4vw,38px);font-weight:700;margin:14px 0 14px;text-transform:uppercase;">{{ $lp['about_section']['title'] }}</h3>
            @foreach (explode("\n\n", $pengaturan->tentang_deskripsi ?? '') as $paragraf)
              <p style="color:var(--text-muted);line-height:1.8;font-size:15px;margin-top:14px;">{{ trim($paragraf) }}</p>
            @endforeach
          </div>
        </div>

        <div class="about-grid" id="tentangIdentitasGrid" data-reveal>
          <div class="about-card hud-panel">
            <div class="about-label">Nama Resmi</div>
            <div class="about-value">{{ $pengaturan->tentang_nama_resmi }}</div>
          </div>
          <div class="about-card hud-panel">
            <div class="about-label">Nama Lama</div>
            <div class="about-value">{{ $pengaturan->tentang_nama_lama }}</div>
          </div>
          <div class="about-card hud-panel">
            <div class="about-label">Fungsi Utama</div>
            <div class="about-value">{{ $pengaturan->tentang_fungsi_utama }}</div>
          </div>
        </div>

        <div class="moto-panel" style="margin:56px 0 40px;" data-reveal>
          <div class="eyebrow">Moto</div>
          <h3 style="font-family:var(--display);font-size:clamp(26px,3vw,34px);font-weight:700;margin:14px 0 18px;text-transform:uppercase;letter-spacing:.04em;">{{ $pengaturan->tentang_moto_judul }}</h3>
          <p class="moto-desc">
            {{ $pengaturan->tentang_moto_deskripsi }}
          </p>
        </div>

        <div class="about-grid" data-reveal>
          @foreach (($lp['about_section']['items'] ?? []) as $item)
            <div class="about-card hud-panel">
              <div class="about-label">{{ $item['label'] ?? '' }}</div>
              <div class="about-value">{{ $item['description'] ?? '' }}</div>
            </div>
          @endforeach
        </div>

      </div>
    </section>
  </main>

  <!-- ================= FOOTER ================= -->
  <footer id="tim">
    <div class="wrap">
      <div class="footer-grid">
        <div>
          <div class="footer-brand-row">
            <span class="footer-crest">@if ($lpLogoUrl)<img src="{{ $lpLogoUrl }}" alt="Lambang Pussiberad">@endif</span>
            <div class="footer-brand">
              <b>{{ $pengaturan->hero_judul_awal }}<span style="display:inline;color:var(--gold-bright);">{{ $pengaturan->hero_judul_aksen }}</span></b>
              <span>{{ $lp['footer']['brand_subtitle'] }}</span>
            </div>
          </div>
          <p class="footer-desc">{{ $lp['footer']['description'] }}</p>
        </div>
        <div>
          <div class="footer-col-title">{{ $lp['footer']['nav_title'] }}</div>
          <ul class="footer-links">
            @foreach (array_slice($lp['nav'] ?? [], 0, 3) as $navItem)
              <li><a href="{{ $navItem['url'] }}">{{ $navItem['label'] }}</a></li>
            @endforeach
          </ul>
        </div>
        @php
          $sosialIcons = [
            'instagram' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1"/></svg>',
            'tiktok' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 3h-3.1v12.4a2.7 2.7 0 1 1-1.9-2.6V9.6a5.8 5.8 0 1 0 5 5.7V9.4a7.9 7.9 0 0 0 4.4 1.3V7.6c-2.2-.2-4-1.9-4.4-4.1z"/></svg>',
            'youtube' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22.5 7.2c-.3-1.1-1.1-1.9-2.1-2.2C18.6 4.5 12 4.5 12 4.5s-6.6 0-8.4.5c-1 .3-1.8 1.1-2.1 2.2C1 9 1 12 1 12s0 3 .5 4.8c.3 1.1 1.1 1.9 2.1 2.2 1.8.5 8.4.5 8.4.5s6.6 0 8.4-.5c1-.3 1.8-1.1 2.1-2.2.5-1.8.5-4.8.5-4.8s0-3-.5-4.8zM9.8 15.3V8.7l6 3.3-6 3.3z"/></svg>',
            'x' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 3H22l-7.5 8.6L23 21h-6.6l-5.2-6.6L5.2 21H2l8.1-9.3L2 3h6.7l4.7 6 5.5-6z"/></svg>',
            'facebook' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-8h2.7l.4-3.1h-3.1V8c0-.9.3-1.5 1.6-1.5h1.7V3.7C16.5 3.6 15.6 3.5 14.6 3.5c-2.4 0-4 1.5-4 4.1v2.3H7.9V13h2.7v8h2.9z"/></svg>',
            'wikipedia' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.8 5.7 3.8 9s-1.3 6.5-3.8 9c-2.5-2.5-3.8-5.7-3.8-9s1.3-6.5 3.8-9z"/></svg>',
          ];
        @endphp
        <div>
          <div class="footer-col-title">{{ $lp['footer']['social_title'] }}</div>
          <ul class="footer-links">
            @foreach (($pengaturan->sosial_media ?? []) as $sosial)
              @if (!empty($sosial['url']))
                <li><a href="{{ $sosial['url'] }}" target="_blank" rel="noopener noreferrer">{!! $sosialIcons[$sosial['platform']] ?? '' !!}{{ $sosial['label'] }}</a></li>
              @endif
            @endforeach
          </ul>
        </div>
        <div>
          <div class="footer-col-title">{{ $lp['footer']['mabesad_title'] }}</div>
          <p class="footer-desc" style="margin-bottom:14px;">{{ $lp['footer']['mabesad_description'] }}</p>
          <ul class="footer-links">
            @if ($pengaturan->alamat)<li>{{ $pengaturan->alamat }}</li>@endif
            @if ($pengaturan->telepon_kontak)<li>{{ $pengaturan->telepon_kontak }}</li>@endif
            @if ($pengaturan->website)<li><a href="{{ $pengaturan->website }}" target="_blank" rel="noopener noreferrer">{{ preg_replace('#^https?://#', '', rtrim($pengaturan->website, '/')) }}</a></li>@endif
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <span>{{ $lp['footer']['copyright'] }}</span>
        <span>{{ $lp['footer']['bottom_tagline'] }}</span>
      </div>
    </div>
  </footer>

  <button id="backToTopFloat" class="back-to-top-float btn-top" type="button" onclick="window.scrollTo({top:0, behavior:'smooth'});" aria-label="Kembali ke atas">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="6 11 12 5 18 11"></polyline></svg>
  </button>

<script>
  // ---------- loader sequence (icon only) ----------
  const loader = document.getElementById('loader');
  const mark = document.getElementById('loaderMark');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const duration = reduceMotion ? 150 : 1200;

  setTimeout(()=>{
    mark.classList.add('leaving');
    setTimeout(()=>{
      loader.classList.add('scan');
      setTimeout(()=>{
        loader.classList.add('split');
        document.body.classList.remove('is-loading');
        revealOnLoad();
        setTimeout(()=>{
          loader.classList.add('hidden');
        }, reduceMotion ? 0 : 900);
      }, reduceMotion ? 0 : 260);
    }, reduceMotion ? 0 : 260);
  }, duration);

  // ---------- ganti tema (gelap/terang) ----------
  const themeToggle = document.getElementById('themeToggle');
  themeToggle.addEventListener('click', ()=>{
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    if(isLight){
      document.documentElement.removeAttribute('data-theme');
      localStorage.setItem('siberad-theme', 'dark');
    } else {
      document.documentElement.setAttribute('data-theme', 'light');
      localStorage.setItem('siberad-theme', 'light');
    }
  });

  // ---------- login modal ----------
  const loginOverlay = document.getElementById('loginOverlay');
  const loginTriggers = document.querySelectorAll('a[href="#login"]');
  const loginClose = document.getElementById('loginClose');
  const loginForm = document.getElementById('loginForm');

  function openLogin(e){
    if(e) e.preventDefault();
    loginOverlay.classList.add('open');
    document.getElementById('loginUser').focus();
  }
  function closeLogin(){ loginOverlay.classList.remove('open'); }

  loginTriggers.forEach(t => t.addEventListener('click', openLogin));
  loginClose.addEventListener('click', closeLogin);
  document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeLogin(); });

  // ---------- submit login via AJAX (biar gagal login tidak refresh halaman) ----------
  loginForm.addEventListener('submit', async function(e){
    e.preventDefault();
    const submitBtn = loginForm.querySelector('.login-submit');
    submitBtn.disabled = true;
    try{
      const res = await fetch(loginForm.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: new FormData(loginForm),
        redirect: 'manual',
      });

      // redirect:'manual' bikin browser berhenti di respons redirect (302) tanpa
      // mengikutinya otomatis — supaya navigasi ke dashboard jadi satu-satunya
      // request berikutnya yang membaca flash session "login_success" (kalau
      // fetch ikut redirect sendiri, flash-nya keburu "dipakai" di request
      // tersembunyi itu dan toast sukses jadi tidak muncul).
      if(res.type === 'opaqueredirect' || res.ok){
        window.location.href = '{{ route('dashboard') }}';
        return;
      }

      if(res.status === 422){
        const data = await res.json();
        const errors = data.errors || {};
        const keys = Object.keys(errors);
        if(keys.length){
          keys.forEach((key, i) => {
            setTimeout(() => siberadShowToast('error', errors[key][0]), i * 150);
          });
        } else {
          siberadShowToast('error', data.message || 'Login gagal.');
        }
      } else {
        siberadShowToast('error', 'Terjadi kesalahan. Coba lagi.');
      }

      const captchaField = document.getElementById('loginCaptcha');
      if(captchaField) captchaField.value = '';
      const captchaImgEl = document.getElementById('captchaImg');
      if(captchaImgEl) captchaImgEl.src = '{{ route('captcha.image') }}?t=' + Date.now();
    } catch(err){
      siberadShowToast('error', 'Gagal terhubung ke server. Periksa koneksi Anda.');
    } finally {
      submitBtn.disabled = false;
    }
  });

  // ---------- toggle tampilkan/sembunyikan NRP/Username & Password ----------
  document.querySelectorAll('.field-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.target);
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      btn.classList.toggle('is-visible', isHidden);
      btn.setAttribute('aria-label', isHidden ? 'Sembunyikan' : 'Tampilkan');
    });
  });

  // ---------- pesan validasi custom (ganti "Please fill out this field.") ----------
  const customMessages = {
    loginUser: 'NRP/Username wajib diisi.',
    loginPass: 'Password wajib diisi.',
    loginCaptcha: 'Captcha wajib diisi.',
  };
  loginForm.querySelectorAll('input[required]').forEach(input => {
    const anchor = input.closest('.login-field, .captcha-row') || input;
    let msg = anchor.nextElementSibling;
    if (!msg || !msg.classList.contains('client-error')) {
      msg = document.createElement('span');
      msg.className = 'login-error client-error';
      msg.style.display = 'none';
      anchor.insertAdjacentElement('afterend', msg);
    }
    input.addEventListener('invalid', (e) => {
      e.preventDefault();
      input.classList.add('field-invalid');
      msg.textContent = customMessages[input.id] || 'Kolom ini wajib diisi.';
      msg.style.display = 'block';
    });
    input.addEventListener('input', () => {
      input.classList.remove('field-invalid');
      msg.style.display = 'none';
    });
  });

  // ---------- muat ulang gambar captcha ----------
  const captchaImg = document.getElementById('captchaImg');
  const captchaRefresh = document.getElementById('captchaRefresh');
  if (captchaRefresh && captchaImg) {
    captchaRefresh.addEventListener('click', () => {
      captchaImg.src = '{{ route('captcha.image') }}?t=' + Date.now();
      captchaRefresh.classList.remove('spinning');
      void captchaRefresh.offsetWidth;
      captchaRefresh.classList.add('spinning');
    });
  }

  // ---------- toast notifikasi ----------
  var siberadToastQueue = [];
  function siberadShowToast(type, message){
    var stack = document.getElementById('siberadToastStack');
    if(!stack){ stack=document.createElement('div'); stack.id='siberadToastStack'; stack.className='toast-stack'; document.body.appendChild(stack); }
    var toast=document.createElement('div');
    toast.className='toast '+type;
    var icon = type==='success'
      ? '<path d="M20 6L9 17l-5-5"></path>'
      : '<line x1="12" y1="8" x2="12" y2="13"></line><circle cx="12" cy="16.5" r=".6" fill="currentColor" stroke="none"></circle><circle cx="12" cy="12" r="9.3"></circle>';
    var label = type==='success' ? 'Berhasil' : 'Gagal';
    toast.innerHTML = '<span class="toast-icon"><svg viewBox="0 0 24 24">'+icon+'</svg></span><span class="toast-body"><span class="toast-label"></span><span class="toast-text"></span></span><span class="toast-bar"></span>';
    toast.querySelector('.toast-label').textContent = label;
    toast.querySelector('.toast-text').textContent = message;
    toast.style.top = '0px';
    stack.prepend(toast);
    siberadRelayoutToasts(stack);

    // Antrean FIFO: toast yang muncul duluan HARUS mulai hilang duluan juga,
    // jadi urutan animasi keluar tidak pernah kebalik walau timer selisih dikit.
    var entry = { el: toast, leaving: false, readyAt: Date.now() + 3000 };
    siberadToastQueue.push(entry);

    function tryLeave(){
      if(entry.leaving) return;
      if(Date.now() < entry.readyAt) return;
      var idx = siberadToastQueue.indexOf(entry);
      if(idx > 0 && siberadToastQueue.slice(0, idx).some(function(e){ return !e.leaving; })) return;
      entry.leaving = true;
      toast.classList.add('leaving');
      setTimeout(function(){
        toast.remove();
        var i = siberadToastQueue.indexOf(entry);
        if(i > -1) siberadToastQueue.splice(i, 1);
        siberadRelayoutToasts(stack);
      }, 400);
    }

    setTimeout(function poll(){
      tryLeave();
      if(!entry.leaving) setTimeout(poll, 100);
    }, 3000);
  }
  // Susun ulang posisi (top) tiap toast berdasarkan urutan & tinggi aktualnya
  // — perubahan nilai top otomatis dianimasikan lewat transition CSS, jadi
  // toast lama beneran "geser" turun, bukan loncat/ketimpa toast baru.
  function siberadRelayoutToasts(stack){
    requestAnimationFrame(function(){
      var y = 0, gap = 10;
      Array.prototype.forEach.call(stack.children, function(el){
        el.style.top = y + 'px';
        y += el.offsetHeight + gap;
      });
    });
  }

  @if ($errors->any())
    openLogin();
    siberadShowToast('error', {!! json_encode($errors->first()) !!});
  @endif

  @if(session('logout_success'))
    siberadShowToast('success', {!! json_encode('Anda berhasil logout dari '.$pengaturan->hero_judul_awal.$pengaturan->hero_judul_aksen.'.') !!});
  @endif

  // ---------- scroll reveal ----------
  const revealEls = document.querySelectorAll('[data-reveal]');
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(e=>{
      if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); }
    });
  }, {threshold:.15});
  revealEls.forEach(el=>io.observe(el));

  function revealOnLoad(){
    document.querySelectorAll('.hero [data-reveal]').forEach(el=>el.classList.add('in'));
  }

  // ---------- toggle menu navigasi mobile (hamburger, ≤900px) ----------
  const navMenuBtn = document.getElementById('navMenuBtn');
  const navLinksList = document.getElementById('navLinks');
  if (navMenuBtn && navLinksList) {
    navMenuBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      const isOpen = navLinksList.classList.toggle('open');
      navMenuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    navLinksList.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        navLinksList.classList.remove('open');
        navMenuBtn.setAttribute('aria-expanded', 'false');
      });
    });
    document.addEventListener('click', function (e) {
      if (navLinksList.classList.contains('open') && !navLinksList.contains(e.target) && e.target !== navMenuBtn && !navMenuBtn.contains(e.target)) {
        navLinksList.classList.remove('open');
        navMenuBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // ---------- nav aktif mengikuti section (scrollspy) ----------
  const navLinks = document.querySelectorAll('.nav-links a[href^="#"]');
  const navSections = [];
  navLinks.forEach(link => {
    const target = document.querySelector(link.getAttribute('href'));
    if (target) navSections.push({ link, target });
  });

  function setActiveNavLink(activeLink) {
    navLinks.forEach(a => a.classList.remove('active'));
    if (activeLink) activeLink.classList.add('active');
  }

  // Dihitung ulang dari posisi asli tiap section setiap ada scroll, bukan
  // menunggu event "masuk/keluar zona" seperti IntersectionObserver — supaya
  // tidak ada section yang "terlewat" saat scroll cepat, baik ke bawah
  // maupun ke atas.
  let scrollSpyTicking = false;
  function updateScrollSpy(){
    scrollSpyTicking = false;

    // Kalau halaman sudah discroll mentok ke paling bawah, langsung pakai
    // menu terakhir (Kontak) — footer kadang lebih pendek dari garis acuan
    // di bawah sehingga tidak akan pernah "kena" garis acuan itu.
    const sudahMentok = (window.innerHeight + window.scrollY) >= (document.documentElement.scrollHeight - 2);
    if (sudahMentok) {
      setActiveNavLink(navSections[navSections.length - 1].link);
      return;
    }

    const garisAcuan = window.innerHeight * 0.35;
    let aktif = navSections[0];
    for (const item of navSections) {
      const rect = item.target.getBoundingClientRect();
      if (rect.top <= garisAcuan) aktif = item;
    }
    setActiveNavLink(aktif.link);
  }

  function requestScrollSpyUpdate(){
    if (!scrollSpyTicking) {
      scrollSpyTicking = true;
      requestAnimationFrame(updateScrollSpy);
    }
  }

  window.addEventListener('scroll', requestScrollSpyUpdate, {passive:true});
  window.addEventListener('resize', requestScrollSpyUpdate);
  updateScrollSpy();

  // set aktif langsung saat diklik (biar kerasa instan sebelum animasi scroll selesai)
  navLinks.forEach(link => {
    link.addEventListener('click', () => setActiveNavLink(link));
  });

  // ---------- tombol back to top (muncul setelah scroll) ----------
  const backToTopBtn = document.getElementById('backToTopFloat');
  function toggleBackToTop(){
    if(window.scrollY > 400){ backToTopBtn.classList.add('is-visible'); }
    else { backToTopBtn.classList.remove('is-visible'); }
  }
  window.addEventListener('scroll', toggleBackToTop, {passive:true});
  toggleBackToTop();

</script>
<script src="{{ asset('js/landing-content.js') }}"></script>
</body>
</html>
