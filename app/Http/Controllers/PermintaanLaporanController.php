<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Laporan;
use App\Models\PermintaanLaporan;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\PermintaanLaporanBaru;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermintaanLaporanController extends Controller
{
    /**
     * Semua satuan pelaksana yang boleh jadi tujuan Permintaan Laporan dari
     * Danpus/Wadan: 4 Satlak, 4 Sdir (pembinaan), Unsur Pelayanan (Urdal),
     * Unsur Pembantu Pimpinan (Pok Analis), dan 21 Kasansi (Sansidam).
     * Diambil dari konstanta Satuan supaya selalu ikut sinkron kalau daftar
     * itu berubah, bukan daftar kode duplikat yang bisa ketinggalan zaman.
     */
    private static function pengirimKode(): array
    {
        return array_merge(
            Satuan::KODE_SATLAK,
            Satuan::KODE_PEMBINAAN,
            Satuan::KODE_UNSUR_PELAYANAN,
            Satuan::KODE_UNSUR_PEMBANTU_PIMPINAN,
            Satuan::KODE_KOTAMA,
        );
    }

    private function isPimpinan(Request $request): bool
    {
        $kode = strtoupper((string) $request->user()->load('satuan')->satuan?->kode);
        return in_array($kode, ['DANPUS', 'WADAN'], true);
    }

    private function isPengirim(Request $request): bool
    {
        $kode = strtoupper((string) $request->user()->load('satuan')->satuan?->kode);
        return in_array($kode, self::pengirimKode(), true);
    }

    public function index(Request $request): RedirectResponse
    {
        abort_unless($this->isPimpinan($request) || $this->isPengirim($request), 403);

        return redirect()->to(route('dashboard').'?tab=permintaan-laporan');
    }

    public function realtime(Request $request): JsonResponse
    {
        // Pimpinan memakai endpoint yang sama untuk mengambil data arsip
        // sehingga fitur baru tidak perlu menambah route baru yang berisiko
        // bertabrakan dengan alur realtime penerima yang sudah ada.
        if ($this->isPimpinan($request) && $request->boolean('history')) {
            // Riwayat Laporan Pimpinan (#riwayat) sekarang berupa KARTU (sama
            // seperti Riwayat Satuan) -- dirender di server pakai partial kartu
            // Pimpinan mode read-only, bukan lagi array buat renderArchivedItem()
            // (tabel lama). `archived_ids` tetap dikirim supaya
            // removeArchivedRows() di klien bisa menyingkirkan kartu yang baru
            // saja diputuskan dari daftar Permintaan Laporan yang masih aktif.
            $items = PermintaanLaporan::with(['pembuat.satuan', 'tujuanSatuan', 'laporan', 'laporans', 'tasks.laporans'])
                ->whereNotNull('archived_at')
                ->whereHas('pembuat.satuan', fn ($q) => $q->whereIn('kode', ['DANPUS', 'WADAN']))
                ->latest('archived_at')
                ->get();

            return response()->json([
                'items_html' => view('siberad.dashboards.partials.permintaan-laporan-pimpinan-riwayat-items', [
                    'permintaanLaporan' => $items,
                ])->render(),
                'archived_ids' => $items->pluck('id')->values(),
                'server_time' => now()->toIso8601String(),
                'pimpinan_satuan_nama' => $request->user()->satuan?->nama ?: 'DANPUS',
            ], 200, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        }

        // Kartu Permintaan Laporan versi Pimpinan yang MASIH AKTIF (belum
        // diarsip) -- dikirim sebagai HTML supaya klien tinggal replaceWith()
        // per kartu tiap siklus poll (status/progres/tugas/tombol ikut
        // ke-refresh), pola sama dengan sisi satuan (laporan-role-realtime-
        // sync.blade.php). Penghapusan kartu (diarsip/diputuskan) tetap
        // ditangani jalur ?history=1 di atas. Incremental: cuma kirim kartu
        // yang berubah sejak `since` (jam server), plus kartu baru.
        if ($this->isPimpinan($request) && $request->boolean('pimpinan')) {
            $sinceRaw = trim((string) $request->query('since', '0'));

            try {
                $since = ($sinceRaw !== '' && $sinceRaw !== '0')
                    ? \Illuminate\Support\Carbon::parse($sinceRaw)
                    : null;
            } catch (\Throwable $e) {
                $since = null;
            }

            $query = PermintaanLaporan::with(['pembuat.satuan', 'tujuanSatuan', 'laporan', 'laporans', 'tasks.laporans'])
                ->whereHas('pembuat.satuan', fn ($q) => $q->whereIn('kode', ['DANPUS', 'WADAN']))
                ->whereNull('archived_at');

            if ($since) {
                $now = now();
                $query->where(function ($q) use ($since, $now) {
                    $q->where('updated_at', '>', $since)
                        ->orWhereHas('tasks', fn ($t) => $t->where('updated_at', '>', $since))
                        ->orWhereHas('laporans', fn ($l) => $l->where('updated_at', '>', $since))
                        // Permintaan yang BARU lewat deadline sejak poll terakhir
                        // (jadi "Terlambat"). Statusnya properti terhitung
                        // (deadline_at vs now), updated_at gak berubah -- jadi
                        // harus dikirim eksplisit di sini biar kartu + modal
                        // "Lihat Progres" yang lagi kebuka ikut nyusut ke
                        // tampilan Terlambat tanpa perlu tutup-buka modal.
                        ->orWhere(function ($t) use ($since, $now) {
                            $t->whereNull('laporan_id')
                                ->whereNotIn('status', [
                                    PermintaanLaporan::STATUS_SELESAI,
                                    PermintaanLaporan::STATUS_PEMERIKSAAN,
                                    PermintaanLaporan::STATUS_DIBATALKAN,
                                ])
                                ->where('deadline_at', '>', $since)
                                ->where('deadline_at', '<=', $now);
                        });
                });
            }

            $items = $query->latest('id')->get();

            return response()->json([
                'items_html' => view('siberad.dashboards.partials.permintaan-laporan-pimpinan-realtime-items', [
                    'permintaanLaporan' => $items,
                    'satuan' => $request->user()->loadMissing('satuan')->satuan,
                ])->render(),
                'server_time' => now()->toIso8601String(),
            ], 200, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        }

        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        $kode = strtoupper((string) $satuan?->kode);
        abort_unless(in_array($kode, self::pengirimKode(), true), 403);

        $latestId = (int) (PermintaanLaporan::where('tujuan_satuan_id', $satuan->id)->max('id') ?? 0);
        $since = max(0, (int) $request->query('since', 0));

        $items = PermintaanLaporan::with(['pembuat.satuan', 'laporan', 'laporans', 'tasks'])
            ->where('tujuan_satuan_id', $satuan->id)
            ->whereNull('archived_at')
            // STATUS_DIBATALKAN ikut -- lihat komentar di DashboardController
            // (kartu dibatalkan tetap kelihatan satuan, read-only, tidak ilang).
            ->whereIn('status', [
                PermintaanLaporan::STATUS_BELUM,
                PermintaanLaporan::STATUS_DIKERJAKAN,
                PermintaanLaporan::STATUS_PEMERIKSAAN,
                PermintaanLaporan::STATUS_DIBATALKAN,
            ])
            ->where('id', '>', $since)
            ->orderBy('id')
            ->get();

        // "Laporan Masuk" pada dashboard penerima harus mencerminkan pekerjaan
        // yang masuk ke satuan ini. Permintaan laporan dibuat sebagai
        // PermintaanLaporan (bukan Laporan), sehingga menghitung tabel Laporan
        // saja membuat kartu tetap 0 walaupun ada permintaan baru.
        $permintaanMasukCount = PermintaanLaporan::where('tujuan_satuan_id', $satuan->id)
            ->whereNull('archived_at')
            ->whereIn('status', [
                PermintaanLaporan::STATUS_BELUM,
                PermintaanLaporan::STATUS_DIKERJAKAN,
                PermintaanLaporan::STATUS_PEMERIKSAAN,
            ])
            ->count();

        $laporanMasukCount = Laporan::where('tujuan_satuan_id', $satuan->id)
            ->where(function ($query) {
                $query->where('status', 'Menunggu')
                    ->orWhere('status', 'like', 'Revisi%');
            })
            ->count();

        $laporanMasukCount = max($permintaanMasukCount, $laporanMasukCount);

        // Riwayat Laporan (kartu status disetujui/ditolak/terlambat/dibatalkan)
        // ikut dikirim di endpoint yang sama supaya cuma butuh 1 fetch per
        // siklus poll -- daftarnya selalu FULL (bukan pakai since) karena
        // isinya perlu di-diff penuh: item bisa hilang lagi dari sini kalau
        // Pimpinan perpanjang deadline-nya (archived_at balik null).
        $riwayatItems = PermintaanLaporan::with(['pembuat.satuan', 'laporan', 'laporans', 'tasks'])
            ->where('tujuan_satuan_id', $satuan->id)
            ->whereNotNull('archived_at')
            ->latest('archived_at')
            ->get();

        return response()->json([
            'latest_id' => $latestId,
            'items_html' => view('siberad.dashboards.partials.permintaan-laporan-realtime-items', [
                'permintaanLaporan' => $items,
            ])->render(),
            'riwayat_items_html' => view('siberad.dashboards.partials.permintaan-laporan-realtime-items', [
                'permintaanLaporan' => $riwayatItems,
            ])->render(),
            'laporan_masuk_count' => $laporanMasukCount,
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($this->isPimpinan($request), 403);

        if ($request->boolean('archive_mode')) {
            return $this->archive($request);
        }

        $validated = $request->validate([
            'tujuan_satuan_ids' => ['required', 'array', 'min:1'],
            'tujuan_satuan_ids.*' => ['integer', 'distinct', 'exists:satuans,id'],
            'perihal' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'max:255'],
            'instruksi' => ['required', 'string', 'max:5000'],
            'deadline_at' => ['required', 'date', 'after:now'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
            'tasks' => ['required', 'array', 'min:1'],
            'tasks.*' => ['required', 'string', 'max:255'],
        ]);

        $tujuan = Satuan::whereIn('id', $validated['tujuan_satuan_ids'])
            ->whereIn('kode', self::pengirimKode())
            ->get()
            ->sortBy(fn ($s) => Satuan::kunciUrutSatuan($s->kategori, $s->kode))
            ->values();

        abort_if($tujuan->count() !== count($validated['tujuan_satuan_ids']), 422, 'Permintaan hanya dapat ditujukan kepada satuan pengirim yang tersedia.');

        $user = $request->user();
        $created = collect();

        DB::transaction(function () use ($validated, $tujuan, $user, &$created) {
            foreach ($tujuan as $satuan) {
                $permintaan = PermintaanLaporan::create([
                    'pembuat_id' => $user->id,
                    'tujuan_satuan_id' => $satuan->id,
                    'perihal' => $validated['perihal'],
                    'kategori' => $validated['kategori'] ?? null,
                    'instruksi' => $validated['instruksi'] ?? null,
                    'deadline_at' => $validated['deadline_at'],
                    'prioritas' => $validated['prioritas'],
                    'status' => PermintaanLaporan::STATUS_BELUM,
                ]);
                $created->push($permintaan);

                foreach (array_values($validated['tasks']) as $urutan => $deskripsi) {
                    $permintaan->tasks()->create([
                        'deskripsi' => $deskripsi,
                        'urutan' => $urutan,
                    ]);
                }

                foreach (User::where('satuan_id', $satuan->id)->get() as $penerima) {
                    $penerima->notify(new PermintaanLaporanBaru($permintaan));
                }
            }
        });

        ActivityLog::catat('permintaan-laporan.create', "Mengirim permintaan laporan \"{$validated['perihal']}\" kepada ".$tujuan->pluck('nama')->join(', ', ' dan ').".", $user, [
            'permintaan_laporan_ids' => $created->pluck('id')->all(),
            'satuan_tujuan' => $tujuan->pluck('nama')->all(),
        ]);

        return redirect()->to(route('dashboard').'?tab=permintaan-laporan')
            ->with('status', 'Permintaan telah terkirim ke Satuan');
    }

    private function archive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'permintaan_laporan_ids' => ['required', 'array', 'min:1'],
            'permintaan_laporan_ids.*' => ['integer', 'distinct'],
        ]);

        $ids = collect($validated['permintaan_laporan_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $items = PermintaanLaporan::with(['pembuat.satuan', 'tujuanSatuan', 'laporan', 'tasks.laporans'])
            ->whereIn('id', $ids)
            ->whereNull('archived_at')
            ->whereHas('pembuat.satuan', fn ($q) => $q->whereIn('kode', ['DANPUS', 'WADAN']))
            ->get()
            ->filter(function (PermintaanLaporan $item) {
                if ($item->status === PermintaanLaporan::STATUS_DIBATALKAN || $item->isTerlambat()) {
                    return true;
                }

                if ($item->status !== PermintaanLaporan::STATUS_SELESAI) {
                    return false;
                }

                $status = strtolower((string) $item->laporan?->status);
                return str_contains($status, 'tolak')
                    || str_contains($status, 'setuj')
                    || str_contains($status, 'diterima');
            })
            ->values();

        abort_if($items->isEmpty(), 422, 'Tidak ada permintaan terpilih yang dapat diarsipkan.');

        DB::transaction(function () use ($items) {
            $items->each(fn (PermintaanLaporan $item) => $item->update(['archived_at' => now()]));
        });

        ActivityLog::catat(
            'permintaan-laporan.arsip',
            'Mengarsipkan '. $items->count() .' permintaan laporan dari daftar aktif.',
            $request->user(),
            ['permintaan_laporan_ids' => $items->pluck('id')->all()]
        );

        // Riwayat Laporan Pimpinan sekarang kartu -- klien cukup tahu id mana
        // yang barusan diarsipkan (buat animasi keluar dari daftar aktif), lalu
        // tarik ulang kartu Riwayat lewat syncRiwayatCards(). Tidak perlu lagi
        // kirim payload arsipItemData() buat renderArchivedItem() tabel lama.
        return response()->json([
            'message' => $items->count().' permintaan laporan berhasil dipindahkan ke Arsip Laporan.',
            'archived_ids' => $items->pluck('id')->values(),
        ]);
    }

    private function arsipItemData(PermintaanLaporan $item): array
    {
        $finalStatus = strtolower((string) $item->laporan?->status);
        $statusLabel = $item->isTerlambat()
            ? 'Terlambat'
            : ($item->status === PermintaanLaporan::STATUS_DIBATALKAN
                ? 'Dibatalkan'
                : (str_contains($finalStatus, 'tolak')
                    ? 'Ditolak'
                    : (str_contains($finalStatus, 'setuj') || str_contains($finalStatus, 'diterima')
                        ? 'Disetujui'
                        : $item->status)));

        return [
            'id' => $item->id,
            'tujuan' => $item->tujuanSatuan?->kode ?: ($item->tujuanSatuan?->nama ?: '-'),
            'tujuan_nama' => $item->tujuanSatuan?->nama ?: '-',
            'tujuan_satuan_id' => $item->tujuan_satuan_id,
            // Dipakai menu titik-3 "Revisi" di baris Riwayat/Status yang statusnya
            // "Ditolak" (danpus-permintaan-arsip-mode.blade.php) -- POST ke
            // /laporan/{id}/status status=Revisi buat buka lagi permintaannya.
            'laporan_id' => $item->laporan_id,
            'perihal' => $item->perihal,
            'kategori' => $item->kategori ?: '-',
            'prioritas' => $item->prioritas ?: '-',
            'deadline' => $item->deadline_at?->translatedFormat('d M Y, H:i') ?: '-',
            'status' => $statusLabel,
            'archived_at' => $item->archived_at?->translatedFormat('d M Y, H:i') ?: now()->translatedFormat('d M Y, H:i'),
            // 'laporan' per task disertakan supaya task yang diklik di dropdown
            // arsip bisa buka modal "Detail Aktivitas Laporan" beneran (data
            // asli), sama kayak request-task-step di tab Permintaan Laporan
            // yang masih aktif (lihat $rtLaporan di laporan-pimpinan.blade.php).
            'tasks' => $item->tasks->sortBy('urutan')->values()->map(function ($task) {
                $laporan = $task->laporans->sortByDesc('id')->first();

                return [
                    'deskripsi' => $task->deskripsi,
                    'selesai' => (bool) $task->selesai,
                    'selesai_at' => $task->selesai_at?->translatedFormat('d M Y H:i'),
                    'laporan' => $laporan ? [
                        'perihal' => $laporan->perihal,
                        'prioritas' => $laporan->prioritas,
                        'progres' => $laporan->progres,
                        'kendala' => $laporan->kendala,
                        'proyek' => $laporan->proyek,
                        'tanggal' => $laporan->created_at?->translatedFormat('d M Y H:i'),
                        'deskripsi' => $laporan->deskripsi,
                        'lampiran' => $laporan->semuaLampiran->map(fn ($x) => [
                            'url' => asset('storage/'.$x->path),
                            'nama' => $x->nama_asli,
                        ])->values(),
                    ] : null,
                ];
            })->all(),
        ];
    }

    public function mulai(Request $request, PermintaanLaporan $permintaanLaporan): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        abort_unless($user->satuan, 403);
        abort_unless((int) $permintaanLaporan->tujuan_satuan_id === (int) $user->satuan->id, 403);
        abort_unless(in_array(strtoupper((string) $user->satuan->kode), self::pengirimKode(), true), 403);
        abort_if($permintaanLaporan->laporan_id, 422, 'Permintaan ini sudah memiliki laporan.');
        abort_if($permintaanLaporan->status === PermintaanLaporan::STATUS_DIBATALKAN, 422, 'Permintaan ini sudah dibatalkan oleh Pimpinan.');
        abort_if($permintaanLaporan->archived_at, 422, 'Permintaan ini sudah masuk Arsip Laporan.');

        $permintaanLaporan->update([
            'status' => PermintaanLaporan::STATUS_DIKERJAKAN,
            'dikerjakan_at' => $permintaanLaporan->dikerjakan_at ?? now(),
        ]);

        ActivityLog::catat('permintaan-laporan.mulai', "Menandai permintaan laporan \"{$permintaanLaporan->perihal}\" sedang dikerjakan.", $user, [
            'permintaan_laporan_id' => $permintaanLaporan->id,
        ]);

        return back()->with('status', 'Permintaan laporan ditandai sedang dikerjakan.');
    }

    public function batal(Request $request, PermintaanLaporan $permintaanLaporan): RedirectResponse|JsonResponse
    {
        abort_unless($this->isPimpinan($request), 403);
        abort_unless($permintaanLaporan->isDapatDibatalkan(), 422, 'Permintaan ini sudah tidak dapat dibatalkan.');
        abort_if($permintaanLaporan->archived_at, 422, 'Permintaan ini sudah masuk Arsip Laporan.');

        $permintaanLaporan->update([
            'status' => PermintaanLaporan::STATUS_DIBATALKAN,
            'dibatalkan_at' => now(),
        ]);

        ActivityLog::catat('permintaan-laporan.batal', "Membatalkan permintaan laporan \"{$permintaanLaporan->perihal}\" kepada {$permintaanLaporan->tujuanSatuan->nama}.", $request->user(), [
            'permintaan_laporan_id' => $permintaanLaporan->id,
        ]);

        // Dipanggil AJAX dari modal "Lihat Progres" (biar modalnya nggak ketutup
        // sama reload) -> balikin JSON, klien yang nyegerin kartu + isi modal.
        if ($request->expectsJson()) {
            return response()->json(['status' => 'Permintaan laporan telah dibatalkan.']);
        }

        return back()->with('status', 'Permintaan laporan telah dibatalkan.');
    }

    public function editDeadline(Request $request, PermintaanLaporan $permintaanLaporan): RedirectResponse|JsonResponse
    {
        abort_unless($this->isPimpinan($request), 403);
        $permintaanLaporan->loadMissing('laporan');
        abort_unless($permintaanLaporan->isDapatEditDeadline(), 422, $permintaanLaporan->alasanTidakBisaEditDeadline());
        abort_if($permintaanLaporan->archived_at, 422, 'Permintaan ini sudah masuk Arsip Laporan.');

        $validated = $request->validate([
            'deadline_at' => ['required', 'date', 'after:now'],
        ], [
            'deadline_at.after' => 'Deadline baru harus lebih besar dari waktu sekarang.',
        ]);

        $perluDibukaKembali = in_array($permintaanLaporan->status, [
            PermintaanLaporan::STATUS_SELESAI,
            PermintaanLaporan::STATUS_DIBATALKAN,
        ], true);

        $permintaanLaporan->update([
            'deadline_at' => $validated['deadline_at'],
            'status' => $perluDibukaKembali ? PermintaanLaporan::STATUS_DIKERJAKAN : $permintaanLaporan->status,
            'laporan_id' => $perluDibukaKembali ? null : $permintaanLaporan->laporan_id,
            'dibatalkan_at' => null,
        ]);

        ActivityLog::catat('permintaan-laporan.edit-deadline', "Memperpanjang deadline permintaan laporan \"{$permintaanLaporan->perihal}\" untuk {$permintaanLaporan->tujuanSatuan->nama}.", $request->user(), [
            'permintaan_laporan_id' => $permintaanLaporan->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'Deadline permintaan laporan berhasil diperbarui.']);
        }

        return back()->with('status', 'Deadline permintaan laporan berhasil diperbarui.');
    }

    /**
     * "Revisi" dari kartu Riwayat Laporan Pimpinan (menu titik-3). Berlaku
     * untuk permintaan yang SUDAH di-arsip dengan status akhir Ditolak /
     * Terlambat / Dibatalkan (pokoknya selain Disetujui) -- Pimpinan kasih
     * deadline baru, lalu permintaannya keluar dari Riwayat & aktif lagi buat
     * satuan (status "Sedang dikerjakan"). Sengaja dipisah dari editDeadline()
     * yang justru MEMBLOKIR item ber-archived_at.
     */
    public function revisiDariRiwayat(Request $request, PermintaanLaporan $permintaanLaporan): RedirectResponse|JsonResponse
    {
        abort_unless($this->isPimpinan($request), 403);
        abort_unless($permintaanLaporan->archived_at, 422, 'Permintaan ini tidak ada di Arsip Laporan.');

        $permintaanLaporan->loadMissing('laporan');
        $laporanStatus = strtolower((string) $permintaanLaporan->laporan?->status);
        abort_if(
            str_contains($laporanStatus, 'setuj') || str_contains($laporanStatus, 'diterima'),
            422,
            'Laporan yang sudah disetujui tidak dapat dikirim ulang untuk revisi.'
        );

        $validated = $request->validate([
            'deadline_at' => ['required', 'date', 'after:now'],
        ], [
            'deadline_at.after' => 'Deadline baru harus lebih besar dari waktu sekarang.',
        ]);

        // Laporan final TERAKHIR (yang barusan ditolak / masih menunggu) ditandai
        // "Revisi" -- persis efek keputusan "Revisi" lewat LaporanController::
        // updateStatus. Tanpa ini PermintaanLaporan::isSedangRevisi() tetap
        // false (dia cek str_contains(status_laporan_terakhir, 'revisi')), jadi
        // kartu satuan salah render: tombol "Edit & Kirim Final" yang nembak
        // updateProgres() -> ketolak "Hanya checkpoint progres yang belum final
        // yang dapat diperbarui", bukan tombol "Revisi" yang lewat store()
        // (kirim laporan baru). Checkpoint progres di tengah jalan (belum ada
        // laporan final) SENGAJA dibiarkan supaya checklist task tetap jalan.
        $laporanTerakhir = $permintaanLaporan->laporans()->latest('id')->first();
        if (
            $laporanTerakhir
            && $laporanTerakhir->status !== Laporan::STATUS_PROGRES
            && ! str_contains(strtolower((string) $laporanTerakhir->status), 'revisi')
        ) {
            $kodePimpinan = strtoupper((string) $request->user()->satuan?->kode);
            $laporanTerakhir->update([
                'status' => match ($kodePimpinan) {
                    'DANPUS' => 'Revisi DANPUS',
                    'WADAN' => 'Revisi WADAN',
                    default => 'Revisi',
                },
            ]);
        }

        $permintaanLaporan->update([
            'deadline_at' => $validated['deadline_at'],
            'status' => PermintaanLaporan::STATUS_DIKERJAKAN,
            'laporan_id' => null,
            'dibatalkan_at' => null,
            'selesai_at' => null,
            'archived_at' => null,
        ]);

        ActivityLog::catat('permintaan-laporan.revisi', "Mengirim ulang permintaan laporan \"{$permintaanLaporan->perihal}\" untuk {$permintaanLaporan->tujuanSatuan->nama} dengan deadline baru.", $request->user(), [
            'permintaan_laporan_id' => $permintaanLaporan->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'Permintaan laporan dikirim ulang untuk revisi dengan deadline baru.']);
        }

        return back()->with('status', 'Permintaan laporan dikirim ulang untuk revisi dengan deadline baru.');
    }
}
