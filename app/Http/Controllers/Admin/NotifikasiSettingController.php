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

class NotifikasiSettingController extends Controller
{
    /**
     * Nyalakan/matikan fitur push notification utk SELURUH pengguna
     * sekaligus (menu Admin -> Setelan -> Notifikasi). Tidak menyentuh
     * notifikasi lonceng in-app (channel database) -- itu tetap jalan
     * seperti biasa, yang dimatikan cuma sisi push (notifikasi OS di
     * luar sistem).
     */
    public function updateToggle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'aktif' => ['required', 'boolean'],
        ]);

        $pengaturan = Pengaturan::current();
        $pengaturan->update(['notifikasi_push_aktif' => $validated['aktif']]);

        ActivityLog::catat(
            $validated['aktif'] ? 'setelan.notifikasi.aktifkan' : 'setelan.notifikasi.matikan',
            $validated['aktif']
                ? 'Mengaktifkan fitur push notification untuk seluruh pengguna.'
                : 'Mematikan fitur push notification untuk seluruh pengguna.'
        );

        return back()->with(
            'status',
            $validated['aktif'] ? 'Fitur notifikasi push diaktifkan.' : 'Fitur notifikasi push dimatikan.'
        );
    }

    /**
     * Kirim pengumuman manual ke SEMUA pengguna terdaftar -- masuk ke
     * lonceng in-app semua orang, dan ke notifikasi OS (push) utk yang
     * sudah mengizinkan & fitur push global sedang aktif.
     */
    public function broadcast(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:100'],
            'pesan' => ['required', 'string', 'max:500'],
        ], [
            'judul.required' => 'Judul pengumuman wajib diisi.',
            'pesan.required' => 'Isi pesan wajib diisi.',
            'judul.max' => 'Judul maksimal 100 karakter.',
            'pesan.max' => 'Isi pesan maksimal 500 karakter.',
        ]);

        $penerima = User::all();

        NotificationFacade::send($penerima, new PengumumanBroadcastAdmin($validated['judul'], $validated['pesan']));

        ActivityLog::catat(
            'setelan.notifikasi.broadcast',
            "Mengirim pengumuman \"{$validated['judul']}\" ke {$penerima->count()} pengguna.",
            null,
            ['judul' => $validated['judul'], 'pesan' => $validated['pesan'], 'jumlah_penerima' => $penerima->count()]
        );

        return back()->with('status', "Pengumuman terkirim ke {$penerima->count()} pengguna.");
    }
}
