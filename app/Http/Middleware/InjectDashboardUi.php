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
        // Pada tema dark, landing memakai palet Obsidian + Electric Cyan tanpa hijau.
        if ($request->path() === '/') {
            $contentType = (string) $response->headers->get('Content-Type');
            if ($contentType === '' || str_contains($contentType, 'text/html')) {
                $html = $response->getContent();
                if (is_string($html) && $html !== '') {
                    $landingThemeStyle = '<style id="siberad-landing-theme">html[data-theme="light"]{--bg:#f4f5f7 !important;--bg-deep:#e9edf1 !important;--overlay-bg:rgba(233,237,241,.78) !important;--hero-ov-1:rgba(244,245,247,.96) !important;--hero-ov-2:rgba(244,245,247,.90) !important;--hero-ov-3:rgba(244,245,247,.55) !important;--hero-ov-4:rgba(244,245,247,.90) !important;--hero-ov-top:rgba(244,245,247,1) !important;--hero-ov-top-fade:rgba(244,245,247,0) !important;}html[data-theme="light"] body{background:#f4f5f7 !important;}html[data-theme="light"] #loader .loader-panel{background:#fff !important;}html:not([data-theme="light"]){--bg:#06090c !important;--bg-deep:#030609 !important;--panel:#11181f !important;--panel-2:#151f27 !important;--panel-alt:#0b1218 !important;--border:rgba(0,217,255,.22) !important;--border-soft:rgba(0,217,255,.13) !important;--border-strong:rgba(0,217,255,.42) !important;--gold:#00d9ff !important;--gold-bright:#67f3ff !important;--gold-dim:rgba(0,217,255,.14) !important;--green:#00b8d4 !important;--green-bright:#00e5ff !important;--green-dim:rgba(0,229,255,.14) !important;--text:#e8f7fb !important;--text-muted:#8fa6b2 !important;--text-dim:#647d89 !important;--header-bg:rgba(6,9,12,.84) !important;--overlay-bg:rgba(3,6,9,.82) !important;--hero-ov-1:rgba(3,6,9,.97) !important;--hero-ov-2:rgba(3,6,9,.93) !important;--hero-ov-3:rgba(3,6,9,.72) !important;--hero-ov-4:rgba(3,6,9,.90) !important;--hero-ov-top:rgba(3,6,9,1) !important;--hero-ov-top-fade:rgba(3,6,9,0) !important;--chip-bg:rgba(17,24,31,.82) !important;--chip-border:rgba(0,217,255,.18) !important;--chip-shadow:0 6px 18px rgba(0,0,0,.28) !important;}html:not([data-theme="light"]) body{background-color:#06090c !important;background-image:radial-gradient(ellipse 90% 55% at 50% -10%,rgba(0,217,255,.09),transparent 60%),radial-gradient(ellipse 60% 40% at 100% 20%,rgba(0,174,199,.07),transparent 60%) !important;}html:not([data-theme="light"]) body::before{background-image:linear-gradient(rgba(0,217,255,.028) 1px,transparent 1px),linear-gradient(90deg,rgba(0,217,255,.028) 1px,transparent 1px) !important;}html:not([data-theme="light"]) .loader-scanline{background:linear-gradient(90deg,transparent,#00d9ff,transparent) !important;}html:not([data-theme="light"]) .mark-plate{box-shadow:0 0 0 1px rgba(0,217,255,.10) inset,0 8px 30px rgba(0,0,0,.58) !important;}</style>';
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
