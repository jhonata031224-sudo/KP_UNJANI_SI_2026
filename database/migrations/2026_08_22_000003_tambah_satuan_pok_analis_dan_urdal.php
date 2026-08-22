<?php

use App\Models\Satuan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah 2 satuan baru yang berdiri sendiri (bukan Direktorat/Satlak/
     * Pimpinan): Pok Analis & Urdal. Kategori "mandiri" -- lihat
     * Satuan::KATEGORI_MANDIRI. Format nama mengikuti pola satuan lain:
     * "<Nama Singkat> (<Keterangan>)".
     *
     * Akun pengguna untuk kedua satuan ini SENGAJA tidak dibuat di sini --
     * akan ditambahkan manual lewat menu Admin > Daftar Pengguna setelah
     * satuannya tersedia di dropdown.
     */
    public function up(): void
    {
        $satuans = [
            ['kode' => 'POKANALIS', 'nama' => 'Pok Analis (Kelompok Analis)', 'kategori' => Satuan::KATEGORI_MANDIRI, 'deskripsi' => 'Satuan analisis yang berdiri sendiri, lapor langsung ke Danpus.'],
            ['kode' => 'URDAL', 'nama' => 'Urdal (Urusan Dalam)', 'kategori' => Satuan::KATEGORI_MANDIRI, 'deskripsi' => 'Satuan urusan dalam yang berdiri sendiri, lapor langsung ke Danpus.'],
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
