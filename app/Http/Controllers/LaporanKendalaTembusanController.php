<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanKendalaTembusan;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanKendalaTembusanFeedbackDiterima;
use App\Support\DecorativeSeparatorCleaner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
    /**
     * Dipoll berkala oleh sisi PENERIMA tembusan (4 Satlak/4 Sdir, mis.
     * Duktek) di card grid #kcard-grid-tembusan & #kcard-grid-tembusan-arsip
     * -- sebelum ini kartu tembusan cuma dirender sekali pas load halaman
     * (beda dari lonceng notifikasi yang sudah realtime lewat
     * NotifikasiController::realtime), jadi kendala baru dari Kasansi baru
     * kelihatan setelah reload manual. Dipisah dari
     * LaporanKendalaController::realtime() karena itu khusus Danpus/Wadan
     * & Kasansi (403 kalau bukan salah satunya), sedangkan role di sini
     * murni penerima tembusan.
     */
    public function realtime(Request $request): JsonResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        $kode = strtoupper((string) $satuan?->kode);
        abort_unless($satuan && in_array($kode, Satuan::kodeTembusanKasansi(), true), 403);

        $items = LaporanKendalaTembusan::with(['laporanKendala.satuan', 'laporanKendala.lampirans', 'dibacaOleh'])
            ->where('satuan_id', $satuan->id)
            ->latest()
            ->get();

        $masuk = $items->whereNull('feedback')->values();
        $arsip = $items->whereNotNull('feedback')->values();

        // Sama seperti items_html di LaporanKendalaController::realtime():
        // normalisasi manual di sini supaya HTML kartu yang dikirim lewat
        // JSON polling ini selalu sama persis dengan HTML kartu hasil
        // render halaman pertama (yang sudah lewat middleware
        // RemoveDecorativeSeparators), biar tidak kedip terus tanpa
        // perubahan data beneran -- lihat DecorativeSeparatorCleaner.
        return response()->json([
            'masuk_items_html' => DecorativeSeparatorCleaner::clean($masuk->map(fn (LaporanKendalaTembusan $t) => view('siberad.dashboards.partials.laporan-kendala-tembusan-card', ['t' => $t, 'satuan' => $satuan])->render())->implode('')),
            'arsip_items_html' => DecorativeSeparatorCleaner::clean($arsip->map(fn (LaporanKendalaTembusan $t) => view('siberad.dashboards.partials.laporan-kendala-tembusan-card', ['t' => $t, 'satuan' => $satuan])->render())->implode('')),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

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

        ActivityLog::catat('laporan-kendala-tembusan.feedback', "Memberi balasan teks untuk laporan kendala \"{$laporanKendala?->perihal}\".", $user, [
            'laporan_kendala_tembusan_id' => $laporanKendalaTembusan->id,
            'laporan_kendala_id'          => $laporanKendalaTembusan->laporan_kendala_id,
        ]);

        return back()->with('status', 'Balasan berhasil dikirim ke '.($laporanKendala?->satuan?->nama ?? 'Kasansi').'.');
    }

    /**
     * Satuan penerima tembusan mengirim DOKUMEN balasan ke Kasansi --
     * misalnya laporan personel yang diminta Kasansi di isi kendala.
     * Bisa dilakukan bersama atau terpisah dari feedback teks (beriFeedback).
     * Dokumen bisa di-replace (upload ulang akan menimpa file lama).
     */
    public function kirimDokumenBalasan(Request $request, LaporanKendalaTembusan $laporanKendalaTembusan): RedirectResponse
    {
        $validated = $request->validate([
            'dokumen_balasan' => ['required', 'file', 'max:10240'],
        ], [
            'dokumen_balasan.required' => 'Pilih file dokumen yang akan dikirim ke Kasansi.',
        ]);

        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        abort_unless(
            (int) $laporanKendalaTembusan->satuan_id === (int) $satuan->id,
            403,
            'Tembusan ini bukan untuk satuan Anda.'
        );

        $file = $request->file('dokumen_balasan');
        $path = $file->store('dokumen-balasan-tembusan', 'public');
        abort_if(! $path, 500, 'Gagal menyimpan dokumen ke server. Coba lagi.');

        // Hapus dokumen lama kalau sudah pernah upload (replace)
        if ($laporanKendalaTembusan->dokumen_balasan_path) {
            Storage::disk('public')->delete($laporanKendalaTembusan->dokumen_balasan_path);
        }

        $laporanKendalaTembusan->update([
            'dokumen_balasan_path'  => $path,
            'dokumen_balasan_nama'  => $file->getClientOriginalName(),
            'dokumen_balasan_at'    => now(),
            'dokumen_balasan_oleh'  => $user->id,
            // Kirim dokumen otomatis menandai "sudah dibaca" juga
            'dibaca_at'             => $laporanKendalaTembusan->dibaca_at ?? now(),
            'dibaca_oleh'           => $laporanKendalaTembusan->dibaca_oleh ?? $user->id,
        ]);

        $laporanKendala = $laporanKendalaTembusan->laporanKendala()->with('satuan')->first();

        // Notifikasi ke Kasansi bahwa dokumen balasan sudah dikirim
        if ($laporanKendala) {
            foreach (User::where('satuan_id', $laporanKendala->satuan_id)->get() as $penerima) {
                $penerima->notify(new LaporanKendalaTembusanFeedbackDiterima($laporanKendalaTembusan, $satuan));
            }
        }

        ActivityLog::catat('laporan-kendala-tembusan.dokumen-balasan', "Mengirim dokumen \"{$file->getClientOriginalName()}\" sebagai balasan tembusan kendala \"{$laporanKendala?->perihal}\".", $user, [
            'laporan_kendala_tembusan_id' => $laporanKendalaTembusan->id,
            'laporan_kendala_id'          => $laporanKendalaTembusan->laporan_kendala_id,
            'dokumen_balasan_nama'        => $file->getClientOriginalName(),
        ]);

        return back()->with('status', 'Dokumen berhasil dikirim ke '.($laporanKendala?->satuan?->nama ?? 'Kasansi').'.');
    }
}
