<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_laporans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembuat_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tujuan_satuan_id')->constrained('satuans')->cascadeOnDelete();
            $table->string('perihal', 255);
            $table->text('instruksi')->nullable();
            $table->dateTime('deadline_at');
            $table->string('prioritas', 20)->default('Sedang');
            $table->string('status', 40)->default('Belum dikerjakan');
            $table->foreignId('laporan_id')->nullable()->constrained('laporans')->nullOnDelete();
            $table->dateTime('dikerjakan_at')->nullable();
            $table->dateTime('selesai_at')->nullable();
            $table->timestamps();

            $table->index(['tujuan_satuan_id', 'deadline_at']);
            $table->index(['status', 'deadline_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_laporans');
    }
};
