<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PermintaanResetPassword;
use App\Notifications\PermintaanResetPasswordDiputuskan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermintaanResetPasswordController extends Controller
{
    /**
     * Realtime tab "Permintaan Ganti Password" di dashboard Admin -- cuma
     * kirim permintaan yang BENERAN baru sejak `since` (dirender pakai
     * partial yang SAMA dengan render awal), bukan snapshot penuh. Status
     * yang berubah lewat aksi Setujui/Tolak sudah langsung ter-update di DOM
     * begitu admin sendiri yang melakukannya, jadi tidak perlu di-polling.
     */
    public function realtime(Request $request): JsonResponse
    {
        $since = max(0, (int) $request->query('since', 0));

        $items = PermintaanResetPassword::with(['user.satuan', 'diprosesOleh'])
            ->where('id', '>', $since)
            ->orderBy('id')
            ->get();

        $latestId = (int) (PermintaanResetPassword::max('id') ?? 0);

        return response()->json([
            'latest_id' => $latestId,
            'items_html' => $items->map(fn (PermintaanResetPassword $r) => view('siberad.dashboards.partials.permintaan-reset-password-row', ['r' => $r])->render())->implode(''),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

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

    /**
     * Hapus bersih riwayat permintaan ganti password yang SUDAH diproses
     * (Disetujui/Ditolak). Permintaan yang masih "Menunggu" sengaja tidak
     * ikut terhapus supaya tidak ada permintaan aktif yang hilang begitu
     * saja sebelum sempat diputuskan admin.
     */
    public function hapusRiwayat(Request $request): RedirectResponse
    {
        $jumlah = PermintaanResetPassword::where('status', '!=', PermintaanResetPassword::STATUS_MENUNGGU)->count();

        PermintaanResetPassword::where('status', '!=', PermintaanResetPassword::STATUS_MENUNGGU)->delete();

        ActivityLog::catat('permintaan-reset-password.hapus-riwayat', "Menghapus {$jumlah} riwayat permintaan ganti password yang sudah diproses.", $request->user());

        return back()->with('status', $jumlah > 0 ? "Riwayat permintaan ganti password ({$jumlah}) berhasil dihapus." : 'Tidak ada riwayat yang perlu dihapus.');
    }
}
