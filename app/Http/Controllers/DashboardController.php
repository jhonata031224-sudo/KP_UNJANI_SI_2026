<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\BackupController;
use App\Models\ActivityLog;
use App\Models\Laporan;
use App\Models\Pengaturan;
use App\Models\PermintaanLaporan;
use App\Models\PermintaanResetPassword;
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
        if ($kode === 'ADMIN') return $this->admin($request, $user, $satuan);
        return $this->pelaporan($user, $satuan, $kode);
    }

    private function admin(Request $request, $user, $satuan): View
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
        $labelKategori = [Satuan::KATEGORI_SATLAK => 'Satlak', Satuan::KATEGORI_DIREKTORAT => 'Direktorat', Satuan::KATEGORI_PIMPINAN => 'Pimpinan', Satuan::KATEGORI_ADMIN => 'Admin', Satuan::KATEGORI_POKPEL => 'Pok Pel', Satuan::KATEGORI_KASANSI => 'Kasansi'];
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
        $logAktivitas = ActivityLog::with('user')
            ->whereBetween('created_at', [$logDari, $logSampai])
            ->latest('created_at')
            ->get();
        $daftarBackup = app(BackupController::class)->index();
        // Hanya sesi yang benar-benar terautentikasi yang ditampilkan.
        // Baris guest dengan user_id NULL tidak termasuk sesi login aktif.
        $sesiAktif = DB::table('sessions')
            ->whereNotNull('sessions.user_id')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->orderByDesc('sessions.last_activity')
            ->get(['sessions.id','sessions.ip_address','sessions.user_agent','sessions.last_activity','users.name as user_name']);
        $kodeSatuanPengirim = [
            'SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK', 'SATLAKDUKTEK',
            'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT',
            'POKANALIS', 'URDAL',
        ];
        // "Total Laporan" di sini (KPI atas & kolom Rekap Laporan) cuma
        // ngitung baris (checkpoint progres maupun laporan final, dedup
        // dulu yang sempat ditolak/direvisi sebelum disetujui/ditolak
        // final) yang ADA lampiran filenya -- samain sama aturan yang
        // sudah dipakai di dashboard Pimpinan (DashboardController::index).
        $laporanRekapDeduped = Laporan::whereIn('satuan_id', Satuan::whereIn('kode', $kodeSatuanPengirim)->pluck('id'))
            ->get()
            ->groupBy(fn ($l) => $l->permintaan_laporan_id ?? 'single-'.$l->id)
            ->flatMap(function ($group) {
                $progres = $group->where('status', Laporan::STATUS_PROGRES);
                $final = $group->reject(fn ($l) => $l->status === Laporan::STATUS_PROGRES)->sortByDesc('id')->take(1);
                return $progres->merge($final);
            })
            ->filter(fn ($l) => filled($l->lampiran_path));
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
        return view('siberad.dashboards.admin', compact('user','satuan','semuaPengguna','semuaSatuan','permintaanResetPassword','distribusiPenggunaKategori','statusLaporanSistem','aktivitasTujuhHari','logAktivitas','daftarBackup','sesiAktif','rekapLaporanSatuan','logDari','logSampai') + ['pengaturan' => Pengaturan::current(), 'sesiSayaId' => session()->getId(), 'modulHakAkses' => Satuan::MODUL_HAK_AKSES, 'stats' => ['total_pengguna' => $semuaPengguna->count(), 'total_satuan' => $semuaSatuan->count(), 'total_laporan' => $laporanRekapDeduped->count(), 'reset_password_pending' => $permintaanResetPassword->where('status', PermintaanResetPassword::STATUS_MENUNGGU)->count()]]);
    }

    private function pelaporan($user, $satuan, ?string $kode): View
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
        // Binmat) -> 4 Satlak (Kal, Dak, Siber Sos, Dukteksi), sesuai urutan
        // organisasi yang diminta -- samain sama Satuan::kunciUrutSatuan().
        $kodeSatuanPelaksanaUrut = [
            'URDAL', 'POKANALIS',
            'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT',
            'SATLAKKAL', 'SATLAKDAK', 'SATLAKSISOS', 'SATLAKDUKTEK',
        ];
        $permintaanLaporan = $modePimpinan
            ? PermintaanLaporan::with(['pembuat.satuan','tujuanSatuan','laporan','laporans'])
                ->whereHas('pembuat.satuan', fn ($q) => $q->whereIn('kode', ['DANPUS','WADAN']))
                ->latest('id')
                ->get()
            : PermintaanLaporan::with(['pembuat.satuan','tujuanSatuan','laporan','laporans'])
                ->where('tujuan_satuan_id', $satuan->id)
                ->whereIn('status', [PermintaanLaporan::STATUS_BELUM, PermintaanLaporan::STATUS_DIKERJAKAN, PermintaanLaporan::STATUS_PEMERIKSAAN])
                ->latest('deadline_at')
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
            $laporanPimpinanSatlak = Laporan::with(['satuan','tujuanSatuan','permintaanLaporan'])
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
                    ->filter(fn ($l) => filled($l->lampiran_path))
                    ->count(),
                'menunggu' => $laporanPimpinanSatlak->where('satuan_id', $satuanPimpinan->id)->where('status', 'Menunggu')->count(),
                'diterima' => $laporanPimpinanSatlak->where('satuan_id', $satuanPimpinan->id)->filter(fn ($l) => str_contains(strtolower((string) $l->status), 'setuj') || str_contains(strtolower((string) $l->status), 'diterima'))->count(),
                'ditolak' => $laporanPimpinanSatlak->where('satuan_id', $satuanPimpinan->id)->filter(fn ($l) => str_contains(strtolower((string) $l->status), 'tolak'))->count(),
            ]);
            return view('siberad.dashboards.laporan-pimpinan-shell', compact('user','satuan','monitoringPimpinanSatlak','laporanPimpinanSatlak','mode','modePimpinan','canReview','canSend','description','permintaanLaporan','satuanPermintaanLaporan','permintaanGantiPasswordPending'));
        }
        // Terlambat/Dibatalkan dihitung dari SELURUH permintaan laporan yang
        // ditujukan ke satuan ini, bukan $permintaanLaporan (yang sengaja
        // sudah difilter cuma yang masih actionable, tanpa Selesai/
        // Dibatalkan, khusus buat daftar tugas di tab "Permintaan Laporan").
        $permintaanLaporanSemua = PermintaanLaporan::where('tujuan_satuan_id', $satuan->id)->get();
        return view('siberad.dashboards.laporan-role-shell', compact('user','satuan','tujuan','defaultDanpus','laporanTerkirim','laporanSatlak','monitoringSatlak','monitoringPimpinanSatlak','laporanPimpinanSatlak','mode','modePimpinan','canReview','canSend','description','permintaanLaporan','satuanPermintaanLaporan','permintaanGantiPasswordPending') + ['defaultTujuanId' => $defaultDanpus?->id, 'stats' => ['dikirim' => $laporanTerkirim->count(), 'disetujui' => $laporanTerkirim->filter(fn($l) => str_contains(strtolower((string)$l->status),'setuj') || str_contains(strtolower((string)$l->status),'diterima'))->count(), 'ditolak' => $laporanTerkirim->filter(fn($l) => str_contains(strtolower((string)$l->status),'tolak'))->count(), 'terlambat' => $permintaanLaporanSemua->filter(fn($p) => $p->isTerlambat())->count(), 'dibatalkan' => $permintaanLaporanSemua->where('status', PermintaanLaporan::STATUS_DIBATALKAN)->count()]]);
    }
}
