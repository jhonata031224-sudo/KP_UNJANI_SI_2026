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

        // Satu akun hanya boleh dipakai di satu device dalam satu waktu.
        // Sesi milik request/browser saat ini tidak dihitung sebagai device lain.
        $user = User::where('username', $credentials['username'])->first();

        if (config('session.driver') === 'database') {
            $batasAktif = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;

            $sesiMasihAktif = DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
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
