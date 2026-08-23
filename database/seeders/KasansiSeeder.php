<?php

namespace Database\Seeders;

use App\Models\Satuan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KasansiSeeder extends Seeder
{
    /**
     * Tambah 23 satuan terpisah berkategori Kasansi (Kodam 1 s.d. Kodam 23).
     * Sama seperti satuan lain, satu satuan = satu akun login.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 23; $i++) {
            $kode = 'KODAM'.$i;
            $username = 'kodam'.$i;
            $nama = 'Kodam '.$i;

            $satuan = Satuan::updateOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $nama,
                    'kategori' => Satuan::KATEGORI_KASANSI,
                    'deskripsi' => 'Satuan Kasansi Kodam '.$i.'.',
                ]
            );

            User::updateOrCreate(
                ['satuan_id' => $satuan->id],
                [
                    'name' => $nama,
                    'username' => $username,
                    'email' => $username.'@pussiberad.mil.id',
                    'password' => Hash::make('111'),
                    'jabatan' => null,
                ]
            );
        }
    }
}
