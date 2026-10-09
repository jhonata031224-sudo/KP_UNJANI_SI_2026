<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sistem berganti nama dari SIBERAD menjadi Cyclone.
 *
 * Kode sumber sudah diganti, tetapi teks yang tersimpan di tabel `pengaturans`
 * (konten landing, judul meta, deskripsi footer, dst.) masih bisa memuat nama
 * lama karena disimpan di database, bukan di kode. Migrasi ini mengganti kata
 * "SIBERAD" di kolom teks tabel tersebut.
 *
 * Aturan:
 *  - "Pussiberad" / "PUSSIBERAD" (nama instansi) TIDAK diubah.
 *  - Idempotent: baris yang sudah bersih tidak disentuh, aman dijalankan ulang.
 *  - down() sengaja kosong; nama lama tidak dipulihkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pengaturans')) {
            return;
        }

        $pola = '/(?<![Pp][Uu][Ss])siberad/i';

        foreach (DB::table('pengaturans')->get() as $baris) {
            $perubahan = [];

            foreach ((array) $baris as $kolom => $nilai) {
                if (! is_string($nilai) || $nilai === '' || ! preg_match($pola, $nilai)) {
                    continue;
                }

                $perubahan[$kolom] = preg_replace_callback($pola, function (array $m): string {
                    // Kata merek: "SIBERAD"/"Siberad" -> "Cyclone"; huruf kecil (mis. di URL/ID) -> "cyclone".
                    return ctype_upper($m[0][0]) ? 'Cyclone' : 'cyclone';
                }, $nilai);
            }

            if ($perubahan !== []) {
                DB::table('pengaturans')->where('id', $baris->id)->update($perubahan);
            }
        }
    }

    public function down(): void
    {
        // Tidak ada pemulihan nama lama.
    }
};
