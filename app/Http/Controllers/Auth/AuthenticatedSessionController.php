<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Proses login: username + password. Satuan sudah melekat pada akun
     * masing-masing pengguna (tidak lagi dipilih manual lewat dropdown).
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'captcha' => ['required', 'string'],
            // Akses lokasi (GPS) sekarang WAJIB untuk semua pengguna saat
            // login -- kalau browser menolak/tidak mengirim koordinat,
            // permintaan ini gagal validasi dan login ditolak sebelum
            // kredensial sempat dicek. Lihat ambilGpsSekali() di
            // welcome.blade.php untuk sisi klien yang mengumpulkan nilai ini.
            'gps_lat' => ['required', 'numeric', 'between:-90,90'],
            'gps_lon' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'gps_lat.required' => 'Anda harus mengizinkan akses lokasi untuk masuk ke sistem.',
            'gps_lon.required' => 'Anda harus mengizinkan akses lokasi untuk masuk ke sistem.',
            'gps_lat.numeric' => 'Data lokasi tidak valid. Coba muat ulang halaman dan izinkan akses lokasi.',
            'gps_lon.numeric' => 'Data lokasi tidak valid. Coba muat ulang halaman dan izinkan akses lokasi.',
        ]);

        $captchaBenar = hash_equals(
            (string) $request->session()->get('captcha_code'),
            $credentials['captcha']
        );
        $request->session()->forget('captcha_code');

        $kredensialBenar = Auth::validate([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ]);

        $errors = [];
        if (! $kredensialBenar) {
            $errors['username'] = 'NRP/Username atau password salah.';
        }
        if (! $captchaBenar) {
            $errors['captcha'] = 'Kode captcha salah.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $user = User::where('username', $credentials['username'])->first();

        if (config('session.driver') === 'database') {
            /*
             * Jangan blokir login karena session lama/orphan masih tersimpan.
             * Ini terutama terjadi ketika browser ditutup paksa, komputer mati,
             * deployment/restart terjadi, atau logout sebelumnya tidak sempat
             * menyelesaikan request.
             *
             * Setelah username + password benar, login baru mengambil alih akun.
             * Session lain milik user dihapus sehingga tidak ada lagi kondisi
             * palsu "Akun sedang digunakan di perangkat lain".
             */
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
        }

        Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'));

        $request->session()->regenerate();

        // Paksa simpan sesi SEKARANG (bukan menunggu akhir request seperti
        // biasanya) supaya baris `sessions` untuk ID sesi yang baru saja
        // di-regenerate benar-benar sudah ada di database sebelum baris
        // `login_at` di bawah ini di-update -- kalau tidak, UPDATE ini bisa
        // saja tidak menyentuh baris manapun (sesi belum ke-persist).
        $request->session()->save();

        DB::table('sessions')
            ->where('id', $request->session()->getId())
            ->update(['login_at' => now()]);

        // Titik lokasi sesi: GPS perangkat sekarang WAJIB (divalidasi di atas
        // sebelum baris ini tercapai), jadi selalu tersedia dan presisinya
        // jauh lebih baik daripada geo-IP. Fallback geo-IP lama
        // (simpanGeoSesi()) sengaja dipertahankan di bawah sebagai
        // referensi/jaring pengaman kalau suatu saat wajib-GPS dilonggarkan
        // lagi, tapi tidak lagi dipanggil di jalur normal ini.
        $this->simpanGeoSesiDariGps(
            $request->session()->getId(),
            (float) $credentials['gps_lat'],
            (float) $credentials['gps_lon']
        );

        ActivityLog::catat('login', 'Berhasil login ke '.Pengaturan::current()->namaSistem().'.', $request->user());

        $request->session()->flash('login_success', $request->user()->name);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Lookup geo dari IP dan simpan ke kolom geo_* di tabel sessions.
     *
     * Menggunakan ip-api.com (HTTP) — endpoint gratis tanpa API key,
     * rate-limit 45 req/menit per IP server (cukup untuk instansi).
     * IP loopback / private (127.x, 10.x, 192.168.x, ::1) dilewati
     * langsung tanpa hit API karena pasti tidak ada data kotanya.
     *
     * CATATAN: sejak izin lokasi (GPS) diwajibkan saat login, method ini
     * tidak lagi dipanggil di jalur normal (lihat store() di atas) karena
     * request tanpa GPS valid sudah ditolak lebih dulu oleh validasi.
     * Dibiarkan di sini sebagai referensi/jaring pengaman.
     */
    private function simpanGeoSesi(string $sesiId, string $ip): void
    {
        // IP private / loopback — tidak ada data geo yang bisa diambil
        if (
            $ip === '127.0.0.1' ||
            $ip === '::1' ||
            str_starts_with($ip, '10.') ||
            str_starts_with($ip, '192.168.') ||
            preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip)
        ) {
            DB::table('sessions')
                ->where('id', $sesiId)
                ->update([
                    'geo_kota'   => 'Jaringan Lokal',
                    'geo_region' => null,
                    'geo_negara' => null,
                    'geo_isp'    => null,
                    'geo_sumber' => 'lokal',
                ]);
            return;
        }

        try {
            $resp = Http::timeout(4)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,city,regionName,country,isp,lat,lon',
                'lang'   => 'id',
            ]);

            if ($resp->successful()) {
                $data = $resp->json();

                if (($data['status'] ?? '') === 'success') {
                    DB::table('sessions')
                        ->where('id', $sesiId)
                        ->update([
                            'geo_kota'   => $data['city']       ?? null,
                            'geo_region' => $data['regionName'] ?? null,
                            'geo_negara' => $data['country']    ?? null,
                            'geo_isp'    => $data['isp']        ?? null,
                            'geo_lat'    => isset($data['lat']) ? (float) $data['lat'] : null,
                            'geo_lon'    => isset($data['lon']) ? (float) $data['lon'] : null,
                            'geo_sumber' => 'ip',
                        ]);
                }
            }
        } catch (\Throwable) {
            // Timeout atau tidak bisa keluar ke internet — biarkan saja,
            // kolom geo tetap NULL dan UI menampilkan "–".
        }
    }

    /**
     * Simpan titik lokasi sesi langsung dari koordinat GPS perangkat
     * (dikirim browser lewat hidden input gps_lat/gps_lon).
     *
     * Koordinat GPS jauh lebih presisi daripada geo-IP, jadi disimpan
     * apa adanya sebagai geo_lat/geo_lon dengan geo_sumber = 'gps' --
     * ini yang dipakai duluan sebelum reverse-geocode nama kota di bawah
     * sempat selesai/gagal, supaya pin Google Maps tetap presisi walau
     * nama kota tidak ketemu.
     *
     * Nama kota/wilayah/negara diisi lewat reverse-geocode Nominatim
     * (OpenStreetMap) -- gratis, tanpa API key, cuma perlu header
     * User-Agent. Kalau reverse-geocode gagal (timeout/rate-limit),
     * kolom kota dibiarkan kosong tapi geo_sumber tetap 'gps' dan
     * koordinat presisinya tetap tersimpan.
     */
    private function simpanGeoSesiDariGps(string $sesiId, float $lat, float $lon): void
    {
        DB::table('sessions')
            ->where('id', $sesiId)
            ->update([
                'geo_lat'    => $lat,
                'geo_lon'    => $lon,
                'geo_sumber' => 'gps',
            ]);

        try {
            $resp = Http::timeout(4)
                ->withHeaders(['User-Agent' => 'SIBERAD-Pussiberad/1.0 (reverse-geocode login)'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format'          => 'jsonv2',
                    'lat'             => $lat,
                    'lon'             => $lon,
                    'accept-language' => 'id',
                ]);

            if ($resp->successful()) {
                $alamat = $resp->json('address') ?? [];

                DB::table('sessions')
                    ->where('id', $sesiId)
                    ->update([
                        'geo_kota'   => $alamat['city'] ?? $alamat['town'] ?? $alamat['regency'] ?? $alamat['village'] ?? null,
                        'geo_region' => $alamat['state'] ?? null,
                        'geo_negara' => $alamat['country'] ?? null,
                    ]);
            }
        } catch (\Throwable) {
            // Reverse-geocode gagal — nama kota kosong, tapi geo_lat/geo_lon
            // dari GPS tetap presisi untuk pin Google Maps.
        }
    }

    /**
     * Logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        ActivityLog::catat('logout', 'Logout dari '.Pengaturan::current()->namaSistem().'.', $request->user());

        // Hapus semua subscription push notifikasi milik user ini SEBELUM
        // sesi diakhiri. Kalau tidak, endpoint push di device/browser ini
        // tetap "nempel" ke akun yang baru logout selamanya -- dan kalau
        // device yang sama dipakai login user lain, notifikasi milik user
        // yang sudah logout tadi akan terus nyasar muncul di situ.
        //
        // Sengaja dihapus di sini (server-side, satu titik pasti dilewati
        // setiap logout) alih-alih hanya mengandalkan JS di
        // push-notification-controls.blade.php -- ada beberapa implementasi
        // tombol/dialog konfirmasi logout yang berbeda-beda di frontend,
        // jadi titik paling aman untuk jaminan konsistensi adalah di sini.
        $request->user()?->pushSubscriptions()->delete();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->session()->flash('logout_success', true);

        return redirect('/');
    }
}
