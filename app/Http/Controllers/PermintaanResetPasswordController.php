<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PermintaanResetPassword;
use App\Models\User;
use App\Notifications\PermintaanResetPasswordBaru;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PermintaanResetPasswordController extends Controller
{
    /**
     * Ajukan permintaan ganti password -- dipakai Pimpinan & Satuan.
     * Admin tidak lewat alur ini (Admin punya akses langsung ke akunnya
     * sendiri di luar sistem persetujuan ini).
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user()->load('satuan');
        abort_if(strtoupper((string) $user->satuan?->kode) === 'ADMIN', 403, 'Admin tidak menggunakan alur permintaan ini.');

        $validated = $request->validate([
            'password_baru' => ['required', 'string', 'confirmed'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ], [
            'password_baru.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $permintaan = PermintaanResetPassword::create([
            'user_id' => $user->id,
            'password_baru' => Hash::make($validated['password_baru']),
            'catatan' => $validated['catatan'] ?? null,
            'status' => PermintaanResetPassword::STATUS_MENUNGGU,
        ]);

        foreach (User::whereHas('satuan', fn ($q) => $q->where('kode', 'ADMIN'))->get() as $admin) {
            $admin->notify(new PermintaanResetPasswordBaru($permintaan));
        }

        ActivityLog::catat('permintaan-reset-password.create', "Mengajukan permintaan ganti password.", $user, [
            'permintaan_reset_password_id' => $permintaan->id,
        ]);

        // Dari modal Pengaturan Akun (AJAX) -> balas JSON supaya modal tetap
        // kebuka: klien tinggal sisipkan `pending_html` + sembunyikan form.
        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $permintaan->id,
                'message' => 'Permintaan ganti password berhasil dikirim ke Admin.',
                'pending_html' => view('siberad.dashboards.partials.profile-password-pending', [
                    'permintaan' => $permintaan,
                ])->render(),
            ]);
        }

        return back()->with('status', 'Permintaan ganti password berhasil dikirim ke Admin.');
    }

    /**
     * Status permintaan ganti password milik user sendiri -- dipoll dari
     * modal Pengaturan Akun (tab Ganti Password) supaya begitu Admin
     * memutuskan, form-nya balik ke semula + muncul toast tanpa reload.
     * Selalu di-scope ke user_id yang login (nggak bisa intip milik orang).
     */
    public function status(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);

        $permintaan = PermintaanResetPassword::where('user_id', $request->user()->id)
            ->when($id > 0, fn ($q) => $q->where('id', $id))
            ->latest('id')
            ->first();

        if (! $permintaan) {
            return response()->json(['state' => 'none']);
        }

        if ($permintaan->status === PermintaanResetPassword::STATUS_MENUNGGU) {
            return response()->json(['state' => 'pending', 'id' => $permintaan->id]);
        }

        $disetujui = $permintaan->status === PermintaanResetPassword::STATUS_DISETUJUI;

        return response()->json([
            'state' => 'decided',
            'id' => $permintaan->id,
            'status' => $permintaan->status,
            'pesan' => $disetujui
                ? 'Permintaan ganti password kamu disetujui Admin. Password baru sudah aktif.'
                : 'Permintaan ganti password kamu ditolak Admin.',
        ], 200, ['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0']);
    }
}
