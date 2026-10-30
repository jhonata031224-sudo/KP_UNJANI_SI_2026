<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fitur "Surat" khusus Kasansi (21 Sansidam): kirim surat ke SATU
     * satuan tujuan bebas (dipilih sendiri dari seluruh satuan lain di
     * sistem), TANPA tembusan dan TANPA alur status/progres apa pun --
     * beda total dari LaporanKendala yang tujuannya selalu tetap DANPUS
     * dan punya status Menunggu/Ditindaklanjuti/Selesai/Ditolak/
     * Dikonfirmasi.
     *
     * Karena tidak ada progres yang perlu dilacak, begitu surat dikirim ia
     * langsung "final" -- sengaja TIDAK ada kolom status. Sisi pengirim
     * melihatnya di Arsip Surat, sisi tujuan melihatnya di Surat Masuk;
     * keduanya baca dari tabel yang sama, dibedakan lewat satuan_id vs
     * tujuan_satuan_id.
     */
    public function up(): void
    {
        Schema::create('laporan_surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satuan_id')->constrained('satuans')->cascadeOnDelete(); // Kasansi pengirim
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();     // pengguna yang mengirim
            $table->foreignId('tujuan_satuan_id')->constrained('satuans')->cascadeOnDelete(); // satu tujuan bebas, tanpa tembusan
            $table->string('perihal');
            $table->string('kategori')->nullable();
            $table->text('deskripsi'); // isi surat
            $table->string('prioritas'); // Tinggi | Sedang | Rendah
            $table->string('lampiran_path'); // wajib, sama seperti kendala
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_surats');
    }
};
