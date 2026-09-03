{{-- CARD: Kendala Terkirim (sisi pengirim / Kasansi) --}}
<div class="kcard" data-kendala-id="{{ $k->id }}" data-search="{{ strtolower($k->perihal.' '.($k->tujuanSatuan->nama ?? '')) }}" data-prioritas="{{ $k->prioritas }}">
  <div class="kcard-header">
    <div class="kcard-meta">
      <span class="priority-tag prio-{{ strtolower($k->prioritas) }}">{{ $k->prioritas }}</span>
      <span class="satuan-pill">{{ $k->tujuanSatuan->kode ?? $k->tujuanSatuan->nama ?? '-' }}</span>
    </div>
    <span class="kcard-status status-dot {{ in_array($k->status, ['Ditindaklanjuti','Selesai','Dikonfirmasi'], true) ? 'green' : ($k->status === 'Ditolak' ? 'bad' : 'amber') }}">{{ $k->status }}</span>
  </div>

  <div class="kcard-body">
    <div class="kcard-perihal">{{ $k->perihal }}</div>
    @if($k->kategori)
      <div class="kcard-kategori">{{ $k->kategori }}</div>
    @endif
  </div>

  @if(! $k->tembusans->isEmpty())
  <div class="kcard-tembusan">
    <span class="kcard-tembusan-label">Tembusan</span>
    @foreach($k->tembusans as $t)
      <div class="kcard-tembusan-item">
        <span class="satuan-pill" style="font-size:10px">{{ $t->satuan->kode ?? $t->satuan->nama ?? '-' }}</span>
        @if($t->feedback)
          <span class="kcard-tembusan-status replied">Sudah dibalas</span>
        @else
          <span class="kcard-tembusan-status waiting">Menunggu…</span>
        @endif
      </div>
    @endforeach
  </div>
  @endif

  <div class="kcard-footer">
    <span class="kcard-date">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
      {{ $k->created_at->translatedFormat('d M Y, H:i') }}
    </span>
    <div class="kcard-actions">
      <button type="button" class="kcard-btn kcard-btn-detail" onclick="openReportDetail(this)"
        data-pengirim="{{ e($satuan->nama) }}"
        data-tujuan="{{ e($k->tujuanSatuan->nama ?? '-') }}"
        data-perihal="{{ e($k->perihal) }}"
        data-prioritas="{{ e($k->prioritas) }}"
        data-proyek="{{ e($k->kategori ?? '-') }}"
        data-tanggal="{{ e($k->created_at->translatedFormat('d M Y H:i')) }}"
        data-deskripsi="{{ e($k->deskripsi) }}"
        data-deskripsi-label="Isi Balasan"
        data-kendala="{{ e($k->catatan ?? '') }}"
        data-lampiran="{{ $k->lampiran_path ? collect([['url' => asset('storage/'.$k->lampiran_path), 'nama' => basename($k->lampiran_path)]])->toJson() : '[]' }}"
        data-tembusan-balasan="{{ $k->tembusans->map(fn($t) => ['satuan' => $t->satuan->nama ?? $t->satuan->kode ?? '-', 'feedback' => $t->feedback])->values()->toJson() }}"
        data-kendala-report="1"
        data-readonly="1"
        data-readonly-text="Laporan kendala ini sudah Anda kirim — status saat ini: {{ $k->status }}.">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Detail
      </button>
      @if($k->status === \App\Models\LaporanKendala::STATUS_MENUNGGU_TEMBUSAN)
        @if($k->siapDiteruskan())
          <button type="button" class="kcard-btn kcard-btn-approve"
            onclick="bukaKonfirmasiTeruskan('{{ route('laporan-kendala.teruskan', $k) }}','{{ csrf_token() }}','{{ e($k->perihal) }}')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9 22 2Z"/></svg>
            Kirim ke Danpus
          </button>
        @endif
      @endif
    </div>
  </div>
</div>
