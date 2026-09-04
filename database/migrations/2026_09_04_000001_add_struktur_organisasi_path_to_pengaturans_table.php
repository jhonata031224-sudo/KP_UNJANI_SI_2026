<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Gambar Struktur Organisasi yang tampil di menu "Lainnya -> Struktur
// Organisasi" pada dashboard Kasansi. Gambarnya diunggah Admin lewat
// menu Admin -> Kelola Sistem -> Struktur Organisasi (lihat
// StrukturOrganisasiController), disimpan sebagai satu file gambar
// tunggal (bukan tabel terpisah) sama seperti pola logo_path/
// hero_image_path yang sudah ada di tabel ini.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->string('struktur_organisasi_path')->nullable()->after('notifikasi_push_aktif');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->dropColumn('struktur_organisasi_path');
        });
    }
};
