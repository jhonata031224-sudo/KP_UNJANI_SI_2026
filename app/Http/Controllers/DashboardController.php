<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\ResetDataLaporanController;
use App\Models\ActivityLog;
use App\Models\Laporan;
use App\Models\LaporanKendala;
use App\Models\LaporanKendalaTembusan;
use App\Models\LaporanSurat;
use App\Models\LaporanSuratTembusan;
use App\Models\Pengaturan;
use App\Models\PermintaanLaporan;
use App\Models\PermintaanResetPassword;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        $kode = $satuan?->kode ? strtoupper(trim($satuan->kode)) : null;
        // Sumber tunggal utk menu/section mana yang boleh dirender di dashboard
        // user -- lihat Satuan::modulAktif(). Dipakai bareng dgn enforcement di
        // EnsureModulAktif (route-level) supaya menu yang disembunyikan disini
        // juga beneran diblokir kalau diakses langsung lewat URL.
        $modulAktif = $satuan
            ? collect(Satuan::MODUL_HAK_AKSES)->keys()->mapWithKeys(fn ($key) => [$key => $satuan->modulAktif($key)])->all()
            : collect(Satuan::MODUL_HAK_AKSES)->keys()->mapWithKeys(fn ($key) => [$key => true])->all();
        if ($kode === 'ADMIN') return $this->admin($request, $user, $satuan, $modulAktif);
        return $this->pelaporan($user, $satuan, $kode, $modulAktif);
    }

    private function admin(Request $request, $user, $satuan, array $modulAktif): View
    {
        // Urutan tampil: Admin -> Pimpinan -> Direktorat -> Satuan (bukan
        // urutan alfabet/urutan input), sesuai jenjang role di organisasi.
        // Logika urut-nya di User::terurutOrganisasi() -- dipakai juga di
        // respons AJAX Tambah/Ubah/Hapus Pengguna biar urutannya konsisten
        // tanpa reload.
        $semuaPengguna = User::terurutOrganisasi();
        // Urutan satuan (dipakai tab "Data Satuan" & "Hak Akses Pengguna")
        // SELALU ikut jenjang organisasi resmi lewat Satuan::kunciUrutSatuan()
        // -- Danpus -> Wadan -> Urdal -> Pok Analis -> 4 Sdir -> 4 Satlak --
        // bukan urutan alfabet ataupun kapan satuan dibuat. Satuan baru yang
        // kodenya belum ada di Satuan::urutanDalamKategori() otomatis jatuh
        // ke urutan terakhir dalam kategorinya (created_at/id sebagai
        // penentu akhir kalau ada beberapa satuan baru sekaligus). Logika
        // urut-nya di Satuan::terurut() -- dipakai juga di respons AJAX
        // Tambah/Ubah Satuan biar urutannya konsisten tanpa reload.
        $semuaSatuan = Satuan::terurut();
        $permintaanResetPassword = PermintaanResetPassword::with(['user.satuan', 'diprosesOleh'])->latest()->get();
        // 3 chart kecil Beranda (radar/donut/tren) -- diekstrak ke method
        // private (bukan dihitung inline di sini) SUPAYA adminKpiRealtime()
        // di bawah bisa manggil algoritma yang SAMA PERSIS buat refresh
        // realtime-nya, gak ada 2 salinan logika yang gampang drift kalau
        // salah satu diubah lupa yang satu lagi.
        $distribusiPenggunaKategori = $this->distribusiPenggunaKategoriRadar($semuaSatuan);
        $statusLaporanSistem = $this->statusLaporanSistem();
        $trenAktivitas = $this->trenAktivitas();
        // Log aktivitas defaultnya cuma nampilin kemarin-hari ini (1 hari
        // terakhir) -- total baris di tabel activity_logs bakal terus
        // bertambah seiring waktu, jadi kalau ditarik semua sekaligus (atau
        // di-cap angka tetap kayak limit(200) sebelumnya) baik render-nya
        // berat maupun cacah "X dari Y data" di UI jadi nyesatin (nampilin
        // seolah itu semua data, padahal cuma potongan terbaru). Dihitung
        // dari now() setiap request, jadi default-nya otomatis geser
        // mengikuti tanggal berjalan tanpa perlu diubah manual. Filter
        // tanggal ini query langsung dari database sesuai rentang yang
        // diminta, sehingga hitungannya selalu akurat terhadap apa yang
        // sedang ditampilkan.
        $logSampai = $request->filled('log_sampai')
            ? \Carbon\Carbon::parse($request->query('log_sampai'))->endOfDay()
            : now()->endOfDay();
        $logDari = $request->filled('log_dari')
            ? \Carbon\Carbon::parse($request->query('log_dari'))->startOfDay()
            : now()->subDays(1)->startOfDay();
        $logAktivitas = ActivityLog::with('user.satuan')
            ->whereBetween('created_at', [$logDari, $logSampai])
            ->latest('created_at')
            ->get();
        // Tab "Data Pelaporan" (Arsip Data) -- SEMUA permintaan laporan dari
        // Pimpinan ke satuan, baik yang sudah diarsipkan (archived_at
        // terisi) MAUPUN yang masih aktif/belum diarsipkan. Global scope
        // hideArchivedOnPimpinanDashboard di model PermintaanLaporan cuma
        // nge-filter kalau request()->is('dashboard') DAN role DANPUS/WADAN
        // -- Admin (kode ADMIN) tidak kena, jadi query polos ini otomatis
        // sudah termasuk yang arsip juga.
        // Eager-load 'laporans' (jamak, dipakai isSedangRevisi()) & rantai
        // 'tasks.laporans.lampirans' (dipakai hitung "x/y tugas selesai" +
        // isi modal "Lihat Progres" -- checklist per task, tiap task bisa
        // punya checkpoint Laporan dengan lampirannya sendiri) -- tanpa ini
        // kartu+modal di admin.blade.php bakal lazy-load semuanya PER BARIS
        // PER TASK (N+1 berlapis).
        $semuaPelaporan = PermintaanLaporan::with(['tujuanSatuan', 'laporan', 'laporans', 'tasks.laporans.lampirans'])->latest()->get();
        $daftarBackup = app(BackupController::class)->index();
        // Hanya sesi yang benar-benar terautentikasi yang ditampilkan.
        // Baris guest dengan user_id NULL tidak termasuk sesi login aktif.
        $sesiAktif = DB::table('sessions')
            ->whereNotNull('sessions.user_id')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->orderByDesc('sessions.last_activity')
            ->get([
                'sessions.id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'sessions.login_at',
                'sessions.geo_kota',
                'sessions.geo_region',
                'sessions.geo_negara',
                'sessions.geo_isp',
                'sessions.geo_lat',
                'sessions.geo_lon',
                'sessions.geo_sumber',
                'users.name as user_name',
            ]);
        $sesiAktif = \App\Helpers\SesiAnomaliDetector::tandai($sesiAktif);
        // Satuan pengirim laporan = semua satuan SELAIN Admin & Pimpinan
        // (Admin cuma pengelola sistem, Pimpinan/Danpus-Wadan cuma
        // menerima & meninjau, bukan pengirim). Dihitung otomatis dari
        // kategori, bukan daftar kode manual, supaya kategori satuan baru
        // (mis. Kotama) otomatis ikut kehitung tanpa perlu diedit lagi di
        // sini tiap kali ada satuan baru.
        $kodeSatuanPengirim = Satuan::whereNotIn('kategori', [Satuan::KATEGORI_ADMIN, Satuan::KATEGORI_PIMPINAN])
            ->pluck('kode')
            ->all();
        // "Total Pelaporan" di sini (KPI atas & kolom Rekap Laporan) dihitung
        // PER PERIHAL (1 permintaan_laporan_id = 1 Perihal), bukan per baris
        // -- satu Perihal yang di-update progresnya berkali-kali (beberapa
        // baris checkpoint "Progres") tetap dihitung SATU laporan, bukan
        // sebanyak baris checkpoint-nya. Lihat hitungLaporanPerPerihal().
        // Samain sama aturan yang sudah dipakai di dashboard Pimpinan
        // (DashboardController::index).
        $laporanRekapMentah = Laporan::whereIn('satuan_id', Satuan::whereIn('kode', $kodeSatuanPengirim)->pluck('id'))
            ->with('lampirans')
            ->get();
        // Dipakai partial admin-kpi-cards.blade.php buat hitung sparkline 7
        // hari terakhir kartu KPI "Total Surat" (jumlah keseluruhan sistem,
        // sama seperti $stats['total_surat'] di bawah).
        $suratSemuaAdmin = LaporanSurat::get();

        return view('siberad.dashboards.admin', compact('user','satuan','semuaPengguna','semuaSatuan','permintaanResetPassword','distribusiPenggunaKategori','statusLaporanSistem','trenAktivitas','logAktivitas','semuaPelaporan','daftarBackup','sesiAktif','logDari','logSampai','laporanRekapMentah','suratSemuaAdmin') + ['pengaturan' => Pengaturan::current(), 'sesiSayaId' => session()->getId(), 'modulHakAkses' => Satuan::MODUL_HAK_AKSES, 'modulAktif' => $modulAktif, 'resetDataKategori' => ResetDataLaporanController::KATEGORI, 'resetDataCounts' => ResetDataLaporanController::hitungPerKategori(), 'resetDataDetails' => ResetDataLaporanController::ambilDetailPerKategori(), 'stats' => ['total_pengguna' => $semuaPengguna->count(), 'total_satuan' => $semuaSatuan->count(), 'total_laporan' => $this->hitungLaporanPerPerihal($laporanRekapMentah), 'total_surat' => LaporanSurat::count(), 'reset_password_pending' => $permintaanResetPassword->where('status', PermintaanResetPassword::STATUS_MENUNGGU)->count()]]);
    }

    public function adminKpiRealtime(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user()->load('satuan');
        $kode = $user->satuan?->kode ? strtoupper(trim($user->satuan->kode)) : null;
        abort_unless($kode === 'ADMIN', 403);

        $semuaPengguna = User::terurutOrganisasi();
        $semuaSatuan = Satuan::terurut();
        $permintaanResetPassword = PermintaanResetPassword::with(['user.satuan', 'diprosesOleh'])->latest()->get();
        $kodeSatuanPengirim = Satuan::whereNotIn('kategori', [Satuan::KATEGORI_ADMIN, Satuan::KATEGORI_PIMPINAN])->pluck('kode')->all();
        $laporanRekapMentah = Laporan::whereIn('satuan_id', Satuan::whereIn('kode', $kodeSatuanPengirim)->pluck('id'))->with('lampirans')->get();
        $suratSemuaAdmin = LaporanSurat::get();
        // Kartu "Aktivitas Terbaru" cuma butuh 5 aksi terbaru -- default
        // rentang tanggal SAMA kayak yang dipakai admin() (1 hari terakhir),
        // bukan seluruh histori, biar query-nya ringan tiap poll (1 detik).
        $logAktivitasTerbaru = ActivityLog::with('user.satuan')
            ->whereBetween('created_at', [now()->subDays(1)->startOfDay(), now()->endOfDay()])
            ->latest('created_at')
            ->take(5)
            ->get();

        $stats = [
            'total_pengguna' => $semuaPengguna->count(),
            'total_satuan' => $semuaSatuan->count(),
            'total_laporan' => $this->hitungLaporanPerPerihal($laporanRekapMentah),
            'total_surat' => $suratSemuaAdmin->count(),
            'reset_password_pending' => $permintaanResetPassword->where('status', PermintaanResetPassword::STATUS_MENUNGGU)->count(),
        ];
        // 3 chart kecil Beranda (radar "Pengguna per Kategori Satuan", donut
        // "Distribusi Status Laporan", "Tren Aktivitas") -- dulu cuma
        // dirender sekali pas load awal (admin()), sekarang ikut di-refresh
        // tiap poll juga lewat method private yang sama biar gak ada 2
        // salinan logika (lihat komentar di distribusiPenggunaKategoriRadar/
        // statusLaporanSistem/trenAktivitas).
        $statusLaporanSistem = $this->statusLaporanSistem();
        $adminStatusDist = [
            ['label' => 'Disetujui',  'color' => '#22c55e', 'labelColor' => '#22c55e', 'count' => $statusLaporanSistem['disetujui']],
            ['label' => 'Ditolak',    'color' => '#ef4444', 'labelColor' => '#ef4444', 'count' => $statusLaporanSistem['ditolak']],
            ['label' => 'Terlambat',  'color' => '#ff6b6b', 'labelColor' => '#ff6b6b', 'count' => $statusLaporanSistem['terlambat']],
            ['label' => 'Dibatalkan', 'color' => '#c1121f', 'labelColor' => '#e5484d', 'count' => $statusLaporanSistem['dibatalkan']],
        ];

        return response()->json([
            'kpis_html' => view('siberad.dashboards.partials.admin-kpi-cards', [
                'stats' => $stats,
                'semuaPengguna' => $semuaPengguna,
                'semuaSatuan' => $semuaSatuan,
                'laporanRekapMentah' => $laporanRekapMentah,
                'suratSemuaAdmin' => $suratSemuaAdmin,
                'permintaanResetPassword' => $permintaanResetPassword,
            ])->render(),
            'reset_password_terbaru_html' => view('siberad.dashboards.partials.admin-reset-password-terbaru-list', [
                'permintaanResetPasswordTerbaru' => $permintaanResetPassword->take(5),
            ])->render(),
            'aktivitas_terbaru_html' => view('siberad.dashboards.partials.admin-aktivitas-terbaru-list', [
                'logAktivitasTerbaru' => $logAktivitasTerbaru,
            ])->render(),
            'radar_kategori' => $this->distribusiPenggunaKategoriRadar($semuaSatuan),
            'status_laporan' => $statusLaporanSistem,
            'status_donut_total' => array_sum($statusLaporanSistem),
            'status_bd_html' => view('siberad.dashboards.partials.pimpinan-status-distribusi-list', [
                'pimpStatusDist' => $adminStatusDist,
            ])->render(),
            'tren_aktivitas' => $this->trenAktivitas(),
            'server_time' => now()->toIso8601String(),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function pelaporan($user, $satuan, ?string $kode, array $modulAktif): View
    {
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        $permintaanGantiPasswordPending = PermintaanResetPassword::where('user_id', $user->id)
            ->where('status', PermintaanResetPassword::STATUS_MENUNGGU)
            ->latest()
            ->first();
        // 'lampirans' ikut di-eager-load (dulu cuma 'tujuanSatuan') --
        // dibutuhkan hitungLaporanPerPerihal() di bawah buat "Total
        // Pelaporan" kartu KPI Beranda Satuan (mirror kartu KPI Pimpinan).
        $laporanTerkirim = Laporan::with(['tujuanSatuan', 'lampirans'])->where('satuan_id', $satuan->id)->latest()->get();
        // Urutan tampil: Admin -> Pimpinan -> Direktorat -> Satuan, lalu
        // urutan tetap per-kode di dalam kategori yang sama (lihat
        // Satuan::kunciUrutSatuan).
        $urutkanSatuan = fn ($s) => Satuan::kunciUrutSatuan($s->kategori, $s->kode);
        $kodeTujuanDiizinkan = Satuan::kodeTujuanUntuk($kode);
        $tujuan = $kodeTujuanDiizinkan !== null
            ? Satuan::whereIn('kode', $kodeTujuanDiizinkan)->get()->sortBy($urutkanSatuan)->values()
            : Satuan::where('kode', '!=', 'ADMIN')->where('id', '!=', $satuan->id)->get()->sortBy($urutkanSatuan)->values();
        $defaultDanpus = $tujuan->firstWhere('kode', 'DANPUS');
        $mode = $kode === 'SATLAKDUKTEK' ? 'duktek' : 'standar';
        $modePimpinan = in_array($kode, ['DANPUS', 'WADAN'], true);
        $canReview = true;
        $canSend = $kode !== 'DANPUS';
        $description = match ($kode) {
            'SATLAKKAL' => 'Pelaporan kegiatan pemantauan dan pemulihan. Tidak ada monitoring CPU, RAM, storage, network, atau data teknis perangkat.',
            'SATLAKSISOS' => 'Pelaporan kegiatan publikasi, edukasi, informasi, dan aktivitas Siber Sosial.',
            'SATLAKDAK' => 'Pelaporan hasil penindakan dan penanganan insiden keamanan siber secara ringkas.',
            'SATLAKDUKTEK' => 'Pelaporan dukungan teknologi sekaligus monitoring ringkasan laporan dari tiga Satlak operasional.',
            'BINFUNG' => 'Pelaporan kegiatan administrasi dan pembinaan fungsi tanpa mengelola data pribadi personel secara rinci.',
            'BINUM' => 'Pelaporan kegiatan pembinaan dan pengawasan satuan.',
            'DIKLAT' => 'Pelaporan kegiatan pendidikan, pelatihan, dan pengembangan kemampuan.',
            'BINMAT' => 'Pelaporan kondisi dan kebutuhan material/perlengkapan tanpa membangun sistem inventaris penuh.',
            'POKANALIS' => 'Pelaporan hasil analisis dan kajian sebagai satuan yang berdiri sendiri, lapor langsung ke Danpus.',
            'URDAL' => 'Pelaporan urusan dalam sebagai satuan yang berdiri sendiri, lapor langsung ke Danpus.',
            'WADAN' => 'Monitoring dan review laporan antar satuan sebagai bagian dari koordinasi.',
            'DANPUS' => 'Pusat penerimaan, pemantauan, dan peninjauan laporan dari seluruh satuan.',
            default => 'Pelaporan kegiatan dan koordinasi satuan melalui satu alur yang terukur.',
        };

        // Urutan: Urdal -> Pok Analis -> 4 Sdir (Binfung, Binum, Bindiklat,
        // Binmat) -> 4 Satlak (Kal, Dak, Siber Sos, Dukteksi) -> 21 Kotama
        // (21 Sansidam aktif), sesuai urutan organisasi -- samain sama
        // Satuan::kunciUrutSatuan(). 21 Kotama ditambahkan di akhir supaya
        // monitoring Pimpinan ikut menampilkan data mereka, konsisten dengan
        // $kodeSatuanPengirim yang sudah include Kotama lewat
        // whereNotIn(['admin','pimpinan']) di bagian atas.
        $kodeKotama = Satuan::KODE_KOTAMA;
        $kodeSatuanPelaksanaUrut = array_merge([
            'URDAL', 'POKANALIS',
            'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT',
            'SATLAKKAL', 'SATLAKDAK', 'SATLAKSISOS', 'SATLAKDUKTEK',
        ], $kodeKotama);
        $permintaanLaporan = $modePimpinan
            ? PermintaanLaporan::with(['pembuat.satuan','tujuanSatuan','laporan','laporans','tasks.laporans'])
                ->whereHas('pembuat.satuan', fn ($q) => $q->whereIn('kode', ['DANPUS','WADAN']))
                ->latest('id')
                ->get()
            : PermintaanLaporan::with(['pembuat.satuan','tujuanSatuan','laporan','laporans','tasks'])
                ->where('tujuan_satuan_id', $satuan->id)
                // STATUS_DIBATALKAN ikut supaya kartu yang dibatalkan Pimpinan
                // TETAP kelihatan satuan (read-only/locked), bukan ilang gitu
                // aja -- sejajar dengan dashboard Pimpinan yang juga masih
                // nampilin Dibatalkan di daftar aktif (bisa dibuka lagi lewat
                // Edit Deadline). Baru pindah ke Riwayat kalau Pimpinan
                // arsipkan manual (archived_at keisi).
                ->whereIn('status', [PermintaanLaporan::STATUS_BELUM, PermintaanLaporan::STATUS_DIKERJAKAN, PermintaanLaporan::STATUS_PEMERIKSAAN, PermintaanLaporan::STATUS_DIBATALKAN])
                // Terlambat yang sudah diarsipkan Pimpinan (lihat
                // $riwayatLaporan di bawah) sengaja gak dobel nongol di sini
                // lagi -- raw status-nya bisa aja masih "Sedang dikerjakan"
                // (isTerlambat() dihitung live, bukan status tersimpan).
                ->whereNull('archived_at')
                ->latest('deadline_at')
                ->get();
        // Riwayat Laporan Pimpinan (#riwayat) -- KARTU read-only (partial
        // permintaan-laporan-pimpinan-card mode riwayatMode), sejajar dengan
        // Riwayat Laporan Satuan. Isinya SEMUA permintaan Danpus/Wadan yang
        // sudah diarsipkan: keputusan akhir Disetujui/Ditolak (archived_at
        // keisi otomatis di LaporanController::updateStatus) + Terlambat/
        // Dibatalkan yang diarsipkan manual. Global scope hideArchivedOn...
        // di-bypass di sini (kalau tidak, whereNotNull('archived_at') selalu
        // kosong pas request /dashboard). Realtime-nya lewat endpoint
        // permintaan-laporan.realtime?history=1.
        $riwayatLaporanPimpinan = $modePimpinan
            ? PermintaanLaporan::withoutGlobalScope('hideArchivedOnPimpinanDashboard')
                ->with(['pembuat.satuan','tujuanSatuan','laporan','laporans','tasks.laporans'])
                ->whereHas('pembuat.satuan', fn ($q) => $q->whereIn('kode', ['DANPUS','WADAN']))
                ->whereNotNull('archived_at')
                ->latest('archived_at')
                ->get()
            : collect();
        // Riwayat Laporan (satuan) -- tampilannya SAMA persis kartu Permintaan
        // Laporan (lihat permintaan-laporan-item.blade.php, yang otomatis
        // render mode read-only "Lihat Progres" begitu laporan_id keisi ATAU
        // Terlambat/Dibatalkan) -- isinya SEMUA permintaan yang sudah
        // diarsipkan: (1) Selesai (Disetujui/Ditolak Pimpinan, archived_at
        // keisi otomatis di LaporanController::updateStatus()), (2) Terlambat/
        // Dibatalkan yang SENGAJA diarsipkan Pimpinan (bukan diperpanjang
        // deadline-nya) lewat PermintaanLaporanController::archive().
        $riwayatLaporan = $modePimpinan
            ? collect()
            : PermintaanLaporan::with(['pembuat.satuan','tujuanSatuan','laporan','laporans','tasks'])
                ->where('tujuan_satuan_id', $satuan->id)
                ->whereNotNull('archived_at')
                ->latest('archived_at')
                ->get();
        $satuanPermintaanLaporan = $modePimpinan
            ? Satuan::whereIn('kode', $kodeSatuanPelaksanaUrut)->get()
                ->sortBy(fn ($s) => array_search($s->kode, $kodeSatuanPelaksanaUrut))
                ->values()
            : collect();

        $monitoringSatlak = collect(); $laporanSatlak = collect();
        if ($mode === 'duktek') {
            $satlakIds = Satuan::whereIn('kode', ['SATLAKKAL','SATLAKSISOS','SATLAKDAK'])->pluck('id');
            $laporanSatlak = Laporan::with(['satuan','tujuanSatuan'])->whereIn('satuan_id', $satlakIds)->latest()->get();
            $monitoringSatlak = Satuan::whereIn('kode', ['SATLAKKAL','SATLAKSISOS','SATLAKDAK'])->get()->sortBy($urutkanSatuan)->values()->map(fn ($satlak) => ['nama' => $satlak->nama, 'total' => $laporanSatlak->where('satuan_id',$satlak->id)->count()]);
        }
        $monitoringPimpinanSatlak = collect();
        $laporanPimpinanSatlak = collect();
        if ($modePimpinan) {
            $satuanPimpinanIds = Satuan::whereIn('kode', $kodeSatuanPelaksanaUrut)->pluck('id');
            // Tiap checkpoint progres tersimpan sebagai baris Laporan
            // tersendiri demi riwayat (lihat komentar di
            // LaporanController::updateProgres) -- itu laporan beneran (ada
            // isinya sendiri per tahap) jadi masing-masing tetap dihitung.
            // Yang JANGAN ikut dobel-dihitung adalah laporan FINAL yang
            // sempat ditolak/revisi lalu dikirim ulang (mis. Tahap Akhir ->
            // ditolak -> Revisi -> ditolak lagi -> Revisi lagi -> disetujui):
            // itu semua satu deliverable yang sama, cuma baris terbaru (hasil
            // akhirnya sekarang) yang dihitung, versi-versi lama yang sudah
            // "ketimpa" resubmit tidak.
            $laporanPimpinanSatlak = Laporan::with([
                    'satuan',
                    'tujuanSatuan',
                    'lampirans',
                    // Global scope hideArchivedOnPimpinanDashboard (lihat
                    // PermintaanLaporan::booted()) nge-filter permintaan yang
                    // sudah diarsip supaya gak nongol lagi di tab Permintaan
                    // Laporan yang aktif -- tapi timeline "Riwayat Aktivitas"
                    // di sini butuh data permintaan-nya TERLEPAS dari status
                    // arsip (laporan yang permintaannya udah diarsip/Selesai
                    // tetap harus nampilin 5 tahap Permintaan Terkirim ->
                    // Laporan Selesai, bukan jatuh ke fallback 3 tahap "laporan
                    // tanpa permintaan"). Tanpa withoutGlobalScope ini,
                    // permintaanLaporan() balik null begitu diarsip.
                    'permintaanLaporan' => fn ($q) => $q->withoutGlobalScope('hideArchivedOnPimpinanDashboard'),
                ])
                ->whereIn('satuan_id', $satuanPimpinanIds)
                ->latest()
                ->get()
                ->groupBy(fn ($l) => $l->permintaan_laporan_id ?? 'single-'.$l->id)
                ->flatMap(function ($group) {
                    $progres = $group->where('status', Laporan::STATUS_PROGRES);
                    $final = $group->reject(fn ($l) => $l->status === Laporan::STATUS_PROGRES)->sortByDesc('id')->take(1);
                    return $progres->merge($final);
                })
                ->values();
            // "Ringkasan Aktivitas Seluruh Satuan" ngitung SEMUA permintaan
            // yang ditugaskan ke satuan itu (terlepas udah dikerjakan atau
            // belum) -- beda dari $permintaanLaporan yang sengaja udah
            // difilter cuma yang masih actionable (tanpa Selesai/
            // Dibatalkan) khusus buat tab "Permintaan Laporan".
            $semuaPermintaanPimpinanSatlak = PermintaanLaporan::whereIn('tujuan_satuan_id', $satuanPimpinanIds)->get();
            $monitoringPimpinanSatlak = Satuan::whereIn('id', $satuanPimpinanIds)->get()
                ->sortBy(fn ($satuanPimpinan) => array_search($satuanPimpinan->kode, $kodeSatuanPelaksanaUrut))
                ->values()
                ->map(fn ($satuanPimpinan) => [
                'id' => $satuanPimpinan->id,
                'kode' => $satuanPimpinan->kode,
                'nama' => $satuanPimpinan->nama,
                // Dipakai di kartu "Ringkasan Aktivitas Seluruh Satuan" --
                // beda dari 'total' (buat grafik "Laporan per Satuan"),
                // ini ngitung SEMUA permintaan yang ditugaskan ke satuan
                // itu, terlepas udah dikerjakan/ada laporannya atau belum.
                'total_permintaan' => $semuaPermintaanPimpinanSatlak->where('tujuan_satuan_id', $satuanPimpinan->id)->count(),
                // "Total Pelaporan" dihitung PER PERIHAL (1 permintaan_laporan_id
                // = 1 Perihal), bukan per baris -- satu Perihal yang di-update
                // progresnya berkali-kali (beberapa baris checkpoint "Progres")
                // tetap dihitung SATU laporan. Lihat hitungLaporanPerPerihal().
                'total' => $this->hitungLaporanPerPerihal($laporanPimpinanSatlak->where('satuan_id', $satuanPimpinan->id)),
                'menunggu' => $laporanPimpinanSatlak->where('satuan_id', $satuanPimpinan->id)->where('status', 'Menunggu')->count(),
                'diterima' => $laporanPimpinanSatlak->where('satuan_id', $satuanPimpinan->id)->filter(fn ($l) => str_contains(strtolower((string) $l->status), 'setuj') || str_contains(strtolower((string) $l->status), 'diterima'))->count(),
                'ditolak' => $laporanPimpinanSatlak->where('satuan_id', $satuanPimpinan->id)->filter(fn ($l) => str_contains(strtolower((string) $l->status), 'tolak'))->count(),
            ]);
            // ===== Laporan Kendala: 21 Kasansi (Kotama) -> DANPUS =====
            // Kasansi kirim kendala/laporan rutin bebas kapan saja (tidak
            // terikat Permintaan Laporan) LANGSUNG ke Danpus. Danpus & Wadan
            // berdua bisa melihat & menindaklanjuti -- disatukan lewat kode
            // satuan Danpus supaya sengaja TIDAK bercampur dengan
            // $permintaanLaporan (alur "kebutuhan khusus" yang diminta
            // Danpus/Wadan lebih dulu), lihat komentar di
            // LaporanKendalaController.
            //
            // Begitu Danpus menekan "Konfirmasi & Arsipkan" (status jadi
            // Dikonfirmasi, confirmed_at terisi), record otomatis pindah
            // dari daftar "Kendala Kasansi" (masih actionable) ke submenu
            // "Arsip Kendala Kasansi" -- makanya keduanya dipisah lewat
            // whereNull/whereNotNull('confirmed_at'), BUKAN sekadar filter
            // status, supaya laporan yang ditolak pun tetap bisa diarsipkan.
            $danpusSatuanId = Satuan::where('kode', 'DANPUS')->value('id');
            // Laporan yang masih mampir di tembusan (Menunggu Tembusan)
            // sengaja DIKECUALIKAN -- baru muncul di sini begitu Kasansi
            // menekan "Kirim ke Danpus" (LaporanKendalaController::teruskan()).
            $kendalaMasuk = $danpusSatuanId
                ? LaporanKendala::with(['satuan', 'lampirans'])
                    ->where('tujuan_satuan_id', $danpusSatuanId)
                    ->whereNull('confirmed_at')
                    ->where('status', '!=', LaporanKendala::STATUS_MENUNGGU_TEMBUSAN)
                    ->latest()
                    ->get()
                : collect();
            $kendalaArsip = $danpusSatuanId
                ? LaporanKendala::with(['satuan', 'confirmedBy', 'lampirans'])->where('tujuan_satuan_id', $danpusSatuanId)->whereNotNull('confirmed_at')->latest('confirmed_at')->get()
                : collect();

            // ===== Surat Masuk: surat dari Kasansi ke SATU tujuan bebas,
            // tanpa tembusan & tanpa progres -- lihat komentar
            // LaporanSuratController. Danpus/Wadan bisa saja jadi salah
            // satu tujuan surat, sama seperti satuan lain manapun. Cuma yang
            // masih MENUNGGU -- yang sudah dikonfirmasi pindah ke $suratArsip
            // di bawah (niru pola Surat Keluar: begitu dikonfirmasi, otomatis
            // pindah ke Arsip Surat, bukan nyangkut selamanya di Surat Masuk).
            $isDanpusKode = $kode === 'DANPUS';
            // Wadan: surat masuk termasuk yang sudah dikonfirmasi TAPI belum
            // diteruskan ke satuan -- surat baru pindah ke Arsip setelah
            // Wadan klik "Teruskan Surat" (status berubah ke menunggu dengan
            // tujuan_satuan_id baru / bukan Wadan lagi). Danpus tetap pakai
            // filter STATUS_MENUNGGU saja (alur Danpus berbeda).
            $isWadanKode = $kode === 'WADAN';
            $suratMasukUtama = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'riwayats.penerimaSatuan', 'tembusans.satuan'])
                ->where('tujuan_satuan_id', $satuan->id)
                ->where('is_selesai', false)
                ->when($isWadanKode, function ($q) {
                    // Wadan: ambil menunggu ATAU sudah dikonfirmasi (belum diteruskan)
                    $q->whereIn('status', [LaporanSurat::STATUS_MENUNGGU, LaporanSurat::STATUS_DIKONFIRMASI]);
                }, function ($q) {
                    // Danpus & lainnya: hanya menunggu
                    $q->where('status', LaporanSurat::STATUS_MENUNGGU);
                })
                ->when($isDanpusKode, function ($q) use ($satuan) {
                    $q->whereHas('riwayats', function ($rq) use ($satuan) {
                        $rq->where('penerima_satuan_id', $satuan->id);
                    });
                })
                ->latest()
                ->get();

            $suratTembusanMasuk = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'riwayats.penerimaSatuan', 'tembusans.satuan'])
                ->where('is_selesai', false)
                ->whereHas('tembusans', function ($tq) use ($satuan) {
                    $tq->where('satuan_id', $satuan->id)
                       ->whereNull('dikonfirmasi_at');
                })
                ->where('tujuan_satuan_id', '!=', $satuan->id)
                ->latest()
                ->get();

            $suratMasuk = $suratMasukUtama->concat($suratTembusanMasuk)->unique('id')->values();

            // ===== Menu Surat Danpus/Wadan =====
            $suratTerkirim = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'riwayats.penerimaSatuan', 'tembusans.satuan'])
                ->where('satuan_id', $satuan->id)
                ->where('is_selesai', false)
                // Danpus: alur 3 step terpenuhi -> otomatis pindah ke Arsip.
                ->when($isDanpusKode, fn ($q) => $q->alurBelumTuntasSisiPengirim())
                ->latest()
                ->get();

            $suratArsip = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'riwayats.penerimaSatuan', 'tembusans.satuan'])
                ->where(function ($q) use ($satuan, $isWadanKode, $isDanpusKode) {
                    $q->where(function ($sub) use ($satuan, $isWadanKode, $isDanpusKode) {
                        $sub->where(function ($own) use ($satuan, $isDanpusKode) {
                                // Pengirim asli. Khusus Danpus: baru masuk Arsip kalau
                                // is_selesai ATAU alur 3 step terpenuhi (Wadan sudah
                                // meneruskan & satuan tujuan akhir sudah konfirmasi) --
                                // BUKAN cuma karena Wadan baru konfirmasi terima, biar
                                // tidak tampil ganda di Surat Keluar & Arsip.
                                $own->where('satuan_id', $satuan->id);
                                if ($isDanpusKode) {
                                    $own->where(function ($fin) {
                                        $fin->where('is_selesai', true)
                                            ->orWhere(fn ($t) => $t->alurTuntasSisiPengirim());
                                    });
                                }
                            })
                            ->orWhere(function ($tj) use ($satuan, $isWadanKode) {
                                $tj->where('tujuan_satuan_id', $satuan->id);
                                // Wadan: surat masuk yang statusnya DIKONFIRMASI
                                // TAPI masih di tangan Wadan (belum diteruskan,
                                // is_selesai masih false) SENGAJA dikecualikan
                                // dari Arsip -- supaya tetap nyangkut & tetap
                                // actionable (tombol Teruskan Surat) di Surat
                                // Masuk sampai Wadan benar-benar meneruskannya
                                // (tujuan_satuan_id pindah dari Wadan) atau
                                // sampai alurnya tuntas (is_selesai).
                                if ($isWadanKode) {
                                    $tj->where(function ($w) {
                                        $w->where('is_selesai', true)
                                          ->orWhere('status', '!=', LaporanSurat::STATUS_DIKONFIRMASI);
                                    });
                                }
                            });
                    })
                    ->where(function ($st) {
                        $st->where('status', LaporanSurat::STATUS_DIKONFIRMASI)
                           ->orWhere('is_selesai', true);
                    });
                })
                ->orWhere(function ($q) use ($satuan) {
                    $q->whereHas('tembusans', function ($tq) use ($satuan) {
                        $tq->where('satuan_id', $satuan->id)
                           ->whereNotNull('dikonfirmasi_at');
                    });
                })
                ->orWhere(function ($q) use ($satuan) {
                    $q->where('is_selesai', true)
                      ->whereHas('tembusans', function ($tq) use ($satuan) {
                          $tq->where('satuan_id', $satuan->id)
                             ->where('jenis', LaporanSuratTembusan::JENIS_HASIL_RC);
                      });
                })
                ->latest()
                ->get()
                ->unique('id')
                ->values();

            $satuanSuratTujuanPilihan = Satuan::where('id', '!=', $satuan->id)->where('kode', '!=', 'ADMIN')->get()->sortBy($urutkanSatuan)->values();

            // DANPUS & WADAN dulunya 1 view bareng (laporan-danpus-shell & laporan-wadan-shell).
            // Sekarang dipisah jadi file sendiri-sendiri (isi & wewenang
            // TETAP SAMA persis) supaya perombakan menu Surat ke depan bisa
            // digarap per satuan tanpa saling nyenggol.
            $shellPimpinan = $kode === 'WADAN' ? 'siberad.dashboards.laporan-wadan-shell' : 'siberad.dashboards.laporan-danpus-shell';

            return view($shellPimpinan, compact('user','satuan','monitoringPimpinanSatlak','laporanPimpinanSatlak','mode','modePimpinan','canReview','canSend','description','permintaanLaporan','riwayatLaporanPimpinan','satuanPermintaanLaporan','permintaanGantiPasswordPending','modulAktif','kendalaMasuk','kendalaArsip','suratMasuk','suratTerkirim','suratArsip','satuanSuratTujuanPilihan') + ['pengaturan' => Pengaturan::current()]);
        }
        // Terlambat/Dibatalkan dihitung dari SELURUH permintaan laporan yang
        // ditujukan ke satuan ini, bukan $permintaanLaporan (yang sengaja
        // sudah difilter cuma yang masih actionable, tanpa Selesai/
        // Dibatalkan, khusus buat daftar tugas di tab "Permintaan Laporan").
        $permintaanLaporanSemua = PermintaanLaporan::where('tujuan_satuan_id', $satuan->id)->get();

        // ===== Laporan Kendala: 21 Kasansi (Kotama) -> DANPUS =====
        // Kasansi bisa kirim kendala/laporan rutin bebas kapan saja (tidak
        // terikat Permintaan Laporan) LANGSUNG ke Danpus (bukan ke Satlak
        // lagi) -- lihat komentar di LaporanKendalaController. Di sini cuma
        // perlu riwayat kirim Kasansi sendiri; yang menerima & menindak
        // lanjuti (Danpus/Wadan) sudah ditangani di cabang $modePimpinan di
        // atas, jadi TIDAK bercampur dengan dashboard non-pimpinan ini.
        $isKasansi = in_array($kode, Satuan::KODE_KOTAMA, true);
        // Dipisah 2: yang masih aktif (belum dikonfirmasi Danpus) tetap di
        // tab "Kirim Laporan", sedangkan yang statusnya sudah Dikonfirmasi
        // otomatis pindah ke tab "Arsip Kendala" -- SENGAJA dipisah dari
        // $laporanTerkirim/tab "Riwayat Laporan" di bawah karena itu untuk
        // model Laporan biasa, bukan LaporanKendala.
        $kendalaTerkirimSemua = $isKasansi
            ? LaporanKendala::with(['tujuanSatuan', 'tembusans.satuan', 'lampirans'])->where('satuan_id', $satuan->id)->latest()->get()
            : collect();
        $kendalaTerkirim = $kendalaTerkirimSemua->where('status', '!=', LaporanKendala::STATUS_DIKONFIRMASI)->values();
        $kendalaArsip = $kendalaTerkirimSemua->where('status', LaporanKendala::STATUS_DIKONFIRMASI)->values();
        $kodeTembusanKasansi = Satuan::kodeTembusanKasansi();
        // Pilihan checkbox "Tembusan ke" di form Kirim Laporan (dropdown 4
        // Satlak + 4 Sdir), cuma perlu disiapkan buat Kasansi.
        $satuanTembusanPilihan = $isKasansi
            ? Satuan::whereIn('kode', $kodeTembusanKasansi)->get()->sortBy($urutkanSatuan)->values()
            : collect();

        // ===== Tembusan (CC) laporan kendala Kasansi -> 4 Satlak/4 Sdir =====
        // SENGAJA terpisah total dari $permintaanLaporan/$laporanTerkirim di
        // atas (alur Danpus/Wadan <-> satuan pelaksana) -- ini cuma daftar
        // info/koordinasi read-only, lihat komentar LaporanKendalaTembusan.
        // Begitu satuan penerima mengisi feedback (submenu "Tembusan
        // Kendala" -> tombol Detail -> kirim balasan), baris itu dianggap
        // selesai dan otomatis pindah ke submenu "Arsip Kendala" -- sama
        // pola dengan Kirim Kendala/Arsip Kendala milik Kasansi di atas.
        $isPenerimaTembusan = in_array($kode, $kodeTembusanKasansi, true);
        $tembusanMasukSemua = $isPenerimaTembusan
            ? LaporanKendalaTembusan::with(['laporanKendala.satuan', 'laporanKendala.lampirans', 'dibacaOleh'])
                ->where('satuan_id', $satuan->id)
                ->latest()
                ->get()
            : collect();
        $tembusanMasuk = $tembusanMasukSemua->whereNull('feedback')->values();
        $tembusanArsip = $tembusanMasukSemua->whereNotNull('feedback')->values();

        // ===== Surat: Kasansi (21 Sansidam), 4 Satlak, 4 Sdir (Pembinaan),
        // Urdal, dan Pok Analis semuanya bisa Surat Keluar ke SATU tujuan bebas.
        // $bisaKirimSurat sengaja DIPISAH dari $isKasansi agar logika Kendala
        // (hanya Kasansi) tidak ikut terpengaruh.
        // Surat berstatus 'menunggu_konfirmasi' tetap di Surat Keluar.
        // Surat berstatus 'dikonfirmasi' pindah ke Arsip Surat.
        $bisaKirimSurat = $isKasansi
            || in_array($kode, Satuan::KODE_SATLAK, true)
            || in_array($kode, Satuan::KODE_PEMBINAAN, true)
            || in_array($kode, Satuan::KODE_UNSUR_PELAYANAN, true)
            || in_array($kode, Satuan::KODE_UNSUR_PEMBANTU_PIMPINAN, true);
        $suratTerkirim = $bisaKirimSurat
            ? LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'riwayats.penerimaSatuan', 'tembusans.satuan'])
                ->where('satuan_id', $satuan->id)
                ->where('is_selesai', false)
                ->latest()
                ->get()
            : collect();

        // Alur naik (balasan): surat turun dari Danpus yang sudah di-ACC satuan ini
        // TAPI belum dibalas TETAP di Surat Masuk (tombol "Kirim Surat"), belum
        // masuk Arsip. Satuan bawaan alur (Danpus/Wadan/Urdal) tidak terkena.
        $bolehKirimBalasan = LaporanSurat::satuanBolehKirimBalasanNaik($kode);

        $suratArsip = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'riwayats.penerimaSatuan', 'tembusans.satuan'])
            ->where(function ($q) use ($satuan, $bolehKirimBalasan) {
                $q->where(function ($sub) use ($satuan) {
                    $sub->where('satuan_id', $satuan->id)
                        ->orWhere('tujuan_satuan_id', $satuan->id);
                })
                ->where(function ($st) use ($bolehKirimBalasan) {
                    $st->where(function ($d) use ($bolehKirimBalasan) {
                        $d->where('status', \App\Models\LaporanSurat::STATUS_DIKONFIRMASI);
                        if ($bolehKirimBalasan) {
                            $d->whereDoesntHave('satuan', fn ($a) => $a->where('kode', 'DANPUS'));
                        }
                    })->orWhere('is_selesai', true);
                });
            })
            ->orWhere(function ($q) use ($satuan) {
                $q->whereHas('tembusans', function ($tq) use ($satuan) {
                    $tq->where('satuan_id', $satuan->id)
                       ->whereNotNull('dikonfirmasi_at');
                });
            })
            ->orWhere(function ($q) use ($satuan) {
                $q->where('is_selesai', true)
                  ->whereHas('tembusans', function ($tq) use ($satuan) {
                      $tq->where('satuan_id', $satuan->id)
                         ->where('jenis', LaporanSuratTembusan::JENIS_HASIL_RC);
                  });
            })
            ->orWhere(function ($q) use ($satuan) {
                // Pernah menangani (tercatat di riwayat alur) tapi surat sekarang
                // sudah di satuan lain -- mis. balasan yang sudah dikirim satuan
                // ini ke Wadan. Sama dengan bagian (d) Arsip di
                // LaporanSuratController::realtime(), supaya render awal tidak
                // beda dengan hasil polling.
                $q->where('satuan_id', '!=', $satuan->id)
                  ->where('tujuan_satuan_id', '!=', $satuan->id)
                  ->whereHas('riwayats', function ($rq) use ($satuan) {
                      $rq->where('pengirim_satuan_id', $satuan->id)
                         ->orWhere('penerima_satuan_id', $satuan->id);
                  });
            })
            ->latest()
            ->get()
            ->unique('id')
            ->values();

        $satuanSuratTujuanPilihan = $bisaKirimSurat
            ? Satuan::where('id', '!=', $satuan->id)->where('kode', '!=', 'ADMIN')->get()->sortBy($urutkanSatuan)->values()
            : collect();

        $suratMasukUtama = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'riwayats.penerimaSatuan', 'tembusans.satuan'])
            ->where('tujuan_satuan_id', $satuan->id)
            ->where(function ($x) use ($satuan, $bolehKirimBalasan) {
                $x->where('status', \App\Models\LaporanSurat::STATUS_MENUNGGU);
                if ($bolehKirimBalasan) {
                    $x->orWhere(fn ($m) => $m->menungguBalasanSatuan($satuan->id));
                }
            })
            ->where('is_selesai', false)
            ->latest()
            ->get();

        $suratTembusanMasuk = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'riwayats.penerimaSatuan', 'tembusans.satuan'])
            ->where('is_selesai', false)
            ->whereHas('tembusans', function ($tq) use ($satuan) {
                $tq->where('satuan_id', $satuan->id)
                   ->whereNull('dikonfirmasi_at');
            })
            ->where('tujuan_satuan_id', '!=', $satuan->id)
            ->latest()
            ->get();

        $suratMasuk = $suratMasukUtama->concat($suratTembusanMasuk)->unique('id')->values();

        // ===== 3 kartu KPI Beranda Satuan (Total Pelaporan/Surat/Kendala
        // Kasansi) -- MIRROR PERSIS kartu KPI Beranda Pimpinan, pakai
        // partial yang SAMA (partials/pimpinan-kpi-cards.blade.php) supaya
        // style+algoritma sparkline gak pernah drift antara dua dashboard.
        // Lihat satuanKpiRealtime() di bawah buat versi poll-nya.
        // "Total Pelaporan" dihitung PER PERIHAL sama seperti versi
        // Pimpinan (lihat hitungLaporanPerPerihal). "Total Kendala Kasansi"
        // digabung dari DUA kemungkinan sumber tergantung peran satuan ini:
        // Kasansi (21 Kotama) menghitung kendala yang MEREKA KIRIM
        // ($kendalaTerkirim/$kendalaArsip), sedangkan penerima tembusan (4
        // Satlak/4 Sdir) menghitung tembusan yang MASUK ke mereka
        // ($tembusanMasuk/$tembusanArsip) -- kedua pasangan itu SALING
        // EKSKLUSIF (satuan yang bukan keduanya dapat 4 koleksi kosong
        // semua, hasil akhirnya 0, itu benar/bukan bug).
        $satuanTotalPelaporan = $this->hitungLaporanPerPerihal($laporanTerkirim);
        $kendalaKasansiKpiAktif = $kendalaTerkirim->concat($tembusanMasuk);
        $kendalaKasansiKpiArsip = $kendalaArsip->concat($tembusanArsip);

        // ===== "Distribusi Status Laporan" (donut) Beranda Satuan -- MIRROR
        // PERSIS kartu Distribusi Status Laporan Beranda Pimpinan (4
        // kategori sama: Disetujui/Ditolak/Terlambat/Dibatalkan, warna sama,
        // partial pimpinan-status-distribusi-list.blade.php dipakai
        // bareng). Bedanya cuma cakupan datanya: punya Pimpinan seluruh
        // satuan pelaksana, punya di sini cuma laporan/permintaan MILIK
        // satuan ini sendiri ($laporanTerkirim/$permintaanLaporanSemua yang
        // udah ada). Dipisah jadi variabel sendiri (bukan langsung di
        // dalam array 'stats' di bawah) supaya gak hitung ulang filter yang
        // sama 2x -- 'stats' di bawah REUSE variabel ini juga.
        $satuanDisetujui = $laporanTerkirim->filter(fn($l) => str_contains(strtolower((string)$l->status),'setuj') || str_contains(strtolower((string)$l->status),'diterima'))->count();
        $satuanDitolak = $laporanTerkirim->filter(fn($l) => str_contains(strtolower((string)$l->status),'tolak'))->count();
        $satuanTerlambat = $permintaanLaporanSemua->filter(fn($p) => $p->isTerlambat())->count();
        $satuanDibatalkan = $permintaanLaporanSemua->where('status', PermintaanLaporan::STATUS_DIBATALKAN)->count();
        $satuanStatusDist = [
            ['label' => 'Disetujui', 'color' => '#22c55e', 'labelColor' => '#22c55e', 'count' => $satuanDisetujui],
            ['label' => 'Ditolak', 'color' => '#ef4444', 'labelColor' => '#ef4444', 'count' => $satuanDitolak],
            ['label' => 'Terlambat', 'color' => '#ff6b6b', 'labelColor' => '#ff6b6b', 'count' => $satuanTerlambat],
            ['label' => 'Dibatalkan', 'color' => '#c1121f', 'labelColor' => '#e5484d', 'count' => $satuanDibatalkan],
        ];
        $satuanTotalStatus = $satuanDisetujui + $satuanDitolak + $satuanTerlambat + $satuanDibatalkan;

        // ===== "Surat Terbaru" & "Kendala Kasansi Terbaru" Beranda Satuan
        // -- MIRROR PERSIS 2 kartu yang sama di Beranda Pimpinan, reuse
        // partial yang SAMA (pimpinan-surat-terbaru-rows.blade.php &
        // pimpinan-kendala-terbaru-list.blade.php) apa adanya.
        // "Surat Terbaru" = 5 surat (masuk+terkirim+arsip) MILIK SATUAN INI
        // paling baru -- pola sama persis $pimpSuratTerbaru Pimpinan.
        $satuanSuratTerbaru = $suratMasuk->concat($suratTerkirim)->concat($suratArsip)->sortByDesc('created_at')->take(5)->values();
        // "Kendala Kasansi Terbaru" sumbernya tergantung peran (SAMA logic
        // saling-eksklusif kayak $kendalaKasansiKpiAktif/Arsip di atas),
        // TAPI partial ini butuh row LaporanKendala ASLI (->perihal/->status/
        // ->satuan), BUKAN LaporanKendalaTembusan (field-nya beda, gak ada
        // ->perihal/->status langsung) -- makanya utk penerima tembusan,
        // di-map ke relasi ->laporanKendala (sudah eager-loaded di atas via
        // 'laporanKendala.satuan') dulu, BUKAN pakai $tembusanMasukSemua
        // mentah.
        $satuanKendalaTerbaruSumber = $isKasansi
            ? $kendalaTerkirimSemua
            : $tembusanMasukSemua->pluck('laporanKendala')->filter()->values();
        $satuanKendalaTerbaru = $satuanKendalaTerbaruSumber->sortByDesc('created_at')->take(5)->values();

        return view('siberad.dashboards.laporan-role-shell', compact('user','satuan','tujuan','defaultDanpus','laporanTerkirim','laporanSatlak','monitoringSatlak','monitoringPimpinanSatlak','laporanPimpinanSatlak','mode','modePimpinan','canReview','canSend','description','permintaanLaporan','riwayatLaporan','satuanPermintaanLaporan','permintaanGantiPasswordPending','isKasansi','bisaKirimSurat','kendalaTerkirim','kendalaArsip','satuanTembusanPilihan','isPenerimaTembusan','tembusanMasuk','tembusanArsip','suratTerkirim','suratArsip','satuanSuratTujuanPilihan','suratMasuk') + ['defaultTujuanId' => $defaultDanpus?->id, 'modulAktif' => $modulAktif, 'pengaturan' => Pengaturan::current(), 'satuanTotalPelaporan' => $satuanTotalPelaporan, 'kendalaKasansiKpiAktif' => $kendalaKasansiKpiAktif, 'kendalaKasansiKpiArsip' => $kendalaKasansiKpiArsip, 'satuanStatusDist' => $satuanStatusDist, 'satuanTotalStatus' => $satuanTotalStatus, 'satuanSuratTerbaru' => $satuanSuratTerbaru, 'satuanKendalaTerbaru' => $satuanKendalaTerbaru, 'stats' => ['dikirim' => $laporanTerkirim->count(), 'disetujui' => $satuanDisetujui, 'ditolak' => $satuanDitolak, 'terlambat' => $satuanTerlambat, 'dibatalkan' => $satuanDibatalkan]]);
    }

    /**
     * Poll realtime buat SELURUH Beranda Pimpinan -- 3 kartu KPI, donut
     * Distribusi Status, chart Tren Aktivitas, Surat Terbaru, Kendala
     * Kasansi Terbaru. Digabung jadi SATU response/SATU siklus poll (bukan
     * bikin poller terpisah per section) -- lihat catatan long-poll di
     * bawah soal kenapa nambah poller baru di halaman ini berisiko.
     *
     * SEMPAT dicoba long-poll (request ditahan sampai ada perubahan, pola
     * sama kayak LaporanController::tungguPerubahanPermintaan()) supaya
     * update kerasa instan -- TAPI diukur langsung, hasilnya JAUH lebih
     * lambat dari dugaan (9-13 detik buat request yang seharusnya maks
     * ~4 detik), bukan karena query version-nya lambat (diukur terpisah,
     * cuma ~3ms per iterasi), tapi karena tab dashboard ini SUDAH punya
     * beberapa poller lain yang jalan otomatis tiap 4-5 detik (kendala,
     * surat, permintaan-laporan, log-aktivitas) -- di server dev lokal
     * Windows yang cuma 1 worker (lihat [[project_railway_worker_config]]),
     * SATU request yang ditahan beberapa detik bikin semua poller lain itu
     * ngantre di belakangnya, dan hasilnya malah lebih lambat daripada
     * polling interval biasa. DIBATALKAN, balik ke polling interval pendek
     * (lihat `syncPimpinanKpis` di laporan-danpus.blade.php & laporan-wadan.blade.php, tiap 1 detik)
     * -- request-nya sendiri cepat & jarang nge-hold worker, jadi gak
     * nyumbat poller lain. Kalau nanti mau coba long-poll lagi, JANGAN di
     * halaman yang udah banyak poller lain kayak dashboard Pimpinan ini
     * tanpa multi-worker (production Railway aman, 8 worker).
     *
     * Query di sini SENGAJA query baru yang lebih ringkas (bukan reuse query
     * $laporanPimpinanSatlak/$suratMasuk dkk di pelaporan() di atas) karena
     * versi di pelaporan() ikut eager-load relasi buat fitur LAIN di halaman
     * yang sama (mis. Riwayat Aktivitas) -- di sini cuma butuh yang relevan
     * buat hitungan KPI, biar query poll-nya ringan.
     */
    public function pimpinanKpiRealtime(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        $kode = $satuan?->kode ? strtoupper(trim($satuan->kode)) : null;
        abort_unless(in_array($kode, ['DANPUS', 'WADAN'], true), 403);

        $kodeSatuanPelaksanaUrut = array_merge([
            'URDAL', 'POKANALIS',
            'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT',
            'SATLAKKAL', 'SATLAKDAK', 'SATLAKSISOS', 'SATLAKDUKTEK',
        ], Satuan::KODE_KOTAMA);
        $satuanPimpinanIds = Satuan::whereIn('kode', $kodeSatuanPelaksanaUrut)->pluck('id');

        $laporanPimpinanSatlak = Laporan::with('lampirans')
            ->whereIn('satuan_id', $satuanPimpinanIds)
            ->latest()
            ->get()
            ->groupBy(fn ($l) => $l->permintaan_laporan_id ?? 'single-'.$l->id)
            ->flatMap(function ($group) {
                $progres = $group->where('status', Laporan::STATUS_PROGRES);
                $final = $group->reject(fn ($l) => $l->status === Laporan::STATUS_PROGRES)->sortByDesc('id')->take(1);
                return $progres->merge($final);
            })
            ->values();

        $danpusSatuanId = Satuan::where('kode', 'DANPUS')->value('id');
        $suratMasuk = LaporanSurat::where('tujuan_satuan_id', $satuan->id)->where('status', LaporanSurat::STATUS_MENUNGGU)->get();
        $suratTerkirim = LaporanSurat::where('satuan_id', $satuan->id)->where('status', LaporanSurat::STATUS_MENUNGGU)->get();
        $suratArsip = LaporanSurat::where(function ($q) use ($satuan) {
                $q->where('satuan_id', $satuan->id)->orWhere('tujuan_satuan_id', $satuan->id);
            })->where('status', LaporanSurat::STATUS_DIKONFIRMASI)->get();
        $kendalaMasuk = $danpusSatuanId
            ? LaporanKendala::where('tujuan_satuan_id', $danpusSatuanId)->whereNull('confirmed_at')->where('status', '!=', LaporanKendala::STATUS_MENUNGGU_TEMBUSAN)->get()
            : collect();
        $kendalaArsip = $danpusSatuanId
            ? LaporanKendala::where('tujuan_satuan_id', $danpusSatuanId)->whereNotNull('confirmed_at')->get()
            : collect();

        $pimpTotalPelaporan = $this->hitungLaporanPerPerihal($laporanPimpinanSatlak);

        // Distribusi Status (donut) -- versi ringkas dari perhitungan yang
        // sama di laporan-danpus.blade.php & laporan-wadan.blade.php (Disetujui/Ditolak dari
        // $laporanPimpinanSatlak, Terlambat/Dibatalkan dari PermintaanLaporan
        // milik Danpus/Wadan). isTerlambat() cuma butuh kolom polos
        // (laporan_id/status/deadline_at), jadi query di sini gak perlu
        // eager-load apa-apa.
        $permintaanLaporanPimpinan = PermintaanLaporan::whereHas('pembuat.satuan', fn ($q) => $q->whereIn('kode', ['DANPUS', 'WADAN']))->get();
        $pimpTotalDisetujui = $laporanPimpinanSatlak->filter(fn ($l) => str_contains(strtolower((string) $l->status), 'setuj') || str_contains(strtolower((string) $l->status), 'diterima'))->count();
        $pimpTotalDitolak = $laporanPimpinanSatlak->filter(fn ($l) => str_contains(strtolower((string) $l->status), 'tolak'))->count();
        $pimpTotalTerlambat = $permintaanLaporanPimpinan->filter(fn ($p) => $p->isTerlambat())->count();
        $pimpTotalDibatalkan = $permintaanLaporanPimpinan->where('status', PermintaanLaporan::STATUS_DIBATALKAN)->count();
        $pimpStatusDist = [
            ['label' => 'Disetujui', 'color' => '#22c55e', 'labelColor' => '#22c55e', 'count' => $pimpTotalDisetujui],
            ['label' => 'Ditolak', 'color' => '#ef4444', 'labelColor' => '#ef4444', 'count' => $pimpTotalDitolak],
            ['label' => 'Terlambat', 'color' => '#ff6b6b', 'labelColor' => '#ff6b6b', 'count' => $pimpTotalTerlambat],
            ['label' => 'Dibatalkan', 'color' => '#c1121f', 'labelColor' => '#e5484d', 'count' => $pimpTotalDibatalkan],
        ];
        $pimpTotalStatus = $pimpTotalDisetujui + $pimpTotalDitolak + $pimpTotalTerlambat + $pimpTotalDibatalkan;

        // Tren Aktivitas 7/30 hari -- sama persis logikanya kayak
        // $pimpTrenBuat di laporan-danpus.blade.php & laporan-wadan.blade.php.
        $pimpSuratSemuaTren = $suratMasuk->concat($suratTerkirim)->concat($suratArsip);
        $pimpTrenBuat = function (int $n) use ($laporanPimpinanSatlak, $pimpSuratSemuaTren) {
            return collect(range($n - 1, 0))->map(function ($k) use ($laporanPimpinanSatlak, $pimpSuratSemuaTren) {
                $day = now()->startOfDay()->subDays($k);
                $lap = $laporanPimpinanSatlak->filter(fn ($l) => $l->created_at?->isSameDay($day))->count();
                $sur = $pimpSuratSemuaTren->filter(fn ($s) => $s->created_at?->isSameDay($day))->count();
                return ['label' => $day->translatedFormat('d M'), 'laporan' => $lap, 'surat' => $sur, 'total' => $lap + $sur];
            })->values();
        };
        $pimpTrenRentang = ['7' => $pimpTrenBuat(7), '30' => $pimpTrenBuat(30)];

        $pimpSuratTerbaru = $pimpSuratSemuaTren->sortByDesc('created_at')->take(5)->values();
        $pimpKendalaTerbaru = $kendalaMasuk->concat($kendalaArsip)->sortByDesc('created_at')->take(5)->values();

        return response()->json([
            'kpis_html' => view('siberad.dashboards.partials.pimpinan-kpi-cards', [
                'pimpTotalPelaporan' => $pimpTotalPelaporan,
                'laporanPimpinanSatlak' => $laporanPimpinanSatlak,
                'suratMasuk' => $suratMasuk,
                'suratTerkirim' => $suratTerkirim,
                'suratArsip' => $suratArsip,
                'kendalaMasuk' => $kendalaMasuk,
                'kendalaArsip' => $kendalaArsip,
            ])->render(),
            'status_bd_html' => view('siberad.dashboards.partials.pimpinan-status-distribusi-list', [
                'pimpStatusDist' => $pimpStatusDist,
            ])->render(),
            'status_donut_total' => $pimpTotalStatus,
            'status_donut_counts' => [$pimpTotalDisetujui, $pimpTotalDitolak, $pimpTotalTerlambat, $pimpTotalDibatalkan],
            'tren_data' => $pimpTrenRentang,
            'surat_terbaru_html' => view('siberad.dashboards.partials.pimpinan-surat-terbaru-rows', [
                'pimpSuratTerbaru' => $pimpSuratTerbaru,
                'satuan' => $satuan,
            ])->render(),
            'kendala_terbaru_html' => view('siberad.dashboards.partials.pimpinan-kendala-terbaru-list', [
                'pimpKendalaTerbaru' => $pimpKendalaTerbaru,
            ])->render(),
            'server_time' => now()->toIso8601String(),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Poll realtime buat 3 kartu KPI Beranda Satuan (Total Pelaporan/Surat/
     * Kendala Kasansi) -- MIRROR pimpinanKpiRealtime() di atas, cuma
     * discoped ke satuan yang login sendiri (bukan cakupan seluruh satuan
     * pelaksana kayak Pimpinan). Query di sini SENGAJA ringkas/tanpa
     * eager-load relasi yang gak relevan buat KPI, pola sama seperti alasan
     * di komentar pimpinanKpiRealtime() di atas.
     */
    public function satuanKpiRealtime(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        $kode = $satuan?->kode ? strtoupper(trim($satuan->kode)) : null;
        abort_unless($satuan, 403);
        // Danpus/Wadan pakai endpoint pimpinanKpiRealtime() sendiri (lihat
        // di atas), Admin gak pernah nyampe ke dashboard Satuan sama
        // sekali -- endpoint ini murni buat role non-Pimpinan/non-Admin.
        abort_if(in_array($kode, ['ADMIN', 'DANPUS', 'WADAN'], true), 403);

        $laporanTerkirim = Laporan::with('lampirans')->where('satuan_id', $satuan->id)->get();
        $satuanTotalPelaporan = $this->hitungLaporanPerPerihal($laporanTerkirim);

        // Alur naik (balasan): surat Danpus yang sudah di-ACC tapi belum dibalas
        // dihitung Surat Masuk (bukan Arsip) -- sama dengan pelaporan() di atas.
        $bolehKirimBalasan = LaporanSurat::satuanBolehKirimBalasanNaik($kode);
        $suratMasuk = LaporanSurat::where('tujuan_satuan_id', $satuan->id)
            ->where(function ($x) use ($satuan, $bolehKirimBalasan) {
                $x->where('status', LaporanSurat::STATUS_MENUNGGU);
                if ($bolehKirimBalasan) {
                    $x->orWhere(fn ($m) => $m->menungguBalasanSatuan($satuan->id));
                }
            })->get();
        $suratTerkirim = LaporanSurat::where('satuan_id', $satuan->id)->where('status', LaporanSurat::STATUS_MENUNGGU)->get();
        $suratArsip = LaporanSurat::where(function ($q) use ($satuan) {
                $q->where('satuan_id', $satuan->id)->orWhere('tujuan_satuan_id', $satuan->id);
            })->where('status', LaporanSurat::STATUS_DIKONFIRMASI)
            ->when($bolehKirimBalasan, fn ($q) => $q->whereDoesntHave('satuan', fn ($a) => $a->where('kode', 'DANPUS')))
            ->get();

        // "Total Kendala Kasansi" -- lihat komentar lengkap soal dua sumber
        // saling eksklusif ini di pelaporan() (KPI render awal) di atas.
        $isKasansi = in_array($kode, Satuan::KODE_KOTAMA, true);
        $kendalaTerkirimSemua = $isKasansi
            ? LaporanKendala::with('satuan')->where('satuan_id', $satuan->id)->get()
            : collect();
        $kendalaTerkirim = $kendalaTerkirimSemua->where('status', '!=', LaporanKendala::STATUS_DIKONFIRMASI)->values();
        $kendalaArsip = $kendalaTerkirimSemua->where('status', LaporanKendala::STATUS_DIKONFIRMASI)->values();

        $isPenerimaTembusan = in_array($kode, Satuan::kodeTembusanKasansi(), true);
        // 'laporanKendala.satuan' di-eager-load -- dibutuhkan "Kendala
        // Kasansi Terbaru" di bawah ($k->perihal/$k->status/$k->satuan
        // datang dari relasi ini, bukan dari LaporanKendalaTembusan
        // langsung, lihat komentar lengkap di pelaporan()).
        $tembusanMasukSemua = $isPenerimaTembusan
            ? LaporanKendalaTembusan::with('laporanKendala.satuan')->where('satuan_id', $satuan->id)->get()
            : collect();
        $tembusanMasuk = $tembusanMasukSemua->whereNull('feedback')->values();
        $tembusanArsip = $tembusanMasukSemua->whereNotNull('feedback')->values();

        // "Distribusi Status Laporan" (donut) -- lihat komentar lengkap di
        // pelaporan() (KPI render awal) di atas soal 4 kategori & sumbernya.
        $permintaanLaporanSemua = PermintaanLaporan::where('tujuan_satuan_id', $satuan->id)->get();
        $satuanDisetujui = $laporanTerkirim->filter(fn ($l) => str_contains(strtolower((string) $l->status), 'setuj') || str_contains(strtolower((string) $l->status), 'diterima'))->count();
        $satuanDitolak = $laporanTerkirim->filter(fn ($l) => str_contains(strtolower((string) $l->status), 'tolak'))->count();
        $satuanTerlambat = $permintaanLaporanSemua->filter(fn ($p) => $p->isTerlambat())->count();
        $satuanDibatalkan = $permintaanLaporanSemua->where('status', PermintaanLaporan::STATUS_DIBATALKAN)->count();
        $satuanTotalStatus = $satuanDisetujui + $satuanDitolak + $satuanTerlambat + $satuanDibatalkan;

        // "Surat Terbaru" & "Kendala Kasansi Terbaru" -- lihat komentar
        // lengkap di pelaporan() (KPI render awal) di atas.
        $satuanSuratTerbaru = $suratMasuk->concat($suratTerkirim)->concat($suratArsip)->sortByDesc('created_at')->take(5)->values();
        $satuanKendalaTerbaruSumber = $isKasansi
            ? $kendalaTerkirimSemua
            : $tembusanMasukSemua->pluck('laporanKendala')->filter()->values();
        $satuanKendalaTerbaru = $satuanKendalaTerbaruSumber->sortByDesc('created_at')->take(5)->values();

        return response()->json([
            'kpis_html' => view('siberad.dashboards.partials.pimpinan-kpi-cards', [
                'pimpTotalPelaporan' => $satuanTotalPelaporan,
                'laporanPimpinanSatlak' => $laporanTerkirim,
                'suratMasuk' => $suratMasuk,
                'suratTerkirim' => $suratTerkirim,
                'suratArsip' => $suratArsip,
                'kendalaMasuk' => $kendalaTerkirim->concat($tembusanMasuk),
                'kendalaArsip' => $kendalaArsip->concat($tembusanArsip),
            ])->render(),
            'status_bd_html' => view('siberad.dashboards.partials.pimpinan-status-distribusi-list', [
                'pimpStatusDist' => [
                    ['label' => 'Disetujui', 'color' => '#22c55e', 'labelColor' => '#22c55e', 'count' => $satuanDisetujui],
                    ['label' => 'Ditolak', 'color' => '#ef4444', 'labelColor' => '#ef4444', 'count' => $satuanDitolak],
                    ['label' => 'Terlambat', 'color' => '#ff6b6b', 'labelColor' => '#ff6b6b', 'count' => $satuanTerlambat],
                    ['label' => 'Dibatalkan', 'color' => '#c1121f', 'labelColor' => '#e5484d', 'count' => $satuanDibatalkan],
                ],
            ])->render(),
            'status_donut_total' => $satuanTotalStatus,
            'status_donut_counts' => [$satuanDisetujui, $satuanDitolak, $satuanTerlambat, $satuanDibatalkan],
            'surat_terbaru_html' => view('siberad.dashboards.partials.pimpinan-surat-terbaru-rows', [
                'pimpSuratTerbaru' => $satuanSuratTerbaru,
                'satuan' => $satuan,
            ])->render(),
            'kendala_terbaru_html' => view('siberad.dashboards.partials.pimpinan-kendala-terbaru-list', [
                'pimpKendalaTerbaru' => $satuanKendalaTerbaru,
            ])->render(),
            'server_time' => now()->toIso8601String(),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * "Total Pelaporan" (KPI Admin & Pimpinan, kolom Rekap Laporan, grafik
     * "Laporan per Satuan") dihitung PER PERIHAL -- 1 permintaan_laporan_id
     * (atau 1 baris tunggal tanpa Permintaan, key 'single-<id>') = 1 Perihal
     * = 1 hitungan, BUKAN per baris Laporan. Satu Perihal yang progresnya
     * di-update berkali-kali (beberapa baris checkpoint status "Progres")
     * sebelum laporan finalnya tetap dihitung SATU, bukan sebanyak baris
     * checkpoint-nya -- beda dari $laporanPimpinanSatlak sendiri (dipakai
     * buat menunggu/diterima/ditolak & daftar Riwayat Aktivitas) yang
     * SENGAJA menghitung tiap checkpoint progres sebagai baris tersendiri
     * (lihat komentar di atas definisinya) -- cuma "Total Pelaporan" yang
     * dikelompokkan per Perihal, bukan seluruh cara hitung lainnya.
     *
     * Sebuah Perihal ikut terhitung kalau SALAH SATU barisnya (checkpoint
     * progres ataupun laporan final) ADA lampiran filenya -- baris progres
     * tanpa lampiran dianggap sekadar update angka, bukan laporan yang
     * beneran punya berkas.
     */
    private function hitungLaporanPerPerihal(Collection $laporan): int
    {
        return $laporan
            ->groupBy(fn (Laporan $l) => $l->permintaan_laporan_id ?? 'single-'.$l->id)
            ->filter(fn ($group) => $group->contains(fn (Laporan $l) => $l->semuaLampiran->isNotEmpty()))
            ->count();
    }

    /**
     * Data radar "Pengguna per Kategori Satuan" Beranda Admin -- 5 sumbu,
     * BUKAN 7 kategori asli Satuan::KATEGORI_* apa adanya. Direktorat (4
     * Sdir) + Unsur Pembantu Pimpinan (Pok Analis) SENGAJA digabung jadi 1
     * sumbu "Unsur Pembantu Pimpinan" (sama-sama "membantu Danpus", lihat
     * komentar Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN), Pimpinan dilabeli
     * "Unsur Pimpinan", Satlak dilabeli "Unsur Pelaksana" -- sesuai
     * referensi gambar yang diminta user. Kotama (21 Sansidam) SENGAJA
     * tidak ikut, referensinya cuma minta 5 sumbu ini. Dipakai admin() (render
     * awal) & adminKpiRealtime() (poll) -- SATU sumber biar gak drift.
     */
    private function distribusiPenggunaKategoriRadar(Collection $semuaSatuan): Collection
    {
        $radarKategoriMap = [
            Satuan::KATEGORI_ADMIN => 'Admin',
            Satuan::KATEGORI_PIMPINAN => 'Unsur Pimpinan',
            Satuan::KATEGORI_DIREKTORAT => 'Unsur Pembantu Pimpinan',
            Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
            Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
            Satuan::KATEGORI_SATLAK => 'Unsur Pelaksana',
        ];
        $radarUrutan = ['Admin', 'Unsur Pimpinan', 'Unsur Pembantu Pimpinan', 'Unsur Pelayanan', 'Unsur Pelaksana'];

        return $semuaSatuan->filter(fn ($s) => isset($radarKategoriMap[$s->kategori]))
            ->groupBy(fn ($s) => $radarKategoriMap[$s->kategori])
            ->map(fn ($group, $label) => ['kategori' => $label, 'jumlah' => $group->sum('users_count')])
            ->sortBy(fn ($row) => array_search($row['kategori'], $radarUrutan))
            ->values();
    }

    /**
     * Data donut "Distribusi Status Laporan" Beranda Admin (disetujui/
     * ditolak/terlambat/dibatalkan, seluruh sistem). Dipakai admin() (render
     * awal) & adminKpiRealtime() (poll) -- SATU sumber biar gak drift.
     */
    private function statusLaporanSistem(): array
    {
        return [
            // Laporan bisa diputuskan lewat 2 jalur (Danpus ATAU Wadan, lihat
            // LaporanController::updateStatus) -- status akhirnya "Disetujui
            // DANPUS"/"Disetujui WADAN" (begitu juga Ditolak). Dulu cuma cek
            // varian DANPUS doang, jadi laporan yang diputuskan Wadan gak
            // ikut kehitung di sini walau udah kehitung di Distribusi Status
            // Laporan versi Pimpinan (yang pakai str_contains 'setuj'/'tolak',
            // otomatis nangkep kedua varian).
            'disetujui' => Laporan::whereIn('status', ['Disetujui DANPUS', 'Disetujui WADAN'])->count(),
            'ditolak' => Laporan::whereIn('status', ['Ditolak DANPUS', 'Ditolak WADAN'])->count(),
            // Samain persis sama kondisi PermintaanLaporan::isTerlambat(),
            // ditulis sebagai query (bukan ->get()->filter()) karena ini
            // hitungan seluruh sistem, bisa banyak baris.
            'terlambat' => PermintaanLaporan::whereNull('laporan_id')
                ->whereNotIn('status', [PermintaanLaporan::STATUS_SELESAI, PermintaanLaporan::STATUS_PEMERIKSAAN, PermintaanLaporan::STATUS_DIBATALKAN])
                ->where('deadline_at', '<', now())
                ->count(),
            'dibatalkan' => PermintaanLaporan::where('status', PermintaanLaporan::STATUS_DIBATALKAN)->count(),
        ];
    }

    /**
     * Data chart "Tren Aktivitas" Beranda Admin (dulu "Aktivitas 7 Hari
     * Terakhir", sekarang ada toggle 7/30 hari niru "Tren Aktivitas"
     * Pimpinan -- array 2 rentang dikirim sekaligus, toggle tombol di
     * frontend cuma ganti chart.data lalu chart.update(), TANPA request
     * ulang ke server). Dipakai admin() (render awal) & adminKpiRealtime()
     * (poll) -- SATU sumber biar gak drift.
     */
    private function trenAktivitas(): array
    {
        $build = function (int $n) {
            return collect(range($n - 1, 0))->map(function ($i) {
                $tanggal = now()->subDays($i);

                return ['label' => $tanggal->translatedFormat('d M'), 'jumlah' => ActivityLog::whereDate('created_at', $tanggal->toDateString())->count()];
            })->values();
        };

        return ['7' => $build(7), '30' => $build(30)];
    }
}
