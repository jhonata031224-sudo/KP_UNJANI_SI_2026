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
use Illuminate\Support\Facades\Storage;

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

    /**
     * Admin mengunggah SATU file suara (mp3/wav/ogg) yang lalu diputar
     * otomatis di navbar SEMUA dashboard (Admin, Danpus/Wadan, dan
     * seluruh role Satuan) setiap kali ada notifikasi baru masuk ke
     * lonceng in-app -- lihat partials/notification-controls.blade.php,
     * fungsi poll() yang membandingkan id notifikasi baru vs yang sudah
     * pernah dilihat sebelum memutar audio ini.
     */
    public function updateSuara(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notifikasi_suara' => ['required', 'file', 'mimes:mp3,wav,ogg,mpga', 'max:2048'],
        ], [
            'notifikasi_suara.required' => 'Pilih file suara terlebih dahulu.',
            'notifikasi_suara.mimes' => 'File yang diunggah harus berformat MP3, WAV, atau OGG.',
            'notifikasi_suara.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        $pengaturan = Pengaturan::current();

        // Sama seperti bug logo/struktur-organisasi yang sudah diperbaiki
        // di tempat lain (lihat StrukturOrganisasiController::update()):
        // disk 'public' disetel throw=false, jadi kalau penulisan file
        // gagal di level filesystem, store() tetap "sukses" tanpa
        // exception. Verifikasi manual di sini supaya path yang tidak
        // benar-benar ada di disk tidak ikut disimpan ke kolom.
        $path = $request->file('notifikasi_suara')->store('notifikasi-suara', 'public');

        if (! $path || ! Storage::disk('public')->exists($path) || Storage::disk('public')->size($path) < 1) {
            if ($path) Storage::disk('public')->delete($path);

            return back()->with('error',
                'File suara notifikasi GAGAL disimpan ke server (storage tidak bisa ditulis). '
                .'Coba upload ulang; kalau gagal terus, cek log server / kapasitas volume Railway.'
            );
        }

        if ($pengaturan->notifikasi_sound_path) {
            Storage::disk('public')->delete($pengaturan->notifikasi_sound_path);
        }

        $pengaturan->update(['notifikasi_sound_path' => $path]);

        ActivityLog::catat('setelan.notifikasi.suara.update', 'Memperbarui suara notifikasi.');

        return back()->with('status', 'Suara notifikasi berhasil disimpan. Akan berbunyi di semua dashboard saat ada notifikasi baru.');
    }

    public function destroySuara(Request $request): RedirectResponse
    {
        $pengaturan = Pengaturan::current();

        if ($pengaturan->notifikasi_sound_path) {
            Storage::disk('public')->delete($pengaturan->notifikasi_sound_path);
            $pengaturan->update(['notifikasi_sound_path' => null]);
            ActivityLog::catat('setelan.notifikasi.suara.destroy', 'Menghapus suara notifikasi.');
        }

        return back()->with('status', 'Suara notifikasi berhasil dihapus. Notifikasi baru tidak akan berbunyi lagi.');
    }
}
