{{-- CARD: Kendala Masuk (sisi penerima / Danpus & Wadan) --}}
@php
  $kendalaAdaAksi = in_array($k->status, ['Menunggu','Ditindaklanjuti'], true) || strtoupper($satuan->kode ?? '') === 'DANPUS';
  $isDanpus = strtoupper($satuan->kode ?? '') === 'DANPUS';
  $statusClass = in_array($k->status, ['Ditindaklanjuti','Selesai'], true) ? 'ok' : ($k->status === 'Ditolak' ? 'bad' : 'wait');
@endphp
<div class="kcard" data-kendala-id="{{ $k->id }}" data-search="{{ strtolower(($k->satuan->nama ?? '').' '.$k->perihal) }}" data-prioritas="{{ $k->prioritas }}">
  <div class="kcard-header">
    <div class="kcard-meta">
      <span class="satuan-pill">{{ $k->satuan->kode ?? $k->satuan->nama ?? '-' }}</span>
    </div>
    <span class="status-pill {{ $statusClass }}">{{ $k->status }}</span>
  </div>

  <div class="kcard-body">
    <div class="kcard-perihal">{{ $k->perihal }}</div>
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
        data-lampiran="{{ $k->lampiran_path ? collect([['url' => asset('storage/'.$k->lampiran_path), 'nama' => basename($k->lampiran_path)]])->toJson() : '[]' }}"
        data-kendala-report="1"
        data-readonly="{{ $kendalaAdaAksi ? '0' : '1' }}"
        @if(! $kendalaAdaAksi) data-readonly-text="Kendala ini sudah ditindaklanjuti — status: {{ $k->status }}." @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Detail
      </button>

      @if($k->status === 'Menunggu' && ! $isDanpus)
        <form method="POST" action="{{ route('laporan-kendala.status', $k) }}" style="display:inline-flex">
          @csrf @method('PATCH')
          <input type="hidden" name="status" value="Ditindaklanjuti">
          <button class="kcard-btn kcard-btn-approve" type="submit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Tindak Lanjuti
          </button>
        </form>
        <form method="POST" action="{{ route('laporan-kendala.status', $k) }}" style="display:inline-flex">
          @csrf @method('PATCH')
          <input type="hidden" name="status" value="Ditolak">
          <button class="kcard-btn kcard-btn-reject" type="submit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            Tolak
          </button>
        </form>
      @elseif($k->status === 'Ditindaklanjuti')
        <form method="POST" action="{{ route('laporan-kendala.status', $k) }}" style="display:inline-flex">
          @csrf @method('PATCH')
          <input type="hidden" name="status" value="Selesai">
          <button class="kcard-btn kcard-btn-approve" type="submit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Tandai Selesai
          </button>
        </form>
      @endif

      @if($isDanpus)
        <form method="POST" action="{{ route('laporan-kendala.status', $k) }}" data-perihal="{{ e($k->perihal) }}" style="display:inline-flex">
          <input type="hidden" name="status" value="Dikonfirmasi">
          @csrf @method('PATCH')
          <button class="kcard-btn kcard-btn-archive confirm-archive" type="submit" title="Konfirmasi dan pindahkan ke Arsip Kendala Kasansi">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M23 3H1v5h22V3z"/><path d="M10 12h4"/></svg>
            Konfirmasi & Arsipkan
          </button>
        </form>
      @endif
    </div>
  </div>
</div>
