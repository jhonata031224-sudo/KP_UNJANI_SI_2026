<style>
  /*
   * Submenu Danpus dibuat polos dan mengikuti alignment menu utama.
   * Menu utama tidak disentuh.
   */
  .side-nav-group .side-dropdown-menu {
    display: flex !important;
    flex-direction: column !important;
    gap: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  .side-nav-group .side-sub-link {
    display: flex !important;
    align-items: center !important;
    gap: 0 !important;
    margin: 0 !important;
    padding: 4px 0 4px 18px !important;
    min-height: 38px !important;
    box-sizing: border-box !important;
    font-size: 17px !important;
    line-height: 1.25 !important;
    font-weight: 500 !important;
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
