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
            'semuaPengguna' => User::with('satuan')->orderBy('name')->get(),
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
        $users = User::with('satuan')->orderBy('name')->get();
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
            'Log Aktivitas',
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
                ? User::with('satuan')->orderBy('name')->get()
                : collect(),
            'log' => $jenis === 'aktivitas'
                ? ActivityLog::with('user.satuan')->latest('created_at')->limit(500)->get()
                : collect(),
            'dicetakOleh' => $request->user(),
            'dicetakPada' => now(),
        ]);
    }
}
