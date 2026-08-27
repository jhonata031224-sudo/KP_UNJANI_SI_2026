<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menambahkan dukungan Web Push Notification (notifikasi yang bisa muncul
 * di luar sistem/tab tertutup, dan web app bisa di-"Install" ke home
 * screen/desktop) ke SEMUA dashboard, semua role -- lihat
 * resources/views/siberad/dashboards/partials/push-notification-controls.blade.php
 * untuk logika izin & subscribe-nya.
 */
class InjectWebPushUi
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
