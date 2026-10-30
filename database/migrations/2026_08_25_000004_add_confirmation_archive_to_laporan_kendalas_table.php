<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda penerimaan resmi Danpus untuk laporan kendala Kasansi.
     * Record tidak dipindahkan ke tabel lain; confirmed_at menjadi batas
     * antara daftar masuk dan Arsip Kendala Kasansi sehingga data tetap utuh
     * dan tidak pernah bercampur dengan Permintaan Laporan.
     */
    public function up(): void
    {
        Schema::table('laporan_kendalas', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('catatan');
            $table->foreignId('confirmed_by')->nullable()->after('confirmed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_kendalas', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['confirmed_at', 'confirmed_by']);
        });
    }
};
