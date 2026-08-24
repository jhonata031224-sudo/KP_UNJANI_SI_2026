<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Laporan;
use App\Models\PermintaanLaporan;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanBaruDiterima;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LaporanController extends Controller
{
    private const KODE_SATUAN_PELAKSANA = [
        'SATLAKDAK', 'SATLAKKAL', 'SATLAKSISOS', 'SATLAKDUKTEK',
        'DIKLAT', 'BINUM', 'BINFUNG', 'BINMAT',
    ];

    /**
     * Baris "kosong" untuk tabel Riwayat Laporan / Laporan Masuk pada sinkronisasi
     * realtime, agar sama dengan keterangan @empty di laporan-role.blade.php.
     * Tanpa ini, replaceBody() di laporan-role-realtime-sync.blade.php akan
     * menimpa tbody dengan string kosong setiap polling sehingga keterangan
     * "Belum ada laporan" hilang begitu realtime sync jalan.
     */
    private function emptyStateRow(string $title, string $subtitle): string
    {
        $title = e($title);
        $subtitle = e($subtitle);

        return <<<HTML
        <tr><td colspan="6"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">{$title}</div><div class="empty-state-sub">{$subtitle}</div></div></td></tr>
        HTML;
    }

    public function realtime(Request $request): JsonResponse
    {
        $user = $request->user()->load('satuan');
        $kode = strtoupper((string) $user->satuan?->kode);
        $isPimpinan = in_array($kode, ['DANPUS', 'WADAN'], true);
        $isPelaksana = in_array($kode, self::KODE_SATUAN_PELAKSANA, true);
        abort_unless($isPimpinan || $isPelaksana, 403);

        $includeReports = $request->query('reports', '1') !== '0';
        $includeRequests = $request->query('requests', '1') !== '0';
        $since = max(0, (int) $request->query('since', 0));

        if ($isPelaksana) {
            $sent = Laporan::with(['satuan', 'tujuanSatuan'])
                ->where('satuan_id', $user->satuan->id)
                ->latest('id')
                ->get();
            $incoming = Laporan::with(['satuan', 'tujuanSatuan'])
                ->where('tujuan_satuan_id', $user->satuan->id)
                ->where(function ($query) {
                    $query->where('status', 'Menunggu')
                        ->orWhere('status', 'like', 'Revisi%');
                })
                ->latest('id')
                ->get();

            $semuaPermintaan = PermintaanLaporan::where('tujuan_satuan_id', $user->satuan->id)->get();
            $roleStats = [
                'masuk' => $incoming->count(),
                'disetujui' => $sent->filter(fn ($l) => str_contains(strtolower($l->status), 'setuj') || str_contains(strtolower($l->status), 'diterima'))->count(),
                'ditolak' => $sent->filter(fn ($l) => str_contains(strtolower($l->status), 'tolak'))->count(),
                'terlambat' => $semuaPermintaan->filter(fn ($p) => $p->isTerlambat())->count(),
                'dibatalkan' => $semuaPermintaan->where('status', PermintaanLaporan::STATUS_DIBATALKAN)->count(),
            ];

            return response()->json([
                'role' => 'pelaksana',
                'sent_html' => $includeReports
                    ? ($sent->isEmpty()
                        ? $this->emptyStateRow('Belum ada laporan', 'Riwayat laporan yang kamu kirim akan muncul di sini.')
                        : $sent->map(fn ($l) => view('siberad.dashboards.partials.laporan-role-realtime-sent-row', ['l' => $l])->render())->implode(''))
                    : '',
                'incoming_html' => $includeReports
                    ? ($incoming->isEmpty()
                        ? $this->emptyStateRow('Belum ada laporan masuk', 'Laporan dari satuan lain akan muncul di sini.')
                        : $incoming->map(fn ($l) => view('siberad.dashboards.partials.laporan-role-realtime-incoming-row', ['l' => $l, 'canReview' => true, 'satuan' => $user->satuan])->render())->implode(''))
                    : '',
                'role_stats' => $roleStats,
            ], 200, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        }

        $satuanIds = Satuan::whereIn('kode', self::KODE_SATUAN_PELAKSANA)->pluck('id');
        $latestId = (int) (Laporan::whereIn('satuan_id', $satuanIds)->max('id') ?? 0);

        $items = $includeReports
            ? Laporan::with(['satuan', 'tujuanSatuan', 'permintaanLaporan'])
                ->whereIn('satuan_id', $satuanIds)
                ->where('id', '>', $since)
                ->orderBy('id')
                ->get()
            : collect();

        $rowsBySatuan = $items->groupBy('satuan_id')->map(
            fn ($group) => $group
                ->map(fn ($l) => view('siberad.dashboards.partials.laporan-pimpinan-row', ['l' => $l])->render())
                ->implode('')
        );

        $semuaStatus = Laporan::whereIn('satuan_id', $satuanIds)->get(['satuan_id', 'status']);
        $statsBySatuan = collect($satuanIds)->mapWithKeys(function ($satuanId) use ($semuaStatus) {
            $group = $semuaStatus->where('satuan_id', $satuanId);
            return [$satuanId => [
                'total' => $group->count(),
                'menunggu' => $group->where('status', 'Menunggu')->count(),
                'diterima' => $group->filter(fn ($l) => str_contains(strtolower($l->status), 'setuj') || str_contains(strtolower($l->status), 'diterima'))->count(),
                'ditolak' => $group->filter(fn ($l) => str_contains(strtolower($l->status), 'tolak'))->count(),
            ]];
        });

        $meta = $items->map(fn ($l) => [
            'laporan_id' => $l->id,
            'satuan_id' => $l->satuan_id,
            'satuan_nama' => $l->satuan->nama ?? 'Satuan',
            'perihal' => $l->perihal,
            'progres' => $l->progres,
            'is_progres' => $l->status === Laporan::STATUS_PROGRES,
        ])->values();

        $requestStates = [];
        if ($includeRequests) {
            $requests = PermintaanLaporan::with(['laporan', 'laporans'])
                ->whereHas('pembuat.satuan', fn ($q) => $q->whereIn('kode', ['DANPUS', 'WADAN']))
                ->latest('id')
                ->get();

            foreach ($requests as $permintaan) {
                $latestReport = $permintaan->laporans->sortByDesc('id')->first();
                $requestStates[(string) $permintaan->id] = [
                    'id' => $permintaan->id,
                    'status' => $permintaan->status,
                    'progres' => (int) $permintaan->progres,
                    'laporan_id' => $permintaan->laporan_id,
                    'laporan_status' => $latestReport?->status ?? $permintaan->laporan?->status ?? '',
                    'ditinjau_at' => $permintaan->dikerjakan_at?->translatedFormat('d M Y H:i'),
                    'dibatalkan_at' => $permintaan->dibatalkan_at?->translatedFormat('d M Y H:i'),
                    'terlambat' => $permintaan->isTerlambat(),
                ];
            }
        }

        return response()->json([
            'role' => 'pimpinan',
            'latest_id' => $latestId,
            'rows' => $rowsBySatuan,
            'stats' => $statsBySatuan,
            'items' => $meta,
            'request_states' => $requestStates,
            'total_laporan' => $semuaStatus->count(),
            'total_disetujui' => $semuaStatus->filter(fn ($l) => str_contains(strtolower($l->status), 'setuj') || str_contains(strtolower($l->status), 'diterima'))->count(),
            'total_ditolak' => $semuaStatus->filter(fn ($l) => str_contains(strtolower($l->status), 'tolak'))->count(),
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tujuan_satuan_id' => ['required', 'integer', 'exists:satuans,id'],
            'permintaan_laporan_id' => ['required', 'integer', 'exists:permintaan_laporans,id'],
            'proyek' => ['nullable', 'string', 'max:255'],
            'perihal' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:10000'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'progres' => ['required', 'integer', 'min:0', 'max:100'],
            'kendala' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;
        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');

        $tujuan = Satuan::findOrFail($validated['tujuan_satuan_id']);
        abort_if(strtoupper((string) $tujuan->kode) === 'ADMIN', 422, 'Laporan tidak dapat ditujukan ke Admin.');
        abort_if((int) $tujuan->id === (int) $satuanAsal->id, 422, 'Tujuan laporan tidak boleh sama dengan satuan pengirim.');

        $kodeTujuanDiizinkan = Satuan::kodeTujuanUntuk($satuanAsal->kode);
        if ($kodeTujuanDiizinkan !== null) {
            abort_unless(
                in_array(strtoupper((string) $tujuan->kode), $kodeTujuanDiizinkan, true),
                422,
                'Tujuan laporan tidak sesuai dengan alur pelaporan satuan Anda.'
            );
        }

        $permintaan = PermintaanLaporan::findOrFail($validated['permintaan_laporan_id']);
        abort_unless((int) $permintaan->tujuan_satuan_id === (int) $satuanAsal->id, 403, 'Permintaan laporan bukan untuk satuan Anda.');
        abort_unless((int) $permintaan->pembuat->satuan_id === (int) $tujuan->id, 422, 'Tujuan laporan tidak sesuai dengan permintaan laporan.');

        $progresValue = (int) $validated['progres'];
        $laporan = null;

        DB::transaction(function () use (&$laporan, &$permintaan, $progresValue, $validated, $satuanAsal, $user, $tujuan, $request) {
            $permintaan = PermintaanLaporan::whereKey($permintaan->id)->lockForUpdate()->first();
            abort_if($permintaan->laporan_id, 422, 'Permintaan laporan tersebut sudah memiliki laporan yang menunggu pemeriksaan atau sudah diputuskan.');
            abort_if($permintaan->status === PermintaanLaporan::STATUS_DIBATALKAN, 422, 'Permintaan laporan ini sudah dibatalkan oleh Pimpinan.');

            $permintaan->load('tasks');
            if ($permintaan->tasks->isNotEmpty()) {
                // Permintaan ini punya checklist task -- progres sudah
                // otomatis ter-update tiap task dicentang (lihat
                // PermintaanLaporanController::toggleTask), jadi dipakai
                // apa adanya di sini, bukan divalidasi "harus naik" ala
                // checkpoint manual lagi (checklist boleh naik-turun kalau
                // satuan koreksi centangan).
                $progresValue = $permintaan->hitungProgresDariTask();
            } else {
                // Progres yang sama cuma ditolak buat checkpoint biasa (harus
                // strictly naik). Kalau ini resubmit hasil Revisi, laporan
                // terakhirnya udah ditolak/diminta revisi -- progres permintaan
                // masih nyangkut di angka lama (biasanya 100%) karena memang
                // sengaja nggak direset pas ditolak, jadi di sini cukup nggak
                // boleh MENURUN dari itu, boleh sama.
                $progresMinimal = $permintaan->isSedangRevisi() ? $permintaan->progres : $permintaan->progres + 1;
                abort_if($progresValue < $progresMinimal, 422, $permintaan->isSedangRevisi()
                    ? 'Persentase progres tidak boleh lebih kecil dari progres terakhir ('.$permintaan->progres.'%).'
                    : 'Persentase progres harus lebih besar dari progres terakhir ('.$permintaan->progres.'%).');
            }

            $lampiranPath = $request->hasFile('lampiran')
                ? $request->file('lampiran')->store('lampiran-laporan', 'public')
                : null;

            $statusLaporan = $progresValue < 100 ? Laporan::STATUS_PROGRES : 'Menunggu';

            $laporan = Laporan::create([
                'satuan_id' => $satuanAsal->id,
                'user_id' => $user->id,
                'tujuan_satuan_id' => $tujuan->id,
                'permintaan_laporan_id' => $permintaan->id,
                'proyek' => $validated['proyek'] ?? null,
                'perihal' => $validated['perihal'],
                'deskripsi' => $validated['deskripsi'],
                'kendala' => $validated['kendala'] ?? null,
                'progres' => $progresValue,
                'prioritas' => $validated['prioritas'],
                'lampiran_path' => $lampiranPath,
                'status' => $statusLaporan,
            ]);

            $permintaan->progres = $progresValue;
            if ($progresValue >= 100) {
                $permintaan->laporan_id = $laporan->id;
                $permintaan->status = PermintaanLaporan::STATUS_PEMERIKSAAN;
                $permintaan->selesai_at = null;
            }
            $permintaan->save();
        });

        foreach (User::where('satuan_id', $tujuan->id)->get() as $penerima) {
            $penerima->notify(new LaporanBaruDiterima($laporan));
        }

        ActivityLog::catat('laporan.create', "Mengirim laporan \"{$laporan->perihal}\" ke {$tujuan->nama}.", $user, [
            'laporan_id' => $laporan->id,
            'tujuan_satuan' => $tujuan->nama,
            'prioritas' => $laporan->prioritas,
        ]);

        return back()->with('status', 'Laporan berhasil dikirim ke Pimpinan.');
    }

    // Setiap klik Update Progres untuk laporan yang masih berjalan membuat
    // CHECKPOINT BARU. Row checkpoint lama sengaja tidak diubah agar riwayat
    // progres 20 -> 40 -> 70 -> ... tetap permanen di database dan dapat
    // diterima DANPUS sebagai item realtime baru dalam satu perihal.
    public function updateProgres(Request $request, Laporan $laporan): RedirectResponse
    {
        $validated = $request->validate([
            'deskripsi' => ['required', 'string', 'max:10000'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'progres' => ['required', 'integer', 'min:0', 'max:100'],
            'kendala' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;
        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');
        abort_unless((int) $laporan->satuan_id === (int) $satuanAsal->id, 403, 'Anda tidak berhak mengedit laporan ini.');
        abort_unless($laporan->status === Laporan::STATUS_PROGRES, 422, 'Hanya checkpoint progres yang belum final yang dapat diperbarui.');

        $progresValue = (int) $validated['progres'];
        $laporanBaru = null;

        DB::transaction(function () use (&$laporanBaru, $laporan, $progresValue, $validated, $request, $user, $satuanAsal) {
            $sumber = Laporan::whereKey($laporan->id)->lockForUpdate()->first();
            abort_unless($sumber && $sumber->status === Laporan::STATUS_PROGRES, 422, 'Checkpoint ini sudah final dan tidak dapat diperbarui.');

            $permintaan = $sumber->permintaan_laporan_id
                ? PermintaanLaporan::whereKey($sumber->permintaan_laporan_id)->lockForUpdate()->first()
                : null;

            if ($permintaan) {
                abort_if($permintaan->status === PermintaanLaporan::STATUS_DIBATALKAN, 422, 'Permintaan laporan ini sudah dibatalkan oleh Pimpinan.');
                $permintaan->load('tasks');
                if ($permintaan->tasks->isNotEmpty()) {
                    // Sama seperti LaporanController::store() -- progres
                    // checklist otomatis, gak perlu validasi "gak boleh
                    // turun dari checkpoint terakhir" lagi.
                    $progresValue = $permintaan->hitungProgresDariTask();
                } else {
                    $progresTerakhir = (int) (Laporan::where('permintaan_laporan_id', $permintaan->id)->max('progres') ?? 0);
                    abort_if($progresValue < $progresTerakhir, 422, 'Persentase progres tidak boleh lebih kecil dari progres terakhir ('.$progresTerakhir.'%).');
                }
            }

            $lampiranPath = $sumber->lampiran_path;
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('lampiran-laporan', 'public');
            }

            $statusLaporan = ($permintaan && $progresValue < 100) ? Laporan::STATUS_PROGRES : 'Menunggu';

            $laporanBaru = Laporan::create([
                'satuan_id' => $sumber->satuan_id,
                'user_id' => $user->id,
                'tujuan_satuan_id' => $sumber->tujuan_satuan_id,
                'permintaan_laporan_id' => $sumber->permintaan_laporan_id,
                'proyek' => $sumber->proyek,
                'perihal' => $sumber->perihal,
                'deskripsi' => $validated['deskripsi'],
                'kendala' => $validated['kendala'] ?? null,
                'progres' => $progresValue,
                'prioritas' => $validated['prioritas'],
                'lampiran_path' => $lampiranPath,
                'status' => $statusLaporan,
            ]);

            if ($permintaan) {
                $permintaan->progres = max($progresValue, (int) $permintaan->progres);
                if ($progresValue >= 100) {
                    $permintaan->laporan_id = $laporanBaru->id;
                    $permintaan->status = PermintaanLaporan::STATUS_PEMERIKSAAN;
                    $permintaan->selesai_at = null;
                }
                $permintaan->save();
            }
        });

        ActivityLog::catat('laporan.update-progres', "Mengirim checkpoint progres baru laporan \"{$laporanBaru->perihal}\" sebesar {$laporanBaru->progres}%.", $user, [
            'laporan_id' => $laporanBaru->id,
            'progres' => $laporanBaru->progres,
            'permintaan_laporan_id' => $laporanBaru->permintaan_laporan_id,
        ]);

        return back()->with('status', 'Checkpoint progres '.$laporanBaru->progres.'% berhasil dikirim.');
    }

    public function updateStatus(Request $request, Laporan $laporan): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Diterima,Ditolak,Revisi,Disetujui,Disetujui DANPUS,Ditolak DANPUS,Revisi DANPUS,Disetujui WADAN,Ditolak WADAN,Revisi WADAN'],
            'catatan' => ['nullable', 'string', 'max:5000', 'required_if:status,Ditolak,Ditolak DANPUS,Ditolak WADAN'],
        ], [
            'catatan.required_if' => 'Catatan penolakan wajib diisi.',
        ]);

        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');

        // Danpus & Wadan itu satu payung pimpinan yang bisa saling gantiin --
        // laporan yang tujuannya ke Danpus tetap boleh diputuskan (Terima/
        // Tolak) oleh Wadan, begitu juga sebaliknya. Pola yang sama persis
        // sudah dipakai di destroy() (hapus dari Riwayat) lewat
        // $isPimpinanRiwayatSatlak, cuma di sini kelewatan belum diterapkan.
        $isPenerimaLaporan = (int) $laporan->tujuan_satuan_id === (int) $satuan->id;
        $isPimpinanRiwayatSatlak = in_array(strtoupper((string) $satuan->kode), ['DANPUS', 'WADAN'], true)
            && $laporan->satuan
            && $laporan->satuan->kategori === Satuan::KATEGORI_SATLAK;
        abort_unless($isPenerimaLaporan || $isPimpinanRiwayatSatlak, 403, 'Anda bukan penerima laporan ini.');
        abort_if($laporan->status === Laporan::STATUS_PROGRES, 422, 'Baris ini adalah catatan progres, bukan laporan final — tidak dapat diputuskan.');

        $kode = strtoupper((string) $satuan->kode);
        $aksi = strtolower((string) $validated['status']);

        $statusFinal = match ($kode) {
            'DANPUS' => str_contains($aksi, 'setuj') || str_contains($aksi, 'terima')
                ? 'Disetujui DANPUS'
                : (str_contains($aksi, 'tolak') ? 'Ditolak DANPUS' : 'Revisi DANPUS'),
            'WADAN' => str_contains($aksi, 'setuj') || str_contains($aksi, 'terima')
                ? 'Disetujui WADAN'
                : (str_contains($aksi, 'tolak') ? 'Ditolak WADAN' : 'Revisi WADAN'),
            default => str_contains($aksi, 'terima') || str_contains($aksi, 'setuj')
                ? 'Diterima'
                : (str_contains($aksi, 'tolak') ? 'Ditolak' : 'Revisi'),
        };

        $laporan->update([
            'status' => $statusFinal,
            'catatan' => str_contains(strtolower($statusFinal), 'tolak')
                ? $validated['catatan']
                : ($validated['catatan'] ?? null),
        ]);

        if ($laporan->permintaanLaporan) {
            $permintaan = $laporan->permintaanLaporan;
            $statusLower = strtolower($statusFinal);
            $isRevisi = str_contains($statusLower, 'revisi');
            $isSelesai = str_contains($statusLower, 'tolak') || str_contains($statusLower, 'setuj') || str_contains($statusLower, 'diterima');
            $permintaan->update([
                'laporan_id' => $isRevisi ? null : $permintaan->laporan_id,
                'status' => $isRevisi ? PermintaanLaporan::STATUS_DIKERJAKAN : ($isSelesai ? PermintaanLaporan::STATUS_SELESAI : PermintaanLaporan::STATUS_PEMERIKSAAN),
                'selesai_at' => $isSelesai ? now() : null,
            ]);
        }

        ActivityLog::catat('laporan.status', "Memperbarui status laporan \"{$laporan->perihal}\" menjadi {$laporan->status}.", $user, [
            'laporan_id' => $laporan->id,
            'status' => $laporan->status,
        ]);

        return back()->with('status', 'Status laporan berhasil diperbarui menjadi '.$laporan->status.'.');
    }

    public function destroy(Request $request, Laporan $laporan): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);

        $isPenerimaLaporan = (int) $laporan->tujuan_satuan_id === (int) $satuan->id;
        $isPimpinanRiwayatSatlak = in_array(strtoupper((string) $satuan->kode), ['DANPUS', 'WADAN'], true)
            && $laporan->satuan
            && $laporan->satuan->kategori === Satuan::KATEGORI_SATLAK;

        abort_unless($isPenerimaLaporan || $isPimpinanRiwayatSatlak, 403);
        abort_if($laporan->status === Laporan::STATUS_PROGRES, 422, 'Catatan progres tidak dapat dihapus.');

        if ($laporan->lampiran_path) {
            Storage::disk('public')->delete($laporan->lampiran_path);
        }
        $perihal = $laporan->perihal;
        $laporanId = $laporan->id;
        $laporan->delete();

        ActivityLog::catat('laporan.delete', "Menghapus laporan \"{$perihal}\" dari riwayat penerimaan.", $user, [
            'laporan_id' => $laporanId,
        ]);

        return back()->with('status', 'Laporan berhasil dihapus dari riwayat penerimaan.');
    }
}
