<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyuntikkan optimasi performa HP (lihat partial mobile-perf) paling awal di
 * <head> halaman dashboard yang sudah login. Di desktop efeknya nol.
 */
class InjectMobilePerf
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $request->ajax() || ! $request->user()) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || $html === '' || str_contains($html, 'id="cyclone-mobile-perf"')) {
            return $response;
        }

        if (! preg_match('/<head[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            return $response;
        }

        $pos = $m[0][1] + strlen($m[0][0]);
        $snippet = view('cyclone.dashboards.partials.mobile-perf')->render();
        $response->setContent(substr($html, 0, $pos).$snippet.substr($html, $pos));

        return $response;
    }
}
