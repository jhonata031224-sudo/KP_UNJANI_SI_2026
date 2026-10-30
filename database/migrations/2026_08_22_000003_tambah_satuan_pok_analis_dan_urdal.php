<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah 2 satuan baru yang berdiri sendiri (bukan Direktorat/Satlak/
     * Pimpinan): Pok Analis & Urdal. Kategori "mandiri" dipakai apa adanya
     * di sini (bukan lewat konstanta Satuan) supaya migrasi historis ini
     * tetap akurat -- kategorinya kemudian di-rename jadi "pokpel" (Pok
     * Pel/Kelompok Pelayan) lewat migrasi
     * 2026_08_22_000004_rename_kategori_mandiri_ke_pokpel. Format nama
     * mengikuti pola satuan lain: "<Nama Singkat> (<Keterangan>)".
     *
     * Akun pengguna untuk kedua satuan ini SENGAJA tidak dibuat di sini --
     * akan ditambahkan manual lewat menu Admin > Daftar Pengguna setelah
     * satuannya tersedia di dropdown.
     */
    public function up(): void
    {
        $satuans = [
            ['kode' => 'POKANALIS', 'nama' => 'Pok Analis (Kelompok Analis)', 'kategori' => 'mandiri', 'deskripsi' => 'Satuan analisis yang berdiri sendiri, lapor langsung ke Danpus.'],
            ['kode' => 'URDAL', 'nama' => 'Urdal (Urusan Dalam)', 'kategori' => 'mandiri', 'deskripsi' => 'Satuan urusan dalam yang berdiri sendiri, lapor langsung ke Danpus.'],
        ];

        foreach ($satuans as $data) {
            DB::table('satuans')->updateOrInsert(
                ['kode' => $data['kode']],
                $data + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('satuans')->whereIn('kode', ['POKANALIS', 'URDAL'])->delete();
    }
};
