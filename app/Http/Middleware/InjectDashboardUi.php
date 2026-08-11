<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectDashboardUi
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Landing page: panel loader tetap putih, sementara background halaman
        // dibuat putih-keabuan lembut agar card/section putih tetap terlihat.
        // Overlay hero juga disesuaikan agar gradasi gambar tidak lagi bernuansa cream.
        if ($request->path() === '/') {
            $contentType = (string) $response->headers->get('Content-Type');
            if ($contentType === '' || str_contains($contentType, 'text/html')) {
                $html = $response->getContent();
                if (is_string($html) && $html !== '') {
                    $landingThemeStyle = '<style id="siberad-landing-light-theme">html[data-theme="light"]{--bg:#f4f5f7 !important;--bg-deep:#e9edf1 !important;--overlay-bg:rgba(233,237,241,.78) !important;--hero-ov-1:rgba(244,245,247,.96) !important;--hero-ov-2:rgba(244,245,247,.90) !important;--hero-ov-3:rgba(244,245,247,.55) !important;--hero-ov-4:rgba(244,245,247,.90) !important;--hero-ov-top:rgba(244,245,247,1) !important;--hero-ov-top-fade:rgba(244,245,247,0) !important;}html[data-theme="light"] body{background:#f4f5f7 !important;}html[data-theme="light"] #loader .loader-panel{background:#fff !important;}</style>';
                    $pos = stripos($html, '</head>');
                    if ($pos !== false) {
                        $html = substr($html, 0, $pos).$landingThemeStyle.substr($html, $pos);
                        $response->setContent($html);
                    }
                }
            }

            return $response;
        }

        if (! $request->routeIs('dashboard') || ! $request->user()) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || $html === '') {
            return $response;
        }

        $fixedHeaderAsset = asset('js/siberad-fixed-header.js');
        $fixedHeaderInjection = '<script src="'.e($fixedHeaderAsset).'"></script>';

        // Dashboard yang sudah memiliki komponen notifikasi sendiri tetap
        // mempertahankan UI tersebut. Kita hanya menyuntikkan behavior header
        // fixed agar layout sidebar asli tidak disentuh.
        if (str_contains($html, 'id="notifMenu"')) {
            $pos = strripos($html, '</body>');
            if ($pos !== false) {
                $html = substr($html, 0, $pos).$fixedHeaderInjection.substr($html, $pos);
                $response->setContent($html);
            }

            return $response;
        }

        $notifications = $request->user()->unreadNotifications->take(20)->map(function ($notification) {
            $data = is_array($notification->data) ? $notification->data : [];

            return [
                'message' => $data['pesan'] ?? $data['message'] ?? 'Laporan baru masuk.',
                'time' => $notification->created_at?->diffForHumans() ?? '',
            ];
        })->values()->all();

        $notificationJson = json_encode(
            $notifications,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );
        $csrfJson = json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $asset = asset('js/siberad-dashboard-ui.js');
        $injection = '<script>window.__SIBERAD_NOTIFICATIONS__ = '.$notificationJson.'; window.__SIBERAD_CSRF__ = '.$csrfJson.';</script>'
            .'<script src="'.e($asset).'"></script>'
            .$fixedHeaderInjection;

        $pos = strripos($html, '</body>');
        if ($pos !== false) {
            $html = substr($html, 0, $pos).$injection.substr($html, $pos);
            $response->setContent($html);
        }

        return $response;
    }
}
