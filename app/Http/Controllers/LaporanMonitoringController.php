<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanMonitoring;
use App\Models\LaporanMonitoringLampiran;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanMonitoringBaruDiterima;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LaporanMonitoringController extends Controller
{
    /**
     * Simpan Laporan Monitoring & Recovery baru — dipakai fitur "Buat
     * Laporan Monitoring & Recovery". Bisa disimpan sebagai Draft (belum
     * dikirim) atau langsung dikirim ke DANPUS, tergantung nilai 'aksi'
     * dari form ("draft" | "kirim").
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_kegiatan' => ['required', 'string', 'max:100'],
            'tanggal_kegiatan' => ['required', 'date'],
            'ringkasan_kegiatan' => ['required', 'string'],
            'hasil' => ['required', 'string'],
            'aksi' => ['required', 'in:draft,kirim'],
            'lampiran' => ['nullable', 'array'],
            'lampiran.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:20480'],
        ]);

        $user = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;

        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');

        $danpus = Satuan::where('kode', 'DANPUS')->firstOrFail();
        $dikirim = $validated['aksi'] === 'kirim';

        $laporan = LaporanMonitoring::create([
            'satuan_id' => $satuanAsal->id,
            'user_id' => $user->id,
            'tujuan_satuan_id' => $danpus->id,
            'jenis_kegiatan' => $validated['jenis_kegiatan'],
            'tanggal_kegiatan' => $validated['tanggal_kegiatan'],
            'ringkasan_kegiatan' => $validated['ringkasan_kegiatan'],
            'hasil' => $validated['hasil'],
            'status' => $dikirim ? 'Dikirim' : 'Draft',
            'tanggal_kirim' => $dikirim ? now() : null,
        ]);

        $this->simpanLampiran($request, $laporan, $user->id);

        ActivityLog::catat(
            $dikirim ? 'laporan-monitoring.kirim' : 'laporan-monitoring.draft',
            ($dikirim ? 'Mengirim' : 'Menyimpan draft')." laporan monitoring & recovery \"{$laporan->jenis_kegiatan}\".",
            $user,
            ['laporan_monitoring_id' => $laporan->id],
        );

        if ($dikirim) {
            $this->notifikasiDanpus($laporan, $danpus);

            return back()->with('status', 'Laporan monitoring & recovery berhasil dikirim ke DANPUS.');
        }

        return back()->with('status', 'Laporan disimpan sebagai draft.');
    }

    /**
     * Perbarui isi laporan — dipakai fitur "Draft Laporan" (edit sebelum
     * dikirim) maupun saat laporan berstatus "Direvisi" (edit sebelum
     * dikirim ulang). Hanya pemilik laporan yang boleh mengubah.
     */
    public function update(Request $request, LaporanMonitoring $laporanMonitoring): RedirectResponse
    {
        $this->pastikanBisaDiedit($request, $laporanMonitoring);

        $validated = $request->validate([
            'jenis_kegiatan' => ['required', 'string', 'max:100'],
            'tanggal_kegiatan' => ['required', 'date'],
            'ringkasan_kegiatan' => ['required', 'string'],
            'hasil' => ['required', 'string'],
            'lampiran' => ['nullable', 'array'],
            'lampiran.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:20480'],
        ]);

        $laporanMonitoring->update([
            'jenis_kegiatan' => $validated['jenis_kegiatan'],
            'tanggal_kegiatan' => $validated['tanggal_kegiatan'],
            'ringkasan_kegiatan' => $validated['ringkasan_kegiatan'],
            'hasil' => $validated['hasil'],
        ]);

        $this->simpanLampiran($request, $laporanMonitoring, $request->user()->id);

        ActivityLog::catat('laporan-monitoring.update', "Memperbarui laporan monitoring & recovery \"{$laporanMonitoring->jenis_kegiatan}\".", $request->user(), [
            'laporan_monitoring_id' => $laporanMonitoring->id,
        ]);

        return back()->with('status', 'Laporan berhasil diperbarui.');
    }

    /**
     * Kirim draft, atau kirim ulang laporan yang statusnya "Direvisi", ke
     * DANPUS. Status berubah menjadi "Dikirim" dan catatan revisi
     * sebelumnya (kalau ada) dikosongkan kembali.
     */
    public function kirim(Request $request, LaporanMonitoring $laporanMonitoring): RedirectResponse
    {
        $this->pastikanBisaDiedit($request, $laporanMonitoring);

        $danpus = Satuan::where('kode', 'DANPUS')->firstOrFail();

        $laporanMonitoring->update([
            'status' => 'Dikirim',
            'catatan_danpus' => null,
            'tanggal_kirim' => now(),
        ]);

        $this->notifikasiDanpus($laporanMonitoring, $danpus);

        ActivityLog::catat('laporan-monitoring.kirim', "Mengirim ulang laporan monitoring & recovery \"{$laporanMonitoring->jenis_kegiatan}\" ke DANPUS.", $request->user(), [
            'laporan_monitoring_id' => $laporanMonitoring->id,
        ]);

        return back()->with('status', 'Laporan berhasil dikirim ke DANPUS.');
    }

    /**
     * Unggah lampiran tambahan (foto/PDF/dokumen) ke laporan yang sudah
     * ada — dipakai fitur "Upload Lampiran", tersedia selama laporan
     * belum diputuskan DANPUS (Draft/Dikirim/Direvisi).
     */
    public function uploadLampiran(Request $request, LaporanMonitoring $laporanMonitoring): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        abort_unless($laporanMonitoring->user_id === $user->id, 403);
        abort_unless(in_array($laporanMonitoring->status, ['Draft', 'Dikirim', 'Direvisi'], true), 403,
            'Lampiran tidak bisa ditambahkan lagi karena laporan sudah diputuskan DANPUS.');

        $request->validate([
            'lampiran' => ['required', 'array', 'min:1'],
            'lampiran.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:20480'],
        ]);

        $this->simpanLampiran($request, $laporanMonitoring, $user->id);

        ActivityLog::catat('laporan-monitoring.upload-lampiran', "Mengunggah lampiran pada laporan monitoring & recovery \"{$laporanMonitoring->jenis_kegiatan}\".", $user, [
            'laporan_monitoring_id' => $laporanMonitoring->id,
        ]);

        return back()->with('status', 'Lampiran berhasil diunggah.');
    }

    /**
     * Hapus satu file lampiran dari laporan (mis. salah unggah).
     */
    public function destroyLampiran(Request $request, LaporanMonitoringLampiran $lampiran): RedirectResponse
    {
        $laporan = $lampiran->laporanMonitoring;
        abort_unless($laporan->user_id === $request->user()->id, 403);
        abort_unless(in_array($laporan->status, ['Draft', 'Dikirim', 'Direvisi'], true), 403);

        $namaFile = $lampiran->nama_file;
        Storage::disk('public')->delete($lampiran->path);
        $lampiran->delete();

        ActivityLog::catat('laporan-monitoring.hapus-lampiran', "Menghapus lampiran \"{$namaFile}\" dari laporan monitoring & recovery \"{$laporan->jenis_kegiatan}\".", $request->user(), [
            'laporan_monitoring_id' => $laporan->id,
        ]);

        return back()->with('status', 'Lampiran berhasil dihapus.');
    }

    /**
     * Hapus laporan — hanya untuk draft milik sendiri yang belum pernah
     * dikirim ke DANPUS.
     */
    public function destroy(Request $request, LaporanMonitoring $laporanMonitoring): RedirectResponse
    {
        abort_unless($laporanMonitoring->user_id === $request->user()->id, 403);
        abort_unless($laporanMonitoring->isDraft(), 403, 'Laporan yang sudah dikirim tidak bisa dihapus lagi.');

        foreach ($laporanMonitoring->lampiran as $l) {
            Storage::disk('public')->delete($l->path);
        }

        $jenisKegiatan = $laporanMonitoring->jenis_kegiatan;
        $laporanId = $laporanMonitoring->id;
        $laporanMonitoring->delete();

        ActivityLog::catat('laporan-monitoring.delete', "Menghapus draft laporan monitoring & recovery \"{$jenisKegiatan}\".", $request->user(), [
            'laporan_monitoring_id' => $laporanId,
        ]);

        return back()->with('status', 'Draft laporan berhasil dihapus.');
    }

    private function pastikanBisaDiedit(Request $request, LaporanMonitoring $laporanMonitoring): void
    {
        abort_unless($laporanMonitoring->user_id === $request->user()->id, 403);
        abort_unless($laporanMonitoring->bisaDiedit(), 403, 'Laporan yang sudah dikirim tidak bisa diubah lagi.');
    }

    private function simpanLampiran(Request $request, LaporanMonitoring $laporan, int $userId): void
    {
        if (! $request->hasFile('lampiran')) {
            return;
        }

        /** @var UploadedFile $file */
        foreach ($request->file('lampiran') as $file) {
            $path = $file->store('lampiran-laporan-monitoring', 'public');

            $tipe = match (true) {
                str_starts_with((string) $file->getMimeType(), 'image/') => 'image',
                $file->getClientOriginalExtension() === 'pdf' => 'pdf',
                default => 'dokumen',
            };

            LaporanMonitoringLampiran::create([
                'laporan_monitoring_id' => $laporan->id,
                'nama_file' => $file->getClientOriginalName(),
                'path' => $path,
                'tipe' => $tipe,
                'diunggah_oleh' => $userId,
            ]);
        }
    }

    private function notifikasiDanpus(LaporanMonitoring $laporan, Satuan $danpus): void
    {
        $penerima = User::where('satuan_id', $danpus->id)->get();
        foreach ($penerima as $u) {
            $u->notify(new LaporanMonitoringBaruDiterima($laporan));
        }
    }
}
