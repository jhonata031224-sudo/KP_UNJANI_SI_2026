(function () {
  'use strict';

  /*
   * Keep this renderer deliberately simple:
   *   1. Fit = scale the complete landing surface to the preview width.
   *   2. Fit never has a horizontal scrollbar.
   *   3. Zoom is the only way to make the surface larger than the preview.
   *   4. When zoomed, scrolling is allowed for detail inspection.
   *
   * Do not try to rewrite the public landing page into a second responsive
   * design here. The admin preview is a scaled viewport of the real page.
   */
  var DESIGN_WIDTH = 1440;
  var zoom = 1;
  var timer = null;
  var styleInstalled = false;

  function byId(id) { return document.getElementById(id); }

  function nodes() {
    return {
      viewport: byId('lpPreview'),
      stage: byId('lpPreviewStage'),
      canvas: byId('lpPreviewCanvas')
    };
  }

  function installFinalCss() {
    if (styleInstalled || document.getElementById('siberad-preview-final-css')) return;
    styleInstalled = true;

    var style = document.createElement('style');
    style.id = 'siberad-preview-final-css';
    style.textContent = [
      '#lpPreview.siberad-final-preview { overflow-x:hidden !important; overflow-y:auto !important; }',
      '#lpPreview.siberad-final-preview.lp-preview-zoomed { overflow:auto !important; }',
      '#lpPreview.siberad-final-preview #lpPreviewStage { overflow:visible !important; }',
      '#lpPreview.siberad-final-preview #lpPreviewCanvas { max-width:none !important; }'
    ].join('\n');
    document.head.appendChild(style);
  }

  function render() {
    var n = nodes();
    if (!n.viewport || !n.stage || !n.canvas) return;

    installFinalCss();

    var viewportWidth = Math.max(n.viewport.clientWidth, 1);

    /* Establish a stable natural surface before calculating the scale. */
    n.canvas.style.transform = 'none';
    n.canvas.style.transformOrigin = 'top left';
    n.canvas.style.width = DESIGN_WIDTH + 'px';
    n.canvas.style.minWidth = DESIGN_WIDTH + 'px';
    n.canvas.style.maxWidth = 'none';
    n.canvas.style.height = 'auto';
    n.canvas.style.margin = '0';

    var naturalHeight = Math.max(
      n.canvas.scrollHeight,
      n.canvas.offsetHeight,
      n.canvas.getBoundingClientRect().height,
      1
    );

    /* At Fit, the entire 1440px landing surface becomes exactly the
       available preview width. This removes the horizontal overflow at the
       source instead of hiding a clipped right side. */
    var fitScale = viewportWidth / DESIGN_WIDTH;
    var scale = fitScale * zoom;
    scale = Math.max(0.05, Math.min(3, scale));

    var renderedWidth = DESIGN_WIDTH * scale;
    var renderedHeight = naturalHeight * scale;

    n.stage.style.position = 'relative';
    n.stage.style.width = Math.ceil(renderedWidth) + 'px';
    n.stage.style.height = Math.ceil(renderedHeight) + 'px';
    n.stage.style.maxWidth = 'none';
    n.stage.style.margin = '0';

    n.canvas.style.height = naturalHeight + 'px';
    n.canvas.style.transform = 'scale(' + scale + ')';

    var zoomed = zoom !== 1;
    n.viewport.classList.add('siberad-final-preview');
    n.viewport.classList.toggle('lp-preview-zoomed', zoomed);

    /* Old preview CSS contains !important overflow rules, so override them
       explicitly. Fit has no horizontal scrolling; zoom enables scrolling. */
    if (zoomed) {
      n.viewport.style.setProperty('overflow', 'auto', 'important');
    } else {
      n.viewport.style.setProperty('overflow-x', 'hidden', 'important');
      n.viewport.style.setProperty('overflow-y', 'auto', 'important');
    }

    var label = byId('lpv4ZoomLabel');
    if (label) label.textContent = zoom === 1 ? 'Fit' : Math.round(zoom * 100) + '%';
  }

  function schedule() {
    clearTimeout(timer);
    timer = setTimeout(render, 35);
  }

  function handleControls(e) {
    var button = e.target.closest('#lpPreview [data-zoom]');
    if (!button) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    var action = button.getAttribute('data-zoom');
    if (action === 'fit') zoom = 1;
    if (action === 'in') zoom = Math.min(3, +(zoom + 0.25).toFixed(2));
    if (action === 'out') zoom = Math.max(0.5, +(zoom - 0.25).toFixed(2));

    schedule();
  }

  function boot() {
    var viewport = byId('lpPreview');
    if (!viewport) return;

    installFinalCss();
    viewport.addEventListener('click', handleControls, true);

    /* Switching Beranda/Fitur/Tentang/Kontak returns to Fit so every page
       starts fully visible. */
    viewport.addEventListener('click', function (e) {
      var nav = e.target.closest('[data-page], [data-nav]');
      if (nav && !e.target.closest('[data-zoom]')) {
        zoom = 1;
        schedule();
        setTimeout(schedule, 100);
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
