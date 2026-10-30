<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `landing-content.js` di sisi browser menimpa ulang teks brand/hero
     * caption/stats/footer copyright di halaman landing berdasarkan JSON
     * di kolom `landing_content`. Baris `pengaturans` yang sudah ada dari
     * sebelum rebrand ke BRAHMASTRA WIRA masih menyimpan JSON lama
     * (SIBER/AD, "Satria Yudha Waskita", stat 04/01, dst). Migration ini
     * menambal HANYA field yang masih persis nilai lama, supaya kustomisasi
     * lain yang mungkin sudah diubah lewat Admin > Pengaturan tidak ikut
     * tertimpa.
     */
    public function up(): void
    {
        DB::table('pengaturans')
            ->where('hero_judul_awal', 'SIBER')
            ->where('hero_judul_aksen', 'AD')
            ->update([
                'hero_judul_awal' => 'BRAHMASTRA ',
                'hero_judul_aksen' => 'WIRA',
            ]);

        DB::table('pengaturans')->orderBy('id')->get(['id', 'landing_content'])->each(function ($row) {
            $decoded = json_decode($row->landing_content ?? '', true);
            if (! is_array($decoded)) {
                return;
            }

            $changed = false;

            if (($decoded['brand']['name'] ?? null) === 'SIBER') {
                $decoded['brand']['name'] = 'BRAHMASTRA ';
                $changed = true;
            }
            if (($decoded['brand']['accent'] ?? null) === 'AD') {
                $decoded['brand']['accent'] = 'WIRA';
                $changed = true;
            }
            if (str_starts_with($decoded['meta']['title'] ?? '', 'SIBERAD')) {
                $decoded['meta']['title'] = 'BRAHMASTRA WIRA — Sistem Informasi Berbasis Elektronik Angkatan Darat | PUSSIBERAD';
                $changed = true;
            }
            if (str_starts_with($decoded['meta']['description'] ?? '', 'SIBERAD')) {
                $decoded['meta']['description'] = 'BRAHMASTRA WIRA — Sistem Informasi Berbasis Elektronik Angkatan Darat. Platform pelaporan dan monitoring resmi Pusat Siber Angkatan Darat (PUSSIBERAD).';
                $changed = true;
            }
            if (($decoded['hero']['crest_motto'] ?? null) === 'Satria Yudha Waskita') {
                $decoded['hero']['crest_motto'] = '';
                $changed = true;
            }
            if (isset($decoded['stats'][0]) && ($decoded['stats'][0]['number'] ?? null) === '04' && ($decoded['stats'][0]['label'] ?? null) === 'Satuan Pelaksana Terpantau') {
                $decoded['stats'][0]['number'] = '12';
                $decoded['stats'][0]['label'] = 'Pengguna Terpantau';
                $changed = true;
            }
            if (isset($decoded['stats'][3]) && ($decoded['stats'][3]['number'] ?? null) === '01') {
                $decoded['stats'][3]['number'] = '1';
                $changed = true;
            }
            if (($decoded['footer']['copyright'] ?? null) === 'SIBERAD · Pussiberad · TNI AD') {
                $decoded['footer']['copyright'] = 'BRAHMASTRA WIRA · Pussiberad · TNI AD';
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
        // Tidak ada rollback — ini migration penambal data, bukan skema.
    }
};
