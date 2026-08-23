<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laporan keluhan yang dikirim satuan Kasansi (21 Kodam) ke salah satu
     * dari 4 Satlak operasional. Terpisah dari tabel "laporans" (yang
     * dipakai buat alur pelaporan progres/permintaan laporan resmi ke
     * Danpus/Wadan) karena keluhan ini sifatnya bebas/tidak terikat
     * permintaan laporan apa pun -- Kasansi bisa mengirim kapan saja
     * ada keluhan ke Satlak terkait.
     */
    public function up(): void
    {
        Schema::create('laporan_keluhans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satuan_id')->constrained('satuans')->cascadeOnDelete(); // Kasansi pengirim
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();     // pengguna yang mengirim
            $table->foreignId('tujuan_satuan_id')->constrained('satuans')->cascadeOnDelete(); // Satlak tujuan
            $table->string('perihal');
            $table->text('deskripsi'); // isi keluhan
            $table->string('prioritas'); // Tinggi | Sedang | Rendah
            $table->string('lampiran_path')->nullable();
            $table->string('status')->default('Menunggu'); // Menunggu | Ditindaklanjuti | Selesai | Ditolak
            $table->text('catatan')->nullable(); // catatan Satlak saat menindaklanjuti/menolak
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_keluhans');
    }
};
