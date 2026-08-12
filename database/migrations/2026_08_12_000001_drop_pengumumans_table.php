<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fitur Pengumuman (broadcast banner ke seluruh satuan) dicabut —
     * tidak pernah dipakai, tabel masih kosong.
     */
    public function up(): void
    {
        Schema::dropIfExists('pengumumans');
    }

    public function down(): void
    {
        Schema::create('pengumumans', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('isi');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }
};
