<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanPublikasi;
use App\Models\LaporanPublikasiDokumen;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanPublikasiBaruDiterima;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LaporanPublikasiController extends Controller
{
    /**
     * Simpan Laporan Publikasi baru — dipakai fitur "Buat Laporan Publikasi".
     * Bisa disimpan sebagai Draft (belum dikirim) atau langsung dikirim ke
     * DANPUS, tergantung nilai 'aksi' dari form ("draft" | "kirim").
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:100'],
            'link_publikasi' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'aksi' => ['required', 'in:draft,kirim'],
            'dokumentasi' => ['nullable', 'array'],
            'dokumentasi.*' => ['file', 'mimes:jpg,jpeg,png,mp4,mov,pdf', 'max:20480'],
        ]);

        $user = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;

        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');

        $danpus = Satuan::where('kode', 'DANPUS')->firstOrFail();
        $dikirim = $validated['aksi'] === 'kirim';

        $laporan = LaporanPublikasi::create([
            'satuan_id' => $satuanAsal->id,
            'user_id' => $user->id,
            'tujuan_satuan_id' => $danpus->id,
            'judul' => $validated['judul'],
            'platform' => $validated['platform'] ?? null,
            'link_publikasi' => $validated['link_publikasi'] ?? null,
            'deskripsi' => $validated['deskripsi'],
            'status' => $dikirim ? 'Menunggu' : 'Draft',
            'tanggal_kirim' => $dikirim ? now() : null,
        ]);

        $this->simpanDokumentasi($request, $laporan, $user->id);

        ActivityLog::catat(
            $dikirim ? 'laporan-publikasi.kirim' : 'laporan-publikasi.draft',
            ($dikirim ? 'Mengirim' : 'Menyimpan draft')." laporan publikasi \"{$laporan->judul}\".",
            $user,
            ['laporan_publikasi_id' => $laporan->id],
        );

        if ($dikirim) {
            $this->notifikasiDanpus($laporan, $danpus);

            return back()->with('status', 'Laporan publikasi berhasil dikirim ke DANPUS.');
        }

        return back()->with('status', 'Laporan publikasi disimpan sebagai draft.');
    }

    /**
     * Perbarui isi draft — dipakai fitur "Draft Laporan" (edit sebelum
     * dikirim). Hanya pemilik draft yang boleh mengubah, dan hanya selama
     * statusnya masih Draft.
     */
    public function update(Request $request, LaporanPublikasi $laporanPublikasi): RedirectResponse
    {
        $this->pastikanPemilikDraft($request, $laporanPublikasi);

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:100'],
            'link_publikasi' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'dokumentasi' => ['nullable', 'array'],
            'dokumentasi.*' => ['file', 'mimes:jpg,jpeg,png,mp4,mov,pdf', 'max:20480'],
        ]);

        $laporanPublikasi->update([
            'judul' => $validated['judul'],
            'platform' => $validated['platform'] ?? null,
            'link_publikasi' => $validated['link_publikasi'] ?? null,
            'deskripsi' => $validated['deskripsi'],
        ]);

        $this->simpanDokumentasi($request, $laporanPublikasi, $request->user()->id);

        ActivityLog::catat('laporan-publikasi.update', "Memperbarui draft laporan publikasi \"{$laporanPublikasi->judul}\".", $request->user(), [
            'laporan_publikasi_id' => $laporanPublikasi->id,
        ]);

        return back()->with('status', 'Draft laporan publikasi berhasil diperbarui.');
    }

    /**
     * Kirim draft ke DANPUS — mengubah status Draft menjadi Menunggu dan
     * memicu notifikasi ke seluruh akun DANPUS.
     */
    public function kirim(Request $request, LaporanPublikasi $laporanPublikasi): RedirectResponse
    {
        $this->pastikanPemilikDraft($request, $laporanPublikasi);

        $danpus = Satuan::where('kode', 'DANPUS')->firstOrFail();

        $laporanPublikasi->update([
            'status' => 'Menunggu',
            'tanggal_kirim' => now(),
        ]);

        $this->notifikasiDanpus($laporanPublikasi, $danpus);

        ActivityLog::catat('laporan-publikasi.kirim', "Mengirim draft laporan publikasi \"{$laporanPublikasi->judul}\" ke DANPUS.", $request->user(), [
            'laporan_publikasi_id' => $laporanPublikasi->id,
        ]);

        return back()->with('status', 'Draft berhasil dikirim ke DANPUS.');
    }

    /**
     * Unggah dokumentasi tambahan (foto/video/PDF) ke laporan yang sudah
     * ada — dipakai fitur "Upload Dokumentasi", tersedia selama laporan
     * belum diputuskan DANPUS (Draft/Menunggu).
     */
    public function uploadDokumentasi(Request $request, LaporanPublikasi $laporanPublikasi): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        abort_unless($laporanPublikasi->user_id === $user->id, 403);
        abort_unless(in_array($laporanPublikasi->status, ['Draft', 'Menunggu'], true), 403,
            'Dokumentasi tidak bisa ditambahkan lagi karena laporan sudah diputuskan DANPUS.');

        $request->validate([
            'dokumentasi' => ['required', 'array', 'min:1'],
            'dokumentasi.*' => ['file', 'mimes:jpg,jpeg,png,mp4,mov,pdf', 'max:20480'],
        ]);

        $this->simpanDokumentasi($request, $laporanPublikasi, $user->id);

        ActivityLog::catat('laporan-publikasi.upload-dokumentasi', "Mengunggah dokumentasi pada laporan publikasi \"{$laporanPublikasi->judul}\".", $user, [
            'laporan_publikasi_id' => $laporanPublikasi->id,
        ]);

        return back()->with('status', 'Dokumentasi berhasil diunggah.');
    }

    /**
     * Hapus satu file dokumentasi dari laporan (mis. salah unggah).
     */
    public function destroyDokumentasi(Request $request, LaporanPublikasiDokumen $dokumen): RedirectResponse
    {
        $laporan = $dokumen->laporanPublikasi;
        abort_unless($laporan->user_id === $request->user()->id, 403);
        abort_unless(in_array($laporan->status, ['Draft', 'Menunggu'], true), 403);

        $namaFile = $dokumen->nama_file;
        Storage::disk('public')->delete($dokumen->path);
        $dokumen->delete();

        ActivityLog::catat('laporan-publikasi.hapus-dokumentasi', "Menghapus dokumentasi \"{$namaFile}\" dari laporan publikasi \"{$laporan->judul}\".", $request->user(), [
            'laporan_publikasi_id' => $laporan->id,
        ]);

        return back()->with('status', 'Dokumentasi berhasil dihapus.');
    }

    /**
     * Hapus laporan publikasi — hanya untuk draft milik sendiri yang belum
     * pernah dikirim ke DANPUS.
     */
    public function destroy(Request $request, LaporanPublikasi $laporanPublikasi): RedirectResponse
    {
        $this->pastikanPemilikDraft($request, $laporanPublikasi);

        foreach ($laporanPublikasi->dokumentasi as $dok) {
            Storage::disk('public')->delete($dok->path);
        }

        $judul = $laporanPublikasi->judul;
        $laporanId = $laporanPublikasi->id;
        $laporanPublikasi->delete();

        ActivityLog::catat('laporan-publikasi.delete', "Menghapus draft laporan publikasi \"{$judul}\".", $request->user(), [
            'laporan_publikasi_id' => $laporanId,
        ]);

        return back()->with('status', 'Draft laporan publikasi berhasil dihapus.');
    }

    private function pastikanPemilikDraft(Request $request, LaporanPublikasi $laporanPublikasi): void
    {
        abort_unless($laporanPublikasi->user_id === $request->user()->id, 403);
        abort_unless($laporanPublikasi->isDraft(), 403, 'Laporan yang sudah dikirim tidak bisa diubah lagi.');
    }

    private function simpanDokumentasi(Request $request, LaporanPublikasi $laporan, int $userId): void
    {
        if (! $request->hasFile('dokumentasi')) {
            return;
        }

        /** @var UploadedFile $file */
        foreach ($request->file('dokumentasi') as $file) {
            $path = $file->store('dokumentasi-laporan-publikasi', 'public');

            $tipe = match (true) {
                str_starts_with((string) $file->getMimeType(), 'image/') => 'image',
                str_starts_with((string) $file->getMimeType(), 'video/') => 'video',
                default => 'pdf',
            };

            LaporanPublikasiDokumen::create([
                'laporan_publikasi_id' => $laporan->id,
                'nama_file' => $file->getClientOriginalName(),
                'path' => $path,
                'tipe' => $tipe,
                'diunggah_oleh' => $userId,
            ]);
        }
    }

    private function notifikasiDanpus(LaporanPublikasi $laporan, Satuan $danpus): void
    {
        $penerima = User::where('satuan_id', $danpus->id)->get();
        foreach ($penerima as $u) {
            $u->notify(new LaporanPublikasiBaruDiterima($laporan));
        }
    }
}
