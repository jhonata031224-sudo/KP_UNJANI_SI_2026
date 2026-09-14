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
     * Fitur push notification WAJIB selalu aktif untuk seluruh pengguna --
     * dianggap krusial (mis. notifikasi kendala/laporan darurat) sehingga
     * tidak boleh dimatikan siapapun, termasuk Admin. Endpoint ini sengaja
     * DIPERTAHANKAN (bukan dihapus) supaya request lama/eksternal ke rute
     * ini tidak 404, tapi apapun yang dikirim akan selalu dipaksa menjadi
     * aktif -- efeknya toggle "mati" sudah tidak punya jalan lagi, baik
     * dari UI (lihat admin.blade.php, switch sekarang dikunci/disabled)
     * maupun dari request manual ke rute ini.
     */
    public function updateToggle(Request $request): RedirectResponse
    {
        $request->validate([
            'aktif' => ['sometimes', 'boolean'],
        ]);

        $pengaturan = Pengaturan::current();
        if (! $pengaturan->notifikasi_push_aktif) {
            $pengaturan->update(['notifikasi_push_aktif' => true]);
        }

        return back()->with(
            'status',
            'Fitur push notifikasi bersifat wajib aktif dan tidak bisa dimatikan.'
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
            'kategori' => ['required', 'string', 'in:maintenance,keterangan'],
        ], [
            'judul.required' => 'Judul pengumuman wajib diisi.',
            'pesan.required' => 'Isi pesan wajib diisi.',
            'judul.max' => 'Judul maksimal 100 karakter.',
            'pesan.max' => 'Isi pesan maksimal 500 karakter.',
            'kategori.required' => 'Kategori pengumuman wajib dipilih.',
            'kategori.in' => 'Kategori pengumuman tidak valid.',
        ]);

        $penerima = User::all();

        NotificationFacade::send($penerima, new PengumumanBroadcastAdmin($validated['judul'], $validated['pesan'], $validated['kategori']));

        ActivityLog::catat(
            'setelan.notifikasi.broadcast',
            "Mengirim pengumuman \"{$validated['judul']}\" ke {$penerima->count()} pengguna.",
            null,
            ['judul' => $validated['judul'], 'pesan' => $validated['pesan'], 'kategori' => $validated['kategori'], 'jumlah_penerima' => $penerima->count()]
        );

        return back()->with('status', "Pengumuman terkirim ke {$penerima->count()} pengguna.");
    }
}
