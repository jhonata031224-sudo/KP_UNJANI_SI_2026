<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Bersihkan 23 satuan Kasansi lama (kode KODAM1..KODAM23, hasil
     * KasansiSeeder) beserta akun & data turunannya (laporan, postingan,
     * dll -- semuanya cascade ikut terhapus lewat FK satuan_id), supaya
     * KotamaSeeder (21 Sansidam nama resmi, kode berbasis nama wilayah) bisa
     * diisi ulang tanpa duplikat/menggantung. Data lama ini murni dummy
     * percobaan, bukan data laporan sungguhan.
     */
    public function up(): void
    {
        $satuanLama = DB::table('satuans')->where('kode', 'like', 'KODAM%')->pluck('id');

        if ($satuanLama->isEmpty()) {
            return;
        }

        // Hapus akun dulu (kolom users.satuan_id nullOnDelete, jadi kalau
        // satuan dihapus duluan akun lama akan menggantung tanpa satuan).
        DB::table('users')->whereIn('satuan_id', $satuanLama)->delete();

        // Hapus satuannya -- laporan/postingan/dll yang menempel otomatis
        // ikut terhapus lewat cascadeOnDelete pada foreign key satuan_id.
        DB::table('satuans')->whereIn('id', $satuanLama)->delete();
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan -- data lama murni dummy percobaan.
    }
};
