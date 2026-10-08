<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyuntik skrip penyamar alamat (public/js/siberad-url-mask.js) ke halaman
 * dashboard pengguna yang sudah login, sehingga address bar hanya menampilkan
 * domain, bukan /dashboard atau nama menu.
 */
class InjectUrlMask
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
        if (! is_string($html) || $html === '' || str_contains($html, 'siberad-url-mask.js')) {
            return $response;
        }

        $script = '<script src="'.e(asset('js/siberad-url-mask.js')).'?v=20261008-1"></script>';
        $pos = strripos($html, '</body>');
        if ($pos !== false) {
            $response->setContent(substr($html, 0, $pos).$script.substr($html, $pos));
        }

        return $response;
    }
}
