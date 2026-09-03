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
        $prioritasKategori = Satuan::prioritasKategori();
        $semuaPengguna = User::with('satuan')->get()
            ->sortBy(fn ($p) => Satuan::kunciUrutSatuan($p->satuan->kategori ?? null, $p->satuan->kode ?? null))
            ->values();
        // Urutan satuan (dipakai tab "Data Satuan" & "Hak Akses Pengguna")
        // SELALU ikut jenjang organisasi resmi lewat Satuan::kunciUrutSatuan()
        // -- Danpus -> Wadan -> Urdal -> Pok Analis -> 4 Sdir -> 4 Satlak --
        // bukan urutan alfabet ataupun kapan satuan dibuat. Satuan baru yang
        // kodenya belum ada di Satuan::urutanDalamKategori() otomatis jatuh
        // ke urutan terakhir dalam kategorinya (created_at/id sebagai
        // penentu akhir kalau ada beberapa satuan baru sekaligus).
        $semuaSatuan = Satuan::withCount('users')->get()
            ->sort(function ($a, $b) {
                $kunciA = Satuan::kunciUrutSatuan($a->kategori, $a->kode);
                $kunciB = Satuan::kunciUrutSatuan($b->kategori, $b->kode);
                if ($kunciA !== $kunciB) return $kunciA <=> $kunciB;
                if ($a->created_at != $b->created_at) return $a->created_at <=> $b->created_at;
                return $a->id <=> $b->id;
            })
            ->values();
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
            ->get(['sessions.id','sessions.ip_address','sessions.user_agent','sessions.last_activity','users.name as user_name']);
        // Satuan pengirim laporan = semua satuan SELAIN Admin & Pimpinan
        // (Admin cuma pengelola sistem, Pimpinan/Danpus-Wadan cuma
        // menerima & meninjau, bukan pengirim). Dihitung otomatis dari
        // kategori, bukan daftar kode manual, supaya kategori satuan baru
        // (mis. Kotama) otomatis ikut ke "Ringkasan Data"/"Detail per
        // Satuan" tanpa perlu diedit lagi di sini tiap kali ada satuan baru.
        $kodeSatuanPengirim = Satuan::whereNotIn('kategori', [Satuan::KATEGORI_ADMIN, Satuan::KATEGORI_PIMPINAN])
            ->pluck('kode')
            ->all();
        // "Total Laporan" di sini (KPI atas & kolom Rekap Laporan) cuma
        // ngitung baris (checkpoint progres maupun laporan final, dedup
        // dulu yang sempat ditolak/direvisi sebelum disetujui/ditolak
        // final) yang ADA lampiran filenya -- samain sama aturan yang
        // sudah dipakai di dashboard Pimpinan (DashboardController::index).
        $laporanRekapDeduped = Laporan::whereIn('satuan_id', Satuan::whereIn('kode', $kodeSatuanPengirim)->pluck('id'))
            ->with('lampirans')
            ->get()
            ->groupBy(fn ($l) => $l->permintaan_laporan_id ?? 'single-'.$l->id)
            ->flatMap(function ($group) {
                $progres = $group->where('status', Laporan::STATUS_PROGRES);
                $final = $group->reject(fn ($l) => $l->status === Laporan::STATUS_PROGRES)->sortByDesc('id')->take(1);
                return $progres->merge($final);
            })
            ->filter(fn ($l) => $l->semuaLampiran->isNotEmpty());
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
            ->map(function ($s) use ($laporanRekapDeduped) {
                $s->total_laporan = $laporanRekapDeduped->where('satuan_id', $s->id)->count();

                return $s;
            });
        return view('siberad.dashboards.admin', compact('user','satuan','semuaPengguna','semuaSatuan','permintaanResetPassword','distribusiPenggunaKategori','statusLaporanSistem','aktivitasTujuhHari','logAktivitas','daftarBackup','sesiAktif','rekapLaporanSatuan','logDari','logSampai','daftarPushSubscription') + ['pengaturan' => Pengaturan::current(), 'sesiSayaId' => session()->getId(), 'modulHakAkses' => Satuan::MODUL_HAK_AKSES, 'modulAktif' => $modulAktif, 'resetDataKategori' => ResetDataLaporanController::KATEGORI, 'resetDataCounts' => ResetDataLaporanController::hitungPerKategori(), 'stats' => ['total_pengguna' => $semuaPengguna->count(), 'total_satuan' => $semuaSatuan->count(), 'total_laporan' => $laporanRekapDeduped->count(), 'reset_password_pending' => $permintaanResetPassword->where('status', PermintaanResetPassword::STATUS_MENUNGGU)->count()]]);
    }

    private function pelaporan($user, $satuan, ?string $kode, array $modulAktif): View
    {
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        $permintaanGantiPasswordPending = PermintaanResetPassword::where('user_id', $user->id)
            ->where('status', PermintaanResetPassword::STATUS_MENUNGGU)
            ->latest()
            ->first();
        $laporanTerkirim = Laporan::with('tujuanSatuan')->where('satuan_id', $satuan->id)->latest()->get();
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
                // "Total Laporan" cuma ngitung baris (checkpoint progres
                // maupun laporan final) yang ADA lampiran filenya -- baris
                // riwayat progres tanpa lampiran dianggap sekadar update
                // angka, bukan "laporan" yang beneran punya berkas.
                'total' => $laporanPimpinanSatlak->where('satuan_id', $satuanPimpinan->id)
                    ->filter(fn ($l) => $l->semuaLampiran->isNotEmpty())
                    ->count(),
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
            // satu tujuan surat, sama seperti satuan lain manapun.
            $suratMasuk = LaporanSurat::with('satuan')->where('tujuan_satuan_id', $satuan->id)->latest()->get();

            // ===== Menu Surat Danpus/Wadan: FULL sama seperti Kasansi --
            // Danpus/Wadan juga bisa Kirim Surat (bukan cuma terima),
            // lihat LaporanSuratController::store() yang sudah
            // mengizinkan $kodeAsal DANPUS/WADAN selain KODE_KOTAMA.
            $suratTerkirim = LaporanSurat::with('tujuanSatuan')
                ->where('satuan_id', $satuan->id)
                ->where('status', LaporanSurat::STATUS_MENUNGGU)
                ->latest()
                ->get();
            $suratArsip = LaporanSurat::with('tujuanSatuan')
                ->where('satuan_id', $satuan->id)
                ->where('status', LaporanSurat::STATUS_DIKONFIRMASI)
                ->latest()
                ->get();
            // Pilihan tujuan di form Kirim Surat: seluruh satuan lain di
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
        $isPenerimaTembusan = in_array($kode, $kodeTembusanKasansi, true);
        $tembusanMasuk = $isPenerimaTembusan
            ? LaporanKendalaTembusan::with(['laporanKendala.satuan', 'laporanKendala.lampirans', 'dibacaOleh'])
                ->where('satuan_id', $satuan->id)
                ->latest()
                ->get()
            : collect();

        // ===== Surat: Kasansi (21 Sansidam), 4 Satlak, 4 Sdir (Pembinaan),
        // Urdal, dan Pok Analis semuanya bisa Kirim Surat ke SATU tujuan bebas.
        // $bisaKirimSurat sengaja DIPISAH dari $isKasansi agar logika Kendala
        // (hanya Kasansi) tidak ikut terpengaruh.
        // Surat berstatus 'menunggu_konfirmasi' tetap di Kirim Surat.
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
        $suratArsip = $bisaKirimSurat
            ? LaporanSurat::with('tujuanSatuan')
                ->where('satuan_id', $satuan->id)
                ->where('status', \App\Models\LaporanSurat::STATUS_DIKONFIRMASI)
                ->latest()
                ->get()
            : collect();
        // Pilihan tujuan di form Kirim Surat: seluruh satuan lain di
        // sistem selain diri sendiri dan ADMIN.
        $satuanSuratTujuanPilihan = $bisaKirimSurat
            ? Satuan::where('id', '!=', $satuan->id)->where('kode', '!=', 'ADMIN')->get()->sortBy($urutkanSatuan)->values()
            : collect();
        // Surat Masuk: satuan APAPUN bisa jadi tujuan surat,
        // jadi selalu disiapkan buat semua role.
        $suratMasuk = LaporanSurat::with('satuan')->where('tujuan_satuan_id', $satuan->id)->latest()->get();

        return view('siberad.dashboards.laporan-role-shell', compact('user','satuan','tujuan','defaultDanpus','laporanTerkirim','laporanSatlak','monitoringSatlak','monitoringPimpinanSatlak','laporanPimpinanSatlak','mode','modePimpinan','canReview','canSend','description','permintaanLaporan','riwayatLaporan','satuanPermintaanLaporan','permintaanGantiPasswordPending','isKasansi','bisaKirimSurat','kendalaTerkirim','kendalaArsip','satuanTembusanPilihan','isPenerimaTembusan','tembusanMasuk','suratTerkirim','suratArsip','satuanSuratTujuanPilihan','suratMasuk') + ['defaultTujuanId' => $defaultDanpus?->id, 'modulAktif' => $modulAktif, 'pengaturan' => Pengaturan::current(), 'stats' => ['dikirim' => $laporanTerkirim->count(), 'disetujui' => $laporanTerkirim->filter(fn($l) => str_contains(strtolower((string)$l->status),'setuj') || str_contains(strtolower((string)$l->status),'diterima'))->count(), 'ditolak' => $laporanTerkirim->filter(fn($l) => str_contains(strtolower((string)$l->status),'tolak'))->count(), 'terlambat' => $permintaanLaporanSemua->filter(fn($p) => $p->isTerlambat())->count(), 'dibatalkan' => $permintaanLaporanSemua->where('status', PermintaanLaporan::STATUS_DIBATALKAN)->count()]]);
    }
}
