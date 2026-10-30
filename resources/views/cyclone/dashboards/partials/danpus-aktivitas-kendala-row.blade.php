{{--
  Baris Kendala pada Riwayat Aktivitas DANPUS (tab "Kendala" per satuan) --
  daftar ringkas yang bisa dibuka, SAMA modelnya dengan baris Surat
  (danpus-aktivitas-surat-row) & Laporan: chevron + judul perihal, isinya
  alur singkat (Dilaporkan -> Dikonfirmasi) + tombol Lihat Detail.
  View-only; alur kendala tetap Menunggu Konfirmasi -> Dikonfirmasi, aksi
  Konfirmasi hanya ada di menu Kendala Kasansi.
  Params: $k (LaporanKendala), $namaDanpus, $m (satuan: nama)
--}}
@php
    $sudahKonfirmasi = $k->sudahDikonfirmasi();
    $labelStatus = $sudahKonfirmasi ? \App\Models\LaporanKendala::STATUS_DIKONFIRMASI : $k->status;
    $dilaporkan = $k->created_at->translatedFormat('d M Y H:i');
    $dikonfirmasiTgl = ($sudahKonfirmasi && $k->confirmed_at) ? $k->confirmed_at->translatedFormat('d M Y H:i') : '';
@endphp
<details class="danpus-report-dropdown akt-srow akt-krow"
     data-kendala-id="{{ $k->id }}"
     data-created-at="{{ $k->created_at->timestamp }}"
     data-search="{{ strtolower(($k->satuan->nama ?? '').' '.$k->perihal.' '.($k->kategori ?? '')) }}"
     data-status="{{ $sudahKonfirmasi ? 'dikonfirmasi' : 'menunggu' }}">
  <summary>
    <div class="danpus-report-summary-main">
      <span class="danpus-report-chevron" aria-hidden="true"></span>
      <span class="akt-srow-main">
        <span class="danpus-report-subject">{{ $k->perihal }}</span>
      </span>
    </div>
  </summary>

  <div class="danpus-report-content">
    <div class="danpus-activity-log" aria-label="Alur kendala {{ $k->perihal }}">
      <article class="danpus-activity-item is-done">
        <div class="danpus-activity-dot"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg></div>
        <div class="danpus-activity-line"></div>
        <div class="danpus-activity-card">
          <div class="danpus-activity-head">
            <div class="danpus-activity-stage">Kendala Dilaporkan</div>
            <div class="danpus-activity-date">{{ $dilaporkan }}</div>
          </div>
          <div class="danpus-activity-description akt-step-route">{{ $k->satuan->nama ?? '-' }} &rarr; {{ $namaDanpus }}</div>
          @if($k->kategori)<div class="danpus-activity-description"><b>Kategori:</b> {{ $k->kategori }}</div>@endif
          <span class="danpus-activity-state">Selesai</span>
        </div>
      </article>
      @if($sudahKonfirmasi)
      <article class="danpus-activity-item is-done">
        <div class="danpus-activity-dot"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg></div>
        <div class="danpus-activity-card">
          <div class="danpus-activity-head">
            <div class="danpus-activity-stage">Dikonfirmasi</div>
            @if($dikonfirmasiTgl !== '')<div class="danpus-activity-date">{{ $dikonfirmasiTgl }}</div>@endif
          </div>
          @if($k->confirmedBy)<div class="danpus-activity-description akt-step-route">{{ $k->confirmedBy->name }}</div>@endif
          <span class="danpus-activity-state">Selesai</span>
        </div>
      </article>
      @else
      <article class="danpus-activity-item is-current is-menunggu">
        <div class="danpus-activity-dot"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg></div>
        <div class="danpus-activity-card">
          <div class="danpus-activity-head">
            <div class="danpus-activity-stage">Menunggu konfirmasi {{ $namaDanpus }}</div>
          </div>
          <span class="danpus-activity-state">Menunggu</span>
        </div>
      </article>
      @endif
    </div>

    <div class="akt-srow-actions">
      <button type="button" class="akt-btn" onclick="openReportDetail(this)"
        data-pengirim="{{ e($k->satuan->nama ?? '-') }}"
        data-tujuan="{{ e($namaDanpus) }}"
        data-perihal="{{ e($k->perihal) }}"
        data-prioritas="{{ e($k->prioritas) }}"
        data-proyek="{{ e($k->kategori ?? '-') }}"
        data-tanggal="{{ e($dilaporkan) }}"
        data-deskripsi="{{ e($k->deskripsi) }}"
        data-kendala="{{ e($k->catatan ?? '') }}"
        data-lampiran="{{ $k->semuaLampiran->map(fn($x) => ['url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values()->toJson() }}"
        data-kendala-report="1"
        data-readonly="1"
        data-readonly-text="Status: {{ $labelStatus }}.">Lihat Detail</button>
      @if(! $sudahKonfirmasi)<a href="#kendala-kasansi" class="akt-link-tindak">Tindak lanjuti &rarr;</a>@endif
    </div>
  </div>
</details>
