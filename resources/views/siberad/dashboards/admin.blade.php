<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Admin — {{ $pengaturan?->namaSistem() ?? 'SIBERAD' }}</title>
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo-pussiberad.jpg') }}">
@include('siberad.dashboards.partials.dash-styles')
{{-- Kartu "Data Pelaporan" (Arsip Data) niru persis gaya kartu Permintaan
     Laporan Pimpinan/Satuan (.deadline-sender-item/.dcard-*) -- partial ini
     MURNI CSS (dipakai bareng laporan-pimpinan.blade.php & laporan-role.blade.php,
     tanpa JS di dalamnya), aman di-include di sini juga. Variabel --p-* yang
     dipakainya sudah dibungkus fallback ke token dasar (var(--panel-alt) dkk)
     yang memang sudah ada di Admin, KECUALI beberapa warna solid yang sudah
     hardcode hex/rgba -- jadi tetap tampil benar tanpa perlu definisi --p-*
     tambahan di sini. --}}
@include('siberad.dashboards.partials.permintaan-laporan-deadline-styles')
<style>
/* Token --p-* punya laporan-pimpinan.blade.php/laporan-role.blade.php (dipakai
   modal "Lihat Detail"/"Lihat Progres" punya Pimpinan yang dipinjam persis di
   bawah, bagian "Data Pelaporan") -- SAMA PERSIS definisinya (disalin apa
   adanya), supaya warna/permukaan modal itu identik di sini. Admin sendiri
   TIDAK pernah pakai nama --p-* di CSS-nya sendiri, jadi aman didefinisikan
   global tanpa nabrak apa-apa. */
:root{--p-bg:#f5f7f9;--p-surface:#fff;--p-surface-2:#f8fafc;--p-border:#e2e8f0;--p-text:#17212b;--p-muted:#64748b;--p-accent:#FF9800;--p-green:#16834b;--p-red:#c83b3b;--p-yellow:#b77900;--p-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25)}
:root:not([data-theme="light"]){--p-bg:var(--bg);--p-surface:var(--panel);--p-surface-2:var(--panel-alt);--p-border:var(--border);--p-text:var(--text);--p-muted:var(--text-muted);--p-accent:var(--gold-bright);--p-green:var(--success-bright);--p-red:var(--red);--p-yellow:var(--amber);--p-shadow:0 10px 30px rgba(0,0,0,.18)}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
  /* Samakan palet mode terang dengan dashboard Pimpinan (abu-abu netral/putih), bukan cream bawaan. */
  :root[data-theme="light"]{
    --bg:#f5f7f9;--bg-deep:#ffffff;--panel:#ffffff;--panel-2:#f8fafc;--panel-alt:#f8fafc;
    --border:#e2e8f0;--border-soft:#e2e8f0;--border-strong:#cbd5e1;
    --gold:#FF9800;--gold-bright:#FF9800;--gold-dim:rgba(255,152,0,.12);
    --green:#16834b;--green-bright:#16834b;--green-dim:rgba(22,131,75,.12);
    --amber:#b77900;--amber-dim:rgba(183,121,0,.14);
    --red:#c83b3b;--red-dim:rgba(200,59,59,.12);
    --text:#17212b;--text-muted:#64748b;--text-dim:#64748b;
    --surface:rgba(255,255,255,.9);--hover-tint:rgba(15,23,42,.035);
  }
  .chart-box{margin-bottom:26px;}
  {{-- 2 kolom (bukan 3 lagi) -- "Pengguna per Kategori Satuan" dipisah user
       jadi baris sendiri di bawah, niru posisi Pimpinan (donut+tren 2 kolom
       sejajar, chart lain-lain baris terpisah). --}}
  .chart-box-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;}
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
  {{-- Radar "Pengguna per Kategori Satuan" -- chart di kiri, rincian
       per-kategori (dot+nama+jumlah) di kanan, niru referensi gambar yang
       diminta user. Kartu ini berdiri sendiri full-width (lihat baris
       terpisah di atas .chart-box-grid), jadi chart-wrap dikasih tinggi
       lebih besar (260px) daripada 178px bawaan .chart-mini yang buat tile
       kecil di grid. --}}
  .radar-card .chart-wrap{height:260px;}
  .radar-card-body{display:flex;align-items:center;gap:28px;flex-wrap:wrap;}
  .radar-card-body .chart-wrap{flex:1 1 320px;min-width:260px;}
  .radar-legend-list{flex:1 1 220px;display:flex;flex-direction:column;gap:14px;min-width:200px;}
  .radar-legend-row{display:flex;align-items:center;gap:8px;}
  .radar-legend-dot{width:11px;height:11px;border-radius:50%;flex:0 0 auto;}
  .radar-legend-label{flex:0 0 auto;font-size:13px;font-weight:700;color:var(--text);white-space:nowrap;}
  {{-- Penghubung ANTARA nama kategori & angkanya -- 3 percobaan sebelumnya
       (garis dari titik chart, garis titik-titik gaya daftar isi, progress
       bar terisi) gak dipakai lagi ("jelek"/"kurang pas"/progress bar tanpa
       persen kesannya nyiratin ukuran tapi gak logis buat angka mentah).
       Sekarang garis solid tipis POLOS (bukan "terisi" sebagian kayak bar --
       gak ada makna persentase/completion yang bisa disalahartikan), cuma
       penghubung visual doang, mengabur di kedua ujung (gradient) biar
       nempel rapi ke dot & angka, bukan garis kaku penuh. --}}
  .radar-legend-connector{flex:1;min-width:16px;align-self:center;height:1px;background:linear-gradient(90deg,transparent,var(--border-strong) 15%,var(--border-strong) 85%,transparent);}
  .radar-legend-value{flex:0 0 auto;font-family:var(--mono);font-size:15px;font-weight:800;color:var(--text);}
  @media(max-width:980px){.chart-box-grid{grid-template-columns:1fr;}.chart-mini .chart-wrap{height:198px;}}


  /* ===== toolbar cari & filter tabel =====
     Disamakan gayanya dengan .rpt-filter-bar/.danpus-log-search (Pimpinan):
     tinggi 38px, radius 9px, ikon di posisi yang sama, dan teks jumlah hasil
     nempel pojok kanan lewat margin-left:auto -- 1 sistem, bukan style sendiri2. */
  .table-toolbar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;align-items:center;}
  .table-search-wrap{position:relative;flex:1 1 240px;min-width:200px;max-width:360px;}
  .table-search-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:16px;height:16px;stroke:var(--text-dim);pointer-events:none;}
  .table-search{
    width:100%;box-sizing:border-box;height:38px;background:var(--panel);border:1px solid var(--border);color:var(--text);
    font-family:var(--body);font-size:12px;border-radius:9px;padding:8px 11px 8px 35px;
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
  /* Kode satuan Admin dikunci di modal Ubah (readonly) -- kelihatan non-aktif. */
  #usKode[readonly]{opacity:.6;cursor:not-allowed;background:var(--panel-alt);}
  /* Combobox Satuan (input teks + saran) di modal Tambah/Ubah Pengguna --
     niru "Tujuan" di Buat Surat. Daftar saran-nya numpang .styled-select-menu
     (partials/styled-select.blade.php) yang di-append ke <body>. */
  .pengguna-satuan-cb{position:relative;width:100%;}
  .pengguna-satuan-cb input[type="text"]{width:100%;box-sizing:border-box;}

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
  /* ===== Arsip Data (submenu Monitoring, dulu "Data Laporan") ===== */
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
<script>
// Anti-kedip tab aktif: tab yang aktif secara default di HTML selalu
// "dashboard" (lihat data-tab-panel="dashboard" section.tab-panel active
// di bawah), sedangkan tab yang SEBENARNYA terakhir dibuka Admin (mis.
// "pengaturan-umum" setelah klik Simpan lalu halaman reload penuh)
// disimpan di sessionStorage dan baru dipulihkan oleh script di
// dash-script.blade.php -- yang posisinya di akhir <body>. Jeda antara
// browser mengecat HTML awal (tab Dashboard) dan script itu jalan itulah
// yang kelihatan sebagai "kedip" balik ke Dashboard sekejap.
//
// Script ini jalan SEBELUM konten <body> lain di-parse (jadi sebelum
// sempat dicat), baca tab tersimpan, lalu suntik <style> yang langsung
// menimpa tampilan ke tab yang benar. Style sementara ini dihapus lagi
// begitu activateAdminTab() asli (di dash-script.blade.php) jalan dan
// benar-benar memindah class .active -- supaya klik pindah tab sesudahnya
// tetap normal.
(function () {
  try {
    var KEY = 'siberad-admin-active-tab';
    var t = sessionStorage.getItem(KEY);
    if (!t || t === 'dashboard' || !/^[a-z0-9-]+$/i.test(t)) return;
    var css =
      '[data-tab-panel="dashboard"].tab-panel{display:none!important;}' +
      '[data-tab-panel="' + t + '"].tab-panel{display:block!important;}' +
      '.side-link[data-tab-link="dashboard"]{background:transparent!important;color:var(--text-muted)!important;border-color:transparent!important;font-weight:500!important;}' +
      '.side-link[data-tab-link="' + t + '"]{background:var(--gold-dim)!important;color:var(--gold-bright)!important;border-color:var(--border)!important;font-weight:600!important;}';
    var style = document.createElement('style');
    style.id = 'siberadNoFlashTab';
    style.textContent = css;
    document.head.appendChild(style);
  } catch (e) {}
})();
</script>
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
      <p class="help-intro">Ringkasan menu di dashboard Admin. Semua menu ada di sidebar kiri.</p>

      <div class="help-topics">
        <div class="help-topic">
          <div class="help-topic-icon">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
          <div class="help-topic-body">
            <div class="help-topic-title">Kelola Pengguna</div>
            <div class="help-topic-desc">Daftar Pengguna untuk menambah, mengubah, dan menonaktifkan akun tiap satuan. Permintaan Ganti Password untuk menyetujui atau menolak reset password dari pengguna.</div>
          </div>
        </div>
        <div class="help-topic">
          <div class="help-topic-icon">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
          </div>
          <div class="help-topic-body">
            <div class="help-topic-title">Monitoring</div>
            <div class="help-topic-desc">Arsip Data (daftar lengkap + ekspor) dan Pengguna Aktif (daftar yang sedang login). Ringkasan grafik per satuan bisa dibuka lewat kartu "Distribusi Status Laporan" di Dashboard.</div>
          </div>
        </div>
        <div class="help-topic">
          <div class="help-topic-icon">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
          </div>
          <div class="help-topic-body">
            <div class="help-topic-title">Riwayat Aktivitas</div>
            <div class="help-topic-desc">Rekam jejak login, logout, dan seluruh aksi kelola sistem oleh Admin, lengkap dengan waktu dan pelakunya.</div>
          </div>
        </div>
        <div class="help-topic">
          <div class="help-topic-icon">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
          </div>
          <div class="help-topic-body">
            <div class="help-topic-title">Kelola Sistem</div>
            <div class="help-topic-desc">Data Satuan, Hak Akses Pengguna, Cadangan Data, Reset Data Laporan, Pengaturan Umum, dan Notifikasi aplikasi.</div>
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
    <form class="form-grid" method="POST" action="{{ route('admin.users.store') }}" id="tambahPenggunaForm" autocomplete="off">
      @csrf
      {{-- Penanda form: dipakai saat validasi gagal supaya modal yang benar
           dibuka ulang dengan error merah inline (lihat script re-open di bawah). --}}
      <input type="hidden" name="_form" value="user_store">
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
        <label for="uSatuanSearch">Satuan</label>
        {{-- Input teks + saran otomatis (bukan <select>), pola sama persis
             kayak "Tujuan" di Buat Surat. Hidden #uSatuan yang bawa id satuan
             ke server; ngetik doang tanpa milih dari saran = hidden kosong. --}}
        <div class="pengguna-satuan-cb" id="uSatuanCombobox">
          <input type="hidden" id="uSatuan" name="satuan_id">
          <input type="text" id="uSatuanSearch" autocomplete="off" placeholder="Ketik nama / kode satuan...">
        </div>
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
      {{-- Penanda + id user: dipakai saat validasi gagal supaya modal Ubah
           dibuka ulang (action-nya dibangun dari _uid) dengan error merah inline. --}}
      <input type="hidden" name="_form" value="user_update">
      <input type="hidden" name="_uid" id="upUid">
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
        <label for="upSatuanSearch">Satuan</label>
        <div class="pengguna-satuan-cb" id="upSatuanCombobox">
          <input type="hidden" id="upSatuan" name="satuan_id">
          <input type="text" id="upSatuanSearch" autocomplete="off" placeholder="Ketik nama / kode satuan...">
        </div>
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
        <button type="submit" class="btn btn-ghost-red" id="hapusPenggunaYa">Ya, Hapus</button>
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

<div class="confirm-overlay" id="resetDataLaporanOverlay">
  <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="resetDataLaporanTitle">
    <div class="confirm-icon">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>
    </div>
    <h3 id="resetDataLaporanTitle">Hapus Data Laporan Terpilih?</h3>
    <p id="resetDataLaporanSub">Data berikut akan dihapus secara permanen dari database dan penyimpanan server (lampiran file ikut dibersihkan):</p>
    <ul id="resetDataLaporanDaftar" style="text-align:left;font-size:12px;font-weight:700;color:var(--text);margin:10px 0 0;padding-left:18px;max-height:160px;overflow-y:auto;"></ul>
    <div class="confirm-actions">
      <button type="button" class="btn" id="resetDataLaporanBatal">Batal</button>
      <button type="button" class="btn btn-ghost-red" id="resetDataLaporanYa">Ya, Hapus Permanen</button>
    </div>
  </div>
</div>

<div class="confirm-overlay" id="hapusLandingGambarOverlay">
  <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="hapusLandingGambarTitle">
    <div class="confirm-icon">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>
    </div>
    <h3 id="hapusLandingGambarTitle">Hapus Gambar?</h3>
    <p><strong id="hapusLandingGambarNama">Gambar ini</strong> akan dihapus permanen dari server dan tidak bisa dikembalikan.</p>
    <form id="formHapusLandingGambar" method="POST" action="">
      @csrf @method('DELETE')
      <div class="confirm-actions">
        <button type="button" class="btn" id="hapusLandingGambarBatal">Batal</button>
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
      {{-- Penanda form: dipakai saat validasi gagal supaya modal yang benar
           dibuka ulang dengan error merah inline (lihat script re-open di bawah). --}}
      <input type="hidden" name="_form" value="satuan_store">
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
      {{-- Penanda + id satuan: dipakai saat validasi gagal supaya modal Ubah
           dibuka ulang (action dibangun dari _uid) dengan error merah inline. --}}
      <input type="hidden" name="_form" value="satuan_update">
      <input type="hidden" name="_uid" id="usSatuanId">
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
        <button type="submit" class="btn btn-ghost-red" id="hapusSatuanYa">Ya, Hapus</button>
      </div>
    </form>
  </div>
</div>


{{-- Modal "Lihat Detail"/"Lihat Progres" (Data Pelaporan) HARUS di luar
     .shell/.content (lihat blok CSS+HTML+JS di bawah) -- .shell & .content
     (dash-styles.blade.php) sama-sama position:relative + z-index eksplisit,
     jadi keduanya bikin stacking context sendiri. Kalau modal ini dulu
     ditaruh NESTED di dalam .content, z-index setinggi apapun di dalam
     modal itu cuma dibandingkan LOKAL di dalam .content (z-index:1) --
     gak pernah bisa ngalahin #sidebar (z-index:100010) yang notabene
     SEJAJAR .content di dalam .shell, walau angka z-index modalnya
     (100200) keliatan jauh lebih tinggi. Makanya backdrop blur/gelapnya
     dulu nutupin konten tapi nembus di bawah sidebar. Sama persis pola
     laporan-danpus.blade.php: modal-modal ini DUA-duanya ditaruh sebelum
     <div class="shell"> (bukan di dalam .content), biar z-index-nya
     dibandingkan langsung di root <body> lawan .shell, bukan kejebak
     stacking context lokal. --}}
      {{-- ===== Modal "Lihat Detail" & "Lihat Progres" (kartu Data Pelaporan) =====
           Versi READ-ONLY dari #permintaanDetailModal/#pimpinanProgresModal/
           #pimpinanTaskDetailModal punya laporan-pimpinan.blade.php -- HTML,
           CSS, dan JS-nya disalin apa adanya dari sana (termasuk fungsi
           openPimpinanProgres() yang TIDAK diubah SAMA SEKALI), supaya
           tampilan & perilakunya identik. Bedanya cuma 2:
           (1) openPermintaanDetailModal() versi sini gak bangun tombol
               "Lihat Aktivitas" di footer (cuma "Tutup") -- fitur itu butuh
               window.danpusLihatAktivitas yang tidak dimuat di Admin.
           (2) Kartu Data Pelaporan SELALU kirim data-riwayat="1" ke
               openPimpinanProgres() -- flag yang SAMA yang dipakai versi
               Riwayat Laporan Pimpinan buat bikin modal "Lihat Progres"
               read-only total (tanpa Tolak/Terima/Batalkan/Edit Deadline/
               Revisi); openPimpinanProgres() sendiri sudah py`nya cabang
               `if(riwayat==='1'){ /* kosong */ }` yang otomatis skip SEMUA
               tombol aksi begitu flag ini dikirim, jadi fungsinya bisa
               dipakai apa adanya tanpa modifikasi. --}}
      <style>
      .priority-tag{display:inline-flex;align-items:center;border-radius:999px;padding:5px 10px;font-size:10px;font-weight:800;border:1px solid transparent;white-space:nowrap}
      .report-modal{position:fixed;inset:0;background:rgba(15,23,42,.48);display:flex;align-items:center;justify-content:center;padding:20px;z-index:1000;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease}
      .report-modal.open{opacity:1;visibility:visible;pointer-events:auto}
      .report-modal-card{width:min(760px,100%);max-height:90vh;overflow:auto;background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px;padding:22px;box-shadow:0 25px 70px rgba(15,23,42,.22);box-sizing:border-box;transform:translateY(14px) scale(.97);transition:transform .2s ease}
      .report-modal.open .report-modal-card{transform:translateY(0) scale(1)}
      .report-modal-head{display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:18px}
      .report-modal-head h3{margin:0;font-family:var(--display);font-size:20px}
      .report-modal-close{flex-shrink:0;width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:1px solid var(--p-border);background:transparent;color:var(--p-muted);cursor:pointer;transition:border-color .2s ease,color .2s ease,transform .2s ease}
      .report-modal-close:hover{border-color:var(--p-red);color:var(--p-red);transform:rotate(90deg);}
      .report-modal-close svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;}
      .detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
      .detail-item{padding:12px;border:1px solid var(--p-border);border-radius:9px;background:var(--p-surface-2)}
      .detail-item.full{grid-column:1/-1}
      .detail-label{font-size:9px;text-transform:uppercase;color:var(--p-muted);font-weight:800;letter-spacing:.06em;margin-bottom:5px}
      .detail-value{font-size:12px;line-height:1.65;white-space:pre-wrap;color:var(--p-text)}
      .modal-actions{display:flex;justify-content:flex-end;margin-top:16px}
      .action-row{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
      .action-row button{border:1px solid transparent;border-radius:8px;padding:8px 14px;font-size:11px;font-weight:700;cursor:pointer;transition:filter .15s ease,transform .15s ease,background .15s ease,color .15s ease}
      .action-row button:active{transform:scale(.96)}
      .satuan-pill{display:inline-flex;align-items:center;border-radius:8px;padding:4px 9px;font-size:10px;font-weight:800;letter-spacing:.03em;color:var(--p-accent);background:rgba(201,122,0,.1);border:1px solid rgba(201,122,0,.22);white-space:nowrap}
      .request-deadline{display:inline-flex;align-items:center;gap:5px;font-weight:700}
      .request-deadline svg{width:13px;height:13px;flex-shrink:0;opacity:.75}
      #permintaanDetailModal{display:none;visibility:visible;background:rgba(15,23,42,.28);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);z-index:100200;padding:24px;transition:opacity .22s ease}
      #permintaanDetailModal.pl-mounted{display:flex}
      #permintaanDetailModal .report-modal-card{max-height:min(86vh,760px);padding:20px;border-radius:18px;box-shadow:0 24px 70px rgba(15,23,42,.24);transform:translateY(10px) scale(.985);transition:transform .22s ease;will-change:transform}
      #permintaanDetailModal.open .report-modal-card{transform:translateY(0) scale(1)}
      #permintaanDetailModal .report-modal-head h3{font-size:22px;font-weight:700;letter-spacing:.01em}
      #permintaanDetailModal .detail-grid{gap:12px}
      #permintaanDetailModal .detail-item{padding:11px;border-radius:8px}
      #permintaanDetailModal .detail-label{font-size:10px;font-weight:700;letter-spacing:.05em}
      #permintaanDetailModal .detail-value{font-size:13px;line-height:1.6}
      #permintaanDetailModal .modal-actions{gap:8px;flex-wrap:wrap;margin-top:18px}
      #permintaanDetailModal .modal-actions .action-row{gap:8px;justify-content:flex-end}
      #permintaanDetailModal .action-row .pl-btn-ghost{border:1px solid var(--p-border);background:transparent;color:var(--p-text);font-family:var(--mono);font-weight:600;font-size:11.5px;letter-spacing:.04em;text-transform:uppercase;padding:9px 15px;border-radius:8px;transition:border-color .15s ease,color .15s ease,transform .15s ease}
      #permintaanDetailModal .action-row .pl-btn-ghost:hover{border-color:var(--p-accent);color:var(--p-accent);transform:translateY(-1px)}
      {{-- Media query ini kelewat pas HTML/CSS modal disalin dari
           laporan-danpus.blade.php (lihat komentar di atas) -- tanpanya,
           .detail-grid tetap 2 kolom di layar sempit/HP, beda dari versi
           Pimpinan yang collapse ke 1 kolom. --}}
      @media(max-width:700px){#permintaanDetailModal .detail-grid{grid-template-columns:1fr}}
      #pimpinanProgresModal{display:none;visibility:visible;background:rgba(0,0,0,.55);transition:opacity .22s ease}
      #pimpinanProgresModal.pl-mounted{display:flex}
      #pimpinanProgresModal .report-modal-card{width:min(720px,100%);border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);transform:translateY(10px) scale(.985);transition:width .2s ease,transform .22s ease}
      #pimpinanProgresModal.pl-progres-has-steps .report-modal-card{width:min(940px,100%)}
      #pimpinanProgresModal.open .report-modal-card{transform:translateY(0) scale(1)}
      #pimpinanProgresModal .kirim-laporan-modal-head{align-items:center}
      #pimpinanProgresModal .task-detail-btn{margin-left:auto;flex-shrink:0;display:inline-flex;align-items:center;gap:6px;border:1px solid color-mix(in srgb,var(--p-accent) 45%,var(--p-border));background:color-mix(in srgb,var(--p-accent) 10%,var(--p-surface-2));color:var(--p-accent);border-radius:9px;padding:8px 12px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .15s ease,transform .15s ease,border-color .15s ease}
      #pimpinanProgresModal .task-detail-btn:hover{background:color-mix(in srgb,var(--p-accent) 20%,var(--p-surface-2));transform:translateY(-1px)}
      #pimpinanProgresModal .task-detail-btn svg{width:14px;height:14px;flex-shrink:0}
      #pimpinanProgresModal .task-detail-btn[hidden]{display:none}
      #pimpinanTaskDetailModal .report-modal-card{width:min(480px,100%)}
      #pimpinanTaskDetailModal .task-detail-modal-sub{margin:2px 0 12px;font-size:12px;color:var(--p-muted);line-height:1.55}
      #pimpinanTaskDetailModal .task-detail-modal-body{font-size:13px;line-height:1.7;white-space:pre-wrap;color:var(--p-text);border:1px solid var(--p-border);border-radius:10px;background:var(--p-surface-2);padding:13px 15px;max-height:56vh;overflow-y:auto}
      #pimpinanProgresModal .form-grid{gap:14px}
      #pimpinanProgresModal .form-field{gap:7px}
      #pimpinanProgresModal .form-field label{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono);font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em}
      #pimpinanProgresModal .form-field textarea{width:100%;box-sizing:border-box;background:var(--panel-alt);border:1px solid var(--border);border-radius:7px;color:var(--text);padding:10px 11px;font:inherit;font-size:13px;resize:none;min-height:120px}
      #pimpinanProgresModal .kirim-laporan-form-card{grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:18px 20px;padding:20px;border:1px solid var(--border-soft);border-radius:14px;background:var(--panel-alt)}
      {{-- Media query ini juga kelewat waktu disalin -- HARUS setelah rule
           di atas (specificity-nya sama, jadi urutan cascade yang menang),
           bukan sebelumnya seperti draf awal. --}}
      @media(max-width:640px){#pimpinanProgresModal .kirim-laporan-form-card{grid-template-columns:1fr;padding:16px}}
      #pimpinanProgresModal .wizard-step-pending{cursor:pointer}
      </style>
      <div class="report-modal" id="permintaanDetailModal"><div class="report-modal-card"><div class="report-modal-head"><div><h3>Detail Permintaan Laporan</h3><p style="margin:4px 0 0;font-size:12px;color:var(--p-muted);font-weight:400;">Detail permintaan laporan yang dikirim kepada satuan.</p></div></div><div class="detail-grid"><div class="detail-item"><div class="detail-label">Tujuan</div><div class="detail-value" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;"><span id="permintaanDetailTujuan">-</span><span class="satuan-pill" id="permintaanDetailTujuanKode" style="display:none;"></span></div></div><div class="detail-item"><div class="detail-label">Deadline</div><div class="detail-value request-deadline"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg><span id="permintaanDetailDeadline">-</span></div></div><div class="detail-item"><div class="detail-label">Perihal</div><div class="detail-value" id="permintaanDetailPerihal">-</div></div><div class="detail-item"><div class="detail-label">Kategori</div><div class="detail-value" id="permintaanDetailKategori">-</div></div><div class="detail-item"><div class="detail-label">Prioritas</div><div class="detail-value"><span class="priority-tag" id="permintaanDetailPrioritas">-</span></div></div><div class="detail-item"><div class="detail-label">Status</div><div class="detail-value"><span class="deadline-pill" id="permintaanDetailStatus">-</span></div></div><div class="detail-item full"><div class="detail-label">Instruksi</div><div class="detail-value" id="permintaanDetailInstruksi">-</div></div><div class="detail-item full" id="permintaanDetailCatatanWrap" style="display:none;"><div class="detail-label">Catatan / Keterangan</div><div class="detail-value" id="permintaanDetailCatatan" style="white-space:pre-line;">-</div></div></div><div class="modal-actions" id="permintaanDetailActions"></div></div></div>
      <div class="report-modal" id="pimpinanProgresModal"><div class="report-modal-card"><div class="kirim-laporan-wizard-body"><div class="kirim-laporan-wizard-topbar wizard-topbar-visible"><button type="button" class="wizard-topbar-nav wizard-topbar-nav-prev" id="pimpinanProgresPrev" aria-label="Task sebelumnya" hidden><svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"></path></svg></button><ol class="wizard-step-list" id="pimpinanProgresSteps"></ol><button type="button" class="wizard-topbar-nav wizard-topbar-nav-next" id="pimpinanProgresNext" aria-label="Task selanjutnya" hidden><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg></button></div><div class="kirim-laporan-wizard-panel"><div class="kirim-laporan-modal-head"><span class="kirim-laporan-modal-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect><path d="m9 14 2 2 4-4"></path></svg></span><span><h3 id="pimpinanProgresTitle" style="margin:0 0 4px;">Lihat Progres</h3><p id="pimpinanProgresDesc" style="margin:0;font-size:12px;color:var(--p-muted);line-height:1.5;">Checklist tugas untuk permintaan ini.</p></span><button type="button" id="pimpinanTaskDetailBtn" class="task-detail-btn" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>Detail Task</button></div><div class="form-grid"><div class="kirim-laporan-form-card"><div class="form-field"><label><svg class="form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>Isi Laporan</label><textarea id="pimpinanProgresDeskripsi" readonly></textarea></div><div class="form-field"><label><svg class="form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>Kendala/Alasan</label><textarea id="pimpinanProgresKendala" readonly></textarea></div><div class="form-field full"><label><svg class="form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>Lampiran</label><div class="lampiran-file-list" id="pimpinanProgresLampiran"><div class="lampiran-file-list-empty">Belum ada file yang diupload</div></div></div></div><div class="form-field full" id="pimpinanProgresActions" style="display:flex;flex-direction:row;justify-content:flex-end;gap:8px;margin-top:4px;"><button type="button" class="btn" id="pimpinanProgresTutupBtn">Tutup</button></div></div></div></div></div></div>
      <div class="report-modal" id="pimpinanTaskDetailModal" style="z-index:100300"><div class="report-modal-card"><div class="report-modal-head"><h3>Detail Task</h3></div><p class="task-detail-modal-sub">Instruksi rinci dari Pimpinan untuk task yang dikerjakan satuan.</p><div class="task-detail-modal-body" id="pimpinanTaskDetailModalBody">-</div><div class="modal-actions"><button type="button" class="btn" id="pimpinanTaskDetailModalClose">Tutup</button></div></div></div>
      <script>
      (function(){
      window.openPermintaanDetailModal=function(button){
        const modal=document.getElementById('permintaanDetailModal');
        document.getElementById('permintaanDetailTujuan').textContent=button.dataset.tujuan||'-';
        const tujuanKodeEl=document.getElementById('permintaanDetailTujuanKode');
        if(button.dataset.tujuanKode){tujuanKodeEl.textContent=button.dataset.tujuanKode;tujuanKodeEl.style.display='inline-flex';}else{tujuanKodeEl.style.display='none';}
        document.getElementById('permintaanDetailDeadline').textContent=button.dataset.deadline||'-';
        document.getElementById('permintaanDetailPerihal').textContent=button.dataset.perihal||'-';
        document.getElementById('permintaanDetailKategori').textContent=button.dataset.kategori||'-';
        const prioEl=document.getElementById('permintaanDetailPrioritas');
        prioEl.textContent=button.dataset.prioritas||'-';
        prioEl.className='priority-tag pl-prio-violet'+(button.dataset.prioritas?' prio-'+button.dataset.prioritas.toLowerCase():'');
        const statusEl=document.getElementById('permintaanDetailStatus');
        statusEl.textContent=button.dataset.status||'-';
        statusEl.className='deadline-pill '+(button.dataset.statusClass||'');
        document.getElementById('permintaanDetailInstruksi').textContent=button.dataset.instruksi||'-';
        const catatanPenolakan=(button.dataset.catatan||'').trim();
        const catatanWrap=document.getElementById('permintaanDetailCatatanWrap');
        const catatanEl=document.getElementById('permintaanDetailCatatan');
        if(catatanWrap&&catatanEl){
          if(catatanPenolakan){catatanEl.textContent=catatanPenolakan;catatanWrap.style.display='';}
          else{catatanEl.textContent='-';catatanWrap.style.display='none';}
        }
        const actionsEl=document.getElementById('permintaanDetailActions');
        const row=document.createElement('div');row.className='action-row';
        // Versi Admin (Arsip Data) SENGAJA cuma "Tutup" -- gak ada "Lihat
        // Aktivitas" (butuh window.danpusLihatAktivitas milik dashboard
        // Pimpinan, tidak dimuat di sini).
        const tutupBtn=document.createElement('button');tutupBtn.type='button';tutupBtn.className='btn pl-btn-ghost';tutupBtn.textContent='Tutup';
        tutupBtn.addEventListener('click',function(){tutupPermintaanDetailModal();});
        row.appendChild(tutupBtn);
        actionsEl.innerHTML='';actionsEl.appendChild(row);
        if(modal){
          modal.classList.remove('open');
          modal.classList.add('pl-mounted');
          void modal.offsetWidth;
          requestAnimationFrame(function(){modal.classList.add('open');});
        }
      };
      window.tutupPermintaanDetailModal=function(){
        const m=document.getElementById('permintaanDetailModal');
        if(!m)return;
        m.classList.remove('open');
        setTimeout(function(){if(!m.classList.contains('open'))m.classList.remove('pl-mounted');},240);
      };
      document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('permintaanDetailModal')?.classList.contains('open'))tutupPermintaanDetailModal()});

      // openPimpinanProgres() DISALIN APA ADANYA dari laporan-pimpinan.blade.php
      // -- lihat catatan panjang di atas kenapa aman dipakai tanpa modifikasi
      // (cabang riwayat==='1' bikin SEMUA tombol aksi ke-skip otomatis).
      window.openPimpinanProgres=function(button,refresh){
        const modal=document.getElementById('pimpinanProgresModal');
        let tasks=[],ppItems=null,ppDefault=0;
        try{tasks=button.dataset.tasks?JSON.parse(button.dataset.tasks):[];}catch(e){tasks=[];}
        if(modal)modal.dataset.plPid=button.dataset.permintaanId||'';
        const stepsEl=document.getElementById('pimpinanProgresSteps');
        const descEl=document.getElementById('pimpinanProgresDesc');
        const detailBtnEl=document.getElementById('pimpinanTaskDetailBtn');
        const detailBodyEl=document.getElementById('pimpinanTaskDetailModalBody');
        const deskTa=document.getElementById('pimpinanProgresDeskripsi');
        const kendalaTa=document.getElementById('pimpinanProgresKendala');
        const lampiranEl=document.getElementById('pimpinanProgresLampiran');
        const stepPrev=document.getElementById('pimpinanProgresPrev');
        const stepNext=document.getElementById('pimpinanProgresNext');
        function refreshStepNav(){
          if(!stepPrev||!stepNext)return;
          const of=stepsEl.scrollWidth>stepsEl.clientWidth+1;
          stepPrev.hidden=!of;stepNext.hidden=!of;
          if(!of)return;
          stepPrev.disabled=stepsEl.scrollLeft<=0;
          stepNext.disabled=stepsEl.scrollLeft+stepsEl.clientWidth>=stepsEl.scrollWidth-1;
        }
        if(stepPrev&&stepPrev.dataset.navBound!=='1'){
          stepPrev.dataset.navBound='1';
          const pageScroll=function(d){stepsEl.scrollBy({left:d*Math.max(stepsEl.clientWidth-60,120),behavior:'smooth'});};
          stepPrev.addEventListener('click',function(){pageScroll(-1);});
          stepNext.addEventListener('click',function(){pageScroll(1);});
          stepsEl.addEventListener('scroll',refreshStepNav);
          window.addEventListener('resize',refreshStepNav);
        }
        function showTask(idx,items){
          if(modal)modal.dataset.plStep=idx;
          items.forEach(function(el,i){el.classList.toggle('wizard-step-current',i===idx);el.classList.remove('wizard-step-marker-in');});
          const cur=items[idx];
          if(cur){
            requestAnimationFrame(function(){requestAnimationFrame(function(){if(cur.classList.contains('wizard-step-current'))cur.classList.add('wizard-step-marker-in');});});
            cur.scrollIntoView({inline:'nearest',block:'nearest'});
          }
          const t=tasks[idx];
          const lap=t&&t.laporan;
          const td=(t&&t.detail)||'';
          if(detailBodyEl)detailBodyEl.textContent=td||'Detail task tidak tersedia.';
          if(detailBtnEl)detailBtnEl.hidden=!td;
          document.getElementById('pimpinanTaskDetailModal')?.classList.remove('open');
          descEl.textContent=lap?'Checkpoint ini sudah dikerjakan satuan.':'Task ini belum dikerjakan satuan.';
          deskTa.value=lap?(lap.deskripsi||''):'';
          kendalaTa.value=lap?(lap.kendala||''):'';
          lampiranEl.innerHTML='';
          const lampiran=lap?(lap.lampiran||[]):[];
          if(lampiran.length){
            lampiran.forEach(function(x){
              const row=document.createElement('div');row.className='lampiran-file-row';
              const b=(window.siberadLampiranBadge&&window.siberadLampiranBadge(x.nama||x.url))||{text:'FILE',cls:'lfx-other'};
              const icon=document.createElement('span');icon.className='lampiran-file-row-icon '+b.cls;icon.textContent=b.text;
              const info=document.createElement('span');info.className='lampiran-file-row-info';
              const a=document.createElement('a');a.className='lampiran-file-row-name';a.href=x.url;a.target='_blank';a.rel='noopener';a.textContent=x.nama||'Lihat lampiran';
              const size=document.createElement('span');size.className='lampiran-file-row-size';size.textContent='Tersimpan';
              info.appendChild(a);info.appendChild(size);
              row.appendChild(icon);row.appendChild(info);
              lampiranEl.appendChild(row);
            });
          }else{
            const empty=document.createElement('div');empty.className='lampiran-file-list-empty';empty.textContent='Belum ada file yang diupload';
            lampiranEl.appendChild(empty);
          }
        }
        stepsEl.innerHTML='';
        if(!tasks.length){
          descEl.textContent='Tidak ada task untuk permintaan ini.';
          if(detailBodyEl)detailBodyEl.textContent='Detail task tidak tersedia.';
          if(detailBtnEl)detailBtnEl.hidden=true;
          document.getElementById('pimpinanTaskDetailModal')?.classList.remove('open');
          deskTa.value='';kendalaTa.value='';lampiranEl.innerHTML='';
          if(stepPrev)stepPrev.hidden=true;
          if(stepNext)stepNext.hidden=true;
        }else{
          let activeAssigned=false,defaultIdx=tasks.length-1;
          const items=tasks.map(function(t,i){
            let isActiveTask=false;
            if(t.selesai){}else if(!activeAssigned){activeAssigned=true;defaultIdx=i;isActiveTask=true;}
            const state=t.selesai?'done':(isActiveTask?'active':'pending');
            const li=document.createElement('li');
            li.className='wizard-step wizard-step-'+state;
            li.title=t.deskripsi||'';
            const dot=document.createElement('span');dot.className='wizard-step-dot';
            if(t.selesai){dot.innerHTML='<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';}
            else{dot.textContent=String(i+1);}
            const label=document.createElement('span');label.className='wizard-step-label';label.textContent=t.deskripsi||('Task '+(i+1));
            li.appendChild(dot);li.appendChild(label);
            li.setAttribute('role','button');li.tabIndex=0;
            stepsEl.appendChild(li);
            return li;
          });
          items.forEach(function(li,i){
            li.addEventListener('click',function(){showTask(i,items)});
            li.addEventListener('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();showTask(i,items)}});
          });
          let startIdx=defaultIdx;
          if(refresh){const p=parseInt((modal&&modal.dataset.plStep)||'',10);if(!isNaN(p))startIdx=Math.max(0,Math.min(p,items.length-1));}
          ppItems=items;ppDefault=startIdx;
        }
        const progresActions=document.getElementById('pimpinanProgresActions');
        if(progresActions){
          progresActions.querySelectorAll('.pl-progres-action').forEach(function(b){b.remove();});
          const statusP=button.dataset.status||'';
          let actionBtn=null;
          if(button.dataset.riwayat==='1'){
            // Admin (Arsip Data) SELALU kirim riwayat="1" -- modal ini jadi
            // read-only total, cuma tombol "Tutup".
          }else if(statusP==='Dibatalkan'||statusP==='Terlambat'){
            actionBtn=document.createElement('button');actionBtn.type='button';actionBtn.className='btn btn-edit-permintaan pl-progres-action';actionBtn.textContent='Edit Deadline';
            actionBtn.dataset.permintaanId=button.dataset.permintaanId||'';actionBtn.dataset.perihal=button.dataset.perihal||'';actionBtn.dataset.deadline=button.dataset.deadlineRaw||'';actionBtn.dataset.editable=button.dataset.editable||'0';actionBtn.dataset.alasan=button.dataset.alasan||'';
            actionBtn.addEventListener('click',function(){window.bukaEditDeadlinePermintaan&&window.bukaEditDeadlinePermintaan(actionBtn);});
          }else if(statusP==='Sedang diproses'||statusP==='Terbaru'){
            actionBtn=document.createElement('button');actionBtn.type='button';actionBtn.className='btn btn-batalkan-permintaan pl-progres-action';actionBtn.textContent='Batalkan';
            actionBtn.dataset.permintaanId=button.dataset.permintaanId||'';actionBtn.dataset.perihal=button.dataset.perihal||'';
            actionBtn.addEventListener('click',function(){window.bukaBatalkanPermintaan&&window.bukaBatalkanPermintaan(actionBtn);});
          }else if(statusP==='Ditolak'){
            actionBtn=document.createElement('button');actionBtn.type='button';actionBtn.className='btn btn-revisi-permintaan pl-progres-action';actionBtn.textContent='Revisi';
            actionBtn.dataset.laporanId=button.dataset.laporanId||'';
            actionBtn.addEventListener('click',function(){window.bukaRevisiLaporanPimpinan&&window.bukaRevisiLaporanPimpinan(actionBtn);});
          }else if(statusP==='Menunggu'){
            const lid=button.dataset.laporanId||'';
            const tolakBtn=document.createElement('button');tolakBtn.type='button';tolakBtn.className='btn pl-progres-action pl-progres-reject';tolakBtn.textContent='Tolak';
            tolakBtn.addEventListener('click',function(){window.bukaTolakLaporanPimpinan&&window.bukaTolakLaporanPimpinan(lid);});
            const terimaBtn=document.createElement('button');terimaBtn.type='button';terimaBtn.className='btn pl-progres-action pl-progres-approve';terimaBtn.textContent='Terima';
            terimaBtn.addEventListener('click',function(){window.bukaTerimaLaporanPimpinan&&window.bukaTerimaLaporanPimpinan(lid);});
            progresActions.appendChild(tolakBtn);progresActions.appendChild(terimaBtn);
          }
          if(actionBtn)progresActions.appendChild(actionBtn);
        }
        if(modal){
          modal.classList.toggle('pl-progres-has-steps',tasks.length>0);
          if(refresh){
            if(ppItems&&ppItems.length)showTask(ppDefault,ppItems);
            else{modal.dataset.plStep='';}
            refreshStepNav();
          }else{
            modal.classList.remove('open');
            modal.classList.add('pl-mounted');
            void modal.offsetWidth;
            requestAnimationFrame(function(){
              modal.classList.add('open');
              requestAnimationFrame(function(){
                if(ppItems&&ppItems.length)showTask(ppDefault,ppItems);
                refreshStepNav();
              });
            });
          }
        }
      };
      // Dipanggil oleh sync() (realtime Data Pelaporan di bawah) tiap kartu
      // di-replace -- kalau modal "Lihat Progres" lagi kebuka buat
      // permintaan itu, checklist + panelnya ikut ke-refresh live. Kembar
      // window.siberadRefreshPimpinanProgres punya Danpus/Wadan
      // (laporan-danpus.blade.php / laporan-wadan.blade.php).
      window.siberadRefreshPimpinanProgres=function(freshCard){
        const modal=document.getElementById('pimpinanProgresModal');
        if(!modal||!modal.classList.contains('open'))return;
        const id=(freshCard&&freshCard.getAttribute('data-realtime-permintaan-id'))||'';
        if(!id||id!==(modal.dataset.plPid||''))return;
        const btn=freshCard.querySelector('[onclick*="openPimpinanProgres"]');
        if(btn)window.openPimpinanProgres(btn,true);
      };
      window.tutupPimpinanProgres=function(){
        const m=document.getElementById('pimpinanProgresModal');
        if(!m)return;
        m.classList.remove('open');
        setTimeout(function(){if(!m.classList.contains('open'))m.classList.remove('pl-mounted');},240);
      };
      document.getElementById('pimpinanProgresTutupBtn')?.addEventListener('click',()=>tutupPimpinanProgres());
      document.addEventListener('keydown',e=>{
        if(e.key!=='Escape')return;
        if(!document.getElementById('pimpinanProgresModal')?.classList.contains('open'))return;
        if(document.getElementById('pimpinanTaskDetailModal')?.classList.contains('open'))return;
        tutupPimpinanProgres();
      });
      (function(){
        const tdm=document.getElementById('pimpinanTaskDetailModal');
        if(!tdm)return;
        const close=()=>tdm.classList.remove('open');
        document.getElementById('pimpinanTaskDetailBtn')?.addEventListener('click',()=>tdm.classList.add('open'));
        document.getElementById('pimpinanTaskDetailModalClose')?.addEventListener('click',close);
        document.addEventListener('keydown',e=>{if(e.key==='Escape'&&tdm.classList.contains('open'))close();});
      })();
      })();
      </script>
<div class="shell">

  <aside class="sidebar" id="sidebar">
    <div class="side-brand">
      <img src="{{ asset('images/logo-pussiberad.jpg') }}" alt="Lambang Pussiberad">
      <div class="logo">{{ $pengaturan->hero_judul_awal }}<span>{{ $pengaturan->hero_judul_aksen }}</span></div>
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
          <a href="#" class="side-sub-link" data-tab-link="laporan-admin" title="Arsip Data"><span class="sub-dot"></span>Arsip Data</a>
          <a href="#" class="side-sub-link" data-tab-link="sesi-aktif" title="Pengguna Aktif"><span class="sub-dot"></span>Pengguna Aktif</a>
        </div></div>
      </div>

      <div class="side-nav-group open" id="aktivitasGroup">
        <button type="button" class="side-nav-group-title" id="aktivitasToggle" title="Riwayat Aktivitas">
          <span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span>
          <span class="side-text">Riwayat Aktivitas</span>
          <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg>
        </button>
        <div class="side-subnav"><div>
          <span class="side-subnav-label">Riwayat Aktivitas</span>
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
          <a href="#" class="side-sub-link" data-tab-link="reset-data-laporan" title="Reset Data Laporan"><span class="sub-dot"></span>Reset Data Laporan</a>
          <a href="#" class="side-sub-link" data-tab-link="pengaturan-umum" title="Pengaturan Umum"><span class="sub-dot"></span>Pengaturan Umum</a>
        </div></div>
      </div>
      <div class="side-nav-group open" id="lainnyaAdminGroup">
        <button type="button" class="side-nav-group-title" id="lainnyaAdminToggle" title="Lainnya">
          <span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg></span>
          <span class="side-text">Lainnya</span>
          <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg>
        </button>
        <div class="side-subnav"><div>
          <span class="side-subnav-label">Lainnya</span>
          <a href="#" class="side-sub-link" data-tab-link="setelan-notifikasi" title="Notifikasi"><span class="sub-dot"></span>Notifikasi</a>
          <a href="#" class="side-sub-link" data-tab-link="struktur-organisasi" title="Struktur Organisasi"><span class="sub-dot"></span>Struktur Organisasi</a>
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
    @php
      // Badge notifikasi awal (server-rendered, sebelum JS polling jalan).
      // Pengumuman kategori 'keterangan' TETAP ikut dihitung di sini --
      // walau notif jenis itu memang tidak pernah bisa diklik/ditandai
      // dibaca (lihat notification-controls.blade.php: hitungSebagaiUnread()
      // & render()), sehingga andilnya ke angka ini tidak akan pernah
      // berkurang sendiri. Logikanya disamakan persis dengan
      // hitungSebagaiUnread() di sisi client supaya angka awal (sebelum
      // poll pertama) tidak beda.
      $snUnreadNotifCount = auth()->user()?->unreadNotifications->count() ?? 0;
    @endphp
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
            <span class="siberad-notif-badge" style="{{ $snUnreadNotifCount ? '' : 'display:none;' }}">{{ $snUnreadNotifCount > 99 ? '99+' : $snUnreadNotifCount }}</span>
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
      {{-- Validasi form gagal ($errors) ditoast di sini (bukan cuma di 1 tab
           tertentu) karena SEMUA tab admin ada di 1 halaman yang sama, cuma
           ditampilkan/disembunyikan lewat JS (display:none) -- kalau pesan
           errornya ditaruh di dalam satu tab-panel spesifik, pesan itu tidak
           akan pernah kelihatan kalau user submit form dari tab LAIN
           (JS mengembalikan user ke tab terakhir yang dibuka, bukan otomatis
           ke tab yang errornya ada). --}}
      {{-- Error form Tambah/Ubah Pengguna TIDAK di-toast -- ditampilkan inline
           (merah, di bawah field) lewat script re-open modal di bawah. --}}
      @if($errors->any() && ! in_array(old('_form'), ['user_store', 'user_update', 'satuan_store', 'satuan_update'], true))<script>document.addEventListener('DOMContentLoaded',function(){window.siberadShowToast?window.siberadShowToast('error',{!! json_encode($errors->first()) !!}):null});</script>@endif

      {{-- ===== DASHBOARD ===== --}}
      <section class="tab-panel active" data-tab-panel="dashboard">

        <div class="dash-hero">
          <div>
            <div class="dash-hero-eyebrow">{{ $pengaturan->namaSistem() }} // {{ $satuan->kode ?? 'SISTEM' }}</div>
            <h2>{{ $satuan->nama ?? $user->name }}</h2>
            <p>{{ now()->translatedFormat('l, d F Y') }}</p>
          </div>
        </div>

        <div id="adminKpisWrap">@include('siberad.dashboards.partials.admin-kpi-cards', ['stats' => $stats, 'semuaPengguna' => $semuaPengguna, 'semuaSatuan' => $semuaSatuan, 'laporanRekapMentah' => $laporanRekapMentah, 'suratSemuaAdmin' => $suratSemuaAdmin, 'permintaanResetPassword' => $permintaanResetPassword])</div>

        {{-- Panel pembungkus "Statistik Sistem" (1 border besar ngerangkul
             ketiga chart) SENGAJA dihapus atas permintaan user -- dia mau
             chart-chart ini berdiri sendiri-sendiri (gak "nyatu") & posisinya
             niru Pimpinan: "Distribusi Status Laporan" + "Aktivitas 7 Hari
             Terakhir" duduk sejajar 2 kolom (lihat .chart-box-grid di bawah),
             "Pengguna per Kategori Satuan" baris sendiri di atasnya. Class
             "chart-box" (margin-bottom:26px) dipindah ke tiap wrapper baris
             (bukan dihapus) biar spacing antar-baris tetap kepakai walau
             panel pembungkus besarnya hilang. --}}
        @php
          // Data rincian donut "Distribusi Status Laporan" -- SAMA PERSIS
          // bentuknya kayak $pimpStatusDist punya Pimpinan (partials/
          // pimpinan-status-distribusi-list.blade.php, reuse langsung di
          // bawah), sumber angkanya dari $statusLaporanSistem yang udah ada.
          $adminStatusDist = [
            ['label' => 'Disetujui',  'color' => '#22c55e', 'labelColor' => '#22c55e', 'count' => $statusLaporanSistem['disetujui']],
            ['label' => 'Ditolak',    'color' => '#ef4444', 'labelColor' => '#ef4444', 'count' => $statusLaporanSistem['ditolak']],
            ['label' => 'Terlambat',  'color' => '#ff6b6b', 'labelColor' => '#ff6b6b', 'count' => $statusLaporanSistem['terlambat']],
            ['label' => 'Dibatalkan', 'color' => '#c1121f', 'labelColor' => '#e5484d', 'count' => $statusLaporanSistem['dibatalkan']],
          ];
          $adminTotalStatus = collect($adminStatusDist)->sum('count');
        @endphp
        {{-- "Pengguna per Kategori Satuan" SENGAJA dipisah jadi baris sendiri
             (bukan ikut grid 2 kolom di bawah) -- niru posisi Pimpinan, donut
             "Distribusi Status Laporan" + "Tren Aktivitas" duduk sejajar 2
             kolom, chart lain-lain baris terpisah sendiri. --}}
        {{-- Radar (bukan doughnut lagi) -- sesuai referensi gambar yang
             diminta user: chart di kiri, rincian per-kategori (dot+nama+
             jumlah) di kanan, bukan legend titik yang wrap di bawah chart
             kayak sebelumnya. Ikon+warna (ungu #8b5cf6) SENGAJA disamakan
             ke kartu KPI "Total Pengguna" (admin-kpi-cards.blade.php,
             accent #8b5cf6) -- chart ini kan soal pengguna juga, jadi
             ikonnya (sama-sama ikon orang) + warnanya nyambung sama KPI
             itu, bukan warna gold default. Wrapper + header SEKARANG
             .chart-card/.pimp-card-head (bukan .chart-mini/.chart-mini-head
             lagi) -- biar background/border/padding-nya SAMA PERSIS kayak
             kartu "Distribusi Status Laporan"/"Tren Aktivitas" di
             bawahnya (dulu .chart-mini pakai var(--panel-alt), beda dari
             .chart-card yang var(--panel), keliatan beda kartunya). --}}
        <div class="chart-card chart-mini-link chart-box radar-card" data-tab-link="pengguna" role="button" tabindex="0" title="Lihat Daftar Pengguna">
          <div class="pimp-card-head">
            <div class="pimp-card-head-main">
              <span class="pimp-card-ico" style="background:rgba(139,92,246,.14);color:#8b5cf6"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
              <div><h3>Pengguna per Kategori Satuan</h3><p>Sebaran akun berdasarkan kategori.</p></div>
            </div>
          </div>
          <div class="radar-card-body">
            <div class="chart-wrap"><canvas id="chartKategoriSatuan"></canvas></div>
            <div class="radar-legend-list" id="chartKategoriSatuanLegend"></div>
          </div>
        </div>

        <div class="chart-box-grid chart-box">

          {{-- "Distribusi Status Laporan" -- MIRROR PERSIS style+fungsi donut
               "Distribusi Status Laporan" Pimpinan (laporan-pimpinan.blade.php,
               .status-dist-card/.status-donut-wrap/.status-donut-center dkk):
               donut 70% cutout + label % di luar tiap arc (bukan cuma legend
               titik kayak sebelumnya) + teks total di tengah + rincian
               dot/nama/jumlah/bar/persen di bawahnya. Rincian-nya REUSE
               langsung partial pimpinan-status-distribusi-list.blade.php
               (markup-nya generik, sudah dipakai lintas partial lain juga --
               cuma butuh variabel $pimpStatusDist, diisi $adminStatusDist
               punya Admin). --}}
          <div class="chart-card compact status-dist-card">
            <div class="pimp-card-head">
              <div class="pimp-card-head-main">
                <span class="pimp-card-ico" style="background:color-mix(in srgb,#22c55e 15%,transparent);color:#22c55e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"></path></svg></span>
                <div><h3>Distribusi Status Laporan</h3><p>Proporsi status seluruh laporan di sistem.</p></div>
              </div>
            </div>
            <div class="status-donut-wrap"><canvas id="chartStatusLaporan"></canvas><div class="status-donut-center"><span>Total Laporan</span><strong id="adminDonutTotal">{{ $adminTotalStatus }}</strong></div></div>
            <div id="adminStatusBdWrap">@include('siberad.dashboards.partials.pimpinan-status-distribusi-list', ['pimpStatusDist' => $adminStatusDist])</div>
          </div>

          {{-- "Tren Aktivitas" (dulu "Aktivitas 7 Hari Terakhir") -- MIRROR
               style kartu "Tren Aktivitas" Pimpinan (chart-card+pimp-card-head,
               tooltip ikut tema, TOGGLE 7/30 Hari), TAPI TANPA dataset batang
               (Laporan/Surat) punya Pimpinan -- data Admin di sini cuma 1
               seri (jumlah ActivityLog per hari), jadi dari awal udah cocok
               sebagai garis polos + gradient area doang, gak ada yang perlu
               "dihapus battangnya" di data-nya sendiri. Nama diganti dari
               "Aktivitas 7 Hari Terakhir" -- sekarang ada opsi 30 hari juga,
               nama lama jadi gak akurat lagi. Toggle-nya SENGAJA
               e.stopPropagation() di JS (lihat init chart) -- kartu ini
               sendiri clickable (data-tab-link="log-aktivitas"), tanpa itu
               klik tombol toggle bakal ikut ke-anggep klik kartu & pindah
               tab. --}}
          <div class="chart-card pimp-tren-card chart-mini-link" data-tab-link="log-aktivitas" role="button" tabindex="0" title="Lihat Riwayat Aktivitas">
            <div class="pimp-card-head">
              <div class="pimp-card-head-main">
                <span class="pimp-card-ico" style="background:color-mix(in srgb,#6366f1 15%,transparent);color:#6366f1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span>
                <div><h3>Tren Aktivitas</h3><p>Jumlah aksi tercatat per hari, 7 atau 30 hari terakhir.</p></div>
              </div>
              <div class="tren-range-toggle" id="adminTrenRangeToggle">
                <button type="button" class="tren-range-btn active" data-range="7">7 Hari</button>
                <button type="button" class="tren-range-btn" data-range="30">30 Hari</button>
              </div>
            </div>
            <div class="chart-wrap"><canvas id="chartAktivitasMingguan"></canvas></div>
          </div>

        </div>

        <script>
          document.querySelectorAll('.chart-mini-link').forEach(function (el) {
            el.addEventListener('keydown', function (e) {
              if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
            });
          });
        </script>

        {{-- Kartu "Permintaan Ganti Password" & "Aktivitas Terbaru" -- MIRROR
             style+fungsi kartu "Surat Terbaru"/"Kendala Kasansi Terbaru"
             Pimpinan/Satuan (chart-card + pimp-card-head + pimp-activity-list,
             realtime tiap 1 detik + fade-in "is-fresh" pas ada data baru).
             "Lihat Semua" tetap pakai mekanisme data-tab-link Admin yang
             sudah ada (BUKAN href="#section-id" punya Pimpinan/Satuan --
             tab-switcher Admin beda), cuma posisi+style tombolnya udah
             sama dari awal (btn btn-ghost btn-sm di pimp-card-head). --}}
        <div class="admin-terbaru-row">
          <div class="chart-card">
            <div class="pimp-card-head">
              <div class="pimp-card-head-main">
                <span class="pimp-card-ico" style="background:color-mix(in srgb,#f59e0b 15%,transparent);color:#f59e0b"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></span>
                <div><h3>Permintaan Ganti Password</h3><p>5 permintaan terbaru.</p></div>
              </div>
              <a href="#" class="btn btn-ghost btn-sm" data-tab-link="reset-password">Lihat Semua</a>
            </div>
            <div class="pimp-activity-list" id="adminResetPasswordTerbaruList">@include('siberad.dashboards.partials.admin-reset-password-terbaru-list', ['permintaanResetPasswordTerbaru' => $permintaanResetPassword->take(5)])</div>
          </div>

          <div class="chart-card">
            <div class="pimp-card-head">
              <div class="pimp-card-head-main">
                <span class="pimp-card-ico" style="background:color-mix(in srgb,#6366f1 15%,transparent);color:#6366f1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
                <div><h3>Aktivitas Terbaru</h3><p>5 aksi terakhir tercatat.</p></div>
              </div>
              <a href="#" class="btn btn-ghost btn-sm" data-tab-link="log-aktivitas">Lihat Semua</a>
            </div>
            <div class="pimp-activity-list" id="adminAktivitasTerbaruList">@include('siberad.dashboards.partials.admin-aktivitas-terbaru-list', ['logAktivitasTerbaru' => $logAktivitas->take(5)])</div>
          </div>
        </div>

        <style>
          {{-- Kartu KPI Beranda Admin -- MIRROR style+fungsi kartu KPI Beranda
               Pimpinan/Satuan (laporan-pimpinan.blade.php/laporan-role.blade.php,
               ".pimp-kpis"/".pimp-kpi" dkk). Sama alasan kayak CSS Satuan:
               disalin+dipetakan ke token dashboard Admin sendiri
               (var(--panel)/var(--border-soft)/var(--text)/var(--text-muted)),
               BUKAN var(--p-*) (lapisan alias itu cuma ada di halaman
               Pimpinan). Kalau style kartu KPI diubah lagi nanti, salin
               ulang perubahannya ke 3 lokasi (Pimpinan/Satuan/Admin). --}}
          .pimp-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:26px}
          .pimp-kpi{position:relative;overflow:hidden;background:var(--panel);border:1px solid var(--border-soft);border-radius:18px;padding:20px 22px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25);min-width:0}
          .pimp-kpi>:not(.kpi-deco){position:relative;z-index:1}
          .pimp-kpi .kpi-deco{position:absolute;right:0;bottom:0;width:68%;height:60%;z-index:0;color:var(--kpi-accent);fill:currentColor;opacity:.15;pointer-events:none;transform-origin:bottom;transform:scaleY(1)}
          @keyframes kpiDecoGrow{0%{transform:scaleY(0)}55%{transform:scaleY(1.12)}100%{transform:scaleY(1)}}
          .kpi-deco.is-growing{animation:kpiDecoGrow .9s cubic-bezier(.33,1,.68,1) both}
          @media(prefers-reduced-motion:reduce){.kpi-deco.is-growing{animation:none}}
          .pimp-kpi .kpi-top{display:flex;align-items:center;gap:12px}
          .pimp-kpi .kpi-badge{flex:0 0 auto;width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--kpi-accent) 15%,transparent);color:var(--kpi-accent)}
          .pimp-kpi .kpi-badge svg{width:22px;height:22px}
          .pimp-kpi .kpi-eyebrow{font-size:11px;font-weight:600;letter-spacing:.09em;text-transform:uppercase;color:var(--text-muted);line-height:1.3}
          .pimp-kpi .kpi-eyebrow b{font-weight:800;color:var(--text)}
          .pimp-kpi .kpi-value{font-family:var(--display);font-size:38px;font-weight:800;line-height:1;margin-top:16px;color:var(--text)}
          .pimp-kpi .kpi-desc{font-size:13px;color:var(--text-muted);margin-top:8px}
          .pimp-kpi .kpi-trend{display:flex;align-items:center;gap:5px;margin-top:12px;font-family:var(--mono);font-size:13px;font-weight:800;color:var(--text-muted)}
          .pimp-kpi .kpi-trend svg{width:14px;height:14px;display:none}
          .pimp-kpi .kpi-trend.is-up{color:var(--kpi-accent)}
          .pimp-kpi .kpi-trend.is-up svg{display:block}
          .pimp-kpi .kpi-trend-cap{font-size:11px;color:var(--text-muted);margin-top:2px}
          .admin-kpis{grid-template-columns:repeat(5,minmax(0,1fr))}
          @media(max-width:1200px){.admin-kpis{grid-template-columns:repeat(3,1fr)}}
          @media(max-width:980px){.admin-kpis{grid-template-columns:repeat(2,1fr)}}
          @media(max-width:560px){.admin-kpis{grid-template-columns:1fr}}

          .chart-mini-link{cursor:pointer;}
          .chart-mini-link:hover,.chart-mini-link:focus-visible{border-color:var(--gold-bright);box-shadow:0 6px 18px rgba(0,0,0,.18);}
          .chart-mini-link:focus-visible{outline:2px solid var(--gold-bright);outline-offset:2px;}
          .chart-mini-head{display:flex;align-items:flex-start;gap:11px;}
          .chart-mini-icon{width:28px;height:28px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--gold-dim);color:var(--gold-bright);}
          .chart-mini-icon svg{width:15px;height:15px;}
          .chart-mini-icon.amber{background:var(--amber-dim);color:var(--amber);}
          .chart-mini-icon.green{background:var(--green-dim);color:var(--green-bright);}
          .chart-mini-icon.blue{background:rgba(99,102,241,.14);color:#6366f1;}

          {{-- Kartu "Distribusi Status Laporan" (donut) & "Aktivitas 7 Hari
               Terakhir" (chart-card, bukan chart-mini lagi) -- MIRROR
               style+fungsi kartu "Distribusi Status Laporan"/"Tren Aktivitas"
               Pimpinan (laporan-pimpinan.blade.php, .status-donut-wrap/
               .status-bd* dkk). Sama alasan kayak blok KPI/Terbaru di atas:
               CSS disalin+dipetakan ke token dashboard Admin sendiri
               (var(--panel)/var(--border-soft)/var(--text)/var(--text-muted)/
               var(--panel-alt)), BUKAN var(--p-*). .chart-wrap di dalam
               .chart-card SENGAJA dikasih tinggi sendiri (230px, beda dari
               178px punya .chart-mini .chart-wrap) -- kartu chart-card lebih
               besar, canvas-nya juga butuh lebih tinggi biar proporsinya
               enak dilihat. --}}
          .chart-card .chart-wrap{height:230px;}
          {{-- .pimp-tren-card bikin kartu "Tren Aktivitas" ikut
               meregang setinggi kartu donut di sebelahnya (sibling di grid
               2 kolom yang sama) -- MIRROR PERSIS .pimp-tren-card Pimpinan,
               cuma target child-nya .chart-wrap (bukan .chart-box, lihat
               komentar penamaan di atas). --}}
          .pimp-tren-card{display:flex;flex-direction:column;height:100%;}
          .pimp-tren-card .chart-wrap{flex:1;min-height:0;height:auto;}
          {{-- Toggle "7 Hari"/"30 Hari" kartu "Tren Aktivitas" -- MIRROR
               PERSIS .tren-range-toggle/.tren-range-btn Pimpinan
               (laporan-pimpinan.blade.php), token dipetakan ke punya Admin
               (var(--p-surface-2)->var(--panel-alt), var(--p-border)->
               var(--border-soft), var(--p-muted)->var(--text-muted)) --
               warna aktif TETAP literal #6366f1 (sama kayak Pimpinan, juga
               sama kayak warna ikon kartu ini). --}}
          .tren-range-toggle{display:flex;gap:3px;background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:999px;padding:3px;flex:0 0 auto;}
          .tren-range-btn{border:0;background:transparent;color:var(--text-muted);font-size:11px;font-weight:700;padding:6px 12px;border-radius:999px;cursor:pointer;white-space:nowrap;font-family:inherit;}
          .tren-range-btn.active{background:#6366f1;color:#fff;}
          .status-donut-wrap{position:relative;width:100%;height:230px;margin:6px 0 2px;}
          .status-donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;pointer-events:none;}
          .status-donut-center span{font-size:11px;color:var(--text-muted);}
          .status-donut-center strong{font-family:var(--mono);font-size:30px;font-weight:700;color:var(--text);line-height:1;}
          .status-bd{display:flex;flex-direction:column;gap:12px;margin-top:14px;padding-top:16px;border-top:1px solid var(--border-soft);}
          .status-bd-row{display:grid;grid-template-columns:10px 1fr auto minmax(90px,1.6fr) auto;align-items:center;gap:10px;}
          .status-bd-row.is-zero{opacity:.5;}
          .status-bd-dot{width:10px;height:10px;border-radius:50%;}
          .status-bd-name{font-size:12.5px;font-weight:700;color:var(--text);white-space:nowrap;}
          .status-bd-count{font-family:var(--mono);font-size:12.5px;font-weight:700;color:var(--text);text-align:right;}
          .status-bd-pct{font-family:var(--mono);font-size:10.5px;font-weight:800;padding:3px 8px;border-radius:999px;white-space:nowrap;text-align:center;}
          .status-bd-bar{position:relative;height:9px;border-radius:999px;background:var(--panel-alt);border:1px solid var(--border-soft);overflow:hidden;}
          .status-bd-bar-fill{position:absolute;left:0;top:0;bottom:0;border-radius:999px;transition:width .9s cubic-bezier(.22,1,.36,1);}
          @media(prefers-reduced-motion:reduce){.status-bd-bar-fill{transition:none;}}

          {{-- Kartu "Permintaan Ganti Password" & "Aktivitas Terbaru" -- MIRROR
               style+fungsi kartu "Surat Terbaru"/"Kendala Kasansi Terbaru"
               Pimpinan/Satuan (laporan-pimpinan.blade.php/laporan-role.blade.php,
               ".chart-card"/".pimp-card-head"/".pimp-activity-list" dkk).
               Sama alasan kayak blok KPI di atas: CSS disalin+dipetakan ke
               token dashboard Admin sendiri, BUKAN var(--p-*). Kalau
               style-nya diubah lagi di Pimpinan/Satuan nanti, salin ulang
               ke 3 lokasi (Pimpinan/Satuan/Admin). --}}
          .admin-terbaru-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:22px}
          @media(max-width:980px){.admin-terbaru-row{grid-template-columns:1fr}}
          .chart-card{background:var(--panel);border:1px solid var(--border-soft);border-radius:16px;padding:18px 20px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25);min-width:0}
          .pimp-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap}
          .pimp-card-head-main{display:flex;align-items:flex-start;gap:12px;min-width:0}
          .pimp-card-head>.btn{align-self:center}
          .pimp-card-ico{flex:0 0 auto;width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center}
          .pimp-card-ico svg{width:19px;height:19px}
          .pimp-card-head h3{font-family:var(--display);font-size:16px;margin:0;color:var(--text)}
          .pimp-card-head p{font-size:11px;color:var(--text-muted);margin:4px 0 0;line-height:1.5}
          .pimp-activity-list{display:flex;flex-direction:column}
          .pimp-activity-item{display:flex;align-items:center;gap:12px;padding:13px 0}
          .pimp-activity-item:first-child{padding-top:0}
          .pimp-activity-item:last-child{padding-bottom:0}
          .pimp-activity-item:not(:last-child){border-bottom:1px solid var(--border-soft)}
          .pimp-activity-body{flex:1;min-width:0}
          .pimp-activity-title{font-size:13px;font-weight:700;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
          .pimp-activity-sub{font-size:11px;color:var(--text-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
          .pimp-activity-item .status-pill{flex:0 0 auto;white-space:nowrap}
          {{-- :before dot dihapus atas permintaan user -- pill di sini cuma
               teks polos, gak pakai titik warna di depannya kayak
               status-pill Pimpinan/Satuan. --}}
          .status-pill{display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:10px;font-weight:800;border:1px solid transparent}
          .status-pill.wait{color:var(--amber);background:var(--amber-dim);border-color:rgba(224,168,58,.35)}
          .status-pill.ok{color:var(--success-bright);background:var(--success-dim);border-color:rgba(63,194,125,.28)}
          .status-pill.bad{color:var(--red);background:var(--red-dim);border-color:rgba(198,40,40,.3)}
          {{-- Dipakai khusus "Menunggu" kartu Permintaan Ganti Password atas
               permintaan user -- warna sama persis .status-pill.blue/
               .deadline-pill.blue Pimpinan (#2476ad), belum ada di CSS Admin
               sebelumnya karena Admin cuma punya 3 state (wait/ok/bad). --}}
          .status-pill.blue{color:#2476ad;background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.25)}
          .kcard-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:48px 20px;text-align:center;color:var(--text-muted)}
          .kcard-empty-title{font-size:14px;font-weight:700;color:var(--text-muted)}
          .kcard-empty-sub{font-size:12px;color:var(--text-muted);line-height:1.5;max-width:320px;opacity:.7}
          .pimp-empty-compact{padding:30px 16px;gap:8px}
          .pimp-empty-compact .kcard-empty-title{font-size:12.5px}
          .pimp-empty-compact .kcard-empty-sub{font-size:11px;max-width:260px}
          {{-- Animasi "baris/item baru" -- MIRROR PERSIS animasi yang sama di
               Beranda Pimpinan/Satuan (fungsi JS animateTerbaruRows). --}}
          @keyframes terbaruFreshIn{0%{opacity:0;background-color:rgba(59,130,246,.14)}100%{opacity:1;background-color:transparent}}
          .pimp-activity-item.is-fresh{animation:terbaruFreshIn .8s ease both}
          @media(prefers-reduced-motion:reduce){.pimp-activity-item.is-fresh{animation:none}}
        </style>
      </section>

      {{-- ===== KELOLA PENGGUNA ===== --}}
      <section class="tab-panel" data-tab-panel="pengguna">
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
              <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Satuan</th><th>Aksi</th></tr></thead>
              <tbody>
                @foreach($semuaPengguna as $p)@include('siberad.dashboards.partials.pengguna-row', ['p' => $p, 'authUserId' => $user->id])@endforeach
              </tbody>
            </table>
          </div>
        </div>
      </section>

      @php
        // Disiapkan di PHP -- @json() dengan map() + mb_strtolower(trim())
        // bersarang bikin parser direktif Blade salah hitung kurung.
        $akunListForDup = $semuaPengguna->map(fn ($u) => [
          'id' => $u->id,
          'username' => mb_strtolower(trim((string) $u->username)),
          'email' => mb_strtolower(trim((string) $u->email)),
        ])->values();
        $satuanListForDup = $semuaSatuan->map(fn ($s) => [
          'id' => $s->id,
          'kode' => mb_strtolower(trim((string) $s->kode)),
        ])->values();
        // Opsi combobox "Satuan" di modal Tambah/Ubah Pengguna -- teks tampil
        // = "Nama (KODE)" pakai kode satuan APA ADANYA dari Data Satuan (dulu
        // URDAL & POKANALIS dikecualikan; sekarang semua konsisten). id string
        // biar cocok dibanding dgn value hidden. @php block wajib (bukan inline
        // @json) -- lihat [[project_blade_at_word_comment_gotcha]].
        $penggunaSatuanOpts = $semuaSatuan->map(fn ($s) => [
          'id' => (string) $s->id,
          'name' => $s->nama.' ('.$s->kode.')',
          'kode' => (string) $s->kode,
        ])->values();
      @endphp
      <script>
        // Daftar akun & satuan yang sudah ada -> deteksi Username/NRP / Email /
        // Kode Satuan KEMBAR secara LIVE saat diketik (bukan cuma pas submit).
        // Rule unique:* di server tetap jadi pengaman terakhir + fallback
        // re-open modal kalau ada balapan dgn tab / admin lain.
        window.__siberadAkunList = @json($akunListForDup);
        window.__siberadSatuanList = @json($satuanListForDup);
        window.__penggunaSatuanOptions = @json($penggunaSatuanOpts);
        function siberadBindDupCheck(field, kind, getIgnoreId) {
          if (!field) return;
          var msg = field.nextElementSibling;
          if (!msg || !msg.classList.contains('profile-field-error')) {
            msg = document.createElement('span');
            msg.className = 'profile-field-error';
            msg.style.display = 'none';
            field.insertAdjacentElement('afterend', msg);
          }
          var label = kind === 'email' ? 'Email ini sudah terdaftar di akun lain.'
            : kind === 'kode' ? 'Kode satuan ini sudah dipakai satuan lain.'
            : 'Username / NRP ini sudah dipakai akun lain.';
          function check() {
            var val = (field.value || '').trim().toLowerCase();
            var list = kind === 'kode' ? (window.__siberadSatuanList || []) : (window.__siberadAkunList || []);
            var ignoreId = getIgnoreId ? String(getIgnoreId() || '') : '';
            var dup = !!val && list.some(function (a) {
              return String(a[kind] || '') === val && String(a.id) !== ignoreId;
            });
            if (dup) {
              field.classList.add('field-invalid');
              field.setCustomValidity(label);   // blokir submit juga
              msg.textContent = label;
              msg.style.display = 'flex';
            } else {
              field.setCustomValidity('');
              if (msg.textContent === label) { field.classList.remove('field-invalid'); msg.style.display = 'none'; }
            }
          }
          field.addEventListener('input', check);
          field.addEventListener('blur', check);
        }

        // Combobox "Satuan" -- input teks + saran melayang, niru "Tujuan" di
        // Buat Surat. Daftar saran numpang .styled-select-menu (di-append ke
        // <body>). Hidden #<hiddenId> nyimpen id satuan yg BENERAN dipilih dari
        // saran; ngetik tanpa milih = hidden dikosongin -> submit diblok.
        function siberadBindSatuanCombobox(wrapId, searchId, hiddenId) {
          var wrap = document.getElementById(wrapId);
          var search = document.getElementById(searchId);
          var hidden = document.getElementById(hiddenId);
          if (!wrap || !search || !hidden) return;
          // SENGAJA gak disimpan sekali di variabel lokal (dulu begitu, jadi
          // "beku" di daftar Satuan versi awal load halaman) -- combobox ini
          // dibikin cuma sekali per modal saat page load, tapi dipakai
          // berkali-kali (tiap dropdown dibuka). Kalau Satuan
          // ditambah/ubah/hapus SETELAH itu, window.__penggunaSatuanOptions
          // sendiri sudah ke-refresh (lihat siberadSubmitSatuanAjax() &
          // handler hapus Satuan), tapi combobox tetap nunjukin daftar lama
          // kalau opsinya cuma dibaca sekali di sini. getOptions() dipanggil
          // ULANG tiap render()/​__setSatuan(), jadi selalu ambil versi
          // terbaru dari window.
          function getOptions() { return window.__penggunaSatuanOptions || []; }
          var menu = document.createElement('div');
          menu.className = 'styled-select-menu';
          var inner = document.createElement('div');
          inner.className = 'styled-select-menu-inner';
          menu.appendChild(inner);
          document.body.appendChild(menu);
          var GUARD_MSG = 'Satuan wajib dipilih dari daftar.';
          var errMsg = search.nextElementSibling;
          if (!errMsg || !errMsg.classList.contains('profile-field-error')) {
            errMsg = document.createElement('span');
            errMsg.className = 'profile-field-error';
            errMsg.style.display = 'none';
            search.insertAdjacentElement('afterend', errMsg);
          }
          function setInvalid(on) {
            search.classList.toggle('field-invalid', !!on);
            if (on) { errMsg.textContent = GUARD_MSG; errMsg.style.display = 'flex'; }
            else if (errMsg.textContent === GUARD_MSG) { errMsg.style.display = 'none'; }
          }
          function render() {
            inner.innerHTML = '';
            var q = search.value.trim().toLowerCase();
            var filtered = getOptions().filter(function (o) {
              return !q || o.name.toLowerCase().indexOf(q) > -1 || (o.kode && o.kode.toLowerCase().indexOf(q) > -1);
            });
            if (!filtered.length) {
              var empty = document.createElement('div');
              empty.className = 'styled-select-option';
              empty.style.cssText = 'cursor:default;opacity:.55';
              empty.textContent = 'Tidak ada satuan yang cocok.';
              inner.appendChild(empty);
              return;
            }
            filtered.forEach(function (o) {
              var item = document.createElement('button');
              item.type = 'button';
              item.className = 'styled-select-option' + (hidden.value === o.id ? ' active' : '');
              var txt = document.createElement('span');
              txt.className = 'ss-opt-text';
              txt.textContent = o.name;
              item.appendChild(txt);
              item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                hidden.value = o.id;
                search.value = o.name;
                setInvalid(false);
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
                close();
              });
              inner.appendChild(item);
            });
          }
          function position() {
            var r = search.getBoundingClientRect();
            menu.style.minWidth = r.width + 'px';
            menu.style.left = r.left + 'px';
            menu.style.top = (r.bottom + 6) + 'px';
            var mr = menu.getBoundingClientRect();
            var vw = window.innerWidth;
            var left = r.left;
            if (mr.right > vw - 8) left = Math.max(8, vw - 8 - mr.width);
            menu.style.left = left + 'px';
          }
          function open() { render(); position(); menu.classList.add('open'); }
          function close() { menu.classList.remove('open'); }
          search.addEventListener('input', function () {
            hidden.value = '';
            if (!search.value.trim()) { close(); return; }
            open();
          });
          search.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
              var first = inner.querySelector('button.styled-select-option');
              if (first) { e.preventDefault(); first.dispatchEvent(new Event('mousedown')); }
            } else if (e.key === 'Escape') { close(); }
          });
          document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target) && !menu.contains(e.target)) close();
          });
          window.addEventListener('scroll', function (e) {
            if (menu.contains(e.target)) return;
            if (menu.classList.contains('open')) close();
          }, true);
          window.addEventListener('resize', function () { if (menu.classList.contains('open')) position(); });
          var form = search.closest('form');
          if (form) {
            form.addEventListener('submit', function (e) {
              if (hidden.value) return;
              e.preventDefault();
              e.stopImmediatePropagation();
              setInvalid(true);
              search.focus();
            }, true);
          }
          // Dipakai bukaUbahPengguna / reopen script buat set teks tampilannya.
          wrap.__setSatuan = function (id) {
            var o = getOptions().filter(function (x) { return x.id === String(id || ''); })[0];
            hidden.value = o ? o.id : '';
            search.value = o ? o.name : '';
            setInvalid(false);
          };
        }
        siberadBindSatuanCombobox('uSatuanCombobox', 'uSatuanSearch', 'uSatuan');
        siberadBindSatuanCombobox('upSatuanCombobox', 'upSatuanSearch', 'upSatuan');

        // Submit Tambah/Ubah Pengguna via AJAX -> modal TETAP kebuka (nggak
        // reload). Sukses: server balikin SELURUH <tbody> #tblPengguna yang
        // sudah terurut jenjang organisasi -> timpa langsung, highlight baris
        // baru/berubah, reset form (khusus Tambah) + toast + refresh filter.
        // Pola & penanganan error identik dengan siberadSubmitSatuanAjax.
        function siberadSubmitPenggunaAjax(form, isEdit) {
          var yesBtn = document.getElementById(isEdit ? 'ubahPenggunaKonfirmasiYa' : 'tambahPenggunaKonfirmasiYa');
          if (yesBtn) yesBtn.disabled = true;
          fetch(form.action, {
            method: 'POST', body: new FormData(form), credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
          }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (d) { return { status: r.status, data: d }; });
          }).then(function (res) {
            if (res.status === 200 && res.data && res.data.ok) {
              var tbody = document.querySelector('#tblPengguna tbody');
              if (tbody && typeof res.data.rows_html === 'string') {
                tbody.innerHTML = res.data.rows_html;
                var affected = tbody.querySelector('tr[data-user-id="' + res.data.id + '"]');
                if (affected) affected.classList.add(isEdit ? 'siberad-row-updated' : 'siberad-row-in');
                if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblPengguna');
              }
              // Kebalikan dari sinkronisasi Satuan->Pengguna: tambah/ubah
              // (termasuk pindah satuan)/hapus Pengguna bikin kolom "Jumlah
              // Pengguna" di tab Data Satuan basi kalau cuma tbody Pengguna
              // yang ditimpa (satu halaman yang sama, beda tab, bukan
              // reload). Server (UserController::tableJson()) sudah ikut
              // ngirim tbody Satuan terbarunya.
              var tblSatuan = document.querySelector('#tblSatuan tbody');
              if (tblSatuan && typeof res.data.satuan_rows_html === 'string') {
                tblSatuan.innerHTML = res.data.satuan_rows_html;
                if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblSatuan');
              }
              if (!isEdit) {
                form.reset();
                // Combobox Satuan pakai input teks biasa -> form.reset() sudah
                // ngosongin #uSatuanSearch & hidden #uSatuan, nggak perlu sync
                // label kayak styled-select.
              }
              form.querySelectorAll('.field-invalid').forEach(function (el) { el.classList.remove('field-invalid'); });
              form.querySelectorAll('.profile-field-error').forEach(function (el) { el.style.display = 'none'; });
              window.siberadShowToast && window.siberadShowToast('success', res.data.message || 'Berhasil disimpan.');
            } else if (res.status === 422 && res.data && res.data.errors) {
              var map = isEdit
                ? { name: 'upNama', username: 'upUsername', email: 'upEmail', satuan_id: 'upSatuanSearch', password: 'upPassword' }
                : { name: 'uNama', username: 'uUsername', email: 'uEmail', satuan_id: 'uSatuanSearch', password: 'uPassword' };
              Object.keys(res.data.errors).forEach(function (f) {
                var el = document.getElementById(map[f]);
                if (!el) return;
                el.classList.add('field-invalid');
                var msg = el.nextElementSibling;
                if (!msg || !msg.classList.contains('profile-field-error')) {
                  msg = document.createElement('span'); msg.className = 'profile-field-error';
                  el.insertAdjacentElement('afterend', msg);
                }
                msg.textContent = res.data.errors[f][0];
                msg.style.display = 'flex';
              });
            } else if (res.status === 401 && window.siberadTampilkanSesiBerakhir) {
              window.siberadTampilkanSesiBerakhir();
            } else {
              window.siberadShowToast && window.siberadShowToast('error', (res.data && res.data.message) || 'Gagal menyimpan data pengguna.');
            }
          }).catch(function () {
            window.siberadShowToast && window.siberadShowToast('error', 'Gagal terhubung ke server, coba lagi.');
          }).finally(function () {
            if (yesBtn) yesBtn.disabled = false;
          });
        }

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
                msg.textContent = field.validity.customError
                  ? field.validationMessage
                  : field.validity.typeMismatch
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

          // Deteksi Username/NRP & Email KEMBAR secara LIVE saat diketik.
          siberadBindDupCheck(document.getElementById('uUsername'), 'username', null);
          siberadBindDupCheck(document.getElementById('uEmail'), 'email', null);

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
              siberadSubmitPenggunaAjax(form, false);
            });
            document.getElementById('tambahPenggunaKonfirmasiBatal')?.addEventListener('click', closeKonfirm);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && konfirmOverlay.classList.contains('open')) closeKonfirm(); });
          }
        })();

        window.bukaUbahPengguna = function (btn) {
          document.getElementById('ubahPenggunaForm').action = btn.dataset.action;
          document.getElementById('upUid').value = btn.dataset.userId || '';
          document.getElementById('upNama').value = btn.dataset.name || '';
          document.getElementById('upUsername').value = btn.dataset.username || '';
          document.getElementById('upEmail').value = btn.dataset.email || '';
          var _upCb = document.getElementById('upSatuanCombobox');
          if (_upCb && _upCb.__setSatuan) _upCb.__setSatuan(btn.dataset.satuanId || '');
          else document.getElementById('upSatuan').value = btn.dataset.satuanId || '';
          document.getElementById('upPassword').value = '';
          // Bersihkan sisa penanda error merah dari sesi Ubah sebelumnya.
          document.querySelectorAll('#ubahPenggunaForm .field-invalid').forEach(function (el) { el.classList.remove('field-invalid'); });
          document.querySelectorAll('#ubahPenggunaForm .profile-field-error').forEach(function (el) { el.style.display = 'none'; });
          document.getElementById('ubahPenggunaModal').classList.add('open');
        };

        window.bukaHapusPengguna = function (btn) {
          document.getElementById('formHapusPengguna').action = btn.dataset.action;
          document.getElementById('hapusPenggunaNama').textContent = btn.dataset.nama || 'ini';
          document.getElementById('hapusPenggunaOverlay')?.classList.add('open');
        };
        document.getElementById('hapusPenggunaBatal')?.addEventListener('click', () => document.getElementById('hapusPenggunaOverlay')?.classList.remove('open'));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') document.getElementById('hapusPenggunaOverlay')?.classList.remove('open'); });

        // Hapus pengguna via AJAX -- baris tabel dibuang tanpa reload halaman,
        // sama seperti Tambah/Ubah. Tombolnya tetap type="submit" jadi kalau
        // JS mati masih jalan (reload biasa).
        (function () {
          var formHapus = document.getElementById('formHapusPengguna');
          var overlay = document.getElementById('hapusPenggunaOverlay');
          var yesBtn = document.getElementById('hapusPenggunaYa');
          if (!formHapus) return;
          formHapus.addEventListener('submit', function (e) {
            e.preventDefault();
            if (yesBtn) yesBtn.disabled = true;
            fetch(formHapus.action, {
              method: 'POST', body: new FormData(formHapus), credentials: 'same-origin',
              headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (r) {
              return r.json().catch(function () { return {}; }).then(function (d) { return { status: r.status, data: d }; });
            }).then(function (res) {
              if (res.status === 200 && res.data && res.data.ok) {
                var row = document.querySelector('#tblPengguna tbody tr[data-user-id="' + res.data.id + '"]');
                if (row) {
                  row.classList.add('siberad-row-out');
                  setTimeout(function () {
                    row.remove();
                    if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblPengguna');
                  }, 260);
                }
                overlay && overlay.classList.remove('open');
                // Hapus akun bikin "Jumlah Pengguna" satuan asalnya berkurang
                // 1 di tab Data Satuan -- server sudah ikut ngirim tbody
                // terbarunya.
                var tblSatuanHapus = document.querySelector('#tblSatuan tbody');
                if (tblSatuanHapus && typeof res.data.satuan_rows_html === 'string') {
                  tblSatuanHapus.innerHTML = res.data.satuan_rows_html;
                  if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblSatuan');
                }
                window.siberadShowToast && window.siberadShowToast('success', res.data.message || 'Akun berhasil dihapus.');
              } else if (res.status === 401 && window.siberadTampilkanSesiBerakhir) {
                window.siberadTampilkanSesiBerakhir();
              } else {
                overlay && overlay.classList.remove('open');
                window.siberadShowToast && window.siberadShowToast('error', (res.data && res.data.message) || 'Gagal menghapus akun pengguna.');
              }
            }).catch(function () {
              window.siberadShowToast && window.siberadShowToast('error', 'Gagal terhubung ke server, coba lagi.');
            }).finally(function () {
              if (yesBtn) yesBtn.disabled = false;
            });
          });
        })();

        window.bukaHapusBackup = function (btn) {
          document.getElementById('formHapusBackup').action = btn.dataset.action;
          document.getElementById('hapusBackupNama').textContent = btn.dataset.nama || 'ini';
          document.getElementById('hapusBackupOverlay')?.classList.add('open');
        };
        document.getElementById('hapusBackupBatal')?.addEventListener('click', () => document.getElementById('hapusBackupOverlay')?.classList.remove('open'));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') document.getElementById('hapusBackupOverlay')?.classList.remove('open'); });

        // Dipakai tombol "Hapus BG" (statis) dan "Hapus Logo" (disuntik oleh
        // admin-landing-editor.js) di tab Pengaturan Umum -> Beranda.
        window.bukaHapusLandingGambar = function (btn) {
          document.getElementById('formHapusLandingGambar').action = btn.dataset.action;
          document.getElementById('hapusLandingGambarNama').textContent = btn.dataset.nama || 'Gambar ini';
          document.getElementById('hapusLandingGambarOverlay')?.classList.add('open');
        };
        document.getElementById('hapusLandingGambarBatal')?.addEventListener('click', () => document.getElementById('hapusLandingGambarOverlay')?.classList.remove('open'));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') document.getElementById('hapusLandingGambarOverlay')?.classList.remove('open'); });
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
                msg.textContent = field.validity.customError
                  ? field.validationMessage
                  : field.validity.typeMismatch
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

          // Deteksi Username/NRP & Email KEMBAR secara LIVE (kecuali akun yang
          // sedang diedit itu sendiri -> pakai #upUid sebagai id yang diabaikan).
          siberadBindDupCheck(document.getElementById('upUsername'), 'username', function () { return document.getElementById('upUid').value; });
          siberadBindDupCheck(document.getElementById('upEmail'), 'email', function () { return document.getElementById('upUid').value; });

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
              siberadSubmitPenggunaAjax(form, true);
            });
            document.getElementById('ubahPenggunaKonfirmasiBatal')?.addEventListener('click', closeKonfirm);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && konfirmOverlay.classList.contains('open')) closeKonfirm(); });
          }
        })();
      </script>

      @php $reopenForm = old('_form'); @endphp
      @if($errors->any() && in_array($reopenForm, ['user_store', 'user_update', 'satuan_store', 'satuan_update'], true))
      @php
        // Disiapkan di PHP -- @json() dgn array literal multi-baris + old()
        // bersarang bikin parser direktif Blade salah hitung kurung.
        $reopenIsUpdate = str_ends_with($reopenForm, '_update');
        $reopenIsSatuan = str_starts_with($reopenForm, 'satuan_');
        $reopenOldVals = $reopenIsSatuan
          ? ['kode' => old('kode'), 'nama' => old('nama'), 'kategori' => old('kategori'), 'deskripsi' => old('deskripsi')]
          : ['name' => old('name'), 'username' => old('username'), 'email' => old('email'), 'satuan_id' => old('satuan_id')];
        $reopenErrs = $errors->messages();
        $reopenUid = old('_uid');
        $reopenBase = $reopenIsSatuan ? url('admin/satuan') : url('admin/users');
      @endphp
      {{-- Validasi Tambah/Ubah Pengguna atau Satuan gagal (mis. Username/NRP,
           Email, atau Kode Satuan kembar) -> buka ulang modal-nya, isi ulang
           nilai lama, tampilkan pesan MERAH inline di bawah field yang salah
           (bukan toast). Reuse .field-invalid / .profile-field-error + listener
           input/change dari IIFE modal (error hilang begitu diketik ulang). --}}
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          var isUpdate = {{ $reopenIsUpdate ? 'true' : 'false' }};
          var isSatuan = {{ $reopenIsSatuan ? 'true' : 'false' }};
          var cfg = isSatuan
            ? (isUpdate
                ? { modal: 'ubahSatuanModal', form: 'ubahSatuanForm', uidField: 'usSatuanId', map: { kode: 'usKode', nama: 'usNama', kategori: 'usKategori', deskripsi: 'usDeskripsi' } }
                : { modal: 'tambahSatuanModal', map: { kode: 'sKode', nama: 'sNama', kategori: 'sKategori', deskripsi: 'sDeskripsi' } })
            : (isUpdate
                ? { modal: 'ubahPenggunaModal', form: 'ubahPenggunaForm', uidField: 'upUid', map: { name: 'upNama', username: 'upUsername', email: 'upEmail', satuan_id: 'upSatuan' } }
                : { modal: 'tambahPenggunaModal', map: { name: 'uNama', username: 'uUsername', email: 'uEmail', satuan_id: 'uSatuan' } });
          var modal = document.getElementById(cfg.modal);
          if (!modal) return;
          var oldVals = @json($reopenOldVals);
          @if($reopenIsUpdate)
          var uid = @json($reopenUid);
          var uForm = cfg.form ? document.getElementById(cfg.form) : null;
          if (uForm && uid) {
            uForm.action = @json($reopenBase) + '/' + uid;
            var uidField = cfg.uidField ? document.getElementById(cfg.uidField) : null;
            if (uidField) uidField.value = uid;
          }
          @endif
          Object.keys(cfg.map).forEach(function (f) {
            var el = document.getElementById(cfg.map[f]);
            if (el && oldVals[f] != null && oldVals[f] !== '') el.value = oldVals[f];
          });
          @if(! $reopenIsSatuan)
          // Field Satuan sekarang combobox -- set teks tampilannya juga, bukan
          // cuma hidden #uSatuan/#upSatuan yang barusan di-set loop di atas.
          var _sCb = document.getElementById((isUpdate ? 'up' : 'u') + 'SatuanCombobox');
          if (_sCb && _sCb.__setSatuan && oldVals.satuan_id) _sCb.__setSatuan(oldVals.satuan_id);
          @endif
          var errs = @json($reopenErrs);
          Object.keys(errs).forEach(function (f) {
            var el = document.getElementById(cfg.map[f]);
            if (!el) return;
            el.classList.add('field-invalid');
            var msg = el.nextElementSibling;
            if (!msg || !msg.classList.contains('profile-field-error')) {
              msg = document.createElement('span');
              msg.className = 'profile-field-error';
              el.insertAdjacentElement('afterend', msg);
              el.addEventListener('input', function () { el.classList.remove('field-invalid'); msg.style.display = 'none'; });
              el.addEventListener('change', function () { el.classList.remove('field-invalid'); msg.style.display = 'none'; });
            }
            msg.textContent = errs[f][0];
            msg.style.display = 'flex';
          });
          modal.classList.add('open');
          var firstBad = Object.keys(errs).map(function (f) { return document.getElementById(cfg.map[f]); }).filter(Boolean)[0];
          if (firstBad) setTimeout(function () { try { firstBad.focus(); } catch (e) {} }, 60);
        });
      </script>
      @endif

      {{-- ===== MANAJEMEN SATUAN ===== --}}
      <section class="tab-panel" data-tab-panel="satlak">

        <div class="panel">
          <div class="panel-head">
            <div><h2>Data Satuan</h2><p>Kelola daftar satuan/Satlak yang terdaftar di {{ $pengaturan?->namaSistem() ?? "SIBERAD" }}. Satuan yang masih punya pengguna tidak bisa dihapus.</p></div>
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
                @forelse($semuaSatuan as $s)@include('siberad.dashboards.partials.satuan-row', ['s' => $s])@empty
                <tr><td colspan="5"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada data satuan</div></div></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
      <script>
        // Submit Tambah/Ubah Satuan via AJAX -> modal TETAP kebuka (nggak
        // reload). Sukses: sisipkan (tambah) / replaceWith (ubah) <tr> yang
        // dirender ulang server, reset form + toast, refresh filter tabel.
        function siberadSubmitSatuanAjax(form, isEdit) {
          var yesBtn = document.getElementById(isEdit ? 'ubahSatuanKonfirmasiYa' : 'tambahSatuanKonfirmasiYa');
          if (yesBtn) yesBtn.disabled = true;
          fetch(form.action, {
            method: 'POST', body: new FormData(form), credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
          }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (d) { return { status: r.status, data: d }; });
          }).then(function (res) {
            if (res.status === 200 && res.data && res.data.ok) {
              // Server balikin SELURUH tbody yang sudah terurut (kategori ->
              // urutan-dalam-kategori). Timpa langsung biar baris baru/berubah
              // mendarat di posisi yang benar, bukan dipaksa ke paling atas.
              var tbody = document.querySelector('#tblSatuan tbody');
              if (tbody && typeof res.data.rows_html === 'string') {
                tbody.innerHTML = res.data.rows_html;
                var affected = tbody.querySelector('tr[data-satuan-id="' + res.data.id + '"]');
                if (affected) affected.classList.add(isEdit ? 'siberad-row-updated' : 'siberad-row-in');
                if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblSatuan');
              }
              // Tab "Daftar Pengguna" ada di halaman yang SAMA (cuma beda
              // tab, bukan reload) -- kolom "Satuan" di tabelnya & opsi
              // combobox Satuan di modal Tambah/Ubah Pengguna jadi basi
              // kalau nama/kode satuan berubah tapi cuma tbody Satuan yang
              // ditimpa. Server (SatuanController::tableJson()) sudah ikut
              // ngirim data terbarunya -- tinggal disimpan di sini.
              var tblPengguna = document.querySelector('#tblPengguna tbody');
              if (tblPengguna && typeof res.data.pengguna_rows_html === 'string') {
                tblPengguna.innerHTML = res.data.pengguna_rows_html;
                if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblPengguna');
              }
              if (res.data.pengguna_satuan_options) window.__penggunaSatuanOptions = res.data.pengguna_satuan_options;
              if (res.data.satuan_list_for_dup) window.__siberadSatuanList = res.data.satuan_list_for_dup;
              // Tambah: kosongkan form biar siap nambah lagi. Ubah: biarkan
              // nilai yang barusan tersimpan tetap tampil (jangan reset ke kosong).
              if (!isEdit) {
                form.reset();
                // form.reset() nggak nge-fire 'change', jadi label trigger
                // styled-select (Kategori) perlu di-sync manual.
                form.querySelectorAll('.styled-select-wrap').forEach(function (w) {
                  if (w.__syncStyledSelect) w.__syncStyledSelect();
                });
              }
              form.querySelectorAll('.field-invalid').forEach(function (el) { el.classList.remove('field-invalid'); });
              form.querySelectorAll('.profile-field-error').forEach(function (el) { el.style.display = 'none'; });
              window.siberadShowToast && window.siberadShowToast('success', res.data.message || 'Berhasil disimpan.');
            } else if (res.status === 422 && res.data && res.data.errors) {
              var map = isEdit
                ? { kode: 'usKode', nama: 'usNama', kategori: 'usKategori' }
                : { kode: 'sKode', nama: 'sNama', kategori: 'sKategori' };
              Object.keys(res.data.errors).forEach(function (f) {
                var el = document.getElementById(map[f]);
                if (!el) return;
                el.classList.add('field-invalid');
                var msg = el.nextElementSibling;
                if (!msg || !msg.classList.contains('profile-field-error')) {
                  msg = document.createElement('span'); msg.className = 'profile-field-error';
                  el.insertAdjacentElement('afterend', msg);
                }
                msg.textContent = res.data.errors[f][0];
                msg.style.display = 'flex';
              });
            } else if (res.status === 401 && window.siberadTampilkanSesiBerakhir) {
              window.siberadTampilkanSesiBerakhir();
            } else {
              window.siberadShowToast && window.siberadShowToast('error', (res.data && res.data.message) || 'Gagal menyimpan data satuan.');
            }
          }).catch(function () {
            window.siberadShowToast && window.siberadShowToast('error', 'Gagal terhubung ke server, coba lagi.');
          }).finally(function () {
            if (yesBtn) yesBtn.disabled = false;
          });
        }
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
                msg.textContent = field.validity.customError
                  ? field.validationMessage
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

          // Deteksi Kode Satuan KEMBAR secara LIVE saat diketik.
          siberadBindDupCheck(document.getElementById('sKode'), 'kode', null);

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
              siberadSubmitSatuanAjax(form, false);
            });
            document.getElementById('tambahSatuanKonfirmasiBatal')?.addEventListener('click', closeKonfirm);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && konfirmOverlay.classList.contains('open')) closeKonfirm(); });
          }
        })();

        window.bukaUbahSatuan = function (btn) {
          document.getElementById('ubahSatuanForm').action = btn.dataset.action;
          document.getElementById('usSatuanId').value = btn.dataset.satuanId || '';
          var usKode = document.getElementById('usKode');
          usKode.value = btn.dataset.kode || '';
          // Kode satuan kategori Admin TIDAK boleh diubah -- dipakai hardcoded di
          // banyak cek role (kode === 'ADMIN'). readonly (bukan disabled) supaya
          // nilainya tetap ke-submit & lolos validasi 'required'. Server juga
          // dikunci di SatuanController::update().
          var kunciKode = (btn.dataset.kategori || '') === @json(\App\Models\Satuan::KATEGORI_ADMIN);
          usKode.readOnly = kunciKode;
          usKode.title = kunciKode ? 'Kode satuan Admin tidak dapat diubah.' : '';
          document.getElementById('usNama').value = btn.dataset.nama || '';
          // Kategori satuan Admin juga dikunci (sama alasan kayak kode). Select
          // di-disable -> styled-select ikut non-aktif; nilainya tetap ke-submit
          // lewat input hidden bayangan supaya validasi 'required' lolos. Server
          // juga maksa kategori tetap di SatuanController::update().
          var usKategori = document.getElementById('usKategori');
          usKategori.value = btn.dataset.kategori || '';
          usKategori.disabled = kunciKode;
          usKategori.title = kunciKode ? 'Kategori satuan Admin tidak dapat diubah.' : '';
          var katShadow = document.getElementById('usKategoriShadow');
          if (kunciKode) {
            if (!katShadow) {
              katShadow = document.createElement('input');
              katShadow.type = 'hidden'; katShadow.name = 'kategori'; katShadow.id = 'usKategoriShadow';
              document.getElementById('ubahSatuanForm').appendChild(katShadow);
            }
            katShadow.value = usKategori.value;
          } else if (katShadow) {
            katShadow.remove();
          }
          document.getElementById('usDeskripsi').value = btn.dataset.deskripsi || '';
          // Bersihkan sisa penanda error merah dari sesi Ubah sebelumnya.
          document.querySelectorAll('#ubahSatuanForm .field-invalid').forEach(function (el) { el.classList.remove('field-invalid'); });
          document.querySelectorAll('#ubahSatuanForm .profile-field-error').forEach(function (el) { el.style.display = 'none'; });
          document.getElementById('ubahSatuanModal').classList.add('open');
        };

        window.bukaHapusSatuan = function (btn) {
          document.getElementById('formHapusSatuan').action = btn.dataset.action;
          document.getElementById('hapusSatuanNama').textContent = btn.dataset.nama || 'ini';
          document.getElementById('hapusSatuanOverlay')?.classList.add('open');
        };
        document.getElementById('hapusSatuanBatal')?.addEventListener('click', () => document.getElementById('hapusSatuanOverlay')?.classList.remove('open'));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') document.getElementById('hapusSatuanOverlay')?.classList.remove('open'); });

        // Hapus satuan via AJAX -- baris tabel dibuang tanpa reload halaman,
        // sama seperti Tambah/Ubah. Fallback ke submit form biasa kalau fetch
        // gagal total (bukan cuma respons error server).
        (function () {
          var formHapus = document.getElementById('formHapusSatuan');
          var overlay = document.getElementById('hapusSatuanOverlay');
          var yesBtn = document.getElementById('hapusSatuanYa');
          if (!formHapus) return;
          formHapus.addEventListener('submit', function (e) {
            e.preventDefault();
            if (yesBtn) yesBtn.disabled = true;
            fetch(formHapus.action, {
              method: 'POST', body: new FormData(formHapus), credentials: 'same-origin',
              headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (r) {
              return r.json().catch(function () { return {}; }).then(function (d) { return { status: r.status, data: d }; });
            }).then(function (res) {
              if (res.status === 200 && res.data && res.data.ok) {
                var row = document.querySelector('#tblSatuan tbody tr[data-satuan-id="' + res.data.id + '"]');
                if (row) {
                  row.classList.add('siberad-row-out');
                  setTimeout(function () {
                    row.remove();
                    if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblSatuan');
                  }, 260);
                }
                overlay && overlay.classList.remove('open');
                // Satuan yg masih punya pengguna gak bisa dihapus (server
                // sudah nolak), jadi gak ada baris tabel Pengguna yg jadi
                // basi -- tapi opsi combobox Satuan di modal Tambah/Ubah
                // Pengguna tetap perlu disegarkan, biar satuan yg baru
                // dihapus gak nyangkut sbg pilihan yg masih bisa dipilih.
                if (res.data.pengguna_satuan_options) window.__penggunaSatuanOptions = res.data.pengguna_satuan_options;
                if (res.data.satuan_list_for_dup) window.__siberadSatuanList = res.data.satuan_list_for_dup;
                window.siberadShowToast && window.siberadShowToast('success', res.data.message || 'Satuan berhasil dihapus.');
              } else if (res.status === 401 && window.siberadTampilkanSesiBerakhir) {
                window.siberadTampilkanSesiBerakhir();
              } else {
                overlay && overlay.classList.remove('open');
                window.siberadShowToast && window.siberadShowToast('error', (res.data && res.data.message) || 'Gagal menghapus satuan.');
              }
            }).catch(function () {
              window.siberadShowToast && window.siberadShowToast('error', 'Gagal terhubung ke server, coba lagi.');
            }).finally(function () {
              if (yesBtn) yesBtn.disabled = false;
            });
          });
        })();
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
                msg.textContent = field.validity.customError
                  ? field.validationMessage
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

          // Deteksi Kode Satuan KEMBAR secara LIVE (kecuali satuan yang sedang
          // diedit itu sendiri -> pakai #usSatuanId sebagai id yang diabaikan).
          siberadBindDupCheck(document.getElementById('usKode'), 'kode', function () { return document.getElementById('usSatuanId').value; });

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
              siberadSubmitSatuanAjax(form, true);
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
          .perm-global-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;padding:12px 16px;margin-bottom:16px;border:1px solid var(--border);border-radius:10px;background:var(--panel-2);position:sticky;top:0;z-index:5;}
          .perm-filter-group{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
          .perm-filter-btn{display:inline-flex;align-items:center;height:30px;padding:0 12px;border:1px solid var(--border);border-radius:8px;background:var(--panel-alt);color:var(--text-dim);font-size:11.5px;font-weight:700;cursor:pointer;transition:border-color .15s,background .15s,color .15s;}
          .perm-filter-btn:hover{border-color:var(--border-strong);}
          .perm-filter-btn.is-active{border-color:var(--gold-bright);background:var(--gold-dim);color:var(--gold-bright);}
          .perm-global-actions{display:flex;align-items:center;gap:12px;flex-wrap:nowrap;}
          .perm-global-count{font-size:11px;font-weight:700;color:var(--text-dim);white-space:nowrap;}
          .perm-global-select-all{display:inline-flex;align-items:center;height:30px;gap:8px;padding:0 12px;border:1px solid var(--border);border-radius:8px;background:var(--panel-alt);cursor:pointer;user-select:none;transition:border-color .15s,background .15s;}
          .perm-global-select-all:hover{border-color:var(--border-strong);}
          .perm-global-select-all input[type="checkbox"]{width:16px;height:16px;margin:0;accent-color:var(--gold-bright);cursor:pointer;flex-shrink:0;display:block;}
          .perm-global-select-all span{font-size:11.5px;font-weight:700;color:var(--text);white-space:nowrap;}
          .perm-global-select-all.is-all-active{border-color:var(--gold-bright);background:var(--gold-dim);}
          .perm-global-select-all.is-all-active span{color:var(--gold-bright);}
          .perm-batch-fab{position:fixed;bottom:28px;right:32px;z-index:900;display:flex;flex-direction:column;align-items:flex-end;gap:8px;pointer-events:none;}
          .perm-batch-apply{display:none;align-items:center;gap:8px;padding:11px 20px;border:none;border-radius:12px;background:var(--gold-bright);color:#000;font-family:var(--mono);font-size:11.5px;font-weight:700;cursor:pointer;letter-spacing:.05em;text-transform:uppercase;white-space:nowrap;box-shadow:0 4px 18px rgba(0,0,0,.28);transition:opacity .2s,transform .2s,box-shadow .2s;pointer-events:auto;}
          .perm-batch-apply:hover{opacity:.88;box-shadow:0 6px 24px rgba(0,0,0,.36);transform:translateY(-1px);}
          .perm-batch-apply:active{transform:scale(.97);}
          .perm-batch-apply.visible{display:inline-flex;animation:permFabIn .22s ease;}
          .perm-batch-apply:disabled{opacity:.5;cursor:not-allowed;transform:none;}
          .perm-batch-progress{display:none;align-items:center;gap:9px;padding:10px 16px;border-radius:12px;background:var(--panel);border:1px solid var(--border-soft);box-shadow:0 4px 18px rgba(0,0,0,.22);font-size:11px;font-weight:700;color:var(--text-dim);pointer-events:auto;}
          .perm-batch-progress.visible{display:inline-flex;}
          .perm-batch-progress-bar{width:90px;height:5px;border-radius:3px;background:var(--border);overflow:hidden;}
          .perm-batch-progress-fill{height:100%;width:0%;background:var(--gold-bright);border-radius:3px;transition:width .25s ease;}
          @keyframes permFabIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
          .perm-satuan-list .panel[data-kategori]{display:block;}
          .perm-satuan-list .panel[data-kategori].perm-hidden{display:none;}
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

        @php
          $permKategoriMap = [
            \App\Models\Satuan::KATEGORI_ADMIN => 'Admin',
            \App\Models\Satuan::KATEGORI_PIMPINAN => 'Pimpinan',
            \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
            \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
            \App\Models\Satuan::KATEGORI_DIREKTORAT => 'Direktorat',
            \App\Models\Satuan::KATEGORI_KOTAMA => 'Kasansi',
          ];
        @endphp

        <div class="perm-global-toolbar">
          <div class="perm-filter-group" data-perm-filter-group>
            <button type="button" class="perm-filter-btn is-active" data-perm-filter="">Semua</button>
            @foreach(['Pimpinan','Unsur Pelayanan','Unsur Pembantu Pimpinan','Direktorat','Satlak','Kasansi'] as $kategoriLabel)
            <button type="button" class="perm-filter-btn" data-perm-filter="{{ $kategoriLabel }}">{{ $kategoriLabel }}</button>
            @endforeach
          </div>
          <div class="perm-global-actions">
            <span class="perm-global-count" data-perm-global-count></span>
            <label class="perm-global-select-all" data-perm-global-select-all>
              <input type="checkbox">
              <span data-perm-global-select-all-label>Pilih Semua Modul</span>
            </label>
          </div>
        </div>

        {{-- FAB: Terapkan ke Semua (muncul floating di kanan bawah saat ada perubahan pending) --}}
        <div class="perm-batch-fab">
          <div class="perm-batch-progress" data-perm-batch-progress>
            <div class="perm-batch-progress-bar"><div class="perm-batch-progress-fill" data-perm-batch-fill></div></div>
            <span data-perm-batch-progress-text>Menyimpan...</span>
          </div>
          <button type="button" class="perm-batch-apply" data-perm-batch-apply>
            <span data-perm-batch-apply-label>Terapkan ke Semua</span>
          </button>
        </div>

        <div class="perm-satuan-list">
          @foreach($semuaSatuan as $s)
          {{-- Admin selalu punya akses penuh ke semua modul dan tidak bisa
               di-nonaktifkan, jadi kartu hak akses modul untuk kategori
               Admin tidak perlu ditampilkan di sini. --}}
          @continue(($s->kategori ?? null) === \App\Models\Satuan::KATEGORI_ADMIN)
          @php $kategoriLabel = $permKategoriMap[$s->kategori ?? ''] ?? 'Satlak'; @endphp
          <div class="panel" data-kategori="{{ $kategoriLabel }}">
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
        </div>

        <script>
          (function () {
            var panel = document.querySelector('[data-tab-panel="role-akses"]');
            if (!panel) return;

            var satuanPanels = Array.prototype.slice.call(panel.querySelectorAll('.perm-satuan-list .panel[data-kategori]'));
            var filterBtns = Array.prototype.slice.call(panel.querySelectorAll('[data-perm-filter]'));
            var globalWrap = panel.querySelector('[data-perm-global-select-all]');
            var globalCb = globalWrap.querySelector('input[type="checkbox"]');
            var globalLabelEl = globalWrap.querySelector('[data-perm-global-select-all-label]');
            var globalCountEl = panel.querySelector('[data-perm-global-count]');
            var batchBtn = document.querySelector('[data-perm-batch-apply]');
            var batchApplyLabelEl = batchBtn.querySelector('[data-perm-batch-apply-label]');
            var batchProgress = document.querySelector('[data-perm-batch-progress]');
            var batchFill = document.querySelector('[data-perm-batch-fill]');
            var batchProgressText = document.querySelector('[data-perm-batch-progress-text]');
            var activeFilter = '';
            /* pristineSnapshot: diambil saat halaman load, sebelum user menyentuh apapun.
               Tombol Terapkan muncul hanya jika state sekarang != pristine. */
            var pristineSnapshot = {};
            panel.querySelectorAll('.perm-grid input[type="checkbox"]').forEach(function (cb) {
              pristineSnapshot[cb.closest('form').action + '||' + cb.value] = cb.checked;
            });

            function hasRealChanges() {
              var changed = false;
              panel.querySelectorAll('.perm-grid input[type="checkbox"]').forEach(function (cb) {
                var key = cb.closest('form').action + '||' + cb.value;
                if (cb.checked !== (pristineSnapshot[key] === true)) changed = true;
              });
              return changed;
            }

            function updatePristine() {
              panel.querySelectorAll('.perm-grid input[type="checkbox"]').forEach(function (cb) {
                pristineSnapshot[cb.closest('form').action + '||' + cb.value] = cb.checked;
              });
            }

            var hasPendingChanges = false;

            function refreshCard(cb) {
              var card = cb.closest('.perm-card');
              var status = card.querySelector('.perm-card-status');
              card.classList.toggle('is-active', cb.checked);
              status.textContent = cb.checked ? 'Aktif' : 'Nonaktif';
            }

            function visibleCheckboxes() {
              var boxes = [];
              satuanPanels.forEach(function (p) {
                if (p.classList.contains('perm-hidden')) return;
                boxes = boxes.concat(Array.prototype.slice.call(p.querySelectorAll('.perm-grid input[type="checkbox"]')));
              });
              return boxes;
            }

            function visibleForms() {
              var forms = [];
              satuanPanels.forEach(function (p) {
                if (p.classList.contains('perm-hidden')) return;
                var f = p.querySelector('form[method="POST"]');
                if (f) forms.push(f);
              });
              return forms;
            }

            var userHasInteracted = false;

            function refreshGlobal() {
              var boxes = visibleCheckboxes();
              var total = boxes.length;
              var checked = boxes.filter(function (cb) { return cb.checked; }).length;
              globalCountEl.textContent = checked + ' dari ' + total + ' modul aktif' + (activeFilter ? ' (' + activeFilter + ')' : '');
              /* Checkbox global selalu mengikuti kondisi sebenarnya: jika seluruh
                 modul yang tampil sudah aktif (baik dari awal maupun karena user
                 mencentang satu-satu), checkbox "Pilih Semua Modul" otomatis ikut
                 tercentang. Indeterminate (sebagian aktif) hanya ditampilkan
                 setelah user berinteraksi, supaya tampilan awal halaman tidak
                 langsung menunjukkan status "sebagian" sebelum disentuh. */
              globalCb.checked = total > 0 && checked === total;
              globalCb.indeterminate = userHasInteracted && checked > 0 && checked < total;
              globalWrap.classList.toggle('is-all-active', globalCb.checked);
              if (globalLabelEl) {
                globalLabelEl.textContent = globalCb.checked ? 'Semua Modul Aktif' : 'Pilih Semua Modul';
              }
              refreshBatchApplyLabel();
            }

            /* ---- Label tombol "Terapkan ke ..." mengikuti kategori/filter yang
               sedang aktif, misalnya "Terapkan ke Pimpinan" saat filter Pimpinan
               dipilih, atau "Terapkan ke Semua" saat tidak ada filter. ---- */
            function refreshBatchApplyLabel() {
              if (!batchApplyLabelEl) return;
              batchApplyLabelEl.textContent = activeFilter ? ('Terapkan ke ' + activeFilter) : 'Terapkan ke Semua';
            }

            function setPending(val) {
              hasPendingChanges = val;
              /* Tampilkan tombol hanya jika benar-benar ada perubahan dari state awal */
              if (val && hasRealChanges()) {
                batchBtn.classList.add('visible');
              } else if (!val || !hasRealChanges()) {
                batchBtn.classList.remove('visible');
              }
            }

            /* ---- Simpan semua form visible via fetch satu per satu ---- */
            batchBtn.addEventListener('click', function () {
              var forms = visibleForms();
              if (!forms.length) return;
              var total = forms.length;
              var done = 0;

              batchBtn.disabled = true;
              batchBtn.classList.remove('visible');
              batchProgress.classList.add('visible');
              batchFill.style.width = '0%';
              batchProgressText.textContent = '0 / ' + total + ' tersimpan';

              function submitNext(i) {
                if (i >= total) {
                  /* semua selesai */
                  batchFill.style.width = '100%';
                  batchProgressText.textContent = 'Semua tersimpan!';
                  batchBtn.disabled = false;
                  /* Perbarui pristine agar state saat ini jadi baseline baru */
                  updatePristine();
                  setPending(false);
                  window.setTimeout(function () {
                    batchProgress.classList.remove('visible');
                    batchFill.style.width = '0%';
                  }, 2200);
                  return;
                }
                var form = forms[i];
                var data = new FormData(form);
                /* FormData hanya mengambil checkbox yang checked,
                   tapi kita perlu memastikan field permissions[] ada
                   meski kosong (semua dicentang off) */
                if (!data.has('permissions[]')) {
                  data.append('permissions[]', '');
                }
                fetch(form.action, {
                  method: 'POST',
                  headers: { 'X-Requested-With': 'XMLHttpRequest' },
                  body: data,
                  credentials: 'same-origin'
                }).then(function () {
                  done++;
                  batchFill.style.width = Math.round((done / total) * 100) + '%';
                  batchProgressText.textContent = done + ' / ' + total + ' tersimpan';
                  submitNext(i + 1);
                }).catch(function () {
                  done++;
                  batchProgressText.textContent = done + ' / ' + total + ' tersimpan';
                  submitNext(i + 1);
                });
              }

              submitNext(0);
            });

            /* ---- Checkbox individual → tandai pending ---- */
            panel.querySelectorAll('.perm-grid input[type="checkbox"]').forEach(function (cb) {
              cb.addEventListener('change', function () {
                userHasInteracted = true;
                refreshCard(cb);
                refreshGlobal();
                setPending(hasRealChanges());
              });
            });

            /* ---- "Pilih Semua Modul" checkbox ---- */
            globalCb.addEventListener('change', function () {
              userHasInteracted = true;
              var next = globalCb.checked;
              visibleCheckboxes().forEach(function (cb) {
                cb.checked = next;
                refreshCard(cb);
              });
              refreshGlobal();
              setPending(hasRealChanges());
            });

            /* ---- Filter kategori ---- */
            filterBtns.forEach(function (btn) {
              btn.addEventListener('click', function () {
                activeFilter = btn.getAttribute('data-perm-filter') || '';
                filterBtns.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
                satuanPanels.forEach(function (p) {
                  var match = !activeFilter || p.getAttribute('data-kategori') === activeFilter;
                  p.classList.toggle('perm-hidden', !match);
                });
                refreshGlobal();
                /* reset pending & tombol saat ganti filter supaya tidak simpan
                   lintas filter secara tidak sengaja */
                setPending(false);
              });
            });

            refreshGlobal();
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
                <tr data-log-id="{{ $l->id }}" data-filter-value="{{ $kategoriLabelLog }}">
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
                    return '<tr data-log-id="' + escapeHtml(String(l.id)) + '" data-filter-value="' + escapeHtml(l.kategori || '') + '">'
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
                // Samakan dengan Arsip Data: "Dari" & "Sampai" sama-sama
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

      {{-- ===== RESET DATA LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="reset-data-laporan">
        <div class="section-head panel rdl-head-panel">
          <div class="rdl-head-content">
            <div class="rdl-head-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
            </div>
            <div>
              <h2>Reset Data Laporan</h2>
              <p>Kelola dan bersihkan data laporan uji coba (dummy) secara terpisah per kategori. Anda dapat menghapus seluruh data pada kategori atau memilih beberapa baris data spesifik saja. Data pengguna, satuan, dan pengaturan sistem tetap aman.</p>
            </div>
          </div>
        </div>

        <style>
          .rdl-head-panel{padding:20px 24px;}
          .rdl-head-content{display:flex;align-items:center;gap:18px;}
          .rdl-head-icon{flex:0 0 auto;width:46px;height:46px;border-radius:12px;background:rgba(229,72,77,.12);color:#e5484d;display:flex;align-items:center;justify-content:center;}
          .rdl-head-icon svg{width:24px;height:24px;}

          /* Grid Switcher Kategori (5 Card Panel Mini) */
          .rdl-category-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:20px;}
          .rdl-cat-card{
            display:flex;align-items:flex-start;gap:13px;padding:16px 18px;border-radius:14px;
            background:var(--panel);border:1px solid var(--border-soft);cursor:pointer;
            transition:all .18s ease;text-align:left;font-family:inherit;width:100%;
          }
          .rdl-cat-card:hover{border-color:var(--border-strong);transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,.2);}
          .rdl-cat-card.active{
            border-color:var(--gold);background:var(--gold-dim);
            box-shadow:0 6px 18px rgba(255,152,0,.15), inset 0 0 0 1px var(--gold);
          }
          .rdl-cat-icon{
            flex:0 0 auto;width:38px;height:38px;border-radius:10px;background:var(--panel-alt);
            display:flex;align-items:center;justify-content:center;color:var(--gold-bright);
            border:1px solid var(--border-soft);transition:background .18s, color .18s;
          }
          .rdl-cat-card.active .rdl-cat-icon{background:var(--panel);color:var(--gold-bright);border-color:var(--gold);}
          .rdl-cat-icon svg{width:18px;height:18px;}
          .rdl-cat-body{flex:1 1 auto;min-width:0;}
          .rdl-cat-title{display:block;font-size:13px;font-weight:700;color:var(--text);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
          .rdl-cat-badge{
            display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;font-family:var(--mono);
            padding:2px 8px;border-radius:999px;background:var(--panel-alt);color:var(--text-muted);border:1px solid var(--border-soft);
          }
          .rdl-cat-card.active .rdl-cat-badge{background:var(--panel);color:var(--gold-bright);border-color:rgba(255,152,0,.3);}

          /* Card Panel Detail Terpisah */
          .rdl-detail-panel{display:none;}
          .rdl-detail-panel.active{display:block;animation:rdlFadeIn .2s ease;}
          @keyframes rdlFadeIn{from{opacity:0;transform:translateY(6px);}to{opacity:1;transform:none;}}

          .rdl-panel-head{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border-soft);}
          .rdl-panel-title-area{display:flex;align-items:center;gap:12px;}
          .rdl-panel-icon{width:36px;height:36px;border-radius:9px;background:rgba(255,152,0,.12);color:var(--gold-bright);display:flex;align-items:center;justify-content:center;}
          .rdl-panel-icon svg{width:20px;height:20px;}
          .rdl-panel-head h3{font-size:16px;font-weight:700;margin:0;color:var(--text);}
          .rdl-panel-head p{font-size:12px;color:var(--text-muted);margin:3px 0 0;}

          .rdl-panel-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
          .rdl-search-wrap{position:relative;}
          .rdl-search-input{
            background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:10px;
            padding:7px 12px 7px 32px;font-size:12.5px;color:var(--text);width:220px;transition:border-color .15s,box-shadow .15s;
          }
          .rdl-search-input:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px var(--gold-dim);}
          .rdl-search-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-dim);pointer-events:none;}
          .rdl-search-icon svg{width:14px;height:14px;}

          /* Tabel Detail */
          .rdl-table-wrap{overflow-x:auto;max-height:480px;border:1px solid var(--border-soft);border-radius:12px;background:var(--panel-alt);}
          .rdl-table-wrap::-webkit-scrollbar{width:7px;height:7px;}
          .rdl-table-wrap::-webkit-scrollbar-thumb{background:var(--border-strong);border-radius:4px;}
          table.rdl-table{width:100%;border-collapse:collapse;font-size:12.5px;text-align:left;}
          table.rdl-table th{
            position:sticky;top:0;z-index:2;background:var(--panel);
            font-family:var(--mono);font-size:10.5px;letter-spacing:.05em;text-transform:uppercase;color:var(--text-dim);
            padding:12px 14px;border-bottom:1px solid var(--border-soft);white-space:nowrap;
          }
          table.rdl-table td{padding:12px 14px;border-bottom:1px solid var(--border-soft);vertical-align:middle;color:var(--text);}
          table.rdl-table tr:hover td{background:rgba(255,152,0,.04);}
          table.rdl-table tr.is-selected td{background:rgba(229,72,77,.08);}

          .rdl-check-cell{width:36px;text-align:center;}
          .rdl-check-cell input[type="checkbox"]{width:16px;height:16px;accent-color:#e5484d;cursor:pointer;margin:0;}

          .rdl-title-cell{display:flex;flex-direction:column;gap:3px;}
          .rdl-item-title{font-weight:600;color:var(--text);line-height:1.4;}
          .rdl-item-subtype{display:inline-flex;align-items:center;align-self:flex-start;font-size:10px;font-weight:700;font-family:var(--mono);padding:1px 6px;border-radius:4px;text-transform:uppercase;}
          .rdl-subtype-gold{background:rgba(255,152,0,.15);color:var(--gold-bright);}
          .rdl-subtype-amber{background:rgba(245,158,11,.15);color:#f59e0b;}
          .rdl-subtype-cyan{background:rgba(6,182,212,.15);color:#06b6d4;}
          .rdl-subtype-red{background:rgba(239,68,68,.15);color:#ef4444;}
          .rdl-subtype-purple{background:rgba(168,85,247,.15);color:#a855f7;}

          .rdl-satuan-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 8px;border-radius:6px;background:var(--panel);border:1px solid var(--border-soft);color:var(--text);}
          .rdl-user-name{font-size:11px;color:var(--text-muted);margin-top:2px;}

          .rdl-date{font-family:var(--mono);font-size:11px;color:var(--text-muted);white-space:nowrap;}
          .rdl-status-pill{display:inline-block;padding:3px 8px;border-radius:999px;font-size:10.5px;font-weight:700;text-transform:uppercase;background:var(--panel);border:1px solid var(--border-soft);color:var(--text-muted);}

          .rdl-action-del-btn{
            width:30px;height:30px;border-radius:8px;background:transparent;border:1px solid transparent;
            color:var(--text-dim);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;
            transition:all .15s ease;
          }
          .rdl-action-del-btn:hover{background:rgba(229,72,77,.12);color:#e5484d;border-color:rgba(229,72,77,.3);}
          .rdl-action-del-btn svg{width:15px;height:15px;}

          /* Empty State */
          .rdl-empty-box{padding:48px 24px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:12px;border:1px dashed var(--border-soft);border-radius:12px;background:var(--panel-alt);}
          .rdl-empty-icon{width:56px;height:56px;border-radius:16px;background:rgba(47,158,99,.12);color:#2f9e63;display:flex;align-items:center;justify-content:center;}
          .rdl-empty-icon svg{width:28px;height:28px;}
          .rdl-empty-box h4{font-size:15px;font-weight:700;color:var(--text);margin:0;}
          .rdl-empty-box p{font-size:12.5px;color:var(--text-muted);margin:0;max-width:380px;line-height:1.5;}

          .btn-danger-soft{background:rgba(229,72,77,.12);color:#e5484d;border:1px solid rgba(229,72,77,.28);font-weight:700;}
          .btn-danger-soft:hover{background:#e5484d;color:#fff;border-color:#e5484d;}
        </style>

        {{-- Grid Kartu Kategori --}}
        <div class="rdl-category-grid" role="tablist">
          @foreach($resetDataKategori as $key => $def)
            @php
              $countData = $resetDataCounts[$key] ?? 0;
              $isActiveCat = $loop->first;
            @endphp
            <button type="button" class="rdl-cat-card {{ $isActiveCat ? 'active' : '' }}" data-rdl-tab="{{ $key }}" onclick="switchRdlCategory('{{ $key }}')">
              <div class="rdl-cat-icon">
                @if($key === 'laporan')
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                @elseif($key === 'monitoring')
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                @elseif($key === 'permintaan')
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><polyline points="9 14 11 16 15 11"/></svg>
                @else
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z" opacity="0"/><path d="M22 6c0-1.1-.9-2-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2V6z"/><path d="m22 6-10 7L2 6"/></svg>
                @endif
              </div>
              <div class="rdl-cat-body">
                <span class="rdl-cat-title">{{ $def['label'] }}</span>
                <span class="rdl-cat-badge">{{ number_format($countData) }} baris data</span>
              </div>
            </button>
          @endforeach
        </div>

        {{-- Card Panels Detail per Kategori --}}
        @foreach($resetDataKategori as $key => $def)
          @php
            $items = $resetDataDetails[$key] ?? [];
            $totalCount = count($items);
            $isActiveCat = $loop->first;
          @endphp
          <div class="panel rdl-detail-panel {{ $isActiveCat ? 'active' : '' }}" id="rdlDetail_{{ $key }}">
            <div class="rdl-panel-head">
              <div class="rdl-panel-title-area">
                <div class="rdl-panel-icon">
                  @if($key === 'laporan')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  @elseif($key === 'monitoring')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                  @elseif($key === 'permintaan')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                  @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 6c0-1.1-.9-2-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2V6z"/><path d="m22 6-10 7L2 6"/></svg>
                  @endif
                </div>
                <div>
                  <h3>{{ $def['label'] }}</h3>
                  <p>{{ $def['desc'] ?? 'Kelola dan hapus data laporan pada kategori ini.' }} &bull; <strong id="rdlCountBadge_{{ $key }}">{{ $totalCount }}</strong> data tersedia</p>
                </div>
              </div>
              <div class="rdl-panel-actions">
                <div class="rdl-search-wrap">
                  <span class="rdl-search-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
                  <input type="text" class="rdl-search-input" placeholder="Cari perihal, pengirim..." oninput="filterRdlTable(this, '{{ $key }}')">
                </div>
                <button type="button" class="btn btn-sm btn-ghost-red" id="btnDeleteSelected_{{ $key }}" disabled onclick="bukaKonfirmasiHapusTerpilih('{{ $key }}')">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  Hapus Baris Terpilih (<span id="rdlSelectedNum_{{ $key }}">0</span>)
                </button>
                <button type="button" class="btn btn-sm btn-danger-soft" @if($totalCount === 0) disabled style="opacity:.5;cursor:not-allowed;" @endif onclick="bukaKonfirmasiHapusSemua('{{ $key }}', '{{ addslashes($def['label']) }}', {{ $totalCount }})">
                  Hapus Semua ({{ $totalCount }})
                </button>
              </div>
            </div>

            @if($totalCount === 0)
              <div class="rdl-empty-box">
                <div class="rdl-empty-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                </div>
                <h4>Kategori Bersih &amp; Aman</h4>
                <p>Tidak ada data laporan pada kategori ini. Anda dapat memasukkan data laporan baru melalui modul terkait.</p>
              </div>
            @else
              <div class="rdl-table-wrap">
                <table class="rdl-table" id="rdlTable_{{ $key }}">
                  <thead>
                    <tr>
                      <th class="rdl-check-cell">
                        <input type="checkbox" title="Pilih semua baris" onchange="toggleRdlCheckAll(this, '{{ $key }}')">
                      </th>
                      <th style="width:40px;">No</th>
                      <th>Perihal / Rincian Laporan</th>
                      <th>Satuan &amp; Pengaju</th>
                      <th>Tanggal Dibuat</th>
                      <th>Status</th>
                      <th style="text-align:center;width:80px;">Lampiran</th>
                      <th style="text-align:center;width:60px;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($items as $i => $item)
                      <tr data-rdl-row data-item-key="{{ $item['key'] }}" data-search-text="{{ strtolower($item['judul'].' '.$item['satuan'].' '.$item['user'].' '.$item['status'].' '.$item['subtipe']) }}">
                        <td class="rdl-check-cell">
                          <input type="checkbox" class="rdl-row-cb" value="{{ $item['key'] }}" data-title="{{ e($item['judul']) }}" onchange="syncRdlSelection('{{ $key }}')">
                        </td>
                        <td style="font-family:var(--mono);color:var(--text-dim);">{{ $i + 1 }}</td>
                        <td>
                          <div class="rdl-title-cell">
                            <span class="rdl-item-title">{{ $item['judul'] }}</span>
                            <span class="rdl-item-subtype rdl-subtype-{{ $item['subtipe_badge'] ?? 'gold' }}">{{ $item['subtipe'] }}</span>
                          </div>
                        </td>
                        <td>
                          <span class="rdl-satuan-badge">{{ $item['satuan'] }}</span>
                          <div class="rdl-user-name">{{ $item['user'] }}</div>
                        </td>
                        <td class="rdl-date">{{ $item['tanggal'] }}</td>
                        <td>
                          <span class="rdl-status-pill">{{ $item['status'] }}</span>
                        </td>
                        <td style="text-align:center;">
                          @if(!empty($item['lampiran']))
                            <span title="Ada lampiran berkas" style="color:var(--success);font-weight:700;display:inline-flex;align-items:center;gap:3px;font-size:11px;">
                              <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                              Ada
                            </span>
                          @else
                            <span style="color:var(--text-dim);font-size:12px;">-</span>
                          @endif
                        </td>
                        <td style="text-align:center;">
                          <button type="button" class="rdl-action-del-btn" title="Hapus baris ini" onclick="bukaKonfirmasiHapusSatu('{{ $item['key'] }}', '{{ addslashes($item['judul']) }}')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                          </button>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </div>
        @endforeach

        {{-- Form Tindakan Reset (Submit Tersembunyi) --}}
        <form id="formRdlAction" method="POST" action="{{ route('admin.reset-data-laporan.destroy') }}" style="display:none;">
          @csrf @method('DELETE')
          <div id="formRdlActionInputs"></div>
        </form>

        <script>
        (function(){
          var overlay = document.getElementById('resetDataLaporanOverlay');
          var titleEl = document.getElementById('resetDataLaporanTitle');
          var subEl = document.getElementById('resetDataLaporanSub');
          var listEl = document.getElementById('resetDataLaporanDaftar');
          var yaBtn = document.getElementById('resetDataLaporanYa');
          var batalBtn = document.getElementById('resetDataLaporanBatal');
          var formAction = document.getElementById('formRdlAction');
          var formInputs = document.getElementById('formRdlActionInputs');

          // 1. Switch Active Category
          window.switchRdlCategory = function(catKey) {
            document.querySelectorAll('[data-rdl-tab]').forEach(function(card){
              card.classList.toggle('active', card.getAttribute('data-rdl-tab') === catKey);
            });
            document.querySelectorAll('.rdl-detail-panel').forEach(function(panel){
              panel.classList.toggle('active', panel.id === 'rdlDetail_' + catKey);
            });
          };

          // 2. Search Filter in Table
          window.filterRdlTable = function(input, catKey) {
            var val = (input.value || '').trim().toLowerCase();
            var panel = document.getElementById('rdlDetail_' + catKey);
            if (!panel) return;
            var rows = panel.querySelectorAll('tbody tr[data-rdl-row]');
            rows.forEach(function(row){
              var searchTxt = row.getAttribute('data-search-text') || '';
              row.style.display = searchTxt.indexOf(val) !== -1 ? '' : 'none';
            });
            syncRdlSelection(catKey);
          };

          // 3. Toggle Check All
          window.toggleRdlCheckAll = function(master, catKey) {
            var panel = document.getElementById('rdlDetail_' + catKey);
            if (!panel) return;
            var cbs = panel.querySelectorAll('tbody tr[data-rdl-row]:not([style*="display: none"]) .rdl-row-cb');
            cbs.forEach(function(cb){
              cb.checked = master.checked;
              var tr = cb.closest('tr');
              if (tr) tr.classList.toggle('is-selected', cb.checked);
            });
            syncRdlSelection(catKey);
          };

          // 4. Sync Selection State & Enable/Disable Buttons
          window.syncRdlSelection = function(catKey) {
            var panel = document.getElementById('rdlDetail_' + catKey);
            if (!panel) return;
            var cbs = panel.querySelectorAll('tbody .rdl-row-cb:checked');
            var numEl = document.getElementById('rdlSelectedNum_' + catKey);
            var delBtn = document.getElementById('btnDeleteSelected_' + catKey);
            var count = cbs.length;
            if (numEl) numEl.textContent = count;
            if (delBtn) delBtn.disabled = count === 0;

            panel.querySelectorAll('tbody .rdl-row-cb').forEach(function(cb){
              var tr = cb.closest('tr');
              if (tr) tr.classList.toggle('is-selected', cb.checked);
            });
          };

          // Helper buka overlay
          function openConfirmDialog(title, desc, items, submitCallback) {
            if (!overlay) return;
            if (titleEl) titleEl.textContent = title;
            if (subEl) subEl.textContent = desc;
            if (listEl) {
              listEl.innerHTML = '';
              items.forEach(function(txt){
                var li = document.createElement('li');
                li.textContent = txt;
                listEl.appendChild(li);
              });
              listEl.style.display = items.length ? 'block' : 'none';
            }

            // Bind click Ya
            yaBtn.onclick = function() {
              yaBtn.disabled = true;
              yaBtn.textContent = 'Menghapus...';
              submitCallback();
            };

            overlay.classList.add('open');
          }

          function closeConfirmDialog() {
            if (overlay) overlay.classList.remove('open');
            if (yaBtn) {
              yaBtn.disabled = false;
              yaBtn.textContent = 'Ya, Hapus Permanen';
            }
          }

          if (batalBtn) batalBtn.addEventListener('click', closeConfirmDialog);
          document.addEventListener('keydown', function(e){
            if (e.key === 'Escape' && overlay && overlay.classList.contains('open')) closeConfirmDialog();
          });

          // 5. Buka Konfirmasi Hapus Baris Terpilih
          window.bukaKonfirmasiHapusTerpilih = function(catKey) {
            var panel = document.getElementById('rdlDetail_' + catKey);
            if (!panel) return;
            var cbs = panel.querySelectorAll('tbody .rdl-row-cb:checked');
            if (!cbs.length) return;

            var items = [];
            cbs.forEach(function(cb){
              items.push(cb.getAttribute('data-title') || cb.value);
            });

            openConfirmDialog(
              'Hapus ' + cbs.length + ' Baris Data Terpilih?',
              'Baris data laporan yang Anda centang berikut akan dihapus secara permanen beserta lampirannya:',
              items.slice(0, 10).concat(items.length > 10 ? ['... dan ' + (items.length - 10) + ' baris lainnya.'] : []),
              function() {
                formInputs.innerHTML = '';
                cbs.forEach(function(cb){
                  var input = document.createElement('input');
                  input.type = 'hidden';
                  input.name = 'item_keys[]';
                  input.value = cb.value;
                  formInputs.appendChild(input);
                });
                formAction.submit();
              }
            );
          };

          // 6. Buka Konfirmasi Hapus Satu Baris
          window.bukaKonfirmasiHapusSatu = function(itemKey, title) {
            openConfirmDialog(
              'Hapus Baris Data Laporan Ini?',
              'Data laporan "' + title + '" akan dihapus secara permanen dan tidak bisa dikembalikan:',
              [title],
              function() {
                formInputs.innerHTML = '';
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'item_keys[]';
                input.value = itemKey;
                formInputs.appendChild(input);
                formAction.submit();
              }
            );
          };

          // 7. Buka Konfirmasi Hapus Semua dalam Kategori
          window.bukaKonfirmasiHapusSemua = function(catKey, catLabel, totalCount) {
            openConfirmDialog(
              'Hapus Semua Data di ' + catLabel + '?',
              'PERINGATAN: Seluruh data sebanyak ' + totalCount + ' baris pada kategori ini akan dibersihkan secara permanen:',
              [catLabel + ' (Total ' + totalCount + ' baris data)'],
              function() {
                formInputs.innerHTML = '';
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'kategori[]';
                input.value = catKey;
                formInputs.appendChild(input);
                formAction.submit();
              }
            );
          };
        })();
        </script>
      </section>

      {{-- ===== ARSIP DATA (dulu "Data Laporan") ===== --}}
      <section class="tab-panel" data-tab-panel="laporan-admin">
        <div class="panel dl-head-panel">
          <div class="dl-head">
            <div class="dl-head-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>
            </div>
            <div>
              <h2>Arsip Data</h2>
              <p>Rekap data pelaporan, pengguna, dan aktivitas sistem dalam satu tempat. Data Pengguna &amp; Data Aktivitas bisa diunduh sebagai CSV/Excel atau PDF.</p>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="dl-tabs">
            <button type="button" class="dl-tab active" data-dl-tab="dl-pelaporan">
              <svg viewBox="0 0 24 24"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
              Data Pelaporan
            </button>
            <button type="button" class="dl-tab" data-dl-tab="dl-pengguna">
              <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              Data Pengguna
            </button>
            <button type="button" class="dl-tab" data-dl-tab="dl-aktivitas">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
              Data Aktivitas
            </button>
          </div>

          {{-- ----- Sub-tab: Data Pelaporan ----- --}}
          <div class="dl-section active" data-dl-section="dl-pelaporan">
            <div class="dl-section-head">
              <div>
                <h3>Data Pelaporan</h3>
                <p>Permintaan laporan dari Pimpinan ke satuan, baik yang sudah diarsipkan maupun belum.</p>
              </div>
            </div>

            {{-- Search+filter+sort di sini SENGAJA dibuat SAMA PERSIS dengan
                 punya Pimpinan (initRiwayatCardFilter() di
                 danpus-permintaan-arsip-mode.blade.php) -- class .rpt-filter-*
                 sama (CSS-nya di-include lewat danpus-report-table-filter.blade.php
                 di bawah), tanpa filter tanggal/kategori/tombol Unduh yang
                 memang tidak ada di kartu Pimpinan. --}}
            <div class="rpt-filter-bar" id="dlPelaporanFilterBar">
              <div class="rpt-filter-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
                <input type="search" autocomplete="off" id="dlPelaporanSearch" placeholder="Cari perihal atau tujuan satuan..." aria-label="Cari perihal atau tujuan satuan...">
              </div>
              <select class="rpt-filter-select" id="dlPelaporanStatusFilter" aria-label="Filter status">
                <option value="all">Semua Status</option>
                <option value="Terbaru">Terbaru</option>
                <option value="Sedang diproses">Sedang diproses</option>
                <option value="Menunggu">Menunggu</option>
                <option value="Revisi">Revisi</option>
                <option value="Terlambat">Terlambat</option>
                <option value="Dibatalkan">Dibatalkan</option>
                <option value="Disetujui">Disetujui</option>
                <option value="Ditolak">Ditolak</option>
              </select>
              <select class="rpt-filter-select" id="dlPelaporanSort" aria-label="Urutkan">
                <option value="terbaru">Dibuat Terbaru</option>
                <option value="terlama">Dibuat Terlama</option>
              </select>
              <span class="rpt-filter-count" id="dlPelaporanCount"></span>
            </div>

            {{-- TANPA .tbl-wrap.tbl-scroll (max-height+overflow-y:auto) yang
                 dipakai 2 tabel lain -- itu bikin box scroll internal sendiri
                 yang scrollbar-nya nempel dempet ke tepi kanan (kelihatan
                 sempit/aneh, beda dari kartu Pimpinan yang ngalir bebas ikut
                 tinggi halaman, gak dibungkus box terpisah). Kartu di sini
                 biarkan tumbuh natural, ikut discroll bareng halaman. --}}
              <div class="deadline-sender-list" id="tblDlPelaporan">
                @forelse($semuaPelaporan as $pl)
                @include('siberad.dashboards.partials.admin-data-pelaporan-row', ['pl' => $pl])
                @empty
                <div class="empty-state">
                  <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M12 11h4"></path><path d="M12 16h4"></path><path d="M8 11h.01"></path><path d="M8 16h.01"></path></svg>
                  <div class="empty-state-title">Belum ada permintaan laporan</div>
                </div>
                @endforelse
              </div>

            <div class="dl-foot">
              <p>Data ditampilkan langsung dari database sistem.</p>
            </div>
          </div>

          {{-- ----- Sub-tab: Data Pengguna ----- --}}
          <div class="dl-section" data-dl-section="dl-pengguna">
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
                  <a href="{{ route('admin.laporan.export-pengguna') }}" data-dl-base-href="{{ route('admin.laporan.export-pengguna') }}"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>Unduh CSV / Excel</a>
                  <a href="{{ route('admin.laporan.cetak', 'pengguna') }}" data-dl-base-href="{{ route('admin.laporan.cetak', 'pengguna') }}" target="_blank"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>Unduh PDF</a>
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
                    <td><strong>{{ $p->name }}</strong></td>
                    <td>{{ $p->username }}</td>
                    <td>{{ $p->email ?: '-' }}</td>
                    <td>{{ $p->satuan->nama_keterangan ?? '-' }}</td>
                    <td style="white-space:nowrap;" data-tanggal="{{ $p->created_at?->format('Y-m-d') }}">{{ $p->created_at?->format('d/m/Y H:i') }}</td>
                  </tr>
                  @empty
                  <tr><td colspan="6"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Belum ada pengguna</div></div></td></tr>
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
                  <a href="{{ route('admin.laporan.export-aktivitas') }}" data-dl-base-href="{{ route('admin.laporan.export-aktivitas') }}"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>Unduh CSV / Excel</a>
                  <a href="{{ route('admin.laporan.cetak', 'aktivitas') }}" data-dl-base-href="{{ route('admin.laporan.cetak', 'aktivitas') }}" target="_blank"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>Unduh PDF</a>
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
                  <tr><td colspan="5"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada aktivitas tercatat</div></div></td></tr>
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
        // Toggle sub-tab Data Pengguna / Data Aktivitas / Data Pelaporan di dalam panel Arsip Data.
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

        // Hitung jumlah data yang tampil untuk kedua tabel Arsip Data, format
        // "X data ditampilkan" -- beda dari format global "X dari Y data" supaya
        // sama persis dengan rancangan.
        function dlHitungTampil(tableId) {
          var table = document.getElementById(tableId);
          var countEl = document.querySelector('[data-dl-count="' + tableId + '"]');
          if (!table) return;
          var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr[data-search-value]'));
          var visible = rows.filter(function (tr) { return tr.style.display !== 'none'; });
          if (countEl) countEl.textContent = visible.length + ' data ditampilkan';

          // Tabel ini gak punya baris ".empty-state" buat kasus "ada data tapi
          // kefilter jadi 0" (beda dari baris fallback forelse statis yang cuma
          // dirender kalau memang belum ada data sama sekali) -- makanya dulu search yang
          // gak nemu apa-apa cuma bikin tabel kelihatan kosong polos, tanpa
          // pesan/kotak apapun (cuma angka "0 data ditampilkan" yang berubah).
          // Sama kayak pola ensureEmptyRow() di danpus-report-table-filter.blade.php.
          var emptyRow = table.querySelector('tbody > tr.dl-filter-empty-row');
          if (rows.length && !visible.length) {
            if (!emptyRow) {
              var colCount = table.querySelectorAll('thead th').length || 1;
              emptyRow = document.createElement('tr');
              emptyRow.className = 'dl-filter-empty-row';
              emptyRow.innerHTML = '<td colspan="' + colCount + '"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Tidak ada data yang cocok dengan pencarian/filter.</div></div></td>';
              table.querySelector('tbody').appendChild(emptyRow);
            }
            emptyRow.style.display = '';
          } else if (emptyRow) {
            emptyRow.style.display = 'none';
          }
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

        // Konfigurasi tabel Arsip Data (Data Pengguna/Data Aktivitas) dalam
        // SATU array supaya nambah tabel baru ke depannya cukup nambah 1
        // entri di sini, gak perlu ubah tiap fungsi di bawah satu-satu
        // (dulu di-hardcode lewat ternary). "Data Pelaporan" TIDAK make
        // sistem ini -- itu grid kartu dengan search+filter+sort SENDIRI
        // (lihat initDlPelaporanFilter() di bawah, niru persis
        // initRiwayatCardFilter() Pimpinan, bukan tabel Dari/Sampai/Unduh).
        var dlTables = [
          { id: 'tblDlPengguna',  section: 'dl-pengguna',  dari: 'dlPenggunaDari',  sampai: 'dlPenggunaSampai',  reset: 'dlPenggunaReset' },
          { id: 'tblDlAktivitas', section: 'dl-aktivitas', dari: 'dlAktivitasDari', sampai: 'dlAktivitasSampai', reset: 'dlAktivitasReset' },
        ];
        function dlCfg(tableId) {
          for (var i = 0; i < dlTables.length; i++) { if (dlTables[i].id === tableId) return dlTables[i]; }
          return null;
        }

        dlTables.forEach(function (t) {
          var input = document.querySelector('[data-dl-search="' + t.id + '"]');
          if (input) input.addEventListener('input', function () { dlSaringGabungan(t.id); });
          dlHitungTampil(t.id);
        });

        // ── Filter tanggal + refresh untuk Arsip Data ──────────────────────
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

          table.querySelectorAll('[data-search-value]').forEach(function (tr) {
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
          var cfg = dlCfg(tableId);
          if (cfg) dlSaringTanggal(cfg.id, cfg.dari, cfg.sampai);
        }

        // Pasang listener pada input tanggal
        dlTables.forEach(function (t) {
          [document.getElementById(t.dari), document.getElementById(t.sampai)].forEach(function (el) {
            if (el) el.addEventListener('change', function () { dlSaringTanggal(t.id, t.dari, t.sampai); });
          });
        });

        // Override listener search supaya juga jalankan filter tanggal
        dlTables.forEach(function (t) {
          var input = document.querySelector('[data-dl-search="' + t.id + '"]');
          if (input) {
            // hapus listener lama (cloneNode), pasang yang baru
            var fresh = input.cloneNode(true);
            input.parentNode.replaceChild(fresh, input);
            fresh.addEventListener('input', function () { dlSaringGabungan(t.id); });
          }
        });

        // Tombol refresh/reset tanggal
        function buatResetHandler(dariId, sampaiId, tableId) {
          var cfg = dlCfg(tableId);
          var btn = cfg ? document.getElementById(cfg.reset) : null;
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
        dlTables.forEach(function (t) { buatResetHandler(t.dari, t.sampai, t.id); });

        // Dropdown filter kategori Arsip Data (Data Pengguna / Data Aktivitas / Data Pelaporan)
        dlTables.forEach(function (t) {
          var filterEl = document.querySelector('[data-dl-filter="' + t.id + '"]');
          if (filterEl) filterEl.addEventListener('change', function () { dlSaringTanggal(t.id, t.dari, t.sampai); });
        });

        // ── Sinkronkan link "Unduh" (CSV/Excel & PDF) dengan filter aktif ──
        // Tabel di layar cuma difilter lewat JS (tampil/sembunyi baris), jadi
        // tanpa ini tombol Unduh selalu mengambil SELURUH data dari server
        // walau kategori/tanggal/pencarian sedang dipilih. Query string yang
        // sama ('q', 'kategori', 'dari', 'sampai') dibaca ulang di
        // ReportController (exportUsersExcel/exportActivityExcel/
        // exportPelaporanExcel/printView).
        function dlBuildQuery(tableId, dariId, sampaiId) {
          var search = document.querySelector('[data-dl-search="' + tableId + '"]');
          var dariEl = document.getElementById(dariId);
          var sampaiEl = document.getElementById(sampaiId);
          var filterEl = document.querySelector('[data-dl-filter="' + tableId + '"]');
          var params = new URLSearchParams();
          if (search && search.value.trim()) params.set('q', search.value.trim());
          if (dariEl && dariEl.value) params.set('dari', dariEl.value);
          if (sampaiEl && sampaiEl.value) params.set('sampai', sampaiEl.value);
          if (filterEl && filterEl.value) params.set('kategori', filterEl.value);
          return params.toString();
        }

        function dlUpdateDownloadLinks(tableId, dariId, sampaiId) {
          var cfg = dlCfg(tableId);
          var section = cfg ? document.querySelector('[data-dl-section="' + cfg.section + '"]') : null;
          if (!section) return;
          var qs = dlBuildQuery(tableId, dariId, sampaiId);
          section.querySelectorAll('.dl-download-menu a[data-dl-base-href]').forEach(function (a) {
            var base = a.getAttribute('data-dl-base-href');
            a.setAttribute('href', qs ? (base + '?' + qs) : base);
          });
        }

        dlTables.forEach(function (t) {
          var tableId = t.id, dariId = t.dari, sampaiId = t.sampai;

          // Set awal saat halaman dimuat (mis. filter "Sampai" default hari ini).
          dlUpdateDownloadLinks(tableId, dariId, sampaiId);

          var search = document.querySelector('[data-dl-search="' + tableId + '"]');
          var dariEl = document.getElementById(dariId);
          var sampaiEl = document.getElementById(sampaiId);
          var filterEl = document.querySelector('[data-dl-filter="' + tableId + '"]');
          var resetBtn = document.getElementById(t.reset);

          if (search) search.addEventListener('input', function () { dlUpdateDownloadLinks(tableId, dariId, sampaiId); });
          if (dariEl) dariEl.addEventListener('change', function () { dlUpdateDownloadLinks(tableId, dariId, sampaiId); });
          if (sampaiEl) sampaiEl.addEventListener('change', function () { dlUpdateDownloadLinks(tableId, dariId, sampaiId); });
          if (filterEl) filterEl.addEventListener('change', function () { dlUpdateDownloadLinks(tableId, dariId, sampaiId); });
          if (resetBtn) resetBtn.addEventListener('click', function () { dlUpdateDownloadLinks(tableId, dariId, sampaiId); });
        });
      })();
      </script>

      {{-- Search+filter+sort kartu "Data Pelaporan" -- CSS .rpt-filter-* &
           JS di bawah niru PERSIS initRiwayatCardFilter() punya Pimpinan
           (danpus-permintaan-arsip-mode.blade.php): search box + dropdown
           status + dropdown urutan + counter "X dari Y data" + empty-state
           pencarian. Sengaja CSS-nya disalin langsung (bukan @include
           danpus-report-table-filter.blade.php) supaya gak ikut narik JS
           initReportFilter()/boot() punya file itu yang emang buat tabel
           Pimpinan lain (gak dipakai di sini, walau sebenarnya aman no-op
           karena section id-nya gak ada di Admin -- tetap lebih bersih
           kalau gak usah dimuat sama sekali). Beda dari kartu Riwayat
           Pimpinan: TANPA pin/tandai (gak ada tombolnya di kartu versi
           Admin ini) & TANPA animasi FLIP reorder (cuma reorder polos). --}}
      <style>
      .rpt-filter-bar{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:0 0 14px}
      .rpt-filter-search{position:relative;width:330px;max-width:100%}
      .rpt-filter-search svg{position:absolute;left:11px;top:50%;width:16px;height:16px;transform:translateY(-50%);color:var(--text-muted);pointer-events:none}
      .rpt-filter-search input{box-sizing:border-box;width:100%;height:38px;border:1px solid var(--border);border-radius:9px;outline:0;background:var(--panel-alt);color:var(--text);font:inherit;font-size:12px;padding:8px 11px 8px 35px}
      .rpt-filter-search input:focus{border-color:var(--gold-bright);box-shadow:0 0 0 3px rgba(201,122,0,.10)}
      .rpt-filter-search input::placeholder{color:var(--text-muted)}
      .rpt-filter-count{font-size:10px;color:var(--text-muted);white-space:nowrap;margin-left:auto}
      @media(max-width:700px){.rpt-filter-bar{gap:7px}.rpt-filter-search{width:100%}.rpt-filter-count{width:100%;margin-left:0}}

      {{-- Kartu Data Pelaporan kelihatan "menyatu" ke panel putih pembungkusnya
           -- BUKAN soal row-gap/box-shadow (keduanya sudah benar, sudah
           dicek lewat getComputedStyle: row-gap 64px & box-shadow ada).
           Penyebabnya: tema terang Admin punya --panel (#fff) & --panel-alt
           (#f8fafc, warna kartu) yang SENGAJA dibikin nyaris sama (palet
           "abu-abu netral/putih" dari sesi sebelumnya, beda dari Pimpinan
           yang krem vs abu-abu = kontras alami) -- jadi kartu & panel di
           belakangnya kebaca 1 warna polos. Kasih "tray" (latar --bg + garis
           tepi) di belakang grid supaya kartunya tetap kebaca terpisah, dan
           perkuat border kartu ke border-strong (bukan border-soft bawaan
           .deadline-sender-item) biar gak cuma mengandalkan beda warna latar
           yang tipis. --}}
      {{-- padding-top 80px (sebelumnya 60px, awalnya 22px) -- .dcard-icon
           punya margin-top:-42px (buat efek "ngambang" nongol di atas
           kartunya sendiri, lihat permintaan-laporan-deadline-styles.blade.php).
           Padding tray yang lebih tipis dari 42px bikin ikon baris PERTAMA
           nongol MELEWATI tepi atas tray, nabrak ke rpt-filter-bar
           (search/filter) di atasnya -- kelihatan dempet/nyatu lagi walau
           dari sisi lain (bukan ke panel, tapi ke search bar). 60px (42px
           margin ikon + ~18px jarak aman) ternyata masih kelihatan mepet di
           sebagian layar/skala font, jadi dilebarkan ke 80px (42px + ~38px
           jarak aman) supaya jarak ke rpt-filter-bar lebih lega. --}}
      #tblDlPelaporan{background:var(--bg);border:1px solid var(--border-soft);border-radius:14px;padding:80px 20px 8px;}
      #tblDlPelaporan .deadline-sender-item{border-color:var(--border-strong);}
      </style>
      {{-- Animasi kartu Data Pelaporan (masuk/keluar) -- kembar
           siberadPimpinanCardIn/Out punya Danpus/Wadan
           (danpus-permintaan-arsip-mode.blade.php), didefinisikan sendiri di
           sini karena partial itu gak dimuat di dashboard Admin. --}}
      <style>
      @keyframes adminDlCardIn{from{opacity:0;transform:translateY(-10px) scale(.98)}to{opacity:1;transform:none}}
      @keyframes adminDlCardOut{to{opacity:0;transform:translateY(-6px) scale(.96)}}
      #tblDlPelaporan .deadline-sender-item.siberad-dl-card-in{animation:adminDlCardIn .42s cubic-bezier(.2,.82,.2,1)}
      #tblDlPelaporan .deadline-sender-item.siberad-dl-card-out{animation:adminDlCardOut .3s ease forwards;pointer-events:none}
      @media(prefers-reduced-motion:reduce){#tblDlPelaporan .deadline-sender-item.siberad-dl-card-in,#tblDlPelaporan .deadline-sender-item.siberad-dl-card-out{animation:none!important}}
      </style>
      <script>
      (function(){
        function initDlPelaporanFilter(){
          var list=document.getElementById('tblDlPelaporan');
          var bar=document.getElementById('dlPelaporanFilterBar');
          if(!list||!bar)return;
          var input=document.getElementById('dlPelaporanSearch');
          var statusSelect=document.getElementById('dlPelaporanStatusFilter');
          var sortSelect=document.getElementById('dlPelaporanSort');
          var count=document.getElementById('dlPelaporanCount');

          var emptyBox=document.createElement('div');
          emptyBox.className='empty-state';emptyBox.style.display='none';
          emptyBox.innerHTML='<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Tidak ada permintaan laporan yang sesuai dengan pencarian/filter.</div>';
          list.parentNode.insertBefore(emptyBox,list.nextSibling);

          // "Belum ada permintaan laporan sama sekali" (dirender server pas
          // $semuaPelaporan kosong) -- beda dari emptyBox di atas (hasil
          // pencarian/filter kosong). Dipanggil ulang tiap siklus realtime
          // supaya kartu pertama yang masuk langsung nyingkirin pesan ini
          // tanpa perlu reload, kembar syncEmptyState() punya Danpus/Wadan.
          function syncEmptyState(){
            var hasItems=!!list.querySelector(':scope > article[data-realtime-permintaan-id]');
            var emptyNode=list.querySelector(':scope > .empty-state');
            if(!emptyNode)return;
            emptyNode.style.display=hasItems?'none':'';
            emptyNode.setAttribute('aria-hidden',hasItems?'true':'false');
          }

          function apply(){
            // Kartu yang lagi fade-out (data-removing) dikecualikan biar
            // animasi keluarnya nggak keganggu display:none/FLIP-reorder,
            // sama seperti apply() punya Danpus/Wadan.
            var items=Array.prototype.slice.call(list.querySelectorAll(':scope > article.deadline-sender-item'))
              .filter(function(el){return el.dataset.removing!=='1';});
            items.sort(function(a,b){
              var diff=Number(a.dataset.createdAt)-Number(b.dataset.createdAt);
              return sortSelect.value==='terlama'?diff:-diff;
            });
            var needsReorder=items.some(function(item,i){return item.nextElementSibling!==(items[i+1]||null)});
            if(needsReorder){
              var reduceMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
              var prevRects=reduceMotion?null:new Map();
              if(prevRects){items.forEach(function(item){if(item.style.display!=='none')prevRects.set(item,item.getBoundingClientRect());});}
              items.forEach(function(item){list.appendChild(item);});
              if(prevRects){
                items.forEach(function(item){
                  var prev=prevRects.get(item);if(!prev)return;
                  var next=item.getBoundingClientRect();
                  var dx=prev.left-next.left,dy=prev.top-next.top;
                  if(Math.abs(dx)<1&&Math.abs(dy)<1)return;
                  item.style.transition='none';item.style.transform='translate('+dx+'px,'+dy+'px)';
                  item.getBoundingClientRect();
                  (function(el){requestAnimationFrame(function(){requestAnimationFrame(function(){
                    el.style.transition='transform .58s cubic-bezier(.16,1,.3,1)';el.style.transform='';
                  });});})(item);
                  item.addEventListener('transitionend',function handler(e){if(e.propertyName!=='transform')return;item.style.transition='';item.removeEventListener('transitionend',handler);});
                });
              }
            }

            var q=(input.value||'').trim().toLowerCase();
            var statusFilter=statusSelect.value;
            var visible=0;
            items.forEach(function(item){
              var matchesSearch=!q||(item.dataset.search||'').indexOf(q)!==-1;
              var matchesStatus=statusFilter==='all'||item.dataset.status===statusFilter;
              var match=matchesSearch&&matchesStatus;
              item.style.display=match?'':'none';
              if(match)visible++;
            });
            count.textContent=visible+' dari '+items.length+' data';
            emptyBox.style.display=(items.length>0&&visible===0)?'block':'none';
          }
          input.addEventListener('input',apply);
          statusSelect.addEventListener('change',apply);
          sortSelect.addEventListener('change',apply);
          // Diekspos supaya sinkronisasi realtime di bawah bisa minta ulang
          // sort/filter/empty-state tiap ada kartu baru/berubah/hilang.
          window.siberadRefreshAdminDataPelaporanFilter=apply;
          window.siberadSyncAdminDataPelaporanEmptyState=syncEmptyState;
          apply();
          syncEmptyState();
        }
        if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initDlPelaporanFilter);else initDlPelaporanFilter();
      })();
      </script>

      {{-- Realtime + animasi kartu Data Pelaporan -- niru PERSIS mekanisme
           syncPimpinanCards() punya Danpus/Wadan (danpus-permintaan-arsip-
           mode.blade.php): poll tiap 3dt ke /permintaan-laporan/realtime,
           kartu di-diff per id (data-realtime-permintaan-id), yang berubah
           di-replaceWith() + animasi delta (progres bar/persen/"x/y tugas"/
           status pill), yang baru masuk slide-in, yang hilang (mis. dihapus
           lewat Reset Data Laporan) fade-out. Bedanya cuma scope: satu list
           gabungan (semua status+semua satuan, arsip maupun belum) sesuai
           $semuaPelaporan, bukan aktif/riwayat terpisah -- jadi tanpa
           loadHistory()/removeArchivedRows() endpoint kedua, dan TANPA pin/
           menu titik-3 (kartu Admin ini emang gak punya tombolnya). --}}
      <script>
      (function(){
        function initAdminDataPelaporanRealtime(){
          var list=document.getElementById('tblDlPelaporan');
          if(!list||list.dataset.realtimeBound==='1')return;
          list.dataset.realtimeBound='1';
          var endpoint='{{ route('permintaan-laporan.realtime') }}?admin=1';
          var busy=false;

          function prefersReduce(){return window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;}

          // Bandingkan kartu tanpa style/kelas transient yang dikelola klien
          // (animasi masuk/keluar, sisa style FLIP) -- biar cuma ke-replace
          // kalau data server-nya yang beneran beda. Kembar cardSignature()
          // Danpus/Wadan, tanpa bagian pin/menu karena kartu ini gak punya.
          function cardSignature(el){
            var c=el.cloneNode(true);
            c.removeAttribute('style');
            c.classList.remove('siberad-dl-card-in','siberad-dl-card-out');
            c.querySelectorAll('[style]').forEach(function(n){
              ['transition','-webkit-transition','opacity','transform'].forEach(function(p){n.style.removeProperty(p);});
              var cs=n.style.cssText;
              if(cs)n.setAttribute('style',cs);else n.removeAttribute('style');
            });
            return c.outerHTML.replace(/>\s+/g,'>').replace(/\s+</g,'<');
          }
          function playOnce(card,cls,ms){
            card.classList.add(cls);
            var done=function(){card.classList.remove(cls);card.removeEventListener('animationend',done);clearTimeout(t);};
            var t=setTimeout(done,ms);
            card.addEventListener('animationend',done);
          }
          function enterCard(card){ if(!prefersReduce())playOnce(card,'siberad-dl-card-in',700); }

          function cardSnapshot(card){
            var val=card.querySelector('.dcard-progress-value');
            var fill=card.querySelector('.dcard-progress-fill');
            var tasks=card.querySelector('.dcard-tasks-summary');
            var pill=card.querySelector('.dcard-status-pill');
            var tasksText=tasks?tasks.textContent.replace(/\s+/g,' ').trim():'';
            return {
              pct: val?(parseInt((val.textContent||'').replace(/\D/g,''),10)||0):null,
              fillW: fill?(fill.style.width||''):null,
              tasksText: tasksText,
              tasksNums: tasksText.match(/^(\d+)\s*\/\s*(\d+)/),
              statusText: pill?pill.textContent.replace(/\s+/g,' ').trim():null,
              statusClass: pill?pill.className:null
            };
          }
          function tweenNum(from,to,ms,onStep){
            from=Number(from)||0;to=Number(to)||0;
            if(from===to){onStep(to);return;}
            var t0=performance.now();
            (function frame(now){
              var p=Math.min(1,(now-t0)/ms);
              var e=1-Math.pow(1-p,3);
              onStep(p>=1?to:(from+(to-from)*e));
              if(p<1)requestAnimationFrame(frame);
            })(performance.now());
          }
          function setTasksText(el,text){
            Array.prototype.slice.call(el.childNodes).forEach(function(n){if(n.nodeType===3)n.remove();});
            el.appendChild(document.createTextNode(text));
          }
          function crossfadeText(el,oldText,newText,isTasks){
            isTasks?setTasksText(el,oldText):(el.textContent=oldText);
            el.style.transition='none';el.style.opacity='1';
            void el.offsetWidth;
            el.style.transition='opacity .16s ease';
            el.style.opacity='0';
            setTimeout(function(){
              isTasks?setTasksText(el,newText):(el.textContent=newText);
              el.style.opacity='0';void el.offsetWidth;el.style.opacity='1';
              setTimeout(function(){el.style.transition='';el.style.opacity='';},200);
            },170);
          }
          function animateCardDelta(freshCard,old){
            if(prefersReduce())return;
            var fill=freshCard.querySelector('.dcard-progress-fill');
            var val=freshCard.querySelector('.dcard-progress-value');
            var tasks=freshCard.querySelector('.dcard-tasks-summary');
            var pill=freshCard.querySelector('.dcard-status-pill');
            if(fill&&old.fillW!=null){
              var target=fill.style.width||'';
              if(target!==old.fillW){
                fill.style.transition='none';fill.style.width=old.fillW;
                void fill.offsetWidth;
                fill.style.transition='width .7s cubic-bezier(.4,0,.2,1)';
                requestAnimationFrame(function(){fill.style.width=target;});
                setTimeout(function(){fill.style.transition='';},820);
              }
            }
            if(val&&old.pct!=null){
              var target2=parseInt((val.textContent||'').replace(/\D/g,''),10)||0;
              if(target2!==old.pct)tweenNum(old.pct,target2,700,function(v){val.textContent=Math.round(v)+'%';});
            }
            if(tasks&&old.tasksText){
              var now=tasks.textContent.replace(/\s+/g,' ').trim();
              if(now!==old.tasksText){
                var m=now.match(/^(\d+)\s*\/\s*(\d+)/);
                if(m&&old.tasksNums&&m[2]===old.tasksNums[2]){
                  var y=m[2];
                  tweenNum(parseInt(old.tasksNums[1],10),parseInt(m[1],10),700,function(v){setTasksText(tasks,Math.round(v)+'/'+y+' tugas selesai');});
                }else{
                  crossfadeText(tasks,old.tasksText,now,true);
                }
              }
            }
            if(pill&&old.statusText!=null){
              var newText=pill.textContent.replace(/\s+/g,' ').trim();
              var newClass=pill.className;
              if(newText!==old.statusText||newClass!==old.statusClass){
                pill.textContent=old.statusText;pill.className=old.statusClass;
                pill.style.transition='none';pill.style.opacity='1';pill.style.transform='none';
                void pill.offsetWidth;
                pill.style.transition='opacity .17s ease,transform .17s ease';
                pill.style.opacity='0';pill.style.transform='translateY(-3px)';
                setTimeout(function(){
                  pill.textContent=newText;pill.className=newClass;
                  pill.style.opacity='0';pill.style.transform='translateY(3px)';
                  void pill.offsetWidth;
                  pill.style.opacity='1';pill.style.transform='none';
                  setTimeout(function(){pill.style.transition='';pill.style.transform='';pill.style.opacity='';},240);
                },180);
              }
            }
          }

          function afterChange(){
            window.siberadRefreshAdminDataPelaporanFilter&&window.siberadRefreshAdminDataPelaporanFilter();
            window.siberadSyncAdminDataPelaporanEmptyState&&window.siberadSyncAdminDataPelaporanEmptyState();
          }

          // Kartu yang gak ada lagi di respon server (mis. dihapus lewat
          // Reset Data Laporan) di-fade-out lalu dibuang -- kembar
          // syncRiwayatCards() punya Danpus/Wadan.
          function removeMissingCards(freshIds){
            var reduce=prefersReduce();
            var changed=false;
            Array.prototype.slice.call(list.querySelectorAll(':scope > article[data-realtime-permintaan-id]')).forEach(function(card){
              var id=card.dataset.realtimePermintaanId;
              if(freshIds[id])return;
              changed=true;
              if(reduce){card.remove();return;}
              if(card.dataset.removing==='1')return;
              card.dataset.removing='1';
              var fin=function(){card.remove();clearTimeout(t);afterChange();};
              var t=setTimeout(fin,380);
              card.addEventListener('animationend',fin,{once:true});
              card.classList.add('siberad-dl-card-out');
            });
            return changed;
          }

          async function sync(){
            if(busy||document.hidden)return;
            busy=true;
            try{
              var r=await fetch(endpoint+'&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}});
              if(!r.ok)return;
              var data=await r.json();
              if(typeof data.items_html!=='string')return;
              var holder=document.createElement('div');holder.innerHTML=data.items_html.trim();
              var fresh=Array.prototype.slice.call(holder.querySelectorAll(':scope > article.deadline-sender-item'));
              var freshIds={};fresh.forEach(function(c){var id=c.getAttribute('data-realtime-permintaan-id');if(id)freshIds[id]=true;});

              var changed=removeMissingCards(freshIds);
              fresh.forEach(function(freshCard){
                var id=freshCard.getAttribute('data-realtime-permintaan-id');if(!id)return;
                var current=list.querySelector(':scope > article[data-realtime-permintaan-id="'+id+'"]');
                if(current){
                  if(cardSignature(current)===cardSignature(freshCard))return;
                  var wasHidden=current.style.display==='none';
                  var snap=cardSnapshot(current);
                  current.replaceWith(freshCard);
                  if(wasHidden)freshCard.style.display='none';
                  animateCardDelta(freshCard,snap);
                  window.siberadRefreshPimpinanProgres&&window.siberadRefreshPimpinanProgres(freshCard);
                  changed=true;
                }else{
                  list.insertBefore(freshCard,list.firstChild);
                  enterCard(freshCard);
                  changed=true;
                }
              });
              if(changed)afterChange();
            }catch(e){}
            finally{busy=false;}
          }

          sync();
          window.setInterval(function(){if(!document.hidden)sync();},3000);
          document.addEventListener('visibilitychange',function(){if(!document.hidden)sync();});
        }
        if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initAdminDataPelaporanRealtime);else initAdminDataPelaporanRealtime();
      })();
      </script>


      {{-- ===== PENGATURAN UMUM ===== --}}
      <section class="tab-panel" data-tab-panel="pengaturan-umum">
        {{-- ===== BANNER RINGKASAN (gaya kartu referensi: ikon bulat +
             judul/subjudul di kiri, ilustrasi dekoratif di kanan, latar
             gradasi lembut) -- menggantikan header polos ".section-head"
             lama. Class dasar ".section-head" TETAP dipakai (dipakai juga
             oleh tab lain), tambahan ".lp-hero-banner" cuma nambah style,
             bukan gantiin. ===== --}}
        <div class="section-head panel lp-hero-banner">
          <div class="lp-hero-banner-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          </div>
          <div class="lp-hero-banner-text">
            <h2>Pengaturan Konten Halaman Landing</h2>
            <p>Kelola konten halaman landing sesuai kebutuhan — atur identitas brand, hero, tentang, fitur, dan kontak di bawah ini.</p>
          </div>
          <div class="lp-hero-banner-art" aria-hidden="true">
            <svg viewBox="0 0 120 90" fill="none">
              <rect x="4" y="6" width="72" height="50" rx="8" fill="rgba(255,255,255,.16)" stroke="rgba(255,255,255,.35)" stroke-width="1.5"/>
              <circle cx="20" cy="20" r="6" fill="rgba(255,255,255,.5)"/>
              <path d="M9 46 26 30l10 9 14-15 18 22H9Z" fill="rgba(255,255,255,.32)"/>
              <rect x="46" y="30" width="62" height="46" rx="8" fill="rgba(255,255,255,.24)" stroke="rgba(255,255,255,.4)" stroke-width="1.5"/>
              <circle cx="77" cy="53" r="13" fill="none" stroke="rgba(255,255,255,.55)" stroke-width="2.4"/>
              <path d="M85 61l7 7" stroke="rgba(255,255,255,.55)" stroke-width="2.4" stroke-linecap="round"/>
            </svg>
          </div>
        </div>

        @php
          // Verifikasi logo/gambar latar beranda BENAR-BENAR ada secara fisik
          // di disk, bukan cuma percaya kolom logo_path/hero_image_path
          // terisi -- kalau path-nya "dangling" (file sudah tidak ada, mis.
          // gara-gara upload gagal senyap sebelum fix di
          // SettingController::storeVerifiedImage(), lihat commit 9fbcbc21),
          // panel ini dulu menampilkan <img> rusak padahal data-has-current
          // masih "1". Sekarang kalau file-nya gak ketemu, panel ini ikut
          // nunjukin kotak "Belum ada logo/gambar" yang sama seperti kalau
          // memang belum pernah di-upload.
          $pengaturanLogoExists = $pengaturan->logo_path
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($pengaturan->logo_path);
          $pengaturanHeroExists = $pengaturan->hero_image_path
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($pengaturan->hero_image_path);
          $pengaturanHeroVideoExists = $pengaturan->hero_video_path
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($pengaturan->hero_video_path);
          $pengaturanHeroBgType = old('hero_bg_type', $pengaturan->hero_bg_type ?? 'gambar');
          $pengaturanStrukturOrgExists = $pengaturan->struktur_organisasi_path
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($pengaturan->struktur_organisasi_path);
          // Nilai awal slider Blur Latar & Kepekatan Overlay, dipakai buat
          // ngisi posisi awal "fill" track custom (var CSS --lp-range-fill)
          // supaya pas pertama kali kebuka track-nya udah keisi sesuai
          // angka tersimpan, bukan mulai dari 0 dulu baru keupdate pas
          // slider digeser.
          $pengaturanHeroBlur = (int) old('hero_blur_level', $pengaturan->hero_blur_level ?? 0);
          $pengaturanHeroOverlay = (int) old('hero_overlay_intensity', $pengaturan->hero_overlay_intensity ?? 100);
        @endphp

        {{-- ===== KONTEN HALAMAN LANDING ===== --}}
        {{-- Header, tab Beranda/Fitur/Tentang/Kontak, & banner keterangan
             tab sekarang balik jadi SATU kartu yang sama dengan judul
             "Konten Halaman Landing" (sesuai permintaan) -- supaya tombol
             tab & keterangan "Bagian paling atas landing page..." tidak
             lagi kelihatan mengambang terpisah di luar kartu judulnya.
             Yang membedakan tiap kelompok field (Judul & Deskripsi Utama,
             Gambar Latar Beranda, dst) dari kartu induk ini SEKARANG bukan
             lagi bingkai/aksen warna terpisah (lihat aturan ".lp-card" di
             bawah yang sudah diratakan, tanpa aksen emas), melainkan cuma
             pemisah tipis + lebar yang SAMA PERSIS dengan kartu induk. --}}
        <div class="lp-layout">

          {{-- <form> sekarang membungkus SEMUA kartu editor (baik "Konten
               Halaman Landing" maupun kartu-kartu terpisah lainnya seperti
               "Judul & Deskripsi Utama"), bukan cuma satu kartu -- supaya
               kartu-kartu itu bisa jadi kotak sejajar terpisah (sesuai
               permintaan) tapi tetap satu form yang sama saat disimpan. --}}
          <form id="landingForm" method="POST" action="{{ route('admin.pengaturan.landing.update') }}" enctype="multipart/form-data" data-current-logo="{{ $pengaturanLogoExists ? asset('storage/'.$pengaturan->logo_path) : '' }}" data-logo-delete-url="{{ route('admin.pengaturan.landing.image.destroy', 'logo') }}">
            @csrf @method('PATCH')

            {{-- ---------- PANEL EDITOR ---------- --}}
            <div class="panel lp-panel lp-overview-wrap">
              <div class="panel-head">
                <div>
                  <h3>Konten Halaman Landing</h3>
                  <p>Pilih kartu bagian yang mau diedit di bawah ini, lalu lihat hasilnya di panel pratinjau.</p>
                </div>
              </div>

              {{-- ===== GRID KARTU RINGKASAN (gaya referensi: ikon bulat
                   warna, judul, deskripsi singkat, chevron di kanan).
                   Setiap kartu TETAP pakai data-lp-tab yang sama seperti
                   pill-tab lama -- diklik, JS di bawah ("tab switching")
                   otomatis panggil activateTab(name) yang sama persis
                   seperti sebelumnya (fungsinya tidak berubah), lalu
                   men-scroll ke kelompok field yang bersangkutan lewat
                   data-lp-scroll-target (id panel field-nya). Dua kartu
                   bisa mengarah ke tab yang sama (mis. "Latar Belakang
                   Beranda" & "Judul & Deskripsi Utama" sama-sama tab
                   "beranda") karena memang keduanya sudah satu kelompok
                   show/hide yang sama dari dulu -- jadi pemisahan jadi 6
                   kartu di sini murni tampilan, bukan fungsi baru. ===== --}}
              <div class="lp-overview-grid">
                <button type="button" class="lp-overview-card" data-lp-tab="beranda" data-lp-scroll-target="lpPanelHeroBg">
                  <span class="lp-ov-icon lp-ov-gold" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="m4.5 18 5-5.5 3 3 3.5-4L20.5 18"/></svg></span>
                  <span class="lp-ov-body">
                    <span class="lp-ov-title">Latar Belakang Beranda</span>
                    <span class="lp-ov-desc">Atur gambar atau video latar, blur, dan overlay warna pada bagian hero.</span>
                  </span>
                  <svg class="lp-ov-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>

                <button type="button" class="lp-overview-card" data-lp-tab="beranda" data-lp-scroll-target="lpPanelHeroText">
                  <span class="lp-ov-icon lp-ov-purple" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg></span>
                  <span class="lp-ov-body">
                    <span class="lp-ov-title">Judul &amp; Deskripsi Utama</span>
                    <span class="lp-ov-desc">Atur label kecil, judul, sub judul, dan deskripsi utama pada bagian hero.</span>
                  </span>
                  <svg class="lp-ov-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>

                <button type="button" class="lp-overview-card" data-lp-tab="tentang" data-lp-scroll-target="lpPanelTentang">
                  <span class="lp-ov-icon lp-ov-green" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V8l8-5 8 5v13"/><path d="M9 21v-6h6v6"/></svg></span>
                  <span class="lp-ov-body">
                    <span class="lp-ov-title">Tentang &amp; Profil Instansi</span>
                    <span class="lp-ov-desc">Kelola deskripsi profil, identitas instansi, moto, dan makna logo.</span>
                  </span>
                  <svg class="lp-ov-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>

                <button type="button" class="lp-overview-card" data-lp-tab="fitur" data-lp-scroll-target="lpPanelFitur">
                  <span class="lp-ov-icon lp-ov-amber" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></span>
                  <span class="lp-ov-body">
                    <span class="lp-ov-title">Fitur Unggulan</span>
                    <span class="lp-ov-desc">Atur judul dan deskripsi kartu fitur yang tampil di bagian "Fitur".</span>
                  </span>
                  <svg class="lp-ov-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>

                <button type="button" class="lp-overview-card" data-lp-tab="kontak" data-lp-scroll-target="lpPanelKontakInfo">
                  <span class="lp-ov-icon lp-ov-pink" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12a10 10 0 1 1-5.6-9"/><path d="M15 8l4-4"/><path d="M15 4h4v4"/></svg></span>
                  <span class="lp-ov-body">
                    <span class="lp-ov-title">Informasi Kontak</span>
                    <span class="lp-ov-desc">Atur alamat, email, telepon, dan website yang tampil di footer.</span>
                  </span>
                  <svg class="lp-ov-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>

                <button type="button" class="lp-overview-card" data-lp-tab="kontak" data-lp-scroll-target="lpPanelKontakSosmed">
                  <span class="lp-ov-icon lp-ov-red" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-3.9M8.6 13.5l6.8 3.9"/></svg></span>
                  <span class="lp-ov-body">
                    <span class="lp-ov-title">Sosial Media</span>
                    <span class="lp-ov-desc">Atur label dan tautan akun sosial media di footer.</span>
                  </span>
                  <svg class="lp-ov-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
              </div>
            </div>

            {{-- ---------- WADAH PENYIMPANAN PANEL EDITOR (TERSEMBUNYI TOTAL DARI HALAMAN) ----------
                 Semua panel editor di bawah ini disimpan di dalam #lpPanelsStore dengan display:none !important.
                 Panel TIDAK AKAN PERNAH muncul menumpuk di alur halaman utama.
                 Saat kartu pintasan diklik, JS memindahkan panel terkait ke dalam Modal Dialog di bawah.
                 Saat modal ditutup atau form di-submit, panel dikembalikan ke posisi asal di sini. --}}
            <div class="lp-panels-store" id="lpPanelsStore" style="display:none !important;" aria-hidden="true">

              {{-- ---------- PANEL "LATAR BELAKANG BERANDA" ---------- --}}
              <div class="panel lp-panel lp-tab-panel" data-lp-tab-panel="beranda" id="lpPanelHeroBg">
                <div class="panel-head">
                  <div>
                    <h3>Latar Belakang Beranda</h3>
                    <p>Latar bagian hero (opsional) — bisa berupa gambar diam atau video singkat yang berputar otomatis.</p>
                  </div>
                </div>
                <div class="form-grid">
                  <div class="form-field full">
                    <label style="display:block;margin-bottom:8px;">Tipe Latar Belakang</label>
                    <div class="lp-bg-type-toggle" role="radiogroup" aria-label="Tipe latar belakang beranda">
                      <label class="lp-bg-type-option">
                        <input type="radio" name="hero_bg_type" value="gambar" data-lp-bg-type-radio @checked($pengaturanHeroBgType !== 'video')>
                        <span class="lp-bg-type-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><circle cx="9" cy="10" r="1.8"></circle><path d="m4.5 18 5-5.5 3 3 3.5-4L20.5 18"></path></svg></span>
                        <span>Gambar</span>
                      </label>
                      <label class="lp-bg-type-option">
                        <input type="radio" name="hero_bg_type" value="video" data-lp-bg-type-radio @checked($pengaturanHeroBgType === 'video')>
                        <span class="lp-bg-type-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="m10 9 5 3-5 3z"></path></svg></span>
                        <span>Video</span>
                      </label>
                    </div>
                    <small>Beralih tipe tidak menghapus file yang sudah diunggah sebelumnya — kalau nanti mau balik lagi, tidak perlu unggah ulang.</small>
                  </div>

                  {{-- ----- Sub-opsi: GAMBAR ----- --}}
                  <div class="form-field full lp-bg-type-panel" data-lp-bg-type-panel="gambar">
                    <label for="lpHeroImage" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">Gambar Latar Beranda</label>
                    <div class="lp-hero-image-row">
                      <input id="lpHeroImage" name="hero_image" type="file" accept="image/*" data-lp-image="hero_image" data-has-current="{{ $pengaturanHeroExists ? '1' : '0' }}" data-label-existing="Ganti Gambar">
                      <div class="lp-hero-preview-frame" id="lpHeroImagePreviewFrame" style="{{ $pengaturanHeroExists ? '' : 'display:none' }}">
                        <img src="{{ $pengaturanHeroExists ? asset('storage/'.$pengaturan->hero_image_path) : '' }}" alt="Gambar beranda saat ini" class="lp-current-image" id="lpHeroImagePreviewImg" style="filter:blur({{ $pengaturanHeroBlur }}px);">
                        <div class="lp-hero-preview-overlay" id="lpHeroImagePreviewOverlay" style="opacity:{{ $pengaturanHeroOverlay / 100 }};"></div>
                      </div>
                      <div class="lp-image-placeholder" id="lpHeroImagePreviewPlaceholder" style="{{ $pengaturanHeroExists ? 'display:none' : '' }}">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><circle cx="9" cy="10" r="1.8"></circle><path d="m4.5 18 5-5.5 3 3 3.5-4L20.5 18"></path></svg>
                        <span>Belum ada gambar latar belakang</span>
                      </div>
                      <button type="button" class="btn btn-ghost-red lp-delete-img-btn" id="lpHeroImageDeleteBtn" style="{{ $pengaturanHeroExists ? '' : 'display:none' }}" onclick="window.bukaHapusLandingGambar(this)" data-action="{{ route('admin.pengaturan.landing.image.destroy', 'hero_image') }}" data-nama="Gambar Latar Belakang Beranda">Hapus Gambar</button>
                    </div>
                    <small>Format JPG, PNG, atau WEBP · maksimal 5 MB.</small>
                  </div>

                  {{-- ----- Sub-opsi: VIDEO ----- --}}
                  <div class="form-field full lp-bg-type-panel" data-lp-bg-type-panel="video" style="display:none;">
                    <label for="lpHeroVideo" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">Video Latar Beranda</label>
                    <div class="lp-hero-image-row">
                      <input id="lpHeroVideo" name="hero_video" type="file" accept="video/mp4,video/webm,video/quicktime,.mov" data-lp-video="hero_video" data-has-current="{{ $pengaturanHeroVideoExists ? '1' : '0' }}" data-label-existing="Ganti Video">
                      <div class="lp-hero-preview-frame" id="lpHeroVideoPreviewFrame" style="{{ $pengaturanHeroVideoExists ? '' : 'display:none' }}">
                        <video src="{{ $pengaturanHeroVideoExists ? asset('storage/'.$pengaturan->hero_video_path) : '' }}" class="lp-current-image" id="lpHeroVideoPreviewVideo" muted loop autoplay playsinline style="filter:blur({{ $pengaturanHeroBlur }}px);"></video>
                        <div class="lp-hero-preview-overlay" id="lpHeroVideoPreviewOverlay" style="opacity:{{ $pengaturanHeroOverlay / 100 }};"></div>
                      </div>
                      <div class="lp-image-placeholder" id="lpHeroVideoPreviewPlaceholder" style="{{ $pengaturanHeroVideoExists ? 'display:none' : '' }}">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="m10 9 5 3-5 3z"></path></svg>
                        <span>Belum ada video latar belakang</span>
                      </div>
                      <button type="button" class="btn btn-ghost-red lp-delete-img-btn" id="lpHeroVideoDeleteBtn" style="{{ $pengaturanHeroVideoExists ? '' : 'display:none' }}" onclick="window.bukaHapusLandingGambar(this)" data-action="{{ route('admin.pengaturan.landing.image.destroy', 'hero_video') }}" data-nama="Video Latar Belakang Beranda">Hapus Video</button>
                    </div>
                    <small>Format MP4, WEBM, atau MOV · maksimal 100 MB · durasi maksimal 1 menit · sebaiknya tanpa suara karena akan berputar otomatis (looping) tanpa audio.</small>
                  </div>

                  <div class="form-field lp-range-field">
                    <div class="lp-range-head">
                      <label for="lpHeroBlur"><span class="lp-range-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><circle cx="12" cy="12" r="7" stroke-dasharray="2.2 3.4" opacity=".75"></circle><circle cx="12" cy="12" r="10.5" stroke-dasharray="1.2 4" opacity=".4"></circle></svg></span>Blur Latar</label>
                      <span class="lp-range-badge"><span id="lpHeroBlurVal">{{ $pengaturanHeroBlur }}</span><small>px</small></span>
                    </div>
                    <div class="lp-range-shell">
                      <input id="lpHeroBlur" name="hero_blur_level" type="range" min="0" max="20" step="1" value="{{ $pengaturanHeroBlur }}" class="lp-range" data-lp="hero_blur_level" style="--lp-range-fill:{{ round($pengaturanHeroBlur / 20 * 100, 2) }}%" oninput="document.getElementById('lpHeroBlurVal').textContent=this.value;this.style.setProperty('--lp-range-fill',(this.value/this.max*100)+'%');">
                    </div>
                    <small>0 = tajam, 20 = paling buram.</small>
                  </div>
                  <div class="form-field lp-range-field">
                    <div class="lp-range-head">
                      <label for="lpHeroOverlay"><span class="lp-range-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 9 5-9 5-9-5 9-5Z"></path><path d="m3 13 9 5 9-5"></path></svg></span>Kepekatan Overlay Warna</label>
                      <span class="lp-range-badge"><span id="lpHeroOverlayVal">{{ $pengaturanHeroOverlay }}</span><small>%</small></span>
                    </div>
                    <div class="lp-range-shell">
                      <input id="lpHeroOverlay" name="hero_overlay_intensity" type="range" min="0" max="100" step="1" value="{{ $pengaturanHeroOverlay }}" class="lp-range" data-lp="hero_overlay_intensity" style="--lp-range-fill:{{ $pengaturanHeroOverlay }}%" oninput="document.getElementById('lpHeroOverlayVal').textContent=this.value;this.style.setProperty('--lp-range-fill',(this.value/this.max*100)+'%');">
                    </div>
                    <small>0 = tanpa overlay, 100 = paling pekat.</small>
                  </div>
                </div>
              </div>

              {{-- ---------- PANEL "JUDUL & DESKRIPSI UTAMA" ---------- --}}
              <div class="panel lp-panel lp-tab-panel" data-lp-tab-panel="beranda" id="lpPanelHeroText">
                <div class="panel-head">
                  <div>
                    <h3>Judul &amp; Deskripsi Utama</h3>
                    <p>Teks utama yang tampil di bagian paling atas (hero) landing page.</p>
                  </div>
                </div>
                <div class="form-grid">
                  <div class="form-field full">
                    <label for="lpEyebrow">Label Kecil di Atas Judul</label>
                    <input id="lpEyebrow" name="hero_eyebrow" type="text" value="{{ old('hero_eyebrow', $pengaturan->hero_eyebrow) }}" data-lp="hero_eyebrow">
                  </div>
                  <div class="form-field">
                    <label for="lpJudulAwal">Judul (bagian 1) — juga jadi nama sistem di logo &amp; sidebar semua pengguna</label>
                    <input id="lpJudulAwal" name="hero_judul_awal" type="text" value="{{ old('hero_judul_awal', $pengaturan->hero_judul_awal) }}" data-lp="hero_judul_awal">
                  </div>
                  <div class="form-field">
                    <label for="lpJudulAksen">Judul (bagian 2, warna emas) — juga ikut di logo &amp; sidebar semua pengguna</label>
                    <input id="lpJudulAksen" name="hero_judul_aksen" type="text" value="{{ old('hero_judul_aksen', $pengaturan->hero_judul_aksen) }}" data-lp="hero_judul_aksen">
                  </div>
                  <div class="form-field full">
                    <label for="lpSubjudul">Sub Judul</label>
                    <input id="lpSubjudul" name="hero_subjudul" type="text" value="{{ old('hero_subjudul', $pengaturan->hero_subjudul) }}" data-lp="hero_subjudul">
                  </div>
                  <div class="form-field full">
                    <label for="lpDeskripsi">Deskripsi</label>
                    <textarea id="lpDeskripsi" name="hero_deskripsi" rows="3" data-lp="hero_deskripsi">{{ old('hero_deskripsi', $pengaturan->hero_deskripsi) }}</textarea>
                  </div>
                </div>
              </div>

              {{-- ---------- PANEL(S) "FITUR N" ---------- --}}
              @foreach ((old('fitur') ?? $pengaturan->fitur ?? []) as $i => $fitur)
                <div class="panel lp-panel lp-tab-panel" data-lp-tab-panel="fitur" data-lp-group="lpPanelFitur" @if($i === 0) id="lpPanelFitur" @endif>
                  <div class="panel-head">
                    <div>
                      <h3>Fitur {{ $i + 1 }}</h3>
                    </div>
                  </div>
                  <div class="form-grid">
                    <div class="form-field full">
                      <label for="lpFiturJudul{{ $i }}">Judul</label>
                      <input id="lpFiturJudul{{ $i }}" name="fitur[{{ $i }}][judul]" type="text" value="{{ is_array($fitur) ? $fitur['judul'] : '' }}" data-lp="fitur_judul_{{ $i }}" required>
                    </div>
                    <div class="form-field full">
                      <label for="lpFiturDesk{{ $i }}">Deskripsi</label>
                      <textarea id="lpFiturDesk{{ $i }}" name="fitur[{{ $i }}][deskripsi]" rows="2" data-lp="fitur_deskripsi_{{ $i }}" required>{{ is_array($fitur) ? $fitur['deskripsi'] : '' }}</textarea>
                    </div>
                  </div>
                </div>
              @endforeach

              {{-- ---------- PANEL "DESKRIPSI PROFIL INSTANSI" ---------- --}}
              <div class="panel lp-panel lp-tab-panel" data-lp-tab-panel="tentang" id="lpPanelTentang">
                <div class="panel-head">
                  <div>
                    <h3>Deskripsi Profil Instansi</h3>
                    <p>Paragraf profil singkat instansi yang tampil di bagian "Tentang" landing page.</p>
                  </div>
                </div>
                <div class="form-grid">
                  <div class="form-field full">
                    <label for="lpTentangDeskripsi">Deskripsi Tentang (pisahkan paragraf dengan baris kosong)</label>
                    <textarea id="lpTentangDeskripsi" name="tentang_deskripsi" rows="10" data-lp="tentang_deskripsi">{{ old('tentang_deskripsi', $pengaturan->tentang_deskripsi) }}</textarea>
                  </div>
                </div>
              </div>

              {{-- ---------- PANEL "IDENTITAS INSTANSI" ---------- --}}
              <div class="panel lp-panel lp-tab-panel" data-lp-tab-panel="tentang" data-lp-group="lpPanelTentang">
                <div class="panel-head">
                  <div>
                    <h3>Identitas Instansi</h3>
                    <p>Tiga kartu identitas (Nama Resmi, Nama Lama, Fungsi Utama) yang tampil di bagian "Tentang" landing page.</p>
                  </div>
                </div>
                <div class="form-grid">
                  <div class="form-field">
                    <label for="lpNamaResmi">Nama Resmi</label>
                    <input id="lpNamaResmi" name="tentang_nama_resmi" type="text" value="{{ old('tentang_nama_resmi', $pengaturan->tentang_nama_resmi) }}" data-lp="tentang_nama_resmi">
                  </div>
                  <div class="form-field">
                    <label for="lpNamaLama">Nama Lama</label>
                    <input id="lpNamaLama" name="tentang_nama_lama" type="text" value="{{ old('tentang_nama_lama', $pengaturan->tentang_nama_lama) }}" data-lp="tentang_nama_lama">
                  </div>
                  <div class="form-field full">
                    <label for="lpFungsiUtama">Fungsi Utama</label>
                    <textarea id="lpFungsiUtama" name="tentang_fungsi_utama" rows="3" data-lp="tentang_fungsi_utama">{{ old('tentang_fungsi_utama', $pengaturan->tentang_fungsi_utama) }}</textarea>
                  </div>
                </div>
              </div>

              {{-- ---------- PANEL "MOTO" ---------- --}}
              <div class="panel lp-panel lp-tab-panel" data-lp-tab-panel="tentang" data-lp-group="lpPanelTentang">
                <div class="panel-head">
                  <div>
                    <h3>Moto</h3>
                    <p>Judul &amp; penjelasan moto instansi yang tampil di bagian "Tentang" landing page.</p>
                  </div>
                </div>
                <div class="form-grid">
                  <div class="form-field full">
                    <label for="lpMotoJudul">Judul Moto</label>
                    <input id="lpMotoJudul" name="tentang_moto_judul" type="text" value="{{ old('tentang_moto_judul', $pengaturan->tentang_moto_judul) }}" data-lp="tentang_moto_judul">
                  </div>
                  <div class="form-field full">
                    <label for="lpMotoDeskripsi">Deskripsi Moto</label>
                    <textarea id="lpMotoDeskripsi" name="tentang_moto_deskripsi" rows="3" data-lp="tentang_moto_deskripsi">{{ old('tentang_moto_deskripsi', $pengaturan->tentang_moto_deskripsi) }}</textarea>
                  </div>
                </div>
              </div>

              {{-- ---------- PANEL "MAKNA LOGO" ---------- --}}
              <div class="panel lp-panel lp-tab-panel" data-lp-tab-panel="tentang" data-lp-group="lpPanelTentang">
                <div class="panel-head">
                  <div>
                    <h3>Makna Logo</h3>
                    <p>
                      10 poin keterangan makna lambang. Saat pengunjung mengklik logo di bagian "Tentang" landing page,
                      muncul jendela berisi lambang yang membesar ke tengah beserta 10 kartu keterangan bernomor
                      (1-10) di sekelilingnya -- isi Judul &amp; Keterangan tiap poin di bawah ini sesuai nomornya.
                    </p>
                  </div>
                </div>
                @foreach ((old('makna_logo') ?? $pengaturan->makna_logo ?? \App\Models\Pengaturan::defaultMaknaLogo()) as $i => $makna)
                  <div class="lp-card">
                    <div class="lp-card-title">Poin Nomor {{ $i + 1 }}</div>
                    <div class="form-grid">
                      <div class="form-field full">
                        <label for="lpMaknaJudul{{ $i }}">Judul Singkat</label>
                        <input id="lpMaknaJudul{{ $i }}" name="makna_logo[{{ $i }}][judul]" type="text" value="{{ is_array($makna) ? ($makna['judul'] ?? '') : '' }}" data-lp="makna_logo_judul_{{ $i }}">
                      </div>
                      <div class="form-field full">
                        <label for="lpMaknaKeterangan{{ $i }}">Keterangan</label>
                        <textarea id="lpMaknaKeterangan{{ $i }}" name="makna_logo[{{ $i }}][keterangan]" rows="2" data-lp="makna_logo_keterangan_{{ $i }}">{{ is_array($makna) ? ($makna['keterangan'] ?? '') : '' }}</textarea>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>

              {{-- ---------- PANEL "INFORMASI KONTAK" ---------- --}}
              <div class="panel lp-panel lp-tab-panel" data-lp-tab-panel="kontak" id="lpPanelKontakInfo">
                <div class="panel-head">
                  <div>
                    <h3>Informasi Kontak</h3>
                    <p>Alamat, email, telepon, dan website yang tampil di bagian footer landing page.</p>
                  </div>
                </div>
                <div class="form-grid">
                  <div class="form-field full">
                    <label for="lpKontakAlamat">Alamat (tampil di footer)</label>
                    <textarea id="lpKontakAlamat" name="alamat" rows="2" data-lp="alamat">{{ old('alamat', $pengaturan->alamat) }}</textarea>
                  </div>
                  <div class="form-field">
                    <label for="lpKontakEmail">Email Kontak</label>
                    <input id="lpKontakEmail" name="email_kontak" type="email" value="{{ old('email_kontak', $pengaturan->email_kontak) }}" data-lp="email_kontak">
                  </div>
                  <div class="form-field">
                    <label for="lpKontakTelepon">Telepon Kontak (tampil di footer)</label>
                    <input id="lpKontakTelepon" name="telepon_kontak" type="text" value="{{ old('telepon_kontak', $pengaturan->telepon_kontak) }}" data-lp="telepon_kontak">
                  </div>
                  <div class="form-field full">
                    <label for="lpWebsite">Website</label>
                    <input id="lpWebsite" name="website" type="url" value="{{ old('website', $pengaturan->website) }}" data-lp="website" placeholder="https://...">
                  </div>
                </div>
              </div>

              {{-- ---------- PANEL "SOSIAL MEDIA" ---------- --}}
              <div class="panel lp-panel lp-tab-panel" data-lp-tab-panel="kontak" id="lpPanelKontakSosmed">
                <div class="panel-head">
                  <div>
                    <h3>Sosial Media</h3>
                    <p>Label &amp; tautan akun sosial media yang tampil di bagian footer landing page.</p>
                  </div>
                </div>
                <div class="lp-sosmed-list">
                  @foreach ((old('sosial_media') ?? $pengaturan->sosial_media ?? []) as $i => $sosial)
                    <div class="lp-sosmed-row">
                      <input type="hidden" name="sosial_media[{{ $i }}][platform]" value="{{ is_array($sosial) ? $sosial['platform'] : '' }}" data-lp="sosial_platform_{{ $i }}">
                      <div class="form-grid">
                        <div class="form-field">
                          <label>Label ({{ ucfirst(is_array($sosial) ? $sosial['platform'] : '') }})</label>
                          <input name="sosial_media[{{ $i }}][label]" type="text" value="{{ is_array($sosial) ? $sosial['label'] : '' }}" data-lp="sosial_label_{{ $i }}">
                        </div>
                        <div class="form-field">
                          <label>URL</label>
                          <input name="sosial_media[{{ $i }}][url]" type="url" value="{{ is_array($sosial) ? $sosial['url'] : '' }}" placeholder="https://..." data-lp="sosial_url_{{ $i }}">
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>

            </div>

            {{-- ---------- MODAL CARD PANEL EDITOR (POP-UP ON CLICK) ----------
                 Modal pop-up elegan yang menampung form panel yang sedang diedit.
                 Panel dipindahkan secara dinamis ke #lpLandingModalBody saat kartu diklik,
                 dan dikembalikan ke #lpPanelsStore saat modal ditutup atau saat form di-submit. --}}
            <div class="lp-landing-modal-backdrop" id="lpLandingModalBackdrop" aria-hidden="true">
              <div class="lp-landing-modal-box" role="dialog" aria-modal="true" aria-labelledby="lpLandingModalTitle">
                <div class="lp-landing-modal-head">
                  <div class="lp-landing-modal-head-left">
                    <span class="lp-landing-modal-icon" id="lpLandingModalIcon"></span>
                    <div>
                      <h3 id="lpLandingModalTitle">Pengaturan Section</h3>
                      <p id="lpLandingModalDesc">Ubah konten dan preferensi bagian ini sesuai kebutuhan.</p>
                    </div>
                  </div>
                  <button type="button" class="lp-landing-modal-close" id="lpLandingModalCloseBtn" aria-label="Tutup modal">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </button>
                </div>
                <div class="lp-landing-modal-body" id="lpLandingModalBody">
                  {{-- Panel form yang aktif akan dipindahkan ke sini oleh JS --}}
                </div>
                <div class="lp-landing-modal-foot">
                  <div class="lp-landing-modal-notice">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>Perubahan otomatis tersinkronisasi ke Pratinjau Langsung di latar.</span>
                  </div>
                  <div class="lp-landing-modal-foot-actions">
                    <button type="button" class="btn btn-ghost" id="lpLandingModalCancelBtn">Tutup</button>
                    <button type="button" class="btn btn-primary" id="lpLandingModalSaveBtn">
                      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                      Simpan Konten Landing
                    </button>
                  </div>
                </div>
              </div>
            </div>

          </form>

          {{-- ---------- PANEL PRATINJAU (terpisah) ---------- --}}
          <div class="panel lp-preview-panel">
            <div class="panel-head">
              <div>
                <h3>Pratinjau Langsung <span class="lp-live-dot" aria-hidden="true"></span></h3>
                <p>Mengikuti tema (gelap/terang) yang sedang aktif.</p>
              </div>
            </div>
            <div class="lp-preview-body">
              <div class="lp-browser-frame">
                <div class="lp-browser-bar">
                  <span class="lp-browser-dot"></span><span class="lp-browser-dot"></span><span class="lp-browser-dot"></span>
                  <span class="lp-browser-url">siberad</span>
                </div>
                <div class="lp-preview" id="lpPreview">
                  <div class="lp-hero {{ $pengaturanHeroBgType === 'video' ? 'lp-hero-bg-video' : '' }}" id="lpPreviewHero" data-lp-preview-section="beranda"
                    style="--lp-hero-photo:@if($pengaturan->hero_image_path)url('{{ asset('storage/'.$pengaturan->hero_image_path) }}')@else none @endif;--lp-hero-blur:{{ old('hero_blur_level', $pengaturan->hero_blur_level ?? 0) }}px;--lp-hero-overlay:{{ (old('hero_overlay_intensity', $pengaturan->hero_overlay_intensity ?? 100)) / 100 }};"
                  >
                    <video id="lpPreviewHeroVideo" class="lp-hero-video-bg" muted loop autoplay playsinline
                      @if($pengaturanHeroVideoExists) src="{{ asset('storage/'.$pengaturan->hero_video_path) }}" @endif
                      style="{{ $pengaturanHeroBgType === 'video' && $pengaturanHeroVideoExists ? '' : 'display:none' }}"></video>
                    <div class="lp-eyebrow" id="lpPvEyebrow"></div>
                    <div class="lp-h1"><span id="lpPvJudulAwal"></span><em id="lpPvJudulAksen"></em></div>
                    <div class="lp-h2" id="lpPvSubjudul"></div>
                    <div class="lp-p" id="lpPvDeskripsi"></div>
                  </div>
                  <div class="lp-features" id="lpPvFitur" data-lp-preview-section="fitur"></div>
                  <div class="lp-about" data-lp-preview-section="tentang">
                    <div class="lp-section-title">Tentang</div>
                    <div class="lp-p" id="lpPvTentang"></div>
                    <div class="lp-moto-title" id="lpPvMotoJudul"></div>
                    <div class="lp-p" id="lpPvMoto"></div>
                  </div>
                  <div class="lp-footer" data-lp-preview-section="kontak">
                    <div class="lp-section-title">Kontak</div>
                    <div class="lp-p" id="lpPvAlamat" data-lp-empty="Alamat belum diisi"></div>
                    <div class="lp-p" id="lpPvTelepon" data-lp-empty="Telepon belum diisi"></div>
                    <div class="lp-p" id="lpPvEmail" data-lp-empty="Email belum diisi"></div>
                    <div class="lp-p" id="lpPvWebsite" data-lp-empty="Website belum diisi"></div>
                    <div class="lp-sosial-list" id="lpPvSosial"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <style>
          /* Layout selalu 1 kolom: editor (tabel/tab) di atas, Pratinjau
             Langsung tetap di bawah dalam posisi landscape/lebar seperti
             sekarang -- SENGAJA tidak dibikin sejajar 2 kolom ke samping. */
          .lp-layout{display:flex;flex-direction:column;gap:22px;}

          .lp-panel form{padding:22px;}

          /* ===== Banner ringkasan "Pengaturan Konten Halaman Landing" =====
             Sebelumnya solid oranye penuh (kesan berat/mentok) -- sekarang
             dibikin gradasi PUTIH ke oranye (putih dominan di kiri tempat
             teks, oranye cuma jadi aksen yang "menguar" di kanan/tepi),
             ditambah border tipis oranye pudar & radial glow lembut supaya
             tetap kerasa "branded" tapi jauh lebih kalem & premium,
             bukan blok oranye pekat dari ujung ke ujung. */
          .lp-hero-banner{
            display:flex;align-items:center;gap:18px;
            background:
              radial-gradient(120% 180% at 100% -20%, rgba(255,152,0,.35), transparent 60%),
              linear-gradient(115deg,#ffffff 0%,#fffaf2 30%,#ffe7c2 62%,#ffb74d 88%,var(--gold-solid-bright) 100%);
            border:1px solid rgba(255,152,0,.22);
            color:var(--on-gold);overflow:hidden;position:relative;
            box-shadow:0 1px 0 rgba(255,255,255,.7) inset,0 14px 34px -16px rgba(201,122,0,.4);
          }
          .lp-hero-banner-icon{flex:0 0 auto;width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--gold-solid-bright),#c9740a);box-shadow:0 6px 16px -6px rgba(201,122,0,.55);display:flex;align-items:center;justify-content:center;}
          .lp-hero-banner-icon svg{width:22px;height:22px;stroke:#fff;}
          .lp-hero-banner-text{flex:1 1 auto;min-width:0;}
          .lp-hero-banner-text h2{color:#241a05;margin-bottom:4px;}
          .lp-hero-banner-text p{color:rgba(36,26,5,.68);font-size:13px;max-width:640px;}
          .lp-hero-banner-art{flex:0 0 auto;width:120px;height:90px;display:none;opacity:.85;}
          @media(min-width:860px){ .lp-hero-banner-art{display:block;} }

          /* ===== Grid kartu ringkasan bagian Konten Halaman Landing ===== */
          .lp-overview-wrap .panel-head{margin-bottom:16px;}
          .lp-overview-grid{display:grid;grid-template-columns:1fr;gap:14px;}
          @media(min-width:760px){ .lp-overview-grid{grid-template-columns:1fr 1fr;} }
          .lp-overview-card{
            display:flex;align-items:flex-start;gap:14px;text-align:left;
            font-family:inherit;padding:18px;border-radius:14px;cursor:pointer;
            background:var(--panel);border:1px solid var(--border-soft);
            transition:border-color .15s ease,box-shadow .15s ease,transform .15s ease;
          }
          .lp-overview-card:hover{border-color:var(--border-strong);box-shadow:0 10px 24px rgba(0,0,0,.1);transform:translateY(-1px);}
          .lp-overview-card:active{transform:translateY(0);}
          /* Kartu yang sedang "terbuka" (grup field-nya lagi ditampilkan di
             bawah) ditandai aksen emas di border + latar redup, senada tema. */
          .lp-overview-card.lp-ov-open{border-color:var(--gold);background:var(--gold-dim);}
          .lp-ov-icon{flex:0 0 auto;width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;}
          .lp-ov-icon svg{width:19px;height:19px;}
          .lp-ov-gold{background:var(--gold-dim);color:var(--gold-bright);}
          .lp-ov-purple{background:rgba(147,51,234,.12);color:#9333ea;}
          .lp-ov-green{background:var(--green-dim);color:var(--green-bright);}
          .lp-ov-amber{background:var(--amber-dim);color:var(--amber);}
          .lp-ov-pink{background:rgba(219,39,119,.12);color:#db2777;}
          .lp-ov-red{background:var(--red-dim);color:var(--red);}
          .lp-ov-body{flex:1 1 auto;min-width:0;padding-top:2px;}
          .lp-ov-title{display:block;font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px;}
          .lp-ov-desc{display:block;font-size:12px;color:var(--text-muted);line-height:1.5;}
          .lp-ov-chevron{flex:0 0 auto;width:18px;height:18px;color:var(--text-dim);margin-top:9px;transition:transform .2s ease;}
          .lp-overview-card:hover .lp-ov-chevron{transform:translateX(2px);color:var(--text-muted);}
          .lp-overview-card.lp-ov-open .lp-ov-chevron{transform:rotate(90deg);color:var(--gold-bright);}
          .lp-overview-card.lp-ov-open:hover .lp-ov-chevron{transform:rotate(90deg) translateX(0);}

          /* ===== Kelompok field cuma tampil setelah kartu ringkasan
             diklik -- sebelum itu, ".lp-tab-panel" (tanpa ".active") tetap
             disembunyikan oleh aturan lama ".lp-tab-panel{display:none}"
             di bawah. Wadah kecil "Sedang mengedit..." ini muncul di atas
             kelompok field yang lagi terbuka, biar jelas & ada tombol
             tutup baliknya ke ringkasan. ===== --}}
          .lp-open-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;background:var(--gold-dim);border:1px solid var(--gold);border-radius:10px;padding:10px 14px;margin-bottom:16px;font-size:12.5px;font-weight:600;color:var(--gold-bright);}
          .lp-open-bar button{font-family:inherit;font-size:12px;font-weight:700;color:var(--text-muted);background:var(--panel);border:1px solid var(--border-soft);border-radius:8px;padding:5px 12px;cursor:pointer;transition:color .15s ease,border-color .15s ease;}
          .lp-open-bar button:hover{color:var(--text);border-color:var(--border-strong);}

          .lp-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:6px;border-bottom:1px solid var(--border-soft);padding-bottom:16px;}
          .lp-tab{
            display:flex;align-items:center;gap:7px;
            font-family:inherit;font-size:12.5px;font-weight:600;letter-spacing:.02em;
            padding:9px 16px;border-radius:999px;cursor:pointer;
            background:var(--panel-alt);border:1px solid var(--border-soft);color:var(--text-muted);
            transition:background .15s ease,color .15s ease,border-color .15s ease;
          }
          .lp-tab svg{width:15px;height:15px;flex-shrink:0;}
          .lp-tab:hover{color:var(--text);border-color:var(--border);}
          .lp-tab.active{background:var(--gold-dim);border-color:var(--gold);color:var(--gold-bright);}

          .lp-tab-panel{display:none;padding-top:18px;}
          .lp-tab-panel.active{display:block;animation:lpFadeIn .18s ease;}

          /* ===== Modal editor pop-up (konten muncul saat kartu pintasan diklik) ===== */

          body.lp-modal-lock .sidebar,
          body.lp-modal-lock .topbar{
            backdrop-filter:none!important;
            -webkit-backdrop-filter:none!important;
          }

          body.lp-modal-lock .content{
            z-index:200001;
          }

          .lp-landing-modal-backdrop{
            position:fixed!important;inset:0!important;z-index:999999!important;
            background:rgba(15,23,42,.64)!important;
            backdrop-filter:blur(8px)!important;-webkit-backdrop-filter:blur(8px)!important;
            display:flex!important;align-items:center!important;justify-content:center!important;
            padding:24px 16px!important;overflow:hidden!important;
            opacity:0;pointer-events:none;visibility:hidden;
            transition:opacity .25s ease,visibility .25s ease;
          }
          .lp-landing-modal-backdrop.is-open{
            opacity:1!important;pointer-events:auto!important;visibility:visible!important;
          }

          /* Ukuran popup modal SAMA PERSIS untuk ke-6 modal */
          .lp-landing-modal-box{
            background:var(--panel)!important;border:1px solid var(--border-strong)!important;
            border-radius:16px!important;width:92vw!important;max-width:780px!important;
            height:84vh!important;max-height:680px!important;min-height:500px!important;
            display:flex!important;flex-direction:column!important;overflow:hidden!important;
            box-shadow:0 30px 90px rgba(0,0,0,.75),0 0 0 1px rgba(255,255,255,.07)!important;
            transform:scale(.96) translateY(10px);
            transition:transform .25s cubic-bezier(.16,1,.3,1);
            position:relative!important;
          }
          .lp-landing-modal-backdrop.is-open .lp-landing-modal-box{
            transform:scale(1) translateY(0);
          }

          /* -- Header modal: ringkas, ikon + judul + deskripsi + tombol tutup (tetap di atas) -- */
          .lp-landing-modal-head{
            flex:0 0 auto!important;display:flex!important;align-items:center!important;justify-content:space-between!important;
            gap:14px!important;padding:16px 22px!important;
            border-bottom:1px solid var(--border-soft)!important;
            background:linear-gradient(to bottom,var(--panel-alt),var(--panel))!important;
          }
          .lp-landing-modal-head-left{
            display:flex;align-items:center;gap:12px;min-width:0;
          }
          .lp-landing-modal-icon{
            width:38px;height:38px;border-radius:10px;
            display:flex;align-items:center;justify-content:center;flex-shrink:0;
          }
          .lp-landing-modal-icon svg{width:18px;height:18px;}
          .lp-landing-modal-head h3{
            margin:0 0 2px;font-family:var(--display);font-size:15.5px;font-weight:700;
            color:var(--text);letter-spacing:.01em;
          }
          .lp-landing-modal-head p{
            margin:0;font-size:11.5px;color:var(--text-muted);line-height:1.4;
            max-width:480px;
          }
          .lp-landing-modal-close{
            flex:0 0 auto;width:32px;height:32px;border-radius:8px;
            border:1px solid var(--border-soft);background:transparent;
            color:var(--text-dim);display:flex;align-items:center;justify-content:center;
            cursor:pointer;transition:all .15s ease;flex-shrink:0;
          }
          .lp-landing-modal-close:hover{
            color:var(--red);border-color:var(--red);
            background:var(--red-dim);transform:rotate(90deg);
          }

          /* -- Body modal: scrollable seragam, scrollbar vertikal aktif & rapi -- */
          .lp-landing-modal-body{
            flex:1 1 auto!important;
            min-height:0!important; /* Wajib min-height:0 pada flex child agar memicu scrollbar vertikal */
            height:auto!important;
            overflow-y:auto!important; /* Scrollbar vertikal aktif */
            overflow-x:hidden!important;
            padding:22px 24px!important;
            scrollbar-width:thin!important;
            scrollbar-color:var(--text-dim) rgba(0,0,0,.25)!important;
            -webkit-overflow-scrolling:touch!important;
            overscroll-behavior:contain!important;
          }
          .lp-landing-modal-body::-webkit-scrollbar{width:8px!important;}
          .lp-landing-modal-body::-webkit-scrollbar-track{background:rgba(0,0,0,.2)!important;border-radius:6px!important;}
          .lp-landing-modal-body::-webkit-scrollbar-thumb{background:var(--text-dim)!important;border-radius:6px!important;}
          .lp-landing-modal-body::-webkit-scrollbar-thumb:hover{background:var(--text-muted)!important;}

          /* Sub-panel di dalam modal (mis. Makna Logo, Moto, Identitas Instansi, Fitur 1..4) */
          .lp-landing-modal-body .lp-tab-panel{
            display:block!important;
            background:var(--panel-alt)!important;
            border:1px solid var(--border-soft)!important;
            border-radius:12px!important;
            padding:18px 20px!important;
            margin-bottom:16px!important;
            box-shadow:none!important;
            animation:lpFadeIn .18s ease;
          }
          .lp-landing-modal-body .lp-tab-panel:last-child{
            margin-bottom:0!important;
          }

          /* Header sub-panel di dalam modal */
          .lp-landing-modal-body .panel-head{
            display:block!important;
            margin-bottom:14px!important;
            padding-bottom:10px!important;
            border-bottom:1px solid var(--border-soft)!important;
          }
          /* Khusus panel tunggal (seperti Judul & Deskripsi Utama), sembunyikan panel-head agar tidak dobel */
          .lp-landing-modal-body .lp-tab-panel:only-child > .panel-head{
            display:none!important;
          }
          .lp-landing-modal-body .panel-head h3{
            font-size:13.5px!important;
            font-weight:700!important;
            color:var(--text)!important;
            letter-spacing:.02em!important;
            margin:0 0 4px!important;
            display:flex!important;
            align-items:center!important;
            gap:8px!important;
          }
          .lp-landing-modal-body .panel-head h3::before{
            content:'';
            width:6px;height:6px;border-radius:50%;
            background:var(--gold);
            flex-shrink:0;
          }
          .lp-landing-modal-body .panel-head p{
            font-size:11.5px!important;
            color:var(--text-muted)!important;
            margin:0!important;
            line-height:1.5!important;
          }

          /* Kartu item di dalam modal (Makna Logo poin 1..10, kartu sosial media, dsb) */
          .lp-landing-modal-body .lp-card,
          .lp-landing-modal-body .lp-sosmed-row{
            background:var(--panel)!important;
            border:1px solid var(--border-soft)!important;
            border-radius:10px!important;
            padding:14px 16px!important;
            margin-bottom:12px!important;
          }
          .lp-landing-modal-body .lp-card:last-child,
          .lp-landing-modal-body .lp-sosmed-row:last-child{
            margin-bottom:0!important;
          }
          .lp-landing-modal-body .lp-card-title{
            font-size:11.5px!important;
            font-weight:700!important;
            color:var(--gold-bright,#ffb300)!important;
            margin-bottom:8px!important;
            text-transform:uppercase!important;
            letter-spacing:.05em!important;
            display:flex!important;
            align-items:center!important;
            gap:6px!important;
          }
          .lp-landing-modal-body .lp-card-desc{
            font-size:11.5px!important;
            color:var(--text-muted)!important;
            margin:0 0 12px!important;
            line-height:1.5!important;
          }

          /* Form Grid & Input konsisten di dalam modal */
          .lp-landing-modal-body .form-grid{
            display:grid!important;
            grid-template-columns:repeat(2,minmax(0,1fr))!important;
            gap:12px 14px!important;
          }
          .lp-landing-modal-body .form-field.full{
            grid-column:1 / -1!important;
          }
          .lp-landing-modal-body .form-field label{
            display:block!important;
            font-size:12px!important;
            font-weight:600!important;
            color:var(--text)!important;
            margin-bottom:6px!important;
          }
          .lp-landing-modal-body .form-field input,
          .lp-landing-modal-body .form-field textarea,
          .lp-landing-modal-body .form-field select{
            width:100%!important;
            box-sizing:border-box!important;
            border-radius:8px!important;
            padding:9px 12px!important;
            font-size:13px!important;
            background:var(--surface,#1e2621)!important;
            border:1px solid var(--border-soft)!important;
            color:var(--text)!important;
          }
          .lp-landing-modal-body .form-field input:focus,
          .lp-landing-modal-body .form-field textarea:focus{
            border-color:var(--gold)!important;
            outline:none!important;
            box-shadow:0 0 0 2px rgba(255,152,0,.2)!important;
          }
          .lp-landing-modal-body .form-field small{
            display:block!important;
            font-size:11px!important;
            color:var(--text-muted)!important;
            margin-top:4px!important;
            line-height:1.4!important;
          }
          .lp-landing-modal-body .lp-dynamic-section{
            display:flex!important;
            flex-direction:column!important;
            gap:14px!important;
            margin-top:14px!important;
          }
          .lp-landing-modal-body .lp-dynamic-section .lp-card{
            margin:0!important;
          }
          .lp-landing-modal-body .lp-hero-image-row{
            display:flex!important;
            align-items:center!important;
            gap:14px!important;
            flex-wrap:wrap!important;
            margin:8px 0!important;
          }

          /* -- Footer modal: compact, info kiri + tombol kanan (tetap di bawah) -- */
          .lp-landing-modal-foot{
            flex:0 0 auto!important;display:flex!important;align-items:center!important;justify-content:space-between!important;
            gap:12px!important;padding:13px 22px!important;
            border-top:1px solid var(--border-soft)!important;
            background:var(--panel-alt)!important;
          }
          .lp-landing-modal-notice{
            display:flex;align-items:center;gap:6px;
            font-size:11px;color:var(--text-dim);
          }
          .lp-landing-modal-notice svg{color:var(--gold-bright);flex-shrink:0;}
          .lp-landing-modal-foot-actions{
            display:flex;align-items:center;gap:8px;flex-shrink:0;
          }

          body.lp-modal-lock{overflow:hidden;}
          @media(max-width:640px){
            .lp-landing-modal-box{
              width:96vw!important;height:90vh!important;max-height:90vh!important;border-radius:14px!important;
            }
            .lp-landing-modal-body .form-grid{grid-template-columns:1fr!important;}
            .lp-landing-modal-head,.lp-landing-modal-body,.lp-landing-modal-foot{padding:14px 16px!important;}
            .lp-landing-modal-foot{flex-direction:column-reverse;align-items:stretch;gap:10px;}
            .lp-landing-modal-foot-actions{flex-direction:column-reverse;gap:8px;}
            .lp-landing-modal-foot-actions .btn{width:100%;justify-content:center;}
            .lp-landing-modal-notice{justify-content:center;}
          }
          @keyframes lpFadeIn{ from{opacity:0;transform:translateY(4px);} to{opacity:1;transform:none;} }
          .lp-tab-desc{font-size:12.5px;color:var(--text-muted);margin-bottom:16px;line-height:1.6;}

          /* Kartu ".lp-card" DULU dibikin sebagai kotak penuh (border+
             shadow+background) sendiri-sendiri persis seperti kartu
             ".panel" -- efeknya tiap kelompok field (Judul & Deskripsi
             Utama, Latar Belakang Beranda, dst) jadi kelihatan seperti
             "kartu di dalam kartu" yang bertumpuk di atas kartu induk
             "Konten Halaman Landing". Sekarang polanya disamakan dengan
             kartu satuan (mis. "Danpus") di tab Hak Akses Pengguna: kartu
             induk (".lp-panel", sudah berupa kotak) cukup diisi
             LANGSUNG oleh kelompok-kelompok field yang dipisahkan garis
             putus-putus tipis (bukan kotak/bayangan sendiri lagi) --
             terpisah tetap jelas lewat judul + garis, tapi tidak lagi
             boros bikin kotak baru di dalam kotak. */
          .lp-card{
            background:none;
            border:none;
            border-radius:0;
            box-shadow:none;
            padding:0 0 20px;
            margin-bottom:20px;
          }
          .lp-card-compact{padding:0 0 14px;}
          .lp-card-title{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);margin-bottom:10px;}
          .lp-card-desc{margin:-4px 0 14px;font-size:11.5px;line-height:1.55;color:var(--text-muted);}

          /* ===== SENGAJA di-scope semua di bawah ".lp-panel" (panel
             editor) saja, supaya TIDAK ikut mengubah ".lp-preview-panel"
             (Pratinjau Langsung, sudah pas seperti sekarang) maupun
             halaman/tab lain di luar Pengaturan Umum yang kebetulan
             memakai class umum seperti .form-field. */
          /* ===== Redesign kartu kelompok field (dulu cuma dipisah garis
             putus-putus tipis, jadi satu tumpukan panjang seragam yang
             capek di-scan) jadi kotak (".lp-card") sendiri-sendiri yang
             jelas batasnya -- supaya modal pintasan, walau isinya banyak
             field, tetap kelihatan simpel & gampang dipahami: satu kotak =
             satu topik, judulnya langsung kelihatan beda dari isiannya. */
          .lp-panel .lp-card{
            background:var(--panel);
            border:1px solid var(--border-soft);
            border-radius:14px;
            padding:16px 18px 18px;
            margin-bottom:14px;
            box-shadow:0 1px 2px rgba(15,23,42,.03);
            transition:border-color .15s ease;
          }
          .lp-panel .lp-card:last-child{margin-bottom:0;}
          .lp-panel .lp-card-compact{padding:12px 16px 14px;}

          /* Judul kartu: dikasih penanda titik kecil warna emas di depan
             biar langsung kelihatan sebagai "kepala kelompok", ukuran &
             bobot dinaikkan sedikit (dari 11px) supaya lebih mudah dipindai
             sekilas dibanding badan teks/isian di bawahnya. */
          .lp-panel .lp-card-title{
            display:flex; align-items:center; gap:9px;
            margin-bottom:4px;
            font-size:12px;
            color:var(--text);
          }
          .lp-panel .lp-card-title::before{
            content:'';
            width:7px;height:7px;border-radius:2.5px;flex-shrink:0;
            background:var(--gold-bright);
          }
          .lp-panel .lp-card-desc{margin:0 0 16px;}

          /* Kartu ITEM berulang (tiap "Fitur N" lewat class tambahan
             ".lp-card-item", dan tiap "Poin Nomor N" bersarang di dalam
             kartu "Makna Logo") TETAP dikasih kotak ringan (border solid
             + latar sedikit beda, tanpa bayangan) -- ini murni daftar
             entri berulang seperti kartu modul (checkbox) di Hak Akses
             Pengguna, jadi wajar tetap dibedakan dari kelompok field
             biasa yang cuma dipisah garis di atas. */
          .lp-panel .lp-card.lp-card-item,
          .lp-panel .lp-card .lp-card{
            border:1px solid var(--border-soft);
            border-radius:12px;
            background:var(--panel-alt);
            box-shadow:none;
            padding:16px 18px 18px;
            margin-bottom:14px;
          }
          .lp-panel .lp-card.lp-card-item:last-child,
          .lp-panel .lp-card .lp-card:last-child{margin-bottom:0;}
          .lp-panel .lp-card.lp-card-item .lp-card-title,
          .lp-panel .lp-card .lp-card-title{border-bottom:none;padding-bottom:0;margin-bottom:10px;}
          .lp-panel .lp-card.lp-card-item .lp-card-desc,
          .lp-panel .lp-card .lp-card-desc{margin:10px 0 14px;}

          /* Field lebih empuk & nyaman diisi -- radius lebih besar, warna
             latar sedikit beda dari kartu (supaya kelihatan sebagai "kotak
             isian", bukan garis tabel), dengan efek fokus glow lembut. */
          .lp-panel .form-field input,
          .lp-panel .form-field select,
          .lp-panel .form-field textarea{
            border-radius:12px;
            padding:12px 14px;
            background:var(--panel-alt);
            transition:border-color .15s ease, box-shadow .15s ease, background .15s ease;
          }
          .lp-panel .form-field input:hover,
          .lp-panel .form-field select:hover,
          .lp-panel .form-field textarea:hover{
            border-color:var(--border-strong);
          }
          .lp-panel .form-field input:focus,
          .lp-panel .form-field select:focus,
          .lp-panel .form-field textarea:focus{
            box-shadow:0 0 0 3px var(--gold-dim);
            background:var(--panel);
          }
          .lp-panel .form-grid{row-gap:18px;}

          /* Tab pill sedikit lebih tegas & "hidup" saat aktif. */
          .lp-panel .lp-tab{padding:10px 18px;border-radius:12px;}
          .lp-panel .lp-tab.active{box-shadow:0 4px 14px rgba(255,152,0,.16);}
          /* Sosial Media: dulu tiap platform (Instagram/TikTok/dst) jadi
             kartu ".lp-card" terpisah sendiri-sendiri (numpuk banyak kartu
             kecil) -- sekarang digabung jadi SATU kartu "Sosial Media" berisi
             beberapa baris yang cuma dipisahkan garis tipis, bukan kotak
             kartu masing-masing. */
          .lp-sosmed-row{padding:16px 0;border-top:1px solid var(--border-soft);}
          .lp-sosmed-row:first-child{padding-top:0;border-top:none;}
          .lp-sosmed-row:last-child{padding-bottom:0;}
          /* ===== Toggle "Tipe Latar Belakang" -- didesain ulang jadi
             segmented-control bericon (senada sama pill ".lp-tab" di atas),
             bukan radio polos lagi. Radio aslinya TETAP ada di DOM (supaya
             form submit & aksesibilitas keyboard/screen-reader tetap
             jalan normal) tapi disembunyikan visual lewat teknik sr-only --
             ini juga otomatis menghilangkan bug "GAMBAR/VIDEO ga sejajar"
             sebelumnya, karena tidak ada lagi kontrol radio bawaan browser
             yang ukurannya bisa beda antara checked vs unchecked. */
          /* align-self:flex-start -- ".lp-bg-type-toggle" duduk di dalam
             ".form-field" yang flex-direction:column (default
             align-items:stretch), jadi tanpa ini kotak toggle ikut
             direntangkan selebar kartu (kelihatan "kepanjangan", separuh
             lebih cuma latar abu-abu kosong di kanan tombol GAMBAR/VIDEO).
             align-self:flex-start mengembalikannya jadi selebar isinya
             saja (sesuai display:inline-flex di bawah). */
          .lp-bg-type-toggle{display:inline-flex;align-self:flex-start;max-width:100%;gap:6px;padding:5px;border-radius:14px;background:var(--panel-alt);border:1px solid var(--border-soft);}
          .lp-bg-type-option{
            position:relative;display:flex;align-items:center;gap:7px;
            padding:9px 18px;border-radius:10px;cursor:pointer;
            font-size:12.5px;font-weight:700;letter-spacing:.02em;
            color:var(--text-muted);background:transparent;
            transition:background .18s ease,color .18s ease,box-shadow .18s ease,transform .12s ease;
          }
          .lp-bg-type-option:active{transform:scale(.97);}
          .lp-bg-type-icon{display:flex;flex-shrink:0;color:currentColor;opacity:.65;transition:opacity .18s ease;}
          .lp-bg-type-icon svg{width:16px;height:16px;}
          .lp-bg-type-option:hover{color:var(--text);}
          .lp-bg-type-option:hover .lp-bg-type-icon{opacity:1;}
          .lp-bg-type-option input[type="radio"]{
            position:absolute;width:1px;height:1px;padding:0;margin:-1px;
            overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;
          }
          /* Sengaja DUA rule terpisah (bukan digabung satu selector list
             pakai koma) untuk ".is-active" vs ":has(input:checked)" --
             kalau digabung satu list dan browser-nya belum kenal :has()
             (di luar seluruh browser modern hari ini yang sudah dukung),
             seluruh rule (termasuk fallback class ".is-active" yang di-
             toggle lewat JS di bawah) ikut dianggap tidak valid dan
             gagal semua. Dipisah begini, ".is-active" (jalur utama, selalu
             jalan lewat JS) tetap aman walau :has() nggak didukung. */
          .lp-bg-type-option.is-active{
            background:var(--gold-dim);
            color:var(--gold-bright);
            box-shadow:0 3px 10px rgba(255,152,0,.2), inset 0 0 0 1px var(--gold);
          }
          .lp-bg-type-option.is-active .lp-bg-type-icon{opacity:1;}
          .lp-bg-type-option:has(input:checked){
            background:var(--gold-dim);
            color:var(--gold-bright);
            box-shadow:0 3px 10px rgba(255,152,0,.2), inset 0 0 0 1px var(--gold);
          }
          .lp-bg-type-option:has(input:checked) .lp-bg-type-icon{opacity:1;}
          .lp-bg-type-option:has(input:focus-visible){outline:2px solid var(--gold-bright);outline-offset:2px;}

          /* ===== Slider "Blur Latar" & "Kepekatan Overlay Warna" -- model
             GARIS TIPIS + thumb bulat solid (sesuai referensi desain dari
             Admin), BUKAN lagi model kapsul/pil dengan kotak pembungkus
             (.lp-range-shell) seperti sebelumnya. Track sekarang polos
             tanpa background/border/padding pembungkus -- cuma garis tipis
             yang bagian terisinya (kiri thumb) solid warna emas, dan
             bagian sisanya (kanan thumb) pudar/redup. Thumb jadi lingkaran
             solid polos (tanpa cincin putih) yang ukurannya jelas lebih
             besar dari garis track, biar gampang di-drag & dipegang mata. */
          .lp-range-field{gap:10px;}
          .lp-range-head{display:flex;align-items:center;justify-content:space-between;gap:12px;}
          .lp-range-head label{display:flex;align-items:center;gap:7px;margin:0;}
          .lp-range-icon{display:flex;flex-shrink:0;color:var(--gold-bright);}
          .lp-range-icon svg{width:15px;height:15px;}
          .lp-range-badge{
            display:inline-flex;align-items:baseline;gap:2px;flex-shrink:0;
            font-family:var(--mono);font-weight:700;font-size:12.5px;color:var(--gold-bright);
            background:var(--gold-dim);border:1px solid var(--border-soft);border-radius:999px;
            padding:3px 12px;line-height:1.5;
          }
          .lp-range-badge small{font-size:9.5px;font-weight:600;color:var(--text-muted);text-transform:lowercase;margin-left:1px;}
          .lp-range-shell{
            display:flex;align-items:center;
            background:none;border:none;border-radius:0;padding:0 9px;height:auto;margin:10px 0;
            box-shadow:none;
          }
          input.lp-range[type="range"]{
            -webkit-appearance:none;appearance:none;
            width:100%;height:4px;border-radius:999px;margin:0;
            background:linear-gradient(to right, var(--gold-bright) 0%, var(--gold-bright) var(--lp-range-fill,0%), var(--border-soft) var(--lp-range-fill,0%), var(--border-soft) 100%);
            border:none;padding:0;outline:none;cursor:pointer;
          }
          input.lp-range[type="range"]::-webkit-slider-thumb{
            -webkit-appearance:none;width:18px;height:18px;border-radius:50%;
            background:var(--gold-bright);border:none;margin-top:0;
            box-shadow:0 1px 4px rgba(15,23,42,.35);cursor:pointer;
            transition:transform .15s ease,box-shadow .15s ease;
          }
          input.lp-range[type="range"]:hover::-webkit-slider-thumb{transform:scale(1.1);}
          input.lp-range[type="range"]:active::-webkit-slider-thumb{transform:scale(1.02);box-shadow:0 0 0 6px var(--gold-dim);}
          input.lp-range[type="range"]::-moz-range-track{height:4px;border-radius:999px;background:var(--border-soft);border:none;}
          input.lp-range[type="range"]::-moz-range-progress{height:4px;border-radius:999px;background:var(--gold-bright);}
          input.lp-range[type="range"]::-moz-range-thumb{
            width:18px;height:18px;border-radius:50%;background:var(--gold-bright);border:none;
            box-shadow:0 1px 4px rgba(15,23,42,.35);cursor:pointer;transition:transform .15s ease;
          }
          input.lp-range[type="range"]:hover::-moz-range-thumb{transform:scale(1.1);}
          input.lp-range[type="range"]:focus-visible{box-shadow:0 0 0 3px var(--gold-dim);border-radius:999px;}
          /* CATATAN (fix): partials/pengumuman-banner.blade.php (di-include
             lebih awal di halaman ini) punya rule generik
             ".lp-tab-panel .form-field input[type=range]{background:
             transparent!important}" untuk range field lain di tab "Konten
             Halaman Landing". Slider Blur Latar & Kepekatan Overlay Warna
             ini kebetulan juga bersarang di dalam ".lp-tab-panel
             .form-field" (walau kartunya sendiri sudah di luar "Konten
             Halaman Landing"), jadi ikut kena background:transparent itu
             dan gradient dua-warna terisi/kosong di atas jadi tidak
             kelihatan. Selector di bawah ini SENGAJA dibuat lebih spesifik
             (3 class + atribut) supaya menang dari rule generik tsb tanpa
             perlu mengubah file pengumuman-banner.blade.php. */
          .lp-tab-panel .form-field input.lp-range[type="range"]{
            background:linear-gradient(to right, var(--gold-bright) 0%, var(--gold-bright) var(--lp-range-fill,0%), var(--border-soft) var(--lp-range-fill,0%), var(--border-soft) 100%) !important;
          }
          .lp-hero-image-row{display:flex;flex-direction:column;align-items:center;gap:16px;margin-top:0;text-align:center;}
          /* Video BG sebelumnya pakai rule generik ".lp-hero-image-row
             video.lp-current-image{max-width:360px}" -- jauh lebih kecil
             & beda rasio dari kotak Gambar BG (460x259). Sekarang video
             dikasih rule khusus per-ID, PERSIS menyamai ukuran & rasio
             kotak Gambar BG (lihat "#lpHeroImagePreviewImg" di bawah)
             supaya kotak pratinjau Gambar <-> Video terlihat konsisten
             saat berpindah tipe. */
          #lpHeroVideoPreviewVideo{width:460px;height:259px;object-fit:cover;object-position:center;background:#000;}
          #lpHeroVideoPreviewPlaceholder{width:460px;height:259px;}
          @media(max-width:760px){#lpHeroVideoPreviewVideo,#lpHeroVideoPreviewPlaceholder{width:100%;max-width:400px;}}
          @media(max-width:560px){#lpHeroVideoPreviewVideo,#lpHeroVideoPreviewPlaceholder{max-width:340px;}}
          .lp-hero-image-row .siberad-file-wrap{align-self:center;flex:0 0 auto;min-width:200px;justify-content:center;}
          /* Tombol "Pilih File", preview gambar, & tombol "Hapus Latar
             Belakang" semuanya rata tengah (align-items:center di parent
             .lp-hero-image-row) -- sebelumnya rata kiri lalu preview
             digeser pakai margin-left, sekarang cukup center semua & margin
             kiri itu dihapus supaya gak nambah geser dari titik tengah. */
          .lp-hero-image-row .lp-current-image,
          .lp-hero-image-row .lp-image-placeholder,
          .lp-hero-image-row .lp-hero-preview-frame{align-self:center;margin:0;}
          .lp-current-image{display:block;border-radius:9px;border:1px solid var(--border-soft);}
          /* Bungkus <img>/<video> pratinjau supaya bisa ditumpuk sama layer
             overlay warna (.lp-hero-preview-overlay) -- blur dipasang
             langsung sebagai CSS filter di <img>/<video>-nya sendiri, tapi
             overlay warna butuh elemen terpisah di atasnya karena filter
             CSS tidak bisa "mewarnai" transparan seperti overlay gradasi. */
          .lp-hero-preview-frame{position:relative;display:inline-block;border-radius:9px;overflow:hidden;}
          .lp-hero-preview-frame .lp-current-image{border:1px solid var(--border-soft);}
          .lp-hero-preview-overlay{
            position:absolute;inset:0;pointer-events:none;
            background-image:linear-gradient(160deg, color-mix(in srgb, var(--panel-2) 85%, transparent), color-mix(in srgb, var(--bg-deep) 75%, transparent));
          }
          .lp-image-placeholder{box-sizing:border-box;border-radius:12px;border:2px dashed var(--border-strong);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;text-align:center;padding:14px;font-size:11.5px;line-height:1.5;color:var(--text-muted);background:var(--panel-alt);}
          .lp-image-placeholder svg{display:block;flex-shrink:0;}
          .lp-delete-img-btn{align-self:center;min-height:32px;height:32px;padding:0 13px;font-size:11px;}
          /* BG (Gambar Latar Beranda): rasio landscape, mengikuti bentuk asli
             foto latar (bukan kotak seperti logo) -- object-fit:cover supaya
             foto memenuhi kotak tanpa gepeng/distorsi. Ukuran diperbesar
             (280x158 -> 460x259, tetap rasio ~16:9) supaya lebih jelas
             kelihatan. */
          #lpHeroImagePreviewImg{width:460px;height:259px;object-fit:cover;object-position:center;}
          #lpHeroImagePreviewPlaceholder{width:460px;height:259px;}
          @media(max-width:760px){#lpHeroImagePreviewImg,#lpHeroImagePreviewPlaceholder{width:100%;max-width:400px;}}
          @media(max-width:560px){#lpHeroImagePreviewImg,#lpHeroImagePreviewPlaceholder{max-width:340px;}}
          #strukturOrgForm{max-width:100%;box-sizing:border-box;}
          #strukturOrgForm .lp-hero-image-row{width:100%;box-sizing:border-box;}
          @media(max-width:760px){#strukturOrgPreviewImg,#strukturOrgPreviewPlaceholder{width:100%;max-width:360px;}}
          @media(max-width:560px){#strukturOrgPreviewImg,#strukturOrgPreviewPlaceholder{max-width:100%;}}
          /* Logo: kotak & object-fit:contain (bukan cover) supaya lambang/
             logo utuh terlihat jelas tanpa terpotong, dengan ukuran yang
             pas -- tidak menyisakan ruang kosong berlebihan seperti kotak
             landscape BG. */
          #lpLogoPreviewImg{width:150px;height:150px;object-fit:contain;background:var(--panel);padding:8px;box-sizing:border-box;}
          #lpLogoPreviewPlaceholder{width:150px;height:150px;}
          @media(max-width:560px){#lpLogoPreviewImg,#lpLogoPreviewPlaceholder{width:150px;}}

          /* Field & kartu tambahan yang disuntik lewat admin-landing-editor.js
             (Identitas Brand, Tombol Hero, SEO, Logo, Navigasi, Statistik) --
             dulu dikasih garis putus-putus + jarak ekstra (22px+22px) buat
             misahin dari field statis di atasnya. Sekarang field statis di
             tab Beranda SUDAH dibungkus kartu ".lp-card" sendiri-sendiri
             (Judul & Deskripsi Utama, Gambar Latar Beranda) yang masing-
             masing sudah punya margin-bottom sendiri -- jadi garis + jarak
             ekstra itu jadi dobel/kelebaran & keliatan kayak ada garis
             nyangkut di tengah. Cukup diselarasi normal, tanpa garis. */
          .lp-dynamic-section{margin-top:0;}
          .lp-dynamic-section .lp-card:last-child{margin-bottom:0;}

          /* pengumuman-banner.blade.php men-set .lp-panel{overflow:hidden}
             (buat clipping visual rounded corner) -- tapi overflow selain
             visible pada ancestor bikin position:sticky di dalamnya (tombol
             Simpan) tidak berfungsi. Dikembalikan ke visible di sini karena
             urutan <style> ini di render lebih akhir di halaman. */
          .lp-panel{overflow:visible;}

          /* Tombol Simpan pakai position:fixed (BUKAN sticky). Ternyata
             .content (partials/admin-ui-consistency.blade.php) dikasih
             overflow-x:hidden supaya chart/grid lain nggak bikin scroll
             horizontal -- efek sampingnya, begitu overflow-x bukan
             'visible', browser otomatis bikin overflow-y ikutan jadi
             'auto' juga (aturan baku CSS box model). .content pun jadi
             "kotak scroll" sendiri, dan position:sticky di dalamnya jadi
             dihitung relatif ke kotak itu (yang tingginya persis sama
             dengan isinya, jadi gak ada jarak buat "nempel") -- bukan ke
             viewport, makanya tombol kelihatan diam di tempat biasa aja.
             position:fixed gak kena masalah itu karena selalu relatif ke
             viewport. Posisi kiri disesuaikan lebar sidebar (256px normal,
             76px saat diciutkan, 0 saat mobile) supaya gak numpuk sidebar. */
          /* --lp-bar-h: tinggi bar tombol Simpan (padding 14px atas+bawah + tombol ~38px).
             Dipakai supaya kotak Keluar di footer sidebar (.side-foot) dibikin
             SETINGGI bar ini persis -- karena sidebar itu flex-column dgn
             height:100vh (nav flex:1 + side-foot nempel di dasar), begitu
             tinggi .side-foot == tinggi bar, garis atas (border-top) keduanya
             otomatis jatuh di koordinat Y yang sama persis -- lurus, gak
             "anak tangga". (Pendekatan lama: nambah padding-bottom ke
             .sidebar -- ini KELIRU, cuma nambah ruang kosong di bawah kotak
             Keluar tanpa mengubah tinggi kotaknya sendiri, jadi garis
             atasnya tetap gak ketemu garis atas bar.)
             Nilai 66px di bawah cuma FALLBACK -- nilai final yang benar-benar
             dipakai browser diukur & di-set ulang secara akurat oleh JS
             (lihat "sinkron --lp-bar-h" di <script> bawah), supaya tetap pas
             piksel-demi-piksel walau tingginya berubah (responsif, font,
             dll). Jangan andalkan angka statis ini sendirian. */
          :root{ --lp-bar-h:66px; }

          /* Wrapper luar: fixed full-width dari tepi sidebar ke kanan,
             tanpa padding horizontal -- supaya border-top dan background
             melebar penuh sejajar dengan garis topbar dan footer sidebar. */
          .lp-form-actions{
            position:fixed;left:var(--sidebar-w);right:0;bottom:0;
            padding:0;
            border-top:1px solid var(--border-soft);
            background:var(--panel);z-index:4;
            box-shadow:0 -8px 16px -8px rgba(0,0,0,.12);
            transition:left .25s ease;
          }
          /* pengumuman-banner.blade.php (di-include lebih awal di halaman,
             lihat baris ~912) punya style generic ".lp-form-actions" &
             ".lp-form-actions .btn" dengan !important (ditujukan utk konteks
             lain, bar aksi yang menyatu di dalam form -- bukan bar fixed
             ini) yang tanpa sengaja "bocor" ke sini karena sama-sama pakai
             class .lp-form-actions. Efeknya: margin-top & padding-top
             nambah, display berubah jadi flex, dan tombol Simpan dipaksa
             min-height 42px -- tinggi bar asli jadi membengkak jauh di atas
             --lp-bar-h (66px), sehingga sidebar (yang cuma reserve 66px
             lewat padding-bottom) berhenti terlalu cepat & garis
             pertemuannya jadi "anak tangga" / tidak sejajar dgn bar.
             Reset di sini (aturan ini render LEBIH AKHIR di halaman jadi
             menang di !important-vs-!important tie-break) supaya tinggi
             bar balik ke ukuran desain aslinya. */
          .lp-form-actions{margin-top:0!important;padding-top:0!important;display:block!important;}
          .lp-form-actions .btn{min-height:0!important;}
          /* Wrapper dalam: padding 14px 32px meniru .content{padding:30px 32px}
             sehingga sisi kiri tombol sejajar lurus dengan tepi kiri panel/
             tabel di atasnya. flex + justify-content:flex-end supaya tombol
             "Simpan Konten Landing" nempel ke KANAN (bukan default kiri). */
          .lp-form-actions-inner{
            padding:14px 32px;
            display:flex;
            justify-content:flex-end;
          }
          /* Kotak Keluar (.side-foot) dipindah jadi position:fixed nempel ke
             dasar viewport, PERSIS kayak bar Simpan (position:fixed;bottom:0)
             -- dgn tinggi (height, bukan min-height) yg sama-sama pakai var
             --lp-bar-h. Karena dua-duanya "nempel dasar viewport + tinggi
             sama", border-top keduanya MESTI jatuh di Y yang sama persis --
             gak lagi bergantung ke hitungan flex/100vh sidebar yang ternyata
             masih bisa meleset dikit. width ikut lebar sidebar (256px normal
             / 76px collapsed) biar nempel pas di bawah nav, dan .side-nav
             dikasih padding-bottom senilai --lp-bar-h supaya isi nav gak
             ketutup kotak Keluar yang sekarang fixed. border-right jadi
             garis vertikal pembatas menerus dgn border-right .sidebar di
             atasnya (warna & ketebalan sama) -- karena .side-foot sekarang
             fixed & lepas dari flow .sidebar, garis vertikal .sidebar itu
             sendiri gak otomatis "nempel" turun ke box ini, jadi digambar
             ulang di sini. Hanya berlaku saat tab Konten Landing aktif
             (body punya class lp-active yg ditambah JS). */
          body.lp-active .side-foot{
            position:fixed;
            left:0;bottom:0;
            width:var(--sidebar-w);
            height:var(--lp-bar-h);
            box-sizing:border-box;
            display:flex;
            align-items:center;
            background:var(--panel);
            border-right:1px solid var(--border-soft);
            z-index:100011;
            transition:width .25s ease;
          }
          body.lp-active .side-foot form.logout{width:100%;}
          body.lp-active .side-nav{padding-bottom:var(--lp-bar-h);}
          body.lp-active .sidebar.collapsed .side-foot{width:76px;}
          .sidebar.collapsed ~ .main .lp-form-actions{left:76px;}
          @media(max-width:900px){
            .lp-form-actions{left:0;}
            .lp-form-actions-inner{padding:14px 16px;}
            body.lp-active .side-foot{position:static;width:100%;height:auto;display:block;border-right:none;}
            body.lp-active .side-nav{padding-bottom:0;}
          }
          @media(max-width:600px){
            .lp-form-actions-inner{padding:12px;}
          }

          /* Pratinjau tetap di posisi normalnya (bawah editor, landscape) --
             tidak sticky karena sudah 1 kolom, bukan sejajar ke samping. */
          .lp-preview-panel{position:static;}
          .lp-preview-panel .panel-head h3{display:flex;align-items:center;gap:9px;}
          .lp-live-dot{width:7px;height:7px;border-radius:50%;background:var(--success-bright);box-shadow:0 0 0 3px var(--success-dim);animation:lpPulse 1.8s ease-in-out infinite;}
          @keyframes lpPulse{ 0%,100%{opacity:1;} 50%{opacity:.35;} }
          .lp-preview-body{padding:0 22px 22px;}

          /* Preview dibuat seperti viewport desktop mini agar landing page utuh
             terlihat di dalam kartu Admin tanpa memperbesar panel. */
          .lp-browser-frame{
            border-radius:12px;overflow:hidden;border:1px solid var(--border-soft);
            box-shadow:0 14px 34px -14px rgba(0,0,0,.4);
            background:var(--bg);
          }
          .lp-preview{
            zoom:.72;
            width:138.8889%;
          }
          @media (max-width:1280px){
            .lp-preview{zoom:.64;width:156.25%;}
          }
          @media (max-width:1100px){
            .lp-preview{zoom:.78;width:128.2051%;}
          }

          /* Mode rasio sempit (mobile/tablet kecil): kartu "Pratinjau Langsung"
             tetap merender landing page pada lebar wajarnya (bukan dipaksa
             mengecil sampai kepotong), lalu framenya dikasih scroll horizontal
             supaya Admin bisa geser scrollbar untuk melihat sisi yang tidak
             muat -- BUKAN diubah jadi ikut menyempit/terpotong seperti sebelumnya.
             Sengaja dibatasi @media ini saja, jadi tampilan desktop (>900px)
             di atas tetap persis seperti semula, tidak ikut berubah. */
          @media (max-width:900px){
            .lp-browser-frame{
              overflow-x:auto;
              overflow-y:hidden;
              -webkit-overflow-scrolling:touch;
              scrollbar-width:thin;
              scrollbar-color:var(--border-strong) transparent;
            }
            .lp-browser-frame::-webkit-scrollbar{height:9px;}
            .lp-browser-frame::-webkit-scrollbar-track{background:transparent;}
            .lp-browser-frame::-webkit-scrollbar-thumb{background:var(--border-strong);border-radius:999px;}
            .lp-browser-frame::-webkit-scrollbar-thumb:hover{background:var(--gold);}
            .lp-browser-bar{min-width:640px;}
            .lp-preview{zoom:1;width:640px;min-width:640px;}
          }

          .lp-browser-bar{display:flex;align-items:center;gap:6px;padding:10px 12px;background:var(--panel-alt);border-bottom:1px solid var(--border-soft);}
          .lp-browser-dot{width:8px;height:8px;border-radius:50%;background:var(--border-strong);}
          .lp-browser-url{
            margin-left:8px;flex:1;font-family:var(--mono);font-size:10.5px;color:var(--text-dim);
            background:var(--panel);border:1px solid var(--border-soft);border-radius:6px;padding:3px 10px;
          }

          .lp-preview{background:var(--bg);color:var(--text);font-family:var(--body);}
          .lp-preview [data-lp-preview-section]{position:relative;outline:2px solid transparent;outline-offset:-2px;transition:outline-color .2s ease;}
          .lp-preview [data-lp-preview-section].is-focus{outline-color:var(--gold);}

          .lp-hero{padding:26px 22px 22px;position:relative;overflow:hidden;background:linear-gradient(160deg,var(--panel-2),var(--bg-deep));}
          /* Foto (::before) & overlay gradient (::after) dipisah supaya slider
             "Blur Foto Latar" cuma mem-blur foto-nya, tidak ikut mem-blur teks
             judul/deskripsi pratinjau -- lihat --lp-hero-photo/-blur/-overlay
             yang di-set inline & disinkronkan lewat JS pas slider digeser. */
          .lp-hero::before{
            content:"";position:absolute;inset:-14px;z-index:0;
            background-image:var(--lp-hero-photo, none);
            background-size:cover;background-position:center;
            filter:blur(var(--lp-hero-blur, 0px));
          }
          /* Mode latar VIDEO: sembunyikan layer foto (::before) & tampilkan
             <video> sungguhan sebagai gantinya, blur/overlay tetap sinkron
             lewat variabel CSS yang sama dengan mode gambar. */
          .lp-hero.lp-hero-bg-video::before{display:none;}
          .lp-hero-video-bg{
            position:absolute;inset:-14px;z-index:0;width:calc(100% + 28px);height:calc(100% + 28px);
            object-fit:cover;filter:blur(var(--lp-hero-blur, 0px));
          }
          .lp-hero::after{
            content:"";position:absolute;inset:0;z-index:1;
            opacity:var(--lp-hero-overlay, 1);
            background-image:linear-gradient(160deg, color-mix(in srgb, var(--panel-2) 85%, transparent), color-mix(in srgb, var(--bg-deep) 75%, transparent));
          }
          .lp-hero > *{position:relative;z-index:2;}
          .lp-eyebrow{font-family:var(--mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--gold-bright);margin-bottom:10px;}
          .lp-h1{font-family:var(--display);font-size:27px;font-weight:700;line-height:1;text-transform:uppercase;margin-bottom:9px;color:var(--text);}
          .lp-h1 em{color:var(--gold-bright);font-style:normal;}
          .lp-h2{font-size:13px;font-weight:600;margin-bottom:9px;color:var(--text);}
          .lp-p{font-size:11.5px;line-height:1.65;color:var(--text-muted);white-space:pre-line;}

          .lp-features{display:grid;grid-template-columns:1fr 1fr;gap:1px;background:var(--border-soft);}
          .lp-features .lp-feature-card{background:var(--panel);padding:15px;}
          .lp-features .lp-feature-card b{display:block;font-family:var(--display);font-size:12.5px;margin-bottom:5px;color:var(--text);}
          .lp-features .lp-feature-card span{font-size:10.5px;color:var(--text-muted);line-height:1.55;}

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

        </style>

        <script>
          (function(){
            var form = document.getElementById('landingForm');
            if(!form) return;

            // ---------- lp-active body class: kotak Keluar jadi fixed, nempel dasar ----------
            // Saat tab Pengaturan Umum aktif, body.lp-active bikin .side-foot (kotak Keluar)
            // position:fixed;bottom:0 dgn tinggi --lp-bar-h -- persis kayak bar Simpan yg juga
            // fixed;bottom:0 dgn tinggi sama. Border-top keduanya dijamin lurus di Y yang sama.
            (function(){
              var pengaturanPanel = document.querySelector('[data-tab-panel="pengaturan-umum"]');
              var bar = document.querySelector('.lp-form-actions');
              if(!pengaturanPanel) return;

              // ---- sinkron --lp-bar-h: ukur tinggi ASLI bar Simpan, jangan
              // percaya angka statis di CSS. Bar ini display:none saat tab
              // nggak aktif (tinggi kebaca 0), jadi cuma diukur waktu tab
              // aktif. Dengan ini, --lp-bar-h selalu pas piksel-demi-piksel
              // dgn tinggi bar yang beneran dirender browser -- kebal dari
              // style bocor / perubahan CSS di masa depan yang mengubah
              // tinggi bar tanpa sadar mengubah nomor 66px di CSS. ----
              function syncBarHeight(){
                if(!bar || !pengaturanPanel.classList.contains('active')) return;
                var h = bar.getBoundingClientRect().height;
                if(h > 0){
                  document.documentElement.style.setProperty('--lp-bar-h', h + 'px');
                }
              }
              function syncLpActive(){
                var active = pengaturanPanel.classList.contains('active');
                document.body.classList.toggle('lp-active', active);
                if(active){
                  // rAF: tunggu 1 frame supaya display:block dari .active
                  // sudah kepakai & tinggi bar bisa diukur dgn benar.
                  requestAnimationFrame(syncBarHeight);
                }
              }
              syncLpActive(); // cek kondisi awal (sesi tersimpan)
              var obs = new MutationObserver(syncLpActive);
              obs.observe(pengaturanPanel, {attributes:true, attributeFilter:['class']});

              window.addEventListener('resize', syncBarHeight);
              if(bar && window.ResizeObserver){
                new ResizeObserver(syncBarHeight).observe(bar);
              }
              if(document.fonts && document.fonts.ready){
                document.fonts.ready.then(syncBarHeight);
              }
            })();

            // ---------- draft autosave (jaga-jaga sesi habis di tengah edit) ----------
            var LP_DRAFT_KEY = 'siberadLandingDraft';
            function saveDraft(){
              try {
                var data = {};
                form.querySelectorAll('input[type="text"], input[type="url"], input[type="email"], textarea').forEach(function(el){
                  if (el.name) data[el.name] = el.value;
                });
                sessionStorage.setItem(LP_DRAFT_KEY, JSON.stringify(data));
              } catch (e) {}
            }
            function restoreDraftIfAny(){
              try {
                var raw = sessionStorage.getItem(LP_DRAFT_KEY);
                if (!raw) return;
                var data = JSON.parse(raw);
                var restored = false;
                Object.keys(data).forEach(function(name){
                  var el = form.querySelector('[name="'+name+'"]');
                  if (el && data[name] && el.value !== data[name]) { el.value = data[name]; restored = true; }
                });
                if (restored) siberadShowToast('success', 'Draft konten landing yang belum tersimpan berhasil dipulihkan.');
              } catch (e) {}
            }
            restoreDraftIfAny();
            form.addEventListener('input', function(){ saveDraft(); });

            // ---------- submit: loading indicator + upload progress ----------
            // Tombol Simpan di-disable saat submit untuk mencegah double-submit
            // (klik berkali-kali saat upload video besar yang lambat). Progress
            // text ditampilkan supaya Admin tahu upload sedang berjalan, bukan
            // browser-nya hang. Kalau ada video dipilih, tampilkan progress
            // upload via XMLHttpRequest agar lebih informatif dari sekadar spinner.
            @php
            // Inject CSRF token ke JS -- tidak bisa pakai meta[csrf-token] karena
            // bisa saja belum ada, dan form sudah punya @csrf yang di-render server.
            $csrfToken = csrf_token();
            @endphp
            var lpSubmitBtn = document.getElementById('landingFormSubmitBtn');
            var lpUploadProgress = document.getElementById('landingFormUploadProgress');
            var lpUploadProgressText = document.getElementById('landingFormUploadProgressText');

            // Inject animasi spin sekali (tidak perlu duplikat kalau sudah ada)
            if(!document.getElementById('lpUploadSpinStyle')){
              var spinStyle = document.createElement('style');
              spinStyle.id = 'lpUploadSpinStyle';
              spinStyle.textContent = '@keyframes lpUploadSpin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
              document.head.appendChild(spinStyle);
            }

            form.addEventListener('submit', function(e){
              try { sessionStorage.removeItem(LP_DRAFT_KEY); } catch (ex) {}
              if (typeof lpRestorePanelsToStore === 'function') lpRestorePanelsToStore();

              var heroVideoInput = form.querySelector('[data-lp-video="hero_video"]');
              var hasVideo = heroVideoInput && heroVideoInput.files && heroVideoInput.files.length > 0;

              // Tampilkan loading state pada tombol
              if(lpSubmitBtn){
                lpSubmitBtn.disabled = true;
                lpSubmitBtn.textContent = hasVideo ? 'Mengunggah video...' : 'Menyimpan...';
              }
              if(lpUploadProgress) lpUploadProgress.style.display = 'inline-flex';

              if(!hasVideo) return; // submit biasa (native), tidak perlu XHR

              // Ada video -- intercept dengan XHR supaya bisa pantau progress
              e.preventDefault();

              var xhr = new XMLHttpRequest();
              var formData = new FormData(form);

              xhr.upload.addEventListener('progress', function(ev){
                if(!ev.lengthComputable) return;
                var pct = Math.round((ev.loaded / ev.total) * 100);
                var mb = (ev.loaded / (1024*1024)).toFixed(1);
                var total = (ev.total / (1024*1024)).toFixed(1);
                if(lpUploadProgressText) lpUploadProgressText.textContent = 'Mengunggah video... '+pct+'% ('+mb+'/'+total+' MB)';
              });

              xhr.addEventListener('load', function(){
                // Server merespons -- ikuti redirect (3xx) atau tampilkan error
                if(xhr.status >= 200 && xhr.status < 400){
                  // Respons sukses atau redirect -- reload halaman untuk
                  // menampilkan flash message dari server (back()->with(...))
                  if(lpUploadProgressText) lpUploadProgressText.textContent = 'Tersimpan, memuat ulang...';
                  window.location.href = xhr.responseURL || window.location.href;
                } else {
                  // Error dari server -- tampilkan pesan dan enable kembali tombol
                  var errMsg = 'Gagal menyimpan (HTTP '+xhr.status+'). Coba lagi.';
                  try {
                    var json = JSON.parse(xhr.responseText);
                    if(json && json.message) errMsg = json.message;
                    else if(json && json.errors){
                      var msgs = [];
                      Object.values(json.errors).forEach(function(arr){ if(Array.isArray(arr)) msgs = msgs.concat(arr); });
                      if(msgs.length) errMsg = msgs.join(' ');
                    }
                  } catch(ex){}
                  window.siberadShowToast && window.siberadShowToast('error', errMsg);
                  if(lpSubmitBtn){ lpSubmitBtn.disabled = false; lpSubmitBtn.textContent = 'Simpan Konten Landing'; }
                  if(lpUploadProgress) lpUploadProgress.style.display = 'none';
                }
              });

              xhr.addEventListener('error', function(){
                window.siberadShowToast && window.siberadShowToast('error',
                  'Koneksi terputus saat mengunggah video. Periksa internet dan coba lagi.');
                if(lpSubmitBtn){ lpSubmitBtn.disabled = false; lpSubmitBtn.textContent = 'Simpan Konten Landing'; }
                if(lpUploadProgress) lpUploadProgress.style.display = 'none';
              });

              xhr.addEventListener('timeout', function(){
                window.siberadShowToast && window.siberadShowToast('error',
                  'Waktu upload habis (timeout). Coba lagi atau gunakan video yang lebih kecil.');
                if(lpSubmitBtn){ lpSubmitBtn.disabled = false; lpSubmitBtn.textContent = 'Simpan Konten Landing'; }
                if(lpUploadProgress) lpUploadProgress.style.display = 'none';
              });

              xhr.timeout = 600000; // 10 menit -- batas klien, lebih dari cukup
              xhr.open('POST', form.action);
              xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
              xhr.setRequestHeader('X-CSRF-TOKEN', '{{ $csrfToken }}');
              xhr.send(formData);
            });

            // ---------- MODAL CARD PANEL EDITOR: KLIK KARTU RINGKASAN -> BUKA MODAL POP-UP ----------
            var overviewCards = form.querySelectorAll('[data-lp-tab]');
            var previewSections = document.querySelectorAll('[data-lp-preview-section]');
            var modalBackdrop = document.getElementById('lpLandingModalBackdrop');
            if (modalBackdrop && modalBackdrop.parentElement !== document.body) {
              document.body.appendChild(modalBackdrop);
            }
            var modalBody = document.getElementById('lpLandingModalBody');
            var modalTitle = document.getElementById('lpLandingModalTitle');
            var modalDesc = document.getElementById('lpLandingModalDesc');
            var modalIcon = document.getElementById('lpLandingModalIcon');
            var modalCloseBtn = document.getElementById('lpLandingModalCloseBtn');
            var modalCancelBtn = document.getElementById('lpLandingModalCancelBtn');
            var modalSaveBtn = document.getElementById('lpLandingModalSaveBtn');
            var lpMovedPanels = []; // {el, parent, next}
            var lpOpenCard = null;

            var SECTION_META = {
              'lpPanelHeroBg': {
                title: 'Latar Belakang Beranda',
                desc: 'Atur gambar atau video latar belakang pada bagian hero beranda beserta efek blur dan overlay warna.',
                iconClass: 'lp-ov-gold',
                iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>'
              },
              'lpPanelHeroText': {
                title: 'Judul & Deskripsi Utama',
                desc: 'Atur teks sapaan (eyebrow), judul utama dengan aksen warna, subjudul, dan deskripsi beranda.',
                iconClass: 'lp-ov-purple',
                iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>'
              },
              'lpPanelTentang': {
                title: 'Tentang & Profil Instansi',
                desc: 'Atur ringkasan profil satuan, moto instansi, makna logo resmi, dan tautan dokumen selayang pandang.',
                iconClass: 'lp-ov-green',
                iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
              },
              'lpPanelFitur': {
                title: 'Fitur Unggulan',
                desc: 'Atur judul dan deskripsi 4 pilar fitur atau layanan utama yang ditampilkan di beranda.',
                iconClass: 'lp-ov-amber',
                iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'
              },
              'lpPanelKontakInfo': {
                title: 'Informasi Kontak',
                desc: 'Atur alamat kantor, email resmi, nomor telepon, dan URL website resmi instansi.',
                iconClass: 'lp-ov-pink',
                iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12a10 10 0 1 1-5.6-9"/><path d="M15 8l4-4"/><path d="M15 4h4v4"/></svg>'
              },
              'lpPanelKontakSosmed': {
                title: 'Sosial Media',
                desc: 'Atur label tautan dan URL channel sosial media resmi instansi di bagian footer.',
                iconClass: 'lp-ov-red',
                iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-3.9M8.6 13.5l6.8 3.9"/></svg>'
              }
            };

            function lpRestorePanelsToStore(){
              for (var i = lpMovedPanels.length - 1; i >= 0; i--) {
                var rec = lpMovedPanels[i];
                if (rec.el && rec.parent) {
                  rec.parent.insertBefore(rec.el, rec.next);
                }
              }
              lpMovedPanels = [];
            }

            function lpCloseModal(){
              if (!modalBackdrop || !modalBackdrop.classList.contains('is-open')) return;
              lpRestorePanelsToStore();
              modalBackdrop.classList.remove('is-open');
              modalBackdrop.setAttribute('aria-hidden', 'true');
              document.body.classList.remove('lp-modal-lock');
              if (modalBody) {
                modalBody.innerHTML = '';
                modalBody.scrollTop = 0;
              }
              if (lpOpenCard) {
                lpOpenCard.classList.remove('lp-ov-open');
                lpOpenCard = null;
              }
              previewSections.forEach(function(s){ s.classList.remove('is-focus'); });
            }

            function lpOpenModal(card){
              var groupKey = card.dataset.lpScrollTarget;
              if (!groupKey || !modalBackdrop || !modalBody) return;
              lpCloseModal();

              var candidates = form.querySelectorAll('[data-lp-tab-panel]');
              var moved = [];
              candidates.forEach(function(panel){
                var panelGroup = panel.dataset.lpGroup || panel.id;
                if (panelGroup === groupKey) {
                  lpMovedPanels.push({ el: panel, parent: panel.parentNode, next: panel.nextSibling });
                  modalBody.appendChild(panel);
                  moved.push(panel);
                }
              });
              if (!moved.length) return;

              var meta = SECTION_META[groupKey] || {};
              var titleEl = card.querySelector('.lp-ov-title');
              var descEl = card.querySelector('.lp-ov-desc');
              var cardIcon = card.querySelector('.lp-ov-icon');

              if (modalTitle) modalTitle.textContent = meta.title || (titleEl ? titleEl.textContent : 'Pengaturan Section');
              if (modalDesc) modalDesc.textContent = meta.desc || (descEl ? descEl.textContent : 'Ubah konten dan preferensi bagian ini.');
              if (modalIcon) {
                modalIcon.className = 'lp-landing-modal-icon ' + (meta.iconClass || (cardIcon ? cardIcon.className.replace('lp-ov-icon', '').trim() : 'lp-ov-gold'));
                modalIcon.innerHTML = meta.iconSvg || (cardIcon ? cardIcon.innerHTML : '');
              }

              modalBackdrop.classList.add('is-open');
              modalBackdrop.setAttribute('aria-hidden', 'false');
              document.body.classList.add('lp-modal-lock');
              card.classList.add('lp-ov-open');
              lpOpenCard = card;
              if (modalBody) modalBody.scrollTop = 0;

              var focusName = moved[0].dataset.lpTabPanel;
              previewSections.forEach(function(s){ s.classList.toggle('is-focus', s.dataset.lpPreviewSection === focusName); });
            }

            overviewCards.forEach(function(card){
              card.addEventListener('click', function(e){
                e.preventDefault();
                if (lpOpenCard === card) { lpCloseModal(); return; }
                lpOpenModal(card);
              });
            });

            if (modalCloseBtn) modalCloseBtn.addEventListener('click', function(e){ e.preventDefault(); lpCloseModal(); });
            if (modalCancelBtn) modalCancelBtn.addEventListener('click', function(e){ e.preventDefault(); lpCloseModal(); });
            if (modalSaveBtn) {
              modalSaveBtn.addEventListener('click', function(e){
                e.preventDefault();
                if (typeof lpRestorePanelsToStore === 'function') lpRestorePanelsToStore();
                if (form.requestSubmit) {
                  form.requestSubmit(lpSubmitBtn);
                } else {
                  form.submit();
                }
              });
            }

            if (modalBackdrop) {
              modalBackdrop.addEventListener('click', function(e){
                if (e.target === modalBackdrop) lpCloseModal();
              });
            }
            document.addEventListener('keydown', function(e){
              if (e.key === 'Escape') lpCloseModal();
            });

            // ---------- live preview ----------
            var sosialIcons = {
              instagram: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1"/></svg>',
              tiktok: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 3h-3.1v12.4a2.7 2.7 0 1 1-1.9-2.6V9.6a5.8 5.8 0 1 0 5 5.7V9.4a7.9 7.9 0 0 0 4.4 1.3V7.6c-2.2-.2-4-1.9-4.4-4.1z"/></svg>',
              youtube: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M22.5 7.2c-.3-1.1-1.1-1.9-2.1-2.2C18.6 4.5 12 4.5 12 4.5s-6.6 0-8.4.5c-1 .3-1.8 1.1-2.1 2.2C1 9 1 12 1 12s0 3 .5 4.8c.3 1.1 1.1 1.9 2.1 2.2 1.8.5 8.4.5 8.4.5s6.6 0 8.4-.5c1-.3 1.8-1.1 2.1-2.2.5-1.8.5-4.8.5-4.8s0-3-.5-4.8zM9.8 15.3V8.7l6 3.3-6 3.3z"/></svg>',
              x: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 3H22l-7.5 8.6L23 21h-6.6l-5.2-6.6L5.2 21H2l8.1-9.3L2 3h6.7l4.7 6 5.5-6z"/></svg>',
              facebook: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-8h2.7l.4-3.1h-3.1V8c0-.9.3-1.5 1.6-1.5h1.7V3.7C16.5 3.6 15.6 3.5 14.6 3.5c-2.4 0-4 1.5-4 4.1v2.3H7.9V13h2.7v8h2.9z"/></svg>',
              wikipedia: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.8 5.7 3.8 9s-1.3 6.5-3.8 9c-2.5-2.5-3.8-5.7-3.8-9s1.3-6.5 3.8-9z"/></svg>'
            };

            function setText(id, val, emptyLabel){
              var el = document.getElementById(id);
              if(!el) return;
              var hasVal = val && val.trim() !== '';
              el.textContent = hasVal ? val : (emptyLabel || el.dataset.lpEmpty || '');
              el.style.opacity = hasVal ? '1' : '.45';
              el.style.fontStyle = hasVal ? 'normal' : 'italic';
            }

            function renderFitur(){
              var wrap = document.getElementById('lpPvFitur');
              wrap.innerHTML = '';
              for(var i=0;i<4;i++){
                // document, bukan form -- lihat catatan di applyBgType() di
                // bawah: elemen ini bisa sedang dipindah ke dalam modal.
                var judul = document.querySelector('[data-lp="fitur_judul_'+i+'"]');
                var desk = document.querySelector('[data-lp="fitur_deskripsi_'+i+'"]');
                if(!judul) continue;
                var card = document.createElement('div');
                card.className = 'lp-feature-card';
                card.innerHTML = '<b></b><span></span>';
                card.querySelector('b').textContent = judul.value || 'Judul fitur ' + (i + 1);
                card.querySelector('span').textContent = desk ? desk.value : '';
                wrap.appendChild(card);
              }
            }

            function renderSosial(){
              var wrap = document.getElementById('lpPvSosial');
              wrap.innerHTML = '';
              var i = 0;
              while (true) {
                var platformEl = document.querySelector('[data-lp="sosial_platform_'+i+'"]');
                if(!platformEl) break;
                var labelEl = document.querySelector('[data-lp="sosial_label_'+i+'"]');
                var urlEl = document.querySelector('[data-lp="sosial_url_'+i+'"]');
                var url = urlEl ? urlEl.value.trim() : '';
                if(url){
                  var chip = document.createElement('span');
                  chip.className = 'lp-sosial-chip';
                  chip.innerHTML = (sosialIcons[platformEl.value] || '') + '<span></span>';
                  chip.querySelector('span').textContent = (labelEl && labelEl.value) || platformEl.value;
                  wrap.appendChild(chip);
                }
                i++;
              }
            }

            function updatePreview(){
              // document, bukan form -- panel section yang sedang dibuka di
              // modal (lihat catatan di applyBgType() di bawah) sudah
              // dipindah keluar dari <form>, jadi query lewat `form` bisa
              // balik null utk field yang sedang diedit & bikin baris
              // `.value` di bawah lempar TypeError (mematikan sisa
              // pratinjau, padahal modal menjanjikan "otomatis
              // tersinkronisasi ke Pratinjau Langsung").
              var heroBlurEl = document.querySelector('[data-lp="hero_blur_level"]');
              var heroOverlayEl = document.querySelector('[data-lp="hero_overlay_intensity"]');
              var lpPreviewHero = document.getElementById('lpPreviewHero');
              if(lpPreviewHero && heroBlurEl){ lpPreviewHero.style.setProperty('--lp-hero-blur', heroBlurEl.value + 'px'); }
              if(lpPreviewHero && heroOverlayEl){ lpPreviewHero.style.setProperty('--lp-hero-overlay', (heroOverlayEl.value / 100)); }
              // Sinkron juga ke pratinjau gambar/video BESAR di dalam modal
              // "Latar Belakang Beranda" sendiri (bukan cuma di panel
              // Pratinjau Langsung yang letaknya terpisah) -- supaya efek
              // blur & overlay-nya langsung kelihatan begitu slider digeser,
              // tanpa perlu simpan dulu.
              if(heroBlurEl){
                var blurCss = 'blur(' + heroBlurEl.value + 'px)';
                var imgEl = document.getElementById('lpHeroImagePreviewImg');
                var vidEl = document.getElementById('lpHeroVideoPreviewVideo');
                if(imgEl){ imgEl.style.filter = blurCss; }
                if(vidEl){ vidEl.style.filter = blurCss; }
              }
              if(heroOverlayEl){
                var overlayOpacity = heroOverlayEl.value / 100;
                var imgOverlayEl = document.getElementById('lpHeroImagePreviewOverlay');
                var vidOverlayEl = document.getElementById('lpHeroVideoPreviewOverlay');
                if(imgOverlayEl){ imgOverlayEl.style.opacity = overlayOpacity; }
                if(vidOverlayEl){ vidOverlayEl.style.opacity = overlayOpacity; }
              }
              setText('lpPvEyebrow', document.querySelector('[data-lp="hero_eyebrow"]').value, 'PUSSIBERAD SISTEM PENDUKUNG OPERASIONAL');
              setText('lpPvJudulAwal', document.querySelector('[data-lp="hero_judul_awal"]').value, 'SIBER');
              setText('lpPvJudulAksen', document.querySelector('[data-lp="hero_judul_aksen"]').value, 'AD');
              setText('lpPvSubjudul', document.querySelector('[data-lp="hero_subjudul"]').value);
              setText('lpPvDeskripsi', document.querySelector('[data-lp="hero_deskripsi"]').value);
              setText('lpPvTentang', document.querySelector('[data-lp="tentang_deskripsi"]').value);
              setText('lpPvMotoJudul', document.querySelector('[data-lp="tentang_moto_judul"]').value);
              setText('lpPvMoto', document.querySelector('[data-lp="tentang_moto_deskripsi"]').value);
              setText('lpPvAlamat', document.querySelector('[data-lp="alamat"]').value);
              setText('lpPvTelepon', document.querySelector('[data-lp="telepon_kontak"]').value);
              setText('lpPvEmail', document.querySelector('[data-lp="email_kontak"]').value);
              setText('lpPvWebsite', document.querySelector('[data-lp="website"]').value);
              renderFitur();
              renderSosial();
            }

            form.querySelectorAll('[data-lp]').forEach(function(el){
              el.addEventListener('input', updatePreview);
              el.addEventListener('focus', function(){
                var tabPanel = el.closest('[data-lp-tab-panel]');
                if(tabPanel){
                  previewSections.forEach(function(s){ s.classList.toggle('is-focus', s.dataset.lpPreviewSection === tabPanel.dataset.lpTabPanel); });
                }
              });
            });

            // Batas ukuran gambar (BG beranda & logo) -- HARUS sama persis
            // dengan validasi server ('max:5120' KB di SettingController)
            // supaya Admin ditolak SAAT MEMILIH FILE (instan, tanpa perlu
            // submit + tunggu round-trip ke server dulu baru tahu gagal).
            var LP_MAX_IMAGE_BYTES = 5 * 1024 * 1024; // 5 MB
            function lpTolakJikaTerlaluBesar(input, labelGambar){
              var file = input.files && input.files[0];
              if(!file || file.size <= LP_MAX_IMAGE_BYTES) return false;
              var ukuranMb = (file.size / (1024 * 1024)).toFixed(1);
              window.siberadShowToast && window.siberadShowToast('error',
                labelGambar+' berukuran '+ukuranMb+' MB, melebihi batas maksimal 5 MB. Silakan kompres atau pilih foto lain.');
              input.value = ''; // batalkan pilihan supaya tidak ikut ke-submit
              return true;
            }

            var heroImageInput = form.querySelector('[data-lp-image="hero_image"]');
            if(heroImageInput){
              heroImageInput.addEventListener('change', function(){
                if(lpTolakJikaTerlaluBesar(this, 'Gambar latar beranda')) return;
                var file = this.files && this.files[0];
                var heroEl = document.getElementById('lpPreviewHero');
                var previewImg = document.getElementById('lpHeroImagePreviewImg');
                var previewFrame = document.getElementById('lpHeroImagePreviewFrame');
                var placeholder = document.getElementById('lpHeroImagePreviewPlaceholder');
                if(!file){ heroEl.style.setProperty('--lp-hero-photo', 'none'); return; }
                var reader = new FileReader();
                reader.onload = function(e){
                  heroEl.style.setProperty('--lp-hero-photo', 'url(' + e.target.result + ')');
                  // Tampilkan pratinjau BG realtime di sebelah tombol pilih file,
                  // gantikan kotak "belum ada gambar" begitu file dipilih.
                  if(previewImg){ previewImg.src = e.target.result; }
                  if(previewFrame){ previewFrame.style.display = ''; }
                  if(placeholder){ placeholder.style.display = 'none'; }
                };
                reader.readAsDataURL(file);
              });
            }

            // Batas ukuran & durasi VIDEO latar beranda -- HARUS sama persis
            // dengan validasi server ('max:102400' KB + rule durasi 60 detik
            // di SettingController::updateLanding()).
            var LP_MAX_VIDEO_BYTES = 100 * 1024 * 1024; // 100 MB
            var LP_MAX_VIDEO_DURATION_SECONDS = 60; // 1 menit
            var heroVideoInput = form.querySelector('[data-lp-video="hero_video"]');
            if(heroVideoInput){
              heroVideoInput.addEventListener('change', function(){
                var input = this;
                var file = input.files && input.files[0];
                if(!file){
                  var lpPreviewVideoKosong = document.getElementById('lpPreviewHeroVideo');
                  if(lpPreviewVideoKosong){ lpPreviewVideoKosong.removeAttribute('src'); lpPreviewVideoKosong.style.display = 'none'; }
                  return;
                }
                if(file.size > LP_MAX_VIDEO_BYTES){
                  var ukuranMb = (file.size / (1024 * 1024)).toFixed(1);
                  window.siberadShowToast && window.siberadShowToast('error',
                    'Video latar beranda berukuran '+ukuranMb+' MB, melebihi batas maksimal 100 MB. Silakan kompres atau pilih video lain.');
                  input.value = '';
                  return;
                }

                var url = URL.createObjectURL(file);

                // Cek durasi lewat metadata video di browser SEBELUM
                // ditampilkan sebagai pratinjau / ikut ke-submit -- deteksi
                // instan tanpa perlu round-trip ke server dulu. Validasi
                // durasi di server (ffprobe, lihat SettingController) tetap
                // jadi pengaman utama karena cek di sisi klien ini bisa saja
                // dilewati (mis. devtools).
                var probeVideo = document.createElement('video');
                probeVideo.preload = 'metadata';
                probeVideo.onloadedmetadata = function(){
                  var durasi = probeVideo.duration;
                  if(isFinite(durasi) && durasi > LP_MAX_VIDEO_DURATION_SECONDS){
                    var durasiBulat = Math.ceil(durasi);
                    window.siberadShowToast && window.siberadShowToast('error',
                      'Durasi video latar beranda '+durasiBulat+' detik, melebihi batas maksimal 60 detik (1 menit). Silakan potong videonya terlebih dahulu.');
                    input.value = '';
                    URL.revokeObjectURL(url);
                    return;
                  }
                  tampilkanPratinjauVideoHero(file, url);
                };
                probeVideo.onerror = function(){
                  // Gagal baca metadata (format tak dikenal browser, dsb) --
                  // lewati saja pengecekan durasi di sini, biar server yang
                  // akhirnya menolak/menerima lewat ffprobe.
                  tampilkanPratinjauVideoHero(file, url);
                };
                probeVideo.src = url;
              });
            }

            function tampilkanPratinjauVideoHero(file, url){
              var previewVideo = document.getElementById('lpHeroVideoPreviewVideo');
              var previewFrame = document.getElementById('lpHeroVideoPreviewFrame');
              var placeholder = document.getElementById('lpHeroVideoPreviewPlaceholder');
              var lpPreviewVideo = document.getElementById('lpPreviewHeroVideo');
              if(previewVideo){ previewVideo.src = url; }
              if(previewFrame){ previewFrame.style.display = ''; }
              if(placeholder){ placeholder.style.display = 'none'; }
              // Ikut tampilkan di panel Pratinjau Langsung kalau tipe latar
              // yang sedang aktif memang video.
              if(lpPreviewVideo){ lpPreviewVideo.src = url; }
            }

            // Toggle panel Gambar <-> Video sesuai radio "hero_bg_type" yang
            // dipilih, plus sinkronkan latar di panel Pratinjau Langsung.
            //
            // CATATAN PERBAIKAN BUG: sebelumnya listener "change" dipasang
            // satu-satu ke tiap radio (bgTypeRadios.forEach(...)) saat panel
            // ini masih berada di dalam #lpPanelsStore. Begitu panel yang
            // sama dipindah (appendChild) ke dalam modal lewat lpOpenModal(),
            // radio berpindah tempat tapi listener HARUSNYA tetap menempel --
            // namun pada praktiknya highlight tombol GAMBAR/VIDEO tetap
            // berubah (itu murni CSS ":has(input:checked)"), sedangkan
            // panel kontennya tidak ikut berganti, tanda listener "change"-
            // nya tidak lagi terpicu. Diganti jadi event delegation di
            // `document` (elemen yang TIDAK PERNAH ikut berpindah), supaya
            // toggle panel tetap jalan berapa kali pun modalnya dibuka-tutup.
            var lpPreviewHeroEl = document.getElementById('lpPreviewHero');
            var lpPreviewVideoEl = document.getElementById('lpPreviewHeroVideo');
            function applyBgType(type){
              // PENTING: query dari document, BUKAN dari `form`. Panel ini
              // (beserta sub-panel Gambar/Video di dalamnya) dipindahkan
              // (appendChild) ke dalam modal saat diklik, dan modal itu
              // sendiri sudah dipindah jadi child langsung <body> -- jadi
              // begitu modal terbuka, sub-panel ini sudah tidak lagi berada
              // di dalam elemen `form`. Query lewat `form.querySelectorAll`
              // jadi selalu kosong di dalam modal, sehingga toggle Gambar<->
              // Video kelihatan macet (radio-nya tetap ke-highlight karena
              // itu murni CSS :checked, tapi sub-panel yang tampil tidak
              // pernah ikut ditukar). Query dari `document` aman karena
              // atribut data-lp-bg-type-panel ini unik untuk section ini.
              document.querySelectorAll('[data-lp-bg-type-panel]').forEach(function(panel){
                panel.style.display = (panel.dataset.lpBgTypePanel === type) ? '' : 'none';
              });
              document.querySelectorAll('[data-lp-bg-type-radio]').forEach(function(radio){
                var opt = radio.closest('.lp-bg-type-option');
                if(opt) opt.classList.toggle('is-active', radio.checked);
              });
              if(lpPreviewHeroEl) lpPreviewHeroEl.classList.toggle('lp-hero-bg-video', type === 'video');
              if(lpPreviewVideoEl) lpPreviewVideoEl.style.display = (type === 'video' && lpPreviewVideoEl.getAttribute('src')) ? 'block' : 'none';
            }
            document.addEventListener('change', function(e){
              // CATATAN PERBAIKAN BUG: guard "form.contains(radio)" di bawah
              // ini SEBELUMNYA bikin toggle macet begitu modal dibuka --
              // sama persis root cause-nya dengan applyBgType() di atas:
              // radio-nya sudah tidak lagi berada di dalam `form` begitu
              // panel dipindah ke modal, jadi form.contains() selalu balik
              // false dan listener ini langsung berhenti (return) sebelum
              // sempat memanggil applyBgType(). Atribut data-lp-bg-type-radio
              // sudah unik untuk section ini (lihat catatan applyBgType),
              // jadi guard containment ini dihapus -- cukup pastikan radio-nya
              // ditemukan.
              var radio = e.target.closest && e.target.closest('[data-lp-bg-type-radio]');
              if(!radio) return;
              applyBgType(radio.value);
            });
            // Jaring pengaman tambahan: sebagian browser/skema event tertentu
            // kadang tidak selalu membubble-kan "change" dari radio custom
            // (yang inputnya disembunyikan lewat CSS) sampai ke document.
            // Klik pada label tombolnya dipantau juga, dicek sesaat setelah
            // status "checked" bawaan browser selesai diperbarui.
            document.addEventListener('click', function(e){
              var opt = e.target.closest && e.target.closest('.lp-bg-type-option');
              if(!opt) return;
              setTimeout(function(){
                var checked = opt.querySelector('[data-lp-bg-type-radio]:checked');
                if(checked) applyBgType(checked.value);
              }, 0);
            });
            var initialBgTypeRadio = form.querySelector('[data-lp-bg-type-radio]:checked');
            applyBgType(initialBgTypeRadio ? initialBgTypeRadio.value : 'gambar');

            updatePreview();
          })();
        </script>
      </section>

      {{-- ===== KELOLA SISTEM -> NOTIFIKASI ===== --}}
      <section class="tab-panel" data-tab-panel="setelan-notifikasi">
        <div class="section-head panel">
          <h2>Setelan Notifikasi</h2>
          <p>Fitur push notification (notifikasi yang muncul di luar sistem/tab tertutup) selalu aktif untuk seluruh pengguna dan tidak dapat dimatikan. Kirim pengumuman ke semua pengguna dari sini.</p>
        </div>

        @php
          // Kategori satuan yang bisa dipilih spesifik sebagai tujuan
          // pengumuman (opsi "Satuan Tertentu") -- sengaja TIDAK termasuk
          // 'pimpinan' (sudah ada opsi cepat "Pimpinan" sendiri) maupun
          // 'admin' (bukan target pengumuman). Urutan & label sama dengan
          // yang dipakai di form "Permintaan Laporan" Pimpinan supaya
          // konsisten.
          $snTujuanKategoriMap = [
            \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
            \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
            \App\Models\Satuan::KATEGORI_DIREKTORAT => 'Direktorat',
            \App\Models\Satuan::KATEGORI_SATLAK => 'Satlak',
            \App\Models\Satuan::KATEGORI_KOTAMA => 'Kasansi',
          ];
          $snSatuanByKategori = $semuaSatuan->groupBy('kategori');
          $snSatuanByKategoriJson = collect($snTujuanKategoriMap)->keys()->mapWithKeys(function ($snKey) use ($snSatuanByKategori) {
            return [$snKey => $snSatuanByKategori->get($snKey, collect())->map(fn ($s) => ['id' => $s->id, 'nama' => $s->nama])->values()];
          });
        @endphp

        <style>
          .sn-broadcast-form{display:flex;flex-direction:column;gap:18px;padding:18px 22px;}
          .sn-field label{display:block;font-size:11.5px;font-weight:700;color:var(--text-muted);margin-bottom:5px;}
          .sn-field input[type="text"],.sn-field select,.sn-field textarea{width:100%;box-sizing:border-box;border:1px solid var(--border-soft);border-radius:9px;padding:9px 12px;font-family:var(--body);font-size:12.5px;background:var(--panel-alt);color:var(--text);}
          .sn-field textarea{resize:vertical;min-height:80px;}
          .sn-field select:disabled{opacity:.55;cursor:not-allowed;}
          .sn-panel-row{display:flex;gap:14px;flex-wrap:wrap;}
          .sn-opt-panel{flex:1 1 220px;position:relative;cursor:pointer;}
          .sn-opt-panel input{position:absolute;opacity:0;width:100%;height:100%;margin:0;cursor:pointer;z-index:1;}
          .sn-opt-panel-card{height:100%;box-sizing:border-box;background:linear-gradient(180deg, rgba(255,255,255,.02), transparent), var(--panel-alt);border:1.5px solid var(--border-soft);border-radius:12px;padding:14px 16px;box-shadow:0 6px 18px rgba(0,0,0,.12);transition:border-color .15s ease,background .15s ease,box-shadow .15s ease,transform .15s ease;}
          .sn-opt-panel-title{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--text);}
          .sn-opt-panel-title svg{width:16px;height:16px;flex-shrink:0;}
          .sn-opt-panel-desc{font-size:11px;color:var(--text-muted);margin-top:5px;line-height:1.5;}
          .sn-opt-panel input:checked ~ .sn-opt-panel-card{border-color:var(--gold,#FF9800);background:var(--gold-dim,rgba(201,122,0,.08));box-shadow:0 8px 22px rgba(0,0,0,.18);transform:translateY(-1px);}
          .sn-opt-panel input:checked ~ .sn-opt-panel-card .sn-opt-panel-title{color:var(--gold-bright,#FF9800);}
          .sn-satuan-picker{display:flex;gap:14px;flex-wrap:wrap;margin-top:12px;padding:14px 16px;border:1px dashed var(--border-soft);border-radius:12px;background:var(--panel-alt);}
          .sn-satuan-picker .sn-field{flex:1 1 220px;margin:0;}
        </style>

        <div class="panel" style="margin-top:16px;">
          <div class="panel-head"><div><h3>Kirim Pengumuman</h3><p>Pesan akan masuk ke lonceng notifikasi penerima, dan ke notifikasi OS (push) bagi yang sudah mengizinkan.</p></div></div>
          <form method="POST" action="{{ route('admin.setelan.notifikasi.broadcast') }}" class="sn-broadcast-form" id="snBroadcastForm">
            @csrf
            <div class="sn-field">
              <label>Kategori Pengumuman</label>
              <div class="sn-panel-row">
                <label class="sn-opt-panel">
                  <input type="radio" name="kategori" value="keterangan" checked>
                  <div class="sn-opt-panel-card">
                    <div class="sn-opt-panel-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>Keterangan</div>
                    <div class="sn-opt-panel-desc">Info umum, sekadar pemberitahuan. Di lonceng penerima tidak ditandai "belum dibaca" & tidak bisa diklik.</div>
                  </div>
                </label>
                <label class="sn-opt-panel">
                  <input type="radio" name="kategori" value="maintenance">
                  <div class="sn-opt-panel-card">
                    <div class="sn-opt-panel-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6 6a2 2 0 0 0 2.8 2.8l6-6a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.8-2.8Z"></path></svg>Maintenance</div>
                    <div class="sn-opt-panel-desc">Pemeliharaan sistem/butuh perhatian. Ditandai "belum dibaca" & bisa diklik penerima utk buka detail lengkap.</div>
                  </div>
                </label>
              </div>
            </div>

            <div class="sn-field">
              <label>Tujuan Pengumuman</label>
              <div class="sn-panel-row">
                <label class="sn-opt-panel">
                  <input type="radio" name="tujuan" value="semua" checked data-sn-tujuan-radio>
                  <div class="sn-opt-panel-card">
                    <div class="sn-opt-panel-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>Semua Pengguna</div>
                    <div class="sn-opt-panel-desc">Terkirim ke seluruh pengguna yang terdaftar di sistem.</div>
                  </div>
                </label>
                <label class="sn-opt-panel">
                  <input type="radio" name="tujuan" value="pimpinan" data-sn-tujuan-radio>
                  <div class="sn-opt-panel-card">
                    <div class="sn-opt-panel-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6Z"></path></svg>Pimpinan</div>
                    <div class="sn-opt-panel-desc">Hanya Danpus & Wadan yang menerima.</div>
                  </div>
                </label>
                <label class="sn-opt-panel">
                  <input type="radio" name="tujuan" value="satuan" data-sn-tujuan-radio>
                  <div class="sn-opt-panel-card">
                    <div class="sn-opt-panel-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M8 10h.01"></path><path d="M16 10h.01"></path></svg>Satuan Tertentu</div>
                    <div class="sn-opt-panel-desc">Pilih satu satuan spesifik sebagai penerima.</div>
                  </div>
                </label>
              </div>

              <div class="sn-satuan-picker" id="snSatuanPicker" style="display:none;">
                <div class="sn-field">
                  <label for="snKategoriSatuan">Kategori Satuan</label>
                  <select id="snKategoriSatuan">
                    <option value="">Pilih kategori...</option>
                    @foreach($snTujuanKategoriMap as $snKey => $snLabel)
                      @continue(($snSatuanByKategori->get($snKey) ?? collect())->isEmpty())
                      <option value="{{ $snKey }}">{{ $snLabel }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="sn-field">
                  <label for="snSatuanId">Pilih Satuan</label>
                  <select name="satuan_id" id="snSatuanId" disabled>
                    <option value="">Pilih kategori dulu...</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="sn-field">
              <label for="snJudul">Judul</label>
              <input type="text" id="snJudul" name="judul" maxlength="100" placeholder="Contoh: Pemeliharaan Sistem" required>
            </div>
            <div class="sn-field">
              <label for="snPesan">Isi Pesan</label>
              <textarea id="snPesan" name="pesan" maxlength="500" placeholder="Tulis isi pengumuman di sini..." required></textarea>
            </div>
            <div>
              <button type="submit" class="btn btn-primary btn-sm">Kirim Pengumuman</button>
            </div>
          </form>
        </div>

        <script>
        (function () {
          var picker = document.getElementById('snSatuanPicker');
          var kategoriSelect = document.getElementById('snKategoriSatuan');
          var satuanSelect = document.getElementById('snSatuanId');
          var tujuanRadios = document.querySelectorAll('[data-sn-tujuan-radio]');
          if (!picker || !kategoriSelect || !satuanSelect || !tujuanRadios.length) return;

          var SATUAN_BY_KATEGORI = @json($snSatuanByKategoriJson);

          function toggleSatuanPicker() {
            var current = document.querySelector('[data-sn-tujuan-radio]:checked');
            var tampilkan = !!current && current.value === 'satuan';
            picker.style.display = tampilkan ? '' : 'none';
            satuanSelect.required = tampilkan;
            if (!tampilkan) {
              kategoriSelect.value = '';
              satuanSelect.innerHTML = '<option value="">Pilih kategori dulu...</option>';
              satuanSelect.disabled = true;
            }
          }

          tujuanRadios.forEach(function (radio) {
            radio.addEventListener('change', toggleSatuanPicker);
          });

          kategoriSelect.addEventListener('change', function () {
            var daftar = SATUAN_BY_KATEGORI[kategoriSelect.value] || [];
            satuanSelect.innerHTML = '';
            if (!daftar.length) {
              satuanSelect.disabled = true;
              var kosong = document.createElement('option');
              kosong.value = '';
              kosong.textContent = 'Tidak ada satuan pada kategori ini';
              satuanSelect.appendChild(kosong);
              return;
            }
            satuanSelect.disabled = false;
            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Pilih satuan...';
            satuanSelect.appendChild(placeholder);
            daftar.forEach(function (item) {
              var opt = document.createElement('option');
              opt.value = item.id;
              opt.textContent = item.nama;
              satuanSelect.appendChild(opt);
            });
          });

          toggleSatuanPicker();
        })();
        </script>

        @php
          // Verifikasi file suara benar-benar ada di disk, bukan cuma
          // percaya kolom notifikasi_sound_path terisi -- sama seperti
          // pola $pengaturanStrukturOrgExists di atas (path bisa
          // "dangling" kalau upload gagal senyap atau file terhapus
          // manual di server).
          $notifSoundExists = ($pengaturan->notifikasi_sound_path ?? null)
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($pengaturan->notifikasi_sound_path);
        @endphp
        <div class="panel" style="margin-top:16px;">
          <div class="panel-head"><div><h3>Suara Notifikasi</h3><p>Pasang satu file suara yang akan otomatis berbunyi di navbar semua dashboard (Admin, Pimpinan, dan semua Satuan) setiap kali ada notifikasi baru masuk ke lonceng.</p></div></div>
          <div style="padding:18px 22px;display:flex;flex-direction:column;gap:14px;">
            @if($notifSoundExists)
              <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:11px;flex-wrap:wrap;">
                <button type="button" class="btn btn-ghost btn-sm" id="notifSoundPlayBtn" style="display:flex;align-items:center;gap:6px;">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                  Putar Contoh
                </button>
                <span style="font-size:12px;color:var(--text-muted);flex:1;min-width:120px;">Suara notifikasi sudah terpasang.</span>
                <audio id="notifSoundPreview" src="{{ asset('storage/'.$pengaturan->notifikasi_sound_path) }}" preload="none"></audio>
              </div>
            @else
              <div class="kcard-empty" style="padding:20px 14px;">
                <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5 6 9H2v6h4l5 4V5Z"></path><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                <div class="kcard-empty-title">Belum ada suara notifikasi</div>
                <div class="kcard-empty-sub">Notifikasi masih senyap sampai kamu unggah satu file suara di bawah.</div>
              </div>
            @endif

            <form method="POST" action="{{ route('admin.setelan.notifikasi.suara.update') }}" enctype="multipart/form-data" id="notifSoundForm" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
              @csrf
              <label class="btn btn-ghost btn-sm" style="cursor:pointer" for="notifSoundInput">{{ $notifSoundExists ? 'Ganti Suara' : 'Pilih File Suara' }}</label>
              <input id="notifSoundInput" name="notifikasi_suara" type="file" accept="audio/mpeg,audio/wav,audio/ogg,.mp3,.wav,.ogg" hidden required>
              <span id="notifSoundFileName" style="font-size:12px;color:var(--text-muted)"></span>
              <button type="submit" class="btn btn-primary btn-sm" id="notifSoundSubmitBtn" disabled>Simpan Suara</button>
              @if($notifSoundExists)
                <button type="button" class="btn btn-ghost-red btn-sm" id="notifSoundDeleteBtn">Hapus Suara</button>
              @endif
            </form>
            <form method="POST" action="{{ route('admin.setelan.notifikasi.suara.destroy') }}" id="notifSoundDeleteForm" style="display:none">
              @csrf @method('DELETE')
            </form>
            <span style="font-size:11px;color:var(--text-dim)">Format MP3, WAV, atau OGG, maksimal 2 MB. Gunakan nada singkat (1&ndash;2 detik) supaya tidak mengganggu.</span>
          </div>

          <script>
          (function () {
            var input = document.getElementById('notifSoundInput');
            var fileName = document.getElementById('notifSoundFileName');
            var submitBtn = document.getElementById('notifSoundSubmitBtn');
            var deleteBtn = document.getElementById('notifSoundDeleteBtn');
            var deleteForm = document.getElementById('notifSoundDeleteForm');
            var playBtn = document.getElementById('notifSoundPlayBtn');
            var preview = document.getElementById('notifSoundPreview');

            if (input) {
              input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (submitBtn) submitBtn.disabled = !file;
                if (fileName) fileName.textContent = file ? file.name : '';
              });
            }
            if (deleteBtn && deleteForm) {
              deleteBtn.addEventListener('click', function () {
                if (window.confirm('Hapus suara notifikasi ini? Notifikasi baru tidak akan berbunyi lagi sampai Admin mengunggah suara baru.')) {
                  deleteForm.submit();
                }
              });
            }
            if (playBtn && preview) {
              playBtn.addEventListener('click', function () {
                preview.currentTime = 0;
                preview.play().catch(function () {});
              });
            }
          })();
          </script>
        </div>

      </section>

      {{-- ===== KELOLA SISTEM -> STRUKTUR ORGANISASI ===== --}}
      <section class="tab-panel" data-tab-panel="struktur-organisasi">
        <div class="section-head panel">
          <h2>Struktur Organisasi</h2>
          <p>Unggah satu gambar bagan struktur organisasi. Gambar ini akan tampil apa adanya di dashboard Kasansi, menu Lainnya &rarr; Struktur Organisasi.</p>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Gambar Struktur Organisasi</h3><p>Format JPG, PNG, atau WEBP, maksimal 8 MB. Gunakan gambar dengan resolusi tinggi supaya tetap jelas saat ditampilkan penuh.</p></div></div>
          <form method="POST" action="{{ route('admin.struktur-organisasi.update') }}" enctype="multipart/form-data" id="strukturOrgForm" style="padding:18px 22px">
            @csrf
            <div class="lp-hero-image-row">
              <img src="{{ $pengaturanStrukturOrgExists ? asset('storage/'.$pengaturan->struktur_organisasi_path) : '' }}" alt="Gambar Struktur Organisasi saat ini" class="lp-current-image" id="strukturOrgPreviewImg" style="width:100%;max-width:420px;max-height:320px;object-fit:contain;{{ $pengaturanStrukturOrgExists ? '' : 'display:none' }}">
              <div class="lp-image-placeholder" id="strukturOrgPreviewPlaceholder" style="width:100%;max-width:260px;height:160px;{{ $pengaturanStrukturOrgExists ? 'display:none' : '' }}">
                <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><circle cx="9" cy="10" r="1.8"></circle><path d="m4.5 18 5-5.5 3 3 3.5-4L20.5 18"></path></svg>
                <span>Belum ada gambar struktur organisasi</span>
              </div>
              <label class="btn btn-ghost" style="cursor:pointer" for="strukturOrgInput">{{ $pengaturanStrukturOrgExists ? 'Ganti Gambar' : 'Pilih Gambar' }}</label>
              <input id="strukturOrgInput" name="struktur_organisasi" type="file" accept="image/*" hidden required>
              <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center">
                <button type="submit" class="btn btn-primary btn-sm" id="strukturOrgSubmitBtn" disabled>Simpan Gambar</button>
                @if($pengaturanStrukturOrgExists)
                <button type="button" class="btn btn-ghost-red btn-sm" id="strukturOrgDeleteBtn">Hapus Gambar</button>
                @endif
              </div>
            </div>
          </form>
          <form method="POST" action="{{ route('admin.struktur-organisasi.destroy') }}" id="strukturOrgDeleteForm" style="display:none">
            @csrf @method('DELETE')
          </form>
        </div>

        <script>
        (function () {
          var input = document.getElementById('strukturOrgInput');
          var img = document.getElementById('strukturOrgPreviewImg');
          var placeholder = document.getElementById('strukturOrgPreviewPlaceholder');
          var submitBtn = document.getElementById('strukturOrgSubmitBtn');
          var deleteBtn = document.getElementById('strukturOrgDeleteBtn');
          var deleteForm = document.getElementById('strukturOrgDeleteForm');

          if (input) {
            input.addEventListener('change', function () {
              var file = input.files && input.files[0];
              if (!file) return;
              submitBtn.disabled = false;
              var reader = new FileReader();
              reader.onload = function (e) {
                if (img) { img.src = e.target.result; img.style.display = 'block'; }
                if (placeholder) placeholder.style.display = 'none';
              };
              reader.readAsDataURL(file);
            });
          }

          if (deleteBtn && deleteForm) {
            deleteBtn.addEventListener('click', function () {
              if (window.confirm('Hapus gambar Struktur Organisasi? Menu Struktur Organisasi di dashboard Kasansi akan kosong sampai gambar baru diunggah.')) {
                deleteForm.submit();
              }
            });
          }
        })();
        </script>
      </section>

      {{-- ===== PERMINTAAN RESET PASSWORD ===== --}}
      <section class="tab-panel" data-tab-panel="reset-password">
        <div class="panel">
          <div class="panel-head">
            <div><h2>Permintaan Ganti Password</h2><p>Permintaan ganti kata sandi yang dikirim pengguna lewat menu "Pengaturan Akun".</p></div>
            <button type="button" class="btn btn-ghost-red btn-sm" id="btnHapusRiwayatResetPassword">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>
              Hapus Riwayat
            </button>
          </div>
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
                @forelse($permintaanResetPassword as $r)@include('siberad.dashboards.partials.permintaan-reset-password-row', ['r' => $r])@empty
                <tr><td colspan="4"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada permintaan ganti password</div></div></td></tr>
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
            <table class="dtbl" id="tblSesiAktif">
              <thead><tr><th>Pengguna</th><th>IP Address</th><th>Perangkat / Browser</th><th>Titik Lokasi</th><th>Terakhir Aktif</th><th style="text-align:center;">Aksi</th></tr></thead>
              <tbody>
                @forelse($sesiAktif as $s)@include('siberad.dashboards.partials.sesi-aktif-row', ['s' => $s])@empty
                <tr class="table-empty-row"><td colspan="6"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Tidak ada sesi aktif</div></div></td></tr>
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
              <button type="submit" class="btn btn-ghost-red" id="paksaLogoutYa">Ya</button>
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

        // Paksa Logout via AJAX -- baris tabel Pengguna Aktif dibuang tanpa
        // reload halaman, sama seperti Setujui/Tolak Permintaan Ganti
        // Password. Fallback ke submit form biasa kalau fetch gagal total
        // (bukan cuma respons error server).
        document.getElementById('formPaksaLogout')?.addEventListener('submit', function (e) {
          e.preventDefault();
          var form = this;
          var overlay = document.getElementById('paksaLogoutOverlay');
          var yaBtn = document.getElementById('paksaLogoutYa');
          if (yaBtn) yaBtn.disabled = true;
          fetch(form.action, {
            method: 'POST', body: new FormData(form), credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
          }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (d) { return { status: r.status, data: d }; });
          }).then(function (res) {
            if (res.status === 200 && res.data && res.data.ok) {
              var row = document.querySelector('#tblSesiAktif tbody tr[data-session-id="' + res.data.id + '"]');
              if (row) {
                row.classList.add('siberad-row-out');
                setTimeout(function () {
                  row.remove();
                  if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblSesiAktif');
                }, 260);
              }
              overlay && overlay.classList.remove('open');
              window.siberadShowToast && window.siberadShowToast('success', res.data.message || 'Sesi berhasil dipaksa logout.');
            } else if (res.status === 401 && window.siberadTampilkanSesiBerakhir) {
              window.siberadTampilkanSesiBerakhir();
            } else {
              overlay && overlay.classList.remove('open');
              window.siberadShowToast && window.siberadShowToast('error', (res.data && res.data.message) || 'Gagal memaksa logout sesi.');
            }
          }).catch(function () {
            window.siberadShowToast && window.siberadShowToast('error', 'Gagal terhubung ke server, coba lagi.');
          }).finally(function () {
            if (yaBtn) yaBtn.disabled = false;
          });
        });
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
          <div class="confirm-icon" style="background:var(--success-dim);color:var(--success-bright)">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><circle cx="12" cy="12" r="9"></circle><path d="M9 12l2 2 4-4"></path></svg>
          </div>
          <h3 id="setujuiResetPasswordTitle">Setujui Permintaan Ganti Password?</h3>
          <p>Password baru untuk <strong id="setujuiResetPasswordNama">akun ini</strong> akan langsung aktif setelah disetujui.</p>
          <form id="formSetujuiResetPassword" method="POST" action="">
            @csrf @method('PATCH')
            <div class="confirm-actions">
              <button type="button" class="btn" id="setujuiResetPasswordBatal">Batal</button>
              <button type="submit" class="btn btn-ghost-green" id="setujuiResetPasswordYa">Ya, Setujui</button>
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
              <button type="submit" class="btn btn-ghost-red" id="tolakResetPasswordYa">Ya, Tolak</button>
            </div>
          </form>
        </div>
      </div>
      {{-- ===== KONFIRMASI HAPUS RIWAYAT PERMINTAAN GANTI PASSWORD ===== --}}
      <div class="confirm-overlay" id="hapusRiwayatResetPasswordOverlay">
        <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="hapusRiwayatResetPasswordTitle">
          <div class="confirm-icon">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>
          </div>
          <h3 id="hapusRiwayatResetPasswordTitle">Hapus Riwayat Permintaan Ganti Password?</h3>
          <p>Semua permintaan yang sudah <strong>Disetujui</strong> atau <strong>Ditolak</strong> akan dihapus permanen. Permintaan yang masih menunggu keputusan tidak akan ikut terhapus.</p>
          <form id="formHapusRiwayatResetPassword" method="POST" action="{{ route('admin.permintaan-reset-password.hapus-riwayat') }}">
            @csrf @method('DELETE')
            <div class="confirm-actions">
              <button type="button" class="btn" id="hapusRiwayatResetPasswordBatal">Batal</button>
              <button type="submit" class="btn btn-ghost-red" id="hapusRiwayatResetPasswordYa">Ya, Hapus</button>
            </div>
          </form>
        </div>
      </div>
      <script>
        document.getElementById('btnHapusRiwayatResetPassword')?.addEventListener('click', () => document.getElementById('hapusRiwayatResetPasswordOverlay')?.classList.add('open'));
        document.getElementById('hapusRiwayatResetPasswordBatal')?.addEventListener('click', () => document.getElementById('hapusRiwayatResetPasswordOverlay')?.classList.remove('open'));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') document.getElementById('hapusRiwayatResetPasswordOverlay')?.classList.remove('open'); });
      </script>
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

        // Setujui/Tolak via AJAX -- baris tabel ditimpa di tempat (badge
        // status muncul, tombol Setujui/Tolak hilang) tanpa reload halaman,
        // senada dengan Tambah/Ubah/Hapus Satuan & Pengguna. Fallback ke
        // submit form biasa kalau fetch gagal total (bukan cuma respons
        // error server).
        function siberadSubmitResetPasswordKeputusan(form, overlayId, yaBtnId, pesanDefault) {
          var overlay = document.getElementById(overlayId);
          var yaBtn = document.getElementById(yaBtnId);
          if (yaBtn) yaBtn.disabled = true;
          fetch(form.action, {
            method: 'POST', body: new FormData(form), credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
          }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (d) { return { status: r.status, data: d }; });
          }).then(function (res) {
            if (res.status === 200 && res.data && res.data.ok) {
              var row = document.querySelector('#tblResetPassword tbody tr[data-reset-id="' + res.data.id + '"]');
              if (row && typeof res.data.row_html === 'string') {
                var temp = document.createElement('tbody');
                temp.innerHTML = res.data.row_html;
                var newRow = temp.firstElementChild;
                if (newRow) row.replaceWith(newRow);
              }
              overlay && overlay.classList.remove('open');
              window.siberadShowToast && window.siberadShowToast('success', res.data.message || pesanDefault);
            } else if (res.status === 401 && window.siberadTampilkanSesiBerakhir) {
              window.siberadTampilkanSesiBerakhir();
            } else {
              overlay && overlay.classList.remove('open');
              window.siberadShowToast && window.siberadShowToast('error', (res.data && res.data.message) || 'Gagal memproses permintaan.');
            }
          }).catch(function () {
            window.siberadShowToast && window.siberadShowToast('error', 'Gagal terhubung ke server, coba lagi.');
          }).finally(function () {
            if (yaBtn) yaBtn.disabled = false;
          });
        }
        document.getElementById('formSetujuiResetPassword')?.addEventListener('submit', function (e) {
          e.preventDefault();
          siberadSubmitResetPasswordKeputusan(this, 'setujuiResetPasswordOverlay', 'setujuiResetPasswordYa', 'Permintaan ganti password disetujui.');
        });
        document.getElementById('formTolakResetPassword')?.addEventListener('submit', function (e) {
          e.preventDefault();
          siberadSubmitResetPasswordKeputusan(this, 'tolakResetPasswordOverlay', 'tolakResetPasswordYa', 'Permintaan ganti password ditolak.');
        });

        // Hapus Riwayat via AJAX -- buang semua baris yang BUKAN "Menunggu"
        // (baris "Menunggu" dikenali dari .btn-row Setujui/Tolak-nya, karena
        // server juga sengaja tidak menghapus yang masih menunggu) tanpa
        // reload halaman.
        document.getElementById('formHapusRiwayatResetPassword')?.addEventListener('submit', function (e) {
          e.preventDefault();
          var form = this;
          var overlay = document.getElementById('hapusRiwayatResetPasswordOverlay');
          var yaBtn = document.getElementById('hapusRiwayatResetPasswordYa');
          if (yaBtn) yaBtn.disabled = true;
          fetch(form.action, {
            method: 'POST', body: new FormData(form), credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
          }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (d) { return { status: r.status, data: d }; });
          }).then(function (res) {
            if (res.status === 200 && res.data && res.data.ok) {
              var tbody = document.querySelector('#tblResetPassword tbody');
              if (tbody) {
                Array.prototype.slice.call(tbody.querySelectorAll('tr[data-reset-id]')).forEach(function (row) {
                  if (row.querySelector('.btn-row')) return; // masih "Menunggu", jangan ikut dibuang
                  row.classList.add('siberad-row-out');
                  setTimeout(function () {
                    row.remove();
                    if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblResetPassword');
                  }, 260);
                });
              }
              overlay && overlay.classList.remove('open');
              window.siberadShowToast && window.siberadShowToast('success', res.data.message || 'Riwayat berhasil dihapus.');
            } else if (res.status === 401 && window.siberadTampilkanSesiBerakhir) {
              window.siberadTampilkanSesiBerakhir();
            } else {
              overlay && overlay.classList.remove('open');
              window.siberadShowToast && window.siberadShowToast('error', (res.data && res.data.message) || 'Gagal menghapus riwayat.');
            }
          }).catch(function () {
            window.siberadShowToast && window.siberadShowToast('error', 'Gagal terhubung ke server, coba lagi.');
          }).finally(function () {
            if (yaBtn) yaBtn.disabled = false;
          });
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
      <p>Sesi kamu akan diakhiri dan kamu perlu login kembali untuk mengakses {{ $pengaturan?->namaSistem() ?? "SIBERAD" }}.</p>
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

    var formToSubmit = pendingForm;
    confirmBtn.disabled = true;

    // Lepas subscription push notifikasi milik user ini dari server SEBELUM
    // sesi diakhiri (lihat penjelasan lengkap di siberadUnsubscribePush,
    // partials/push-notification-controls.blade.php) -- kalau tidak,
    // notifikasi admin ini akan terus nyasar ke device/browser ini walau
    // user lain yang login berikutnya.
    var unsubscribe = window.siberadUnsubscribePush
      ? window.siberadUnsubscribePush()
      : Promise.resolve();

    unsubscribe.then(function () {
      formToSubmit.submit();
    });
  });
})();
</script>

  <script>
  (function () {
    if (typeof Chart === 'undefined') return;

    var root = getComputedStyle(document.documentElement);
    var cGold = root.getPropertyValue('--gold-bright').trim() || '#FF9800';
    var cGreen = root.getPropertyValue('--green-bright').trim() || '#3fc27d';
    var cAmber = root.getPropertyValue('--amber').trim() || '#e0a83a';
    var cRed = root.getPropertyValue('--red').trim() || '#c62828';
    var cMuted = root.getPropertyValue('--text-muted').trim() || '#9fb3a5';
    // Warna grid chart disamakan ke --border (bukan --border-soft) supaya
    // garis bantu sumbu kelihatan jelas di kedua tema -- sebelumnya pakai
    // rgba(255,255,255,.06) hardcode yang nyaris tak kelihatan di tema
    // terang (background putih vs garis putih transparan).
    var cGrid = root.getPropertyValue('--border').trim() || 'rgba(148,163,184,.35)';
    // Dipakai buat tooltip Chart.js ikut tema (bukan kotak gelap bawaan
    // Chart.js yang selalu sama apapun temanya) -- pola sama persis kayak
    // donutRoot/trenRoot di makeStatusDonut/makeTrenLaporanChart Pimpinan.
    var cSurface = root.getPropertyValue('--panel').trim() || '#fff';
    var cText = root.getPropertyValue('--text').trim() || '#17212b';

    Chart.defaults.color = cMuted;
    Chart.defaults.font.family = "'JetBrains Mono', monospace";
    Chart.defaults.font.size = 11;

    // ===== Grafik 1: Pengguna per Kategori Satuan -- radar (bukan doughnut
    // lagi), sesuai referensi gambar yang diminta user: 5 sumbu (Admin,
    // Unsur Pimpinan, Unsur Pembantu Pimpinan, Unsur Pelayanan, Unsur
    // Pelaksana -- urutannya dari backend lewat $radarUrutan di
    // DashboardController::admin(), bukan ikut urutan $semuaSatuan begitu
    // saja) + rincian di samping (dot warna + nama + jumlah). Satu warna
    // konsisten (UNGU #8b5cf6, samain ke accent kartu KPI "Total Pengguna" --
    // lihat komentar ikon di markup) buat garis/isian polygon-nya (niru
    // referensi: garis radar 1 warna, TIAP SUMBU baru dikasih warna beda di
    // dot rincian sampingnya doang) -- beda dari renderDoughnut() lama yang
    // mewarnai tiap potongan beda-beda. =====
    var cUnguPengguna = '#8b5cf6';
    // Instance chart disimpan (bukan cuma "new Chart()" lepas) supaya poll
    // realtime bisa destroy+recreate pas datanya beneran berubah -- MIRROR
    // pola window.siberadRefreshStatusDonut Pimpinan: destroy+recreate
    // (bukan chart.update() manual) sekalian jadi sinyal visual "data
    // berubah" lewat animasi masuk yang replay.
    var radarChartInstance = null;
    function renderRadar(canvasId, labels, values, colors) {
      var el = document.getElementById(canvasId);
      if (!el || typeof Chart === 'undefined') return;
      if (radarChartInstance) { radarChartInstance.destroy(); radarChartInstance = null; }
      radarChartInstance = new Chart(el, {
        type: 'radar',
        data: {
          labels: labels,
          datasets: [{
            data: values,
            backgroundColor: 'rgba(139,92,246,.18)',
            borderColor: cUnguPengguna, borderWidth: 2,
            pointBackgroundColor: cUnguPengguna, pointBorderColor: cSurface, pointBorderWidth: 2,
            pointRadius: 4, pointHoverRadius: 6
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: cSurface, titleColor: cText, bodyColor: cText,
              borderColor: cGrid, borderWidth: 1, cornerRadius: 10, padding: 10,
              titleFont: { weight: '700' }, bodyFont: { weight: '600' }
            }
          },
          scales: {
            r: {
              beginAtZero: true,
              ticks: { precision: 0, backdropColor: 'transparent', color: cMuted },
              grid: { color: cGrid },
              angleLines: { color: cGrid },
              pointLabels: { color: cText, font: { size: 11, weight: '700' } }
            }
          }
        }
      });
      var legendBox = document.getElementById(canvasId + 'Legend');
      if (!legendBox) return;
      legendBox.innerHTML = '';
      // Garis solid tipis POLOS (bukan bar terisi) sebagai penghubung visual
      // label<->angka -- lihat komentar .radar-legend-connector, sengaja
      // gak proporsional ke apapun biar gak nyiratin persentase yang gak
      // ada datanya.
      labels.forEach(function (label, i) {
        var row = document.createElement('div');
        row.className = 'radar-legend-row';
        row.innerHTML = '<span class="radar-legend-dot" style="background:' + colors[i] + '"></span><span class="radar-legend-label">' + label + '</span><span class="radar-legend-connector"></span><span class="radar-legend-value">' + values[i] + '</span>';
        legendBox.appendChild(row);
      });
    }
    var distribusiKategori = @json($distribusiPenggunaKategori);
    var radarWarna = [cUnguPengguna, '#6366f1', '#0ea5e9', '#22c55e', '#f59e0b'];
    renderRadar(
      'chartKategoriSatuan',
      distribusiKategori.map(function (d) { return d.kategori; }),
      distribusiKategori.map(function (d) { return d.jumlah; }),
      radarWarna
    );
    // Dipanggil dari syncAdminKpis() (poll realtime) tiap data radar_kategori
    // dari server beda dari sebelumnya.
    window.siberadRefreshRadarKategori = function (fresh) {
      renderRadar(
        'chartKategoriSatuan',
        fresh.map(function (d) { return d.kategori; }),
        fresh.map(function (d) { return d.jumlah; }),
        radarWarna
      );
    };

    // ===== Grafik 2: Distribusi Status Laporan -- MIRROR PERSIS donut
    // "Distribusi Status Laporan" Pimpinan (makeStatusDonut() di
    // laporan-pimpinan.blade.php): 70% cutout, label % di luar tiap arc
    // (plugin arcPct) yang "tumbuh" bareng animasi awal, tooltip ikut tema
    // (bukan renderDoughnut() generik lagi kayak Grafik 1/4 -- itu dulu
    // dipakai buat semua doughnut termasuk ini, tapi versi ini sekarang
    // butuh detail lebih: teks tengah, arc %, growth-guard biar hover gak
    // bikin animasi %-nya nyangkut, lihat komentar arcPct Pimpinan). Warna
    // data TETAP hijau/merah/oren literal (bukan var(--green-bright) yang
    // di-repurpose jadi gold di dark mode). =====
    var statusLaporan = @json($statusLaporanSistem);
    // Instance chart disimpan (bukan "var chart" lokal) supaya bisa
    // destroy+recreate pas poll realtime dapet data baru -- MIRROR pola
    // window.siberadRefreshStatusDonut Pimpinan persis.
    var adminStatusDonutInstance = null;
    function makeAdminStatusDonut() {
      var el = document.getElementById('chartStatusLaporan');
      if (!el || typeof Chart === 'undefined') return;
      if (adminStatusDonutInstance) { adminStatusDonutInstance.destroy(); adminStatusDonutInstance = null; }
      var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var defs = [
        { label: 'Disetujui', color: '#22c55e', count: statusLaporan.disetujui },
        { label: 'Ditolak', color: '#ef4444', count: statusLaporan.ditolak },
        { label: 'Terlambat', color: '#ff6b6b', count: statusLaporan.terlambat },
        { label: 'Dibatalkan', color: '#c1121f', count: statusLaporan.dibatalkan }
      ];
      var total = defs.reduce(function (s, d) { return s + d.count; }, 0);
      var empty = total <= 0;
      var arcPct = {
        id: 'sbArcPctAdmin',
        afterDatasetsDraw: function (chart) {
          if (empty) return;
          var t = chart.$sbT == null ? 1 : Math.max(0, Math.min(1, chart.$sbT));
          var ctx = chart.ctx, meta = chart.getDatasetMeta(0);
          ctx.save();
          ctx.font = '800 12px ' + (getComputedStyle(el).fontFamily || 'sans-serif');
          ctx.textBaseline = 'middle';
          meta.data.forEach(function (arc, i) {
            if (!defs[i].count) return;
            var pct = Math.round(defs[i].count / total * 100);
            if (pct < 4) return;
            var shown = t >= 0.985 ? pct : Math.round(pct * t);
            if (shown < 1) return;
            var ang = (arc.startAngle + arc.endAngle) / 2;
            var rr = arc.outerRadius + 13;
            var x = arc.x + Math.cos(ang) * rr, y = arc.y + Math.sin(ang) * rr;
            ctx.fillStyle = defs[i].color;
            ctx.textAlign = Math.cos(ang) > -0.1 ? 'left' : 'right';
            ctx.fillText(shown + '%', x, y);
          });
          ctx.restore();
        }
      };
      adminStatusDonutInstance = new Chart(el, {
        type: 'doughnut',
        data: {
          labels: defs.map(function (d) { return d.label; }),
          datasets: [{
            data: empty ? [1] : defs.map(function (d) { return d.count; }),
            backgroundColor: empty ? ['rgba(127,127,127,.16)'] : defs.map(function (d) { return d.color; }),
            borderColor: 'transparent', borderWidth: 0,
            borderRadius: empty ? 0 : 7, spacing: empty ? 0 : 3, hoverOffset: empty ? 0 : 5
          }]
        },
        options: {
          cutout: '70%', responsive: true, maintainAspectRatio: false, layout: { padding: 24 },
          // $sbGrown SENGAJA mengunci animasi "tumbuh" cuma sekali -- tanpa
          // guard ini, onProgress/onComplete kepanggil ulang tiap arc
          // "membesar" pas di-hover (hoverOffset), bukan cuma pas load awal.
          animation: reduce ? false : {
            duration: 1100, easing: 'easeOutCubic',
            onProgress: function (a) { if (a && a.chart && !a.chart.$sbGrown) a.chart.$sbT = a.numSteps ? a.currentStep / a.numSteps : 1; },
            onComplete: function (a) { if (a && a.chart) { a.chart.$sbT = 1; a.chart.$sbGrown = true; } }
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              enabled: !empty, backgroundColor: cSurface, titleColor: cText, bodyColor: cText,
              borderColor: cGrid, borderWidth: 1, cornerRadius: 10, padding: 10, usePointStyle: true,
              titleFont: { weight: '700' }, bodyFont: { weight: '600' },
              callbacks: { label: function (c) { return ' ' + c.label + ': ' + c.raw + ' (' + Math.round(c.raw / total * 100) + '%)'; } }
            }
          }
        },
        plugins: [arcPct]
      });
      adminStatusDonutInstance.$sbT = reduce ? 1 : 0;
      adminStatusDonutInstance.$sbGrown = !!reduce;
    }
    makeAdminStatusDonut();
    // Dipanggil dari syncAdminKpis() (poll realtime) tiap data status_laporan
    // dari server beda dari sebelumnya -- destroy+recreate chart (animasi
    // "tumbuh" replay jadi sinyal data berubah), rincian bawahnya (#adminStatusBdWrap)
    // + total tengah donut di-swap langsung oleh syncAdminKpis() sendiri
    // (BUKAN di sini) sama seperti pola status_bd_html/status_donut_total
    // Pimpinan.
    window.siberadRefreshAdminStatusDonut = function (fresh) {
      statusLaporan = fresh;
      makeAdminStatusDonut();
    };
    // "Mengisi perlahan" -- count-up angka tengah/rincian + progress bar
    // rincian (width 0->target), MIRROR PERSIS animateStatusDistrib()
    // Pimpinan. Discope ke .status-dist-card biar gak kesenggol elemen
    // ".status-bd-count/.status-bd-pct" lain kalau suatu saat ada lagi.
    (function animateAdminStatusDistrib() {
      var card = document.querySelector('.status-dist-card');
      if (!card) return;
      if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      var bars = card.querySelectorAll('.status-bd-bar-fill');
      var barTargets = [];
      bars.forEach(function (b) { barTargets.push(b.style.width || '0%'); b.style.width = '0%'; });
      var nums = [];
      var center = card.querySelector('.status-donut-center strong');
      if (center) nums.push({ el: center, to: parseInt(center.textContent, 10) || 0, suf: '' });
      card.querySelectorAll('.status-bd-count').forEach(function (el) { nums.push({ el: el, to: parseInt(el.textContent, 10) || 0, suf: '' }); });
      card.querySelectorAll('.status-bd-pct').forEach(function (el) { nums.push({ el: el, to: parseInt(el.textContent, 10) || 0, suf: '%' }); });
      nums.forEach(function (n) { n.el.textContent = '0' + n.suf; });
      requestAnimationFrame(function () {
        bars.forEach(function (b, i) { setTimeout(function () { b.style.width = barTargets[i]; }, 120 + i * 90); });
      });
      var dur = 1000, t0 = performance.now();
      (function tick(now) {
        var p = Math.min(1, ((now || performance.now()) - t0) / dur), e = 1 - Math.pow(1 - p, 3);
        nums.forEach(function (n) { n.el.textContent = Math.round(n.to * e) + n.suf; });
        if (p < 1) requestAnimationFrame(tick);
        else nums.forEach(function (n) { n.el.textContent = n.to + n.suf; });
      })();
    })();

    // ===== Grafik 3: Tren Aktivitas (dulu "Aktivitas 7 Hari Terakhir",
    // sekarang ada toggle 7/30 Hari kayak "Tren Aktivitas" Pimpinan) -- data
    // 2 rentang di-embed sekaligus lewat trenAktivitasData ({'7':[...],
    // '30':[...]}), toggle tombol cuma ganti chart.data lalu chart.update(),
    // TANPA request ulang ke server -- MIRROR persis pola applyRange()
    // makeTrenLaporanChart Pimpinan. =====
    var trenAktivitasData = @json($trenAktivitas);
    var trenAktivitasActiveRange = '7';
    function mkTrenAktivitas(rangeKey) {
      var rows = trenAktivitasData[rangeKey] || [];
      return {
        labels: rows.map(function (r) { return r.label; }),
        jumlah: rows.map(function (r) { return r.jumlah; })
      };
    }
    var elAktivitas = document.getElementById('chartAktivitasMingguan');
    var aktivitasChartInstance = null;
    if (elAktivitas) {
      var aktivitasCtx = elAktivitas.getContext('2d');
      var aktivitasGradient = aktivitasCtx.createLinearGradient(0, 0, 0, elAktivitas.height || 220);
      aktivitasGradient.addColorStop(0, 'rgba(99,102,241,.35)');
      aktivitasGradient.addColorStop(1, 'rgba(99,102,241,0)');
      var curTrenAktivitas = mkTrenAktivitas(trenAktivitasActiveRange);
      aktivitasChartInstance = new Chart(elAktivitas, {
        type: 'line',
        data: {
          labels: curTrenAktivitas.labels,
          datasets: [{
            label: 'Aktivitas',
            data: curTrenAktivitas.jumlah,
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
          plugins: {
            legend: { display: false },
            // Tooltip ikut tema (bukan kotak gelap bawaan Chart.js) -- MIRROR
            // style tooltip "Tren Aktivitas" Pimpinan (makeTrenLaporanChart).
            tooltip: {
              backgroundColor: cSurface, titleColor: cText, bodyColor: cText,
              borderColor: cGrid, borderWidth: 1, cornerRadius: 10, padding: 10, boxPadding: 5,
              usePointStyle: true, titleFont: { weight: '700' }, bodyFont: { weight: '600' }
            }
          },
          scales: {
            // ticks.maxRotation:90 (bukan cuma autoSkip default) -- MIRROR
            // "Tren Laporan" Pimpinan. Tanpa ini, 30 label ("30 Hari") gak
            // muat sejajar horizontal di lebar kartu, Chart.js autoSkip
            // motong sampai cuma ~separuh yang kelihatan (kesannya cuma ada
            // 15 hari data, padahal datanya lengkap 30 -- cuma LABEL-nya
            // yang disembunyiin). Dikasih rotasi vertikal, semua/hampir
            // semua 30 label muat ditampilkan.
            x: { grid: { display: false }, ticks: { autoSkip: true, maxRotation: 90, minRotation: 0, font: { size: 10 } } },
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: cGrid } }
          }
        }
      });
    }
    function applyTrenAktivitasRange(rangeKey) {
      if (!aktivitasChartInstance) return;
      var fresh = mkTrenAktivitas(rangeKey);
      aktivitasChartInstance.data.labels = fresh.labels;
      aktivitasChartInstance.data.datasets[0].data = fresh.jumlah;
      aktivitasChartInstance.update();
    }
    var trenAktivitasToggleEl = document.getElementById('adminTrenRangeToggle');
    if (trenAktivitasToggleEl) {
      trenAktivitasToggleEl.addEventListener('click', function (e) {
        var btn = e.target.closest('.tren-range-btn');
        if (!btn) return;
        // Kartu ini SENDIRI clickable (data-tab-link="log-aktivitas") --
        // tanpa stopPropagation, klik tombol toggle ikut ke-anggep klik
        // kartu & pindah tab ke "Riwayat Aktivitas".
        e.stopPropagation();
        this.querySelectorAll('.tren-range-btn').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        trenAktivitasActiveRange = btn.dataset.range;
        applyTrenAktivitasRange(trenAktivitasActiveRange);
      });
    }
    // Dipanggil dari syncAdminKpis() (poll realtime) tiap data tren_aktivitas
    // dari server beda dari sebelumnya -- trenAktivitasData (variabel di
    // atas) di-assign ulang dapet data segar, rentang yang lagi aktif (7/30
    // Hari) TETAP kepilih, cuma isinya yang di-refresh -- MIRROR
    // siberadRefreshTrenChart Pimpinan persis. Update-in-place (chart.data +
    // chart.update(), BUKAN destroy+recreate) -- chart line ini gak punya
    // growth-guard animasi khusus kayak donut/radar jadi gak perlu direplay.
    window.siberadRefreshTrenAktivitas = function (fresh) {
      trenAktivitasData = fresh;
      applyTrenAktivitasRange(trenAktivitasActiveRange);
    };

  })();
  </script>

  <script>
  (function(){
    // Kartu KPI (Total Pengguna/Satuan/Pelaporan/Surat/Reset Password) +
    // "Permintaan Ganti Password"/"Aktivitas Terbaru" Beranda Admin: animasi
    // count-up angka + gunung "tumbuh" dari dasar + fade-in item baru + poll
    // realtime -- MIRROR PERSIS logika yang sama di Beranda Pimpinan/Satuan
    // (laporan-pimpinan.blade.php/laporan-role.blade.php, fungsi countUp/
    // animatePimpKpis/animateSatuanKpis/animateTerbaruRows/syncPimpinanKpis/
    // syncSatuanKpis). SATU IIFE/SATU poll buat ketiga section (bukan
    // 3 poller terpisah), sama alasannya kayak syncSatuanKpis -- discoped
    // ke halaman ini doang (gak ada donut Distribusi Status versi
    // pimp-card-head di section yang sama) dan nembak endpoint
    // dashboard.admin-kpi.realtime (5 metrik Admin, bukan 3 metrik
    // Pimpinan/Satuan).
    const wrap=document.getElementById('adminKpisWrap');
    if(!wrap)return;
    const reduceMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function countUp(el,from,to,dur){
      if(!el)return;
      if(from===to){el.textContent=to;return;}
      const t0=performance.now();
      let done=false;
      function finish(){if(done)return;done=true;el.textContent=to;}
      (function tick(now){
        if(done)return;
        const p=Math.min(1,((now||performance.now())-t0)/dur),e=1-Math.pow(1-p,3);
        el.textContent=Math.round(from+(to-from)*e);
        if(p<1)requestAnimationFrame(tick);else finish();
      })();
      setTimeout(finish,dur+150);
    }
    function animateAdminKpis(fromList,trendFromList){
      const cards=wrap.querySelectorAll('.pimp-kpi');
      cards.forEach(function(card,i){
        const valEl=card.querySelector('.kpi-value');
        const to=parseInt(((valEl&&valEl.textContent)||'0').replace(/[^0-9-]/g,''),10)||0;
        const trendEl=card.querySelector('.kpi-trend span');
        const trendTo=parseInt(((trendEl&&trendEl.textContent)||'0').replace(/[^0-9-]/g,''),10)||0;
        if(reduceMotion){if(valEl)valEl.textContent=to;if(trendEl)trendEl.textContent=trendTo;return;}
        const deco=card.querySelector('.kpi-deco');
        if(deco){
          deco.classList.remove('is-growing');
          void deco.offsetWidth;
          deco.classList.add('is-growing');
        }
        const from=(fromList&&typeof fromList[i]==='number')?fromList[i]:0;
        const trendFrom=(trendFromList&&typeof trendFromList[i]==='number')?trendFromList[i]:0;
        countUp(valEl,from,to,900);
        countUp(trendEl,trendFrom,trendTo,900);
      });
    }
    // Cache HTML "fresh" TERAKHIR (bukan baca wrap.innerHTML tiap kali) --
    // sama persis alasannya kayak versi Pimpinan/Satuan, lihat
    // [[feedback_dom_diff_flicker_gotcha]].
    let lastFreshHtml=wrap.innerHTML.trim();
    animateAdminKpis();
    // "Permintaan Ganti Password"/"Aktivitas Terbaru" -- swap innerHTML
    // biasa (bukan dianimasiin kayak KPI yang count-up), tapi item-nya
    // dikasih fade-in + flash background pas baru dirender -- MIRROR PERSIS
    // animasi yang sama di Beranda Pimpinan/Satuan (fungsi
    // animateTerbaruRows).
    const resetPasswordList=document.getElementById('adminResetPasswordTerbaruList');
    const aktivitasList=document.getElementById('adminAktivitasTerbaruList');
    let lastResetPasswordHtml=resetPasswordList?resetPasswordList.innerHTML.trim():'';
    let lastAktivitasHtml=aktivitasList?aktivitasList.innerHTML.trim():'';
    // 3 chart kecil Beranda (radar "Pengguna per Kategori Satuan", donut
    // "Distribusi Status Laporan", "Tren Aktivitas") -- fungsi refresh-nya
    // (window.siberadRefreshRadarKategori/AdminStatusDonut/TrenAktivitas)
    // didefinisikan di <script> LAIN
    // (blok init Chart.js di atas, closure beda) makanya ditempel ke
    // `window`, bukan dipanggil langsung -- signature "data lama" di sini
    // SENGAJA diinisialisasi ulang dari data PHP yang sama (bukan baca
    // variabel closure lain yang gak kejangkau dari sini).
    const adminStatusBdWrap=document.getElementById('adminStatusBdWrap');
    const adminDonutTotalEl=document.getElementById('adminDonutTotal');
    let lastRadarKey=JSON.stringify(@json($distribusiPenggunaKategori));
    let lastStatusLaporanKey=JSON.stringify(@json($statusLaporanSistem));
    let lastTrenAktivitasKey=JSON.stringify(@json($trenAktivitas));
    function animateTerbaruRows(container,selector){
      if(!container)return;
      container.querySelectorAll(selector).forEach(function(el,i){
        el.style.animationDelay=(i*60)+'ms';
        el.classList.add('is-fresh');
      });
    }
    animateTerbaruRows(resetPasswordList,'.pimp-activity-item');
    animateTerbaruRows(aktivitasList,'.pimp-activity-item');
    // Jam relatif (".pimp-activity-time[data-ts]") kartu "Permintaan Ganti
    // Password" & "Aktivitas Terbaru" SENGAJA di-tick di sini (bukan ikut
    // dikirim server tiap poll) -- lihat penjelasan lengkap di komentar atas
    // admin-reset-password-terbaru-list.blade.php/admin-aktivitas-terbaru-list.blade.php.
    // Cuma update textContent, GAK nyentuh innerHTML/class, jadi detik jalan
    // terus tanpa memicu animasi fade-in ulang / flicker.
    function formatRelatifID(detik){
      if(detik<5)return'Baru saja';
      const unit=[['tahun',31536000],['bulan',2592000],['minggu',604800],['hari',86400],['jam',3600],['menit',60],['detik',1]];
      for(let i=0;i<unit.length;i++){
        const n=Math.floor(detik/unit[i][1]);
        if(n>=1)return n+' '+unit[i][0]+' yang lalu';
      }
      return'Baru saja';
    }
    function tickRelativeTimes(){
      if(document.hidden)return;
      [resetPasswordList,aktivitasList].forEach(function(container){
        container&&container.querySelectorAll('.pimp-activity-time[data-ts]').forEach(function(el){
          const ts=parseInt(el.getAttribute('data-ts'),10);
          if(!ts)return;
          const text=formatRelatifID(Math.max(0,Math.floor(Date.now()/1000-ts)));
          if(el.textContent!==text)el.textContent=text;
        });
      });
    }
    tickRelativeTimes();
    window.setInterval(tickRelativeTimes,1000);
    const kpiEndpoint='{{ route('dashboard.admin-kpi.realtime') }}';
    let kpiBusy=false;
    async function syncAdminKpis(){
      if(kpiBusy||document.hidden)return;
      kpiBusy=true;
      try{
        const r=await fetch(kpiEndpoint+'?_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}});
        if(!r.ok)return;
        const data=await r.json();
        if(typeof data.kpis_html==='string'){
          const fresh=data.kpis_html.trim();
          if(lastFreshHtml!==fresh){
            const fromList=Array.prototype.map.call(wrap.querySelectorAll('.pimp-kpi .kpi-value'),function(el){return parseInt((el.textContent||'0').replace(/[^0-9-]/g,''),10)||0;});
            const trendFromList=Array.prototype.map.call(wrap.querySelectorAll('.pimp-kpi .kpi-trend span'),function(el){return parseInt((el.textContent||'0').replace(/[^0-9-]/g,''),10)||0;});
            lastFreshHtml=fresh;
            wrap.innerHTML=fresh;
            animateAdminKpis(fromList,trendFromList);
          }
        }
        if(resetPasswordList&&typeof data.reset_password_terbaru_html==='string'){
          const freshRp=data.reset_password_terbaru_html.trim();
          if(lastResetPasswordHtml!==freshRp){
            lastResetPasswordHtml=freshRp;
            resetPasswordList.innerHTML=freshRp;
            animateTerbaruRows(resetPasswordList,'.pimp-activity-item');
            tickRelativeTimes();
          }
        }
        if(aktivitasList&&typeof data.aktivitas_terbaru_html==='string'){
          const freshAk=data.aktivitas_terbaru_html.trim();
          if(lastAktivitasHtml!==freshAk){
            lastAktivitasHtml=freshAk;
            aktivitasList.innerHTML=freshAk;
            animateTerbaruRows(aktivitasList,'.pimp-activity-item');
            tickRelativeTimes();
          }
        }
        if(Array.isArray(data.radar_kategori)){
          const freshRadarKey=JSON.stringify(data.radar_kategori);
          if(lastRadarKey!==freshRadarKey){
            lastRadarKey=freshRadarKey;
            window.siberadRefreshRadarKategori&&window.siberadRefreshRadarKategori(data.radar_kategori);
          }
        }
        if(data.status_laporan&&typeof data.status_laporan==='object'){
          const freshStatusKey=JSON.stringify(data.status_laporan);
          if(lastStatusLaporanKey!==freshStatusKey){
            lastStatusLaporanKey=freshStatusKey;
            window.siberadRefreshAdminStatusDonut&&window.siberadRefreshAdminStatusDonut(data.status_laporan);
          }
        }
        if(adminStatusBdWrap&&typeof data.status_bd_html==='string'){
          adminStatusBdWrap.innerHTML=data.status_bd_html;
        }
        if(adminDonutTotalEl&&typeof data.status_donut_total!=='undefined'){
          adminDonutTotalEl.textContent=data.status_donut_total;
        }
        if(data.tren_aktivitas&&typeof data.tren_aktivitas==='object'){
          const freshTrenAktivitasKey=JSON.stringify(data.tren_aktivitas);
          if(lastTrenAktivitasKey!==freshTrenAktivitasKey){
            lastTrenAktivitasKey=freshTrenAktivitasKey;
            window.siberadRefreshTrenAktivitas&&window.siberadRefreshTrenAktivitas(data.tren_aktivitas);
          }
        }
      }catch(e){}
      finally{kpiBusy=false;}
    }
    // Interval poll KPI/Terbaru sengaja 2 detik (bukan 1 detik lagi) -- server
    // dev lokal cuma 1 worker (lihat komentar di DashboardController::
    // adminKpiRealtime), dan poller ini jalan BARENGAN sama syncPimpinanKpis/
    // syncSatuanKpis/danpus-laporan-request-realtime kalau beberapa dashboard
    // dibuka sekaligus -- semua nembak tiap 1 detik bikin request-nya numpuk
    // & keseluruhan situs kerasa lag. tickRelativeTimes TETAP 1 detik (client-
    // only, gak ada network call, jadi gak nyumbang beban server).
    // Dulu cuma setInterval tanpa poll langsung -- begitu admin buka tab
    // Dashboard, KPI/chart/list di sini nunggu 2 detik dulu sebelum sempat
    // nyegerin diri, walau datanya sendiri udah kebentuk dari render
    // server. lastFreshHtml/lastResetPasswordHtml/dst di atas udah di-seed
    // dari HTML yang sama, jadi panggilan langsung ini aman (gak bikin
    // flicker kalau memang belum ada perubahan) -- cuma mempercepat begitu
    // ADA perubahan yang kejadian pas admin lagi transisi ke tab ini
    // (audit polling 2026-09-14).
    syncAdminKpis();
    window.setInterval(syncAdminKpis,2000);
    document.addEventListener('visibilitychange',function(){if(!document.hidden){syncAdminKpis();tickRelativeTimes();}});
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
      td.innerHTML = '<div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Tidak ada data yang cocok</div><div class="empty-state-sub">Coba ubah kata kunci pencarian atau filter-nya.</div></div>';
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

    // Urutkan baris tabel (dipakai tombol "Terbaru/Terlama" di Permintaan
    // Ganti Password) berdasarkan data-created (unix timestamp) di tiap
    // baris. Diekspos ke window supaya baris baru yang disisipkan realtime
    // (lihat partials/admin-permintaan-reset-password-realtime.blade.php)
    // ikut mengikuti urutan sort yang lagi aktif, bukan selalu nempel di atas.
    function terapkanTabelSort(tableId, mode) {
      var table = document.getElementById(tableId);
      if (!table) return;
      var tbody = table.querySelector('tbody');
      if (!tbody) return;
      var rows = Array.prototype.filter.call(tbody.children, function (r) {
        return r.hasAttribute('data-created');
      });
      rows.sort(function (a, b) {
        var ta = parseInt(a.getAttribute('data-created'), 10) || 0;
        var tb = parseInt(b.getAttribute('data-created'), 10) || 0;
        return mode === 'terlama' ? ta - tb : tb - ta;
      });
      rows.forEach(function (r) { tbody.appendChild(r); });
      terapkanTabelFilter(tableId);
    }
    window.terapkanTabelSort = terapkanTabelSort;

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
    document.querySelectorAll('[data-table-sort]').forEach(function (select) {
      select.addEventListener('change', function () {
        terapkanTabelSort(select.getAttribute('data-table-sort'), select.value);
      });
    });
    // Isi teks jumlah data begitu halaman dimuat, bukan cuma pas user mulai cari/filter.
    document.querySelectorAll('[data-table-count]').forEach(function (el) {
      terapkanTabelFilter(el.getAttribute('data-table-count'));
    });
  })();
  </script>

@include('siberad.dashboards.partials.admin-log-aktivitas-realtime')
@include('siberad.dashboards.partials.admin-permintaan-reset-password-realtime')
@include('siberad.dashboards.partials.admin-sesi-aktif-realtime')
@include('siberad.dashboards.partials.dash-script')
</body>
</html>