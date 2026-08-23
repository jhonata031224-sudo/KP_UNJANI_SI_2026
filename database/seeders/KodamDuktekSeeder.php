<?php

namespace Database\Seeders;

use App\Models\Satuan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KodamDuktekSeeder extends Seeder
{
    /**
     * Tambah 23 akun pengguna (Kodam 1 - Kodam 23) di bawah Satlak Duktek.
     * Berbeda dari satuan lain yang cuma satu akun, di sini sengaja banyak
     * akun dalam satu satuan yang sama (Satlak Duktek) karena tiap akun
     * mewakili satu Kodam.
     */
    public function run(): void
    {
        $satlakDuktek = Satuan::where('kode', 'SATLAKDUKTEK')->first();

        if (! $satlakDuktek) {
            $this->command?->warn('Satuan SATLAKDUKTEK tidak ditemukan, KodamDuktekSeeder dilewati.');

            return;
        }

        for ($i = 1; $i <= 23; $i++) {
            $username = 'kodam'.$i;

            User::updateOrCreate(
                ['username' => $username],
                [
                    'name' => 'Kodam '.$i,
                    'email' => $username.'@pussiberad.mil.id',
                    'satuan_id' => $satlakDuktek->id,
                    'jabatan' => 'Kodam',
                    'password' => Hash::make('111'),
                ]
            );
        }
    }
}
