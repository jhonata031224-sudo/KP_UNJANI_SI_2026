<style>
/* Refactor laporan: sidebar dan header mengikuti surface #11181F pada dark mode. */
:root:not([data-theme="light"]){
  --surface:#11181F;
}

:root:not([data-theme="light"]) .sidebar,
:root:not([data-theme="light"]) .side-brand,
:root:not([data-theme="light"]) .topbar,
:root:not([data-theme="light"]) header.topbar-simple{
  background:#11181F !important;
}
</style>
