<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbarui deskripsi section "Keunggulan" pada landing page (kolom JSON
     * landing_content->features_section->description) agar lebih sederhana,
     * tidak overclaim, dan tidak menyebut "input di lapangan".
     */
    public function up(): void
    {
        $rows = DB::table('pengaturans')->whereNotNull('landing_content')->get(['id', 'landing_content']);

        foreach ($rows as $row) {
            $content = json_decode($row->landing_content, true);

            if (! is_array($content)) {
                continue;
            }

            if (
                isset($content['features_section']['description'])
                && $content['features_section']['description'] === 'Dirancang untuk menyederhanakan alur pelaporan harian, dari input di lapangan hingga pengambilan keputusan.'
            ) {
                $content['features_section']['description'] = 'Membantu proses pelaporan dan persetujuan agar lebih tertata dan mudah dipantau.';

                DB::table('pengaturans')
                    ->where('id', $row->id)
                    ->update(['landing_content' => json_encode($content)]);
            }
        }
    }

    public function down(): void
    {
        // Tidak ada rollback — ini migration penambal data, bukan skema.
    }
};
