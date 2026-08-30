@foreach($permintaanLaporan as $item)
@include('siberad.dashboards.partials.permintaan-laporan-pimpinan-card', ['item' => $item])
@endforeach
