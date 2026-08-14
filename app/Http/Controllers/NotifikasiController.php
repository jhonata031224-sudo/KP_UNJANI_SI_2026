<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotifikasiController extends Controller
{
    /**
     * Dipoll berkala oleh lonceng notifikasi di navbar (semua role) buat
     * nyari notifikasi belum dibaca terbaru + jumlahnya, biar badge & daftar
     * di dropdown ikut update tanpa perlu reload halaman.
     */
    public function realtime(Request $request): JsonResponse
    {
        $notifications = $request->user()->unreadNotifications()
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'message' => $n->data['pesan'] ?? 'Status laporan diperbarui.',
                'time' => optional($n->created_at)->diffForHumans(),
            ])
            ->values();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->count(),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Hapus satu notifikasi (dipanggil dari tombol X di dropdown).
     */
    public function hapus(Request $request, DatabaseNotification $notifikasi): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            (string) $notifikasi->notifiable_id === (string) $user->id
                && $notifikasi->notifiable_type === $user->getMorphClass(),
            403
        );

        $notifikasi->delete();

        return response()->json(['status' => 'ok']);
    }
}
