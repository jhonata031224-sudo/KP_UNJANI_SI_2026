<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * SDIR bukan entitas satuan yang nyata -- struktur organisasi sebenarnya
     * hanya punya 4 satuan Direktorat (Binfung, Binum, Diklat, Binmat) yang
     * secara kolektif menjalankan peran koordinasi yang dulunya melekat pada
     * "SDIR". Hapus record satuan SDIR yang tersisa di database.
     *
     * Catatan: relasi users.satuan_id memakai nullOnDelete, jadi jika ada
     * akun yang masih terhubung ke SDIR, akun tsb tidak akan ikut terhapus,
     * hanya satuan_id-nya jadi null.
     */
    public function up(): void
    {
        DB::table('satuans')->where('kode', 'SDIR')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SDIR tidak direkonstruksi ulang karena bukan struktur organisasi
        // yang valid.
    }
};
