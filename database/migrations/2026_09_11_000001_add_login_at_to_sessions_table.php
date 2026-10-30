<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom `login_at` dipakai supaya "Terakhir Aktif" di menu admin
 * Pengguna Aktif dihitung MURNI dari waktu login (bukan dari
 * `last_activity` bawaan Laravel yang ke-update ulang setiap kali
 * sesi menyentuh apapun -- request, klik menu, dsb -- sehingga
 * sebelumnya selalu terlihat "0 detik yang lalu").
 *
 * Dengan `login_at`, durasi yang ditampilkan terus bertambah selama
 * pengguna login (2 jam login tanpa aktivitas tetap tampil "2 jam
 * yang lalu"), dan baris otomatis hilang begitu sesi berakhir
 * (logout / paksa logout menghapus baris session-nya).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->timestamp('login_at')->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('login_at');
        });
    }
};
