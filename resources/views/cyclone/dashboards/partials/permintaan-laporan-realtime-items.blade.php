@foreach($permintaanLaporan as $permintaan)
@include('cyclone.dashboards.partials.permintaan-laporan-item', ['permintaan' => $permintaan])
@endforeach
