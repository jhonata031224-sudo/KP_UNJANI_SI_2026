<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            // Alur review laporan umum antar-satuan.
            // Route ini tetap dipisahkan dari routes/web.php agar perubahan
            // pada route lama tidak mengganggu modul operasional yang masih dipertahankan.
            // Middleware web diperlukan agar session, CSRF, dan redirect bekerja
            // sama seperti route aplikasi web lainnya.
            Route::patch('/laporan/{laporan}/status', [\App\Http\Controllers\LaporanController::class, 'updateStatus'])
                ->middleware(['web', 'auth'])
                ->name('laporan.status');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
