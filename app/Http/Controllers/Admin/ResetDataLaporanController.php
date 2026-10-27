<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Laporan;
use App\Models\LaporanKendala;
use App\Models\LaporanMonitoring;
use App\Models\LaporanSurat;
use App\Models\LaporanSuratRiwayat;
use App\Models\PermintaanLaporan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Fitur "Reset Data Laporan" khusus Admin: membersihkan data laporan
 * (dummy/uji coba) secara permanen per kategori atau per baris spesifik,
 * TANPA menyentuh data pengguna (username/password), satuan, ataupun pengaturan sistem.
 */
class ResetDataLaporanController extends Controller
{
    /**
     * Daftar kategori data laporan yang boleh dibersihkan, beserta
     * tabel-tabel yang terlibat. Urutan tabel dalam tiap kategori sengaja
     * anak dulu baru induk, supaya aman dihapus meskipun foreign key
     * constraint tidak ditegakkan oleh database.
     */
    public const KATEGORI = [
        'laporan' => [
            'label' => 'Laporan & Kendala',
            'desc' => 'Laporan berkala dan laporan kendala operasional yang dikirim satuan, beserta lampirannya.',
            'icon' => 'file-text',
            'tables' => [
                ['table' => 'laporans', 'file_column' => 'lampiran_path'],
                ['table' => 'laporan_kendalas', 'file_column' => 'lampiran_path'],
            ],
        ],
        'monitoring' => [
            'label' => 'Monitoring & Recovery',
            'desc' => 'Rekap kegiatan monitoring sistem dan recovery insiden yang dikirim satuan, beserta berkas pendukung.',
            'icon' => 'activity',
            'tables' => [
                ['table' => 'laporan_monitoring_lampirans', 'file_column' => 'path'],
                ['table' => 'laporan_monitorings', 'file_column' => null],
            ],
        ],
        'permintaan' => [
            'label' => 'Permintaan Laporan',
            'desc' => 'Permintaan laporan dari pimpinan ke satuan, beserta penugasan (task) untuk tiap satuan tujuan.',
            'icon' => 'clipboard-list',
            'tables' => [
                ['table' => 'permintaan_laporan_tasks', 'file_column' => null],
                ['table' => 'permintaan_laporans', 'file_column' => null],
            ],
        ],
        'surat' => [
            'label' => 'Surat',
            'desc' => 'Surat masuk/keluar antar satuan, riwayat alur disposisi, tembusan, dan berkas lampirannya di semua pengguna terkait.',
            'icon' => 'mail',
            'tables' => [
                ['table' => 'laporan_surat_tembusans', 'file_column' => null],
                ['table' => 'laporan_surat_riwayats', 'file_column' => 'lampiran_path'],
                ['table' => 'laporan_surats', 'file_column' => 'lampiran_path'],
            ],
        ],
    ];

    /**
     * Hitung jumlah baris tabel utama tiap kategori, dipakai untuk
     * ditampilkan di indikator ringkasan "Reset Data Laporan".
     */
    public static function hitungPerKategori(): array
    {
        $hasil = [];

        foreach (self::KATEGORI as $key => $def) {
            $count = 0;
            foreach ($def['tables'] as $t) {
                // Untuk kategori gabungan (seperti laporan & kendala), jumlahkan tabel utamanya
                if ($key === 'laporan') {
                    $count += DB::table($t['table'])->count();
                } else {
                    $tabelUtama = collect($def['tables'])->last()['table'];
                    $count = DB::table($tabelUtama)->count();
                    break;
                }
            }
            $hasil[$key] = $count;
        }

        return $hasil;
    }

    /**
     * Ambil rincian baris data per kategori untuk ditampilkan dalam
     * card panel detail interaktif, sehingga Admin bisa meninjau dan memilih
     * baris mana yang hendak dihapus secara selektif.
     */
    public static function ambilDetailPerKategori(): array
    {
        $detail = [
            'laporan' => [],
            'monitoring' => [],
            'permintaan' => [],
            'surat' => [],
        ];

        try {
            // 1. Kategori: Laporan & Kendala
            $laporans = Laporan::with(['satuan', 'user', 'lampirans'])->latest('id')->limit(150)->get();
            foreach ($laporans as $row) {
                $detail['laporan'][] = [
                    'key' => 'laporan:'.$row->id,
                    'id' => $row->id,
                    'tipe' => 'laporan',
                    'subtipe' => 'Laporan Berkala',
                    'subtipe_badge' => 'gold',
                    'judul' => $row->perihal ?: ($row->proyek ?: 'Laporan #'.$row->id),
                    'satuan' => $row->satuan->nama ?? ($row->satuan->kode ?? '-'),
                    'user' => $row->user->name ?? '-',
                    'tanggal' => $row->created_at ? $row->created_at->translatedFormat('d M Y H:i') : '-',
                    'status' => $row->status ?: 'Terkirim',
                    'lampiran' => !empty($row->lampiran_path),
                    'lampiran_files' => self::daftarLampiran($row->semuaLampiran->map(fn ($x) => ['path' => $x->path, 'nama' => $x->nama_asli])->all()),
                    'ts' => $row->created_at ? $row->created_at->timestamp : 0,
                ];
            }

            $kendalas = LaporanKendala::with(['satuan', 'user', 'lampirans'])->latest('id')->limit(150)->get();
            foreach ($kendalas as $row) {
                $detail['laporan'][] = [
                    'key' => 'kendala:'.$row->id,
                    'id' => $row->id,
                    'tipe' => 'kendala',
                    'subtipe' => 'Laporan Kendala',
                    'subtipe_badge' => 'amber',
                    'judul' => $row->perihal ?: 'Kendala #'.$row->id,
                    'satuan' => $row->satuan->nama ?? ($row->satuan->kode ?? '-'),
                    'user' => $row->user->name ?? '-',
                    'tanggal' => $row->created_at ? $row->created_at->translatedFormat('d M Y H:i') : '-',
                    'status' => $row->status ?: 'Terkirim',
                    'lampiran' => !empty($row->lampiran_path),
                    'lampiran_files' => self::daftarLampiran($row->semuaLampiran->map(fn ($x) => ['path' => $x->path, 'nama' => $x->nama_asli])->all()),
                    'ts' => $row->created_at ? $row->created_at->timestamp : 0,
                ];
            }
            usort($detail['laporan'], fn ($a, $b) => $b['ts'] <=> $a['ts']);

            // 2. Kategori: Monitoring & Recovery
            $monitorings = LaporanMonitoring::with(['satuan', 'user'])->latest('id')->limit(150)->get();
            foreach ($monitorings as $row) {
                $detail['monitoring'][] = [
                    'key' => 'monitoring:'.$row->id,
                    'id' => $row->id,
                    'tipe' => 'monitoring',
                    'subtipe' => 'Monitoring & Recovery',
                    'subtipe_badge' => 'cyan',
                    'judul' => $row->jenis_kegiatan ?: ($row->ringkasan_kegiatan ? Str::limit($row->ringkasan_kegiatan, 50) : 'Monitoring #'.$row->id),
                    'satuan' => $row->satuan->nama ?? ($row->satuan->kode ?? '-'),
                    'user' => $row->user->name ?? '-',
                    'tanggal' => $row->created_at ? $row->created_at->translatedFormat('d M Y H:i') : '-',
                    'status' => $row->status ?: 'Terkirim',
                    'lampiran' => false,
                    'ts' => $row->created_at ? $row->created_at->timestamp : 0,
                ];
            }

            // 3. Kategori: Permintaan Laporan (beserta task penugasannya)
            $permintaans = PermintaanLaporan::with(['tujuanSatuan', 'pembuat'])->latest('id')->limit(150)->get();
            foreach ($permintaans as $row) {
                $detail['permintaan'][] = [
                    'key' => 'permintaan:'.$row->id,
                    'id' => $row->id,
                    'tipe' => 'permintaan',
                    'subtipe' => 'Permintaan Laporan',
                    'subtipe_badge' => 'amber',
                    'judul' => $row->perihal ?: 'Permintaan #'.$row->id,
                    'satuan' => $row->tujuanSatuan->nama ?? ($row->tujuanSatuan->kode ?? '-'),
                    'user' => $row->pembuat->name ?? '-',
                    'tanggal' => $row->created_at ? $row->created_at->translatedFormat('d M Y H:i') : '-',
                    'status' => $row->status ?: 'Aktif',
                    'lampiran' => false,
                    'ts' => $row->created_at ? $row->created_at->timestamp : 0,
                ];
            }

            // 4. Kategori: Surat (surat antar satuan/pengguna, semua pihak terkait)
            $surats = LaporanSurat::with(['satuan', 'tujuanSatuan', 'user', 'riwayats.pengirimSatuan', 'riwayats.penerimaSatuan'])->latest('id')->limit(150)->get();
            foreach ($surats as $row) {
                $detail['surat'][] = [
                    'key' => 'surat:'.$row->id,
                    'id' => $row->id,
                    'tipe' => 'surat',
                    'subtipe' => 'Surat',
                    'subtipe_badge' => 'purple',
                    'judul' => $row->perihalUntuk(auth()->user()?->satuan_id) ?: 'Surat #'.$row->id,
                    'satuan' => self::jalurAlurSurat($row),
                    'alur_ket' => self::keteranganAlurSurat($row),
                    'grup' => self::grupSurat($row),
                    'user' => $row->user->name ?? '-',
                    'tanggal' => $row->created_at ? $row->created_at->translatedFormat('d M Y H:i') : '-',
                    'status' => $row->labelStatus(),
                    'lampiran' => !empty($row->lampiran_path),
                    // Surat Rahasia: tautan/nama berkas tidak ditampilkan ke Admin (hanya penanda).
                    'lampiran_rahasia' => $row->isRahasia(),
                    'lampiran_files' => $row->isRahasia() ? [] : self::daftarLampiran(array_merge(
                        [['path' => $row->lampiran_path, 'nama' => $row->lampiran_nama_asli]],
                        $row->riwayats->map(fn ($r) => ['path' => $r->lampiran_path, 'nama' => $r->lampiran_nama_asli])->all()
                    )),
                    'ts' => $row->created_at ? $row->created_at->timestamp : 0,
                ];
            }
        } catch (\Throwable $e) {
            // Tangani gracefully jika tabel belum siap atau migration belum jalan
        }

        return $detail;
    }

    /**
     * Ubah daftar [path, nama] lampiran jadi [url, nama] siap pakai di view admin:
     * path kosong dilewati & path ganda digabung. URL memakai pola yang sama dengan
     * view lain (asset('storage/'.path), disk "public"). Hanya untuk tampilan.
     *
     * @param  array<int, array{path:?string, nama:?string}>  $files
     * @return array<int, array{url:string, nama:string}>
     */
    private static function daftarLampiran(array $files): array
    {
        $hasil = [];
        foreach ($files as $f) {
            $path = $f['path'] ?? null;
            if (empty($path) || isset($hasil[$path])) {
                continue;
            }
            $hasil[$path] = [
                'url' => asset('storage/'.ltrim($path, '/')),
                'nama' => ($f['nama'] ?? '') !== '' ? $f['nama'] : basename($path),
            ];
        }

        return array_values($hasil);
    }

    /**
     * Nama satuan versi singkat (mis. "Danpus" dari "Danpus (Komandan Pusat
     * Pussiberad)") supaya jalur alur surat muat dibaca di tabel admin.
     */
    private static function namaSingkatSatuan($satuan): string
    {
        if (! $satuan) {
            return '-';
        }

        return $satuan->nama_singkat ?: ($satuan->kode ?? '-');
    }

    /**
     * Daftar satuan yang dilalui surat, urut sesuai riwayat sebenarnya
     * (pembuat -> penerima tiap langkah: surat dibuat, diteruskan/disposisi,
     * balasan/surat keluar). Konfirmasi & Selesai tidak menambah satuan baru.
     * Hanya untuk tampilan admin; tidak mengubah data/alur surat.
     *
     * @return array<int, string>
     */
    private static function jalurSatuanSurat(LaporanSurat $row): array
    {
        $jalur = [];
        $tambah = function ($satuan) use (&$jalur) {
            $nama = self::namaSingkatSatuan($satuan);
            if ($nama !== '-' && (empty($jalur) || end($jalur) !== $nama)) {
                $jalur[] = $nama;
            }
        };

        $langkahPindah = [
            LaporanSuratRiwayat::AKSI_BUAT_SURAT,
            LaporanSuratRiwayat::AKSI_TERUSKAN,
            LaporanSuratRiwayat::AKSI_SURAT_KELUAR,
        ];

        foreach ($row->riwayats->sortBy('id') as $r) {
            if (! in_array($r->aksi, $langkahPindah, true)) {
                continue;
            }
            if (empty($jalur)) {
                $tambah($r->pengirimSatuan);
            }
            $tambah($r->penerimaSatuan);
        }

        // Data lama tanpa riwayat: pakai pembuat -> pemegang saat ini.
        if (count($jalur) < 2) {
            $jalur = [];
            $tambah($row->satuan);
            $tambah($row->tujuanSatuan);
        }

        return $jalur;
    }

    /**
     * Rangkai daftar satuan dari sekumpulan langkah riwayat (pengirim -> penerima),
     * tanpa satuan ganda berurutan. Hanya untuk tampilan admin.
     *
     * @return array<int, string>
     */
    private static function rangkaiJalur(iterable $langkah): array
    {
        $jalur = [];
        $tambah = function ($satuan) use (&$jalur) {
            $nama = self::namaSingkatSatuan($satuan);
            if ($nama !== '-' && (empty($jalur) || end($jalur) !== $nama)) {
                $jalur[] = $nama;
            }
        };

        foreach ($langkah as $r) {
            if (empty($jalur)) {
                $tambah($r->pengirimSatuan);
            }
            $tambah($r->penerimaSatuan);
        }

        return $jalur;
    }

    /**
     * Pecah riwayat surat Danpus jadi dua jalur (siklus berjalan; jika siklus itu
     * belum punya balasan, pakai seluruh riwayat):
     *  - 'turun'   : Danpus -> ... -> satuan penerima akhir (sebelum balasan pertama)
     *  - 'balasan' : satuan penerima -> ... -> Danpus (mulai balasan pertama sampai ke Danpus)
     * Hanya untuk tampilan admin; tidak mengubah data/alur surat.
     *
     * @return array{turun: array<int, string>, balasan: array<int, string>}
     */
    private static function jalurTurunDanBalasan(LaporanSurat $row): array
    {
        $semua = $row->riwayats->sortBy('id')->values();
        $siklusIni = $semua->where('siklus', (int) ($row->siklus ?? 1))->values();
        $riwayats = $siklusIni->contains('aksi', LaporanSuratRiwayat::AKSI_SURAT_KELUAR) ? $siklusIni : $semua;

        $idxBalasan = $riwayats->search(fn ($r) => $r->aksi === LaporanSuratRiwayat::AKSI_SURAT_KELUAR);

        $turunLangkah = $riwayats
            ->filter(fn ($r, $i) => ($idxBalasan === false || $i < $idxBalasan)
                && in_array($r->aksi, [LaporanSuratRiwayat::AKSI_BUAT_SURAT, LaporanSuratRiwayat::AKSI_TERUSKAN], true));

        $balasanLangkah = $idxBalasan === false
            ? collect()
            : $riwayats->filter(fn ($r, $i) => $i >= $idxBalasan
                && in_array($r->aksi, [LaporanSuratRiwayat::AKSI_SURAT_KELUAR, LaporanSuratRiwayat::AKSI_TERUSKAN], true));

        $turun = self::rangkaiJalur($turunLangkah);
        if (count($turun) < 2) {
            // Data lama tanpa riwayat: pembuat -> pemegang saat ini.
            $turun = array_values(array_filter([
                self::namaSingkatSatuan($row->satuan),
                self::namaSingkatSatuan($row->tujuanSatuan),
            ], fn ($n) => $n !== '-'));
        }

        return ['turun' => $turun, 'balasan' => self::rangkaiJalur($balasanLangkah)];
    }

    /**
     * Satuan tujuan yang dipilih Wadan saat menekan "Teruskan Surat" pada surat
     * turun Danpus (siklus berjalan, sebelum balasan pertama). Kosong = surat
     * belum diteruskan Wadan. Hanya untuk tampilan admin.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Satuan>
     */
    private static function tujuanTeruskanWadan(LaporanSurat $row): \Illuminate\Support\Collection
    {
        $semua = $row->riwayats->sortBy('id')->values();
        $siklusIni = $semua->where('siklus', (int) ($row->siklus ?? 1))->values();
        $riwayats = $siklusIni->contains('aksi', LaporanSuratRiwayat::AKSI_SURAT_KELUAR) ? $siklusIni : $semua;

        $idxBalasan = $riwayats->search(fn ($r) => $r->aksi === LaporanSuratRiwayat::AKSI_SURAT_KELUAR);

        return $riwayats
            ->filter(function ($r, $i) use ($idxBalasan) {
                return ($idxBalasan === false || $i < $idxBalasan)
                    && $r->aksi === LaporanSuratRiwayat::AKSI_TERUSKAN
                    && strtoupper((string) ($r->pengirimSatuan->kode ?? '')) === 'WADAN'
                    && ! in_array(strtoupper((string) ($r->penerimaSatuan->kode ?? '')), ['', 'DANPUS', 'WADAN'], true);
            })
            ->map(fn ($r) => $r->penerimaSatuan)
            ->filter()
            ->unique('id')
            ->values();
    }

    private static function jalurAlurSurat(LaporanSurat $row): string
    {
        // Surat dari Danpus: tampilkan jalur turun saja (Danpus -> ... -> satuan penerima akhir).
        // Balasan ke Danpus: tampilkan jalur balasan saja (satuan penerima -> ... -> Danpus).
        // Surat Keluar Satuan: jalur lengkap seperti sebelumnya.
        $grup = self::grupSurat($row);

        if ($grup === 'danpus') {
            $jalur = implode(' → ', self::jalurTurunDanBalasan($row)['turun']);

            // Belum diteruskan Wadan -> penerima akhir memang belum ditentukan.
            if (self::tujuanTeruskanWadan($row)->isEmpty()) {
                $jalur .= ' → (menunggu diteruskan Wadan)';
            }

            return $jalur;
        }

        if ($grup === 'balasan') {
            $balasan = self::jalurTurunDanBalasan($row)['balasan'];
            if (count($balasan) >= 2) {
                return implode(' → ', $balasan);
            }
        }

        return implode(' → ', self::jalurSatuanSurat($row));
    }

    /**
     * Kelompok surat untuk tab pemisah di menu Reset Data (hanya tampilan admin):
     *  - 'danpus'  : surat turun yang dikeluarkan Danpus (belum ada balasan satuan)
     *  - 'balasan' : surat turun Danpus yang sudah dibalas satuan (balasan naik ke Danpus)
     *  - 'satuan'  : surat keluar murni yang berawal dari satuan (pengajuan satuan)
     * Balasan satuan disimpan sebagai riwayat SURAT_KELUAR pada surat Danpus yang sama
     * (bukan baris baru), jadi satu surat hanya masuk ke satu kelompok.
     */
    private static function grupSurat(LaporanSurat $row): string
    {
        if (strtoupper((string) ($row->satuan->kode ?? '')) !== 'DANPUS') {
            return 'satuan';
        }

        return $row->riwayats->contains('aksi', LaporanSuratRiwayat::AKSI_SURAT_KELUAR)
            ? 'balasan'
            : 'danpus';
    }

    /**
     * Keterangan jenis alur: surat turun dari Danpus ke satuan (dan balasan
     * satuan naik lagi), atau pengajuan/surat keluar yang berawal dari satuan.
     */
    private static function keteranganAlurSurat(LaporanSurat $row): string
    {
        $asal = self::namaSingkatSatuan($row->satuan);
        $adaBalasan = $row->riwayats->contains('aksi', LaporanSuratRiwayat::AKSI_SURAT_KELUAR);

        if (strtoupper((string) ($row->satuan->kode ?? '')) === 'DANPUS') {
            $diteruskan = self::tujuanTeruskanWadan($row)
                ->map(fn ($sat) => $sat->nama ?: self::namaSingkatSatuan($sat))
                ->implode(', ');

            if ($adaBalasan) {
                $turun = self::jalurTurunDanBalasan($row)['turun'];
                $ket = 'Balasan naik ke Danpus · Surat turun: '.implode(' → ', $turun);
                if ($diteruskan !== '') {
                    $ket .= ' · Wadan meneruskan ke '.$diteruskan;
                }
            } elseif ($diteruskan !== '') {
                $ket = 'Surat turun dari Danpus · Wadan meneruskan ke '.$diteruskan
                    .' · Saat ini di '.self::namaSingkatSatuan($row->tujuanSatuan)
                    .' ('.$row->labelStatus().')';
            } else {
                $ket = 'Surat turun dari Danpus · Belum diteruskan Wadan, masih di '
                    .self::namaSingkatSatuan($row->tujuanSatuan).' ('.$row->labelStatus().')';
            }
        } else {
            $ket = 'Pengajuan surat dari '.$asal;
        }

        if ((int) ($row->siklus ?? 1) > 1) {
            $ket .= ' · Siklus '.(int) $row->siklus;
        }

        return $ket;
    }

    /**
     * Gerbang akses menu Reset Data Laporan (password + captcha), pola sama
     * dengan Pengaturan Umum (SettingController::verifyLandingAccess).
     * Status terverifikasi disimpan di sesi dan dicabut (action=revoke) begitu
     * Admin pindah ke menu lain. Password = password login Admin yang aktif.
     */
    public function verifyAccess(Request $request): JsonResponse
    {
        if ($request->input('action') === 'revoke') {
            $request->session()->forget(['reset_data_terverifikasi', 'reset_data_terverifikasi_at', 'captcha_code']);

            return response()->json(['ok' => true, 'access' => false]);
        }

        $key = 'admin-reset-data-access:'.$request->user()->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ], 429);
        }

        RateLimiter::increment($key, 60);

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'captcha'  => ['required', 'string', 'size:5'],
        ], [
            'password.required' => 'Password wajib diisi.',
            'captcha.required'  => 'Captcha wajib diisi.',
            'captcha.size'      => 'Captcha harus terdiri dari 5 karakter.',
        ]);

        $captchaExpected = (string) $request->session()->pull('captcha_code', '');
        // Password akses = password login Admin yang sedang aktif.
        $passwordValid   = Hash::check(trim((string) $validated['password']), (string) $request->user()->password);
        $captchaValid  = $captchaExpected !== '' && hash_equals($captchaExpected, (string) $validated['captcha']);

        if (! $passwordValid || ! $captchaValid) {
            ActivityLog::catat('reset-data.access_denied', 'Percobaan membuka Reset Data Laporan ditolak karena password atau captcha tidak valid.');

            if (! $passwordValid && ! $captchaValid) {
                $pesan = 'Password dan captcha salah. Periksa ulang keduanya.';
            } elseif (! $passwordValid) {
                $pesan = 'Password salah. Gunakan password login Admin Anda.';
            } else {
                $pesan = 'Kode captcha salah atau sudah kedaluwarsa. Captcha baru sudah dimuat ulang, coba masukkan lagi.';
            }

            return response()->json(['message' => $pesan], 422);
        }

        RateLimiter::clear($key);
        // Sama seperti Pengaturan Umum: sengaja TIDAK regenerate sesi supaya
        // token CSRF form hapus yang sudah ter-render tidak basi (419).
        $request->session()->put('reset_data_terverifikasi', true);
        $request->session()->put('reset_data_terverifikasi_at', now()->timestamp);

        ActivityLog::catat('reset-data.access_granted', 'Konfirmasi akses Reset Data Laporan berhasil.');

        return response()->json([
            'ok'         => true,
            'access'     => true,
            'csrf_token' => $request->session()->token(),
        ]);
    }

    /**
     * Hapus data laporan baik per baris terpilih (selektif) ataupun per kategori utuh.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Penjagaan di SERVER (bukan cuma modal di tampilan): penghapusan
        // permanen hanya boleh jika akses sudah diverifikasi password + captcha.
        if (! $request->session()->get('reset_data_terverifikasi', false)) {
            return back()->with('error', 'Akses Reset Data Laporan belum diverifikasi. Masukkan password dan captcha terlebih dahulu.');
        }

        // -------------------------------------------------------------
        // MODE A: Hapus baris-baris spesifik yang dipilih (item_keys[])
        // -------------------------------------------------------------
        if ($request->filled('item_keys')) {
            $itemKeys = (array) $request->input('item_keys');
            if (empty($itemKeys)) {
                return back()->with('error', 'Pilih minimal satu baris data yang ingin dihapus.');
            }

            $totalTerhapus = 0;

            DB::transaction(function () use ($itemKeys, &$totalTerhapus) {
                foreach ($itemKeys as $itemKey) {
                    if (!str_contains($itemKey, ':')) continue;
                    [$tipe, $id] = explode(':', $itemKey, 2);
                    $id = (int) $id;
                    if ($id <= 0) continue;

                    switch ($tipe) {
                        case 'laporan':
                            $row = Laporan::find($id);
                            if ($row) {
                                if (!empty($row->lampiran_path)) {
                                    Storage::disk('public')->delete($row->lampiran_path);
                                }
                                // Bersihkan berkas di relasi lampiran (jika ada tabel laporan_lampirans)
                                if (DB::getSchemaBuilder()->hasTable('laporan_lampirans')) {
                                    DB::table('laporan_lampirans')->where('laporan_id', $id)->pluck('path')->filter()->each(function ($p) {
                                        Storage::disk('public')->delete($p);
                                    });
                                    DB::table('laporan_lampirans')->where('laporan_id', $id)->delete();
                                }
                                $row->delete();
                                $totalTerhapus++;
                            }
                            break;

                        case 'kendala':
                            $row = LaporanKendala::find($id);
                            if ($row) {
                                if (!empty($row->lampiran_path)) {
                                    Storage::disk('public')->delete($row->lampiran_path);
                                }
                                if (DB::getSchemaBuilder()->hasTable('laporan_kendala_lampirans')) {
                                    DB::table('laporan_kendala_lampirans')->where('laporan_kendala_id', $id)->pluck('path')->filter()->each(function ($p) {
                                        Storage::disk('public')->delete($p);
                                    });
                                    DB::table('laporan_kendala_lampirans')->where('laporan_kendala_id', $id)->delete();
                                }
                                $row->delete();
                                $totalTerhapus++;
                            }
                            break;

                        case 'monitoring':
                            $row = LaporanMonitoring::find($id);
                            if ($row) {
                                DB::table('laporan_monitoring_lampirans')->where('laporan_monitoring_id', $id)->pluck('path')->filter()->each(function ($p) {
                                    Storage::disk('public')->delete($p);
                                });
                                DB::table('laporan_monitoring_lampirans')->where('laporan_monitoring_id', $id)->delete();
                                $row->delete();
                                $totalTerhapus++;
                            }
                            break;

                        case 'permintaan':
                            $row = PermintaanLaporan::find($id);
                            if ($row) {
                                DB::table('permintaan_laporan_tasks')->where('permintaan_laporan_id', $id)->delete();
                                $row->delete();
                                $totalTerhapus++;
                            }
                            break;

                        case 'surat':
                            $row = LaporanSurat::find($id);
                            if ($row) {
                                // Hapus berkas lampiran surat utama
                                if (!empty($row->lampiran_path)) {
                                    LaporanSurat::hapusBerkasLampiran($row->lampiran_path); // disk publik & privat (rahasia)
                                }

                                // Hapus berkas lampiran di setiap riwayat alur (disposisi/teruskan/balasan)
                                DB::table('laporan_surat_riwayats')
                                    ->where('laporan_surat_id', $id)
                                    ->whereNotNull('lampiran_path')
                                    ->pluck('lampiran_path')
                                    ->filter()
                                    ->each(fn ($p) => LaporanSurat::hapusBerkasLampiran($p));

                                // Bersihkan tembusan/view-only & riwayat alur di semua satuan/pengguna terkait
                                DB::table('laporan_surat_tembusans')->where('laporan_surat_id', $id)->delete();
                                DB::table('laporan_surat_riwayats')->where('laporan_surat_id', $id)->delete();

                                $row->delete();
                                $totalTerhapus++;
                            }
                            break;
                    }
                }
            });

            ActivityLog::catat(
                'reset-data-laporan',
                "Menghapus {$totalTerhapus} baris data laporan spesifik dari sistem."
            );

            return back()->with('status', "Berhasil menghapus {$totalTerhapus} baris data laporan terpilih secara permanen.");
        }

        // -------------------------------------------------------------
        // MODE B: Hapus seluruh baris dalam kategori yang dipilih
        // -------------------------------------------------------------
        $kategoriInput = $request->input('kategori');
        if (is_string($kategoriInput)) {
            $kategoriInput = [$kategoriInput];
        }

        $request->merge(['kategori' => $kategoriInput]);

        $data = $request->validate([
            'kategori' => ['required', 'array', 'min:1'],
            'kategori.*' => [Rule::in(array_keys(self::KATEGORI))],
        ], [
            'kategori.required' => 'Pilih dulu minimal satu kategori data laporan yang mau dihapus.',
        ]);

        $labelTerhapus = [];
        $totalBarisTerhapus = 0;

        DB::transaction(function () use ($data, &$labelTerhapus, &$totalBarisTerhapus) {
            foreach ($data['kategori'] as $key) {
                $def = self::KATEGORI[$key];
                $labelTerhapus[] = $def['label'];

                foreach ($def['tables'] as $t) {
                    if (!DB::getSchemaBuilder()->hasTable($t['table'])) continue;

                    // Hapus file fisik (lampiran/dokumen/bukti) sebelum barisnya dihapus
                    if (!empty($t['file_column']) && DB::getSchemaBuilder()->hasColumn($t['table'], $t['file_column'])) {
                        DB::table($t['table'])
                            ->whereNotNull($t['file_column'])
                            ->pluck($t['file_column'])
                            ->filter()
                            ->each(function ($path) {
                                Storage::disk('public')->delete($path);
                                Storage::disk(LaporanSurat::DISK_RAHASIA)->delete($path); // lampiran surat rahasia
                            });
                    }

                    $totalBarisTerhapus += DB::table($t['table'])->count();
                    DB::table($t['table'])->delete();
                }
            }
        });

        ActivityLog::catat(
            'reset-data-laporan',
            'Membersihkan data laporan: '.implode(', ', $labelTerhapus).". Total {$totalBarisTerhapus} baris dihapus."
        );

        return back()->with('status', 'Data laporan kategori terpilih berhasil dihapus bersih: '.implode(', ', $labelTerhapus).'.');
    }
}

