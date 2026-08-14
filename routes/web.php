<?php

use App\Http\Controllers\Admin\BackupController;
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
use App\Http\Controllers\PersonelController;
use App\Http\Controllers\PersonelDokumenController;
use App\Http\Controllers\PersonelMutasiController;
use App\Http\Controllers\PostinganController;
use App\Http\Controllers\ProyekRisetController;
use App\Models\Pengaturan;
use App\Models\Satuan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $satuans = Satuan::orderBy('urutan')->get()->groupBy('kategori');
    return view('siberad.landing.welcome', [
        'satuans' => $satuans,
        'pengaturan' => Pengaturan::current(),
    ]);
});

Route::get('/landing-config', function () {
    $p = Pengaturan::current();
    $config = $p->landingConfig();
    $config['logo_url'] = $p->logo_path ? asset('storage/'.$p->logo_path) : asset('images/logo-pussiberad.jpg');
    $config['background_url'] = $p->hero_image_path ? asset('storage/'.$p->hero_image_path) : asset('images/hero-lapangan-mabesad.jpg');
    return response()->json(['config' => $config]);
});

Route::get('/login', function () {
    return redirect('/');
});

Route::get('/captcha/image', [CaptchaController::class, 'image'])->name('captcha.image');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::post('/laporan', [LaporanController::class, 'store'])
    ->middleware('auth')
    ->name('laporan.store');
Route::patch('/laporan/{laporan}/status', [LaporanController::class, 'updateStatus'])->middleware('auth')->name('laporan.status');
Route::patch('/laporan/{laporan}/progres', [LaporanController::class, 'updateProgres'])->middleware('auth')->name('laporan.update-progres');
Route::delete('/laporan/{laporan}', [LaporanController::class, 'destroy'])->middleware('auth')->name('laporan.destroy');

// ===== Permintaan Laporan & Deadline dari DANPUS/WADAN =====
Route::get('/permintaan-laporan', [PermintaanLaporanController::class, 'index'])->middleware('auth')->name('permintaan-laporan.index');
Route::get('/permintaan-laporan/realtime', [PermintaanLaporanController::class, 'realtime'])->middleware('auth')->name('permintaan-laporan.realtime');
Route::post('/permintaan-laporan', [PermintaanLaporanController::class, 'store'])->middleware('auth')->name('permintaan-laporan.store');
Route::patch('/permintaan-laporan/{permintaanLaporan}/mulai', [PermintaanLaporanController::class, 'mulai'])->middleware('auth')->name('permintaan-laporan.mulai');

// ===== Laporan Publikasi ke DANPUS (Satuan Pelaksanaan Siber Sosial) =====
Route::post('/laporan-publikasi', [LaporanPublikasiController::class, 'store'])->middleware('auth')->name('laporan-publikasi.store');
Route::patch('/laporan-publikasi/{laporanPublikasi}', [LaporanPublikasiController::class, 'update'])->middleware('auth')->name('laporan-publikasi.update');
Route::post('/laporan-publikasi/{laporanPublikasi}/kirim', [LaporanPublikasiController::class, 'kirim'])->middleware('auth')->name('laporan-publikasi.kirim');
Route::post('/laporan-publikasi/{laporanPublikasi}/dokumentasi', [LaporanPublikasiController::class, 'uploadDokumentasi'])->middleware('auth')->name('laporan-publikasi.upload-dokumentasi');
Route::delete('/laporan-publikasi-dokumen/{dokumen}', [LaporanPublikasiController::class, 'destroyDokumentasi'])->middleware('auth')->name('laporan-publikasi-dokumen.destroy');
Route::delete('/laporan-publikasi/{laporanPublikasi}', [LaporanPublikasiController::class, 'destroy'])->middleware('auth')->name('laporan-publikasi.destroy');

// ===== Laporan Monitoring & Recovery ke DANPUS =====
Route::post('/laporan-monitoring', [LaporanMonitoringController::class, 'store'])->middleware('auth')->name('laporan-monitoring.store');
Route::patch('/laporan-monitoring/{laporanMonitoring}', [LaporanMonitoringController::class, 'update'])->middleware('auth')->name('laporan-monitoring.update');
Route::post('/laporan-monitoring/{laporanMonitoring}/kirim', [LaporanMonitoringController::class, 'kirim'])->middleware('auth')->name('laporan-monitoring.kirim');
Route::post('/laporan-monitoring/{laporanMonitoring}/lampiran', [LaporanMonitoringController::class, 'uploadLampiran'])->middleware('auth')->name('laporan-monitoring.upload-lampiran');
Route::delete('/laporan-monitoring-lampiran/{lampiran}', [LaporanMonitoringController::class, 'destroyLampiran'])->middleware('auth')->name('laporan-monitoring-lampiran.destroy');
Route::delete('/laporan-monitoring/{laporanMonitoring}', [LaporanMonitoringController::class, 'destroy'])->middleware('auth')->name('laporan-monitoring.destroy');
Route::patch('/laporan-monitoring/{laporanMonitoring}/status', [DanpusLaporanMonitoringController::class, 'updateStatus'])->middleware('auth')->name('laporan-monitoring.update-status');

Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->middleware('auth')->name('notifikasi.baca-semua');

// ===== Manajemen Akun Media Sosial =====
Route::post('/akun-medsos', [AkunMedsosController::class, 'store'])->middleware('auth')->name('akun-medsos.store');
Route::patch('/akun-medsos/{akunMedsos}', [AkunMedsosController::class, 'update'])->middleware('auth')->name('akun-medsos.update');
Route::delete('/akun-medsos/{akunMedsos}', [AkunMedsosController::class, 'destroy'])->middleware('auth')->name('akun-medsos.destroy');

// ===== Postingan Media Sosial =====
Route::post('/posting', [PostinganController::class, 'store'])->middleware('auth')->name('posting.store');
Route::post('/posting/{posting}/terbitkan', [PostinganController::class, 'terbitkan'])->middleware('auth')->name('posting.terbitkan');
Route::patch('/posting/{posting}/engagement', [PostinganController::class, 'updateEngagement'])->middleware('auth')->name('posting.engagement');
Route::delete('/posting/{posting}', [PostinganController::class, 'destroy'])->middleware('auth')->name('posting.destroy');

// ===== Satuan Pelaksanaan Dukungan Teknologi =====
Route::post('/proyek-riset', [ProyekRisetController::class, 'store'])->middleware('auth')->name('proyek-riset.store');
Route::patch('/proyek-riset/{proyekRiset}', [ProyekRisetController::class, 'update'])->middleware('auth')->name('proyek-riset.update');
Route::delete('/proyek-riset/{proyekRiset}', [ProyekRisetController::class, 'destroy'])->middleware('auth')->name('proyek-riset.destroy');
Route::post('/log-uji', [LogUjiPengembanganController::class, 'store'])->middleware('auth')->name('log-uji.store');
Route::delete('/log-uji/{logUjiPengembangan}', [LogUjiPengembanganController::class, 'destroy'])->middleware('auth')->name('log-uji.destroy');
Route::post('/dukungan-teknis', [DukunganTeknisController::class, 'store'])->middleware('auth')->name('dukungan-teknis.store');
Route::delete('/dukungan-teknis/{dukunganTeknisLog}', [DukunganTeknisController::class, 'destroy'])->middleware('auth')->name('dukungan-teknis.destroy');

// ===== Administrasi Personel =====
Route::post('/personel', [PersonelController::class, 'store'])->middleware('auth')->name('personel.store');
Route::patch('/personel/{personel}', [PersonelController::class, 'update'])->middleware('auth')->name('personel.update');
Route::delete('/personel/{personel}', [PersonelController::class, 'destroy'])->middleware('auth')->name('personel.destroy');
Route::post('/pangkat', [PangkatController::class, 'store'])->middleware('auth')->name('pangkat.store');
Route::patch('/pangkat/{pangkat}', [PangkatController::class, 'update'])->middleware('auth')->name('pangkat.update');
Route::delete('/pangkat/{pangkat}', [PangkatController::class, 'destroy'])->middleware('auth')->name('pangkat.destroy');
Route::post('/jabatan', [JabatanController::class, 'store'])->middleware('auth')->name('jabatan.store');
Route::patch('/jabatan/{jabatan}', [JabatanController::class, 'update'])->middleware('auth')->name('jabatan.update');
Route::delete('/jabatan/{jabatan}', [JabatanController::class, 'destroy'])->middleware('auth')->name('jabatan.destroy');
Route::post('/personel-mutasi', [PersonelMutasiController::class, 'store'])->middleware('auth')->name('personel-mutasi.store');
Route::patch('/personel-mutasi/{mutasi}', [PersonelMutasiController::class, 'update'])->middleware('auth')->name('personel-mutasi.update');
Route::delete('/personel-mutasi/{mutasi}', [PersonelMutasiController::class, 'destroy'])->middleware('auth')->name('personel-mutasi.destroy');
Route::post('/personel-dokumen', [PersonelDokumenController::class, 'store'])->middleware('auth')->name('personel-dokumen.store');
Route::delete('/personel-dokumen/{dokumen}', [PersonelDokumenController::class, 'destroy'])->middleware('auth')->name('personel-dokumen.destroy');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/satuan', [SatuanController::class, 'store'])->name('satuan.store');
    Route::patch('/satuan/{satuan}', [SatuanController::class, 'update'])->name('satuan.update');
    Route::delete('/satuan/{satuan}', [SatuanController::class, 'destroy'])->name('satuan.destroy');
    Route::patch('/satuan/{satuan}/permissions', [PermissionController::class, 'update'])->name('satuan.permissions');
    Route::patch('/pengaturan/landing', [SettingController::class, 'updateLanding'])->name('pengaturan.landing.update');
    Route::post('/backup', [BackupController::class, 'store'])->name('backup.store');
    Route::get('/backup/{filename}/download', [BackupController::class, 'download'])->name('backup.download');
    Route::get('/laporan', [ReportController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/aktivitas-terbaru', [ReportController::class, 'aktivitasTerbaru'])->name('laporan.aktivitas-terbaru');
    Route::get('/laporan/cetak/{jenis}', [ReportController::class, 'printView'])->whereIn('jenis', ['pengguna', 'aktivitas'])->name('laporan.cetak');
    Route::get('/laporan/export/pengguna', [ReportController::class, 'exportUsersExcel'])->name('laporan.export-pengguna');
    Route::get('/laporan/export/aktivitas', [ReportController::class, 'exportActivityExcel'])->name('laporan.export-aktivitas');
    Route::delete('/sessions/{id}', [SessionController::class, 'destroy'])->name('sessions.destroy');
});
