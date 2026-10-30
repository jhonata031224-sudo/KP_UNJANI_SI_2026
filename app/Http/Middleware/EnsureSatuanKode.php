<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi route hanya untuk satuan dengan kode tertentu, mis.
 * ->middleware('satuan:BINFUNG,ADMIN'). Dipakai bersama middleware 'modul'
 * (yang cuma mengecek modul aktif, bukan siapa penggunanya).
 */
class EnsureSatuanKode
{
    public function handle(Request $request, Closure $next, string ...$kodeDiizinkan): Response
    {
        $kode = strtoupper(trim((string) $request->user()?->satuan?->kode));
        $diizinkan = array_map(fn ($k) => strtoupper(trim($k)), $kodeDiizinkan);

        abort_unless(
            $kode !== '' && in_array($kode, $diizinkan, true),
            403,
            'Fitur ini tidak tersedia untuk satuan Anda.'
        );

        return $next($request);
    }
}
