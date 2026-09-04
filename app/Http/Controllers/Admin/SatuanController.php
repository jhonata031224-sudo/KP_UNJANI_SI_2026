<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Satuan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    /**
     * Tambah satuan/Satlak baru — fitur "Manajemen Satlak".
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validated($request);

        $satuan = Satuan::create($validated);

        ActivityLog::catat('satuan.create', "Menambahkan satuan \"{$satuan->nama}\" ({$satuan->kode}).");

        $pesan = "Satuan \"{$satuan->nama}\" berhasil ditambahkan.";

        return $request->wantsJson()
            ? $this->tableJson($satuan, $pesan)
            : back()->with('status', $pesan);
    }

    public function update(Request $request, Satuan $satuan): RedirectResponse|JsonResponse
    {
        $validated = $this->validated($request, $satuan);

        // Kode & kategori satuan Admin dikunci -- dipakai hardcoded di banyak
        // cek role (kode === 'ADMIN', kategori admin); mengubahnya bikin
        // deteksi role admin kacau. Field-nya sudah readonly/disabled di modal,
        // ini pengaman kalau request diakalin.
        if ($satuan->kategori === Satuan::KATEGORI_ADMIN) {
            $validated['kode'] = $satuan->kode;
            $validated['kategori'] = $satuan->kategori;
        }

        $satuan->update($validated);

        ActivityLog::catat('satuan.update', "Memperbarui data satuan \"{$satuan->nama}\" ({$satuan->kode}).");

        $pesan = "Satuan \"{$satuan->nama}\" berhasil diperbarui.";

        return $request->wantsJson()
            ? $this->tableJson($satuan, $pesan)
            : back()->with('status', $pesan);
    }

    /**
     * Balas JSON berisi SELURUH isi <tbody> tabel Data Satuan yang sudah
     * dirender ulang & terurut (Satuan::terurut()) -- klien tinggal timpa
     * innerHTML tbody tanpa reload, jadi modal Tambah/Ubah Satuan tetap
     * kebuka DAN baris baru/berubah tetap di posisi sesuai urutan kategori
     * (bukan nyelonong ke paling atas).
     */
    private function tableJson(Satuan $satuan, string $pesan): JsonResponse
    {
        $rowsHtml = Satuan::terurut()
            ->map(fn (Satuan $s) => view('siberad.dashboards.partials.satuan-row', ['s' => $s])->render())
            ->implode('');

        return response()->json([
            'ok' => true,
            'id' => $satuan->id,
            'message' => $pesan,
            'rows_html' => $rowsHtml,
        ]);
    }

    /**
     * Hapus satuan. Ditolak kalau masih ada pengguna terdaftar di satuan
     * tsb, supaya tidak ada akun yang kehilangan relasi satuan. Balas JSON
     * saat wantsJson() supaya tabel Data Satuan bisa hapus barisnya tanpa
     * reload (senada dengan Tambah/Ubah yang sudah AJAX).
     */
    public function destroy(Request $request, Satuan $satuan): RedirectResponse|JsonResponse
    {
        if ($satuan->users()->exists()) {
            $pesan = "Satuan \"{$satuan->nama}\" masih punya pengguna terdaftar, pindahkan dulu akunnya sebelum menghapus.";

            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => $pesan], 422)
                : back()->with('error', $pesan);
        }

        $id = $satuan->id;
        $nama = $satuan->nama;
        $satuan->delete();

        ActivityLog::catat('satuan.delete', "Menghapus satuan \"{$nama}\".");

        $pesan = "Satuan \"{$nama}\" berhasil dihapus.";

        return $request->wantsJson()
            ? response()->json(['ok' => true, 'id' => $id, 'message' => $pesan])
            : back()->with('status', $pesan);
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
