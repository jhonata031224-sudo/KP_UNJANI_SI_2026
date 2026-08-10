<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Satuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if (! $captchaBenar) {
            throw ValidationException::withMessages([
                'captcha' => 'Kode captcha salah.',
            ]);
        }

        if (! Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'username' => 'NRP/Username atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        ActivityLog::catat('login', 'Berhasil login ke SIBERAD.', $request->user());

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

        return redirect('/');
    }
}