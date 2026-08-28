<?php

namespace App\Providers;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Share $pengaturan ke SEMUA view (termasuk error pages) sehingga
     * nama sistem (judul_awal + judul_aksen) di <title> tab browser
     * selalu sinkron dengan nilai yang disimpan admin di Pengaturan Umum.
     *
     * PENTING: pakai View::composer('*', ...) (LAZY), BUKAN View::share()
     * langsung (EAGER). View::share() di sini akan mengeksekusi
     * Pengaturan::current() -- yang query + firstOrCreate ke DB -- setiap
     * kali aplikasi Laravel di-boot, termasuk saat `composer install`
     * menjalankan `artisan package:discover` (bagian standar Laravel di
     * post-autoload-dump). Itu kejadian di tahap BUILD Railway, yang
     * belum ada akses ke host DB internal (mis. mysql.railway.internal
     * cuma bisa di-resolve saat runtime/deploy) -- build pun gagal dengan
     * error koneksi PDO. Dengan View::composer('*', ...), query ke DB
     * baru benar-benar jalan waktu ada view yang di-render (permintaan
     * HTTP sungguhan), bukan waktu framework sekadar di-boot untuk
     * command console seperti package:discover/key:generate.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            // Cache statis per-request: satu halaman bisa merender banyak
            // view/partial (layout + child view), tanpa ini tiap partial
            // akan query DB sendiri-sendiri padahal datanya sama persis.
            static $pengaturan = null;
            $pengaturan ??= Pengaturan::current();

            $view->with('pengaturan', $pengaturan);
        });
    }
}
