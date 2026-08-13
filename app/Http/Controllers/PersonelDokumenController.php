<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PersonelDokumen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PersonelDokumenController extends Controller
{
    /**
     * Unggah dokumen administrasi personel (mis. SK, KTP, Ijazah) — dipakai
     * fitur "Upload Dokumen" pada Administrasi Personel Binfung.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'personel_id' => ['required', 'exists:personels,id'],
            'jenis_dokumen' => ['required', 'string', 'max:255'],
            'dokumen' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $file = $request->file('dokumen');
        $path = $file->store('dokumen-personel', 'public');

        $dokumen = PersonelDokumen::create([
            'personel_id' => $validated['personel_id'],
            'jenis_dokumen' => $validated['jenis_dokumen'],
            'nama_file' => $file->getClientOriginalName(),
            'path' => $path,
            'diunggah_oleh' => $request->user()->id,
        ]);

        ActivityLog::catat('personel-dokumen.upload', "Mengunggah dokumen \"{$validated['jenis_dokumen']}\" ({$dokumen->nama_file}) untuk personel.", $request->user(), [
            'personel_id' => $validated['personel_id'],
            'personel_dokumen_id' => $dokumen->id,
        ]);

        return back()->with('status', 'Dokumen personel berhasil diunggah.');
    }

    public function destroy(Request $request, PersonelDokumen $dokumen): RedirectResponse
    {
        $namaFile = $dokumen->nama_file;
        $jenisDokumen = $dokumen->jenis_dokumen;
        $personelId = $dokumen->personel_id;
        Storage::disk('public')->delete($dokumen->path);
        $dokumen->delete();

        ActivityLog::catat('personel-dokumen.delete', "Menghapus dokumen \"{$jenisDokumen}\" ({$namaFile}) personel.", $request->user(), [
            'personel_id' => $personelId,
        ]);

        return back()->with('status', 'Dokumen personel berhasil dihapus.');
    }
}
