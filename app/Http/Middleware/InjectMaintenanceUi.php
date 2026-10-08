<?php

namespace App\Http\Middleware;

use App\Models\Pengaturan;
use App\Models\User;
use App\Notifications\PengumumanBroadcastAdmin;
use Closure;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyuntik banner PERSISTEN Mode Maintenance (+ script proteksi tombol
 * di sisi klien) ke SEMUA halaman HTML pengguna non-Admin yang sedang
 * login (banner tampil hanya selagi mode_maintenance_aktif TRUE, dan
 * disinkronkan realtime lewat polling /maintenance/status) --
 * mengikuti pola persis InjectWebPushUi/InjectPengaturanAccessUi (append
 * global di bootstrap/app.php, cek Content-Type text/html, sisipkan
 * sebelum </body>).
 *
 * CATATAN PENTING: middleware ini HANYA lapisan UI/UX. Proteksi
 * sesungguhnya (menolak request perubahan data) ada di
 * EnforceMaintenanceMode -- middleware itu jalan terpisah dan tetap
 * menolak request meskipun HTML/JS di sini entah kenapa gagal ter-inject
 * atau di-bypass pengguna (mis. lewat DevTools).
 */
class InjectMaintenanceUi
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if (! $user) {
            return $response;
        }

        $kode = strtoupper(trim((string) $user->satuan?->kode));
        if ($kode === 'ADMIN') {
            return $response;
        }

        // Skrip maintenance DISUNTIK SELALU ke halaman non-Admin (bukan cuma saat
        // maintenance aktif) supaya perubahan status oleh Admin langsung
        // tampil di halaman yang sudah terbuka tanpa refresh -- banner
        // disembunyikan (hidden) selagi maintenance mati, dan skrip polling
        // /maintenance/status yang menyalakan/mematikannya.
        $pengaturan = Pengaturan::current();
        $aktif      = (bool) $pengaturan->mode_maintenance_aktif;

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || $html === '' || str_contains($html, 'data-siberad-maintenance-banner')) {
            return $response;
        }

        $banner = view('siberad.dashboards.partials.maintenance-banner', [
            'aktifMaintenance' => $aktif,
            'pesanMaintenance' => $pengaturan->pesanMaintenance(),
            'waktuMaintenance' => $aktif ? $this->waktuPengumumanMaintenance($user) : null,
        ])->render();

        // PENTING: cari `</body>` dari BELAKANG (strripos), BUKAN `<body` dari
        // depan (stripos) -- pola ini SENGAJA disamakan dengan InjectWebPushUi.
        // `<body` dari depan pernah salah tangkap komentar JS di <head> (mis.
        // "// <body>/sidebar sempat di-parse..." di dash-styles.blade.php) yang
        // kebetulan mengandung teks "<body>", lalu menyisipkan banner di
        // TENGAH-TENGAH sebuah <script> -- merusak seluruh parsing script
        // setelahnya (kelihatan sebagai kode JS mentah tampil sebagai teks di
        // halaman). `</body>` jauh lebih jarang muncul di luar tag penutup
        // asli, dan diambil dari kemunculan TERAKHIR supaya makin aman.
        // Posisi visual "banner di paling atas" tetap dicapai lewat script di
        // partial banner yang memindahkan elemennya jadi anak pertama <body>
        // begitu DOM siap -- bukan lewat manipulasi string di titik ini.
        $bodyClosePos = strripos($html, '</body>');
        if ($bodyClosePos !== false) {
            $html = substr($html, 0, $bodyClosePos).$banner.substr($html, $bodyClosePos);
            $response->setContent($html);
        }

        return $response;
    }

    /**
     * Teks waktu "Dikirim" untuk modal pemeliharaan yang dibuka dari banner
     * -- SAMA persis dengan yang ditampilkan lonceng notifikasi
     * (NotifikasiController::realtime -> created_at->diffForHumans()),
     * diambil dari notifikasi pengumuman 'maintenance' yang dikirim
     * MaintenanceSettingController saat mode diaktifkan. Prioritas:
     * notifikasi milik pengguna ini (identik dengan yang dilihatnya di
     * lonceng); kalau sudah dihapus dari lonceng, pakai notifikasi
     * pemeliharaan terbaru milik pengguna mana pun (dikirim serentak ke
     * semua non-Admin, jadi waktunya sama). NULL kalau memang tidak ada.
     */
    public function waktuPengumumanMaintenance(User $user): ?string
    {
        $filter = fn ($q) => $q
            ->where('type', PengumumanBroadcastAdmin::class)
            ->where('data', 'like', '%"kategori":"maintenance"%')
            ->where('data', 'like', '%"judul":"Sistem Dalam Pemeliharaan"%');

        $notif = $filter($user->notifications()->getQuery())->latest('created_at')->first()
            ?? $filter(DatabaseNotification::query())->latest('created_at')->first();

        return $notif?->created_at?->diffForHumans();
    }
}
