{{-- 5 kartu KPI Beranda Admin (Total Pengguna/Satuan/Pelaporan/Surat/Reset
     Password). MIRROR style+fungsi kartu KPI Beranda Pimpinan/Satuan
     (partials/pimpinan-kpi-cards.blade.php, class ".pimp-kpis"/".pimp-kpi"
     dkk + JS countUp/animate*Kpis/sync*Kpis) -- TAPI domain metriknya beda
     (5 metrik Admin, bukan 3 Total Pelaporan/Surat/Kendala Kasansi), jadi
     partial ini TERPISAH, bukan reuse langsung punya Pimpinan/Satuan.
     Partial terpisah supaya render awal (admin.blade.php) dan poll
     realtime (DashboardController::adminKpiRealtime) pakai SATU sumber
     markup+algoritma sparkline yang sama persis -- jangan duplikat manual
     di dua tempat, nanti gampang drift.
     Variabel yang WAJIB dikirim caller: $stats (array total_pengguna/
     total_satuan/total_laporan/total_surat/reset_password_pending),
     $semuaPengguna, $semuaSatuan, $laporanRekapMentah, $suratSemuaAdmin,
     $permintaanResetPassword (Collection, dipakai buat hitung sparkline
     7 hari terakhir per metrik). --}}
@php
  $adminHari = collect(range(6,0))->map(fn($k)=>now()->startOfDay()->subDays($k));
  // Sparkline "gunung" -- algoritma & alasan SAMA PERSIS kayak $pimpSpark
  // di pimpinan-kpi-cards.blade.php, disalin ke sini (bukan di-share)
  // supaya partial ini berdiri sendiri.
  $adminSpark = function(array $s, float $ampl = 1.0){
    $w=120; $h=60; $top=$h*0.86*$ampl; $n=count($s);
    $k = count(array_filter($s, fn($v)=>$v>0));
    if($k===0) return 'M0 '.$h.' L'.$w.' '.$h.' L'.$w.' '.$h.' Z';
    $yy = fn($lvl)=>$h - $lvl*$top;
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
  $adminSerPengguna = $adminHari->map(fn($day)=>$semuaPengguna->filter(fn($u)=>$u->created_at?->isSameDay($day))->count())->all();
  $adminSerSatuan = $adminHari->map(fn($day)=>$semuaSatuan->filter(fn($s)=>$s->created_at?->isSameDay($day))->count())->all();
  $adminSerLaporan = $adminHari->map(fn($day)=>$laporanRekapMentah->filter(fn($l)=>$l->created_at?->isSameDay($day))->count())->all();
  $adminSerSurat = $adminHari->map(fn($day)=>$suratSemuaAdmin->filter(fn($s)=>$s->created_at?->isSameDay($day))->count())->all();
  $adminSerReset = $adminHari->map(fn($day)=>$permintaanResetPassword->filter(fn($r)=>$r->created_at?->isSameDay($day))->count())->all();
  $adminKpi = [
    ['awal'=>'Total','aksen'=>'Pengguna','value'=>$stats['total_pengguna'],'desc'=>'Akun terdaftar di sistem','series'=>$adminSerPengguna,'delta'=>array_sum($adminSerPengguna),'accent'=>'#8b5cf6','icon'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>'],
    ['awal'=>'Total','aksen'=>'Satuan','value'=>$stats['total_satuan'],'desc'=>'Termasuk Admin','series'=>$adminSerSatuan,'delta'=>array_sum($adminSerSatuan),'accent'=>'#06b6d4','icon'=>'<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path>'],
    ['awal'=>'Total','aksen'=>'Pelaporan','value'=>$stats['total_laporan'],'desc'=>'Laporan tercatat di sistem','series'=>$adminSerLaporan,'delta'=>array_sum($adminSerLaporan),'accent'=>'#22c55e','icon'=>'<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"></path>'],
    ['awal'=>'Total','aksen'=>'Surat','value'=>$stats['total_surat'],'desc'=>'Surat tercatat di sistem','series'=>$adminSerSurat,'delta'=>array_sum($adminSerSurat),'accent'=>'#3b82f6','icon'=>'<rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path>'],
    ['awal'=>'Reset','aksen'=>'Password','value'=>$stats['reset_password_pending'],'desc'=>'Menunggu diverifikasi','series'=>$adminSerReset,'delta'=>array_sum($adminSerReset),'accent'=>'#f59e0b','icon'=>'<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>'],
  ];
  $adminMaxDelta = max(1, collect($adminKpi)->max('delta'));
@endphp
<div class="pimp-kpis admin-kpis">@foreach($adminKpi as $k)<div class="pimp-kpi" style="--kpi-accent:{{ $k['accent'] }}"><svg class="kpi-deco" viewBox="0 0 120 60" preserveAspectRatio="none" aria-hidden="true"><path d="{{ $adminSpark($k['series'], max(0.35, $k['delta']/$adminMaxDelta)) }}"></path></svg><div class="kpi-top"><span class="kpi-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $k['icon'] !!}</svg></span><span class="kpi-eyebrow">{{ $k['awal'] }} <b>{{ $k['aksen'] }}</b></span></div><div class="kpi-value">{{ $k['value'] }}</div><div class="kpi-desc">{{ $k['desc'] }}</div><div class="kpi-trend{{ $k['delta'] > 0 ? ' is-up' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"></path><path d="m5 12 7-7 7 7"></path></svg><span>{{ $k['delta'] }}</span></div><div class="kpi-trend-cap">minggu ini</div></div>@endforeach</div>
