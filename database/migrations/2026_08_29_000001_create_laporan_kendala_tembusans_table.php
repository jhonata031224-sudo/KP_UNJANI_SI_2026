<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tembusan (CC) laporan kendala Kasansi -> DANPUS ke 4 Satlak + 4 Sdir,
     * sekadar info/koordinasi -- BUKAN alur approval kedua. Tujuan resmi
     * laporan_kendalas TETAP satu-satunya DANPUS (kolom tujuan_satuan_id di
     * tabel itu tidak diubah); baris di sini murni penanda "siapa saja yang
     * diberi tahu", tidak pernah mengubah status Menunggu/Ditindaklanjuti/
     * Selesai/Ditolak/Dikonfirmasi milik laporan_kendalas.
     *
     * dibaca_at/dibaca_oleh dicatat PER SATUAN (bukan per user) -- siapa pun
     * pengguna di satuan penerima yang pertama kali membuka detailnya sudah
     * cukup menandai satuan itu "sudah membaca", pengguna lain di satuan yang
     * sama tetap bisa melihat detailnya kapan saja tanpa perlu menandai lagi.
     */
    public function up(): void
    {
        Schema::create('laporan_kendala_tembusans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_kendala_id')->constrained('laporan_kendalas')->cascadeOnDelete();
            $table->foreignId('satuan_id')->constrained('satuans')->cascadeOnDelete(); // 4 Satlak/4 Sdir penerima tembusan
            $table->timestamp('dibaca_at')->nullable();
            $table->foreignId('dibaca_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['laporan_kendala_id', 'satuan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_kendala_tembusans');
    }
};
