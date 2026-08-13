<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanPenindakan;
use App\Notifications\LaporanPenindakanStatusDiubah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Aksi DANPUS atas Laporan Penanganan Insiden yang dikirim Satuan
 * Pelaksanaan Penindakan (Satlakdak): Setujui, Tolak, atau Minta Revisi.
 * Terpisah dari LaporanPenindakanController (yang khusus sisi
 * Satlakdak/pembuat laporan) supaya wewenangnya jelas.
 */
class DanpusLaporanPenindakanController extends Controller
{
    public function updateStatus(Request $request, LaporanPenindakan $laporanPenindakan): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        abort_unless($user->satuan && $laporanPenindakan->tujuan_satuan_id === $user->satuan->id, 403);
        abort_unless($laporanPenindakan->status === 'Dikirim', 403, 'Laporan ini sudah diputuskan atau belum dikirim.');

        $validated = $request->validate([
            'status' => ['required', 'in:Disetujui,Ditolak,Direvisi'],
            'catatan_danpus' => ['nullable', 'string', 'max:2000', 'required_if:status,Direvisi,Ditolak'],
        ]);

        $laporanPenindakan->update([
            'status' => $validated['status'],
            'catatan_danpus' => $validated['catatan_danpus'] ?? null,
        ]);

        $laporanPenindakan->user->notify(new LaporanPenindakanStatusDiubah($laporanPenindakan));

        ActivityLog::catat('laporan-penindakan.keputusan', "Memutuskan laporan penanganan insiden \"{$laporanPenindakan->perihal}\" menjadi {$validated['status']}.", $user, [
            'laporan_penindakan_id' => $laporanPenindakan->id,
            'status' => $validated['status'],
        ]);

        return back()->with('status', "Laporan penindakan \"{$laporanPenindakan->perihal}\" telah diperbarui menjadi {$validated['status']}.");
    }
}
