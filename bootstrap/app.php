<?php

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BackupFileController;
use App\Http\Controllers\Admin\LandingAccessCaptchaController;
use App\Http\Controllers\Admin\PermintaanResetPasswordController as AdminPermintaanResetPasswordController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SatuanController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AkunMedsosController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\DanpusLaporanMonitoringController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DukunganTeknisController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LaporanMonitoringController;
use App\Http\Controllers\LaporanPublikasiController;
use App\Http\Controllers\LogUjiPengembanganController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PangkatController;
use App\Http\Controllers\PermintaanLaporanController;
use App\Http\Controllers\PermintaanResetPasswordController;
use App\Http\Controllers\PersonelController;
use App\Http\Controllers\PersonelDokumenController;
use App\Http\Controllers\PersonelMutasiController;
use App\Http\Controllers\PostinganController;
use App\Http\Controllers\ProfilFotoController;
use App\Http\Controllers\ProyekRisetController;
use App\Http\Middleware\InjectDashboardUi;
use App\Http\Middleware\InjectPengaturanAccessUi;
use App\Http\Middleware\RemoveDecorativeSeparators;
use App\Models\Pengaturan;
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
            Route::patch('/laporan/{laporan}/status', [\App\Http\Controllers\LaporanController::class, 'updateStatus'])
                ->middleware(['web', 'auth'])
                ->name('laporan.status');

            Route::middleware(['web', 'auth', \App\Http\Middleware\EnsureUserIsAdmin::class])
                ->prefix('admin')
                ->name('admin.')
                ->group(function (): void {
                    Route::post('/pengaturan/access', [SettingController::class, 'verifyLandingAccess'])
                        ->name('pengaturan.access');
                    Route::get('/pengaturan/captcha', [LandingAccessCaptchaController::class, 'image'])
                        ->name('pengaturan.access-captcha');
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(RemoveDecorativeSeparators::class);
        $middleware->append(InjectDashboardUi::class);
        $middleware->append(InjectPengaturanAccessUi::class);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
