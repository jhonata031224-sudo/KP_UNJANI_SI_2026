<?php

namespace App\Http\Middleware;

use App\Models\Pengaturan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforcement NYATA untuk Mode Maintenance (Admin -> Kelola Sistem -> Mode
 * Maintenance) -- BUKAN sekadar mendisable tombol di frontend. Dipasang
 * GLOBAL (lihat bootstrap/app.php, $middleware->append) supaya berlaku ke
 * SELURUH request perubahan data di sistem (laporan, surat, kendala,
 * personel, dst.) tanpa perlu menempel middleware ini satu-persatu ke
 * puluhan route yang ada.
 *
 * Aturan:
 *  - Method aman (GET/HEAD/OPTIONS) SELALU lolos -- baca/lihat data tidak
 *    pernah diblokir Mode Maintenance.
 *  - Guest (belum login) SELALU lolos -- supaya /login (POST) tidak ikut
 *    keblokir dan pengguna non-Admin tetap bisa login selagi maintenance.
 *  - Admin (kode satuan ADMIN) SELALU lolos, sama seperti pengecualian
 *    ADMIN di Satuan::modulAktif() -- anti self-lockout & Admin tetap bisa
 *    bekerja normal.
 *  - Route di ROUTE_TERKECUALI SELALU lolos: bukan "aksi mengubah data"
 *    dalam pengertian bisnis/workflow (buat/edit/hapus/kirim/konfirmasi/
 *    disposisi laporan-surat-kendala-personel, dst.), melainkan fungsi
 *    akun/sesi & housekeeping notifikasi milik pengguna sendiri. Route ini
 *    SENGAJA tetap dibuka supaya tidak merusak fitur yang sudah berjalan,
 *    terutama sistem notifikasi (unread indicator, tandai dibaca) yang
 *    diminta TETAP utuh saat maintenance.
 *  - Selain itu, request perubahan data (POST/PUT/PATCH/DELETE) pengguna
 *    non-Admin DITOLAK di sini -- terlepas dari tombol di UI sempat
 *    diklik atau tidak.
 */
class EnforceMaintenanceMode
{
    private const ROUTE_TERKECUALI = [
        // Autentikasi & pemulihan akses -- non-Admin wajib tetap bisa login.
        'login',
        'logout',
        'captcha.image',
        'permintaan-reset-password.store',
        // Housekeeping notifikasi milik sendiri (bukan data bisnis/workflow) --
        // wajib tetap jalan supaya "sistem notifikasi Maintenance yang sudah
        // ada, termasuk unread indicator" tidak ikut rusak.
        'notifikasi.baca',
        'notifikasi.hapus',
        'notifikasi.hapus-semua',
        'notifikasi.toggle-user',
        'push.subscribe',
        'push.unsubscribe',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $kode = strtoupper(trim((string) $user->satuan?->kode));
        if ($kode === 'ADMIN') {
            return $next($request);
        }

        $pengaturan = Pengaturan::current();
        if (! $pengaturan->mode_maintenance_aktif) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, self::ROUTE_TERKECUALI, true)) {
            return $next($request);
        }

        $pesan = $pengaturan->pesanMaintenance();

        if ($request->expectsJson()) {
            return response()->json(['message' => $pesan], 423);
        }

        return back()->with('error', $pesan);
    }
}
