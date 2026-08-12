(function () {
  'use strict';

  var zoom = 1;
  var timer = null;
  var installed = false;

  function byId(id) { return document.getElementById(id); }

  function getNodes() {
    return {
      viewport: byId('lpPreview'),
      stage: byId('lpPreviewStage'),
      canvas: byId('lpPreviewCanvas')
    };
  }

  function installResponsivePreviewCss() {
    if (installed || document.getElementById('siberad-preview-responsive-fix-style')) return;
    installed = true;

    var style = document.createElement('style');
    style.id = 'siberad-preview-responsive-fix-style';
    style.textContent = `
      /* Fit mode is a responsive page, not a horizontally scrollable desktop canvas. */
      #lpPreview:not(.lp-preview-zoomed) {
        overflow-x: hidden !important;
        overflow-y: hidden !important;
      }

      #lpPreview .lpv4-stage {
        width: 100% !important;
        max-width: 100% !important;
        overflow: visible !important;
      }

      #lpPreview .lpv4-canvas {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
      }

      #lpPreview .lpv4-header {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
      }

      #lpPreview .lpv4-page-inner,
      #lpPreview .lpv4-hero-content,
      #lpPreview .lpv4-features,
      #lpPreview .lpv4-contact,
      #lpPreview .lpv4-moto {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
      }

      #lpPreview .lpv4-features {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 18px !important;
      }

      #lpPreview .lpv4-features > * {
        min-width: 0 !important;
        width: auto !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
      }

      #lpPreview .lpv4-contact {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 18px !important;
      }

      #lpPreview .lpv4-contact > * {
        min-width: 0 !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        overflow-wrap: anywhere !important;
      }

      #lpPreview .lpv4-hero-content,
      #lpPreview .lpv4-page-inner {
        overflow-wrap: anywhere !important;
      }

      #lpPreview img {
        max-width: 100% !important;
      }

      @media (max-width: 760px) {
        #lpPreview .lpv4-features,
        #lpPreview .lpv4-contact {
          grid-template-columns: minmax(0, 1fr) !important;
        }

        #lpPreview .lpv4-header {
          padding-left: 22px !important;
          padding-right: 22px !important;
          gap: 12px !important;
        }

        #lpPreview .lpv4-header nav {
          gap: 8px !important;
        }
      }
    `;
    document.head.appendChild(style);
  }

  function fitScale() {
    var n = getNodes();
    if (!n.viewport || !n.stage || !n.canvas) return;

    /*
     * First lay the landing page out at the exact width of the preview.
     * This is the important difference from the old 1440px fixed canvas:
     * cards and text are allowed to reflow, so no element can create a
     * horizontal overflow just because the public page is desktop-sized.
     */
    var availableWidth = Math.max(n.viewport.clientWidth, 1);
    var availableHeight = Math.max(n.viewport.clientHeight - 52, 1);

    n.canvas.style.transform = 'none';
    n.canvas.style.transformOrigin = 'top left';
    n.canvas.style.width = availableWidth + 'px';
    n.canvas.style.minWidth = '0';
    n.canvas.style.maxWidth = availableWidth + 'px';
    n.canvas.style.height = 'auto';

    /* Force a synchronous layout after the responsive width is applied. */
    var naturalHeight = Math.max(n.canvas.scrollHeight, n.canvas.offsetHeight, 1);
    var scale = Math.min(1, availableHeight / naturalHeight);

    /* Zoom is deliberately applied only after Fit has established a
       responsive baseline. Fit itself never creates horizontal scrolling. */
    scale = Math.max(0.08, Math.min(3, scale * zoom));

    n.stage.style.width = Math.ceil(availableWidth * scale) + 'px';
    n.stage.style.maxWidth = '100%';
    n.stage.style.height = Math.ceil(naturalHeight * scale) + 'px';
    n.stage.style.marginTop = '52px';
    n.stage.style.position = 'relative';

    n.canvas.style.height = naturalHeight + 'px';
    n.canvas.style.transform = 'scale(' + scale + ')';

    var zoomed = zoom !== 1;
    n.viewport.classList.toggle('lp-preview-zoomed', zoomed);
    n.viewport.style.overflowX = zoomed ? 'auto' : 'hidden';
    n.viewport.style.overflowY = zoomed ? 'auto' : 'hidden';

    var label = byId('lpv4ZoomLabel');
    if (label) label.textContent = zoom === 1 ? 'Fit' : Math.round(zoom * 100) + '%';
  }

  function schedule() {
    clearTimeout(timer);
    timer = setTimeout(fitScale, 50);
  }

  function handleControls(e) {
    var button = e.target.closest('#lpPreview [data-zoom]');
    if (!button) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    var action = button.getAttribute('data-zoom');
    if (action === 'fit') zoom = 1;
    else if (action === 'in') zoom = Math.min(3, +(zoom + 0.25).toFixed(2));
    else if (action === 'out') zoom = Math.max(0.5, +(zoom - 0.25).toFixed(2));

    schedule();
  }

  function boot() {
    var viewport = byId('lpPreview');
    if (!viewport) return;

    installResponsivePreviewCss();
    viewport.addEventListener('click', handleControls, true);

    viewport.addEventListener('click', function (e) {
      var nav = e.target.closest('[data-page], [data-nav]');
      if (nav && !e.target.closest('[data-zoom]')) {
        zoom = 1;
        schedule();
        setTimeout(schedule, 120);
      }
    }, false);

    if (window.ResizeObserver) {
      new ResizeObserver(schedule).observe(viewport);
    } else {
      window.addEventListener('resize', schedule, { passive: true });
    }

    if (window.MutationObserver) {
      new MutationObserver(schedule).observe(viewport, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['class', 'style']
      });
    }

    schedule();
    setTimeout(schedule, 120);
    setTimeout(schedule, 500);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
