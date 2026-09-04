<?php

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BackupFileController;
use App\Http\Controllers\Admin\PermintaanResetPasswordController as AdminPermintaanResetPasswordController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResetDataLaporanController;
use App\Http\Controllers\Admin\SatuanController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\NotifikasiSettingController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StrukturOrganisasiController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AkunMedsosController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\DanpusLaporanMonitoringController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DukunganTeknisController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LaporanKendalaController;
use App\Http\Controllers\LaporanKendalaTembusanController;
use App\Http\Controllers\LaporanSuratController;
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
use App\Models\Pengaturan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('siberad.landing.welcome', [
        'pengaturan' => Pengaturan::current(),
    ]);
});

Route::get('/landing-config', function () {
    $p = Pengaturan::current();
    $config = $p->landingConfig();
    // Sama seperti welcome.blade.php: TIDAK fallback ke logo/gambar bawaan
    // lagi kalau Admin sudah menghapusnya -- biar konsisten "ikut kosong".
    $config['logo_url'] = $p->logo_path ? asset('storage/'.$p->logo_path) : null;
    $config['background_url'] = $p->hero_image_path ? asset('storage/'.$p->hero_image_path) : null;
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
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan.store');

Route::get('/laporan/log-aktivitas/realtime', [LaporanController::class, 'realtime'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan.log-aktivitas.realtime');

Route::get('/laporan/permintaan-laporan/long-poll', [LaporanController::class, 'tungguPerubahanPermintaan'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan.permintaan-laporan.long-poll');

Route::patch('/laporan/{laporan}/status', [LaporanController::class, 'updateStatus'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan.status');

Route::patch('/laporan/{laporan}/progres', [LaporanController::class, 'updateProgres'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan.update-progres');

Route::delete('/laporan/{laporan}', [LaporanController::class, 'destroy'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan.destroy');

// ===== Permintaan Laporan & Deadline dari DANPUS/WADAN =====
Route::get('/permintaan-laporan', [PermintaanLaporanController::class, 'index'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('permintaan-laporan.index');
Route::get('/permintaan-laporan/realtime', [PermintaanLaporanController::class, 'realtime'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('permintaan-laporan.realtime');
Route::post('/permintaan-laporan', [PermintaanLaporanController::class, 'store'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('permintaan-laporan.store');
Route::patch('/permintaan-laporan/{permintaanLaporan}/mulai', [PermintaanLaporanController::class, 'mulai'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('permintaan-laporan.mulai');
Route::patch('/permintaan-laporan/{permintaanLaporan}/batal', [PermintaanLaporanController::class, 'batal'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('permintaan-laporan.batal');
Route::patch('/permintaan-laporan/{permintaanLaporan}/deadline', [PermintaanLaporanController::class, 'editDeadline'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('permintaan-laporan.edit-deadline');
Route::patch('/permintaan-laporan/{permintaanLaporan}/revisi', [PermintaanLaporanController::class, 'revisiDariRiwayat'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('permintaan-laporan.revisi');

// ===== Laporan Kendala Kasansi (21 Sansidam) langsung ke Danpus =====
Route::post('/laporan-kendala', [LaporanKendalaController::class, 'store'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-kendala.store');
Route::get('/laporan-kendala/realtime', [LaporanKendalaController::class, 'realtime'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-kendala.realtime');
Route::patch('/laporan-kendala/{laporanKendala}/status', [LaporanKendalaController::class, 'updateStatus'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-kendala.status');
Route::patch('/laporan-kendala/{laporanKendala}/teruskan', [LaporanKendalaController::class, 'teruskan'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-kendala.teruskan');
Route::delete('/laporan-kendala/{laporanKendala}', [LaporanKendalaController::class, 'destroy'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-kendala.destroy');

// ===== Surat Kasansi (21 Sansidam) ke SATU tujuan bebas =====
// Status: menunggu_konfirmasi -> dikonfirmasi (oleh penerima).
// Surat masuk Arsip Surat pengirim hanya setelah dikonfirmasi.
Route::post('/laporan-surat', [LaporanSuratController::class, 'store'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-surat.store');
Route::get('/laporan-surat/realtime', [LaporanSuratController::class, 'realtime'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-surat.realtime');
Route::patch('/laporan-surat/{laporanSurat}/konfirmasi', [LaporanSuratController::class, 'konfirmasi'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-surat.konfirmasi');
Route::delete('/laporan-surat/{laporanSurat}', [LaporanSuratController::class, 'destroy'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-surat.destroy');

// ===== Tembusan laporan kendala Kasansi ke 4 Satlak/4 Sdir (info/koordinasi
// + feedback balik ke Kasansi -- lihat komentar LaporanKendalaTembusanController) =====
Route::patch('/laporan-kendala-tembusan/{laporanKendalaTembusan}/baca', [LaporanKendalaTembusanController::class, 'tandaiDibaca'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-kendala-tembusan.baca');
Route::patch('/laporan-kendala-tembusan/{laporanKendalaTembusan}/feedback', [LaporanKendalaTembusanController::class, 'beriFeedback'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-kendala-tembusan.feedback');

// ===== Laporan Publikasi ke DANPUS (Satuan Pelaksanaan Siber Sosial) =====
Route::post('/laporan-publikasi', [LaporanPublikasiController::class, 'store'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-publikasi.store');
Route::patch('/laporan-publikasi/{laporanPublikasi}', [LaporanPublikasiController::class, 'update'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-publikasi.update');
Route::post('/laporan-publikasi/{laporanPublikasi}/kirim', [LaporanPublikasiController::class, 'kirim'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-publikasi.kirim');
Route::post('/laporan-publikasi/{laporanPublikasi}/dokumentasi', [LaporanPublikasiController::class, 'uploadDokumentasi'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-publikasi.upload-dokumentasi');
Route::delete('/laporan-publikasi-dokumen/{dokumen}', [LaporanPublikasiController::class, 'destroyDokumentasi'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-publikasi-dokumen.destroy');
Route::delete('/laporan-publikasi/{laporanPublikasi}', [LaporanPublikasiController::class, 'destroy'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-publikasi.destroy');

// ===== Laporan Monitoring & Recovery ke DANPUS =====
Route::post('/laporan-monitoring', [LaporanMonitoringController::class, 'store'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-monitoring.store');
Route::patch('/laporan-monitoring/{laporanMonitoring}', [LaporanMonitoringController::class, 'update'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-monitoring.update');
Route::post('/laporan-monitoring/{laporanMonitoring}/kirim', [LaporanMonitoringController::class, 'kirim'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-monitoring.kirim');
Route::post('/laporan-monitoring/{laporanMonitoring}/lampiran', [LaporanMonitoringController::class, 'uploadLampiran'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-monitoring.upload-lampiran');
Route::delete('/laporan-monitoring-lampiran/{lampiran}', [LaporanMonitoringController::class, 'destroyLampiran'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-monitoring-lampiran.destroy');
Route::delete('/laporan-monitoring/{laporanMonitoring}', [LaporanMonitoringController::class, 'destroy'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-monitoring.destroy');
Route::patch('/laporan-monitoring/{laporanMonitoring}/status', [DanpusLaporanMonitoringController::class, 'updateStatus'])
    ->middleware(['auth', 'modul:laporan'])
    ->name('laporan-monitoring.update-status');

// ===== Foto Profil (semua role) =====
Route::post('/profil/foto', [ProfilFotoController::class, 'update'])
    ->middleware('auth')
    ->name('profil-foto.update');
Route::delete('/profil/foto', [ProfilFotoController::class, 'destroy'])
    ->middleware('auth')
    ->name('profil-foto.destroy');

// ===== Permintaan Ganti Password (Pimpinan/Satuan -> Admin) =====
Route::post('/permintaan-reset-password', [PermintaanResetPasswordController::class, 'store'])
    ->middleware('auth')
    ->name('permintaan-reset-password.store');
// Dipoll pengaju buat tahu (realtime) begitu Admin menyetujui/menolak
// permintaannya -> form balik ke semula + toast, tanpa reload.
Route::get('/permintaan-reset-password/status', [PermintaanResetPasswordController::class, 'status'])
    ->middleware('auth')
    ->name('permintaan-reset-password.status');

Route::get('/notifikasi/realtime', [NotifikasiController::class, 'realtime'])
    ->middleware(['auth', 'modul:notifikasi'])
    ->name('notifikasi.realtime');
Route::delete('/notifikasi/{notifikasi}', [NotifikasiController::class, 'hapus'])
    ->middleware(['auth', 'modul:notifikasi'])
    ->name('notifikasi.hapus');

// Push subscription (notifikasi di luar sistem/browser tertutup) --
// sengaja TIDAK diikat modul:notifikasi, karena on/off-nya diatur lewat
// izin browser masing-masing user, bukan konfigurasi per-satuan.
Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])
    ->middleware('auth')
    ->name('push.subscribe');
Route::post('/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy'])
    ->middleware('auth')
    ->name('push.unsubscribe');

// Toggle preferensi push notification per-user (on/off dari panel Notifikasi Kasansi)
Route::post('/notifikasi/toggle-user', [\App\Http\Controllers\UserNotifikasiController::class, 'toggle'])
    ->middleware('auth')
    ->name('notifikasi.toggle-user');

// ===== Manajemen Akun Media Sosial =====
Route::post('/akun-medsos', [AkunMedsosController::class, 'store'])->middleware(['auth', 'modul:laporan'])->name('akun-medsos.store');
Route::patch('/akun-medsos/{akunMedsos}', [AkunMedsosController::class, 'update'])->middleware(['auth', 'modul:laporan'])->name('akun-medsos.update');
Route::delete('/akun-medsos/{akunMedsos}', [AkunMedsosController::class, 'destroy'])->middleware(['auth', 'modul:laporan'])->name('akun-medsos.destroy');

// ===== Postingan Media Sosial =====
Route::post('/posting', [PostinganController::class, 'store'])->middleware(['auth', 'modul:laporan'])->name('posting.store');
Route::post('/posting/{posting}/terbitkan', [PostinganController::class, 'terbitkan'])->middleware(['auth', 'modul:laporan'])->name('posting.terbitkan');
Route::patch('/posting/{posting}/engagement', [PostinganController::class, 'updateEngagement'])->middleware(['auth', 'modul:laporan'])->name('posting.engagement');
Route::delete('/posting/{posting}', [PostinganController::class, 'destroy'])->middleware(['auth', 'modul:laporan'])->name('posting.destroy');

// ===== Satuan Pelaksanaan Dukungan Teknologi =====
Route::post('/proyek-riset', [ProyekRisetController::class, 'store'])->middleware(['auth', 'modul:laporan'])->name('proyek-riset.store');
Route::patch('/proyek-riset/{proyekRiset}', [ProyekRisetController::class, 'update'])->middleware(['auth', 'modul:laporan'])->name('proyek-riset.update');
Route::delete('/proyek-riset/{proyekRiset}', [ProyekRisetController::class, 'destroy'])->middleware(['auth', 'modul:laporan'])->name('proyek-riset.destroy');
Route::post('/log-uji', [LogUjiPengembanganController::class, 'store'])->middleware(['auth', 'modul:laporan'])->name('log-uji.store');
Route::delete('/log-uji/{logUjiPengembangan}', [LogUjiPengembanganController::class, 'destroy'])->middleware(['auth', 'modul:laporan'])->name('log-uji.destroy');
Route::post('/dukungan-teknis', [DukunganTeknisController::class, 'store'])->middleware(['auth', 'modul:laporan'])->name('dukungan-teknis.store');
Route::delete('/dukungan-teknis/{dukunganTeknisLog}', [DukunganTeknisController::class, 'destroy'])->middleware(['auth', 'modul:laporan'])->name('dukungan-teknis.destroy');

// ===== Administrasi Personel =====
Route::post('/personel', [PersonelController::class, 'store'])->middleware(['auth', 'modul:laporan'])->name('personel.store');
Route::patch('/personel/{personel}', [PersonelController::class, 'update'])->middleware(['auth', 'modul:laporan'])->name('personel.update');
Route::delete('/personel/{personel}', [PersonelController::class, 'destroy'])->middleware(['auth', 'modul:laporan'])->name('personel.destroy');
Route::post('/pangkat', [PangkatController::class, 'store'])->middleware(['auth', 'modul:laporan'])->name('pangkat.store');
Route::patch('/pangkat/{pangkat}', [PangkatController::class, 'update'])->middleware(['auth', 'modul:laporan'])->name('pangkat.update');
Route::delete('/pangkat/{pangkat}', [PangkatController::class, 'destroy'])->middleware(['auth', 'modul:laporan'])->name('pangkat.destroy');
Route::post('/jabatan', [JabatanController::class, 'store'])->middleware(['auth', 'modul:laporan'])->name('jabatan.store');
Route::patch('/jabatan/{jabatan}', [JabatanController::class, 'update'])->middleware(['auth', 'modul:laporan'])->name('jabatan.update');
Route::delete('/jabatan/{jabatan}', [JabatanController::class, 'destroy'])->middleware(['auth', 'modul:laporan'])->name('jabatan.destroy');
Route::post('/personel-mutasi', [PersonelMutasiController::class, 'store'])->middleware(['auth', 'modul:laporan'])->name('personel-mutasi.store');
Route::patch('/personel-mutasi/{mutasi}', [PersonelMutasiController::class, 'update'])->middleware(['auth', 'modul:laporan'])->name('personel-mutasi.update');
Route::delete('/personel-mutasi/{mutasi}', [PersonelMutasiController::class, 'destroy'])->middleware(['auth', 'modul:laporan'])->name('personel-mutasi.destroy');
Route::post('/personel-dokumen', [PersonelDokumenController::class, 'store'])->middleware(['auth', 'modul:laporan'])->name('personel-dokumen.store');
Route::delete('/personel-dokumen/{dokumen}', [PersonelDokumenController::class, 'destroy'])->middleware(['auth', 'modul:laporan'])->name('personel-dokumen.destroy');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/satuan', [SatuanController::class, 'store'])->name('satuan.store');
    Route::patch('/satuan/{satuan}', [SatuanController::class, 'update'])->name('satuan.update');
    Route::delete('/satuan/{satuan}', [SatuanController::class, 'destroy'])->name('satuan.destroy');
    Route::patch('/satuan/{satuan}/permissions', [PermissionController::class, 'update'])->name('satuan.permissions');
    Route::patch('/pengaturan/landing', [SettingController::class, 'updateLanding'])->name('pengaturan.landing.update');
    Route::delete('/pengaturan/landing/gambar/{tipe}', [SettingController::class, 'deleteLandingImage'])->whereIn('tipe', ['logo', 'hero_image'])->name('pengaturan.landing.image.destroy');
    Route::post('/backup', [BackupController::class, 'store'])->name('backup.store');
    Route::post('/backup/upload', [BackupFileController::class, 'store'])->name('backup.upload');
    Route::get('/backup/{filename}/download', [BackupController::class, 'download'])->name('backup.download');
    Route::delete('/backup/{filename}', [BackupFileController::class, 'destroy'])->name('backup.destroy');
    Route::get('/laporan', [ReportController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/aktivitas-terbaru', [ReportController::class, 'aktivitasTerbaru'])->name('laporan.aktivitas-terbaru');
    Route::get('/log-aktivitas/rentang', [ReportController::class, 'logAktivitasRentang'])->name('log-aktivitas.rentang');
    Route::get('/laporan/cetak/{jenis}', [ReportController::class, 'printView'])
        ->whereIn('jenis', ['pengguna', 'aktivitas'])
        ->name('laporan.cetak');
    Route::get('/laporan/export/pengguna', [ReportController::class, 'exportUsersExcel'])->name('laporan.export-pengguna');
    Route::get('/laporan/export/aktivitas', [ReportController::class, 'exportActivityExcel'])->name('laporan.export-aktivitas');
    Route::get('/sessions/realtime', [SessionController::class, 'realtime'])->name('sessions.realtime');
    Route::delete('/sessions/{id}', [SessionController::class, 'destroy'])->name('sessions.destroy');
    Route::get('/permintaan-reset-password/realtime', [AdminPermintaanResetPasswordController::class, 'realtime'])->name('permintaan-reset-password.realtime');
    Route::patch('/permintaan-reset-password/{permintaanResetPassword}/setujui', [AdminPermintaanResetPasswordController::class, 'setujui'])->name('permintaan-reset-password.setujui');
    Route::patch('/permintaan-reset-password/{permintaanResetPassword}/tolak', [AdminPermintaanResetPasswordController::class, 'tolak'])->name('permintaan-reset-password.tolak');
    Route::delete('/permintaan-reset-password/riwayat', [AdminPermintaanResetPasswordController::class, 'hapusRiwayat'])->name('permintaan-reset-password.hapus-riwayat');
    Route::delete('/reset-data-laporan', [ResetDataLaporanController::class, 'destroy'])->name('reset-data-laporan.destroy');
    Route::patch('/setelan/notifikasi/toggle', [NotifikasiSettingController::class, 'updateToggle'])->name('setelan.notifikasi.toggle');
    Route::post('/setelan/notifikasi/broadcast', [NotifikasiSettingController::class, 'broadcast'])->name('setelan.notifikasi.broadcast');
    Route::post('/struktur-organisasi', [StrukturOrganisasiController::class, 'update'])->name('struktur-organisasi.update');
    Route::delete('/struktur-organisasi', [StrukturOrganisasiController::class, 'destroy'])->name('struktur-organisasi.destroy');
});
