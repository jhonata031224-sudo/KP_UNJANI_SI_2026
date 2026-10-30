<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Satuan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Simpan hak akses modul untuk satu satuan (role). Modul yang tidak
     * tersedia untuk role tersebut selalu dibuang dari request, sehingga
     * konfigurasi permission tidak dapat melampaui matriks akses role.
     *
     * Mendukung dua mode respons:
     *  - AJAX / fetch (header X-Requested-With: XMLHttpRequest) → JSON
     *  - Form biasa → redirect back() seperti semula
     */
    public function update(Request $request, Satuan $satuan): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'in:'.implode(',', array_keys(Satuan::MODUL_HAK_AKSES))],
        ]);

        $allowed = Satuan::modulHakAksesKeysUntukRole($satuan->kode);

        /* Filter nilai kosong yang mungkin dikirim saat semua modul di-uncheck */
        $raw = array_filter($validated['permissions'] ?? [], fn ($v) => $v !== '');
        $permissions = array_values(array_intersect($raw, $allowed));

        $satuan->update(['permissions' => $permissions]);

        ActivityLog::catat('role.update', "Memperbarui hak akses modul untuk satuan \"{$satuan->nama}\".");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'      => true,
                'satuan'  => $satuan->nama,
                'saved'   => count($permissions),
            ]);
        }

        return back()->with('status', "Hak akses \"{$satuan->nama}\" berhasil disimpan.");
    }
}
