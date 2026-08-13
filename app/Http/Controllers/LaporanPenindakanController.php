<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanPenindakan;
use App\Models\LaporanPenindakanBukti;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanPenindakanBaruDiterima;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LaporanPenindakanController extends Controller
{
    /**
     * Simpan Laporan Penanganan Insiden baru — dipakai fitur "Buat Laporan
     * Penanganan Insiden". Bisa disimpan sebagai Draft (belum dikirim) atau
     * langsung dikirim ke DANPUS, tergantung nilai 'aksi' dari form
     * ("draft" | "kirim").
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'aset' => ['nullable', 'string', 'max:255'],
            'jenis_ancaman' => ['nullable', 'string', 'max:100'],
            'perihal' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'tindakan' => ['nullable', 'string'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
            'aksi' => ['required', 'in:draft,kirim'],
            'bukti' => ['nullable', 'array'],
            'bukti.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx,zip,log,txt', 'max:20480'],
        ]);

        $user = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;

        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');

        $danpus = Satuan::where('kode', 'DANPUS')->firstOrFail();
        $dikirim = $validated['aksi'] === 'kirim';

        $laporan = LaporanPenindakan::create([
            'satuan_id' => $satuanAsal->id,
            'user_id' => $user->id,
            'tujuan_satuan_id' => $danpus->id,
            'aset' => $validated['aset'] ?? null,
            'jenis_ancaman' => $validated['jenis_ancaman'] ?? null,
            'perihal' => $validated['perihal'],
            'deskripsi' => $validated['deskripsi'],
            'tindakan' => $validated['tindakan'] ?? null,
            'prioritas' => $validated['prioritas'],
            'status' => $dikirim ? 'Dikirim' : 'Draft',
            'tanggal_kirim' => $dikirim ? now() : null,
        ]);

        $this->simpanBukti($request, $laporan, $user->id);

        ActivityLog::catat(
            $dikirim ? 'laporan-penindakan.kirim' : 'laporan-penindakan.draft',
            ($dikirim ? 'Mengirim' : 'Menyimpan draft')." laporan penanganan insiden \"{$laporan->perihal}\".",
            $user,
            ['laporan_penindakan_id' => $laporan->id],
        );

        if ($dikirim) {
            $this->notifikasiDanpus($laporan, $danpus);

            return back()->with('status', 'Laporan penanganan insiden berhasil dikirim ke DANPUS.');
        }

        return back()->with('status', 'Laporan disimpan sebagai draft.');
    }

    /**
     * Perbarui isi laporan — dipakai fitur "Draft Laporan" (edit sebelum
     * dikirim) maupun saat laporan berstatus "Direvisi" (edit sebelum
     * dikirim ulang). Hanya pemilik laporan yang boleh mengubah.
     */
    public function update(Request $request, LaporanPenindakan $laporanPenindakan): RedirectResponse
    {
        $this->pastikanBisaDiedit($request, $laporanPenindakan);

        $validated = $request->validate([
            'aset' => ['nullable', 'string', 'max:255'],
            'jenis_ancaman' => ['nullable', 'string', 'max:100'],
            'perihal' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'tindakan' => ['nullable', 'string'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
            'bukti' => ['nullable', 'array'],
            'bukti.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx,zip,log,txt', 'max:20480'],
        ]);

        $laporanPenindakan->update([
            'aset' => $validated['aset'] ?? null,
            'jenis_ancaman' => $validated['jenis_ancaman'] ?? null,
            'perihal' => $validated['perihal'],
            'deskripsi' => $validated['deskripsi'],
            'tindakan' => $validated['tindakan'] ?? null,
            'prioritas' => $validated['prioritas'],
        ]);

        $this->simpanBukti($request, $laporanPenindakan, $request->user()->id);

        ActivityLog::catat('laporan-penindakan.update', "Memperbarui laporan penanganan insiden \"{$laporanPenindakan->perihal}\".", $request->user(), [
            'laporan_penindakan_id' => $laporanPenindakan->id,
        ]);

        return back()->with('status', 'Laporan berhasil diperbarui.');
    }

    /**
     * Kirim draft, atau kirim ulang laporan yang statusnya "Direvisi", ke
     * DANPUS. Status berubah menjadi "Dikirim" dan catatan revisi
     * sebelumnya (kalau ada) dikosongkan kembali.
     */
    public function kirim(Request $request, LaporanPenindakan $laporanPenindakan): RedirectResponse
    {
        $this->pastikanBisaDiedit($request, $laporanPenindakan);

        $danpus = Satuan::where('kode', 'DANPUS')->firstOrFail();

        $laporanPenindakan->update([
            'status' => 'Dikirim',
            'catatan_danpus' => null,
            'tanggal_kirim' => now(),
        ]);

        $this->notifikasiDanpus($laporanPenindakan, $danpus);

        ActivityLog::catat('laporan-penindakan.kirim', "Mengirim ulang laporan penanganan insiden \"{$laporanPenindakan->perihal}\" ke DANPUS.", $request->user(), [
            'laporan_penindakan_id' => $laporanPenindakan->id,
        ]);

        return back()->with('status', 'Laporan berhasil dikirim ke DANPUS.');
    }

    /**
     * Unggah bukti digital tambahan (foto/PDF/log/ZIP forensik) ke laporan
     * yang sudah ada — dipakai fitur "Upload Bukti Digital", tersedia
     * selama laporan belum diputuskan DANPUS (Draft/Dikirim/Direvisi).
     */
    public function uploadBukti(Request $request, LaporanPenindakan $laporanPenindakan): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        abort_unless($laporanPenindakan->user_id === $user->id, 403);
        abort_unless(in_array($laporanPenindakan->status, ['Draft', 'Dikirim', 'Direvisi'], true), 403,
            'Bukti digital tidak bisa ditambahkan lagi karena laporan sudah diputuskan DANPUS.');

        $request->validate([
            'bukti' => ['required', 'array', 'min:1'],
            'bukti.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx,zip,log,txt', 'max:20480'],
        ]);

        $this->simpanBukti($request, $laporanPenindakan, $user->id);

        ActivityLog::catat('laporan-penindakan.upload-bukti', "Mengunggah bukti digital pada laporan penanganan insiden \"{$laporanPenindakan->perihal}\".", $user, [
            'laporan_penindakan_id' => $laporanPenindakan->id,
        ]);

        return back()->with('status', 'Bukti digital berhasil diunggah.');
    }

    /**
     * Hapus satu file bukti digital dari laporan (mis. salah unggah).
     */
    public function destroyBukti(Request $request, LaporanPenindakanBukti $bukti): RedirectResponse
    {
        $laporan = $bukti->laporanPenindakan;
        abort_unless($laporan->user_id === $request->user()->id, 403);
        abort_unless(in_array($laporan->status, ['Draft', 'Dikirim', 'Direvisi'], true), 403);

        $namaFile = $bukti->nama_file;
        Storage::disk('public')->delete($bukti->path);
        $bukti->delete();

        ActivityLog::catat('laporan-penindakan.hapus-bukti', "Menghapus bukti digital \"{$namaFile}\" dari laporan penanganan insiden \"{$laporan->perihal}\".", $request->user(), [
            'laporan_penindakan_id' => $laporan->id,
        ]);

        return back()->with('status', 'Bukti digital berhasil dihapus.');
    }

    /**
     * Hapus laporan — hanya untuk draft milik sendiri yang belum pernah
     * dikirim ke DANPUS.
     */
    public function destroy(Request $request, LaporanPenindakan $laporanPenindakan): RedirectResponse
    {
        abort_unless($laporanPenindakan->user_id === $request->user()->id, 403);
        abort_unless($laporanPenindakan->isDraft(), 403, 'Laporan yang sudah dikirim tidak bisa dihapus lagi.');

        foreach ($laporanPenindakan->bukti as $b) {
            Storage::disk('public')->delete($b->path);
        }

        $perihal = $laporanPenindakan->perihal;
        $laporanId = $laporanPenindakan->id;
        $laporanPenindakan->delete();

        ActivityLog::catat('laporan-penindakan.delete', "Menghapus draft laporan penanganan insiden \"{$perihal}\".", $request->user(), [
            'laporan_penindakan_id' => $laporanId,
        ]);

        return back()->with('status', 'Draft laporan berhasil dihapus.');
    }

    private function pastikanBisaDiedit(Request $request, LaporanPenindakan $laporanPenindakan): void
    {
        abort_unless($laporanPenindakan->user_id === $request->user()->id, 403);
        abort_unless($laporanPenindakan->bisaDiedit(), 403, 'Laporan yang sudah dikirim tidak bisa diubah lagi.');
    }

    private function simpanBukti(Request $request, LaporanPenindakan $laporan, int $userId): void
    {
        if (! $request->hasFile('bukti')) {
            return;
        }

        /** @var UploadedFile $file */
        foreach ($request->file('bukti') as $file) {
            $path = $file->store('bukti-laporan-penindakan', 'public');

            $tipe = match (true) {
                str_starts_with((string) $file->getMimeType(), 'image/') => 'image',
                $file->getClientOriginalExtension() === 'pdf' => 'pdf',
                default => 'dokumen',
            };

            LaporanPenindakanBukti::create([
                'laporan_penindakan_id' => $laporan->id,
                'nama_file' => $file->getClientOriginalName(),
                'path' => $path,
                'tipe' => $tipe,
                'diunggah_oleh' => $userId,
            ]);
        }
    }

    private function notifikasiDanpus(LaporanPenindakan $laporan, Satuan $danpus): void
    {
        $penerima = User::where('satuan_id', $danpus->id)->get();
        foreach ($penerima as $u) {
            $u->notify(new LaporanPenindakanBaruDiterima($laporan));
        }
    }
}
