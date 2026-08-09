<style>
  /*
   * Submenu Danpus:
   * - tanpa bullet/titik
   * - ukuran teks sama dengan menu induk
   * - awal teks submenu sejajar dengan awal teks menu induk
   * - jarak vertikal dibuat konsisten dan tidak terlalu renggang
   * Menu utama tidak disentuh.
   */
  .side-nav-group .side-dropdown-menu {
    padding-top: 4px !important;
    padding-bottom: 4px !important;
  }

  .side-nav-group .side-sub-link {
    display: flex !important;
    align-items: center !important;
    gap: 0 !important;
    margin: 0 !important;
    padding: 8px 0 8px 29px !important;
    min-height: 40px !important;
    box-sizing: border-box !important;
    font-size: inherit !important;
    line-height: 1.35 !important;
  }

  .side-nav-group .side-sub-link .sub-dot {
    display: none !important;
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
