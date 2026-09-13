<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom geo_* menyimpan hasil reverse-geocode IP saat login
 * (via ip-api.com — gratis, tanpa API key, hanya HTTP/HTTPS).
 *
 * Disimpan di sessions (bukan di-lookup tiap request) karena:
 *  1. IP pengguna tidak berubah selama satu sesi aktif.
 *  2. Menghindari hit API berulang setiap poll realtime 4 detik.
 *  3. Kolom nullable — kalau lookup gagal (timeout, IP private/loopback,
 *     atau server tidak bisa keluar ke internet), baris sesi tetap dibuat
 *     dan kolom ini kosong; UI menampilkan "–" alih-alih error.
 *
 * geo_lat & geo_lon: koordinat desimal dari ip-api.com, dipakai untuk
 * membuka pin Google Maps langsung (`?q=lat,lon`) alih-alih search nama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('geo_kota', 100)->nullable()->after('login_at');
            $table->string('geo_region', 100)->nullable()->after('geo_kota');
            $table->string('geo_negara', 100)->nullable()->after('geo_region');
            $table->string('geo_isp', 150)->nullable()->after('geo_negara');
            $table->decimal('geo_lat', 10, 6)->nullable()->after('geo_isp');
            $table->decimal('geo_lon', 10, 6)->nullable()->after('geo_lat');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['geo_kota', 'geo_region', 'geo_negara', 'geo_isp', 'geo_lat', 'geo_lon']);
        });
    }
};
