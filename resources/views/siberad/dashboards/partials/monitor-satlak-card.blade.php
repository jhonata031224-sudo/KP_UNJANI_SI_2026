@php
  $statusLower = strtolower($l->status ?? '');
  $isProgres   = $l->status === \App\Models\Laporan::STATUS_PROGRES;
  $isDitolak   = str_contains($statusLower, 'tolak');
  $isDisetujui = ! $isDitolak && (str_contains($statusLower, 'setuju') || str_contains($statusLower, 'diterima'));
  $isRevisi    = ! $isDitolak && ! $isDisetujui && str_contains($statusLower, 'revisi');

  $statusCat = $isProgres ? 'progres' : ($isDitolak ? 'ditolak' : ($isDisetujui ? 'disetujui' : ($isRevisi ? 'revisi' : 'menunggu')));
  $statusBadgeClass = [
    'progres'   => 'status-progres',
    'ditolak'   => 'status-ditolak',
    'disetujui' => 'status-dikonfirmasi',
    'revisi'    => 'status-menunggu',
    'menunggu'  => 'status-menunggu',
  ][$statusCat];
  $statusLabel = $isProgres ? 'Progres · '.(int) $l->progres.'%' : ($l->status ?: '-');

  $prioLower = strtolower($l->prioritas ?? '');
  $prioClass = $prioLower === 'tinggi' ? 'prio-tinggi' : ($prioLower === 'sedang' ? 'prio-sedang' : 'prio-rendah');

  $progresVal = (int) ($l->progres ?? 0);
  $progresVal = max(0, min(100, $progresVal));
  $progresFillColor = [
    'progres'   => 'var(--p-orange)',
    'ditolak'   => 'var(--red)',
    'disetujui' => 'var(--success-bright,#3dba7e)',
    'revisi'    => 'var(--amber)',
    'menunggu'  => 'var(--amber)',
  ][$statusCat];
@endphp
<div class="kcard monitor-satlak-card"
     data-laporan-id="{{ $l->id }}"
     data-search="{{ strtolower(($l->satuan->nama ?? '').' '.$l->perihal) }}"
     data-prioritas="{{ $l->prioritas }}"
     data-status-cat="{{ $statusCat }}">
  <div class="kcard-header">
    <div class="kcard-perihal">{{ $l->perihal }}</div>
    <span class="priority-tag {{ $prioClass }}">{{ $l->prioritas ?: '-' }}</span>
  </div>

  <div class="kcard-body monitor-satlak-meta">
    <div class="monitor-meta-row">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/></svg>
      <span>Proyek: <strong>{{ $l->proyek ?: '—' }}</strong></span>
    </div>
    <div class="monitor-meta-row">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9 22 2Z"/></svg>
      <span>Tujuan: <strong>{{ $l->tujuanSatuan->nama ?? '-' }}</strong></span>
    </div>
  </div>

  <div class="dcard-progress">
    <div class="dcard-progress-head">
      <span class="dcard-progress-label">Progres</span>
      <span class="dcard-progress-value">{{ $progresVal }}%</span>
    </div>
    <div class="dcard-progress-track"><div class="dcard-progress-fill" style="width:{{ $progresVal }}%;background:{{ $progresFillColor }}"></div></div>
  </div>

  <div class="kcard-footer">
    <div class="monitor-card-footer-meta">
      <span class="status-badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
      <span class="monitor-card-date">{{ $l->created_at->translatedFormat('d M Y H:i') }}</span>
    </div>
    <button type="button" class="kcard-btn kcard-btn-detail" onclick="openReportDetail(this)"
      data-pengirim="{{ e($l->satuan->nama ?? '-') }}"
      data-tujuan="{{ e($l->tujuanSatuan->nama ?? '-') }}"
      data-perihal="{{ e($l->perihal) }}"
      data-prioritas="{{ e($l->prioritas) }}"
      data-progres="{{ $l->progres }}"
      data-kendala="{{ e($l->kendala ?? '') }}"
      data-proyek="{{ e($l->proyek ?? '-') }}"
      data-tanggal="{{ e($l->created_at->translatedFormat('d M Y H:i')) }}"
      data-deskripsi="{{ e($l->deskripsi) }}"
      data-lampiran="{{ $l->semuaLampiran->map(fn($x) => ['url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values()->toJson() }}"
      data-readonly="1"
      data-readonly-text="Mode pemantauan Duktek — detail ini hanya untuk melihat aktivitas laporan.">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
      Detail
    </button>
  </div>
</div>
