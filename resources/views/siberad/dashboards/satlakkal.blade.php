<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Satuan Pelaksanaan Penangkalan — SIBERAD</title>
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo-pussiberad.jpg') }}">
@include('siberad.dashboards.partials.dash-styles')
</head>
<body>

{{-- ===== POPUP DETAIL PROFIL (muncul di tengah layar) ===== --}}
{{-- Ditaruh di luar .shell/.topbar karena .topbar pakai backdrop-filter, yang
     membuat position:fixed di dalamnya ke-scope ke box .topbar saja, bukan ke
     seluruh layar. Popup ini baru muncul saat item di dropdown kecil
     (Profil Saya / Pengaturan Akun / Bantuan & Panduan) diklik. --}}
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

      <div class="side-dropdown" id="laporanDropdown">
        <button type="button" class="side-link side-dropdown-toggle" id="laporanToggle" aria-expanded="false" aria-controls="laporanSubmenu">
          <span class="dot"></span>
          <span class="side-link-label">Laporan Kegiatan</span>
          <svg class="side-dropdown-arrow" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"></path></svg>
        </button>
        <div class="side-dropdown-menu" id="laporanSubmenu">
          <a href="#" class="side-link side-sublink" data-tab-link="tambah-laporan">Buat Laporan</a>
          <a href="#" class="side-link side-sublink" data-tab-link="draft-laporan">Draft Laporan</a>
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

    // Kalau salah satu sub-menu Laporan sedang aktif saat halaman dimuat,
    // buka dropdown-nya supaya kelihatan item mana yang aktif.
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
            @if(($notifikasi ?? collect())->isNotEmpty())
            <span style="position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:var(--red);box-shadow:0 0 0 2px var(--panel,#0c2417);"></span>
            @endif
          </button>

          <div class="profile-dropdown" id="notifDropdown" role="menu" aria-label="Notifikasi">
            <div class="profile-dropdown-head" style="border-bottom:1px solid var(--border-soft);display:flex;justify-content:space-between;align-items:center;gap:8px;">
              <div class="profile-dropdown-name" style="font-size:14px;">Notifikasi</div>
              @if(($notifikasi ?? collect())->isNotEmpty())
              <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                @csrf
                <button type="submit" class="btn-link" style="font-size:11px;color:var(--gold-bright);background:none;border:none;cursor:pointer;">Tandai dibaca</button>
              </form>
              @endif
            </div>

            @forelse(($notifikasi ?? collect()) as $n)
            <div class="profile-dropdown-item" style="align-items:flex-start;white-space:normal;cursor:default;">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--gold-bright)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:2px;">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
              </svg>
              <div>
                <div style="font-size:12.5px;line-height:1.5;color:var(--text);">{{ $n->data['pesan'] ?? 'Status laporan diperbarui.' }}</div>
                <div style="font-size:11px;color:var(--text-dim);margin-top:2px;">{{ $n->created_at->diffForHumans() }}</div>
              </div>
            </div>
            @empty
            <div style="text-align:center;padding:20px 6px 8px;">
              <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 14px;display:block;">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
              </svg>
              <p style="margin:0;font-size:12.5px;line-height:1.6;color:var(--text-muted);">Belum ada notifikasi saat ini.</p>
            </div>
            @endforelse
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

        <div class="section-head">
          <h2>Ringkasan Laporan Kegiatan</h2>
          <p>Rekap laporan kegiatan pemantauan &amp; pemulihan Satuan Pelaksanaan Penangkalan ke DANPUS.</p>
        </div>
        <div class="stat-grid">
          <div class="stat-card">
            <div class="lbl">Total Laporan</div>
            <div class="val">{{ $statsLaporanMonitoring['total'] }}</div>
            <div class="sub">Draft &amp; terkirim</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Draft</div>
            <div class="val" style="color:var(--text-muted);">{{ $statsLaporanMonitoring['draft'] }}</div>
            <div class="sub">Belum dikirim ke DANPUS</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Menunggu Verifikasi</div>
            <div class="val" style="color:var(--amber);">{{ $statsLaporanMonitoring['dikirim'] }}</div>
            <div class="sub">Sudah dikirim, belum diputuskan</div>
          </div>
          <div class="stat-card">
            <div class="lbl">Disetujui DANPUS</div>
            <div class="val" style="color:var(--green-bright);">{{ $statsLaporanMonitoring['disetujui'] }}</div>
            <div class="sub">Laporan yang sudah disetujui</div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head"><div><h3>Laporan Kegiatan Terbaru</h3><p>5 laporan kegiatan pemantauan &amp; pemulihan terakhir yang dibuat.</p></div></div>
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Jenis Kegiatan</th><th>Tanggal Kegiatan</th><th>Dibuat</th><th>Status</th></tr></thead>
              <tbody>
                @forelse($semuaLaporanMonitoring->take(5) as $l)
                <tr>
                  <td>{{ $l->jenis_kegiatan }}</td>
                  <td>{{ $l->tanggal_kegiatan?->translatedFormat('d M Y') ?? '—' }}</td>
                  <td>{{ $l->created_at->translatedFormat('d M Y') }}</td>
                  <td>
                    <span class="badge {{ match($l->status) {
                      'Disetujui' => 'green',
                      'Ditolak' => 'red',
                      'Direvisi' => 'amber',
                      default => '',
                    } }}">{{ $l->status }}</span>
                  </td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;color:var(--text-muted);">Belum ada laporan kegiatan.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== LAPORAN › TAMBAH LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="tambah-laporan">
        <div class="section-head">
          <h2>Buat Laporan</h2>
          <p>Laporkan kegiatan pemantauan &amp; pemulihan ke DANPUS. Bisa disimpan sebagai draft dulu atau langsung dikirim.</p>
        </div>
        <div class="panel">
          @if(session('status'))
          <div class="profile-form-notice" style="margin:22px 22px 0;border-color:var(--green);color:var(--green);">{{ session('status') }}</div>
          @endif
          @if($errors->any())
          <div class="profile-form-notice" style="margin:22px 22px 0;border-color:var(--red);color:var(--red);">{{ $errors->first() }}</div>
          @endif
          <form class="form-grid" method="POST" action="{{ route('laporan-monitoring.store') }}" enctype="multipart/form-data" style="padding:22px;">
            @csrf
            <div class="form-field">
              <label for="jenisKegiatanTambahLaporan">Jenis Kegiatan</label>
              <select id="jenisKegiatanTambahLaporan" name="jenis_kegiatan" required>
                <option value="">— Pilih jenis kegiatan —</option>
                <option value="Pemantauan Rutin">Pemantauan Rutin</option>
                <option value="Pemulihan Sistem">Pemulihan Sistem</option>
                <option value="Pemeliharaan">Pemeliharaan</option>
                <option value="Patroli Siber">Patroli Siber</option>
                <option value="Lainnya">Lainnya</option>
              </select>
            </div>
            <div class="form-field">
              <label for="tanggalKegiatanTambahLaporan">Tanggal Kegiatan</label>
              <input id="tanggalKegiatanTambahLaporan" name="tanggal_kegiatan" type="date" required>
            </div>
            <div class="form-field full">
              <label for="ringkasanKegiatanTambahLaporan">Ringkasan Kegiatan</label>
              <textarea id="ringkasanKegiatanTambahLaporan" name="ringkasan_kegiatan" rows="4" placeholder="Jelaskan kegiatan pemantauan/pemulihan yang dilakukan..." required></textarea>
            </div>
            <div class="form-field full">
              <label for="hasilTambahLaporan">Hasil</label>
              <textarea id="hasilTambahLaporan" name="hasil" rows="3" placeholder="Jelaskan hasil/capaian dari kegiatan ini..." required></textarea>
            </div>
            <div class="form-field full">
              <label for="lampiranTambahLaporan">Upload Lampiran (Foto, PDF, Dokumen)</label>
              <input id="lampiranTambahLaporan" name="lampiran[]" type="file" multiple accept="image/*,.pdf,.doc,.docx">
              <span class="form-hint">Bisa pilih beberapa file sekaligus. Maks. 20 MB per file.</span>
            </div>
            <div class="form-field full" style="display:flex;gap:12px;flex-wrap:wrap;">
              <button class="btn btn-ghost" type="submit" name="aksi" value="draft">Simpan sebagai Draft</button>
              <button class="btn btn-primary" type="submit" name="aksi" value="kirim">Kirim ke DANPUS</button>
            </div>
          </form>
        </div>
      </section>

      {{-- ===== LAPORAN › DRAFT LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="draft-laporan">
        <div class="section-head">
          <h2>Draft Laporan</h2>
          <p>Laporan yang belum dikirim, atau yang dikembalikan DANPUS untuk direvisi. Bisa diedit, ditambah lampiran, atau dikirim kapan saja.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Jenis Kegiatan</th><th>Tanggal Kegiatan</th><th>Status</th><th>Lampiran</th><th>Dibuat</th><th>Aksi</th></tr></thead>
              <tbody>
                @forelse($draftLaporanMonitoring as $d)
                <tr>
                  <td>{{ $d->jenis_kegiatan }}</td>
                  <td>{{ $d->tanggal_kegiatan?->translatedFormat('d M Y') ?? '—' }}</td>
                  <td><span class="badge {{ $d->status === 'Direvisi' ? 'amber' : '' }}">{{ $d->status }}</span></td>
                  <td>{{ $d->lampiran->count() }} file</td>
                  <td>{{ $d->created_at->translatedFormat('d M Y') }}</td>
                  <td>
                    <div class="btn-row">
                      <button class="btn btn-ghost btn-sm" type="button" onclick="bukaDetailLaporanMonitoring({{ $d->id }})">Detail</button>
                      <button class="btn btn-ghost btn-sm" type="button" onclick="bukaEditLaporanMonitoring({{ $d->id }})">Edit</button>
                      <button class="btn btn-ghost btn-sm" type="button" onclick="bukaUploadLampiran({{ $d->id }})">+ Lampiran</button>
                      <form method="POST" action="{{ route('laporan-monitoring.kirim', $d) }}" style="display:inline;">
                        @csrf
                        <button class="btn btn-primary btn-sm" type="submit">Kirim</button>
                      </form>
                      @if($d->status === 'Draft')
                      <form method="POST" action="{{ route('laporan-monitoring.destroy', $d) }}" style="display:inline;" onsubmit="return confirm('Hapus draft ini?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost-red btn-sm" type="submit">Hapus</button>
                      </form>
                      @endif
                    </div>
                  </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">Belum ada draft laporan.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== LAPORAN › STATUS LAPORAN ===== --}}
      <section class="tab-panel" data-tab-panel="status-laporan">
        <div class="section-head">
          <h2>Status Laporan</h2>
          <p>Pantau progres laporan yang sudah dikirim ke DANPUS.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Jenis Kegiatan</th><th>Tanggal Kegiatan</th><th>Tanggal Kirim</th><th>Status</th><th>Detail</th></tr></thead>
              <tbody>
                @forelse($statusLaporanMonitoring as $s)
                <tr>
                  <td>{{ $s->jenis_kegiatan }}</td>
                  <td>{{ $s->tanggal_kegiatan?->translatedFormat('d M Y') ?? '—' }}</td>
                  <td>{{ $s->tanggal_kirim?->translatedFormat('d M Y') ?? '—' }}</td>
                  <td>
                    <span class="status-dot {{ match($s->status) {
                      'Disetujui' => 'ok',
                      'Ditolak' => 'bad',
                      'Direvisi' => 'amber',
                      default => 'warn',
                    } }}">{{ $s->status }}</span>
                  </td>
                  <td><button class="btn btn-ghost btn-sm" type="button" onclick="bukaDetailLaporanMonitoring({{ $s->id }})">Lihat Detail</button></td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);">Belum ada laporan yang dikirim ke DANPUS.</td></tr>
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
          <p>Log lengkap seluruh laporan monitoring &amp; recovery Satuan Pelaksanaan Penangkalan, termasuk draft dan yang sudah diputuskan DANPUS.</p>
        </div>
        <div class="panel">
          <div class="tbl-wrap">
            <table class="dtbl">
              <thead><tr><th>Jenis Kegiatan</th><th>Tanggal Kegiatan</th><th>Dibuat</th><th>Status</th><th>Detail</th></tr></thead>
              <tbody>
                @forelse($semuaLaporanMonitoring as $r)
                <tr>
                  <td>{{ $r->jenis_kegiatan }}</td>
                  <td>{{ $r->tanggal_kegiatan?->translatedFormat('d M Y') ?? '—' }}</td>
                  <td>{{ $r->created_at->translatedFormat('d M Y') }}</td>
                  <td>
                    <span class="badge {{ match($r->status) {
                      'Disetujui' => 'green',
                      'Ditolak' => 'red',
                      'Direvisi' => 'amber',
                      default => '',
                    } }}">{{ $r->status }}</span>
                  </td>
                  <td><button class="btn btn-ghost btn-sm" type="button" onclick="bukaDetailLaporanMonitoring({{ $r->id }})">Lihat Detail</button></td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);">Belum ada riwayat laporan.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {{-- ===== MODAL: DETAIL LAPORAN MONITORING ===== --}}
      <div class="modal-overlay" id="modalDetailLaporanMonitoring">
        <div class="modal-box" style="max-width:560px;">
          <div class="modal-head">
            <div>
              <h3 id="dlmJenisKegiatan">-</h3>
              <p id="dlmTanggalKegiatan" style="margin:2px 0 0;font-size:12.5px;color:var(--text-muted);">-</p>
            </div>
            <button type="button" class="modal-close" onclick="tutupDetailLaporanMonitoring()">&times;</button>
          </div>
          <div class="modal-body">
            <div class="detail-grid">
              <div class="detail-item"><span class="detail-label">Status</span><span id="dlmStatus">-</span></div>
              <div class="detail-item"><span class="detail-label">Tanggal Kirim</span><span id="dlmTanggal">-</span></div>
              <div class="detail-item full"><span class="detail-label">Ringkasan Kegiatan</span><span id="dlmRingkasan">-</span></div>
              <div class="detail-item full"><span class="detail-label">Hasil</span><span id="dlmHasil">-</span></div>
              <div class="detail-item full" id="dlmCatatanWrap" style="display:none;">
                <span class="detail-label">Catatan DANPUS</span>
                <span id="dlmCatatan" style="color:var(--gold-bright);"></span>
              </div>
              <div class="detail-item full">
                <span class="detail-label">Lampiran</span>
                <div id="dlmLampiran" class="btn-row" style="flex-wrap:wrap;">-</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== MODAL: EDIT / REVISI LAPORAN ===== --}}
      <div class="modal-overlay" id="modalEditLaporanMonitoring">
        <div class="modal-box" style="max-width:560px;">
          <div class="modal-head">
            <div><h3>Edit Laporan</h3></div>
            <button type="button" class="modal-close" onclick="tutupEditLaporanMonitoring()">&times;</button>
          </div>
          <div class="modal-body">
            <form method="POST" id="formEditLaporanMonitoring" class="form-grid">
              @csrf @method('PATCH')
              <div class="form-field">
                <label for="elmJenisKegiatan">Jenis Kegiatan</label>
                <select id="elmJenisKegiatan" name="jenis_kegiatan" required>
                  <option value="">— Pilih jenis kegiatan —</option>
                  <option value="Pemantauan Rutin">Pemantauan Rutin</option>
                  <option value="Pemulihan Sistem">Pemulihan Sistem</option>
                  <option value="Pemeliharaan">Pemeliharaan</option>
                  <option value="Patroli Siber">Patroli Siber</option>
                  <option value="Lainnya">Lainnya</option>
                </select>
              </div>
              <div class="form-field">
                <label for="elmTanggalKegiatan">Tanggal Kegiatan</label>
                <input id="elmTanggalKegiatan" name="tanggal_kegiatan" type="date" required>
              </div>
              <div class="form-field full">
                <label for="elmRingkasan">Ringkasan Kegiatan</label>
                <textarea id="elmRingkasan" name="ringkasan_kegiatan" rows="4" required></textarea>
              </div>
              <div class="form-field full">
                <label for="elmHasil">Hasil</label>
                <textarea id="elmHasil" name="hasil" rows="3" required></textarea>
              </div>
              <div class="form-field full">
                <label for="elmLampiran">Tambah Lampiran (opsional)</label>
                <input id="elmLampiran" name="lampiran[]" type="file" multiple accept="image/*,.pdf,.doc,.docx">
                <span class="form-hint">Bisa pilih beberapa file sekaligus. Maks. 20 MB per file.</span>
              </div>
              <div class="form-field full" style="display:flex;justify-content:flex-end;">
                <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {{-- ===== MODAL: UPLOAD LAMPIRAN ===== --}}
      <div class="modal-overlay" id="modalUploadLampiran">
        <div class="modal-box" style="max-width:420px;">
          <div class="modal-head">
            <div><h3>Upload Lampiran</h3></div>
            <button type="button" class="modal-close" onclick="tutupUploadLampiran()">&times;</button>
          </div>
          <div class="modal-body">
            <form method="POST" id="formUploadLampiran" enctype="multipart/form-data" class="form-grid">
              @csrf
              <div class="form-field full">
                <label for="ulInput">Pilih file (foto/PDF/dokumen)</label>
                <input id="ulInput" name="lampiran[]" type="file" multiple required accept="image/*,.pdf,.doc,.docx">
                <span class="form-hint">Bisa pilih beberapa file sekaligus. Maks. 20 MB per file.</span>
              </div>
              <div class="form-field full" style="display:flex;justify-content:flex-end;">
                <button class="btn btn-primary" type="submit">Unggah</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <style>
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;align-items:center;justify-content:center;padding:20px;}
        .modal-overlay.open{display:flex;}
        .modal-box{background:var(--panel,#0f1a14);border:1px solid var(--border-strong,#2a3a30);border-radius:12px;max-width:720px;width:100%;max-height:80vh;display:flex;flex-direction:column;}
        .modal-head{display:flex;align-items:flex-start;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--border-soft,#22302a);}
        .modal-head h3{margin:0;font-size:16px;}
        .modal-close{background:none;border:none;color:var(--text-muted,#9fb0a8);font-size:22px;line-height:1;cursor:pointer;}
        .modal-close:hover{color:var(--gold-bright,#f2c14e);}
        .modal-body{padding:16px 20px 20px;overflow-y:auto;}
        .detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 20px;}
        .detail-item{display:flex;flex-direction:column;gap:4px;}
        .detail-item.full{grid-column:1 / -1;}
        .detail-label{font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;text-transform:uppercase;color:var(--text-dim);}
      </style>

      <script>
        const laporanMonitoringData = @json($laporanMonitoringData);

        function bukaDetailLaporanMonitoring(id) {
          const l = laporanMonitoringData[id];
          if (!l) return;
          document.getElementById('dlmJenisKegiatan').textContent = l.jenis_kegiatan;
          document.getElementById('dlmTanggalKegiatan').textContent = l.tanggal_kegiatan;
          document.getElementById('dlmStatus').textContent = l.status;
          document.getElementById('dlmTanggal').textContent = l.tanggal;
          document.getElementById('dlmRingkasan').textContent = l.ringkasan_kegiatan;
          document.getElementById('dlmHasil').textContent = l.hasil;

          const catatanWrap = document.getElementById('dlmCatatanWrap');
          if (l.catatan_danpus) {
            document.getElementById('dlmCatatan').textContent = l.catatan_danpus;
            catatanWrap.style.display = '';
          } else {
            catatanWrap.style.display = 'none';
          }

          const lampiranWrap = document.getElementById('dlmLampiran');
          if (l.lampiran.length) {
            lampiranWrap.innerHTML = l.lampiran.map(function (d) {
              return '<a href="' + d.url + '" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">' + d.nama + '</a>';
            }).join('');
          } else {
            lampiranWrap.innerHTML = '<span style="font-size:12.5px;color:var(--text-dim);">Belum ada lampiran</span>';
          }

          document.getElementById('modalDetailLaporanMonitoring').classList.add('open');
        }

        function tutupDetailLaporanMonitoring() {
          document.getElementById('modalDetailLaporanMonitoring').classList.remove('open');
        }

        document.getElementById('modalDetailLaporanMonitoring').addEventListener('click', function (e) {
          if (e.target === this) tutupDetailLaporanMonitoring();
        });

        function bukaEditLaporanMonitoring(id) {
          const l = laporanMonitoringData[id];
          if (!l) return;
          const form = document.getElementById('formEditLaporanMonitoring');
          form.action = '/laporan-monitoring/' + id;
          document.getElementById('elmJenisKegiatan').value = l.jenis_kegiatan;
          document.getElementById('elmTanggalKegiatan').value = l.tanggal_kegiatan_raw || '';
          document.getElementById('elmRingkasan').value = l.ringkasan_kegiatan;
          document.getElementById('elmHasil').value = l.hasil;
          document.getElementById('modalEditLaporanMonitoring').classList.add('open');
        }

        function tutupEditLaporanMonitoring() {
          document.getElementById('modalEditLaporanMonitoring').classList.remove('open');
        }

        document.getElementById('modalEditLaporanMonitoring').addEventListener('click', function (e) {
          if (e.target === this) tutupEditLaporanMonitoring();
        });

        function bukaUploadLampiran(id) {
          document.getElementById('formUploadLampiran').action = '/laporan-monitoring/' + id + '/lampiran';
          document.getElementById('modalUploadLampiran').classList.add('open');
        }

        function tutupUploadLampiran() {
          document.getElementById('modalUploadLampiran').classList.remove('open');
        }

        document.getElementById('modalUploadLampiran').addEventListener('click', function (e) {
          if (e.target === this) tutupUploadLampiran();
        });
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

    </div>
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

@include('siberad.dashboards.partials.dash-script')
</body>
</html>