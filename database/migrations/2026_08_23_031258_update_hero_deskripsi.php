<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ganti hero_deskripsi lama jadi copy baru yang lebih ringkas.
     */
    public function up(): void
    {
        DB::table('pengaturans')
            ->where('hero_deskripsi', 'Mendigitalisasi alur pelaporan kegiatan seluruh Satuan Pelaksana Pusat Siber Angkatan Darat — dari input laporan di lapangan, verifikasi berjenjang, hingga visualisasi real-time bagi pengambil keputusan.')
            ->update([
                'hero_deskripsi' => 'Sistem pendukung operasional Satuan Siber AD dan jajaran Pusat Siber TNI Angkatan Darat — mulai dari input laporan, hingga visualisasi real-time bagi pengambil keputusan.',
            ]);
    }

    public function down(): void
    {
        // Tidak ada rollback — ini migration penambal data, bukan skema.
    }
};
