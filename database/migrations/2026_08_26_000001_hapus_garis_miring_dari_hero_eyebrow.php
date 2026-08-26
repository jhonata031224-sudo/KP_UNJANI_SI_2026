<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Landing page sekarang sudah tidak lagi memakai "//" sebagai pemisah
     * di badge eyebrow hero ("PUSSIBERAD // SISTEM PENDUKUNG OPERASIONAL"),
     * tapi kolom `hero_eyebrow` di tabel `pengaturans` belum pernah ditambal
     * oleh migration rebrand sebelumnya (2026_08_23_000002 dan
     * 2026_08_24_132827 cuma nyentuh hero_judul_awal/aksen & landing_content,
     * bukan hero_eyebrow). Migration ini menambal HANYA baris yang masih
     * persis nilai lama, supaya kustomisasi lain yang mungkin sudah diubah
     * lewat Admin > Pengaturan Umum tidak ikut tertimpa.
     */
    public function up(): void
    {
        DB::table('pengaturans')
            ->where('hero_eyebrow', 'PUSSIBERAD // SISTEM PENDUKUNG OPERASIONAL')
            ->update([
                'hero_eyebrow' => 'PUSSIBERAD SISTEM PENDUKUNG OPERASIONAL',
            ]);
    }

    public function down(): void
    {
        // Tidak ada rollback -- ini migration penambal data, bukan skema.
    }
};
