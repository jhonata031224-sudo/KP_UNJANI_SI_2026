<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom `geo_sumber` menandai asal data geo_* di baris sesi ini:
 *
 *  - 'gps'   : dari GPS perangkat (navigator.geolocation), dikirim
 *              browser lewat hidden input saat submit form login.
 *              Presisinya jauh lebih akurat dari geo-IP (biasanya
 *              hanya benar sampai level kota/ISP).
 *  - 'ip'    : fallback reverse-geo dari IP klien (ip-api.com) --
 *              dipakai kalau GPS tidak tersedia (izin ditolak,
 *              browser tidak mendukung, atau timeout 6 detik).
 *  - 'lokal' : IP private/loopback (jaringan LAN instansi), tidak
 *              ada koordinat sama sekali.
 *  - null    : sesi lama sebelum kolom ini ada, atau kedua sumber
 *              gagal.
 *
 * Dipakai di UI "Pengguna Aktif" supaya admin tahu seberapa bisa
 * dipercaya titik lokasi yang ditampilkan (badge GPS vs IP).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('geo_sumber', 10)->nullable()->after('geo_lon');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('geo_sumber');
        });
    }
};
