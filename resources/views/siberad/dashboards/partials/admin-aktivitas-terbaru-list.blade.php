{{-- Isi kartu "Aktivitas Terbaru" Beranda Admin -- dipakai render awal &
     poll realtime (DashboardController::adminKpiRealtime). Variabel wajib:
     $logAktivitasTerbaru (5 ActivityLog terbaru). Markup MIRROR "Kendala
     Kasansi Terbaru" Pimpinan/Satuan (partials/pimpinan-kendala-terbaru-list.blade.php,
     class ".pimp-activity-item" dkk, baris data TANPA icon) -- tapi TANPA
     status-pill di kanan (ActivityLog gak punya konsep status, beda dari
     LaporanKendala/PermintaanResetPassword yang punya). --}}
@forelse($logAktivitasTerbaru as $log)<div class="pimp-activity-item"><div class="pimp-activity-body"><div class="pimp-activity-title">{{ $log->deskripsi ?: $log->aksi }}</div><div class="pimp-activity-sub" title="{{ $log->created_at?->translatedFormat('d M Y, H:i') }}">{{ $log->nama_pengguna ?? 'Sistem' }} &middot; {{ $log->created_at?->diffForHumans() }}</div></div></div>@empty<div class="kcard-empty pimp-empty-compact"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg><div class="kcard-empty-title">Belum ada aktivitas tercatat</div><div class="kcard-empty-sub">Aksi yang tercatat sistem akan muncul di sini.</div></div>@endforelse
