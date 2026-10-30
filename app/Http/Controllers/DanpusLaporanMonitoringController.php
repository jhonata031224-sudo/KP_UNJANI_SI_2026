<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanMonitoring;
use App\Notifications\LaporanMonitoringStatusDiubah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Aksi DANPUS atas Laporan Monitoring & Recovery yang dikirim Satuan Pelaksanaan Penangkalan:
 * Setujui, Tolak, atau Minta Revisi. Terpisah dari LaporanMonitoringController
 * (yang khusus sisi Satuan Pelaksanaan Penangkalan/pembuat laporan) supaya wewenangnya jelas.
 */
class DanpusLaporanMonitoringController extends Controller
{
    public function updateStatus(Request $request, LaporanMonitoring $laporanMonitoring): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        abort_unless($user->satuan && $laporanMonitoring->tujuan_satuan_id === $user->satuan->id, 403);
        abort_unless($laporanMonitoring->status === 'Dikirim', 403, 'Laporan ini sudah diputuskan atau belum dikirim.');

        $validated = $request->validate([
            'status' => ['required', 'in:Disetujui,Ditolak,Direvisi'],
            'catatan_danpus' => ['nullable', 'string', 'max:2000', 'required_if:status,Direvisi,Ditolak'],
        ]);

        $laporanMonitoring->update([
            'status' => $validated['status'],
            'catatan_danpus' => $validated['catatan_danpus'] ?? null,
        ]);

        $laporanMonitoring->user->notify(new LaporanMonitoringStatusDiubah($laporanMonitoring));

        ActivityLog::catat('laporan-monitoring.keputusan', "Memutuskan laporan monitoring & recovery \"{$laporanMonitoring->jenis_kegiatan}\" menjadi {$validated['status']}.", $user, [
            'laporan_monitoring_id' => $laporanMonitoring->id,
            'status' => $validated['status'],
        ]);

        return back()->with('status', "Laporan kegiatan \"{$laporanMonitoring->jenis_kegiatan}\" telah diperbarui menjadi {$validated['status']}.");
    }
}
