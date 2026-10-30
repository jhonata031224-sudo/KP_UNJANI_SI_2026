<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan "alamat" push notification browser (endpoint + kunci
     * enkripsi) per device/browser yang mengizinkan notifikasi. Satu user
     * bisa punya banyak baris (login dari HP & laptop sekaligus, misalnya)
     * -- semuanya dikirimi push yang sama saat ada notifikasi baru.
     */
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // endpoint unik per subscription browser (URL push service:
            // FCM utk Chrome, Mozilla push service utk Firefox, dst).
            $table->string('endpoint', 500);
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            // Buat ditampilkan di UI "Perangkat yang menerima notifikasi"
            // (opsional) -- bukan dipakai buat logika apapun.
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'endpoint'], 'push_subscriptions_user_endpoint_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
