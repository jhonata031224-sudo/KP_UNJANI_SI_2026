@php
  // Status & warna kartu niru PERSIS logika
  // permintaan-laporan-pimpinan-card.blade.php (BUKAN
  // $pl->statusTampilan() yang cuma 7 label datar) -- biar
  // kartu Arsip Data Admin ini konsisten visual DAN maknanya
  // dengan kartu asli yang dilihat Pimpinan, termasuk
  // membedakan Selesai->Disetujui vs Ditolak yang
  // statusTampilan() tidak bedakan (dipakai ekspor CSV/PDF,
  // sengaja dibiarkan pakai statusTampilan() -- teks polos
  // sudah cukup jelas buat file, gak butuh warna).
  if ($pl->status === \App\Models\PermintaanLaporan::STATUS_DIBATALKAN) {
      $plStatus = 'Dibatalkan'; $plStatusClass = 'bad';
  } elseif ($pl->status === \App\Models\PermintaanLaporan::STATUS_PEMERIKSAAN) {
      $plStatus = 'Menunggu'; $plStatusClass = 'blue';
  } elseif ($pl->status === \App\Models\PermintaanLaporan::STATUS_SELESAI) {
      $plHasilAkhir = strtolower($pl->laporan?->status ?? '');
      if (str_contains($plHasilAkhir, 'tolak')) { $plStatus = 'Ditolak'; $plStatusClass = 'bad'; }
      else { $plStatus = 'Disetujui'; $plStatusClass = 'ok'; }
  } elseif ($pl->isSedangRevisi()) { $plStatus = 'Revisi'; $plStatusClass = 'revisi'; }
  elseif ($pl->isTerlambat()) { $plStatus = 'Terlambat'; $plStatusClass = 'bad'; }
  elseif ($pl->status === \App\Models\PermintaanLaporan::STATUS_BELUM) { $plStatus = 'Terbaru'; $plStatusClass = 'new'; }
  else { $plStatus = 'Sedang diproses'; $plStatusClass = 'wait'; }
  $plPrioClass = 'prio-'.strtolower($pl->prioritas);
  $plTasksTotal = $pl->tasks->count();
  $plTasksDone = $pl->tasks->where('selesai', true)->count();
  // Pill deadline & tombol Lihat Detail/Lihat Progres -- SAMA
  // PERSIS logikanya dengan permintaan-laporan-pimpinan-card.blade.php
  // (lihat catatan panjang di partial modalnya, ditaruh setelah
  // </script> panel Arsip Data).
  $plDeadlineHidden = !in_array($plStatus, ['Terbaru', 'Sedang diproses', 'Terlambat', 'Revisi'], true);
  $plDeadlineClass = $pl->isTerlambat() ? 'bad' : ($pl->deadline_at && $pl->deadline_at->diffInHours(now()) <= 24 ? 'near' : 'normal');
  $plCatatanPenolakan = trim((string) ($pl->laporans
      ->filter(fn ($l) => (str_contains(strtolower((string) $l->status), 'tolak') || str_contains(strtolower((string) $l->status), 'revisi')) && trim((string) $l->catatan) !== '')
      ->sortByDesc('id')
      ->first()?->catatan ?? ''));
  $plTasksJson = $pl->tasks->sortBy('urutan')->values()->map(function ($task) {
      $taskLaporan = $task->laporans->sortByDesc('id')->first();
      return [
          'deskripsi' => $task->deskripsi,
          'detail' => $task->detail,
          'selesai' => (bool) $task->selesai,
          'laporan' => $taskLaporan ? [
              'deskripsi' => $taskLaporan->deskripsi,
              'kendala' => $taskLaporan->kendala,
              'lampiran' => $taskLaporan->semuaLampiran->map(fn ($x) => ['url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values(),
          ] : null,
      ];
  })->values()->toJson();
@endphp
<article class="deadline-sender-item" data-realtime-permintaan-id="{{ $pl->id }}" data-status="{{ $plStatus }}" data-search="{{ strtolower($pl->perihal.' '.($pl->tujuanSatuan->nama ?? '')) }}" data-created-at="{{ $pl->created_at?->timestamp ?? 0 }}">
  <div class="dcard-head">
    <div class="dcard-icon {{ $plPrioClass }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="1"></rect><path d="m9 14 2 2 4-4"></path></svg>
    </div>
  </div>
  <div class="dcard-body">
    <div class="deadline-sender-title">{{ $pl->perihal }}</div>
    <span class="deadline-pill dcard-status-pill {{ $plStatusClass }}">{{ $plStatus }}</span>
    <span class="satuan-pill">{{ $pl->tujuanSatuan->kode ?? $pl->tujuanSatuan->nama ?? '-' }}</span>
  </div>
  <div class="dcard-progress">
    <div class="dcard-progress-head"><span class="dcard-progress-label">Progres</span><span class="dcard-progress-value">{{ $pl->progres }}%</span></div>
    <div class="dcard-progress-track"><div class="dcard-progress-fill" style="width:{{ min(100, max(0, (int) $pl->progres)) }}%"></div></div>
  </div>
  <div class="dcard-footer">
    <span class="dcard-tasks-summary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
      @if($plTasksTotal > 0)
        {{ $plTasksDone }}/{{ $plTasksTotal }} tugas selesai
      @else
        Prioritas {{ $pl->prioritas }}
      @endif
    </span>
    @unless($plDeadlineHidden)
    <span class="dcard-deadline-pill {{ $plDeadlineClass }}" title="{{ $pl->deadline_at?->translatedFormat('d M Y H:i') }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
      {{ $pl->deadline_at?->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE) ?? '-' }}
    </span>
    @endunless
  </div>
  <div class="dcard-status-area">
    <div class="deadline-actions">
      <button type="button" class="deadline-secondary small" onclick="openPermintaanDetailModal(this)"
        data-tujuan="{{ e($pl->tujuanSatuan->nama ?? '-') }}"
        data-tujuan-kode="{{ e($pl->tujuanSatuan->kode ?? '') }}"
        data-deadline="{{ e($pl->deadline_at?->translatedFormat('d M Y H:i')) }}"
        data-perihal="{{ e($pl->perihal) }}"
        data-kategori="{{ e($pl->kategori ?: '-') }}"
        data-prioritas="{{ e($pl->prioritas) }}"
        data-status="{{ $plStatus }}"
        data-status-class="{{ $plStatusClass }}"
        data-instruksi="{{ e($pl->instruksi ?? '') }}"
        data-catatan="{{ e($plCatatanPenolakan) }}"
      >Lihat Detail</button>
      <button type="button" class="deadline-primary small" onclick="openPimpinanProgres(this)"
        data-perihal="{{ e($pl->perihal) }}"
        data-tasks="{{ $plTasksJson }}"
        data-status="{{ $plStatus }}"
        data-riwayat="1"
        data-permintaan-id="{{ $pl->id }}"
      >Lihat Progres</button>
    </div>
  </div>
</article>
