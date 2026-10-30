<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda kapan & oleh siapa Kasansi meneruskan laporan kendala yang
     * sempat "mampir" ke tembusan (status Menunggu Tembusan) ke Danpus.
     * Cuma terisi untuk laporan yang punya tembusan -- laporan tanpa
     * tembusan tetap langsung Menunggu seperti alur lama dan kolom ini
     * dibiarkan null. Lihat LaporanKendalaController::store()/teruskan().
     */
    public function up(): void
    {
        Schema::table('laporan_kendalas', function (Blueprint $table) {
            $table->timestamp('diteruskan_at')->nullable()->after('confirmed_by');
            $table->foreignId('diteruskan_oleh')->nullable()->after('diteruskan_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_kendalas', function (Blueprint $table) {
            $table->dropForeign(['diteruskan_oleh']);
            $table->dropColumn(['diteruskan_at', 'diteruskan_oleh']);
        });
    }
};
