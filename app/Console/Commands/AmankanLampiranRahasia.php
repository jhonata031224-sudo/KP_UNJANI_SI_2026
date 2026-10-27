<?php

namespace App\Console\Commands;

use App\Models\LaporanSurat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Pindahkan lampiran surat RAHASIA yang masih berada di penyimpanan PUBLIK
 * (dibuat sebelum fitur penyimpanan privat) ke disk privat, sehingga tidak bisa
 * lagi dibuka lewat URL publik -- hanya lewat route ber-otorisasi
 * (LaporanSuratController::lampiran). Aman dijalankan berulang.
 *
 *   php artisan surat:amankan-lampiran-rahasia --dry-run   (lihat dulu, tanpa mengubah)
 *   php artisan surat:amankan-lampiran-rahasia
 */
class AmankanLampiranRahasia extends Command
{
    protected $signature = 'surat:amankan-lampiran-rahasia {--dry-run : Hanya tampilkan yang akan dipindah, tanpa mengubah berkas}';

    protected $description = 'Pindahkan lampiran surat Rahasia dari penyimpanan publik ke penyimpanan privat';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $private = Storage::disk(LaporanSurat::DISK_RAHASIA);
        $public = Storage::disk('public');

        $dipindah = 0;
        $sudahAman = 0;
        $tidakAda = 0;
        $gagal = 0;

        LaporanSurat::with('riwayats')
            ->where('prioritas', LaporanSurat::PRIORITAS_DANPUS_RAHASIA)
            ->chunkById(100, function ($kelompok) use ($dry, $private, $public, &$dipindah, &$sudahAman, &$tidakAda, &$gagal) {
                foreach ($kelompok as $surat) {
                    $paths = collect([$surat->lampiran_path])
                        ->merge($surat->riwayats->pluck('lampiran_path'))
                        ->filter()
                        ->unique();

                    foreach ($paths as $path) {
                        if (! $public->exists($path)) {
                            $private->exists($path) ? $sudahAman++ : $tidakAda++;
                            continue;
                        }

                        if ($dry) {
                            $this->line("  [dry-run] surat #{$surat->id}: {$path}");
                            $dipindah++;
                            continue;
                        }

                        try {
                            if (! $private->exists($path)) {
                                $stream = $public->readStream($path);
                                $private->writeStream($path, $stream);
                                if (is_resource($stream)) {
                                    fclose($stream);
                                }
                            }
                            // hapus salinan publik hanya bila salinan privat terbukti utuh
                            if ($private->exists($path) && $private->size($path) === $public->size($path)) {
                                $public->delete($path);
                                $dipindah++;
                            } else {
                                $gagal++;
                                $this->error("  gagal memverifikasi surat #{$surat->id}: {$path}");
                            }
                        } catch (\Throwable $e) {
                            $gagal++;
                            $this->error("  gagal surat #{$surat->id}: {$path} ({$e->getMessage()})");
                        }
                    }
                }
            });

        $this->info(($dry ? '[dry-run] ' : '')."Dipindah: {$dipindah} | sudah aman: {$sudahAman} | berkas tidak ada: {$tidakAda} | gagal: {$gagal}");

        return $gagal > 0 ? self::FAILURE : self::SUCCESS;
    }
}
