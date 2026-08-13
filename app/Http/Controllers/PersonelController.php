<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Personel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PersonelController extends Controller
{
    /**
     * Tambah personel baru — dipakai fitur "Tambah/Edit Personel" Binfung.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $validated['dicatat_oleh'] = $request->user()->id;

        $personel = Personel::create($validated);

        ActivityLog::catat('personel.create', "Menambahkan data personel \"{$personel->nama}\" ({$personel->nrp}).", $request->user(), [
            'personel_id' => $personel->id,
        ]);

        return back()->with('status', 'Data personel berhasil ditambahkan.');
    }

    /**
     * Perbarui data personel yang sudah ada.
     */
    public function update(Request $request, Personel $personel): RedirectResponse
    {
        $validated = $this->validated($request, $personel);

        $personel->update($validated);

        ActivityLog::catat('personel.update', "Memperbarui data personel \"{$personel->nama}\" ({$personel->nrp}).", $request->user(), [
            'personel_id' => $personel->id,
        ]);

        return back()->with('status', 'Data personel berhasil diperbarui.');
    }

    /**
     * Hapus data personel beserta riwayat mutasi & dokumennya (cascade).
     */
    public function destroy(Request $request, Personel $personel): RedirectResponse
    {
        $nama = $personel->nama;
        $nrp = $personel->nrp;
        $personelId = $personel->id;
        $personel->delete();

        ActivityLog::catat('personel.delete', "Menghapus data personel \"{$nama}\" ({$nrp}).", $request->user(), [
            'personel_id' => $personelId,
        ]);

        return back()->with('status', 'Data personel berhasil dihapus.');
    }

    private function validated(Request $request, ?Personel $personel = null): array
    {
        return $request->validate([
            'nrp' => ['required', 'string', 'max:30', 'unique:personels,nrp'.($personel ? ','.$personel->id : '')],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'pangkat_id' => ['nullable', 'exists:pangkats,id'],
            'jabatan_id' => ['nullable', 'exists:jabatans,id'],
            'satuan_id' => ['nullable', 'exists:satuans,id'],
            'status' => ['required', 'in:Aktif,Mutasi,Purna'],
            'tanggal_masuk' => ['nullable', 'date'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string'],
        ]);
    }
}
