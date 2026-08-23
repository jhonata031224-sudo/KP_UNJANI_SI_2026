<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbarui teks deskripsi 4 kartu "Keunggulan" di landing page agar
     * lebih menggambarkan kondisi sistem sebenarnya, tidak terkesan
     * mengklaim sistem sudah sempurna (mis. klaim "sistem cadangan"
     * padahal belum ada fitur backup otomatis).
     */
    public function up(): void
    {
        DB::table('pengaturans')->update([
            'fitur' => json_encode([
                ['judul' => 'Real-time', 'deskripsi' => 'Status dan progres laporan diperbarui otomatis, sehingga tidak perlu menunggu rekap manual untuk memantaunya.'],
                ['judul' => 'Terpusat', 'deskripsi' => 'Data laporan dan dokumen pendukung tersimpan dalam satu sistem, memudahkan pencarian dibanding menyimpannya secara terpisah.'],
                ['judul' => 'Efisien', 'deskripsi' => 'Alur persetujuan bertingkat membantu mempersingkat proses administrasi dibanding pengumpulan laporan secara manual.'],
                ['judul' => 'Aman & Terkontrol', 'deskripsi' => 'Akses data diatur berdasarkan peran pengguna, sehingga laporan hanya dapat dilihat atau diubah oleh pihak yang berwenang.'],
            ]),
        ]);
    }

    public function down(): void
    {
        // Tidak ada rollback — ini migration penambal data, bukan skema.
    }
};
