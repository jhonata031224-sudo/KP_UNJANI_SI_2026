<?php

namespace App\Http\Middleware;

use App\Models\Pengaturan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyuntik banner PERSISTEN Mode Maintenance (+ script proteksi tombol
 * di sisi klien) ke SEMUA halaman HTML pengguna non-Admin yang sedang
 * login, selagi Pengaturan::current()->mode_maintenance_aktif TRUE --
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

        $pengaturan = Pengaturan::current();
        if (! $pengaturan->mode_maintenance_aktif) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || $html === '' || str_contains($html, 'data-siberad-maintenance-banner')) {
            return $response;
        }

        $banner = view('siberad.dashboards.partials.maintenance-banner', [
            'pesanMaintenance' => $pengaturan->pesanMaintenance(),
        ])->render();

        // Disisipkan sebagai anak PERTAMA <body> (bukan fixed/overlay) supaya
        // banner mendorong konten di bawahnya secara alami tanpa perlu hitung
        // offset/menimpa topbar yang sudah ada di tiap dashboard.
        $bodyOpenPos = stripos($html, '<body');
        if ($bodyOpenPos !== false) {
            $bodyTagEnd = strpos($html, '>', $bodyOpenPos);
            if ($bodyTagEnd !== false) {
                $insertAt = $bodyTagEnd + 1;
                $html = substr($html, 0, $insertAt).$banner.substr($html, $insertAt);
                $response->setContent($html);
            }
        }

        return $response;
    }
}
