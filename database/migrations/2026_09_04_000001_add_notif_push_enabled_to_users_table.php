<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Preferensi push notification per-user: default true (ikut setelan global admin).
            // Kalau false, user ini tidak akan nerima push meski global aktif.
            $table->boolean('notif_push_enabled')->default(true)->after('foto_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notif_push_enabled');
        });
    }
};
