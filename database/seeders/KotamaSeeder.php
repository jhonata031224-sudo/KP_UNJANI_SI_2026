<?php

namespace Database\Seeders;

use App\Models\Satuan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KotamaSeeder extends Seeder
{
    /**
     * 21 Sansidam (Komando Daerah Militer) aktif di lingkungan TNI AD,
     * berkategori Kotama. Data resmi -- kode & username dibuat dari
     * nama/julukan wilayah masing-masing (bukan penomoran generik
     * dam1, dam2, dst) supaya lebih natural secara teknis.
     * Diurutkan GEOGRAFIS dari Barat ke Timur (Aceh -> Papua).
     * Sama seperti satuan lain, satu satuan = satu akun login.
     */
    public function run(): void
    {
        $kotamas = [
            ['username' => 'iskandarmuda',    'nama' => 'Sansidam Iskandar Muda'],
            ['username' => 'bukitbarisan',    'nama' => 'Sansidam I/Bukit Barisan'],
            ['username' => 'tuankutambusai',  'nama' => 'Sansidam XIX/Tuanku Tambusai'],
            ['username' => 'imambonjol',      'nama' => 'Sansidam XX/Tuanku Imam Bonjol'],
            ['username' => 'sriwijaya',       'nama' => 'Sansidam II/Sriwijaya'],
            ['username' => 'radininten',      'nama' => 'Sansidam XXI/Radin Inten'],
            ['username' => 'siliwangi',       'nama' => 'Sansidam III/Siliwangi'],
            ['username' => 'jaya',            'nama' => 'Sansidam Jayakarta'],
            ['username' => 'diponegoro',      'nama' => 'Sansidam IV/Diponegoro'],
            ['username' => 'brawijaya',       'nama' => 'Sansidam V/Brawijaya'],
            ['username' => 'udayana',         'nama' => 'Sansidam IX/Udayana'],
            ['username' => 'tanjungpura',     'nama' => 'Sansidam XII/Tanjungpura'],
            ['username' => 'tambunbungai',    'nama' => 'Sansidam XXII/Tambun Bungai'],
            ['username' => 'mulawarman',      'nama' => 'Sansidam VI/Mulawarman'],
            ['username' => 'palakawira',      'nama' => 'Sansidam XXIII/Palaka Wira'],
            ['username' => 'hasanuddin',      'nama' => 'Sansidam XIV/Hasanuddin'],
            ['username' => 'merdeka',         'nama' => 'Sansidam XIII/Merdeka'],
            ['username' => 'pattimura',       'nama' => 'Sansidam XV/Pattimura'],
            ['username' => 'kasuari',         'nama' => 'Sansidam XVIII/Kasuari'],
            ['username' => 'cenderawasih',    'nama' => 'Sansidam XVII/Cenderawasih'],
            ['username' => 'mandalatrikora',  'nama' => 'Sansidam XXIV/Mandala Trikora'],
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
