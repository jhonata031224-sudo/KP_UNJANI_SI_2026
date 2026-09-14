<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\PengumumanBroadcastAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotifikasiSettingController extends Controller
{
    /**
     * Fitur push notification WAJIB selalu aktif untuk seluruh pengguna --
     * dianggap krusial (mis. notifikasi kendala/laporan darurat) sehingga
     * tidak boleh dimatikan siapapun, termasuk Admin. Endpoint ini sengaja
     * DIPERTAHANKAN (bukan dihapus) supaya request lama/eksternal ke rute
     * ini tidak 404, tapi apapun yang dikirim akan selalu dipaksa menjadi
     * aktif -- efeknya toggle "mati" sudah tidak punya jalan lagi, baik
     * dari UI (lihat admin.blade.php, switch sekarang dikunci/disabled)
     * maupun dari request manual ke rute ini.
     */
    public function updateToggle(Request $request): RedirectResponse
    {
        $request->validate([
            'aktif' => ['sometimes', 'boolean'],
        ]);

        $pengaturan = Pengaturan::current();
        if (! $pengaturan->notifikasi_push_aktif) {
            $pengaturan->update(['notifikasi_push_aktif' => true]);
        }

        return back()->with(
            'status',
            'Fitur push notifikasi bersifat wajib aktif dan tidak bisa dimatikan.'
        );
    }

    /**
     * Kirim pengumuman manual ke penerima yang dipilih -- masuk ke lonceng
     * in-app penerima, dan ke notifikasi OS (push) utk yang sudah
     * mengizinkan & fitur push global sedang aktif.
     *
     * Tujuan pengumuman ada 3 pilihan (lihat "Tujuan Pengumuman" di
     * admin.blade.php):
     *  - 'semua'   : seluruh pengguna terdaftar (perilaku lama, default)
     *  - 'pimpinan': hanya user yang satuannya berkategori Pimpinan
     *                (Danpus & Wadan)
     *  - 'satuan'  : hanya user yang terdaftar pada satu satuan spesifik
     *                (satuan_id), mis. "Satlak Dak (Penindakan)"
     */
    public function broadcast(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:100'],
            'pesan' => ['required', 'string', 'max:500'],
            'kategori' => ['required', 'string', 'in:maintenance,keterangan'],
            'tujuan' => ['required', 'string', 'in:semua,pimpinan,satuan'],
            'satuan_id' => ['required_if:tujuan,satuan', 'nullable', 'integer', 'exists:satuans,id'],
        ], [
            'judul.required' => 'Judul pengumuman wajib diisi.',
            'pesan.required' => 'Isi pesan wajib diisi.',
            'judul.max' => 'Judul maksimal 100 karakter.',
            'pesan.max' => 'Isi pesan maksimal 500 karakter.',
            'kategori.required' => 'Kategori pengumuman wajib dipilih.',
            'kategori.in' => 'Kategori pengumuman tidak valid.',
            'tujuan.required' => 'Tujuan pengumuman wajib dipilih.',
            'tujuan.in' => 'Tujuan pengumuman tidak valid.',
            'satuan_id.required_if' => 'Pilih satuan tujuan terlebih dahulu.',
            'satuan_id.exists' => 'Satuan tujuan tidak ditemukan.',
        ]);

        [$penerima, $labelTujuan] = match ($validated['tujuan']) {
            'pimpinan' => [
                User::whereHas('satuan', fn ($q) => $q->where('kategori', Satuan::KATEGORI_PIMPINAN))->get(),
                'Pimpinan (Danpus & Wadan)',
            ],
            'satuan' => [
                User::where('satuan_id', $validated['satuan_id'])->get(),
                'satuan '.(Satuan::find($validated['satuan_id'])->nama ?? '-'),
            ],
            default => [User::all(), 'semua pengguna'],
        };

        if ($penerima->isEmpty()) {
            return back()->withErrors(['satuan_id' => 'Tidak ada pengguna terdaftar pada tujuan yang dipilih.'])->withInput();
        }

        NotificationFacade::send($penerima, new PengumumanBroadcastAdmin($validated['judul'], $validated['pesan'], $validated['kategori']));

        ActivityLog::catat(
            'setelan.notifikasi.broadcast',
            "Mengirim pengumuman \"{$validated['judul']}\" ke {$labelTujuan} ({$penerima->count()} pengguna).",
            null,
            ['judul' => $validated['judul'], 'pesan' => $validated['pesan'], 'kategori' => $validated['kategori'], 'tujuan' => $validated['tujuan'], 'satuan_id' => $validated['satuan_id'] ?? null, 'jumlah_penerima' => $penerima->count()]
        );

        return back()->with('status', "Pengumuman terkirim ke {$labelTujuan} ({$penerima->count()} pengguna).");
    }
}
