<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanKendala;
use App\Models\LaporanKendalaTembusan;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanKendalaBaruDiterima;
use App\Notifications\LaporanKendalaTembusanBaru;
use App\Support\DecorativeSeparatorCleaner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Alur "Kirim Laporan" (kendala/laporan rutin) khusus 21 Kasansi (Kotama)
 * LANGSUNG ke DANPUS -- tanpa lewat Satlak. Berbeda dari LaporanController
 * (yang terikat alur Permintaan Laporan Danpus/Wadan), fitur ini bebas dikirim
 * kapan saja oleh Kasansi tanpa perlu ada permintaan laporan lebih dulu.
 *
 * Laporan kendala memakai tabel/model sendiri supaya tidak pernah bercampur
 * dengan alur Permintaan Laporan. Setelah Danpus menekan Konfirmasi pada
 * detail, record diberi tanda konfirmasi dan ditampilkan di Arsip Kendala
 * Kasansi yang terpisah.
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
     * terkirim (ditindaklanjuti/ditolak/selesai oleh Danpus/Wadan) -- pola
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

            // Danpus/Wadan tidak boleh melihat (apalagi dipoll realtime)
            // laporan yang masih mampir di tembusan -- baru muncul di sini
            // begitu Kasansi menekan "Kirim ke Danpus" lewat teruskan().
            $items = $danpusId
                ? LaporanKendala::with(['satuan', 'lampirans'])
                    ->where('tujuan_satuan_id', $danpusId)
                    ->whereNull('confirmed_at')
                    ->where('status', '!=', LaporanKendala::STATUS_MENUNGGU_TEMBUSAN)
                    ->where('id', '>', $since)
                    ->orderBy('id')
                    ->get()
                : collect();

            $latestId = $danpusId
                ? (int) (LaporanKendala::where('tujuan_satuan_id', $danpusId)
                    ->whereNull('confirmed_at')
                    ->where('status', '!=', LaporanKendala::STATUS_MENUNGGU_TEMBUSAN)
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

        $items = LaporanKendala::with(['tujuanSatuan', 'tembusans.satuan', 'lampirans'])
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
        // Lampiran WAJIB untuk laporan kendala Kasansi -> Danpus (beda dari
        // alur "Kirim Laporan" biasa yang lampirannya opsional). Divalidasi
        // lagi di sini sebagai jaring pengaman -- validasi di frontend
        // (modal peringatan sebelum submit) bisa saja terlewat kalau ada
        // yang mengirim request langsung tanpa lewat form.
        $validated = $request->validate([
            'perihal' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:10000'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
            // Semua format file diterima (bukan cuma PDF lagi) dan boleh
            // lebih dari 1 file -- batas 10240 KB per file di sini sekadar
            // jaring pengaman, batas TOTAL 10 MB gabungan semua file
            // divalidasi manual di bawah (Validator bawaan Laravel tidak
            // punya aturan "jumlah ukuran array file").
            'lampiran' => ['required', 'array', 'min:1'],
            'lampiran.*' => ['file', 'max:10240'],
            // Tembusan (CC) opsional ke 4 Satlak/4 Sdir -- sekadar info
            // koordinasi, sama sekali bukan tujuan approval kedua. Dibatasi
            // ketat ke 8 kode yang diizinkan supaya tidak bisa
            // "menembuskan" ke satuan lain (mis. sesama Kasansi) yang belum
            // didukung, dan dibatasi maksimal 2 satuan per laporan supaya
            // tembusan tetap fokus/tidak disebar ke semua 8 satuan sekaligus.
            // Tembusan WAJIB dalam alur baru: Kasansi → Tembusan → Danpus.
            // Tembusan harus membalas dulu sebelum Kasansi bisa upload dokumen
            // dan meneruskan ke Danpus -- min:1 & required memaksa user memilih.
            'tembusan_ke' => ['required', 'array', 'min:1', 'max:1'],
            'tembusan_ke.*' => ['string', 'in:'.implode(',', Satuan::kodeTembusanKasansi())],
        ], [
            'lampiran.required'   => 'Lampiran wajib diisi untuk mengirim laporan kendala.',
            'lampiran.min'        => 'Lampiran wajib diisi untuk mengirim laporan kendala.',
            'tembusan_ke.required' => 'Tembusan wajib dipilih. Pilih 1 satuan penerima tembusan.',
            'tembusan_ke.min'     => 'Tembusan wajib dipilih minimal 1 satuan.',
            'tembusan_ke.max'     => 'Tembusan maksimal 1 satuan saja.',
        ]);

        $totalLampiranBytes = collect($request->file('lampiran', []))->filter()->sum(fn ($file) => $file->getSize());
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

        // Simpan SEMUA file lampiran yang dikirim (bukan cuma 1 lagi) --
        // masing-masing jadi 1 baris di tabel laporan_kendala_lampirans,
        // path fisiknya tetap di disk 'lampiran-kendala' yang sama seperti
        // sebelumnya supaya tidak perlu migrasi file lama.
        $lampiranDisimpan = collect($request->file('lampiran', []))->filter()->map(function ($file) {
            $path = $file->store('lampiran-kendala', 'public');
            abort_if(! $path, 500, 'Gagal menyimpan file lampiran ke server. Coba lagi, atau hubungi Admin kalau masalah berlanjut.');

            return [
                'path' => $path,
                'nama_asli' => $file->getClientOriginalName(),
            ];
        });

        // Tembusan WAJIB dalam alur baru -- validasi di atas sudah memaksa
        // min:1, jadi $satuanTembusan dijamin tidak pernah kosong di sini.
        $satuanTembusan = Satuan::whereIn('kode', array_unique($validated['tembusan_ke']))->get();
        abort_if($satuanTembusan->isEmpty(), 422, 'Satuan tembusan yang dipilih tidak ditemukan.');

        // Alur baru selalu lewat tembusan: status awal Menunggu Balasan,
        // Danpus baru diberi tahu setelah Kasansi upload dokumen & teruskan.
        $adaTembusan = true;

        $kendala = null;
        DB::transaction(function () use (&$kendala, $satuanAsal, $user, $tujuan, $validated, $lampiranDisimpan, $satuanTembusan, $adaTembusan) {
            $kendala = LaporanKendala::create([
                'satuan_id' => $satuanAsal->id,
                'user_id' => $user->id,
                'tujuan_satuan_id' => $tujuan->id,
                'perihal' => $validated['perihal'],
                'kategori' => $validated['kategori'] ?? null,
                'deskripsi' => $validated['deskripsi'],
                'prioritas' => $validated['prioritas'],
                'status' => $adaTembusan ? LaporanKendala::STATUS_MENUNGGU_TEMBUSAN : LaporanKendala::STATUS_MENUNGGU,
            ]);

            foreach ($lampiranDisimpan as $lampiran) {
                $kendala->lampirans()->create($lampiran);
            }

            foreach ($satuanTembusan as $penerimaTembusan) {
                LaporanKendalaTembusan::create([
                    'laporan_kendala_id' => $kendala->id,
                    'satuan_id' => $penerimaTembusan->id,
                ]);
            }
        });

        // Danpus BELUM diberi tahu -- laporan mampir ke tembusan dulu.
        // Tembusan diberi notifikasi agar segera membalas ke Kasansi.
        foreach (User::whereIn('satuan_id', $satuanTembusan->pluck('id'))->get() as $penerimaTembusan) {
            $penerimaTembusan->notify(new LaporanKendalaTembusanBaru($kendala));
        }

        ActivityLog::catat('laporan-kendala.create', "Mengirim laporan kendala \"{$kendala->perihal}\" ke tembusan ({$satuanTembusan->pluck('nama')->implode(', ')}) sebelum diteruskan ke {$tujuan->nama}.", $user, [
            'laporan_kendala_id' => $kendala->id,
            'tujuan_satuan'      => $tujuan->nama,
            'prioritas'          => $kendala->prioritas,
            'tembusan_ke'        => $satuanTembusan->pluck('nama')->all(),
        ]);

        return back()->with('status',
            'Laporan kendala terkirim ke tembusan ('.$satuanTembusan->pluck('nama_singkat')->implode(', ').'). '.
            'Tunggu balasan dari tembusan, lalu upload dokumen sebelum meneruskan ke '.$tujuan->nama.'.'
        );
    }

    /**
     * Kasansi upload dokumen setelah membaca balasan/dokumen dari tembusan.
     * Ini adalah tahap antara: tembusan sudah membalas → Kasansi siapkan
     * dokumen → baru bisa teruskan ke Danpus. Dokumen disimpan di kolom
     * dokumen_kasansi_path pada laporan_kendalas (bukan di tabel lampirans,
     * karena ini dokumen hasil "siap kirim" bukan lampiran awal).
     */
    public function uploadDokumenKasansi(Request $request, LaporanKendala $laporanKendala): RedirectResponse
    {
        $validated = $request->validate([
            'dokumen_kasansi' => ['required', 'file', 'max:10240'],
        ], [
            'dokumen_kasansi.required' => 'Dokumen wajib dipilih sebelum dikirim ke Danpus.',
        ]);

        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        abort_unless(
            in_array(strtoupper((string) $satuan->kode), Satuan::KODE_KOTAMA, true),
            403,
            'Hanya Kasansi yang dapat mengupload dokumen kendala.'
        );
        abort_unless(
            (int) $laporanKendala->satuan_id === (int) $satuan->id,
            403,
            'Laporan kendala ini bukan milik satuan Anda.'
        );
        abort_unless(
            $laporanKendala->status === LaporanKendala::STATUS_MENUNGGU_TEMBUSAN,
            422,
            'Laporan kendala ini tidak sedang di tahap menunggu balasan tembusan.'
        );

        // Pastikan minimal satu tembusan sudah membalas (feedback teks atau dokumen)
        $laporanKendala->load('tembusans');
        abort_unless(
            $laporanKendala->tembusans->contains(fn ($t) => $t->sudahMembalas()),
            422,
            'Tunggu balasan dari minimal satu tembusan sebelum upload dokumen.'
        );

        $file = $request->file('dokumen_kasansi');
        $path = $file->store('dokumen-kendala-kasansi', 'public');
        abort_if(! $path, 500, 'Gagal menyimpan dokumen ke server. Coba lagi.');

        // Hapus dokumen lama kalau sudah pernah upload sebelumnya (replace)
        if ($laporanKendala->dokumen_kasansi_path) {
            Storage::disk('public')->delete($laporanKendala->dokumen_kasansi_path);
        }

        $laporanKendala->update([
            'dokumen_kasansi_path'  => $path,
            'dokumen_kasansi_nama'  => $file->getClientOriginalName(),
            'dokumen_kasansi_at'    => now(),
            'dokumen_kasansi_oleh'  => $user->id,
        ]);

        ActivityLog::catat('laporan-kendala.upload-dokumen', "Upload dokumen \"{$file->getClientOriginalName()}\" untuk kendala \"{$laporanKendala->perihal}\" siap dikirim ke Danpus.", $user, [
            'laporan_kendala_id'   => $laporanKendala->id,
            'dokumen_kasansi_nama' => $file->getClientOriginalName(),
        ]);

        return back()->with('status', 'Dokumen berhasil diupload. Sekarang klik "Kirim ke Danpus" untuk meneruskan laporan.');
    }

    /**
     * Kasansi meneruskan laporan kendala ke Danpus setelah:
     *   1. Minimal satu tembusan sudah membalas
     *   2. Kasansi sudah upload dokumen lewat uploadDokumenKasansi()
     *
     * Baru di titik ini Danpus diberi tahu lewat notifikasi.
     */
    public function teruskan(Request $request, LaporanKendala $laporanKendala): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        abort_unless(
            (int) $laporanKendala->satuan_id === (int) $satuan->id,
            403,
            'Laporan kendala ini bukan milik satuan Anda.'
        );
        abort_unless(
            $laporanKendala->status === LaporanKendala::STATUS_MENUNGGU_TEMBUSAN,
            422,
            'Laporan kendala ini tidak sedang menunggu tembusan.'
        );

        $laporanKendala->load('tembusans');

        // Pastikan minimal satu tembusan sudah membalas
        abort_unless(
            $laporanKendala->tembusans->contains(fn ($t) => $t->sudahMembalas()),
            422,
            'Tunggu balasan dari minimal satu tembusan sebelum meneruskan ke Danpus.'
        );

        // Pastikan Kasansi sudah upload dokumen
        abort_unless(
            filled($laporanKendala->dokumen_kasansi_path),
            422,
            'Upload dokumen terlebih dahulu sebelum meneruskan ke Danpus.'
        );

        $laporanKendala->update([
            'status'          => LaporanKendala::STATUS_MENUNGGU,
            'diteruskan_at'   => now(),
            'diteruskan_oleh' => $user->id,
        ]);

        // Baru di sini Danpus diberi tahu -- laporan resmi "sampai" ke mereka
        $tujuan = $laporanKendala->tujuanSatuan;
        foreach (User::where('satuan_id', $tujuan->id)->get() as $penerima) {
            $penerima->notify(new LaporanKendalaBaruDiterima($laporanKendala));
        }

        ActivityLog::catat('laporan-kendala.teruskan', "Meneruskan laporan kendala \"{$laporanKendala->perihal}\" beserta dokumen ke {$tujuan->nama} setelah balasan tembusan.", $user, [
            'laporan_kendala_id'   => $laporanKendala->id,
            'tujuan_satuan'        => $tujuan->nama,
            'dokumen_kasansi_nama' => $laporanKendala->dokumen_kasansi_nama,
        ]);

        return back()->with('status', 'Laporan kendala dan dokumen berhasil diteruskan ke '.$tujuan->nama.'.');
    }

    public function updateStatus(Request $request, LaporanKendala $laporanKendala): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Ditindaklanjuti,Selesai,Ditolak,Dikonfirmasi'],
            'catatan' => ['nullable', 'string', 'max:5000', 'required_if:status,Ditolak'],
        ], [
            'catatan.required_if' => 'Catatan penolakan wajib diisi.',
        ]);

        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');

        $kodeSatuan = strtoupper((string) $satuan->kode);
        abort_unless(
            in_array($kodeSatuan, ['DANPUS', 'WADAN'], true),
            403,
            'Anda bukan penerima laporan kendala ini.'
        );

        // Jaring pengaman -- laporan yang masih mampir di tembusan belum
        // pernah "sampai" ke Danpus/Wadan sama sekali, jadi tidak boleh
        // ditindaklanjuti biarpun request-nya dikirim manual langsung ke
        // endpoint ini (di UI, laporan begini memang tidak pernah muncul di
        // daftar Danpus/Wadan -- lihat realtime()/DashboardController).
        abort_if(
            $laporanKendala->status === LaporanKendala::STATUS_MENUNGGU_TEMBUSAN,
            422,
            'Laporan kendala ini masih menunggu tembusan dan belum diteruskan ke Danpus.'
        );

        // Konfirmasi/arsip adalah tindakan khusus Danpus. Wadan tetap boleh
        // menindaklanjuti status laporan, tetapi tidak memindahkannya ke arsip
        // penerimaan Danpus.
        if ($validated['status'] === LaporanKendala::STATUS_DIKONFIRMASI) {
            abort_unless($kodeSatuan === 'DANPUS', 403, 'Hanya Danpus yang dapat mengonfirmasi dan mengarsipkan laporan kendala.');
            abort_unless(!$laporanKendala->confirmed_at, 422, 'Laporan kendala ini sudah dikonfirmasi dan diarsipkan.');

            $laporanKendala->update([
                'status' => LaporanKendala::STATUS_DIKONFIRMASI,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);

            ActivityLog::catat('laporan-kendala.confirm', "Mengonfirmasi dan mengarsipkan laporan kendala \"{$laporanKendala->perihal}\".", $user, [
                'laporan_kendala_id' => $laporanKendala->id,
                'status' => LaporanKendala::STATUS_DIKONFIRMASI,
            ]);

            return back()->with('status', 'Laporan kendala berhasil dikonfirmasi dan dipindahkan ke Arsip Kendala Kasansi.');
        }

        abort_unless(!$laporanKendala->confirmed_at, 422, 'Laporan kendala ini sudah berada di arsip dan tidak dapat ditindaklanjuti dari daftar masuk.');

        // Alur Danpus untuk Kendala Kasansi disederhanakan jadi langsung
        // "Konfirmasi & Arsipkan" saja -- Danpus tidak lagi menindaklanjuti
        // atau menolak satu-satu (itu tetap jadi wewenang Wadan).
        abort_if(
            $kodeSatuan === 'DANPUS' && in_array($validated['status'], [LaporanKendala::STATUS_DITINDAKLANJUTI, LaporanKendala::STATUS_DITOLAK], true),
            403,
            'Danpus tidak lagi menindaklanjuti/menolak kendala satu-satu -- gunakan "Konfirmasi & Arsipkan".'
        );

        $laporanKendala->update([
            'status' => $validated['status'],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        ActivityLog::catat('laporan-kendala.status', "Memperbarui status laporan kendala \"{$laporanKendala->perihal}\" menjadi {$laporanKendala->status}.", $user, [
            'laporan_kendala_id' => $laporanKendala->id,
            'status' => $laporanKendala->status,
        ]);

        return back()->with('status', 'Status laporan kendala berhasil diperbarui menjadi '.$laporanKendala->status.'.');
    }

    public function destroy(Request $request, LaporanKendala $laporanKendala): RedirectResponse
    {
        $user       = $request->user()->load('satuan');
        $satuan     = $user->satuan;
        $kodeSatuan = strtoupper($satuan->kode ?? '');
        $isDanpus   = $kodeSatuan === 'DANPUS';
        abort_unless($satuan, 403);

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
