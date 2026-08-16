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

        if ($request->path() === '/') {
            $contentType = (string) $response->headers->get('Content-Type');
            if ($contentType === '' || str_contains($contentType, 'text/html')) {
                $html = $response->getContent();
                if (is_string($html) && $html !== '') {
                    $landingThemeStyle = '<style id="siberad-landing-theme">html[data-theme="light"]{--bg:#f4f5f7 !important;--bg-deep:#e9edf1 !important;--overlay-bg:rgba(233,237,241,.78) !important;--hero-ov-1:rgba(244,245,247,.96) !important;--hero-ov-2:rgba(244,245,247,.90) !important;--hero-ov-3:rgba(244,245,247,.55) !important;--hero-ov-4:rgba(244,245,247,.90) !important;--hero-ov-top:rgba(244,245,247,1) !important;--hero-ov-top-fade:rgba(244,245,247,0) !important;}html[data-theme="light"] body{background:#f4f5f7 !important;}html[data-theme="light"] #loader .loader-panel{background:#fff !important;}html:not([data-theme="light"]){--bg:#06090c !important;--bg-deep:#030609 !important;--panel:#11181f !important;--panel-2:#151f27 !important;--panel-alt:#0b1218 !important;--border:rgba(217,146,11,.22) !important;--border-soft:rgba(217,146,11,.13) !important;--border-strong:rgba(217,146,11,.42) !important;--gold:#d9920b !important;--gold-bright:#f2b94b !important;--gold-dim:rgba(217,146,11,.14) !important;--green:#d9920b !important;--green-bright:#f2b94b !important;--green-dim:rgba(242,185,75,.14) !important;--text:#f5f1e8 !important;--text-muted:#a9a39a !important;--text-dim:#746e65 !important;--header-bg:rgba(6,9,12,.84) !important;--overlay-bg:rgba(3,6,9,.82) !important;--hero-ov-1:rgba(3,6,9,.97) !important;--hero-ov-2:rgba(3,6,9,.93) !important;--hero-ov-3:rgba(3,6,9,.72) !important;--hero-ov-4:rgba(3,6,9,.90) !important;--hero-ov-top:rgba(3,6,9,1) !important;--hero-ov-top-fade:rgba(3,6,9,0) !important;--chip-bg:rgba(17,24,31,.82) !important;--chip-border:rgba(217,146,11,.18) !important;--chip-shadow:0 6px 18px rgba(0,0,0,.28) !important;}html:not([data-theme="light"]) body{background-color:#06090c !important;background-image:radial-gradient(ellipse 90% 55% at 50% -10%,rgba(217,146,11,.09),transparent 60%),radial-gradient(ellipse 60% 40% at 100% 20%,rgba(170,111,5,.07),transparent 60%) !important;}html:not([data-theme="light"]) body::before{background-image:linear-gradient(rgba(217,146,11,.028) 1px,transparent 1px),linear-gradient(90deg,rgba(217,146,11,.028) 1px,transparent 1px) !important;}html:not([data-theme="light"]) .loader-scanline{background:linear-gradient(90deg,transparent,#d9920b,transparent) !important;}html:not([data-theme="light"]) .mark-plate{box-shadow:0 0 0 1px rgba(217,146,11,.10) inset,0 8px 30px rgba(0,0,0,.58) !important;}</style>';
                    $pos = stripos($html, '</head>');
                    if ($pos !== false) {
                        $html = substr($html, 0, $pos).$landingThemeStyle.substr($html, $pos);
                        $response->setContent($html);
                    }
                }
            }
            return $response;
        }

        if (! $request->routeIs('dashboard') || ! $request->user()) return $response;

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) return $response;

        $html = $response->getContent();
        if (! is_string($html) || $html === '') return $response;

        $fixedHeaderAsset = asset('js/siberad-fixed-header.js');
        $fixedHeaderInjection = '<script src="' . e($fixedHeaderAsset) . '"></script>';

        if (str_contains($html, 'id="notifMenu"')) {
            $adminPreviewFix = '<style id="siberad-admin-preview-fix">.lp-layout{grid-template-columns:minmax(0,1.15fr) minmax(0,1fr) !important;width:100%;min-width:0;}.lp-panel,.lp-preview-panel{min-width:0;max-width:100%;box-sizing:border-box;}.lp-preview-panel{overflow:hidden;}.lp-preview-body{min-width:0;overflow:hidden;}.lp-browser-frame{width:100%;max-width:100%;min-width:0;box-sizing:border-box;}#lpPreview{width:100%;max-width:100%;min-width:0;overflow:hidden !important;overflow-x:hidden !important;overflow-y:hidden !important;}.lp-preview.lp-preview-zoomed{overflow:auto !important;overflow-x:auto !important;overflow-y:auto !important;}.lp-features{grid-template-columns:repeat(2,minmax(0,1fr));}.lp-feature-card,.lp-p,.lp-h1,.lp-h2,.lp-eyebrow{min-width:0;overflow-wrap:anywhere;word-break:break-word;}.lp-h1,.lp-h2{display:block !important;visibility:visible !important;color:var(--text) !important;}@media(max-width:1100px){.lp-layout{grid-template-columns:minmax(0,1fr) !important;}.lp-preview-panel{position:relative;top:auto;}}</style>';
            $pos = strripos($html, '</head>');
            if ($pos !== false) $html = substr($html, 0, $pos).$adminPreviewFix.substr($html, $pos);

            $landingPreviewAsset = asset('js/siberad-landing-preview.js');
            $landingPreviewInjection = '<script src="' . e($landingPreviewAsset) . '"></script>';
            $roleAccessAsset = asset('js/role-access-layout.js') . '?v=20260816-5';
            $roleAccessInjection = '<script src="' . e($roleAccessAsset) . '"></script>';
            $pos = strripos($html, '</body>');
            if ($pos !== false) {
                $html = substr($html, 0, $pos).$landingPreviewInjection.$roleAccessInjection.$fixedHeaderInjection.substr($html, $pos);
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
        $injection = '<script>window.__SIBERAD_NOTIFICATIONS__ = ' . $notificationJson . '; window.__SIBERAD_CSRF__ = ' . $csrfJson . ';</script>'
            . '<script src="' . e($asset) . '"> </script>'
            . $fixedHeaderInjection;

        $pos = strripos($html, '</body>');
        if ($pos !== false) {
            $html = substr($html, 0, $pos).$injection.substr($html, $pos);
            $response->setContent($html);
        }

        return $response;
    }
}
