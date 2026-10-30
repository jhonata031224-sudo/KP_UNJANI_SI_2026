{{-- Isi kartu "Permintaan Ganti Password" Beranda Admin -- dipakai render
     awal & poll realtime (DashboardController::adminKpiRealtime). Variabel
     wajib: $permintaanResetPasswordTerbaru (5 PermintaanResetPassword
     terbaru). Markup MIRROR "Kendala Kasansi Terbaru" Pimpinan/Satuan
     (partials/pimpinan-kendala-terbaru-list.blade.php, class
     ".pimp-activity-item" dkk, baris data TANPA icon) -- tapi domain
     modelnya beda (PermintaanResetPassword, bukan LaporanKendala) jadi
     partial ini TERPISAH, bukan reuse langsung. Status-pill pakai 3 state
     PermintaanResetPassword::STATUS_* (Disetujui->ok, Ditolak->bad,
     Menunggu(default)->blue) -- warna var(--success-bright)/var(--red)
     SENGAJA dipakai (bukan var(--green) yang di dark mode di-repurpose jadi
     gold, lihat komentar di admin.blade.php bagian Chart.js), sama seperti
     match $statusWarna yang sebelumnya dipakai di sini. Menunggu SENGAJA
     'blue' (bukan 'wait'/amber default status-pill) atas permintaan user --
     amber sebenarnya lebih dominan buat "Menunggu" di sistem ini (dipakai
     di Kendala Kasansi dkk), biru cuma dipakai khusus di 1 tahap Permintaan
     Laporan ("Menunggu pemeriksaan", lihat permintaan-laporan-item.blade.php)
     -- user tetap milih biru walau sudah dikasih tau itu bukan yang paling
     konsisten.

     Waktu relatif (".pimp-activity-time") SENGAJA dikosongkan di server dan
     diisi + di-tick client-side lewat data-ts (tickRelativeTimes di
     admin.blade.php, SATU fungsi buat kartu ini + "Aktivitas Terbaru") --
     lihat [[feedback_dom_diff_flicker_gotcha]] & komentar lengkap di
     admin-aktivitas-terbaru-list.blade.php: poll kartu ini jalan tiap 1
     detik, kalau teks diffForHumans() dirender server bakal beda tiap detik
     -> HTML hasil poll "beda" terus -> innerHTML di-swap ulang -> animasi
     fade-in retrigger tiap detik -> kedip-kedip. --}}
@forelse($permintaanResetPasswordTerbaru as $r)<div class="pimp-activity-item"><div class="pimp-activity-body"><div class="pimp-activity-title">{{ $r->user->name ?? '-' }}</div><div class="pimp-activity-sub" title="{{ $r->created_at?->translatedFormat('d M Y, H:i') }}">{{ $r->user->satuan->kode ?? 'Sistem' }} &middot; <span class="pimp-activity-time" data-ts="{{ $r->created_at?->timestamp }}"></span></div></div><span class="status-pill {{ $r->status === \App\Models\PermintaanResetPassword::STATUS_DISETUJUI ? 'ok' : ($r->status === \App\Models\PermintaanResetPassword::STATUS_DITOLAK ? 'bad' : 'blue') }}">{{ $r->status }}</span></div>@empty<div class="kcard-empty pimp-empty-compact"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg><div class="kcard-empty-title">Belum ada permintaan ganti password</div><div class="kcard-empty-sub">Permintaan reset password dari pengguna akan muncul di sini.</div></div>@endforelse
