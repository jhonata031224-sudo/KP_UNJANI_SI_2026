<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbaiki deskripsi satuan Kotama (21 satuan yang di-seed lewat
     * KotamaSeeder) yang masih menyimpan format lama "Satuan Kotama
     * {nama}." -- kata "Kotama" di sini jadi kata dobel/aneh setelah nama
     * satuannya direbrand jadi "Sansidam" (mis. "Satuan Kotama Sansidam
     * Iskandar Muda." atau, untuk data yang belum sempat ke-refresh sama
     * sekali, masih "Satuan Kotama Kodam Iskandar Muda." yang lama).
     *
     * Deskripsi di-generate ULANG dari nilai `nama` satuan yang sekarang
     * (sudah pasti benar "Sansidam ..." lewat migration
     * 2026_08_25_000001_rebrand_kodam_jadi_sansidam), bukan cuma
     * str_replace teks lama -- supaya hasilnya konsisten apa pun kondisi
     * deskripsi sebelumnya. Format baru: "Satuan {nama}." (tanpa kata
     * "Kotama"), sama seperti template baru di KotamaSeeder.
     */
    public function up(): void
    {
        DB::table('satuans')
            ->where('kategori', 'kotama')
            ->orderBy('id')
            ->get(['id', 'nama'])
            ->each(function ($row) {
                DB::table('satuans')->where('id', $row->id)->update([
                    'deskripsi' => 'Satuan '.$row->nama.'.',
                ]);
            });
    }

    public function down(): void
    {
        DB::table('satuans')
            ->where('kategori', 'kotama')
            ->orderBy('id')
            ->get(['id', 'nama'])
            ->each(function ($row) {
                DB::table('satuans')->where('id', $row->id)->update([
                    'deskripsi' => 'Satuan Kotama '.$row->nama.'.',
                ]);
            });
    }
};
