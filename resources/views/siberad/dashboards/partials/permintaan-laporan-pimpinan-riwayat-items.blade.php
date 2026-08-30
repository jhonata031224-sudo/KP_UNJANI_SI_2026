{{-- Dipakai PermintaanLaporanController::realtime(?history=1) buat nge-render
     ulang kartu Riwayat Laporan Pimpinan (#riwayat) tiap siklus poll, sejajar
     dengan permintaan-laporan-realtime-items.blade.php milik Satuan. Kartu
     dirender mode riwayatMode=true -> menu titik-3 jadi "Lihat Aktivitas" +
     "Revisi" (bukan "Arsipkan"). --}}
@foreach($permintaanLaporan as $item)
@include('siberad.dashboards.partials.permintaan-laporan-pimpinan-card', ['item' => $item, 'riwayatMode' => true])
@endforeach
