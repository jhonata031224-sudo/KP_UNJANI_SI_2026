<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Jabatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JabatanController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', 'unique:jabatans,nama'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ]);

        $jabatan = Jabatan::create($validated);

        ActivityLog::catat('jabatan.create', "Menambahkan data jabatan \"{$jabatan->nama}\".", $request->user(), [
            'jabatan_id' => $jabatan->id,
        ]);

        return back()->with('status', 'Data jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, Jabatan $jabatan): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', 'unique:jabatans,nama,'.$jabatan->id],
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ]);

        $jabatan->update($validated);

        ActivityLog::catat('jabatan.update', "Memperbarui data jabatan \"{$jabatan->nama}\".", $request->user(), [
            'jabatan_id' => $jabatan->id,
        ]);

        return back()->with('status', 'Data jabatan berhasil diperbarui.');
    }

    public function destroy(Request $request, Jabatan $jabatan): RedirectResponse
    {
        if ($jabatan->personels()->exists()) {
            return back()->with('error', 'Jabatan masih dipakai oleh data personel dan tidak bisa dihapus.');
        }

        $nama = $jabatan->nama;
        $jabatanId = $jabatan->id;
        $jabatan->delete();

        ActivityLog::catat('jabatan.delete', "Menghapus data jabatan \"{$nama}\".", $request->user(), [
            'jabatan_id' => $jabatanId,
        ]);

        return back()->with('status', 'Data jabatan berhasil dihapus.');
    }
}
