<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom dokumen_balasan* di laporan_kendala_tembusans menyimpan dokumen
     * yang dikirim tembusan (Satlak/Sdir) balik ke Kasansi sebagai lampiran
     * jawaban atas feedback mereka -- misalnya laporan personel yang diminta.
     * Berbeda dari kolom 'feedback' (teks murni), dokumen_balasan adalah FILE
     * yang disiapkan tembusan dan menjadi bahan bagi Kasansi sebelum
     * meneruskan laporan ke Danpus.
     *
     * dokumen_balasan_path  : path file di disk 'public' (storage/app/public)
     * dokumen_balasan_nama  : nama asli file saat diupload
     * dokumen_balasan_at    : kapan file dikirim
     * dokumen_balasan_oleh  : user tembusan yang mengirim file
     */
    public function up(): void
    {
        Schema::table('laporan_kendala_tembusans', function (Blueprint $table) {
            $table->string('dokumen_balasan_path')->nullable()->after('feedback_oleh');
            $table->string('dokumen_balasan_nama')->nullable()->after('dokumen_balasan_path');
            $table->timestamp('dokumen_balasan_at')->nullable()->after('dokumen_balasan_nama');
            $table->foreignId('dokumen_balasan_oleh')->nullable()->after('dokumen_balasan_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_kendala_tembusans', function (Blueprint $table) {
            $table->dropForeign(['dokumen_balasan_oleh']);
            $table->dropColumn([
                'dokumen_balasan_path',
                'dokumen_balasan_nama',
                'dokumen_balasan_at',
                'dokumen_balasan_oleh',
            ]);
        });
    }
};
