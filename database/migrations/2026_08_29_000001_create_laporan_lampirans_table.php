<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lampiran (PDF) buat satu Laporan (checkpoint progres) -- satu laporan
     * sekarang bisa punya BANYAK lampiran, bukan cuma 1 kayak sebelumnya
     * (kolom lampiran_path/lampiran_nama_asli di tabel laporans). Kolom lama
     * itu SENGAJA dibiarkan (tidak dihapus/dimigrasikan) buat jaga data lama
     * yang sudah ada di production tetap bisa dibaca -- lihat
     * Laporan::getSemuaLampiranAttribute() yang gabungin keduanya jadi satu
     * daftar konsisten buat ditampilkan di semua view.
     */
    public function up(): void
    {
        Schema::create('laporan_lampirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporans')->cascadeOnDelete();
            $table->string('path');
            $table->string('nama_asli');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_lampirans');
    }
};
