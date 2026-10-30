<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbaiki nama tampil satuan DANPUS & WADAN yang sebelumnya tersimpan
     * huruf besar semua ("DANPUS" / "WADAN") menjadi format yang sama
     * dengan satuan lain: "<Nama Singkat> (<Keterangan>)" -> ditampilkan
     * sebagai "Danpus" di kolom Nama dan "Komandan Pusat Pussiberad" di
     * kolom Satuan (begitu juga Wadan), lewat accessor nama_singkat /
     * nama_keterangan di model Satuan. `kode` tidak diubah, jadi seluruh
     * logika bisnis yang mengandalkan kode (routing, hak akses, dsb) tidak
     * terpengaruh.
     *
     * Nama user satuan (kolom users.name) ikut diupdate juga, karena
     * SatuanSeeder selalu menyamakan users.name dengan satuans.nama.
     */
    public function up(): void
    {
        $target = [
            'DANPUS' => 'Danpus (Komandan Pusat Pussiberad)',
            'WADAN'  => 'Wadan (Wakil Komandan Pussiberad)',
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
            'DANPUS' => 'Komandan Pusat',
            'WADAN'  => 'Wakil Komandan',
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
