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
use Illuminate\Validation\Rule;

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
        // Wadan: surat masuk termasuk yang sudah dikonfirmasi TAPI belum
        // diteruskan ke satuan lain -- surat baru pindah ke Arsip setelah
        // Wadan klik "Teruskan Surat" (tujuan_satuan_id pindah dari Wadan)
        // atau setelah is_selesai. Sama pola dengan DashboardController.
        // Tanpa pengecualian ini, polling realtime akan "menghilangkan"
        // tombol Teruskan Surat begitu Wadan klik Konfirmasi, karena surat
        // langsung dianggap pindah ke Arsip padahal belum diteruskan.
        $isWadan = $kodeSatuan === 'WADAN';

        // 1. Surat Masuk:
        //    a. Surat di mana satuan ini adalah tujuan utama (tujuan_satuan_id)
        //       DAN berstatus MENUNGGU (atau, khusus Wadan, DIKONFIRMASI TAPI
        //       belum diteruskan) DAN belum selesai.
        //       (Khusus Danpus: surat awal Danpus TIDAK tampil di Surat Masuk Danpus sendiri,
        //        tetapi tetap di Surat Keluar Danpus sampai selesai).
        //    b. Surat di mana satuan ini terdaftar sebagai View Only atau Tembusan
        //       yang BELUM dikonfirmasi oleh satuan ini, dan belum selesai.
        $suratMasukUtama = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'tembusans'])
            ->where('tujuan_satuan_id', $satuan->id)
            ->where('is_selesai', false)
            ->when($isWadan, function ($q) {
                $q->whereIn('status', [LaporanSurat::STATUS_MENUNGGU, LaporanSurat::STATUS_DIKONFIRMASI]);
            }, function ($q) {
                $q->where('status', LaporanSurat::STATUS_MENUNGGU);
            })
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
        //    a. PENGIRIM ASLI (pembuat surat, satuan_id): baru masuk Arsip kalau
        //       SELURUH alur Danpus>Wadan>Satuan>Urdal sudah benar-benar tuntas
        //       (is_selesai = true, lewat aksi "Selesai" oleh Danpus) -- BUKAN
        //       cuma karena satu penerima di tengah alur (mis. Wadan) baru
        //       konfirmasi terima. Selama belum is_selesai, surat tetap tampil
        //       di Surat Keluar sisi pengirim (lihat query Surat Keluar di atas),
        //       supaya tidak nyangkut/ganda tampil di dua tempat sekaligus.
        //    b. PEMEGANG SAAT INI (tujuan_satuan_id): masuk Arsip kalau sudah
        //       dia sendiri konfirmasi (STATUS_DIKONFIRMASI) atau surat sudah
        //       is_selesai -- ini status konfirmasi langkahnya SENDIRI, terpisah
        //       dari status pengirim asli di atas.
        //    c. Tembusan yang sudah dikonfirmasi oleh satuan ini.
        //    d. Surat yang PERNAH ditangani/diteruskan oleh satuan ini (tercatat sebagai
        //       pengirim atau penerima di riwayat alur), tapi sekarang tanggung jawabnya
        //       sudah pindah ke satuan lain -- tetap tercatat sebagai riwayat historis di
        //       Arsip, supaya tidak "hilang" begitu saja setelah diteruskan lebih lanjut.
        $arsip = LaporanSurat::with(['satuan', 'tujuanSatuan', 'riwayats.pengirimSatuan', 'tembusans'])
            ->where(function ($q) use ($satuan) {
                // (a) Pengirim asli -- nunggu is_selesai, bukan status per-langkah.
                $q->where('satuan_id', $satuan->id)
                  ->where('is_selesai', true);
            })
            ->orWhere(function ($q) use ($satuan, $isWadan) {
                // (b) Pemegang saat ini -- status konfirmasi langkahnya sendiri.
                //     Khusus Wadan: SELAMA belum diteruskan (masih tujuan ke
                //     Wadan) dan belum is_selesai, surat TIDAK dianggap masuk
                //     Arsip walau statusnya sudah DIKONFIRMASI -- supaya
                //     tombol Teruskan Surat di Surat Masuk tidak hilang.
                $q->where('tujuan_satuan_id', $satuan->id)
                  ->where(function ($st) use ($isWadan) {
                      if ($isWadan) {
                          $st->where('is_selesai', true);
                      } else {
                          $st->where('status', LaporanSurat::STATUS_DIKONFIRMASI)
                             ->orWhere('is_selesai', true);
                      }
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
            ->orWhere(function ($q) use ($satuan) {
                // Pernah menangani (via riwayat), tapi bukan lagi pembuat asli
                // maupun pemegang aktif saat ini -- surat sudah lanjut ke
                // satuan berikutnya, jadi tampil sebagai riwayat historis saja.
                $q->where('satuan_id', '!=', $satuan->id)
                  ->where('tujuan_satuan_id', '!=', $satuan->id)
                  ->whereHas('riwayats', function ($rq) use ($satuan) {
                      $rq->where('pengirim_satuan_id', $satuan->id)
                         ->orWhere('penerima_satuan_id', $satuan->id);
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

        // Disposisi surat awal Danpus SELALU "WADAN" -- field-nya di form udah
        // dikunci/disable, jadi validasinya juga cuma nerima nilai itu (bukan
        // lagi daftar DISPOSISI_DANPUS_OPTIONS punya Wadan).
        $disposisiRules = $isDanpus
            ? ['required', 'string', 'in:'.LaporanSurat::DISPOSISI_AWAL_DANPUS]
            : ['nullable', 'string'];
        $tindakanRules = $isDanpus
            ? ['required', 'array', 'min:1']
            : ['nullable', 'array'];
        // Isi Ringkasan Surat: opsional buat Danpus (baik Rahasia maupun
        // prioritas lain) -- isinya toh nggak pernah dipaksa wajib lagi
        // sejak field ini dibikin opsional di form Buat Surat Danpus.
        $deskripsiRules = $isDanpus
            ? ['nullable', 'string', 'max:10000']
            : ['required', 'string', 'max:10000'];

        // Fondasi alur "Surat dari Satlak ke Urdal": khusus 4 Satlak (Kal,
        // Sisos, Dak, Duktek) tujuan utama Surat Keluar SELALU dikunci ke
        // Urdal -- bukan pilihan bebas seperti satuan lain. Tembusan (CC)
        // tetap boleh bebas pilih satuan lain, KECUALI Danpus & Wadan
        // (keduanya cuma boleh menerima surat lewat alur disposisi resmi,
        // bukan tembusan langsung dari Satlak). Lihat juga penguncian yang
        // sama di sisi form -- resources/views/.../laporan-role.blade.php.
        $isSatlak       = in_array($kodeAsal, Satuan::KODE_SATLAK, true);
        $urdalSatuan    = $isSatlak ? Satuan::where('kode', 'URDAL')->first() : null;
        abort_if($isSatlak && ! $urdalSatuan, 500, 'Satuan Urdal belum terdaftar di sistem.');

        $tujuanRules = ($isSatlak && $urdalSatuan)
            ? ['required', 'integer', Rule::in([$urdalSatuan->id])]
            : ['required', 'integer', 'exists:satuans,id'];

        $tembusanItemRules = ['integer', 'exists:satuans,id'];
        if ($isSatlak) {
            $idDanpusWadan = Satuan::whereIn('kode', ['DANPUS', 'WADAN'])->pluck('id')->all();
            $tembusanItemRules[] = Rule::notIn($idDanpusWadan);
        }

        $validated = $request->validate([
            'induk_surat_id'   => ['nullable', 'integer', 'exists:laporan_surats,id'],
            'tujuan_satuan_id' => $tujuanRules,
            'perihal'          => ['required', 'string', 'max:255'],
            'kategori'         => ['required', 'string', 'max:255'],
            'deskripsi'        => $deskripsiRules,
            'prioritas'        => $prioritasRules,
            'disposisi'        => $disposisiRules,
            'tindakan'         => $tindakanRules,
            'tindakan.*'       => ['string'],
            'tembusan'         => ['nullable', 'array'],
            'tembusan.*'       => $tembusanItemRules,
            'lampiran'         => ['required', 'file', 'max:10240'],
        ], [
            'tujuan_satuan_id.required' => 'Tujuan surat wajib dipilih.',
            'tujuan_satuan_id.in'       => 'Tujuan surat dari Satlak wajib ke Urdal.',
            'disposisi.required'        => 'Disposisi wajib dipilih.',
            'tindakan.required'         => 'Tindakan wajib dipilih.',
            'tindakan.min'              => 'Pilih minimal satu tindakan.',
            'tembusan.*.not_in'         => 'Tembusan tidak boleh ditujukan ke Danpus atau Wadan.',
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
    public function konfirmasi(Request $request, LaporanSurat $laporanSurat): RedirectResponse|JsonResponse
    {
        $user   = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);

        // Dipakai oleh tombol "Konfirmasi" mode simpel Wadan (dipanggil via
        // fetch/AJAX supaya modal Detail Surat TIDAK ikut ter-reload/tertutup
        // seperti submit form biasa) -- lihat surat-detail-modal.blade.php.
        $wantsJson = $request->wantsJson() || $request->ajax();

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

            $pesan = 'Surat "'.$laporanSurat->perihal.'" berhasil dikonfirmasi (Mengetahui).';

            if ($wantsJson) {
                return response()->json(['status' => 'ok', 'message' => $pesan]);
            }

            return back()->with('status', $pesan);
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

        $pesan = 'Surat "'.$laporanSurat->perihal.'" berhasil dikonfirmasi / ACC & Diterima.';

        if ($wantsJson) {
            return response()->json([
                'status'             => 'ok',
                'message'            => $pesan,
                'dikonfirmasi_oleh'  => $satuan->nama,
                'dikonfirmasi_tanggal' => $laporanSurat->dikonfirmasi_at->translatedFormat('d M Y H:i'),
            ]);
        }

        return back()->with('status', $pesan);
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
        // Catatan: dulu wajib klik "Konfirmasi / ACC & Terima" dulu baru bisa
        // meneruskan. Sekarang Wadan bisa langsung "Teruskan Surat" dari
        // modal Surat Masuk tanpa langkah konfirmasi terpisah -- kalau surat
        // belum dikonfirmasi, otomatis dikonfirmasi dulu (dicatat ke riwayat)
        // sesaat sebelum diteruskan, supaya jejak riwayatnya tetap lengkap.
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

        // Auto-konfirmasi implisit kalau surat ini belum sempat dikonfirmasi
        // manual (mis. Wadan langsung klik "Teruskan Surat" dari surat baru).
        if (! $laporanSurat->isDikonfirmasi()) {
            $laporanSurat->update([
                'status'            => LaporanSurat::STATUS_DIKONFIRMASI,
                'dikonfirmasi_at'   => now(),
                'dikonfirmasi_oleh' => $user->id,
            ]);

            LaporanSuratRiwayat::create([
                'laporan_surat_id'   => $laporanSurat->id,
                'siklus'             => $laporanSurat->siklus,
                'aksi'               => LaporanSuratRiwayat::AKSI_KONFIRMASI,
                'pengirim_satuan_id' => $satuan->id,
                'user_id'            => $user->id,
                'catatan'            => "Surat dikonfirmasi / ACC & Diterima oleh {$satuan->nama}.",
            ]);
        }

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
     * Urdal meneruskan surat ke Wadan (mis. setelah cek & konfirmasi Surat
     * Keluar dari Satlak). Sekali klik, tanpa form disposisi/tindakan --
     * sama polanya dengan kembalikanKeDanpus() di atas (Wadan -> Danpus).
     */
    public function teruskanKeWadan(Request $request, LaporanSurat $laporanSurat): RedirectResponse
    {
        $user   = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);
        abort_unless(strtoupper((string) $satuan->kode) === 'URDAL', 403, 'Hanya Urdal yang dapat meneruskan surat ini ke Wadan.');
        abort_unless(
            (int) $laporanSurat->tujuan_satuan_id === (int) $satuan->id,
            403,
            'Surat ini bukan sedang berada di satuan Anda.'
        );
        abort_unless($laporanSurat->isDikonfirmasi(), 422, 'Konfirmasi surat terlebih dahulu sebelum meneruskannya ke Wadan.');
        abort_if($laporanSurat->isSelesai(), 422, 'Surat ini sudah selesai.');

        $wadanSatuan = Satuan::where('kode', 'WADAN')->firstOrFail();

        $laporanSurat->update([
            'tujuan_satuan_id'  => $wadanSatuan->id,
            'status'            => LaporanSurat::STATUS_MENUNGGU,
            'dikonfirmasi_at'   => null,
            'dikonfirmasi_oleh' => null,
        ]);

        LaporanSuratRiwayat::create([
            'laporan_surat_id'   => $laporanSurat->id,
            'siklus'             => $laporanSurat->siklus,
            'aksi'               => LaporanSuratRiwayat::AKSI_TERUSKAN,
            'pengirim_satuan_id' => $satuan->id,
            'penerima_satuan_id' => $wadanSatuan->id,
            'user_id'            => $user->id,
            'catatan'            => $request->input('catatan', 'Diteruskan oleh Urdal ke Wadan.'),
        ]);

        foreach (User::where('satuan_id', $wadanSatuan->id)->get() as $p) {
            $p->notify(new LaporanSuratBaruDiterima($laporanSurat));
        }

        ActivityLog::catat('laporan-surat.ke-wadan', "Meneruskan surat \"{$laporanSurat->perihal}\" ke Wadan.", $user, [
            'laporan_surat_id' => $laporanSurat->id,
        ]);

        return back()->with('status', 'Surat berhasil diteruskan ke Wadan.');
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
