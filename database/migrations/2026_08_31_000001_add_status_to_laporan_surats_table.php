<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom `status` ke tabel laporan_surats.
     *
     * Status awal setelah surat dikirim: 'menunggu_konfirmasi'.
     * Penerima bisa mengkonfirmasi surat sehingga status berubah ke
     * 'dikonfirmasi'. Surat baru masuk ke Arsip Surat pengirim (Kasansi)
     * setelah status 'dikonfirmasi'; selama masih 'menunggu_konfirmasi'
     * surat tampil di tabel Kirim Surat (belum diarsipkan).
     */
    public function up(): void
    {
        Schema::table('laporan_surats', function (Blueprint $table) {
            $table->string('status')->default('menunggu_konfirmasi')->after('prioritas');
            $table->timestamp('dikonfirmasi_at')->nullable()->after('status');
            $table->foreignId('dikonfirmasi_oleh')->nullable()->constrained('users')->nullOnDelete()->after('dikonfirmasi_at');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_surats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dikonfirmasi_oleh');
            $table->dropColumn(['status', 'dikonfirmasi_at']);
        });
    }
};
