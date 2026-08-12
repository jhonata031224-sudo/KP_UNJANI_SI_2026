(function () {
  'use strict';

  function installFixedHeaderStyles() {
    if (document.getElementById('siberad-fixed-header-style')) return;
    var style = document.createElement('style');
    style.id = 'siberad-fixed-header-style';
    style.textContent = `
      .main > .topbar{position:fixed!important;top:0!important;left:var(--siberad-sidebar-width,256px)!important;right:0!important;width:auto!important;z-index:100005!important}
      .main{padding-top:82px!important}
      @media(max-width:900px){.main > .topbar{left:0!important}}

      /* Pengaturan Umum: editor dan preview disusun vertikal penuh-lebar.
         Ini sengaja tidak mengubah layout halaman admin lain. */
      .siberad-landing-stack{display:grid!important;grid-template-columns:minmax(0,1fr)!important;gap:20px!important;align-items:start!important}
      .siberad-landing-stack>*{width:100%!important;min-width:0!important;box-sizing:border-box!important}
      #lpPreview{width:100%!important;max-width:100%!important;box-sizing:border-box!important}
      #lpPreview *{box-sizing:border-box}
      @media(max-width:900px){.siberad-landing-stack{gap:14px!important}}
    `;
    document.head.appendChild(style);
  }

  function syncHeaderToSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    var width = sidebar.getBoundingClientRect().width;
    if (width > 0) document.documentElement.style.setProperty('--siberad-sidebar-width', width + 'px');
  }

  function arrangeLandingCards() {
    var form = document.getElementById('landingForm');
    var preview = document.getElementById('lpPreview');
    if (!form || !preview) return false;

    var formCard = form.closest('.panel, .card, .settings-card, .content-card') || form.parentElement;
    var previewCard = preview.closest('.panel, .card, .settings-card, .content-card') || preview.parentElement;
    if (!formCard || !previewCard) return false;

    var parent = formCard.parentElement;
    if (!parent || previewCard.parentElement !== parent) {
      var a = [], b = formCard;
      while (b) { a.push(b); b = b.parentElement; }
      b = previewCard;
      while (b && a.indexOf(b) < 0) b = b.parentElement;
      parent = b || parent;
    }
    if (!parent) return false;

    parent.classList.add('siberad-landing-stack');
    formCard.style.width = '100%';
    previewCard.style.width = '100%';
    formCard.style.minWidth = '0';
    previewCard.style.minWidth = '0';
    return true;
  }

  function loadLandingPreviewV4() {
    var form = document.getElementById('landingForm');
    var viewport = document.getElementById('lpPreview');
    if (!form || !viewport) return false;

    arrangeLandingCards();

    var existing = document.getElementById('siberad-landing-preview-v4-script');
    if (existing) return true;

    var script = document.createElement('script');
    script.id = 'siberad-landing-preview-v4-script';
    script.src = '/js/siberad-landing-preview-v4.js?v=20260812-02';
    script.onload = function () { arrangeLandingCards(); };
    script.defer = false;
    document.body.appendChild(script);
    return true;
  }

  function boot() {
    installFixedHeaderStyles();
    syncHeaderToSidebar();
    loadLandingPreviewV4();

    var tries = 0;
    var timer = setInterval(function () {
      tries++;
      arrangeLandingCards();
      loadLandingPreviewV4();
      if (tries >= 30) clearInterval(timer);
    }, 250);

    var sidebar = document.getElementById('sidebar');
    if (sidebar && window.ResizeObserver) new ResizeObserver(syncHeaderToSidebar).observe(sidebar);
    if (sidebar && window.MutationObserver) new MutationObserver(syncHeaderToSidebar).observe(sidebar, {attributes:true,attributeFilter:['class','style']});
    window.addEventListener('resize', function () { syncHeaderToSidebar(); arrangeLandingCards(); }, {passive:true});
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, {once:true});
  else boot();
})();
