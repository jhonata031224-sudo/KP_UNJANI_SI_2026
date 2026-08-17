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
            ->sortBy(fn ($p) => sprintf('%d-%s', $prioritasKategori[$p->satuan->kategori ?? ''] ?? 9, $p->name))
            ->values();
        // Di dalam kategori yang sama, satuan yang paling baru ditambahkan
        // tampil paling atas (id dipakai sebagai penentu akhir kalau
        // created_at-nya kebetulan sama persis, misal data hasil seeding).
        $semuaSatuan = Satuan::withCount('users')->get()
            ->sort(function ($a, $b) use ($prioritasKategori) {
                $prioA = $prioritasKategori[$a->kategori] ?? 9;
                $prioB = $prioritasKategori[$b->kategori] ?? 9;
                if ($prioA !== $prioB) return $prioA <=> $prioB;
                if ($a->created_at != $b->created_at) return $b->created_at <=> $a->created_at;
                return $b->id <=> $a->id;
            })
            ->values();
        $permintaanResetPassword = PermintaanResetPassword::with(['user.satuan', 'diprosesOleh'])->latest()->get();
        $labelKategori = [Satuan::KATEGORI_SATLAK => 'Satlak', Satuan::KATEGORI_DIREKTORAT => 'Direktorat', Satuan::KATEGORI_PIMPINAN => 'Pimpinan', Satuan::KATEGORI_ADMIN => 'Admin'];
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
        ];
        $rekapLaporanSatuan = Satuan::whereIn('kode', $kodeSatuanPengirim)->withCount([
            'laporanTerkirim as total_laporan',
            'laporanTerkirim as laporan_disetujui' => fn ($q) => $q->where('status', 'Disetujui DANPUS'),
            'laporanTerkirim as laporan_ditolak' => fn ($q) => $q->where('status', 'Ditolak DANPUS'),
            // Samain persis sama kondisi PermintaanLaporan::isTerlambat().
            'permintaanLaporanMasuk as laporan_terlambat' => fn ($q) => $q->whereNull('laporan_id')
                ->whereNotIn('status', [PermintaanLaporan::STATUS_SELESAI, PermintaanLaporan::STATUS_PEMERIKSAAN, PermintaanLaporan::STATUS_DIBATALKAN])
                ->where('deadline_at', '<', now()),
            'permintaanLaporanMasuk as laporan_dibatalkan' => fn ($q) => $q->where('status', PermintaanLaporan::STATUS_DIBATALKAN),
        ])->get()
            ->sortBy(fn ($s) => sprintf('%d-%s', $prioritasKategori[$s->kategori] ?? 9, $s->nama))
            ->values();
        return view('siberad.dashboards.admin', compact('user','satuan','semuaPengguna','semuaSatuan','permintaanResetPassword','distribusiPenggunaKategori','statusLaporanSistem','aktivitasTujuhHari','logAktivitas','daftarBackup','sesiAktif','rekapLaporanSatuan','logDari','logSampai') + ['pengaturan' => Pengaturan::current(), 'sesiSayaId' => session()->getId(), 'modulHakAkses' => Satuan::MODUL_HAK_AKSES, 'stats' => ['total_pengguna' => $semuaPengguna->count(), 'total_satuan' => $semuaSatuan->count(), 'total_laporan' => Laporan::count(), 'reset_password_pending' => $permintaanResetPassword->where('status', PermintaanResetPassword::STATUS_MENUNGGU)->count()]]);
    }

    private function pelaporan($user, $satuan, ?string $kode): View
    {
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        $permintaanGantiPasswordPending = PermintaanResetPassword::where('user_id', $user->id)
            ->where('status', PermintaanResetPassword::STATUS_MENUNGGU)
            ->latest()
            ->first();
        $laporanTerkirim = Laporan::with('tujuanSatuan')->where('satuan_id', $satuan->id)->latest()->get();
        $laporanMasuk = Laporan::with(['satuan','tujuanSatuan'])
            ->where('tujuan_satuan_id', $satuan->id)
            ->where(function ($query) {
                $query->where('status', 'Menunggu')
                    ->orWhere('status', 'like', 'Revisi%');
            })
            ->latest()
            ->get();
        // Urutan tampil: Admin -> Pimpinan -> Direktorat -> Satuan, lalu
        // abjad nama di dalam kategori yang sama.
        $prioritasKategori = Satuan::prioritasKategori();
        $urutkanSatuan = fn ($s) => sprintf('%d-%s', $prioritasKategori[$s->kategori] ?? 9, $s->nama);
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
            'WADAN' => 'Monitoring dan review laporan antar satuan sebagai bagian dari koordinasi.',
            'DANPUS' => 'Pusat penerimaan, pemantauan, dan peninjauan laporan dari seluruh satuan.',
            default => 'Pelaporan kegiatan dan koordinasi satuan melalui satu alur yang terukur.',
        };

        $kodeSatuanPelaksanaUrut = [
            'SATLAKDAK', 'SATLAKKAL', 'SATLAKSISOS', 'SATLAKDUKTEK',
            'DIKLAT', 'BINUM', 'BINFUNG', 'BINMAT',
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
            $laporanPimpinanSatlak = Laporan::with(['satuan','tujuanSatuan','permintaanLaporan'])
                ->whereIn('satuan_id', $satuanPimpinanIds)
                ->latest()
                ->get();
            $monitoringPimpinanSatlak = Satuan::whereIn('id', $satuanPimpinanIds)->get()
                ->sortBy(fn ($satuanPimpinan) => array_search($satuanPimpinan->kode, $kodeSatuanPelaksanaUrut))
                ->values()
                ->map(fn ($satuanPimpinan) => [
                'id' => $satuanPimpinan->id,
                'kode' => $satuanPimpinan->kode,
                'nama' => $satuanPimpinan->nama,
                'total' => $laporanPimpinanSatlak->where('satuan_id', $satuanPimpinan->id)->count(),
                'menunggu' => $laporanPimpinanSatlak->where('satuan_id', $satuanPimpinan->id)->where('status', 'Menunggu')->count(),
                'diterima' => $laporanPimpinanSatlak->where('satuan_id', $satuanPimpinan->id)->filter(fn ($l) => str_contains(strtolower((string) $l->status), 'setuj') || str_contains(strtolower((string) $l->status), 'diterima'))->count(),
                'ditolak' => $laporanPimpinanSatlak->where('satuan_id', $satuanPimpinan->id)->filter(fn ($l) => str_contains(strtolower((string) $l->status), 'tolak'))->count(),
            ]);
            return view('siberad.dashboards.laporan-pimpinan-shell', compact('user','satuan','laporanMasuk','monitoringPimpinanSatlak','laporanPimpinanSatlak','mode','modePimpinan','canReview','canSend','description','permintaanLaporan','satuanPermintaanLaporan','permintaanGantiPasswordPending'));
        }
        // Terlambat/Dibatalkan dihitung dari SELURUH permintaan laporan yang
        // ditujukan ke satuan ini, bukan $permintaanLaporan (yang sengaja
        // sudah difilter cuma yang masih actionable, tanpa Selesai/
        // Dibatalkan, khusus buat daftar tugas di tab "Permintaan Laporan").
        $permintaanLaporanSemua = PermintaanLaporan::where('tujuan_satuan_id', $satuan->id)->get();
        return view('siberad.dashboards.laporan-role-shell', compact('user','satuan','tujuan','defaultDanpus','laporanTerkirim','laporanMasuk','laporanSatlak','monitoringSatlak','monitoringPimpinanSatlak','laporanPimpinanSatlak','mode','modePimpinan','canReview','canSend','description','permintaanLaporan','satuanPermintaanLaporan','permintaanGantiPasswordPending') + ['defaultTujuanId' => $defaultDanpus?->id, 'stats' => ['dikirim' => $laporanTerkirim->count(), 'disetujui' => $laporanTerkirim->filter(fn($l) => str_contains(strtolower((string)$l->status),'setuj') || str_contains(strtolower((string)$l->status),'diterima'))->count(), 'ditolak' => $laporanTerkirim->filter(fn($l) => str_contains(strtolower((string)$l->status),'tolak'))->count(), 'terlambat' => $permintaanLaporanSemua->filter(fn($p) => $p->isTerlambat())->count(), 'dibatalkan' => $permintaanLaporanSemua->where('status', PermintaanLaporan::STATUS_DIBATALKAN)->count(), 'masuk' => $laporanMasuk->count()]]);
    }
}
