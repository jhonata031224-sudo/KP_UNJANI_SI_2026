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

        if (filled($validated['password'] ?? null)) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        ActivityLog::catat('user.update', "Memperbarui akun pengguna \"{$user->name}\" ({$user->username}).");

        return back()->with('status', "Akun \"{$user->name}\" berhasil diperbarui.");
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'.($user ? ','.$user->id : '')],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'.($user ? ','.$user->id : '')],
            'satuan_id' => ['required', 'exists:satuans,id'],
            'password' => [$user ? 'nullable' : 'required', 'string'],
        ]);

        return $data;
    }
}
