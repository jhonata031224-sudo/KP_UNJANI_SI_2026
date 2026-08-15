<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PermintaanResetPassword;
use App\Notifications\PermintaanResetPasswordDiputuskan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermintaanResetPasswordController extends Controller
{
    /**
     * Setujui permintaan -- password baru yang diajukan langsung diterapkan
     * ke akun pengaju. Update lewat query builder (bukan Eloquent) karena
     * password_baru yang tersimpan SUDAH di-hash saat permintaan dibuat,
     * jadi harus ditulis apa adanya tanpa lewat cast 'hashed' lagi.
     */
    public function setujui(Request $request, PermintaanResetPassword $permintaanResetPassword): RedirectResponse
    {
        abort_if($permintaanResetPassword->status !== PermintaanResetPassword::STATUS_MENUNGGU, 422, 'Permintaan ini sudah diproses sebelumnya.');

        DB::table('users')->where('id', $permintaanResetPassword->user_id)->update([
            'password' => $permintaanResetPassword->password_baru,
        ]);

        $permintaanResetPassword->update([
            'status' => PermintaanResetPassword::STATUS_DISETUJUI,
            'diproses_oleh' => $request->user()->id,
            'diproses_at' => now(),
        ]);

        $permintaanResetPassword->user->notify(new PermintaanResetPasswordDiputuskan($permintaanResetPassword));

        ActivityLog::catat('permintaan-reset-password.setujui', "Menyetujui permintaan ganti password \"{$permintaanResetPassword->user->name}\".", $request->user(), [
            'permintaan_reset_password_id' => $permintaanResetPassword->id,
        ]);

        return back()->with('status', 'Permintaan ganti password disetujui. Password baru sudah aktif.');
    }

    public function tolak(Request $request, PermintaanResetPassword $permintaanResetPassword): RedirectResponse
    {
        abort_if($permintaanResetPassword->status !== PermintaanResetPassword::STATUS_MENUNGGU, 422, 'Permintaan ini sudah diproses sebelumnya.');

        $permintaanResetPassword->update([
            'status' => PermintaanResetPassword::STATUS_DITOLAK,
            'diproses_oleh' => $request->user()->id,
            'diproses_at' => now(),
        ]);

        $permintaanResetPassword->user->notify(new PermintaanResetPasswordDiputuskan($permintaanResetPassword));

        ActivityLog::catat('permintaan-reset-password.tolak', "Menolak permintaan ganti password \"{$permintaanResetPassword->user->name}\".", $request->user(), [
            'permintaan_reset_password_id' => $permintaanResetPassword->id,
        ]);

        return back()->with('status', 'Permintaan ganti password ditolak.');
    }
}
