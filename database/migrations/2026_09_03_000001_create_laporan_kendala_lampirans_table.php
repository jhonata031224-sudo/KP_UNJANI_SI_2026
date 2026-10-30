<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lampiran buat satu LaporanKendala (Kasansi -> Danpus) -- satu kendala
     * sekarang bisa punya BANYAK lampiran (semua format, bukan cuma PDF),
     * bukan cuma 1 kayak sebelumnya (kolom lampiran_path di tabel
     * laporan_kendalas). Kolom lama itu SENGAJA dibiarkan (tidak dihapus/
     * dimigrasikan) buat jaga data lama yang sudah ada di production tetap
     * bisa dibaca -- lihat LaporanKendala::getSemuaLampiranAttribute() yang
     * gabungin keduanya jadi satu daftar konsisten buat ditampilkan di semua
     * view. Pola sama persis kayak migration create_laporan_lampirans_table
     * punya model Laporan.
     */
    public function up(): void
    {
        Schema::create('laporan_kendala_lampirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_kendala_id')->constrained('laporan_kendalas')->cascadeOnDelete();
            $table->string('path');
            $table->string('nama_asli');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_kendala_lampirans');
    }
};
