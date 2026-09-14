<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserNotifikasiController extends Controller
{
    /**
     * Sama seperti NotifikasiSettingController::updateToggle (saklar global
     * admin) -- fitur push notification per-user WAJIB selalu aktif dan
     * tidak boleh dimatikan siapapun, termasuk oleh pemilik akun itu sendiri.
     * Endpoint ini sengaja DIPERTAHANKAN (bukan dihapus) supaya request
     * lama/eksternal ke rute ini tidak 404, tapi apapun yang dikirim akan
     * selalu dipaksa menjadi aktif -- efeknya toggle "mati" sudah tidak
     * punya jalan lagi, baik dari UI (lihat lainnya-kasansi.blade.php,
     * toggle-nya sudah diganti jadi status baca-saja "Selalu Aktif") maupun
     * dari request manual ke rute ini.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        if (! $user->notif_push_enabled) {
            $user->update(['notif_push_enabled' => true]);
        }

        return response()->json([
            'ok' => true,
            'enabled' => true,
            'unsubscribe_browser' => false,
        ]);
    }
}
