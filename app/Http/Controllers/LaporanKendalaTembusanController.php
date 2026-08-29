<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanKendalaTembusan;
use App\Models\User;
use App\Notifications\LaporanKendalaTembusanFeedbackDiterima;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sisi PENERIMA tembusan (4 Satlak/4 Sdir) laporan kendala Kasansi. Dua aksi
 * tersedia di sini: menandai "sudah dibaca" dan memberi feedback balik ke
 * Kasansi (beriFeedback -- begitu terisi, Kasansi sudah boleh meneruskan
 * laporannya ke Danpus). Penerima tembusan TETAP TIDAK BISA menindaklanjuti/
 * menolak/mengonfirmasi laporan itu sendiri, wewenang itu murni milik DANPUS
 * lewat LaporanKendalaController.
 */
class LaporanKendalaTembusanController extends Controller
{
    public function tandaiDibaca(Request $request, LaporanKendalaTembusan $laporanKendalaTembusan): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        abort_unless(
            (int) $laporanKendalaTembusan->satuan_id === (int) $satuan->id,
            403,
            'Tembusan ini bukan untuk satuan Anda.'
        );

        // dibaca_at dicatat PER SATUAN, bukan per user -- begitu satuan ini
        // pertama kali menandai dibaca, pengguna lain di satuan yang sama
        // tidak perlu menandai ulang, jadi cukup idempotent (tidak
        // menimpa dibaca_at/dibaca_oleh yang sudah ada).
        if (! $laporanKendalaTembusan->dibaca_at) {
            $laporanKendalaTembusan->update([
                'dibaca_at' => now(),
                'dibaca_oleh' => $user->id,
            ]);
        }

        return back()->with('status', 'Tembusan ditandai sudah dibaca.');
    }

    /**
     * Satuan penerima tembusan mengirim feedback/catatan balik ke Kasansi.
     * Hanya bisa sekali per baris tembusan (per satuan penerima). Feedback
     * ini yang membuka tombol "Kirim ke Danpus" milik Kasansi -- lihat
     * LaporanKendala::siapDiteruskan() dan LaporanKendalaController::teruskan().
     */
    public function beriFeedback(Request $request, LaporanKendalaTembusan $laporanKendalaTembusan): RedirectResponse
    {
        $validated = $request->validate([
            'feedback' => ['required', 'string', 'max:5000'],
        ], [
            'feedback.required' => 'Feedback wajib diisi.',
        ]);

        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        abort_unless(
            (int) $laporanKendalaTembusan->satuan_id === (int) $satuan->id,
            403,
            'Tembusan ini bukan untuk satuan Anda.'
        );
        abort_unless(! $laporanKendalaTembusan->feedback, 422, 'Feedback untuk tembusan ini sudah pernah dikirim.');

        $laporanKendalaTembusan->update([
            'feedback' => $validated['feedback'],
            'feedback_at' => now(),
            'feedback_oleh' => $user->id,
            // Kasih feedback otomatis menandai tembusan ini "sudah dibaca"
            // juga kalau belum -- idempotent, tidak menimpa dibaca_at yang
            // sudah ada.
            'dibaca_at' => $laporanKendalaTembusan->dibaca_at ?? now(),
            'dibaca_oleh' => $laporanKendalaTembusan->dibaca_oleh ?? $user->id,
        ]);

        $laporanKendala = $laporanKendalaTembusan->laporanKendala()->with('satuan')->first();
        if ($laporanKendala) {
            foreach (User::where('satuan_id', $laporanKendala->satuan_id)->get() as $penerima) {
                $penerima->notify(new LaporanKendalaTembusanFeedbackDiterima($laporanKendalaTembusan, $satuan));
            }
        }

        ActivityLog::catat('laporan-kendala-tembusan.feedback', "Memberi feedback tembusan untuk laporan kendala \"{$laporanKendala?->perihal}\".", $user, [
            'laporan_kendala_tembusan_id' => $laporanKendalaTembusan->id,
            'laporan_kendala_id' => $laporanKendalaTembusan->laporan_kendala_id,
        ]);

        return back()->with('status', 'Feedback berhasil dikirim ke '.($laporanKendala?->satuan?->nama ?? 'Kasansi').'.');
    }
}
