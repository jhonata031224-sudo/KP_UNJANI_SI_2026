<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Simpan/perbarui subscription push milik device/browser yang sedang
     * dipakai user login. Dipanggil JS setelah user klik "Aktifkan
     * Notifikasi" dan browser berhasil bikin push subscription baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'endpoint' => $validated['endpoint'],
            ],
            [
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                // WAJIB "aes128gcm" (RFC 8291), BUKAN "aesgcm" (skema draft lama).
                // Browser modern (Chrome/Firefox/Edge/Safari) HANYA mendekripsi
                // payload push yang dienkripsi dengan aes128gcm -- lihat MDN
                // PushManager.supportedContentEncodings: "User agents must
                // support the aes128gcm content coding defined in RFC 8291".
                // Sebelumnya field ini di-hardcode "aesgcm" (skema lama), jadi
                // WebPushChannel berhasil ngirim ke push service (respons tetap
                // sukses, tidak ada error di log), tapi browser penerima gagal
                // mendekripsi payload-nya secara DIAM-DIAM -- notifikasi push
                // tidak pernah muncul di tray OS walau semuanya "terlihat"
                // berhasil di sisi server. Ini root cause laporan "notif tidak
                // muncul di penerima saat di luar sistem".
                'content_encoding' => 'aes128gcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Hapus subscription (dipanggil saat user klik "Matikan Notifikasi",
     * atau otomatis oleh browser saat permission dicabut).
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $request->user()->pushSubscriptions()
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json(['ok' => true]);
    }
}
