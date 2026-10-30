<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Personel;
use App\Models\PersonelMutasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PersonelMutasiController extends Controller
{
    /**
     * Ajukan mutasi personel ke satuan/jabatan tujuan baru. Status personel
     * langsung diubah menjadi "Mutasi" sampai SK diproses.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'personel_id' => ['required', 'exists:personels,id'],
            'satuan_tujuan_id' => ['required', 'exists:satuans,id'],
            'jabatan_tujuan_id' => ['nullable', 'exists:jabatans,id'],
            'nomor_sk' => ['nullable', 'string', 'max:255'],
            'tanggal_mutasi' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $personel = Personel::findOrFail($validated['personel_id']);

        $mutasi = PersonelMutasi::create([
            'personel_id' => $personel->id,
            'satuan_asal_id' => $personel->satuan_id,
            'satuan_tujuan_id' => $validated['satuan_tujuan_id'],
            'jabatan_asal_id' => $personel->jabatan_id,
            'jabatan_tujuan_id' => $validated['jabatan_tujuan_id'] ?? null,
            'nomor_sk' => $validated['nomor_sk'] ?? null,
            'tanggal_mutasi' => $validated['tanggal_mutasi'],
            'keterangan' => $validated['keterangan'] ?? null,
            'status' => PersonelMutasi::STATUS_MENUNGGU,
            'diajukan_oleh' => $request->user()->id,
        ]);

        $personel->update(['status' => Personel::STATUS_MUTASI]);

        ActivityLog::catat('personel-mutasi.create', "Mengajukan mutasi personel \"{$personel->nama}\".", $request->user(), [
            'personel_id' => $personel->id,
            'personel_mutasi_id' => $mutasi->id,
        ]);

        return back()->with('status', 'Pengajuan mutasi personel berhasil disimpan.');
    }

    /**
     * Proses SK mutasi — kalau disetujui, satuan/jabatan aktif personel
     * langsung diperbarui mengikuti tujuan mutasi.
     */
    public function update(Request $request, PersonelMutasi $mutasi): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Menunggu SK,Disetujui,Ditolak'],
            'nomor_sk' => ['nullable', 'string', 'max:255'],
        ]);

        $mutasi->update([
            'status' => $validated['status'],
            'nomor_sk' => $validated['nomor_sk'] ?? $mutasi->nomor_sk,
        ]);

        $personel = $mutasi->personel;

        if ($validated['status'] === PersonelMutasi::STATUS_DISETUJUI) {
            $personel->update([
                'satuan_id' => $mutasi->satuan_tujuan_id,
                'jabatan_id' => $mutasi->jabatan_tujuan_id ?? $personel->jabatan_id,
                'status' => Personel::STATUS_AKTIF,
            ]);
        } elseif ($validated['status'] === PersonelMutasi::STATUS_DITOLAK) {
            $personel->update(['status' => Personel::STATUS_AKTIF]);
        }

        ActivityLog::catat('personel-mutasi.update', "Memperbarui status mutasi personel \"{$personel->nama}\" menjadi {$validated['status']}.", $request->user(), [
            'personel_id' => $personel->id,
            'personel_mutasi_id' => $mutasi->id,
        ]);

        return back()->with('status', 'Status mutasi berhasil diperbarui.');
    }

    public function destroy(Request $request, PersonelMutasi $mutasi): RedirectResponse
    {
        $namaPersonel = $mutasi->personel?->nama ?? '-';
        $personelId = $mutasi->personel_id;
        $mutasi->delete();

        ActivityLog::catat('personel-mutasi.delete', "Menghapus data mutasi personel \"{$namaPersonel}\".", $request->user(), [
            'personel_id' => $personelId,
        ]);

        return back()->with('status', 'Data mutasi berhasil dihapus.');
    }
}
