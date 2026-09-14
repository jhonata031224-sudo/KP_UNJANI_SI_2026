<?php

namespace App\Http\Middleware;

use App\Models\Pengaturan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menambahkan dukungan Web Push Notification (notifikasi yang bisa muncul
 * di luar sistem/tab tertutup, dan web app bisa di-"Install" ke home
 * screen/desktop) ke SEMUA halaman HTML yang sudah login, semua role --
 * bukan cuma route 'dashboard'. Ini penting supaya proses request izin &
 * subscribe terpanggil di halaman manapun yang pertama kali dibuka user
 * setelah login, tidak harus /dashboard dulu.
 *
 * Lihat resources/views/siberad/dashboards/partials/push-notification-controls.blade.php
 * untuk logika izin & subscribe-nya.
 */
class InjectWebPushUi
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Hanya inject ke request user yang sudah login -- request AJAX,
        // polling realtime, endpoint JSON, dan halaman publik (login, captcha)
        // tidak perlu script push sama sekali.
        if (! $request->user()) {
            return $response;
        }

        // Admin bisa matikan fitur push utk SELURUH pengguna lewat menu
        // Setelan -> Notifikasi. Kalau dimatikan, script subscribe tidak
        // pernah diinjek sama sekali di halaman manapun (bukan cuma
        // pengiriman push-nya yang di-skip, lihat WebPushChannel::send()).
        if (! Pengaturan::current()->notifikasi_push_aktif) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || $html === '' || str_contains($html, 'id="pushEnableBtn"') || str_contains($html, 'siberad-push-style')) {
            return $response;
        }

        $manifestTag = '<link rel="manifest" href="'.e(asset('manifest.json')).'">';
        $headPos = stripos($html, '</head>');
        if ($headPos !== false) {
            $html = substr($html, 0, $headPos).$manifestTag.substr($html, $headPos);
        }

        $pushUi = view('siberad.dashboards.partials.push-notification-controls')->render();
        $bodyPos = strripos($html, '</body>');
        if ($bodyPos !== false) {
            $html = substr($html, 0, $bodyPos).$pushUi.substr($html, $bodyPos);
        }

        $response->setContent($html);

        return $response;
    }
}
