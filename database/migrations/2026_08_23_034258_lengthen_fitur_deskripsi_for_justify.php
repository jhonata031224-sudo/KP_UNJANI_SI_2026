<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sesuaikan panjang teks deskripsi 4 kartu "Keunggulan" di landing page
     * agar wrapping-nya lebih pas saat teks di-justify (text-align: justify),
     * tetap ringkas dan tidak overclaim.
     */
    public function up(): void
    {
        DB::table('pengaturans')->update([
            'fitur' => json_encode([
                ['judul' => 'Real-time', 'deskripsi' => 'Status dan perkembangan laporan diperbarui otomatis, sehingga tidak perlu direkap manual setiap saat.'],
                ['judul' => 'Terpusat', 'deskripsi' => 'Laporan beserta dokumen pendukungnya tersimpan rapi dalam satu sistem yang sama, sehingga lebih mudah ditemukan.'],
                ['judul' => 'Efisien', 'deskripsi' => 'Alur persetujuan bertingkat membantu mempercepat proses dibanding pengumpulan laporan secara manual.'],
                ['judul' => 'Aman & Terkontrol', 'deskripsi' => 'Akses terhadap laporan diatur sesuai peran pengguna, sehingga hanya pihak berwenang yang dapat melihat atau mengubah.'],
            ]),
        ]);
    }

    public function down(): void
    {
        // Tidak ada rollback — ini migration penambal data, bukan skema.
    }
};
