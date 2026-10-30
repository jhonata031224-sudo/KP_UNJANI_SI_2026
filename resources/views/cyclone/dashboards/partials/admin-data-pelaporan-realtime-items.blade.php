@foreach($semuaPelaporan as $pl)
@include('cyclone.dashboards.partials.admin-data-pelaporan-row', ['pl' => $pl])
@endforeach
