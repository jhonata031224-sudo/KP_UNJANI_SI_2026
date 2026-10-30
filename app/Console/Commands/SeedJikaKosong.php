<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SeedJikaKosong extends Command
{
    protected $signature = 'db:seed-jika-kosong';

    protected $description = 'Jalankan db:seed hanya bila tabel users masih kosong (database baru), supaya ada akun Admin awal.';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->info('Tabel users sudah berisi data, seed awal dilewati.');

            return self::SUCCESS;
        }

        $this->info('Database baru terdeteksi (users kosong), menjalankan seed awal...');

        return $this->call('db:seed', ['--force' => true]);
    }
}
