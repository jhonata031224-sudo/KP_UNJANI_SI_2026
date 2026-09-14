<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Flush SEMUA baris push_subscriptions yang ada saat ini.
 *
 * Kenapa perlu diflush sekarang?
 *
 * Sebelum fix di commit ini, ada dua bug berlapis yang menyebabkan
 * notifikasi push tidak pernah muncul di sisi penerima:
 *
 *  1) content_encoding tersimpan "aesgcm" (skema draft lama) -- migrasi
 *     000003 sudah membetulkan nilai di DB menjadi "aes128gcm", tapi
 *     endpoint yang tersimpan di DB belum tentu masih hidup/valid di sisi
 *     push service (FCM/Mozilla), karena:
 *
 *  2) InjectWebPushUi hanya inject script subscribe ke route bernama
 *     "dashboard" (/dashboard). Artinya user yang pertama kali membuka
 *     halaman lain setelah login tidak pernah menjalankan
 *     Notification.requestPermission() maupun PushManager.subscribe() --
 *     subscription yang tersimpan di DB bisa saja sudah expired/dibuang
 *     oleh push service tanpa pernah diperbarui dari browser.
 *
 * Fix di commit ini sudah menyelesaikan kedua masalah di atas:
 *  - InjectWebPushUi sekarang inject ke SEMUA halaman HTML yang sudah
 *    login (bukan cuma /dashboard).
 *  - JS subscribe sekarang konfirmasi ulang ke server setiap 6 jam, dan
 *    force re-subscribe kalau subscription hilang dari browser.
 *
 * Migrasi ini memastikan baris-baris lama yang tersimpan (yang mungkin
 * sudah tidak valid di sisi push service) dibersihkan sekalian, sehingga
 * saat user pertama kali login setelah deploy ini, browser mereka akan
 * membuat subscription baru yang segar dan langsung tersimpan ke server
 * dengan data yang benar -- tanpa perlu user klik apapun.
 *
 * Efek samping yang diharapkan: tidak ada notif push "hantu" (server
 * menganggap berhasil kirim, tapi push service langsung 404/discard karena
 * endpoint sudah kadaluarsa).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('push_subscriptions')->delete();
    }

    public function down(): void
    {
        // Data subscription tidak bisa dikembalikan -- ini memang data
        // operasional ephemeral yang sengaja dibersihkan, bukan data
        // bisnis yang perlu di-restore. User akan re-subscribe otomatis
        // saat pertama kali membuka sistem setelah deploy.
    }
};
