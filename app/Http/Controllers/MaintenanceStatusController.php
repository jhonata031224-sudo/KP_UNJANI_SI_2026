<?php

namespace App\Http\Controllers;

use App\Http\Middleware\InjectMaintenanceUi;
use App\Models\Pengaturan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Status Mode Maintenance untuk sinkronisasi REALTIME di sisi klien.
 *
 * Banner + penguncian tombol (partials/maintenance-banner.blade.php) dulu
 * hanya disuntik server saat halaman dimuat, jadi pengguna non-Admin yang
 * sudah membuka halaman baru tahu Admin mengaktifkan/menonaktifkan
 * maintenance setelah refresh. Skrip di partial itu sekarang polling
 * endpoint ini dan menyalakan/mematikan banner + kunci tombol tanpa reload.
 *
 * Ini HANYA lapisan UX: penolakan request sesungguhnya tetap di
 * EnforceMaintenanceMode (dievaluasi tiap request di server).
 */
class MaintenanceStatusController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('satuan');

        // Admin tidak pernah terdampak, dan pengguna di luar target maintenance
        // (kalau Admin menarget satuan tertentu) juga tidak -- logika tunggal
        // yang sama dengan EnforceMaintenanceMode & InjectMaintenanceUi.
        $pengaturan = Pengaturan::current();
        $aktif      = $pengaturan->maintenanceBerlakuUntuk($user);

        return response()->json([
            'aktif' => $aktif,
            'pesan' => $aktif ? $pengaturan->pesanMaintenance() : null,
            'waktu' => $aktif ? app(InjectMaintenanceUi::class)->waktuPengumumanMaintenance($user) : null,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
