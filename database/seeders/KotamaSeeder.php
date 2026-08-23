<?php

namespace Database\Seeders;

use App\Models\Satuan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KotamaSeeder extends Seeder
{
    /**
     * 21 Kodam (Komando Daerah Militer) aktif di lingkungan TNI AD,
     * berkategori Kotama. Data resmi -- kode & username dibuat dari
     * nama/julukan wilayah masing-masing (bukan penomoran generik
     * dam1, dam2, dst) supaya lebih natural secara teknis.
     * Sama seperti satuan lain, satu satuan = satu akun login.
     */
    public function run(): void
    {
        $kotamas = [
            ['username' => 'bukitbarisan',    'nama' => 'Kodam I/Bukit Barisan'],
            ['username' => 'sriwijaya',       'nama' => 'Kodam II/Sriwijaya'],
            ['username' => 'siliwangi',       'nama' => 'Kodam III/Siliwangi'],
            ['username' => 'diponegoro',      'nama' => 'Kodam IV/Diponegoro'],
            ['username' => 'brawijaya',       'nama' => 'Kodam V/Brawijaya'],
            ['username' => 'mulawarman',      'nama' => 'Kodam VI/Mulawarman'],
            ['username' => 'udayana',         'nama' => 'Kodam IX/Udayana'],
            ['username' => 'tanjungpura',     'nama' => 'Kodam XII/Tanjungpura'],
            ['username' => 'merdeka',         'nama' => 'Kodam XIII/Merdeka'],
            ['username' => 'hasanuddin',      'nama' => 'Kodam XIV/Hasanuddin'],
            ['username' => 'pattimura',       'nama' => 'Kodam XV/Pattimura'],
            ['username' => 'cenderawasih',    'nama' => 'Kodam XVII/Cenderawasih'],
            ['username' => 'kasuari',         'nama' => 'Kodam XVIII/Kasuari'],
            ['username' => 'jaya',            'nama' => 'Kodam Jayakarta'],
            ['username' => 'iskandarmuda',    'nama' => 'Kodam Iskandar Muda'],
            ['username' => 'tuankutambusai',  'nama' => 'Kodam XIX/Tuanku Tambusai'],
            ['username' => 'imambonjol',      'nama' => 'Kodam XX/Tuanku Imam Bonjol'],
            ['username' => 'radininten',      'nama' => 'Kodam XXI/Radin Inten'],
            ['username' => 'tambunbungai',    'nama' => 'Kodam XXII/Tambun Bungai'],
            ['username' => 'palakawira',      'nama' => 'Kodam XXIII/Palaka Wira'],
            ['username' => 'mandalatrikora',  'nama' => 'Kodam XXIV/Mandala Trikora'],
        ];

        foreach ($kotamas as $data) {
            $kode = strtoupper($data['username']);

            $satuan = Satuan::updateOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $data['nama'],
                    'kategori' => Satuan::KATEGORI_KOTAMA,
                    'deskripsi' => 'Satuan Kotama '.$data['nama'].'.',
                ]
            );

            User::updateOrCreate(
                ['satuan_id' => $satuan->id],
                [
                    'name' => $data['nama'],
                    'username' => $data['username'],
                    'email' => $data['username'].'@pussiberad.mil.id',
                    'password' => Hash::make('111'),
                    'jabatan' => null,
                ]
            );
        }
    }
}
