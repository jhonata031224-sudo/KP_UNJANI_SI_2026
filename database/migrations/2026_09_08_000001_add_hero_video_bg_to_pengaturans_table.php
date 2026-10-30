<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fitur "Latar Belakang Video Beranda": Admin bisa memilih latar hero
     * pakai VIDEO (bukan cuma gambar statis) lewat Pengaturan Umum -> tab
     * Beranda, sistemnya sama seperti unggah gambar yang sudah ada.
     *
     * - hero_bg_type: penentu latar mana yang dipakai ('gambar' / 'video').
     *   Kolom gambar (hero_image_path) SENGAJA tetap dipertahankan terpisah
     *   dari video (bukan saling menimpa) supaya Admin bisa bolak-balik
     *   ganti tipe tanpa harus upload ulang gambar/videonya setiap kali.
     * - hero_video_path: path file video tersimpan di disk 'public', sama
     *   persis mekanismenya dengan hero_image_path/logo_path.
     */
    public function up(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->string('hero_bg_type')->default('gambar')->after('hero_image_path');
            $table->string('hero_video_path')->nullable()->after('hero_bg_type');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->dropColumn(['hero_bg_type', 'hero_video_path']);
        });
    }
};
