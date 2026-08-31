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
 * Alur "Kirim Surat" khusus 21 Kasansi (Kotama): kirim surat ke SATU satuan
 * tujuan bebas (dipilih sendiri dari seluruh satuan lain di sistem), TANPA
 * tembusan dan TANPA status/progres apa pun -- lihat komentar migration
 * create_laporan_surats_table & model LaporanSurat. Berbeda dari
 * LaporanKendalaController yang tujuannya tetap DANPUS dan masih punya
 * alur status Menunggu/Ditindaklanjuti/dst.
 *
 * Karena tidak ada progres, begitu surat dikirim langsung tercatat final:
 * sisi pengirim (Kasansi) melihatnya di Arsip Surat, sisi tujuan (satuan
 * mana pun) melihatnya di Surat Masuk.
 */
class LaporanSuratController extends Controller
{
    /**
     * Realtime surat, dipoll dari JS (bukan WebSocket) -- pola sama seperti
     * LaporanKendalaController::realtime(). Setiap satuan bisa jadi
     * penerima Surat Masuk (butuh item BARU sejak `since`), sedangkan
     * khusus Kasansi juga butuh snapshot penuh surat yang dia kirim sendiri
     * untuk tab Arsip Surat.
     */
    public function realtime(Request $request): JsonResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');

        $since = max(0, (int) $request->query('since', 0));

        $suratMasukBaru = LaporanSurat::with('satuan')
            ->where('tujuan_satuan_id', $satuan->id)
            ->where('id', '>', $since)
            ->orderBy('id')
            ->get();

        $payload = [
            'latest_id' => (int) (LaporanSurat::where('tujuan_satuan_id', $satuan->id)->max('id') ?? 0),
            'masuk_items_html' => $suratMasukBaru->map(fn (LaporanSurat $s) => view('siberad.dashboards.partials.surat-masuk-row', ['s' => $s])->render())->implode(''),
        ];

        $isKasansi = in_array(strtoupper((string) $satuan->kode), Satuan::KODE_KOTAMA, true);
        if ($isKasansi) {
            $terkirim = LaporanSurat::with('tujuanSatuan')->where('satuan_id', $satuan->id)->latest()->get();
            $payload['terkirim_items_html'] = $terkirim->map(fn (LaporanSurat $s) => view('siberad.dashboards.partials.surat-terkirim-row', ['s' => $s, 'satuan' => $satuan])->render())->implode('');
        }

        return response()->json($payload, 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;
        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');
        abort_unless(
            in_array(strtoupper((string) $satuanAsal->kode), Satuan::KODE_KOTAMA, true),
            403,
            'Hanya Kasansi yang dapat mengirim Surat.'
        );

        $validated = $request->validate([
            'tujuan_satuan_id' => ['required', 'integer', 'exists:satuans,id'],
            'perihal' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:10000'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
            'lampiran' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'tujuan_satuan_id.required' => 'Tujuan surat wajib dipilih.',
            'lampiran.required' => 'Lampiran wajib diisi untuk mengirim Surat.',
        ]);

        // Tujuan tidak boleh satuan sendiri -- divalidasi manual (bukan
        // lewat rule "different") karena satuan asal bukan input form.
        abort_if(
            (int) $validated['tujuan_satuan_id'] === (int) $satuanAsal->id,
            422,
            'Tujuan surat tidak boleh satuan sendiri.'
        );

        $tujuan = Satuan::findOrFail($validated['tujuan_satuan_id']);

        $lampiranPath = $request->file('lampiran')->store('lampiran-surat', 'public');
        abort_if(! $lampiranPath, 500, 'Gagal menyimpan file lampiran ke server. Coba lagi, atau hubungi Admin kalau masalah berlanjut.');

        $surat = LaporanSurat::create([
            'satuan_id' => $satuanAsal->id,
            'user_id' => $user->id,
            'tujuan_satuan_id' => $tujuan->id,
            'perihal' => $validated['perihal'],
            'kategori' => $validated['kategori'] ?? null,
            'deskripsi' => $validated['deskripsi'],
            'prioritas' => $validated['prioritas'],
            'lampiran_path' => $lampiranPath,
        ]);

        foreach (User::where('satuan_id', $tujuan->id)->get() as $penerima) {
            $penerima->notify(new LaporanSuratBaruDiterima($surat));
        }

        ActivityLog::catat('laporan-surat.create', "Mengirim surat \"{$surat->perihal}\" ke {$tujuan->nama}.", $user, [
            'laporan_surat_id' => $surat->id,
            'tujuan_satuan' => $tujuan->nama,
            'prioritas' => $surat->prioritas,
        ]);

        return back()->with('status', 'Surat berhasil dikirim ke '.$tujuan->nama.'.');
    }

    public function destroy(Request $request, LaporanSurat $laporanSurat): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);
        // Surat cuma boleh dihapus dari Arsip Surat milik satuan pengirim
        // sendiri -- satuan tujuan cukup melihat di Surat Masuk, tanpa hak
        // menghapus punya orang lain.
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
