<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanKendala;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanKendalaBaruDiterima;
use App\Notifications\LaporanKendalaDikonfirmasi;
use App\Support\DecorativeSeparatorCleaner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Alur "Kirim Laporan" (kendala/laporan rutin) khusus 21 Kasansi (Kotama)
 * LANGSUNG ke DANPUS -- tanpa lewat Satlak, tanpa tembusan. Berbeda dari
 * LaporanController (yang terikat alur Permintaan Laporan Danpus/Wadan), fitur
 * ini bebas dikirim kapan saja oleh Kasansi tanpa perlu ada permintaan
 * laporan lebih dulu.
 *
 * ALUR: Menunggu Konfirmasi -> Dikonfirmasi.
 *   1. Kasansi mengirim laporan (lampiran opsional) -> langsung masuk daftar
 *      Danpus + Danpus diberi notifikasi.
 *   2. Danpus menekan Konfirmasi (satu-satunya aksi, konfirmasi()) -> status
 *      Dikonfirmasi + Kasansi diberi notifikasi. Proses selesai di sini.
 *
 * Laporan kendala memakai tabel/model sendiri supaya tidak pernah bercampur
 * dengan alur Permintaan Laporan. Setelah dikonfirmasi, record tampil di Arsip
 * Kendala Kasansi yang terpisah.
 */
class LaporanKendalaController extends Controller
{
    /**
     * Realtime kendala Kasansi -> Danpus, dipoll dari JS (bukan WebSocket).
     * Dua sisi beda kebutuhan: Danpus/Wadan cuma butuh kendala BARU sejak
     * `since` (kendala lama statusnya tidak berubah dari sisi mereka selain
     * lewat aksi mereka sendiri, yang sudah langsung update DOM tanpa poll).
     * Kasansi (pengirim) butuh SNAPSHOT PENUH kendala miliknya sendiri tiap
     * poll, karena yang berubah justru STATUS kendala yang sudah lama
     * terkirim (dikonfirmasi oleh Danpus) -- pola
     * sama seperti syncRequestList() di laporan-role-realtime-sync.blade.php.
     */
    public function realtime(Request $request): JsonResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        $kode = strtoupper((string) $satuan?->kode);
        $isPimpinan = in_array($kode, ['DANPUS', 'WADAN'], true);
        $isKasansi = in_array($kode, Satuan::KODE_KOTAMA, true);
        abort_unless($isPimpinan || $isKasansi, 403);

        if ($isPimpinan) {
            $danpusId = Satuan::where('kode', 'DANPUS')->value('id');
            $since = max(0, (int) $request->query('since', 0));

            // Laporan langsung masuk begitu Kasansi mengirim (tanpa tahap
            // perantara), jadi cukup yang belum dikonfirmasi.
            $items = $danpusId
                ? LaporanKendala::with(['satuan', 'lampirans'])
                    ->where('tujuan_satuan_id', $danpusId)
                    ->menungguKonfirmasi()
                    ->where('id', '>', $since)
                    ->orderBy('id')
                    ->get()
                : collect();

            $latestId = $danpusId
                ? (int) (LaporanKendala::where('tujuan_satuan_id', $danpusId)
                    ->menungguKonfirmasi()
                    ->max('id') ?? 0)
                : 0;

            // PENTING: items_html dikirim lewat JSON, jadi middleware
            // RemoveDecorativeSeparators (yang cuma jalan utk response
            // text/html) TIDAK PERNAH menyentuhnya. Kalau tidak dibersihkan
            // manual di sini, teks kartu ini bisa beda PERMANEN dari versi
            // yang dirender pas halaman pertama dibuka (yang sudah kena
            // middleware) -- signature() di sisi JS jadi selalu menganggap
            // kartunya "berubah" & kartu itu kedip terus tiap polling
            // walau datanya sama sekali tidak berubah. Lihat
            // DecorativeSeparatorCleaner utk detail lengkapnya.
            return response()->json([
                'latest_id' => $latestId,
                'items_html' => DecorativeSeparatorCleaner::clean($items->map(fn (LaporanKendala $k) => view('siberad.dashboards.partials.kendala-kasansi-row', ['k' => $k, 'satuan' => $satuan])->render())->implode('')),
            ], 200, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        }

        $items = LaporanKendala::with(['tujuanSatuan', 'confirmedBy', 'lampirans'])
            ->where('satuan_id', $satuan->id)
            ->latest()
            ->get();

        // Dipisah sama seperti $kendalaTerkirim/$kendalaArsip di
        // DashboardController: begitu Danpus menekan Konfirmasi & Arsipkan,
        // kendala harus pindah dari tab "Kirim Kendala" ke "Arsip Kendala"
        // di sisi Kasansi TANPA perlu reload halaman -- lihat
        // kendala-terkirim-realtime.blade.php yang mengonsumsi kedua key ini.
        $terkirim = $items->where('status', '!=', LaporanKendala::STATUS_DIKONFIRMASI)->values();
        $arsip = $items->where('status', LaporanKendala::STATUS_DIKONFIRMASI)->values();

        // Sama seperti items_html di atas: normalisasi manual di sini
        // supaya HTML kartu yang dikirim lewat JSON polling ini SELALU
        // sama persis dengan HTML kartu hasil render halaman pertama (yang
        // sudah lewat middleware RemoveDecorativeSeparators). Tanpa ini,
        // kartu dgn teks yang kena pola pembersihnya (mis. ada "-", "/",
        // atau spasi ganda di perihal/catatan) bakal kedip terus nonstop
        // tiap 3 detik walau tidak ada perubahan data sama sekali -- lihat
        // DecorativeSeparatorCleaner.
        return response()->json([
            'terkirim_items_html' => DecorativeSeparatorCleaner::clean($terkirim->map(fn (LaporanKendala $k) => view('siberad.dashboards.partials.kendala-terkirim-row', ['k' => $k, 'satuan' => $satuan])->render())->implode('')),
            'arsip_items_html' => DecorativeSeparatorCleaner::clean($arsip->map(fn (LaporanKendala $k) => view('siberad.dashboards.partials.kendala-terkirim-row', ['k' => $k, 'satuan' => $satuan])->render())->implode('')),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Total gabungan seluruh lampiran kendala dibatasi 10 MB (BEDA dari
     * batas per-file 10 MB di aturan validasi 'lampiran.*' -- itu cuma
     * jaring pengaman per-file, batas yang sebenarnya berlaku buat pengguna
     * adalah TOTAL di sini). Kasansi boleh melampirkan lebih dari 1 file
     * (PDF, Excel, Word, gambar, dll -- semua format) asal jumlahnya masih
     * di bawah batas ini.
     */
    private const LAMPIRAN_TOTAL_MAX_BYTES = 10 * 1024 * 1024;

    public function store(Request $request): RedirectResponse
    {
        // Lampiran OPSIONAL: laporan boleh dikirim hanya dengan perihal,
        // kategori (opsional), isi kendala, dan prioritas. Kalau ada lampiran,
        // aturan format/ukuran lama tetap berlaku (semua format, <=10 MB per
        // file, <=10 MB total). Tidak ada lagi tembusan -- field 'tembusan_ke'
        // yang masih dikirim klien lama diabaikan (tidak divalidasi/disimpan).
        $validated = $request->validate([
            'perihal' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:10000'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
            'lampiran' => ['nullable', 'array'],
            'lampiran.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $files = collect($request->file('lampiran', []))->filter();

        $totalLampiranBytes = $files->sum(fn ($file) => $file->getSize());
        abort_if(
            $totalLampiranBytes > self::LAMPIRAN_TOTAL_MAX_BYTES,
            422,
            'Total ukuran seluruh lampiran melebihi 10 MB. Kurangi jumlah/ukuran file lalu coba lagi.'
        );

        $user = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;
        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');
        abort_unless(
            in_array(strtoupper((string) $satuanAsal->kode), Satuan::KODE_KOTAMA, true),
            403,
            'Hanya Kasansi yang dapat mengirim laporan kendala ke Danpus.'
        );

        $tujuan = Satuan::where('kode', 'DANPUS')->firstOrFail();

        // Simpan SEMUA file lampiran yang dikirim (kalau ada) -- masing-masing
        // jadi 1 baris di tabel laporan_kendala_lampirans, path fisiknya tetap
        // di disk 'lampiran-kendala' yang sama seperti sebelumnya.
        $lampiranDisimpan = $files->map(function ($file) {
            $path = $file->store('lampiran-kendala', 'public');
            abort_if(! $path, 500, 'Gagal menyimpan file lampiran ke server. Coba lagi, atau hubungi Admin kalau masalah berlanjut.');

            return [
                'path' => $path,
                'nama_asli' => $file->getClientOriginalName(),
            ];
        });

        $kendala = null;
        DB::transaction(function () use (&$kendala, $satuanAsal, $user, $tujuan, $validated, $lampiranDisimpan) {
            $kendala = LaporanKendala::create([
                'satuan_id' => $satuanAsal->id,
                'user_id' => $user->id,
                'tujuan_satuan_id' => $tujuan->id,
                'perihal' => $validated['perihal'],
                'kategori' => $validated['kategori'] ?? null,
                'deskripsi' => $validated['deskripsi'],
                'prioritas' => $validated['prioritas'],
                'status' => LaporanKendala::STATUS_MENUNGGU_KONFIRMASI,
            ]);

            foreach ($lampiranDisimpan as $lampiran) {
                $kendala->lampirans()->create($lampiran);
            }
        });

        // Laporan langsung sampai ke Danpus -> Danpus langsung diberi tahu.
        foreach (User::where('satuan_id', $tujuan->id)->get() as $penerima) {
            $penerima->notify(new LaporanKendalaBaruDiterima($kendala));
        }

        ActivityLog::catat('laporan-kendala.create', "Mengirim laporan kendala \"{$kendala->perihal}\" ke {$tujuan->nama}.", $user, [
            'laporan_kendala_id' => $kendala->id,
            'tujuan_satuan'      => $tujuan->nama,
            'prioritas'          => $kendala->prioritas,
            'jumlah_lampiran'    => $lampiranDisimpan->count(),
        ]);

        return back()->with('status', 'Laporan kendala berhasil dikirim ke '.$tujuan->nama.' dan menunggu konfirmasi.');
    }

    /**
     * Satu-satunya aksi Danpus pada laporan kendala Kasansi: KONFIRMASI.
     * Menandai laporan diterima (status Dikonfirmasi + confirmed_at/by) dan
     * proses berakhir di sini -- tidak ada tindak lanjut, selesai, maupun
     * penolakan. Wadan tidak punya aksi di fitur ini.
     *
     * Aturan bisnis dijaga di backend (bukan cuma menyembunyikan tombol):
     * hanya Danpus, hanya laporan yang ditujukan ke Danpus, dan hanya sekali.
     */
    public function konfirmasi(Request $request, LaporanKendala $laporanKendala): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        abort_unless(
            strtoupper((string) $satuan->kode) === 'DANPUS',
            403,
            'Hanya Danpus yang dapat mengonfirmasi laporan kendala.'
        );
        abort_unless(
            (int) $laporanKendala->tujuan_satuan_id === (int) $satuan->id,
            403,
            'Laporan kendala ini bukan ditujukan ke Danpus.'
        );

        // UPDATE bersyarat (atomik) supaya dua klik/dua request bersamaan tidak
        // sama-sama "berhasil" mengonfirmasi laporan yang sama.
        $diperbarui = LaporanKendala::whereKey($laporanKendala->id)
            ->whereNull('confirmed_at')
            ->update([
                'status' => LaporanKendala::STATUS_DIKONFIRMASI,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
                'updated_at' => now(),
            ]);

        abort_if($diperbarui === 0, 422, 'Laporan kendala ini sudah dikonfirmasi.');

        $laporanKendala->refresh()->loadMissing('satuan');

        // Beri tahu Kasansi pengirim bahwa laporannya sudah dikonfirmasi.
        foreach (User::where('satuan_id', $laporanKendala->satuan_id)->get() as $penerima) {
            $penerima->notify(new LaporanKendalaDikonfirmasi($laporanKendala));
        }

        ActivityLog::catat('laporan-kendala.confirm', "Mengonfirmasi laporan kendala \"{$laporanKendala->perihal}\" dari {$laporanKendala->satuan->nama}.", $user, [
            'laporan_kendala_id' => $laporanKendala->id,
            'status' => LaporanKendala::STATUS_DIKONFIRMASI,
        ]);

        return back()->with('status', 'Laporan kendala berhasil dikonfirmasi.');
    }

    public function destroy(Request $request, LaporanKendala $laporanKendala): RedirectResponse
    {
        $user       = $request->user()->load('satuan');
        $satuan     = $user->satuan;
        abort_unless($satuan, 403);
        $kodeSatuan = strtoupper($satuan->kode ?? '');
        $isDanpus   = $kodeSatuan === 'DANPUS';

        if ($isDanpus) {
            // Danpus hanya boleh menghapus arsip (status Dikonfirmasi).
            abort_unless(
                $laporanKendala->status === LaporanKendala::STATUS_DIKONFIRMASI,
                403,
                'Danpus hanya dapat menghapus kendala yang sudah diarsipkan.'
            );
        } else {
            // Kasansi hanya boleh menghapus miliknya sendiri.
            abort_unless((int) $laporanKendala->satuan_id === (int) $satuan->id, 403);
        }

        if ($laporanKendala->lampiran_path) {
            Storage::disk('public')->delete($laporanKendala->lampiran_path);
        }
        foreach ($laporanKendala->lampirans as $lampiranLama) {
            Storage::disk('public')->delete($lampiranLama->path);
        }
        // Peninggalan alur lama: dokumen "siap kirim" Kasansi (kalau ada).
        if ($laporanKendala->dokumen_kasansi_path) {
            Storage::disk('public')->delete($laporanKendala->dokumen_kasansi_path);
        }
        $perihal = $laporanKendala->perihal;
        $laporanKendala->delete();

        $catatan = $isDanpus
            ? "Danpus menghapus arsip kendala \"{$perihal}\" dari riwayat."
            : "Menghapus laporan kendala \"{$perihal}\" dari riwayat.";

        ActivityLog::catat('laporan-kendala.delete', $catatan, $user, [
            'laporan_kendala_id' => $laporanKendala->id,
        ]);

        return back()->with('status', 'Laporan kendala berhasil dihapus.');
    }
}
