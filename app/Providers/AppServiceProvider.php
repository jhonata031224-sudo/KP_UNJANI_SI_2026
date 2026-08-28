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
     */
    public function boot(): void
    {
        View::share('pengaturan', Pengaturan::current());
    }
}
