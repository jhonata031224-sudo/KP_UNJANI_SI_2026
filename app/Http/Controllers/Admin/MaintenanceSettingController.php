<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use App\Models\Satuan;
use App\Notifications\PengumumanBroadcastAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class MaintenanceSettingController extends Controller
{
    /**
     * Saklar Mode Maintenance -- efeknya LANGSUNG nyata di seluruh sistem
     * lewat EnforceMaintenanceMode (middleware grup 'web', lihat
     * bootstrap/app.php): begitu aktif, semua request perubahan data
     * (POST/PUT/PATCH/DELETE) dari pengguna TARGET ditolak di server,
     * terlepas dari tombol di frontend didisable atau tidak. Admin (kode
     * satuan ADMIN) selalu dikecualikan supaya tidak self-lockout, sama
     * seperti pola pengecualian ADMIN di Satuan::modulAktif().
     *
     * TARGET maintenance:
     *  - 'semua'  : seluruh pengguna non-Admin (perbaikan sistem menyeluruh).
     *  - 'satuan' : hanya pengguna pada satuan yang dipilih (mis. hanya
     *               Danpus saat role itu sedang dikembangkan/diperbaiki);
     *               pengguna satuan lain tetap bekerja normal.
     * Disimpan di pengaturans.mode_maintenance_satuan_ids (kosong = semua).
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'aktif' => ['sometimes', 'boolean'],
            'pesan' => ['nullable', 'string', 'max:500'],
            'target' => ['nullable', 'in:semua,satuan'],
            'satuan_ids' => ['nullable', 'array'],
            'satuan_ids.*' => ['integer', 'exists:satuans,id'],
        ], [
            'pesan.max' => 'Pesan maintenance maksimal 500 karakter.',
            'target.in' => 'Target maintenance tidak valid.',
            'satuan_ids.*.exists' => 'Ada satuan target yang tidak ditemukan.',
        ]);

        $pengaturan = Pengaturan::current();
        $aktifSebelumnya = (bool) $pengaturan->mode_maintenance_aktif;
        $targetSebelumnya = $pengaturan->maintenanceTargetSatuanIds();
        $aktifBaru = $request->boolean('aktif');

        // Target baru: 'semua' (default, juga untuk request/form lama tanpa
        // field target) -> array kosong. 'satuan' -> id satuan terpilih,
        // satuan ADMIN dibuang karena Admin memang tidak pernah terkena.
        $target = $validated['target'] ?? 'semua';
        $targetBaru = [];
        if ($target === 'satuan') {
            $targetBaru = Satuan::whereIn('id', $validated['satuan_ids'] ?? [])
                ->whereRaw('UPPER(TRIM(kode)) != ?', ['ADMIN'])
                ->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

            if ($aktifBaru && $targetBaru === []) {
                return back()
                    ->withErrors(['satuan_ids' => 'Pilih minimal satu satuan sebagai target maintenance, atau pilih "Semua pengguna".'])
                    ->withInput();
            }
        }

        $pengaturan->update([
            'mode_maintenance_aktif' => $aktifBaru,
            'mode_maintenance_pesan' => $validated['pesan'] ?? null,
            'mode_maintenance_satuan_ids' => $targetBaru === [] ? null : $targetBaru,
        ]);

        // Notifikasi (lonceng in-app + push + modal "Pemeliharaan Sistem",
        // lihat partials/notification-controls.blade.php). Ini SATU-SATUNYA
        // jalur pengumuman pemeliharaan (kategori 'maintenance' sudah dihapus
        // dari menu Notifikasi). Dikirim berdasarkan SIAPA yang statusnya
        // BERUBAH, bukan tiap form disimpan -- jadi Admin yang cuma mengedit
        // teks pesan tidak membanjiri lonceng, dan saat target diubah:
        //  - pengguna yang BARU terkena  -> "Sistem Dalam Pemeliharaan"
        //  - pengguna yang TIDAK LAGI terkena -> "Pemeliharaan Sistem Selesai"
        //  - pengguna di luar target tidak pernah diganggu.
        $terdampakSebelum = $aktifSebelumnya ? Pengaturan::penggunaTerdampak($targetSebelumnya) : collect();
        $terdampakSesudah = $aktifBaru ? Pengaturan::penggunaTerdampak($targetBaru) : collect();

        $baruTerkena = $terdampakSesudah->diffKeys($terdampakSebelum);
        $selesai = $terdampakSebelum->diffKeys($terdampakSesudah);

        if ($baruTerkena->isNotEmpty()) {
            // 'maintenance' -> ditandai belum dibaca & bisa diklik buka modal.
            NotificationFacade::send(
                $baruTerkena->values(),
                new PengumumanBroadcastAdmin('Sistem Dalam Pemeliharaan', $pengaturan->fresh()->pesanMaintenance(), 'maintenance')
            );
        }

        if ($selesai->isNotEmpty()) {
            // 'keterangan' -> info biasa, tanpa titik belum-dibaca & tidak clickable.
            NotificationFacade::send(
                $selesai->values(),
                new PengumumanBroadcastAdmin('Pemeliharaan Sistem Selesai', 'Pemeliharaan telah selesai. Sistem kembali normal dan semua fitur dapat digunakan seperti biasa.', 'keterangan')
            );
        }

        $labelTarget = $targetBaru === []
            ? 'seluruh pengguna non-Admin'
            : 'satuan: '.Satuan::whereIn('id', $targetBaru)->orderBy('nama')->pluck('nama')->join(', ');

        ActivityLog::catat(
            'setelan.maintenance.toggle',
            $aktifBaru
                ? "Mengaktifkan Mode Maintenance untuk {$labelTarget}. Aksi perubahan data pada target dinonaktifkan sementara."
                : 'Menonaktifkan Mode Maintenance. Sistem kembali berjalan normal untuk semua pengguna.',
            null,
            [
                'mode_maintenance_aktif' => $aktifBaru,
                'mode_maintenance_pesan' => $validated['pesan'] ?? null,
                'mode_maintenance_satuan_ids' => $targetBaru,
            ]
        );

        return back()->with(
            'status',
            $aktifBaru
                ? "Mode Maintenance diaktifkan untuk {$labelTarget}. Pengguna target tidak bisa melakukan perubahan data sampai dimatikan kembali."
                : 'Mode Maintenance dinonaktifkan. Sistem kembali normal untuk semua pengguna.'
        );
    }
}
