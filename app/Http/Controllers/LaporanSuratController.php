<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanSurat;
use App\Models\LaporanSuratRiwayat;
use App\Models\LaporanSuratTembusan;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanSuratBaruDiterima;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Alur Surat Masuk, Surat Keluar, Disposisi & Tindakan Berkelanjutan:
 *   - Danpus -> Wadan -> Satrap Penindakan -> Wadan -> Danpus (Selesai / Siklus Baru)
 *   - Disposisi = penerima utama yang harus menangani surat.
 *   - Tindakan = instruksi yang harus dilakukan penerima.
 *   - Tembusan = pihak yang hanya mengetahui/menerima informasi.
 *   - Urdal View Only otomatis saat surat diteruskan ke satuan utama.
 *   - Hasil/RC otomatis masuk ke Urdal begitu Danpus menyatakan Selesai.
 *   - Semua disposisi, tindakan, penerusan, konfirmasi, dan tembusan tersimpan sebagai riwayat.
 */
class LaporanSuratController extends Controller
{
    /**
     * Realtime poll dari JS -- mengembalikan snapshot Surat Masuk, Surat Keluar, dan Arsip Surat.
     */
    public function realtime(Request $request): JsonResponse
    {
        $user   = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');

        $kodeSatuan = strtoupper((string) $satuan->kode);
        $isDanpus   = $kodeSatuan === 'DANPUS';

        // 1. Surat Masuk:
        //    a. Surat di mana satuan ini adalah tujuan utama (tujuan_satuan_id)
        //       DAN berstatus MENUNGGU DAN belum selesai.
        //       (Khusus Danpus: surat awal Danpus TIDAK tampil di Surat Masuk Danpus sendiri,
        //        tetapi tetap di Surat Keluar Danpus sampai selesai).
        //    b. Surat di mana satuan ini terdaftar sebagai View Only atau Tembusan
        //       yang BELUM dikonfirmasi oleh satuan ini, dan belum selesai.
        $suratMasukUtama = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'tembusans'])
            ->where('tujuan_satuan_id', $satuan->id)
            ->where('status', LaporanSurat::STATUS_MENUNGGU)
            ->where('is_selesai', false)
            ->when($isDanpus, function ($q) use ($satuan) {
                // Danpus hanya melihat di surat masuk jika dikembalikan/diteruskan kepadanya oleh Wadan/satuan lain
                $q->whereHas('riwayats', function ($rq) use ($satuan) {
                    $rq->where('penerima_satuan_id', $satuan->id);
                });
            })
            ->latest()
            ->get();

        $suratTembusanMasuk = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'tembusans'])
            ->where('is_selesai', false)
            ->whereHas('tembusans', function ($tq) use ($satuan) {
                $tq->where('satuan_id', $satuan->id)
                   ->whereNull('dikonfirmasi_at');
            })
            ->where('tujuan_satuan_id', '!=', $satuan->id)
            ->latest()
            ->get();

        $suratMasuk = $suratMasukUtama->concat($suratTembusanMasuk)->unique('id')->values();

        $payload = [
            'masuk_items_html' => $suratMasuk->map(
                fn (LaporanSurat $s) => view('siberad.dashboards.partials.surat-masuk-row', ['s' => $s, 'satuan' => $satuan])->render()
            )->implode(''),
        ];

        // 2. Surat Keluar:
        //    Surat yang dibuat oleh satuan ini dan belum selesai / masih berjalan.
        $bisaKirimSurat = in_array($kodeSatuan, Satuan::KODE_KOTAMA, true)
            || in_array($kodeSatuan, Satuan::KODE_SATLAK, true)
            || in_array($kodeSatuan, Satuan::KODE_PEMBINAAN, true)
            || in_array($kodeSatuan, Satuan::KODE_UNSUR_PELAYANAN, true)
            || in_array($kodeSatuan, Satuan::KODE_UNSUR_PEMBANTU_PIMPINAN, true)
            || in_array($kodeSatuan, ['DANPUS', 'WADAN'], true);

        if ($bisaKirimSurat) {
            $terkirim = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'tembusans'])
                ->where('satuan_id', $satuan->id)
                ->where('is_selesai', false)
                ->latest()
                ->get();

            $payload['terkirim_items_html'] = $terkirim->map(
                fn (LaporanSurat $s) => view('siberad.dashboards.partials.surat-terkirim-row', ['s' => $s, 'satuan' => $satuan])->render()
            )->implode('');
        }

        // 3. Arsip Surat:
        //    a. Surat yang sudah selesai (is_selesai = true) bagi yang terlibat (pembuat, penerima, tembusan, atau RC Urdal).
        //    b. Surat yang sudah dikonfirmasi (STATUS_DIKONFIRMASI).
        //    c. Tembusan yang sudah dikonfirmasi oleh satuan ini.
        $arsip = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'tembusans'])
            ->where(function ($q) use ($satuan) {
                $q->where(function ($sub) use ($satuan) {
                    $sub->where('satuan_id', $satuan->id)
                        ->orWhere('tujuan_satuan_id', $satuan->id);
                })
                ->where(function ($st) {
                    $st->where('status', LaporanSurat::STATUS_DIKONFIRMASI)
                       ->orWhere('is_selesai', true);
                });
            })
            ->orWhere(function ($q) use ($satuan) {
                // Tembusan / RC yang sudah dikonfirmasi oleh satuan ini
                $q->whereHas('tembusans', function ($tq) use ($satuan) {
                    $tq->where('satuan_id', $satuan->id)
                       ->whereNotNull('dikonfirmasi_at');
                });
            })
            ->orWhere(function ($q) use ($satuan) {
                // Hasil/RC khusus Urdal
                $q->where('is_selesai', true)
                  ->whereHas('tembusans', function ($tq) use ($satuan) {
                      $tq->where('satuan_id', $satuan->id)
                         ->where('jenis', LaporanSuratTembusan::JENIS_HASIL_RC);
                  });
            })
            ->latest()
            ->get()
            ->unique('id')
            ->values();

        $payload['arsip_items_html'] = $arsip->map(
            fn (LaporanSurat $s) => view('siberad.dashboards.partials.surat-arsip-row', ['s' => $s, 'satuan' => $satuan])->render()
        )->implode('');

        return response()->json($payload, 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Membuat Surat Baru (Danpus / Kasansi / Satlak / Satuan lain).
     * Jika ada opsi tembusan[] (mis. dari Satrap Penindakan), otomatis dicatat ke laporan_surat_tembusans.
     */
    public function store(Request $request): RedirectResponse
    {
        $user       = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;
        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');
        $kodeAsal = strtoupper((string) $satuanAsal->kode);
        abort_unless(
            in_array($kodeAsal, Satuan::KODE_KOTAMA, true)
                || in_array($kodeAsal, Satuan::KODE_SATLAK, true)
                || in_array($kodeAsal, Satuan::KODE_PEMBINAAN, true)
                || in_array($kodeAsal, Satuan::KODE_UNSUR_PELAYANAN, true)
                || in_array($kodeAsal, Satuan::KODE_UNSUR_PEMBANTU_PIMPINAN, true)
                || in_array($kodeAsal, ['DANPUS', 'WADAN'], true),
            403,
            'Hanya Kasansi, Satlak, Sdir, Urdal, Pok Analis, atau Danpus/Wadan yang dapat mengirim Surat.'
        );

        $isDanpus       = $kodeAsal === 'DANPUS';
        $prioritasRules = $isDanpus
            ? ['required', 'in:'.implode(',', LaporanSurat::PRIORITAS_DANPUS)]
            : ['required', 'in:Tinggi,Sedang,Rendah,Biasa,Kilat,Rahasia'];

        $disposisiRules = $isDanpus
            ? ['required', 'string', 'in:'.implode(',', LaporanSurat::DISPOSISI_DANPUS_OPTIONS)]
            : ['nullable', 'string'];
        $tindakanRules = $isDanpus
            ? ['required', 'array', 'min:1']
            : ['nullable', 'array'];
        // Rahasia (khusus Danpus): kolom "Isi Ringkasan Surat" disembunyikan
        // & opsional di form -- isinya toh nggak pernah ditampilkan ke
        // penerima (lihat LaporanSurat::ringkasanUntuk()).
        $deskripsiRules = ($isDanpus && $request->input('prioritas') === LaporanSurat::PRIORITAS_DANPUS_RAHASIA)
            ? ['nullable', 'string', 'max:10000']
            : ['required', 'string', 'max:10000'];

        $validated = $request->validate([
            'induk_surat_id'   => ['nullable', 'integer', 'exists:laporan_surats,id'],
            'tujuan_satuan_id' => ['required', 'integer', 'exists:satuans,id'],
            'perihal'          => ['required', 'string', 'max:255'],
            'kategori'         => ['required', 'string', 'max:255'],
            'deskripsi'        => $deskripsiRules,
            'prioritas'        => $prioritasRules,
            'disposisi'        => $disposisiRules,
            'tindakan'         => $tindakanRules,
            'tindakan.*'       => ['string'],
            'tembusan'         => ['nullable', 'array'],
            'tembusan.*'       => ['integer', 'exists:satuans,id'],
            'lampiran'         => ['required', 'file', 'max:10240'],
        ], [
            'tujuan_satuan_id.required' => 'Tujuan surat wajib dipilih.',
            'disposisi.required'        => 'Disposisi wajib dipilih.',
            'tindakan.required'         => 'Tindakan wajib dipilih.',
            'tindakan.min'              => 'Pilih minimal satu tindakan.',
            'lampiran.required'         => 'Lampiran wajib diisi untuk mengirim Surat.',
        ]);

        abort_if(
            (int) $validated['tujuan_satuan_id'] === (int) $satuanAsal->id,
            422,
            'Tujuan surat tidak boleh satuan sendiri.'
        );

        $tujuan = Satuan::findOrFail($validated['tujuan_satuan_id']);

        $lampiranFile = $request->file('lampiran');
        $lampiranPath = $lampiranFile->store('lampiran-surat', 'public');
        abort_if(! $lampiranPath, 500, 'Gagal menyimpan file lampiran ke server.');

        // Jika merupakan balasan/laporan lanjutan dari surat induk (mis. Satrap Penindakan -> Wadan)
        if (! empty($validated['induk_surat_id'])) {
            $indukSurat = LaporanSurat::findOrFail($validated['induk_surat_id']);

            abort_unless(
                (int) $indukSurat->tujuan_satuan_id === (int) $satuanAsal->id,
                403,
                'Surat ini bukan sedang berada di satuan Anda, tidak bisa dibalas.'
            );
            abort_if($indukSurat->isSelesai(), 422, 'Surat ini sudah selesai dan tidak bisa dibalas lagi.');

            // Update surat induk agar mengalir kembali ke tujuan baru (Wadan)
            $indukSurat->update([
                'tujuan_satuan_id'    => $tujuan->id,
                'status'              => LaporanSurat::STATUS_MENUNGGU,
                'lampiran_path'       => $lampiranPath,
                'lampiran_nama_asli'  => $lampiranFile->getClientOriginalName(),
                'dikonfirmasi_at'     => null,
                'dikonfirmasi_oleh'   => null,
            ]);

            // Catat Riwayat Pengiriman Surat Keluar / Balasan
            $riwayat = LaporanSuratRiwayat::create([
                'laporan_surat_id'   => $indukSurat->id,
                'siklus'             => $indukSurat->siklus,
                'aksi'               => LaporanSuratRiwayat::AKSI_SURAT_KELUAR,
                'pengirim_satuan_id' => $satuanAsal->id,
                'penerima_satuan_id' => $tujuan->id,
                'user_id'            => $user->id,
                'catatan'            => $validated['deskripsi'] ?? '',
                'lampiran_path'      => $lampiranPath,
                'lampiran_nama_asli' => $lampiranFile->getClientOriginalName(),
            ]);

            // Tambahkan Tembusan jika ada
            if (! empty($validated['tembusan'])) {
                foreach ($validated['tembusan'] as $tembusanSatuanId) {
                    if ((int) $tembusanSatuanId !== (int) $satuanAsal->id && (int) $tembusanSatuanId !== (int) $tujuan->id) {
                        LaporanSuratTembusan::create([
                            'laporan_surat_id'         => $indukSurat->id,
                            'laporan_surat_riwayat_id' => $riwayat->id,
                            'satuan_id'                => $tembusanSatuanId,
                            'jenis'                    => LaporanSuratTembusan::JENIS_TEMBUSAN,
                        ]);

                        foreach (User::where('satuan_id', $tembusanSatuanId)->get() as $penerimaTembusan) {
                            $penerimaTembusan->notify(new LaporanSuratBaruDiterima($indukSurat));
                        }
                    }
                }
            }

            foreach (User::where('satuan_id', $tujuan->id)->get() as $penerima) {
                $penerima->notify(new LaporanSuratBaruDiterima($indukSurat));
            }

            ActivityLog::catat('laporan-surat.balasan', "Mengirim balasan surat \"{$indukSurat->perihal}\" ke {$tujuan->nama}.", $user, [
                'laporan_surat_id' => $indukSurat->id,
                'tujuan_satuan'    => $tujuan->nama,
            ]);

            return back()->with('status', 'Surat balasan berhasil dikirim ke '.$tujuan->nama.'.');
        }

        // Pembuatan Surat Baru Standar
        $surat = LaporanSurat::create([
            'satuan_id'           => $satuanAsal->id,
            'user_id'             => $user->id,
            'tujuan_satuan_id'    => $tujuan->id,
            'perihal'             => $validated['perihal'],
            'kategori'            => $validated['kategori'] ?? null,
            'deskripsi'           => $validated['deskripsi'] ?? '',
            'prioritas'           => $validated['prioritas'],
            'disposisi'           => $validated['disposisi'] ?? null,
            'tindakan'            => $validated['tindakan'] ?? null,
            'disposisi_terakhir'  => $validated['disposisi'] ?? null,
            'tindakan_terakhir'   => $validated['tindakan'] ?? null,
            'siklus'              => 1,
            'lampiran_path'       => $lampiranPath,
            'lampiran_nama_asli'  => $lampiranFile->getClientOriginalName(),
            'status'              => LaporanSurat::STATUS_MENUNGGU,
        ]);

        // Catat Riwayat Pembuatan Surat Awal
        $riwayat = LaporanSuratRiwayat::create([
            'laporan_surat_id'   => $surat->id,
            'siklus'             => 1,
            'aksi'               => LaporanSuratRiwayat::AKSI_BUAT_SURAT,
            'pengirim_satuan_id' => $satuanAsal->id,
            'penerima_satuan_id' => $tujuan->id,
            'user_id'            => $user->id,
            'disposisi'          => $surat->disposisi,
            'tindakan'           => $surat->tindakan,
            'catatan'            => $surat->deskripsi,
            'lampiran_path'      => $surat->lampiran_path,
            'lampiran_nama_asli' => $surat->lampiran_nama_asli,
        ]);

        // Tambahkan Tembusan opsional jika dipilih
        if (! empty($validated['tembusan'])) {
            foreach ($validated['tembusan'] as $tembusanSatuanId) {
                if ((int) $tembusanSatuanId !== (int) $satuanAsal->id && (int) $tembusanSatuanId !== (int) $tujuan->id) {
                    LaporanSuratTembusan::create([
                        'laporan_surat_id'         => $surat->id,
                        'laporan_surat_riwayat_id' => $riwayat->id,
                        'satuan_id'                => $tembusanSatuanId,
                        'jenis'                    => LaporanSuratTembusan::JENIS_TEMBUSAN,
                    ]);

                    foreach (User::where('satuan_id', $tembusanSatuanId)->get() as $penerimaTembusan) {
                        $penerimaTembusan->notify(new LaporanSuratBaruDiterima($surat));
                    }
                }
            }
        }

        foreach (User::where('satuan_id', $tujuan->id)->get() as $penerima) {
            $penerima->notify(new LaporanSuratBaruDiterima($surat));
        }

        ActivityLog::catat('laporan-surat.create', "Mengirim surat \"{$surat->perihal}\" ke {$tujuan->nama}.", $user, [
            'laporan_surat_id' => $surat->id,
            'tujuan_satuan'    => $tujuan->nama,
            'prioritas'        => $surat->prioritas,
        ]);

        return back()->with('status', 'Surat berhasil dikirim ke '.$tujuan->nama.'.');
    }

    /**
     * Konfirmasi / ACC & Terima surat oleh penerima utama,
     * ATAU Konfirmasi tanda mengetahui oleh pihak tembusan / view only.
     */
    public function konfirmasi(Request $request, LaporanSurat $laporanSurat): RedirectResponse
    {
        $user   = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);

        $isTujuanUtama = (int) $laporanSurat->tujuan_satuan_id === (int) $satuan->id;

        // Cek apakah user adalah pihak tembusan / view only
        $tembusanRow = LaporanSuratTembusan::where('laporan_surat_id', $laporanSurat->id)
            ->where('satuan_id', $satuan->id)
            ->first();

        if (! $isTujuanUtama && $tembusanRow) {
            // Konfirmasi Tembusan / View Only
            $tembusanRow->update([
                'dikonfirmasi_at'   => now(),
                'dikonfirmasi_oleh' => $user->id,
            ]);

            ActivityLog::catat('laporan-surat.konfirmasi-tembusan', "Mengkonfirmasi tanda terima surat \"{$laporanSurat->perihal}\".", $user, [
                'laporan_surat_id' => $laporanSurat->id,
                'jenis_tembusan'   => $tembusanRow->jenis,
            ]);

            return back()->with('status', 'Surat "'.$laporanSurat->perihal.'" berhasil dikonfirmasi (Mengetahui).');
        }

        // Penerima Utama: Konfirmasi / ACC & Terima
        abort_unless($isTujuanUtama, 403, 'Hanya penerima yang dapat mengkonfirmasi surat ini.');

        $laporanSurat->update([
            'status'            => LaporanSurat::STATUS_DIKONFIRMASI,
            'dikonfirmasi_at'   => now(),
            'dikonfirmasi_oleh' => $user->id,
        ]);

        // Catat riwayat konfirmasi penerima
        LaporanSuratRiwayat::create([
            'laporan_surat_id'   => $laporanSurat->id,
            'siklus'             => $laporanSurat->siklus,
            'aksi'               => LaporanSuratRiwayat::AKSI_KONFIRMASI,
            'pengirim_satuan_id' => $satuan->id,
            'user_id'            => $user->id,
            'catatan'            => "Surat dikonfirmasi / ACC & Diterima oleh {$satuan->nama}.",
        ]);

        ActivityLog::catat('laporan-surat.konfirmasi', "Mengkonfirmasi surat \"{$laporanSurat->perihal}\" dari {$laporanSurat->satuan->nama}.", $user, [
            'laporan_surat_id' => $laporanSurat->id,
            'pengirim_satuan'  => $laporanSurat->satuan->nama,
        ]);

        return back()->with('status', 'Surat "'.$laporanSurat->perihal.'" berhasil dikonfirmasi / ACC & Diterima.');
    }

    /**
     * Wadan meneruskan surat ke Satuan Tujuan (misal: Satrap Penindakan).
     * Disposisi + Tindakan + Lampiran (opsional baru).
     * Urdal otomatis masuk sebagai View Only!
     */
    public function teruskan(Request $request, LaporanSurat $laporanSurat): RedirectResponse
    {
        $user   = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);
        abort_unless(strtoupper((string) $satuan->kode) === 'WADAN', 403, 'Hanya Wadan yang dapat meneruskan surat ini.');
        abort_unless(
            (int) $laporanSurat->tujuan_satuan_id === (int) $satuan->id,
            403,
            'Surat ini bukan sedang berada di satuan Anda.'
        );
        abort_unless($laporanSurat->isDikonfirmasi(), 422, 'Konfirmasi surat terlebih dahulu sebelum meneruskannya.');
        abort_if($laporanSurat->isSelesai(), 422, 'Surat ini sudah selesai.');

        $validated = $request->validate([
            'tujuan_satuan_id' => ['required', 'integer', 'exists:satuans,id'],
            'disposisi'        => ['required', 'string'],
            'tindakan'         => ['required', 'array', 'min:1'],
            'tindakan.*'       => ['string'],
            'catatan'          => ['nullable', 'string', 'max:5000'],
            'lampiran'         => ['nullable', 'file', 'max:10240'],
        ], [
            'tujuan_satuan_id.required' => 'Satuan tujuan penerusan wajib dipilih.',
            'disposisi.required'        => 'Disposisi wajib dipilih.',
            'tindakan.required'         => 'Tindakan wajib dipilih minimal satu.',
            'tindakan.min'              => 'Pilih minimal satu tindakan.',
        ]);

        $tujuan = Satuan::findOrFail($validated['tujuan_satuan_id']);

        $lampiranPath     = $laporanSurat->lampiran_path;
        $lampiranNamaAsli = $laporanSurat->lampiran_nama_asli;

        if ($request->hasFile('lampiran')) {
            $file             = $request->file('lampiran');
            $lampiranPath     = $file->store('lampiran-surat', 'public');
            $lampiranNamaAsli = $file->getClientOriginalName();
        }

        // Update surat agar dipegang oleh satuan tujuan baru
        $laporanSurat->update([
            'tujuan_satuan_id'   => $tujuan->id,
            'disposisi_terakhir' => $validated['disposisi'],
            'tindakan_terakhir'  => $validated['tindakan'],
            'status'             => LaporanSurat::STATUS_MENUNGGU,
            'lampiran_path'      => $lampiranPath,
            'lampiran_nama_asli' => $lampiranNamaAsli,
            'dikonfirmasi_at'    => null,
            'dikonfirmasi_oleh'  => null,
        ]);

        // Catat ke riwayat
        $riwayat = LaporanSuratRiwayat::create([
            'laporan_surat_id'   => $laporanSurat->id,
            'siklus'             => $laporanSurat->siklus,
            'aksi'               => LaporanSuratRiwayat::AKSI_TERUSKAN,
            'pengirim_satuan_id' => $satuan->id,
            'penerima_satuan_id' => $tujuan->id,
            'user_id'            => $user->id,
            'disposisi'          => $validated['disposisi'],
            'tindakan'           => $validated['tindakan'],
            'catatan'            => $validated['catatan'] ?? "Diteruskan ke {$tujuan->nama} oleh {$satuan->nama}.",
            'lampiran_path'      => $lampiranPath,
            'lampiran_nama_asli' => $lampiranNamaAsli,
        ]);

        // Requirement 3: URDAL otomatis masuk sebagai View Only jika tujuan bukan Urdal
        $urdalSatuan = Satuan::where('kode', 'URDAL')->first();
        if ($urdalSatuan && (int) $tujuan->id !== (int) $urdalSatuan->id && (int) $satuan->id !== (int) $urdalSatuan->id) {
            LaporanSuratTembusan::create([
                'laporan_surat_id'         => $laporanSurat->id,
                'laporan_surat_riwayat_id' => $riwayat->id,
                'satuan_id'                => $urdalSatuan->id,
                'jenis'                    => LaporanSuratTembusan::JENIS_VIEW_ONLY,
            ]);

            foreach (User::where('satuan_id', $urdalSatuan->id)->get() as $u) {
                $u->notify(new LaporanSuratBaruDiterima($laporanSurat));
            }
        }

        foreach (User::where('satuan_id', $tujuan->id)->get() as $p) {
            $p->notify(new LaporanSuratBaruDiterima($laporanSurat));
        }

        ActivityLog::catat('laporan-surat.teruskan', "Meneruskan surat \"{$laporanSurat->perihal}\" ke {$tujuan->nama}.", $user, [
            'laporan_surat_id' => $laporanSurat->id,
            'tujuan_satuan'    => $tujuan->nama,
            'disposisi'        => $validated['disposisi'],
        ]);

        return back()->with('status', 'Surat berhasil diteruskan ke '.$tujuan->nama.'.');
    }

    /**
     * Wadan meneruskan surat kembali ke Danpus untuk keputusan akhir.
     */
    public function kembalikanKeDanpus(Request $request, LaporanSurat $laporanSurat): RedirectResponse
    {
        $user   = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);
        abort_unless(strtoupper((string) $satuan->kode) === 'WADAN', 403, 'Hanya Wadan yang dapat meneruskan surat ini ke Danpus.');
        abort_unless(
            (int) $laporanSurat->tujuan_satuan_id === (int) $satuan->id,
            403,
            'Surat ini bukan sedang berada di satuan Anda.'
        );
        abort_unless($laporanSurat->isDikonfirmasi(), 422, 'Konfirmasi surat terlebih dahulu sebelum meneruskannya ke Danpus.');
        abort_if($laporanSurat->isSelesai(), 422, 'Surat ini sudah selesai.');

        $danpusSatuan = Satuan::where('kode', 'DANPUS')->firstOrFail();

        $laporanSurat->update([
            'tujuan_satuan_id'  => $danpusSatuan->id,
            'status'            => LaporanSurat::STATUS_MENUNGGU,
            'dikonfirmasi_at'   => null,
            'dikonfirmasi_oleh' => null,
        ]);

        LaporanSuratRiwayat::create([
            'laporan_surat_id'   => $laporanSurat->id,
            'siklus'             => $laporanSurat->siklus,
            'aksi'               => LaporanSuratRiwayat::AKSI_TERUSKAN,
            'pengirim_satuan_id' => $satuan->id,
            'penerima_satuan_id' => $danpusSatuan->id,
            'user_id'            => $user->id,
            'catatan'            => $request->input('catatan', 'Diteruskan kembali ke Danpus untuk keputusan akhir.'),
        ]);

        foreach (User::where('satuan_id', $danpusSatuan->id)->get() as $p) {
            $p->notify(new LaporanSuratBaruDiterima($laporanSurat));
        }

        ActivityLog::catat('laporan-surat.ke-danpus', "Meneruskan surat \"{$laporanSurat->perihal}\" kembali ke Danpus.", $user, [
            'laporan_surat_id' => $laporanSurat->id,
        ]);

        return back()->with('status', 'Surat berhasil diteruskan kembali ke Danpus untuk keputusan akhir.');
    }

    /**
     * Keputusan Danpus: PILIHAN A - Selesai.
     * Surat dianggap selesai, Hasil / Record Copy (RC) otomatis masuk ke Urdal.
     */
    public function selesai(Request $request, LaporanSurat $laporanSurat): RedirectResponse
    {
        $user   = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan && strtoupper((string) $satuan->kode) === 'DANPUS', 403, 'Hanya Danpus yang dapat menyelesaikan surat ini.');
        abort_unless(
            (int) $laporanSurat->tujuan_satuan_id === (int) $satuan->id,
            403,
            'Surat ini bukan sedang berada di Danpus.'
        );
        abort_unless($laporanSurat->isDikonfirmasi(), 422, 'Konfirmasi surat terlebih dahulu sebelum menyelesaikannya.');
        abort_if($laporanSurat->isSelesai(), 422, 'Surat ini sudah selesai.');

        $laporanSurat->update([
            'is_selesai'        => true,
            'status'            => LaporanSurat::STATUS_SELESAI,
            'selesai_at'        => now(),
            'selesai_oleh'      => $user->id,
            'dikonfirmasi_at'   => now(),
            'dikonfirmasi_oleh' => $user->id,
        ]);

        $riwayat = LaporanSuratRiwayat::create([
            'laporan_surat_id'   => $laporanSurat->id,
            'siklus'             => $laporanSurat->siklus,
            'aksi'               => LaporanSuratRiwayat::AKSI_SELESAI,
            'pengirim_satuan_id' => $satuan->id,
            'user_id'            => $user->id,
            'catatan'            => $request->input('catatan', 'Surat dinyatakan selesai oleh Danpus. Hasil / Record Copy (RC) masuk ke Urdal.'),
        ]);

        // Requirement 8A & 10: Hasil/RC masuk ke Urdal
        $urdalSatuan = Satuan::where('kode', 'URDAL')->first();
        if ($urdalSatuan) {
            LaporanSuratTembusan::create([
                'laporan_surat_id'         => $laporanSurat->id,
                'laporan_surat_riwayat_id' => $riwayat->id,
                'satuan_id'                => $urdalSatuan->id,
                'jenis'                    => LaporanSuratTembusan::JENIS_HASIL_RC,
            ]);

            foreach (User::where('satuan_id', $urdalSatuan->id)->get() as $u) {
                $u->notify(new LaporanSuratBaruDiterima($laporanSurat));
            }
        }

        ActivityLog::catat('laporan-surat.selesai', "Menyelesaikan surat \"{$laporanSurat->perihal}\". Hasil/RC masuk ke Urdal.", $user, [
            'laporan_surat_id' => $laporanSurat->id,
        ]);

        return back()->with('status', 'Surat telah selesai. Hasil / RC telah diteruskan ke Urdal.');
    }

    /**
     * Keputusan Danpus: PILIHAN B - Siklus Baru / Tindakan Baru.
     * Membuat disposisi baru (misal kembali ke Wadan) dan memilih tindakan baru.
     * Tindakan lama tetap tercatat sebagai riwayat dan tidak hilang.
     */
    public function disposisiUlang(Request $request, LaporanSurat $laporanSurat): RedirectResponse
    {
        $user   = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan && strtoupper((string) $satuan->kode) === 'DANPUS', 403, 'Hanya Danpus yang dapat membuat disposisi ulang.');
        abort_unless(
            (int) $laporanSurat->tujuan_satuan_id === (int) $satuan->id,
            403,
            'Surat ini bukan sedang berada di Danpus.'
        );
        abort_unless($laporanSurat->isDikonfirmasi(), 422, 'Konfirmasi surat terlebih dahulu sebelum membuat disposisi ulang.');
        abort_if($laporanSurat->isSelesai(), 422, 'Surat ini sudah selesai.');

        $validated = $request->validate([
            'tujuan_satuan_id' => ['required', 'integer', 'exists:satuans,id'],
            'disposisi'        => ['required', 'string'],
            'tindakan'         => ['required', 'array', 'min:1'],
            'tindakan.*'       => ['string'],
            'catatan'          => ['nullable', 'string', 'max:5000'],
            'lampiran'         => ['nullable', 'file', 'max:10240'],
        ], [
            'tujuan_satuan_id.required' => 'Satuan tujuan disposisi baru wajib dipilih.',
            'disposisi.required'        => 'Disposisi wajib dipilih.',
            'tindakan.required'         => 'Tindakan wajib dipilih minimal satu.',
            'tindakan.min'              => 'Pilih minimal satu tindakan.',
        ]);

        $tujuan = Satuan::findOrFail($validated['tujuan_satuan_id']);

        $lampiranPath     = $laporanSurat->lampiran_path;
        $lampiranNamaAsli = $laporanSurat->lampiran_nama_asli;

        if ($request->hasFile('lampiran')) {
            $file             = $request->file('lampiran');
            $lampiranPath     = $file->store('lampiran-surat', 'public');
            $lampiranNamaAsli = $file->getClientOriginalName();
        }

        $siklusBaru = (int) $laporanSurat->siklus + 1;

        $laporanSurat->update([
            'tujuan_satuan_id'   => $tujuan->id,
            'disposisi_terakhir' => $validated['disposisi'],
            'tindakan_terakhir'  => $validated['tindakan'],
            'siklus'             => $siklusBaru,
            'is_selesai'         => false,
            'status'             => LaporanSurat::STATUS_MENUNGGU,
            'lampiran_path'      => $lampiranPath,
            'lampiran_nama_asli' => $lampiranNamaAsli,
            'dikonfirmasi_at'    => null,
            'dikonfirmasi_oleh'  => null,
        ]);

        LaporanSuratRiwayat::create([
            'laporan_surat_id'   => $laporanSurat->id,
            'siklus'             => $siklusBaru,
            'aksi'               => LaporanSuratRiwayat::AKSI_TERUSKAN,
            'pengirim_satuan_id' => $satuan->id,
            'penerima_satuan_id' => $tujuan->id,
            'user_id'            => $user->id,
            'disposisi'          => $validated['disposisi'],
            'tindakan'           => $validated['tindakan'],
            'catatan'            => $validated['catatan'] ?? "Disposisi baru oleh Danpus (Siklus ke-{$siklusBaru}).",
            'lampiran_path'      => $lampiranPath,
            'lampiran_nama_asli' => $lampiranNamaAsli,
        ]);

        foreach (User::where('satuan_id', $tujuan->id)->get() as $p) {
            $p->notify(new LaporanSuratBaruDiterima($laporanSurat));
        }

        ActivityLog::catat('laporan-surat.disposisi-ulang', "Membuat disposisi baru untuk surat \"{$laporanSurat->perihal}\" (Siklus {$siklusBaru}).", $user, [
            'laporan_surat_id' => $laporanSurat->id,
            'siklus'           => $siklusBaru,
            'tujuan_satuan'    => $tujuan->nama,
        ]);

        return back()->with('status', "Disposisi & tindakan baru berhasil dikirim (Siklus {$siklusBaru}).");
    }

    /**
     * Konfirmasi tanda mengetahui bagi penerima Tembusan / View Only.
     */
    public function konfirmasiTembusan(Request $request, LaporanSurat $laporanSurat): RedirectResponse
    {
        $user   = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);

        $tembusan = LaporanSuratTembusan::where('laporan_surat_id', $laporanSurat->id)
            ->where('satuan_id', $satuan->id)
            ->firstOrFail();

        $tembusan->update([
            'dikonfirmasi_at'   => now(),
            'dikonfirmasi_oleh' => $user->id,
        ]);

        ActivityLog::catat('laporan-surat.konfirmasi-tembusan', "Mengkonfirmasi tanda mengetahui surat \"{$laporanSurat->perihal}\".", $user, [
            'laporan_surat_id' => $laporanSurat->id,
            'jenis'            => $tembusan->jenis,
        ]);

        return back()->with('status', 'Konfirmasi tanda mengetahui berhasil disimpan.');
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
