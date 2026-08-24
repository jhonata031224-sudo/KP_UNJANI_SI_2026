<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permintaan_laporan_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_laporan_id')->constrained()->cascadeOnDelete();
            $table->string('deskripsi', 255);
            $table->boolean('selesai')->default(false);
            $table->dateTime('selesai_at')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['permintaan_laporan_id', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permintaan_laporan_tasks');
    }
};
