<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Saklar global (menu Admin -> Setelan -> Notifikasi) buat menyalakan/
// mematikan fitur push notification utk SELURUH pengguna sekaligus --
// dipakai baik di sisi tampil-tidaknya tombol "Aktifkan Notifikasi"
// (lihat InjectWebPushUi) maupun di pengiriman aktualnya (lihat
// WebPushChannel::send()), supaya Admin punya satu tombol pusat tanpa
// perlu ubah environment variable/server.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->boolean('notifikasi_push_aktif')->default(true)->after('sosial_media');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturans', function (Blueprint $table) {
            $table->dropColumn('notifikasi_push_aktif');
        });
    }
};
