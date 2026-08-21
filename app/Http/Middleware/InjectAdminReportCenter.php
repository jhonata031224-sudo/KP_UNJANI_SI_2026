<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectAdminReportCenter
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('dashboard') || ! $request->user()) {
            return $response;
        }

        if (strtoupper(trim((string) $request->user()->satuan?->kode)) !== 'ADMIN') {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || $html === '' || ! str_contains($html, 'Laporan Pengguna &amp; Aktivitas')) {
            return $response;
        }

        if (str_contains($html, 'admin-report-center.js')) {
            return $response;
        }

        $asset = asset('js/admin-report-center.js?v=20260821-1');
        $script = '<script src="'.e($asset).'"></script>';
        $pos = strripos($html, '</body>');
        if ($pos !== false) {
            $html = substr($html, 0, $pos).$script.substr($html, $pos);
            $response->setContent($html);
        }

        return $response;
    }
}
