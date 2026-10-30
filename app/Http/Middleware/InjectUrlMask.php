<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyuntik skrip penyamar alamat (public/js/cyclone-url-mask.js) ke halaman
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
        if (! is_string($html) || $html === '' || str_contains($html, 'cyclone-url-mask.js')) {
            return $response;
        }

        $script = '<script src="'.e(asset('js/cyclone-url-mask.js')).'?v=20261008-1"></script>';
        $pos = strripos($html, '</body>');
        if ($pos !== false) {
            $response->setContent(substr($html, 0, $pos).$script.substr($html, $pos));
        }

        return $response;
    }
}
