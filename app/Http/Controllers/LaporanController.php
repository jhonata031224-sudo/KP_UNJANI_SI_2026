<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Laporan;
use App\Models\PermintaanLaporan;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanBaruDiterima;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'progres' => ['nullable', 'integer', 'min:0', 'max:100', 'required_with:permintaan_laporan_id'],
            'kendala' => ['nullable', 'string', 'max:5000'],
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
            abort_unless((int) $permintaan->pembuat->satuan_id === (int) $tujuan->id, 422, 'Tujuan laporan tidak sesuai dengan permintaan laporan.');
        }

        $progresValue = $permintaan ? (int) $validated['progres'] : 100;
        $laporan = null;

        DB::transaction(function () use (&$laporan, &$permintaan, $progresValue, $validated, $satuanAsal, $user, $tujuan, $request) {
            if ($permintaan) {
                $permintaan = PermintaanLaporan::whereKey($permintaan->id)->lockForUpdate()->first();
                abort_if($permintaan->laporan_id, 422, 'Permintaan laporan tersebut sudah memiliki laporan yang menunggu pemeriksaan atau sudah diputuskan.');
                abort_if($permintaan->status === PermintaanLaporan::STATUS_DIBATALKAN, 422, 'Permintaan laporan ini sudah dibatalkan oleh Pimpinan.');
                abort_if($progresValue < $permintaan->progres, 422, 'Persentase progres tidak boleh lebih kecil dari progres terakhir ('.$permintaan->progres.'%).');
            }

            $lampiranPath = $request->hasFile('lampiran')
                ? $request->file('lampiran')->store('lampiran-laporan', 'public')
                : null;

            $statusLaporan = ($permintaan && $progresValue < 100) ? Laporan::STATUS_PROGRES : 'Menunggu';

            $laporan = Laporan::create([
                'satuan_id' => $satuanAsal->id,
                'user_id' => $user->id,
                'tujuan_satuan_id' => $tujuan->id,
                'permintaan_laporan_id' => $permintaan?->id,
                'proyek' => $validated['proyek'] ?? null,
                'perihal' => $validated['perihal'],
                'deskripsi' => $validated['deskripsi'],
                'kendala' => $validated['kendala'] ?? null,
                'progres' => $progresValue,
                'prioritas' => $validated['prioritas'],
                'lampiran_path' => $lampiranPath,
                'status' => $statusLaporan,
            ]);

            if ($permintaan) {
                $permintaan->progres = $progresValue;
                if ($progresValue >= 100) {
                    $permintaan->laporan_id = $laporan->id;
                    $permintaan->status = PermintaanLaporan::STATUS_PEMERIKSAAN;
                    $permintaan->selesai_at = null;
                }
                $permintaan->save();
            }
        });

        foreach (User::where('satuan_id', $tujuan->id)->get() as $penerima) {
            $penerima->notify(new LaporanBaruDiterima($laporan));
        }

        ActivityLog::catat('laporan.create', "Mengirim laporan \"{$laporan->perihal}\" ke {$tujuan->nama}.", $user, [
            'laporan_id' => $laporan->id,
            'tujuan_satuan' => $tujuan->nama,
            'prioritas' => $laporan->prioritas,
        ]);

        return back()->with('status', 'Laporan berhasil dikirim ke Pimpinan.');
    }

    // Edit checkpoint progres yang SUDAH terkirim tapi BELUM final (masih
    // status Laporan::STATUS_PROGRES) -- UPDATE row yang sama, bukan bikin
    // row baru, supaya satuan bisa betulkan salah ketik/salah persentase
    // tanpa nambah entry riwayat progres baru.
    public function updateProgres(Request $request, Laporan $laporan): RedirectResponse
    {
        $validated = $request->validate([
            'deskripsi' => ['required', 'string', 'max:10000'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'progres' => ['required', 'integer', 'min:0', 'max:100'],
            'kendala' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;
        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');
        abort_unless((int) $laporan->satuan_id === (int) $satuanAsal->id, 403, 'Anda tidak berhak mengedit laporan ini.');
        abort_unless($laporan->status === Laporan::STATUS_PROGRES, 422, 'Hanya checkpoint progres yang belum final yang dapat diedit.');

        $progresValue = (int) $validated['progres'];

        DB::transaction(function () use (&$laporan, $progresValue, $validated, $request) {
            $laporan = Laporan::whereKey($laporan->id)->lockForUpdate()->first();
            abort_unless($laporan->status === Laporan::STATUS_PROGRES, 422, 'Checkpoint ini sudah final dan tidak dapat diedit.');

            $permintaan = $laporan->permintaan_laporan_id
                ? PermintaanLaporan::whereKey($laporan->permintaan_laporan_id)->lockForUpdate()->first()
                : null;

            if ($permintaan) {
                abort_if($permintaan->status === PermintaanLaporan::STATUS_DIBATALKAN, 422, 'Permintaan laporan ini sudah dibatalkan oleh Pimpinan.');
                $progresCheckpointSebelumnya = Laporan::where('permintaan_laporan_id', $permintaan->id)
                    ->where('id', '!=', $laporan->id)
                    ->max('progres') ?? 0;
                abort_if($progresValue < $progresCheckpointSebelumnya, 422, 'Persentase progres tidak boleh lebih kecil dari checkpoint sebelumnya ('.$progresCheckpointSebelumnya.'%).');
            }

            $lampiranPath = $laporan->lampiran_path;
            if ($request->hasFile('lampiran')) {
                if ($lampiranPath) {
                    Storage::disk('public')->delete($lampiranPath);
                }
                $lampiranPath = $request->file('lampiran')->store('lampiran-laporan', 'public');
            }

            $laporan->update([
                'deskripsi' => $validated['deskripsi'],
                'kendala' => $validated['kendala'] ?? null,
                'progres' => $progresValue,
                'prioritas' => $validated['prioritas'],
                'lampiran_path' => $lampiranPath,
                'status' => ($permintaan && $progresValue < 100) ? Laporan::STATUS_PROGRES : 'Menunggu',
            ]);

            if ($permintaan) {
                // Progres permintaan dihitung ulang sebagai nilai TERTINGGI
                // antar semua checkpoint (bukan langsung ditimpa nilai baru),
                // supaya kalau yang diedit itu checkpoint LAMA (bukan yang
                // terakhir), progres permintaan tidak ikut mundur.
                $progresCheckpointSebelumnya = Laporan::where('permintaan_laporan_id', $permintaan->id)
                    ->where('id', '!=', $laporan->id)
                    ->max('progres') ?? 0;
                $permintaan->progres = max($progresValue, $progresCheckpointSebelumnya);
                if ($progresValue >= 100) {
                    $permintaan->laporan_id = $laporan->id;
                    $permintaan->status = PermintaanLaporan::STATUS_PEMERIKSAAN;
                    $permintaan->selesai_at = null;
                }
                $permintaan->save();
            }
        });

        ActivityLog::catat('laporan.update-progres', "Mengedit checkpoint progres laporan \"{$laporan->perihal}\" menjadi {$laporan->progres}%.", $user, [
            'laporan_id' => $laporan->id,
            'progres' => $laporan->progres,
        ]);

        return back()->with('status', 'Progres laporan berhasil diperbarui.');
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
        abort_if($laporan->status === Laporan::STATUS_PROGRES, 422, 'Baris ini adalah catatan progres, bukan laporan final — tidak dapat diputuskan.');

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

        ActivityLog::catat('laporan.status', "Memperbarui status laporan \"{$laporan->perihal}\" menjadi {$laporan->status}.", $user, [
            'laporan_id' => $laporan->id,
            'status' => $laporan->status,
        ]);

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
        abort_if($laporan->status === Laporan::STATUS_PROGRES, 422, 'Catatan progres tidak dapat dihapus.');

        if ($laporan->lampiran_path) {
            Storage::disk('public')->delete($laporan->lampiran_path);
        }
        $perihal = $laporan->perihal;
        $laporanId = $laporan->id;
        $laporan->delete();

        ActivityLog::catat('laporan.delete', "Menghapus laporan \"{$perihal}\" dari riwayat penerimaan.", $user, [
            'laporan_id' => $laporanId,
        ]);

        return back()->with('status', 'Laporan berhasil dihapus dari riwayat penerimaan.');
    }
}
