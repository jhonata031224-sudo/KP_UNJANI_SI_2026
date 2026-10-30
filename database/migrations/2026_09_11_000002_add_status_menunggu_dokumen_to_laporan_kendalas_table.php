<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom dokumen_siap (boolean) dan dokumen_dikirim_at di
     * laporan_kendalas untuk melacak tahap baru dalam alur:
     *
     * Alur baru kendala Kasansi:
     *   1. Kasansi kirim kendala  → STATUS: Menunggu Balasan (wajib ada tembusan)
     *   2. Tembusan kirim balasan (teks feedback + opsional dokumen)
     *   3. Kasansi baca balasan, siapkan dokumen → upload dok lewat uploadDokumenKasansi()
     *   4. Kasansi kirim dokumen  → STATUS: Menunggu (baru Danpus diberi tahu)
     *
     * dokumen_kasansi_path / dokumen_kasansi_nama : dokumen yang disiapkan
     *   Kasansi setelah baca balasan tembusan, SEBELUM diteruskan ke Danpus.
     * dokumen_kasansi_at / dokumen_kasansi_oleh   : kapan & siapa yang upload.
     */
    public function up(): void
    {
        Schema::table('laporan_kendalas', function (Blueprint $table) {
            $table->string('dokumen_kasansi_path')->nullable()->after('diteruskan_oleh');
            $table->string('dokumen_kasansi_nama')->nullable()->after('dokumen_kasansi_path');
            $table->timestamp('dokumen_kasansi_at')->nullable()->after('dokumen_kasansi_nama');
            $table->foreignId('dokumen_kasansi_oleh')->nullable()->after('dokumen_kasansi_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_kendalas', function (Blueprint $table) {
            $table->dropForeign(['dokumen_kasansi_oleh']);
            $table->dropColumn([
                'dokumen_kasansi_path',
                'dokumen_kasansi_nama',
                'dokumen_kasansi_at',
                'dokumen_kasansi_oleh',
            ]);
        });
    }
};
