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

        // The main report script may already be present in the page. Do not
        // skip the hardening script in that case; inject the fix independently.
        if (str_contains($html, 'admin-report-center-fix.js')) {
            return $response;
        }

        $fixAsset = asset('js/admin-report-center-fix.js?v=20260821-2');
        $script = '<script src="'.e($fixAsset).'"></script>';

        if (! str_contains($html, 'admin-report-center.js')) {
            $asset = asset('js/admin-report-center.js?v=20260821-2');
            $script = '<script src="'.e($asset).'"></script>'.$script;
        }

        $pos = strripos($html, '</body>');
        if ($pos !== false) {
            $html = substr($html, 0, $pos).$script.substr($html, $pos);
            $response->setContent($html);
        }

        return $response;
    }
}
