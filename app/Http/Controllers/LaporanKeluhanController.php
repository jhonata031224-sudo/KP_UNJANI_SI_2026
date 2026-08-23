<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanKeluhan;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanKeluhanBaruDiterima;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Alur "Kirim Laporan" (keluhan) khusus 21 Kasansi (Kotama) ke salah satu
 * dari 4 Satlak operasional. Berbeda dari LaporanController (yang terikat
 * alur Permintaan Laporan Danpus/Wadan), fitur ini bebas dikirim kapan saja
 * tanpa perlu ada permintaan laporan lebih dulu.
 */
class LaporanKeluhanController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tujuan_satuan_id' => ['required', 'integer', 'exists:satuans,id'],
            'perihal' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:10000'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $user = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;
        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');
        abort_unless(
            in_array(strtoupper((string) $satuanAsal->kode), Satuan::KODE_KOTAMA, true),
            403,
            'Hanya Kasansi yang dapat mengirim laporan keluhan ke Satlak.'
        );

        $tujuan = Satuan::findOrFail($validated['tujuan_satuan_id']);
        abort_unless(
            in_array(strtoupper((string) $tujuan->kode), Satuan::KODE_SATLAK, true),
            422,
            'Tujuan laporan keluhan harus salah satu Satlak.'
        );

        $lampiranPath = $request->hasFile('lampiran')
            ? $request->file('lampiran')->store('lampiran-keluhan', 'public')
            : null;

        $keluhan = LaporanKeluhan::create([
            'satuan_id' => $satuanAsal->id,
            'user_id' => $user->id,
            'tujuan_satuan_id' => $tujuan->id,
            'perihal' => $validated['perihal'],
            'deskripsi' => $validated['deskripsi'],
            'prioritas' => $validated['prioritas'],
            'lampiran_path' => $lampiranPath,
            'status' => LaporanKeluhan::STATUS_MENUNGGU,
        ]);

        foreach (User::where('satuan_id', $tujuan->id)->get() as $penerima) {
            $penerima->notify(new LaporanKeluhanBaruDiterima($keluhan));
        }

        ActivityLog::catat('laporan-keluhan.create', "Mengirim laporan keluhan \"{$keluhan->perihal}\" ke {$tujuan->nama}.", $user, [
            'laporan_keluhan_id' => $keluhan->id,
            'tujuan_satuan' => $tujuan->nama,
            'prioritas' => $keluhan->prioritas,
        ]);

        return back()->with('status', 'Laporan keluhan berhasil dikirim ke '.$tujuan->nama.'.');
    }

    public function updateStatus(Request $request, LaporanKeluhan $laporanKeluhan): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Ditindaklanjuti,Selesai,Ditolak'],
            'catatan' => ['nullable', 'string', 'max:5000', 'required_if:status,Ditolak'],
        ], [
            'catatan.required_if' => 'Catatan penolakan wajib diisi.',
        ]);

        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        abort_unless((int) $laporanKeluhan->tujuan_satuan_id === (int) $satuan->id, 403, 'Anda bukan penerima laporan keluhan ini.');

        $laporanKeluhan->update([
            'status' => $validated['status'],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        ActivityLog::catat('laporan-keluhan.status', "Memperbarui status laporan keluhan \"{$laporanKeluhan->perihal}\" menjadi {$laporanKeluhan->status}.", $user, [
            'laporan_keluhan_id' => $laporanKeluhan->id,
            'status' => $laporanKeluhan->status,
        ]);

        return back()->with('status', 'Status laporan keluhan berhasil diperbarui menjadi '.$laporanKeluhan->status.'.');
    }

    public function destroy(Request $request, LaporanKeluhan $laporanKeluhan): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);
        abort_unless((int) $laporanKeluhan->satuan_id === (int) $satuan->id, 403);

        if ($laporanKeluhan->lampiran_path) {
            Storage::disk('public')->delete($laporanKeluhan->lampiran_path);
        }
        $perihal = $laporanKeluhan->perihal;
        $laporanKeluhan->delete();

        ActivityLog::catat('laporan-keluhan.delete', "Menghapus laporan keluhan \"{$perihal}\" dari riwayat.", $user, [
            'laporan_keluhan_id' => $laporanKeluhan->id,
        ]);

        return back()->with('status', 'Laporan keluhan berhasil dihapus.');
    }
}
