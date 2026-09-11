<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Laporan;
use App\Models\LaporanKendala;
use App\Models\LaporanMonitoring;
use App\Models\PermintaanLaporan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Fitur "Reset Data Laporan" khusus Admin: membersihkan data laporan
 * (dummy/uji coba) secara permanen per kategori atau per baris spesifik,
 * TANPA menyentuh data pengguna (username/password), satuan, ataupun pengaturan sistem.
 */
class ResetDataLaporanController extends Controller
{
    /**
     * Daftar kategori data laporan yang boleh dibersihkan, beserta
     * tabel-tabel yang terlibat. Urutan tabel dalam tiap kategori sengaja
     * anak dulu baru induk, supaya aman dihapus meskipun foreign key
     * constraint tidak ditegakkan oleh database.
     */
    public const KATEGORI = [
        'laporan' => [
            'label' => 'Laporan & Laporan Kendala',
            'desc' => 'Laporan berkala satuan, kendala operasional, serta dokumen lampirannya.',
            'icon' => 'file-text',
            'tables' => [
                ['table' => 'laporans', 'file_column' => 'lampiran_path'],
                ['table' => 'laporan_kendalas', 'file_column' => 'lampiran_path'],
            ],
        ],
        'monitoring' => [
            'label' => 'Laporan Monitoring & Recovery',
            'desc' => 'Rekap kegiatan monitoring sistem, recovery insiden, dan berkas pendukung.',
            'icon' => 'activity',
            'tables' => [
                ['table' => 'laporan_monitoring_lampirans', 'file_column' => 'path'],
                ['table' => 'laporan_monitorings', 'file_column' => null],
            ],
        ],
        'permintaan' => [
            'label' => 'Permintaan Laporan & Task',
            'desc' => 'Daftar disposisi permintaan laporan dan tracking penugasan satuan.',
            'icon' => 'clipboard-list',
            'tables' => [
                ['table' => 'permintaan_laporan_tasks', 'file_column' => null],
                ['table' => 'permintaan_laporans', 'file_column' => null],
            ],
        ],
    ];

    /**
     * Hitung jumlah baris tabel utama tiap kategori, dipakai untuk
     * ditampilkan di indikator ringkasan "Reset Data Laporan".
     */
    public static function hitungPerKategori(): array
    {
        $hasil = [];

        foreach (self::KATEGORI as $key => $def) {
            $count = 0;
            foreach ($def['tables'] as $t) {
                // Untuk kategori gabungan (seperti laporan & kendala), jumlahkan tabel utamanya
                if ($key === 'laporan') {
                    $count += DB::table($t['table'])->count();
                } else {
                    $tabelUtama = collect($def['tables'])->last()['table'];
                    $count = DB::table($tabelUtama)->count();
                    break;
                }
            }
            $hasil[$key] = $count;
        }

        return $hasil;
    }

    /**
     * Ambil rincian baris data per kategori untuk ditampilkan dalam
     * card panel detail interaktif, sehingga Admin bisa meninjau dan memilih
     * baris mana yang hendak dihapus secara selektif.
     */
    public static function ambilDetailPerKategori(): array
    {
        $detail = [
            'laporan' => [],
            'monitoring' => [],
            'permintaan' => [],
        ];

        try {
            // 1. Kategori: Laporan & Kendala
            $laporans = Laporan::with(['satuan', 'user'])->latest('id')->limit(150)->get();
            foreach ($laporans as $row) {
                $detail['laporan'][] = [
                    'key' => 'laporan:'.$row->id,
                    'id' => $row->id,
                    'tipe' => 'laporan',
                    'subtipe' => 'Laporan Berkala',
                    'subtipe_badge' => 'gold',
                    'judul' => $row->perihal ?: ($row->proyek ?: 'Laporan #'.$row->id),
                    'satuan' => $row->satuan->nama ?? ($row->satuan->kode ?? '-'),
                    'user' => $row->user->name ?? '-',
                    'tanggal' => $row->created_at ? $row->created_at->format('d M Y H:i') : '-',
                    'status' => $row->status ?: 'Terkirim',
                    'lampiran' => !empty($row->lampiran_path),
                    'ts' => $row->created_at ? $row->created_at->timestamp : 0,
                ];
            }

            $kendalas = LaporanKendala::with(['satuan', 'user'])->latest('id')->limit(150)->get();
            foreach ($kendalas as $row) {
                $detail['laporan'][] = [
                    'key' => 'kendala:'.$row->id,
                    'id' => $row->id,
                    'tipe' => 'kendala',
                    'subtipe' => 'Laporan Kendala',
                    'subtipe_badge' => 'amber',
                    'judul' => $row->perihal ?: 'Kendala #'.$row->id,
                    'satuan' => $row->satuan->nama ?? ($row->satuan->kode ?? '-'),
                    'user' => $row->user->name ?? '-',
                    'tanggal' => $row->created_at ? $row->created_at->format('d M Y H:i') : '-',
                    'status' => $row->status ?: 'Terkirim',
                    'lampiran' => !empty($row->lampiran_path),
                    'ts' => $row->created_at ? $row->created_at->timestamp : 0,
                ];
            }
            usort($detail['laporan'], fn ($a, $b) => $b['ts'] <=> $a['ts']);

            // 2. Kategori: Monitoring & Recovery
            $monitorings = LaporanMonitoring::with(['satuan', 'user'])->latest('id')->limit(150)->get();
            foreach ($monitorings as $row) {
                $detail['monitoring'][] = [
                    'key' => 'monitoring:'.$row->id,
                    'id' => $row->id,
                    'tipe' => 'monitoring',
                    'subtipe' => 'Monitoring & Recovery',
                    'subtipe_badge' => 'cyan',
                    'judul' => $row->jenis_kegiatan ?: ($row->ringkasan_kegiatan ? Str::limit($row->ringkasan_kegiatan, 50) : 'Monitoring #'.$row->id),
                    'satuan' => $row->satuan->nama ?? ($row->satuan->kode ?? '-'),
                    'user' => $row->user->name ?? '-',
                    'tanggal' => $row->created_at ? $row->created_at->format('d M Y H:i') : '-',
                    'status' => $row->status ?: 'Terkirim',
                    'lampiran' => false,
                    'ts' => $row->created_at ? $row->created_at->timestamp : 0,
                ];
            }

            // 3. Kategori: Permintaan Laporan & Task
            $permintaans = PermintaanLaporan::with(['tujuanSatuan', 'pembuat'])->latest('id')->limit(150)->get();
            foreach ($permintaans as $row) {
                $detail['permintaan'][] = [
                    'key' => 'permintaan:'.$row->id,
                    'id' => $row->id,
                    'tipe' => 'permintaan',
                    'subtipe' => 'Permintaan Laporan',
                    'subtipe_badge' => 'amber',
                    'judul' => $row->perihal ?: 'Permintaan #'.$row->id,
                    'satuan' => $row->tujuanSatuan->nama ?? ($row->tujuanSatuan->kode ?? '-'),
                    'user' => $row->pembuat->name ?? '-',
                    'tanggal' => $row->created_at ? $row->created_at->format('d M Y H:i') : '-',
                    'status' => $row->status ?: 'Aktif',
                    'lampiran' => false,
                    'ts' => $row->created_at ? $row->created_at->timestamp : 0,
                ];
            }
        } catch (\Throwable $e) {
            // Tangani gracefully jika tabel belum siap atau migration belum jalan
        }

        return $detail;
    }

    /**
     * Hapus data laporan baik per baris terpilih (selektif) ataupun per kategori utuh.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // -------------------------------------------------------------
        // MODE A: Hapus baris-baris spesifik yang dipilih (item_keys[])
        // -------------------------------------------------------------
        if ($request->filled('item_keys')) {
            $itemKeys = (array) $request->input('item_keys');
            if (empty($itemKeys)) {
                return back()->with('error', 'Pilih minimal satu baris data yang ingin dihapus.');
            }

            $totalTerhapus = 0;

            DB::transaction(function () use ($itemKeys, &$totalTerhapus) {
                foreach ($itemKeys as $itemKey) {
                    if (!str_contains($itemKey, ':')) continue;
                    [$tipe, $id] = explode(':', $itemKey, 2);
                    $id = (int) $id;
                    if ($id <= 0) continue;

                    switch ($tipe) {
                        case 'laporan':
                            $row = Laporan::find($id);
                            if ($row) {
                                if (!empty($row->lampiran_path)) {
                                    Storage::disk('public')->delete($row->lampiran_path);
                                }
                                // Bersihkan berkas di relasi lampiran (jika ada tabel laporan_lampirans)
                                if (DB::getSchemaBuilder()->hasTable('laporan_lampirans')) {
                                    DB::table('laporan_lampirans')->where('laporan_id', $id)->pluck('path')->filter()->each(function ($p) {
                                        Storage::disk('public')->delete($p);
                                    });
                                    DB::table('laporan_lampirans')->where('laporan_id', $id)->delete();
                                }
                                $row->delete();
                                $totalTerhapus++;
                            }
                            break;

                        case 'kendala':
                            $row = LaporanKendala::find($id);
                            if ($row) {
                                if (!empty($row->lampiran_path)) {
                                    Storage::disk('public')->delete($row->lampiran_path);
                                }
                                if (DB::getSchemaBuilder()->hasTable('laporan_kendala_lampirans')) {
                                    DB::table('laporan_kendala_lampirans')->where('laporan_kendala_id', $id)->pluck('path')->filter()->each(function ($p) {
                                        Storage::disk('public')->delete($p);
                                    });
                                    DB::table('laporan_kendala_lampirans')->where('laporan_kendala_id', $id)->delete();
                                }
                                $row->delete();
                                $totalTerhapus++;
                            }
                            break;

                        case 'monitoring':
                            $row = LaporanMonitoring::find($id);
                            if ($row) {
                                DB::table('laporan_monitoring_lampirans')->where('laporan_monitoring_id', $id)->pluck('path')->filter()->each(function ($p) {
                                    Storage::disk('public')->delete($p);
                                });
                                DB::table('laporan_monitoring_lampirans')->where('laporan_monitoring_id', $id)->delete();
                                $row->delete();
                                $totalTerhapus++;
                            }
                            break;

                        case 'permintaan':
                            $row = PermintaanLaporan::find($id);
                            if ($row) {
                                DB::table('permintaan_laporan_tasks')->where('permintaan_laporan_id', $id)->delete();
                                $row->delete();
                                $totalTerhapus++;
                            }
                            break;
                    }
                }
            });

            ActivityLog::catat(
                'reset-data-laporan',
                "Menghapus {$totalTerhapus} baris data laporan spesifik dari sistem."
            );

            return back()->with('status', "Berhasil menghapus {$totalTerhapus} baris data laporan terpilih secara permanen.");
        }

        // -------------------------------------------------------------
        // MODE B: Hapus seluruh baris dalam kategori yang dipilih
        // -------------------------------------------------------------
        $kategoriInput = $request->input('kategori');
        if (is_string($kategoriInput)) {
            $kategoriInput = [$kategoriInput];
        }

        $request->merge(['kategori' => $kategoriInput]);

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
                    if (!DB::getSchemaBuilder()->hasTable($t['table'])) continue;

                    // Hapus file fisik (lampiran/dokumen/bukti) sebelum barisnya dihapus
                    if (!empty($t['file_column']) && DB::getSchemaBuilder()->hasColumn($t['table'], $t['file_column'])) {
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
            'Membersihkan data laporan: '.implode(', ', $labelTerhapus).". Total {$totalBarisTerhapus} baris dihapus."
        );

        return back()->with('status', 'Data laporan kategori terpilih berhasil dihapus bersih: '.implode(', ', $labelTerhapus).'.');
    }
}

