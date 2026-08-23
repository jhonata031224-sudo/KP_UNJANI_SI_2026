<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Hapus tanda "—" di footer description landing page yang mungkin
     * masih tersimpan di landing_content JSON dari sebelum perubahan ini.
     */
    public function up(): void
    {
        $old = 'Sistem Informasi Berbasis Elektronik Angkatan Darat — mendigitalisasi alur pelaporan seluruh Satuan Pelaksana Pussiberad.';
        $new = 'Sistem Informasi Berbasis Elektronik Angkatan Darat, mendigitalisasi alur pelaporan seluruh Satuan Pelaksana Pussiberad.';

        DB::table('pengaturans')->orderBy('id')->get(['id', 'landing_content'])->each(function ($row) use ($old, $new) {
            $decoded = json_decode($row->landing_content ?? '', true);
            if (! is_array($decoded)) {
                return;
            }

            if (($decoded['footer']['description'] ?? null) === $old) {
                $decoded['footer']['description'] = $new;
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
