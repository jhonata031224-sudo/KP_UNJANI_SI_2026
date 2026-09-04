<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserNotifikasiController extends Controller
{
    /**
     * Toggle preferensi push notification per-user (on/off).
     * Dipanggil via AJAX dari panel Notifikasi di sidebar Kasansi.
     * Kalau dimatikan: subscription push di DB tidak dihapus, tapi
     * WebPushChannel akan skip user ini saat ngirim push.
     */
    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->update(['notif_push_enabled' => $validated['enabled']]);

        // Kalau user nonaktifkan notif, cabut semua push subscription di browser
        // supaya benar-benar tidak ada push yang masuk (bukan cuma skip di server).
        // Ini dilakukan lewat response flag -- JS yang handle unsubscribe di sisi browser.
        return response()->json([
            'ok'      => true,
            'enabled' => (bool) $user->notif_push_enabled,
            'unsubscribe_browser' => ! $validated['enabled'],
        ]);
    }
}
