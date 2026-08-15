<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PermintaanResetPassword;
use App\Models\User;
use App\Notifications\PermintaanResetPasswordBaru;
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
    public function store(Request $request): RedirectResponse
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

        return back()->with('status', 'Permintaan ganti password berhasil dikirim ke Admin.');
    }
}
