<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Penambal ulang untuk migration 2026_08_23_000001. Migration itu
     * membandingkan kolom JSON `sosial_media` langsung dengan literal SQL
     * ('', '[]') lewat query builder — di sebagian driver (terutama MySQL
     * dengan kolom bertipe JSON asli) perbandingan seperti ini bisa gagal
     * match walau isinya sebenarnya kosong, atau bisa saja baris yang ada
     * di server justru berisi array platform tanpa url (semua ke-filter
     * oleh blade karena `url` kosong). Migration ini mendecode tiap baris
     * di level PHP dan menambal kalau memang tidak ada satu pun entri yang
     * punya url terisi, apapun bentuk mentah datanya di database.
     */
    public function up(): void
    {
        $defaults = [
            ['platform' => 'instagram', 'label' => 'Instagram @pussiberad', 'url' => 'https://www.instagram.com/pussiberad?igsh=MTA1N2tuMHRobzE5OQ=='],
            ['platform' => 'tiktok', 'label' => 'TikTok @pusat.siber_ad', 'url' => 'https://www.tiktok.com/@pusat.siber_ad?_r=1&_t=ZS-98XYV7h9dfs'],
            ['platform' => 'youtube', 'label' => 'YouTube TNI Angkatan Darat', 'url' => 'https://www.youtube.com/@tniangkatandarat'],
            ['platform' => 'x', 'label' => 'X (Twitter) @tni_ad', 'url' => 'https://x.com/tni_ad'],
            ['platform' => 'facebook', 'label' => 'Facebook TNI Angkatan Darat', 'url' => 'https://web.facebook.com/TNIAngkatanDarat'],
            ['platform' => 'wikipedia', 'label' => 'Profil Resmi', 'url' => 'https://id.wikipedia.org/wiki/Pusat_Sandi_dan_Siber_Angkatan_Darat'],
        ];

        DB::table('pengaturans')->orderBy('id')->get(['id', 'sosial_media'])->each(function ($row) use ($defaults) {
            $decoded = json_decode($row->sosial_media ?? '', true);

            $hasAnyUrl = is_array($decoded) && collect($decoded)->contains(fn ($item) => ! empty($item['url'] ?? null));

            if (! $hasAnyUrl) {
                DB::table('pengaturans')->where('id', $row->id)->update([
                    'sosial_media' => json_encode($defaults),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Tidak ada rollback — ini migration penambal data, bukan skema.
    }
};
