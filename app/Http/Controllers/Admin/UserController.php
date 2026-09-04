<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Tambah akun pengguna baru — fitur "Manajemen Pengguna (CRUD User)".
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        ActivityLog::catat('user.create', "Membuat akun pengguna \"{$user->name}\" ({$user->username}).");

        return back()->with('status', "Akun \"{$user->name}\" berhasil ditambahkan.");
    }

    /**
     * Perbarui data akun pengguna. Password hanya diubah kalau field diisi.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validated($request, $user);

        $passwordDiubah = filled($validated['password'] ?? null);

        if ($passwordDiubah) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Catat field lain (di luar password) yang benar-benar berubah, agar
        // jejak Log Aktivitas selalu jelas -- termasuk kalau Admin mengganti
        // password pengguna lain, supaya tidak ada perubahan yang lolos tanpa
        // tercatat (transparansi/akuntabilitas aksi Admin).
        $fieldLain = collect($validated)->except('password')->filter(
            fn ($nilai, $field) => (string) $user->getAttribute($field) !== (string) $nilai
        )->keys();

        $user->update($validated);

        if ($passwordDiubah && $fieldLain->isEmpty()) {
            ActivityLog::catat('user.update_password', "Mengubah password akun pengguna \"{$user->name}\" ({$user->username}).");
        } elseif ($passwordDiubah) {
            ActivityLog::catat('user.update_password', "Memperbarui akun pengguna \"{$user->name}\" ({$user->username}), termasuk mengubah password.");
        } else {
            ActivityLog::catat('user.update', "Memperbarui akun pengguna \"{$user->name}\" ({$user->username}).");
        }

        return back()->with('status', "Akun \"{$user->name}\" berhasil diperbarui.".($passwordDiubah ? ' Password baru sudah aktif.' : ''));
    }

    /**
     * Hapus akun pengguna. Admin tidak bisa menghapus akunnya sendiri.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tidak bisa menghapus akun yang sedang digunakan.');
        }

        $nama = $user->name;
        $user->delete();

        ActivityLog::catat('user.delete', "Menghapus akun pengguna \"{$nama}\".");

        return back()->with('status', "Akun \"{$nama}\" berhasil dihapus.");
    }

    private function validated(Request $request, ?User $user = null): array
    {
        // Pesan Bahasa Indonesia -- proyek ini tidak punya file lang, jadi
        // tanpa ini pesan gagal validasi (mis. username/NRP kembar) muncul
        // dalam Bahasa Inggris bawaan Laravel. Ditampilkan inline di bawah
        // field yang salah di modal Tambah/Ubah Pengguna (bukan toast).
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'.($user ? ','.$user->id : '')],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'.($user ? ','.$user->id : '')],
            'satuan_id' => ['required', 'exists:satuans,id'],
            'password' => [$user ? 'nullable' : 'required', 'string'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username / NRP wajib diisi.',
            'username.unique' => 'Username / NRP ini sudah dipakai akun lain.',
            'username.max' => 'Username / NRP maksimal 50 karakter.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar di akun lain.',
            'satuan_id.required' => 'Satuan wajib dipilih.',
            'satuan_id.exists' => 'Satuan yang dipilih tidak valid.',
            'password.required' => 'Password awal wajib diisi.',
        ]);

        return $data;
    }
}
