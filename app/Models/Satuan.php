<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Satuan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'kategori',
        'deskripsi',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Bagian nama sebelum tanda kurung, mis. "Sdir Binfung" dari
     * "Sdir Binfung (Pembinaan Fungsi)". Jika tidak ada kurung,
     * kembalikan nama lengkap.
     */
    public function getNamaSingkatAttribute(): string
    {
        $pos = strpos($this->nama, '(');
        return $pos !== false ? rtrim(substr($this->nama, 0, $pos)) : $this->nama;
    }

    /**
     * Bagian nama di dalam tanda kurung, mis. "Pembinaan Fungsi" dari
     * "Sdir Binfung (Pembinaan Fungsi)". Jika tidak ada kurung,
     * kembalikan nama lengkap.
     */
    public function getNamaKeteranganAttribute(): string
    {
        preg_match('/\(([^)]+)\)/', $this->nama, $m);
        return $m[1] ?? $this->nama;
    }

    /**
     * Modul dashboard yang bisa diatur hak aksesnya lewat "Manajemen Role &
     * Hak Akses" (Admin). Kunci dipakai sebagai nilai checkbox, label untuk
     * ditampilkan di UI.
     */
    public const MODUL_HAK_AKSES = [
        'laporan' => 'Kirim & Kelola Laporan',
        'medsos' => 'Pelaporan Publikasi',
        'personel' => 'Pelaporan Administrasi Personel',
        'monitoring' => 'Monitoring Laporan & Aktivitas',
        'notifikasi' => 'Notifikasi',
    ];

    /**
     * Penjelasan singkat per modul, dipakai di halaman Admin > Hak Akses
     * Pengguna supaya jelas menu apa yang muncul/hilang saat modul itu
     * dicentang, tanpa perlu buka-tutup dashboard satuan yang bersangkutan
     * untuk mengecek sendiri.
     */
    public const MODUL_HAK_AKSES_DESKRIPSI = [
        'laporan' => 'Bisa membuat & mengirim laporan kegiatan ke satuan lain, lalu memantau statusnya (menunggu, disetujui, revisi, atau ditolak).',
        'medsos' => 'Bisa mengelola akun media sosial resmi satuan dan membuat/melaporkan postingan publikasi (mis. Instagram).',
        'personel' => 'Bisa mengelola data personel: penempatan, mutasi/pindah satuan, dan dokumen administrasi personel.',
        'monitoring' => 'Bisa memantau (lihat saja, tanpa mengelola) laporan dan aktivitas dari satuan-satuan lain.',
        'notifikasi' => 'Menerima notifikasi lonceng otomatis di dashboard saat ada laporan baru, permintaan laporan, atau aktivitas penting lainnya.',
    ];

    /**
     * Matriks modul yang relevan dengan tanggung jawab tiap role/satuan.
     * Ini dipakai Admin saat mengatur Role & Hak Akses agar pilihan modul
     * tidak lagi sama untuk seluruh role.
     */
    public const MODUL_HAK_AKSES_PER_ROLE = [
        'ADMIN' => ['laporan', 'medsos', 'personel', 'monitoring', 'notifikasi'],
        'DANPUS' => ['laporan', 'medsos', 'monitoring', 'notifikasi'],
        'WADAN' => ['laporan', 'medsos', 'monitoring', 'notifikasi'],
        'SATLAKKAL' => ['laporan', 'monitoring', 'notifikasi'],
        'SATLAKSISOS' => ['laporan', 'medsos', 'notifikasi'],
        'SATLAKDAK' => ['laporan', 'monitoring', 'notifikasi'],
        'SATLAKDUKTEK' => ['laporan', 'monitoring', 'notifikasi'],
        'BINFUNG' => ['laporan', 'personel', 'monitoring', 'notifikasi'],
        'BINUM' => ['laporan', 'monitoring', 'notifikasi'],
        'DIKLAT' => ['laporan', 'monitoring', 'notifikasi'],
        'BINMAT' => ['laporan', 'monitoring', 'notifikasi'],
        'POKANALIS' => ['laporan', 'monitoring', 'notifikasi'],
        'URDAL' => ['laporan', 'monitoring', 'notifikasi'],
    ];

    public static function modulHakAksesUntukRole(?string $kode): array
    {
        $kode = strtoupper(trim((string) $kode));
        $keys = self::MODUL_HAK_AKSES_PER_ROLE[$kode] ?? array_keys(self::MODUL_HAK_AKSES);

        return array_intersect_key(self::MODUL_HAK_AKSES, array_flip($keys));
    }

    public static function modulHakAksesKeysUntukRole(?string $kode): array
    {
        return array_keys(self::modulHakAksesUntukRole($kode));
    }

    /**
     * Kategori satuan yang tersedia, dipakai untuk pengelompokan di dropdown login.
     */
    public const KATEGORI_SATLAK = 'satlak';
    public const KATEGORI_DIREKTORAT = 'direktorat';
    public const KATEGORI_PIMPINAN = 'pimpinan';
    public const KATEGORI_ADMIN = 'admin';
    /**
     * Kelompok Pelayan (Pok Pel) -- satuan yang langsung di bawah/melayani
     * Danpus, tidak masuk kelompok Direktorat (Sdir/Binfung/Binum/dst),
     * Satlak, maupun Pimpinan (mis. Pok Analis, Urdal). Lapor langsung ke
     * DANPUS (lihat kodeTujuanUntuk()).
     */
    public const KATEGORI_POKPEL = 'pokpel';

    /**
     * Urutan tampil kategori secara umum (Admin -> Pimpinan -> Pok Pel ->
     * Direktorat -> Satlak), dipakai di seluruh tempat yang menampilkan
     * daftar satuan gabungan lintas kategori -- menggantikan field "urutan"
     * manual yang sudah dihapus.
     *
     * Pok Pel (Pok Analis, Urdal) sengaja ditaruh setelah Pimpinan (Wadan)
     * dan sebelum Direktorat (4 Sdir) sesuai urutan organisasi yang diminta.
     */
    public static function prioritasKategori(): array
    {
        return [
            self::KATEGORI_ADMIN => 1,
            self::KATEGORI_PIMPINAN => 2,
            self::KATEGORI_POKPEL => 3,
            self::KATEGORI_DIREKTORAT => 4,
            self::KATEGORI_SATLAK => 5,
        ];
    }

    /**
     * Urutan tampil satuan di DALAM satu kategori yang sama (dipakai sebagai
     * tie-breaker setelah prioritasKategori()), berdasarkan kode -- BUKAN
     * alfabet nama. Dulu tie-breaker-nya alfabet nama, tapi begitu nama
     * satuan Direktorat/Satlak diubah pakai prefix "Sdir"/"Satlak" hasil
     * alfabetnya jadi acak dan tidak sesuai urutan organisasi yang diminta.
     * Kode dipilih sebagai kunci karena kode tidak pernah berubah walau
     * nama tampilnya diubah-ubah.
     */
    public static function urutanDalamKategori(): array
    {
        return [
            'ADMIN' => 1,
            'DANPUS' => 1,
            'WADAN' => 2,
            'BINFUNG' => 1,
            'BINUM' => 2,
            'DIKLAT' => 3,
            'BINMAT' => 4,
            'URDAL' => 1,
            'POKANALIS' => 2,
            'SATLAKKAL' => 1,
            'SATLAKDAK' => 2,
            'SATLAKSISOS' => 3,
            'SATLAKDUKTEK' => 4,
        ];
    }

    /**
     * Kunci urut gabungan (kategori lalu urutan-dalam-kategori) yang dipakai
     * di semua tempat yang menampilkan daftar satuan/pengguna lintas
     * kategori, supaya urutannya konsisten di seluruh sistem.
     */
    public static function kunciUrutSatuan(?string $kategori, ?string $kode): string
    {
        $prioritasKategori = self::prioritasKategori()[$kategori ?? ''] ?? 9;
        $urutanDalamKategori = self::urutanDalamKategori()[strtoupper($kode ?? '')] ?? 99;

        return sprintf('%d-%03d', $prioritasKategori, $urutanDalamKategori);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Laporan yang dikirim oleh satuan ini (mis. laporan dari Satuan Pelaksanaan Dukungan Teknologi).
     */
    public function laporanTerkirim(): HasMany
    {
        return $this->hasMany(Laporan::class, 'satuan_id');
    }

    /**
     * Permintaan laporan yang ditujukan ke satuan ini (dari Danpus/Wadan).
     */
    public function permintaanLaporanMasuk(): HasMany
    {
        return $this->hasMany(PermintaanLaporan::class, 'tujuan_satuan_id');
    }

    /**
     * Laporan yang ditujukan ke satuan ini (mis. laporan masuk ke DANPUS).
     */
    public function laporanDiterima(): HasMany
    {
        return $this->hasMany(Laporan::class, 'tujuan_satuan_id');
    }

    /**
     * Akun media sosial resmi yang dikelola satuan ini (mis. akun Instagram
     * resmi Satuan Pelaksanaan Siber Sosial) — dipakai fitur manajemen & posting konten.
     */
    public function akunMedsos(): HasMany
    {
        return $this->hasMany(AkunMedsos::class);
    }

    /**
     * Seluruh postingan media sosial yang dibuat oleh satuan ini.
     */
    public function postingan(): HasMany
    {
        return $this->hasMany(Postingan::class);
    }

    /**
     * Personel yang saat ini ditempatkan di satuan ini — dipakai fitur
     * Administrasi Personel (Binfung).
     */
    public function personels(): HasMany
    {
        return $this->hasMany(Personel::class);
    }

    /**
     * Kode 4 Satlak operasional (Penangkalan, Siber Sosial, Penindakan, Duktek).
     */
    public const KODE_SATLAK = ['SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK', 'SATLAKDUKTEK'];

    /**
     * Kode satuan pembinaan/direktorat (Binfung, Binum, Diklat, Binmat).
     */
    public const KODE_PEMBINAAN = ['BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT'];

    /**
     * Kode satuan Pok Pel (Kelompok Pelayan) -- langsung di bawah/melayani
     * Danpus, bukan bagian Direktorat/Satlak/Pimpinan (Pok Analis, Urdal).
     */
    public const KODE_POKPEL = ['POKANALIS', 'URDAL'];

    /**
     * Alur tujuan laporan resmi (hierarki komando):
     * - Satlak hanya boleh lapor ke DANPUS/WADAN (tujuan utama).
     *   Satlak tidak boleh saling kirim ke sesama Satlak maupun ke satuan pembinaan.
     * - Satuan pembinaan (Binmat, Binfung, Binum, Diklat) langsung lapor ke DANPUS.
     * - Satuan Pok Pel (Pok Analis, Urdal) juga langsung lapor ke DANPUS.
     * - Satuan lain (mis. WADAN) tidak dibatasi di sini (kembalikan null).
     *
     * @return string[]|null Daftar kode satuan tujuan yang diizinkan, atau null jika tidak dibatasi.
     */
    public static function kodeTujuanUntuk(?string $kodeAsal): ?array
    {
        $kodeAsal = strtoupper((string) $kodeAsal);

        if (in_array($kodeAsal, self::KODE_SATLAK, true)) {
            return ['DANPUS', 'WADAN'];
        }

        if (in_array($kodeAsal, self::KODE_PEMBINAAN, true) || in_array($kodeAsal, self::KODE_POKPEL, true)) {
            return ['DANPUS'];
        }

        return null;
    }
}
