<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fitur push notifikasi sekarang WAJIB selalu aktif (lihat
 * NotifikasiSettingController@updateToggle & panel Saklar Global di
 * admin.blade.php -- switch-nya sudah dikunci/disabled di UI). Migrasi ini
 * cuma jaga-jaga: kalau sebelum perubahan ini ada instalasi yang baris
 * pengaturan-nya kebetulan sudah pernah dimatikan (notifikasi_push_aktif =
 * false), baris itu dipaksa balik ke true sekali di sini supaya datanya
 * konsisten dengan aturan baru sejak awal, bukan menunggu admin membuka
 * halaman Setelan dulu.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('pengaturans')->update(['notifikasi_push_aktif' => true]);
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan ke false -- fitur ini memang tidak lagi
        // boleh nonaktif, jadi migrasi ini tidak reversibel secara bermakna.
    }
};
