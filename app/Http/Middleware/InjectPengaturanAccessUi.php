<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectPengaturanAccessUi
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('dashboard') || ! $request->user()) {
            return $response;
        }

        $kode = strtoupper(trim((string) $request->user()?->satuan?->kode));
        if ($kode !== 'ADMIN') {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || $html === '' || str_contains($html, 'id="adminPengaturanAccessModal"')) {
            return $response;
        }

        $modal = view('siberad.dashboards.partials.admin-pengaturan-access')->render();
        $pos = strripos($html, '</body>');
        if ($pos !== false) {
            $html = substr($html, 0, $pos).$modal.substr($html, $pos);
            $response->setContent($html);
        }

        return $response;
    }
}
