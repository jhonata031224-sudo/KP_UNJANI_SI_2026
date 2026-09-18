<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deadline surat -- SAAT INI cuma dipakai surat dari Danpus dengan
     * prioritas "Kilat" (lihat priority-toggle di modal Buat Surat Baru,
     * laporan-danpus.blade.php, dan validasi required_if di
     * LaporanSuratController::store()). Nullable karena prioritas lain
     * (Biasa/Rahasia) maupun surat dari satuan selain Danpus tidak wajib
     * mengisi deadline.
     */
    public function up(): void
    {
        Schema::table('laporan_surats', function (Blueprint $table) {
            $table->dateTime('deadline_at')->nullable()->after('prioritas');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_surats', function (Blueprint $table) {
            $table->dropColumn('deadline_at');
        });
    }
};
