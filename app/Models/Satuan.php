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
        'urutan',
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
     * Kategori satuan yang tersedia, dipakai untuk pengelompokan di dropdown login.
     */
    public const KATEGORI_SATLAK = 'satlak';
    public const KATEGORI_DIREKTORAT = 'direktorat';
    public const KATEGORI_PIMPINAN = 'pimpinan';
    public const KATEGORI_ADMIN = 'admin';

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
     * - Satlak hanya boleh lapor ke SDIR (koordinasi) atau DANPUS/WADAN (tujuan utama).
     *   Satlak tidak boleh saling kirim ke sesama Satlak maupun ke satuan pembinaan.
     * - SDIR boleh koordinasi ke Satlak dan melapor ke DANPUS.
     * - Satuan pembinaan (Binmat, Binfung, Binum, Diklat) langsung lapor ke DANPUS.
     * - Satuan lain (mis. WADAN) tidak dibatasi di sini (kembalikan null).
     *
     * @return string[]|null Daftar kode satuan tujuan yang diizinkan, atau null jika tidak dibatasi.
     */
    public static function kodeTujuanUntuk(?string $kodeAsal): ?array
    {
        $kodeAsal = strtoupper((string) $kodeAsal);

        if (in_array($kodeAsal, self::KODE_SATLAK, true)) {
            return ['SDIR', 'DANPUS', 'WADAN'];
        }

        if ($kodeAsal === 'SDIR') {
            return [...self::KODE_SATLAK, 'DANPUS'];
        }

        if (in_array($kodeAsal, self::KODE_PEMBINAAN, true)) {
            return ['DANPUS'];
        }

        return null;
    }
}
