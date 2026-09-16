<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengumuman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PengumumanController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'isi' => ['required', 'string', 'max:2000'],
        ]);

        $validated['dibuat_oleh'] = $request->user()->id;

        $pengumuman = Pengumuman::create($validated);

        ActivityLog::catat('pengumuman.store', "Membuat pengumuman baru: \"{$pengumuman->judul}\".");

        return back()->with('status', 'Pengumuman berhasil dipublikasikan.');
    }

    public function toggle(Pengumuman $pengumuman): RedirectResponse
    {
        $pengumuman->update(['aktif' => ! $pengumuman->aktif]);

        ActivityLog::catat(
            'pengumuman.toggle',
            ($pengumuman->aktif ? 'Mengaktifkan' : 'Menonaktifkan') . " pengumuman \"{$pengumuman->judul}\"."
        );

        return back()->with('status', 'Status pengumuman berhasil diperbarui.');
    }

    public function destroy(Pengumuman $pengumuman): RedirectResponse
    {
        $judul = $pengumuman->judul;
        $pengumuman->delete();

        ActivityLog::catat('pengumuman.destroy', "Menghapus pengumuman \"{$judul}\".");

        return back()->with('status', 'Pengumuman berhasil dihapus.');
    }
}
