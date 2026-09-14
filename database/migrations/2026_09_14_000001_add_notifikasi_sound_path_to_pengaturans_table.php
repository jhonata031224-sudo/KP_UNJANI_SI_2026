<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// File suara (mp3/wav/ogg) yang diputar otomatis di SEMUA dashboard
// (Admin, Danpus/Wadan, dan seluruh role Satuan) setiap kali ada
// notifikasi baru masuk ke lonceng in-app. Diunggah Admin lewat menu
// Lainnya -> Notifikasi (lihat NotifikasiSettingController::updateSuara),
// disimpan sebagai satu file tunggal -- sama seperti pola
// struktur_organisasi_path di tabel ini.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->string('notifikasi_sound_path')->nullable()->after('struktur_organisasi_path');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->dropColumn('notifikasi_sound_path');
        });
    }
};
