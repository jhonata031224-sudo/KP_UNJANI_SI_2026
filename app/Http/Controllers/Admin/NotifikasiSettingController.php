<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use App\Models\User;
use App\Notifications\PengumumanBroadcastAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Storage;

class NotifikasiSettingController extends Controller
{
    /**
     * Saklar GLOBAL push notification (notifikasi OS yang muncul di luar
     * tab/sistem) untuk SELURUH pengguna -- diatur Admin dari menu Setelan ->
     * Notifikasi. Efek saat dimatikan (sudah ditegakkan di server, bukan
     * cuma tampilan):
     *  - WebPushChannel::send() tidak mengirim push apapun ke siapapun,
     *    meski subscription-nya masih tersimpan;
     *  - InjectWebPushUi tidak menyuntik skrip/menu push ke halaman manapun.
     * Lonceng notifikasi in-app, popup, dan banner Mode Maintenance TIDAK
     * ikut mati -- hanya jalur push OS-nya. Saat dinyalakan lagi, semua
     * subscription yang sudah ada langsung berfungsi kembali.
     */
    public function updateToggle(Request $request): RedirectResponse
    {
        $request->validate([
            'aktif' => ['sometimes', 'boolean'],
        ]);

        $aktif = $request->boolean('aktif');

        $pengaturan = Pengaturan::current();
        $pengaturan->update(['notifikasi_push_aktif' => $aktif]);

        ActivityLog::catat(
            'setelan.notifikasi.toggle',
            $aktif
                ? 'Mengaktifkan push notification untuk seluruh pengguna.'
                : 'Menonaktifkan push notification untuk seluruh pengguna.',
            null,
            ['notifikasi_push_aktif' => $aktif]
        );

        return back()->with(
            'status',
            $aktif
                ? 'Push notification diaktifkan untuk seluruh pengguna.'
                : 'Push notification dinonaktifkan untuk seluruh pengguna. Lonceng notifikasi di dalam sistem tetap berjalan.'
        );
    }

    /**
     * Kirim pengumuman manual ke penerima yang dipilih -- masuk ke lonceng
     * in-app penerima, dan ke notifikasi OS (push) utk yang sudah
     * mengizinkan & fitur push global sedang aktif.
     *
     * Tujuan SELALU semua pengguna (kategori selalu 'keterangan').
     */
    public function broadcast(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:100'],
            'pesan' => ['required', 'string', 'max:500'],
        ], [
            'judul.required' => 'Judul pengumuman wajib diisi.',
            'pesan.required' => 'Isi pesan wajib diisi.',
            'judul.max' => 'Judul maksimal 100 karakter.',
            'pesan.max' => 'Isi pesan maksimal 500 karakter.',
        ]);

        // Pengumuman manual SELALU berkategori 'keterangan'. Kategori
        // 'maintenance' sengaja tidak bisa dipilih dari sini -- notifikasi
        // pemeliharaan hanya dikirim oleh saklar Mode Maintenance
        // (MaintenanceSettingController), supaya tidak dobel/bentrok.
        // Nilai 'kategori' dari request (mis. form lama) diabaikan.
        $validated['kategori'] = 'keterangan';

        // Pengumuman manual SELALU ke SEMUA pengguna (pilihan tujuan
        // Pimpinan/Satuan Tertentu dihapus). Penargetan per-satuan hanya
        // ada di Mode Maintenance (MaintenanceSettingController).
        $penerima = User::all();
        $labelTujuan = 'semua pengguna';

        if ($penerima->isEmpty()) {
            return back()->withErrors(['judul' => 'Belum ada pengguna terdaftar.'])->withInput();
        }

        NotificationFacade::send($penerima, new PengumumanBroadcastAdmin($validated['judul'], $validated['pesan'], $validated['kategori']));

        ActivityLog::catat(
            'setelan.notifikasi.broadcast',
            "Mengirim pengumuman \"{$validated['judul']}\" ke {$labelTujuan} ({$penerima->count()} pengguna).",
            null,
            ['judul' => $validated['judul'], 'pesan' => $validated['pesan'], 'kategori' => $validated['kategori'], 'tujuan' => 'semua', 'jumlah_penerima' => $penerima->count()]
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
