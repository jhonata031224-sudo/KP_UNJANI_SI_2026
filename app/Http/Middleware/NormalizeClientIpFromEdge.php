<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Railway (hosting produksi app ini) kadang gonta-ganti jalur routing
 * internal antar-request (kadang lewat CDN/edge tambahan, kadang tidak),
 * jadi HEADER X-Forwarded-For yang diterima Laravel bisa beda JUMLAH &
 * ISI hop-nya dari satu request ke request berikutnya walau pengirimnya
 * (device/jaringan) yang sama persis. Karena bootstrap/app.php pakai
 * trustProxies(at: '*') (percaya semua hop), request()->ip() ikut goyang
 * mengikuti perubahan jalur itu -- efeknya paling kelihatan di kolom
 * "IP Address" tabel Pengguna Aktif (admin-sesi-aktif-realtime.blade.php):
 * nilainya beda tiap poll (4 detik) padahal sesi & jaringan penggunanya
 * sama, bikin baris dianggap "berubah" terus dan animasi kedip kuning
 * (siberad-row-updated) nyala tanpa henti.
 *
 * Railway sendiri menyediakan header X-Real-IP yang isinya SATU nilai
 * (bukan rantai/chain) berisi IP klien asli, lepas dari jalur routing
 * mana yang dipakai request itu (lihat docs.railway.com/networking/
 * public-networking/specs-and-limits). Middleware ini menormalkan
 * X-Forwarded-For supaya cuma berisi nilai X-Real-IP itu SEBELUM
 * TrustProxies bawaan Laravel jalan -- jadi request()->ip() (dan kolom
 * sessions.ip_address yang diisi dari situ) konsisten stabil apa pun
 * jalur yang dipakai Railway hari itu.
 *
 * HARUS didaftarkan lewat prepend() di bootstrap/app.php supaya jalan
 * SEBELUM middleware TrustProxies bawaan framework.
 */
class NormalizeClientIpFromEdge
{
    public function handle(Request $request, Closure $next): Response
    {
        $realIp = $request->headers->get('X-Real-IP');

        if ($realIp && filter_var($realIp, FILTER_VALIDATE_IP)) {
            $request->headers->set('X-Forwarded-For', $realIp);
            $request->server->set('HTTP_X_FORWARDED_FOR', $realIp);
        }

        return $next($request);
    }
}
