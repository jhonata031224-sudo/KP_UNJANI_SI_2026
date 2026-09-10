{{-- Baris <tr> tabel "Surat Terbaru" -- dipakai render awal (laporan-pimpinan.blade.php)
     & poll realtime (DashboardController::pimpinanKpiRealtime), biar
     algoritma format kolom cuma 1 salinan. Variabel wajib: $pimpSuratTerbaru
     (5 LaporanSurat terbaru), $satuan (satuan Pimpinan yang login, buat
     nentuin arah Surat Masuk/Keluar). Kolom "No" SENGAJA dihapus (gak
     ada gunanya di list 5-terbaru), "Pengirim" pakai kode satuan (bukan
     nama panjang), dan "Status" SENGAJA pakai class+label PERSIS dari
     LaporanSurat::badgeClass()/isDikonfirmasi() -- sumber kebenaran yang
     sama dipakai kartu asli di #surat-masuk/#kirim-surat/#arsip-surat
     (lihat partials/surat-masuk-row.blade.php) -- biar status di sini
     BENERAN selaras/gak ada kata beda ("Diterima"/"Diproses" versi lama
     itu istilah sendiri, ganti persis "Dikonfirmasi"/"Menunggu"). Empty
     state pakai class kcard-empty yang sama kayak Kendala Kasansi (lihat
     #kendala-kasansi), biar 1 gaya "belum ada data" konsisten. --}}
@forelse($pimpSuratTerbaru as $s)<tr><td>{{ $s->created_at->translatedFormat('d M Y') }}</td><td><span class="surat-arah surat-arah-{{ $s->tujuan_satuan_id === $satuan->id ? 'masuk' : 'keluar' }}">{{ $s->tujuan_satuan_id === $satuan->id ? '↓ Masuk' : '↑ Keluar' }}</span></td><td>{{ $s->perihal }}</td><td><span class="satuan-pill">{{ $s->satuan->kode ?? $s->satuan->nama ?? '-' }}</span></td><td><span class="status-badge {{ $s->badgeClass() }}">{{ $s->isDikonfirmasi() ? 'Dikonfirmasi' : 'Menunggu' }}</span></td></tr>@empty<tr><td colspan="5"><div class="kcard-empty pimp-empty-compact"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="var(--p-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg><div class="kcard-empty-title">Belum ada surat terbaru</div><div class="kcard-empty-sub">Surat masuk maupun keluar yang tercatat akan muncul di sini.</div></div></td></tr>@endforelse
