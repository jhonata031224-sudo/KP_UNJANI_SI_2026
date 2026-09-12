{{--
  Section Monitoring per-Satlak (card-based) untuk mode Duktek.
  Params wajib:
    $sectionId  : id anchor tab, mis. 'monitoring-kal'
    $prefix     : suffix unik utk id elemen filter, mis. 'kal'
    $judul      : judul section, mis. 'Satlak Kal (Penangkalan)'
    $deskripsi  : deskripsi singkat di bawah judul
    $namaSatlak : nama satlak utk teks banner mode pemantauan
    $laporan    : koleksi Laporan milik satlak ini
    $stat       : array ['total'=>, 'menunggu'=>, 'progres'=>, 'prioritas_tinggi'=>]
    $emptyTitle : judul empty-state saat literally belum ada laporan
    $emptySub   : sub empty-state
--}}
<section id="{{ $sectionId }}" class="tab-panel">
  <div class="section-head panel">
    <h2>{{ $judul }}</h2>
    <p>{{ $deskripsi }}</p>
  </div>

  <div class="monitor-kpi-row">
    <div class="panel monitor-kpi">
      <span class="monitor-kpi-label">Total Laporan</span>
      <span class="monitor-kpi-value">{{ $stat['total'] }}</span>
    </div>
    <div class="panel monitor-kpi">
      <span class="monitor-kpi-label">Menunggu Persetujuan Danpus</span>
      <span class="monitor-kpi-value tone-amber">{{ $stat['menunggu'] }}</span>
    </div>
    <div class="panel monitor-kpi">
      <span class="monitor-kpi-label">Progres</span>
      <span class="monitor-kpi-value tone-blue">{{ $stat['progres'] }}</span>
    </div>
    <div class="panel monitor-kpi monitor-kpi-prio">
      <span class="monitor-kpi-label">Prioritas</span>
      <div class="monitor-kpi-prio-row">
        <span class="monitor-kpi-prio-item prio-tinggi"><span class="monitor-kpi-prio-dot"></span>Tinggi <b>{{ $stat['prioritas_tinggi'] }}</b></span>
        <span class="monitor-kpi-prio-item prio-sedang"><span class="monitor-kpi-prio-dot"></span>Sedang <b>{{ $stat['prioritas_sedang'] }}</b></span>
        <span class="monitor-kpi-prio-item prio-rendah"><span class="monitor-kpi-prio-dot"></span>Rendah <b>{{ $stat['prioritas_rendah'] }}</b></span>
      </div>
    </div>
  </div>

  <div class="monitor-mode-banner">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
    <span>Mode pemantauan Duktek hanya dapat melihat aktivitas laporan {{ $namaSatlak }}.</span>
  </div>

  @if($laporan->isNotEmpty())
  <div class="monitor-toolbar">
    <div class="monitor-search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
      <input type="search" id="monitorSearch-{{ $prefix }}" placeholder="Cari perihal atau satuan tujuan..." aria-label="Cari perihal atau satuan tujuan..." autocomplete="off">
    </div>
    <select id="monitorStatus-{{ $prefix }}" aria-label="Filter status">
      <option value="">Semua status</option>
      <option value="menunggu">Menunggu</option>
      <option value="progres">Progres</option>
      <option value="revisi">Revisi</option>
      <option value="disetujui">Disetujui</option>
      <option value="ditolak">Ditolak</option>
    </select>
    <select id="monitorPrioritas-{{ $prefix }}" aria-label="Filter prioritas">
      <option value="">Semua prioritas</option>
      <option value="Tinggi">Tinggi</option>
      <option value="Sedang">Sedang</option>
      <option value="Rendah">Rendah</option>
    </select>
  </div>
  <div class="monitor-count-text" id="monitorCount-{{ $prefix }}">Menampilkan {{ $laporan->count() }} dari {{ $laporan->count() }} laporan</div>
  @endif

  <div class="kcard-grid" id="monitorGrid-{{ $prefix }}">
    @forelse($laporan as $l)
      @include('siberad.dashboards.partials.monitor-satlak-card', ['l' => $l])
    @empty
      <div class="kcard-empty">
        <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg>
        <div class="kcard-empty-title">{{ $emptyTitle }}</div>
        <div class="kcard-empty-sub">{{ $emptySub }}</div>
      </div>
    @endforelse
  </div>

  <div class="kcard-empty" id="monitorNoMatch-{{ $prefix }}" style="display:none">
    <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
    <div class="kcard-empty-title">Tidak ada laporan yang cocok</div>
    <div class="kcard-empty-sub">Coba ubah kata kunci pencarian atau filter status/prioritas.</div>
  </div>
</section>
