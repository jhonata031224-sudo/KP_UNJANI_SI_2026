<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pembinaan Fungsi — SIBERAD</title>
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

      <div class="side-dropdown" id="personelDropdown">
        <button type="button" class="side-link side-dropdown-toggle" id="personelToggle" aria-expanded="false" aria-controls="personelSubmenu">
          <span class="dot"></span>
          <span class="side-link-label">Administrasi Personel</span>
          <svg class="side-dropdown-arrow" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"></path></svg>
        </button>
        <div class="side-dropdown-menu" id="personelSubmenu">
          <a href="#" class="side-link side-sublink" data-tab-link="data-personel">Data Personel</a>
          <a href="#" class="side-link side-sublink" data-tab-link="form-personel">Tambah/Edit Personel</a>
          <a href="#" class="side-link side-sublink" data-tab-link="mutasi">Mutasi</a>
          <a href="#" class="side-link side-sublink" data-tab-link="pangkat">Pangkat</a>
          <a href="#" class="side-link side-sublink" data-tab-link="jabatan">Jabatan</a>
          <a href="#" class="side-link side-sublink" data-tab-link="satuan-ref">Satuan</a>
          <a href="#" class="side-link side-sublink" data-tab-link="upload-dokumen">Upload Dokumen</a>
          <a href="#" class="side-link side-sublink" data-tab-link="riwayat">Riwayat</a>
        </div>
      </div>

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
    ['laporanDropdown', 'personelDropdown'].forEach(function (dropdownId) {
      var dropdown = document.getElementById(dropdownId);
      if (!dropdown) return;
      var toggle = dropdown.querySelector('.side-dropdown-toggle');
      if (!toggle) return;

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
          <h2>Ringkasan Penempatan Personel</h2>
          <p>Status penempatan personel yang ditangani Binfung bulan ini.</p>
        </div>
        <div class="stat-grid">
          <div class="stat-card">
            <div class="lbl">Personel Masuk</div>
            <div class="val">{{ $stats['personel_masuk_bulan_ini'] }}</div>
            <div class="sub">Bulan ini</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Menunggu SK</div>
            <div class="val" style="color:var(--amber);">{{ $stats['menunggu_sk'] }}</div>
            <div class="sub">Perlu ditindaklanjuti</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Satuan Terisi</div>
            <div class="val" style="color:var(--green);">{{ $stats['satuan_terisi'] }}</div>
            <div class="sub">Dari seluruh satuan</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Total Personel</div>
            <div class="val">{{ $stats['total_personel'] }}</div>
            <div class="sub">Terdata di sistem</div>
          </div>
        </div>

        <div class="panel chart-box">
          <div class="chart-box-head-row">
            <div><h3 style="font-family:var(--display);font-size:17px;font-weight:700;">Analitik Penempatan Personel</h3><p style="font-size:12px;color:var(--text-muted);margin-top:2px;">Status penempatan, sebaran per satuan tujuan, dan jabatan yang diisi.</p></div>
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
              <div class="chart-mini-head"><h4>Status Penempatan</h4><p>Menunggu SK vs Ditempatkan.</p></div>
              <div class="chart-wrap"><canvas id="chartStatusPenempatan"></canvas></div>
            </div>
            <div class="chart-mini">
              <div class="chart-mini-head"><h4>Penempatan per Satuan</h4><p>Jumlah personel per satuan tujuan.</p></div>
              <div class="chart-wrap"><canvas id="chartPenempatanSatuan"></canvas></div>
            </div>
            <div class="chart-mini">
              <div class="chart-mini-head"><h4>Distribusi Jabatan</h4><p>Jabatan yang diisi oleh personel baru.</p></div>
              <div class="chart-wrap"><canvas id="chartJabatan"></canvas></div>
            </div>
          </div>
          <p class="chart-legend-note">Emas = Menunggu SK, Hijau = sudah Ditempatkan. Ganti jenis grafik lewat dropdown di kanan atas — pilihan selain "Batang" akan otomatis memisah tiap grafik menjadi tampilan yang lebih besar. Filter tanggal masih simulasi proporsional karena histori per tanggal belum tersambung ke database.</p>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Aktivitas Terbaru</h3><p>Aktivitas Administrasi Personel yang baru berlangsung.</p></div></div>
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Personel</th><th>Kegiatan</th><th>Waktu</th><th>Jenis</th></tr></thead>
              <tbody>
                @forelse($riwayat->take(5) as $i)
                <tr>
                  <td>{{ $i['nama'] }}</td>
                  <td>{{ $i['keterangan'] }}</td>
                  <td>{{ $i['tanggal']?->diffForHumans() }}</td>
                  <td><span class="status-dot {{ $i['status_class'] }}">{{ $i['jenis'] }}</span></td>
                </tr>
                @empty
                <tr><td colspan="4" style="color:var(--text-dim);text-align:center;padding:24px;">Belum ada aktivitas.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== ADMINISTRASI PERSONEL › DATA PERSONEL ===== --}}
      <section class="tab-panel" data-tab-panel="data-personel">
        <div class="section-head">
          <h2>Data Personel</h2>
          <p>Seluruh personel yang tercatat di sistem Administrasi Personel Binfung.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="notice" style="border-color:var(--red);">{{ session('error') }}</div>
        @endif

        <div class="panel">
          <div class="panel-head"><div><h3>Daftar Personel</h3><p>Klik "Edit" untuk mengubah data lewat form Tambah/Edit Personel.</p></div></div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl">
              <thead><tr><th>NRP</th><th>Nama</th><th>Pangkat</th><th>Jabatan</th><th>Satuan</th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($semuaPersonel as $p)
                <tr>
                  <td>{{ $p->nrp }}</td>
                  <td>{{ $p->nama }}</td>
                  <td style="color:var(--text-muted);">{{ $p->pangkat->nama ?? '-' }}</td>
                  <td style="color:var(--text-muted);">{{ $p->jabatan->nama ?? '-' }}</td>
                  <td style="color:var(--text-muted);">{{ $p->satuan->nama ?? '-' }}</td>
                  <td><span class="status-dot {{ $p->status === 'Aktif' ? 'ok' : ($p->status === 'Mutasi' ? 'warn' : 'bad') }}">{{ $p->status }}</span></td>
                  <td>
                    <div class="btn-row">
                      <button class="btn btn-sm" type="button" onclick="pilihEditPersonel({{ $p->id }})">Edit</button>
                      <form method="POST" action="{{ route('personel.destroy', $p) }}" onsubmit="return confirm('Hapus data personel {{ $p->nama }}? Seluruh riwayat mutasi & dokumennya ikut terhapus.');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-ghost-red" type="submit">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
                @empty
                <tr><td colspan="7" style="color:var(--text-dim);text-align:center;padding:24px;">Belum ada data personel.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== ADMINISTRASI PERSONEL › TAMBAH/EDIT PERSONEL ===== --}}
      <section class="tab-panel" data-tab-panel="form-personel">
        <div class="section-head">
          <h2>Tambah / Edit Personel</h2>
          <p>Tambahkan personel baru, atau pilih personel yang sudah ada untuk diedit.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif

        <div class="panel">
          <div class="panel-head"><div><h3 id="formPersonelTitle">Tambah Personel Baru</h3><p>Pilih personel di bawah untuk beralih ke mode edit.</p></div></div>

          <div class="form-grid" style="padding:22px 22px 0;">
            <div class="form-field full">
              <label for="pilihEditPersonelSelect">Edit Personel yang Sudah Ada (opsional)</label>
              <select id="pilihEditPersonelSelect" onchange="pilihEditPersonel(this.value)">
                <option value="">— Tambah personel baru —</option>
                @foreach($semuaPersonel as $p)
                  <option
                    value="{{ $p->id }}"
                    data-action="{{ route('personel.update', $p) }}"
                    data-nrp="{{ $p->nrp }}"
                    data-nama="{{ $p->nama }}"
                    data-jk="{{ $p->jenis_kelamin }}"
                    data-tempat-lahir="{{ $p->tempat_lahir }}"
                    data-tanggal-lahir="{{ $p->tanggal_lahir?->format('Y-m-d') }}"
                    data-pangkat="{{ $p->pangkat_id }}"
                    data-jabatan="{{ $p->jabatan_id }}"
                    data-satuan="{{ $p->satuan_id }}"
                    data-status="{{ $p->status }}"
                    data-tanggal-masuk="{{ $p->tanggal_masuk?->format('Y-m-d') }}"
                    data-no-hp="{{ $p->no_hp }}"
                    data-alamat="{{ $p->alamat }}"
                    data-catatan="{{ $p->catatan }}"
                  >{{ $p->nrp }} — {{ $p->nama }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <form class="form-grid" id="formPersonel" method="POST" action="{{ route('personel.store') }}" style="padding:22px;">
            @csrf
            <input type="hidden" name="_method" id="formPersonelMethod" value="POST">

            <div class="form-field">
              <label for="fNrp">NRP</label>
              <input id="fNrp" name="nrp" type="text" placeholder="Contoh: 21030112345678" required>
            </div>
            <div class="form-field">
              <label for="fNama">Nama Lengkap</label>
              <input id="fNama" name="nama" type="text" placeholder="Contoh: Serda Ahmad Fauzi" required>
            </div>
            <div class="form-field">
              <label for="fJk">Jenis Kelamin</label>
              <select id="fJk" name="jenis_kelamin">
                <option value="">-</option>
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
            </div>
            <div class="form-field">
              <label for="fTempatLahir">Tempat Lahir</label>
              <input id="fTempatLahir" name="tempat_lahir" type="text" placeholder="Contoh: Cimahi">
            </div>
            <div class="form-field">
              <label for="fTanggalLahir">Tanggal Lahir</label>
              <input id="fTanggalLahir" name="tanggal_lahir" type="date">
            </div>
            <div class="form-field">
              <label for="fPangkat">Pangkat</label>
              <select id="fPangkat" name="pangkat_id">
                <option value="">-</option>
                @foreach($semuaPangkat as $pk)
                  <option value="{{ $pk->id }}">{{ $pk->kode }} — {{ $pk->nama }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-field">
              <label for="fJabatan">Jabatan</label>
              <select id="fJabatan" name="jabatan_id">
                <option value="">-</option>
                @foreach($semuaJabatan as $j)
                  <option value="{{ $j->id }}">{{ $j->nama }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-field">
              <label for="fSatuan">Satuan</label>
              <select id="fSatuan" name="satuan_id">
                <option value="">-</option>
                @foreach($semuaSatuanRef as $s)
                  <option value="{{ $s->id }}">{{ $s->nama }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-field">
              <label for="fStatus">Status</label>
              <select id="fStatus" name="status" required>
                <option value="Aktif">Aktif</option>
                <option value="Mutasi">Mutasi</option>
                <option value="Purna">Purna</option>
              </select>
            </div>
            <div class="form-field">
              <label for="fTanggalMasuk">Tanggal Masuk</label>
              <input id="fTanggalMasuk" name="tanggal_masuk" type="date">
            </div>
            <div class="form-field">
              <label for="fNoHp">No. HP</label>
              <input id="fNoHp" name="no_hp" type="text" placeholder="08xxxxxxxxxx">
            </div>
            <div class="form-field full">
              <label for="fAlamat">Alamat</label>
              <textarea id="fAlamat" name="alamat" rows="2" placeholder="Alamat domisili"></textarea>
            </div>
            <div class="form-field full">
              <label for="fCatatan">Catatan (opsional)</label>
              <textarea id="fCatatan" name="catatan" rows="2" placeholder="Catatan tambahan"></textarea>
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="submit" id="formPersonelSubmitBtn">Simpan Personel</button>
              <button class="btn btn-sm" type="button" onclick="pilihEditPersonel('')" style="margin-left:8px;">Batal Edit</button>
            </div>
          </form>
        </div>
      </section>

      {{-- ===== ADMINISTRASI PERSONEL › MUTASI ===== --}}
      <section class="tab-panel" data-tab-panel="mutasi">
        <div class="section-head">
          <h2>Mutasi Personel</h2>
          <p>Ajukan mutasi personel ke satuan/jabatan tujuan, lalu proses status SK-nya.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif

        <div class="panel">
          <div class="panel-head"><div><h3>Ajukan Mutasi Baru</h3><p>Personel akan berstatus "Mutasi" sampai SK diproses.</p></div></div>
          <form class="form-grid" method="POST" action="{{ route('personel-mutasi.store') }}" style="padding:22px;">
            @csrf
            <div class="form-field">
              <label for="mPersonel">Personel</label>
              <select id="mPersonel" name="personel_id" required>
                <option value="">Pilih personel</option>
                @foreach($semuaPersonel as $p)
                  <option value="{{ $p->id }}">{{ $p->nrp }} — {{ $p->nama }} ({{ $p->satuan->nama ?? 'Belum ada satuan' }})</option>
                @endforeach
              </select>
            </div>
            <div class="form-field">
              <label for="mSatuanTujuan">Satuan Tujuan</label>
              <select id="mSatuanTujuan" name="satuan_tujuan_id" required>
                <option value="">Pilih satuan tujuan</option>
                @foreach($semuaSatuanRef as $s)
                  <option value="{{ $s->id }}">{{ $s->nama }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-field">
              <label for="mJabatanTujuan">Jabatan Tujuan (opsional)</label>
              <select id="mJabatanTujuan" name="jabatan_tujuan_id">
                <option value="">-</option>
                @foreach($semuaJabatan as $j)
                  <option value="{{ $j->id }}">{{ $j->nama }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-field">
              <label for="mTanggal">Tanggal Mutasi</label>
              <input id="mTanggal" name="tanggal_mutasi" type="date" required>
            </div>
            <div class="form-field">
              <label for="mNomorSk">Nomor SK (opsional)</label>
              <input id="mNomorSk" name="nomor_sk" type="text" placeholder="Contoh: SK/123/VIII/2026">
            </div>
            <div class="form-field full">
              <label for="mKeterangan">Keterangan</label>
              <textarea id="mKeterangan" name="keterangan" rows="2" placeholder="Alasan/keterangan mutasi"></textarea>
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="submit">Ajukan Mutasi</button>
            </div>
          </form>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Daftar Mutasi</h3><p>Proses status SK — "Disetujui" akan langsung memindahkan satuan/jabatan aktif personel.</p></div></div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl">
              <thead><tr><th>Personel</th><th>Asal</th><th>Tujuan</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($semuaMutasi as $m)
                <tr>
                  <td>{{ $m->personel->nama ?? '-' }}</td>
                  <td style="color:var(--text-muted);">{{ $m->satuanAsal->nama ?? '-' }}</td>
                  <td style="color:var(--text-muted);">{{ $m->satuanTujuan->nama ?? '-' }}</td>
                  <td>{{ $m->tanggal_mutasi?->translatedFormat('d M Y') }}</td>
                  <td><span class="status-dot {{ $m->status === 'Disetujui' ? 'ok' : ($m->status === 'Ditolak' ? 'bad' : 'warn') }}">{{ $m->status }}</span></td>
                  <td>
                    @if($m->status === 'Menunggu SK')
                    <div class="btn-row">
                      <form method="POST" action="{{ route('personel-mutasi.update', $m) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="Disetujui">
                        <button class="btn btn-primary btn-sm" type="submit">Setujui</button>
                      </form>
                      <form method="POST" action="{{ route('personel-mutasi.update', $m) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="Ditolak">
                        <button class="btn btn-sm btn-ghost-red" type="submit">Tolak</button>
                      </form>
                    </div>
                    @else
                    <form method="POST" action="{{ route('personel-mutasi.destroy', $m) }}" onsubmit="return confirm('Hapus riwayat mutasi ini?');">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-ghost-red" type="submit">Hapus</button>
                    </form>
                    @endif
                  </td>
                </tr>
                @empty
                <tr><td colspan="6" style="color:var(--text-dim);text-align:center;padding:24px;">Belum ada pengajuan mutasi.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== ADMINISTRASI PERSONEL › PANGKAT ===== --}}
      <section class="tab-panel" data-tab-panel="pangkat">
        <div class="section-head">
          <h2>Pangkat</h2>
          <p>Kelola master data pangkat yang dipakai pada Data Personel.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="notice" style="border-color:var(--red);">{{ session('error') }}</div>
        @endif

        <div class="panel">
          <div class="panel-head"><div><h3>Tambah Pangkat</h3><p>Daftarkan pangkat baru beserta kategorinya.</p></div></div>
          <form class="form-grid" method="POST" action="{{ route('pangkat.store') }}" style="padding:22px;">
            @csrf
            <div class="form-field">
              <label for="pkKode">Kode</label>
              <input id="pkKode" name="kode" type="text" placeholder="Contoh: SERDA" required>
            </div>
            <div class="form-field">
              <label for="pkNama">Nama Pangkat</label>
              <input id="pkNama" name="nama" type="text" placeholder="Contoh: Sersan Dua" required>
            </div>
            <div class="form-field">
              <label for="pkKategori">Kategori</label>
              <select id="pkKategori" name="kategori" required>
                <option value="Tamtama">Tamtama</option>
                <option value="Bintara">Bintara</option>
                <option value="Perwira">Perwira</option>
              </select>
            </div>
            <div class="form-field">
              <label for="pkUrutan">Urutan</label>
              <input id="pkUrutan" name="urutan" type="number" min="0" placeholder="0">
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="submit">Simpan Pangkat</button>
            </div>
          </form>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Daftar Pangkat</h3><p>Pangkat yang masih dipakai personel tidak bisa dihapus.</p></div></div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl">
              <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Jumlah Personel</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($semuaPangkat as $pk)
                <tr>
                  <td>{{ $pk->kode }}</td>
                  <td>{{ $pk->nama }}</td>
                  <td style="color:var(--text-muted);">{{ $pk->kategori }}</td>
                  <td>{{ $pk->personels_count }}</td>
                  <td>
                    <form method="POST" action="{{ route('pangkat.destroy', $pk) }}" onsubmit="return confirm('Hapus pangkat {{ $pk->nama }}?');">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-ghost-red" type="submit">Hapus</button>
                    </form>
                  </td>
                </tr>
                @empty
                <tr><td colspan="5" style="color:var(--text-dim);text-align:center;padding:24px;">Belum ada data pangkat.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== ADMINISTRASI PERSONEL › JABATAN ===== --}}
      <section class="tab-panel" data-tab-panel="jabatan">
        <div class="section-head">
          <h2>Jabatan</h2>
          <p>Kelola master data jabatan yang dipakai pada Data Personel.</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="notice" style="border-color:var(--red);">{{ session('error') }}</div>
        @endif

        <div class="panel">
          <div class="panel-head"><div><h3>Tambah Jabatan</h3><p>Daftarkan jabatan baru.</p></div></div>
          <form class="form-grid" method="POST" action="{{ route('jabatan.store') }}" style="padding:22px;">
            @csrf
            <div class="form-field">
              <label for="jbNama">Nama Jabatan</label>
              <input id="jbNama" name="nama" type="text" placeholder="Contoh: Piket" required>
            </div>
            <div class="form-field">
              <label for="jbDeskripsi">Deskripsi (opsional)</label>
              <input id="jbDeskripsi" name="deskripsi" type="text" placeholder="Deskripsi singkat">
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="submit">Simpan Jabatan</button>
            </div>
          </form>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Daftar Jabatan</h3><p>Jabatan yang masih dipakai personel tidak bisa dihapus.</p></div></div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl">
              <thead><tr><th>Nama</th><th>Deskripsi</th><th>Jumlah Personel</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($semuaJabatan as $j)
                <tr>
                  <td>{{ $j->nama }}</td>
                  <td style="color:var(--text-muted);">{{ $j->deskripsi ?? '-' }}</td>
                  <td>{{ $j->personels_count }}</td>
                  <td>
                    <form method="POST" action="{{ route('jabatan.destroy', $j) }}" onsubmit="return confirm('Hapus jabatan {{ $j->nama }}?');">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-ghost-red" type="submit">Hapus</button>
                    </form>
                  </td>
                </tr>
                @empty
                <tr><td colspan="4" style="color:var(--text-dim);text-align:center;padding:24px;">Belum ada data jabatan.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== ADMINISTRASI PERSONEL › SATUAN (REFERENSI) ===== --}}
      <section class="tab-panel" data-tab-panel="satuan-ref">
        <div class="section-head">
          <h2>Satuan</h2>
          <p>Referensi seluruh satuan dan jumlah personel yang ditempatkan di masing-masing satuan. Data satuan dikelola oleh Admin.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap" data-row-limit="12">
            <table class="dtbl">
              <thead><tr><th>Kode</th><th>Nama Satuan</th><th>Kategori</th><th>Jumlah Personel</th></tr></thead>
              <tbody>
                @forelse($semuaSatuanRef as $s)
                <tr>
                  <td>{{ $s->kode }}</td>
                  <td>{{ $s->nama }}</td>
                  <td style="color:var(--text-muted);text-transform:capitalize;">{{ $s->kategori }}</td>
                  <td>{{ $s->personels_count }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="color:var(--text-dim);text-align:center;padding:24px;">Belum ada data satuan.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== ADMINISTRASI PERSONEL › UPLOAD DOKUMEN ===== --}}
      <section class="tab-panel" data-tab-panel="upload-dokumen">
        <div class="section-head">
          <h2>Upload Dokumen</h2>
          <p>Unggah dokumen administrasi personel (SK, KTP, Ijazah, dll).</p>
        </div>

        @if (session('status'))
          <div class="notice">{{ session('status') }}</div>
        @endif

        <div class="panel">
          <div class="panel-head"><div><h3>Unggah Dokumen Baru</h3><p>Format PDF/JPG/PNG, maksimal 10 MB.</p></div></div>
          <form class="form-grid" method="POST" action="{{ route('personel-dokumen.store') }}" enctype="multipart/form-data" style="padding:22px;">
            @csrf
            <div class="form-field">
              <label for="dPersonel">Personel</label>
              <select id="dPersonel" name="personel_id" required>
                <option value="">Pilih personel</option>
                @foreach($semuaPersonel as $p)
                  <option value="{{ $p->id }}">{{ $p->nrp }} — {{ $p->nama }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-field">
              <label for="dJenis">Jenis Dokumen</label>
              <input id="dJenis" name="jenis_dokumen" type="text" placeholder="Contoh: SK, KTP, Ijazah" required>
            </div>
            <div class="form-field full">
              <label for="dFile">Berkas</label>
              <input id="dFile" name="dokumen" type="file" accept="application/pdf,.pdf,image/png,image/jpeg" required>
              <span class="form-hint">Format PDF/JPG/PNG, maksimal 10 MB.</span>
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="submit">Unggah Dokumen</button>
            </div>
          </form>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Dokumen Tersimpan</h3></div></div>
          <div class="tbl-wrap" data-row-limit="8">
            <table class="dtbl">
              <thead><tr><th>Personel</th><th>Jenis Dokumen</th><th>Nama Berkas</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($semuaDokumen as $d)
                <tr>
                  <td>{{ $d->personel->nama ?? '-' }}</td>
                  <td style="color:var(--text-muted);">{{ $d->jenis_dokumen }}</td>
                  <td>{{ $d->nama_file }}</td>
                  <td>
                    <div class="btn-row">
                      <a class="btn btn-sm" href="{{ asset('storage/'.$d->path) }}" target="_blank" rel="noopener">Lihat</a>
                      <form method="POST" action="{{ route('personel-dokumen.destroy', $d) }}" onsubmit="return confirm('Hapus dokumen ini?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-ghost-red" type="submit">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
                @empty
                <tr><td colspan="4" style="color:var(--text-dim);text-align:center;padding:24px;">Belum ada dokumen yang diunggah.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== ADMINISTRASI PERSONEL › RIWAYAT ===== --}}
      <section class="tab-panel" data-tab-panel="riwayat">
        <div class="section-head">
          <h2>Riwayat</h2>
          <p>Log gabungan aktivitas Administrasi Personel: personel baru, mutasi, dan unggah dokumen.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap" data-row-limit="10">
            <table class="dtbl">
              <thead><tr><th>Jenis</th><th>Personel</th><th>Keterangan</th><th>Waktu</th></tr></thead>
              <tbody>
                @forelse($riwayat as $r)
                <tr>
                  <td><span class="status-dot {{ $r['status_class'] }}">{{ $r['jenis'] }}</span></td>
                  <td>{{ $r['nama'] }}</td>
                  <td style="color:var(--text-muted);">{{ $r['keterangan'] }}</td>
                  <td>{{ $r['tanggal']?->translatedFormat('d M Y, H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="color:var(--text-dim);text-align:center;padding:24px;">Belum ada riwayat aktivitas.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <script>
      function pilihEditPersonel(personelId) {
        var select = document.getElementById('pilihEditPersonelSelect');
        var form = document.getElementById('formPersonel');
        var methodInput = document.getElementById('formPersonelMethod');
        var title = document.getElementById('formPersonelTitle');
        var submitBtn = document.getElementById('formPersonelSubmitBtn');
        var nrpField = document.getElementById('fNrp');
        if (!select || !form) return;

        // Sinkronkan dropdown kalau dipanggil dari tombol "Edit" di tabel Data Personel.
        if (select.value !== String(personelId)) select.value = personelId || '';

        if (!personelId) {
          form.action = form.getAttribute('data-store-url');
          methodInput.value = 'POST';
          title.textContent = 'Tambah Personel Baru';
          submitBtn.textContent = 'Simpan Personel';
          form.reset();
          if (nrpField) nrpField.removeAttribute('readonly');
          return;
        }

        var opt = select.querySelector('option[value="' + personelId + '"]');
        if (!opt) return;

        form.action = opt.getAttribute('data-action');
        methodInput.value = 'PATCH';
        title.textContent = 'Edit Personel: ' + opt.getAttribute('data-nama');
        submitBtn.textContent = 'Simpan Perubahan';

        document.getElementById('fNrp').value = opt.getAttribute('data-nrp') || '';
        document.getElementById('fNama').value = opt.getAttribute('data-nama') || '';
        document.getElementById('fJk').value = opt.getAttribute('data-jk') || '';
        document.getElementById('fTempatLahir').value = opt.getAttribute('data-tempat-lahir') || '';
        document.getElementById('fTanggalLahir').value = opt.getAttribute('data-tanggal-lahir') || '';
        document.getElementById('fPangkat').value = opt.getAttribute('data-pangkat') || '';
        document.getElementById('fJabatan').value = opt.getAttribute('data-jabatan') || '';
        document.getElementById('fSatuan').value = opt.getAttribute('data-satuan') || '';
        document.getElementById('fStatus').value = opt.getAttribute('data-status') || 'Aktif';
        document.getElementById('fTanggalMasuk').value = opt.getAttribute('data-tanggal-masuk') || '';
        document.getElementById('fNoHp').value = opt.getAttribute('data-no-hp') || '';
        document.getElementById('fAlamat').value = opt.getAttribute('data-alamat') || '';
        document.getElementById('fCatatan').value = opt.getAttribute('data-catatan') || '';

        // Beralih ke tab "Tambah/Edit Personel" kalau dipanggil dari tabel Data Personel.
        var link = document.querySelector('[data-tab-link="form-personel"]');
        if (link) link.click();
      }

      document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('formPersonel');
        if (form) form.setAttribute('data-store-url', form.getAttribute('action'));
      });
      </script>

      {{-- ===== LAPOR / VERIFIKASI ===== --}}
      {{-- ===== LAPORAN › TAMBAH LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="tambah-laporan">
        <div class="section-head">
          <h2>Tambah Laporan</h2>
          <p>Catat kendala, kebutuhan, atau perkembangan baru terkait penempatan personel.</p>
        </div>
        <div class="panel">
          <form class="form-grid" id="formTambahLaporan" style="padding:22px;" novalidate>
            <div class="form-field">
              <label for="personelTambahLaporan">Personel Terkait</label>
              <select id="personelTambahLaporan" required>
                @foreach($semuaPersonel as $p)
                  <option>{{ $p->nama }}</option>
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
              <label for="perihalTambahLaporan">Perihal</label>
              <input id="perihalTambahLaporan" type="text" placeholder="Contoh: Pengajuan penempatan personel baru" required>
            </div>
            <div class="form-field full">
              <label for="deskripsiTambahLaporan">Deskripsi</label>
              <textarea id="deskripsiTambahLaporan" rows="4" placeholder="Jelaskan kronologi dan dampaknya..." required></textarea>
            </div>
            <div class="form-field full">
              <label for="lampiranTambahLaporan">Lampiran (bukti / dokumentasi)</label>
              <input id="lampiranTambahLaporan" type="file" accept="application/pdf,.pdf">
              <span class="form-hint">Format PDF, maksimal 20 MB.</span>
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
            <h2>Verifikasi &amp; Teruskan Laporan</h2>
            <p>Pengajuan penempatan personel yang menunggu diteruskan ke WADAN.</p>
          </div>
          <div class="panel">
            <div class="tbl-wrap">
              <table class="dtbl">
                <thead><tr><th>Nama</th><th>Perihal</th><th>Dilaporkan Oleh</th><th>Tanggal</th><th>Prioritas</th><th>Aksi</th></tr></thead>
                <tbody>
                  @foreach($laporanPiket as $l)
                  <tr>
                    <td>{{ $l['nama'] }}</td>
                    <td>{{ $l['perihal'] }}</td>
                    <td>{{ $l['pelapor'] }}</td>
                    <td>{{ $l['tanggal'] }}</td>
                    <td><span class="status-dot {{ $l['prioritas_class'] }}">{{ $l['prioritas'] }}</span></td>
                    <td>
                      <div class="btn-row">
                        <button class="btn btn-primary btn-sm" type="button">Verifikasi & Teruskan</button>
                        <button class="btn btn-ghost-red btn-sm" type="button">Tolak</button>
                      </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
      </section>

      {{-- ===== LAPORAN › RIWAYAT LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="riwayat-laporan">
        <div class="section-head">
          <h2>Riwayat Laporan</h2>
          <p>Log aktivitas penempatan personel yang pernah tercatat.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap" data-row-limit="10">
            <table class="dtbl">
              <thead><tr><th>Personel</th><th>Kegiatan</th><th>Waktu</th><th>Jenis</th></tr></thead>
              <tbody>
                @forelse($riwayat as $a)
                <tr>
                  <td>{{ $a['nama'] }}</td>
                  <td>{{ $a['keterangan'] }}</td>
                  <td>{{ $a['tanggal']?->translatedFormat('d M Y, H:i') }}</td>
                  <td><span class="status-dot {{ $a['status_class'] }}">{{ $a['jenis'] }}</span></td>
                </tr>
                @empty
                <tr><td colspan="4" style="color:var(--text-dim);text-align:center;padding:24px;">Belum ada riwayat.</td></tr>
                @endforelse
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

{{-- ===== ANALITIK PENEMPATAN PERSONEL (RINGKASAN) ===== --}}
<script>
(function () {
  var canvasCheck = document.getElementById('chartStatusPenempatan');
  if (!canvasCheck || typeof Chart === 'undefined') return;

  var css = getComputedStyle(document.documentElement);
  var cGold = css.getPropertyValue('--gold-bright').trim() || '#f2c14e';
  var cGreen = css.getPropertyValue('--green').trim() || '#3ddc84';
  var cAmber = css.getPropertyValue('--amber').trim() || '#f2a93b';
  var cRed = css.getPropertyValue('--red').trim() || '#e5484d';
  var cMuted = css.getPropertyValue('--text-dim').trim() || '#7d8f87';
  var cText = css.getPropertyValue('--text').trim() || '#e8efe9';
  var cBorder = css.getPropertyValue('--border-soft').trim() || '#22302a';

  var statusPenempatan = @json($statusPenempatanChart ?? []);
  var penempatanSatuan = @json($penempatanPerSatuanChart ?? []);
  var jabatan = @json($jabatanChart ?? []);

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

  // ===== Grafik 1: Status penempatan =====
  function drawStatusPenempatan(type, data) {
    var t = type === 'bar' ? 'doughnut' : type;
    renderChart(
      'chartStatusPenempatan', t,
      data.map(function (s) { return s.label; }),
      data.map(function (s) { return s.jumlah; }),
      [cAmber, cGreen],
      { label: 'Jumlah Personel' }
    );
  }

  // ===== Grafik 2: Penempatan per satuan tujuan =====
  function drawPenempatanSatuan(type, data) {
    renderChart(
      'chartPenempatanSatuan', type,
      data.map(function (s) { return s.satuan; }),
      data.map(function (s) { return s.jumlah; }),
      type === 'line' || type === 'radar' ? cGold : cGold,
      { horizontal: type === 'bar', label: 'Jumlah Personel', lineColor: cGold }
    );
  }

  // ===== Grafik 3: Distribusi jabatan =====
  function drawJabatan(type, data) {
    renderChart(
      'chartJabatan', type,
      data.map(function (s) { return s.jabatan; }),
      data.map(function (s) { return s.jumlah; }),
      type === 'line' || type === 'radar' ? cGold : cGold,
      { horizontal: type === 'bar', label: 'Jumlah Personel', lineColor: cGold }
    );
  }

  var DATE_RANGE_FACTOR = { '7d': 0.35, '30d': 0.7, '90d': 0.9, 'all': 1 };

  function scaledStatusPenempatan(factor) {
    return statusPenempatan.map(function (s) { return { label: s.label, jumlah: Math.max(0, Math.round(s.jumlah * factor)) }; });
  }
  function scaledPenempatanSatuan(factor) {
    return penempatanSatuan.map(function (s) { return { satuan: s.satuan, jumlah: Math.max(0, Math.round(s.jumlah * factor)) }; });
  }
  function scaledJabatan(factor) {
    return jabatan.map(function (s) { return { jabatan: s.jabatan, jumlah: Math.max(0, Math.round(s.jumlah * factor)) }; });
  }

  var typeFilterEl = document.getElementById('chartTypeFilterGlobal');
  var dateFilterEl = document.getElementById('chartDateFilterGlobal');
  var gridEl = document.getElementById('chartBoxGrid');

  function redrawAll() {
    var type = typeFilterEl ? typeFilterEl.value : 'bar';
    var factor = DATE_RANGE_FACTOR[dateFilterEl ? dateFilterEl.value : 'all'] || 1;
    if (gridEl) gridEl.classList.toggle('split-mode', type !== 'bar');

    drawStatusPenempatan(type, scaledStatusPenempatan(factor));
    drawPenempatanSatuan(type, scaledPenempatanSatuan(factor));
    drawJabatan(type, scaledJabatan(factor));
  }

  redrawAll();

  if (typeFilterEl) typeFilterEl.addEventListener('change', redrawAll);
  if (dateFilterEl) dateFilterEl.addEventListener('change', redrawAll);
})();
</script>

@include('siberad.dashboards.partials.dash-script')
</body>
</html>