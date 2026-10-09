{{-- Isi kartu "Aktivitas Terbaru" Beranda Admin -- dipakai render awal &
     poll realtime (DashboardController::adminKpiRealtime). Variabel wajib:
     $logAktivitasTerbaru (5 ActivityLog terbaru). Markup MIRROR "Kendala
     Kasansi Terbaru" Pimpinan/Satuan (partials/pimpinan-kendala-terbaru-list.blade.php,
     class ".pimp-activity-item" dkk, baris data TANPA icon) -- tapi TANPA
     status-pill di kanan (ActivityLog gak punya konsep status, beda dari
     LaporanKendala/PermintaanResetPassword yang punya).

     Waktu relatif (".pimp-activity-time") SENGAJA dikosongkan di server dan
     diisi + di-tick client-side lewat data-ts (lihat tickRelativeTimes di
     admin.blade.php), BUKAN diffForHumans() langsung -- poll kartu ini jalan
     tiap 1 detik (syncAdminKpis). Kalau teksnya dirender server, "X detik
     yang lalu" bakal beda tiap detik -> string HTML hasil poll "beda" tiap
     detik -> innerHTML di-swap ulang -> animasi fade-in ke SEMUA baris
     retrigger tiap detik -> keliatan kedip-kedip. Dengan data-ts (timestamp
     mentah, statis selama log-nya sama), string HTML cuma beneran beda kalau
     AKTIVITASNYA beda (baris baru/hilang) -- jamnya jalan tiap detik tanpa
     ikut memicu swap+animasi ulang. --}}
@forelse($logAktivitasTerbaru as $log)<div class="pimp-activity-item"><div class="pimp-activity-body"><div class="pimp-activity-title">{{ $log->deskripsi ?: $log->aksi }}</div><div class="pimp-activity-sub" title="{{ $log->created_at?->translatedFormat('d M Y, H:i') }}">{{ $log->nama_pengguna ?? 'Sistem' }} &middot; <span class="pimp-activity-time" data-ts="{{ $log->created_at?->timestamp }}"></span></div></div></div>@empty<div class="kcard-empty pimp-empty-compact"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg><div class="kcard-empty-title">Belum ada aktivitas tercatat</div><div class="kcard-empty-sub">Aksi yang tercatat sistem akan muncul di sini.</div></div>@endforelse
