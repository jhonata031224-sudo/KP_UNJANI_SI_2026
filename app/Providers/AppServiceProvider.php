<?php

namespace App\Providers;

use App\Models\Pengumuman;
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
     */
    public function boot(): void
    {
        // Pengumuman aktif dibagikan ke semua view dashboard satuan, supaya
        // banner-nya bisa tampil tanpa mengubah tiap method di DashboardController.
        // Pakai daftar nama view eksplisit (bukan wildcard "dashboards.*") supaya
        // partial (dash-styles/dash-script) yang di-@include tiap halaman tidak
        // ikut memicu query berulang.
        $viewDashboard = [
            'admin', 'danpus', 'wadan', 'satlakkal', 'satlaksisos', 'satlakdak',
            'satlakduktek', 'binfung', 'binum', 'diklat', 'binmat', 'generic',
        ];
        View::composer(
            array_map(fn ($nama) => "siberad.dashboards.$nama", $viewDashboard),
            function ($view) {
                $view->with('pengumumanAktif', Pengumuman::where('aktif', true)->latest()->get());
            }
        );
    }
}
