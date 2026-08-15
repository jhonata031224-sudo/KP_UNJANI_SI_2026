<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        ]);

        // Dibandingkan case-sensitive — captcha memang campur huruf besar/kecil
        // sebagai bagian dari kode yang harus diketik persis.
        $captchaBenar = hash_equals((string) $request->session()->get('captcha_code'), $credentials['captcha']);
        $request->session()->forget('captcha_code');

        // Kredensial dicek pakai validate() (bukan attempt()) supaya tidak
        // langsung login kalau ternyata captcha-nya salah. Dua-duanya dicek
        // independen supaya kalau kredensial DAN captcha sama-sama salah,
        // pesan errornya muncul untuk keduanya — bukan cuma yang tercek duluan.
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

        // Satu akun (role apa pun -- bukan cuma Admin) cuma boleh dipakai di
        // satu device dalam satu waktu. Baris sesi lama (driver database)
        // baru dihapus otomatis kalau sudah lewat SESSION_LIFETIME (bukan
        // seketika pas browser ditutup/logout lupa diklik), jadi itu juga
        // yang jadi patokan "masih aktif" di sini -- biar konsisten sama apa
        // yang ditampilkan di tabel Sesi Login Aktif punya Admin. Dijaga
        // dengan cek driver supaya tidak query tabel `sessions` kalau
        // SESSION_DRIVER bukan database (mis. file/redis).
        $user = User::where('username', $credentials['username'])->first();

        if (config('session.driver') === 'database') {
            $batasAktif = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;

            $sesiMasihAktif = DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('last_activity', '>=', $batasAktif)
                ->exists();

            if ($sesiMasihAktif) {
                throw ValidationException::withMessages([
                    'username' => 'Akun ini sedang digunakan di perangkat lain. Logout dari perangkat itu dulu, atau hubungi Admin untuk paksa logout.',
                ]);
            }
        }

        Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'));

        $request->session()->regenerate();

        ActivityLog::catat('login', 'Berhasil login ke SIBERAD.', $request->user());

        $request->session()->flash('login_success', $request->user()->name);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        ActivityLog::catat('logout', 'Logout dari SIBERAD.', $request->user());

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->session()->flash('logout_success', true);

        return redirect('/');
    }
}
