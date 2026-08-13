<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Pangkat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PangkatController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:pangkats,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:Tamtama,Bintara,Perwira'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);

        $pangkat = Pangkat::create($validated);

        ActivityLog::catat('pangkat.create', "Menambahkan data pangkat \"{$pangkat->nama}\".", $request->user(), [
            'pangkat_id' => $pangkat->id,
        ]);

        return back()->with('status', 'Data pangkat berhasil ditambahkan.');
    }

    public function update(Request $request, Pangkat $pangkat): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:pangkats,kode,'.$pangkat->id],
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:Tamtama,Bintara,Perwira'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);

        $pangkat->update($validated);

        ActivityLog::catat('pangkat.update', "Memperbarui data pangkat \"{$pangkat->nama}\".", $request->user(), [
            'pangkat_id' => $pangkat->id,
        ]);

        return back()->with('status', 'Data pangkat berhasil diperbarui.');
    }

    public function destroy(Request $request, Pangkat $pangkat): RedirectResponse
    {
        if ($pangkat->personels()->exists()) {
            return back()->with('error', 'Pangkat masih dipakai oleh data personel dan tidak bisa dihapus.');
        }

        $nama = $pangkat->nama;
        $pangkatId = $pangkat->id;
        $pangkat->delete();

        ActivityLog::catat('pangkat.delete', "Menghapus data pangkat \"{$nama}\".", $request->user(), [
            'pangkat_id' => $pangkatId,
        ]);

        return back()->with('status', 'Data pangkat berhasil dihapus.');
    }
}
