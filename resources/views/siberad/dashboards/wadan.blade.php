<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Wakil Komandan — SIBERAD</title>
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
      <a href="#" class="side-link active" data-tab-link="ringkasan"><span class="dot"></span>Ringkasan</a>
      <a href="#" class="side-link" data-tab-link="laporan"><span class="dot"></span>Laporan Masuk</a>
      <a href="#" class="side-link" data-tab-link="koordinasi"><span class="dot"></span>Koordinasi Satlak</a>
      <a href="#" class="side-link" data-tab-link="diteruskan"><span class="dot"></span>Diteruskan ke DANPUS</a>
    </nav>
    <div class="side-foot">
      <form class="logout logout-form" method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Keluar</button>
      </form>
    </div>
  </aside>

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
      <section class="tab-panel active" data-tab-panel="ringkasan">
        <div class="dash-hero">
          <div>
            <div class="dash-hero-eyebrow">SIBERAD // {{ $satuan->kode ?? 'SISTEM' }}</div>
            <h2>Selamat datang, {{ $satuan->nama ?? $user->name }}</h2>
            <p>{{ now()->translatedFormat('l, d F Y') }}</p>
          </div>
        </div>

        <div class="section-head">
          <h2>Ringkasan Koordinasi</h2>
          <p>Status laporan yang masuk dari SDIR maupun langsung dari Satlak.</p>
        </div>
        <div class="stat-grid">
          <div class="stat-card">
            <div class="lbl">Menunggu Verifikasi</div>
            <div class="val" style="color:var(--amber);">{{ $stats['menunggu_verifikasi'] }}</div>
            <div class="sub">Perlu ditinjau WADAN</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Diteruskan ke DANPUS</div>
            <div class="val" style="color:var(--green-bright);">{{ $stats['diteruskan'] }}</div>
            <div class="sub">Bulan ini</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Satuan Aktif Melapor</div>
            <div class="val">{{ $stats['satuan_aktif'] }}/8</div>
            <div class="sub">Dari Satlak & Direktorat</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Permintaan Koordinasi</div>
            <div class="val">{{ $stats['permintaan_koordinasi'] }}</div>
            <div class="sub">Menunggu tindak lanjut</div>
          </div>
        </div>

        <div class="panel chart-box">
          <div class="chart-box-head-row">
            <div><h3 style="font-family:var(--display);font-size:17px;font-weight:700;">Analitik Verifikasi Laporan</h3><p style="font-size:12px;color:var(--text-muted);margin-top:2px;">Beban laporan berdasarkan prioritas, satuan paling aktif melapor, dan status permintaan koordinasi.</p></div>
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
                <h4>Laporan per Prioritas</h4><p>Beban laporan yang menunggu verifikasi.</p>
              </div>
              <div class="chart-wrap"><canvas id="chartLaporanPrioritas"></canvas></div>
            </div>

            <div class="chart-mini">
              <div class="chart-mini-head">
                <h4>Satuan Paling Aktif Melapor</h4><p>Jumlah laporan masuk per satuan.</p>
              </div>
              <div class="chart-wrap"><canvas id="chartLaporanPerSatuan"></canvas></div>
            </div>

            <div class="chart-mini">
              <div class="chart-mini-head">
                <h4>Status Permintaan Koordinasi</h4><p>Menunggu tindak lanjut vs sudah selesai.</p>
              </div>
              <div class="chart-wrap"><canvas id="chartStatusKoordinasi"></canvas></div>
            </div>

          </div>
          <p class="chart-legend-note">Ganti jenis grafik lewat dropdown di kanan atas — pilihan selain "Batang" akan otomatis memisah tiap grafik menjadi tampilan yang lebih besar. Filter tanggal masih simulasi proporsional karena histori laporan per tanggal belum tersambung ke database.</p>
        </div>

        <div class="panel">
          <div class="panel-head">
            <div><h3>Aktivitas Terbaru</h3><p>Ringkasan laporan & koordinasi terbaru dari satuan pelaksana.</p></div>
          </div>
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Asal</th><th>Perihal</th><th>Tanggal</th><th>Status</th></tr></thead>
              <tbody>
                @foreach($aktivitasTerbaru as $a)
                <tr>
                  <td>{{ $a['satuan'] }}</td>
                  <td>{{ $a['perihal'] }}</td>
                  <td>{{ $a['tanggal'] }}</td>
                  <td><span class="badge {{ $a['status_class'] }}">{{ $a['status'] }}</span></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== LAPORAN MASUK ===== --}}
      <section class="tab-panel" data-tab-panel="laporan">
        <div class="section-head">
          <h2>Laporan Masuk</h2>
          <p>Laporan dari SDIR dan seluruh Satlak yang perlu diverifikasi sebelum diteruskan ke DANPUS.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Asal Satuan</th><th>Perihal</th><th>Tanggal</th><th>Prioritas</th><th>Status</th>
              <th>Detail</th>
              </tr></thead>
              <tbody>
                @forelse($laporanMasuk as $i => $l)
                <tr id="rowLaporan{{ $i }}">
                  <td>{{ $l['satuan'] }}</td>
                  <td>{{ $l['perihal'] }}</td>
                  <td>{{ $l['tanggal'] }}</td>
                  <td><span class="status-dot {{ $l['prioritas_class'] }}">{{ $l['prioritas'] }}</span></td>
                  <td id="statusLaporan{{ $i }}"><span class="badge {{ $l['status_class'] }}">{{ $l['status'] }}</span></td>
                  <td>
                    <button class="btn btn-ghost btn-sm" type="button" onclick="bukaDetailLaporan({{ $i }})">Lihat Detail</button>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="6" style="text-align:center;color:var(--text-muted);padding:20px;">Belum ada laporan masuk.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== KOORDINASI SATLAK ===== --}}
      <section class="tab-panel" data-tab-panel="koordinasi">
        <div class="section-head">
          <h2>Koordinasi dengan Satlak</h2>
          <p>Permintaan koordinasi antar satuan — misalnya permintaan pengiriman personel dari SDIR ke Satlak.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Satuan Tujuan</th><th>Perihal Koordinasi</th><th>Diminta Oleh</th><th>Tanggal</th><th>Status</th></tr></thead>
              <tbody>
                @foreach($koordinasi as $k)
                <tr>
                  <td>{{ $k['satuan'] }}</td>
                  <td>{{ $k['perihal'] }}</td>
                  <td>{{ $k['diminta_oleh'] }}</td>
                  <td>{{ $k['tanggal'] }}</td>
                  <td><span class="badge {{ $k['status_class'] }}">{{ $k['status'] }}</span></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== DITERUSKAN KE DANPUS ===== --}}
      <section class="tab-panel" data-tab-panel="diteruskan">
        <div class="section-head">
          <h2>Riwayat Diteruskan ke DANPUS</h2>
          <p>Laporan yang sudah diverifikasi WADAN dan sudah dikirim ke DANPUS untuk persetujuan akhir.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Asal Satuan</th><th>Perihal</th><th>Tanggal Diteruskan</th><th>Status di DANPUS</th></tr></thead>
              <tbody>
                @foreach($riwayatDiteruskan as $r)
                <tr>
                  <td>{{ $r['satuan'] }}</td>
                  <td>{{ $r['perihal'] }}</td>
                  <td>{{ $r['tanggal'] }}</td>
                  <td><span class="badge {{ $r['status_class'] }}">{{ $r['status'] }}</span></td>
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

  <style>
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;align-items:center;justify-content:center;padding:20px;}
    .modal-overlay.open{display:flex;}
    .modal-box{background:var(--panel,#0f1a14);border:1px solid var(--border-strong,#2a3a30);border-radius:12px;max-width:720px;width:100%;max-height:80vh;display:flex;flex-direction:column;}
    .modal-head{display:flex;align-items:flex-start;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--border-soft,#22302a);}
    .modal-head h3{margin:0;font-size:16px;}
    .modal-close{background:none;border:none;color:var(--text-muted,#9fb0a8);font-size:22px;line-height:1;cursor:pointer;}
    .modal-close:hover{color:var(--gold-bright,#f2c14e);}
    .modal-body{padding:16px 20px 20px;overflow-y:auto;}
  </style>

  {{-- ===== MODAL KONFIRMASI TERUSKAN / KEMBALIKAN ===== --}}
  <div class="modal-overlay" id="modalKonfirmasiLaporan">
    <div class="modal-box" style="max-width:480px;">
      <div class="modal-head">
        <div>
          <h3 id="konfirmasiJudul">Konfirmasi</h3>
          <p id="konfirmasiSub" style="margin:2px 0 0;font-size:12.5px;color:var(--text-muted);">-</p>
        </div>
        <button type="button" class="modal-close" onclick="tutupKonfirmasiLaporan()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-field full" style="margin-bottom:16px;">
          <label for="konfirmasiCatatan">Catatan (opsional)</label>
          <textarea id="konfirmasiCatatan" rows="3" placeholder="Tulis catatan terkait keputusan ini..."></textarea>
        </div>
        <div class="btn-row" style="justify-content:flex-end;">
          <button type="button" class="btn" onclick="tutupKonfirmasiLaporan()">Batal</button>
          <button type="button" class="btn btn-primary" id="konfirmasiBtnAksi" onclick="konfirmasiLaporanSubmit()">Konfirmasi</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    let laporanAktif = null;

    function bukaKonfirmasiLaporan(index, aksi, satuan, perihal){
      laporanAktif = { index, aksi };
      const judul = aksi === 'teruskan' ? 'Teruskan ke DANPUS' : 'Kembalikan ke Satuan';
      document.getElementById('konfirmasiJudul').textContent = judul;
      document.getElementById('konfirmasiSub').textContent = satuan + ' \u2014 ' + perihal;
      document.getElementById('konfirmasiCatatan').value = '';

      const btnAksi = document.getElementById('konfirmasiBtnAksi');
      btnAksi.textContent = aksi === 'teruskan' ? 'Ya, Teruskan' : 'Ya, Kembalikan';
      btnAksi.className = aksi === 'teruskan' ? 'btn btn-primary' : 'btn btn-ghost-red';

      document.getElementById('modalKonfirmasiLaporan').classList.add('open');
    }

    function tutupKonfirmasiLaporan(){
      document.getElementById('modalKonfirmasiLaporan').classList.remove('open');
      laporanAktif = null;
    }

    function konfirmasiLaporanSubmit(){
      if(!laporanAktif) return;
      const { index, aksi } = laporanAktif;

      const statusCell = document.getElementById('statusLaporan' + index);

      if(aksi === 'teruskan'){
        statusCell.innerHTML = '<span class="badge green">Diteruskan ke DANPUS</span>';
        if(laporanMasukData[index]){ laporanMasukData[index].status = 'Diteruskan ke DANPUS'; laporanMasukData[index].status_class = 'green'; }
      } else {
        statusCell.innerHTML = '<span class="badge red">Dikembalikan</span>';
        if(laporanMasukData[index]){ laporanMasukData[index].status = 'Dikembalikan ke Satuan'; laporanMasukData[index].status_class = 'red'; }
      }

      // Catatan (jika ada) saat ini baru tersimpan sementara di sisi tampilan.
      // Kalau nanti mau disimpan permanen ke database, tinggal kirim nilai
      // document.getElementById('konfirmasiCatatan').value beserta index-nya ke route backend di sini.

      tutupKonfirmasiLaporan();
    }

    document.getElementById('modalKonfirmasiLaporan').addEventListener('click', function(e){
      if(e.target === this) tutupKonfirmasiLaporan();
    });
  </script>

  {{-- ===== MODAL DETAIL LAPORAN MASUK ===== --}}
  <div class="modal-overlay" id="modalDetailLaporan">
    <div class="modal-box" style="max-width:560px;">
      <div class="modal-head">
        <div>
          <h3 id="detailLaporanJudul">-</h3>
          <p id="detailLaporanSub" style="margin:2px 0 0;font-size:12.5px;color:var(--text-muted);">-</p>
        </div>
        <button type="button" class="modal-close" onclick="tutupDetailLaporan()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Asal Satuan</span>
            <span id="detailDiteruskan">-</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Tanggal</span>
            <span id="detailTanggal">-</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Prioritas</span>
            <span id="detailPrioritas">-</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Status</span>
            <span id="detailStatus">-</span>
          </div>
        </div>
        <div class="detail-item full" style="margin-top:14px;">
          <span class="detail-label">Lampiran</span>
          <div id="detailLampiran">-</div>
        </div>
        <div id="detailAksi" class="btn-row" style="margin-top:20px;justify-content:flex-end;"></div>
      </div>
    </div>
  </div>

  <style>
    .detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 20px;}
    .detail-item{display:flex;flex-direction:column;gap:4px;}
    .detail-item.full{grid-column:1 / -1;}
    .detail-label{font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;text-transform:uppercase;color:var(--text-dim);}
  </style>

  <script>
    const laporanMasukData = @json($laporanMasuk);

    function bukaDetailLaporan(index){
      const l = laporanMasukData[index];
      if(!l) return;

      document.getElementById('detailLaporanJudul').textContent = l.perihal;
      document.getElementById('detailLaporanSub').textContent = l.satuan;
      document.getElementById('detailDiteruskan').textContent = l.satuan;
      document.getElementById('detailTanggal').textContent = l.tanggal;
      document.getElementById('detailPrioritas').innerHTML = '<span class="status-dot ' + l.prioritas_class + '">' + l.prioritas + '</span>';
      document.getElementById('detailStatus').innerHTML = '<span class="badge ' + l.status_class + '">' + l.status + '</span>';

      const lampiranWrap = document.getElementById('detailLampiran');
      if(l.lampiran_url){
        lampiranWrap.innerHTML = '<div class="btn-row">' +
          '<a href="' + l.lampiran_url + '" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">Lihat PDF</a>' +
          '<a href="' + l.lampiran_url + '" download class="btn btn-ghost btn-sm">Unduh</a>' +
          '</div>';
      } else {
        lampiranWrap.innerHTML = '<span style="font-size:12.5px;color:var(--text-dim);">Tidak ada lampiran</span>';
      }

      const aksiWrap = document.getElementById('detailAksi');
      const satuanEsc = l.satuan.replace(/'/g, "\\'");
      const perihalEsc = l.perihal.replace(/'/g, "\\'");
      if(l.status === 'Menunggu'){
        aksiWrap.innerHTML =
          '<button type="button" class="btn btn-ghost-red" onclick="tutupDetailLaporan(); bukaKonfirmasiLaporan(' + index + ", 'kembalikan', '" + satuanEsc + "', '" + perihalEsc + "')\">Kembalikan ke Satuan</button>" +
          '<button type="button" class="btn btn-primary" onclick="tutupDetailLaporan(); bukaKonfirmasiLaporan(' + index + ", 'teruskan', '" + satuanEsc + "', '" + perihalEsc + "')\">Teruskan ke DANPUS</button>";
      } else {
        aksiWrap.innerHTML = '<span style="font-size:12.5px;color:var(--text-dim);">Laporan ini sudah diproses.</span>';
      }

      document.getElementById('modalDetailLaporan').classList.add('open');
    }

    function tutupDetailLaporan(){
      document.getElementById('modalDetailLaporan').classList.remove('open');
    }

    document.getElementById('modalDetailLaporan').addEventListener('click', function(e){
      if(e.target === this) tutupDetailLaporan();
    });
  </script>

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
</script>

{{-- ===== ANALITIK VERIFIKASI LAPORAN (RINGKASAN) ===== --}}
<script>
(function () {
  var canvasCheck = document.getElementById('chartLaporanPrioritas');
  if (!canvasCheck || typeof Chart === 'undefined') return;

  var css = getComputedStyle(document.documentElement);
  var cGold = css.getPropertyValue('--gold-bright').trim() || '#f2c14e';
  var cGreen = css.getPropertyValue('--green').trim() || '#3ddc84';
  var cAmber = css.getPropertyValue('--amber').trim() || '#f2a93b';
  var cRed = css.getPropertyValue('--red').trim() || '#e5484d';
  var cMuted = css.getPropertyValue('--text-dim').trim() || '#7d8f87';
  var cText = css.getPropertyValue('--text').trim() || '#e8efe9';
  var cBorder = css.getPropertyValue('--border-soft').trim() || '#22302a';

  var laporanPrioritas = @json($laporanPerPrioritas ?? []);
  var laporanPerSatuan = @json($laporanPerSatuanChart ?? []);
  var statusKoordinasi = @json($statusKoordinasi ?? []);

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

  // ===== Grafik 1: Laporan per prioritas =====
  function drawLaporanPrioritas(type, data) {
    renderChart(
      'chartLaporanPrioritas', type,
      data.map(function (p) { return p.label; }),
      data.map(function (p) { return p.jumlah; }),
      type === 'line' || type === 'radar' ? cGold : [cRed, cAmber, cMuted],
      { label: 'Jumlah Laporan', lineColor: cGold }
    );
  }

  // ===== Grafik 2: Satuan paling aktif melapor =====
  function drawLaporanPerSatuan(type, data) {
    renderChart(
      'chartLaporanPerSatuan', type,
      data.map(function (s) { return s.satuan; }),
      data.map(function (s) { return s.jumlah; }),
      type === 'line' || type === 'radar' ? cGold : cGold,
      { horizontal: type === 'bar', label: 'Jumlah Laporan', lineColor: cGold }
    );
  }

  // ===== Grafik 3: Status permintaan koordinasi =====
  function drawStatusKoordinasi(type, data) {
    var t = type === 'bar' ? 'doughnut' : type; // status komposisi tetap lingkaran di mode default
    renderChart(
      'chartStatusKoordinasi', t,
      data.map(function (s) { return s.label; }),
      data.map(function (s) { return s.jumlah; }),
      [cAmber, cGreen],
      { label: 'Jumlah Permintaan' }
    );
  }

  // Filter tanggal di sini murni simulasi proporsional di sisi tampilan (mengecilkan
  // angka jumlah sesuai rentang yang dipilih), karena histori laporan per tanggal
  // belum tersambung ke database. Kalau nanti sudah ada, bagian ini tinggal diganti
  // pemanggilan data asli sesuai rentang yang dipilih.
  var DATE_RANGE_FACTOR = { '7d': 0.35, '30d': 0.7, '90d': 0.9, 'all': 1 };

  function scaledLaporanPrioritas(factor) {
    return laporanPrioritas.map(function (p) { return { label: p.label, jumlah: Math.max(0, Math.round(p.jumlah * factor)) }; });
  }
  function scaledLaporanPerSatuan(factor) {
    return laporanPerSatuan.map(function (s) { return { satuan: s.satuan, jumlah: Math.max(0, Math.round(s.jumlah * factor)) }; });
  }
  function scaledStatusKoordinasi(factor) {
    return statusKoordinasi.map(function (s) { return { label: s.label, jumlah: Math.max(0, Math.round(s.jumlah * factor)) }; });
  }

  var typeFilterEl = document.getElementById('chartTypeFilterGlobal');
  var dateFilterEl = document.getElementById('chartDateFilterGlobal');
  var gridEl = document.getElementById('chartBoxGrid');

  function redrawAll() {
    var type = typeFilterEl ? typeFilterEl.value : 'bar';
    var factor = DATE_RANGE_FACTOR[dateFilterEl ? dateFilterEl.value : 'all'] || 1;
    if (gridEl) gridEl.classList.toggle('split-mode', type !== 'bar');

    drawLaporanPrioritas(type, scaledLaporanPrioritas(factor));
    drawLaporanPerSatuan(type, scaledLaporanPerSatuan(factor));
    drawStatusKoordinasi(type, scaledStatusKoordinasi(factor));
  }

  redrawAll();

  if (typeFilterEl) typeFilterEl.addEventListener('change', redrawAll);
  if (dateFilterEl) dateFilterEl.addEventListener('change', redrawAll);
})();
</script>

@include('siberad.dashboards.partials.dash-script')
</body>
</html>