{{-- CARD: Kendala Masuk (sisi penerima / Danpus & Wadan) --}}
@php
  // Alur: Menunggu Konfirmasi -> Dikonfirmasi. SATU-SATUNYA aksi adalah
  // Konfirmasi, dan hanya untuk Danpus. Wadan hanya melihat (tanpa aksi).
  // Aturan ini ditegakkan lagi di backend (LaporanKendalaController::konfirmasi).
  $isDanpus        = strtoupper($satuan->kode ?? '') === 'DANPUS';
  $sudahKonfirmasi = $k->sudahDikonfirmasi();
  $bisaKonfirmasi  = $isDanpus && ! $sudahKonfirmasi;
  $labelStatus     = $sudahKonfirmasi ? \App\Models\LaporanKendala::STATUS_DIKONFIRMASI : $k->status;
  $statusClass     = $sudahKonfirmasi ? 'ok' : 'wait';
@endphp
<div class="kcard" data-kendala-id="{{ $k->id }}" data-search="{{ strtolower(($k->satuan->nama ?? '').' '.$k->perihal) }}" data-prioritas="{{ $k->prioritas }}">
  <div class="kcard-header">
    <div class="kcard-meta">
      <span class="satuan-pill">{{ $k->satuan->kode ?? $k->satuan->nama ?? '-' }}</span>
    </div>
    <span class="status-pill {{ $statusClass }}">{{ $labelStatus }}</span>
  </div>

  <div class="kcard-body">
    <div class="kcard-body-row">
      <span class="kcard-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </span>
      <div class="kcard-perihal">{{ $k->perihal }}</div>
    </div>
  </div>

  <div class="kcard-footer">
    <div class="kcard-actions">
      <button type="button" class="kcard-btn kcard-btn-detail" onclick="openReportDetail(this)"
        data-pengirim="{{ e($k->satuan->nama ?? '-') }}"
        data-tujuan="{{ e($satuan->nama) }}"
        data-perihal="{{ e($k->perihal) }}"
        data-prioritas="{{ e($k->prioritas) }}"
        data-proyek="{{ e($k->kategori ?? '-') }}"
        data-tanggal="{{ e($k->created_at->translatedFormat('d M Y H:i')) }}"
        data-deskripsi="{{ e($k->deskripsi) }}"
        data-kendala="{{ e($k->catatan ?? '') }}"
        data-lampiran="{{ $k->semuaLampiran->map(fn($x) => ['url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values()->toJson() }}"
        data-kendala-report="1"
        data-readonly="{{ $bisaKonfirmasi ? '0' : '1' }}"
        @if($bisaKonfirmasi) data-konfirmasi-kendala-action="{{ route('laporan-kendala.konfirmasi', $k) }}" @endif
        @if(! $bisaKonfirmasi) data-readonly-text="Status: {{ $labelStatus }}." @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Lihat Detail
      </button>

      @if($bisaKonfirmasi)
        <button type="button" class="kcard-btn kcard-btn-archive confirm-archive"
          onclick="bukaKonfirmasiArsipkanKendala(this)"
          data-action="{{ route('laporan-kendala.konfirmasi', $k) }}"
          data-perihal="{{ e($k->perihal) }}"
          title="Konfirmasi penerimaan laporan kendala ini">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Konfirmasi
        </button>
      @endif
    </div>
  </div>
</div>
