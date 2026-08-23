<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migration 2026_08_07_000014 hanya menambal `sosial_media` untuk baris
     * yang `hero_judul_awal`-nya NULL. Kalau baris pengaturan sudah pernah
     * dibuat lebih dulu lewat Pengaturan::current() (hero_judul_awal sudah
     * terisi default), migration itu ke-skip dan `sosial_media` tetap
     * kosong ('[]' atau NULL). Migration ini menambal khusus itu, tanpa
     * syarat hero_judul_awal, supaya section "Terhubung" di footer landing
     * page tidak kosong.
     */
    public function up(): void
    {
        DB::table('pengaturans')
            ->where(function ($query) {
                $query->whereNull('sosial_media')
                    ->orWhere('sosial_media', '')
                    ->orWhere('sosial_media', '[]');
            })
            ->update([
                'sosial_media' => json_encode([
                    ['platform' => 'instagram', 'label' => 'Instagram @pussiberad', 'url' => 'https://www.instagram.com/pussiberad?igsh=MTA1N2tuMHRobzE5OQ=='],
                    ['platform' => 'tiktok', 'label' => 'TikTok @pusat.siber_ad', 'url' => 'https://www.tiktok.com/@pusat.siber_ad?_r=1&_t=ZS-98XYV7h9dfs'],
                    ['platform' => 'youtube', 'label' => 'YouTube TNI Angkatan Darat', 'url' => 'https://www.youtube.com/@tniangkatandarat'],
                    ['platform' => 'x', 'label' => 'X (Twitter) @tni_ad', 'url' => 'https://x.com/tni_ad'],
                    ['platform' => 'facebook', 'label' => 'Facebook TNI Angkatan Darat', 'url' => 'https://web.facebook.com/TNIAngkatanDarat'],
                    ['platform' => 'wikipedia', 'label' => 'Profil Resmi', 'url' => 'https://id.wikipedia.org/wiki/Pusat_Sandi_dan_Siber_Angkatan_Darat'],
                ]),
            ]);
    }

    public function down(): void
    {
        // Tidak ada rollback — ini migration penambal data, bukan skema.
    }
};
