<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PermintaanLaporan;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\PermintaanLaporanBaru;
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

        // Tetap memakai shell dashboard dan sidebar Pelaporan yang sama,
        // tetapi Permintaan Laporan dibuka sebagai halaman/tab mandiri.
        return redirect()->to(route('dashboard').'?tab=permintaan-laporan');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->isPimpinan($request), 403);

        $validated = $request->validate([
            'tujuan_satuan_ids' => ['required', 'array', 'min:1'],
            'tujuan_satuan_ids.*' => ['integer', 'distinct', 'exists:satuans,id'],
            'perihal' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'max:255'],
            'instruksi' => ['required', 'string', 'max:5000'],
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

                // Setiap pengguna pada satuan tujuan mendapat notifikasi database.
                foreach (User::where('satuan_id', $satuan->id)->get() as $penerima) {
                    $penerima->notify(new PermintaanLaporanBaru($permintaan));
                }
            }
        });

        ActivityLog::catat('permintaan-laporan.create', "Mengirim permintaan laporan \"{$validated['perihal']}\" kepada ".$tujuan->pluck('nama')->join(', ', ' dan ').".", $user, [
            'permintaan_laporan_ids' => $created->pluck('id')->all(),
            'satuan_tujuan' => $tujuan->pluck('nama')->all(),
        ]);

        // Redirect langsung ke dashboard (bukan lewat route index yang cuma
        // bakal redirect lagi) -- flash session('status') cuma bertahan SATU
        // hop redirect, jadi kalau lewat index dulu pesannya keburu hilang
        // sebelum sempat nyampe ke halaman yang nampilin toast-nya.
        return redirect()->to(route('dashboard').'?tab=permintaan-laporan')
            ->with('status', 'Permintaan telah terkirim ke Satuan');
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
