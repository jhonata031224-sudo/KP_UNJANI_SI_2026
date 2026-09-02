<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function verifyLandingAccess(Request $request)
    {
        if ($request->input('action') === 'revoke') {
            $request->session()->forget([
                'pengaturan_umum_terverifikasi',
                'pengaturan_umum_terverifikasi_at',
                'captcha_code',
            ]);
            return response()->json(['ok' => true, 'access' => false]);
        }

        $key = 'admin-landing-access:'.$request->user()->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => 'Terlalu banyak percobaan. Coba lagi dalam '.$seconds.' detik.',
            ], 429);
        }

        RateLimiter::increment($key, 60);

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'captcha' => ['required', 'string', 'size:5'],
        ], [
            'password.required' => 'Password wajib diisi.',
            'captcha.required' => 'Captcha wajib diisi.',
            'captcha.size' => 'Captcha harus terdiri dari 5 karakter.',
        ]);

        $captchaExpected = (string) $request->session()->pull('captcha_code', '');
        $captchaGiven = (string) $validated['captcha'];

        // Password akses Pengaturan Umum dapat dibuat terpisah dari password login Admin.
        // Jika belum dikonfigurasi, fallback ke password Admin menjaga instalasi lama tetap berfungsi.
        // trim() di sini mengantisipasi spasi/baris baru tak sengaja ikut
        // tersimpan saat isi value environment variable lewat panel hosting.
        $accessPassword = trim((string) env('PENGATURAN_UMUM_ACCESS_PASSWORD', ''));
        $passwordGiven = trim((string) $validated['password']);
        $passwordValid = $accessPassword !== ''
            ? hash_equals($accessPassword, $passwordGiven)
            : Hash::check($passwordGiven, (string) $request->user()->password);
        $captchaValid = $captchaExpected !== '' && hash_equals($captchaExpected, $captchaGiven);

        if (! $passwordValid || ! $captchaValid) {
            ActivityLog::catat('pengaturan.landing.access_denied', 'Percobaan membuka Pengaturan Umum ditolak karena password atau captcha tidak valid.');

            // Pesan dipisah per-field (bukan digabung generik) supaya Admin
            // tidak salah menebak mana yang sebenarnya keliru saat gagal.
            if (! $passwordValid && ! $captchaValid) {
                $pesan = 'Password dan captcha salah. Periksa ulang keduanya.';
            } elseif (! $passwordValid) {
                $pesan = $accessPassword !== ''
                    ? 'Password salah. Periksa lagi nilai PENGATURAN_UMUM_ACCESS_PASSWORD di environment variable server.'
                    : 'Password salah. Karena PENGATURAN_UMUM_ACCESS_PASSWORD belum diisi, sistem memakai password login Admin yang sedang aktif.';
            } else {
                $pesan = 'Kode captcha salah atau sudah kedaluwarsa. Captcha baru sudah dimuat ulang, coba masukkan lagi.';
            }

            return response()->json(['message' => $pesan], 422);
        }

        RateLimiter::clear($key);
        $request->session()->put('pengaturan_umum_terverifikasi', true);
        $request->session()->put('pengaturan_umum_terverifikasi_at', now()->timestamp);

        // CATATAN: sengaja TIDAK memanggil $request->session()->regenerate() di sini.
        // regenerate() otomatis me-regenerate token CSRF juga (lihat Illuminate\Session\Store::regenerate()),
        // sehingga token _token yang sudah ter-render di form #landingForm (dimuat sebelum modal
        // verifikasi ini dibuka) langsung basi walau form belum pernah di-refresh. Efeknya: submit
        // form landing SELALU gagal dengan 419 setelah verifikasi password+captcha berhasil.
        // Rate limiting + captcha di atas sudah cukup mencegah brute force, jadi regenerate ID sesi
        // di sini tidak diperlukan untuk keamanan.

        ActivityLog::catat('pengaturan.landing.access_granted', 'Konfirmasi akses Pengaturan Umum berhasil.');

        return response()->json([
            'ok' => true,
            'access' => true,
            // Dikirim balik agar sisi klien bisa sinkronkan token CSRF di halaman
            // (defense-in-depth kalau suatu saat sesi diregenerate oleh middleware lain).
            'csrf_token' => $request->session()->token(),
        ]);
    }

    public function updateLanding(Request $request): RedirectResponse
    {
        // Sengaja TIDAK ada batas waktu (TTL) di sini -- akses berlaku terus
        // selama sesi verifikasi belum dicabut (revoke), yaitu ketika Admin
        // pindah dari tab Pengaturan Umum ke tab lain (lihat JS di
        // admin-pengaturan-access.blade.php). Gerbang password + captcha di
        // awal sudah dianggap cukup, jadi tidak perlu re-verifikasi berkala.
        $verified = $request->session()->get('pengaturan_umum_terverifikasi', false);

        if (! $verified) {
            $request->session()->forget(['pengaturan_umum_terverifikasi', 'pengaturan_umum_terverifikasi_at']);
            return back()->with('error', 'Akses Pengaturan Umum belum diverifikasi. Masukkan password dan captcha terlebih dahulu.');
        }

        $validated = $request->validate([
            'hero_eyebrow'=>['nullable','string','max:255'],
            'hero_judul_awal'=>['nullable','string','max:50'],
            'hero_judul_aksen'=>['nullable','string','max:50'],
            'hero_subjudul'=>['nullable','string','max:255'],
            'hero_deskripsi'=>['nullable','string','max:2000'],
            'hero_image'=>['nullable','image','max:5120'],
            'logo_file'=>['nullable','image','max:5120'],
            'fitur'=>['required','array','size:4'],
            'fitur.*.judul'=>['required','string','max:100'],
            'fitur.*.deskripsi'=>['required','string','max:500'],
            'tentang_deskripsi'=>['nullable','string','max:4000'],
            'tentang_nama_resmi'=>['nullable','string','max:255'],
            'tentang_nama_lama'=>['nullable','string','max:255'],
            'tentang_fungsi_utama'=>['nullable','string','max:1000'],
            'tentang_moto_judul'=>['nullable','string','max:255'],
            'tentang_moto_deskripsi'=>['nullable','string','max:2000'],
            'alamat'=>['nullable','string','max:1000'],
            'email_kontak'=>['nullable','email','max:255'],
            'telepon_kontak'=>['nullable','string','max:30'],
            'website'=>['nullable','url','max:255'],
            'sosial_media'=>['required','array'],
            'sosial_media.*.platform'=>['required','string','max:30'],
            'sosial_media.*.label'=>['nullable','string','max:100'],
            'sosial_media.*.url'=>['nullable','url','max:500'],
            'landing_content'=>['nullable','string','max:30000'],
        ]);

        $pengaturan = Pengaturan::current();

        if ($request->hasFile('hero_image')) {
            if ($pengaturan->hero_image_path) Storage::disk('public')->delete($pengaturan->hero_image_path);
            $validated['hero_image_path'] = $request->file('hero_image')->store('pengaturan', 'public');
        }
        if ($request->hasFile('logo_file')) {
            if ($pengaturan->logo_path) Storage::disk('public')->delete($pengaturan->logo_path);
            $validated['logo_path'] = $request->file('logo_file')->store('pengaturan', 'public');
        }

        if (!empty($validated['landing_content'])) {
            $decoded = json_decode($validated['landing_content'], true);
            if (is_array($decoded)) $validated['landing_content'] = $decoded;
            else unset($validated['landing_content']);
        } else {
            unset($validated['landing_content']);
        }

        unset($validated['hero_image'], $validated['logo_file']);
        $pengaturan->update($validated);
        ActivityLog::catat('pengaturan.landing.update', 'Memperbarui seluruh konten halaman landing (branding, navigasi, beranda, fitur, tentang, kontak, footer).');

        return back()->with('status', 'Konten halaman landing berhasil disimpan.');
    }

    public function deleteLandingImage(Request $request, string $tipe): RedirectResponse
    {
        // Guard akses sama persis dengan updateLanding() -- tanpa TTL, cukup
        // sudah lolos verifikasi password + captcha di awal (lihat komentar
        // di updateLanding()).
        $verified = $request->session()->get('pengaturan_umum_terverifikasi', false);

        if (! $verified) {
            $request->session()->forget(['pengaturan_umum_terverifikasi', 'pengaturan_umum_terverifikasi_at']);
            return back()->with('error', 'Akses Pengaturan Umum belum diverifikasi. Masukkan password dan captcha terlebih dahulu.');
        }

        $kolom = $tipe === 'logo' ? 'logo_path' : 'hero_image_path';
        $label = $tipe === 'logo' ? 'Logo' : 'Gambar latar (BG) beranda';

        $pengaturan = Pengaturan::current();

        if ($pengaturan->{$kolom}) {
            Storage::disk('public')->delete($pengaturan->{$kolom});
            $pengaturan->update([$kolom => null]);
            ActivityLog::catat('pengaturan.landing.image_delete', $label.' landing page dihapus.');
        }

        return back()->with('status', $label.' berhasil dihapus.');
    }
}
