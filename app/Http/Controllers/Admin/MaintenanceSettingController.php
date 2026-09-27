<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenanceSettingController extends Controller
{
    /**
     * Saklar Mode Maintenance -- efeknya LANGSUNG nyata di seluruh sistem
     * lewat EnforceMaintenanceMode (middleware global, lihat
     * bootstrap/app.php): begitu aktif, semua request perubahan data
     * (POST/PUT/PATCH/DELETE) dari pengguna non-Admin ditolak di server,
     * terlepas dari tombol di frontend didisable atau tidak. Admin
     * (kode satuan ADMIN) selalu dikecualikan supaya tidak self-lockout,
     * sama seperti pola pengecualian ADMIN di Satuan::modulAktif().
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'aktif' => ['sometimes', 'boolean'],
            'pesan' => ['nullable', 'string', 'max:500'],
        ], [
            'pesan.max' => 'Pesan maintenance maksimal 500 karakter.',
        ]);

        $pengaturan = Pengaturan::current();
        $aktifBaru = $request->boolean('aktif');

        $pengaturan->update([
            'mode_maintenance_aktif' => $aktifBaru,
            'mode_maintenance_pesan' => $validated['pesan'] ?? null,
        ]);

        ActivityLog::catat(
            'setelan.maintenance.toggle',
            $aktifBaru
                ? 'Mengaktifkan Mode Maintenance. Aksi perubahan data pengguna non-Admin dinonaktifkan sementara.'
                : 'Menonaktifkan Mode Maintenance. Sistem kembali berjalan normal untuk semua pengguna.',
            null,
            ['mode_maintenance_aktif' => $aktifBaru, 'mode_maintenance_pesan' => $validated['pesan'] ?? null]
        );

        return back()->with(
            'status',
            $aktifBaru
                ? 'Mode Maintenance diaktifkan. Pengguna non-Admin tidak bisa melakukan perubahan data sampai dimatikan kembali.'
                : 'Mode Maintenance dinonaktifkan. Sistem kembali normal untuk semua pengguna.'
        );
    }
}
