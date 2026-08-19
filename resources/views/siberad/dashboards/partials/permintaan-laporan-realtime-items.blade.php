@foreach($permintaanLaporan as $permintaan)
@include('siberad.dashboards.partials.permintaan-laporan-item', ['permintaan' => $permintaan])
@endforeach
