<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rebrand nama dari "BRAHMASTRA WIRA" jadi "DT-PHATRAM-2639" (bagian
     * emas/aksen sekarang "2639"). Baris `pengaturans` yang sudah ada dari
     * sebelum rebrand ini masih menyimpan JSON lama -- migration ini
     * menambal HANYA field yang masih persis nilai BRAHMASTRA WIRA lama,
     * supaya kustomisasi lain yang mungkin sudah diubah lewat Admin >
     * Pengaturan tidak ikut tertimpa (pola sama kayak
     * 2026_08_23_000002_fix_stale_brand_in_landing_content.php).
     */
    public function up(): void
    {
        DB::table('pengaturans')
            ->where('hero_judul_awal', 'BRAHMASTRA ')
            ->where('hero_judul_aksen', 'WIRA')
            ->update([
                'hero_judul_awal' => 'DT-PHATRAM-',
                'hero_judul_aksen' => '2639',
            ]);

        DB::table('pengaturans')->orderBy('id')->get(['id', 'landing_content'])->each(function ($row) {
            $decoded = json_decode($row->landing_content ?? '', true);
            if (! is_array($decoded)) {
                return;
            }

            $changed = false;

            if (($decoded['brand']['name'] ?? null) === 'BRAHMASTRA ') {
                $decoded['brand']['name'] = 'DT-PHATRAM-';
                $changed = true;
            }
            if (($decoded['brand']['accent'] ?? null) === 'WIRA') {
                $decoded['brand']['accent'] = '2639';
                $changed = true;
            }
            if (str_starts_with($decoded['meta']['title'] ?? '', 'BRAHMASTRA WIRA')) {
                $decoded['meta']['title'] = 'DT-PHATRAM-2639 — Sistem Informasi Berbasis Elektronik Angkatan Darat | PUSSIBERAD';
                $changed = true;
            }
            if (str_starts_with($decoded['meta']['description'] ?? '', 'BRAHMASTRA WIRA')) {
                $decoded['meta']['description'] = 'DT-PHATRAM-2639 — Sistem Informasi Berbasis Elektronik Angkatan Darat. Platform pelaporan dan monitoring resmi Pusat Siber Angkatan Darat (PUSSIBERAD).';
                $changed = true;
            }
            if (($decoded['footer']['copyright'] ?? null) === 'BRAHMASTRA WIRA · Pussiberad · TNI AD') {
                $decoded['footer']['copyright'] = 'DT-PHATRAM-2639 · Pussiberad · TNI AD';
                $changed = true;
            }

            if ($changed) {
                DB::table('pengaturans')->where('id', $row->id)->update([
                    'landing_content' => json_encode($decoded),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Tidak ada rollback -- ini migration penambal data, bukan skema.
    }
};
