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
        'BINFUNG' => ['laporan', 'personel', 'notifikasi'],
        'BINUM' => ['laporan', 'monitoring', 'notifikasi'],
        'DIKLAT' => ['laporan', 'notifikasi'],
        'BINMAT' => ['laporan', 'notifikasi'],
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
     * Urutan tampil kategori secara umum (Admin -> Pimpinan -> Direktorat ->
     * Satlak), dipakai di seluruh tempat yang menampilkan daftar satuan
     * gabungan lintas kategori -- menggantikan field "urutan" manual yang
     * sudah dihapus.
     */
    public static function prioritasKategori(): array
    {
        return [
            self::KATEGORI_ADMIN => 1,
            self::KATEGORI_PIMPINAN => 2,
            self::KATEGORI_DIREKTORAT => 3,
            self::KATEGORI_SATLAK => 4,
        ];
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
     * Alur tujuan laporan resmi (hierarki komando):
     * - Satlak hanya boleh lapor ke DANPUS/WADAN (tujuan utama).
     *   Satlak tidak boleh saling kirim ke sesama Satlak maupun ke satuan pembinaan.
     * - Satuan pembinaan (Binmat, Binfung, Binum, Diklat) langsung lapor ke DANPUS.
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

        if (in_array($kodeAsal, self::KODE_PEMBINAAN, true)) {
            return ['DANPUS'];
        }

        return null;
    }
}
