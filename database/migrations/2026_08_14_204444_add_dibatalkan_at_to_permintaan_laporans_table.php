<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('permintaan_laporans', function (Blueprint $table) {
            $table->dateTime('dibatalkan_at')->nullable()->after('selesai_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permintaan_laporans', function (Blueprint $table) {
            $table->dropColumn('dibatalkan_at');
        });
    }
};
