<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    /**
     * Realtime tab "Pengguna Aktif" -- session bisa nambah (login baru),
     * ilang (logout/paksa logout), ATAU cuma `last_activity`-nya kepencet
     * (user masih di halaman yang sama), jadi dikirim SNAPSHOT PENUH tiap
     * poll (bukan cursor "since") biar frontend bisa diff insert/update/
     * hapus sekaligus, pola sama seperti syncRequestList() di
     * laporan-role-realtime-sync.blade.php.
     */
    public function realtime(Request $request): JsonResponse
    {
        $sesiAktif = DB::table('sessions')
            ->whereNotNull('sessions.user_id')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->orderByDesc('sessions.last_activity')
            ->get(['sessions.id', 'sessions.ip_address', 'sessions.user_agent', 'sessions.last_activity', 'users.name as user_name']);

        return response()->json([
            'items_html' => $sesiAktif->map(fn ($s) => view('siberad.dashboards.partials.sesi-aktif-row', ['s' => $s, 'sesiSayaId' => $request->session()->getId()])->render())->implode(''),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Paksa logout satu sesi (satu perangkat/browser) dengan menghapus
     * baris session-nya di tabel `sessions` (SESSION_DRIVER=database).
     * Menghapus baris ini membuat sesi itu langsung tidak valid di sisi
     * server, walau cookie di browser pengguna masih ada.
     */
    public function destroy(string $id): RedirectResponse
    {
        $sesi = DB::table('sessions')->where('id', $id)->first();

        if (! $sesi) {
            return back()->with('status', 'Sesi tidak ditemukan (mungkin sudah berakhir).');
        }

        $namaPengguna = $sesi->user_id
            ? DB::table('users')->where('id', $sesi->user_id)->value('name')
            : null;

        DB::table('sessions')->where('id', $id)->delete();

        ActivityLog::catat(
            'session.force_logout',
            'Memaksa logout sesi milik ' . ($namaPengguna ?? 'pengguna tidak dikenal') . " (IP: {$sesi->ip_address})."
        );

        return back()->with('status', 'Sesi berhasil dipaksa logout.');
    }
}
