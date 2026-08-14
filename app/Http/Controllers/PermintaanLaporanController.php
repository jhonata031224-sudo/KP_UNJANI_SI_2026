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
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        $kode = strtoupper((string) $satuan?->kode);
        abort_unless(in_array($kode, self::PENGIRIM_KODE, true), 403);

        $latestId = (int) (PermintaanLaporan::where('tujuan_satuan_id', $satuan->id)->max('id') ?? 0);
        $since = max(0, (int) $request->query('since', 0));

        $items = PermintaanLaporan::with(['pembuat.satuan', 'laporans'])
            ->where('tujuan_satuan_id', $satuan->id)
            ->whereIn('status', [
                PermintaanLaporan::STATUS_BELUM,
                PermintaanLaporan::STATUS_DIKERJAKAN,
                PermintaanLaporan::STATUS_PEMERIKSAAN,
            ])
            ->where('id', '>', $since)
            ->orderBy('id')
            ->get();

        $notifications = $user->unreadNotifications
            ->take(20)
            ->map(function ($notification) {
                return [
                    'message' => $notification->data['pesan'] ?? 'Status laporan diperbarui.',
                    'time' => optional($notification->created_at)->diffForHumans(),
                    'id' => (string) $notification->id,
                ];
            })
            ->values();

        $laporanMasukCount = Laporan::where('tujuan_satuan_id', $satuan->id)
            ->where(function ($query) {
                $query->where('status', 'Menunggu')
                    ->orWhere('status', 'like', 'Revisi%');
            })
            ->count();

        return response()->json([
            'latest_id' => $latestId,
            'items_html' => view('siberad.dashboards.partials.permintaan-laporan-realtime-items', [
                'permintaanLaporan' => $items,
            ])->render(),
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
            'laporan_masuk_count' => $laporanMasukCount,
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->isPimpinan($request), 403);

        $validated = $request->validate([
            'tujuan_satuan_ids' => ['required', 'array', 'min:1'],
            'tujuan_satuan_ids.*' => ['integer', 'distinct', 'exists:satuans,id'],
            'perihal' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'instruksi' => ['nullable', 'string', 'max:5000'],
            'deadline_at' => ['required', 'date', 'after:now'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
        ]);

        $tujuan = Satuan::whereIn('id', $validated['tujuan_satuan_ids'])
            ->whereIn('kode', self::PENGIRIM_KODE)
            ->orderBy('urutan')
            ->get();

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

        return redirect()->route('permintaan-laporan.index')
            ->with('status', 'Permintaan laporan berhasil dikirim kepada '.$created->count().' satuan.');
    }

    public function mulai(Request $request, PermintaanLaporan $permintaanLaporan): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        abort_unless($user->satuan, 403);
        abort_unless((int) $permintaanLaporan->tujuan_satuan_id === (int) $user->satuan->id, 403);
        abort_unless(in_array(strtoupper((string) $user->satuan->kode), self::PENGIRIM_KODE, true), 403);
        abort_if($permintaanLaporan->laporan_id, 422, 'Permintaan ini sudah memiliki laporan.');

        $permintaanLaporan->update([
            'status' => PermintaanLaporan::STATUS_DIKERJAKAN,
            'dikerjakan_at' => $permintaanLaporan->dikerjakan_at ?? now(),
        ]);

        ActivityLog::catat('permintaan-laporan.mulai', "Menandai permintaan laporan \"{$permintaanLaporan->perihal}\" sedang dikerjakan.", $user, [
            'permintaan_laporan_id' => $permintaanLaporan->id,
        ]);

        return back()->with('status', 'Permintaan laporan ditandai sedang dikerjakan.');
    }
}
