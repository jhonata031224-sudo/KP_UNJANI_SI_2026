{{-- CARD: Tembusan Kendala (sisi penerima tembusan / 4 Satlak & 4 Sdir) --}}
@php
  $statusBadgeClass  = $t->dibaca_at ? 'status-dikonfirmasi' : 'status-menunggu';
  $sudahFeedbackTeks = filled($t->feedback);
  $sudahDokBalasan   = filled($t->dokumen_balasan_path);
  $sudahMembalas     = $t->sudahMembalas();
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
    <span class="kcard-tembusan-label">Balasan ke Kasansi</span>
    {{-- Status balasan teks --}}
    <div class="kcard-tembusan-item">
      <span style="font-size:10px;color:var(--text-muted);font-weight:600">Teks</span>
      @if($sudahFeedbackTeks)
        <span class="kcard-tembusan-status replied">Terkirim</span>
      @else
        <span class="kcard-tembusan-status waiting">Belum</span>
      @endif
    </div>
    {{-- Status dokumen balasan --}}
    <div class="kcard-tembusan-item">
      <span style="font-size:10px;color:var(--text-muted);font-weight:600">Dokumen</span>
      @if($sudahDokBalasan)
        <span class="kcard-tembusan-status replied">{{ $t->dokumen_balasan_nama }}</span>
      @else
        <span class="kcard-tembusan-status waiting">Belum dikirim</span>
      @endif
    </div>
  </div>

  <div class="kcard-footer">
    <span class="kcard-date">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
      {{ $t->laporanKendala->created_at->translatedFormat('d M Y H:i') }}
    </span>
    <div class="kcard-actions" style="flex-wrap:wrap;gap:6px">
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
        data-readonly-text="Tembusan kendala dari {{ e($t->laporanKendala->satuan->nama ?? '-') }}. Kirim balasan teks dan/atau dokumen kepada Kasansi.">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Lihat &amp; Balas
      </button>

      {{-- Tombol tandai dibaca --}}
      @if(! $t->dibaca_at)
        <form method="POST" action="{{ route('laporan-kendala-tembusan.baca', $t->id) }}" style="display:inline-flex">
          @csrf @method('PATCH')
          <button type="submit" class="kcard-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Tandai Dibaca
          </button>
        </form>
      @endif

      {{-- Tombol kirim dokumen balasan (upload file langsung dari card) --}}
      @if(! $sudahDokBalasan)
        <form method="POST" action="{{ route('laporan-kendala-tembusan.dokumen-balasan', $t->id) }}"
              enctype="multipart/form-data" style="display:inline-flex">
          @csrf
          <label class="kcard-btn kcard-btn-approve" style="cursor:pointer" title="Kirim dokumen balasan ke Kasansi">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 12v6"/><path d="M9 15h6"/></svg>
            Kirim Dokumen
            <input type="file" name="dokumen_balasan" style="display:none" onchange="this.closest('form').submit()">
          </label>
        </form>
      @else
        {{-- Sudah ada dokumen → tombol ganti --}}
        <form method="POST" action="{{ route('laporan-kendala-tembusan.dokumen-balasan', $t->id) }}"
              enctype="multipart/form-data" style="display:inline-flex">
          @csrf
          <label class="kcard-btn" style="cursor:pointer;background:var(--panel-alt);border:1px solid var(--border)" title="Ganti dokumen balasan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Ganti Dok.
            <input type="file" name="dokumen_balasan" style="display:none" onchange="this.closest('form').submit()">
          </label>
        </form>
      @endif
    </div>
  </div>
</div>
