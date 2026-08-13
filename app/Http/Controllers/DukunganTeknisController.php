<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Laporan;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanBaruDiterima;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DukunganTeknisController extends Controller
{
    /**
     * Duktek tetap memiliki fungsi khusus untuk mendukung tiga Satlak
     * operasional, tetapi pencatatannya masuk ke alur Laporan terpadu.
     * Tidak ada pencatatan CPU/RAM/storage/network atau data teknis perangkat.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun ini belum terhubung ke satuan manapun.');

        $validated = $request->validate([
            'satuan_tujuan_id' => [
                'required',
                'exists:satuans,id',
                function ($attribute, $value, $fail) {
                    $kode = Satuan::whereKey($value)->value('kode');
                    if (! in_array(strtoupper(trim((string) $kode)), ['SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK'], true)) {
                        $fail('Tujuan dukungan Duktek hanya Penangkalan, Siber Sosial, atau Penindakan.');
                    }
                },
            ],
            'jenis_bantuan' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:10000'],
        ]);

        abort_if((int) $validated['satuan_tujuan_id'] === (int) $satuan->id, 422, 'Tujuan laporan tidak boleh sama dengan satuan pengirim.');

        $tujuan = Satuan::findOrFail($validated['satuan_tujuan_id']);
        $laporan = Laporan::create([
            'satuan_id' => $satuan->id,
            'user_id' => $user->id,
            'tujuan_satuan_id' => $tujuan->id,
            'proyek' => 'Dukungan Teknologi',
            'perihal' => $validated['jenis_bantuan'],
            'deskripsi' => $validated['keterangan'] ?: 'Dukungan teknologi untuk kegiatan '.$tujuan->nama.'.',
            'prioritas' => 'Sedang',
            'status' => 'Menunggu',
        ]);

        foreach (User::where('satuan_id', $tujuan->id)->get() as $penerima) {
            $penerima->notify(new LaporanBaruDiterima($laporan));
        }

        ActivityLog::catat('dukungan-teknis.create', "Mengirim dukungan teknis \"{$laporan->perihal}\" ke {$tujuan->nama}.", $user, [
            'laporan_id' => $laporan->id,
        ]);

        return back()->with('status', 'Laporan dukungan Duktek berhasil dikirim ke '.$tujuan->nama.'.');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        // Riwayat dukungan sekarang tersimpan sebagai Laporan terpadu.
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        $laporan = Laporan::whereKey($id)->firstOrFail();

        abort_unless($satuan && (int) $laporan->satuan_id === (int) $satuan->id, 403);
        abort_unless($laporan->proyek === 'Dukungan Teknologi', 404);

        $perihal = $laporan->perihal;
        $laporanId = $laporan->id;
        $laporan->delete();

        ActivityLog::catat('dukungan-teknis.delete', "Menghapus laporan dukungan teknis \"{$perihal}\".", $user, [
            'laporan_id' => $laporanId,
        ]);

        return back()->with('status', 'Laporan dukungan Duktek berhasil dihapus.');
    }
}
