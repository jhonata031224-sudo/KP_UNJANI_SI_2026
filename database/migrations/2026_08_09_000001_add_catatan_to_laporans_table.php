<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('laporans', 'catatan')) {
            Schema::table('laporans', function (Blueprint $table) {
                $table->text('catatan')->nullable()->after('deskripsi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('laporans', 'catatan')) {
            Schema::table('laporans', function (Blueprint $table) {
                $table->dropColumn('catatan');
            });
        }
    }
};
