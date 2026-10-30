<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Kartu "Nama Resmi", "Nama Lama", "Fungsi Utama" di section Tentang landing
// page sebelumnya hardcoded langsung di blade (tidak seperti field tentang_*
// lain yang sudah tersambung ke tabel ini) -- jadi Admin tidak bisa
// mengeditnya lewat Pengaturan Umum. Migration ini menambah kolomnya.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->string('tentang_nama_resmi', 255)->nullable()->after('tentang_deskripsi');
            $table->string('tentang_nama_lama', 255)->nullable()->after('tentang_nama_resmi');
            $table->text('tentang_fungsi_utama')->nullable()->after('tentang_nama_lama');
        });

        // Isi nilai default dari yang sebelumnya hardcoded di welcome.blade.php,
        // supaya landing page tidak tiba-tiba kosong setelah migration ini jalan.
        DB::table('pengaturans')->whereNull('tentang_nama_resmi')->update([
            'tentang_nama_resmi' => 'Pusat Siber Angkatan Darat (Pussiberad)',
            'tentang_nama_lama' => 'Pusat Sandi dan Siber TNI Angkatan Darat (Pussansiad)',
            'tentang_fungsi_utama' => 'Pertahanan siber, sandi, serta penanganan insiden keamanan informasi di lingkungan TNI AD.',
        ]);
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->dropColumn(['tentang_nama_resmi', 'tentang_nama_lama', 'tentang_fungsi_utama']);
        });
    }
};
