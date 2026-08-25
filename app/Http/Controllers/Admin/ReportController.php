<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use App\Models\User;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Tab "Laporan Pengguna & Aktivitas" — rekap pengguna per satuan dan
     * aktivitas terakhir, sumber untuk tombol export di bawah.
     */
    public function index(Request $request): View
    {
        $dariTanggal = $request->date('dari');
        $sampaiTanggal = $request->date('sampai');

        $log = ActivityLog::with('user')
            ->when($dariTanggal, fn ($q) => $q->whereDate('created_at', '>=', $dariTanggal))
            ->when($sampaiTanggal, fn ($q) => $q->whereDate('created_at', '<=', $sampaiTanggal))
            ->latest('created_at')
            ->limit(500)
            ->get();

        return view('admin.laporan', [
            'user' => $request->user()->load('satuan'),
            'satuan' => $request->user()->satuan,
            'pengaturan' => Pengaturan::current(),
            'semuaPengguna' => User::terurutOrganisasi(),
            'log' => $log,
            'dari' => $dariTanggal?->format('Y-m-d'),
            'sampai' => $sampaiTanggal?->format('Y-m-d'),
        ]);
    }

    /**
     * Export daftar pengguna sebagai XLSX yang sudah diformat untuk Excel:
     * kolom diberi lebar, header jelas, filter aktif, dan isi panjang di-wrap.
     */
    public function exportUsersExcel()
    {
        $users = User::terurutOrganisasi();
        $rows = $users->map(fn ($u) => [
            $u->name ?: '-',
            $u->username ?: '-',
            $u->email ?: '-',
            $u->satuan?->nama ?: '-',
            $u->jabatan ?: '-',
            $u->created_at?->format('d/m/Y H:i') ?: '-',
        ])->all();

        return SimpleXlsx::download(
            'laporan-pengguna-'.now()->format('Ymd_His').'.xlsx',
            'Laporan Pengguna',
            ['Nama', 'Username', 'Email', 'Satuan', 'Jabatan', 'Dibuat'],
            $rows,
            [34, 22, 38, 38, 34, 22],
        );
    }

    /**
     * Export log aktivitas sebagai XLSX yang mudah dibaca tanpa kolom ####
     * atau teks terpotong: waktu dibuat sebagai teks, deskripsi di-wrap,
     * dan lebar kolom disesuaikan dengan isi.
     */
    public function exportActivityExcel(Request $request)
    {
        $log = ActivityLog::with('user.satuan')->latest('created_at')->limit(2000)->get();
        $rows = $log->map(fn ($l) => [
            $l->created_at?->format('d/m/Y H:i:s') ?: '-',
            $l->nama_pengguna ?: ($l->user?->name ?: '-'),
            $l->user?->satuan?->nama ?: '-',
            $l->aksi ?: '-',
            $l->deskripsi ?: '-',
            $l->context ? json_encode($l->context, JSON_UNESCAPED_UNICODE) : '-',
            $l->ip_address ?: '-',
        ])->all();

        return SimpleXlsx::download(
            'log-aktivitas-'.now()->format('Ymd_His').'.xlsx',
            'Riwayat Aktivitas',
            ['Waktu', 'Pengguna', 'Satuan', 'Aksi', 'Deskripsi', 'Detail', 'IP Address'],
            $rows,
            [23, 32, 26, 30, 70, 46, 22],
        );
    }

    /**
     * Endpoint JSON dipoll otomatis oleh halaman Log Aktivitas (lihat script
     * di admin.laporan) supaya aktivitas baru muncul realtime tanpa reload.
     * Hanya mengembalikan log dengan id lebih besar dari 'after_id'.
     */
    public function aktivitasTerbaru(Request $request)
    {
        $afterId = (int) $request->query('after_id', 0);

        $log = ActivityLog::with('user.satuan')
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json([
            'log' => $log->map(fn ($l) => [
                'id' => $l->id,
                'waktu' => $l->created_at?->translatedFormat('d M Y H:i:s'),
                'pengguna' => $l->nama_pengguna ?: ($l->user?->name ?: '-'),
                'satuan' => $l->user?->satuan?->nama ?: '-',
                'aksi' => $l->aksi,
                'deskripsi' => $l->deskripsi,
                'ip' => $l->ip_address,
            ]),
            'max_id' => $log->max('id') ?? $afterId,
        ]);
    }

    /**
     * Endpoint JSON untuk filter tanggal di tab "Log Aktivitas" dashboard
     * admin (lihat siberad.dashboards.admin). Dipanggil lewat fetch() setiap
     * input tanggal "Dari"/"Sampai" berubah, supaya tabelnya kefilter tanpa
     * reload halaman.
     */
    public function logAktivitasRentang(Request $request)
    {
        $sampai = $request->filled('log_sampai')
            ? \Carbon\Carbon::parse($request->query('log_sampai'))->endOfDay()
            : now()->endOfDay();
        // "Dari" kosong (mis. setelah tombol reset) berarti tanpa batas
        // bawah tanggal — samakan dengan filter tanggal Data Laporan, yang
        // menganggap "Dari" kosong = tampilkan semua sampai batas "Sampai".
        $dari = $request->filled('log_dari')
            ? \Carbon\Carbon::parse($request->query('log_dari'))->startOfDay()
            : null;

        $log = ActivityLog::with('user.satuan')
            ->when($dari, fn ($q) => $q->where('created_at', '>=', $dari))
            ->where('created_at', '<=', $sampai)
            ->latest('created_at')
            ->get();

        // Label kategori disamakan persis dengan yang dipakai filter
        // "Detail per Satuan"/"Daftar Pengguna" di dashboard admin, supaya
        // baris yang dimuat ulang lewat AJAX ini tetap kena filter kategori
        // yang sama walau tabelnya dirender ulang lewat JS (bukan Blade).
        $labelKategori = [
            \App\Models\Satuan::KATEGORI_ADMIN => 'Admin',
            \App\Models\Satuan::KATEGORI_PIMPINAN => 'Pimpinan',
            \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
            \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
            \App\Models\Satuan::KATEGORI_DIREKTORAT => 'Direktorat',
            \App\Models\Satuan::KATEGORI_KOTAMA => 'Kasansi',
        ];

        return response()->json([
            'log' => $log->map(fn ($l) => [
                'waktu' => $l->created_at?->translatedFormat('d M Y H:i'),
                'pengguna' => $l->nama_pengguna ?? '-',
                'aksi' => $l->aksi,
                'deskripsi' => $l->deskripsi,
                'ip' => $l->ip_address,
                'kategori' => $l->user?->satuan
                    ? ($labelKategori[$l->user->satuan->kategori] ?? 'Satlak')
                    : null,
            ]),
            'total_rentang' => $log->count(),
            'total_keseluruhan' => ActivityLog::count(),
        ]);
    }

    /**
     * Versi cetak (untuk disimpan sebagai PDF lewat dialog "Print" browser),
     * dipisah per jenis ('pengguna' atau 'aktivitas') supaya masing-masing
     * jadi dokumen sendiri yang fokus, bukan digabung dalam satu halaman.
     */
    public function printView(Request $request, string $jenis): View
    {
        return view('admin.laporan-cetak', [
            'jenis' => $jenis,
            'pengaturan' => Pengaturan::current(),
            'semuaPengguna' => $jenis === 'pengguna'
                ? User::terurutOrganisasi()
                : collect(),
            'log' => $jenis === 'aktivitas'
                ? ActivityLog::with('user.satuan')->latest('created_at')->limit(500)->get()
                : collect(),
            'dicetakOleh' => $request->user(),
            'dicetakPada' => now(),
        ]);
    }
}
