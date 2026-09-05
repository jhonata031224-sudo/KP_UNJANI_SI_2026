<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            // hero_blur_level: kekuatan efek blur (buram) langsung di FOTO latar
            // hero, dalam satuan px (0 = tidak buram sama sekali).
            $table->unsignedTinyInteger('hero_blur_level')->default(0)->after('hero_image_path');

            // hero_overlay_intensity: kepekatan lapisan gradient gelap/terang di
            // ATAS foto (bukan foto-nya), dalam persen 0-100. 100 = kepekatan
            // penuh seperti sebelum fitur ini ada (tidak mengubah tampilan
            // instalasi lama secara default).
            $table->unsignedTinyInteger('hero_overlay_intensity')->default(100)->after('hero_blur_level');
        });

        DB::table('pengaturans')->update([
            'hero_blur_level' => 0,
            'hero_overlay_intensity' => 100,
        ]);
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->dropColumn(['hero_blur_level', 'hero_overlay_intensity']);
        });
    }
};
