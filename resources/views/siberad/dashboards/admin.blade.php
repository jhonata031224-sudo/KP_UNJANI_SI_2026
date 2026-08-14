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
  .chart-mini{background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:12px;padding:16px;transition:border-color .15s ease,box-shadow .15s ease;}
  .chart-mini:hover{border-color:var(--border-strong);box-shadow:0 6px 16px rgba(0,0,0,.12);}
  .chart-mini-head{margin-bottom:10px;}
  .chart-mini-head h4{font-family:var(--display);font-size:13px;font-weight:700;letter-spacing:.01em;line-height:1.3;}
  .chart-mini-head p{font-size:11px;color:var(--text-muted);margin-top:2px;}
  .chart-mini .chart-wrap{position:relative;height:210px;}
  @media(max-width:980px){.chart-box-grid{grid-template-columns:1fr;}.chart-mini .chart-wrap{height:230px;}}

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
    background:var(--panel);border:1px solid var(--border);color:var(--text);font-family:var(--mono);
    font-size:11.5px;letter-spacing:.02em;border-radius:9px;padding:0 10px;cursor:pointer;flex-shrink:0;
    min-width:170px;height:38px;
  }
  .table-filter:focus{outline:none;border-color:var(--gold);}
  .table-filter-count{font-size:10px;color:var(--text-dim);white-space:nowrap;margin-left:auto;}
  .table-empty-row td{text-align:center;color:var(--text-dim);font-size:12.5px;padding:26px 12px !important;}
  @media(max-width:640px){.table-toolbar{flex-direction:column;align-items:stretch;}.table-search-wrap{max-width:none;}.table-filter{width:100%;}.table-filter-count{width:100%;margin-left:0;}}

  /* ===== badge status Rekap Laporan (warna tetap hijau/merah/oren asli,
     tidak ikut --green yang di-repurpose jadi gold di tempat lain) ===== */
  .badge-status{display:inline-flex;align-items:center;justify-content:center;min-width:34px;font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;padding:7px 14px;border-radius:8px;text-transform:uppercase;border:1px solid transparent;box-sizing:border-box;line-height:1.2;}
  .badge-status.ok{background:rgba(34,197,94,.14);color:#22c55e;border-color:rgba(34,197,94,.32);}
  .badge-status.bad{background:rgba(239,68,68,.14);color:#ef4444;border-color:rgba(239,68,68,.32);}
  .badge-status.wait{background:rgba(245,158,11,.14);color:#f59e0b;border-color:rgba(245,158,11,.32);}

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
    flex-shrink:0;width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;
    border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;
    transition:border-color .15s ease,color .15s ease;
  }
  .user-modal-close:hover{border-color:var(--gold);color:var(--text);}
  .user-modal-actions{grid-column:1/-1;display:flex;flex-direction:row;align-items:center;justify-content:flex-end;gap:10px;margin-top:4px;}
  @media(max-width:640px){.user-modal-actions{flex-direction:column-reverse;align-items:stretch;}}
  @media(max-width:640px){.user-modal-card{padding:20px;}}
  /* Form di dalam modal dibuat 1 kolom terus (bukan ikut breakpoint global .form-grid),
     supaya opsi teks panjang di dropdown Satuan tidak memepetkan/merusak layout 2 kolom
     di lebar modal yang terbatas (560px). */
  .user-modal-card .form-grid{grid-template-columns:1fr;min-width:0;}
  .user-modal-card .form-field{min-width:0;}
  .user-modal-card .form-field select,.user-modal-card .form-field input{min-width:0;width:100%;box-sizing:border-box;}

  /* ===== tombol aksi berikon di tabel Daftar Pengguna ===== */
  .icon-action-btn{
    display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;
    width:32px;height:32px;padding:0;border-radius:8px;
    border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;
    transition:border-color .15s ease,color .15s ease,background .15s ease;
  }
  .icon-action-btn svg{width:15px;height:15px;}
  .icon-action-btn:hover{border-color:var(--gold);color:var(--gold-bright);background:var(--panel-alt);}
  .icon-action-btn.danger{color:var(--red);border-color:rgba(200,59,59,.32);}
  .icon-action-btn.danger:hover{background:rgba(200,59,59,.1);border-color:var(--red);color:var(--red);}

  .side-nav-group{margin:0}.side-nav-group-title{width:100%;display:flex;align-items:center;gap:10px;padding:10px 12px;margin:2px 0;border:1px solid transparent;border-radius:9px;background:transparent;color:var(--text-muted);font-family:var(--body);font-size:13.5px;font-weight:500;cursor:pointer;text-align:left;box-sizing:border-box;transition:background .15s ease,color .15s ease}.side-nav-group-title:hover{background:var(--hover-tint);color:var(--text)}.side-nav-group.open .side-nav-group-title{color:var(--text)}.side-nav-group-title .side-text{flex:1}.side-nav-group-title .chevron{margin-left:auto;width:15px;height:15px;flex-shrink:0;opacity:.6;transition:transform .25s cubic-bezier(.4,0,.2,1),opacity .2s ease}.side-nav-group.open .chevron{transform:rotate(180deg);opacity:1}.side-subnav{display:grid;grid-template-rows:0fr;opacity:0;transition:grid-template-rows .3s cubic-bezier(.4,0,.2,1),opacity .25s ease;overflow:hidden}.side-subnav>div{min-height:0;padding:3px 0;margin-left:18px;border-left:1px solid var(--border-soft)}.side-nav-group.open .side-subnav{grid-template-rows:1fr;opacity:1}.side-sub-link{position:relative;display:flex;align-items:center;gap:10px;padding:9px 12px 9px 17px;border-radius:0 9px 9px 0;color:var(--text-muted);font-family:var(--body);font-size:13px;font-weight:500;text-decoration:none;margin:1px 0;box-sizing:border-box;transition:background .15s ease,color .15s ease}.side-sub-link:hover{background:var(--hover-tint);color:var(--text)}.side-sub-link .sub-dot{width:5px;height:5px;border-radius:50%;background:currentColor;opacity:.5;flex:0 0 auto;transition:opacity .15s ease,background .15s ease,box-shadow .15s ease}.side-sub-link.active{background:var(--gold-dim);color:var(--gold-bright);font-weight:600}.side-sub-link.active:before{content:"";position:absolute;left:-1px;top:8px;bottom:8px;width:2px;border-radius:2px;background:var(--gold-bright)}.side-sub-link.active .sub-dot{background:var(--gold-bright);opacity:1;box-shadow:0 0 0 3px rgba(201,122,0,.15)}.side-subnav-label{display:none}
  .sidebar.collapsed .side-subnav{display:none}.sidebar.collapsed .side-nav-group.open .side-subnav{display:block;position:fixed;min-width:216px;background:var(--panel);border:1px solid var(--border-soft);border-radius:12px;box-shadow:0 14px 34px rgba(0,0,0,.22);padding:8px;z-index:100020}.sidebar.collapsed .side-subnav>div{margin-left:0;border-left:none;padding:0}.sidebar.collapsed .side-subnav-label{display:block;font-family:var(--mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);padding:4px 10px 8px}.sidebar.collapsed .side-sub-link{padding:9px 10px;border-radius:8px}.sidebar.collapsed .side-nav-group.has-active-child .side-nav-group-title{color:var(--gold-bright);background:var(--gold-dim)}
</style>
</head>
<body>
<div class="profile-modal-overlay" id="profileModalOverlay">
  <div class="profile-modal-card" id="profileModalCard" role="dialog" aria-modal="true" aria-label="Detail profil">
    <button type="button" class="profile-modal-close" id="profileModalCloseBtn" aria-label="Tutup">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>
    </button>

    {{-- ===== VIEW PENGATURAN AKUN (Foto Profil + Ganti Password, ketuker via tab) ===== --}}
    <div class="profile-dropdown-view" id="profileSettingsView" style="display:none;">
      <div class="profile-modal-title">Pengaturan Akun</div>

      <div class="profile-subtabs" role="tablist">
        <button type="button" class="profile-subtab-btn active" data-subtab-target="profilePhotoView" role="tab" aria-selected="true">
          <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8.5A1.5 1.5 0 0 1 5.5 7h2l1-2h7l1 2h2A1.5 1.5 0 0 1 20 8.5v9A1.5 1.5 0 0 1 18.5 19h-13A1.5 1.5 0 0 1 4 17.5Z"></path><circle cx="12" cy="13" r="3.4"></circle></svg>
          Foto Profil
        </button>
        <button type="button" class="profile-subtab-btn" data-subtab-target="profilePasswordView" role="tab" aria-selected="false">
          <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2.2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
          Ganti Password
        </button>
      </div>

      <div class="profile-subtab-panel active" id="profilePhotoView" role="tabpanel">
        <div class="profile-dropdown-head-lg">
          <div class="profile-dropdown-avatar-lg">
            <span class="profile-initial" id="profileInitialLarge">{{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}</span>
            <img class="profile-photo" id="profilePhotoLarge" alt="Foto profil {{ $user->name }}">
          </div>
          <div class="profile-dropdown-name">{{ $user->name }}</div>
          <div class="profile-dropdown-role">{{ $user->jabatan ?? 'Pengguna' }}</div>
        </div>

        <div class="profile-photo-actions">
          <button type="button" class="profile-btn profile-btn-primary" id="gantiFotoBtn">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8.5A1.5 1.5 0 0 1 5.5 7h2l1-2h7l1 2h2A1.5 1.5 0 0 1 20 8.5v9A1.5 1.5 0 0 1 18.5 19h-13A1.5 1.5 0 0 1 4 17.5Z"></path><circle cx="12" cy="13" r="3.4"></circle></svg>
            <span id="gantiFotoLabel">Ganti Foto</span>
          </button>
          <button type="button" class="profile-btn profile-btn-outline" id="hapusFotoBtn" style="display:none;">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>
            Hapus
          </button>
        </div>
        <p class="profile-photo-hint">Format JPG, PNG, atau WEBP — ukuran maksimal 5 MB.</p>
        <input type="file" id="fotoProfilInput" accept="image/png,image/jpeg,image/webp" hidden>
      </div>

      <div class="profile-subtab-panel" id="profilePasswordView" role="tabpanel">
        <div class="profile-form-notice">
          Perubahan kata sandi tidak langsung berlaku. Permintaan akan dikirim ke <b>Admin</b> untuk diverifikasi terlebih dahulu.
        </div>

        <form class="profile-form" id="formGantiPassword" novalidate>
          <div class="profile-form-field">
            <label for="passBaru">Kata Sandi Baru</label>
            <input type="password" id="passBaru" minlength="8" required placeholder="Minimal 8 karakter">
          </div>
          <div class="profile-form-field">
            <label for="passKonfirmasi">Konfirmasi Kata Sandi Baru</label>
            <input type="password" id="passKonfirmasi" minlength="8" required placeholder="Ulangi kata sandi baru">
          </div>
          <div class="profile-form-field">
            <label for="passCatatan">Catatan untuk Admin (opsional)</label>
            <textarea id="passCatatan" rows="2" placeholder="Contoh: lupa kata sandi lama"></textarea>
          </div>
          <span class="profile-form-error" id="passError"></span>
          <button type="submit" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;"><rect x="5" y="11" width="14" height="9" rx="2.2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
            Kirim Permintaan ke Admin
          </button>
        </form>
      </div>
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
            <div class="help-topic-desc">Pantau rekap laporan dari seluruh satuan, dan lihat siapa saja yang sedang aktif login ke sistem lewat Sesi Aktif.</div>
          </div>
        </div>
        <div class="help-topic">
          <div class="help-topic-icon">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
          </div>
          <div class="help-topic-body">
            <div class="help-topic-title">Kelola Sistem</div>
            <div class="help-topic-desc">Atur data satuan, role &amp; hak akses, log aktivitas, backup database, hingga pengaturan umum aplikasi.</div>
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
    <form class="form-grid" method="POST" action="{{ route('admin.users.store') }}">
      @csrf
      <div class="form-field">
        <label for="uNama">Nama Lengkap</label>
        <input id="uNama" name="name" type="text" required>
      </div>
      <div class="form-field">
        <label for="uUsername">Username / NRP</label>
        <input id="uUsername" name="username" type="text" required>
      </div>
      <div class="form-field">
        <label for="uEmail">Email (opsional)</label>
        <input id="uEmail" name="email" type="email">
      </div>
      <div class="form-field">
        <label for="uSatuan">Satuan</label>
        <select id="uSatuan" name="satuan_id" required>
          <option value="">— Pilih satuan —</option>
          @foreach($semuaSatuan as $s)
          <option value="{{ $s->id }}">{{ $s->nama }} ({{ $s->kode }})</option>
          @endforeach
        </select>
      </div>
      <div class="form-field">
        <label for="uPassword">Password Awal</label>
        <input id="uPassword" name="password" type="text" minlength="8" required placeholder="Minimal 8 karakter">
      </div>
      <div class="user-modal-actions">
        <button class="btn" type="button" id="tambahPenggunaCancel">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan Pengguna</button>
      </div>
    </form>
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
    <form class="form-grid" method="POST" action="" id="ubahPenggunaForm">
      @csrf
      @method('PATCH')
      <div class="form-field">
        <label for="upNama">Nama Lengkap</label>
        <input id="upNama" name="name" type="text" required>
      </div>
      <div class="form-field">
        <label for="upUsername">Username / NRP</label>
        <input id="upUsername" name="username" type="text" required>
      </div>
      <div class="form-field">
        <label for="upEmail">Email (opsional)</label>
        <input id="upEmail" name="email" type="email">
      </div>
      <div class="form-field">
        <label for="upSatuan">Satuan</label>
        <select id="upSatuan" name="satuan_id" required>
          @foreach($semuaSatuan as $s)
          <option value="{{ $s->id }}">{{ $s->nama }} ({{ $s->kode }})</option>
          @endforeach
        </select>
      </div>
      <div class="form-field">
        <label for="upPassword">Password Baru (opsional)</label>
        <input id="upPassword" name="password" type="text" minlength="8" placeholder="Kosongkan jika tidak diubah">
      </div>
      <div class="user-modal-actions">
        <button class="btn" type="button" id="ubahPenggunaCancel">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
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
    <form class="form-grid" method="POST" action="{{ route('admin.satuan.store') }}">
      @csrf
      <div class="form-field"><label for="sKode">Kode</label><input id="sKode" name="kode" type="text" placeholder="Contoh: BINLOG" required style="text-transform:uppercase;"></div>
      <div class="form-field"><label for="sNama">Nama Satuan</label><input id="sNama" name="nama" type="text" required></div>
      <div class="form-field">
        <label for="sKategori">Kategori</label>
        <select id="sKategori" name="kategori" required>
          <option value="{{ \App\Models\Satuan::KATEGORI_SATLAK }}">Satlak</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_DIREKTORAT }}">Direktorat</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_PIMPINAN }}">Pimpinan</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_ADMIN }}">Admin</option>
        </select>
      </div>
      <div class="form-field"><label for="sUrutan">Urutan</label><input id="sUrutan" name="urutan" type="number" min="0" placeholder="0"></div>
      <div class="form-field full"><label for="sDeskripsi">Deskripsi (opsional)</label><textarea id="sDeskripsi" name="deskripsi" rows="2"></textarea></div>
      <div class="user-modal-actions">
        <button class="btn" type="button" id="tambahSatuanCancel">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan Satuan</button>
      </div>
    </form>
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
    <form class="form-grid" method="POST" action="" id="ubahSatuanForm">
      @csrf
      @method('PATCH')
      <div class="form-field"><label for="usKode">Kode</label><input id="usKode" name="kode" type="text" required style="text-transform:uppercase;"></div>
      <div class="form-field"><label for="usNama">Nama Satuan</label><input id="usNama" name="nama" type="text" required></div>
      <div class="form-field">
        <label for="usKategori">Kategori</label>
        <select id="usKategori" name="kategori" required>
          <option value="{{ \App\Models\Satuan::KATEGORI_SATLAK }}">Satlak</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_DIREKTORAT }}">Direktorat</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_PIMPINAN }}">Pimpinan</option>
          <option value="{{ \App\Models\Satuan::KATEGORI_ADMIN }}">Admin</option>
        </select>
      </div>
      <div class="form-field"><label for="usUrutan">Urutan</label><input id="usUrutan" name="urutan" type="number" min="0"></div>
      <div class="form-field full"><label for="usDeskripsi">Deskripsi (opsional)</label><textarea id="usDeskripsi" name="deskripsi" rows="2"></textarea></div>
      <div class="user-modal-actions">
        <button class="btn" type="button" id="ubahSatuanCancel">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<div class="shell">

  <aside class="sidebar" id="sidebar">
    <div class="side-brand">
      <img src="{{ asset('images/logo-pussiberad.jpg') }}" alt="Lambang Pussiberad">
      <div class="logo">SIBER<span>AD</span></div>
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
          <a href="#" class="side-sub-link" data-tab-link="reset-password" title="Permintaan Reset Password"><span class="sub-dot"></span>Permintaan Reset Password</a>
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
          <a href="#" class="side-sub-link" data-tab-link="rekap-laporan" title="Rekap Laporan"><span class="sub-dot"></span>Rekap Laporan</a>
          <a href="#" class="side-sub-link" data-tab-link="sesi-aktif" title="Sesi Aktif"><span class="sub-dot"></span>Sesi Aktif</a>
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
          <a href="#" class="side-sub-link" data-tab-link="satlak" title="Manajemen Satuan"><span class="sub-dot"></span>Manajemen Satuan</a>
          <a href="#" class="side-sub-link" data-tab-link="role-akses" title="Role &amp; Hak Akses"><span class="sub-dot"></span>Role &amp; Hak Akses</a>
          <a href="#" class="side-sub-link" data-tab-link="log-aktivitas" title="Log Aktivitas"><span class="sub-dot"></span>Log Aktivitas</a>
          <a href="#" class="side-sub-link" data-tab-link="backup" title="Backup Database"><span class="sub-dot"></span>Backup Database</a>
          <a href="#" class="side-sub-link" data-tab-link="laporan-admin" title="Laporan &amp; Export"><span class="sub-dot"></span>Laporan &amp; Export</a>
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
      if (!sidebar || !sidebar.classList.contains('collapsed') || !g.classList.contains('open')) {
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
            <span class="siberad-notif-dot" style="position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:var(--red);box-shadow:0 0 0 2px var(--panel,#0c2417);{{ auth()->user()?->unreadNotifications->count() ? '' : 'display:none;' }}"></span>
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
            <span class="profile-initial" id="profileInitial">{{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}</span>
            <img class="profile-photo" id="profilePhotoBtn" alt="Foto profil {{ $user->name }}">
          </button>

          <div class="profile-dropdown" id="profileDropdown" role="menu" aria-label="Menu profil">

            <div class="profile-dropdown-head">
              <div class="profile-dropdown-avatar">
                <span class="profile-initial" id="profileInitialDropdown">{{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}</span>
                <img class="profile-photo" id="profilePhotoDropdown" alt="Foto profil {{ $user->name }}">
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

            <div class="chart-mini">
              <div class="chart-mini-head">
                <div class="chart-mini-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                <div><h4>Pengguna per Kategori Satuan</h4><p>Sebaran akun berdasarkan kategori.</p></div>
              </div>
              <div class="chart-wrap"><canvas id="chartKategoriSatuan"></canvas></div>
            </div>

            <div class="chart-mini">
              <div class="chart-mini-head">
                <div class="chart-mini-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
                <div><h4>Distribusi Status Laporan</h4><p>Proporsi status seluruh laporan di sistem.</p></div>
              </div>
              <div class="chart-wrap"><canvas id="chartStatusLaporan"></canvas></div>
            </div>

            <div class="chart-mini">
              <div class="chart-mini-head">
                <div class="chart-mini-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
                <div><h4>Aktivitas 7 Hari Terakhir</h4><p>Jumlah aksi tercatat per hari.</p></div>
              </div>
              <div class="chart-wrap"><canvas id="chartAktivitasMingguan"></canvas></div>
            </div>

          </div>
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

        <style>
          .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:26px;}
          @media(max-width:980px){.kpi-grid{grid-template-columns:repeat(2,1fr);}}
          .kpi-card .lbl{font-weight:800;}
          .kpi-card .val{font-family:var(--mono);color:var(--text);}
          .kpi-card.wait .val{color:#f59e0b;}
          .kpi-card.ok .val{color:#22c55e;}
          .kpi-card.bad .val{color:#ef4444;}

          .chart-mini-head{display:flex;align-items:flex-start;gap:11px;}
          .chart-mini-icon{width:28px;height:28px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--gold-dim);color:var(--gold-bright);}
          .chart-mini-icon svg{width:15px;height:15px;}
          .chart-mini-icon.amber{background:var(--amber-dim);color:var(--amber);}
          .chart-mini-icon.green{background:var(--green-dim);color:var(--green-bright);}
          .chart-mini-icon.blue{background:rgba(99,102,241,.14);color:#6366f1;}

          .activity-panel{margin-top:22px;}
          .activity-feed{list-style:none;padding:2px 0 4px;margin:0;}
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
        <div class="section-head">
          <h2>Kelola Pengguna</h2>
          <p>Seluruh akun yang terdaftar, satu akun per satuan.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="notice" style="border-color:var(--red);">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
          <div class="notice" style="border-color:var(--red);">{{ $errors->first() }}</div>
        @endif

        <div class="panel">
          <div class="panel-head">
            <div><h3>Daftar Pengguna</h3><p>Klik "Ubah" untuk mengedit satuan/jabatan/password.</p></div>
            <button class="btn btn-primary btn-sm" type="button" id="tambahPenggunaOpen">+ Tambah Pengguna</button>
          </div>
          <div class="table-toolbar">
            <div class="table-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
              <input type="text" class="table-search" data-table-search="tblPengguna" placeholder="Cari nama, username, atau email...">
            </div>
            <select class="table-filter" data-table-filter="tblPengguna">
              <option value="">Semua Satuan</option>
              @foreach($semuaSatuan as $s)
              <option value="{{ $s->nama }}">{{ $s->nama }}</option>
              @endforeach
            </select>
            <span class="table-filter-count" data-table-count="tblPengguna"></span>
          </div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl" id="tblPengguna">
              <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Satuan</th><th>Aksi</th></tr></thead>
              <tbody>
                @foreach($semuaPengguna as $p)
                <tr data-filter-value="{{ $p->satuan->nama ?? '' }}">
                  <td>{{ $p->name }}</td>
                  <td><span class="badge">{{ $p->username }}</span></td>
                  <td style="color:var(--text-muted);">{{ $p->email ?: '-' }}</td>
                  <td>{{ $p->satuan->nama ?? '-' }}</td>
                  <td>
                    <div class="btn-row">
                      <form method="POST" action="{{ route('admin.users.reset-password', $p) }}" onsubmit="return confirm('Reset password akun {{ $p->name }}?');" style="display:inline;">
                        @csrf
                        <button class="icon-action-btn" type="submit" title="Reset Password" aria-label="Reset Password {{ $p->name }}">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="15" r="3.2"></circle><path d="M10.2 12.8 19 4"></path><path d="M15.5 8.3 18 10.8"></path><path d="M18.3 5.5 20.8 8"></path></svg>
                        </button>
                      </form>
                      <button class="icon-action-btn" type="button" onclick="bukaUbahPengguna(this)"
                        data-action="{{ route('admin.users.update', $p) }}"
                        data-name="{{ $p->name }}"
                        data-username="{{ $p->username }}"
                        data-email="{{ $p->email }}"
                        data-satuan-id="{{ $p->satuan_id }}"
                        title="Ubah" aria-label="Ubah {{ $p->name }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                      </button>
                      @if($p->id !== $user->id)
                      <form method="POST" action="{{ route('admin.users.destroy', $p) }}" onsubmit="return confirm('Hapus akun {{ $p->name }}?');" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="icon-action-btn danger" type="submit" title="Hapus" aria-label="Hapus {{ $p->name }}">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>
                        </button>
                      </form>
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
          modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
          document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
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
        (function () {
          var modal = document.getElementById('ubahPenggunaModal');
          var closeBtn = document.getElementById('ubahPenggunaClose');
          var cancelBtn = document.getElementById('ubahPenggunaCancel');
          if (!modal) return;
          function close() { modal.classList.remove('open'); }
          if (closeBtn) closeBtn.addEventListener('click', close);
          if (cancelBtn) cancelBtn.addEventListener('click', close);
          modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
          document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
        })();
      </script>

      {{-- ===== MANAJEMEN SATUAN ===== --}}
      <section class="tab-panel" data-tab-panel="satlak">
        <div class="section-head">
          <h2>Manajemen Satuan</h2>
          <p>Kelola daftar satuan/Satlak yang terdaftar di SIBERAD.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="notice" style="border-color:var(--red);">{{ session('error') }}</div>
        @endif

        <div class="panel">
          <div class="panel-head">
            <div><h3>Daftar Satuan</h3><p>Satuan yang masih punya pengguna tidak bisa dihapus.</p></div>
            <button class="btn btn-primary btn-sm" type="button" id="tambahSatuanOpen">+ Tambah Satuan</button>
          </div>
          <div class="table-toolbar">
            <div class="table-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
              <input type="text" class="table-search" data-table-search="tblSatuan" placeholder="Cari kode atau nama satuan...">
            </div>
            <select class="table-filter" data-table-filter="tblSatuan">
              <option value="">Semua Kategori</option>
              <option value="{{ \App\Models\Satuan::KATEGORI_SATLAK }}">Satlak</option>
              <option value="{{ \App\Models\Satuan::KATEGORI_DIREKTORAT }}">Direktorat</option>
              <option value="{{ \App\Models\Satuan::KATEGORI_PIMPINAN }}">Pimpinan</option>
              <option value="{{ \App\Models\Satuan::KATEGORI_ADMIN }}">Admin</option>
            </select>
            <span class="table-filter-count" data-table-count="tblSatuan"></span>
          </div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl" id="tblSatuan">
              <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Jumlah Pengguna</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($semuaSatuan as $s)
                <tr data-filter-value="{{ $s->kategori }}">
                  <td><span class="badge">{{ $s->kode }}</span></td>
                  <td>{{ $s->nama }}</td>
                  <td style="color:var(--text-muted);text-transform:capitalize;">{{ $s->kategori }}</td>
                  <td>{{ $s->users_count }}</td>
                  <td>
                    <div class="btn-row">
                      <button class="btn btn-sm" type="button" onclick="bukaUbahSatuan(this)"
                        data-action="{{ route('admin.satuan.update', $s) }}"
                        data-kode="{{ $s->kode }}"
                        data-nama="{{ $s->nama }}"
                        data-kategori="{{ $s->kategori }}"
                        data-urutan="{{ $s->urutan }}"
                        data-deskripsi="{{ $s->deskripsi }}">Ubah</button>
                      <form method="POST" action="{{ route('admin.satuan.destroy', $s) }}" onsubmit="return confirm('Hapus satuan {{ $s->nama }}?');" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-ghost-red" type="submit">Hapus</button>
                      </form>
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
          modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
          document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
        })();

        window.bukaUbahSatuan = function (btn) {
          document.getElementById('ubahSatuanForm').action = btn.dataset.action;
          document.getElementById('usKode').value = btn.dataset.kode || '';
          document.getElementById('usNama').value = btn.dataset.nama || '';
          document.getElementById('usKategori').value = btn.dataset.kategori || '';
          document.getElementById('usUrutan').value = btn.dataset.urutan || '';
          document.getElementById('usDeskripsi').value = btn.dataset.deskripsi || '';
          document.getElementById('ubahSatuanModal').classList.add('open');
        };
        (function () {
          var modal = document.getElementById('ubahSatuanModal');
          var closeBtn = document.getElementById('ubahSatuanClose');
          var cancelBtn = document.getElementById('ubahSatuanCancel');
          if (!modal) return;
          function close() { modal.classList.remove('open'); }
          if (closeBtn) closeBtn.addEventListener('click', close);
          if (cancelBtn) cancelBtn.addEventListener('click', close);
          modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
          document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
        })();
      </script>

      {{-- ===== ROLE & HAK AKSES ===== --}}
      <section class="tab-panel" data-tab-panel="role-akses">
        <div class="section-head">
          <h2>Role &amp; Hak Akses</h2>
          <p>Setiap satuan berperan sebagai role login. Atur modul apa saja yang boleh diakses tiap satuan.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif

        @foreach($semuaSatuan as $s)
        <div class="panel">
          <div class="panel-head"><div><h3>{{ $s->nama }} <span class="badge">{{ $s->kode }}</span></h3><p>{{ $s->deskripsi ?: 'Tidak ada deskripsi.' }}</p></div></div>
          <form method="POST" action="{{ route('admin.satuan.permissions', $s) }}" style="padding:18px 22px;">
            @csrf @method('PATCH')
            <div style="display:flex;flex-wrap:wrap;gap:14px;margin-bottom:14px;">
              @foreach($modulHakAkses as $key => $label)
              <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--text-muted);">
                <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $s->permissions ?? []))>
                {{ $label }}
              </label>
              @endforeach
            </div>
            <button class="btn btn-primary btn-sm" type="submit">Simpan Hak Akses {{ $s->kode }}</button>
          </form>
        </div>
        @endforeach
      </section>

      {{-- ===== LOG AKTIVITAS ===== --}}
      <section class="tab-panel" data-tab-panel="log-aktivitas">
        <div class="section-head">
          <h2>Monitoring Aktivitas Sistem</h2>
          <p>Rekam jejak login, logout, dan seluruh aksi kelola sistem oleh Admin.</p>
        </div>
        <div class="panel">
          <div class="table-toolbar">
            <div class="table-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
              <input type="text" class="table-search" data-table-search="tblLogAktivitas" placeholder="Cari pengguna atau aksi...">
            </div>
            <span class="table-filter-count" data-table-count="tblLogAktivitas"></span>
          </div>
          <div class="tbl-wrap" data-row-limit="10">
            <table class="dtbl" id="tblLogAktivitas">
              <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Deskripsi</th><th>IP</th></tr></thead>
              <tbody>
                @forelse($logAktivitas as $l)
                <tr>
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
        </div>
      </section>

      {{-- ===== BACKUP DATABASE ===== --}}
      <section class="tab-panel" data-tab-panel="backup">
        <div class="section-head">
          <h2>Backup Database</h2>
          <p>Buat salinan database sewaktu-waktu dan unduh untuk disimpan di luar server.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="notice" style="border-color:var(--red);">{{ session('error') }}</div>
        @endif

        <div class="panel">
          <div class="panel-head"><div><h3>Buat Backup Baru</h3><p>Untuk koneksi SQLite: salin file database. Untuk MySQL: jalankan mysqldump.</p></div></div>
          <form method="POST" action="{{ route('admin.backup.store') }}" style="padding:18px 22px;">
            @csrf
            <button class="btn btn-primary" type="submit">+ Buat Backup Sekarang</button>
          </form>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Riwayat Backup</h3></div></div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl">
              <thead><tr><th>Nama File</th><th>Ukuran</th><th>Tanggal</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($daftarBackup as $b)
                <tr>
                  <td>{{ $b['nama'] }}</td>
                  <td>{{ $b['ukuran'] }}</td>
                  <td>{{ $b['tanggal'] }}</td>
                  <td><a class="btn btn-sm" href="{{ route('admin.backup.download', $b['nama']) }}">Unduh</a></td>
                </tr>
                @empty
                <tr><td colspan="4"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada backup dibuat</div></div></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== LAPORAN & EXPORT ===== --}}
      <section class="tab-panel" data-tab-panel="laporan-admin">
        <div class="section-head">
          <h2>Laporan Pengguna &amp; Aktivitas</h2>
          <p>Rekap data pengguna dan aktivitas sistem, siap diekspor.</p>
        </div>
        <div class="panel">
          <div class="panel-head"><div><h3>Export</h3><p>Unduh dalam format CSV (bisa dibuka Excel) atau cetak sebagai PDF.</p></div></div>
          <div class="btn-row" style="padding:18px 22px;flex-wrap:wrap;">
            <a class="btn btn-primary btn-sm" href="{{ route('admin.laporan.export-pengguna') }}">Export Pengguna (Excel/CSV)</a>
            <a class="btn btn-primary btn-sm" href="{{ route('admin.laporan.export-aktivitas') }}">Export Aktivitas (Excel/CSV)</a>
            <a class="btn btn-sm" href="{{ route('admin.laporan.cetak', 'pengguna') }}" target="_blank">Cetak Pengguna (PDF)</a>
            <a class="btn btn-sm" href="{{ route('admin.laporan.cetak', 'aktivitas') }}" target="_blank">Cetak Aktivitas (PDF)</a>
            <a class="btn btn-sm" href="{{ route('admin.laporan.index') }}" target="_blank">Buka Halaman Laporan Lengkap</a>
          </div>
        </div>
      </section>

      {{-- ===== PENGATURAN UMUM ===== --}}
      <section class="tab-panel" data-tab-panel="pengaturan-umum">
        <div class="section-head">
          <h2>Pengaturan Umum</h2>
          <p>Konfigurasi umum aplikasi SIBERAD.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif

        {{-- ===== KONTEN HALAMAN LANDING ===== --}}
        <div class="lp-layout">

          {{-- ---------- PANEL EDITOR ---------- --}}
          <div class="panel lp-panel">
            <div class="panel-head">
              <div>
                <h3>Konten Halaman Landing</h3>
                <p>Pilih bagian yang mau diedit, lalu lihat hasilnya di panel pratinjau.</p>
              </div>
            </div>

            <form id="landingForm" method="POST" action="{{ route('admin.pengaturan.landing.update') }}" enctype="multipart/form-data">
              @csrf @method('PATCH')

              <div class="lp-tabs" role="tablist">
                <button type="button" class="lp-tab active" data-lp-tab="beranda">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-7 9 7"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/></svg>
                  Beranda
                </button>
                <button type="button" class="lp-tab" data-lp-tab="fitur">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                  Fitur
                </button>
                <button type="button" class="lp-tab" data-lp-tab="tentang">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-5"/><path d="M12 8h.01"/></svg>
                  Tentang
                </button>
                <button type="button" class="lp-tab" data-lp-tab="kontak">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12a10 10 0 1 1-5.6-9"/><path d="M15 8l4-4"/><path d="M15 4h4v4"/></svg>
                  Kontak
                </button>
              </div>

              {{-- ===== TAB: BERANDA ===== --}}
              <div class="lp-tab-panel active" data-lp-tab-panel="beranda">
                <p class="lp-tab-desc">Bagian paling atas landing page — yang pertama kali dilihat pengunjung.</p>
                <div class="form-grid">
                  <div class="form-field full">
                    <label for="lpEyebrow">Label Kecil di Atas Judul</label>
                    <input id="lpEyebrow" name="hero_eyebrow" type="text" value="{{ old('hero_eyebrow', $pengaturan->hero_eyebrow) }}" data-lp="hero_eyebrow">
                  </div>
                  <div class="form-field">
                    <label for="lpJudulAwal">Judul (bagian 1)</label>
                    <input id="lpJudulAwal" name="hero_judul_awal" type="text" value="{{ old('hero_judul_awal', $pengaturan->hero_judul_awal) }}" data-lp="hero_judul_awal">
                  </div>
                  <div class="form-field">
                    <label for="lpJudulAksen">Judul (bagian 2, warna emas)</label>
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
                  <div class="form-field full">
                    <label for="lpHeroImage">Gambar Latar Beranda (opsional)</label>
                    <input id="lpHeroImage" name="hero_image" type="file" accept="image/*" data-lp-image="hero_image">
                    @if($pengaturan->hero_image_path)
                      <img src="{{ asset('storage/'.$pengaturan->hero_image_path) }}" alt="Gambar beranda saat ini" class="lp-current-image">
                    @endif
                  </div>
                </div>
              </div>

              {{-- ===== TAB: FITUR ===== --}}
              <div class="lp-tab-panel" data-lp-tab-panel="fitur">
                <p class="lp-tab-desc">Empat kartu keunggulan yang tampil di bagian "Fitur".</p>
                @foreach ((old('fitur') ?? $pengaturan->fitur ?? []) as $i => $fitur)
                  <div class="lp-card">
                    <div class="lp-card-title">Fitur {{ $i + 1 }}</div>
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
              </div>

              {{-- ===== TAB: TENTANG ===== --}}
              <div class="lp-tab-panel" data-lp-tab-panel="tentang">
                <p class="lp-tab-desc">Profil singkat instansi dan moto yang tampil di bagian "Tentang".</p>
                <div class="form-grid">
                  <div class="form-field full">
                    <label for="lpTentangDeskripsi">Deskripsi Tentang (pisahkan paragraf dengan baris kosong)</label>
                    <textarea id="lpTentangDeskripsi" name="tentang_deskripsi" rows="5" data-lp="tentang_deskripsi">{{ old('tentang_deskripsi', $pengaturan->tentang_deskripsi) }}</textarea>
                  </div>
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

              {{-- ===== TAB: KONTAK ===== --}}
              <div class="lp-tab-panel" data-lp-tab-panel="kontak">
                <p class="lp-tab-desc">Informasi kontak &amp; tautan sosial media yang tampil di footer.</p>
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

                <div class="lp-card-title" style="margin-top:18px;">Sosial Media</div>
                @foreach ((old('sosial_media') ?? $pengaturan->sosial_media ?? []) as $i => $sosial)
                  <div class="lp-card lp-card-compact">
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

              <div class="lp-form-actions">
                <button class="btn btn-primary" type="submit">Simpan Konten Landing</button>
              </div>
            </form>
          </div>

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
                  <div class="lp-hero" id="lpPreviewHero" data-lp-preview-section="beranda"
                    @if($pengaturan->hero_image_path)
                      style="background-image:linear-gradient(160deg, color-mix(in srgb, var(--panel-2) 85%, transparent), color-mix(in srgb, var(--bg-deep) 75%, transparent)), url('{{ asset('storage/'.$pengaturan->hero_image_path) }}');background-size:cover;background-position:center;"
                    @endif
                  >
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
          .lp-layout{display:grid;grid-template-columns:1.3fr 1fr;gap:22px;align-items:start;}
          @media (max-width:1100px){ .lp-layout{grid-template-columns:1fr;} }

          .lp-panel form{padding:22px;}

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
          @keyframes lpFadeIn{ from{opacity:0;transform:translateY(4px);} to{opacity:1;transform:none;} }
          .lp-tab-desc{font-size:12.5px;color:var(--text-muted);margin-bottom:16px;line-height:1.6;}

          .lp-card{background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:10px;padding:16px;margin-bottom:14px;}
          .lp-card-compact{padding:12px 16px;}
          .lp-card-title{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--gold-bright);margin-bottom:10px;}
          .lp-current-image{height:52px;margin-top:10px;border-radius:6px;display:block;border:1px solid var(--border-soft);}

          .lp-form-actions{margin-top:6px;padding-top:18px;border-top:1px solid var(--border-soft);}

          .lp-preview-panel{position:sticky;top:16px;}
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
          .lp-browser-bar{display:flex;align-items:center;gap:6px;padding:10px 12px;background:var(--panel-alt);border-bottom:1px solid var(--border-soft);}
          .lp-browser-dot{width:8px;height:8px;border-radius:50%;background:var(--border-strong);}
          .lp-browser-url{
            margin-left:8px;flex:1;font-family:var(--mono);font-size:10.5px;color:var(--text-dim);
            background:var(--panel);border:1px solid var(--border-soft);border-radius:6px;padding:3px 10px;
          }

          .lp-preview{background:var(--bg);color:var(--text);font-family:var(--body);}
          .lp-preview [data-lp-preview-section]{position:relative;outline:2px solid transparent;outline-offset:-2px;transition:outline-color .2s ease;}
          .lp-preview [data-lp-preview-section].is-focus{outline-color:var(--gold);}

          .lp-hero{padding:26px 22px 22px;background:linear-gradient(160deg,var(--panel-2),var(--bg-deep));background-size:cover;background-position:center;}
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

            // ---------- tab switching ----------
            var tabs = form.querySelectorAll('[data-lp-tab]');
            var panels = form.querySelectorAll('[data-lp-tab-panel]');
            var previewSections = document.querySelectorAll('[data-lp-preview-section]');

            function activateTab(name){
              tabs.forEach(function(t){ t.classList.toggle('active', t.dataset.lpTab === name); });
              panels.forEach(function(p){ p.classList.toggle('active', p.dataset.lpTabPanel === name); });
              previewSections.forEach(function(s){ s.classList.toggle('is-focus', s.dataset.lpPreviewSection === name); });
            }

            tabs.forEach(function(t){
              t.addEventListener('click', function(){ activateTab(t.dataset.lpTab); });
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
                var judul = form.querySelector('[data-lp="fitur_judul_'+i+'"]');
                var desk = form.querySelector('[data-lp="fitur_deskripsi_'+i+'"]');
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
                var platformEl = form.querySelector('[data-lp="sosial_platform_'+i+'"]');
                if(!platformEl) break;
                var labelEl = form.querySelector('[data-lp="sosial_label_'+i+'"]');
                var urlEl = form.querySelector('[data-lp="sosial_url_'+i+'"]');
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
              setText('lpPvEyebrow', form.querySelector('[data-lp="hero_eyebrow"]').value, 'PUSSIBERAD // SISTEM PENDUKUNG OPERASIONAL');
              setText('lpPvJudulAwal', form.querySelector('[data-lp="hero_judul_awal"]').value, 'SIBER');
              setText('lpPvJudulAksen', form.querySelector('[data-lp="hero_judul_aksen"]').value, 'AD');
              setText('lpPvSubjudul', form.querySelector('[data-lp="hero_subjudul"]').value);
              setText('lpPvDeskripsi', form.querySelector('[data-lp="hero_deskripsi"]').value);
              setText('lpPvTentang', form.querySelector('[data-lp="tentang_deskripsi"]').value);
              setText('lpPvMotoJudul', form.querySelector('[data-lp="tentang_moto_judul"]').value);
              setText('lpPvMoto', form.querySelector('[data-lp="tentang_moto_deskripsi"]').value);
              setText('lpPvAlamat', form.querySelector('[data-lp="alamat"]').value);
              setText('lpPvTelepon', form.querySelector('[data-lp="telepon_kontak"]').value);
              setText('lpPvEmail', form.querySelector('[data-lp="email_kontak"]').value);
              setText('lpPvWebsite', form.querySelector('[data-lp="website"]').value);
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

            var heroImageInput = form.querySelector('[data-lp-image="hero_image"]');
            if(heroImageInput){
              heroImageInput.addEventListener('change', function(){
                var file = this.files && this.files[0];
                var heroEl = document.getElementById('lpPreviewHero');
                if(!file){ heroEl.style.backgroundImage = ''; return; }
                var reader = new FileReader();
                reader.onload = function(e){
                  heroEl.style.backgroundImage = 'linear-gradient(160deg, color-mix(in srgb, var(--panel-2) 85%, transparent), color-mix(in srgb, var(--bg-deep) 75%, transparent)), url(' + e.target.result + ')';
                };
                reader.readAsDataURL(file);
              });
            }

            updatePreview();
          })();
        </script>
      </section>

      {{-- ===== PERMINTAAN RESET PASSWORD ===== --}}
      <section class="tab-panel" data-tab-panel="reset-password">
        <div class="section-head">
          <h2>Permintaan Reset Password</h2>
          <p>Permintaan ganti kata sandi yang dikirim pengguna lewat menu "Pengaturan Akun".</p>
        </div>
        <div class="panel">
          <div class="table-toolbar">
            <div class="table-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
              <input type="text" class="table-search" data-table-search="tblResetPassword" placeholder="Cari satuan atau catatan...">
            </div>
            <select class="table-filter" data-table-filter="tblResetPassword">
              <option value="">Semua Status</option>
              <option value="Menunggu">Menunggu</option>
              <option value="Selesai">Selesai</option>
              <option value="Ditolak">Ditolak</option>
            </select>
            <span class="table-filter-count" data-table-count="tblResetPassword"></span>
          </div>
          <div class="tbl-wrap" data-row-limit="5">
            <table class="dtbl" id="tblResetPassword">
              <thead><tr><th>Satuan</th><th>Catatan</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody>
                @foreach($permintaanResetPassword as $i => $r)
                <tr id="rowReset{{ $i }}" data-filter-value="{{ $r['status'] }}">
                  <td>{{ $r['satuan'] }}</td>
                  <td style="color:var(--text-muted);">{{ $r['catatan'] }}</td>
                  <td>{{ $r['tanggal'] }}</td>
                  <td id="statusReset{{ $i }}"><span class="badge {{ $r['status_class'] }}">{{ $r['status'] }}</span></td>
                  <td>
                    @if($r['status_class'] === 'amber')
                    <div class="btn-row">
                      <button class="btn btn-primary btn-sm" type="button" onclick="setujuiResetPassword({{ $i }})">Setujui</button>
                      <button class="btn btn-ghost-red btn-sm" type="button" onclick="tolakResetPassword({{ $i }})">Tolak</button>
                    </div>
                    @else
                      <span style="font-size:11.5px;color:var(--text-dim);">Sudah diproses</span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== REKAP LAPORAN LINTAS SATLAK ===== --}}
      <section class="tab-panel" data-tab-panel="rekap-laporan">
        <div class="section-head">
          <h2>Rekap Laporan</h2>
          <p>Ringkasan jumlah &amp; status laporan tiap satuan dalam satu tampilan.</p>
        </div>

        <div class="chart-box">
          <div class="chart-mini">
            <div class="chart-mini-head">
              <h4>Total Laporan per Satuan</h4>
              <p>Perbandingan volume laporan yang sudah dikirim tiap satuan pelaksana.</p>
            </div>
            <div class="chart-wrap" style="height:260px;">
              <canvas id="chartRekapLaporan"></canvas>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Detail per Satuan</h3></div></div>
          <div class="table-toolbar">
            <div class="table-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
              <input type="text" class="table-search" data-table-search="tblRekapSatuan" placeholder="Cari nama satuan...">
            </div>
            <select class="table-filter" data-table-filter="tblRekapSatuan">
              <option value="">Semua Kategori</option>
              <option value="{{ \App\Models\Satuan::KATEGORI_SATLAK }}">Satlak</option>
              <option value="{{ \App\Models\Satuan::KATEGORI_DIREKTORAT }}">Direktorat</option>
            </select>
            <span class="table-filter-count" data-table-count="tblRekapSatuan"></span>
          </div>
          <div class="tbl-wrap">
            <table class="dtbl" id="tblRekapSatuan">
              <thead><tr><th>Satuan</th><th style="text-align:center;">Total Laporan</th><th style="text-align:center;">Disetujui</th><th style="text-align:center;">Ditolak</th><th style="text-align:center;">Menunggu</th></tr></thead>
              <tbody>
                @forelse($rekapLaporanSatuan as $s)
                <tr data-filter-value="{{ $s->kategori }}" data-search-value="{{ $s->nama }}">
                  <td>{{ $s->nama }} <span class="badge">{{ $s->kode }}</span></td>
                  <td style="text-align:center;">{{ $s->total_laporan }}</td>
                  <td style="text-align:center;"><span class="badge-status ok">{{ $s->laporan_disetujui }}</span></td>
                  <td style="text-align:center;"><span class="badge-status bad">{{ $s->laporan_ditolak }}</span></td>
                  <td style="text-align:center;"><span class="badge-status wait">{{ $s->laporan_menunggu }}</span></td>
                </tr>
                @empty
                <tr class="table-empty-row"><td colspan="5"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada data Satlak</div></div></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== SESI LOGIN AKTIF ===== --}}
      <section class="tab-panel" data-tab-panel="sesi-aktif">
        <div class="section-head">
          <h2>Sesi Login Aktif</h2>
          <p>Pantau perangkat/browser yang sedang login, dan paksa logout kalau perlu.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif

        <div class="panel">
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
                    <form method="POST" action="{{ route('admin.sessions.destroy', $s->id) }}" onsubmit="return confirm('Paksa logout sesi ini?');">
                      @csrf @method('DELETE')
                      <button class="btn btn-ghost-red btn-sm" type="submit">Paksa Logout</button>
                    </form>
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

    </div>

      <script>
        function setujuiResetPassword(i) {
          document.getElementById('statusReset' + i).innerHTML = '<span class="badge green">Selesai</span>';
          var row = document.getElementById('rowReset' + i);
          if (row) {
            row.children[4].innerHTML = '<span style="font-size:11.5px;color:var(--text-dim);">Sudah diproses</span>';
            row.setAttribute('data-filter-value', 'Selesai');
            if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblResetPassword');
          }
        }
        function tolakResetPassword(i) {
          document.getElementById('statusReset' + i).innerHTML = '<span class="badge red">Ditolak</span>';
          var row = document.getElementById('rowReset' + i);
          if (row) {
            row.children[4].innerHTML = '<span style="font-size:11.5px;color:var(--text-dim);">Sudah diproses</span>';
            row.setAttribute('data-filter-value', 'Ditolak');
            if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblResetPassword');
          }
        }
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

        // Klik di backdrop (di luar kartu popup) menutup popup
        overlay.addEventListener('click', function (e) {
          if (e.target === overlay) closeModal();
        });

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
        var MAX_PHOTO_MB = 5;
        var MAX_PHOTO_BYTES = MAX_PHOTO_MB * 1024 * 1024;
        var ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
        var STORAGE_KEY = 'siberad-profile-photo-{{ $user->id ?? "default" }}';

        var fileInput = document.getElementById('fotoProfilInput');
        var gantiBtn = document.getElementById('gantiFotoBtn');
        var gantiLabel = document.getElementById('gantiFotoLabel');
        var hapusBtn = document.getElementById('hapusFotoBtn');

        var photoBtn = document.getElementById('profilePhotoBtn');
        var photoDropdown = document.getElementById('profilePhotoDropdown');
        var photoLarge = document.getElementById('profilePhotoLarge');
        var initialBtn = document.getElementById('profileInitial');
        var initialDropdown = document.getElementById('profileInitialDropdown');
        var initialLarge = document.getElementById('profileInitialLarge');

        if (!fileInput || !gantiBtn || !hapusBtn) return;

        function showPhoto(dataUrl) {
          photoBtn.src = dataUrl;
          photoDropdown.src = dataUrl;
          photoLarge.src = dataUrl;
          photoBtn.classList.add('visible');
          photoDropdown.classList.add('visible');
          photoLarge.classList.add('visible');
          initialBtn.classList.add('hidden');
          initialDropdown.classList.add('hidden');
          initialLarge.classList.add('hidden');
          hapusBtn.style.display = 'flex';
        }

        function clearPhoto() {
          photoBtn.classList.remove('visible');
          photoDropdown.classList.remove('visible');
          photoLarge.classList.remove('visible');
          photoBtn.removeAttribute('src');
          photoDropdown.removeAttribute('src');
          photoLarge.removeAttribute('src');
          initialBtn.classList.remove('hidden');
          initialDropdown.classList.remove('hidden');
          initialLarge.classList.remove('hidden');
          hapusBtn.style.display = 'none';
        }

        // Muat foto tersimpan (jika ada) saat halaman dibuka
        try {
          var saved = localStorage.getItem(STORAGE_KEY);
          if (saved) showPhoto(saved);
        } catch (e) {}

        gantiBtn.addEventListener('click', function () {
          fileInput.click();
        });

        hapusBtn.addEventListener('click', function () {
          clearPhoto();
          try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
        });

        fileInput.addEventListener('change', function () {
          var file = fileInput.files && fileInput.files[0];
          if (!file) return;

          if (ALLOWED_TYPES.indexOf(file.type) === -1) {
            alert('File "' + file.name + '" ditolak: hanya format JPG, PNG, atau WEBP yang diperbolehkan.');
            fileInput.value = '';
            return;
          }

          if (file.size > MAX_PHOTO_BYTES) {
            alert('File "' + file.name + '" (' + (file.size / (1024 * 1024)).toFixed(2) + ' MB) melebihi batas maksimal ' + MAX_PHOTO_MB + ' MB.');
            fileInput.value = '';
            return;
          }

          gantiLabel.textContent = 'Memproses...';
          gantiBtn.setAttribute('disabled', 'disabled');

          var reader = new FileReader();
          reader.onload = function (e) {
            var dataUrl = e.target.result;
            showPhoto(dataUrl);
            try {
              localStorage.setItem(STORAGE_KEY, dataUrl);
            } catch (err) {
              alert('Foto berhasil ditampilkan, tetapi gagal disimpan secara lokal (kemungkinan ukuran terlalu besar untuk penyimpanan browser).');
            }
            gantiLabel.textContent = 'Ganti Foto Profil';
            gantiBtn.removeAttribute('disabled');
            fileInput.value = '';
          };
          reader.onerror = function () {
            alert('Gagal membaca file gambar. Silakan coba file lain.');
            gantiLabel.textContent = 'Ganti Foto Profil';
            gantiBtn.removeAttribute('disabled');
            fileInput.value = '';
          };
          reader.readAsDataURL(file);
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
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) closeConfirm();
  });
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
    var cText = root.getPropertyValue('--text').trim() || '#f4f1e6';

    Chart.defaults.color = cMuted;
    Chart.defaults.font.family = "'JetBrains Mono', monospace";
    Chart.defaults.font.size = 11;

    var doughnutOptions = {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '62%',
      plugins: { legend: { position: 'bottom', labels: { color: cText, boxWidth: 10, padding: 12 } } }
    };

    function renderDoughnut(canvasId, labels, values, colors) {
      var el = document.getElementById(canvasId);
      if (!el) return;
      new Chart(el, {
        type: 'doughnut',
        data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderColor: 'transparent' }] },
        options: doughnutOptions
      });
    }

    // ===== Grafik 1: Pengguna per Kategori Satuan (warna literal — urutan
    // groupBy() selalu Admin, Satlak, Direktorat, Pimpinan karena mengikuti
    // urutan kolom "urutan" di $semuaSatuan) =====
    var distribusiKategori = @json($distribusiPenggunaKategori);
    renderDoughnut(
      'chartKategoriSatuan',
      distribusiKategori.map(function (d) { return d.kategori; }),
      distribusiKategori.map(function (d) { return d.jumlah; }),
      [cGold, '#6366f1', '#22c55e', '#f59e0b']
    );

    // ===== Grafik 2: Distribusi Status Laporan (data asli, warna tetap
    // hijau/merah/oren — bukan var(--green-bright) yang di-repurpose jadi
    // gold di dark mode) =====
    var statusLaporan = @json($statusLaporanSistem);
    renderDoughnut(
      'chartStatusLaporan',
      ['Disetujui', 'Ditolak', 'Menunggu'],
      [statusLaporan.disetujui, statusLaporan.ditolak, statusLaporan.menunggu],
      ['#22c55e', '#ef4444', '#f59e0b']
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
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(255,255,255,.06)' } }
          }
        }
      });
    }

    // ===== Grafik 4: Rekap Total Laporan per Satlak =====
    var rekapSatuan = @json($rekapLaporanSatuan);
    var elRekap = document.getElementById('chartRekapLaporan');
    if (elRekap) {
      var rekapCtx = elRekap.getContext('2d');
      var rekapGradient = rekapCtx.createLinearGradient(0, 0, 0, elRekap.height || 260);
      rekapGradient.addColorStop(0, '#6366f1');
      rekapGradient.addColorStop(1, '#3b82f6');
      new Chart(elRekap, {
        type: 'bar',
        data: {
          labels: rekapSatuan.map(function (s) { return s.kode; }),
          datasets: [{
            label: 'Total Laporan',
            data: rekapSatuan.map(function (s) { return s.total_laporan; }),
            backgroundColor: rekapGradient,
            hoverBackgroundColor: '#4f46e5',
            borderRadius: 6,
            maxBarThickness: 46
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(255,255,255,.06)' } }
          }
        }
      });
    }
  })();
  </script>

  <script>
  (function () {
    function collectRows(table) {
      return Array.prototype.slice.call(table.querySelectorAll('tbody tr:not(.table-empty-row)'));
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
      var q = searchInput ? searchInput.value.trim().toLowerCase() : '';
      var f = filterSelect ? filterSelect.value : '';
      var rows = collectRows(table);
      var visibleCount = 0;

      rows.forEach(function (row) {
        var teksCari = row.hasAttribute('data-search-value') ? row.getAttribute('data-search-value') : row.textContent;
        var cocokCari = !q || teksCari.toLowerCase().indexOf(q) !== -1;
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