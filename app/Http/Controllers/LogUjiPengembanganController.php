<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LogUjiPengembangan;
use App\Models\ProyekRiset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LogUjiPengembanganController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $satuan = $request->user()->load('satuan')->satuan;
        abort_unless($satuan, 403, 'Akun ini belum terhubung ke satuan manapun.');

        $validated = $request->validate([
            'proyek_riset_id' => ['nullable', 'exists:proyek_risets,id'],
            'kegiatan' => ['required', 'string', 'max:255'],
            'hasil' => ['nullable', 'string'],
            'status' => ['required', 'in:Selesai,Perlu Tindak Lanjut'],
            'waktu_uji' => ['nullable', 'date'],
        ]);

        if (! empty($validated['proyek_riset_id'])) {
            $milikSatuan = ProyekRiset::where('id', $validated['proyek_riset_id'])
                ->where('satuan_id', $satuan->id)
                ->exists();
            abort_unless($milikSatuan, 403, 'Proyek riset tidak ditemukan di satuan ini.');
        }

        $validated['satuan_id'] = $satuan->id;
        $validated['waktu_uji'] = $validated['waktu_uji'] ?? now();

        $log = LogUjiPengembangan::create($validated);

        ActivityLog::catat('log-uji-pengembangan.create', "Mencatat log uji & pengembangan \"{$log->kegiatan}\".", $request->user(), [
            'log_uji_pengembangan_id' => $log->id,
        ]);

        return back()->with('status', 'Log uji & pengembangan berhasil dicatat.');
    }

    public function destroy(Request $request, LogUjiPengembangan $logUjiPengembangan): RedirectResponse
    {
        $satuan = $request->user()->load('satuan')->satuan;
        abort_unless($satuan && $logUjiPengembangan->satuan_id === $satuan->id, 403);

        $kegiatan = $logUjiPengembangan->kegiatan;
        $logId = $logUjiPengembangan->id;
        $logUjiPengembangan->delete();

        ActivityLog::catat('log-uji-pengembangan.delete', "Menghapus log uji & pengembangan \"{$kegiatan}\".", $request->user(), [
            'log_uji_pengembangan_id' => $logId,
        ]);

        return back()->with('status', 'Log uji & pengembangan berhasil dihapus.');
    }
}
