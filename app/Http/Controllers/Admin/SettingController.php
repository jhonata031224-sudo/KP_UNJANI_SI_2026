<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    private const ACCESS_TTL_SECONDS = 900;

    /**
     * Daftar platform sosial media yang boleh dipilih saat menambah entri baru
     * lewat popup edit landing. Dipakai untuk validasi `in:`.
     */
    private const SOSIAL_PLATFORMS = ['instagram', 'tiktok', 'youtube', 'x', 'facebook', 'wikipedia'];

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
        $request->session()->regenerate();

        ActivityLog::catat('pengaturan.landing.access_granted', 'Konfirmasi akses Pengaturan Umum berhasil.');

        return response()->json(['ok' => true, 'access' => true]);
    }

    /**
     * Memastikan sesi verifikasi password+captcha Pengaturan Umum masih berlaku.
     * Dipanggil di setiap endpoint yang mengubah konten landing.
     */
    private function ensureLandingAccessVerified(Request $request): void
    {
        $verified = $request->session()->get('pengaturan_umum_terverifikasi', false);
        $verifiedAt = (int) $request->session()->get('pengaturan_umum_terverifikasi_at', 0);

        if (! $verified || $verifiedAt <= 0 || (now()->timestamp - $verifiedAt) > self::ACCESS_TTL_SECONDS) {
            $request->session()->forget(['pengaturan_umum_terverifikasi', 'pengaturan_umum_terverifikasi_at']);

            abort(response()->json([
                'message' => 'Sesi verifikasi Pengaturan Umum sudah berakhir. Muat ulang halaman lalu masukkan password dan captcha lagi.',
                'session_expired' => true,
            ], 440));
        }
    }

    /**
     * Endpoint tunggal untuk popup "klik elemen -> edit" di landing page.
     * Menerima satu bagian konten setiap kali submit (bukan seluruh form
     * sekaligus), lalu hanya menyimpan bagian itu dan mengembalikan nilai
     * final dalam JSON supaya pratinjau bisa diperbarui tanpa reload.
     */
    public function updateLandingField(Request $request): JsonResponse
    {
        $this->ensureLandingAccessVerified($request);

        $field = (string) $request->input('field');
        $pengaturan = Pengaturan::current();

        $result = match ($field) {
            'logo' => $this->saveLogo($request, $pengaturan),
            'brand' => $this->saveBrand($request, $pengaturan),
            'hero_eyebrow' => $this->saveSimpleFields($request, $pengaturan, [
                'hero_eyebrow' => ['nullable', 'string', 'max:255'],
            ]),
            'hero_judul' => $this->saveSimpleFields($request, $pengaturan, [
                'hero_judul_awal' => ['nullable', 'string', 'max:50'],
                'hero_judul_aksen' => ['nullable', 'string', 'max:50'],
            ]),
            'hero_subjudul' => $this->saveSimpleFields($request, $pengaturan, [
                'hero_subjudul' => ['nullable', 'string', 'max:255'],
            ]),
            'hero_deskripsi' => $this->saveSimpleFields($request, $pengaturan, [
                'hero_deskripsi' => ['nullable', 'string', 'max:2000'],
            ]),
            'hero_image' => $this->saveHeroImage($request, $pengaturan),
            'fitur' => $this->saveFitur($request, $pengaturan),
            'tentang_deskripsi' => $this->saveSimpleFields($request, $pengaturan, [
                'tentang_deskripsi' => ['nullable', 'string', 'max:4000'],
            ]),
            'tentang_moto' => $this->saveSimpleFields($request, $pengaturan, [
                'tentang_moto_judul' => ['nullable', 'string', 'max:255'],
                'tentang_moto_deskripsi' => ['nullable', 'string', 'max:2000'],
            ]),
            'alamat' => $this->saveSimpleFields($request, $pengaturan, [
                'alamat' => ['nullable', 'string', 'max:1000'],
            ]),
            'email_kontak' => $this->saveSimpleFields($request, $pengaturan, [
                'email_kontak' => ['nullable', 'email', 'max:255'],
            ]),
            'telepon_kontak' => $this->saveSimpleFields($request, $pengaturan, [
                'telepon_kontak' => ['nullable', 'string', 'max:30'],
            ]),
            'website' => $this->saveSimpleFields($request, $pengaturan, [
                'website' => ['nullable', 'url', 'max:255'],
            ]),
            'sosial_media' => $this->saveSosialMedia($request, $pengaturan),
            default => null,
        };

        if ($result === null) {
            return response()->json(['message' => 'Bagian konten tidak dikenali.'], 422);
        }

        ActivityLog::catat('pengaturan.landing.update', 'Memperbarui bagian "'.$field.'" pada konten halaman landing.');

        return response()->json(array_merge(['ok' => true, 'message' => 'Tersimpan.'], $result));
    }

    private function saveSimpleFields(Request $request, Pengaturan $pengaturan, array $rules): array
    {
        $validated = $request->validate($rules);
        $pengaturan->update($validated);

        return $validated;
    }

    private function saveLogo(Request $request, Pengaturan $pengaturan): array
    {
        $request->validate(['logo_file' => ['required', 'image', 'max:5120']]);

        if ($pengaturan->logo_path) {
            Storage::disk('public')->delete($pengaturan->logo_path);
        }

        $path = $request->file('logo_file')->store('pengaturan', 'public');
        $pengaturan->update(['logo_path' => $path]);

        return ['logo_url' => asset('storage/'.$path)];
    }

    private function saveHeroImage(Request $request, Pengaturan $pengaturan): array
    {
        $request->validate(['hero_image' => ['required', 'image', 'max:5120']]);

        if ($pengaturan->hero_image_path) {
            Storage::disk('public')->delete($pengaturan->hero_image_path);
        }

        $path = $request->file('hero_image')->store('pengaturan', 'public');
        $pengaturan->update(['hero_image_path' => $path]);

        return ['hero_image_url' => asset('storage/'.$path)];
    }

    private function saveBrand(Request $request, Pengaturan $pengaturan): array
    {
        $validated = $request->validate([
            'brand_name' => ['nullable', 'string', 'max:50'],
            'brand_accent' => ['nullable', 'string', 'max:50'],
            'brand_tagline' => ['nullable', 'string', 'max:100'],
        ]);

        $content = $pengaturan->landing_content ?? [];
        $content['brand'] = array_merge($content['brand'] ?? [], [
            'name' => $validated['brand_name'] ?? '',
            'accent' => $validated['brand_accent'] ?? '',
            'tagline' => $validated['brand_tagline'] ?? '',
        ]);
        $pengaturan->update(['landing_content' => $content]);

        return ['brand' => $content['brand']];
    }

    private function saveFitur(Request $request, Pengaturan $pengaturan): array
    {
        $validated = $request->validate([
            'index' => ['required', 'integer', 'min:0', 'max:3'],
            'judul' => ['required', 'string', 'max:100'],
            'deskripsi' => ['required', 'string', 'max:500'],
        ]);

        $fitur = $pengaturan->fitur ?? [];
        $fitur[$validated['index']] = [
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
        ];
        ksort($fitur);
        $pengaturan->update(['fitur' => array_values($fitur)]);

        return ['index' => $validated['index'], 'judul' => $validated['judul'], 'deskripsi' => $validated['deskripsi']];
    }

    private function saveSosialMedia(Request $request, Pengaturan $pengaturan): array
    {
        $action = (string) $request->input('action', 'save');
        $sosial = $pengaturan->sosial_media ?? [];

        if ($action === 'delete') {
            $validated = $request->validate(['index' => ['required', 'integer', 'min:0']]);
            unset($sosial[$validated['index']]);
            $sosial = array_values($sosial);
            $pengaturan->update(['sosial_media' => $sosial]);

            return ['deleted' => true, 'sosial_media' => $sosial];
        }

        $validated = $request->validate([
            'index' => ['nullable', 'integer', 'min:0'],
            'platform' => ['required', 'string', 'in:'.implode(',', self::SOSIAL_PLATFORMS)],
            'label' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'url', 'max:500'],
        ]);

        $entry = [
            'platform' => $validated['platform'],
            'label' => $validated['label'] ?? '',
            'url' => $validated['url'] ?? '',
        ];

        if (array_key_exists('index', $validated) && $validated['index'] !== null && isset($sosial[$validated['index']])) {
            $sosial[$validated['index']] = $entry;
        } else {
            $sosial[] = $entry;
        }

        $sosial = array_values($sosial);
        $pengaturan->update(['sosial_media' => $sosial]);

        return ['sosial_media' => $sosial];
    }
}
