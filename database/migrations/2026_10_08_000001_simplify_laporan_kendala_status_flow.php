<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Penyederhanaan alur Kendala Kasansi: Menunggu Konfirmasi -> Dikonfirmasi.
 *
 * Hanya MENGGANTI LABEL status "menunggu" peninggalan alur lama pada laporan
 * yang belum dikonfirmasi Danpus:
 *   - 'Menunggu'          (alur lama: sudah di Danpus, belum diputuskan)
 *   - 'Menunggu Balasan'  (alur lama: masih mampir di tembusan)
 * keduanya menjadi 'Menunggu Konfirmasi' supaya langsung muncul di daftar
 * Danpus dan bisa dikonfirmasi.
 *
 * TIDAK ada kolom/tabel/data yang dihapus. Baris yang sudah 'Dikonfirmasi'
 * tidak disentuh. Baris lama berstatus Ditindaklanjuti/Selesai/Ditolak yang
 * belum dikonfirmasi juga dibiarkan apa adanya (label & catatan aslinya
 * utuh); karena daftar Danpus berdasar confirmed_at, baris-baris itu tetap
 * tampil dan masih bisa dikonfirmasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('laporan_kendalas')
            ->whereNull('confirmed_at')
            ->whereIn('status', ['Menunggu', 'Menunggu Balasan'])
            ->update(['status' => 'Menunggu Konfirmasi']);
    }

    public function down(): void
    {
        // Label asli ('Menunggu' vs 'Menunggu Balasan') tidak bisa dibedakan
        // lagi; kembalikan ke 'Menunggu' (label dasar alur lama).
        DB::table('laporan_kendalas')
            ->whereNull('confirmed_at')
            ->where('status', 'Menunggu Konfirmasi')
            ->update(['status' => 'Menunggu']);
    }
};
