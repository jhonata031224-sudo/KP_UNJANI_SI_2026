<style>
  /*
   * Submenu Danpus dibuat mengikuti tipografi dan ritme menu utama.
   * Hanya submenu yang diatur; menu utama tidak disentuh.
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
    padding: 4px 0 4px 29px !important;
    min-height: 36px !important;
    height: 36px !important;
    box-sizing: border-box !important;
    font-size: 17px !important;
    font-weight: 500 !important;
    line-height: 24px !important;
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
