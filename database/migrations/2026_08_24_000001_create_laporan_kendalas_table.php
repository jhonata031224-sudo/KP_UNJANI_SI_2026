<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laporan kendala/rutin yang dikirim satuan Kasansi (21 Sansidam)
     * LANGSUNG ke DANPUS. Terpisah dari tabel "laporans" (yang dipakai buat
     * alur pelaporan progres/permintaan laporan resmi ke Danpus/Wadan --
     * "kebutuhan khusus" yang diminta duluan oleh Danpus) karena kendala ini
     * sifatnya bebas/tidak terikat permintaan laporan apa pun -- Kasansi
     * bisa mengirim kapan saja ada kendala, tanpa perlu diminta lebih dulu.
     */
    public function up(): void
    {
        Schema::create('laporan_kendalas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satuan_id')->constrained('satuans')->cascadeOnDelete(); // Kasansi pengirim
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();     // pengguna yang mengirim
            $table->foreignId('tujuan_satuan_id')->constrained('satuans')->cascadeOnDelete(); // selalu DANPUS
            $table->string('perihal');
            $table->text('deskripsi'); // isi kendala
            $table->string('prioritas'); // Tinggi | Sedang | Rendah
            $table->string('lampiran_path')->nullable();
            $table->string('status')->default('Menunggu'); // Menunggu | Ditindaklanjuti | Selesai | Ditolak
            $table->text('catatan')->nullable(); // catatan Danpus/Wadan saat menindaklanjuti/menolak
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_kendalas');
    }
};
