<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanSurat;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanSuratBaruDiterima;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Alur "Kirim Surat" -- boleh dipakai oleh 21 Kasansi (Kotama) MAUPUN
 * Danpus/Wadan (lihat $bisaKirimSurat di realtime() & pengecekan $kodeAsal
 * di store()):
 *   - Pengirim mengirim surat ke SATU satuan tujuan.
 *   - Surat awalnya berstatus 'menunggu_konfirmasi' dan tampil di tabel
 *     Kirim Surat (bukan Arsip Surat) sisi pengirim.
 *   - Penerima melihat surat di Surat Masuk (dalam grup menu Surat) dan
 *     dapat mengkonfirmasi surat lewat tombol Konfirmasi di dalam modal
 *     Detail (bukan tombol terpisah di baris tabel) -- lihat
 *     surat-masuk-row.blade.php & window.bukaKonfirmasiSurat().
 *   - Setelah dikonfirmasi, surat pindah ke Arsip Surat sisi pengirim.
 */
class LaporanSuratController extends Controller
{
    /**
     * Realtime poll dari JS -- pola sama dengan LaporanKendalaController.
     * Sisi penerima: item baru di Surat Masuk sejak `since`.
     * Sisi Kasansi: snapshot penuh terpisah antara suratTerkirim
     *               (menunggu_konfirmasi) dan suratArsip (dikonfirmasi).
     */
    public function realtime(Request $request): JsonResponse
    {
        $user   = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');

        $since = max(0, (int) $request->query('since', 0));

        $suratMasukBaru = LaporanSurat::with('satuan')
            ->where('tujuan_satuan_id', $satuan->id)
            ->where('id', '>', $since)
            ->orderBy('id')
            ->get();

        $payload = [
            'latest_id'        => (int) (LaporanSurat::where('tujuan_satuan_id', $satuan->id)->max('id') ?? 0),
            'masuk_items_html' => $suratMasukBaru->map(
                fn (LaporanSurat $s) => view('siberad.dashboards.partials.surat-masuk-row', ['s' => $s])->render()
            )->implode(''),
        ];

        $kodeSatuan     = strtoupper((string) $satuan->kode);
        $bisaKirimSurat = in_array($kodeSatuan, Satuan::KODE_KOTAMA, true) || in_array($kodeSatuan, ['DANPUS', 'WADAN'], true);
        if ($bisaKirimSurat) {
            // Kirim Surat: hanya yang masih menunggu konfirmasi
            $terkirim = LaporanSurat::with('tujuanSatuan')
                ->where('satuan_id', $satuan->id)
                ->where('status', LaporanSurat::STATUS_MENUNGGU)
                ->latest()
                ->get();

            // Arsip Surat: hanya yang sudah dikonfirmasi
            $arsip = LaporanSurat::with('tujuanSatuan')
                ->where('satuan_id', $satuan->id)
                ->where('status', LaporanSurat::STATUS_DIKONFIRMASI)
                ->latest()
                ->get();

            $payload['terkirim_items_html'] = $terkirim->map(
                fn (LaporanSurat $s) => view('siberad.dashboards.partials.surat-terkirim-row', ['s' => $s, 'satuan' => $satuan])->render()
            )->implode('');

            $payload['arsip_items_html'] = $arsip->map(
                fn (LaporanSurat $s) => view('siberad.dashboards.partials.surat-arsip-row', ['s' => $s, 'satuan' => $satuan])->render()
            )->implode('');
        }

        return response()->json($payload, 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user       = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;
        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');
        $kodeAsal = strtoupper((string) $satuanAsal->kode);
        abort_unless(
            in_array($kodeAsal, Satuan::KODE_KOTAMA, true) || in_array($kodeAsal, ['DANPUS', 'WADAN'], true),
            403,
            'Hanya Kasansi atau Danpus/Wadan yang dapat mengirim Surat.'
        );

        $validated = $request->validate([
            'tujuan_satuan_id' => ['required', 'integer', 'exists:satuans,id'],
            'perihal'          => ['required', 'string', 'max:255'],
            'kategori'         => ['nullable', 'string', 'max:255'],
            'deskripsi'        => ['required', 'string', 'max:10000'],
            'prioritas'        => ['required', 'in:Tinggi,Sedang,Rendah'],
            'lampiran'         => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'tujuan_satuan_id.required' => 'Tujuan surat wajib dipilih.',
            'lampiran.required'         => 'Lampiran wajib diisi untuk mengirim Surat.',
        ]);

        abort_if(
            (int) $validated['tujuan_satuan_id'] === (int) $satuanAsal->id,
            422,
            'Tujuan surat tidak boleh satuan sendiri.'
        );

        $tujuan = Satuan::findOrFail($validated['tujuan_satuan_id']);

        $lampiranPath = $request->file('lampiran')->store('lampiran-surat', 'public');
        abort_if(! $lampiranPath, 500, 'Gagal menyimpan file lampiran ke server.');

        $surat = LaporanSurat::create([
            'satuan_id'        => $satuanAsal->id,
            'user_id'          => $user->id,
            'tujuan_satuan_id' => $tujuan->id,
            'perihal'          => $validated['perihal'],
            'kategori'         => $validated['kategori'] ?? null,
            'deskripsi'        => $validated['deskripsi'],
            'prioritas'        => $validated['prioritas'],
            'lampiran_path'    => $lampiranPath,
            'status'           => LaporanSurat::STATUS_MENUNGGU,
        ]);

        foreach (User::where('satuan_id', $tujuan->id)->get() as $penerima) {
            $penerima->notify(new LaporanSuratBaruDiterima($surat));
        }

        ActivityLog::catat('laporan-surat.create', "Mengirim surat \"{$surat->perihal}\" ke {$tujuan->nama}.", $user, [
            'laporan_surat_id' => $surat->id,
            'tujuan_satuan'    => $tujuan->nama,
            'prioritas'        => $surat->prioritas,
        ]);

        return back()->with('status', 'Surat berhasil dikirim ke '.$tujuan->nama.'. Menunggu konfirmasi dari penerima.');
    }

    /**
     * Konfirmasi surat oleh penerima.
     * Hanya satuan tujuan yang boleh mengkonfirmasi.
     * Setelah dikonfirmasi, surat pindah dari Kirim Surat ke Arsip Surat
     * di sisi pengirim.
     */
    public function konfirmasi(Request $request, LaporanSurat $laporanSurat): RedirectResponse
    {
        $user   = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);

        // Hanya penerima (tujuan_satuan_id) yang boleh konfirmasi
        abort_unless((int) $laporanSurat->tujuan_satuan_id === (int) $satuan->id, 403, 'Hanya penerima yang dapat mengkonfirmasi surat ini.');
        abort_if($laporanSurat->isDikonfirmasi(), 422, 'Surat ini sudah dikonfirmasi sebelumnya.');

        $laporanSurat->update([
            'status'            => LaporanSurat::STATUS_DIKONFIRMASI,
            'dikonfirmasi_at'   => now(),
            'dikonfirmasi_oleh' => $user->id,
        ]);

        ActivityLog::catat('laporan-surat.konfirmasi', "Mengkonfirmasi surat \"{$laporanSurat->perihal}\" dari {$laporanSurat->satuan->nama}.", $user, [
            'laporan_surat_id' => $laporanSurat->id,
            'pengirim_satuan'  => $laporanSurat->satuan->nama,
        ]);

        return back()->with('status', 'Surat "'.$laporanSurat->perihal.'" berhasil dikonfirmasi.');
    }

    public function destroy(Request $request, LaporanSurat $laporanSurat): RedirectResponse
    {
        $user   = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);
        // Hanya pengirim (satuan asal) yang boleh menghapus dari Arsip Surat
        abort_unless((int) $laporanSurat->satuan_id === (int) $satuan->id, 403);

        if ($laporanSurat->lampiran_path) {
            Storage::disk('public')->delete($laporanSurat->lampiran_path);
        }
        $perihal = $laporanSurat->perihal;
        $laporanSurat->delete();

        ActivityLog::catat('laporan-surat.delete', "Menghapus surat \"{$perihal}\" dari Arsip Surat.", $user, [
            'laporan_surat_id' => $laporanSurat->id,
        ]);

        return back()->with('status', 'Surat berhasil dihapus dari Arsip Surat.');
    }
}
