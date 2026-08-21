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
    private const ACCESS_TTL_SECONDS = 900;

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
        $passwordValid = Hash::check($validated['password'], (string) $request->user()->password);
        $captchaValid = $captchaExpected !== '' && hash_equals($captchaExpected, $captchaGiven);

        if (! $passwordValid || ! $captchaValid) {
            ActivityLog::catat('pengaturan.landing.access_denied', 'Percobaan membuka Pengaturan Umum ditolak karena password atau captcha tidak valid.');
            return response()->json([
                'message' => 'Konfirmasi akses gagal. Periksa password dan captcha lalu coba lagi.',
            ], 422);
        }

        RateLimiter::clear($key);
        $request->session()->put('pengaturan_umum_terverifikasi', true);
        $request->session()->put('pengaturan_umum_terverifikasi_at', now()->timestamp);
        $request->session()->regenerate();

        ActivityLog::catat('pengaturan.landing.access_granted', 'Konfirmasi akses Pengaturan Umum berhasil.');

        return response()->json(['ok' => true, 'access' => true]);
    }

    public function updateLanding(Request $request): RedirectResponse
    {
        $verified = $request->session()->get('pengaturan_umum_terverifikasi', false);
        $verifiedAt = (int) $request->session()->get('pengaturan_umum_terverifikasi_at', 0);

        if (! $verified || $verifiedAt <= 0 || (now()->timestamp - $verifiedAt) > self::ACCESS_TTL_SECONDS) {
            $request->session()->forget(['pengaturan_umum_terverifikasi', 'pengaturan_umum_terverifikasi_at']);
            return back()->with('error', 'Akses Pengaturan Umum sudah berakhir. Masukkan password dan captcha terlebih dahulu.');
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
}
