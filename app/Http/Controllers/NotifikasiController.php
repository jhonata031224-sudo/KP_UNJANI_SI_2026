<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotifikasiController extends Controller
{
    /**
     * Dipoll berkala oleh lonceng notifikasi di navbar (semua role) buat
     * nyari notifikasi terbaru (dibaca maupun belum) + jumlah yang belum
     * dibaca, biar badge & daftar di dropdown ikut update tanpa perlu reload
     * halaman.
     *
     * Sengaja ambil SEMUA notifikasi terbaru (bukan cuma yang unread) --
     * begitu notifikasi diklik & ditandai dibaca (lihat method `baca()`),
     * dia tetap harus muncul di daftar dropdown, cuma tidak lagi dihitung
     * di badge angka. Notifikasi baru hilang dari daftar kalau memang
     * dihapus manual lewat tombol X (lihat method `hapus()`).
     */
    public function realtime(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'message' => $n->data['pesan'] ?? 'Status laporan diperbarui.',
                'time' => optional($n->created_at)->diffForHumans(),
                // Dipakai lonceng notifikasi buat langsung buka tab/section
                // yang relevan begitu notifikasinya diklik -- null kalau
                // notifikasinya memang tidak punya tujuan spesifik (mis.
                // pengumuman broadcast Admin).
                'url' => $n->data['url'] ?? null,
                // Judul & tipe khusus pengumuman broadcast Admin (lihat
                // App\Notifications\PengumumanBroadcastAdmin) -- dipakai
                // frontend buat nampilin modal penjelasan penuh begitu
                // notifikasi jenis ini diklik (bukan pindah tab/section).
                'title' => $n->data['judul'] ?? null,
                'tipe' => $n->data['tipe'] ?? null,
                // Kategori pengumuman Admin ('maintenance' / 'keterangan')
                // -- null kalau notifikasi bukan tipe pengumuman_admin, atau
                // kalau ini pengumuman lama yang dikirim sebelum kolom
                // kategori ini ada (default-nya diperlakukan sebagai
                // 'maintenance' di sisi frontend, lihat notification-
                // controls.blade.php, supaya perilaku lama tetap sama).
                'kategori' => $n->data['kategori'] ?? null,
                // Dipakai buat nge-redupin tampilan item yang sudah dibaca
                // di dropdown & buat mastiin item itu tidak ikut dihitung
                // ulang ke badge pas hasil poll ini nimpa state di klien.
                'read' => ! is_null($n->read_at),
            ])
            ->values();

        return response()->json([
            'notifications' => $notifications,
            // Sama seperti hitungSebagaiUnread() di notification-controls.
            // blade.php: pengumuman kategori 'keterangan' TIDAK pernah ikut
            // dihitung "belum dibaca" (tidak pernah bisa ditandai dibaca
            // karena memang tidak bisa diklik), supaya field ini konsisten
            // dengan badge yang dirender di client kalau suatu saat dipakai.
            'unread_count' => $user->unreadNotifications
                ->reject(fn (DatabaseNotification $n) => ($n->data['tipe'] ?? null) === 'pengumuman_admin' && ($n->data['kategori'] ?? null) === 'keterangan')
                ->count(),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Tandai satu notifikasi sudah dibaca (dipanggil begitu notifikasi
     * dengan tujuan/url diklik dari dropdown lonceng). Notifikasinya
     * sengaja TIDAK dihapus -- cuma ditandai dibaca -- supaya isinya tetap
     * kelihatan di daftar, cuma tidak lagi ikut dihitung di badge angka.
     */
    public function baca(Request $request, DatabaseNotification $notifikasi): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            (string) $notifikasi->notifiable_id === (string) $user->id
                && $notifikasi->notifiable_type === $user->getMorphClass(),
            403
        );

        if (is_null($notifikasi->read_at)) {
            $notifikasi->markAsRead();
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Hapus satu notifikasi (dipanggil dari tombol X di dropdown).
     */
    public function hapus(Request $request, DatabaseNotification $notifikasi): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            (string) $notifikasi->notifiable_id === (string) $user->id
                && $notifikasi->notifiable_type === $user->getMorphClass(),
            403
        );

        $notifikasi->delete();

        return response()->json(['status' => 'ok']);
    }

    /**
     * Hapus SEMUA notifikasi milik user (dipanggil dari tombol "Hapus
     * Semua" di sebelah "Tutup" pada dropdown lonceng). Tidak butuh
     * pengecekan kepemilikan per-item kayak hapus()/baca() karena query-nya
     * sendiri sudah dibatasi ke notifikasi milik user yang login.
     */
    public function hapusSemua(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json(['status' => 'ok']);
    }
}
