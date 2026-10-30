<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Feedback/catatan yang dikirim satuan penerima tembusan (Satlak/Sdir)
     * balik ke Kasansi pengirim. Begitu SATU tembusan saja mengisi feedback,
     * Kasansi sudah boleh meneruskan laporan kendalanya ke Danpus lewat
     * LaporanKendalaController::teruskan() -- lihat komentar migration
     * create_laporan_kendala_tembusans_table untuk konteks tembusan itu
     * sendiri.
     *
     * Sengaja dipisah dari dibaca_at/dibaca_oleh (yang murni penanda "sudah
     * dibaca", per satuan) -- feedback adalah tindakan eksplisit satu kali
     * per baris tembusan (per satuan penerima), dicatat siapa user-nya yang
     * mengisi lewat feedback_oleh.
     */
    public function up(): void
    {
        Schema::table('laporan_kendala_tembusans', function (Blueprint $table) {
            $table->text('feedback')->nullable()->after('satuan_id');
            $table->timestamp('feedback_at')->nullable()->after('feedback');
            $table->foreignId('feedback_oleh')->nullable()->after('feedback_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_kendala_tembusans', function (Blueprint $table) {
            $table->dropForeign(['feedback_oleh']);
            $table->dropColumn(['feedback', 'feedback_at', 'feedback_oleh']);
        });
    }
};
