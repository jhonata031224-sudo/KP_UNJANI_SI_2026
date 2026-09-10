{{-- Rincian bawah donut "Distribusi Status Laporan" (dot+nama+jumlah+bar+persen).
     Partial terpisah dari canvas donut-nya sendiri -- donut di-refresh lewat
     JS (window.siberadRefreshStatusDonut, destroy+recreate Chart.js instance)
     bukan lewat swap HTML, tapi list rincian ini murni HTML jadi aman
     di-swap innerHTML biasa lewat poll realtime.
     Variabel yang WAJIB dikirim caller: $pimpStatusDist (array 4 baris,
     masing-masing ['label','color','labelColor','count']). --}}
@php
  $pimpTotalStatus = collect($pimpStatusDist)->sum('count');
  $pimpPersen = fn ($n) => $pimpTotalStatus > 0 ? round($n / $pimpTotalStatus * 100) : 0;
@endphp
<div class="status-bd">@foreach($pimpStatusDist as $sd)<div class="status-bd-row{{ $sd['count'] === 0 ? ' is-zero' : '' }}"><span class="status-bd-dot" style="background:{{ $sd['color'] }}"></span><span class="status-bd-name">{{ $sd['label'] }}</span><span class="status-bd-count">{{ $sd['count'] }}</span><span class="status-bd-bar"><span class="status-bd-bar-fill" style="width:{{ $pimpPersen($sd['count']) }}%;background:{{ $sd['color'] }}"></span></span><span class="status-bd-pct" style="color:{{ $sd['labelColor'] }};background:color-mix(in srgb,{{ $sd['color'] }} 15%,transparent)">{{ $pimpPersen($sd['count']) }}%</span></div>@endforeach</div>
