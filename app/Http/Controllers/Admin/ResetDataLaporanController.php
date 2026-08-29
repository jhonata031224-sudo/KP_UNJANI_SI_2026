<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Fitur "Reset Data Laporan" khusus Admin: membersihkan data laporan
 * (dummy/uji coba) secara permanen per kategori, TANPA menyentuh data
 * pengguna (username/password), satuan, ataupun pengaturan sistem.
 *
 * Admin memilih sendiri kategori mana yang mau dihapus lewat checklist —
 * tidak ada tombol "hapus semua sekali klik" supaya tidak kehapus tanpa
 * sengaja.
 */
class ResetDataLaporanController extends Controller
{
    /**
     * Daftar kategori data laporan yang boleh dibersihkan, beserta
     * tabel-tabel yang terlibat. Urutan tabel dalam tiap kategori sengaja
     * anak dulu baru induk, supaya aman dihapus meskipun foreign key
     * constraint tidak ditegakkan oleh database (mis. SQLite tanpa PRAGMA
     * foreign_keys). Kolom file (jika ada) ikut dibersihkan dari storage
     * sebelum barisnya dihapus, supaya tidak menyisakan file yatim di
     * server.
     */
    public const KATEGORI = [
        'laporan' => [
            'label' => 'Laporan & Laporan Kendala',
            'tables' => [
                ['table' => 'laporans', 'file_column' => 'lampiran_path'],
                ['table' => 'laporan_kendalas', 'file_column' => 'lampiran_path'],
            ],
        ],
        'monitoring' => [
            'label' => 'Laporan Monitoring & Recovery',
            'tables' => [
                ['table' => 'laporan_monitoring_lampirans', 'file_column' => 'path'],
                ['table' => 'laporan_monitorings', 'file_column' => null],
            ],
        ],
        'penindakan' => [
            'label' => 'Laporan Penindakan (Insiden Siber)',
            'tables' => [
                ['table' => 'laporan_penindakan_buktis', 'file_column' => 'path'],
                ['table' => 'laporan_penindakans', 'file_column' => null],
            ],
        ],
        'publikasi' => [
            'label' => 'Laporan Publikasi',
            'tables' => [
                ['table' => 'laporan_publikasi_dokumens', 'file_column' => 'path'],
                ['table' => 'laporan_publikasis', 'file_column' => null],
            ],
        ],
        'permintaan' => [
            'label' => 'Permintaan Laporan & Task',
            'tables' => [
                ['table' => 'permintaan_laporan_tasks', 'file_column' => null],
                ['table' => 'permintaan_laporans', 'file_column' => null],
            ],
        ],
    ];

    /**
     * Hitung jumlah baris tabel utama tiap kategori, dipakai untuk
     * ditampilkan di checklist "Reset Data Laporan" supaya admin tahu
     * kira-kira berapa banyak data yang akan hilang sebelum menghapus.
     */
    public static function hitungPerKategori(): array
    {
        $hasil = [];

        foreach (self::KATEGORI as $key => $def) {
            // Tabel terakhir pada tiap kategori adalah tabel induk/utama
            // yang representatif untuk dihitung.
            $tabelUtama = collect($def['tables'])->last()['table'];
            $hasil[$key] = DB::table($tabelUtama)->count();
        }

        return $hasil;
    }

    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kategori' => ['required', 'array', 'min:1'],
            'kategori.*' => [Rule::in(array_keys(self::KATEGORI))],
        ], [
            'kategori.required' => 'Pilih dulu minimal satu kategori data laporan yang mau dihapus.',
        ]);

        $labelTerhapus = [];
        $totalBarisTerhapus = 0;

        DB::transaction(function () use ($data, &$labelTerhapus, &$totalBarisTerhapus) {
            foreach ($data['kategori'] as $key) {
                $def = self::KATEGORI[$key];
                $labelTerhapus[] = $def['label'];

                foreach ($def['tables'] as $t) {
                    // Hapus file fisik (lampiran/dokumen/bukti) dulu sebelum
                    // baris DB-nya hilang, supaya tidak ada file yatim.
                    if (! empty($t['file_column'])) {
                        DB::table($t['table'])
                            ->whereNotNull($t['file_column'])
                            ->pluck($t['file_column'])
                            ->filter()
                            ->each(fn ($path) => Storage::disk('public')->delete($path));
                    }

                    $totalBarisTerhapus += DB::table($t['table'])->count();
                    DB::table($t['table'])->delete();
                }
            }
        });

        ActivityLog::catat(
            'reset-data-laporan',
            'Membersihkan data laporan dummy: '.implode(', ', $labelTerhapus).". Total {$totalBarisTerhapus} baris dihapus."
        );

        return back()->with('status', 'Data laporan terpilih berhasil dihapus bersih: '.implode(', ', $labelTerhapus).'.');
    }
}
