<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use App\Models\Satuan;
use App\Models\User;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
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
     *
     * Menerima query 'q' (pencarian), 'kategori', 'dari', 'sampai' — sama
     * persis dengan filter yang tampil di tabel "Data Pengguna" pada tab
     * Data Laporan, supaya hasil unduhan konsisten dengan apa yang sedang
     * dilihat/difilter oleh admin, bukan selalu seluruh data.
     */
    public function exportUsersExcel(Request $request)
    {
        $users = $this->filteredUsers($request);
        $rows = $users->map(fn ($u) => [
            $u->name ?: '-',
            $u->username ?: '-',
            $u->email ?: '-',
            $u->satuan?->nama ?: '-',
            $u->created_at?->format('d/m/Y H:i') ?: '-',
        ])->all();

        return SimpleXlsx::download(
            'laporan-pengguna-'.now()->format('Ymd_His').'.xlsx',
            'Laporan Pengguna',
            ['Nama', 'Username', 'Email', 'Satuan', 'Dibuat'],
            $rows,
            [34, 22, 38, 38, 22],
        );
    }

    /**
     * Export log aktivitas sebagai XLSX yang mudah dibaca tanpa kolom ####
     * atau teks terpotong: waktu dibuat sebagai teks, deskripsi di-wrap,
     * dan lebar kolom disesuaikan dengan isi.
     *
     * Menerima query 'q', 'kategori', 'dari', 'sampai' — sama seperti pada
     * exportUsersExcel(), supaya kategori/tanggal/pencarian yang dipilih di
     * tabel "Data Aktivitas" benar-benar ikut membatasi isi file unduhan.
     */
    public function exportActivityExcel(Request $request)
    {
        $log = $this->filteredActivityLog($request);
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
                // Dipakai tab "Log Aktivitas" dashboard admin (bukan
                // halaman terpisah ini) buat filter kategori satuan --
                // mapping-nya SAMA persis dengan yang di
                // siberad.dashboards.admin (data-filter-value tiap baris).
                'kategori' => $l->user && $l->user->satuan ? match ($l->user->satuan->kategori) {
                    \App\Models\Satuan::KATEGORI_ADMIN => 'Admin',
                    \App\Models\Satuan::KATEGORI_PIMPINAN => 'Pimpinan',
                    \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
                    \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
                    \App\Models\Satuan::KATEGORI_DIREKTORAT => 'Direktorat',
                    \App\Models\Satuan::KATEGORI_KOTAMA => 'Kasansi',
                    default => 'Satlak',
                } : null,
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
        // "Dari"/"Sampai" kosong (mis. setelah tombol reset) berarti tanpa
        // batas tanggal ke arah itu — samakan dengan filter tanggal Data
        // Laporan, yang menganggap field kosong = tampilkan semua data.
        $dari = $request->filled('log_dari')
            ? \Carbon\Carbon::parse($request->query('log_dari'))->startOfDay()
            : null;
        $sampai = $request->filled('log_sampai')
            ? \Carbon\Carbon::parse($request->query('log_sampai'))->endOfDay()
            : null;

        $log = ActivityLog::with('user.satuan')
            ->when($dari, fn ($q) => $q->where('created_at', '>=', $dari))
            ->when($sampai, fn ($q) => $q->where('created_at', '<=', $sampai))
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
                'id' => $l->id,
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
                ? $this->filteredUsers($request)
                : collect(),
            'log' => $jenis === 'aktivitas'
                ? $this->filteredActivityLog($request)
                : collect(),
            'dicetakOleh' => $request->user(),
            'dicetakPada' => now(),
        ]);
    }

    /**
     * Daftar pengguna terurut organisasi, disaring dengan query 'q'
     * (nama/username/email/satuan/jabatan), 'kategori' (label kategori
     * satuan, mis. "Admin"/"Pimpinan"), serta rentang tanggal dibuat
     * 'dari'/'sampai'. Dipakai bersama oleh export Excel & cetak PDF supaya
     * keduanya konsisten dengan filter yang aktif di tabel "Data Pengguna".
     */
    private function filteredUsers(Request $request)
    {
        $users = User::terurutOrganisasi();

        $q = mb_strtolower(trim((string) $request->query('q', '')));
        $kategori = trim((string) $request->query('kategori', ''));
        $dari = $request->filled('dari') ? Carbon::parse($request->query('dari'))->startOfDay() : null;
        $sampai = $request->filled('sampai') ? Carbon::parse($request->query('sampai'))->endOfDay() : null;

        if ($q !== '') {
            $users = $users->filter(function ($u) use ($q) {
                $haystack = mb_strtolower(trim(implode(' ', [
                    $u->name, $u->username, $u->email,
                    $u->satuan->nama ?? '', $u->jabatan ?? '',
                ])));

                return str_contains($haystack, $q);
            });
        }

        if ($kategori !== '') {
            $users = $users->filter(fn ($u) => $this->kategoriLabel($u->satuan->kategori ?? null) === $kategori);
        }

        if ($dari) {
            $users = $users->filter(fn ($u) => $u->created_at && $u->created_at->gte($dari));
        }

        if ($sampai) {
            $users = $users->filter(fn ($u) => $u->created_at && $u->created_at->lte($sampai));
        }

        return $users->values();
    }

    /**
     * Log aktivitas, disaring dengan query 'q' (pengguna/aksi/deskripsi),
     * 'kategori' (label kategori satuan pemilik log), serta rentang tanggal
     * 'dari'/'sampai'. Dipakai bersama oleh export Excel & cetak PDF supaya
     * keduanya konsisten dengan filter yang aktif di tabel "Data Aktivitas".
     */
    private function filteredActivityLog(Request $request)
    {
        $dari = $request->filled('dari') ? Carbon::parse($request->query('dari'))->startOfDay() : null;
        $sampai = $request->filled('sampai') ? Carbon::parse($request->query('sampai'))->endOfDay() : null;
        $q = mb_strtolower(trim((string) $request->query('q', '')));
        $kategori = trim((string) $request->query('kategori', ''));

        $log = ActivityLog::with('user.satuan')
            ->when($dari, fn ($qq) => $qq->where('created_at', '>=', $dari))
            ->when($sampai, fn ($qq) => $qq->where('created_at', '<=', $sampai))
            ->latest('created_at')
            ->get();

        if ($q !== '') {
            $log = $log->filter(function ($l) use ($q) {
                $haystack = mb_strtolower(trim(($l->nama_pengguna ?? '').' '.$l->aksi.' '.$l->deskripsi));

                return str_contains($haystack, $q);
            });
        }

        if ($kategori !== '') {
            $log = $log->filter(function ($l) use ($kategori) {
                if (! $l->user || ! $l->user->satuan) {
                    return false;
                }

                return $this->kategoriLabel($l->user->satuan->kategori) === $kategori;
            });
        }

        return $log->values();
    }

    /**
     * Label kategori satuan untuk ditampilkan/difilter, disamakan persis
     * dengan mapping yang dipakai tabel "Data Laporan" di
     * siberad.dashboards.admin (dropdown "Semua Kategori" & atribut
     * data-filter-value tiap baris), supaya nilai yang dikirim dari filter
     * di halaman itu cocok dengan yang dihitung di sini.
     */
    private function kategoriLabel(?string $kategori): string
    {
        return match ($kategori) {
            Satuan::KATEGORI_ADMIN => 'Admin',
            Satuan::KATEGORI_PIMPINAN => 'Pimpinan',
            Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
            Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
            Satuan::KATEGORI_DIREKTORAT => 'Direktorat',
            Satuan::KATEGORI_KOTAMA => 'Kasansi',
            default => 'Satlak',
        };
    }
}
