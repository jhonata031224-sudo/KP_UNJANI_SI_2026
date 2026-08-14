<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Satuan Pelaksanaan Penindakan — SIBERAD</title>
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo-pussiberad.jpg') }}">
@include('siberad.dashboards.partials.dash-styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
{{-- Library untuk fitur "Export Laporan" pada tab Riwayat Laporan. --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<style>
  .chart-box{margin-bottom:26px;}
  .progress-track{background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:20px;height:8px;width:100%;overflow:hidden;}
  .progress-fill{height:100%;border-radius:20px;background:linear-gradient(90deg,var(--gold),var(--gold-bright));}
  .progress-row{display:flex;align-items:center;gap:10px;min-width:140px;}
  .progress-row span{font-family:var(--mono);font-size:11px;color:var(--text-muted);flex-shrink:0;width:34px;text-align:right;}
  .timeline{position:relative;padding:6px 4px 6px 6px;}
  .timeline-item{position:relative;display:flex;gap:16px;padding-bottom:26px;}
  .timeline-item:last-child{padding-bottom:0;}
  .timeline-marker{position:relative;display:flex;flex-direction:column;align-items:center;flex-shrink:0;width:16px;}
  .timeline-dot{width:14px;height:14px;border-radius:50%;border:2px solid var(--border);background:var(--panel-alt);flex-shrink:0;z-index:1;}
  .timeline-item.done .timeline-dot{background:var(--green-bright);border-color:var(--green-bright);box-shadow:0 0 0 3px var(--green-dim);}
  .timeline-item.active .timeline-dot{background:var(--gold-bright);border-color:var(--gold-bright);box-shadow:0 0 0 3px var(--gold-dim);}
  .timeline-item.pending .timeline-dot{background:var(--panel-alt);border-color:var(--border);}
  .timeline-line{position:absolute;top:16px;bottom:-26px;width:2px;background:var(--border-soft);}
  .timeline-item:last-child .timeline-line{display:none;}
  .timeline-item.done .timeline-line{background:var(--green-dim);}
  .timeline-content{flex:1;padding-top:-1px;}
  .timeline-time{font-family:var(--mono);font-size:10.5px;color:var(--text-dim);letter-spacing:.04em;text-transform:uppercase;}
  .timeline-title{font-family:var(--display);font-size:14px;font-weight:700;margin:3px 0 4px;}
  .timeline-item.active .timeline-title{color:var(--gold-bright);}
  .timeline-desc{font-size:12.5px;color:var(--text-muted);line-height:1.55;}
  .chart-box-head-row{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:16px;}
  .chart-filter-group{display:flex;gap:8px;flex-wrap:wrap;flex-shrink:0;}
  .chart-type-select{background:var(--panel);border:1px solid var(--border);color:var(--text);font-family:var(--mono);font-size:11px;border-radius:6px;padding:5px 8px;cursor:pointer;flex-shrink:0;}
  .chart-type-select:focus{outline:none;border-color:var(--gold);}
  .chart-box-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;transition:all .2s ease;}
  .chart-mini{background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:10px;padding:14px;}
  .chart-mini-head{margin-bottom:8px;}
  .chart-mini-head h4{font-family:var(--display);font-size:13px;font-weight:700;letter-spacing:.01em;line-height:1.3;}
  .chart-mini-head p{font-size:11px;color:var(--text-muted);margin-top:2px;}
  .chart-mini .chart-wrap{position:relative;height:210px;transition:height .2s ease;}
  .chart-box-grid.split-mode{grid-template-columns:1fr;gap:18px;}
  .chart-box-grid.split-mode .chart-mini{padding:18px 20px;border-color:var(--border);}
  .chart-box-grid.split-mode .chart-mini .chart-wrap{height:320px;}
  .chart-legend-note{font-size:11px;color:var(--text-dim);margin-top:14px;line-height:1.5;}
  @media(max-width:980px){.chart-box-grid{grid-template-columns:1fr;}.chart-mini .chart-wrap{height:230px;}}
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
      <div class="side-nav-label">Menu</div>
      <a href="#" class="side-link active" data-tab-link="dashboard"><span class="dot"></span>Dashboard</a>
      <a href="#" class="side-link" data-tab-link="ancaman"><span class="dot"></span>Deteksi Ancaman</a>
      <a href="#" class="side-link" data-tab-link="penanganan"><span class="dot"></span>Log Penanganan</a>

      <div class="side-dropdown" id="laporanDropdown">
        <button type="button" class="side-link side-dropdown-toggle" id="laporanToggle" aria-expanded="false" aria-controls="laporanSubmenu">
          <span class="dot"></span>
          <span class="side-link-label">Laporan</span>
          <svg class="side-dropdown-arrow" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"></path></svg>
        </button>
        <div class="side-dropdown-menu" id="laporanSubmenu">
          <a href="#" class="side-link side-sublink" data-tab-link="tambah-laporan">Tambah Laporan</a>
          <a href="#" class="side-link side-sublink" data-tab-link="status-laporan">Status Laporan</a>
          <a href="#" class="side-link side-sublink" data-tab-link="riwayat-laporan">Riwayat Laporan</a>
        </div>
      </div>

      <div class="side-dropdown" id="insidenDropdown">
        <button type="button" class="side-link side-dropdown-toggle" id="insidenToggle" aria-expanded="false" aria-controls="insidenSubmenu">
          <span class="dot"></span>
          <span class="side-link-label">Manajemen Insiden</span>
          <svg class="side-dropdown-arrow" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"></path></svg>
        </button>
        <div class="side-dropdown-menu" id="insidenSubmenu">
          <a href="#" class="side-link side-sublink" data-tab-link="investigasi">Investigasi</a>
          <a href="#" class="side-link side-sublink" data-tab-link="timeline-penanganan">Timeline Penanganan</a>
          <a href="#" class="side-link side-sublink" data-tab-link="mitigasi">Mitigasi</a>
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
    var pairs = [
      { dropdown: document.getElementById('laporanDropdown'), toggle: document.getElementById('laporanToggle') },
      { dropdown: document.getElementById('insidenDropdown'), toggle: document.getElementById('insidenToggle') }
    ];

    pairs.forEach(function (pair) {
      var dropdown = pair.dropdown, toggle = pair.toggle;
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
        <button type="button" class="menu-btn" id="menuBtn">☰</button>
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

      {{-- ===== RINGKASAN ===== --}}
      <section class="tab-panel active" data-tab-panel="dashboard">
        <div class="dash-hero">
          <div>
            <div class="dash-hero-eyebrow">SIBERAD // {{ $satuan->kode ?? 'SISTEM' }}</div>
            <h2>Selamat datang, {{ $satuan->nama ?? $user->name }}</h2>
            <p>{{ now()->translatedFormat('l, d F Y') }}</p>
          </div>
        </div>

        <div class="section-head">
          <h2>Ringkasan Penanganan Ancaman</h2>
          <p>Status ancaman siber yang ditangani Satuan Pelaksanaan Penindakan hari ini.</p>
        </div>
        <div class="stat-grid">
          <div class="stat-card">
            <div class="lbl">Ancaman Aktif</div>
            <div class="val" style="color:var(--red);">{{ $stats['ancaman_aktif'] }}</div>
            <div class="sub">Sedang ditangani</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Ransomware</div>
            <div class="val" style="color:var(--red);">{{ $stats['ransomware'] }}</div>
            <div class="sub">Kasus berjalan</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Malware Dikarantina</div>
            <div class="val" style="color:var(--amber);">{{ $stats['malware_dikarantina'] }}</div>
            <div class="sub">Bulan ini</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Insiden Selesai</div>
            <div class="val" style="color:var(--green);">{{ $stats['insiden_selesai_bulan_ini'] }}</div>
            <div class="sub">Bulan ini</div>
          </div>
        </div>


        <div class="panel chart-box">
          <div class="chart-box-head-row">
            <div><h3 style="font-family:var(--display);font-size:17px;font-weight:700;">Analitik Penanganan Ancaman</h3><p style="font-size:12px;color:var(--text-muted);margin-top:2px;">Tingkat keparahan ancaman, komposisi jenis ancaman, dan progres penanganan insiden.</p></div>
            <div class="chart-filter-group">
              <select class="chart-type-select" id="chartDateFilterGlobal">
                <option value="7d">7 Hari Terakhir</option>
                <option value="30d">30 Hari Terakhir</option>
                <option value="90d">3 Bulan Terakhir</option>
                <option value="all" selected>Semua Waktu</option>
              </select>
              <select class="chart-type-select" id="chartTypeFilterGlobal">
                <option value="bar" selected>Grafik Batang</option>
                <option value="line">Grafik Garis</option>
                <option value="radar">Grafik Radar</option>
              </select>
            </div>
          </div>

          <div class="chart-box-grid" id="chartBoxGrid">

            <div class="chart-mini">
              <div class="chart-mini-head">
                <h4>Ancaman per Tingkat</h4><p>Kritis, Tinggi, Sedang, Rendah.</p>
              </div>
              <div class="chart-wrap"><canvas id="chartAncamanTingkat"></canvas></div>
            </div>

            <div class="chart-mini">
              <div class="chart-mini-head">
                <h4>Jenis Ancaman Terdeteksi</h4><p>Ransomware, malware, phishing, dsb.</p>
              </div>
              <div class="chart-wrap"><canvas id="chartJenisAncaman"></canvas></div>
            </div>

            <div class="chart-mini">
              <div class="chart-mini-head">
                <h4>Status Penanganan</h4><p>Berlangsung, Dalam Penanganan, Selesai.</p>
              </div>
              <div class="chart-wrap"><canvas id="chartStatusPenangananRindak"></canvas></div>
            </div>
          </div>
          <p class="chart-legend-note">Merah = ancaman tingkat kritis/tinggi, butuh penanganan segera. Ganti jenis grafik lewat dropdown di kanan atas — pilihan selain "Batang" akan otomatis memisah tiap grafik menjadi tampilan yang lebih besar. Filter tanggal masih simulasi proporsional karena histori ancaman per tanggal belum tersambung ke database.</p>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Insiden Terbaru</h3><p>Ancaman siber yang baru terdeteksi dan sedang ditangani.</p></div></div>
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Aset</th><th>Jenis Ancaman</th><th>Terdeteksi</th><th>Status</th></tr></thead>
              <tbody>
                @foreach($insidenTerbaru as $i)
                <tr>
                  <td>{{ $i['aset'] }}</td>
                  <td>{{ $i['jenis'] }}</td>
                  <td>{{ $i['waktu'] }}</td>
                  <td><span class="status-dot {{ $i['status_class'] }}">{{ $i['status'] }}</span></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== DETEKSI ANCAMAN ===== --}}
      <section class="tab-panel" data-tab-panel="ancaman">
        <div class="section-head">
          <h2>Deteksi Ancaman</h2>
          <p>Daftar ancaman siber yang terdeteksi sistem monitoring beserta tingkat keparahannya.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Aset Terdampak</th><th>Jenis Ancaman</th><th>Tingkat</th><th>Terdeteksi</th><th>Aksi</th></tr></thead>
              <tbody>
                @foreach($ancamanTerdeteksi as $a)
                <tr>
                  <td>{{ $a['nama'] }}</td>
                  <td style="color:var(--text-muted);">{{ $a['jenis'] }}</td>
                  <td><span class="status-dot {{ $a['tingkat_class'] }}">{{ $a['tingkat'] }}</span></td>
                  <td>{{ $a['terdeteksi'] }}</td>
                  <td>
                    <div class="btn-row">
                      @if($a['tingkat_class'] === 'bad')
                        <button class="btn btn-primary btn-sm" type="button">Isolasi & Tangani</button>
                      @else
                        <button class="btn btn-sm" type="button">Investigasi</button>
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

      {{-- ===== LOG PENANGANAN ===== --}}
      <section class="tab-panel" data-tab-panel="penanganan">
        <div class="section-head">
          <h2>Log Penanganan Insiden</h2>
          <p>Riwayat lengkap ancaman siber dan tindakan penanganan yang sudah dilakukan.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Aset</th><th>Jenis Ancaman</th><th>Waktu Insiden</th><th>Tindakan</th><th>Status</th></tr></thead>
              <tbody>
                @foreach($logPenanganan as $l)
                <tr>
                  <td>{{ $l['aset'] }}</td>
                  <td>{{ $l['jenis'] }}</td>
                  <td>{{ $l['waktu'] }}</td>
                  <td>{{ $l['tindakan'] }}</td>
                  <td><span class="badge {{ $l['status_class'] }}">{{ $l['status'] }}</span></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== LAPORAN › TAMBAH LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="tambah-laporan">
        <div class="section-head">
          <h2>Tambah Laporan</h2>
          <p>Catat insiden ancaman siber baru yang terdeteksi atau ditangani Satuan Pelaksanaan Penindakan.</p>
        </div>
        <div class="panel">
          <form class="form-grid" id="formTambahLaporan" style="padding:22px;" novalidate>
            <div class="form-field">
              <label for="asetTambahLaporan">Aset Terdampak</label>
              <select id="asetTambahLaporan" required>
                @foreach($ancamanTerdeteksi as $a)
                  <option>{{ $a['nama'] }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-field">
              <label for="prioritasTambahLaporan">Prioritas</label>
              <select id="prioritasTambahLaporan" required>
                <option>Tinggi</option><option>Sedang</option><option>Rendah</option>
              </select>
            </div>
            <div class="form-field full">
              <label for="jenisSeranganTambahLaporan">Klasifikasi Jenis Serangan</label>
              <select id="jenisSeranganTambahLaporan" required>
                <option value="">— Pilih klasifikasi —</option>
                <option>Ransomware</option>
                <option>Malware / Trojan</option>
                <option>Phishing</option>
                <option>DDoS (Distributed Denial of Service)</option>
                <option>Brute Force</option>
                <option>SQL Injection</option>
                <option>Defacement</option>
                <option>Social Engineering</option>
                <option>Lainnya</option>
              </select>
              <span class="form-hint">Menentukan alur investigasi & mitigasi yang dipakai.</span>
            </div>
            <div class="form-field full">
              <label for="perihalTambahLaporan">Perihal</label>
              <input id="perihalTambahLaporan" type="text" placeholder="Contoh: Indikasi ransomware mengenkripsi file bersama" required>
            </div>
            <div class="form-field full">
              <label for="deskripsiTambahLaporan">Deskripsi Kejadian</label>
              <textarea id="deskripsiTambahLaporan" rows="4" placeholder="Jelaskan kronologi dan dampak insiden..." required></textarea>
            </div>
            <div class="form-field full">
              <label for="lampiranTambahLaporan">Upload Bukti Digital</label>
              <input id="lampiranTambahLaporan" type="file" accept="application/pdf,.pdf,image/png,image/jpeg,.png,.jpg,.jpeg,.zip,.log" multiple>
              <span class="form-hint">Format PDF, gambar (PNG/JPG), file log, atau ZIP bukti forensik — maksimal 20 MB per file.</span>
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="button" onclick="alert('Prototype — form Tambah Laporan belum tersambung ke database.')">Simpan Laporan</button>
            </div>
          </form>
        </div>
      </section>

      {{-- ===== LAPORAN › STATUS LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="status-laporan">
        <div class="section-head">
          <h2>Status Laporan</h2>
          <p>Pantau progres laporan yang sudah diajukan oleh Satuan Pelaksanaan Penindakan.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Aset</th><th>Perihal</th><th>Tanggal</th><th>Status</th></tr></thead>
              <tbody>
                <tr>
                  <td>Server File Sharing Ditjen</td>
                  <td>Indikasi ransomware mengenkripsi file bersama</td>
                  <td>02 Agu 2026</td>
                  <td><span class="status-dot amber">Menunggu Verifikasi</span></td>
                </tr>
                <tr>
                  <td>Endpoint Staf Binmat #14</td>
                  <td>Trojan terdeteksi via antivirus terpusat</td>
                  <td>02 Agu 2026</td>
                  <td><span class="status-dot warn">Diteruskan ke DANPUS</span></td>
                </tr>
                <tr>
                  <td>Gateway Email Satuan Pelaksanaan Dukungan Teknologi</td>
                  <td>Phishing campaign berhasil diblokir</td>
                  <td>02 Agu 2026</td>
                  <td><span class="status-dot green">Disetujui DANPUS</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== LAPORAN › RIWAYAT LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="riwayat-laporan">
        <div class="section-head">
          <h2>Riwayat Laporan</h2>
          <p>Log lengkap ancaman dan tindak lanjut yang pernah ditangani Satuan Pelaksanaan Penindakan.</p>
        </div>
        <div class="panel">
          <div class="btn-row" style="margin-bottom:18px;">
            <button type="button" class="btn btn-sm" id="btnExportPdfRiwayat">
              <svg viewBox="0 0 24 24" width="14" height="14" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>
              Export PDF
            </button>
            <button type="button" class="btn btn-sm" id="btnExportExcelRiwayat">
              <svg viewBox="0 0 24 24" width="14" height="14" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M8 8l8 8M16 8l-8 8"></path></svg>
              Export Excel
            </button>
          </div>
          <div class="tbl-wrap">
            <table class="dtbl" id="tabelRiwayatLaporan">
              <thead><tr><th>Aset</th><th>Jenis Ancaman</th><th>Waktu</th><th>Tindakan</th><th>Status</th></tr></thead>
              <tbody>
                @foreach($logPenanganan as $l)
                <tr>
                  <td>{{ $l['aset'] }}</td>
                  <td>{{ $l['jenis'] }}</td>
                  <td>{{ $l['waktu'] }}</td>
                  <td>{{ $l['tindakan'] }}</td>
                  <td><span class="status-dot {{ $l['status_class'] }}">{{ $l['status'] }}</span></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <p class="form-hint" style="margin-top:12px;">Export mengambil data yang sedang tampil di tabel Riwayat Laporan.</p>
        </div>
      </section>

      <script>
      (function () {
        var table = document.getElementById('tabelRiwayatLaporan');
        var HEADER = ['Aset', 'Jenis Ancaman', 'Waktu', 'Tindakan', 'Status'];
        if (!table) return;

        function ambilBarisRiwayat() {
          var rows = [];
          table.querySelectorAll('tbody tr').forEach(function (tr) {
            var sel = [];
            tr.querySelectorAll('td').forEach(function (td) { sel.push(td.textContent.trim()); });
            rows.push(sel);
          });
          return rows;
        }

        var btnExcel = document.getElementById('btnExportExcelRiwayat');
        if (btnExcel) {
          btnExcel.addEventListener('click', function () {
            if (typeof XLSX === 'undefined') {
              alert('Pustaka export Excel belum termuat (periksa koneksi internet), coba lagi.');
              return;
            }
            var ws = XLSX.utils.aoa_to_sheet([HEADER].concat(ambilBarisRiwayat()));
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Riwayat Laporan');
            XLSX.writeFile(wb, 'riwayat-laporan-satlak-penindakan.xlsx');
          });
        }

        var btnPdf = document.getElementById('btnExportPdfRiwayat');
        if (btnPdf) {
          btnPdf.addEventListener('click', function () {
            if (typeof window.jspdf === 'undefined') {
              alert('Pustaka export PDF belum termuat (periksa koneksi internet), coba lagi.');
              return;
            }
            var doc = new window.jspdf.jsPDF();
            doc.setFontSize(14);
            doc.text('Riwayat Laporan — Satuan Pelaksanaan Penindakan', 14, 16);
            doc.setFontSize(10);
            doc.text('Dicetak: ' + new Date().toLocaleString('id-ID'), 14, 23);
            doc.autoTable({
              startY: 29,
              head: [HEADER],
              body: ambilBarisRiwayat(),
              styles: { fontSize: 8.5 },
              headStyles: { fillColor: [212, 175, 55], textColor: [36, 26, 5] }
            });
            doc.save('riwayat-laporan-satlak-penindakan.pdf');
          });
        }
      })();
      </script>

      {{-- ===== MANAJEMEN INVESTIGASI ===== --}}
      <section class="tab-panel" data-tab-panel="investigasi">
        <div class="section-head">
          <h2>Manajemen Investigasi</h2>
          <p>Kasus yang sedang atau sudah diinvestigasi oleh tim Satuan Pelaksanaan Penindakan.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>No. Kasus</th><th>Aset</th><th>Jenis Ancaman</th><th>Investigator</th><th>Mulai</th><th>Progres</th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody>
                @foreach($investigasi as $inv)
                <tr>
                  <td style="font-family:var(--mono);">{{ $inv['kasus'] }}</td>
                  <td>{{ $inv['aset'] }}</td>
                  <td style="color:var(--text-muted);">{{ $inv['jenis'] }}</td>
                  <td>{{ $inv['investigator'] }}</td>
                  <td>{{ $inv['mulai'] }}</td>
                  <td>
                    <div class="progress-row">
                      <div class="progress-track"><div class="progress-fill" style="width:{{ $inv['progres'] }}%;"></div></div>
                      <span>{{ $inv['progres'] }}%</span>
                    </div>
                  </td>
                  <td><span class="badge {{ $inv['status_class'] }}">{{ $inv['status'] }}</span></td>
                  <td>
                    <div class="btn-row">
                      <button class="btn btn-sm" type="button" onclick="alert('Prototype — detail investigasi {{ $inv['kasus'] }} belum tersambung ke database.')">Lihat Detail</button>
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== TIMELINE PENANGANAN ===== --}}
      <section class="tab-panel" data-tab-panel="timeline-penanganan">
        <div class="section-head">
          <h2>Timeline Penanganan</h2>
          <p>Kronologi penanganan kasus prioritas tertinggi yang sedang berjalan — Server File Sharing Ditjen (INV-2026-014).</p>
        </div>
        <div class="panel">
          <div class="timeline">
            @foreach($timelinePenanganan as $t)
            <div class="timeline-item {{ $t['state'] }}">
              <div class="timeline-marker">
                <div class="timeline-dot"></div>
                <div class="timeline-line"></div>
              </div>
              <div class="timeline-content">
                <div class="timeline-time">{{ $t['waktu'] }}</div>
                <div class="timeline-title">{{ $t['judul'] }}</div>
                <div class="timeline-desc">{{ $t['deskripsi'] }}</div>
              </div>
            </div>
            @endforeach
          </div>
          <p class="form-hint" style="margin-top:16px;">Prototype — timeline masih statis untuk satu kasus contoh. Pemilihan kasus lain lewat dropdown belum tersambung ke database.</p>
        </div>
      </section>

      {{-- ===== MANAJEMEN MITIGASI ===== --}}
      <section class="tab-panel" data-tab-panel="mitigasi">
        <div class="section-head">
          <h2>Manajemen Mitigasi</h2>
          <p>Tindak lanjut mitigasi untuk setiap insiden setelah investigasi awal dilakukan.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Aset</th><th>Ancaman</th><th>Tindakan Mitigasi</th><th>Penanggung Jawab</th><th>Tenggat</th><th>Status</th></tr></thead>
              <tbody>
                @foreach($mitigasi as $m)
                <tr>
                  <td>{{ $m['aset'] }}</td>
                  <td style="color:var(--text-muted);">{{ $m['ancaman'] }}</td>
                  <td>{{ $m['tindakan'] }}</td>
                  <td>{{ $m['penanggung_jawab'] }}</td>
                  <td>{{ $m['tenggat'] }}</td>
                  <td><span class="badge {{ $m['status_class'] }}">{{ $m['status'] }}</span></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </section>

    </div>

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

{{-- ===== ANALITIK PENANGANAN ANCAMAN (RINGKASAN) ===== --}}
<script>
(function () {
  var canvasCheck = document.getElementById('chartAncamanTingkat');
  if (!canvasCheck || typeof Chart === 'undefined') return;

  var css = getComputedStyle(document.documentElement);
  var cGold = css.getPropertyValue('--gold-bright').trim() || '#f2c14e';
  var cGreen = css.getPropertyValue('--green').trim() || '#3ddc84';
  var cAmber = css.getPropertyValue('--amber').trim() || '#f2a93b';
  var cRed = css.getPropertyValue('--red').trim() || '#e5484d';
  var cMuted = css.getPropertyValue('--text-dim').trim() || '#7d8f87';
  var cText = css.getPropertyValue('--text').trim() || '#e8efe9';
  var cBorder = css.getPropertyValue('--border-soft').trim() || '#22302a';

  var ancamanTingkat = @json($ancamanPerTingkat ?? []);
  var jenisAncaman = @json($jenisAncamanChart ?? []);
  var statusPenangananRindak = @json($statusPenangananChart ?? []);

  var registry = {};

  function wrapLabel(text, maxCharsPerLine) {
    maxCharsPerLine = maxCharsPerLine || 14;
    var words = String(text).split(' ');
    var lines = [];
    var current = '';
    words.forEach(function (w) {
      var test = current ? current + ' ' + w : w;
      if (test.length > maxCharsPerLine && current) {
        lines.push(current);
        current = w;
      } else {
        current = test;
      }
    });
    if (current) lines.push(current);
    return lines;
  }

  function buildOptions(type, opts) {
    opts = opts || {};
    if (type === 'radar') {
      return {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: 0 },
        plugins: { legend: { display: false, position: 'bottom', labels: { color: cText, boxWidth: 9, padding: 10 } } },
        scales: {
          r: {
            min: 0, max: opts.max || 100,
            grid: { color: cBorder }, angleLines: { color: cBorder },
            pointLabels: { color: cMuted, font: { size: 10 } },
            ticks: { display: false, backdropColor: 'transparent' }
          }
        }
      };
    }
    if (type === 'doughnut') {
      return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: { legend: { position: 'bottom', labels: { color: cText, boxWidth: 10, padding: 12 } } }
      };
    }
    return {
      indexAxis: opts.horizontal ? 'y' : 'x',
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { left: 4, right: 12, top: 8, bottom: 0 } },
      plugins: { legend: { display: false } },
      scales: {
        x: opts.horizontal
          ? { min: 0, grid: { color: cBorder }, ticks: { precision: 0 } }
          : { offset: false, grid: { display: false }, ticks: { maxRotation: 0, minRotation: 0, autoSkip: false, font: { size: 10 } } },
        y: opts.horizontal
          ? { offset: false, grid: { display: false }, ticks: { autoSkip: false } }
          : { min: 0, grid: { color: cBorder }, ticks: { precision: 0 } }
      }
    };
  }

  function renderChart(canvasId, type, rawLabels, values, colors, opts) {
    var el = document.getElementById(canvasId);
    if (!el) return;
    if (registry[canvasId]) registry[canvasId].destroy();

    var labels = (type !== 'doughnut' && !opts.horizontal)
      ? rawLabels.map(function (l) { return wrapLabel(l, 14); })
      : rawLabels;

    var isFillType = (type === 'doughnut' || type === 'radar');
    var fillColor = (type === 'radar') ? hexToRgba(opts.lineColor || cGold, 0.28) : colors;

    var dataset = {
      label: opts.label || '',
      data: values,
      backgroundColor: isFillType ? fillColor : colors,
      borderColor: type === 'line' ? (opts.lineColor || cGold) : (type === 'radar' ? (opts.lineColor || cGold) : 'transparent'),
      borderWidth: (type === 'line' || type === 'radar') ? 2 : 0,
      borderRadius: (type === 'bar') ? 4 : 0,
      maxBarThickness: opts.horizontal ? 34 : 44,
      fill: type === 'radar' ? true : (type === 'line' ? false : undefined),
      tension: 0,
      pointBackgroundColor: (type === 'line' || type === 'radar') ? (opts.lineColor || cGold) : undefined,
      pointRadius: type === 'line' ? 3 : undefined,
    };

    registry[canvasId] = new Chart(el, {
      type: type,
      data: { labels: labels, datasets: [dataset] },
      options: buildOptions(type, opts)
    });
  }

  function hexToRgba(hex, alpha) {
    hex = (hex || '').replace('#', '');
    if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
    var r = parseInt(hex.substring(0, 2), 16) || 0;
    var g = parseInt(hex.substring(2, 4), 16) || 0;
    var b = parseInt(hex.substring(4, 6), 16) || 0;
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  function drawAncamanTingkat(type, data) {
    renderChart(
      'chartAncamanTingkat', type,
      data.map(function (s) { return s.label; }),
      data.map(function (s) { return s.jumlah; }),
      type === 'line' || type === 'radar' ? cGold : [cRed, cRed, cAmber, cGreen],
      { label: 'Jumlah Ancaman', lineColor: cGold }
    );
  }

  function drawJenisAncaman(type, data) {
    var t = type === 'bar' ? 'doughnut' : type;
    renderChart(
      'chartJenisAncaman', t,
      data.map(function (s) { return s.jenis; }),
      data.map(function (s) { return s.jumlah; }),
      [cRed, cAmber, cGold, cGreen],
      { label: 'Jumlah' }
    );
  }

  function drawStatusPenangananRindak(type, data) {
    renderChart(
      'chartStatusPenangananRindak', type,
      data.map(function (p) { return p.label; }),
      data.map(function (p) { return p.jumlah; }),
      type === 'line' || type === 'radar' ? cGold : [cAmber, cAmber, cGreen],
      { label: 'Jumlah Insiden', lineColor: cGold }
    );
  }

  var DATE_RANGE_FACTOR = { '7d': 0.35, '30d': 0.7, '90d': 0.9, 'all': 1 };

  function scaleFactor(arr, factor, keyValue) {
    return arr.map(function (d) {
      var c = Object.assign({}, d);
      c[keyValue] = Math.max(0, Math.round(d[keyValue] * factor));
      return c;
    });
  }

  var typeFilterEl = document.getElementById('chartTypeFilterGlobal');
  var dateFilterEl = document.getElementById('chartDateFilterGlobal');
  var gridEl = document.getElementById('chartBoxGrid');

  function redrawAll() {
    var type = typeFilterEl ? typeFilterEl.value : 'bar';
    var factor = DATE_RANGE_FACTOR[dateFilterEl ? dateFilterEl.value : 'all'] || 1;
    if (gridEl) gridEl.classList.toggle('split-mode', type !== 'bar');

    drawAncamanTingkat(type, scaleFactor(ancamanTingkat, factor, 'jumlah'));
    drawJenisAncaman(type, scaleFactor(jenisAncaman, factor, 'jumlah'));
    drawStatusPenangananRindak(type, scaleFactor(statusPenangananRindak, factor, 'jumlah'));
  }

  redrawAll();

  if (typeFilterEl) typeFilterEl.addEventListener('change', redrawAll);
  if (dateFilterEl) dateFilterEl.addEventListener('change', redrawAll);
})();
</script>

@include('siberad.dashboards.partials.dash-script')
</body>
</html>