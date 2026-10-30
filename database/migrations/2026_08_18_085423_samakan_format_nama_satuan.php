<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Kolom `nama` di beberapa satuan sempat diedit manual dengan format
     * yang beda-beda (ada yang "Kode (Keterangan)", ada yang cuma nama
     * polos, satu bahkan typo "Satlok" alih-alih "Satlak"). Ini bikin
     * judul "Aktivitas ..." di Log Aktivitas pimpinan kelihatan gak
     * konsisten antar satuan. Samakan semua ke format "Kode (Keterangan)"
     * yang sudah dipakai mayoritas satuan.
     */
    public function up(): void
    {
        $target = [
            'SATLAKDAK'    => 'Satlakdak (Penindakan)',
            'SATLAKKAL'    => 'Satlakkal (Penangkalan)',
            'SATLAKSISOS'  => 'Satlaksisos (Siber Sosial)',
            'SATLAKDUKTEK' => 'Satlakduktek (Dukungan Teknologi)',
            'DIKLAT'       => 'Diklat (Pendidikan & Latihan)',
            'BINUM'        => 'Binum (Pembinaan Umum)',
            'BINFUNG'      => 'Binfung (Pembinaan Fungsi)',
            'BINMAT'       => 'Binmat (Pembinaan Materiil)',
        ];

        foreach ($target as $kode => $nama) {
            DB::table('satuans')->where('kode', $kode)->update(['nama' => $nama]);
        }
    }

    public function down(): void
    {
        $previous = [
            'SATLAKDAK'    => 'Satlak Penindakan',
            'SATLAKKAL'    => 'Satlakkal (Penangkalan)',
            'SATLAKSISOS'  => 'Satlak Sibersos',
            'SATLAKDUKTEK' => 'Satlok Duktek (Dukungan Teknologi)',
            'DIKLAT'       => 'Diklat (Pendidikan & Latihan)',
            'BINUM'        => 'Binum (Pembinaan Umum)',
            'BINFUNG'      => 'Binfung (Pembinaan Fungsi)',
            'BINMAT'       => 'Binmat (Pembinaan Materiil)',
        ];

        foreach ($previous as $kode => $nama) {
            DB::table('satuans')->where('kode', $kode)->update(['nama' => $nama]);
        }
    }
};
