<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perpendek & sederhanakan teks deskripsi 4 kartu "Keunggulan" di
     * landing page supaya lebih ringkas dan tidak overclaim.
     */
    public function up(): void
    {
        DB::table('pengaturans')->update([
            'fitur' => json_encode([
                ['judul' => 'Real-time', 'deskripsi' => 'Status laporan diperbarui otomatis, tanpa perlu direkap manual.'],
                ['judul' => 'Terpusat', 'deskripsi' => 'Laporan dan dokumen pendukung tersimpan dalam satu sistem yang sama.'],
                ['judul' => 'Efisien', 'deskripsi' => 'Alur persetujuan bertingkat mempercepat proses dibanding cara manual.'],
                ['judul' => 'Aman & Terkontrol', 'deskripsi' => 'Akses laporan diatur sesuai peran, hanya pihak berwenang yang bisa melihat atau mengubah.'],
            ]),
        ]);
    }

    public function down(): void
    {
        // Tidak ada rollback — ini migration penambal data, bukan skema.
    }
};
