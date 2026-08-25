<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pecah kategori "pokpel" (Pok Pel/Kelompok Pelayan, isinya Urdal & Pok
     * Analis digabung) jadi 2 kategori terpisah sesuai jenjang organisasi
     * yang benar:
     * - Urdal        -> Unsur Pelayanan (Satuan::KATEGORI_UNSUR_PELAYANAN)
     * - Pok Analis   -> Unsur Pembantu Pimpinan (Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN)
     *
     * Menambal data yang sudah kadung ke-seed dengan kategori "pokpel" lama
     * -- instalasi baru otomatis dapat kategori yang benar langsung dari
     * SatuanSeeder yang sudah diubah. Urutan tampilnya di semua tabel admin
     * tidak berubah (tetap setelah Wadan, sebelum 4 Sdir), lihat
     * Satuan::prioritasKategori().
     */
    public function up(): void
    {
        DB::table('satuans')->where('kode', 'URDAL')->update(['kategori' => 'unsur_pelayanan']);
        DB::table('satuans')->where('kode', 'POKANALIS')->update(['kategori' => 'unsur_pembantu_pimpinan']);

        // Jaga-jaga kalau ada satuan lain yang masih kepasang kategori
        // "pokpel" lama di luar Urdal/Pok Analis -- tarik ke Unsur
        // Pelayanan sebagai default aman, supaya tidak ada satuan yang
        // "hilang" dari filter kategori manapun.
        DB::table('satuans')->where('kategori', 'pokpel')->update(['kategori' => 'unsur_pelayanan']);
    }

    public function down(): void
    {
        DB::table('satuans')->whereIn('kode', ['URDAL', 'POKANALIS'])->update(['kategori' => 'pokpel']);
        DB::table('satuans')->whereIn('kategori', ['unsur_pelayanan', 'unsur_pembantu_pimpinan'])->update(['kategori' => 'pokpel']);
    }
};
