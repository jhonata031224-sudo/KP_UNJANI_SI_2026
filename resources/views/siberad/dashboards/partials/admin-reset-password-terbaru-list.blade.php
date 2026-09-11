{{-- Isi kartu "Permintaan Ganti Password" Beranda Admin -- dipakai render
     awal & poll realtime (DashboardController::adminKpiRealtime). Variabel
     wajib: $permintaanResetPasswordTerbaru (5 PermintaanResetPassword
     terbaru). Markup MIRROR "Kendala Kasansi Terbaru" Pimpinan/Satuan
     (partials/pimpinan-kendala-terbaru-list.blade.php, class
     ".pimp-activity-item" dkk, baris data TANPA icon) -- tapi domain
     modelnya beda (PermintaanResetPassword, bukan LaporanKendala) jadi
     partial ini TERPISAH, bukan reuse langsung. Status-pill pakai 3 state
     PermintaanResetPassword::STATUS_* (Disetujui->ok, Ditolak->bad,
     Menunggu(default)->wait) -- warna var(--success-bright)/var(--red)/
     var(--amber) SENGAJA dipakai (bukan var(--green) yang di dark mode
     di-repurpose jadi gold, lihat komentar di admin.blade.php bagian
     Chart.js), sama seperti match $statusWarna yang sebelumnya dipakai di
     sini. --}}
@forelse($permintaanResetPasswordTerbaru as $r)<div class="pimp-activity-item"><div class="pimp-activity-body"><div class="pimp-activity-title">{{ $r->user->name ?? '-' }}</div><div class="pimp-activity-sub" title="{{ $r->created_at?->translatedFormat('d M Y, H:i') }}">{{ $r->user->satuan->kode ?? 'Sistem' }} &middot; {{ $r->created_at?->diffForHumans() }}</div></div><span class="status-pill {{ $r->status === \App\Models\PermintaanResetPassword::STATUS_DISETUJUI ? 'ok' : ($r->status === \App\Models\PermintaanResetPassword::STATUS_DITOLAK ? 'bad' : 'wait') }}">{{ $r->status }}</span></div>@empty<div class="kcard-empty pimp-empty-compact"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg><div class="kcard-empty-title">Belum ada permintaan ganti password</div><div class="kcard-empty-sub">Permintaan reset password dari pengguna akan muncul di sini.</div></div>@endforelse
