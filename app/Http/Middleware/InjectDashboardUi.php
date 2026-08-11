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
