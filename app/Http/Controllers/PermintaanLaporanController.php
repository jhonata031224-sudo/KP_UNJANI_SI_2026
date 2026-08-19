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
    private const PENGIRIM_KODE = [
        'SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK', 'SATLAKDUKTEK',
        'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT',
    ];

    private function isPimpinan(Request $request): bool
    {
        $kode = strtoupper((string) $request->user()->load('satuan')->satuan?->kode);
        return in_array($kode, ['DANPUS', 'WADAN'], true);
    }

    private function isPengirim(Request $request): bool
    {
        $kode = strtoupper((string) $request->user()->load('satuan')->satuan?->kode);
        return in_array($kode, self::PENGIRIM_KODE, true);
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
            $items = PermintaanLaporan::with(['pembuat.satuan', 'tujuanSatuan', 'laporan'])
                ->whereNotNull('archived_at')
                ->whereHas('pembuat.satuan', fn ($q) => $q->whereIn('kode', ['DANPUS', 'WADAN']))
                ->latest('archived_at')
                ->get()
                ->map(fn (PermintaanLaporan $item) => $this->arsipItemData($item))
                ->values();

            return response()->json([
                'items' => $items,
            ], 200, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        }

        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        $kode = strtoupper((string) $satuan?->kode);
        abort_unless(in_array($kode, self::PENGIRIM_KODE, true), 403);

        $latestId = (int) (PermintaanLaporan::where('tujuan_satuan_id', $satuan->id)->max('id') ?? 0);
        $since = max(0, (int) $request->query('since', 0));

        $items = PermintaanLaporan::with(['pembuat.satuan', 'laporans'])
            ->where('tujuan_satuan_id', $satuan->id)
            ->whereNull('archived_at')
            ->whereIn('status', [
                PermintaanLaporan::STATUS_BELUM,
                PermintaanLaporan::STATUS_DIKERJAKAN,
                PermintaanLaporan::STATUS_PEMERIKSAAN,
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

        return response()->json([
            'latest_id' => $latestId,
            'items_html' => view('siberad.dashboards.partials.permintaan-laporan-realtime-items', [
                'permintaanLaporan' => $items,
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
        ]);

        $prioritasKategori = Satuan::prioritasKategori();
        $tujuan = Satuan::whereIn('id', $validated['tujuan_satuan_ids'])
            ->whereIn('kode', self::PENGIRIM_KODE)
            ->get()
            ->sortBy(fn ($s) => sprintf('%d-%s', $prioritasKategori[$s->kategori] ?? 9, $s->nama))
            ->values();

        abort_if($tujuan->count() !== count($validated['tujuan_satuan_ids']), 422, 'Permintaan hanya dapat ditujukan kepada 8 satuan pengirim yang tersedia.');

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

        $items = PermintaanLaporan::with(['pembuat.satuan', 'tujuanSatuan', 'laporan'])
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

        return response()->json([
            'message' => $items->count().' permintaan laporan berhasil dipindahkan ke Riwayat Laporan.',
            'archived_ids' => $items->pluck('id')->values(),
            'items' => $items->map(fn (PermintaanLaporan $item) => $this->arsipItemData($item))->values(),
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
                    ? 'Selesai · Ditolak'
                    : (str_contains($finalStatus, 'setuj') || str_contains($finalStatus, 'diterima')
                        ? 'Selesai · Disetujui'
                        : $item->status)));

        return [
            'id' => $item->id,
            'tujuan' => $item->tujuanSatuan?->kode ?: ($item->tujuanSatuan?->nama ?: '-'),
            'tujuan_nama' => $item->tujuanSatuan?->nama ?: '-',
            'perihal' => $item->perihal,
            'kategori' => $item->kategori ?: '-',
            'prioritas' => $item->prioritas ?: '-',
            'deadline' => $item->deadline_at?->translatedFormat('d M Y, H:i') ?: '-',
            'status' => $statusLabel,
            'archived_at' => $item->archived_at?->translatedFormat('d M Y, H:i') ?: now()->translatedFormat('d M Y, H:i'),
        ];
    }

    public function mulai(Request $request, PermintaanLaporan $permintaanLaporan): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        abort_unless($user->satuan, 403);
        abort_unless((int) $permintaanLaporan->tujuan_satuan_id === (int) $user->satuan->id, 403);
        abort_unless(in_array(strtoupper((string) $user->satuan->kode), self::PENGIRIM_KODE, true), 403);
        abort_if($permintaanLaporan->laporan_id, 422, 'Permintaan ini sudah memiliki laporan.');
        abort_if($permintaanLaporan->status === PermintaanLaporan::STATUS_DIBATALKAN, 422, 'Permintaan ini sudah dibatalkan oleh Pimpinan.');
        abort_if($permintaanLaporan->archived_at, 422, 'Permintaan ini sudah masuk Riwayat Laporan.');

        $permintaanLaporan->update([
            'status' => PermintaanLaporan::STATUS_DIKERJAKAN,
            'dikerjakan_at' => $permintaanLaporan->dikerjakan_at ?? now(),
        ]);

        ActivityLog::catat('permintaan-laporan.mulai', "Menandai permintaan laporan \"{$permintaanLaporan->perihal}\" sedang dikerjakan.", $user, [
            'permintaan_laporan_id' => $permintaanLaporan->id,
        ]);

        return back()->with('status', 'Permintaan laporan ditandai sedang dikerjakan.');
    }

    public function batal(Request $request, PermintaanLaporan $permintaanLaporan): RedirectResponse
    {
        abort_unless($this->isPimpinan($request), 403);
        abort_unless($permintaanLaporan->isDapatDibatalkan(), 422, 'Permintaan ini sudah tidak dapat dibatalkan.');
        abort_if($permintaanLaporan->archived_at, 422, 'Permintaan ini sudah masuk Riwayat Laporan.');

        $permintaanLaporan->update([
            'status' => PermintaanLaporan::STATUS_DIBATALKAN,
            'dibatalkan_at' => now(),
        ]);

        ActivityLog::catat('permintaan-laporan.batal', "Membatalkan permintaan laporan \"{$permintaanLaporan->perihal}\" kepada {$permintaanLaporan->tujuanSatuan->nama}.", $request->user(), [
            'permintaan_laporan_id' => $permintaanLaporan->id,
        ]);

        return back()->with('status', 'Permintaan laporan telah dibatalkan.');
    }

    public function editDeadline(Request $request, PermintaanLaporan $permintaanLaporan): RedirectResponse
    {
        abort_unless($this->isPimpinan($request), 403);
        $permintaanLaporan->loadMissing('laporan');
        abort_unless($permintaanLaporan->isDapatEditDeadline(), 422, $permintaanLaporan->alasanTidakBisaEditDeadline());
        abort_if($permintaanLaporan->archived_at, 422, 'Permintaan ini sudah masuk Riwayat Laporan.');

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

        return back()->with('status', 'Deadline permintaan laporan berhasil diperbarui.');
    }
}
