<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — SIBERAD</title>
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo-pussiberad.jpg') }}">
@include('siberad.dashboards.partials.dash-styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
  /* Samakan palet mode terang dengan dashboard Pimpinan (abu-abu netral/putih), bukan cream bawaan. */
  :root[data-theme="light"]{
    --bg:#f5f7f9;--bg-deep:#ffffff;--panel:#ffffff;--panel-2:#f8fafc;--panel-alt:#f8fafc;
    --border:#e2e8f0;--border-soft:#e2e8f0;--border-strong:#cbd5e1;
    --gold:#c97a00;--gold-bright:#c97a00;--gold-dim:rgba(201,122,0,.12);
    --green:#16834b;--green-bright:#16834b;--green-dim:rgba(22,131,75,.12);
    --amber:#b77900;--amber-dim:rgba(183,121,0,.14);
    --red:#c83b3b;--red-dim:rgba(200,59,59,.12);
    --text:#17212b;--text-muted:#64748b;--text-dim:#64748b;
    --surface:rgba(255,255,255,.9);--hover-tint:rgba(15,23,42,.035);
  }
  .chart-box{margin-bottom:26px;}
  .chart-box-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
  .chart-mini{background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:12px;padding:16px;transition:border-color .15s ease,box-shadow .15s ease,background-color .15s ease;}
  .chart-mini:hover{border-color:var(--border-strong);box-shadow:0 6px 16px rgba(0,0,0,.12);}
  .chart-mini-head{margin-bottom:10px;}
  .chart-mini-head h4{font-family:var(--display);font-size:13px;font-weight:700;letter-spacing:.01em;line-height:1.3;}
  .chart-mini-head p{font-size:11px;color:var(--text-muted);margin-top:2px;}
  .chart-mini .chart-wrap{position:relative;height:178px;}
  .chart-mini .chart-legend{display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:6px 10px;margin-top:10px;}
  .chart-mini .chart-legend-item{display:flex;align-items:center;gap:5px;font-size:10.5px;font-weight:600;color:var(--text-muted);white-space:nowrap;cursor:pointer;user-select:none;}
  .chart-mini .chart-legend-item.is-hidden{text-decoration:line-through;opacity:.5;}
  .chart-mini .chart-legend-dot{width:8px;height:8px;border-radius:50%;flex:0 0 auto;}
  @media(max-width:980px){.chart-box-grid{grid-template-columns:1fr;}.chart-mini .chart-wrap{height:198px;}}

  /* ===== toggle "Lihat Detail per Satuan" di header chart Total Laporan
     per Satuan -- tabel Detail per Satuan disembunyikan default supaya
     chart-nya sendiri punya ruang lebih leluasa, baru muncul (dengan
     scrollbar sendiri di dalam) begitu tombol ini diklik ===== */
  .chart-mini-head-row{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;}
  .chart-mini-head-row h4{margin:0;}
  /* Panel "Total Laporan per Satuan" (Ringkasan Data) butuh header block biasa
     (judul di kiri, tombol "Lihat Detail per Satuan" di kanan lewat
     .chart-mini-head-row di atas), bukan flex-row ikon+teks seperti 3 kartu
     ringkasan lain yang memakai .chart-mini-head -- makanya di-scope lewat
     class tambahan ini supaya nggak ikut kena aturan .chart-mini-head{display:flex}
     di bawah dan tombolnya nggak lagi turun ke bawah judul. */
  .chart-mini-head.chart-mini-head-rekap{display:block;}
  .btn-toggle-detail{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;border:1px solid var(--border-soft);background:var(--panel);font-family:inherit;font-size:11px;font-weight:700;color:var(--text-muted);cursor:pointer;white-space:nowrap;transition:border-color .15s ease,color .15s ease;}
  .btn-toggle-detail:hover{border-color:var(--gold-bright);color:var(--text);}
  .btn-toggle-detail .chevron{transition:transform .2s ease;flex:0 0 auto;}
  .btn-toggle-detail[aria-expanded="true"] .chevron{transform:rotate(180deg);}

  /* ===== toolbar cari & filter tabel =====
     Disamakan gayanya dengan .rpt-filter-bar/.danpus-log-search (Pimpinan):
     tinggi 38px, radius 9px, ikon di posisi yang sama, dan teks jumlah hasil
     nempel pojok kanan lewat margin-left:auto -- 1 sistem, bukan style sendiri2. */
  .table-toolbar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;align-items:center;}
  .table-search-wrap{position:relative;flex:1 1 240px;min-width:200px;max-width:360px;}
  .table-search-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:15px;height:15px;stroke:var(--text-dim);pointer-events:none;}
  .table-search{
    width:100%;box-sizing:border-box;height:38px;background:var(--panel);border:1px solid var(--border);color:var(--text);
    font-family:var(--body);font-size:13px;border-radius:9px;padding:8px 12px 8px 34px;
  }
  .table-search::placeholder{color:var(--text-dim);}
  .table-search:focus{outline:none;border-color:var(--gold);}
  .table-filter{
    box-sizing:border-box;background:var(--panel);border:1px solid var(--border);color:var(--text);font-family:var(--mono);
    font-size:11.5px;letter-spacing:.02em;border-radius:9px;padding:0 28px 0 10px;cursor:pointer;flex-shrink:0;
    min-width:0;width:150px;height:38px;
  }
  .table-filter:focus{outline:none;border-color:var(--gold);}
  /* Filter kategori di baris search+tanggal (Riwayat Aktivitas & Data
     Laporan) -- lebar dipatok tetap (bukan cuma min-width) supaya select
     ini nggak melebar penuh 1 baris sendiri waktu dia yang kena wrap ke
     baris baru; ukurannya disamakan sependek filter kategori di Daftar
     Pengguna, bukan selebar teks opsi terpanjangnya ("Unsur Pembantu
     Pimpinan") kayak sebelumnya. */
  .dl-kategori-filter{width:150px!important;max-width:150px!important;flex:0 0 150px!important;min-width:0!important;padding:0 28px 0 10px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  @media(max-width:640px){.dl-kategori-filter{width:100%!important;max-width:none!important;flex:1 1 auto!important;}}
  .table-filter-count{font-size:10px;color:var(--text-dim);white-space:nowrap;margin-left:auto;}
  .log-filter-row{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin:2px 0 16px;}
  .log-filter-field{display:flex;flex-direction:column;gap:4px;}
  .log-filter-field label{font-size:10px;color:var(--text-dim);font-family:var(--mono);text-transform:uppercase;letter-spacing:.04em;}
  .log-filter-field .table-filter{width:auto;min-width:140px;}
  .log-filter-reset{box-sizing:border-box;width:38px;height:38px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--panel);border:1px solid var(--border);border-radius:9px;color:var(--text-dim);cursor:pointer;transition:background .15s ease,color .15s ease,border-color .15s ease;}
  .log-filter-reset svg{width:16px;height:16px;}
  .log-filter-reset:hover{background:var(--hover-tint);color:var(--gold-bright);border-color:var(--gold);}
  .log-filter-reset.spinning svg{animation:logFilterResetSpin .5s ease;}
  @keyframes logFilterResetSpin{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}
  @media(max-width:640px){.log-filter-row{flex-direction:column;align-items:stretch;}.log-filter-row .table-filter{width:100%;}.log-filter-reset{width:100%;}}
  .table-empty-row td{text-align:center;color:var(--text-dim);font-size:12.5px;padding:26px 12px !important;}
  @media(max-width:640px){.table-toolbar{flex-direction:column;align-items:stretch;}.table-search-wrap{flex:none;max-width:none;}.table-filter{width:100%;}.table-filter-count{width:100%;margin-left:0;}}

  /* ===== badge status Rekap Laporan (warna tetap hijau/merah/oren asli,
     tidak ikut --green yang di-repurpose jadi gold di tempat lain) ===== */
  .badge-status{display:inline-flex;align-items:center;justify-content:center;min-width:34px;font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;padding:7px 14px;border-radius:8px;text-transform:uppercase;border:1px solid transparent;box-sizing:border-box;line-height:1.2;}
  .badge-status.ok{background:rgba(34,197,94,.14);color:#22c55e;border-color:rgba(34,197,94,.32);}
  .badge-status.bad{background:rgba(239,68,68,.14);color:#ef4444;border-color:rgba(239,68,68,.32);}
  .badge-status.wait{background:rgba(245,158,11,.14);color:#f59e0b;border-color:rgba(245,158,11,.32);}
  .badge-status.late{background:rgba(255,107,107,.15);color:#ff6b6b;border-color:rgba(255,107,107,.32);}
  .badge-status.cancelled{background:rgba(193,18,31,.16);color:#c1121f;border-color:rgba(193,18,31,.34);}

  /* ===== modal Tambah Pengguna ===== */
  .user-modal-overlay{
    position:fixed;inset:0;z-index:10030;padding:24px;box-sizing:border-box;
    background:rgba(2,4,6,.6);backdrop-filter:blur(4px);
    display:flex;align-items:center;justify-content:center;
    opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease;
  }
  .user-modal-overlay.open{opacity:1;visibility:visible;pointer-events:auto;}
  .user-modal-card{
    width:440px;max-width:100%;max-height:88vh;overflow-x:hidden;overflow-y:auto;position:relative;box-sizing:border-box;
    background:var(--panel);border:1px solid var(--border-soft);border-radius:16px;
    box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 32px 80px rgba(0,0,0,.5);padding:24px;
    transform:translateY(14px) scale(.97);transition:transform .2s ease;
  }
  .user-modal-overlay.open .user-modal-card{transform:translateY(0) scale(1);}
  .user-modal-head{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:18px;}
  .user-modal-head h3{margin:0;font-family:var(--display);font-size:18px;color:var(--text);}
  .user-modal-head p{margin:5px 0 0;font-size:12px;color:var(--text-muted);}
  .user-modal-close{
    flex-shrink:0;width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;
    border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;
    transition:border-color .2s ease,color .2s ease,transform .2s ease;
  }
  .user-modal-close:hover{border-color:var(--red);color:var(--red);transform:rotate(90deg);}
  .user-modal-actions{grid-column:1/-1;display:flex;flex-direction:row;align-items:center;justify-content:flex-end;gap:10px;margin-top:4px;}
  @media(max-width:640px){.user-modal-actions{flex-direction:column-reverse;align-items:stretch;}}
  @media(max-width:640px){.user-modal-card{padding:20px;}}
  /* Form di dalam modal dibuat 1 kolom terus (bukan ikut breakpoint global .form-grid),
     supaya opsi teks panjang di dropdown Satuan tidak memepetkan/merusak layout 2 kolom
     di lebar modal yang terbatas (560px). */
  .user-modal-card .form-grid{grid-template-columns:1fr;min-width:0;}
  .user-modal-card .form-field{min-width:0;}
  .user-modal-card .form-field select,.user-modal-card .form-field input{min-width:0;width:100%;box-sizing:border-box;}

  /* ===== tombol aksi tabel Daftar Pengguna & Daftar Satuan (gaya sama
     seperti tombol Edit/Batal di Permintaan Laporan Pimpinan) ===== */
  .table-action-btn{
    border:1px solid var(--border-soft);background:var(--panel);color:var(--text);
    border-radius:8px;padding:7px 10px;font-family:var(--body);font-size:10px;font-weight:700;
    text-transform:none;letter-spacing:normal;cursor:pointer;
    transition:border-color .15s ease,background .15s ease,color .15s ease,filter .15s ease,transform .15s ease;
  }
  .table-action-btn:hover{border-color:var(--gold-bright);background:var(--panel-alt);}
  .table-action-btn:active{transform:scale(.96);}
  .table-action-btn.edit{background:color-mix(in srgb, var(--success-bright) 10%, transparent);border-color:color-mix(in srgb, var(--success-bright) 35%, transparent);color:var(--success-bright);}
  .table-action-btn.edit:hover{background:var(--success-bright);color:#fff;border-color:var(--success-bright);filter:brightness(1.08);}
  .table-action-btn.danger{background:color-mix(in srgb, var(--red) 10%, transparent);border-color:color-mix(in srgb, var(--red) 35%, transparent);color:var(--red);}
  .table-action-btn.danger:hover{background:var(--red);color:#fff;border-color:var(--red);filter:brightness(1.08);}
  .table-action-btn.icon-only{width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;background:color-mix(in srgb, var(--gold) 10%, transparent);border-color:color-mix(in srgb, var(--gold) 35%, transparent);color:var(--gold-bright);}
  .table-action-btn.icon-only:hover{background:var(--gold-bright);color:#fff;border-color:var(--gold-bright);filter:brightness(1.05);}
  .table-action-btn.icon-only svg{width:15px;height:15px;flex-shrink:0;}
  .th-center{text-align:center;}
  #tblPengguna th:last-child,#tblPengguna td:last-child,
  #tblSatuan th:last-child,#tblSatuan td:last-child{text-align:center;}
  #tblPengguna .btn-row,#tblSatuan .btn-row{justify-content:center;}
  #tblSatuan th:nth-child(4),#tblSatuan td:nth-child(4){text-align:center;}
  #tblResetPassword .subject{margin-bottom:6px;}
  #tblResetPassword th:nth-child(1),#tblResetPassword td:nth-child(1){text-align:left;}
  #tblResetPassword th:nth-child(2),#tblResetPassword td:nth-child(2){text-align:left;}
  #tblResetPassword th:nth-child(n+3),#tblResetPassword td:nth-child(n+3){text-align:center;}
  #tblResetPassword .btn-row{justify-content:center;}
  .request-deadline{display:inline-flex;align-items:center;gap:5px;font-weight:700;}
  .request-deadline svg{width:13px;height:13px;flex-shrink:0;opacity:.75;}

  .side-nav-group{margin:0}.side-nav-group-title{width:100%;display:flex;align-items:center;gap:10px;padding:10px 12px;margin:2px 0;border:1px solid transparent;border-radius:9px;background:transparent;color:var(--text-muted);font-family:var(--body);font-size:13.5px;font-weight:500;cursor:pointer;text-align:left;box-sizing:border-box;transition:background .15s ease,color .15s ease}.side-nav-group-title:hover{background:var(--hover-tint);color:var(--text)}.side-nav-group.open .side-nav-group-title{color:var(--text)}.side-nav-group-title .side-text{flex:1}.side-nav-group-title .chevron{margin-left:auto;width:15px;height:15px;flex-shrink:0;opacity:.6;transition:transform .25s cubic-bezier(.4,0,.2,1),opacity .2s ease}.side-nav-group.open .chevron{transform:rotate(180deg);opacity:1}.side-subnav{display:grid;grid-template-rows:0fr;opacity:0;transition:grid-template-rows .3s cubic-bezier(.4,0,.2,1),opacity .25s ease;overflow:hidden}.side-subnav>div{min-height:0;padding:3px 0;margin-left:18px;border-left:1px solid var(--border-soft)}.side-nav-group.open .side-subnav{grid-template-rows:1fr;opacity:1}.side-sub-link{position:relative;display:flex;align-items:center;gap:10px;padding:9px 12px 9px 17px;border-radius:0 9px 9px 0;color:var(--text-muted);font-family:var(--body);font-size:13px;font-weight:500;text-decoration:none;margin:1px 0;box-sizing:border-box;transition:background .15s ease,color .15s ease}.side-sub-link:hover{background:var(--hover-tint);color:var(--text)}.side-sub-link .sub-dot{width:5px;height:5px;border-radius:50%;background:currentColor;opacity:.5;flex:0 0 auto;transition:opacity .15s ease,background .15s ease,box-shadow .15s ease}.side-sub-link.active{background:var(--gold-dim);color:var(--gold-bright);font-weight:600}.side-sub-link.active:before{content:"";position:absolute;left:-1px;top:8px;bottom:8px;width:2px;border-radius:2px;background:var(--gold-bright)}.side-sub-link.active .sub-dot{background:var(--gold-bright);opacity:1;box-shadow:0 0 0 3px rgba(201,122,0,.15)}.side-subnav-label{display:none}
  .sidebar.collapsed .side-subnav{display:none}.sidebar.collapsed .side-nav-group.open .side-subnav{display:block;position:fixed;min-width:216px;background:var(--panel);border:1px solid var(--border-soft);border-radius:12px;box-shadow:0 14px 34px rgba(0,0,0,.22);padding:8px;z-index:100020}.sidebar.collapsed .side-subnav>div{margin-left:0;border-left:none;padding:0}.sidebar.collapsed .side-subnav-label{display:block;font-family:var(--mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);padding:4px 10px 8px}.sidebar.collapsed .side-sub-link{padding:9px 10px;border-radius:8px}.sidebar.collapsed .side-nav-group.has-active-child .side-nav-group-title{color:var(--gold-bright);background:var(--gold-dim)}
  @media(max-width:900px){.sidebar.collapsed .side-subnav{display:grid}.sidebar.collapsed .side-nav-group.open .side-subnav{position:static;top:auto!important;left:auto!important;min-width:0;background:none;border:0;box-shadow:none;padding:0;z-index:auto}.sidebar.collapsed .side-subnav>div{margin-left:18px;border-left:1px solid var(--border-soft);padding:3px 0}.sidebar.collapsed .side-subnav-label{display:none}}
</style>
<style>
.role-akses-table-panel{overflow:hidden!important}
.role-akses-table-wrap{overflow-x:hidden!important;width:100%!important}
.role-akses-table{width:100%!important;min-width:0!important;table-layout:fixed!important}
.role-akses-table th,.role-akses-table td{min-width:0!important;box-sizing:border-box}
.role-akses-table th:nth-child(1),.role-akses-table td:nth-child(1){width:20%!important}
.role-akses-table th:nth-child(2),.role-akses-table td:nth-child(2){width:27%!important}
.role-akses-table th:nth-child(3),.role-akses-table td:nth-child(3){width:41%!important}
.role-akses-table th:nth-child(4),.role-akses-table td:nth-child(4){width:12%!important}
.role-akses-table .role-akses-checks{display:flex!important;flex-direction:column!important;align-items:flex-start!important;gap:9px!important}
.role-akses-table .role-akses-check{display:flex!important;width:100%!important;white-space:normal!important}
.role-akses-action-head,.role-akses-action{text-align:center!important;vertical-align:middle!important}
</style>
<style>
  /* ===== Data Laporan (submenu Monitoring) ===== */
  .dl-head{display:flex;align-items:flex-start;gap:14px;}
  .dl-head-panel{margin-bottom:20px;}
  .dl-head-icon{flex:0 0 auto;width:46px;height:46px;border-radius:12px;background:var(--gold-dim);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--gold-bright);}
  .dl-head-icon svg{width:22px;height:22px;stroke:currentColor;fill:none;stroke-width:1.8;}
  .dl-head h2{font-family:var(--display);font-size:21px;font-weight:700;letter-spacing:.01em;}
  .dl-head p{font-size:12.5px;color:var(--text-muted);margin-top:3px;}

  .dl-tabs{display:flex;gap:10px;flex-wrap:wrap;padding-bottom:20px;margin-bottom:20px;border-bottom:1px solid var(--border-soft);}
  .dl-tab{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:10px;border:1px solid transparent;background:var(--gold-dim);color:var(--gold-bright);font-family:var(--body);font-size:13.5px;font-weight:700;cursor:pointer;transition:background .15s ease,color .15s ease,box-shadow .15s ease;}
  .dl-tab svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0;}
  .dl-tab.active{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid));color:var(--on-gold);box-shadow:0 8px 20px -8px rgba(217,146,11,.55);}
  .dl-tab:not(.active):hover{background:var(--hover-tint);color:var(--text);}

  .dl-section{display:none;}
  .dl-section.active{display:block;animation:fadeIn .2s ease;}
  .dl-section-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:16px;}
  .dl-section-head h3{font-family:var(--display);font-size:16.5px;font-weight:700;}
  .dl-section-head p{font-size:12px;color:var(--text-muted);margin-top:3px;}

  .dl-download{position:relative;flex-shrink:0;}
  .dl-download-btn{display:inline-flex;align-items:center;gap:8px;}
  .dl-download-btn svg.chev{width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:2.4;transition:transform .18s ease;}
  .dl-download.open .dl-download-btn svg.chev{transform:rotate(180deg);}
  .dl-download-menu{display:none;position:absolute;top:calc(100% + 6px);right:0;min-width:200px;background:var(--panel);border:1px solid var(--border-soft);border-radius:10px;padding:6px;box-shadow:0 14px 34px rgba(0,0,0,.22);z-index:30;}
  .dl-download.open .dl-download-menu{display:block;}
  .dl-download-menu a{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:7px;color:var(--text);text-decoration:none;font-size:12.5px;font-weight:600;}
  .dl-download-menu a:hover{background:var(--hover-tint);color:var(--gold-bright);}
  .dl-download-menu a svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.8;flex-shrink:0;}

  .dl-search-row{display:flex;align-items:center;gap:12px;margin-bottom:6px;flex-wrap:wrap;}
  .dl-search-count{font-size:12px;color:var(--text-dim);white-space:nowrap;margin-left:auto;}
  .dl-date-filter{display:flex;flex-direction:row;align-items:center;gap:6px;}
  .dl-date-filter label{font-size:10px;color:var(--text-dim);font-family:var(--mono);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;}
  .dl-date-filter .table-filter{width:auto;min-width:140px;}
  .dl-filter-reset{box-sizing:border-box;width:38px;height:38px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--panel);border:1px solid var(--border);border-radius:9px;color:var(--text-dim);cursor:pointer;transition:background .15s ease,color .15s ease,border-color .15s ease;align-self:center;}
  .dl-filter-reset svg{width:16px;height:16px;}
  .dl-filter-reset:hover{background:var(--hover-tint);color:var(--gold-bright);border-color:var(--gold);}
  .dl-filter-reset.spinning svg{animation:logFilterResetSpin .5s ease;}
  @media(max-width:640px){.dl-search-row{flex-direction:column;align-items:stretch;}.dl-date-filter{flex-direction:column;align-items:stretch;gap:4px;}.dl-date-filter .table-filter{width:100%;}.dl-filter-reset{width:100%;height:38px;}.dl-search-count{margin-left:0;}}

  .dl-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:14px;}
  .dl-foot p{font-size:11.5px;color:var(--text-dim);}
  @media(max-width:640px){.dl-section-head{flex-direction:column;}.dl-download{align-self:stretch;}.dl-download-btn{width:100%;justify-content:center;}.dl-search-row{flex-direction:column;align-items:stretch;}.dl-search-count{margin-left:0;}.dl-foot{flex-direction:column;align-items:stretch;text-align:center;}}
</style>
</head>
<body>
<div class="profile-modal-overlay" id="profileModalOverlay">
  <div class="profile-modal-card" id="profileModalCard" role="dialog" aria-modal="true" aria-label="Detail profil">
    <button type="button" class="profile-modal-close" id="profileModalCloseBtn" aria-label="Tutup">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>
    </button>

    {{-- ===== VIEW PENGATURAN AKUN (cuma Foto Profil -- Admin tidak lewat
         alur permintaan ganti password, karena Admin sendiri yang menyetujui
         permintaan itu) ===== --}}
    <div class="profile-dropdown-view" id="profileSettingsView" style="display:none;">
      <div class="profile-modal-title">Pengaturan Akun</div>

      <div class="profile-dropdown-head-lg">
        <div class="profile-dropdown-avatar-lg">
          <span class="profile-initial" id="profileInitialLarge" style="display:{{ $user->foto_path ? 'none' : '' }};">{{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}</span>
          <img class="profile-photo" id="profilePhotoLarge" alt="Foto profil {{ $user->name }}" @if($user->foto_path) src="{{ asset('storage/'.$user->foto_path) }}" style="display:block;" @endif>
        </div>
        <div class="profile-dropdown-name">{{ $user->name }}</div>
        <div class="profile-dropdown-role">{{ $user->jabatan ?? 'Pengguna' }}</div>
      </div>

      <div class="profile-photo-actions">
        <form method="POST" action="{{ route('profil-foto.update') }}" enctype="multipart/form-data" id="formGantiFoto">
          @csrf
          <button type="button" class="profile-btn profile-btn-primary" id="gantiFotoBtn">
            <span id="gantiFotoLabel">Ganti Foto</span>
          </button>
          <input type="file" name="foto" id="fotoProfilInput" accept="image/png,image/jpeg,image/webp" hidden>
        </form>
        <button type="button" class="profile-btn profile-btn-outline" id="hapusFotoBtn" style="display:{{ $user->foto_path ? '' : 'none' }};">
          <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>
          Hapus
        </button>
      </div>
      <p class="profile-photo-hint">Format JPG, PNG, atau WEBP — ukuran maksimal 10 MB.</p>
    </div>

    {{-- ===== VIEW BANTUAN & PANDUAN ===== --}}
    <div class="profile-dropdown-view" id="profileHelpView" style="display:none;">
      <div class="profile-modal-title">Bantuan &amp; Panduan</div>
      <p class="help-intro">Ringkasan singkat menu utama di dashboard Admin.</p>

      <div class="help-topics">
        <div class="help-topic">
          <div class="help-topic-icon">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
          <div class="help-topic-body">
            <div class="help-topic-title">Kelola Pengguna</div>
            <div class="help-topic-desc">Tambah, ubah, atau nonaktifkan akun, serta proses permintaan reset password.</div>
          </div>
        </div>
        <div class="help-topic">
          <div class="help-topic-icon">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
          </div>
          <div class="help-topic-body">
            <div class="help-topic-title">Monitoring</div>
            <div class="help-topic-desc">Pantau rekap &amp; ekspor laporan dari seluruh satuan, lihat siapa saja yang sedang aktif login lewat Pengguna Aktif, serta telusuri log aktivitas sistem.</div>
          </div>
        </div>
        <div class="help-topic">
          <div class="help-topic-icon">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
          </div>
          <div class="help-topic-body">
            <div class="help-topic-title">Kelola Sistem</div>
            <div class="help-topic-desc">Atur data satuan, role &amp; hak akses, backup database, hingga pengaturan umum aplikasi.</div>
          </div>
        </div>
      </div>

      <div class="help-footer">
        <div class="help-footer-icon">
          <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 6-10 7L2 6"></path></svg>
        </div>
        <p>Butuh bantuan lebih lanjut? Hubungi <b>tim IT/Pussiberad</b> melalui jalur koordinasi internal.</p>
      </div>
    </div>

  </div>
</div>

<div class="user-modal-overlay" id="tambahPenggunaModal">
  <div class="user-modal-card" role="dialog" aria-modal="true" aria-label="Tambah Pengguna">
    <div class="user-modal-head">
      <div>
        <h3>Tambah Pengguna</h3>
        <p>Buat akun baru untuk satu satuan.</p>
      </div>
      <button type="button" class="user-modal-close" id="tambahPenggunaClose" aria-label="Tutup">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;"><path d="M6 6l12 12M18 6L6 18"></path></svg>
      </button>
    </div>
    <form class="form-grid" method="POST" action="{{ route('admin.users.store') }}" autocomplete="off">
      @csrf
      <div class="form-field">
        <label for="uNama">Nama Lengkap</label>
        <input id="uNama" name="name" type="text" autocomplete="off" placeholder="Contoh: Budi Santoso" required>
      </div>
      <div class="form-field">
        <label for="uUsername">Username / NRP</label>
        <input id="uUsername" name="username" type="text" autocomplete="off" placeholder="Contoh: budisantoso" required>
      </div>
      <div class="form-field">
        <label for="uEmail">Email (opsional)</label>
        <input id="uEmail" name="email" type="email" autocomplete="off" placeholder="Contoh: nama@email.com">
      </div>
      <div class="form-field">
        <label for="uSatuan">Satuan</label>
        <select id="uSatuan" name="satuan_id" required>
          <option value="">— Pilih Satuan —</option>
          @foreach($semuaSatuan as $s)
          <option value="{{ $s->id }}">{{ $s->nama }}{{ in_array($s->kode, ['URDAL', 'POKANALIS'], true) ? '' : ' ('.$s->kode.')' }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-field">
        <label for="uPassword">Password Awal</label>
        <input id="uPassword" name="password" type="text" autocomplete="off" required placeholder="Password awal">
      </div>
      <div class="user-modal-actions">
        <button class="btn" type="button" id="tambahPenggunaCancel">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan Pengguna</button>
      </div>
    </form>
  </div>
</div>

<div class="confirm-overlay" id="tambahPenggunaKonfirmasiOverlay">
  <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="tambahPenggunaKonfirmasiTitle">
    <div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><circle cx="12" cy="12" r="9"></circle><path d="M9 12l2 2 4-4"></path></svg>
    </div>
    <h3 id="tambahPenggunaKonfirmasiTitle">Tambah Pengguna Ini?</h3>
    <p>Akun baru akan langsung aktif dan bisa dipakai untuk login.</p>
    <div class="confirm-actions">
      <button type="button" class="btn" id="tambahPenggunaKonfirmasiBatal">Batal</button>
      <button type="button" class="btn btn-primary" id="tambahPenggunaKonfirmasiYa">Ya, Tambah</button>
    </div>
  </div>
</div>

<div class="user-modal-overlay" id="ubahPenggunaModal">
  <div class="user-modal-card" role="dialog" aria-modal="true" aria-label="Ubah Pengguna">
    <div class="user-modal-head">
      <div>
        <h3>Ubah Pengguna</h3>
        <p>Perbarui data akun pengguna.</p>
      </div>
      <button type="button" class="user-modal-close" id="ubahPenggunaClose" aria-label="Tutup">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;"><path d="M6 6l12 12M18 6L6 18"></path></svg>
      </button>
    </div>
    <form class="form-grid" method="POST" action="" id="ubahPenggunaForm" autocomplete="off">
      @csrf
      @method('PATCH')
      <div class="form-field">
        <label for="upNama">Nama Lengkap</label>
        <input id="upNama" name="name" type="text" autocomplete="off" placeholder="Contoh: Budi Santoso" required>
      </div>
      <div class="form-field">
        <label for="upUsername">Username / NRP</label>
        <input id="upUsername" name="username" type="text" autocomplete="off" placeholder="Contoh: budisantoso" required>
      </div>
      <div class="form-field">
        <label for="upEmail">Email (opsional)</label>
        <input id="upEmail" name="email" type="email" autocomplete="off" placeholder="Contoh: nama@email.com">
      </div>
      <div class="form-field">
        <label for="upSatuan">Satuan</label>
        <select id="upSatuan" name="satuan_id" required>
          @foreach($semuaSatuan as $s)
          <option value="{{ $s->id }}">{{ $s->nama }}{{ in_array($s->kode, ['URDAL', 'POKANALIS'], true) ? '' : ' ('.$s->kode.')' }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-field">
        <label for="upPassword">Password Baru (opsional)</label>
        <input id="upPassword" name="password" type="text" autocomplete="off" placeholder="Kosongkan jika tidak diubah">
      </div>
      <div class="user-modal-actions">
        <button class="btn" type="button" id="ubahPenggunaCancel">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<div class="user-modal-overlay" id="ubahPasswordPenggunaModal">
  <div class="user-modal-card" role="dialog" aria-modal="true" aria-label="Ubah Password Pengguna">
    <div class="user-modal-head">
      <div>
        <h3>Ubah Password</h3>
        <p>Akun: <strong id="uppNamaLabel">-</strong> (<span id="uppUsernameLabel">-</span>)</p>
      </div>
      <button type="button" class="user-modal-close" id="ubahPasswordPenggunaClose" aria-label="Tutup">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;"><path d="M6 6l12 12M18 6L6 18"></path></svg>
      </button>
    </div>
    <form class="form-grid" method="POST" action="" id="ubahPasswordPenggunaForm" autocomplete="off">
      @csrf
      @method('PATCH')
      <input type="hidden" name="name" id="uppName">
      <input type="hidden" name="username" id="uppUsername">
      <input type="hidden" name="email" id="uppEmail">
      <input type="hidden" name="satuan_id" id="uppSatuanId">
      <div class="form-field">
        <label for="uppPassword">Password Baru</label>
        <input id="uppPassword" name="password" type="text" autocomplete="off" placeholder="Masukkan password baru" required minlength="6">
      </div>
      <div class="user-modal-actions">
        <button class="btn" type="button" id="ubahPasswordPenggunaCancel">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan Password</button>
      </div>
    </form>
  </div>
</div>

<div class="confirm-overlay" id="ubahPasswordPenggunaKonfirmasiOverlay">
  <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="ubahPasswordPenggunaKonfirmasiTitle">
    <div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
    </div>
    <h3 id="ubahPasswordPenggunaKonfirmasiTitle">Ubah Password Akun Ini?</h3>
    <p>Password akun <strong id="uppKonfirmasiNama">ini</strong> akan langsung diganti dan tercatat di Riwayat Aktivitas.</p>
    <div class="confirm-actions">
      <button type="button" class="btn" id="ubahPasswordPenggunaKonfirmasiBatal">Batal</button>
      <button type="button" class="btn btn-primary" id="ubahPasswordPenggunaKonfirmasiYa">Ya, Ubah Password</button>
    </div>
  </div>
</div>

<div class="confirm-overlay" id="ubahPenggunaKonfirmasiOverlay">
  <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="ubahPenggunaKonfirmasiTitle">
    <div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><circle cx="12" cy="12" r="9"></circle><path d="M9 12l2 2 4-4"></path></svg>
    </div>
    <h3 id="ubahPenggunaKonfirmasiTitle">Simpan Perubahan Pengguna Ini?</h3>
    <p>Perubahan data akun akan langsung berlaku.</p>
    <div class="confirm-actions">
      <button type="button" class="btn" id="ubahPenggunaKonfirmasiBatal">Batal</button>
      <button type="button" class="btn btn-primary" id="ubahPenggunaKonfirmasiYa">Ya, Simpan</button>
    </div>
  </div>
</div>

<div class="confirm-overlay" id="hapusPenggunaOverlay">
  <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="hapusPenggunaTitle">
    <div class="confirm-icon">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>
    </div>
    <h3 id="hapusPenggunaTitle">Hapus Akun Pengguna?</h3>
    <p>Akun <strong id="hapusPenggunaNama">ini</strong> akan dihapus permanen dan tidak bisa login lagi.</p>
    <form id="formHapusPengguna" method="POST" action="">
      @csrf @method('DELETE')
      <div class="confirm-actions">
        <button type="button" class="btn" id="hapusPenggunaBatal">Batal</button>
        <button type="submit" class="btn btn-ghost-red">Ya, Hapus</button>
      </div>
    </form>
  </div>
</div>

<div class="confirm-overlay" id="hapusBackupOverlay">
  <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="hapusBackupTitle">
    <div class="confirm-icon">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>
    </div>
    <h3 id="hapusBackupTitle">Hapus File Backup?</h3>
    <p>File backup <strong id="hapusBackupNama">ini</strong> akan dihapus permanen dari server dan tidak bisa dikembalikan.</p>
    <form id="formHapusBackup" method="POST" action="">
      @csrf @method('DELETE')
      <div class="confirm-actions">
        <button type="button" class="btn" id="hapusBackupBatal">Batal</button>
        <button type="submit" class="btn btn-ghost-red">Ya, Hapus</button>
      </div>
    </form>
  </div>
</div>

<div class="user-modal-overlay" id="tambahSatuanModal">
  <div class="user-modal-card" role="dialog" aria-modal="true" aria-label="Tambah Satuan">
    <div class="user-modal-head">
      <div>
        <h3>Tambah Satuan</h3>
        <p>Kode dipakai sebagai identitas login/role.</p>
      </div>
      <button type="button" class="user-modal-close" id="tambahSatuanClose" aria-label="Tutup">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;"><path d="M6 6l12 12M18 6L6 18"></path></svg>
      </button>
    </div>
    <form class="form-grid" method="POST" action="{{ route('admin.satuan.store') }}" autocomplete="off">
      @csrf
      <div class="form-field"><label for="sKode">Kode</label><input id="sKode" name="kode" type="text" autocomplete="off" placeholder="Contoh: BINLOG" required style="text-transform:uppercase;"></div>
      <div class="form-field"><label for="sNama">Nama Satuan</label><input id="sNama" name="nama" type="text" autocomplete="off" placeholder="Contoh: Pembinaan Logistik" required></div>
      <div class="form-field">
        <label for="sKategori">Kategori</label>
        <select id="sKategori" name="kategori" required>
          <option value="">— Pilih Kategori —</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_ADMIN }}">Admin</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_PIMPINAN }}">Pimpinan</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN }}">Unsur Pelayanan</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN }}">Unsur Pembantu Pimpinan</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_DIREKTORAT }}">Direktorat</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_SATLAK }}">Satlak</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_KOTAMA }}">Kasansi</option>
        </select>
      </div>
      <div class="form-field full"><label for="sDeskripsi">Deskripsi (opsional)</label><textarea id="sDeskripsi" name="deskripsi" rows="2" autocomplete="off" placeholder="Contoh: Pengelolaan logistik dan perbekalan satuan."></textarea></div>
      <div class="user-modal-actions">
        <button class="btn" type="button" id="tambahSatuanCancel">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan Satuan</button>
      </div>
    </form>
  </div>
</div>

<div class="confirm-overlay" id="tambahSatuanKonfirmasiOverlay">
  <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="tambahSatuanKonfirmasiTitle">
    <div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><circle cx="12" cy="12" r="9"></circle><path d="M9 12l2 2 4-4"></path></svg>
    </div>
    <h3 id="tambahSatuanKonfirmasiTitle">Tambah Satuan Ini?</h3>
    <p>Satuan baru akan langsung aktif dan bisa dipakai.</p>
    <div class="confirm-actions">
      <button type="button" class="btn" id="tambahSatuanKonfirmasiBatal">Batal</button>
      <button type="button" class="btn btn-primary" id="tambahSatuanKonfirmasiYa">Ya, Tambah</button>
    </div>
  </div>
</div>

<div class="user-modal-overlay" id="ubahSatuanModal">
  <div class="user-modal-card" role="dialog" aria-modal="true" aria-label="Ubah Satuan">
    <div class="user-modal-head">
      <div>
        <h3>Ubah Satuan</h3>
        <p>Perbarui data satuan.</p>
      </div>
      <button type="button" class="user-modal-close" id="ubahSatuanClose" aria-label="Tutup">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;"><path d="M6 6l12 12M18 6L6 18"></path></svg>
      </button>
    </div>
    <form class="form-grid" method="POST" action="" id="ubahSatuanForm" autocomplete="off">
      @csrf
      @method('PATCH')
      <div class="form-field"><label for="usKode">Kode</label><input id="usKode" name="kode" type="text" autocomplete="off" placeholder="Contoh: BINLOG" required style="text-transform:uppercase;"></div>
      <div class="form-field"><label for="usNama">Nama Satuan</label><input id="usNama" name="nama" type="text" autocomplete="off" placeholder="Contoh: Pembinaan Logistik" required></div>
      <div class="form-field">
        <label for="usKategori">Kategori</label>
        <select id="usKategori" name="kategori" required>
          <option value="{{ \App\Models\Satuan::KATEGORI_ADMIN }}">Admin</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_PIMPINAN }}">Pimpinan</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN }}">Unsur Pelayanan</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN }}">Unsur Pembantu Pimpinan</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_DIREKTORAT }}">Direktorat</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_SATLAK }}">Satlak</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_KOTAMA }}">Kasansi</option>
        </select>
      </div>
      <div class="form-field full"><label for="usDeskripsi">Deskripsi (opsional)</label><textarea id="usDeskripsi" name="deskripsi" rows="2" autocomplete="off" placeholder="Contoh: Pengelolaan logistik dan perbekalan satuan."></textarea></div>
      <div class="user-modal-actions">
        <button class="btn" type="button" id="ubahSatuanCancel">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<div class="confirm-overlay" id="ubahSatuanKonfirmasiOverlay">
  <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="ubahSatuanKonfirmasiTitle">
    <div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><circle cx="12" cy="12" r="9"></circle><path d="M9 12l2 2 4-4"></path></svg>
    </div>
    <h3 id="ubahSatuanKonfirmasiTitle">Simpan Perubahan Satuan Ini?</h3>
    <p>Perubahan data satuan akan langsung berlaku.</p>
    <div class="confirm-actions">
      <button type="button" class="btn" id="ubahSatuanKonfirmasiBatal">Batal</button>
      <button type="button" class="btn btn-primary" id="ubahSatuanKonfirmasiYa">Ya, Simpan</button>
    </div>
  </div>
</div>

<div class="confirm-overlay" id="hapusSatuanOverlay">
  <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="hapusSatuanTitle">
    <div class="confirm-icon">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>
    </div>
    <h3 id="hapusSatuanTitle">Hapus Satuan Ini?</h3>
    <p>Satuan <strong id="hapusSatuanNama">ini</strong> akan dihapus permanen dari daftar.</p>
    <form id="formHapusSatuan" method="POST" action="">
      @csrf @method('DELETE')
      <div class="confirm-actions">
        <button type="button" class="btn" id="hapusSatuanBatal">Batal</button>
        <button type="submit" class="btn btn-ghost-red">Ya, Hapus</button>
      </div>
    </form>
  </div>
</div>

<div class="shell">

  <aside class="sidebar" id="sidebar">
    <div class="side-brand">
      <img src="{{ asset('images/logo-pussiberad.jpg') }}" alt="Lambang Pussiberad">
      <div class="logo">DT-PHATRAM-<span>2639</span></div>
      <button type="button" class="side-collapse-btn" id="sideCollapseBtn" aria-label="Ciutkan sidebar" title="Ciutkan sidebar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg></button>
    </div>
    <nav class="side-nav">
      <a href="#" class="side-link active" data-tab-link="dashboard" title="Dashboard"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/></svg></span><span class="side-text">Dashboard</span></a>

      <div class="side-nav-group open" id="penggunaGroup">
        <button type="button" class="side-nav-group-title" id="penggunaToggle" title="Kelola Pengguna">
          <span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
          <span class="side-text">Kelola Pengguna</span>
          <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg>
        </button>
        <div class="side-subnav"><div>
          <span class="side-subnav-label">Kelola Pengguna</span>
          <a href="#" class="side-sub-link" data-tab-link="pengguna" title="Daftar Pengguna"><span class="sub-dot"></span>Daftar Pengguna</a>
          <a href="#" class="side-sub-link" data-tab-link="reset-password" title="Permintaan Ganti Password"><span class="sub-dot"></span>Permintaan Ganti Password</a>
        </div></div>
      </div>

      <div class="side-nav-group open" id="monitoringGroup">
        <button type="button" class="side-nav-group-title" id="monitoringToggle" title="Monitoring">
          <span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
          <span class="side-text">Monitoring</span>
          <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg>
        </button>
        <div class="side-subnav"><div>
          <span class="side-subnav-label">Monitoring</span>
          <a href="#" class="side-sub-link" data-tab-link="rekap-laporan" title="Ringkasan Data"><span class="sub-dot"></span>Ringkasan Data</a>
          <a href="#" class="side-sub-link" data-tab-link="laporan-admin" title="Data Laporan"><span class="sub-dot"></span>Data Laporan</a>
          <a href="#" class="side-sub-link" data-tab-link="sesi-aktif" title="Pengguna Aktif"><span class="sub-dot"></span>Pengguna Aktif</a>
          <a href="#" class="side-sub-link" data-tab-link="log-aktivitas" title="Riwayat Aktivitas"><span class="sub-dot"></span>Riwayat Aktivitas</a>
        </div></div>
      </div>

      <div class="side-nav-group open" id="sistemGroup">
        <button type="button" class="side-nav-group-title" id="sistemToggle" title="Kelola Sistem">
          <span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
          <span class="side-text">Kelola Sistem</span>
          <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg>
        </button>
        <div class="side-subnav"><div>
          <span class="side-subnav-label">Kelola Sistem</span>
          <a href="#" class="side-sub-link" data-tab-link="satlak" title="Data Satuan"><span class="sub-dot"></span>Data Satuan</a>
          <a href="#" class="side-sub-link" data-tab-link="role-akses" title="Hak Akses Pengguna"><span class="sub-dot"></span>Hak Akses Pengguna</a>
          <a href="#" class="side-sub-link" data-tab-link="backup" title="Cadangan Data"><span class="sub-dot"></span>Cadangan Data</a>
          <a href="#" class="side-sub-link" data-tab-link="pengaturan-umum" title="Pengaturan Umum"><span class="sub-dot"></span>Pengaturan Umum</a>
        </div></div>
      </div>
    </nav>
    <div class="side-foot">
      <form class="logout logout-form" method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" title="Keluar"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span><span class="side-text">Keluar</span></button>
      </form>
    </div>
  </aside>
  <script>try{if(localStorage.getItem('siberad-sidebar-collapsed')==='1'){document.getElementById('sidebar').classList.add('collapsed');document.querySelectorAll('.side-nav-group.open').forEach(function(g){g.classList.remove('open')});}}catch(e){}</script>

  <script>
  (function () {
    var sidebar = document.getElementById('sidebar');
    var groups = Array.prototype.slice.call(document.querySelectorAll('.side-nav-group'));

    function positionGroupFlyout(g) {
      var subnav = g.querySelector('.side-subnav');
      var btn = g.querySelector('.side-nav-group-title');
      if (!subnav || !btn) return;
      if (window.innerWidth <= 900 || !sidebar || !sidebar.classList.contains('collapsed') || !g.classList.contains('open')) {
        subnav.style.top = ''; subnav.style.left = '';
        return;
      }
      var r = btn.getBoundingClientRect();
      subnav.style.top = r.top + 'px';
      subnav.style.left = (r.right + 8) + 'px';
    }
    window.siberadRepositionSubnavFlyouts = function () {
      groups.forEach(positionGroupFlyout);
    };
    window.addEventListener('resize', function () { window.siberadRepositionSubnavFlyouts(); });

    // Semua grup di HTML defaultnya class="open" (biar langsung kebuka pas
    // sidebar lebar). Status buka/tutup tiap grup disimpan per-grup ke
    // sessionStorage supaya bertahan lewat refresh -- tanpa ini, refresh
    // selalu balik ke default HTML (semua grup kebuka). Dibungkus fungsi
    // (bukan langsung jalan sekali) supaya bisa dipanggil ulang oleh
    // siberadInitSidebarCollapse() tiap kali sidebar dilebarkan dari mode
    // ciutkan -- termasuk setelah refresh saat masih ciutkan, saat status
    // "grup mana yang tadi kebuka" hanya ada di sessionStorage ini.
    var ADMIN_GROUP_STATE_KEY = 'siberad-admin-group-';
    function restoreAdminGroupState() {
      groups.forEach(function (g) {
        var saved = null;
        try { saved = sessionStorage.getItem(ADMIN_GROUP_STATE_KEY + g.id); } catch (e) {}
        // Sidebar lagi ciutkan menang duluan atas status tersimpan -- lihat
        // catatan yang sama di laporan-pimpinan.blade.php.
        if (saved === 'closed' || (sidebar && sidebar.classList.contains('collapsed'))) g.classList.remove('open');
        else if (saved === 'open') g.classList.add('open');
        positionGroupFlyout(g);
      });
    }
    window.siberadRestoreGroupState = restoreAdminGroupState;
    window.siberadMarkAdminGroupOpen = function (g) {
      if (!g || !g.id) return;
      try { sessionStorage.setItem(ADMIN_GROUP_STATE_KEY + g.id, 'open'); } catch (e) {}
    };
    restoreAdminGroupState();

    groups.forEach(function (g) {
      var btn = g.querySelector('.side-nav-group-title');
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var willOpen = !g.classList.contains('open');
        if (willOpen && sidebar && sidebar.classList.contains('collapsed')) {
          groups.forEach(function (other) {
            if (other === g) return;
            other.classList.remove('open');
            try { sessionStorage.setItem(ADMIN_GROUP_STATE_KEY + other.id, 'closed'); } catch (e) {}
            positionGroupFlyout(other);
          });
        }
        g.classList.toggle('open');
        try { sessionStorage.setItem(ADMIN_GROUP_STATE_KEY + g.id, g.classList.contains('open') ? 'open' : 'closed'); } catch (e) {}
        positionGroupFlyout(g);
      });
    });

    document.addEventListener('click', function (e) {
      if (!sidebar || !sidebar.classList.contains('collapsed')) return;
      if (e.target.closest('#sideCollapseBtn')) return;
      groups.forEach(function (g) {
        if (g.contains(e.target)) return;
        g.classList.remove('open');
        try { sessionStorage.setItem(ADMIN_GROUP_STATE_KEY + g.id, 'closed'); } catch (e) {}
        positionGroupFlyout(g);
      });
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape' || !sidebar || !sidebar.classList.contains('collapsed')) return;
      groups.forEach(function (g) {
        g.classList.remove('open');
        try { sessionStorage.setItem(ADMIN_GROUP_STATE_KEY + g.id, 'closed'); } catch (e) {}
        positionGroupFlyout(g);
      });
    });

    document.querySelectorAll('[data-tab-link]').forEach(function (link) {
      link.addEventListener('click', function () {
        var group = link.closest('.side-nav-group');
        if (sidebar && sidebar.classList.contains('collapsed') && group) {
          group.classList.remove('open');
          try { sessionStorage.setItem(ADMIN_GROUP_STATE_KEY + group.id, 'closed'); } catch (e) {}
          positionGroupFlyout(group);
        }
      });
    });
  })();
  </script>

  <main class="main">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <button class="menu-btn" id="menuBtn" type="button">☰</button>
      </div>
      <div class="topbar-actions">
        <button type="button" class="btn-icon-toggle" id="themeToggleBtn" aria-pressed="false" aria-label="Ganti tema">
          <svg class="icon-moon" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path></svg>
          <svg class="icon-sun" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.2"></circle><path d="M12 2.5v2.4M12 19.1v2.4M4.4 4.4l1.7 1.7M17.9 17.9l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.4 19.6l1.7-1.7M17.9 6.1l1.7-1.7"></path></svg>
        </button>

        <div class="profile-menu" id="notifMenu">
          <button type="button" class="btn-icon-toggle" id="notifBtn" aria-label="Notifikasi" aria-haspopup="menu" aria-expanded="false" style="position:relative;">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="stroke:var(--gold-bright) !important;color:var(--gold-bright) !important;">
              <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" style="fill:var(--gold-dim) !important;stroke:var(--gold-bright) !important;"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0" style="fill:none !important;stroke:var(--gold-bright) !important;"></path>
            </svg>
            <span class="siberad-notif-badge" style="{{ auth()->user()?->unreadNotifications->count() ? '' : 'display:none;' }}">{{ auth()->user()?->unreadNotifications->count() > 99 ? '99+' : auth()->user()?->unreadNotifications->count() }}</span>
          </button>

          <div class="profile-dropdown" id="notifDropdown" role="menu" aria-label="Notifikasi">
            <div class="profile-dropdown-head" style="border-bottom:1px solid var(--border-soft);">
              <div class="profile-dropdown-name" style="font-size:14px;">Notifikasi</div>
            </div>
            <div style="text-align:center;padding:20px 6px 8px;">
              <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 14px;display:block;">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
              </svg>
              <p style="margin:0;font-size:12.5px;line-height:1.6;color:var(--text-muted);">Belum ada notifikasi saat ini.<br>Fitur pusat notifikasi masih prototype dan belum tersambung ke database.</p>
            </div>
          </div>
        </div>
        <div class="profile-menu" id="profileMenu">
          <button type="button" class="profile-menu-btn" id="profileMenuBtn" aria-haspopup="menu" aria-expanded="false" aria-label="Menu profil">
            <span class="profile-initial" id="profileInitial" style="display:{{ $user->foto_path ? 'none' : '' }};">{{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}</span>
            <img class="profile-photo" id="profilePhotoBtn" alt="Foto profil {{ $user->name }}" @if($user->foto_path) src="{{ asset('storage/'.$user->foto_path) }}" style="display:block;" @endif>
          </button>

          <div class="profile-dropdown" id="profileDropdown" role="menu" aria-label="Menu profil">

            <div class="profile-dropdown-head">
              <div class="profile-dropdown-avatar">
                <span class="profile-initial" id="profileInitialDropdown" style="display:{{ $user->foto_path ? 'none' : '' }};">{{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}</span>
                <img class="profile-photo" id="profilePhotoDropdown" alt="Foto profil {{ $user->name }}" @if($user->foto_path) src="{{ asset('storage/'.$user->foto_path) }}" style="display:block;" @endif>
              </div>
              <div>
                <div class="profile-dropdown-name">{{ $user->name }}</div>
                <div class="profile-dropdown-role">{{ $user->jabatan ?? 'Pengguna' }}</div>
              </div>
            </div>

            <button type="button" class="profile-dropdown-item" id="openPengaturanBtn" role="menuitem">
              <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 13.5a7.6 7.6 0 0 0 0-3l2-1.5-2-3.4-2.3.9a7.6 7.6 0 0 0-2.6-1.5L14 2.5h-4l-.5 2.5a7.6 7.6 0 0 0-2.6 1.5l-2.3-.9-2 3.4 2 1.5a7.6 7.6 0 0 0 0 3l-2 1.5 2 3.4 2.3-.9a7.6 7.6 0 0 0 2.6 1.5l.5 2.5h4l.5-2.5a7.6 7.6 0 0 0 2.6-1.5l2.3.9 2-3.4Z"></path></svg>
              Pengaturan Akun
            </button>
            <button type="button" class="profile-dropdown-item" id="openBantuanBtn" role="menuitem">
              <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.5"></circle><path d="M9.2 9.2a2.8 2.8 0 1 1 3.9 2.6c-.8.4-1.1 1-1.1 1.9"></path><path d="M12 17.2h.01"></path></svg>
              Bantuan &amp; Panduan
            </button>

            <div class="profile-dropdown-divider"></div>

            <form class="logout-form" method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="profile-dropdown-item danger" role="menuitem">
                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
                Keluar
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    
    <script>
    (function () {
      // Tombol tema, notifikasi, dan profil di topbar bisa dibind dua kali:
      // sekali di sini/lewat script Admin sendiri di bawah, sekali lagi lewat
      // partials/pengumuman-banner.blade.php (initRoleUi) atau
      // partials/dash-script.blade.php yang ikut ter-include di halaman ini.
      // Dua listener klik pada tombol yang sama saling membatalkan dalam satu
      // klik (buka lalu langsung tertutup lagi), jadi kelihatan seperti tidak
      // merespons. Wiring tema dilakukan tuntas di sini dan langsung ditandai
      // "sudah dibind" (dataset.uiBound) SEBELUM partial lain sempat jalan,
      // supaya partial lain skip. Tombol profil & notifikasi sudah punya
      // implementasi sendiri yang lebih lengkap di bawah, jadi cukup ditandai
      // di sini biar partial lain tidak ikut bind.
      var themeBtn = document.getElementById('themeToggleBtn');
      if (themeBtn && !themeBtn.dataset.uiBound) {
        themeBtn.dataset.uiBound = '1';
        var THEME_KEY = 'siberad-theme';
        function applyTheme(theme) {
          if (theme === 'light') document.documentElement.setAttribute('data-theme', 'light');
          else document.documentElement.removeAttribute('data-theme');
          themeBtn.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
        }
        var savedTheme = 'dark';
        try { savedTheme = localStorage.getItem(THEME_KEY) || 'dark'; } catch (e) {}
        applyTheme(savedTheme);
        themeBtn.addEventListener('click', function () {
          var current = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
          var next = current === 'light' ? 'dark' : 'light';
          try { localStorage.setItem(THEME_KEY, next); } catch (e) {}
          applyTheme(next);
        });
      }

      var profileBtn = document.getElementById('profileMenuBtn');
      if (profileBtn) profileBtn.dataset.uiBound = '1';
    })();
    </script>

    <div class="content">
      @include('siberad.dashboards.partials.pengumuman-banner')
      @include('siberad.dashboards.partials.styled-select')
      @if(session('status'))<script>document.addEventListener('DOMContentLoaded',function(){window.siberadShowToast?window.siberadShowToast('success',{!! json_encode(session('status')) !!}):null});</script>@endif
      @if(session('error'))<script>document.addEventListener('DOMContentLoaded',function(){window.siberadShowToast?window.siberadShowToast('error',{!! json_encode(session('error')) !!}):null});</script>@endif

      {{-- ===== DASHBOARD ===== --}}
      <section class="tab-panel active" data-tab-panel="dashboard">

        <div class="dash-hero">
          <div>
            <div class="dash-hero-eyebrow">SIBERAD // {{ $satuan->kode ?? 'SISTEM' }}</div>
            <h2>{{ $satuan->nama ?? $user->name }}</h2>
            <p>{{ now()->translatedFormat('l, d F Y') }}</p>
          </div>
        </div>

        <div class="kpi-grid">
          <div class="stat-card kpi-card">
            <div class="lbl">Total Pengguna</div>
            <div class="val">{{ $stats['total_pengguna'] }}</div>
            <div class="sub">Akun terdaftar di sistem</div>
          </div>
          <div class="stat-card kpi-card">
            <div class="lbl">Total Satuan</div>
            <div class="val">{{ $stats['total_satuan'] }}</div>
            <div class="sub">Termasuk Admin</div>
          </div>
          <div class="stat-card kpi-card">
            <div class="lbl">Total Laporan</div>
            <div class="val">{{ $stats['total_laporan'] }}</div>
            <div class="sub">Laporan tercatat di sistem</div>
          </div>
          <div class="stat-card kpi-card wait">
            <div class="lbl">Reset Password</div>
            <div class="val">{{ $stats['reset_password_pending'] }}</div>
            <div class="sub">Menunggu diverifikasi</div>
          </div>
        </div>

        <div class="panel chart-box">
          <div class="panel-head"><div><h3>Statistik Sistem</h3><p>Sebaran akun per kategori, status laporan, dan tren aktivitas 7 hari terakhir.</p></div></div>
          <div class="chart-box-grid">

            <div class="chart-mini chart-mini-link" data-tab-link="pengguna" role="button" tabindex="0" title="Lihat Daftar Pengguna">
              <div class="chart-mini-head">
                <div class="chart-mini-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                <div><h4>Pengguna per Kategori Satuan</h4><p>Sebaran akun berdasarkan kategori.</p></div>
              </div>
              <div class="chart-wrap"><canvas id="chartKategoriSatuan"></canvas></div>
              <div class="chart-legend" id="chartKategoriSatuanLegend"></div>
            </div>

            <div class="chart-mini chart-mini-link" data-tab-link="rekap-laporan" role="button" tabindex="0" title="Lihat Ringkasan Data">
              <div class="chart-mini-head">
                <div class="chart-mini-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
                <div><h4>Distribusi Status Laporan</h4><p>Proporsi status seluruh laporan di sistem.</p></div>
              </div>
              <div class="chart-wrap"><canvas id="chartStatusLaporan"></canvas></div>
              <div class="chart-legend" id="chartStatusLaporanLegend"></div>
            </div>

            <div class="chart-mini chart-mini-link" data-tab-link="log-aktivitas" role="button" tabindex="0" title="Lihat Riwayat Aktivitas">
              <div class="chart-mini-head">
                <div class="chart-mini-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
                <div><h4>Aktivitas 7 Hari Terakhir</h4><p>Jumlah aksi tercatat per hari.</p></div>
              </div>
              <div class="chart-wrap"><canvas id="chartAktivitasMingguan"></canvas></div>
            </div>

          </div>
        </div>

        <script>
          document.querySelectorAll('.chart-mini-link').forEach(function (el) {
            el.addEventListener('keydown', function (e) {
              if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
            });
          });
        </script>

        <div class="dash-two-col">
          <div class="panel activity-panel">
            <div class="panel-head">
              <div><h3>Permintaan Ganti Password</h3><p>5 permintaan terbaru.</p></div>
              <a href="#" class="btn btn-ghost btn-sm" data-tab-link="reset-password">Lihat Semua</a>
            </div>
            <ul class="activity-feed">
              @forelse($permintaanResetPassword->take(5) as $r)
              @php
                [$statusWarna, $statusDim] = match ($r->status) {
                  \App\Models\PermintaanResetPassword::STATUS_DISETUJUI => ['var(--success-bright)', 'var(--success-dim)'],
                  \App\Models\PermintaanResetPassword::STATUS_DITOLAK => ['var(--red)', 'var(--red-dim)'],
                  default => ['var(--amber)', 'var(--amber-dim)'],
                };
              @endphp
              <li>
                <span class="activity-dot" style="background:{{ $statusWarna }};box-shadow:0 0 0 3px {{ $statusDim }};"></span>
                <div class="activity-body">
                  <div class="activity-main">
                    <div class="activity-text">{{ $r->user->name ?? '-' }}</div>
                    <div class="activity-meta">{{ $r->user->satuan->kode ?? 'Sistem' }} &middot; <span style="color:{{ $statusWarna }};font-weight:700;">{{ $r->status }}</span></div>
                  </div>
                  <div class="activity-time">{{ $r->created_at?->diffForHumans() }}</div>
                </div>
              </li>
              @empty
              <li class="activity-empty">Belum ada permintaan reset password.</li>
              @endforelse
            </ul>
          </div>

          <div class="panel activity-panel">
            <div class="panel-head">
              <div><h3>Aktivitas Terbaru</h3><p>5 aksi terakhir tercatat.</p></div>
              <a href="#" class="btn btn-ghost btn-sm" data-tab-link="log-aktivitas">Lihat Semua</a>
            </div>
            <ul class="activity-feed">
              @forelse($logAktivitas->take(5) as $log)
              <li>
                <span class="activity-dot"></span>
                <div class="activity-body">
                  <div class="activity-main">
                    <div class="activity-text">{{ $log->deskripsi ?: $log->aksi }}</div>
                    <div class="activity-meta">{{ $log->nama_pengguna ?? 'Sistem' }}</div>
                  </div>
                  <div class="activity-time">{{ $log->created_at?->diffForHumans() }}</div>
                </div>
              </li>
              @empty
              <li class="activity-empty">Belum ada aktivitas tercatat.</li>
              @endforelse
            </ul>
          </div>
        </div>

        <style>
          .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:26px;}
          @media(max-width:980px){.kpi-grid{grid-template-columns:repeat(2,1fr);}}
          .kpi-card .lbl{font-weight:800;}
          .kpi-card .val{font-family:var(--mono);color:var(--text);}
          .kpi-card.wait .val{color:#f59e0b;}
          .kpi-card.ok .val{color:#22c55e;}
          .kpi-card.bad .val{color:#ef4444;}

          .chart-mini-link{cursor:pointer;}
          .chart-mini-link:hover,.chart-mini-link:focus-visible{border-color:var(--gold-bright);box-shadow:0 6px 18px rgba(0,0,0,.18);}
          .chart-mini-link:focus-visible{outline:2px solid var(--gold-bright);outline-offset:2px;}
          .chart-mini-head{display:flex;align-items:flex-start;gap:11px;}
          .chart-mini-icon{width:28px;height:28px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--gold-dim);color:var(--gold-bright);}
          .chart-mini-icon svg{width:15px;height:15px;}
          .chart-mini-icon.amber{background:var(--amber-dim);color:var(--amber);}
          .chart-mini-icon.green{background:var(--green-dim);color:var(--green-bright);}
          .chart-mini-icon.blue{background:rgba(99,102,241,.14);color:#6366f1;}

          .dash-two-col{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-top:22px;}
          @media(max-width:980px){.dash-two-col{grid-template-columns:1fr;}}
          .dash-two-col .activity-panel{margin-top:0;height:100%;display:flex;flex-direction:column;}
          .activity-feed{list-style:none;padding:2px 0 4px;margin:0;}
          .dash-two-col .activity-feed{flex:1;display:flex;flex-direction:column;justify-content:center;}
          .activity-feed li{display:flex;gap:12px;padding:13px 10px;border-radius:9px;transition:background .15s ease;}
          .activity-feed li:hover{background:var(--hover-tint);}
          .activity-feed li + li{border-top:1px solid var(--border-soft);}
          .activity-dot{width:7px;height:7px;border-radius:50%;background:var(--gold-bright);margin-top:6px;flex-shrink:0;box-shadow:0 0 0 3px var(--gold-dim);}
          .activity-body{flex:1;min-width:0;display:flex;justify-content:space-between;align-items:flex-start;gap:14px;}
          .activity-main{min-width:0;}
          .activity-text{font-size:13px;color:var(--text);line-height:1.5;}
          .activity-meta{font-size:11px;color:var(--text-dim);margin-top:3px;}
          .activity-time{font-size:10.5px;color:var(--text-dim);font-family:var(--mono);white-space:nowrap;flex-shrink:0;padding-top:2px;}
          .activity-empty{padding:20px 0;text-align:center;color:var(--text-dim);font-size:12.5px;}
        </style>
      </section>

      {{-- ===== KELOLA PENGGUNA ===== --}}
      <section class="tab-panel" data-tab-panel="pengguna">
        @if ($errors->any())
          <div class="notice" style="border-color:var(--red);">{{ $errors->first() }}</div>
        @endif

        <div class="panel">
          <div class="panel-head">
            <div><h2>Daftar Pengguna</h2><p>Seluruh akun yang terdaftar, satu akun per satuan. Klik "Ubah" untuk mengedit satuan/jabatan/password.</p></div>
            <button class="btn btn-primary" type="button" id="tambahPenggunaOpen">Tambah Pengguna</button>
          </div>
          <div class="table-toolbar">
            <div class="table-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
              <input type="text" class="table-search" data-table-search="tblPengguna" placeholder="Cari nama atau satuan...">
            </div>
            <select class="table-filter" data-table-filter="tblPengguna">
              <option value="">Semua Kategori</option>
              <option value="Admin">Admin</option>
              <option value="Pimpinan">Pimpinan</option>
              <option value="Unsur Pelayanan">Unsur Pelayanan</option>
              <option value="Unsur Pembantu Pimpinan">Unsur Pembantu Pimpinan</option>
              <option value="Direktorat">Direktorat</option>
              <option value="Satlak">Satlak</option>
              <option value="Kasansi">Kasansi</option>
            </select>
            <span class="table-filter-count" data-table-count="tblPengguna"></span>
          </div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl" id="tblPengguna">
              <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Satuan</th><th class="th-center">Password</th><th>Aksi</th></tr></thead>
              <tbody>
                @foreach($semuaPengguna as $p)
                @php
                  $kategoriLabel = match ($p->satuan->kategori ?? null) {
                    \App\Models\Satuan::KATEGORI_ADMIN => 'Admin',
                    \App\Models\Satuan::KATEGORI_PIMPINAN => 'Pimpinan',
                    \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
                    \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
                    \App\Models\Satuan::KATEGORI_DIREKTORAT => 'Direktorat',
                    \App\Models\Satuan::KATEGORI_KOTAMA => 'Kasansi',
                    default => 'Satlak',
                  };
                @endphp
                <tr data-filter-value="{{ $kategoriLabel }}" data-search-value="{{ strtolower($p->name.' '.($p->satuan->nama ?? '').' '.($p->satuan->kode ?? '')) }}">
                  <td>{{ $p->satuan->nama_singkat ?? $p->name }}</td>
                  <td><span class="badge badge-plain">{{ $p->username }}</span></td>
                  <td style="color:var(--text-muted);">{{ $p->email ?: '-' }}</td>
                  <td>{{ $p->satuan->nama_keterangan ?? '-' }}</td>
                  <td class="th-center">
                    <button class="table-action-btn icon-only" type="button" onclick="bukaUbahPasswordPengguna(this)"
                      data-action="{{ route('admin.users.update', $p) }}"
                      data-name="{{ $p->name }}"
                      data-username="{{ $p->username }}"
                      data-email="{{ $p->email }}"
                      data-satuan-id="{{ $p->satuan_id }}"
                      title="Ubah password {{ $p->name }}" aria-label="Ubah password {{ $p->name }}">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </button>
                  </td>
                  <td>
                    <div class="btn-row">
                      <button class="table-action-btn edit" type="button" onclick="bukaUbahPengguna(this)"
                        data-action="{{ route('admin.users.update', $p) }}"
                        data-name="{{ $p->name }}"
                        data-username="{{ $p->username }}"
                        data-email="{{ $p->email }}"
                        data-satuan-id="{{ $p->satuan_id }}">Ubah</button>
                      @if($p->id !== $user->id)
                      <button class="table-action-btn danger" type="button" onclick="bukaHapusPengguna(this)"
                        data-action="{{ route('admin.users.destroy', $p) }}"
                        data-nama="{{ $p->name }}">Hapus</button>
                      @endif
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <script>
        (function () {
          var modal = document.getElementById('tambahPenggunaModal');
          var openBtn = document.getElementById('tambahPenggunaOpen');
          var closeBtn = document.getElementById('tambahPenggunaClose');
          var cancelBtn = document.getElementById('tambahPenggunaCancel');
          if (!modal) return;
          function open() { modal.classList.add('open'); }
          function close() { modal.classList.remove('open'); }
          if (openBtn) openBtn.addEventListener('click', open);
          if (closeBtn) closeBtn.addEventListener('click', close);
          if (cancelBtn) cancelBtn.addEventListener('click', close);
          document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

          // Validasi wajib-diisi custom (senada sama form login & Ganti
          // Password/Foto Profil): ganti tooltip bawaan browser jadi pesan
          // Bahasa Indonesia + border merah di bawah field.
          var form = modal.querySelector('form');
          var requiredMessages = {
            uNama: 'Nama lengkap wajib diisi.',
            uUsername: 'Username / NRP wajib diisi.',
            uSatuan: 'Satuan wajib dipilih.',
            uPassword: 'Password awal wajib diisi.'
          };
          if (form) {
            form.querySelectorAll('input[required], select[required], input[type="email"]').forEach(function (field) {
              var msg = field.nextElementSibling;
              if (!msg || !msg.classList.contains('profile-field-error')) {
                msg = document.createElement('span');
                msg.className = 'profile-field-error';
                msg.style.display = 'none';
                field.insertAdjacentElement('afterend', msg);
              }
              field.addEventListener('invalid', function (e) {
                e.preventDefault();
                field.classList.add('field-invalid');
                msg.textContent = field.validity.typeMismatch
                  ? 'Format email tidak valid.'
                  : (requiredMessages[field.id] || 'Kolom ini wajib diisi.');
                msg.style.display = 'flex';
              });
              field.addEventListener('input', function () {
                field.classList.remove('field-invalid');
                msg.style.display = 'none';
              });
              field.addEventListener('change', function () {
                field.classList.remove('field-invalid');
                msg.style.display = 'none';
              });
            });
          }

          // Konfirmasi dulu sebelum beneran kirim (senada sama konfirmasi
          // Kirim Permintaan ke Admin di form Ganti Password): validasi
          // wajib-diisi bawaan browser tetap jalan duluan (form nggak akan
          // sampai event 'submit' kalau ada field invalid), baru munculin
          // konfirmasi kalau semua sudah valid.
          var konfirmOverlay = document.getElementById('tambahPenggunaKonfirmasiOverlay');
          if (form && konfirmOverlay) {
            function closeKonfirm() { konfirmOverlay.classList.remove('open'); }
            form.addEventListener('submit', function (e) {
              if (form.dataset.confirmed === '1') { form.dataset.confirmed = ''; return; }
              e.preventDefault();
              konfirmOverlay.classList.add('open');
            });
            document.getElementById('tambahPenggunaKonfirmasiYa')?.addEventListener('click', function () {
              closeKonfirm();
              form.dataset.confirmed = '1';
              form.requestSubmit ? form.requestSubmit() : form.submit();
            });
            document.getElementById('tambahPenggunaKonfirmasiBatal')?.addEventListener('click', closeKonfirm);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && konfirmOverlay.classList.contains('open')) closeKonfirm(); });
          }
        })();

        window.bukaUbahPengguna = function (btn) {
          document.getElementById('ubahPenggunaForm').action = btn.dataset.action;
          document.getElementById('upNama').value = btn.dataset.name || '';
          document.getElementById('upUsername').value = btn.dataset.username || '';
          document.getElementById('upEmail').value = btn.dataset.email || '';
          document.getElementById('upSatuan').value = btn.dataset.satuanId || '';
          document.getElementById('upPassword').value = '';
          document.getElementById('ubahPenggunaModal').classList.add('open');
        };

        window.bukaUbahPasswordPengguna = function (btn) {
          document.getElementById('ubahPasswordPenggunaForm').action = btn.dataset.action;
          document.getElementById('uppName').value = btn.dataset.name || '';
          document.getElementById('uppUsername').value = btn.dataset.username || '';
          document.getElementById('uppEmail').value = btn.dataset.email || '';
          document.getElementById('uppSatuanId').value = btn.dataset.satuanId || '';
          document.getElementById('uppPassword').value = '';
          document.getElementById('uppNamaLabel').textContent = btn.dataset.name || '-';
          document.getElementById('uppUsernameLabel').textContent = btn.dataset.username || '-';
          document.getElementById('uppKonfirmasiNama').textContent = btn.dataset.name || 'ini';
          document.getElementById('ubahPasswordPenggunaModal').classList.add('open');
        };

        window.bukaHapusPengguna = function (btn) {
          document.getElementById('formHapusPengguna').action = btn.dataset.action;
          document.getElementById('hapusPenggunaNama').textContent = btn.dataset.nama || 'ini';
          document.getElementById('hapusPenggunaOverlay')?.classList.add('open');
        };
        document.getElementById('hapusPenggunaBatal')?.addEventListener('click', () => document.getElementById('hapusPenggunaOverlay')?.classList.remove('open'));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') document.getElementById('hapusPenggunaOverlay')?.classList.remove('open'); });

        (function () {
          var modal = document.getElementById('ubahPasswordPenggunaModal');
          var closeBtn = document.getElementById('ubahPasswordPenggunaClose');
          var cancelBtn = document.getElementById('ubahPasswordPenggunaCancel');
          if (!modal) return;
          function close() { modal.classList.remove('open'); }
          if (closeBtn) closeBtn.addEventListener('click', close);
          if (cancelBtn) cancelBtn.addEventListener('click', close);
          document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

          var form = modal.querySelector('form');
          var passwordField = document.getElementById('uppPassword');
          if (passwordField) {
            var msg = passwordField.nextElementSibling;
            if (!msg || !msg.classList.contains('profile-field-error')) {
              msg = document.createElement('span');
              msg.className = 'profile-field-error';
              msg.style.display = 'none';
              passwordField.insertAdjacentElement('afterend', msg);
            }
            passwordField.addEventListener('invalid', function (e) {
              e.preventDefault();
              passwordField.classList.add('field-invalid');
              msg.textContent = passwordField.validity.tooShort
                ? 'Password minimal 6 karakter.'
                : 'Password baru wajib diisi.';
              msg.style.display = 'flex';
            });
            passwordField.addEventListener('input', function () {
              passwordField.classList.remove('field-invalid');
              msg.style.display = 'none';
            });
          }

          var konfirmOverlay = document.getElementById('ubahPasswordPenggunaKonfirmasiOverlay');
          if (form && konfirmOverlay) {
            function closeKonfirm() { konfirmOverlay.classList.remove('open'); }
            form.addEventListener('submit', function (e) {
              if (form.dataset.confirmed === '1') { form.dataset.confirmed = ''; return; }
              e.preventDefault();
              konfirmOverlay.classList.add('open');
            });
            document.getElementById('ubahPasswordPenggunaKonfirmasiBatal')?.addEventListener('click', closeKonfirm);
            document.getElementById('ubahPasswordPenggunaKonfirmasiYa')?.addEventListener('click', function () {
              closeKonfirm();
              form.dataset.confirmed = '1';
              form.requestSubmit ? form.requestSubmit() : form.submit();
            });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && konfirmOverlay.classList.contains('open')) closeKonfirm(); });
          }
        })();

        window.bukaHapusBackup = function (btn) {
          document.getElementById('formHapusBackup').action = btn.dataset.action;
          document.getElementById('hapusBackupNama').textContent = btn.dataset.nama || 'ini';
          document.getElementById('hapusBackupOverlay')?.classList.add('open');
        };
        document.getElementById('hapusBackupBatal')?.addEventListener('click', () => document.getElementById('hapusBackupOverlay')?.classList.remove('open'));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') document.getElementById('hapusBackupOverlay')?.classList.remove('open'); });
        (function () {
          var modal = document.getElementById('ubahPenggunaModal');
          var closeBtn = document.getElementById('ubahPenggunaClose');
          var cancelBtn = document.getElementById('ubahPenggunaCancel');
          if (!modal) return;
          function close() { modal.classList.remove('open'); }
          if (closeBtn) closeBtn.addEventListener('click', close);
          if (cancelBtn) cancelBtn.addEventListener('click', close);
          document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

          // Validasi wajib-diisi custom (senada sama modal Tambah Pengguna):
          // ganti tooltip bawaan browser jadi pesan Bahasa Indonesia + border
          // merah di bawah field.
          var form = modal.querySelector('form');
          var requiredMessages = {
            upNama: 'Nama lengkap wajib diisi.',
            upUsername: 'Username / NRP wajib diisi.',
            upSatuan: 'Satuan wajib dipilih.'
          };
          if (form) {
            form.querySelectorAll('input[required], select[required], input[type="email"]').forEach(function (field) {
              var msg = field.nextElementSibling;
              if (!msg || !msg.classList.contains('profile-field-error')) {
                msg = document.createElement('span');
                msg.className = 'profile-field-error';
                msg.style.display = 'none';
                field.insertAdjacentElement('afterend', msg);
              }
              field.addEventListener('invalid', function (e) {
                e.preventDefault();
                field.classList.add('field-invalid');
                msg.textContent = field.validity.typeMismatch
                  ? 'Format email tidak valid.'
                  : (requiredMessages[field.id] || 'Kolom ini wajib diisi.');
                msg.style.display = 'flex';
              });
              field.addEventListener('input', function () {
                field.classList.remove('field-invalid');
                msg.style.display = 'none';
              });
              field.addEventListener('change', function () {
                field.classList.remove('field-invalid');
                msg.style.display = 'none';
              });
            });
          }

          // Konfirmasi dulu sebelum beneran kirim (senada sama modal Tambah
          // Pengguna): validasi wajib-diisi bawaan browser tetap jalan
          // duluan, baru munculin konfirmasi kalau semua sudah valid.
          var konfirmOverlay = document.getElementById('ubahPenggunaKonfirmasiOverlay');
          if (form && konfirmOverlay) {
            function closeKonfirm() { konfirmOverlay.classList.remove('open'); }
            form.addEventListener('submit', function (e) {
              if (form.dataset.confirmed === '1') { form.dataset.confirmed = ''; return; }
              e.preventDefault();
              konfirmOverlay.classList.add('open');
            });
            document.getElementById('ubahPenggunaKonfirmasiYa')?.addEventListener('click', function () {
              closeKonfirm();
              form.dataset.confirmed = '1';
              form.requestSubmit ? form.requestSubmit() : form.submit();
            });
            document.getElementById('ubahPenggunaKonfirmasiBatal')?.addEventListener('click', closeKonfirm);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && konfirmOverlay.classList.contains('open')) closeKonfirm(); });
          }
        })();
      </script>

      {{-- ===== MANAJEMEN SATUAN ===== --}}
      <section class="tab-panel" data-tab-panel="satlak">

        <div class="panel">
          <div class="panel-head">
            <div><h2>Data Satuan</h2><p>Kelola daftar satuan/Satlak yang terdaftar di SIBERAD. Satuan yang masih punya pengguna tidak bisa dihapus.</p></div>
            <button class="btn btn-primary" type="button" id="tambahSatuanOpen">Tambah Satuan</button>
          </div>
          <div class="table-toolbar">
            <div class="table-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
              <input type="text" class="table-search" data-table-search="tblSatuan" placeholder="Cari kode atau nama satuan...">
            </div>
            <select class="table-filter" data-table-filter="tblSatuan">
              <option value="">Semua Kategori</option>
              <option value="Admin">Admin</option>
              <option value="Pimpinan">Pimpinan</option>
              <option value="Unsur Pelayanan">Unsur Pelayanan</option>
              <option value="Unsur Pembantu Pimpinan">Unsur Pembantu Pimpinan</option>
              <option value="Direktorat">Direktorat</option>
              <option value="Satlak">Satlak</option>
              <option value="Kasansi">Kasansi</option>
            </select>
            <span class="table-filter-count" data-table-count="tblSatuan"></span>
          </div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl" id="tblSatuan">
              <colgroup><col style="width:14%"><col style="width:28%"><col style="width:16%"><col style="width:16%"><col style="width:26%"></colgroup>
              <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Jumlah Pengguna</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($semuaSatuan as $s)
                @php
                  $kategoriFilterLabel = match ($s->kategori) {
                    \App\Models\Satuan::KATEGORI_ADMIN => 'Admin',
                    \App\Models\Satuan::KATEGORI_PIMPINAN => 'Pimpinan',
                    \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
                    \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
                    \App\Models\Satuan::KATEGORI_DIREKTORAT => 'Direktorat',
                    \App\Models\Satuan::KATEGORI_KOTAMA => 'Kasansi',
                    default => 'Satlak',
                  };
                @endphp
                <tr data-filter-value="{{ $kategoriFilterLabel }}">
                  <td><span class="badge">{{ $s->kode }}</span></td>
                  <td>{{ $s->nama }}</td>
                  <td style="color:var(--text-muted);">{{ $kategoriFilterLabel }}</td>
                  <td>{{ $s->users_count }}</td>
                  <td>
                    <div class="btn-row">
                      <button class="table-action-btn edit" type="button" onclick="bukaUbahSatuan(this)"
                        data-action="{{ route('admin.satuan.update', $s) }}"
                        data-kode="{{ $s->kode }}"
                        data-nama="{{ $s->nama }}"
                        data-kategori="{{ $s->kategori }}"
                        data-deskripsi="{{ $s->deskripsi }}">Ubah</button>
                      <button class="table-action-btn danger" type="button" onclick="bukaHapusSatuan(this)"
                        data-action="{{ route('admin.satuan.destroy', $s) }}"
                        data-nama="{{ $s->nama }}">Hapus</button>
                    </div>
                  </td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada data satuan</div></div></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
      <script>
        (function () {
          var modal = document.getElementById('tambahSatuanModal');
          var openBtn = document.getElementById('tambahSatuanOpen');
          var closeBtn = document.getElementById('tambahSatuanClose');
          var cancelBtn = document.getElementById('tambahSatuanCancel');
          if (!modal) return;
          function open() { modal.classList.add('open'); }
          function close() { modal.classList.remove('open'); }
          if (openBtn) openBtn.addEventListener('click', open);
          if (closeBtn) closeBtn.addEventListener('click', close);
          if (cancelBtn) cancelBtn.addEventListener('click', close);
          document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

          // Validasi wajib-diisi custom (senada sama modal Tambah Pengguna):
          // ganti tooltip bawaan browser jadi pesan Bahasa Indonesia + border
          // merah di bawah field.
          var form = modal.querySelector('form');
          var requiredMessages = {
            sKode: 'Kode wajib diisi.',
            sNama: 'Nama satuan wajib diisi.',
            sKategori: 'Kategori wajib dipilih.'
          };
          if (form) {
            form.querySelectorAll('input[required], select[required]').forEach(function (field) {
              var msg = field.nextElementSibling;
              if (!msg || !msg.classList.contains('profile-field-error')) {
                msg = document.createElement('span');
                msg.className = 'profile-field-error';
                msg.style.display = 'none';
                field.insertAdjacentElement('afterend', msg);
              }
              field.addEventListener('invalid', function (e) {
                e.preventDefault();
                field.classList.add('field-invalid');
                msg.textContent = requiredMessages[field.id] || 'Kolom ini wajib diisi.';
                msg.style.display = 'flex';
              });
              field.addEventListener('input', function () {
                field.classList.remove('field-invalid');
                msg.style.display = 'none';
              });
              field.addEventListener('change', function () {
                field.classList.remove('field-invalid');
                msg.style.display = 'none';
              });
            });
          }

          // Konfirmasi dulu sebelum beneran kirim (senada sama modal Tambah
          // Pengguna): validasi wajib-diisi bawaan browser tetap jalan
          // duluan, baru munculin konfirmasi kalau semua sudah valid.
          var konfirmOverlay = document.getElementById('tambahSatuanKonfirmasiOverlay');
          if (form && konfirmOverlay) {
            function closeKonfirm() { konfirmOverlay.classList.remove('open'); }
            form.addEventListener('submit', function (e) {
              if (form.dataset.confirmed === '1') { form.dataset.confirmed = ''; return; }
              e.preventDefault();
              konfirmOverlay.classList.add('open');
            });
            document.getElementById('tambahSatuanKonfirmasiYa')?.addEventListener('click', function () {
              closeKonfirm();
              form.dataset.confirmed = '1';
              form.requestSubmit ? form.requestSubmit() : form.submit();
            });
            document.getElementById('tambahSatuanKonfirmasiBatal')?.addEventListener('click', closeKonfirm);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && konfirmOverlay.classList.contains('open')) closeKonfirm(); });
          }
        })();

        window.bukaUbahSatuan = function (btn) {
          document.getElementById('ubahSatuanForm').action = btn.dataset.action;
          document.getElementById('usKode').value = btn.dataset.kode || '';
          document.getElementById('usNama').value = btn.dataset.nama || '';
          document.getElementById('usKategori').value = btn.dataset.kategori || '';
          document.getElementById('usDeskripsi').value = btn.dataset.deskripsi || '';
          document.getElementById('ubahSatuanModal').classList.add('open');
        };

        window.bukaHapusSatuan = function (btn) {
          document.getElementById('formHapusSatuan').action = btn.dataset.action;
          document.getElementById('hapusSatuanNama').textContent = btn.dataset.nama || 'ini';
          document.getElementById('hapusSatuanOverlay')?.classList.add('open');
        };
        document.getElementById('hapusSatuanBatal')?.addEventListener('click', () => document.getElementById('hapusSatuanOverlay')?.classList.remove('open'));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') document.getElementById('hapusSatuanOverlay')?.classList.remove('open'); });
        (function () {
          var modal = document.getElementById('ubahSatuanModal');
          var closeBtn = document.getElementById('ubahSatuanClose');
          var cancelBtn = document.getElementById('ubahSatuanCancel');
          if (!modal) return;
          function close() { modal.classList.remove('open'); }
          if (closeBtn) closeBtn.addEventListener('click', close);
          if (cancelBtn) cancelBtn.addEventListener('click', close);
          document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

          // Validasi wajib-diisi custom (senada sama modal Tambah Satuan).
          var form = modal.querySelector('form');
          var requiredMessages = {
            usKode: 'Kode wajib diisi.',
            usNama: 'Nama satuan wajib diisi.',
            usKategori: 'Kategori wajib dipilih.'
          };
          if (form) {
            form.querySelectorAll('input[required], select[required]').forEach(function (field) {
              var msg = field.nextElementSibling;
              if (!msg || !msg.classList.contains('profile-field-error')) {
                msg = document.createElement('span');
                msg.className = 'profile-field-error';
                msg.style.display = 'none';
                field.insertAdjacentElement('afterend', msg);
              }
              field.addEventListener('invalid', function (e) {
                e.preventDefault();
                field.classList.add('field-invalid');
                msg.textContent = requiredMessages[field.id] || 'Kolom ini wajib diisi.';
                msg.style.display = 'flex';
              });
              field.addEventListener('input', function () {
                field.classList.remove('field-invalid');
                msg.style.display = 'none';
              });
              field.addEventListener('change', function () {
                field.classList.remove('field-invalid');
                msg.style.display = 'none';
              });
            });
          }

          // Konfirmasi dulu sebelum beneran kirim (senada sama modal Tambah
          // Satuan).
          var konfirmOverlay = document.getElementById('ubahSatuanKonfirmasiOverlay');
          if (form && konfirmOverlay) {
            function closeKonfirm() { konfirmOverlay.classList.remove('open'); }
            form.addEventListener('submit', function (e) {
              if (form.dataset.confirmed === '1') { form.dataset.confirmed = ''; return; }
              e.preventDefault();
              konfirmOverlay.classList.add('open');
            });
            document.getElementById('ubahSatuanKonfirmasiYa')?.addEventListener('click', function () {
              closeKonfirm();
              form.dataset.confirmed = '1';
              form.requestSubmit ? form.requestSubmit() : form.submit();
            });
            document.getElementById('ubahSatuanKonfirmasiBatal')?.addEventListener('click', closeKonfirm);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && konfirmOverlay.classList.contains('open')) closeKonfirm(); });
          }
        })();
      </script>

      {{-- ===== HAK AKSES PENGGUNA ===== --}}
      <section class="tab-panel" data-tab-panel="role-akses">
        <div class="section-head panel">
          <h2>Hak Akses Pengguna</h2>
          <p>Setiap satuan berperan sebagai role login. Atur modul apa saja yang boleh diakses tiap satuan.</p>
        </div>

        <div class="notice">
          <b>Cara pakai halaman ini:</b> tiap kotak di bawah ini adalah satu satuan/akun login. Modul yang <b>dicentang</b> akan muncul di menu dashboard mereka saat login — modul yang <b>tidak dicentang</b> akan disembunyikan dan tidak bisa diakses. Baca dulu keterangan di bawah nama tiap modul untuk tahu apa fungsinya, centang/hapus centang sesuai kebutuhan, lalu klik tombol <b>"Simpan Hak Akses"</b> di satuan yang diubah. Satuan hanya menampilkan modul yang memang relevan dengan tugasnya — kalau suatu modul tidak muncul di satuan tertentu, artinya modul itu memang tidak berlaku untuk satuan tersebut.
        </div>

        <style>
          .perm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:10px;margin-bottom:14px;}
          .perm-card{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border:1px solid var(--border);border-radius:10px;background:var(--panel-alt);cursor:pointer;transition:border-color .15s,background .15s;}
          .perm-card:hover{border-color:var(--border-strong);}
          .perm-card input[type="checkbox"]{margin-top:3px;width:16px;height:16px;accent-color:var(--gold-bright);flex-shrink:0;cursor:pointer;}
          .perm-card-main{display:flex;flex-direction:column;gap:3px;flex:1;min-width:0;}
          .perm-card-title{font-size:12.5px;font-weight:700;color:var(--text);}
          .perm-card-desc{font-size:11px;color:var(--text-muted);line-height:1.55;}
          .perm-card-status{font-size:9.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:3px 8px;border-radius:6px;background:var(--panel-2);color:var(--text-dim);white-space:nowrap;flex-shrink:0;}
          .perm-card.is-active{border-color:var(--gold-bright);background:var(--gold-dim);}
          .perm-card.is-active .perm-card-status{background:var(--panel-alt);color:var(--gold-bright);}
        </style>

        @foreach($semuaSatuan as $s)
        <div class="panel">
          <div class="panel-head"><div><h3>{{ $s->nama }} <span class="badge">{{ $s->kode }}</span></h3><p>{{ $s->deskripsi ?: 'Tidak ada deskripsi.' }}</p></div></div>
          <form method="POST" action="{{ route('admin.satuan.permissions', $s) }}" style="padding:18px 22px;">
            @csrf @method('PATCH')
            <div class="perm-grid">
              @foreach(\App\Models\Satuan::modulHakAksesUntukRole($s->kode) as $key => $label)
              @php $modulAktif = in_array($key, $s->permissions ?? []); @endphp
              <label class="perm-card {{ $modulAktif ? 'is-active' : '' }}">
                <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked($modulAktif)>
                <span class="perm-card-main">
                  <span class="perm-card-title">{{ $label }}</span>
                  <span class="perm-card-desc">{{ \App\Models\Satuan::MODUL_HAK_AKSES_DESKRIPSI[$key] ?? '' }}</span>
                </span>
                <span class="perm-card-status">{{ $modulAktif ? 'Aktif' : 'Nonaktif' }}</span>
              </label>
              @endforeach
            </div>
            <button class="btn btn-primary btn-sm" type="submit">Simpan Hak Akses {{ $s->kode }}</button>
          </form>
        </div>
        @endforeach

        <script>
          (function () {
            document.querySelectorAll('[data-tab-panel="role-akses"] .perm-card input[type="checkbox"]').forEach(function (cb) {
              cb.addEventListener('change', function () {
                var card = cb.closest('.perm-card');
                var status = card.querySelector('.perm-card-status');
                card.classList.toggle('is-active', cb.checked);
                status.textContent = cb.checked ? 'Aktif' : 'Nonaktif';
              });
            });
          })();
        </script>
      </section>

      {{-- ===== LOG AKTIVITAS ===== --}}
      <section class="tab-panel" data-tab-panel="log-aktivitas">
        <div class="panel">
          <div class="panel-head"><div><h2>Riwayat Aktivitas</h2><p>Rekam jejak login, logout, dan seluruh aksi kelola sistem oleh Admin.</p></div></div>

          <div class="dl-search-row">
            <div class="table-search-wrap" style="max-width:280px;">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
              <input type="text" class="table-search" data-table-search="tblLogAktivitas" placeholder="Cari pengguna atau aksi...">
            </div>
            <form method="GET" action="{{ route('dashboard') }}" id="logFilterForm" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
              <div class="dl-date-filter">
                <label for="logDariInput">Dari</label>
                <input type="date" id="logDariInput" class="table-filter" name="log_dari" value="{{ $logDari->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
              </div>
              <div class="dl-date-filter">
                <label for="logSampaiInput">Sampai</label>
                <input type="date" id="logSampaiInput" class="table-filter" name="log_sampai" value="{{ $logSampai->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
              </div>
              <select class="table-filter dl-kategori-filter" data-table-filter="tblLogAktivitas">
                <option value="">Semua Kategori</option>
                <option value="Admin">Admin</option>
                <option value="Pimpinan">Pimpinan</option>
                <option value="Unsur Pelayanan">Unsur Pelayanan</option>
                <option value="Unsur Pembantu Pimpinan">Unsur Pembantu Pimpinan</option>
                <option value="Direktorat">Direktorat</option>
                <option value="Satlak">Satlak</option>
                <option value="Kasansi">Kasansi</option>
              </select>
              <button type="button" id="logFilterReset" class="dl-filter-reset" title="Reset ke rentang default">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
              </button>
            </form>
            <span class="table-filter-count" data-table-count="tblLogAktivitas"></span>
          </div>
          <div class="tbl-wrap" data-row-limit="10">
            <table class="dtbl" id="tblLogAktivitas">
              <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Deskripsi</th><th>IP</th></tr></thead>
              <tbody>
                @forelse($logAktivitas as $l)
                @php
                  $kategoriLabelLog = $l->user && $l->user->satuan ? match ($l->user->satuan->kategori) {
                    \App\Models\Satuan::KATEGORI_ADMIN => 'Admin',
                    \App\Models\Satuan::KATEGORI_PIMPINAN => 'Pimpinan',
                    \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
                    \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
                    \App\Models\Satuan::KATEGORI_DIREKTORAT => 'Direktorat',
                    \App\Models\Satuan::KATEGORI_KOTAMA => 'Kasansi',
                    default => 'Satlak',
                  } : null;
                @endphp
                <tr data-filter-value="{{ $kategoriLabelLog }}">
                  <td style="white-space:nowrap;">{{ $l->created_at?->translatedFormat('d M Y H:i') }}</td>
                  <td>{{ $l->nama_pengguna ?? '-' }}</td>
                  <td><span class="badge">{{ $l->aksi }}</span></td>
                  <td style="color:var(--text-muted);">{{ $l->deskripsi }}</td>
                  <td style="color:var(--text-dim);">{{ $l->ip_address }}</td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada aktivitas tercatat</div></div></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <script>
          (function () {
            var dariInput = document.getElementById('logDariInput');
            var sampaiInput = document.getElementById('logSampaiInput');
            var tbody = document.querySelector('#tblLogAktivitas tbody');
            var endpoint = '{{ route('admin.log-aktivitas.rentang') }}';
            if (!dariInput || !sampaiInput || !tbody) return;

            function escapeHtml(s) {
              return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
              });
            }

            function muatUlangViaReload() {
              var url = new URL(window.location.href);
              url.searchParams.set('log_dari', dariInput.value);
              url.searchParams.set('log_sampai', sampaiInput.value);
              window.location.href = url.toString();
            }

            function muatUlang() {
              var wrap = tbody.closest('.tbl-wrap');
              if (wrap) wrap.style.opacity = '.5';
              var params = new URLSearchParams({ log_dari: dariInput.value, log_sampai: sampaiInput.value });
              fetch(endpoint + '?' + params.toString(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) {
                  if (!r.ok) throw new Error('HTTP ' + r.status);
                  return r.json();
                })
                .then(function (data) {
                  if (wrap) wrap.style.opacity = '';
                  if (!data || !Array.isArray(data.log)) throw new Error('Respons tidak sesuai format yang diharapkan.');
                  tbody.innerHTML = data.log.length ? data.log.map(function (l) {
                    return '<tr data-filter-value="' + escapeHtml(l.kategori || '') + '">'
                      + '<td style="white-space:nowrap;">' + escapeHtml(l.waktu) + '</td>'
                      + '<td>' + escapeHtml(l.pengguna) + '</td>'
                      + '<td><span class="badge">' + escapeHtml(l.aksi) + '</span></td>'
                      + '<td style="color:var(--text-muted);">' + escapeHtml(l.deskripsi) + '</td>'
                      + '<td style="color:var(--text-dim);">' + escapeHtml(l.ip) + '</td>'
                      + '</tr>';
                  }).join('') : '<tr class="table-empty-row"><td colspan="5"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada aktivitas tercatat</div></div></td></tr>';
                  if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblLogAktivitas');
                  try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('log_dari', dariInput.value);
                    url.searchParams.set('log_sampai', sampaiInput.value);
                    history.replaceState(null, '', url);
                  } catch (e) {}
                })
                .catch(function (err) {
                  if (wrap) wrap.style.opacity = '';
                  console.error('Gagal memuat log aktivitas via AJAX, fallback ke reload halaman:', err);
                  muatUlangViaReload();
                });
            }

            var debounceTimer = null;
            function muatUlangDebounced() {
              if (!dariInput.value || !sampaiInput.value) return;
              clearTimeout(debounceTimer);
              debounceTimer = setTimeout(muatUlang, 250);
            }
            [dariInput, sampaiInput].forEach(function (el) {
              el.addEventListener('change', muatUlang);
              el.addEventListener('input', muatUlangDebounced);
            });

            var resetBtn = document.getElementById('logFilterReset');
            if (resetBtn) {
              resetBtn.addEventListener('click', function () {
                // Samakan dengan Data Laporan: "Dari" & "Sampai" sama-sama
                // dikosongkan (tanpa batas tanggal sama sekali).
                dariInput.value = '';
                sampaiInput.value = '';

                // Balikin juga filter kategori ke "Semua Kategori"
                var kategoriEl = document.querySelector('[data-table-filter="tblLogAktivitas"]');
                if (kategoriEl && kategoriEl.value !== '') {
                  kategoriEl.value = '';
                  var ssWrap = kategoriEl.closest('.styled-select-wrap');
                  if (ssWrap && ssWrap.__syncStyledSelect) ssWrap.__syncStyledSelect();
                  kategoriEl.dispatchEvent(new Event('change', { bubbles: true }));
                }

                muatUlang();
                resetBtn.classList.remove('spinning');
                void resetBtn.offsetWidth;
                resetBtn.classList.add('spinning');
              });
            }
          })();
          </script>
        </div>
      </section>

      {{-- ===== BACKUP DATABASE ===== --}}
      <section class="tab-panel" data-tab-panel="backup">
        <div class="section-head panel">
          <h2>Cadangan Data</h2>
          <p>Buat salinan database sewaktu-waktu dan unduh untuk disimpan di luar server.</p>
        </div>


        <div class="panel">
          <div class="panel-head"><div><h3>Buat Cadangan Baru</h3><p>Untuk koneksi SQLite: salin file database. Untuk MySQL: jalankan mysqldump.</p></div></div>
          <form method="POST" action="{{ route('admin.backup.store') }}" style="padding:18px 22px;">
            @csrf
            <button class="btn btn-primary" type="submit">Buat Cadangan</button>
          </form>
        </div>

        <div class="backup-upload-panel" style="display:none">
          <form method="POST" action="{{ route('admin.backup.upload') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="backup_file" accept=".sql,.sqlite" required>
            <button class="btn btn-primary" type="submit">Unggah File Cadangan</button>
          </form>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Riwayat Backup</h3></div></div>

          <div class="log-filter-row" id="backupFilterRow">
            <div class="log-filter-field">
              <label for="backupDariInput">Dari</label>
              <input type="date" id="backupDariInput" class="table-filter" max="{{ now()->format('Y-m-d') }}">
            </div>
            <div class="log-filter-field">
              <label for="backupSampaiInput">Sampai</label>
              <input type="date" id="backupSampaiInput" class="table-filter" max="{{ now()->format('Y-m-d') }}">
            </div>
            <button type="button" id="backupFilterReset" class="log-filter-reset" title="Reset filter">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            </button>
          </div>

          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl" id="tblRiwayatBackup">
              <thead><tr><th>Nama File</th><th>Ukuran</th><th>Tanggal</th><th>Jam</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($daftarBackup as $b)
                <tr data-tanggal="{{ $b['tanggal_iso'] }}">
                  <td>{{ $b['nama'] }}</td>
                  <td>{{ $b['ukuran'] }}</td>
                  <td>{{ $b['tanggal'] }}</td>
                  <td>{{ $b['jam'] }}</td>
                  <td>
                    <div class="btn-row">
                      <a class="btn btn-sm" href="{{ route('admin.backup.download', $b['nama']) }}">Unduh</a>
                      <button class="table-action-btn danger" type="button" onclick="bukaHapusBackup(this)"
                        data-action="{{ route('admin.backup.destroy', $b['nama']) }}"
                        data-nama="{{ $b['nama'] }}">Hapus</button>
                    </div>
                  </td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada backup dibuat</div></div></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <script>
          (function () {
            var dariInput = document.getElementById('backupDariInput');
            var sampaiInput = document.getElementById('backupSampaiInput');
            var table = document.getElementById('tblRiwayatBackup');
            if (!dariInput || !sampaiInput || !table) return;

            function terapkanFilterBackup() {
              var dari = dariInput.value;
              var sampai = sampaiInput.value;
              var rows = table.querySelectorAll('tbody tr[data-tanggal]');
              rows.forEach(function (tr) {
                var tgl = tr.getAttribute('data-tanggal');
                var cocokDari = !dari || tgl >= dari;
                var cocokSampai = !sampai || tgl <= sampai;
                tr.style.display = (cocokDari && cocokSampai) ? '' : 'none';
              });
              var wrap = table.closest('[data-row-limit]');
              if (wrap && window.terapkanRowLimitWrap) window.terapkanRowLimitWrap(wrap);
            }

            [dariInput, sampaiInput].forEach(function (el) {
              el.addEventListener('change', terapkanFilterBackup);
            });

            var resetBtn = document.getElementById('backupFilterReset');
            if (resetBtn) {
              resetBtn.addEventListener('click', function () {
                dariInput.value = '';
                sampaiInput.value = '';
                terapkanFilterBackup();
                resetBtn.classList.remove('spinning');
                void resetBtn.offsetWidth;
                resetBtn.classList.add('spinning');
              });
            }
          })();
          </script>
        </div>
      </section>

      {{-- ===== DATA LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="laporan-admin">
        <div class="panel dl-head-panel">
          <div class="dl-head">
            <div class="dl-head-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>
            </div>
            <div>
              <h2>Data Laporan</h2>
              <p>Rekap data pengguna dan aktivitas sistem, siap diekspor. Unduh dalam format CSV (bisa dibuka Excel) atau cetak sebagai PDF.</p>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="dl-tabs">
            <button type="button" class="dl-tab active" data-dl-tab="dl-pengguna">
              <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              Data Pengguna
            </button>
            <button type="button" class="dl-tab" data-dl-tab="dl-aktivitas">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
              Data Aktivitas
            </button>
          </div>

          {{-- ----- Sub-tab: Data Pengguna ----- --}}
          <div class="dl-section active" data-dl-section="dl-pengguna">
            <div class="dl-section-head">
              <div>
                <h3>Data Pengguna</h3>
                <p>Daftar pengguna sistem yang dapat dilihat dan diunduh.</p>
              </div>
              <div class="dl-download" data-dropdown>
                <button type="button" class="btn btn-primary btn-sm dl-download-btn" data-dropdown-toggle>
                  Unduh
                  <svg class="chev" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="dl-download-menu">
                  <a href="{{ route('admin.laporan.export-pengguna') }}"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>Unduh CSV / Excel</a>
                  <a href="{{ route('admin.laporan.cetak', 'pengguna') }}" target="_blank"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>Unduh PDF</a>
                </div>
              </div>
            </div>

            <div class="dl-search-row">
              <div class="table-search-wrap" style="max-width:280px;">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
                <input type="text" class="table-search" data-dl-search="tblDlPengguna" placeholder="Cari pengguna...">
              </div>
              <div class="dl-date-filter">
                <label for="dlPenggunaDari">Dari</label>
                <input type="date" id="dlPenggunaDari" class="table-filter" max="{{ now()->format('Y-m-d') }}">
              </div>
              <div class="dl-date-filter">
                <label for="dlPenggunaSampai">Sampai</label>
                <input type="date" id="dlPenggunaSampai" class="table-filter" max="{{ now()->format('Y-m-d') }}" value="{{ now()->format('Y-m-d') }}">
              </div>
              <select class="table-filter dl-kategori-filter" data-dl-filter="tblDlPengguna">
                <option value="">Semua Kategori</option>
                <option value="Admin">Admin</option>
                <option value="Pimpinan">Pimpinan</option>
                <option value="Unsur Pelayanan">Unsur Pelayanan</option>
                <option value="Unsur Pembantu Pimpinan">Unsur Pembantu Pimpinan</option>
                <option value="Direktorat">Direktorat</option>
                <option value="Satlak">Satlak</option>
                <option value="Kasansi">Kasansi</option>
              </select>
              <button type="button" class="dl-filter-reset" id="dlPenggunaReset" title="Reset filter tanggal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
              </button>
              <span class="dl-search-count" data-dl-count="tblDlPengguna"></span>
            </div>

            <div class="tbl-wrap tbl-scroll" style="max-height:420px;">
              <table class="dtbl" id="tblDlPengguna">
                <thead><tr><th>No</th><th>Nama</th><th>Username</th><th>Email</th><th>Satuan</th><th>Dibuat</th></tr></thead>
                <tbody>
                  @forelse($semuaPengguna as $i => $p)
                  @php
                    $kategoriLabelDlPengguna = match ($p->satuan->kategori ?? null) {
                      \App\Models\Satuan::KATEGORI_ADMIN => 'Admin',
                      \App\Models\Satuan::KATEGORI_PIMPINAN => 'Pimpinan',
                      \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
                      \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
                      \App\Models\Satuan::KATEGORI_DIREKTORAT => 'Direktorat',
                      \App\Models\Satuan::KATEGORI_KOTAMA => 'Kasansi',
                      default => 'Satlak',
                    };
                  @endphp
                  <tr data-filter-value="{{ $kategoriLabelDlPengguna }}" data-search-value="{{ strtolower($p->name.' '.$p->username.' '.$p->email.' '.($p->satuan->nama ?? '').' '.($p->jabatan ?? '')) }}">
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $p->satuan->nama_singkat ?? $p->name }}</strong></td>
                    <td>{{ $p->username }}</td>
                    <td>{{ $p->email ?: '-' }}</td>
                    <td>{{ $p->satuan->nama_keterangan ?? '-' }}</td>
                    <td style="white-space:nowrap;" data-tanggal="{{ $p->created_at?->format('Y-m-d') }}">{{ $p->created_at?->format('d/m/Y H:i') }}</td>
                  </tr>
                  @empty
                  <tr><td colspan="6"><div class="empty-state"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Belum ada pengguna</div></div></td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div class="dl-foot">
              <p>Data ditampilkan langsung dari database sistem.</p>
            </div>
          </div>

          {{-- ----- Sub-tab: Data Aktivitas ----- --}}
          <div class="dl-section" data-dl-section="dl-aktivitas">
            <div class="dl-section-head">
              <div>
                <h3>Data Aktivitas</h3>
                <p>Log aktivitas pengguna di dalam sistem yang dapat dilihat dan diunduh.</p>
              </div>
              <div class="dl-download" data-dropdown>
                <button type="button" class="btn btn-primary btn-sm dl-download-btn" data-dropdown-toggle>
                  Unduh
                  <svg class="chev" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="dl-download-menu">
                  <a href="{{ route('admin.laporan.export-aktivitas') }}"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>Unduh CSV / Excel</a>
                  <a href="{{ route('admin.laporan.cetak', 'aktivitas') }}" target="_blank"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>Unduh PDF</a>
                </div>
              </div>
            </div>

            <div class="dl-search-row">
              <div class="table-search-wrap" style="max-width:280px;">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
                <input type="text" class="table-search" data-dl-search="tblDlAktivitas" placeholder="Cari log aktivitas...">
              </div>
              <div class="dl-date-filter">
                <label for="dlAktivitasDari">Dari</label>
                <input type="date" id="dlAktivitasDari" class="table-filter" max="{{ now()->format('Y-m-d') }}">
              </div>
              <div class="dl-date-filter">
                <label for="dlAktivitasSampai">Sampai</label>
                <input type="date" id="dlAktivitasSampai" class="table-filter" max="{{ now()->format('Y-m-d') }}" value="{{ now()->format('Y-m-d') }}">
              </div>
              <select class="table-filter dl-kategori-filter" data-dl-filter="tblDlAktivitas">
                <option value="">Semua Kategori</option>
                <option value="Admin">Admin</option>
                <option value="Pimpinan">Pimpinan</option>
                <option value="Unsur Pelayanan">Unsur Pelayanan</option>
                <option value="Unsur Pembantu Pimpinan">Unsur Pembantu Pimpinan</option>
                <option value="Direktorat">Direktorat</option>
                <option value="Satlak">Satlak</option>
                <option value="Kasansi">Kasansi</option>
              </select>
              <button type="button" class="dl-filter-reset" id="dlAktivitasReset" title="Reset filter tanggal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
              </button>
              <span class="dl-search-count" data-dl-count="tblDlAktivitas"></span>
            </div>

            <div class="tbl-wrap tbl-scroll" style="max-height:420px;">
              <table class="dtbl" id="tblDlAktivitas">
                <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Deskripsi</th><th>IP</th></tr></thead>
                <tbody>
                  @forelse($logAktivitas as $l)
                  @php
                    $kategoriLabelDlAktivitas = $l->user && $l->user->satuan ? match ($l->user->satuan->kategori) {
                      \App\Models\Satuan::KATEGORI_ADMIN => 'Admin',
                      \App\Models\Satuan::KATEGORI_PIMPINAN => 'Pimpinan',
                      \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
                      \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
                      \App\Models\Satuan::KATEGORI_DIREKTORAT => 'Direktorat',
                      \App\Models\Satuan::KATEGORI_KOTAMA => 'Kasansi',
                      default => 'Satlak',
                    } : null;
                  @endphp
                  <tr data-filter-value="{{ $kategoriLabelDlAktivitas }}" data-search-value="{{ strtolower(($l->nama_pengguna ?? '').' '.$l->aksi.' '.$l->deskripsi) }}">
                    <td style="white-space:nowrap;" data-tanggal="{{ $l->created_at?->format('Y-m-d') }}">{{ $l->created_at?->translatedFormat('d M Y H:i') }}</td>
                    <td>{{ $l->nama_pengguna ?? '-' }}</td>
                    <td><span class="badge">{{ $l->aksi }}</span></td>
                    <td style="color:var(--text-muted);">{{ $l->deskripsi }}</td>
                    <td style="color:var(--text-dim);">{{ $l->ip_address }}</td>
                  </tr>
                  @empty
                  <tr><td colspan="5"><div class="empty-state"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada aktivitas tercatat</div></div></td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div class="dl-foot">
              <p>Data ditampilkan langsung dari database sistem.</p>
            </div>
          </div>
        </div>
      </section>

      <script>
      (function () {
        // Toggle sub-tab Data Pengguna / Data Aktivitas di dalam panel Data Laporan.
        var tabs = document.querySelectorAll('.dl-tab');
        tabs.forEach(function (tab) {
          tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-dl-tab');
            document.querySelectorAll('.dl-tab').forEach(function (t) { t.classList.toggle('active', t === tab); });
            document.querySelectorAll('.dl-section').forEach(function (s) {
              s.classList.toggle('active', s.getAttribute('data-dl-section') === target);
            });
          });
        });

        // Dropdown tombol "Unduh" (dipisah dari dropdown lain di halaman ini
        // lewat query scope [data-dropdown] di dalam .dl-download).
        document.querySelectorAll('.dl-download[data-dropdown]').forEach(function (dd) {
          var toggle = dd.querySelector('[data-dropdown-toggle]');
          toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var sudahTerbuka = dd.classList.contains('open');
            document.querySelectorAll('.dl-download.open').forEach(function (o) { o.classList.remove('open'); });
            if (!sudahTerbuka) dd.classList.add('open');
          });
        });
        document.addEventListener('click', function () {
          document.querySelectorAll('.dl-download.open').forEach(function (o) { o.classList.remove('open'); });
        });

        // Hitung jumlah data yang tampil untuk kedua tabel Data Laporan, format
        // "X data ditampilkan" -- beda dari format global "X dari Y data" supaya
        // sama persis dengan rancangan.
        function dlHitungTampil(tableId) {
          var table = document.getElementById(tableId);
          var countEl = document.querySelector('[data-dl-count="' + tableId + '"]');
          if (!table || !countEl) return;
          var visible = Array.prototype.slice.call(table.querySelectorAll('tbody tr'))
            .filter(function (tr) { return tr.style.display !== 'none' && tr.hasAttribute('data-search-value'); });
          countEl.textContent = visible.length + ' data ditampilkan';
        }

        function dlSaring(tableId) {
          var table = document.getElementById(tableId);
          var input = document.querySelector('[data-dl-search="' + tableId + '"]');
          if (!table || !input) return;
          var q = input.value.trim().toLowerCase();
          table.querySelectorAll('tbody tr[data-search-value]').forEach(function (tr) {
            var cocok = !q || tr.getAttribute('data-search-value').indexOf(q) !== -1;
            tr.style.display = cocok ? '' : 'none';
          });
          dlHitungTampil(tableId);
        }

        ['tblDlPengguna', 'tblDlAktivitas'].forEach(function (id) {
          var input = document.querySelector('[data-dl-search="' + id + '"]');
          if (input) input.addEventListener('input', function () { dlSaringGabungan(id); });
          dlHitungTampil(id);
        });

        // ── Filter tanggal + refresh untuk Data Laporan ──────────────────────
        function parseLocalDate(str) {
          if (!str) return null;
          var p = str.split('-');
          return p.length === 3 ? new Date(+p[0], +p[1] - 1, +p[2]) : null;
        }

        function dlSaringTanggal(tableId, dariId, sampaiId) {
          var table   = document.getElementById(tableId);
          var dariEl  = document.getElementById(dariId);
          var sampaiEl= document.getElementById(sampaiId);
          var searchEl= document.querySelector('[data-dl-search="' + tableId + '"]');
          var filterEl= document.querySelector('[data-dl-filter="' + tableId + '"]');
          if (!table) return;

          var dari   = dariEl   ? parseLocalDate(dariEl.value)   : null;
          var sampai = sampaiEl ? parseLocalDate(sampaiEl.value) : null;
          if (sampai) sampai.setHours(23, 59, 59, 999);
          var q = searchEl ? searchEl.value.trim().toLowerCase() : '';
          var f = filterEl ? filterEl.value : '';

          table.querySelectorAll('tbody tr[data-search-value]').forEach(function (tr) {
            // filter teks
            var cocokTeks = !q || tr.getAttribute('data-search-value').indexOf(q) !== -1;

            // filter kategori (dropdown "Semua Kategori", sama seperti tabel lain)
            var cocokFilter = !f || tr.getAttribute('data-filter-value') === f;

            // filter tanggal — baca dari kolom pertama (td:first-child)
            var cocokTgl = true;
            if (dari || sampai) {
              var td = tr.querySelector('td[data-tanggal]') || tr.querySelector('td:first-child');
              var raw = td ? (td.getAttribute('data-tanggal') || td.textContent.trim()) : '';
              // Coba parse ISO (YYYY-MM-DD) atau format lokal dd/MM/YYYY
              var tgl = null;
              if (/^\d{4}-\d{2}-\d{2}/.test(raw)) {
                tgl = parseLocalDate(raw.slice(0, 10));
              } else if (/^\d{2}\/\d{2}\/\d{4}/.test(raw)) {
                var bp = raw.split('/');
                tgl = new Date(+bp[2], +bp[1] - 1, +bp[0]);
              } else {
                tgl = new Date(raw);
              }
              if (!tgl || isNaN(tgl.getTime())) { cocokTgl = !dari && !sampai; }
              else {
                if (dari   && tgl < dari)   cocokTgl = false;
                if (sampai && tgl > sampai) cocokTgl = false;
              }
            }

            tr.style.display = (cocokTeks && cocokFilter && cocokTgl) ? '' : 'none';
          });
          dlHitungTampil(tableId);
        }

        // Override dlSaring untuk gabungkan teks + tanggal
        function dlSaringGabungan(tableId) {
          if (tableId === 'tblDlPengguna')  dlSaringTanggal('tblDlPengguna',  'dlPenggunaDari',  'dlPenggunaSampai');
          if (tableId === 'tblDlAktivitas') dlSaringTanggal('tblDlAktivitas', 'dlAktivitasDari', 'dlAktivitasSampai');
        }

        // Pasang listener pada input tanggal
        [
          ['dlPenggunaDari',   'dlPenggunaSampai',   'tblDlPengguna'],
          ['dlAktivitasDari',  'dlAktivitasSampai',  'tblDlAktivitas'],
        ].forEach(function (cfg) {
          var dariEl   = document.getElementById(cfg[0]);
          var sampaiEl = document.getElementById(cfg[1]);
          var tid      = cfg[2];
          [dariEl, sampaiEl].forEach(function (el) {
            if (el) el.addEventListener('change', function () { dlSaringTanggal(tid, cfg[0], cfg[1]); });
          });
        });

        // Override listener search supaya juga jalankan filter tanggal
        ['tblDlPengguna', 'tblDlAktivitas'].forEach(function (id) {
          var input = document.querySelector('[data-dl-search="' + id + '"]');
          if (input) {
            // hapus listener lama (cloneNode), pasang yang baru
            var fresh = input.cloneNode(true);
            input.parentNode.replaceChild(fresh, input);
            fresh.addEventListener('input', function () { dlSaringGabungan(id); });
          }
        });

        // Tombol refresh/reset tanggal
        function buatResetHandler(dariId, sampaiId, tableId) {
          var btn = document.getElementById(
            tableId === 'tblDlPengguna' ? 'dlPenggunaReset' : 'dlAktivitasReset'
          );
          if (!btn) return;
          btn.addEventListener('click', function () {
            var dariEl   = document.getElementById(dariId);
            var sampaiEl = document.getElementById(sampaiId);
            if (dariEl)   dariEl.value   = '';
            if (sampaiEl) sampaiEl.value = '';

            // Balikin juga filter kategori ke "Semua Kategori"
            var kategoriEl = document.querySelector('[data-dl-filter="' + tableId + '"]');
            if (kategoriEl && kategoriEl.value !== '') {
              kategoriEl.value = '';
              var ssWrap = kategoriEl.closest('.styled-select-wrap');
              if (ssWrap && ssWrap.__syncStyledSelect) ssWrap.__syncStyledSelect();
            }

            dlSaringTanggal(tableId, dariId, sampaiId);
            btn.classList.remove('spinning');
            void btn.offsetWidth;
            btn.classList.add('spinning');
          });
        }
        buatResetHandler('dlPenggunaDari',  'dlPenggunaSampai',  'tblDlPengguna');
        buatResetHandler('dlAktivitasDari', 'dlAktivitasSampai', 'tblDlAktivitas');

        // Dropdown filter kategori Data Laporan (Data Pengguna & Data Aktivitas)
        ['tblDlPengguna', 'tblDlAktivitas'].forEach(function (id) {
          var cfg = id === 'tblDlPengguna'
            ? ['dlPenggunaDari', 'dlPenggunaSampai']
            : ['dlAktivitasDari', 'dlAktivitasSampai'];
          var filterEl = document.querySelector('[data-dl-filter="' + id + '"]');
          if (filterEl) filterEl.addEventListener('change', function () { dlSaringTanggal(id, cfg[0], cfg[1]); });
        });
      })();
      </script>

      {{-- ===== PENGATURAN UMUM ===== --}}
      <section class="tab-panel" data-tab-panel="pengaturan-umum">
        <div class="section-head panel">
          <h2>Pengaturan Umum</h2>
          <p>Konfigurasi umum aplikasi SIBERAD.</p>
        </div>


        {{-- ===== KONTEN HALAMAN LANDING (klik elemen di pratinjau untuk edit) ===== --}}
        @php
          $lp = $pengaturan->landingConfig();
          $lpLogoUrl = $pengaturan->logo_path ? asset('storage/'.$pengaturan->logo_path) : asset('images/logo-pussiberad.jpg');
          $lpHeroImageUrl = $pengaturan->hero_image_path ? asset('storage/'.$pengaturan->hero_image_path) : null;
          $lpFiturList = $pengaturan->fitur ?? [];
          $lpSosialList = $pengaturan->sosial_media ?? [];
        @endphp

        <div class="panel lp-panel">
          <div class="panel-head">
            <div>
              <h3>Konten Halaman Landing <span class="lp-live-dot" aria-hidden="true"></span></h3>
              <p>Ini tampilan asli landing page. Klik langsung bagian yang mau diubah — logo, judul, deskripsi, kartu fitur, kontak, dan sosial media — lalu isi popup yang muncul.</p>
            </div>
          </div>

          <div class="lp-editor-body">
            <div class="lp-browser-frame">
              <div class="lp-browser-bar">
                <span class="lp-browser-dot"></span><span class="lp-browser-dot"></span><span class="lp-browser-dot"></span>
                <span class="lp-browser-url">siberad</span>
              </div>

              <div class="lp-preview" id="lpPreview">

                {{-- ---------- NAVBAR: LOGO & MEREK ---------- --}}
                <div class="lp-nav">
                  <div class="lp-nav-brand">
                    <button type="button" class="lp-hotspot lp-logo-hotspot" data-editable="logo" title="Klik untuk ganti logo">
                      <img src="{{ $lpLogoUrl }}" alt="Logo" id="lpPvLogo">
                      <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                    </button>
                    <button type="button" class="lp-hotspot lp-brand-hotspot" data-editable="brand" title="Klik untuk ubah nama & tagline">
                      <span class="lp-nav-brand-text">
                        <b id="lpPvBrandName"></b><em id="lpPvBrandAccent"></em>
                        <small id="lpPvBrandTagline"></small>
                      </span>
                      <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                    </button>
                  </div>
                  <span class="lp-nav-hint">Menu navigasi &amp; tombol masuk mengikuti tampilan asli (tidak dapat diedit di sini)</span>
                </div>

                {{-- ---------- HERO ---------- --}}
                <div class="lp-hero" id="lpPreviewHero"
                  @if($lpHeroImageUrl)
                    style="background-image:linear-gradient(160deg, color-mix(in srgb, var(--panel-2) 85%, transparent), color-mix(in srgb, var(--bg-deep) 75%, transparent)), url('{{ $lpHeroImageUrl }}');background-size:cover;background-position:center;"
                  @endif
                >
                  <button type="button" class="lp-hotspot lp-hotspot-block" data-editable="hero_eyebrow" title="Klik untuk ubah label">
                    <div class="lp-eyebrow" id="lpPvEyebrow"></div>
                    <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                  </button>
                  <button type="button" class="lp-hotspot lp-hotspot-block" data-editable="hero_judul" title="Klik untuk ubah judul utama">
                    <div class="lp-h1"><span id="lpPvJudulAwal"></span><em id="lpPvJudulAksen"></em></div>
                    <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                  </button>
                  <button type="button" class="lp-hotspot lp-hotspot-block" data-editable="hero_subjudul" title="Klik untuk ubah sub judul">
                    <div class="lp-h2" id="lpPvSubjudul"></div>
                    <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                  </button>
                  <button type="button" class="lp-hotspot lp-hotspot-block" data-editable="hero_deskripsi" title="Klik untuk ubah deskripsi">
                    <div class="lp-p" id="lpPvDeskripsi"></div>
                    <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                  </button>
                  <button type="button" class="lp-hotspot lp-hero-image-hotspot" data-editable="hero_image" title="Klik untuk ganti gambar latar">
                    <span class="lp-hotspot-pencil lp-hotspot-pencil-static"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg> Ganti gambar latar</span>
                  </button>
                </div>

                {{-- ---------- FITUR ---------- --}}
                <div class="lp-features" id="lpPvFitur">
                  @foreach ($lpFiturList as $i => $fitur)
                    <button type="button" class="lp-feature-card lp-hotspot" data-editable="fitur" data-index="{{ $i }}" title="Klik untuk ubah kartu fitur ini">
                      <b>{{ $fitur['judul'] ?? '' }}</b>
                      <span>{{ $fitur['deskripsi'] ?? '' }}</span>
                      <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                    </button>
                  @endforeach
                </div>

                {{-- ---------- TENTANG ---------- --}}
                <div class="lp-about">
                  <div class="lp-section-title">Tentang</div>
                  <button type="button" class="lp-hotspot lp-hotspot-block" data-editable="tentang_deskripsi" title="Klik untuk ubah deskripsi tentang">
                    <div class="lp-p" id="lpPvTentang"></div>
                    <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                  </button>
                  <button type="button" class="lp-hotspot lp-hotspot-block" data-editable="tentang_moto" title="Klik untuk ubah moto">
                    <div class="lp-moto-title" id="lpPvMotoJudul"></div>
                    <div class="lp-p" id="lpPvMoto"></div>
                    <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                  </button>
                </div>

                {{-- ---------- KONTAK / FOOTER ---------- --}}
                <div class="lp-footer">
                  <div class="lp-section-title">Kontak</div>
                  <button type="button" class="lp-hotspot lp-hotspot-inline" data-editable="alamat" title="Klik untuk ubah alamat">
                    <div class="lp-p" id="lpPvAlamat" data-lp-empty="Alamat belum diisi"></div>
                    <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                  </button>
                  <button type="button" class="lp-hotspot lp-hotspot-inline" data-editable="telepon_kontak" title="Klik untuk ubah telepon">
                    <div class="lp-p" id="lpPvTelepon" data-lp-empty="Telepon belum diisi"></div>
                    <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                  </button>
                  <button type="button" class="lp-hotspot lp-hotspot-inline" data-editable="email_kontak" title="Klik untuk ubah email">
                    <div class="lp-p" id="lpPvEmail" data-lp-empty="Email belum diisi"></div>
                    <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                  </button>
                  <button type="button" class="lp-hotspot lp-hotspot-inline" data-editable="website" title="Klik untuk ubah website">
                    <div class="lp-p" id="lpPvWebsite" data-lp-empty="Website belum diisi"></div>
                    <span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
                  </button>

                  <div class="lp-sosial-list" id="lpPvSosial">
                    @foreach ($lpSosialList as $i => $sosial)
                      <button type="button" class="lp-sosial-chip lp-hotspot" data-editable="sosial_media" data-index="{{ $i }}" title="Klik untuk ubah/hapus"></button>
                    @endforeach
                  </div>
                  <button type="button" class="lp-add-sosial" id="lpAddSosial" data-editable="sosial_media" data-index="new">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah Sosial Media
                  </button>
                </div>

              </div>
            </div>
          </div>
        </div>

        {{-- ---------- MODAL EDIT (generik, isinya diisi lewat JS sesuai elemen yang diklik) ---------- --}}
        <div class="lp-modal-overlay" id="lpModalOverlay" aria-hidden="true">
          <div class="lp-modal" role="dialog" aria-modal="true" aria-labelledby="lpModalTitle">
            <button type="button" class="lp-modal-close" id="lpModalClose" aria-label="Tutup">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
            <h3 class="lp-modal-title" id="lpModalTitle"></h3>
            <p class="lp-modal-desc" id="lpModalDesc"></p>
            <div class="lp-modal-body" id="lpModalBody"></div>
            <div class="lp-modal-error" id="lpModalError"></div>
            <div class="lp-modal-actions">
              <button type="button" class="lp-btn-delete" id="lpModalDelete" hidden>Hapus</button>
              <div class="lp-modal-actions-right">
                <button type="button" class="btn btn-ghost" id="lpModalCancel">Batal</button>
                <button type="button" class="btn btn-primary" id="lpModalSave">Simpan</button>
              </div>
            </div>
          </div>
        </div>

        <div class="lp-toast" id="lpToast" role="status" aria-live="polite"></div>

        <style>
          .lp-panel .panel-head h3{display:flex;align-items:center;gap:9px;}
          .lp-live-dot{width:7px;height:7px;border-radius:50%;background:var(--success-bright);box-shadow:0 0 0 3px var(--success-dim);animation:lpPulse 1.8s ease-in-out infinite;}
          @keyframes lpPulse{ 0%,100%{opacity:1;} 50%{opacity:.35;} }
          .lp-editor-body{padding:0 22px 22px;}

          .lp-browser-frame{
            border-radius:12px;overflow:hidden;border:1px solid var(--border-soft);
            box-shadow:0 14px 34px -14px rgba(0,0,0,.4);
            background:var(--bg);
          }
          .lp-browser-bar{display:flex;align-items:center;gap:6px;padding:10px 12px;background:var(--panel-alt);border-bottom:1px solid var(--border-soft);}
          .lp-browser-dot{width:8px;height:8px;border-radius:50%;background:var(--border-strong);}
          .lp-browser-url{
            margin-left:8px;flex:1;font-family:var(--mono);font-size:10.5px;color:var(--text-dim);
            background:var(--panel);border:1px solid var(--border-soft);border-radius:6px;padding:3px 10px;
          }

          .lp-preview{background:var(--bg);color:var(--text);font-family:var(--body);}

          /* ---- hotspot: elemen yang bisa diklik untuk edit ---- */
          .lp-hotspot{
            all:unset;box-sizing:border-box;position:relative;display:block;cursor:pointer;
            outline:2px dashed transparent;outline-offset:2px;border-radius:6px;transition:outline-color .15s ease,background-color .15s ease;
          }
          .lp-hotspot:hover{outline-color:var(--gold);background:color-mix(in srgb, var(--gold) 6%, transparent);}
          .lp-hotspot:focus-visible{outline-color:var(--gold);outline-style:solid;}
          .lp-hotspot-block{width:100%;text-align:left;padding:2px 6px;margin:0 -6px;}
          .lp-hotspot-inline{width:auto;display:inline-block;padding:2px 6px;margin:0 -6px 4px;}
          .lp-hotspot-pencil{
            position:absolute;top:4px;right:4px;width:22px;height:22px;border-radius:6px;
            background:var(--gold);color:#1c1400;display:flex;align-items:center;justify-content:center;
            opacity:0;transform:scale(.85);transition:opacity .15s ease,transform .15s ease;pointer-events:none;
          }
          .lp-hotspot-pencil svg{width:12px;height:12px;}
          .lp-hotspot:hover .lp-hotspot-pencil{opacity:1;transform:scale(1);}
          .lp-hotspot-pencil-static{position:static;opacity:1;transform:none;width:auto;height:auto;padding:6px 12px;gap:6px;font-size:11px;font-weight:700;}
          .lp-hotspot-pencil-static svg{width:14px;height:14px;}

          .lp-nav{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 22px;border-bottom:1px solid var(--border-soft);flex-wrap:wrap;}
          .lp-nav-brand{display:flex;align-items:center;gap:10px;}
          .lp-logo-hotspot{width:38px;height:38px;border-radius:8px;overflow:hidden;padding:0;margin:0;}
          .lp-logo-hotspot img{width:100%;height:100%;object-fit:cover;display:block;}
          .lp-brand-hotspot{padding:2px 8px;margin:0 -8px;}
          .lp-nav-brand-text{display:flex;flex-direction:column;line-height:1.15;}
          .lp-nav-brand-text b{font-family:var(--display);font-size:14px;text-transform:uppercase;color:var(--text);}
          .lp-nav-brand-text em{font-style:normal;color:var(--gold-bright);font-family:var(--display);font-size:14px;text-transform:uppercase;}
          .lp-nav-brand-text small{font-family:var(--mono);font-size:9px;letter-spacing:.06em;color:var(--text-dim);}
          .lp-nav-hint{font-size:10.5px;color:var(--text-dim);font-style:italic;}

          .lp-hero{position:relative;padding:26px 22px 60px;background:linear-gradient(160deg,var(--panel-2),var(--bg-deep));background-size:cover;background-position:center;}
          .lp-eyebrow{font-family:var(--mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--gold-bright);margin-bottom:10px;}
          .lp-h1{font-family:var(--display);font-size:27px;font-weight:700;line-height:1;text-transform:uppercase;margin-bottom:9px;color:var(--text);}
          .lp-h1 em{color:var(--gold-bright);font-style:normal;}
          .lp-h2{font-size:13px;font-weight:600;margin-bottom:9px;color:var(--text);}
          .lp-p{font-size:11.5px;line-height:1.65;color:var(--text-muted);white-space:pre-line;}
          .lp-hero-image-hotspot{position:absolute;left:22px;bottom:16px;width:auto;display:inline-flex;padding:0;outline:none;background:none;}
          .lp-hero-image-hotspot .lp-hotspot-pencil-static{background:var(--panel);border:1px solid var(--border-soft);color:var(--text);opacity:.92;display:flex;}
          .lp-hero-image-hotspot:hover .lp-hotspot-pencil-static{border-color:var(--gold);color:var(--gold-bright);opacity:1;}

          .lp-features{display:grid;grid-template-columns:1fr 1fr;gap:1px;background:var(--border-soft);}
          .lp-feature-card{background:var(--panel);padding:15px;width:100%;text-align:left;}
          .lp-feature-card b{display:block;font-family:var(--display);font-size:12.5px;margin-bottom:5px;color:var(--text);}
          .lp-feature-card span{font-size:10.5px;color:var(--text-muted);line-height:1.55;display:block;}

          .lp-about,.lp-footer{padding:20px 22px;border-top:1px solid var(--border-soft);}
          .lp-section-title{font-family:var(--mono);font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold-bright);margin-bottom:9px;}
          .lp-moto-title{font-family:var(--display);font-size:14px;font-weight:700;text-transform:uppercase;margin:12px 0 7px;color:var(--text);}

          .lp-sosial-list{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px;}
          .lp-sosial-chip{
            display:inline-flex;align-items:center;gap:5px;
            font-size:10.5px;color:var(--text-muted);
            border:1px solid var(--border-soft);border-radius:999px;padding:5px 10px;
            background:var(--panel-alt);
          }
          .lp-sosial-chip svg{width:12px;height:12px;color:var(--gold-bright);flex-shrink:0;}
          .lp-add-sosial{
            all:unset;box-sizing:border-box;display:inline-flex;align-items:center;gap:6px;margin-top:10px;cursor:pointer;
            font-size:10.5px;font-weight:700;color:var(--gold-bright);border:1px dashed var(--gold);border-radius:999px;padding:6px 12px;
            transition:background-color .15s ease;
          }
          .lp-add-sosial:hover{background:color-mix(in srgb, var(--gold) 10%, transparent);}
          .lp-add-sosial svg{width:12px;height:12px;}

          /* ---- modal popup ---- */
          .lp-modal-overlay{position:fixed;inset:0;z-index:100600;display:flex;align-items:center;justify-content:center;padding:20px;box-sizing:border-box;background:rgba(10,14,20,.6);backdrop-filter:blur(4px);opacity:0;visibility:hidden;pointer-events:none;transition:opacity .18s ease,visibility .18s ease;}
          .lp-modal-overlay.open{opacity:1;visibility:visible;pointer-events:auto;}
          .lp-modal{width:440px;max-width:calc(100vw - 32px);max-height:calc(100vh - 40px);overflow-y:auto;box-sizing:border-box;background:var(--panel);color:var(--text);border:1px solid var(--border-soft);border-radius:16px;box-shadow:0 30px 70px rgba(0,0,0,.4);padding:24px;position:relative;transform:translateY(8px) scale(.98);transition:transform .18s ease;}
          .lp-modal-overlay.open .lp-modal{transform:none;}
          .lp-modal-close{position:absolute;top:14px;right:14px;width:34px;height:34px;border:1px solid var(--border-soft);border-radius:9px;background:var(--panel-alt);color:var(--text-muted);display:flex;align-items:center;justify-content:center;cursor:pointer;}
          .lp-modal-close:hover{color:var(--text);border-color:var(--border);}
          .lp-modal-close svg{width:15px;height:15px;}
          .lp-modal-title{margin:0 40px 6px 0;font-family:var(--display);font-size:18px;font-weight:700;color:var(--text);}
          .lp-modal-desc{margin:0 0 16px;font-size:12px;line-height:1.55;color:var(--text-muted);}
          .lp-modal-body{display:flex;flex-direction:column;gap:14px;}
          .lp-modal-body label{display:block;font-size:11px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:var(--text-muted);margin-bottom:6px;}
          .lp-modal-body input[type=text],.lp-modal-body input[type=email],.lp-modal-body input[type=url],.lp-modal-body textarea,.lp-modal-body select{
            width:100%;box-sizing:border-box;background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:9px;
            color:var(--text);font-family:inherit;font-size:13px;padding:10px 12px;outline:none;transition:border-color .15s ease;
          }
          .lp-modal-body input:focus,.lp-modal-body textarea:focus,.lp-modal-body select:focus{border-color:var(--gold);}
          .lp-modal-body textarea{resize:vertical;min-height:70px;}
          .lp-modal-body input[type=file]{font-size:12px;color:var(--text-muted);}
          .lp-modal-body .lp-current-image{display:block;height:56px;margin-top:8px;border-radius:8px;border:1px solid var(--border-soft);}
          .lp-modal-error{display:none;margin-top:14px;padding:10px 12px;border:1px solid rgba(175,55,46,.3);background:rgba(175,55,46,.08);color:#e0847c;border-radius:9px;font-size:12px;line-height:1.5;}
          .lp-modal-error.show{display:block;}
          .lp-modal-actions{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border-soft);}
          .lp-modal-actions-right{display:flex;align-items:center;gap:8px;margin-left:auto;}
          .lp-btn-delete{all:unset;box-sizing:border-box;font-size:12px;font-weight:700;color:#e0847c;cursor:pointer;padding:9px 12px;border-radius:8px;border:1px solid rgba(224,132,124,.35);}
          .lp-btn-delete:hover{background:rgba(224,132,124,.1);}

          .lp-toast{
            position:fixed;left:50%;bottom:26px;transform:translate(-50%,16px);z-index:100700;
            background:var(--success-bright,#2fa86b);color:#062b17;font-size:12.5px;font-weight:700;
            padding:11px 18px;border-radius:999px;box-shadow:0 12px 30px -8px rgba(0,0,0,.4);
            opacity:0;pointer-events:none;transition:opacity .2s ease,transform .2s ease;
          }
          .lp-toast.show{opacity:1;transform:translate(-50%,0);}
        </style>

        <script>
          (function(){
            var preview = document.getElementById('lpPreview');
            if(!preview) return;

            var updateFieldUrl = @json(route('admin.pengaturan.landing.update-field'));
            var csrfToken = @json(csrf_token());

            var lpData = @json([
              'logo_url' => $lpLogoUrl,
              'hero_image_url' => $lpHeroImageUrl,
              'brand' => $lp['brand'],
              'hero_eyebrow' => $pengaturan->hero_eyebrow,
              'hero_judul_awal' => $pengaturan->hero_judul_awal,
              'hero_judul_aksen' => $pengaturan->hero_judul_aksen,
              'hero_subjudul' => $pengaturan->hero_subjudul,
              'hero_deskripsi' => $pengaturan->hero_deskripsi,
              'fitur' => $lpFiturList,
              'tentang_deskripsi' => $pengaturan->tentang_deskripsi,
              'tentang_moto_judul' => $pengaturan->tentang_moto_judul,
              'tentang_moto_deskripsi' => $pengaturan->tentang_moto_deskripsi,
              'alamat' => $pengaturan->alamat,
              'telepon_kontak' => $pengaturan->telepon_kontak,
              'email_kontak' => $pengaturan->email_kontak,
              'website' => $pengaturan->website,
              'sosial_media' => $lpSosialList,
            ]);

            var SOSIAL_PLATFORMS = [
              {value:'instagram', label:'Instagram'},
              {value:'tiktok', label:'TikTok'},
              {value:'youtube', label:'YouTube'},
              {value:'x', label:'X (Twitter)'},
              {value:'facebook', label:'Facebook'},
              {value:'wikipedia', label:'Wikipedia / Profil Resmi'}
            ];
            var SOSIAL_ICONS = {
              instagram: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1"/></svg>',
              tiktok: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 3h-3.1v12.4a2.7 2.7 0 1 1-1.9-2.6V9.6a5.8 5.8 0 1 0 5 5.7V9.4a7.9 7.9 0 0 0 4.4 1.3V7.6c-2.2-.2-4-1.9-4.4-4.1z"/></svg>',
              youtube: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M22.5 7.2c-.3-1.1-1.1-1.9-2.1-2.2C18.6 4.5 12 4.5 12 4.5s-6.6 0-8.4.5c-1 .3-1.8 1.1-2.1 2.2C1 9 1 12 1 12s0 3 .5 4.8c.3 1.1 1.1 1.9 2.1 2.2 1.8.5 8.4.5 8.4.5s6.6 0 8.4-.5c1-.3 1.8-1.1 2.1-2.2.5-1.8.5-4.8.5-4.8s0-3-.5-4.8zM9.8 15.3V8.7l6 3.3-6 3.3z"/></svg>',
              x: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 3H22l-7.5 8.6L23 21h-6.6l-5.2-6.6L5.2 21H2l8.1-9.3L2 3h6.7l4.7 6 5.5-6z"/></svg>',
              facebook: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-8h2.7l.4-3.1h-3.1V8c0-.9.3-1.5 1.6-1.5h1.7V3.7C16.5 3.6 15.6 3.5 14.6 3.5c-2.4 0-4 1.5-4 4.1v2.3H7.9V13h2.7v8h2.9z"/></svg>',
              wikipedia: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.8 5.7 3.8 9s-1.3 6.5-3.8 9c-2.5-2.5-3.8-5.7-3.8-9s1.3-6.5 3.8-9z"/></svg>'
            };

            // ---------- konfigurasi tiap jenis elemen yang bisa diedit ----------
            var FIELD_CONFIGS = {
              logo: {
                title: 'Ganti Logo', desc: 'Format gambar (PNG/JPG), maksimal 5MB.',
                fields: [{name:'logo_file', label:'File Logo Baru', type:'file', accept:'image/*', showCurrent:function(){ return lpData.logo_url; }}]
              },
              brand: {
                title: 'Nama & Merek', desc: 'Teks yang tampil di sebelah logo pada navigasi.',
                fields: [
                  {name:'brand_name', label:'Nama (mis. SIBER)', type:'text', maxlength:50, value:function(){return lpData.brand.name;}},
                  {name:'brand_accent', label:'Aksen warna emas (mis. AD)', type:'text', maxlength:50, value:function(){return lpData.brand.accent;}},
                  {name:'brand_tagline', label:'Tagline kecil di bawah nama', type:'text', maxlength:100, value:function(){return lpData.brand.tagline;}}
                ]
              },
              hero_eyebrow: {
                title: 'Label Kecil di Atas Judul',
                fields: [{name:'hero_eyebrow', label:'Teks label', type:'text', maxlength:255, value:function(){return lpData.hero_eyebrow;}}]
              },
              hero_judul: {
                title: 'Judul Utama',
                fields: [
                  {name:'hero_judul_awal', label:'Judul (bagian 1)', type:'text', maxlength:50, value:function(){return lpData.hero_judul_awal;}},
                  {name:'hero_judul_aksen', label:'Judul (bagian 2, warna emas)', type:'text', maxlength:50, value:function(){return lpData.hero_judul_aksen;}}
                ]
              },
              hero_subjudul: {
                title: 'Sub Judul',
                fields: [{name:'hero_subjudul', label:'Sub judul', type:'text', maxlength:255, value:function(){return lpData.hero_subjudul;}}]
              },
              hero_deskripsi: {
                title: 'Deskripsi Beranda',
                fields: [{name:'hero_deskripsi', label:'Deskripsi', type:'textarea', maxlength:2000, value:function(){return lpData.hero_deskripsi;}}]
              },
              hero_image: {
                title: 'Gambar Latar Beranda', desc: 'Opsional. Format gambar, maksimal 5MB.',
                fields: [{name:'hero_image', label:'File Gambar Baru', type:'file', accept:'image/*', showCurrent:function(){ return lpData.hero_image_url; }}]
              },
              fitur: {
                title: 'Kartu Fitur',
                fields: [
                  {name:'judul', label:'Judul', type:'text', maxlength:100, value:function(idx){return lpData.fitur[idx] ? lpData.fitur[idx].judul : '';}},
                  {name:'deskripsi', label:'Deskripsi', type:'textarea', maxlength:500, value:function(idx){return lpData.fitur[idx] ? lpData.fitur[idx].deskripsi : '';}}
                ]
              },
              tentang_deskripsi: {
                title: 'Deskripsi Tentang', desc: 'Pisahkan paragraf dengan baris kosong.',
                fields: [{name:'tentang_deskripsi', label:'Deskripsi', type:'textarea', maxlength:4000, value:function(){return lpData.tentang_deskripsi;}}]
              },
              tentang_moto: {
                title: 'Moto',
                fields: [
                  {name:'tentang_moto_judul', label:'Judul moto', type:'text', maxlength:255, value:function(){return lpData.tentang_moto_judul;}},
                  {name:'tentang_moto_deskripsi', label:'Deskripsi moto', type:'textarea', maxlength:2000, value:function(){return lpData.tentang_moto_deskripsi;}}
                ]
              },
              alamat: {
                title: 'Alamat',
                fields: [{name:'alamat', label:'Alamat (tampil di footer)', type:'textarea', maxlength:1000, value:function(){return lpData.alamat;}}]
              },
              telepon_kontak: {
                title: 'Telepon Kontak',
                fields: [{name:'telepon_kontak', label:'Nomor telepon', type:'text', maxlength:30, value:function(){return lpData.telepon_kontak;}}]
              },
              email_kontak: {
                title: 'Email Kontak',
                fields: [{name:'email_kontak', label:'Alamat email', type:'email', maxlength:255, value:function(){return lpData.email_kontak;}}]
              },
              website: {
                title: 'Website',
                fields: [{name:'website', label:'URL website', type:'url', maxlength:255, placeholder:'https://...', value:function(){return lpData.website;}}]
              },
              sosial_media: {
                title: 'Sosial Media', deletable: true,
                fields: [
                  {name:'platform', label:'Platform', type:'select', options:SOSIAL_PLATFORMS, value:function(idx){ var s=lpData.sosial_media[idx]; return s ? s.platform : SOSIAL_PLATFORMS[0].value; }},
                  {name:'label', label:'Label tampilan', type:'text', maxlength:100, value:function(idx){ var s=lpData.sosial_media[idx]; return s ? s.label : ''; }},
                  {name:'url', label:'URL', type:'url', maxlength:500, placeholder:'https://...', value:function(idx){ var s=lpData.sosial_media[idx]; return s ? s.url : ''; }}
                ]
              }
            };

            // ---------- render preview dari lpData ----------
            function setText(id, val, emptyLabel){
              var el = document.getElementById(id);
              if(!el) return;
              var hasVal = val && String(val).trim() !== '';
              el.textContent = hasVal ? val : (emptyLabel || el.dataset.lpEmpty || '');
              el.style.opacity = hasVal ? '1' : '.45';
              el.style.fontStyle = hasVal ? 'normal' : 'italic';
            }

            function renderAll(){
              document.getElementById('lpPvLogo').src = lpData.logo_url;
              setText('lpPvBrandName', lpData.brand.name, 'SIBER');
              document.getElementById('lpPvBrandAccent').textContent = lpData.brand.accent || 'AD';
              setText('lpPvBrandTagline', lpData.brand.tagline);

              var heroEl = document.getElementById('lpPreviewHero');
              heroEl.style.backgroundImage = lpData.hero_image_url
                ? 'linear-gradient(160deg, color-mix(in srgb, var(--panel-2) 85%, transparent), color-mix(in srgb, var(--bg-deep) 75%, transparent)), url(' + lpData.hero_image_url + ')'
                : '';

              setText('lpPvEyebrow', lpData.hero_eyebrow, 'PUSSIBERAD // SISTEM PENDUKUNG OPERASIONAL');
              setText('lpPvJudulAwal', lpData.hero_judul_awal, 'SIBER');
              setText('lpPvJudulAksen', lpData.hero_judul_aksen, 'AD');
              setText('lpPvSubjudul', lpData.hero_subjudul);
              setText('lpPvDeskripsi', lpData.hero_deskripsi);
              setText('lpPvTentang', lpData.tentang_deskripsi);
              setText('lpPvMotoJudul', lpData.tentang_moto_judul);
              setText('lpPvMoto', lpData.tentang_moto_deskripsi);
              setText('lpPvAlamat', lpData.alamat);
              setText('lpPvTelepon', lpData.telepon_kontak);
              setText('lpPvEmail', lpData.email_kontak);
              setText('lpPvWebsite', lpData.website);

              renderFitur();
              renderSosial();
            }

            function renderFitur(){
              var wrap = document.getElementById('lpPvFitur');
              wrap.innerHTML = '';
              lpData.fitur.forEach(function(f, i){
                var card = document.createElement('button');
                card.type = 'button';
                card.className = 'lp-feature-card lp-hotspot';
                card.setAttribute('data-editable', 'fitur');
                card.setAttribute('data-index', i);
                card.title = 'Klik untuk ubah kartu fitur ini';
                card.innerHTML = '<b></b><span></span><span class="lp-hotspot-pencil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>';
                card.querySelector('b').textContent = f.judul || ('Judul fitur ' + (i + 1));
                card.querySelector('span').textContent = f.deskripsi || '';
                wrap.appendChild(card);
              });
            }

            function renderSosial(){
              var wrap = document.getElementById('lpPvSosial');
              wrap.innerHTML = '';
              lpData.sosial_media.forEach(function(s, i){
                var chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'lp-sosial-chip lp-hotspot';
                chip.setAttribute('data-editable', 'sosial_media');
                chip.setAttribute('data-index', i);
                chip.title = 'Klik untuk ubah/hapus';
                chip.innerHTML = (SOSIAL_ICONS[s.platform] || '') + '<span></span>';
                chip.querySelector('span').textContent = s.label || s.platform;
                wrap.appendChild(chip);
              });
            }

            renderAll();

            // ---------- modal ----------
            var overlay = document.getElementById('lpModalOverlay');
            var modalTitle = document.getElementById('lpModalTitle');
            var modalDesc = document.getElementById('lpModalDesc');
            var modalBody = document.getElementById('lpModalBody');
            var modalError = document.getElementById('lpModalError');
            var modalSave = document.getElementById('lpModalSave');
            var modalDelete = document.getElementById('lpModalDelete');
            var modalClose = document.getElementById('lpModalClose');
            var modalCancel = document.getElementById('lpModalCancel');

            var currentKey = null, currentIndex = null, currentConfig = null;

            function setModalError(msg){
              modalError.textContent = msg || '';
              modalError.classList.toggle('show', !!msg);
            }

            function buildInput(field, idx){
              var wrap = document.createElement('div');
              var currentVal = field.value ? field.value(idx) : '';
              var label = document.createElement('label');
              label.textContent = field.label;
              label.setAttribute('for', 'lpField_' + field.name);
              wrap.appendChild(label);

              var input;
              if(field.type === 'textarea'){
                input = document.createElement('textarea');
                input.rows = 4;
                input.value = currentVal || '';
              } else if(field.type === 'select'){
                input = document.createElement('select');
                field.options.forEach(function(opt){
                  var o = document.createElement('option');
                  o.value = opt.value; o.textContent = opt.label;
                  if(opt.value === currentVal) o.selected = true;
                  input.appendChild(o);
                });
              } else if(field.type === 'file'){
                input = document.createElement('input');
                input.type = 'file';
                if(field.accept) input.accept = field.accept;
                var currentSrc = field.showCurrent ? field.showCurrent() : null;
                if(currentSrc){
                  var img = document.createElement('img');
                  img.src = currentSrc; img.className = 'lp-current-image'; img.alt = 'Saat ini';
                  wrap.appendChild(input);
                  wrap.appendChild(img);
                  input.id = 'lpField_' + field.name;
                  input.name = field.name;
                  return wrap;
                }
              } else {
                input = document.createElement('input');
                input.type = field.type || 'text';
                input.value = currentVal || '';
                if(field.placeholder) input.placeholder = field.placeholder;
              }
              input.id = 'lpField_' + field.name;
              input.name = field.name;
              if(field.maxlength) input.maxLength = field.maxlength;
              wrap.appendChild(input);
              return wrap;
            }

            function openModalFor(key, index){
              var config = FIELD_CONFIGS[key];
              if(!config) return;
              currentKey = key; currentIndex = (index === undefined || index === null || index === 'new') ? null : parseInt(index, 10);
              currentConfig = config;

              modalTitle.textContent = config.title;
              modalDesc.textContent = config.desc || '';
              modalDesc.style.display = config.desc ? 'block' : 'none';
              modalBody.innerHTML = '';
              config.fields.forEach(function(field){
                modalBody.appendChild(buildInput(field, currentIndex));
              });
              setModalError('');

              var isNewSosial = key === 'sosial_media' && (index === 'new' || index === undefined || index === null);
              modalDelete.hidden = !(config.deletable && !isNewSosial);
              if(isNewSosial) modalTitle.textContent = 'Tambah Sosial Media';

              overlay.classList.add('open');
              overlay.setAttribute('aria-hidden', 'false');
            }

            function closeModal(){
              overlay.classList.remove('open');
              overlay.setAttribute('aria-hidden', 'true');
              currentKey = null; currentIndex = null; currentConfig = null;
            }

            preview.addEventListener('click', function(e){
              var hotspot = e.target.closest('[data-editable]');
              if(!hotspot) return;
              e.preventDefault();
              openModalFor(hotspot.getAttribute('data-editable'), hotspot.getAttribute('data-index'));
            });
            modalClose.addEventListener('click', closeModal);
            modalCancel.addEventListener('click', closeModal);
            overlay.addEventListener('click', function(e){ if(e.target === overlay) closeModal(); });
            document.addEventListener('keydown', function(e){ if(e.key === 'Escape' && overlay.classList.contains('open')) closeModal(); });

            var toast = document.getElementById('lpToast');
            var toastTimer = null;
            function showToast(msg){
              toast.textContent = msg;
              toast.classList.add('show');
              clearTimeout(toastTimer);
              toastTimer = setTimeout(function(){ toast.classList.remove('show'); }, 2200);
            }

            function applyResultToData(key, index, data){
              if(key === 'logo'){ lpData.logo_url = data.logo_url; }
              else if(key === 'hero_image'){ lpData.hero_image_url = data.hero_image_url; }
              else if(key === 'brand'){ lpData.brand = data.brand; }
              else if(key === 'fitur'){ lpData.fitur[data.index] = {judul:data.judul, deskripsi:data.deskripsi}; }
              else if(key === 'tentang_moto'){ lpData.tentang_moto_judul = data.tentang_moto_judul; lpData.tentang_moto_deskripsi = data.tentang_moto_deskripsi; }
              else if(key === 'hero_judul'){ lpData.hero_judul_awal = data.hero_judul_awal; lpData.hero_judul_aksen = data.hero_judul_aksen; }
              else if(key === 'sosial_media'){ lpData.sosial_media = data.sosial_media; }
              else { Object.keys(data).forEach(function(k){ if(k in lpData) lpData[k] = data[k]; }); }
            }

            function handleSessionExpired(){
              setModalError('Sesi verifikasi berakhir. Halaman akan dimuat ulang, silakan verifikasi ulang untuk melanjutkan.');
              setTimeout(function(){ window.location.reload(); }, 1600);
            }

            function submitField(action){
              if(!currentKey) return;
              modalSave.disabled = true;
              setModalError('');

              var formData = new FormData();
              formData.append('_method', 'PATCH');
              formData.append('_token', csrfToken);
              formData.append('field', currentKey);
              if(currentIndex !== null) formData.append('index', currentIndex);
              if(action === 'delete') formData.append('action', 'delete');

              if(action !== 'delete'){
                currentConfig.fields.forEach(function(field){
                  var el = document.getElementById('lpField_' + field.name);
                  if(!el) return;
                  if(field.type === 'file'){
                    if(el.files && el.files[0]) formData.append(field.name, el.files[0]);
                  } else {
                    formData.append(field.name, el.value);
                  }
                });
              }

              fetch(updateFieldUrl, {
                method: 'POST',
                headers: {'Accept':'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: formData
              }).then(function(r){
                return r.json().catch(function(){ return {message:'Respons server tidak valid.'}; }).then(function(data){
                  if(r.status === 440){ handleSessionExpired(); throw new Error('session_expired'); }
                  if(!r.ok){
                    if(data.errors){
                      var firstMsg = Object.values(data.errors)[0];
                      throw new Error(Array.isArray(firstMsg) ? firstMsg[0] : String(firstMsg));
                    }
                    throw new Error(data.message || 'Gagal menyimpan.');
                  }
                  return data;
                });
              }).then(function(data){
                if(action === 'delete'){
                  lpData.sosial_media = data.sosial_media;
                } else {
                  applyResultToData(currentKey, currentIndex, data);
                }
                renderAll();
                closeModal();
                showToast(action === 'delete' ? 'Item dihapus.' : 'Perubahan tersimpan.');
              }).catch(function(err){
                if(err.message !== 'session_expired') setModalError(err.message || 'Gagal menyimpan.');
              }).finally(function(){
                modalSave.disabled = false;
              });
            }

            modalSave.addEventListener('click', function(){ submitField('save'); });
            modalDelete.addEventListener('click', function(){
              if(!window.confirm('Hapus item ini dari landing page?')) return;
              submitField('delete');
            });
          })();
        </script>
      </section>

      {{-- ===== PERMINTAAN RESET PASSWORD ===== --}}
      <section class="tab-panel" data-tab-panel="reset-password">
        <div class="panel">
          <div class="panel-head"><div><h2>Permintaan Ganti Password</h2><p>Permintaan ganti kata sandi yang dikirim pengguna lewat menu "Pengaturan Akun".</p></div></div>
          <div class="table-toolbar">
            <div class="table-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
              <input type="text" class="table-search" data-table-search="tblResetPassword" placeholder="Cari nama atau satuan...">
            </div>
            <select class="table-filter" id="tblResetPasswordSort" data-table-sort="tblResetPassword">
              <option value="terbaru">Terbaru</option>
              <option value="terlama">Terlama</option>
            </select>
            <span class="table-filter-count" data-table-count="tblResetPassword"></span>
          </div>
          <div class="tbl-wrap" data-row-limit="5">
            <table class="dtbl" id="tblResetPassword">
              <colgroup><col style="width:26%"><col style="width:34%"><col style="width:20%"><col style="width:20%"></colgroup>
              <thead><tr><th>Pengaju</th><th>Catatan</th><th>Tanggal</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($permintaanResetPassword as $r)
                <tr data-created="{{ $r->created_at->timestamp }}" data-search-value="{{ strtolower(($r->user->name ?? '').' '.($r->user->satuan->nama ?? '').' '.($r->user->satuan->kode ?? '')) }}">
                  <td><div class="subject">{{ $r->user->name ?? '-' }}</div></td>
                  <td style="color:var(--text-muted);">{{ trim((string) $r->catatan) !== '' ? $r->catatan : '-' }}</td>
                  <td><div class="request-deadline"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>{{ $r->created_at->translatedFormat('d M Y H:i') }}</div></td>
                  <td>
                    @if($r->status === \App\Models\PermintaanResetPassword::STATUS_MENUNGGU)
                    <div class="btn-row">
                      <button class="table-action-btn edit" type="button" onclick="bukaSetujuiResetPassword(this)" data-id="{{ $r->id }}" data-nama="{{ e($r->user->name ?? '-') }}">Setujui</button>
                      <button class="table-action-btn danger" type="button" onclick="bukaTolakResetPassword(this)" data-id="{{ $r->id }}" data-nama="{{ e($r->user->name ?? '-') }}">Tolak</button>
                    </div>
                    @else
                      <span style="font-size:11.5px;font-weight:700;color:{{ $r->status === \App\Models\PermintaanResetPassword::STATUS_DISETUJUI ? 'var(--success-bright)' : 'var(--red)' }};">{{ $r->status }}</span>
                      <div style="font-size:10.5px;color:var(--text-dim);margin-top:2px;">oleh {{ $r->diprosesOleh->name ?? '-' }}</div>
                    @endif
                  </td>
                </tr>
                @empty
                <tr><td colspan="4"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada permintaan ganti password</div></div></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== REKAP LAPORAN LINTAS SATLAK ===== --}}
      <section class="tab-panel" data-tab-panel="rekap-laporan">
        <div class="section-head panel">
          <h2>Ringkasan Data</h2>
          <p>Ringkasan jumlah &amp; status laporan tiap satuan dalam satu tampilan.</p>
        </div>

        <div class="chart-box">
          <div class="chart-mini">
            <div class="chart-mini-head chart-mini-head-rekap">
              <div class="chart-mini-head-row">
                <h4>Total Laporan per Satuan</h4>
                <button type="button" class="btn-toggle-detail" id="btnToggleDetailSatuan" aria-expanded="false" aria-controls="panelDetailPerSatuan">
                  <span id="btnToggleDetailSatuanLabel">Lihat Detail per Satuan</span>
                  <svg class="chevron" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
              </div>
              <p>Semua satuan pengirim laporan (Unsur Pelayanan, Unsur Pembantu Pimpinan, Direktorat, Satlak, dan 21 satuan Kasansi) tersusun dari atas ke bawah per kategori -- warna batang menunjukkan kategorinya. Makin panjang batang, makin banyak laporan yang sudah dikirim satuan itu. Scroll ke bawah untuk lihat semua satuan.</p>
            </div>
            <div class="chart-wrap" style="overflow-y:auto;overflow-x:auto;max-height:640px;">
              <div id="chartRekapLaporanWrap" style="position:relative;width:100%;">
                <canvas id="chartRekapLaporan"></canvas>
              </div>
            </div>
            <div class="chart-legend" id="chartRekapLaporanLegend"></div>
          </div>
        </div>

        <div class="panel" id="panelDetailPerSatuan" hidden>
          <div class="panel-head">
            <div><h3>Detail per Satuan</h3></div>
            <button type="button" class="btn-toggle-detail" id="btnCloseDetailSatuan" aria-expanded="true" aria-controls="panelDetailPerSatuan" title="Tutup detail dan kembali ke grafik">
              <span>Tutup Detail</span>
              <svg class="chevron" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
          </div>
          <div class="table-toolbar">
            <div class="table-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
              <input type="text" class="table-search" data-table-search="tblRekapSatuan" placeholder="Cari nama satuan...">
            </div>
            <select class="table-filter" data-table-filter="tblRekapSatuan">
              <option value="">Semua Kategori</option>
              <option value="{{ \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN }}">Unsur Pelayanan</option>
              <option value="{{ \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN }}">Unsur Pembantu Pimpinan</option>
              <option value="{{ \App\Models\Satuan::KATEGORI_DIREKTORAT }}">Direktorat</option>
              <option value="{{ \App\Models\Satuan::KATEGORI_SATLAK }}">Satlak</option>
              <option value="{{ \App\Models\Satuan::KATEGORI_KOTAMA }}">Kasansi</option>
            </select>
            <span class="table-filter-count" data-table-count="tblRekapSatuan"></span>
          </div>
          <div class="tbl-wrap tbl-scroll" style="max-height:420px;">
            <table class="dtbl" id="tblRekapSatuan">
              <colgroup><col style="width:30%"><col style="width:14%"><col style="width:14%"><col style="width:14%"><col style="width:14%"><col style="width:14%"></colgroup>
              <thead><tr><th>Satuan</th><th style="text-align:center;">Total Laporan</th><th style="text-align:center;">Disetujui</th><th style="text-align:center;">Ditolak</th><th style="text-align:center;">Terlambat</th><th style="text-align:center;">Dibatalkan</th></tr></thead>
              <tbody>
                @forelse($rekapLaporanSatuan as $s)
                <tr data-filter-value="{{ $s->kategori }}" data-search-value="{{ strtolower($s->nama.' '.$s->kode) }}">
                  <td>{{ $s->nama }}</td>
                  <td style="text-align:center;">{{ $s->total_laporan }}</td>
                  <td style="text-align:center;"><span class="badge-status ok">{{ $s->laporan_disetujui }}</span></td>
                  <td style="text-align:center;"><span class="badge-status bad">{{ $s->laporan_ditolak }}</span></td>
                  <td style="text-align:center;"><span class="badge-status late">{{ $s->laporan_terlambat }}</span></td>
                  <td style="text-align:center;"><span class="badge-status cancelled">{{ $s->laporan_dibatalkan }}</span></td>
                </tr>
                @empty
                <tr class="table-empty-row"><td colspan="6"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada data satuan</div></div></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== SESI LOGIN AKTIF ===== --}}
      <section class="tab-panel" data-tab-panel="sesi-aktif">

        <div class="panel">
          <div class="panel-head"><div><h2>Pengguna Aktif</h2><p>Pantau perangkat/browser yang sedang login, dan paksa logout kalau perlu.</p></div></div>
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Pengguna</th><th>IP Address</th><th>Perangkat / Browser</th><th>Terakhir Aktif</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($sesiAktif as $s)
                <tr>
                  <td>
                    {{ $s->user_name ?? 'Tamu (belum login)' }}
                    @if($s->id === $sesiSayaId)
                      <span class="badge">Sesi Anda</span>
                    @endif
                  </td>
                  <td>{{ $s->ip_address ?? '-' }}</td>
                  <td style="color:var(--text-muted);max-width:260px;">{{ \Illuminate\Support\Str::limit($s->user_agent, 60) }}</td>
                  <td>{{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</td>
                  <td>
                    @if($s->id !== $sesiSayaId)
                    <button class="btn btn-ghost-red btn-sm" type="button" onclick="bukaPaksaLogout(this)"
                      data-action="{{ route('admin.sessions.destroy', $s->id) }}"
                      data-nama="{{ $s->user_name ?? 'Tamu (belum login)' }}">Paksa Logout</button>
                    @else
                      <span style="font-size:11.5px;color:var(--text-dim);">—</span>
                    @endif
                  </td>
                </tr>
                @empty
                <tr class="table-empty-row"><td colspan="5"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Tidak ada sesi aktif</div></div></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <div class="confirm-overlay" id="paksaLogoutOverlay">
        <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="paksaLogoutTitle">
          <div class="confirm-icon">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
          </div>
          <h3 id="paksaLogoutTitle">Paksa Logout Sesi Ini?</h3>
          <p>Sesi milik <strong id="paksaLogoutNama">ini</strong> akan langsung diakhiri dan perlu login ulang.</p>
          <form id="formPaksaLogout" method="POST" action="">
            @csrf @method('DELETE')
            <div class="confirm-actions">
              <button type="button" class="btn" id="paksaLogoutBatal">Batal</button>
              <button type="submit" class="btn btn-ghost-red">Ya</button>
            </div>
          </form>
        </div>
      </div>
      <script>
        window.bukaPaksaLogout = function (btn) {
          document.getElementById('formPaksaLogout').action = btn.dataset.action;
          document.getElementById('paksaLogoutNama').textContent = btn.dataset.nama || 'ini';
          document.getElementById('paksaLogoutOverlay')?.classList.add('open');
        };
        document.getElementById('paksaLogoutBatal')?.addEventListener('click', () => document.getElementById('paksaLogoutOverlay')?.classList.remove('open'));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') document.getElementById('paksaLogoutOverlay')?.classList.remove('open'); });
      </script>

    </div>

      {{-- ===== ATUR FOTO PROFIL (geser + zoom sebelum upload) ===== --}}
      <div class="crop-modal" id="aturFotoOverlay">
        <div class="crop-modal-card">
          <div class="crop-modal-head">
            <h3>Atur Foto Profil</h3>
          </div>
          <div class="crop-stage" id="cropStage">
            <img id="cropImage" alt="Pratinjau foto profil" draggable="false">
            <div class="crop-mask"></div>
          </div>
          <div class="crop-zoom-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="6"></circle><path d="m21 21-4.35-4.35"></path></svg>
            <input type="range" id="cropZoomRange" min="100" max="300" value="100" step="1" aria-label="Perbesar foto">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path><path d="M11 8v6"></path><path d="M8 11h6"></path></svg>
          </div>
          <p class="crop-modal-hint">Geser foto buat atur posisi, geser slider buat zoom.</p>
          <div class="crop-modal-actions">
            <button type="button" class="btn" id="aturFotoBatal">Batal</button>
            <button type="button" class="btn btn-primary" id="aturFotoSimpan">Ganti Foto</button>
          </div>
        </div>
      </div>

      {{-- ===== KONFIRMASI HAPUS FOTO PROFIL ===== --}}
      <div class="confirm-overlay" id="hapusFotoOverlay">
        <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="hapusFotoTitle">
          <div class="confirm-icon">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>
          </div>
          <h3 id="hapusFotoTitle">Hapus Foto Profil?</h3>
          <p>Foto profil kamu akan dihapus dan kembali menampilkan inisial nama.</p>
          <form id="formHapusFoto" method="POST" action="{{ route('profil-foto.destroy') }}">
            @csrf @method('DELETE')
            <div class="confirm-actions">
              <button type="button" class="btn" id="hapusFotoBatal">Batal</button>
              <button type="submit" class="btn btn-ghost-red">Ya, Hapus</button>
            </div>
          </form>
        </div>
      </div>

      {{-- ===== KONFIRMASI SETUJUI/TOLAK PERMINTAAN GANTI PASSWORD ===== --}}
      <div class="confirm-overlay" id="setujuiResetPasswordOverlay">
        <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="setujuiResetPasswordTitle">
          <div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><circle cx="12" cy="12" r="9"></circle><path d="M9 12l2 2 4-4"></path></svg>
          </div>
          <h3 id="setujuiResetPasswordTitle">Setujui Permintaan Ganti Password?</h3>
          <p>Password baru untuk <strong id="setujuiResetPasswordNama">akun ini</strong> akan langsung aktif setelah disetujui.</p>
          <form id="formSetujuiResetPassword" method="POST" action="">
            @csrf @method('PATCH')
            <div class="confirm-actions">
              <button type="button" class="btn" id="setujuiResetPasswordBatal">Batal</button>
              <button type="submit" class="btn btn-primary">Ya, Setujui</button>
            </div>
          </form>
        </div>
      </div>
      <div class="confirm-overlay" id="tolakResetPasswordOverlay">
        <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="tolakResetPasswordTitle">
          <div class="confirm-icon">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><circle cx="12" cy="12" r="9"></circle><path d="M15 9l-6 6"></path><path d="M9 9l6 6"></path></svg>
          </div>
          <h3 id="tolakResetPasswordTitle">Tolak Permintaan Ganti Password?</h3>
          <p>Password akun <strong id="tolakResetPasswordNama">ini</strong> tidak akan berubah, dan pengaju akan diberi tahu.</p>
          <form id="formTolakResetPassword" method="POST" action="">
            @csrf @method('PATCH')
            <div class="confirm-actions">
              <button type="button" class="btn" id="tolakResetPasswordBatal">Batal</button>
              <button type="submit" class="btn btn-ghost-red">Ya, Tolak</button>
            </div>
          </form>
        </div>
      </div>
      <script>
        function bukaSetujuiResetPassword(button) {
          var id = button.dataset.id, nama = button.dataset.nama;
          document.getElementById('formSetujuiResetPassword').action = '{{ url('/admin/permintaan-reset-password') }}/' + id + '/setujui';
          document.getElementById('setujuiResetPasswordNama').textContent = nama;
          document.getElementById('setujuiResetPasswordOverlay')?.classList.add('open');
        }
        function bukaTolakResetPassword(button) {
          var id = button.dataset.id, nama = button.dataset.nama;
          document.getElementById('formTolakResetPassword').action = '{{ url('/admin/permintaan-reset-password') }}/' + id + '/tolak';
          document.getElementById('tolakResetPasswordNama').textContent = nama;
          document.getElementById('tolakResetPasswordOverlay')?.classList.add('open');
        }
        document.getElementById('setujuiResetPasswordBatal')?.addEventListener('click', () => document.getElementById('setujuiResetPasswordOverlay')?.classList.remove('open'));
        document.getElementById('tolakResetPasswordBatal')?.addEventListener('click', () => document.getElementById('tolakResetPasswordOverlay')?.classList.remove('open'));
        document.addEventListener('keydown', e => {
          if (e.key !== 'Escape') return;
          document.getElementById('setujuiResetPasswordOverlay')?.classList.remove('open');
          document.getElementById('tolakResetPasswordOverlay')?.classList.remove('open');
        });

        document.getElementById('tblResetPasswordSort')?.addEventListener('change', function () {
          var table = document.getElementById('tblResetPassword');
          var tbody = table?.querySelector('tbody');
          if (!tbody) return;
          var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-created]'));
          var terlama = this.value === 'terlama';
          rows.sort(function (a, b) {
            var diff = (+a.dataset.created) - (+b.dataset.created);
            return terlama ? diff : -diff;
          });
          rows.forEach(function (row) { tbody.appendChild(row); });
          if (window.terapkanRowLimitWrap) window.terapkanRowLimitWrap(table.closest('.tbl-wrap'));
        });
      </script>

      <script>
      (function () {
        var menuBtn = document.getElementById('profileMenuBtn');
        var dropdown = document.getElementById('profileDropdown');
        var wrapper = document.getElementById('profileMenu');
        var openPengaturanBtn = document.getElementById('openPengaturanBtn');
        var openBantuanBtn = document.getElementById('openBantuanBtn');
        if (!menuBtn || !dropdown || !wrapper) return;

        function closeMenu() {
          dropdown.classList.remove('open');
          menuBtn.classList.remove('open');
          menuBtn.setAttribute('aria-expanded', 'false');
        }

        function openMenu() {
          // Tutup dropdown notifikasi kalau lagi kebuka, biar cuma satu yang tampil
          var notifDropdown = document.getElementById('notifDropdown');
          var notifBtnEl = document.getElementById('notifBtn');
          if (notifDropdown && notifDropdown.classList.contains('open')) {
            notifDropdown.classList.remove('open');
            if (notifBtnEl) {
              notifBtnEl.classList.remove('open');
              notifBtnEl.setAttribute('aria-expanded', 'false');
            }
          }
          dropdown.classList.add('open');
          menuBtn.classList.add('open');
          menuBtn.setAttribute('aria-expanded', 'true');
        }

        menuBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          if (dropdown.classList.contains('open')) {
            closeMenu();
          } else {
            openMenu();
          }
        });

        // Item di dropdown kecil membuka popup besar di tengah layar
        // (fungsi openProfileModal didefinisikan di script popup di bawah)
        if (openPengaturanBtn) {
          openPengaturanBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeMenu();
            if (window.openProfileModal) window.openProfileModal('profileSettingsView');
          });
        }
        if (openBantuanBtn) {
          openBantuanBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeMenu();
            if (window.openProfileModal) window.openProfileModal('profileHelpView');
          });
        }

        document.addEventListener('click', function (e) {
          if (!wrapper.contains(e.target)) closeMenu();
        });

        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') closeMenu();
        });
      })();
      </script>

      <script>
      (function () {
        var overlay = document.getElementById('profileModalOverlay');
        var card = document.getElementById('profileModalCard');
        var closeBtn = document.getElementById('profileModalCloseBtn');
        var views = document.querySelectorAll('#profileModalOverlay .profile-dropdown-view');
        if (!overlay || !card) return;

        function showView(id) {
          views.forEach(function (v) {
            v.style.display = (v.id === id) ? 'block' : 'none';
          });
        }

        window.openProfileModal = function (viewId) {
          showView(viewId);
          overlay.classList.add('open');
          document.body.style.overflow = 'hidden';
        };

        function closeModal() {
          overlay.classList.remove('open');
          document.body.style.overflow = '';
        }

        if (closeBtn) {
          closeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeModal();
          });
        }

        // Klik di dalam kartu popup tidak boleh menutupnya
        card.addEventListener('click', function (e) {
          e.stopPropagation();
        });

        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
        });
      })();
      </script>

      <script>
      (function () {
        var notifBtn = document.getElementById('notifBtn');
        var dropdown = document.getElementById('notifDropdown');
        var wrapper = document.getElementById('notifMenu');
        if (!notifBtn || !dropdown || !wrapper) return;
        // partials/notification-controls.blade.php ikut ter-include lewat
        // pengumuman-banner.blade.php, tapi baru jalan pas event
        // DOMContentLoaded (jadi SETELAH script inline ini, yang jalan
        // langsung waktu parser sampai sini). Dia isi dropdown ini dengan
        // notifikasi asli (tetap jalan, tidak digguard) tapi toggle klik-nya
        // sendiri digated lewat dataset.notifBound. Klaim flag itu di sini
        // duluan supaya nanti dia skip bind toggle klik-nya sendiri —
        // kalau kedua-duanya bind, satu klik langsung kebuka-lalu-tertutup
        // lagi (dua listener saling membatalkan).
        if (notifBtn.dataset.notifBound) return;
        notifBtn.dataset.notifBound = '1';

        function closeNotif() {
          dropdown.classList.remove('open');
          notifBtn.classList.remove('open');
          notifBtn.setAttribute('aria-expanded', 'false');
        }

        function openNotif() {
          // Tutup dropdown profil kalau lagi kebuka, biar cuma satu yang tampil
          var profileDropdown = document.getElementById('profileDropdown');
          var profileMenuBtn = document.getElementById('profileMenuBtn');
          if (profileDropdown && profileDropdown.classList.contains('open')) {
            profileDropdown.classList.remove('open');
            if (profileMenuBtn) {
              profileMenuBtn.classList.remove('open');
              profileMenuBtn.setAttribute('aria-expanded', 'false');
            }
          }
          dropdown.classList.add('open');
          notifBtn.classList.add('open');
          notifBtn.setAttribute('aria-expanded', 'true');
        }

        notifBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          if (dropdown.classList.contains('open')) {
            closeNotif();
          } else {
            openNotif();
          }
        });

        document.addEventListener('click', function (e) {
          if (!wrapper.contains(e.target)) closeNotif();
        });

        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') closeNotif();
        });
      })();
      </script>

  </main>

  {{-- ===== KONFIRMASI KELUAR ===== --}}
  <div class="confirm-overlay" id="logoutConfirmOverlay">
    <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="logoutConfirmTitle">
      <div class="confirm-icon">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
      </div>
      <h3 id="logoutConfirmTitle">Keluar dari akun?</h3>
      <p>Sesi kamu akan diakhiri dan kamu perlu login kembali untuk mengakses SIBERAD.</p>
      <div class="confirm-actions">
        <button type="button" class="btn" id="logoutCancelBtn">Batal</button>
        <button type="button" class="btn btn-ghost-red" id="logoutConfirmBtn">Ya, Keluar</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var overlay = document.getElementById('logoutConfirmOverlay');
  var cancelBtn = document.getElementById('logoutCancelBtn');
  var confirmBtn = document.getElementById('logoutConfirmBtn');
  var pendingForm = null;

  if (!overlay || !cancelBtn || !confirmBtn) return;

  function openConfirm(targetForm) {
    pendingForm = targetForm;
    overlay.classList.add('open');
  }
  function closeConfirm() {
    overlay.classList.remove('open');
    pendingForm = null;
  }

  document.querySelectorAll('.logout-form').forEach(function (logoutForm) {
    logoutForm.addEventListener('submit', function (e) {
      e.preventDefault();
      openConfirm(logoutForm);
    });
  });

  cancelBtn.addEventListener('click', closeConfirm);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && overlay.classList.contains('open')) closeConfirm();
  });
  confirmBtn.addEventListener('click', function () {
    if (!pendingForm) return;
    // Reset HANYA tab menu terakhir supaya login berikutnya selalu mulai dari
    // Dashboard (selaras dengan initLogoutConfirm() di
    // partials/global-shell-enhancements.blade.php). Jangan hapus SEMUA key
    // "siberad-*" -- itu juga menyimpan status buka/tutup tiap dropdown menu,
    // tema, dan status ciutkan sidebar, yang harus tetap seperti preferensi
    // terakhir user, bukan ikut ke-reset tiap logout.
    try {
      ['siberad-admin-active-tab', 'siberad-pimpinan-active-tab', 'siberad-role-active-tab'].forEach(function (k) {
        sessionStorage.removeItem(k);
      });
    } catch (e) {}
    pendingForm.submit();
  });
})();
</script>

  <script>
  (function () {
    if (typeof Chart === 'undefined') return;

    var root = getComputedStyle(document.documentElement);
    var cGold = root.getPropertyValue('--gold-bright').trim() || '#f3cd5c';
    var cGreen = root.getPropertyValue('--green-bright').trim() || '#3fc27d';
    var cAmber = root.getPropertyValue('--amber').trim() || '#e0a83a';
    var cRed = root.getPropertyValue('--red').trim() || '#c62828';
    var cMuted = root.getPropertyValue('--text-muted').trim() || '#9fb3a5';
    // Warna grid chart disamakan ke --border (bukan --border-soft) supaya
    // garis bantu sumbu kelihatan jelas di kedua tema -- sebelumnya pakai
    // rgba(255,255,255,.06) hardcode yang nyaris tak kelihatan di tema
    // terang (background putih vs garis putih transparan).
    var cGrid = root.getPropertyValue('--border').trim() || 'rgba(148,163,184,.35)';

    Chart.defaults.color = cMuted;
    Chart.defaults.font.family = "'JetBrains Mono', monospace";
    Chart.defaults.font.size = 11;

    var doughnutOptions = {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '62%',
      plugins: { legend: { display: false } }
    };

    function renderDoughnut(canvasId, labels, values, colors) {
      var el = document.getElementById(canvasId);
      if (!el) return;
      var chart = new Chart(el, {
        type: 'doughnut',
        data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderColor: 'transparent' }] },
        options: doughnutOptions
      });
      var legendBox = document.getElementById(canvasId + 'Legend');
      if (!legendBox) return;
      legendBox.innerHTML = '';
      labels.forEach(function (label, i) {
        var item = document.createElement('span');
        item.className = 'chart-legend-item';
        item.innerHTML = '<span class="chart-legend-dot" style="background:' + colors[i] + '"></span>' + label;
        item.addEventListener('click', function (e) {
          e.stopPropagation();
          chart.toggleDataVisibility(i);
          chart.update();
          item.classList.toggle('is-hidden', !chart.getDataVisibility(i));
        });
        legendBox.appendChild(item);
      });
    }

    // ===== Grafik 1: Pengguna per Kategori Satuan (warna literal — urutan
    // grup dari backend dijamin selalu Admin, Pimpinan, Unsur Pelayanan,
    // Unsur Pembantu Pimpinan, Direktorat, Satlak, Kotama lewat
    // Satuan::prioritasKategori(), bukan ikut urutan $semuaSatuan begitu
    // saja) =====
    var distribusiKategori = @json($distribusiPenggunaKategori);
    renderDoughnut(
      'chartKategoriSatuan',
      distribusiKategori.map(function (d) { return d.kategori; }),
      distribusiKategori.map(function (d) { return d.jumlah; }),
      [cGold, '#6366f1', '#0ea5e9', '#a855f7', '#22c55e', '#f59e0b', '#ec4899']
    );

    // ===== Grafik 2: Distribusi Status Laporan (data asli, warna tetap
    // hijau/merah/oren — bukan var(--green-bright) yang di-repurpose jadi
    // gold di dark mode) =====
    var statusLaporan = @json($statusLaporanSistem);
    renderDoughnut(
      'chartStatusLaporan',
      ['Disetujui', 'Ditolak', 'Terlambat', 'Dibatalkan'],
      [statusLaporan.disetujui, statusLaporan.ditolak, statusLaporan.terlambat, statusLaporan.dibatalkan],
      ['#22c55e', '#ef4444', '#ff6b6b', '#c1121f']
    );

    // ===== Grafik 3: Aktivitas 7 Hari Terakhir =====
    var aktivitasMingguan = @json($aktivitasTujuhHari);
    var elAktivitas = document.getElementById('chartAktivitasMingguan');
    if (elAktivitas) {
      var aktivitasCtx = elAktivitas.getContext('2d');
      var aktivitasGradient = aktivitasCtx.createLinearGradient(0, 0, 0, elAktivitas.height || 220);
      aktivitasGradient.addColorStop(0, 'rgba(99,102,241,.35)');
      aktivitasGradient.addColorStop(1, 'rgba(99,102,241,0)');
      new Chart(elAktivitas, {
        type: 'line',
        data: {
          labels: aktivitasMingguan.map(function (a) { return a.label; }),
          datasets: [{
            label: 'Aktivitas',
            data: aktivitasMingguan.map(function (a) { return a.jumlah; }),
            borderColor: '#6366f1',
            backgroundColor: aktivitasGradient,
            fill: true,
            tension: 0.35,
            pointBackgroundColor: '#6366f1',
            pointRadius: 3,
            pointHoverRadius: 5,
            borderWidth: 2.5
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: cGrid } }
          }
        }
      });
    }

    // ===== Grafik 4: Rekap Total Laporan per Satuan (termasuk 21 Kotama) =====
    // Horizontal bar (bukan vertikal) supaya dengan 35+ satuan, nama satuan
    // tetap kebaca utuh tanpa dirotasi/dipotong -- yang discroll cukup
    // sumbu vertikal (lebih wajar buat orang baru dibanding scroll ke
    // samping). Tiap batang diwarnai sesuai kategori satuannya (Unsur
    // Pelayanan / Unsur Pembantu Pimpinan / Direktorat / Satlak / Kotama)
    // pakai palet yang sama dengan doughnut "Pengguna per Kategori Satuan"
    // di atas, supaya konsisten dan orang baru langsung bisa menghubungkan
    // warna dengan kategori.
    var rekapSatuan = @json($rekapLaporanSatuan);
    var kategoriWarna = {
      unsur_pelayanan: '#0ea5e9',
      unsur_pembantu_pimpinan: '#a855f7',
      direktorat: '#22c55e',
      satlak: '#f59e0b',
      kotama: '#ec4899'
    };
    var kategoriLabel = {
      unsur_pelayanan: 'Unsur Pelayanan',
      unsur_pembantu_pimpinan: 'Unsur Pembantu Pimpinan',
      direktorat: 'Direktorat',
      satlak: 'Satlak',
      kotama: 'Kasansi'
    };
    var elRekap = document.getElementById('chartRekapLaporan');
    if (elRekap) {
      var innerEl = document.getElementById('chartRekapLaporanWrap');
      // Tinggi awal sekadar placeholder sebelum sesuaikanTinggiChartRekap()
      // (di bawah, setelah chart dibuat) menghitung ukuran final berdasarkan
      // sisa ruang viewport -- dibuat cukup besar biar Chart.js nggak
      // sempat kepepet render di 0px lalu "loncat" pas resize pertama.
      if (innerEl) innerEl.style.height = Math.max(rekapSatuan.length * 22, 320) + 'px';

      var warnaBar = rekapSatuan.map(function (s) { return kategoriWarna[s.kategori] || '#94a3b8'; });

      // Kalibrasi sumbu X: 1 laporan = 3mm panjang batang, tidak dibatasi
      // lebar layar. Batas atas sumbu X (xMaxTarget) dihitung dari total
      // laporan terbesar (dibulatkan ke atas kelipatan 10 + sedikit ruang
      // napas). Lebar kanvas aktual (innerEl) baru dihitung & dilebarkan
      // di sesuaikanLebarChartRekap() di bawah -- SENGAJA tidak dihitung
      // di sini, karena panel ini ada di dalam tab yang default
      // display:none ("rekap-laporan"), jadi lebar container = 0 selama
      // tab belum aktif. Kalau dihitung di sini, lebar kanvas bisa kekunci
      // ke angka kecil yang salah sebelum tab pernah dibuka.
      var MM_PER_LAPORAN = 3;
      var PX_PER_MM = 96 / 25.4;
      var pxPerLaporan = MM_PER_LAPORAN * PX_PER_MM;
      var maxTotalLaporan = rekapSatuan.reduce(function (m, s) { return Math.max(m, s.total_laporan || 0); }, 0);
      var xMaxTarget = Math.max(100, Math.ceil((maxTotalLaporan + 1) / 10) * 10);

      var chartRekapInstance = new Chart(elRekap, {
        type: 'bar',
        data: {
          labels: rekapSatuan.map(function (s) {
            // Horizontal punya lebih banyak ruang dari vertikal, tapi nama
            // yang sangat panjang tetap dipotong dikit biar margin kiri
            // nggak kebesaran -- nama lengkap tetap muncul di tooltip.
            var nama = (s.nama || s.kode).split('(')[0].trim();
            return nama.length > 30 ? nama.slice(0, 28) + '…' : nama;
          }),
          datasets: [{
            label: 'Total Laporan',
            data: rekapSatuan.map(function (s) { return s.total_laporan; }),
            backgroundColor: warnaBar,
            borderRadius: 4,
            maxBarThickness: 16
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                // Tampilkan nama lengkap + kategori satuan di tooltip meski label dipotong.
                title: function (items) {
                  var idx = items[0] && items[0].dataIndex;
                  var s = rekapSatuan[idx];
                  return s ? (s.nama || s.kode) : '';
                },
                afterTitle: function (items) {
                  var idx = items[0] && items[0].dataIndex;
                  var s = rekapSatuan[idx];
                  return s ? 'Kategori: ' + (kategoriLabel[s.kategori] || s.kategori) : '';
                },
                label: function (item) { return 'Total Laporan: ' + item.raw; }
              }
            }
          },
          scales: {
            x: { beginAtZero: true, max: xMaxTarget, ticks: { precision: 0 }, grid: { color: cGrid } },
            y: { grid: { display: false }, ticks: { font: { size: 10 } } }
          }
        }
      });

      // Legend statis warna kategori (bukan toggle seperti doughnut, karena
      // satu batang cuma punya 1 kategori -- klik legend nggak relevan di sini).
      var legendBox = document.getElementById('chartRekapLaporanLegend');
      if (legendBox) {
        legendBox.innerHTML = '';
        Object.keys(kategoriLabel).forEach(function (kunci) {
          if (!rekapSatuan.some(function (s) { return s.kategori === kunci; })) return;
          var item = document.createElement('span');
          item.className = 'chart-legend-item';
          item.innerHTML = '<span class="chart-legend-dot" style="background:' + kategoriWarna[kunci] + '"></span>' + kategoriLabel[kunci];
          legendBox.appendChild(item);
        });
      }

      // ===== Sizing dinamis: isi penuh sisa tinggi viewport, tanpa celah
      // kosong yang canggung di bawah halaman =====
      // Elemen ".chart-wrap" ini juga kena aturan CSS ".chart-mini .chart-wrap"
      // (dipakai chart mini lain di dashboard) yang mematok height:178px --
      // makanya "height" di sini WAJIB di-set langsung lewat inline style
      // (bukan cuma max-height), karena inline style pasti menang atas
      // aturan class manapun. Kalau ruang tersedia cukup buat semua satuan,
      // baris dibuat lega mengisi penuh sisa layar (tanpa scroll, tanpa
      // celah kosong di bawah). Kalau tidak cukup, tinggi baris dijaga di
      // batas minimum biar batang tidak gepeng -- sisanya baru discroll,
      // dan area scroll itu sendiri tetap mengisi penuh sisa layar (jadi
      // scrollbar-nya juga sampai ke bawah, bukan berhenti di tengah).
      var chartWrapRekap = elRekap.closest('.chart-wrap');
      var tabPanelRekap = document.querySelector('[data-tab-panel="rekap-laporan"]');
      var MIN_ROW_HEIGHT_PX = 20;
      var MIN_WRAP_HEIGHT_PX = 320;
      var BOTTOM_BREATHING_ROOM_PX = 32;

      function sesuaikanTinggiChartRekap() {
        if (!chartWrapRekap || !innerEl || !tabPanelRekap || !tabPanelRekap.classList.contains('active')) return;
        var rectAtas = chartWrapRekap.getBoundingClientRect().top;
        var legendH = legendBox ? legendBox.offsetHeight : 0;
        var sisa = window.innerHeight - rectAtas - legendH - BOTTOM_BREATHING_ROOM_PX;
        var tersedia = Math.max(sisa, MIN_WRAP_HEIGHT_PX);
        var tinggiMinimalSemuaBaris = rekapSatuan.length * MIN_ROW_HEIGHT_PX;

        chartWrapRekap.style.height = tersedia + 'px';
        chartWrapRekap.style.maxHeight = tersedia + 'px';
        innerEl.style.height = Math.max(tinggiMinimalSemuaBaris, tersedia) + 'px';

        sesuaikanLebarChartRekap();
      }

      // Lebar kanvas (bukan tinggi): dipanggil dari sesuaikanTinggiChartRekap()
      // di atas supaya ikut kena semua pemicu yang sama (tab "Rekap Laporan"
      // baru dibuka, window di-resize, halaman baru dimuat) -- termasuk saat
      // tab ini pertama kali diaktifkan, yang penting karena sebelum itu
      // panel masih display:none sehingga lebar tidak bisa diukur dengan benar.
      // Direset ke 100% dulu tiap kali supaya pengukuran "overhead" (ruang
      // yang dipakai label sumbu Y) selalu berdasarkan lebar panel yang
      // sebenarnya saat ini, baru dilebarkan kalau ternyata kurang untuk
      // menjaga rasio 3mm per laporan -- sisanya discroll lewat chart-wrap.
      function sesuaikanLebarChartRekap() {
        if (!chartRekapInstance) return;
        innerEl.style.width = '100%';
        chartRekapInstance.resize();
        var overhead = chartRekapInstance.width - (chartRekapInstance.chartArea ? chartRekapInstance.chartArea.width : chartRekapInstance.width);
        var lebarDibutuhkan = Math.ceil(xMaxTarget * pxPerLaporan + overhead);
        var lebarPanelSaatIni = innerEl.getBoundingClientRect().width;
        if (lebarDibutuhkan > lebarPanelSaatIni + 1) {
          innerEl.style.width = lebarDibutuhkan + 'px';
          chartRekapInstance.resize();
        }
      }

      new MutationObserver(sesuaikanTinggiChartRekap).observe(tabPanelRekap || document.body, { attributes: true, attributeFilter: ['class'] });
      window.addEventListener('resize', sesuaikanTinggiChartRekap);
      window.addEventListener('load', sesuaikanTinggiChartRekap);
      sesuaikanTinggiChartRekap();
    }
  })();
  </script>

  <script>
  (function () {
    // Toggle tabel "Detail per Satuan" -- disembunyikan default supaya
    // chart "Total Laporan per Satuan" di atasnya punya ruang lebih lega,
    // baru muncul (dengan scrollbar sendiri) begitu tombol ini diklik.
    var btn = document.getElementById('btnToggleDetailSatuan');
    var panel = document.getElementById('panelDetailPerSatuan');
    var label = document.getElementById('btnToggleDetailSatuanLabel');
    var btnClose = document.getElementById('btnCloseDetailSatuan');
    if (!btn || !panel) return;
    btn.addEventListener('click', function () {
      var terbuka = panel.hidden;
      panel.hidden = !terbuka;
      btn.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
      if (label) label.textContent = terbuka ? 'Sembunyikan Detail per Satuan' : 'Lihat Detail per Satuan';
      if (terbuka) panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    // Tombol "Tutup Detail" (dropup) di dalam panel Detail per Satuan --
    // kebalikan dari tombol "Lihat Detail per Satuan" di atas: nutup lagi
    // panel ini dan scroll baliknya ke tombol toggle di atas biar user
    // ga nyasar keliatan halaman kosong abis tabelnya ilang.
    if (btnClose) {
      btnClose.addEventListener('click', function () {
        panel.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        if (label) label.textContent = 'Lihat Detail per Satuan';
        btn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      });
    }
  })();
  </script>

  <script>
  (function () {
    function collectRows(table) {
      return Array.prototype.slice.call(table.querySelectorAll('tbody tr:not(.table-empty-row)'));
    }

    // Beberapa nilai "Aksi" di Log Aktivitas ditulis dengan format kode
    // (mis. "satuan.create", "permintaan-reset-password.setujui") -- titik
    // & strip di situ bukan hal yang wajar buat diketik pengguna awam saat
    // mencari. Titik/strip di sini disamakan jadi spasi (di query maupun
    // teks yang dicocokkan) supaya "satuan create" tetap ketemu
    // "satuan.create" tanpa pengguna perlu tahu format aslinya.
    function normalisasiTeksCari(s) {
      return String(s || '').toLowerCase().replace(/[.\-]+/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function buatBarisKosong(table) {
      var colCount = table.querySelectorAll('thead th').length || 1;
      var tr = document.createElement('tr');
      tr.className = 'table-empty-row';
      var td = document.createElement('td');
      td.colSpan = colCount;
      td.innerHTML = '<div class="empty-state"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Tidak ada data yang cocok</div><div class="empty-state-sub">Coba ubah kata kunci pencarian atau filter-nya.</div></div>';
      tr.appendChild(td);
      return tr;
    }

    // Terapkan pencarian teks + filter dropdown untuk satu tabel tertentu
    // (dipanggil lewat id tabelnya). Diekspos ke window supaya bisa dipanggil
    // ulang dari tempat lain (mis. setelah status baris berubah).
    function terapkanTabelFilter(tableId) {
      var table = document.getElementById(tableId);
      if (!table) return;
      var wrap = table.closest('.tbl-wrap');
      var searchInput = document.querySelector('[data-table-search="' + tableId + '"]');
      var filterSelect = document.querySelector('[data-table-filter="' + tableId + '"]');
      var q = searchInput ? normalisasiTeksCari(searchInput.value) : '';
      var f = filterSelect ? filterSelect.value : '';
      var rows = collectRows(table);
      var visibleCount = 0;

      rows.forEach(function (row) {
        var teksCari = row.hasAttribute('data-search-value') ? row.getAttribute('data-search-value') : row.textContent;
        var cocokCari = !q || normalisasiTeksCari(teksCari).indexOf(q) !== -1;
        var cocokFilter = !f || row.getAttribute('data-filter-value') === f;
        var tampil = cocokCari && cocokFilter;
        row.style.display = tampil ? '' : 'none';
        if (tampil) visibleCount++;
      });

      var tbody = table.querySelector('tbody');
      var existingEmpty = tbody.querySelector('.table-empty-row');
      if (visibleCount === 0) {
        if (!existingEmpty) tbody.appendChild(buatBarisKosong(table));
      } else if (existingEmpty) {
        existingEmpty.remove();
      }

      // Hitung ulang batas 5 baris hanya berdasarkan baris yang sedang tampil.
      if (window.terapkanRowLimitWrap) window.terapkanRowLimitWrap(wrap);

      var countEl = document.querySelector('[data-table-count="' + tableId + '"]');
      if (countEl) countEl.textContent = visibleCount + ' dari ' + rows.length + ' data';
    }

    window.terapkanTabelFilter = terapkanTabelFilter;

    document.querySelectorAll('[data-table-search]').forEach(function (input) {
      input.addEventListener('input', function () {
        terapkanTabelFilter(input.getAttribute('data-table-search'));
      });
    });
    document.querySelectorAll('[data-table-filter]').forEach(function (select) {
      select.addEventListener('change', function () {
        terapkanTabelFilter(select.getAttribute('data-table-filter'));
      });
    });
    // Isi teks jumlah data begitu halaman dimuat, bukan cuma pas user mulai cari/filter.
    document.querySelectorAll('[data-table-count]').forEach(function (el) {
      terapkanTabelFilter(el.getAttribute('data-table-count'));
    });
  })();
  </script>

@include('siberad.dashboards.partials.dash-script')
</body>
</html>