{{-- 3 kartu KPI Beranda Pimpinan (Total Pelaporan/Surat/Kendala Kasansi).
     Partial terpisah supaya render awal (laporan-pimpinan.blade.php) dan poll
     realtime (DashboardController::pimpinanKpiRealtime) pakai SATU sumber
     markup+algoritma sparkline yang sama persis -- jangan duplikat manual di
     dua tempat, nanti gampang drift.
     Variabel yang WAJIB dikirim caller: $pimpTotalPelaporan (int, dihitung
     per-Perihal), $laporanPimpinanSatlak, $suratMasuk, $suratTerkirim,
     $suratArsip, $kendalaMasuk, $kendalaArsip. --}}
@php
  $pimpHari = collect(range(6,0))->map(fn($k)=>now()->startOfDay()->subDays($k));
  // Sparkline pojok kartu, dihitung PER HARI: tiap hari (dari 7 hari terakhir)
  // yang ada aktivitasnya menaikkan level sebesar 1/K (K = jumlah hari aktif),
  // datar di hari kosong. Jumlah "tanjakan" = jumlah hari yang ada laporan/
  // permintaan masuk -- gampang dihitung, maks 7, mustahil "rusak". 0 hari
  // aktif = garis datar di dasar. Digambar sebagai SATU kurva Catmull-Rom
  // (tangen tiap titik = rata-rata slope kiri-kanan, dibiarkan overshoot
  // dikit) yang lewat semua titik level harian sekaligus -- transisi
  // datar->naik jadi membulat kayak punuk bukit, bukan siku kotak (monotone
  // cubic sebelumnya maksa tangen 0 pas nyambung ke bagian datar, jadi malah
  // kelihatan patah).
  // $ampl (0..1) menyesuaikan tinggi puncak gunung relatif ke kartu paling
  // ramai minggu ini (dihitung di bawah dari $pimpKpi), biar kartu dengan
  // total lebih besar kelihatan jelas lebih "penuh" dari kartu kecil --
  // jumlah tanjakan tetap murni dari jumlah hari aktif, tidak berubah.
  $pimpSpark = function(array $s, float $ampl = 1.0){
    $w=120; $h=60; $top=$h*0.86*$ampl; $n=count($s);
    $k = count(array_filter($s, fn($v)=>$v>0));
    if($k===0) return 'M0 '.$h.' L'.$w.' '.$h.' L'.$w.' '.$h.' Z';
    $yy = fn($lvl)=>$h - $lvl*$top;
    // Titik pertama SENGAJA titik virtual "sebelum hari-0" di x=0/level dasar
    // -- dulu jaraknya ke hari-0 cuma 4 unit (sisa skema padding lama) padahal
    // jarak antar-hari lain ~18.67 unit, jadi walau kurvanya udah bagus,
    // naiknya keburu dipaksa mepet di ruang sempit itu (masih kelihatan
    // tegak). Sekarang SEMUA celah (termasuk dasar->hari-0) sama lebar --
    // 8 titik (1 virtual + 7 hari) merata dari x=0 s/d x=120.
    $seen=0; $xs=[0]; $ys=[$h];
    foreach($s as $i=>$v){
      $xs[] = ($i+1)/$n*$w;
      if($v>0) $seen++;
      $ys[] = $yy($seen/$k);
    }
    $cnt = count($xs);
    $d = 'M '.round($xs[0],2).' '.$h.' L '.round($xs[0],2).' '.round($ys[0],2);
    for($i=0;$i<$cnt-1;$i++){
      $x0 = $i>0 ? $xs[$i-1] : $xs[$i]; $y0 = $i>0 ? $ys[$i-1] : $ys[$i];
      $x3 = $i+2<$cnt ? $xs[$i+2] : $xs[$i+1]; $y3 = $i+2<$cnt ? $ys[$i+2] : $ys[$i+1];
      $c1x = $xs[$i] + ($xs[$i+1]-$x0)/6; $c1y = $ys[$i] + ($ys[$i+1]-$y0)/6;
      $c2x = $xs[$i+1] - ($x3-$xs[$i])/6; $c2y = $ys[$i+1] - ($y3-$ys[$i])/6;
      $c1y = max(0, min($h, $c1y)); $c2y = max(0, min($h, $c2y));
      $d .= ' C '.round($c1x,2).' '.round($c1y,2).' '.round($c2x,2).' '.round($c2y,2).' '.round($xs[$i+1],2).' '.round($ys[$i+1],2);
    }
    return $d.' L '.$w.' '.round($ys[$cnt-1],2).' L '.$w.' '.$h.' Z';
  };
  $pimpSerTotal = $pimpHari->map(fn($day)=>$laporanPimpinanSatlak->filter(fn($l)=>$l->created_at?->isSameDay($day))->count())->all();
  $pimpSuratSemua = $suratMasuk->concat($suratTerkirim)->concat($suratArsip);
  $pimpSerSurat = $pimpHari->map(fn($day)=>$pimpSuratSemua->filter(fn($s)=>$s->created_at?->isSameDay($day))->count())->all();
  $pimpKendalaSemua = $kendalaMasuk->concat($kendalaArsip);
  $pimpSerKendala = $pimpHari->map(fn($day)=>$pimpKendalaSemua->filter(fn($p)=>$p->created_at?->isSameDay($day))->count())->all();
  $pimpKpi = [
    ['awal'=>'Total','aksen'=>'Pelaporan','value'=>$pimpTotalPelaporan,'desc'=>'Laporan tercatat di sistem','series'=>$pimpSerTotal,'delta'=>array_sum($pimpSerTotal),'accent'=>'#22c55e','icon'=>'<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"></path>'],
    ['awal'=>'Total','aksen'=>'Surat','value'=>$pimpSuratSemua->count(),'desc'=>'Surat masuk & keluar tercatat','series'=>$pimpSerSurat,'delta'=>array_sum($pimpSerSurat),'accent'=>'#3b82f6','icon'=>'<rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path>'],
    ['awal'=>'Total','aksen'=>'Kendala Kasansi','value'=>$pimpKendalaSemua->count(),'desc'=>'Kendala yang dilaporkan Kasansi','series'=>$pimpSerKendala,'delta'=>array_sum($pimpSerKendala),'accent'=>'#f59e0b','icon'=>'<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>'],
  ];
  $pimpMaxDelta = max(1, collect($pimpKpi)->max('delta'));
@endphp
<div class="pimp-kpis">@foreach($pimpKpi as $k)<div class="pimp-kpi" style="--kpi-accent:{{ $k['accent'] }}"><svg class="kpi-deco" viewBox="0 0 120 60" preserveAspectRatio="none" aria-hidden="true"><path d="{{ $pimpSpark($k['series'], max(0.35, $k['delta']/$pimpMaxDelta)) }}"></path></svg><div class="kpi-top"><span class="kpi-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $k['icon'] !!}</svg></span><span class="kpi-eyebrow">{{ $k['awal'] }} <b>{{ $k['aksen'] }}</b></span></div><div class="kpi-value">{{ $k['value'] }}</div><div class="kpi-desc">{{ $k['desc'] }}</div><div class="kpi-trend{{ $k['delta'] > 0 ? ' is-up' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"></path><path d="m5 12 7-7 7 7"></path></svg><span>{{ $k['delta'] }}</span></div><div class="kpi-trend-cap">minggu ini</div></div>@endforeach</div>
