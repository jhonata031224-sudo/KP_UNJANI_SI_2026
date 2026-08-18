<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
