{{--
  Kartu Kendala pada Riwayat Aktivitas DANPUS (tab "Kendala" per satuan) --
  view-only. Alur kendala tetap: Menunggu Konfirmasi -> Dikonfirmasi; aksi
  Konfirmasi hanya ada di menu Kendala Kasansi.
  Params: $k (LaporanKendala), $namaDanpus
--}}
@php
    $sudahKonfirmasi = $k->sudahDikonfirmasi();
    $labelStatus = $sudahKonfirmasi ? \App\Models\LaporanKendala::STATUS_DIKONFIRMASI : $k->status;
    $statusClass = $sudahKonfirmasi ? 'ok' : 'wait';
@endphp
<div class="kcard" data-kendala-id="{{ $k->id }}"
     data-created-at="{{ $k->created_at->timestamp }}"
     data-search="{{ strtolower(($k->satuan->nama ?? '').' '.$k->perihal.' '.($k->kategori ?? '')) }}"
     data-status="{{ $sudahKonfirmasi ? 'dikonfirmasi' : 'menunggu' }}">
  <div class="kcard-header">
    <div class="kcard-meta"><span class="satuan-pill">{{ $k->satuan->kode ?? $k->satuan->nama ?? '-' }}</span></div>
    <span class="status-pill {{ $statusClass }}">{{ $labelStatus }}</span>
  </div>
  <div class="kcard-body">
    <div class="kcard-body-row">
      <span class="kcard-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
      <div class="kcard-perihal">{{ $k->perihal }}</div>
    </div>
    <div class="akt-kcard-info">
      <span>Dilaporkan {{ $k->created_at->translatedFormat('d M Y H:i') }}</span>
      @if($k->kategori)<span>&middot; {{ $k->kategori }}</span>@endif
      @if($sudahKonfirmasi && $k->confirmed_at)
        <span>&middot; Dikonfirmasi {{ $k->confirmed_at->translatedFormat('d M Y H:i') }}@if($k->confirmedBy) oleh {{ $k->confirmedBy->name }}@endif</span>
      @endif
    </div>
  </div>
  <div class="kcard-footer">
    <div class="kcard-actions">
      <button type="button" class="kcard-btn kcard-btn-detail" onclick="openReportDetail(this)"
        data-pengirim="{{ e($k->satuan->nama ?? '-') }}"
        data-tujuan="{{ e($namaDanpus) }}"
        data-perihal="{{ e($k->perihal) }}"
        data-prioritas="{{ e($k->prioritas) }}"
        data-proyek="{{ e($k->kategori ?? '-') }}"
        data-tanggal="{{ e($k->created_at->translatedFormat('d M Y H:i')) }}"
        data-deskripsi="{{ e($k->deskripsi) }}"
        data-kendala="{{ e($k->catatan ?? '') }}"
        data-lampiran="{{ $k->semuaLampiran->map(fn($x) => ['url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values()->toJson() }}"
        data-kendala-report="1"
        data-readonly="1"
        data-readonly-text="Status: {{ $labelStatus }}.">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Lihat Detail
      </button>
      @if(! $sudahKonfirmasi)
        <a href="#kendala-kasansi" class="akt-link-tindak">Tindak lanjuti &rarr;</a>
      @endif
    </div>
  </div>
</div>
