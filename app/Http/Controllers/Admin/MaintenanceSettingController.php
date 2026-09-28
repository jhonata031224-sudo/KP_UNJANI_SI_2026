<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use App\Models\User;
use App\Notifications\PengumumanBroadcastAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification as NotificationFacade;

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
        $aktifSebelumnya = (bool) $pengaturan->mode_maintenance_aktif;
        $aktifBaru = $request->boolean('aktif');

        $pengaturan->update([
            'mode_maintenance_aktif' => $aktifBaru,
            'mode_maintenance_pesan' => $validated['pesan'] ?? null,
        ]);

        // Sambungkan ke sistem notifikasi pengumuman yang sudah ada
        // (lonceng in-app + push + modal "Pemeliharaan Sistem", lihat
        // NotifikasiSettingController::broadcast & partials/
        // notification-controls.blade.php). HANYA dikirim saat status
        // BERUBAH (mati->hidup atau hidup->mati), bukan tiap form disimpan,
        // supaya Admin yang cuma mengedit teks pesan tidak membanjiri
        // lonceng pengguna. Penerima: semua pengguna NON-Admin (Admin
        // tidak terdampak maintenance jadi tidak perlu diberi tahu).
        if ($aktifBaru !== $aktifSebelumnya) {
            $penerima = User::whereHas('satuan', fn ($q) => $q->whereRaw('UPPER(TRIM(kode)) != ?', ['ADMIN']))->get();

            if ($penerima->isNotEmpty()) {
                NotificationFacade::send(
                    $penerima,
                    $aktifBaru
                        // 'maintenance' -> ditandai belum dibaca & bisa diklik buka modal.
                        ? new PengumumanBroadcastAdmin('Sistem Dalam Pemeliharaan', $pengaturan->fresh()->pesanMaintenance(), 'maintenance')
                        // 'keterangan' -> info biasa, tanpa titik belum-dibaca & tidak clickable.
                        : new PengumumanBroadcastAdmin('Pemeliharaan Sistem Selesai', 'Pemeliharaan telah selesai. Sistem kembali normal dan semua fitur dapat digunakan seperti biasa.', 'keterangan')
                );
            }
        }

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
