<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ganti label stat "12" dari "Pengguna Terpantau" jadi
     * "Akun Terdaftar" di landing_content JSON baris lama.
     */
    public function up(): void
    {
        DB::table('pengaturans')->orderBy('id')->get(['id', 'landing_content'])->each(function ($row) {
            $decoded = json_decode($row->landing_content ?? '', true);
            if (! is_array($decoded) || ! isset($decoded['stats']) || ! is_array($decoded['stats'])) {
                return;
            }

            $changed = false;
            foreach ($decoded['stats'] as $i => $stat) {
                if (($stat['number'] ?? null) === '12' && ($stat['label'] ?? null) === 'Pengguna Terpantau') {
                    $decoded['stats'][$i]['label'] = 'Akun Terdaftar';
                    $changed = true;
                }
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
