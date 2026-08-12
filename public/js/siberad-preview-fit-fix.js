(function () {
  'use strict';

  var zoom = 1;
  var timer = null;
  var booted = false;

  function byId(id) { return document.getElementById(id); }

  function getNodes() {
    return { viewport: byId('lpPreview'), canvas: byId('lpPreviewCanvas') };
  }

  function css() {
    if (byId('siberad-preview-final-css')) return;
    var s = document.createElement('style');
    s.id = 'siberad-preview-final-css';
    s.textContent = [
      '#lpPreview.siberad-final-preview{overflow:hidden!important;position:relative!important}',
      '#lpPreview.siberad-final-preview.lp-preview-zoomed{overflow:auto!important}',
      '#lpPreview.siberad-final-preview .siberad-fit-stage{position:relative!important;margin:0!important;padding:0!important;overflow:visible!important}',
      '#lpPreview.siberad-final-preview #lpPreviewCanvas{transform-origin:top left!important;max-width:none!important;margin:0!important}',
      '.siberad-preview-zoombar{display:flex!important;align-items:center!important;justify-content:center!important;gap:7px!important;margin:8px 0 10px!important;padding:6px!important;border:1px solid #dbe3ec!important;border-radius:9px!important;background:#fff!important;box-shadow:0 3px 10px rgba(0,0,0,.08)!important;position:relative!important;z-index:9999!important}',
      '.siberad-preview-zoombar button{display:inline-flex!important;align-items:center!important;justify-content:center!important;width:38px!important;height:32px!important;padding:0!important;border:1px solid #d5dee8!important;border-radius:7px!important;background:#fff!important;cursor:pointer!important;font-weight:700!important}',
      '.siberad-preview-zoombar button:hover{background:#f5f7f9!important}',
      '.siberad-preview-zoom-label{display:inline-block!important;min-width:58px!important;text-align:center!important;font-size:12px!important;font-weight:700!important}',
      '#lpPreview .siberad-preview-auth-hidden{display:none!important}'
    ].join('\n');
    document.head.appendChild(s);
  }

  function ensureStage(canvas) {
    if (canvas.parentElement && canvas.parentElement.classList.contains('siberad-fit-stage')) return canvas.parentElement;
    var stage = document.createElement('div');
    stage.className = 'siberad-fit-stage';
    canvas.parentNode.insertBefore(stage, canvas);
    stage.appendChild(canvas);
    return stage;
  }

  function ensureToolbar(viewport) {
    var parent = viewport.parentElement;
    if (!parent) return;
    var bar = parent.querySelector('.siberad-preview-zoombar');
    if (bar) return;
    bar = document.createElement('div');
    bar.className = 'siberad-preview-zoombar';
    bar.innerHTML = '<button type="button" data-preview-zoom="out" aria-label="Perkecil">−</button>' +
      '<button type="button" data-preview-zoom="fit" aria-label="Fit">Fit</button>' +
      '<span class="siberad-preview-zoom-label">Fit</span>' +
      '<button type="button" data-preview-zoom="in" aria-label="Perbesar">+</button>';
    parent.insertBefore(bar, viewport);
  }

  function stripAuth() {
    var n = getNodes();
    if (!n.canvas) return;
    n.canvas.querySelectorAll('a,button,input,form,[role="button"]').forEach(function (node) {
      var text = (node.textContent || node.value || '').replace(/\s+/g, ' ').trim().toLowerCase();
      var href = (node.getAttribute('href') || '').toLowerCase();
      var auth = /^(login|log in|masuk|sign in|signin|masuk aplikasi)$/.test(text) ||
        /(^|\/)(login|masuk|signin|sign-in|auth)(\/|$|\?|#)/.test(href) ||
        node.hasAttribute('data-auth') || node.hasAttribute('data-login');
      if (auth) {
        node.classList.add('siberad-preview-auth-hidden');
        node.setAttribute('aria-hidden', 'true');
        node.setAttribute('tabindex', '-1');
      }
    });
  }

  function render() {
    var n = getNodes();
    if (!n.viewport || !n.canvas) return;
    css();
    ensureToolbar(n.viewport);
    stripAuth();

    var stage = ensureStage(n.canvas);
    var viewportWidth = Math.max(n.viewport.clientWidth, 1);

    n.canvas.style.transform = 'none';
    n.canvas.style.transformOrigin = 'top left';
    n.canvas.style.maxWidth = 'none';
    n.canvas.style.minWidth = '0';
    n.canvas.style.height = 'auto';

    var naturalWidth = Math.max(n.canvas.scrollWidth, n.canvas.getBoundingClientRect().width, 1);
    var naturalHeight = Math.max(n.canvas.scrollHeight, n.canvas.getBoundingClientRect().height, 1);
    var fit = viewportWidth / naturalWidth;
    var scale = Math.max(0.05, Math.min(3, fit * zoom));

    stage.style.width = Math.ceil(naturalWidth * scale) + 'px';
    stage.style.height = Math.ceil(naturalHeight * scale) + 'px';
    n.canvas.style.width = naturalWidth + 'px';
    n.canvas.style.height = naturalHeight + 'px';
    n.canvas.style.transform = 'scale(' + scale + ')';

    n.viewport.classList.add('siberad-final-preview');
    n.viewport.classList.toggle('lp-preview-zoomed', zoom !== 1);
    if (zoom === 1) {
      n.viewport.style.setProperty('overflow-x', 'hidden', 'important');
      n.viewport.style.setProperty('overflow-y', 'hidden', 'important');
    } else {
      n.viewport.style.setProperty('overflow', 'auto', 'important');
    }

    var label = n.viewport.parentElement && n.viewport.parentElement.querySelector('.siberad-preview-zoom-label');
    if (label) label.textContent = zoom === 1 ? 'Fit' : Math.round(zoom * 100) + '%';
  }

  function schedule() {
    clearTimeout(timer);
    timer = setTimeout(render, 50);
  }

  function boot() {
    var viewport = byId('lpPreview');
    if (!viewport || booted) return;
    booted = true;
    css();
    ensureToolbar(viewport);

    document.addEventListener('click', function (e) {
      var button = e.target.closest('[data-preview-zoom],[data-zoom],[data-zoom-action]');
      if (button) {
        var action = button.getAttribute('data-preview-zoom') || button.getAttribute('data-zoom') || button.getAttribute('data-zoom-action');
        if (/^(fit|in|out)$/.test(action)) {
          e.preventDefault();
          e.stopPropagation();
          if (action === 'fit') zoom = 1;
          if (action === 'in') zoom = Math.min(3, +(zoom + .25).toFixed(2));
          if (action === 'out') zoom = Math.max(.5, +(zoom - .25).toFixed(2));
          schedule();
          return;
        }
      }
      var auth = e.target.closest('#lpPreview .siberad-preview-auth-hidden');
      if (auth) { e.preventDefault(); e.stopPropagation(); }
    }, true);

    window.addEventListener('resize', schedule, { passive: true });
    if (window.ResizeObserver) new ResizeObserver(schedule).observe(viewport);
    if (window.MutationObserver) {
      new MutationObserver(function () { stripAuth(); schedule(); }).observe(viewport, { subtree:true, childList:true, attributes:true, attributeFilter:['class','style','href'] });
    }
    schedule();
    setTimeout(schedule, 150);
    setTimeout(schedule, 500);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
