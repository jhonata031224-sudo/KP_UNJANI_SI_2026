<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom "context" dipakai ActivityLog::catat() untuk menyimpan data
     * terstruktur tambahan (mis. role, satuan, payload aksi) sebagai JSON —
     * ditambahkan supaya cocok dengan $fillable & $casts di model ActivityLog.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->json('context')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('context');
        });
    }
};
