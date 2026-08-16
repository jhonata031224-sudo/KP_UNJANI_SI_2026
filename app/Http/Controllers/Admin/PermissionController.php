<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Satuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Simpan hak akses modul untuk satu satuan (role). Modul yang tidak
     * tersedia untuk role tersebut selalu dibuang dari request, sehingga
     * konfigurasi permission tidak dapat melampaui matriks akses role.
     */
    public function update(Request $request, Satuan $satuan): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'in:'.implode(',', array_keys(Satuan::MODUL_HAK_AKSES))],
        ]);

        $allowed = Satuan::modulHakAksesKeysUntukRole($satuan->kode);
        $permissions = array_values(array_intersect($validated['permissions'] ?? [], $allowed));

        $satuan->update(['permissions' => $permissions]);

        ActivityLog::catat('role.update', "Memperbarui hak akses modul untuk satuan \"{$satuan->nama}\".");

        return back()->with('status', "Hak akses \"{$satuan->nama}\" berhasil disimpan.");
    }
}
