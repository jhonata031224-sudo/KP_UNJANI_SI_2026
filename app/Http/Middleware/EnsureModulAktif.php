<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforcement nyata untuk "Manajemen Role & Hak Akses" (Admin). Dipasang di
 * route-route yang termasuk satu modul ('laporan', 'monitoring',
 * 'notifikasi') -- kalau modul itu dimatikan Admin untuk satuan user yang
 * login, request diblokir 403 di sini, terlepas dari menu di UI
 * disembunyikan atau tidak (menu cuma kosmetik, ini yang beneran menggerbang).
 *
 * Pemetaan sumber kebenaran ada di Satuan::modulAktif() -- lihat komentar
 * di sana untuk aturan default (satuan yang belum pernah diatur Admin =
 * tetap aktif) dan pengecualian role ADMIN (selalu aktif, anti self-lockout).
 */
class EnsureModulAktif
{
    public function handle(Request $request, Closure $next, string $modul): Response
    {
        $satuan = $request->user()?->satuan;

        abort_unless(
            $satuan && $satuan->modulAktif($modul),
            403,
            'Modul ini tidak diaktifkan untuk satuan Anda. Hubungi Admin untuk mengaktifkannya lewat Manajemen Role & Hak Akses.'
        );

        return $next($request);
    }
}
