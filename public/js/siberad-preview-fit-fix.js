(function () {
  'use strict';

  var DESIGN_WIDTH = 1440;
  var zoom = 1;
  var timer = null;

  function byId(id) { return document.getElementById(id); }

  function getNodes() {
    return {
      viewport: byId('lpPreview'),
      stage: byId('lpPreviewStage'),
      canvas: byId('lpPreviewCanvas')
    };
  }

  function fitScale() {
    var n = getNodes();
    if (!n.viewport || !n.stage || !n.canvas) return;

    /*
     * The old renderer measured a canvas after setting width:100%, so any
     * child that was wider than the viewport was invisible to the fit
     * calculation. Use a stable desktop design width instead and scale the
     * complete page as one surface.
     */
    n.canvas.style.width = DESIGN_WIDTH + 'px';
    n.canvas.style.maxWidth = 'none';
    n.canvas.style.minWidth = DESIGN_WIDTH + 'px';
    n.canvas.style.transform = 'none';
    n.canvas.style.transformOrigin = 'top left';

    var naturalWidth = DESIGN_WIDTH;
    var naturalHeight = Math.max(n.canvas.scrollHeight, n.canvas.offsetHeight, 1);
    var availableWidth = Math.max(n.viewport.clientWidth, 1);
    var availableHeight = Math.max(n.viewport.clientHeight - 52, 1);

    var scaleToWidth = availableWidth / naturalWidth;
    var scaleToHeight = availableHeight / naturalHeight;
    var scale = Math.min(scaleToWidth, scaleToHeight, 1) * zoom;
    scale = Math.max(0.08, Math.min(3, scale));

    n.stage.style.width = Math.ceil(naturalWidth * scale) + 'px';
    n.stage.style.height = Math.ceil(naturalHeight * scale) + 'px';
    n.stage.style.marginTop = '52px';
    n.stage.style.position = 'relative';

    n.canvas.style.height = naturalHeight + 'px';
    n.canvas.style.transform = 'scale(' + scale + ')';

    var zoomed = zoom !== 1;
    n.viewport.classList.toggle('lp-preview-zoomed', zoomed);
    n.viewport.style.overflow = zoomed ? 'auto' : 'hidden';

    var label = byId('lpv4ZoomLabel');
    if (label) label.textContent = zoom === 1 ? 'Fit' : Math.round(zoom * 100) + '%';
  }

  function schedule() {
    clearTimeout(timer);
    timer = setTimeout(fitScale, 40);
  }

  function handleControls(e) {
    var button = e.target.closest('#lpPreview [data-zoom]');
    if (!button) return;

    /* Override v4's scaling because its fit calculation is based on a fluid
       canvas. Capture phase prevents the old click handler from running. */
    e.preventDefault();
    e.stopImmediatePropagation();

    var action = button.getAttribute('data-zoom');
    if (action === 'fit') zoom = 1;
    else if (action === 'in') zoom = Math.min(3, +(zoom + 0.25).toFixed(2));
    else if (action === 'out') zoom = Math.max(0.5, +(zoom - 0.25).toFixed(2));

    schedule();
  }

  function boot() {
    if (!byId('lpPreview')) return;

    var viewport = byId('lpPreview');
    viewport.addEventListener('click', handleControls, true);

    /* Changing Beranda/Fitur/Tentang/Kontak changes the active page and its
       height. Re-fit the newly selected page, but never create a horizontal
       scrollbar while in Fit mode. */
    viewport.addEventListener('click', function (e) {
      var nav = e.target.closest('[data-page], [data-nav]');
      if (nav && !e.target.closest('[data-zoom]')) {
        zoom = 1;
        schedule();
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
