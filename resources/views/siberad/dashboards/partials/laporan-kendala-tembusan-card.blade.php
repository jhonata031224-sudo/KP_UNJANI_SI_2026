{{-- CARD: Tembusan Kendala (sisi penerima tembusan / 4 Satlak & 4 Sdir) --}}
@php
  $statusBadgeClass = $t->dibaca_at ? 'status-dikonfirmasi' : 'status-menunggu';
  $balasanSudah = (bool) $t->feedback;
@endphp
<div class="kcard" data-tembusan-id="{{ $t->id }}" data-search="{{ strtolower($t->laporanKendala->perihal.' '.($t->laporanKendala->satuan->nama ?? '')) }}" data-prioritas="{{ $t->laporanKendala->prioritas }}">
  <div class="kcard-header">
    <div class="kcard-meta">
      <span class="satuan-pill">{{ $t->laporanKendala->satuan->kode ?? $t->laporanKendala->satuan->nama ?? '-' }}</span>
      <span class="priority-tag prio-{{ strtolower($t->laporanKendala->prioritas) }}">{{ $t->laporanKendala->prioritas }}</span>
    </div>
    <span class="kcard-status status-badge {{ $statusBadgeClass }}">{{ $t->dibaca_at ? 'Sudah dibaca' : 'Belum dibaca' }}</span>
  </div>

  <div class="kcard-body">
    <div class="kcard-body-row">
      <span class="kcard-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </span>
      <div class="kcard-perihal">{{ $t->laporanKendala->perihal }}</div>
    </div>
  </div>

  <div class="kcard-tembusan">
    <span class="kcard-tembusan-label">Balasan Anda</span>
    <div class="kcard-tembusan-item">
      @if($balasanSudah)
        <span class="kcard-tembusan-status replied">Sudah diberi</span>
      @else
        <span class="kcard-tembusan-status waiting">Menunggu…</span>
      @endif
    </div>
  </div>

  <div class="kcard-footer">
    <span class="kcard-date">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
      {{ $t->laporanKendala->created_at->translatedFormat('d M Y H:i') }}
    </span>
    <div class="kcard-actions">
      <button type="button" class="kcard-btn kcard-btn-detail" onclick="openReportDetail(this)"
        data-pengirim="{{ e($t->laporanKendala->satuan->nama ?? '-') }}"
        data-tujuan="DANPUS (tembusan ke {{ e($satuan->nama) }})"
        data-perihal="{{ e($t->laporanKendala->perihal) }}"
        data-prioritas="{{ e($t->laporanKendala->prioritas) }}"
        data-proyek="{{ e($t->laporanKendala->kategori ?? '-') }}"
        data-tanggal="{{ e($t->laporanKendala->created_at->translatedFormat('d M Y H:i')) }}"
        data-deskripsi="{{ e($t->laporanKendala->deskripsi) }}"
        data-lampiran="{{ $t->laporanKendala->semuaLampiran->map(fn($x) => ['url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values()->toJson() }}"
        data-kendala-report="1"
        data-tembusan-feedback="1"
        data-feedback-action="{{ route('laporan-kendala-tembusan.feedback', $t->id) }}"
        data-feedback-existing="{{ e($t->feedback ?? '') }}"
        data-csrf="{{ csrf_token() }}"
        data-readonly="1"
        data-readonly-text="Ini tembusan kendala (info/koordinasi) dari {{ e($t->laporanKendala->satuan->nama ?? '-') }} ke DANPUS — Kasansi menunggu feedback Anda sebelum meneruskannya.">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Lihat Detail
      </button>
      @if(! $t->dibaca_at)
        <form method="POST" action="{{ route('laporan-kendala-tembusan.baca', $t->id) }}" style="display:inline-flex">
          @csrf @method('PATCH')
          <button type="submit" class="kcard-btn kcard-btn-approve">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Tandai Dibaca
          </button>
        </form>
      @endif
    </div>
  </div>
</div>
