<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Postingan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostinganController extends Controller
{
    /**
     * Buat postingan baru. Satu form dipakai untuk tiga aksi sekaligus lewat
     * field "aksi" (dipilih user di tombol submit):
     * - simpan_draft : disimpan sebagai draft, belum tayang & belum dijadwal.
     * - jadwalkan    : butuh scheduled_at, tampil di tab Kalender Konten.
     * - terbitkan    : langsung berstatus Terbit hari ini juga.
     * Ketiganya jadi pusat untuk fitur "Membuat Posting", "Menjadwalkan
     * Posting", "Kalender Konten", dan "Upload Foto/Video".
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'akun_medsos_id' => ['required', 'exists:akun_medsos,id'],
            'judul' => ['required', 'string', 'max:255'],
            'caption' => ['required', 'string'],
            'jenis_konten' => ['required', 'in:Feed,Reels/Video,Story,Carousel'],
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,mp4,mov', 'max:51200'],
            'aksi' => ['required', 'in:simpan_draft,jadwalkan,terbitkan'],
            'scheduled_at' => ['required_if:aksi,jadwalkan', 'nullable', 'date', 'after_or_equal:now'],
        ]);

        $satuan = $request->user()->load('satuan')->satuan;
        abort_unless($satuan, 403, 'Akun ini belum terhubung ke satuan manapun.');

        // Pastikan akun medsos yang dipilih benar milik satuan yang login,
        // bukan milik satuan lain.
        $akun = $satuan->akunMedsos()->findOrFail($validated['akun_medsos_id']);

        $mediaPath = null;
        $mediaType = null;
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mediaPath = $file->store('postingan', 'public');
            $mediaType = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'foto';
        }

        $status = match ($validated['aksi']) {
            'jadwalkan' => 'Terjadwal',
            'terbitkan' => 'Terbit',
            default => 'Draft',
        };

        $posting = Postingan::create([
            'akun_medsos_id' => $akun->id,
            'satuan_id' => $satuan->id,
            'user_id' => $request->user()->id,
            'judul' => $validated['judul'],
            'caption' => $validated['caption'],
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'jenis_konten' => $validated['jenis_konten'],
            'status' => $status,
            'scheduled_at' => $validated['aksi'] === 'jadwalkan' ? $validated['scheduled_at'] : null,
            'published_at' => $validated['aksi'] === 'terbitkan' ? now() : null,
        ]);

        $pesan = match ($validated['aksi']) {
            'jadwalkan' => 'Postingan berhasil dijadwalkan.',
            'terbitkan' => 'Postingan berhasil diterbitkan.',
            default => 'Postingan berhasil disimpan sebagai draft.',
        };

        ActivityLog::catat('postingan.'.$validated['aksi'], "Membuat postingan \"{$posting->judul}\" ({$status}).", $request->user(), [
            'postingan_id' => $posting->id,
        ]);

        return back()->with('status', $pesan);
    }

    /**
     * Terbitkan sekarang postingan yang sebelumnya berstatus Draft atau
     * Terjadwal (dipakai tombol "Terbitkan Sekarang" di tab Kalender Konten).
     */
    public function terbitkan(Request $request, Postingan $posting): RedirectResponse
    {
        $satuan = $request->user()->load('satuan')->satuan;
        abort_unless($satuan && $posting->satuan_id === $satuan->id, 403);

        $posting->update([
            'status' => 'Terbit',
            'published_at' => now(),
        ]);

        ActivityLog::catat('postingan.terbitkan', "Menerbitkan postingan \"{$posting->judul}\".", $request->user(), [
            'postingan_id' => $posting->id,
        ]);

        return back()->with('status', 'Postingan berhasil diterbitkan.');
    }

    /**
     * Perbarui angka engagement (like/komentar/share/dilihat) sebuah
     * postingan yang sudah terbit — dipakai tab "Monitoring Engagement"
     * untuk mencatat hasil pantauan manual dari masing-masing platform,
     * karena sistem belum tersambung API resmi tiap platform.
     */
    public function updateEngagement(Request $request, Postingan $posting): RedirectResponse
    {
        $satuan = $request->user()->load('satuan')->satuan;
        abort_unless($satuan && $posting->satuan_id === $satuan->id, 403);

        $validated = $request->validate([
            'likes' => ['required', 'integer', 'min:0'],
            'komentar' => ['required', 'integer', 'min:0'],
            'share' => ['required', 'integer', 'min:0'],
            'dilihat' => ['required', 'integer', 'min:0'],
        ]);

        $posting->update($validated);

        ActivityLog::catat('postingan.update-engagement', "Memperbarui data engagement postingan \"{$posting->judul}\".", $request->user(), [
            'postingan_id' => $posting->id,
        ]);

        return back()->with('status', 'Data engagement berhasil diperbarui.');
    }

    /**
     * Hapus postingan (dari draft, jadwal, maupun arsip yang sudah terbit).
     */
    public function destroy(Request $request, Postingan $posting): RedirectResponse
    {
        $satuan = $request->user()->load('satuan')->satuan;
        abort_unless($satuan && $posting->satuan_id === $satuan->id, 403);

        if ($posting->media_path) {
            Storage::disk('public')->delete($posting->media_path);
        }

        $judul = $posting->judul;
        $postinganId = $posting->id;
        $posting->delete();

        ActivityLog::catat('postingan.delete', "Menghapus postingan \"{$judul}\".", $request->user(), [
            'postingan_id' => $postinganId,
        ]);

        return back()->with('status', 'Postingan berhasil dihapus.');
    }
}
