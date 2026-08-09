<style>
  /*
   * KHUSUS SIDEBAR DANPUS/PIMPINAN.
   * Menu utama (Dashboard Pimpinan), judul grup (Pantauan Aktivitas
   * Satlak, Pelaporan), dan submenu (SATLAKKAL dst, Laporan Masuk,
   * Riwayat/Status Laporan) sudah sama-sama font-size:13.5px dan
   * font-weight:500 lewat style dasarnya masing-masing. Jangan
   * menambahkan override font-size di sini lagi -- itulah yang
   * sebelumnya membuat submenu tampak lebih besar dari menu utama.
   */
  .side-nav-group .side-dropdown-menu {
    padding: 2px 0 !important;
    margin: 0 !important;
  }

  .side-nav-group .side-sub-link {
    display: flex !important;
    align-items: center !important;
    gap: 0 !important;
    margin: 0 !important;
    /* 20px + padding-left .side-subnav (8px) = 28px, menyamai pangkal
       teks menu utama & judul grup (padding-left 12px + dot 6px + gap 10px). */
    padding: 4px 0 4px 20px !important;
    min-height: 36px !important;
    height: 36px !important;
    box-sizing: border-box !important;
  }

  /* Submenu Danpus harus polos tanpa simbol titik. */
  .side-nav-group .side-sub-link .sub-dot,
  .side-nav-group .side-dropdown-menu .side-sub-link::before,
  .side-nav-group .side-dropdown-menu .side-sub-link::after {
    display: none !important;
    content: none !important;
  }
</style>
<script>
  (function () {
    function removeDanpusSubmenuDots() {
      document.querySelectorAll('.side-nav-group .side-sub-link .sub-dot').forEach(function (dot) {
        dot.remove();
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', removeDanpusSubmenuDots);
    } else {
      removeDanpusSubmenuDots();
    }
  })();
</script>
