<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename kategori "mandiri" (Pok Analis, Urdal) jadi "pokpel" -- ternyata
     * kedua satuan ini bukan berdiri sendiri, melainkan Pok Pel (Kelompok
     * Pelayan) yang langsung di bawah/melayani Danpus. Lihat
     * Satuan::KATEGORI_POKPEL. Urutan tampilnya di semua tabel admin tidak
     * berubah (tetap setelah Wadan, sebelum 4 Sdir).
     */
    public function up(): void
    {
        DB::table('satuans')->where('kategori', 'mandiri')->update(['kategori' => 'pokpel']);
    }

    public function down(): void
    {
        DB::table('satuans')->where('kategori', 'pokpel')->update(['kategori' => 'mandiri']);
    }
};
