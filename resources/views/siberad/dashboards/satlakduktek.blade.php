<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Satuan Pelaksanaan Dukungan Teknologi — SIBERAD</title>
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo-pussiberad.jpg') }}">
@include('siberad.dashboards.partials.dash-styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
  .chart-box{margin-bottom:26px;}
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

  /* Mode terpisah — dipicu otomatis saat jenis grafik diganti selain "Batang" */
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
      <a href="#" class="side-link" data-tab-link="proyek"><span class="dot"></span>Riset & Pengembangan</a>
      <a href="#" class="side-link" data-tab-link="uji"><span class="dot"></span>Log Uji & Pengembangan</a>
      <a href="#" class="side-link" data-tab-link="dukungan-teknis"><span class="dot"></span>Log Dukungan Teknis</a>

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
    var dropdown = document.getElementById('laporanDropdown');
    var toggle = document.getElementById('laporanToggle');
    if (!dropdown || !toggle) return;

    var subActive = dropdown.querySelector('.side-sublink.active');
    if (subActive) dropdown.classList.add('open');

    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      var isOpen = dropdown.classList.toggle('open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
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
          <h2>Ringkasan Riset & Pengembangan</h2>
          <p>Status proyek teknologi yang dikerjakan Satuan Pelaksanaan Dukungan Teknologi saat ini.</p>
        </div>
        <div class="stat-grid">
          <div class="stat-card">
            <div class="lbl">Proyek Aktif</div>
            <div class="val">{{ $stats['proyek_aktif'] }}</div>
            <div class="sub">Sedang dikerjakan</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Proyek AI</div>
            <div class="val" style="color:var(--green);">{{ $stats['proyek_ai'] }}</div>
            <div class="sub">Machine learning & NLP</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Unit Drone Diuji</div>
            <div class="val" style="color:var(--amber);">{{ $stats['unit_drone_uji'] }}</div>
            <div class="sub">Tahap uji lapangan</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Prototipe Selesai</div>
            <div class="val" style="color:var(--green);">{{ $stats['prototipe_selesai'] }}</div>
            <div class="sub">Bulan ini</div>
          </div>
        </div>

        <div class="panel chart-box">
          <div class="chart-box-head-row">
            <div><h3 style="font-family:var(--display);font-size:17px;font-weight:700;">Analitik Riset & Perbandingan Satlak</h3><p style="font-size:12px;color:var(--text-muted);margin-top:2px;">Progres proyek, komposisi kategori, dan posisi Satuan Pelaksanaan Dukungan Teknologi dibanding tiga Satlak lainnya.</p></div>
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
                <h4>Progres Proyek Riset</h4><p>Per proyek yang ditangani.</p>
              </div>
              <div class="chart-wrap"><canvas id="chartProgresProyek"></canvas></div>
            </div>

            <div class="chart-mini">
              <div class="chart-mini-head">
                <h4>Distribusi Kategori</h4><p>Komposisi bidang riset.</p>
              </div>
              <div class="chart-wrap"><canvas id="chartKategoriProyek"></canvas></div>
            </div>

            <div class="chart-mini">
              <div class="chart-mini-head">
                <h4>Perbandingan Antar Satlak</h4><p>Duktek jadi acuan utama.</p>
              </div>
              <div class="chart-wrap"><canvas id="chartPerbandinganSatlak"></canvas></div>
            </div>

          </div>
          <p class="chart-legend-note">Emas = Satuan Pelaksanaan Dukungan Teknologi, disorot sebagai Satlak yang paling krusial pada riset & pengembangan teknologi. Ganti jenis grafik lewat dropdown di kanan atas — pilihan selain "Batang" akan otomatis memisah tiap grafik menjadi tampilan yang lebih besar. Filter tanggal masih simulasi proporsional karena histori progres per tanggal belum tersambung ke database.</p>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Aktivitas Terbaru</h3><p>Kegiatan riset & pengembangan yang baru berlangsung.</p></div></div>
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Proyek</th><th>Kegiatan</th><th>Waktu</th><th>Status</th></tr></thead>
              <tbody>
                @foreach($aktivitasTerbaru as $i)
                <tr>
                  <td>{{ $i['proyek'] }}</td>
                  <td>{{ $i['kegiatan'] }}</td>
                  <td>{{ $i['waktu'] }}</td>
                  <td><span class="status-dot {{ $i['status_class'] }}">{{ $i['status'] }}</span></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== PROYEK RISET ===== --}}
      <section class="tab-panel" data-tab-panel="proyek">
        <div class="section-head">
          <h2>Proyek Riset & Pengembangan</h2>
          <p>Daftar proyek teknologi beserta progres dan target penyelesaian.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif

        <div class="panel">
          <div class="panel-head"><div><h3>Tambah Proyek Baru</h3></div></div>
          <form class="form-grid" method="POST" action="{{ route('proyek-riset.store') }}" style="padding:22px;">
            @csrf
            <div class="form-field full">
              <label for="prNama">Nama Proyek</label>
              <input id="prNama" name="nama" type="text" maxlength="255" required>
            </div>
            <div class="form-field">
              <label for="prKategori">Kategori</label>
              <input id="prKategori" name="kategori" type="text" maxlength="100" required placeholder="mis. AI / Machine Learning">
            </div>
            <div class="form-field">
              <label for="prProgres">Progres (%)</label>
              <input id="prProgres" name="progres" type="number" min="0" max="100" value="0" required>
            </div>
            <div class="form-field">
              <label for="prStatus">Status</label>
              <select id="prStatus" name="status" required>
                <option value="Riset Awal">Riset Awal</option>
                <option value="Berjalan">Berjalan</option>
                <option value="Selesai">Selesai</option>
              </select>
            </div>
            <div class="form-field">
              <label for="prTarget">Target Selesai</label>
              <input id="prTarget" name="target_selesai" type="text" maxlength="50" placeholder="mis. Sep 2026">
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="submit">Simpan Proyek</button>
            </div>
          </form>
        </div>

        <div class="panel" style="margin-top:22px;">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Nama Proyek</th><th>Kategori</th><th>Progres</th><th>Status</th><th>Target</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($proyekRiset as $p)
                <tr>
                  <td>{{ $p['nama'] }}</td>
                  <td style="color:var(--text-muted);">{{ $p['kategori'] }}</td>
                  <td style="font-family:var(--mono);">{{ $p['progres'] }}%</td>
                  <td><span class="status-dot {{ $p['status_class'] }}">{{ $p['status'] }}</span></td>
                  <td>{{ $p['target_selesai'] ?? '-' }}</td>
                  <td>
                    <form method="POST" action="{{ route('proyek-riset.destroy', $p) }}" onsubmit="return confirm('Hapus proyek ini? Log uji terkait juga akan ikut terlepas.');">
                      @csrf @method('DELETE')
                      <button class="btn btn-ghost-red btn-sm" type="submit">Hapus</button>
                    </form>
                  </td>
                </tr>
                @empty
                <tr class="table-empty-row"><td colspan="6">Belum ada proyek riset.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== LOG UJI & PENGEMBANGAN ===== --}}
      <section class="tab-panel" data-tab-panel="uji">
        <div class="section-head">
          <h2>Log Uji & Pengembangan</h2>
          <p>Riwayat pengujian prototipe dan hasil yang didapat.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif

        <div class="panel">
          <div class="panel-head"><div><h3>Catat Uji Baru</h3></div></div>
          <form class="form-grid" method="POST" action="{{ route('log-uji.store') }}" style="padding:22px;">
            @csrf
            <div class="form-field">
              <label for="ljProyek">Proyek Terkait (opsional)</label>
              <select id="ljProyek" name="proyek_riset_id">
                <option value="">— Tidak terkait proyek tertentu —</option>
                @foreach($proyekRiset as $p)
                  <option value="{{ $p->id }}">{{ $p->nama }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-field">
              <label for="ljStatus">Status</label>
              <select id="ljStatus" name="status" required>
                <option value="Selesai">Selesai</option>
                <option value="Perlu Tindak Lanjut">Perlu Tindak Lanjut</option>
              </select>
            </div>
            <div class="form-field full">
              <label for="ljKegiatan">Kegiatan Uji</label>
              <input id="ljKegiatan" name="kegiatan" type="text" maxlength="255" required>
            </div>
            <div class="form-field full">
              <label for="ljHasil">Hasil</label>
              <textarea id="ljHasil" name="hasil" rows="2"></textarea>
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="submit">Simpan Log Uji</button>
            </div>
          </form>
        </div>

        <div class="panel" style="margin-top:22px;">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Proyek</th><th>Kegiatan Uji</th><th>Waktu</th><th>Hasil</th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($logUji as $l)
                <tr>
                  <td>{{ $l->proyekRiset->nama ?? '-' }}</td>
                  <td>{{ $l->kegiatan }}</td>
                  <td>{{ $l->waktu_uji?->format('d M Y, H:i') ?? '-' }}</td>
                  <td style="color:var(--text-muted);">{{ $l->hasil }}</td>
                  <td><span class="badge {{ $l->status_class }}">{{ $l->status }}</span></td>
                  <td>
                    <form method="POST" action="{{ route('log-uji.destroy', $l) }}" onsubmit="return confirm('Hapus log ini?');">
                      @csrf @method('DELETE')
                      <button class="btn btn-ghost-red btn-sm" type="submit">Hapus</button>
                    </form>
                  </td>
                </tr>
                @empty
                <tr class="table-empty-row"><td colspan="6">Belum ada log uji & pengembangan.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== LOG DUKUNGAN TEKNIS ===== --}}
      <section class="tab-panel" data-tab-panel="dukungan-teknis">
        <div class="section-head">
          <h2>Log Dukungan Teknis</h2>
          <p>Catatan bantuan teknis yang diberikan ke Satuan Pelaksanaan Penangkalan, Satuan Pelaksanaan Siber Sosial, dan Satuan Pelaksanaan Penindakan.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif

        <div class="panel">
          <div class="panel-head"><div><h3>Catat Dukungan Baru</h3></div></div>
          <form class="form-grid" method="POST" action="{{ route('dukungan-teknis.store') }}" style="padding:22px;">
            @csrf
            <div class="form-field">
              <label for="dtSatuan">Satlak Penerima</label>
              <select id="dtSatuan" name="satuan_tujuan_id" required>
                <option value="">Pilih satuan...</option>
                @foreach($satuanTujuanDukungan as $s)
                  <option value="{{ $s->id }}">{{ $s->nama }} ({{ $s->kode }})</option>
                @endforeach
              </select>
            </div>
            <div class="form-field">
              <label for="dtJenis">Jenis Bantuan</label>
              <input id="dtJenis" name="jenis_bantuan" type="text" maxlength="150" required placeholder="mis. Setup tools monitoring">
            </div>
            <div class="form-field full">
              <label for="dtKeterangan">Keterangan</label>
              <textarea id="dtKeterangan" name="keterangan" rows="3"></textarea>
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="submit">Simpan Log Dukungan</button>
            </div>
          </form>
        </div>

        <div class="panel" style="margin-top:22px;">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Satlak Penerima</th><th>Jenis Bantuan</th><th>Keterangan</th><th>Tanggal</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($dukunganTeknis as $d)
                <tr>
                  <td>{{ $d->satuanTujuan->nama ?? '-' }} <span class="badge">{{ $d->satuanTujuan->kode ?? '' }}</span></td>
                  <td>{{ $d->jenis_bantuan }}</td>
                  <td style="color:var(--text-muted);">{{ $d->keterangan }}</td>
                  <td>{{ $d->created_at->format('d M Y') }}</td>
                  <td>
                    <form method="POST" action="{{ route('dukungan-teknis.destroy', $d) }}" onsubmit="return confirm('Hapus log ini?');">
                      @csrf @method('DELETE')
                      <button class="btn btn-ghost-red btn-sm" type="submit">Hapus</button>
                    </form>
                  </td>
                </tr>
                @empty
                <tr class="table-empty-row"><td colspan="5">Belum ada log dukungan teknis.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== LAPORAN › TAMBAH LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="tambah-laporan">
        <div class="section-head">
          <h2>Tambah Laporan</h2>
          <p>Catat kendala, kebutuhan, atau perkembangan baru dari proyek riset dan pengembangan. Laporan dikirim langsung ke DANPUS.</p>
        </div>
        <div class="panel">
          @if(session('status'))
          <div class="profile-form-notice" style="margin:22px 22px 0;border-color:var(--green);color:var(--green);">{{ session('status') }}</div>
          @endif
          @if($errors->any())
          <div class="profile-form-notice" style="margin:22px 22px 0;border-color:var(--red);color:var(--red);">
            {{ $errors->first() }}
          </div>
          @endif
          <form class="form-grid" id="formTambahLaporan" method="POST" action="{{ route('laporan.store') }}" enctype="multipart/form-data" style="padding:22px;">
            @csrf
            <div class="form-field">
              <label for="proyekTambahLaporan">Proyek Terkait</label>
              <select id="proyekTambahLaporan" name="proyek" required>
                @foreach($proyekRiset as $p)
                  <option value="{{ $p['nama'] }}">{{ $p['nama'] }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-field">
              <label for="prioritasTambahLaporan">Prioritas</label>
              <select id="prioritasTambahLaporan" name="prioritas" required>
                <option value="Tinggi">Tinggi</option>
                <option value="Sedang">Sedang</option>
                <option value="Rendah">Rendah</option>
              </select>
            </div>
            <div class="form-field full">
              <label for="perihalTambahLaporan">Perihal</label>
              <input id="perihalTambahLaporan" name="perihal" type="text" placeholder="Contoh: Pengajuan anggaran komponen gimbal kamera baru" required>
            </div>
            <div class="form-field full">
              <label for="deskripsiTambahLaporan">Deskripsi Kejadian</label>
              <textarea id="deskripsiTambahLaporan" name="deskripsi" rows="4" placeholder="Jelaskan kronologi dan dampaknya terhadap proyek..." required></textarea>
            </div>
            <div class="form-field full">
              <label for="lampiranTambahLaporan">Lampiran (bukti / dokumentasi)</label>
              <input id="lampiranTambahLaporan" name="lampiran" type="file" accept="application/pdf,.pdf">
              <span class="form-hint">Format PDF, maksimal 20 MB.</span>
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="submit">Kirim Laporan ke DANPUS</button>
            </div>
          </form>
        </div>
      </section>

      {{-- ===== LAPORAN › STATUS LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="status-laporan">
        <div class="section-head">
          <h2>Status Laporan</h2>
          <p>Pantau progres laporan yang sudah diajukan oleh Satuan Pelaksanaan Dukungan Teknologi ke DANPUS.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Proyek</th><th>Perihal</th><th>Tanggal</th><th>Status</th></tr></thead>
              <tbody>
                @forelse($laporanTerkirim as $l)
                <tr>
                  <td>{{ $l->proyek ?? '—' }}</td>
                  <td>{{ $l->perihal }}</td>
                  <td>{{ $l->created_at->translatedFormat('d M Y') }}</td>
                  <td>
                    <span class="status-dot {{ match($l->status) {
                      'Disetujui DANPUS' => 'green',
                      'Ditolak DANPUS' => 'bad',
                      default => 'amber',
                    } }}">{{ $l->status }}</span>
                  </td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;color:var(--text-muted);">Belum ada laporan yang dikirim ke DANPUS.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== LAPORAN › RIWAYAT LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="riwayat-laporan">
        <div class="section-head">
          <h2>Riwayat Laporan</h2>
          <p>Log lengkap kegiatan uji dan pengembangan yang pernah ditangani Satuan Pelaksanaan Dukungan Teknologi.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Proyek</th><th>Kegiatan</th><th>Waktu</th><th>Hasil</th><th>Status</th></tr></thead>
              <tbody>
                @foreach($logUji as $l)
                <tr>
                  <td>{{ $l['proyek'] }}</td>
                  <td>{{ $l['kegiatan'] }}</td>
                  <td>{{ $l['waktu'] }}</td>
                  <td>{{ $l['hasil'] }}</td>
                  <td><span class="status-dot {{ $l['status_class'] }}">{{ $l['status'] }}</span></td>
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

<script>
(function () {
  if (typeof Chart === 'undefined') return;

  var root = getComputedStyle(document.documentElement);
  var cGold = root.getPropertyValue('--gold-bright').trim() || '#f3cd5c';
  var cGreen = root.getPropertyValue('--green-bright').trim() || '#3fc27d';
  var cAmber = root.getPropertyValue('--amber').trim() || '#e0a83a';
  var cMuted = root.getPropertyValue('--text-muted').trim() || '#9fb3a5';
  var cBorder = root.getPropertyValue('--border-soft').trim() || 'rgba(212,175,55,.13)';
  var cText = root.getPropertyValue('--text').trim() || '#f4f1e6';

  Chart.defaults.color = cMuted;
  Chart.defaults.font.family = "'JetBrains Mono', monospace";
  Chart.defaults.font.size = 11;

  // Ubah warna hex jadi rgba supaya area fill (misal grafik radar) bisa transparan, nggak solid penuh.
  function hexToRgba(hex, alpha) {
    hex = String(hex).trim().replace('#', '');
    if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
    var r = parseInt(hex.substring(0, 2), 16);
    var g = parseInt(hex.substring(2, 4), 16);
    var b = parseInt(hex.substring(4, 6), 16);
    if (isNaN(r) || isNaN(g) || isNaN(b)) return 'rgba(212,175,55,' + alpha + ')'; // fallback warna emas
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  var proyekRiset = @json($proyekRiset);
  var kategoriDistribusi = @json($kategoriDistribusi);
  var perbandinganSatlak = @json($perbandinganSatlak);

  var registry = {}; // simpan instance Chart per canvas id, biar bisa di-destroy saat ganti jenis

  // Pecah label panjang jadi beberapa baris (bukan dirotasi/dimiringkan), biar tetap presisi dan jelas dibaca.
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

  // Beberapa jenis grafik (radar, polarArea) butuh scale berbeda dari bar/line biasa,
  // jadi opsi dibangun ulang tiap kali jenis grafik diganti lewat fungsi ini.
  function buildOptions(type, opts) {
    opts = opts || {};
    if (type === 'radar' || type === 'polarArea') {
      return {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: 0 },
        plugins: { legend: { display: type === 'polarArea', position: 'bottom', labels: { color: cText, boxWidth: 9, padding: 10 } } },
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
    if (type === 'doughnut' || type === 'pie') {
      return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: type === 'doughnut' ? '62%' : 0,
        plugins: { legend: { position: 'bottom', labels: { color: cText, boxWidth: 10, padding: 12 } } }
      };
    }
    // bar / line — sumbu kategori dibuat offset:false & tanpa rotasi supaya grafik presisi penuh dari kiri ke kanan.
    return {
      indexAxis: opts.horizontal ? 'y' : 'x',
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { left: 4, right: 12, top: 8, bottom: 0 } },
      plugins: { legend: { display: false } },
      scales: {
        x: opts.horizontal
          ? { min: 0, max: opts.max || 100, grid: { color: cBorder }, ticks: { callback: function (v) { return v + '%'; } } }
          : { offset: false, grid: { display: false }, ticks: { maxRotation: 0, minRotation: 0, autoSkip: false, font: { size: 10 } } },
        y: opts.horizontal
          ? { offset: false, grid: { display: false }, ticks: { autoSkip: false } }
          : { min: 0, max: opts.max || 100, grid: { color: cBorder }, ticks: { callback: function (v) { return v + '%'; } } }
      }
    };
  }

  function renderChart(canvasId, type, rawLabels, values, colors, opts) {
    var el = document.getElementById(canvasId);
    if (!el) return;
    if (registry[canvasId]) registry[canvasId].destroy();

    // Label dipecah jadi beberapa baris untuk sumbu kategori vertikal (x), supaya tidak perlu dimiringkan.
    var labels = (type !== 'doughnut' && type !== 'pie' && !opts.horizontal)
      ? rawLabels.map(function (l) { return wrapLabel(l, 14); })
      : rawLabels;

    var isFillType = (type === 'doughnut' || type === 'pie' || type === 'polarArea' || type === 'radar');

    // Khusus radar: area fill dibuat transparan (bukan solid) supaya garis & grid di baliknya tetap kebaca.
    var fillColor = (type === 'radar')
      ? hexToRgba(opts.lineColor || cGold, 0.28)
      : colors;

    var dataset = {
      label: opts.label || '',
      data: values,
      backgroundColor: isFillType ? fillColor : colors,
      borderColor: type === 'line' ? (opts.lineColor || cGold) : (type === 'radar' ? (opts.lineColor || cGold) : 'transparent'),
      borderWidth: (type === 'line' || type === 'radar') ? 2 : 0,
      borderRadius: (type === 'bar') ? 4 : 0,
      maxBarThickness: opts.horizontal ? 34 : 44,
      fill: type === 'radar' ? true : (type === 'line' ? false : undefined),
      tension: 0, // garis lurus & presisi, hindari lengkungan berlebihan (overshoot) yang bikin grafik terlihat miring
      pointBackgroundColor: (type === 'line' || type === 'radar') ? (opts.lineColor || cGold) : undefined,
      pointRadius: type === 'line' ? 3 : undefined,
    };

    registry[canvasId] = new Chart(el, {
      type: type,
      data: { labels: labels, datasets: [dataset] },
      options: buildOptions(type, opts)
    });
  }

  // Faktor simulasi per rentang tanggal — dashboard ini masih prototype (data belum tersambung
  // ke histori tanggal di database), jadi rentang waktu disimulasikan secara proporsional dari
  // data progres saat ini. Kalau nanti sudah ada log progres bertanggal di database, bagian ini
  // tinggal diganti pemanggilan data asli sesuai rentang yang dipilih.
  var DATE_RANGE_FACTOR = { '7d': 0.35, '30d': 0.7, '90d': 0.9, 'all': 1 };

  function scaledProyekRiset(factor) {
    return proyekRiset.map(function (p) {
      return { nama: p.nama, progres: Math.min(100, Math.round(p.progres * factor)) };
    });
  }
  function scaledKategoriDistribusi(factor) {
    return kategoriDistribusi.map(function (k) {
      return { kategori: k.kategori, jumlah: Math.max(0, Math.round(k.jumlah * factor)) };
    });
  }
  function scaledPerbandinganSatlak(factor) {
    return perbandinganSatlak.map(function (s) {
      return { singkatan: s.singkatan, progres: Math.min(100, Math.round(s.progres * factor)), highlight: s.highlight };
    });
  }

  // ===== Grafik 1: Progres tiap proyek riset =====
  function drawProgresProyek(type, data) {
    renderChart(
      'chartProgresProyek', type,
      data.map(function (p) { return p.nama; }),
      data.map(function (p) { return p.progres; }),
      type === 'line' || type === 'radar'
        ? cGold
        : data.map(function (p) { return p.progres >= 100 ? cGreen : (p.progres >= 50 ? cAmber : cMuted); }),
      { horizontal: type === 'bar', max: 100, label: 'Progres (%)', lineColor: cGold }
    );
  }

  // ===== Grafik 2: Distribusi kategori proyek =====
  function drawKategoriProyek(type, data) {
    renderChart(
      'chartKategoriProyek', type,
      data.map(function (k) { return k.kategori; }),
      data.map(function (k) { return k.jumlah; }),
      [cGold, cGreen, cAmber, cMuted],
      { label: 'Jumlah Proyek', max: Math.max.apply(null, data.map(function (k) { return k.jumlah; })) + 1 }
    );
  }

  // ===== Grafik 3: Perbandingan progres antar Satlak =====
  function drawPerbandinganSatlak(type, data) {
    renderChart(
      'chartPerbandinganSatlak', type,
      data.map(function (s) { return s.singkatan; }),
      data.map(function (s) { return s.progres; }),
      type === 'line' || type === 'radar'
        ? cGold
        : data.map(function (s) { return s.highlight ? cGold : cMuted; }),
      { horizontal: false, max: 100, label: 'Progres Aktivitas (%)', lineColor: cGold }
    );
  }

  var typeFilterEl = document.getElementById('chartTypeFilterGlobal');
  var dateFilterEl = document.getElementById('chartDateFilterGlobal');
  var gridEl = document.getElementById('chartBoxGrid');

  function redrawAll() {
    var type = typeFilterEl ? typeFilterEl.value : 'bar';
    var factor = DATE_RANGE_FACTOR[dateFilterEl ? dateFilterEl.value : 'all'] || 1;

    // "Batang" = tampilan default (gabung 1 kotak). Selain itu = mode terpisah, tiap grafik jadi besar.
    if (gridEl) gridEl.classList.toggle('split-mode', type !== 'bar');

    drawProgresProyek(type, scaledProyekRiset(factor));
    // Grafik distribusi kategori tetap dalam bentuk lingkaran kalau filternya "Batang" (default),
    // tapi ikut berubah ke garis/radar juga saat filter jenis grafik diubah.
    drawKategoriProyek(type === 'bar' ? 'doughnut' : type, scaledKategoriDistribusi(factor));
    drawPerbandinganSatlak(type, scaledPerbandinganSatlak(factor));
  }

  redrawAll();

  if (typeFilterEl) typeFilterEl.addEventListener('change', redrawAll);
  if (dateFilterEl) dateFilterEl.addEventListener('change', redrawAll);
})();
</script>

@include('siberad.dashboards.partials.dash-script')

@if(session('status') || $errors->any())
<script>
// Setelah kirim/gagal kirim laporan, otomatis buka tab "Tambah Laporan"
// supaya pesan sukses/errornya langsung kelihatan (bukan nyangkut di tab Dashboard).
(function () {
  var tab = document.querySelector('[data-tab-link="tambah-laporan"]');
  if (tab) tab.click();
})();
</script>
@endif
</body>
</html>