<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplikasi ini full session-based (dashboard per-role, monitoring realtime,
 * dst) -- kontennya HARUS selalu fresh dari server, tidak boleh sekalipun
 * disajikan dari cache (browser, reverse proxy, atau CDN edge Railway).
 *
 * Tanpa header ini, Laravel/Symfony tidak mengirim Cache-Control eksplisit
 * untuk response HTML biasa, sehingga browser bisa saja pakai heuristic
 * caching (menyimpan halaman lalu menyajikannya lagi dari disk cache saat
 * refresh biasa), dan/atau layer proxy di depan origin (mis. Cloudflare di
 * edge *.up.railway.app) bisa ikut menyimpan salinan singkat -- inilah yang
 * menyebabkan gejala "tampilan sempat benar abis deploy, lalu balik ke
 * versi lama lagi setelah beberapa detik" walau kode di server sudah benar.
 *
 * Middleware ini memaksa header no-store di semua response HTML supaya
 * SETIAP request selalu ambil versi terbaru langsung dari origin.
 */
class PreventHtmlPageCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentType = (string) $response->headers->get('Content-Type');
        if (!str_contains($contentType, 'text/html')) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
