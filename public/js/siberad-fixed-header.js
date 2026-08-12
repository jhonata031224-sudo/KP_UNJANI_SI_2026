(function () {
  'use strict';

  function installFixedHeaderStyles() {
    if (document.getElementById('siberad-fixed-header-style')) return;

    var style = document.createElement('style');
    style.id = 'siberad-fixed-header-style';
    style.textContent = `
      /* Header saja yang dibuat fixed. Sidebar dan mekanisme collapse aslinya tidak diubah. */
      .main > .topbar {
        position: fixed !important;
        top: 0 !important;
        left: var(--siberad-sidebar-width, 256px) !important;
        right: 0 !important;
        width: auto !important;
        z-index: 100005 !important;
      }

      /* Karena fixed keluar dari normal flow, sisakan ruang header di main. */
      .main {
        padding-top: 82px !important;
      }

      /* Pada layar kecil sidebar berubah menjadi overlay; header kembali full width. */
      @media (max-width: 900px) {
        .main > .topbar {
          left: 0 !important;
        }
      }
    `;
    document.head.appendChild(style);
  }

  function syncHeaderToSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    var width = sidebar.getBoundingClientRect().width;
    if (width > 0) {
      document.documentElement.style.setProperty('--siberad-sidebar-width', width + 'px');
    }
  }

  function loadLandingPreviewV4() {
    if (!document.getElementById('landingForm') || !document.getElementById('lpPreview')) return;
    if (document.getElementById('siberad-landing-preview-v4-script')) return;

    var script = document.createElement('script');
    script.id = 'siberad-landing-preview-v4-script';
    script.src = '/js/siberad-landing-preview-v4.js?v=7070af1b';
    script.defer = false;
    document.body.appendChild(script);
  }

  function boot() {
    installFixedHeaderStyles();
    syncHeaderToSidebar();
    loadLandingPreviewV4();

    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    if (window.ResizeObserver) {
      new ResizeObserver(syncHeaderToSidebar).observe(sidebar);
    }

    if (window.MutationObserver) {
      new MutationObserver(syncHeaderToSidebar).observe(sidebar, {
        attributes: true,
        attributeFilter: ['class', 'style']
      });
    }

    window.addEventListener('resize', syncHeaderToSidebar, { passive: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
