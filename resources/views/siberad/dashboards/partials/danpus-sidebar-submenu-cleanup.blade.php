<style>
  /* Submenu Danpus dibuat polos tanpa bullet. Menu utama tetap memiliki titik. */
  .side-nav-group .side-sub-link {
    gap: 0 !important;
    padding-left: 17px !important;
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
