@foreach($permintaanLaporan as $item)
@include('cyclone.dashboards.partials.permintaan-laporan-pimpinan-card', ['item' => $item])
@endforeach
