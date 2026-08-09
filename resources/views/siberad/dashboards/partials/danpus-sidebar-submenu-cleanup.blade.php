<style>
  /* KHUSUS SIDEBAR DANPUS: submenu rapat dan seimbang. */
  .side-nav-group .side-dropdown-menu {
    padding: 0 !important;
    margin: 0 !important;
  }

  .side-nav-group .side-sub-link {
    display: flex !important;
    align-items: center !important;
    gap: 0 !important;
    margin: 0 !important;
    padding: 2px 0 2px 20px !important;
    min-height: 32px !important;
    height: 32px !important;
    box-sizing: border-box !important;
    text-transform: none !important;
  }

  .side-nav-group .side-dropdown-menu .side-sub-link:first-child {
    margin-top: 2px !important;
  }

  .side-nav-group .side-dropdown-menu .side-sub-link + .side-sub-link {
    margin-top: 0 !important;
  }

  .side-nav-group .side-sub-link .sub-dot,
  .side-nav-group .side-dropdown-menu .side-sub-link::before,
  .side-nav-group .side-dropdown-menu .side-sub-link::after {
    display: none !important;
    content: none !important;
  }
</style>
<script>
  (function () {
    function normalizeDanpusSubmenuText() {
      var labels = {
        'SATLAKKAL': 'Satlakkal',
        'SATLAKSISOS': 'Satlaksisos',
        'SATLAKDAK': 'Satlakdak',
        'SATLAKDUKTEK': 'Satlakduktek'
      };

      document.querySelectorAll('.side-nav-group .side-sub-link').forEach(function (link) {
        var text = link.textContent.trim();
        if (labels[text]) {
          Array.prototype.slice.call(link.childNodes).forEach(function (node) {
            if (node.nodeType === Node.TEXT_NODE && node.nodeValue.trim()) {
              node.nodeValue = node.nodeValue.replace(text, labels[text]);
            }
          });
        }
      });
    }

    function removeDanpusSubmenuDots() {
      document.querySelectorAll('.side-nav-group .side-sub-link .sub-dot').forEach(function (dot) {
        dot.remove();
      });
    }

    function applyDanpusSubmenuFix() {
      removeDanpusSubmenuDots();
      normalizeDanpusSubmenuText();
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', applyDanpusSubmenuFix);
    } else {
      applyDanpusSubmenuFix();
    }
  })();
</script>
