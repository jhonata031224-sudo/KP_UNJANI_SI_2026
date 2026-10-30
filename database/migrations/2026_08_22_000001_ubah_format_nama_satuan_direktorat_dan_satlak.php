<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ubah nama tampil 4 satuan Direktorat & 4 satuan Satlak ke format baru
     * ("Sdir <singkatan>" / "Satlak <singkatan>") sesuai permintaan, supaya
     * urutan & penulisan di Rekap Laporan dan Daftar Pengguna (admin)
     * konsisten. `kode` tidak diubah sama sekali, jadi seluruh logika bisnis
     * yang mengandalkan kode (routing, hak akses, dsb) tidak terpengaruh.
     *
     * Nama user satuan ikut diupdate juga (kolom users.name), karena selama
     * ini SatuanSeeder selalu menyamakan users.name dengan satuans.nama --
     * kalau tidak ikut diupdate di sini, nama akun yang login akan beda
     * dengan nama satuan yang tampil di tabel.
     */
    public function up(): void
    {
        $target = [
            'BINFUNG'      => 'Sdir Binfung (Pembinaan Fungsi)',
            'BINUM'        => 'Sdir Binum (Pembinaan Umum)',
            'DIKLAT'       => 'Sdir Bindiklat (Pendidikan & Latihan)',
            'BINMAT'       => 'Sdir Binmat (Pembinaan Materiil)',
            'SATLAKKAL'    => 'Satlak Kal (Penangkalan)',
            'SATLAKDAK'    => 'Satlak Dak (Penindakan)',
            'SATLAKSISOS'  => 'Satlak Siber Sos (Siber Sosial)',
            'SATLAKDUKTEK' => 'Satlak Dukteksi (Dukungan Teknologi)',
        ];

        foreach ($target as $kode => $nama) {
            $satuanId = DB::table('satuans')->where('kode', $kode)->value('id');
            if (! $satuanId) {
                continue;
            }

            DB::table('satuans')->where('id', $satuanId)->update(['nama' => $nama]);
            DB::table('users')->where('satuan_id', $satuanId)->update(['name' => $nama]);
        }
    }

    public function down(): void
    {
        $previous = [
            'BINFUNG'      => 'Binfung (Pembinaan Fungsi)',
            'BINUM'        => 'Binum (Pembinaan Umum)',
            'DIKLAT'       => 'Diklat (Pendidikan & Latihan)',
            'BINMAT'       => 'Binmat (Pembinaan Materiil)',
            'SATLAKKAL'    => 'Satlakkal (Penangkalan)',
            'SATLAKDAK'    => 'Satlakdak (Penindakan)',
            'SATLAKSISOS'  => 'Satlaksisos (Siber Sosial)',
            'SATLAKDUKTEK' => 'Satlakduktek (Dukungan Teknologi)',
        ];

        foreach ($previous as $kode => $nama) {
            $satuanId = DB::table('satuans')->where('kode', $kode)->value('id');
            if (! $satuanId) {
                continue;
            }

            DB::table('satuans')->where('id', $satuanId)->update(['nama' => $nama]);
            DB::table('users')->where('satuan_id', $satuanId)->update(['name' => $nama]);
        }
    }
};
