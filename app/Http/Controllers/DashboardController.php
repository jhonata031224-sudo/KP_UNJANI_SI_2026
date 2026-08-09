<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\BackupController;
use App\Models\ActivityLog;
use App\Models\Laporan;
use App\Models\Pengaturan;
use App\Models\Pengumuman;
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
        if ($kode === 'ADMIN') return $this->admin($user, $satuan);
        return $this->pelaporan($user, $satuan, $kode);
    }

    private function admin($user, $satuan): View
    {
        $semuaPengguna = User::with('satuan')->orderBy('name')->get();
        $semuaSatuan = Satuan::withCount('users')->orderBy('urutan')->get();
        $permintaanResetPassword = [
            ['satuan' => 'Satuan Pelaksanaan Siber Sosial', 'catatan' => 'Lupa kata sandi lama', 'tanggal' => '02 Agu 2026', 'status' => 'Menunggu', 'status_class' => 'amber'],
            ['satuan' => 'Pembinaan Materil', 'catatan' => 'Akun terkunci setelah beberapa kali salah input', 'tanggal' => '01 Agu 2026', 'status' => 'Menunggu', 'status_class' => 'amber'],
            ['satuan' => 'Pendidikan dan Latihan', 'catatan' => 'Pergantian operator baru', 'tanggal' => '29 Jul 2026', 'status' => 'Selesai', 'status_class' => 'green'],
        ];
        $labelKategori = [Satuan::KATEGORI_SATLAK => 'Satlak', Satuan::KATEGORI_DIREKTORAT => 'Direktorat', Satuan::KATEGORI_PIMPINAN => 'Pimpinan', Satuan::KATEGORI_ADMIN => 'Admin'];
        $distribusiPenggunaKategori = $semuaSatuan->groupBy('kategori')->map(fn ($group, $kategori) => ['kategori' => $labelKategori[$kategori] ?? ucfirst($kategori), 'jumlah' => $group->sum('users_count')])->values();
        $statusResetPassword = collect($permintaanResetPassword)->groupBy('status')->map(fn ($group, $status) => ['status' => $status, 'jumlah' => $group->count()])->values();
        $satuanSudahPunyaAkun = $semuaSatuan->where('users_count', '>', 0)->count();
        $satuanBelumPunyaAkun = $semuaSatuan->where('users_count', 0)->count();
        $logAktivitas = ActivityLog::with('user')->latest('created_at')->limit(200)->get();
        $daftarBackup = app(BackupController::class)->index();
        $daftarPengumuman = Pengumuman::with('pembuat')->latest()->get();
        $sesiAktif = DB::table('sessions')->leftJoin('users', 'sessions.user_id', '=', 'users.id')->orderByDesc('sessions.last_activity')->get(['sessions.id','sessions.ip_address','sessions.user_agent','sessions.last_activity','users.name as user_name']);
        $rekapLaporanSatuan = Satuan::where('kategori', Satuan::KATEGORI_SATLAK)->withCount(['laporanTerkirim as total_laporan','laporanTerkirim as laporan_menunggu' => fn ($q) => $q->where('status', 'Menunggu'),'laporanTerkirim as laporan_disetujui' => fn ($q) => $q->where('status', 'Disetujui DANPUS'),'laporanTerkirim as laporan_ditolak' => fn ($q) => $q->where('status', 'Ditolak DANPUS')])->orderBy('urutan')->get();
        return view('siberad.dashboards.admin', compact('user','satuan','semuaPengguna','semuaSatuan','permintaanResetPassword','distribusiPenggunaKategori','statusResetPassword','logAktivitas','daftarBackup','daftarPengumuman','sesiAktif','rekapLaporanSatuan') + ['kelengkapanAkunSatuan' => ['sudah' => $satuanSudahPunyaAkun, 'belum' => $satuanBelumPunyaAkun], 'pengaturan' => Pengaturan::current(), 'sesiSayaId' => session()->getId(), 'modulHakAkses' => Satuan::MODUL_HAK_AKSES, 'stats' => ['total_pengguna' => $semuaPengguna->count(), 'total_satuan' => $semuaSatuan->count(), 'reset_password_pending' => collect($permintaanResetPassword)->where('status_class','amber')->count(), 'satuan_tanpa_pengguna' => $semuaSatuan->where('users_count',0)->count()]]);
    }

    private function pelaporan($user, $satuan, ?string $kode): View
    {
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        $laporanTerkirim = Laporan::with('tujuanSatuan')->where('satuan_id', $satuan->id)->latest()->get();
        // Laporan yang sudah diterima/disetujui atau ditolak tidak lagi tampil di antrean "Laporan Masuk".
        // Riwayat tetap menyimpan seluruh laporan karena data tidak dihapus dari database.
        $laporanMasuk = Laporan::with(['satuan','tujuanSatuan'])
            ->where('tujuan_satuan_id', $satuan->id)
            ->where(function ($query) {
                $query->where('status', 'Menunggu')
                    ->orWhere('status', 'like', 'Revisi%');
            })
            ->latest()
            ->get();
        $tujuan = Satuan::where('kode', '!=', 'ADMIN')->where('id', '!=', $satuan->id)->orderBy('urutan')->get();
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
            'SDIR' => 'Pelaporan koordinasi dan perkembangan kegiatan antar satuan.',
            'WADAN' => 'Monitoring dan review laporan antar satuan sebagai bagian dari koordinasi.',
            'DANPUS' => 'Pusat penerimaan, pemantauan, dan peninjauan laporan dari seluruh satuan.',
            default => 'Pelaporan kegiatan dan koordinasi satuan melalui satu alur yang terukur.',
        };
        $monitoringSatlak = collect(); $laporanSatlak = collect();
        if ($mode === 'duktek') {
            $satlakIds = Satuan::whereIn('kode', ['SATLAKKAL','SATLAKSISOS','SATLAKDAK'])->pluck('id');
            $laporanSatlak = Laporan::with(['satuan','tujuanSatuan'])->whereIn('satuan_id', $satlakIds)->latest()->get();
            $monitoringSatlak = Satuan::whereIn('kode', ['SATLAKKAL','SATLAKSISOS','SATLAKDAK'])->orderBy('urutan')->get()->map(fn ($satlak) => ['nama' => $satlak->nama, 'total' => $laporanSatlak->where('satuan_id',$satlak->id)->count()]);
        }
        $monitoringPimpinanSatlak = collect();
        $laporanPimpinanSatlak = collect();
        if ($modePimpinan) {
            $satlakIds = Satuan::where('kategori', Satuan::KATEGORI_SATLAK)->pluck('id');
            $laporanPimpinanSatlak = Laporan::with(['satuan','tujuanSatuan'])->whereIn('satuan_id', $satlakIds)->latest()->get();
            $monitoringPimpinanSatlak = Satuan::whereIn('id', $satlakIds)->orderBy('urutan')->get()->map(fn ($satlak) => [
                'id' => $satlak->id,
                'kode' => $satlak->kode,
                'nama' => $satlak->nama,
                'total' => $laporanPimpinanSatlak->where('satuan_id', $satlak->id)->count(),
                'menunggu' => $laporanPimpinanSatlak->where('satuan_id', $satlak->id)->where('status', 'Menunggu')->count(),
                'diterima' => $laporanPimpinanSatlak->where('satuan_id', $satlak->id)->filter(fn ($l) => str_contains(strtolower((string) $l->status), 'setuj') || str_contains(strtolower((string) $l->status), 'diterima'))->count(),
                'ditolak' => $laporanPimpinanSatlak->where('satuan_id', $satlak->id)->filter(fn ($l) => str_contains(strtolower((string) $l->status), 'tolak'))->count(),
            ]);
            return view('siberad.dashboards.laporan-pimpinan-shell', compact('user','satuan','laporanMasuk','monitoringPimpinanSatlak','laporanPimpinanSatlak','mode','modePimpinan','canReview','canSend','description'));
        }
        return view('siberad.dashboards.laporan-role-shell', compact('user','satuan','tujuan','defaultDanpus','laporanTerkirim','laporanMasuk','laporanSatlak','monitoringSatlak','monitoringPimpinanSatlak','laporanPimpinanSatlak','mode','modePimpinan','canReview','canSend','description') + ['defaultTujuanId' => $defaultDanpus?->id, 'stats' => ['dikirim' => $laporanTerkirim->count(), 'menunggu' => $laporanTerkirim->where('status','Menunggu')->count(), 'disetujui' => $laporanTerkirim->filter(fn($l) => str_contains(strtolower((string)$l->status),'setuj') || str_contains(strtolower((string)$l->status),'diterima'))->count(), 'ditolak' => $laporanTerkirim->filter(fn($l) => str_contains(strtolower((string)$l->status),'tolak'))->count(), 'masuk' => $laporanMasuk->count()]]);
    }
}
