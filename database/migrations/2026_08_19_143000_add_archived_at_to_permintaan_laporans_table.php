<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_laporans', function (Blueprint $table) {
            $table->dateTime('archived_at')->nullable()->after('dibatalkan_at');
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_laporans', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
