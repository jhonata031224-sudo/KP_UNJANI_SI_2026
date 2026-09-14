<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sama seperti 2026_09_14_000001_force_notifikasi_push_aktif_true.php
 * (saklar global admin), tapi untuk saklar PER-USER: toggle "Notifikasi
 * Push" di menu Lainnya -> Notifikasi (role Kasansi) sekarang juga wajib
 * selalu aktif, tidak boleh dimatikan siapapun -- lihat
 * UserNotifikasiController::toggle yang sudah dikeraskan jadi jaring
 * pengaman, dan lainnya-kasansi.blade.php yang toggle-nya sudah diganti
 * jadi status baca-saja "Selalu Aktif".
 *
 * Migrasi ini jaga-jaga: kalau ada user yang kebetulan sudah pernah
 * mematikan notif_push_enabled sebelum perubahan ini, kolomnya dipaksa
 * balik ke true sekali di sini supaya datanya konsisten dengan aturan
 * baru sejak awal.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->update(['notif_push_enabled' => true]);
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan ke false -- fitur ini memang tidak lagi
        // boleh nonaktif per-user, jadi migrasi ini tidak reversibel secara
        // bermakna.
    }
};
