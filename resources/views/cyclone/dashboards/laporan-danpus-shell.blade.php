@include('cyclone.dashboards.laporan-danpus')
@include('cyclone.dashboards.partials.log-aktivitas-realtime')
@include('cyclone.dashboards.partials.danpus-sidebar-submenu-cleanup')
@include('cyclone.dashboards.partials.danpus-laporan-request-realtime')
@include('cyclone.dashboards.partials.danpus-ringkasan-submenu-hide')
@include('cyclone.dashboards.partials.danpus-activity-dropdown')
@include('cyclone.dashboards.partials.danpus-log-search')
@include('cyclone.dashboards.partials.danpus-report-table-filter')
@include('cyclone.dashboards.partials.styled-select')
@include('cyclone.dashboards.partials.satlak-notification-close-text')
@include('cyclone.dashboards.partials.global-shell-enhancements')
@include('cyclone.dashboards.partials.danpus-monitoring-text-fix')
@include('cyclone.dashboards.partials.profile-description-hide')
@include('cyclone.dashboards.partials.responsive-content-alignment')
@include('cyclone.dashboards.partials.sidebar-header-surface')
@include('cyclone.dashboards.partials.danpus-permintaan-arsip-mode')
{{-- danpus-history-detail-fix & danpus-history-status-filter-match DIHAPUS:
     keduanya khusus struktur TABEL Riwayat lama (#status .clean-table /
     #riwayat .dtbl). Riwayat Pimpinan sekarang kartu (#riwayat, lihat
     danpus-permintaan-arsip-mode -> initRiwayatCardFilter/syncRiwayatCards),
     jadi kedua partial itu inert / malah bikin konflik <select> filter. --}}
@include('cyclone.dashboards.partials.danpus-kendala-kasansi-realtime')
{{-- Surat Keluar/Arsip Surat/Surat Masuk Pimpinan sebelumnya SAMA SEKALI
     gak realtime (partial ini gak pernah di-include di sini) -- id
     container (#suratTerkirimGrid/#suratArsipBody/#suratMasukGrid) sama
     persis kayak dashboard Satuan, jadi partial yang sama dipakai bareng. --}}
@include('cyclone.dashboards.partials.surat-terkirim-realtime')
