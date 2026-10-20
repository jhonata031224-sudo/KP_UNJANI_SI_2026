<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Target Mode Maintenance. Kolom ini berisi daftar id satuan (array JSON)
// yang terkena maintenance. NULL / array kosong = SEMUA pengguna non-Admin
// (perilaku lama, jadi data yang sudah ada tetap berlaku sama persis).
// Isi = hanya pengguna pada satuan-satuan itu yang terkena (mis. hanya
// Danpus saat role itu sedang dikembangkan/diperbaiki); pengguna satuan lain
// tetap bekerja normal. Admin TIDAK PERNAH terkena, apa pun targetnya.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->text('mode_maintenance_satuan_ids')->nullable()->after('mode_maintenance_pesan');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->dropColumn('mode_maintenance_satuan_ids');
        });
    }
};
