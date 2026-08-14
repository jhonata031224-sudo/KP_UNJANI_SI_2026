<script>
  (function(){
    try {
      var t = localStorage.getItem('siberad-theme');
      if (t === 'light') { document.documentElement.setAttribute('data-theme', 'light'); }
    } catch (e) {}
  })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#081a12;
    --bg-deep:#04100a;
    --panel:#0c2417;
    --panel-2:#0f2c1c;
    --panel-alt:#0a2015;
    --border:rgba(212,175,55,.22);
    --border-soft:rgba(212,175,55,.13);
    --border-strong:rgba(212,175,55,.42);
    --gold:#d4af37;
    --gold-bright:#f3cd5c;
    --gold-dim:rgba(212,175,55,.14);
    --green:#2f9e63;
    --green-bright:#3fc27d;
    --green-dim:rgba(63,194,125,.14);
    --amber:#e0a83a;
    --amber-dim:rgba(224,168,58,.15);
    --red:#c0564f;
    --red-dim:rgba(181,52,47,.16);
    --text:#f4f1e6;
    --text-muted:#9fb3a5;
    --text-dim:#6f8577;
    --display:'Rajdhani', sans-serif;
    --mono:'JetBrains Mono', monospace;
    --body:'Inter', sans-serif;
    --sidebar-w:256px;

    /* warna solid isi tombol — sengaja TIDAK ikut berubah antar tema,
       karena selalu dipasangkan dengan teks gelap (--on-gold) di atasnya,
       jadi kontrasnya harus selalu terjaga di dark maupun light */
    --gold-solid:#d4af37;
    --gold-solid-bright:#f3cd5c;
    --on-gold:#241a05;

    /* permukaan blur sidebar/topbar + tint hover — HARUS ikut tema,
       sebelumnya di-hardcode gelap sehingga tidak berubah saat light mode */
    --surface:rgba(6,20,13,.92);
    --hover-tint:rgba(255,255,255,.04);

    color-scheme:dark;
  }

  /* ===== light theme override ===== */
  :root[data-theme="light"]{
    --bg:#f5f2e7;
    --bg-deep:#ffffff;
    --panel:#ffffff;
    --panel-2:#faf8ef;
    --panel-alt:#f2efe2;
    --border:rgba(150,110,10,.25);
    --border-soft:rgba(150,110,10,.15);
    --border-strong:rgba(150,110,10,.4);
    --gold:#e0900d;
    --gold-bright:#c4720a;
    --gold-dim:rgba(224,144,13,.16);
    --green:#1f7a49;
    --green-bright:#166138;
    --green-dim:rgba(31,122,73,.12);
    --amber:#a4700a;
    --amber-dim:rgba(164,112,10,.14);
    --red:#af372e;
    --red-dim:rgba(175,55,46,.12);
    --text:#22281f;
    --text-muted:#5b6a5f;
    --text-dim:#7d8b81;

    --surface:rgba(255,255,255,.85);
    --hover-tint:rgba(0,0,0,.035);

    color-scheme:light;
  }

  *{margin:0;padding:0;box-sizing:border-box;}
  html,body{height:100%;}
  body{
    background:var(--bg);color:var(--text);font-family:var(--body);
    -webkit-font-smoothing:antialiased;
    background-image:
      radial-gradient(ellipse 70% 45% at 15% -10%, rgba(63,194,125,.09), transparent 60%),
      radial-gradient(ellipse 50% 35% at 100% 10%, rgba(212,175,55,.06), transparent 60%);
    background-attachment:fixed;
  }
  ::selection{background:var(--gold);color:var(--bg-deep);}

  /* ===== smooth theme switch ===== */
  body,.sidebar,.topbar,header.topbar-simple,.panel,.stat-card,.hud-panel,
  .modal-box,.side-theme,.theme-toggle,.form-field input,.form-field select,.form-field textarea{
    transition:background-color .25s ease,border-color .25s ease,color .25s ease;
  }

  /* subtle hex/topographic texture, same as landing page */
  body::before{
    content:"";position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.4;
    background-image:
      linear-gradient(rgba(212,175,55,.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(212,175,55,.03) 1px, transparent 1px);
    background-size:42px 42px;
    mask-image:radial-gradient(ellipse 70% 60% at 20% 0%, #000 0%, transparent 75%);
  }

  .eyebrow{
    font-family:var(--mono);font-size:11px;letter-spacing:.18em;color:var(--gold-bright);
    text-transform:uppercase;display:flex;align-items:center;gap:9px;
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

  /* ===== layout shell ===== */
  .shell{display:flex;min-height:100vh;position:relative;z-index:1;}

  /* ===== sidebar ===== */
  .sidebar{
    width:var(--sidebar-w);flex-shrink:0;background:var(--surface);backdrop-filter:blur(12px);
    border-right:1px solid var(--border-soft);
    display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto;
    transition:transform .25s ease;z-index:40;
  }
  .side-brand{height:82px;padding:0 22px;border-bottom:1px solid var(--border-soft);display:flex;align-items:center;gap:13px;box-sizing:border-box;position:sticky;top:0;z-index:5;background:var(--surface);flex-shrink:0;}
  .side-brand img{width:46px;height:46px;border-radius:50%;object-fit:cover;border:1px solid var(--border-strong);box-shadow:0 0 0 3px rgba(212,175,55,.08);flex-shrink:0;}
  .side-brand .logo{font-family:var(--display);font-weight:700;font-size:20px;letter-spacing:.03em;text-transform:uppercase;}
  .side-brand .logo span{color:var(--gold-bright);}
  .side-unit{padding:16px 22px;border-bottom:1px solid var(--border-soft);}
  .side-unit .eyebrow{margin-bottom:6px;}
  .side-unit .name{font-family:var(--display);font-weight:700;font-size:16px;line-height:1.3;letter-spacing:.01em;}
  .side-nav{padding:14px 12px;flex:1;}
  .side-nav-label{font-family:var(--mono);font-size:10px;letter-spacing:.14em;color:var(--text-dim);text-transform:uppercase;padding:10px 10px 6px;}
  .side-link{
    display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;
    color:var(--text-muted);font-size:13.5px;font-weight:500;cursor:pointer;border:1px solid transparent;
    text-decoration:none;font-family:var(--body);
  }
  .side-link:hover{background:var(--hover-tint);color:var(--text);}
  .side-link.active{background:var(--gold-dim);color:var(--gold-bright);border-color:var(--border);font-weight:600;}
  .side-link .dot{width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.6;flex-shrink:0;}

  /* ===== sidebar dropdown (mis. menu "Laporan") ===== */
  .side-dropdown{display:flex;flex-direction:column;}
  .side-dropdown-toggle{width:100%;background:none;border:1px solid transparent;text-align:left;}
  .side-link-label{flex:1;}
  .side-dropdown-arrow{width:13px;height:13px;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:2.3;opacity:.65;transition:transform .2s ease;}
  .side-dropdown.open .side-dropdown-toggle{color:var(--text);}
  .side-dropdown.open .side-dropdown-arrow{transform:rotate(180deg);}
  .side-dropdown-menu{max-height:0;overflow:hidden;transition:max-height .22s ease;display:flex;flex-direction:column;gap:2px;}
  .side-dropdown.open .side-dropdown-menu{max-height:420px;margin-top:2px;}
  .side-sublink{padding-left:32px;}

  .side-foot{padding:14px 22px 20px;border-top:1px solid var(--border-soft);}
  .side-user{display:flex;align-items:center;gap:10px;margin-bottom:12px;}
  .side-avatar{width:34px;height:34px;border-radius:50%;background:var(--gold-dim);color:var(--gold-bright);display:flex;align-items:center;justify-content:center;font-family:var(--mono);font-weight:700;font-size:13px;flex-shrink:0;border:1px solid var(--border);}
  .side-user .n{font-size:13px;font-weight:600;line-height:1.3;color:var(--text);}
  .side-user .j{font-size:11.5px;color:var(--text-muted);}
  form.logout button{
    width:100%;font-family:var(--mono);font-size:11.5px;border:1px solid var(--border);background:transparent;
    padding:9px 12px;border-radius:8px;cursor:pointer;color:var(--text-muted);letter-spacing:.04em;
    text-transform:uppercase;transition:border-color .2s ease,color .2s ease;
  }
  form.logout button:hover{border-color:var(--red);color:var(--red);}

  /* ===== main ===== */
  .main{flex:1;min-width:0;}
  .topbar{
    background:var(--surface);backdrop-filter:blur(12px);border-bottom:1px solid var(--border-soft);
    height:82px;padding:0 32px;box-sizing:border-box;
    display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:30;gap:16px;
  }
  .menu-btn{display:none;background:transparent;border:1px solid var(--border);border-radius:8px;padding:8px 10px;cursor:pointer;color:var(--text);position:relative;z-index:41;touch-action:manipulation;-webkit-tap-highlight-color:rgba(212,175,55,.25);}
  .topbar-title{font-family:var(--display);font-weight:700;font-size:20px;letter-spacing:.01em;}
  .topbar-sub{font-size:12.5px;color:var(--text-muted);margin-top:3px;}

  .badge{
    display:inline-flex;align-items:center;gap:6px;font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;
    background:var(--gold-dim);color:var(--gold-bright);padding:7px 14px;border-radius:8px;
    text-transform:uppercase;border:1px solid var(--border);box-sizing:border-box;line-height:1.2;
  }
  .badge.green{background:var(--green-dim);color:var(--green-bright);border-color:rgba(63,194,125,.28);}
  .badge.amber{background:var(--amber-dim);color:var(--amber);border-color:rgba(224,168,58,.3);}
  .badge.red{background:var(--red-dim);color:var(--red);border-color:rgba(198,40,40,.3);}

  .topbar-actions{display:flex;align-items:center;gap:12px;flex-shrink:0;}
  .btn-icon-toggle{
    width:42px;height:42px;border-radius:8px;flex-shrink:0;box-sizing:border-box;
    display:flex;align-items:center;justify-content:center;
    border:1px solid var(--border);background:transparent;cursor:pointer;
    color:var(--text-muted);
    transition:border-color .2s ease,color .2s ease,transform .2s ease;
  }
  .btn-icon-toggle:hover{border-color:var(--gold);color:var(--gold-bright);transform:translateY(-2px);}
  .btn-icon-toggle svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:1.8;}
  .btn-icon-toggle .icon-sun circle{fill:var(--gold-dim);transition:fill .2s ease;}
  .btn-icon-toggle .icon-moon path{fill:var(--gold-dim);transition:fill .2s ease;}
  .btn-icon-toggle:hover .icon-sun circle{fill:var(--gold);}
  .btn-icon-toggle:hover .icon-moon path{fill:var(--gold);}
  .btn-icon-toggle .icon-sun{display:none;}
  :root[data-theme="light"] .btn-icon-toggle .icon-sun{display:block;}
  :root[data-theme="light"] .btn-icon-toggle .icon-moon{display:none;}

  /* ===== profile menu (topbar) ===== */
  .profile-menu{position:relative;}
  .profile-menu-btn{
    width:42px;height:42px;border-radius:8px;flex-shrink:0;box-sizing:border-box;
    display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;
    border:1px solid var(--border);background:var(--gold-dim);cursor:pointer;
    color:var(--gold-bright);font-family:var(--mono);font-weight:700;font-size:13px;
    transition:border-color .2s ease,color .2s ease,transform .2s ease;
  }
  .profile-menu-btn:hover{border-color:var(--gold);transform:translateY(-2px);}
  .profile-menu-btn.open{border-color:var(--gold);}
  .profile-dropdown{
    position:absolute;top:calc(100% + 10px);right:0;width:252px;
    background:var(--panel);border:1px solid var(--border-soft);border-radius:12px;
    box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 14px 34px rgba(0,0,0,.35);
    padding:8px;z-index:50;box-sizing:border-box;
    opacity:0;visibility:hidden;transform:translateY(-6px);pointer-events:none;
    transition:opacity .18s ease,transform .18s ease,visibility .18s ease;
  }
  .profile-dropdown.open{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto;}
  .profile-dropdown-head{display:flex;align-items:center;gap:10px;padding:8px 8px 12px;border-bottom:1px solid var(--border-soft);margin-bottom:6px;}
  .profile-dropdown-avatar{width:38px;height:38px;border-radius:50%;background:var(--gold-dim);color:var(--gold-bright);display:flex;align-items:center;justify-content:center;font-family:var(--mono);font-weight:700;font-size:13px;flex-shrink:0;border:1px solid var(--border);position:relative;overflow:hidden;}
  .profile-photo{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:none;}
  .profile-photo.visible{display:block;}
  .profile-initial.hidden{display:none;}
  .profile-dropdown-name{font-size:13.5px;font-weight:600;color:var(--text);line-height:1.3;}
  .profile-dropdown-role{font-size:11px;color:var(--text-muted);margin-top:2px;font-family:var(--mono);letter-spacing:.02em;}
  .profile-dropdown-item{
    display:flex;align-items:center;gap:9px;width:100%;padding:9px 10px;border-radius:8px;
    background:transparent;border:none;color:var(--text-muted);font-family:var(--body);font-size:13px;
    cursor:pointer;text-align:left;text-decoration:none;box-sizing:border-box;
  }
  .profile-dropdown-item:hover{background:var(--hover-tint);color:var(--text);}
  .profile-dropdown-item svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;flex-shrink:0;}
  .profile-dropdown-item.danger{color:var(--red);}
  .profile-dropdown-item.danger:hover{background:var(--red-dim);color:var(--red);}
  .profile-dropdown-item[disabled]{opacity:.5;cursor:not-allowed;pointer-events:none;}
  .profile-dropdown-divider{height:1px;background:var(--border-soft);margin:6px 2px;}
  .profile-dropdown form{margin:0;}

  .profile-dropdown-back{
    display:flex;align-items:center;gap:8px;width:100%;padding:9px 10px;margin-bottom:4px;border-radius:8px;
    background:transparent;border:none;color:var(--text-muted);font-family:var(--body);font-size:12.5px;font-weight:600;
    cursor:pointer;text-align:left;
  }
  .profile-dropdown-back:hover{background:var(--hover-tint);color:var(--text);}
  .profile-dropdown-back svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;flex-shrink:0;}
  .profile-dropdown-head-lg{display:flex;flex-direction:column;align-items:center;text-align:center;gap:4px;padding:8px 8px 16px;border-bottom:1px solid var(--border-soft);margin-bottom:8px;}
  .profile-dropdown-avatar-lg{
    width:72px;height:72px;border-radius:50%;background:var(--gold-dim);color:var(--gold-bright);
    display:flex;align-items:center;justify-content:center;font-family:var(--mono);font-weight:700;font-size:24px;
    border:1px solid var(--border);position:relative;overflow:hidden;margin-bottom:8px;
  }

  /* ===== popup detail profil (di tengah layar) ===== */
  .profile-modal-overlay{
    position:fixed;inset:0;z-index:100;padding:24px;box-sizing:border-box;
    background:rgba(4,10,7,.68);backdrop-filter:blur(4px);
    display:flex;align-items:center;justify-content:center;
    opacity:0;visibility:hidden;pointer-events:none;
    transition:opacity .2s ease,visibility .2s ease;
  }
  :root[data-theme="light"] .profile-modal-overlay{background:rgba(40,32,14,.42);}
  .profile-modal-overlay.open{opacity:1;visibility:visible;pointer-events:auto;}
  .profile-modal-card{
    width:460px;max-width:100%;max-height:88vh;overflow-y:auto;position:relative;box-sizing:border-box;
    background:var(--panel);border:1px solid var(--border-soft);border-radius:20px;
    box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 32px 80px rgba(0,0,0,.5);
    padding:52px 32px 28px;
    transform:translateY(14px) scale(.97);
    transition:transform .2s ease;
  }
  .profile-modal-overlay.open .profile-modal-card{transform:translateY(0) scale(1);}
  .profile-modal-close{
    position:absolute;top:16px;right:16px;width:36px;height:36px;border-radius:9px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;box-sizing:border-box;
    border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;
    transition:border-color .2s ease,color .2s ease,transform .2s ease;
  }
  .profile-modal-close:hover{border-color:var(--red);color:var(--red);transform:rotate(90deg);}
  .profile-modal-close svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;}
  .profile-modal-title{font-size:18px;font-weight:700;color:var(--text);padding:0 4px 16px;border-bottom:1px solid var(--border-soft);margin-bottom:16px;}
  .profile-help-text{font-size:14px;line-height:1.7;color:var(--text-muted);padding:0 4px;margin:0;}

  .profile-modal-card .profile-dropdown-item{padding:12px 14px;font-size:14.5px;gap:11px;}
  .profile-modal-card .profile-dropdown-item svg{width:17px;height:17px;}
  .profile-modal-card .profile-dropdown-head-lg{padding:8px 8px 22px;margin-bottom:14px;gap:5px;}
  .profile-modal-card .profile-dropdown-avatar-lg{width:92px;height:92px;font-size:30px;margin-bottom:10px;}
  .profile-modal-card .profile-dropdown-name{font-size:16.5px;}
  .profile-modal-card .profile-dropdown-role{font-size:12px;}

  .profile-form-notice{background:var(--gold-dim);border:1px solid var(--border);color:var(--text-muted);border-radius:10px;padding:12px 14px;font-size:12.5px;line-height:1.6;margin:2px 2px 16px;}
  .profile-form-notice b{color:var(--gold-bright);}
  .profile-form{display:flex;flex-direction:column;gap:14px;padding:0 2px 4px;}
  .profile-form-field{display:flex;flex-direction:column;gap:6px;}
  .profile-form-field label{font-family:var(--mono);font-size:11px;letter-spacing:.05em;color:var(--text-dim);text-transform:uppercase;}
  .profile-form-field input,.profile-form-field textarea{
    border:1px solid var(--border);border-radius:10px;padding:12px 14px;font-family:var(--body);font-size:14px;
    background:var(--bg-deep);color:var(--text);resize:vertical;
  }
  .profile-form-field input:focus,.profile-form-field textarea:focus{outline:none;border-color:var(--gold);}
  .profile-form-field input::placeholder,.profile-form-field textarea::placeholder{color:var(--text-dim);}
  .profile-form-error{font-size:11.5px;color:var(--red);display:none;line-height:1.5;}

  @media (max-width:560px){
    .profile-modal-card{width:100%;padding:46px 20px 22px;}
  }

  .content{padding:30px 32px 64px;max-width:1180px;position:relative;z-index:1;}
  .tab-panel{display:none;}
  .tab-panel.active{display:block;animation:fadeIn .25s ease;}
  @keyframes fadeIn{from{opacity:0;transform:translateY(6px);}to{opacity:1;transform:translateY(0);}}

  .section-head{margin-bottom:20px;}
  .section-head h2{font-family:var(--display);font-size:22px;font-weight:700;margin-bottom:5px;letter-spacing:.01em;}
  .section-head p{font-size:13px;color:var(--text-muted);}

  /* ===== dashboard hero greeting ===== */
  .dash-hero{
    display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:18px;
    margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border-soft);
  }
  .dash-hero-eyebrow{font-family:var(--mono);font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--gold-bright);margin-bottom:8px;}
  .dash-hero h2{font-family:var(--display);font-size:26px;font-weight:700;margin-bottom:6px;}
  .dash-hero p{font-size:13px;color:var(--text-muted);}

  /* ===== stat cards ===== */
  .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:26px;}
  .stat-card{
    padding:19px;position:relative;
    background:linear-gradient(180deg, rgba(255,255,255,.02), transparent), var(--panel);
    border:1px solid var(--border-soft);border-radius:12px;
    box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25);
  }
  .stat-card::before{
    content:"";position:absolute;top:0;left:14px;right:14px;height:1px;
    background:linear-gradient(90deg, transparent, var(--border-strong), transparent);
  }
  .stat-card .lbl{font-family:var(--mono);font-size:10.5px;color:var(--text-dim);text-transform:uppercase;letter-spacing:.06em;margin-bottom:9px;}
  .stat-card .val{font-family:var(--display);font-size:28px;font-weight:700;color:var(--gold-bright);}
  .stat-card .sub{font-size:11.5px;color:var(--text-dim);margin-top:5px;}
  @media(max-width:980px){.stat-grid{grid-template-columns:repeat(2,1fr);}}

  /* ===== panels ===== */
  .panel{
    padding:24px;margin-bottom:20px;position:relative;
    background:linear-gradient(180deg, rgba(255,255,255,.02), transparent), var(--panel);
    border:1px solid var(--border-soft);border-radius:12px;
    box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25);
  }
  .panel::before{
    content:"";position:absolute;top:0;left:14px;right:14px;height:1px;
    background:linear-gradient(90deg, transparent, var(--border-strong), transparent);
  }
  .panel-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap;}
  .panel-head h3{font-family:var(--display);font-size:17px;font-weight:700;letter-spacing:.01em;}
  .panel-head p{font-size:12px;color:var(--text-muted);margin-top:2px;}

  /* ===== table ===== */
  .tbl-wrap{overflow-x:auto;}
  table.dtbl{width:100%;border-collapse:collapse;font-size:13px;}
  table.dtbl th{
    text-align:left;font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;text-transform:uppercase;
    color:var(--text-dim);padding:11px 12px;border-bottom:1px solid var(--border-soft);white-space:nowrap;
  }
  table.dtbl td{padding:13px 12px;border-bottom:1px solid var(--border-soft);vertical-align:middle;color:var(--text);}
  table.dtbl tr:last-child td{border-bottom:none;}
  table.dtbl .btn-row{flex-wrap:nowrap;}
  table.dtbl .btn-row .btn{white-space:nowrap;}

  .tbl-wrap.tbl-scroll{overflow-y:auto;}
  .tbl-wrap.tbl-scroll thead th{position:sticky;top:0;z-index:1;background:var(--panel);}
  .tbl-wrap.tbl-scroll::-webkit-scrollbar{width:8px;}
  .tbl-wrap.tbl-scroll::-webkit-scrollbar-track{background:transparent;}
  .tbl-wrap.tbl-scroll::-webkit-scrollbar-thumb{background:var(--border);border-radius:8px;}
  .tbl-wrap.tbl-scroll::-webkit-scrollbar-thumb:hover{background:var(--gold);}
  table.dtbl tr:hover td{background:var(--hover-tint);}
  .status-dot{display:inline-flex;align-items:center;gap:7px;font-weight:600;font-size:12.5px;}
  .status-dot::before{content:"";width:8px;height:8px;border-radius:50%;background:currentColor;box-shadow:0 0 8px 1px currentColor;opacity:.9;}
  .status-dot.ok{color:var(--green-bright);}
  .status-dot.warn{color:var(--amber);}
  .status-dot.bad{color:var(--red);}

  /* ===== monitoring resource (CPU/RAM/Storage/Network) ===== */
  .gauge-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:14px;}
  .gauge-card{
    padding:16px;position:relative;
    background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:10px;
  }
  .gauge-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:14px;}
  .gauge-card-name{font-family:var(--display);font-weight:700;font-size:14px;}
  .gauge-card-url{font-family:var(--mono);font-size:10.5px;color:var(--text-dim);margin-top:2px;}
  .meter{margin-bottom:10px;}
  .meter-row{display:flex;justify-content:space-between;font-size:11.5px;color:var(--text-muted);margin-bottom:5px;}
  .meter-row span:last-child{font-family:var(--mono);color:var(--text);font-weight:600;}
  .progress-bar{height:7px;border-radius:5px;background:var(--bg-deep);border:1px solid var(--border-soft);overflow:hidden;}
  .progress-fill{height:100%;border-radius:5px;transition:width .3s ease;}
  .progress-fill.ok{background:var(--green-bright);}
  .progress-fill.warn{background:var(--amber);}
  .progress-fill.bad{background:var(--red);}
  .meter-foot{
    display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;
    font-size:11px;color:var(--text-dim);margin-top:12px;padding-top:10px;border-top:1px solid var(--border-soft);
  }
  .meter-foot b{color:var(--text);}

  /* ===== toggle periode (harian/mingguan/bulanan) ===== */
  .period-toggle{display:flex;gap:8px;flex-wrap:wrap;}
  .period-toggle .btn{text-transform:none;}

  /* ===== konfirmasi keluar ===== */
  .confirm-overlay{
    position:fixed;inset:0;z-index:80;
    background:rgba(4,16,10,.6);backdrop-filter:blur(3px);
    display:flex;align-items:center;justify-content:center;padding:20px;
    opacity:0;pointer-events:none;transition:opacity .2s ease;
  }
  :root[data-theme="light"] .confirm-overlay{background:rgba(60,50,20,.35);}
  .confirm-overlay.open{opacity:1;pointer-events:auto;}
  .confirm-box{
    width:100%;max-width:360px;background:var(--panel-2);border:1px solid var(--border);border-radius:14px;
    padding:28px 26px 24px;text-align:center;
    transform:translateY(10px) scale(.98);transition:transform .2s ease;
    box-shadow:0 20px 50px -20px rgba(0,0,0,.5);
  }
  .confirm-overlay.open .confirm-box{transform:translateY(0) scale(1);}
  .confirm-icon{
    width:52px;height:52px;margin:0 auto 14px;border-radius:50%;
    background:var(--red-dim);color:var(--red);
    display:flex;align-items:center;justify-content:center;
  }
  .confirm-icon svg{width:24px;height:24px;stroke:currentColor;fill:none;stroke-width:1.9;}
  .confirm-box h3{font-family:var(--display);font-size:19px;font-weight:700;margin:0 0 8px;}
  .confirm-box p{font-size:13px;color:var(--text-muted);line-height:1.6;margin:0;}
  .confirm-actions{display:flex;gap:10px;margin-top:22px;}
  .confirm-actions .btn{flex:1;justify-content:center;}

  /* ===== buttons ===== */
  .btn{
    font-family:var(--mono);font-weight:600;font-size:11.5px;letter-spacing:.04em;padding:9px 15px;border-radius:8px;
    border:1px solid var(--border);background:transparent;color:var(--text);cursor:pointer;
    display:inline-flex;align-items:center;gap:6px;text-decoration:none;text-transform:uppercase;
    transition:transform .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
  }
  .btn:hover{border-color:var(--gold);color:var(--gold-bright);transform:translateY(-1px);}
  .btn-primary{background:linear-gradient(135deg, var(--gold-solid-bright), var(--gold-solid));color:var(--on-gold);border-color:transparent;box-shadow:0 8px 22px -8px rgba(212,175,55,.5);}
  .btn-primary:hover{color:var(--on-gold);box-shadow:0 10px 26px -6px rgba(212,175,55,.6);}
  .btn-ghost-red{color:var(--red);border-color:rgba(198,40,40,.3);}
  .btn-ghost-red:hover{border-color:var(--red);color:var(--red);}
  .btn-sm{padding:7px 12px;font-size:10.5px;}
  .btn-row{display:flex;gap:8px;flex-wrap:wrap;}

  /* ===== forms ===== */
  .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
  .form-field{display:flex;flex-direction:column;gap:6px;}
  .form-field.full{grid-column:1/-1;}
  .form-field label{font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;color:var(--text-dim);text-transform:uppercase;}
  .form-field input,.form-field select,.form-field textarea{
    border:1px solid var(--border);border-radius:8px;padding:11px 13px;font-family:var(--body);font-size:13.5px;
    background:var(--bg-deep);color:var(--text);
  }
  .form-field select option{background:var(--panel-2);color:var(--text);}
  .form-field input:focus,.form-field select:focus,.form-field textarea:focus{outline:none;border-color:var(--gold);}
  .form-field input::placeholder,.form-field textarea::placeholder{color:var(--text-dim);}
  .form-hint{font-size:11px;color:var(--text-dim);}
  @media(max-width:640px){.form-grid{grid-template-columns:1fr;}}

  /* ===== notice ===== */
  .notice{background:var(--gold-dim);border:1px solid var(--border);color:var(--text);border-radius:10px;padding:14px 16px;font-size:12.5px;line-height:1.65;margin-bottom:22px;}
  .notice b{color:var(--gold-bright);}

  /* ===== org tree ===== */
  .org-list{list-style:none;font-size:13px;}
  .org-list li{padding:10px 0;border-bottom:1px solid var(--border-soft);display:flex;justify-content:space-between;align-items:center;gap:10px;}
  .org-list li:last-child{border-bottom:none;}
  .org-list .lvl1{padding-left:0;font-weight:700;font-family:var(--display);font-size:15px;color:var(--text);}
  .org-list .lvl2{padding-left:18px;color:var(--text-muted);}
  .org-list .lvl3{padding-left:36px;color:var(--text-dim);font-size:12.5px;}

  @media(max-width:900px){
    .sidebar{position:fixed;left:0;top:0;transform:translateX(-100%);box-shadow:0 0 40px rgba(0,0,0,.4);}
    .sidebar.open{transform:translateX(0);}
    .menu-btn{display:inline-flex;}
    .stat-grid{grid-template-columns:1fr 1fr;}
    .content{padding:22px 16px 60px;}
    .topbar{padding:0 16px;}
  }
</style>