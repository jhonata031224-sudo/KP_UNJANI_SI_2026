{{-- Isi kartu "Kendala Kasansi Terbaru" -- dipakai render awal &
     poll realtime (DashboardController::pimpinanKpiRealtime). Variabel
     wajib: $pimpKendalaTerbaru (5 LaporanKendala terbaru). Waktu pakai
     diffForHumans() (relatif, "X menit lalu") biar kerasa "terbaru" --
     tanggal lengkap tetep ada di attribute title (hover) buat presisi.
     Karena payload di-render ulang server tiap poll (1 detik), teks
     relatif ini OTOMATIS nyegerin sendiri tanpa perlu ticker JS terpisah.
     Status SENGAJA pakai label MENTAH $k->status ('Menunggu'/'Ditindaklanjuti'/
     'Selesai'/'Ditolak'/'Dikonfirmasi', field yg sama persis dipakai
     partials/kendala-kasansi-row.blade.php & arsip-kendala-kasansi) +
     class warna dari formula $statusClass YANG SAMA (Ditindaklanjuti/
     Selesai/Dikonfirmasi->ok hijau, Ditolak->bad merah, sisanya(Menunggu)
     ->wait kuning) -- BUKAN 2 state karangan sendiri ("Disetujui"/"Dalam
     Proses" dari confirmed_at doang, versi lama) yang bisa nyasar (misal
     kendala yg beneran DITOLAK sempat ketampil "Dalam Proses"/kuning,
     padahal harusnya merah). Empty state pakai class kcard-empty yang
     sama kayak Kendala Kasansi (lihat #kendala-kasansi), biar 1 gaya
     "belum ada data" konsisten. Empty-state SVG stroke pakai fallback
     chain var(--p-muted, var(--text-muted, ...)) -- partial ini dipakai
     bareng juga oleh laporan-role.blade.php (Satuan) yang gak punya var
     --p-muted sendiri, cuma --text-muted. --}}
@forelse($pimpKendalaTerbaru as $k)<div class="pimp-activity-item"><span class="pimp-activity-ico pimp-activity-ico-kendala"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></span><div class="pimp-activity-body"><div class="pimp-activity-title">{{ $k->perihal }}</div><div class="pimp-activity-sub" title="{{ $k->created_at->translatedFormat('d M Y, H:i') }}">Oleh {{ $k->satuan->nama ?? '-' }} &middot; {{ $k->created_at->diffForHumans() }}</div></div><span class="status-pill {{ in_array($k->status, ['Ditindaklanjuti', 'Selesai', 'Dikonfirmasi'], true) ? 'ok' : ($k->status === 'Ditolak' ? 'bad' : 'wait') }}">{{ $k->status }}</span></div>@empty<div class="kcard-empty pimp-empty-compact"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--p-muted, var(--text-muted, currentColor))" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg><div class="kcard-empty-title">Belum ada kendala kasansi</div><div class="kcard-empty-sub">Kendala yang dilaporkan satuan Kasansi akan muncul di sini.</div></div>@endforelse
