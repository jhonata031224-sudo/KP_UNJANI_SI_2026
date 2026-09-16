@foreach($semuaPelaporan as $pl)
@include('siberad.dashboards.partials.admin-data-pelaporan-row', ['pl' => $pl])
@endforeach
