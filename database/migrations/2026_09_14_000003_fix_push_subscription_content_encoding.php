<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Perbaikan bug: PushSubscriptionController::store() sebelumnya hardcode
 * content_encoding => 'aesgcm' (skema enkripsi draft LAMA) untuk setiap
 * subscription push yang tersimpan. Browser modern (Chrome/Firefox/Edge/
 * Safari) HANYA mendekripsi payload push yang dienkripsi dengan
 * 'aes128gcm' (standar RFC 8291) -- lihat MDN
 * PushManager.supportedContentEncodings.
 *
 * Akibatnya: WebPushChannel berhasil "mengirim" ke push service (respons
 * tetap sukses/201, tidak ada error yang tercatat di log manapun), tapi
 * browser penerima gagal mendekripsi payload-nya secara DIAM-DIAM --
 * notifikasi push tidak pernah muncul di tray OS penerima walau semuanya
 * terlihat berhasil di sisi server. Ini root cause laporan "notif push
 * tidak muncul di penerima saat sedang di luar sistem".
 *
 * Migrasi ini membetulkan SEMUA baris push_subscriptions yang ada
 * sekarang (yang pasti tersimpan 'aesgcm' atau kosong, karena kode lama
 * tidak pernah menyimpan nilai lain) supaya subscription lama langsung
 * berfungsi tanpa user perlu mengizinkan ulang dari nol.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('push_subscriptions')
            ->where('content_encoding', 'aesgcm')
            ->orWhereNull('content_encoding')
            ->update(['content_encoding' => 'aes128gcm']);
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan ke 'aesgcm' -- itu memang bug yang
        // mau diperbaiki, bukan perilaku yang mau dipertahankan.
    }
};
