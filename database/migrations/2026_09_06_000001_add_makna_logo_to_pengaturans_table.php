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
            // makna_logo: array 10 poin keterangan makna lambang (judul singkat +
            // penjelasan) yang tampil di modal "Makna Logo" saat logo di landing
            // page (section Tentang) diklik. Diisi/diedit Admin lewat Pengaturan
            // Umum -> tab Tentang, bukan di-hardcode di kode.
            $table->json('makna_logo')->nullable()->after('struktur_organisasi_path');
        });

        // Isi nilai default (10 poin) untuk instalasi yang sudah berjalan supaya
        // modal tidak kosong sebelum Admin sempat mengisinya sendiri.
        DB::table('pengaturans')->whereNull('makna_logo')->update([
            'makna_logo' => json_encode(\App\Models\Pengaturan::defaultMaknaLogo()),
        ]);
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->dropColumn('makna_logo');
        });
    }
};
