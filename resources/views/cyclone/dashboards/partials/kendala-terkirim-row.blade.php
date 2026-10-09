{{-- CARD: Kendala Terkirim (sisi pengirim / Kasansi) --}}
@php
  // Alur: Menunggu Konfirmasi -> Dikonfirmasi (selesai). Kasansi hanya
  // memantau status; tidak ada aksi lanjutan di sisi pengirim.
  $sudahKonfirmasi  = $k->sudahDikonfirmasi();
  $labelStatus      = $sudahKonfirmasi ? \App\Models\LaporanKendala::STATUS_DIKONFIRMASI : $k->status;
  $statusBadgeClass = $sudahKonfirmasi ? 'status-dikonfirmasi' : 'status-menunggu';
  $infoKonfirmasi   = $sudahKonfirmasi
      ? 'Dikonfirmasi Danpus'.($k->confirmed_at ? ' · '.$k->confirmed_at->translatedFormat('d M Y H:i') : '')
      : 'Terkirim ke Danpus · menunggu konfirmasi';
@endphp
<div class="kcard" data-kendala-id="{{ $k->id }}" data-search="{{ strtolower($k->perihal.' '.($k->tujuanSatuan->nama ?? '')) }}" data-prioritas="{{ $k->prioritas }}">
  <div class="kcard-header">
    <div class="kcard-meta">
      <span class="satuan-pill">{{ $k->tujuanSatuan->kode ?? $k->tujuanSatuan->nama ?? '-' }}</span>
    </div>
    <span class="kcard-status status-badge {{ $statusBadgeClass }}">{{ $labelStatus }}</span>
  </div>

  <div class="kcard-body">
    <div class="kcard-body-row">
      <span class="kcard-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </span>
      <div class="kcard-perihal">{{ $k->perihal }}</div>
    </div>
  </div>

  <div class="kcard-info">
    <span class="kcard-info-label">Status Laporan</span>
    <span class="kcard-info-status {{ $sudahKonfirmasi ? 'done' : 'waiting' }}">{{ $infoKonfirmasi }}</span>
  </div>

  <div class="kcard-footer">
    <div class="kcard-actions" style="flex-wrap:wrap;gap:6px">
      <button type="button" class="kcard-btn kcard-btn-detail" onclick="openReportDetail(this)"
        data-pengirim="{{ e($satuan->nama) }}"
        data-tujuan="{{ e($k->tujuanSatuan->nama ?? '-') }}"
        data-perihal="{{ e($k->perihal) }}"
        data-prioritas="{{ e($k->prioritas) }}"
        data-proyek="{{ e($k->kategori ?? '-') }}"
        data-tanggal="{{ e($k->created_at->translatedFormat('d M Y H:i')) }}"
        data-deskripsi="{{ e($k->deskripsi) }}"
        data-deskripsi-label="Isi Laporan"
        data-kendala="{{ e($k->catatan ?? '') }}"
        data-lampiran="{{ $k->semuaLampiran->map(fn($x) => ['url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values()->toJson() }}"
        data-kendala-report="1"
        data-readonly="1"
        data-readonly-text="Status saat ini: {{ $labelStatus }}.">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Lihat Detail
      </button>
    </div>
  </div>
</div>
