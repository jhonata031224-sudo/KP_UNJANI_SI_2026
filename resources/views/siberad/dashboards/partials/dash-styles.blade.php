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
    --bg:#06090C;
    --bg-deep:#04070A;
    --panel:#11181F;
    --panel-2:#151F27;
    --panel-alt:#0D141A;
    --border:rgba(217,146,11,.22);
    --border-soft:rgba(217,146,11,.13);
    --border-strong:rgba(217,146,11,.42);
    --gold:#D9920B;
    --gold-bright:#F2B94B;
    --gold-dim:rgba(217,146,11,.14);
    --green:#D9920B;
    --green-bright:#F2B94B;
    --green-dim:rgba(217,146,11,.14);
    --amber:#e0a83a;
    --amber-dim:rgba(224,168,58,.15);
    --red:#c0564f;
    --red-dim:rgba(181,52,47,.16);
    --text:#F5F1E8;
    --text-muted:#A9A39A;
    --text-dim:#77736C;
    --display:'Rajdhani', sans-serif;
    --mono:'JetBrains Mono', monospace;
    --body:'Inter', sans-serif;
    --sidebar-w:256px;

    --gold-solid:#D9920B;
    --gold-solid-bright:#F2B94B;
    --on-gold:#241a05;

    --surface:rgba(6,9,12,.92);
    --hover-tint:rgba(255,255,255,.04);

    color-scheme:dark;
  }

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
      radial-gradient(ellipse 70% 45% at 15% -10%, rgba(217,146,11,.08), transparent 60%),
      radial-gradient(ellipse 50% 35% at 100% 10%, rgba(242,185,75,.045), transparent 60%);
    background-attachment:fixed;
  }
  ::selection{background:var(--gold);color:var(--bg-deep);}

  body,.sidebar,.topbar,header.topbar-simple,.panel,.stat-card,.hud-panel,
  .modal-box,.side-theme,.theme-toggle,.form-field input,.form-field select,.form-field textarea{
    transition:background-color .25s ease,border-color .25s ease,color .25s ease;
  }

  body::before{
    content:"";position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.4;
    background-image:
      linear-gradient(rgba(217,146,11,.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(217,146,11,.03) 1px, transparent 1px);
    background-size:42px 42px;
    mask-image:radial-gradient(ellipse 70% 60% at 20% 0%, #000 0%, transparent 75%);
  }

  .eyebrow{font-family:var(--mono);font-size:11px;letter-spacing:.18em;color:var(--gold-bright);text-transform:uppercase;display:flex;align-items:center;gap:9px;}
  .hud-panel{position:relative;background:linear-gradient(180deg, rgba(255,255,255,.02), transparent), var(--panel);border:1px solid var(--border-soft);border-radius:12px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25);}
  .hud-panel::before{content:"";position:absolute;top:0;left:14px;right:14px;height:1px;background:linear-gradient(90deg, transparent, var(--border-strong), transparent);}

  .shell{display:flex;min-height:100vh;position:relative;z-index:1;}
  .sidebar{width:var(--sidebar-w);flex-shrink:0;background:var(--surface);backdrop-filter:blur(12px);border-right:1px solid var(--border-soft);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;transition:transform .25s ease;z-index:100010;}
  .side-brand{height:82px;padding:0 22px;border-bottom:1px solid var(--border-soft);display:flex;align-items:center;gap:13px;box-sizing:border-box;background:var(--surface);flex-shrink:0;position:relative;}
  .side-brand img{width:46px;height:46px;border-radius:50%;object-fit:cover;border:1px solid var(--border-strong);box-shadow:0 0 0 3px rgba(217,146,11,.08);flex-shrink:0;}
  .side-brand .logo{font-family:var(--display);font-weight:700;font-size:20px;letter-spacing:.03em;text-transform:uppercase;}
  .side-brand .logo span{color:var(--gold-bright);}
  .side-unit{padding:16px 22px;border-bottom:1px solid var(--border-soft);}
  .side-unit .eyebrow{margin-bottom:6px;}
  .side-unit .name{font-family:var(--display);font-weight:700;font-size:16px;line-height:1.3;letter-spacing:.01em;}
  .side-nav{padding:14px 12px;flex:1;display:flex;flex-direction:column;gap:2px;overflow-y:auto;overflow-x:hidden;}
  .side-nav-label{font-family:var(--mono);font-size:10px;letter-spacing:.14em;color:var(--text-dim);text-transform:uppercase;padding:10px 10px 6px;}
  .side-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;color:var(--text-muted);font-size:13.5px;font-weight:500;cursor:pointer;border:1px solid transparent;text-decoration:none;font-family:var(--body);box-sizing:border-box;}
  .side-link:hover{background:var(--hover-tint);color:var(--text);}
  .side-link.active{background:var(--gold-dim);color:var(--gold-bright);border-color:var(--border);font-weight:600;}
  .side-link .dot{width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.6;flex-shrink:0;}

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
  form.logout button{width:100%;font-family:var(--mono);font-size:11.5px;border:1px solid var(--border);background:transparent;padding:9px 12px;border-radius:8px;cursor:pointer;color:var(--text-muted);letter-spacing:.04em;text-transform:uppercase;transition:border-color .2s ease,color .2s ease;display:flex;align-items:center;gap:10px;text-align:left;box-sizing:border-box;}
  form.logout button:hover{border-color:var(--red);color:var(--red);}
  .side-icon{width:18px;height:18px;flex-shrink:0;display:flex;align-items:center;justify-content:center;opacity:.85;}
  .side-icon svg{width:18px;height:18px;stroke:currentColor;fill:none;}
  .side-link .side-text{flex:1;}
  .sidebar.collapsed form.logout button{justify-content:center;padding:9px;gap:0;}

  .main{flex:1;min-width:0;}
  .topbar{background:var(--surface);backdrop-filter:blur(12px);border-bottom:1px solid var(--border-soft);height:82px;padding:0 32px;box-sizing:border-box;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:30;gap:16px;}
  .menu-btn{display:none;background:transparent;border:1px solid var(--border);border-radius:8px;padding:8px 10px;cursor:pointer;color:var(--text);}
  .topbar-title{font-family:var(--display);font-weight:700;font-size:20px;letter-spacing:.01em;}
  .topbar-sub{font-size:12.5px;color:var(--text-muted);margin-top:3px;}

  .badge{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;background:var(--gold-dim);color:var(--gold-bright);padding:7px 14px;border-radius:8px;text-transform:uppercase;border:1px solid var(--border);box-sizing:border-box;line-height:1.2;}
  .badge.green{background:var(--gold-dim);color:var(--gold-bright);border-color:var(--border);}
  .badge.amber{background:var(--amber-dim);color:var(--amber);border-color:rgba(224,168,58,.3);}
  .badge.red{background:var(--red-dim);color:var(--red);border-color:rgba(198,40,40,.3);}

  .topbar-actions{display:flex;align-items:center;gap:12px;flex-shrink:0;}
  .btn-icon-toggle{width:42px;height:42px;border-radius:8px;flex-shrink:0;box-sizing:border-box;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);background:transparent;cursor:pointer;color:var(--text-muted);transition:border-color .2s ease,color .2s ease,transform .2s ease;}
  .btn-icon-toggle:hover{border-color:var(--gold);color:var(--gold-bright);transform:translateY(-2px);}
  .btn-icon-toggle svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:1.8;}
  .btn-icon-toggle .icon-sun circle{fill:var(--gold-dim);transition:fill .2s ease;}
  .btn-icon-toggle .icon-moon path{fill:var(--gold-dim);transition:fill .2s ease;}
  .btn-icon-toggle:hover .icon-sun circle{fill:var(--gold);}
  .btn-icon-toggle:hover .icon-moon path{fill:var(--gold);}
  .btn-icon-toggle .icon-sun{display:none;}
  :root[data-theme="light"] .btn-icon-toggle .icon-sun{display:block;}
  :root[data-theme="light"] .btn-icon-toggle .icon-moon{display:none;}

  .profile-menu{position:relative;}
  .profile-menu-btn{width:42px;height:42px;border-radius:8px;flex-shrink:0;box-sizing:border-box;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;border:1px solid var(--border);background:var(--gold-dim);cursor:pointer;color:var(--gold-bright);font-family:var(--mono);font-weight:700;font-size:13px;transition:border-color .2s ease,color .2s ease,transform .2s ease;}
  .profile-menu-btn:hover{border-color:var(--gold);transform:translateY(-2px);}
  .profile-menu-btn.open{border-color:var(--gold);}
  .profile-dropdown{position:absolute;top:calc(100% + 10px);right:0;width:252px;background:var(--panel);border:1px solid var(--border-soft);border-radius:12px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 14px 34px rgba(0,0,0,.35);padding:8px;z-index:50;box-sizing:border-box;opacity:0;visibility:hidden;transform:translateY(-6px);pointer-events:none;transition:opacity .18s ease,transform .18s ease,visibility .18s ease;}
  .profile-dropdown.open{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto;}
  .profile-dropdown-head{display:flex;align-items:center;gap:10px;padding:8px 8px 12px;border-bottom:1px solid var(--border-soft);margin-bottom:6px;}
  .profile-dropdown-avatar{width:38px;height:38px;border-radius:50%;background:var(--gold-dim);color:var(--gold-bright);display:flex;align-items:center;justify-content:center;font-family:var(--mono);font-weight:700;font-size:13px;flex-shrink:0;border:1px solid var(--border);position:relative;overflow:hidden;}
  .profile-photo{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:none;}
  .profile-photo.visible{display:block;}
  .profile-initial.hidden{display:none;}
  .profile-dropdown-name{font-size:13.5px;font-weight:600;color:var(--text);line-height:1.3;}
  .profile-dropdown-role{font-size:11px;color:var(--text-muted);margin-top:2px;font-family:var(--mono);letter-spacing:.02em;}
  .profile-dropdown-item{display:flex;align-items:center;gap:9px;width:100%;padding:9px 10px;border-radius:8px;background:transparent;border:none;color:var(--text-muted);font-family:var(--body);font-size:13px;cursor:pointer;text-align:left;text-decoration:none;box-sizing:border-box;}
  .profile-dropdown-item:hover{background:var(--hover-tint);color:var(--text);}
  .profile-dropdown-item svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;flex-shrink:0;}
  .profile-dropdown-item.danger{color:var(--red);}
  .profile-dropdown-item.danger:hover{background:var(--red-dim);color:var(--red);}
  .profile-dropdown-item[disabled]{opacity:.5;cursor:not-allowed;pointer-events:none;}
  .profile-dropdown-divider{height:1px;background:var(--border-soft);margin:6px 2px;}
  .profile-dropdown form{margin:0;}
  .profile-dropdown-back{display:flex;align-items:center;gap:8px;width:100%;padding:9px 10px;margin-bottom:4px;border-radius:8px;background:transparent;border:none;color:var(--text-muted);font-family:var(--body);font-size:12.5px;font-weight:600;cursor:pointer;text-align:left;}
  .profile-dropdown-back:hover{background:var(--hover-tint);color:var(--text);}
  .profile-dropdown-back svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;flex-shrink:0;}
  .profile-dropdown-head-lg{display:flex;flex-direction:column;align-items:center;text-align:center;gap:4px;padding:8px 8px 16px;border-bottom:1px solid var(--border-soft);margin-bottom:8px;}
  .profile-dropdown-avatar-lg{width:72px;height:72px;border-radius:50%;background:var(--gold-dim);color:var(--gold-bright);display:flex;align-items:center;justify-content:center;font-family:var(--mono);font-weight:700;font-size:24px;border:1px solid var(--border);position:relative;overflow:hidden;margin-bottom:8px;}

  .profile-modal-overlay{position:fixed;inset:0;z-index:100;padding:24px;box-sizing:border-box;background:rgba(2,4,6,.68);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease;}
  :root[data-theme="light"] .profile-modal-overlay{background:rgba(40,32,14,.42);}
  .profile-modal-overlay.open{opacity:1;visibility:visible;pointer-events:auto;}
  .profile-modal-card{width:460px;max-width:100%;max-height:88vh;overflow-y:auto;position:relative;box-sizing:border-box;background:var(--panel);border:1px solid var(--border-soft);border-radius:20px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 32px 80px rgba(0,0,0,.5);padding:52px 32px 28px;transform:translateY(14px) scale(.97);transition:transform .2s ease;}
  .profile-modal-overlay.open .profile-modal-card{transform:translateY(0) scale(1);}
  .profile-modal-close{position:absolute;top:16px;right:16px;width:36px;height:36px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;box-sizing:border-box;border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;transition:border-color .2s ease,color .2s ease,transform .2s ease;}
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
  .profile-form-field{display:flex;flex-direction:column;gap:6px;margin-bottom:14px;}
  .profile-form-field:last-of-type{margin-bottom:0;}
  .profile-form-field label{font-family:var(--mono);font-size:11px;letter-spacing:.05em;color:var(--text-dim);text-transform:uppercase;}
  .profile-form-field input,.profile-form-field textarea{border:1px solid var(--border);border-radius:10px;padding:12px 14px;font-family:var(--body);font-size:14px;background:var(--bg-deep);color:var(--text);resize:vertical;}
  .profile-form-field input:focus,.profile-form-field textarea:focus{outline:none;border-color:var(--gold);}
  .profile-form-field input::placeholder,.profile-form-field textarea::placeholder{color:var(--text-dim);}
  .profile-form-error{font-size:11.5px;color:var(--red);display:none;line-height:1.5;}
  @media (max-width:560px){.profile-modal-card{width:100%;padding:46px 20px 22px;}}

  .content{padding:30px 32px 64px;max-width:1180px;position:relative;z-index:1;}
  .side-collapse-btn{position:absolute;right:-1px;top:50%;transform:translateY(-50%);background:var(--panel,var(--surface));border:1px solid var(--border-strong,var(--border-soft));border-radius:8px;width:16px;height:52px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-muted);box-shadow:0 2px 8px rgba(0,0,0,.3);z-index:6;transition:right .3s cubic-bezier(.4,0,.2,1),background .15s ease,color .15s ease,border-color .15s ease;}
  .side-collapse-btn:hover{background:var(--gold-dim);color:var(--gold-bright);border-color:var(--gold,var(--border-strong));}
  .side-collapse-btn svg{width:11px;height:11px;transition:transform .25s ease;}
  .sidebar.collapsed{width:76px;}
  .sidebar.collapsed .side-collapse-btn{right:-16px;}
  .sidebar.collapsed .side-collapse-btn svg{transform:rotate(180deg);}
  .sidebar.collapsed .side-brand{padding:0;justify-content:center;}
  .sidebar.collapsed .side-brand .logo{display:none;}
  .sidebar.collapsed .side-nav-label,.sidebar.collapsed .side-text,.sidebar.collapsed .chevron,.sidebar.collapsed .side-subnav{display:none;}
  .sidebar.collapsed .side-link,.sidebar.collapsed .side-nav-group-title{justify-content:center;padding:10px;gap:0;}
  .sidebar{transition:width .25s cubic-bezier(.4,0,.2,1);}
  .tab-panel{display:none;}
  .tab-panel.active{display:block;animation:fadeIn .25s ease;}
  @keyframes fadeIn{from{opacity:0;transform:translateY(6px);}to{opacity:1;transform:translateY(0);}}

  .section-head{margin-bottom:20px;}
  .section-head h2{font-family:var(--display);font-size:22px;font-weight:700;margin-bottom:5px;letter-spacing:.01em;}
  .section-head p{font-size:13px;color:var(--text-muted);}

  .dash-hero{display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:18px;margin-bottom:24px;padding:24px 26px;position:relative;background:linear-gradient(180deg, rgba(255,255,255,.02), transparent), var(--panel);border:1px solid var(--border-soft);border-radius:12px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25);}
  .dash-hero::before{content:"";position:absolute;top:0;left:14px;right:14px;height:1px;background:linear-gradient(90deg, transparent, var(--border-strong), transparent);}
  .dash-hero-eyebrow{font-family:var(--mono);font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--gold-bright);margin-bottom:8px;}
  .dash-hero h2{font-family:var(--display);font-size:26px;font-weight:700;margin-bottom:6px;}
  .dash-hero p{font-size:13px;color:var(--text-muted);}
  @media(max-width:700px){.dash-hero{padding:20px;}.dash-hero h2{font-size:22px;}}

  .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:26px;}
  .stat-card{padding:19px;position:relative;background:linear-gradient(180deg, rgba(255,255,255,.02), transparent), var(--panel);border:1px solid var(--border-soft);border-radius:12px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25);}
  .stat-card::before{content:"";position:absolute;top:0;left:14px;right:14px;height:1px;background:linear-gradient(90deg, transparent, var(--border-strong), transparent);}
  .stat-card .lbl{font-family:var(--mono);font-size:10.5px;color:var(--text-dim);text-transform:uppercase;letter-spacing:.06em;margin-bottom:9px;}
  .stat-card .val{font-family:var(--display);font-size:28px;font-weight:700;color:var(--gold-bright);}
  .stat-card .sub{font-size:11.5px;color:var(--text-dim);margin-top:5px;}
  @media(max-width:980px){.stat-grid{grid-template-columns:repeat(2,1fr);}}

  .panel{padding:24px;margin-bottom:20px;position:relative;background:linear-gradient(180deg, rgba(255,255,255,.02), transparent), var(--panel);border:1px solid var(--border-soft);border-radius:12px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25);}
  .panel::before{content:"";position:absolute;top:0;left:14px;right:14px;height:1px;background:linear-gradient(90deg, transparent, var(--border-strong), transparent);}
  .panel-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap;}
  .panel-head h3{font-family:var(--display);font-size:17px;font-weight:700;letter-spacing:.01em;}
  .panel-head p{font-size:12px;color:var(--text-muted);margin-top:2px;}

  .tbl-wrap{overflow-x:auto;}
  table.dtbl{width:100%;border-collapse:collapse;font-size:13px;}
  table.dtbl th{text-align:left;font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;text-transform:uppercase;color:var(--text-dim);padding:11px 12px;border-bottom:1px solid var(--border-soft);white-space:nowrap;}
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
  .status-dot.ok{color:var(--gold-bright);}
  .status-dot.warn{color:var(--amber);}
  .status-dot.bad{color:var(--red);}

  .gauge-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:14px;}
  .gauge-card{padding:16px;position:relative;background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:10px;}
  .gauge-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:14px;}
  .gauge-card-name{font-family:var(--display);font-weight:700;font-size:14px;}
  .gauge-card-url{font-family:var(--mono);font-size:10.5px;color:var(--text-dim);margin-top:2px;}
  .meter{margin-bottom:10px;}
  .meter-row{display:flex;justify-content:space-between;font-size:11.5px;color:var(--text-muted);margin-bottom:5px;}
  .meter-row span:last-child{font-family:var(--mono);color:var(--text);font-weight:600;}
  .progress-bar{height:7px;border-radius:5px;background:var(--bg-deep);border:1px solid var(--border-soft);overflow:hidden;}
  .progress-fill{height:100%;border-radius:5px;transition:width .3s ease;}
  .progress-fill.ok{background:var(--gold-bright);}
  .progress-fill.warn{background:var(--amber);}
  .progress-fill.bad{background:var(--red);}
  .meter-foot{display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;font-size:11px;color:var(--text-dim);margin-top:12px;padding-top:10px;border-top:1px solid var(--border-soft);}
  .meter-foot b{color:var(--text);}

  .period-toggle{display:flex;gap:8px;flex-wrap:wrap;}
  .period-toggle .btn{text-transform:none;}

  .confirm-overlay{position:fixed;inset:0;z-index:80;background:rgba(2,4,6,.6);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity .2s ease;}
  :root[data-theme="light"] .confirm-overlay{background:rgba(60,50,20,.35);}
  .confirm-overlay.open{opacity:1;pointer-events:auto;}
  .confirm-box{width:100%;max-width:360px;background:var(--panel-2);border:1px solid var(--border);border-radius:14px;padding:28px 26px 24px;text-align:center;transform:translateY(10px) scale(.98);transition:transform .2s ease;box-shadow:0 20px 50px -20px rgba(0,0,0,.5);}
  .confirm-overlay.open .confirm-box{transform:translateY(0) scale(1);}
  .confirm-icon{width:52px;height:52px;margin:0 auto 14px;border-radius:50%;background:var(--red-dim);color:var(--red);display:flex;align-items:center;justify-content:center;}
  .confirm-icon svg{width:24px;height:24px;stroke:currentColor;fill:none;stroke-width:1.9;}
  .confirm-box h3{font-family:var(--display);font-size:19px;font-weight:700;margin:0 0 8px;}
  .confirm-box p{font-size:13px;color:var(--text-muted);line-height:1.6;margin:0;}
  .confirm-actions{display:flex;gap:10px;margin-top:22px;}
  .confirm-actions .btn{flex:1;justify-content:center;}

  .btn{font-family:var(--mono);font-weight:600;font-size:11.5px;letter-spacing:.04em;padding:9px 15px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;text-transform:uppercase;transition:transform .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;}
  .btn:hover{border-color:var(--gold);color:var(--gold-bright);transform:translateY(-1px);}
  .btn-primary{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid));color:var(--on-gold);border-color:transparent;box-shadow:0 8px 22px -8px rgba(217,146,11,.5);}
  .btn-primary:hover{color:var(--on-gold);box-shadow:0 10px 26px -6px rgba(217,146,11,.6);}
  .btn-ghost-red{color:var(--red);border-color:rgba(198,40,40,.3);}
  .btn-ghost-red:hover{border-color:var(--red);color:var(--red);}
  .btn-sm{padding:7px 12px;font-size:10.5px;}
  .btn-row{display:flex;gap:8px;flex-wrap:wrap;}

  .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
  .form-field{display:flex;flex-direction:column;gap:6px;}
  .form-field.full{grid-column:1/-1;}
  .form-field label{font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;color:var(--text-dim);text-transform:uppercase;}
  .form-field input,.form-field select,.form-field textarea{border:1px solid var(--border);border-radius:10px;padding:11px 13px;font-family:var(--body);font-size:13.5px;background:var(--bg-deep);color:var(--text);}
  .form-field select{cursor:pointer;}
  .form-field select option{background:var(--panel-2);color:var(--text);padding:10px 14px;margin:2px 0;}
  .form-field input:focus,.form-field select:focus,.form-field textarea:focus{outline:none;border-color:var(--gold);}
  .form-field input::placeholder,.form-field textarea::placeholder{color:var(--text-dim);}
  .form-hint{font-size:11px;color:var(--text-dim);}
  @media(max-width:640px){.form-grid{grid-template-columns:1fr;}}

  .notice{background:var(--gold-dim);border:1px solid var(--border);color:var(--text);border-radius:10px;padding:14px 16px;font-size:12.5px;line-height:1.65;margin-bottom:22px;}
  .notice b{color:var(--gold-bright);}
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

  .toast-stack{position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:2000;width:min(400px,86vw);pointer-events:none;height:0;}
  .toast{position:absolute;top:0;left:50%;overflow:hidden;pointer-events:auto;display:flex;align-items:center;gap:11px;padding:13px 18px 15px;border-radius:11px;background:var(--panel);border:1px solid var(--border-soft);box-shadow:0 15px 40px rgba(0,0,0,.35);font-family:var(--body);font-size:13px;color:var(--text);width:100%;box-sizing:border-box;opacity:0;transform:translate(-50%,-26px) scale(.97);transition:top .8s cubic-bezier(.34,1.2,.64,1);animation:toastIn .35s cubic-bezier(.34,1.56,.64,1) forwards;}
  .toast.leaving{animation:toastOut .4s cubic-bezier(.4,0,.2,1) forwards;}
  .toast.success{border-color:rgba(217,146,11,.4);}
  .toast.success .toast-icon{background:var(--gold-dim);color:var(--gold-bright);}
  .toast.success .toast-bar{background:var(--gold-bright);}
  .toast.error{border-color:rgba(198,40,40,.35);}
  .toast.error .toast-icon{background:var(--red-dim);color:var(--red);}
  .toast.error .toast-bar{background:var(--red);}
  .toast-icon{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
  .toast-icon svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.4;}
  .toast-body{display:flex;flex-direction:column;gap:2px;min-width:0;}
  .toast-label{font-family:var(--mono);font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;}
  .toast.success .toast-label{color:var(--gold-bright);}
  .toast.error .toast-label{color:var(--red);}
  .toast-text{font-family:var(--body);font-size:13.5px;font-weight:600;line-height:1.4;color:var(--text);}
  .toast-bar{position:absolute;left:0;bottom:0;height:3px;width:100%;transform-origin:left;animation:toastBar 3s linear forwards;}
  @keyframes toastIn{to{opacity:1;transform:translate(-50%,0) scale(1);}}
  @keyframes toastOut{0%{opacity:1;transform:translate(-50%,0) scale(1);}35%{opacity:1;transform:translate(-50%,-6px) scale(1.05);}100%{opacity:0;transform:translate(-50%,-30px) scale(.9);}}
  @keyframes toastBar{from{transform:scaleX(1);}to{transform:scaleX(0);}}
</style>
<script>
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
  function siberadRelayoutToasts(stack){
    requestAnimationFrame(function(){
      var y = 0, gap = 10;
      Array.prototype.forEach.call(stack.children, function(el){
        el.style.top = y + 'px';
        y += el.offsetHeight + gap;
      });
    });
  }
  window.siberadShowToast = siberadShowToast;
  @if(session('login_success'))
    document.addEventListener('DOMContentLoaded', function(){
      siberadShowToast('success', {!! json_encode('Selamat Datang '.session('login_success')) !!});
    });
  @endif

  function siberadInitSidebarCollapse(){
    var sidebar = document.getElementById('sidebar');
    var btn = document.getElementById('sideCollapseBtn');
    if(!sidebar || !btn) return;
    var KEY = 'siberad-sidebar-collapsed';
    var pendingApply = 0;
    function apply(collapsed){
      if (collapsed) {
        document.querySelectorAll('.side-nav-group.open').forEach(function(g){ g.classList.remove('open'); });
      }
      sidebar.classList.toggle('collapsed', collapsed);
      if (!collapsed && typeof window.siberadRestoreGroupState === 'function') {
        window.siberadRestoreGroupState();
      }
      pendingApply++;
      var myApply = pendingApply;
      setTimeout(function(){
        if(myApply !== pendingApply) return;
        var content = document.querySelector('.content');
        if(content) content.style.maxWidth = collapsed ? '1360px' : '';
        var pimpPage = document.querySelector('.pimp-page');
        if(pimpPage) pimpPage.style.maxWidth = collapsed ? '1680px' : '';
        (window.siberadCharts || []).forEach(function(chart){
          try{ chart.resize(); }catch(e){}
        });
        if (typeof window.siberadRepositionSubnavFlyouts === 'function') {
          window.siberadRepositionSubnavFlyouts();
        }
      }, 260);
    }
    btn.addEventListener('click', function(){
      var collapsed = !sidebar.classList.contains('collapsed');
      apply(collapsed);
      try{ localStorage.setItem(KEY, collapsed ? '1' : '0'); }catch(e){}
    });
    try{
      if(localStorage.getItem(KEY) === '1') apply(true);
    }catch(e){}
  }
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', siberadInitSidebarCollapse);
  } else {
    siberadInitSidebarCollapse();
  }
</script>