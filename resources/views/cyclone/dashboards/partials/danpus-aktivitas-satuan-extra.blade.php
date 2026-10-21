{{--
  Tambahan Riwayat Aktivitas DANPUS per satuan: tab Laporan | Surat | Kendala.
  Tab Laporan membungkus markup lama TANPA diubah (dirender di
  laporan-danpus.blade.php); file ini hanya menyediakan bilah tab ($part='tabs')
  dan panel Surat/Kendala ($part='views').

  Params: $part, $m (satuan: id,nama,kode), $aktivitasDanpus, $satuan (Danpus),
          $modulAktif, $laporanPimpinanSatlak
--}}
@php
    $sid = (int) $m['id'];
    $adaData = is_array($aktivitasDanpus ?? null) && isset($aktivitasDanpus['ringkasan'][$sid]);
    $tampilSurat = $adaData && ($modulAktif['surat'] ?? true);
    $tampilKendala = $adaData && ($modulAktif['kendala'] ?? true) && ($aktivitasDanpus['kendalaDidukung'][$sid] ?? false);
    $rk = $adaData ? $aktivitasDanpus['ringkasan'][$sid] : [];
    $lapRows = $laporanPimpinanSatlak->where('satuan_id', $sid);
@endphp
@if($adaData && ($tampilSurat || $tampilKendala))
@if($part === 'tabs')
<div class="akt-tabs" role="tablist" aria-label="Kategori aktivitas {{ $m['nama'] }}" data-akt-tabs="{{ $sid }}">
  <button type="button" class="akt-tab is-active" role="tab" aria-selected="true" data-akt-tab="laporan">Laporan <span class="akt-tab-count">{{ $lapRows->count() }}</span>@if(($m['menunggu'] ?? 0) > 0)<span class="akt-tab-alert" title="Laporan menunggu keputusan Danpus">{{ $m['menunggu'] }}</span>@endif</button>
  @if($tampilSurat)<button type="button" class="akt-tab" role="tab" aria-selected="false" data-akt-tab="surat">Surat <span class="akt-tab-count">{{ $rk['surat_total'] }}</span>@if($rk['surat_menunggu_danpus'] > 0)<span class="akt-tab-alert" title="Surat menunggu konfirmasi Danpus">{{ $rk['surat_menunggu_danpus'] }}</span>@endif</button>@endif
  @if($tampilKendala)<button type="button" class="akt-tab" role="tab" aria-selected="false" data-akt-tab="kendala">Kendala <span class="akt-tab-count">{{ $rk['kendala_total'] }}</span>@if($rk['kendala_menunggu'] > 0)<span class="akt-tab-alert" title="Kendala menunggu konfirmasi Danpus">{{ $rk['kendala_menunggu'] }}</span>@endif</button>@endif
</div>
@else
@if($tampilSurat)
@php $suratList = $aktivitasDanpus['surat'][$sid]; $statusSurat = $suratList->map(fn ($x) => $x->labelStatus($sid))->unique()->sort()->values(); @endphp
<div class="akt-view" data-akt-view="surat" hidden>
  <div class="akt-kpi-row">
    <div class="akt-kpi"><span>Total Surat</span><b>{{ $rk['surat_total'] }}</b></div>
    <div class="akt-kpi"><span>Surat Masuk</span><b class="tone-blue">{{ $rk['surat_masuk'] }}</b></div>
    <div class="akt-kpi"><span>Surat Keluar</span><b class="tone-amber">{{ $rk['surat_keluar'] }}</b></div>
    <div class="akt-kpi"><span>Menunggu Konfirmasi</span><b class="tone-amber">{{ $rk['surat_menunggu'] }}</b></div>
    <div class="akt-kpi"><span>Diarsipkan</span><b>{{ $rk['surat_arsip'] }}</b></div>
  </div>
  <p class="akt-note">Surat yang melibatkan {{ $m['nama'] }} (pengirim, penerima, atau tembusan). Klik satu baris untuk melihat alurnya. Hanya untuk dilihat; tindakan surat tetap lewat menu Surat.</p>
  @if($suratList->isNotEmpty())
  <div class="akt-toolbar" data-akt-toolbar>
    <div class="akt-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" data-akt-f="q" placeholder="Cari nomor, perihal, atau satuan..." aria-label="Cari surat" autocomplete="off"></div>
    <select data-akt-f="arah" aria-label="Filter arah surat"><option value="">Masuk &amp; keluar</option><option value="masuk">Surat masuk</option><option value="keluar">Surat keluar</option></select>
    <select data-akt-f="status" aria-label="Filter status surat"><option value="">Semua status</option>@foreach($statusSurat as $st)<option value="{{ $st }}">{{ $st }}</option>@endforeach</select>
    <select data-akt-f="arsip" aria-label="Filter arsip"><option value="">Berjalan &amp; arsip</option><option value="0">Masih berjalan</option><option value="1">Diarsipkan</option></select>
    <select data-akt-f="sort" aria-label="Urutkan surat"><option value="newest">Terbaru</option><option value="oldest">Terlama</option></select>
  </div>
  <div class="akt-count" data-akt-count data-akt-unit="surat">Menampilkan {{ $suratList->count() }} dari {{ $suratList->count() }} surat</div>
  @endif
  <div class="akt-list" data-akt-grid data-akt-card=".akt-srow">
    @forelse($suratList as $s)
      @include('cyclone.dashboards.partials.danpus-aktivitas-surat-row', ['s' => $s, 'sid' => $sid, 'danpusId' => $satuan->id, 'm' => $m])
    @empty
      <div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg><div class="empty-state-title">Belum ada aktivitas surat dari/untuk {{ $m['nama'] }}</div></div>
    @endforelse
  </div>
  <div class="empty-state akt-nomatch" data-akt-nomatch style="display:none"><div class="empty-state-title">Tidak ada surat yang cocok dengan pencarian/filter.</div></div>
</div>
@endif
@if($tampilKendala)
@php $kendalaList = $aktivitasDanpus['kendala'][$sid]; @endphp
<div class="akt-view" data-akt-view="kendala" hidden>
  <div class="akt-kpi-row">
    <div class="akt-kpi"><span>Total Kendala</span><b>{{ $rk['kendala_total'] }}</b></div>
    <div class="akt-kpi"><span>Menunggu Konfirmasi</span><b class="tone-amber">{{ $rk['kendala_menunggu'] }}</b></div>
    <div class="akt-kpi"><span>Dikonfirmasi</span><b class="tone-green">{{ $rk['kendala_dikonfirmasi'] }}</b></div>
  </div>
  <p class="akt-note">Laporan kendala {{ $m['nama'] }} ke Danpus. Konfirmasi dilakukan di menu Kendala Kasansi.</p>
  @if($kendalaList->isNotEmpty())
  <div class="akt-toolbar" data-akt-toolbar>
    <div class="akt-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" data-akt-f="q" placeholder="Cari perihal kendala..." aria-label="Cari kendala" autocomplete="off"></div>
    <select data-akt-f="status" aria-label="Filter status kendala"><option value="">Semua status</option><option value="menunggu">Menunggu Konfirmasi</option><option value="dikonfirmasi">Dikonfirmasi</option></select>
    <select data-akt-f="sort" aria-label="Urutkan kendala"><option value="newest">Terbaru</option><option value="oldest">Terlama</option></select>
  </div>
  <div class="akt-count" data-akt-count data-akt-unit="kendala">Menampilkan {{ $kendalaList->count() }} dari {{ $kendalaList->count() }} kendala</div>
  @endif
  <div class="kcard-grid" data-akt-grid data-akt-card=".kcard">
    @forelse($kendalaList as $k)
      @include('cyclone.dashboards.partials.danpus-aktivitas-kendala-card', ['k' => $k, 'namaDanpus' => $satuan->nama])
    @empty
      <div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg><div class="empty-state-title">Belum ada laporan kendala dari {{ $m['nama'] }}</div></div>
    @endforelse
  </div>
  <div class="empty-state akt-nomatch" data-akt-nomatch style="display:none"><div class="empty-state-title">Tidak ada kendala yang cocok dengan pencarian/filter.</div></div>
</div>
@endif
@endif
@endif
