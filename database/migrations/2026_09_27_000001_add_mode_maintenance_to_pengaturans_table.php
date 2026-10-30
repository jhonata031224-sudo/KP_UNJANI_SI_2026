<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Saklar global Mode Maintenance (menu Admin -> Kelola Sistem -> Mode
// Maintenance). Saat aktif, EnforceMaintenanceMode (middleware) menolak
// SEMUA request perubahan data (POST/PUT/PATCH/DELETE) dari pengguna
// non-Admin di server -- bukan cuma menyembunyikan/mendisable tombol di
// frontend. Admin selalu tetap bisa bekerja normal (lihat pengecualian
// role ADMIN di middleware tsb, sama seperti pola Satuan::modulAktif()).
// Pesan kustom bersifat opsional; kalau kosong, middleware & banner
// pakai pesan default.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->boolean('mode_maintenance_aktif')->default(false)->after('notifikasi_sound_path');
            $table->text('mode_maintenance_pesan')->nullable()->after('mode_maintenance_aktif');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->dropColumn(['mode_maintenance_aktif', 'mode_maintenance_pesan']);
        });
    }
};
