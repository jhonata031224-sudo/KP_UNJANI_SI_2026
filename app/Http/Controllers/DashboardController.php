<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\ResetDataLaporanController;
use App\Models\ActivityLog;
use App\Models\Laporan;
use App\Models\LaporanKendala;
use App\Models\LaporanKendalaTembusan;
use App\Models\LaporanSurat;
use App\Models\Pengaturan;
use App\Models\PermintaanLaporan;
use App\Models\PermintaanResetPassword;
use App\Models\PushSubscription;
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
        $prioritasKategori = Satuan::prioritasKategori();
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
        $labelKategori = [Satuan::KATEGORI_SATLAK => 'Satlak', Satuan::KATEGORI_DIREKTORAT => 'Direktorat', Satuan::KATEGORI_PIMPINAN => 'Pimpinan', Satuan::KATEGORI_ADMIN => 'Admin', Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan', Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan', Satuan::KATEGORI_KOTAMA => 'Kasansi'];
        // Urutan grup di sini SENGAJA dipastikan lewat prioritasKategori
        // (bukan ikut urutan asli $semuaSatuan begitu saja), soalnya grafik
        // "Pengguna per Kategori Satuan" pasangin warna berdasarkan posisi --
        // kalau urutannya berubah-ubah, warnanya ikut kacau kepasang ke
        // kategori yang salah.
        $distribusiPenggunaKategori = $semuaSatuan->groupBy('kategori')
            ->sortBy(fn ($group, $kategori) => $prioritasKategori[$kategori] ?? 9)
            ->map(fn ($group, $kategori) => ['kategori' => $labelKategori[$kategori] ?? ucfirst($kategori), 'jumlah' => $group->sum('users_count')])
            ->values();
        $statusLaporanSistem = [
            'disetujui' => Laporan::where('status', 'Disetujui DANPUS')->count(),
            'ditolak' => Laporan::where('status', 'Ditolak DANPUS')->count(),
            // Samain persis sama kondisi PermintaanLaporan::isTerlambat(),
            // ditulis sebagai query (bukan ->get()->filter()) karena ini
            // hitungan seluruh sistem, bisa banyak baris.
            'terlambat' => PermintaanLaporan::whereNull('laporan_id')
                ->whereNotIn('status', [PermintaanLaporan::STATUS_SELESAI, PermintaanLaporan::STATUS_PEMERIKSAAN, PermintaanLaporan::STATUS_DIBATALKAN])
                ->where('deadline_at', '<', now())
                ->count(),
            'dibatalkan' => PermintaanLaporan::where('status', PermintaanLaporan::STATUS_DIBATALKAN)->count(),
        ];
        $aktivitasTujuhHari = collect(range(6, 0))->map(function ($i) {
            $tanggal = now()->subDays($i);
            return ['label' => $tanggal->translatedFormat('d M'), 'jumlah' => ActivityLog::whereDate('created_at', $tanggal->toDateString())->count()];
        })->values();
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
        $daftarBackup = app(BackupController::class)->index();
        // Daftar pengguna yang sudah mengizinkan push notification --
        // ditampilkan di menu Setelan -> Notifikasi supaya Admin bisa
        // lihat cakupannya sebelum kirim pengumuman broadcast.
        $daftarPushSubscription = PushSubscription::with('user.satuan')
            ->latest('created_at')
            ->get();
        // Hanya sesi yang benar-benar terautentikasi yang ditampilkan.
        // Baris guest dengan user_id NULL tidak termasuk sesi login aktif.
        $sesiAktif = DB::table('sessions')
            ->whereNotNull('sessions.user_id')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->orderByDesc('sessions.last_activity')
            ->get(['sessions.id','sessions.ip_address','sessions.user_agent','sessions.last_activity','sessions.login_at','users.name as user_name']);
        // Satuan pengirim laporan = semua satuan SELAIN Admin & Pimpinan
        // (Admin cuma pengelola sistem, Pimpinan/Danpus-Wadan cuma
        // menerima & meninjau, bukan pengirim). Dihitung otomatis dari
        // kategori, bukan daftar kode manual, supaya kategori satuan baru
        // (mis. Kotama) otomatis ikut ke "Ringkasan Data"/"Detail per
        // Satuan" tanpa perlu diedit lagi di sini tiap kali ada satuan baru.
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
        $rekapLaporanSatuan = Satuan::whereIn('kode', $kodeSatuanPengirim)->withCount([
            'laporanTerkirim as laporan_disetujui' => fn ($q) => $q->where('status', 'Disetujui DANPUS'),
            'laporanTerkirim as laporan_ditolak' => fn ($q) => $q->where('status', 'Ditolak DANPUS'),
            // Samain persis sama kondisi PermintaanLaporan::isTerlambat().
            'permintaanLaporanMasuk as laporan_terlambat' => fn ($q) => $q->whereNull('laporan_id')
                ->whereNotIn('status', [PermintaanLaporan::STATUS_SELESAI, PermintaanLaporan::STATUS_PEMERIKSAAN, PermintaanLaporan::STATUS_DIBATALKAN])
                ->where('deadline_at', '<', now()),
            'permintaanLaporanMasuk as laporan_dibatalkan' => fn ($q) => $q->where('status', PermintaanLaporan::STATUS_DIBATALKAN),
        ])->get()
            ->sortBy(fn ($s) => Satuan::kunciUrutSatuan($s->kategori, $s->kode))
            ->values()
            ->map(function ($s) use ($laporanRekapMentah) {
                $s->total_laporan = $this->hitungLaporanPerPerihal($laporanRekapMentah->where('satuan_id', $s->id));

                return $s;
            });
        // Dipakai partial admin-kpi-cards.blade.php buat hitung sparkline 7
        // hari terakhir kartu KPI "Total Surat" (jumlah keseluruhan sistem,
        // sama seperti $stats['total_surat'] di bawah).
        $suratSemuaAdmin = LaporanSurat::get();

        return view('siberad.dashboards.admin', compact('user','satuan','semuaPengguna','semuaSatuan','permintaanResetPassword','distribusiPenggunaKategori','statusLaporanSistem','aktivitasTujuhHari','logAktivitas','daftarBackup','sesiAktif','rekapLaporanSatuan','logDari','logSampai','daftarPushSubscription','laporanRekapMentah','suratSemuaAdmin') + ['pengaturan' => Pengaturan::current(), 'sesiSayaId' => session()->getId(), 'modulHakAkses' => Satuan::MODUL_HAK_AKSES, 'modulAktif' => $modulAktif, 'resetDataKategori' => ResetDataLaporanController::KATEGORI, 'resetDataCounts' => ResetDataLaporanController::hitungPerKategori(), 'resetDataDetails' => ResetDataLaporanController::ambilDetailPerKategori(), 'stats' => ['total_pengguna' => $semuaPengguna->count(), 'total_satuan' => $semuaSatuan->count(), 'total_laporan' => $this->hitungLaporanPerPerihal($laporanRekapMentah), 'total_surat' => LaporanSurat::count(), 'reset_password_pending' => $permintaanResetPassword->where('status', PermintaanResetPassword::STATUS_MENUNGGU)->count()]]);
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
        // monitoring Pimpinan dan rekap laporan ikut menampilkan data mereka,
        // konsisten dengan $rekapLaporanSatuan yang sudah include Kotama
        // lewat whereNotIn(['admin','pimpinan']) di bagian atas.
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
            $suratMasuk = LaporanSurat::with('satuan')
                ->where('tujuan_satuan_id', $satuan->id)
                ->where('status', LaporanSurat::STATUS_MENUNGGU)
                ->latest()
                ->get();

            // ===== Menu Surat Danpus/Wadan: FULL sama seperti Kasansi --
            // Danpus/Wadan juga bisa Surat Keluar (bukan cuma terima),
            // lihat LaporanSuratController::store() yang sudah
            // mengizinkan $kodeAsal DANPUS/WADAN selain KODE_KOTAMA.
            $suratTerkirim = LaporanSurat::with('tujuanSatuan')
                ->where('satuan_id', $satuan->id)
                ->where('status', LaporanSurat::STATUS_MENUNGGU)
                ->latest()
                ->get();
            // Arsip Surat gabungan dua arah -- lihat komentar panjang di
            // role() untuk $suratArsip, pola & alasannya identik persis.
            $suratArsip = LaporanSurat::with(['satuan', 'tujuanSatuan'])
                ->where(function ($q) use ($satuan) {
                    $q->where('satuan_id', $satuan->id)
                        ->orWhere('tujuan_satuan_id', $satuan->id);
                })
                ->where('status', LaporanSurat::STATUS_DIKONFIRMASI)
                ->latest()
                ->get();
            // Pilihan tujuan di form Surat Keluar: seluruh satuan lain di
            // sistem selain diri sendiri dan ADMIN (sama seperti Kasansi).
            $satuanSuratTujuanPilihan = Satuan::where('id', '!=', $satuan->id)->where('kode', '!=', 'ADMIN')->get()->sortBy($urutkanSatuan)->values();

            return view('siberad.dashboards.laporan-pimpinan-shell', compact('user','satuan','monitoringPimpinanSatlak','laporanPimpinanSatlak','mode','modePimpinan','canReview','canSend','description','permintaanLaporan','riwayatLaporanPimpinan','satuanPermintaanLaporan','permintaanGantiPasswordPending','modulAktif','kendalaMasuk','kendalaArsip','suratMasuk','suratTerkirim','suratArsip','satuanSuratTujuanPilihan') + ['pengaturan' => Pengaturan::current()]);
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
            ? LaporanSurat::with('tujuanSatuan')
                ->where('satuan_id', $satuan->id)
                ->where('status', \App\Models\LaporanSurat::STATUS_MENUNGGU)
                ->latest()
                ->get()
            : collect();
        // Arsip Surat SEKARANG gabungan dua arah -- surat yang DIKIRIM satuan
        // ini dan sudah dikonfirmasi penerima, DITAMBAH surat yang MASUK ke
        // satuan ini dan sudah DIA SENDIRI konfirmasi (dulu surat masuk yang
        // dikonfirmasi cuma diam di Surat Masuk selamanya, gak pernah pindah
        // kemana-mana -- sekarang niru pola Surat Keluar -> Arsip Surat).
        // SENGAJA gak digating $bisaKirimSurat lagi (beda dari suratTerkirim
        // di atas) -- satuan APAPUN bisa nerima & konfirmasi surat masuk,
        // jadi arsipnya juga harus kebentuk buat semua role, bukan cuma yang
        // bisa Surat Keluar.
        $suratArsip = LaporanSurat::with(['satuan', 'tujuanSatuan'])
            ->where(function ($q) use ($satuan) {
                $q->where('satuan_id', $satuan->id)
                    ->orWhere('tujuan_satuan_id', $satuan->id);
            })
            ->where('status', \App\Models\LaporanSurat::STATUS_DIKONFIRMASI)
            ->latest()
            ->get();
        // Pilihan tujuan di form Surat Keluar: seluruh satuan lain di
        // sistem selain diri sendiri dan ADMIN.
        $satuanSuratTujuanPilihan = $bisaKirimSurat
            ? Satuan::where('id', '!=', $satuan->id)->where('kode', '!=', 'ADMIN')->get()->sortBy($urutkanSatuan)->values()
            : collect();
        // Surat Masuk: satuan APAPUN bisa jadi tujuan surat, jadi selalu
        // disiapkan buat semua role. Cuma yang masih MENUNGGU -- yang sudah
        // dikonfirmasi pindah ke $suratArsip di atas (niru persis pola
        // Surat Keluar: begitu dikonfirmasi, otomatis pindah ke Arsip Surat,
        // bukan nyangkut selamanya di Surat Masuk).
        $suratMasuk = LaporanSurat::with('satuan')
            ->where('tujuan_satuan_id', $satuan->id)
            ->where('status', \App\Models\LaporanSurat::STATUS_MENUNGGU)
            ->latest()
            ->get();

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
     * (lihat `syncPimpinanKpis` di laporan-pimpinan.blade.php, tiap 1 detik)
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
        // sama di laporan-pimpinan.blade.php (Disetujui/Ditolak dari
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
        // $pimpTrenBuat di laporan-pimpinan.blade.php.
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

        $suratMasuk = LaporanSurat::where('tujuan_satuan_id', $satuan->id)->where('status', LaporanSurat::STATUS_MENUNGGU)->get();
        $suratTerkirim = LaporanSurat::where('satuan_id', $satuan->id)->where('status', LaporanSurat::STATUS_MENUNGGU)->get();
        $suratArsip = LaporanSurat::where(function ($q) use ($satuan) {
                $q->where('satuan_id', $satuan->id)->orWhere('tujuan_satuan_id', $satuan->id);
            })->where('status', LaporanSurat::STATUS_DIKONFIRMASI)->get();

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
}
