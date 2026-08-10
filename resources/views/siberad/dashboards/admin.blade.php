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
  .chart-box{margin-bottom:26px;}
  .chart-box-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
  .chart-mini{background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:10px;padding:14px;}
  .chart-mini-head{margin-bottom:8px;}
  .chart-mini-head h4{font-family:var(--display);font-size:13px;font-weight:700;letter-spacing:.01em;line-height:1.3;}
  .chart-mini-head p{font-size:11px;color:var(--text-muted);margin-top:2px;}
  .chart-mini .chart-wrap{position:relative;height:210px;}
  @media(max-width:980px){.chart-box-grid{grid-template-columns:1fr;}.chart-mini .chart-wrap{height:230px;}}

  /* ===== toolbar cari & filter tabel ===== */
  .table-toolbar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;}
  .table-search-wrap{position:relative;flex:1;min-width:200px;}
  .table-search-wrap svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;stroke:var(--text-dim);pointer-events:none;}
  .table-search{
    width:100%;box-sizing:border-box;background:var(--panel);border:1px solid var(--border);color:var(--text);
    font-family:var(--body);font-size:13px;border-radius:8px;padding:9px 12px 9px 34px;
  }
  .table-search::placeholder{color:var(--text-dim);}
  .table-search:focus{outline:none;border-color:var(--gold);}
  .table-filter{
    background:var(--panel);border:1px solid var(--border);color:var(--text);font-family:var(--mono);
    font-size:11.5px;letter-spacing:.02em;border-radius:8px;padding:0 10px;cursor:pointer;flex-shrink:0;
    min-width:170px;height:38px;
  }
  .table-filter:focus{outline:none;border-color:var(--gold);}
  .table-empty-row td{text-align:center;color:var(--text-dim);font-size:12.5px;padding:26px 12px !important;}
  @media(max-width:640px){.table-toolbar{flex-direction:column;}.table-filter{width:100%;}}
</style>
</head>
<body>
<div class="profile-modal-overlay" id="profileModalOverlay">
  <div class="profile-modal-card" id="profileModalCard" role="dialog" aria-modal="true" aria-label="Detail profil">
    <button type="button" class="profile-modal-close" id="profileModalCloseBtn" aria-label="Tutup">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>
    </button>

    {{-- ===== VIEW PROFIL SAYA ===== --}}
    <div class="profile-dropdown-view" id="profilePhotoView" style="display:none;">
      <div class="profile-dropdown-head-lg">
        <div class="profile-dropdown-avatar-lg">
          <span class="profile-initial" id="profileInitialLarge">{{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}</span>
          <img class="profile-photo" id="profilePhotoLarge" alt="Foto profil {{ $user->name }}">
        </div>
        <div class="profile-dropdown-name">{{ $user->name }}</div>
        <div class="profile-dropdown-role">{{ $user->jabatan ?? 'Pengguna' }}</div>
      </div>

      <button type="button" class="profile-dropdown-item" id="gantiFotoBtn" role="menuitem">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8.5A1.5 1.5 0 0 1 5.5 7h2l1-2h7l1 2h2A1.5 1.5 0 0 1 20 8.5v9A1.5 1.5 0 0 1 18.5 19h-13A1.5 1.5 0 0 1 4 17.5Z"></path><circle cx="12" cy="13" r="3.4"></circle></svg>
        <span id="gantiFotoLabel">Ganti Foto Profil</span>
      </button>
      <button type="button" class="profile-dropdown-item" id="hapusFotoBtn" role="menuitem" style="display:none;">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>
        Hapus Foto Profil
      </button>
      <input type="file" id="fotoProfilInput" accept="image/png,image/jpeg,image/webp" hidden>
    </div>

    {{-- ===== VIEW PENGATURAN AKUN ===== --}}
    <div class="profile-dropdown-view" id="profileSettingsView" style="display:none;">
      <div class="profile-modal-title">Pengaturan Akun</div>

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
        <button type="submit" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;">Kirim Permintaan ke Admin</button>
      </form>
    </div>

    {{-- ===== VIEW BANTUAN & PANDUAN ===== --}}
    <div class="profile-dropdown-view" id="profileHelpView" style="display:none;">
      <div class="profile-modal-title">Bantuan &amp; Panduan</div>
      <p class="profile-help-text">
        Prototype — pusat bantuan belum tersambung. Kalau butuh bantuan seputar SIBERAD,
        silakan hubungi Admin Pussiberad melalui jalur koordinasi internal.
      </p>
    </div>

  </div>
</div>

<div class="shell">

  <aside class="sidebar" id="sidebar">
    <div class="side-brand">
      <img src="{{ asset('images/logo-pussiberad.jpg') }}" alt="Lambang Pussiberad">
      <div class="logo">SIBER<span>AD</span></div>
    </div>
    <nav class="side-nav">
      <a href="#" class="side-link active" data-tab-link="dashboard"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/></svg></span>Dashboard</a>

      <div class="side-dropdown" id="penggunaDropdown">
        <button type="button" class="side-link side-dropdown-toggle" id="penggunaToggle" aria-expanded="false" aria-controls="penggunaSubmenu">
          <span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
          <span class="side-link-label">Kelola Pengguna</span>
          <svg class="side-dropdown-arrow" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"></path></svg>
        </button>
        <div class="side-dropdown-menu" id="penggunaSubmenu">
          <a href="#" class="side-link side-sublink" data-tab-link="pengguna"><span class="dot"></span>Daftar Pengguna</a>
          <a href="#" class="side-link side-sublink" data-tab-link="reset-password"><span class="dot"></span>Permintaan Reset Password</a>
          <a href="#" class="side-link side-sublink" data-tab-link="pengumuman"><span class="dot"></span>Pengumuman</a>
        </div>
      </div>

      <div class="side-dropdown" id="monitoringDropdown">
        <button type="button" class="side-link side-dropdown-toggle" id="monitoringToggle" aria-expanded="false" aria-controls="monitoringSubmenu">
          <span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
          <span class="side-link-label">Monitoring</span>
          <svg class="side-dropdown-arrow" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"></path></svg>
        </button>
        <div class="side-dropdown-menu" id="monitoringSubmenu">
          <a href="#" class="side-link side-sublink" data-tab-link="rekap-laporan"><span class="dot"></span>Rekap Laporan</a>
          <a href="#" class="side-link side-sublink" data-tab-link="sesi-aktif"><span class="dot"></span>Sesi Aktif</a>
        </div>
      </div>

      <div class="side-dropdown" id="sistemDropdown">
        <button type="button" class="side-link side-dropdown-toggle" id="sistemToggle" aria-expanded="false" aria-controls="sistemSubmenu">
          <span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
          <span class="side-link-label">Kelola Sistem</span>
          <svg class="side-dropdown-arrow" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"></path></svg>
        </button>
        <div class="side-dropdown-menu" id="sistemSubmenu">
          <a href="#" class="side-link side-sublink" data-tab-link="satlak"><span class="dot"></span>Manajemen Satuan</a>
          <a href="#" class="side-link side-sublink" data-tab-link="role-akses"><span class="dot"></span>Role &amp; Hak Akses</a>
          <a href="#" class="side-link side-sublink" data-tab-link="data-master"><span class="dot"></span>Data Master</a>
          <a href="#" class="side-link side-sublink" data-tab-link="log-aktivitas"><span class="dot"></span>Log Aktivitas</a>
          <a href="#" class="side-link side-sublink" data-tab-link="backup"><span class="dot"></span>Backup Database</a>
          <a href="#" class="side-link side-sublink" data-tab-link="laporan-admin"><span class="dot"></span>Laporan &amp; Export</a>
          <a href="#" class="side-link side-sublink" data-tab-link="pengaturan-umum"><span class="dot"></span>Pengaturan Umum</a>
        </div>
      </div>
    </nav>
    <div class="side-foot">
      <form class="logout logout-form" method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Keluar</button>
      </form>
    </div>
  </aside>

  <script>
  (function () {
    var dropdowns = [
      { wrap: 'penggunaDropdown', toggle: 'penggunaToggle' },
      { wrap: 'monitoringDropdown', toggle: 'monitoringToggle' },
      { wrap: 'sistemDropdown', toggle: 'sistemToggle' }
    ];
    dropdowns.forEach(function (cfg) {
      var dropdown = document.getElementById(cfg.wrap);
      var toggle = document.getElementById(cfg.toggle);
      if (!dropdown || !toggle) return;

      var subActive = dropdown.querySelector('.side-sublink.active');
      if (subActive) dropdown.classList.add('open');

      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        var isOpen = dropdown.classList.toggle('open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
    });
  })();
  </script>

  <main class="main">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <button class="menu-btn" id="menuBtn">☰</button>
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
            <span style="position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:var(--red);box-shadow:0 0 0 2px var(--panel,#0c2417);"></span>
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

            <button type="button" class="profile-dropdown-item" id="openProfilSayaBtn" role="menuitem">
              <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"></circle><path d="M5 20c1.2-4 4.2-6 7-6s5.8 2 7 6"></path></svg>
              Profil Saya
            </button>
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

    
    <div class="content">
      @include('siberad.dashboards.partials.pengumuman-banner')

      {{-- ===== DASHBOARD ===== --}}
      <section class="tab-panel active" data-tab-panel="dashboard">

        <div class="dash-hero">
          <div>
            <div class="dash-hero-eyebrow">SIBERAD // {{ $satuan->kode ?? 'SISTEM' }}</div>
            <h2>Selamat datang, {{ $satuan->nama ?? $user->name }}</h2>
            <p>{{ now()->translatedFormat('l, d F Y') }}</p>
          </div>
        </div>

        <div class="kpi-grid">
          <div class="stat-card kpi-card">
            <div class="kpi-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="lbl">Total Pengguna</div>
            <div class="val">{{ $stats['total_pengguna'] }}</div>
            <div class="sub">Akun terdaftar di sistem</div>
          </div>
          <div class="stat-card kpi-card">
            <div class="kpi-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
            </div>
            <div class="lbl">Total Satuan</div>
            <div class="val">{{ $stats['total_satuan'] }}</div>
            <div class="sub">Termasuk Admin</div>
          </div>
          <div class="stat-card kpi-card">
            <div class="kpi-icon" style="color:var(--amber);background:var(--amber-dim);">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 13.5a7.6 7.6 0 0 0 0-3l2-1.5-2-3.4-2.3.9a7.6 7.6 0 0 0-2.6-1.5L14 2.5h-4l-.5 2.5a7.6 7.6 0 0 0-2.6 1.5l-2.3-.9-2 3.4 2 1.5a7.6 7.6 0 0 0 0 3l-2 1.5 2 3.4 2.3-.9a7.6 7.6 0 0 0 2.6 1.5l.5 2.5h4l.5-2.5a7.6 7.6 0 0 0 2.6-1.5l2.3.9 2-3.4Z"/></svg>
            </div>
            <div class="lbl">Reset Password</div>
            <div class="val" style="color:var(--amber);">{{ $stats['reset_password_pending'] }}</div>
            <div class="sub">Menunggu diverifikasi</div>
          </div>
          <div class="stat-card kpi-card">
            <div class="kpi-icon" style="color:{{ $stats['satuan_tanpa_pengguna'] > 0 ? 'var(--red)' : 'var(--green)' }};background:{{ $stats['satuan_tanpa_pengguna'] > 0 ? 'var(--red-dim)' : 'var(--green-dim)' }};">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
            </div>
            <div class="lbl">Satuan Tanpa Pengguna</div>
            <div class="val" style="color:{{ $stats['satuan_tanpa_pengguna'] > 0 ? 'var(--red)' : 'var(--green)' }};">{{ $stats['satuan_tanpa_pengguna'] }}</div>
            <div class="sub">Perlu dibuatkan akun</div>
          </div>
        </div>

        <div class="panel chart-box">
          <div class="panel-head"><div><h3>Statistik Sistem</h3><p>Sebaran akun, satuan, dan permintaan reset password.</p></div></div>
          <div class="chart-box-grid">

            <div class="chart-mini">
              <div class="chart-mini-head">
                <h4>Pengguna per Kategori Satuan</h4><p>Sebaran akun berdasarkan kategori.</p>
              </div>
              <div class="chart-wrap"><canvas id="chartKategoriSatuan"></canvas></div>
            </div>

            <div class="chart-mini">
              <div class="chart-mini-head">
                <h4>Status Reset Password</h4><p>Proporsi permintaan yang masuk.</p>
              </div>
              <div class="chart-wrap"><canvas id="chartStatusReset"></canvas></div>
            </div>

            <div class="chart-mini">
              <div class="chart-mini-head">
                <h4>Kelengkapan Akun Satuan</h4><p>Satuan yang sudah vs belum punya akun.</p>
              </div>
              <div class="chart-wrap"><canvas id="chartKelengkapanSatuan"></canvas></div>
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
                <div class="activity-text">{{ $log->deskripsi ?: $log->aksi }}</div>
                <div class="activity-meta">{{ $log->nama_pengguna ?? 'Sistem' }} &middot; {{ $log->created_at?->diffForHumans() }}</div>
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
          .kpi-card{padding-top:52px;}
          .kpi-icon{
            position:absolute;top:16px;left:16px;width:34px;height:34px;border-radius:9px;
            display:flex;align-items:center;justify-content:center;
            background:var(--gold-dim);color:var(--gold-bright);
          }
          .kpi-icon svg{width:17px;height:17px;}

          .activity-panel{margin-top:22px;}
          .activity-feed{list-style:none;padding:4px 22px 18px;margin:0;}
          .activity-feed li{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--border-soft);}
          .activity-feed li:last-child{border-bottom:none;}
          .activity-dot{width:7px;height:7px;border-radius:50%;background:var(--gold);margin-top:6px;flex-shrink:0;}
          .activity-text{font-size:13px;color:var(--text);line-height:1.5;}
          .activity-meta{font-size:11px;color:var(--text-dim);margin-top:3px;}
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
          <div class="panel-head"><div><h3>Tambah Pengguna</h3><p>Buat akun baru untuk satu satuan.</p></div></div>
          <form class="form-grid" method="POST" action="{{ route('admin.users.store') }}" style="padding:22px;">
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
              <label for="uJabatan">Jabatan (opsional)</label>
              <input id="uJabatan" name="jabatan" type="text">
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
              <input id="uPassword" name="password" type="password" minlength="8" required placeholder="Minimal 8 karakter">
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="submit">Simpan Pengguna</button>
            </div>
          </form>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Daftar Pengguna</h3><p>Klik "Ubah" untuk mengedit satuan/jabatan/password.</p></div></div>
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
          </div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl" id="tblPengguna">
              <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Satuan</th><th>Aksi</th></tr></thead>
              <tbody>
                @foreach($semuaPengguna as $p)
                <tr data-filter-value="{{ $p->satuan->nama ?? '' }}">
                  <td>{{ $p->name }}</td>
                  <td><span class="badge">{{ $p->username }}</span></td>
                  <td style="color:var(--text-muted);">{{ $p->email }}</td>
                  <td>{{ $p->satuan->nama ?? '-' }}</td>
                  <td>
                    <div class="btn-row">
                      <button class="btn btn-sm" type="button" onclick="toggleEditPengguna({{ $p->id }})">Ubah</button>
                      <form method="POST" action="{{ route('admin.users.reset-password', $p) }}" onsubmit="return confirm('Reset password akun {{ $p->name }}?');" style="display:inline;">
                        @csrf
                        <button class="btn btn-sm" type="submit">Reset Password</button>
                      </form>
                      @if($p->id !== $user->id)
                      <form method="POST" action="{{ route('admin.users.destroy', $p) }}" onsubmit="return confirm('Hapus akun {{ $p->name }}?');" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-ghost-red" type="submit">Hapus</button>
                      </form>
                      @endif
                    </div>
                  </td>
                </tr>
                <tr id="editRow{{ $p->id }}" style="display:none;">
                  <td colspan="5" style="background:var(--panel-alt);">
                    <form class="form-grid" method="POST" action="{{ route('admin.users.update', $p) }}" style="padding:16px;">
                      @csrf @method('PATCH')
                      <div class="form-field"><label>Nama</label><input name="name" type="text" value="{{ $p->name }}" required></div>
                      <div class="form-field"><label>Username</label><input name="username" type="text" value="{{ $p->username }}" required></div>
                      <div class="form-field"><label>Email</label><input name="email" type="email" value="{{ $p->email }}"></div>
                      <div class="form-field"><label>Jabatan</label><input name="jabatan" type="text" value="{{ $p->jabatan }}"></div>
                      <div class="form-field">
                        <label>Satuan</label>
                        <select name="satuan_id" required>
                          @foreach($semuaSatuan as $s)
                          <option value="{{ $s->id }}" @selected($p->satuan_id === $s->id)>{{ $s->nama }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="form-field"><label>Password Baru (opsional)</label><input name="password" type="password" minlength="8" placeholder="Kosongkan jika tidak diubah"></div>
                      <div class="form-field full"><button class="btn btn-primary btn-sm" type="submit">Simpan Perubahan</button></div>
                    </form>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </section>
      <script>
        function toggleEditPengguna(id) {
          var row = document.getElementById('editRow' + id);
          if (row) row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
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
          <div class="panel-head"><div><h3>Tambah Satuan</h3><p>Kode dipakai sebagai identitas login/role.</p></div></div>
          <form class="form-grid" method="POST" action="{{ route('admin.satuan.store') }}" style="padding:22px;">
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
            <div class="form-field full"><button class="btn btn-primary" type="submit">Simpan Satuan</button></div>
          </form>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Daftar Satuan</h3><p>Satuan yang masih punya pengguna tidak bisa dihapus.</p></div></div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl">
              <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Jumlah Pengguna</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($semuaSatuan as $s)
                <tr>
                  <td><span class="badge">{{ $s->kode }}</span></td>
                  <td>{{ $s->nama }}</td>
                  <td style="color:var(--text-muted);text-transform:capitalize;">{{ $s->kategori }}</td>
                  <td>{{ $s->users_count }}</td>
                  <td>
                    <div class="btn-row">
                      <button class="btn btn-sm" type="button" onclick="toggleEditSatuan({{ $s->id }})">Ubah</button>
                      <form method="POST" action="{{ route('admin.satuan.destroy', $s) }}" onsubmit="return confirm('Hapus satuan {{ $s->nama }}?');" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-ghost-red" type="submit">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
                <tr id="editSatuan{{ $s->id }}" style="display:none;">
                  <td colspan="5" style="background:var(--panel-alt);">
                    <form class="form-grid" method="POST" action="{{ route('admin.satuan.update', $s) }}" style="padding:16px;">
                      @csrf @method('PATCH')
                      <div class="form-field"><label>Kode</label><input name="kode" type="text" value="{{ $s->kode }}" required></div>
                      <div class="form-field"><label>Nama</label><input name="nama" type="text" value="{{ $s->nama }}" required></div>
                      <div class="form-field">
                        <label>Kategori</label>
                        <select name="kategori" required>
                          @foreach([\App\Models\Satuan::KATEGORI_SATLAK,\App\Models\Satuan::KATEGORI_DIREKTORAT,\App\Models\Satuan::KATEGORI_PIMPINAN,\App\Models\Satuan::KATEGORI_ADMIN] as $k)
                          <option value="{{ $k }}" @selected($s->kategori === $k)>{{ ucfirst($k) }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="form-field"><label>Urutan</label><input name="urutan" type="number" min="0" value="{{ $s->urutan }}"></div>
                      <div class="form-field full"><label>Deskripsi</label><textarea name="deskripsi" rows="2">{{ $s->deskripsi }}</textarea></div>
                      <div class="form-field full"><button class="btn btn-primary btn-sm" type="submit">Simpan Perubahan</button></div>
                    </form>
                  </td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:var(--text-dim);padding:24px;">Belum ada data satuan.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
      <script>
        function toggleEditSatuan(id) {
          var row = document.getElementById('editSatuan' + id);
          if (row) row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
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

      {{-- ===== DATA MASTER ===== --}}
      <section class="tab-panel" data-tab-panel="data-master">
        <div class="section-head">
          <h2>Manajemen Data Master</h2>
          <p>Referensi data pangkat &amp; jabatan yang dipakai seluruh modul Administrasi Personel.</p>
        </div>
        <div class="panel">
          <div style="padding:20px;text-align:center;">
            <p style="margin:0 0 12px;font-size:12.5px;line-height:1.6;color:var(--text-muted);">
              Data master Pangkat dan Jabatan dikelola bersama dari dashboard Pembinaan Fungsi supaya satu sumber data untuk seluruh satuan.
            </p>
            <span class="badge">{{ \App\Models\Pangkat::count() }} Pangkat</span>
            &nbsp;
            <span class="badge">{{ \App\Models\Jabatan::count() }} Jabatan</span>
            &nbsp;
            <span class="badge">{{ $semuaSatuan->count() }} Satuan</span>
          </div>
        </div>
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
                <tr><td colspan="5" style="text-align:center;color:var(--text-dim);padding:24px;">Belum ada aktivitas tercatat.</td></tr>
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
                <tr><td colspan="4" style="text-align:center;color:var(--text-dim);padding:24px;">Belum ada backup dibuat.</td></tr>
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
            <a class="btn btn-sm" href="{{ route('admin.laporan.cetak') }}" target="_blank">Cetak / Simpan sebagai PDF</a>
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
                  <span class="lp-browser-url">siberad.mil.id</span>
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
          .lp-live-dot{width:7px;height:7px;border-radius:50%;background:var(--green-bright);box-shadow:0 0 0 3px var(--green-dim);animation:lpPulse 1.8s ease-in-out infinite;}
          @keyframes lpPulse{ 0%,100%{opacity:1;} 50%{opacity:.35;} }
          .lp-preview-body{padding:0 22px 22px;}

          .lp-browser-frame{border-radius:12px;overflow:hidden;border:1px solid var(--border-soft);box-shadow:0 14px 34px -14px rgba(0,0,0,.4);}
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
              setText('lpPvEyebrow', form.querySelector('[data-lp="hero_eyebrow"]').value);
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

      {{-- ===== PENGUMUMAN ===== --}}
      <section class="tab-panel" data-tab-panel="pengumuman">
        <div class="section-head">
          <h2>Pengumuman</h2>
          <p>Broadcast pesan ke seluruh satuan — tampil sebagai banner di halaman dashboard mereka.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif

        <div class="panel">
          <div class="panel-head"><div><h3>Buat Pengumuman Baru</h3></div></div>
          <form method="POST" action="{{ route('admin.pengumuman.store') }}" class="form-grid" style="padding:22px;">
            @csrf
            <div class="form-field full">
              <label for="pgmJudul">Judul</label>
              <input id="pgmJudul" name="judul" type="text" maxlength="150" required>
            </div>
            <div class="form-field full">
              <label for="pgmIsi">Isi Pengumuman</label>
              <textarea id="pgmIsi" name="isi" rows="3" maxlength="2000" required></textarea>
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="submit">Publikasikan</button>
            </div>
          </form>
        </div>

        <div class="panel" style="margin-top:22px;">
          <div class="panel-head"><div><h3>Daftar Pengumuman</h3></div></div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl">
              <thead><tr><th>Judul</th><th>Isi</th><th>Dibuat Oleh</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($daftarPengumuman as $p)
                <tr>
                  <td>{{ $p->judul }}</td>
                  <td style="color:var(--text-muted);max-width:280px;">{{ \Illuminate\Support\Str::limit($p->isi, 80) }}</td>
                  <td>{{ $p->pembuat?->name ?? '-' }}</td>
                  <td>{{ $p->created_at->format('d M Y H:i') }}</td>
                  <td><span class="badge {{ $p->aktif ? 'green' : '' }}">{{ $p->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                  <td>
                    <div class="btn-row">
                      <form method="POST" action="{{ route('admin.pengumuman.toggle', $p) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-ghost btn-sm" type="submit">{{ $p->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                      </form>
                      <form method="POST" action="{{ route('admin.pengumuman.destroy', $p) }}" onsubmit="return confirm('Hapus pengumuman ini?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost-red btn-sm" type="submit">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
                @empty
                <tr class="table-empty-row"><td colspan="6">Belum ada pengumuman.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== REKAP LAPORAN LINTAS SATLAK ===== --}}
      <section class="tab-panel" data-tab-panel="rekap-laporan">
        <div class="section-head">
          <h2>Rekap Laporan</h2>
          <p>Ringkasan jumlah &amp; status laporan tiap Satlak dalam satu tampilan.</p>
        </div>

        <div class="chart-box">
          <div class="chart-mini">
            <div class="chart-mini-head">
              <h4>Total Laporan per Satlak</h4>
              <p>Perbandingan volume laporan yang sudah dikirim tiap satuan pelaksana.</p>
            </div>
            <div class="chart-wrap" style="height:260px;">
              <canvas id="chartRekapLaporan"></canvas>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Detail per Satlak</h3></div></div>
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Satlak</th><th>Total Laporan</th><th>Menunggu</th><th>Disetujui</th><th>Ditolak</th></tr></thead>
              <tbody>
                @forelse($rekapLaporanSatuan as $s)
                <tr>
                  <td>{{ $s->nama }} <span class="badge">{{ $s->kode }}</span></td>
                  <td>{{ $s->total_laporan }}</td>
                  <td><span class="badge amber">{{ $s->laporan_menunggu }}</span></td>
                  <td><span class="badge green">{{ $s->laporan_disetujui }}</span></td>
                  <td><span class="badge red">{{ $s->laporan_ditolak }}</span></td>
                </tr>
                @empty
                <tr class="table-empty-row"><td colspan="5">Belum ada data Satlak.</td></tr>
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
                <tr class="table-empty-row"><td colspan="5">Tidak ada sesi aktif.</td></tr>
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
        var openProfilBtn = document.getElementById('openProfilSayaBtn');
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
        if (openProfilBtn) {
          openProfilBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeMenu();
            if (window.openProfileModal) window.openProfileModal('profilePhotoView');
          });
        }
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
    if (pendingForm) pendingForm.submit();
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

    // ===== Grafik 1: Pengguna per Kategori Satuan =====
    var distribusiKategori = @json($distribusiPenggunaKategori);
    renderDoughnut(
      'chartKategoriSatuan',
      distribusiKategori.map(function (d) { return d.kategori; }),
      distribusiKategori.map(function (d) { return d.jumlah; }),
      [cGold, cGreen, cAmber, cMuted]
    );

    // ===== Grafik 2: Status Permintaan Reset Password =====
    var statusReset = @json($statusResetPassword);
    var warnaStatus = { 'Menunggu': cAmber, 'Selesai': cGreen, 'Ditolak': cRed };
    renderDoughnut(
      'chartStatusReset',
      statusReset.map(function (s) { return s.status; }),
      statusReset.map(function (s) { return s.jumlah; }),
      statusReset.map(function (s) { return warnaStatus[s.status] || cMuted; })
    );

    // ===== Grafik 3: Kelengkapan Akun Satuan =====
    var kelengkapan = @json($kelengkapanAkunSatuan);
    renderDoughnut(
      'chartKelengkapanSatuan',
      ['Sudah Punya Akun', 'Belum Punya Akun'],
      [kelengkapan.sudah, kelengkapan.belum],
      [cGreen, cRed]
    );

    // ===== Grafik 4: Rekap Total Laporan per Satlak =====
    var rekapSatuan = @json($rekapLaporanSatuan);
    var elRekap = document.getElementById('chartRekapLaporan');
    if (elRekap) {
      new Chart(elRekap, {
        type: 'bar',
        data: {
          labels: rekapSatuan.map(function (s) { return s.kode; }),
          datasets: [{
            label: 'Total Laporan',
            data: rekapSatuan.map(function (s) { return s.total_laporan; }),
            backgroundColor: cGold,
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
      td.textContent = 'Tidak ada data yang cocok.';
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
        var cocokCari = !q || row.textContent.toLowerCase().indexOf(q) !== -1;
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
  })();
  </script>

@include('siberad.dashboards.partials.dash-script')
</body>
</html>