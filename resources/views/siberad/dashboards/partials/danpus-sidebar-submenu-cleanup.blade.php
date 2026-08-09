<style>
  /*
   * KHUSUS SIDEBAR DANPUS.
   * Submenu dibuat lebih kecil dan pangkal teksnya sejajar dengan
   * pangkal teks menu utama. Menu utama tidak disentuh.
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
    padding: 4px 0 4px 17px !important;
    min-height: 36px !important;
    height: 36px !important;
    box-sizing: border-box !important;
    font-size: 15px !important;
    font-weight: 500 !important;
    line-height: 22px !important;
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
