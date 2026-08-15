<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilFotoController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
        ], [
            'foto.image' => 'Hanya format JPG, PNG, atau WEBP yang diperbolehkan.',
            'foto.mimes' => 'Hanya format JPG, PNG, atau WEBP yang diperbolehkan.',
            'foto.max' => 'Ukuran foto maksimal 10 MB.',
        ]);

        $user = $request->user();

        if ($user->foto_path) {
            Storage::disk('public')->delete($user->foto_path);
        }

        $user->update([
            'foto_path' => $request->file('foto')->store('foto-profil', 'public'),
        ]);

        ActivityLog::catat('profil.ganti-foto', 'Mengganti foto profil.', $user);

        return back()->with('status', 'Foto profil berhasil diperbarui.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->foto_path) {
            Storage::disk('public')->delete($user->foto_path);
            $user->update(['foto_path' => null]);
            ActivityLog::catat('profil.hapus-foto', 'Menghapus foto profil.', $user);
        }

        return back()->with('status', 'Foto profil berhasil dihapus.');
    }
}
