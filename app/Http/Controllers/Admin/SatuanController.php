<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Satuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    /**
     * Tambah satuan/Satlak baru — fitur "Manajemen Satlak".
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $satuan = Satuan::create($validated);

        ActivityLog::catat('satuan.create', "Menambahkan satuan \"{$satuan->nama}\" ({$satuan->kode}).");

        return back()->with('status', "Satuan \"{$satuan->nama}\" berhasil ditambahkan.");
    }

    public function update(Request $request, Satuan $satuan): RedirectResponse
    {
        $validated = $this->validated($request, $satuan);

        $satuan->update($validated);

        ActivityLog::catat('satuan.update', "Memperbarui data satuan \"{$satuan->nama}\" ({$satuan->kode}).");

        return back()->with('status', "Satuan \"{$satuan->nama}\" berhasil diperbarui.");
    }

    /**
     * Hapus satuan. Ditolak kalau masih ada pengguna terdaftar di satuan
     * tsb, supaya tidak ada akun yang kehilangan relasi satuan.
     */
    public function destroy(Satuan $satuan): RedirectResponse
    {
        if ($satuan->users()->exists()) {
            return back()->with('error', "Satuan \"{$satuan->nama}\" masih punya pengguna terdaftar, pindahkan dulu akunnya sebelum menghapus.");
        }

        $nama = $satuan->nama;
        $satuan->delete();

        ActivityLog::catat('satuan.delete', "Menghapus satuan \"{$nama}\".");

        return back()->with('status', "Satuan \"{$nama}\" berhasil dihapus.");
    }

    private function validated(Request $request, ?Satuan $satuan = null): array
    {
        // Kode selalu dipaksa kapital di server, soalnya input di form cuma
        // ter-uppercase secara visual (CSS text-transform), bukan nilai
        // aslinya -- kalau tidak dipaksa di sini, kode bisa kesimpan
        // campuran huruf besar/kecil walau kelihatan kapital pas diketik.
        $request->merge(['kode' => strtoupper(trim((string) $request->input('kode')))]);

        // Pesan Bahasa Indonesia -- proyek tidak punya file lang, jadi tanpa
        // ini kode satuan kembar dsb muncul dalam Bahasa Inggris bawaan Laravel.
        // Ditampilkan inline merah di bawah field (bukan toast).
        return $request->validate([
            'kode' => ['required', 'string', 'max:50', 'unique:satuans,kode'.($satuan ? ','.$satuan->id : '')],
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:'.implode(',', [
                Satuan::KATEGORI_SATLAK,
                Satuan::KATEGORI_DIREKTORAT,
                Satuan::KATEGORI_PIMPINAN,
                Satuan::KATEGORI_ADMIN,
                Satuan::KATEGORI_UNSUR_PELAYANAN,
                Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN,
            ])],
            'deskripsi' => ['nullable', 'string'],
        ], [
            'kode.required' => 'Kode satuan wajib diisi.',
            'kode.unique' => 'Kode satuan ini sudah dipakai satuan lain.',
            'kode.max' => 'Kode satuan maksimal 50 karakter.',
            'nama.required' => 'Nama satuan wajib diisi.',
            'kategori.required' => 'Kategori wajib dipilih.',
            'kategori.in' => 'Kategori yang dipilih tidak valid.',
        ]);
    }
}
