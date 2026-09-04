<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Menu Admin -> Kelola Sistem -> Struktur Organisasi. Admin mengunggah
// SATU gambar bagan struktur organisasi (bukan dibangun manual pakai
// HTML/CSS) yang lalu ditampilkan apa adanya di dashboard Kasansi,
// menu Lainnya -> Struktur Organisasi (lihat partial
// siberad.dashboards.partials.lainnya-kasansi).
class StrukturOrganisasiController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'struktur_organisasi' => ['required', 'image', 'max:8192'],
        ], [
            'struktur_organisasi.required' => 'Pilih file gambar struktur organisasi terlebih dahulu.',
            'struktur_organisasi.image' => 'File yang diunggah harus berupa gambar (JPG, PNG, atau WEBP).',
            'struktur_organisasi.max' => 'Ukuran gambar maksimal 8 MB.',
        ]);

        $pengaturan = Pengaturan::current();

        if ($pengaturan->struktur_organisasi_path) {
            Storage::disk('public')->delete($pengaturan->struktur_organisasi_path);
        }

        $path = $request->file('struktur_organisasi')->store('struktur-organisasi', 'public');
        $pengaturan->update(['struktur_organisasi_path' => $path]);

        ActivityLog::catat('struktur-organisasi.update', 'Memperbarui gambar Struktur Organisasi.');

        return back()->with('status', 'Gambar Struktur Organisasi berhasil disimpan.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $pengaturan = Pengaturan::current();

        if ($pengaturan->struktur_organisasi_path) {
            Storage::disk('public')->delete($pengaturan->struktur_organisasi_path);
            $pengaturan->update(['struktur_organisasi_path' => null]);
            ActivityLog::catat('struktur-organisasi.destroy', 'Menghapus gambar Struktur Organisasi.');
        }

        return back()->with('status', 'Gambar Struktur Organisasi berhasil dihapus.');
    }
}
