<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\PermintaanLaporan;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanBaruDiterima;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LaporanController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tujuan_satuan_id' => ['required', 'integer', 'exists:satuans,id'],
            'permintaan_laporan_id' => ['nullable', 'integer', 'exists:permintaan_laporans,id'],
            'proyek' => ['nullable', 'string', 'max:255'],
            'perihal' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:10000'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $user = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;
        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');

        $tujuan = Satuan::findOrFail($validated['tujuan_satuan_id']);
        abort_if(strtoupper((string) $tujuan->kode) === 'ADMIN', 422, 'Laporan tidak dapat ditujukan ke Admin.');
        abort_if((int) $tujuan->id === (int) $satuanAsal->id, 422, 'Tujuan laporan tidak boleh sama dengan satuan pengirim.');

        $kodeTujuanDiizinkan = Satuan::kodeTujuanUntuk($satuanAsal->kode);
        if ($kodeTujuanDiizinkan !== null) {
            abort_unless(
                in_array(strtoupper((string) $tujuan->kode), $kodeTujuanDiizinkan, true),
                422,
                'Tujuan laporan tidak sesuai dengan alur pelaporan satuan Anda.'
            );
        }

        $permintaan = null;
        if (!empty($validated['permintaan_laporan_id'])) {
            $permintaan = PermintaanLaporan::findOrFail($validated['permintaan_laporan_id']);
            abort_unless((int) $permintaan->tujuan_satuan_id === (int) $satuanAsal->id, 403, 'Permintaan laporan bukan untuk satuan Anda.');
            abort_unless((int) $permintaan->tujuanSatuan->id === (int) $tujuan->id, 422, 'Tujuan laporan tidak sesuai dengan permintaan laporan.');
            abort_if($permintaan->laporan_id && $permintaan->laporan?->status !== 'Revisi', 422, 'Permintaan laporan tersebut sudah memiliki laporan.');
        }

        $lampiranPath = $request->hasFile('lampiran')
            ? $request->file('lampiran')->store('lampiran-laporan', 'public')
            : null;

        $laporan = Laporan::create([
            'satuan_id' => $satuanAsal->id,
            'user_id' => $user->id,
            'tujuan_satuan_id' => $tujuan->id,
            'permintaan_laporan_id' => $permintaan?->id,
            'proyek' => $validated['proyek'] ?? null,
            'perihal' => $validated['perihal'],
            'deskripsi' => $validated['deskripsi'],
            'prioritas' => $validated['prioritas'],
            'lampiran_path' => $lampiranPath,
            'status' => 'Menunggu',
        ]);

        if ($permintaan) {
            $permintaan->update([
                'laporan_id' => $laporan->id,
                'status' => PermintaanLaporan::STATUS_PEMERIKSAAN,
                'selesai_at' => null,
            ]);
        }

        foreach (User::where('satuan_id', $tujuan->id)->get() as $penerima) {
            $penerima->notify(new LaporanBaruDiterima($laporan));
        }

        return back()->with('status', $permintaan
            ? 'Laporan berhasil dikirim sesuai permintaan dan deadline yang diberikan.'
            : 'Laporan berhasil dikirim ke '.$tujuan->nama.'.');
    }

    public function updateStatus(Request $request, Laporan $laporan): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Diterima,Ditolak,Revisi,Disetujui,Disetujui DANPUS,Ditolak DANPUS,Revisi DANPUS,Disetujui WADAN,Ditolak WADAN,Revisi WADAN'],
            'catatan' => ['nullable', 'string', 'max:5000', 'required_if:status,Ditolak,Ditolak DANPUS,Ditolak WADAN'],
        ], [
            'catatan.required_if' => 'Catatan penolakan wajib diisi.',
        ]);

        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        abort_unless((int) $laporan->tujuan_satuan_id === (int) $satuan->id, 403, 'Anda bukan penerima laporan ini.');

        $kode = strtoupper((string) $satuan->kode);
        $aksi = strtolower((string) $validated['status']);

        $statusFinal = match ($kode) {
            'DANPUS' => str_contains($aksi, 'setuj') || str_contains($aksi, 'terima')
                ? 'Disetujui DANPUS'
                : (str_contains($aksi, 'tolak') ? 'Ditolak DANPUS' : 'Revisi DANPUS'),
            'WADAN' => str_contains($aksi, 'setuj') || str_contains($aksi, 'terima')
                ? 'Disetujui WADAN'
                : (str_contains($aksi, 'tolak') ? 'Ditolak WADAN' : 'Revisi WADAN'),
            default => str_contains($aksi, 'terima') || str_contains($aksi, 'setuj')
                ? 'Diterima'
                : (str_contains($aksi, 'tolak') ? 'Ditolak' : 'Revisi'),
        };

        $laporan->update([
            'status' => $statusFinal,
            'catatan' => str_contains(strtolower($statusFinal), 'tolak')
                ? $validated['catatan']
                : ($validated['catatan'] ?? null),
        ]);

        if ($laporan->permintaanLaporan) {
            $permintaan = $laporan->permintaanLaporan;
            $statusLower = strtolower($statusFinal);
            $isRevisi = str_contains($statusLower, 'revisi');
            $isSelesai = str_contains($statusLower, 'tolak') || str_contains($statusLower, 'setuj') || str_contains($statusLower, 'diterima');
            $permintaan->update([
                'laporan_id' => $isRevisi ? null : $permintaan->laporan_id,
                'status' => $isRevisi ? PermintaanLaporan::STATUS_DIKERJAKAN : ($isSelesai ? PermintaanLaporan::STATUS_SELESAI : PermintaanLaporan::STATUS_PEMERIKSAAN),
                'selesai_at' => $isSelesai ? now() : null,
            ]);
        }

        return back()->with('status', 'Status laporan berhasil diperbarui menjadi '.$laporan->status.'.');
    }

    public function destroy(Request $request, Laporan $laporan): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);

        $isPenerimaLaporan = (int) $laporan->tujuan_satuan_id === (int) $satuan->id;
        $isPimpinanRiwayatSatlak = in_array(strtoupper((string) $satuan->kode), ['DANPUS', 'WADAN'], true)
            && $laporan->satuan
            && $laporan->satuan->kategori === Satuan::KATEGORI_SATLAK;

        abort_unless($isPenerimaLaporan || $isPimpinanRiwayatSatlak, 403);

        if ($laporan->lampiran_path) {
            Storage::disk('public')->delete($laporan->lampiran_path);
        }
        $laporan->delete();

        return back()->with('status', 'Laporan berhasil dihapus dari riwayat penerimaan.');
    }
}
