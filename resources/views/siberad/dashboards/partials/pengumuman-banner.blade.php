@include('siberad.dashboards.partials.profile-enhancements')
@include('siberad.dashboards.partials.notification-controls')
@include('siberad.dashboards.partials.admin-ui-consistency')

{{-- Inisialisasi UI dashboard role. Tidak mengubah struktur sidebar. --}}
<script>
(function () {
  function initRoleUi() {
    var sidebar = document.getElementById('sidebar');
    var menuBtn = document.getElementById('menuBtn');
    if (menuBtn && sidebar && !menuBtn.dataset.uiBound) {
      menuBtn.dataset.uiBound = '1';
      menuBtn.addEventListener('click', function () { sidebar.classList.toggle('open'); });
    }

    var themeBtn = document.getElementById('themeToggleBtn');
    if (themeBtn && !themeBtn.dataset.uiBound) {
      themeBtn.dataset.uiBound = '1';
      var key = 'siberad-theme';
      function applyTheme(theme) {
        if (theme === 'light') document.documentElement.setAttribute('data-theme', 'light');
        else document.documentElement.removeAttribute('data-theme');
        themeBtn.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
      }
      var saved = 'dark';
      try { saved = localStorage.getItem(key) || 'dark'; } catch (err) {}
      applyTheme(saved);
      themeBtn.addEventListener('click', function () {
        var current = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
        var next = current === 'light' ? 'dark' : 'light';
        try { localStorage.setItem(key, next); } catch (err) {}
        applyTheme(next);
      });
    }

    var profileBtn = document.getElementById('profileMenuBtn');
    var profileDrop = document.getElementById('profileDropdown');
    if (profileBtn && profileDrop && !profileBtn.dataset.uiBound) {
      profileBtn.dataset.uiBound = '1';
      profileBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        profileDrop.classList.toggle('open');
      });
      document.addEventListener('click', function (e) {
        if (!profileDrop.parentElement.contains(e.target)) profileDrop.classList.remove('open');
      });
    }

    window.openProfileModal = window.openProfileModal || function (id) {
      var overlay = document.getElementById('profileModalOverlay');
      if (!overlay) return;
      overlay.querySelectorAll('.profile-dropdown-view').forEach(function (view) {
        view.style.display = view.id === id ? 'block' : 'none';
      });
      overlay.classList.add('open');
      if (profileDrop) profileDrop.classList.remove('open');
    };

    var closeProfile = document.getElementById('profileModalCloseBtn');
    if (closeProfile && !closeProfile.dataset.uiBound) {
      closeProfile.dataset.uiBound = '1';
      closeProfile.addEventListener('click', function () {
        var overlay = document.getElementById('profileModalOverlay');
        if (overlay) overlay.classList.remove('open');
      });
    }

  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initRoleUi);
  else initRoleUi();
})();
</script>

{{-- Normalisasi nama satuan tertentu pada Log Aktivitas Admin. --}}
<script>
(function () {
  function normalisasiNamaLogAktivitas() {
    var table = document.getElementById('tblLogAktivitas');
    if (!table) return;

    var normalisasi = {
      'BINMAT': 'Binmat',
      'BINFUNG': 'Binfung',
      'BINUM': 'Binum',
      'DIKLAT': 'Diklat'
    };

    table.querySelectorAll('tbody tr').forEach(function (row) {
      var cells = row.children;
      if (cells.length < 2) return;

      var pengguna = cells[1];
      var teks = pengguna.textContent;
      Object.keys(normalisasi).forEach(function (nama) {
        teks = teks.replace(new RegExp('\\b' + nama + '\\b', 'g'), normalisasi[nama]);
      });
      pengguna.textContent = teks;
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', normalisasiNamaLogAktivitas);
  } else {
    normalisasiNamaLogAktivitas();
  }
})();
</script>

{{-- Pratinjau landing page Admin: hapus HANYA toolbar Fit/zoom tambahan.
     Landing page tetap dipertahankan, skala 75% tetap dikendalikan oleh
     dash-script, dan scrollbar horizontal preview disembunyikan. --}}
<script>
(function () {
  function removeOnlyPreviewToolbar(frame) {
    if (!frame) return;

    frame.style.overflowX = 'hidden';
    frame.style.overflowY = 'auto';

    var zoomToolbar = frame.querySelector('.lp-zoom-toolbar');
    if (zoomToolbar) zoomToolbar.remove();

    Array.prototype.slice.call(frame.children).forEach(function (child) {
      if (child.classList.contains('lp-browser-bar') || child.classList.contains('lp-preview')) return;

      var text = (child.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
      var buttons = child.querySelectorAll ? child.querySelectorAll('button') : [];
      var hasOnlyFitButton = buttons.length === 1 &&
        (buttons[0].textContent || '').trim().toLowerCase() === 'fit';

      // Hapus hanya baris kontrol yang memang berisi Fit.
      // Jangan pernah menghapus .lp-preview karena itu adalah landing page.
      if (text === 'fit' || hasOnlyFitButton) child.remove();
    });
  }

  function setupLandingPreview() {
    document.querySelectorAll('.lp-browser-frame').forEach(function (frame) {
      removeOnlyPreviewToolbar(frame);

      if (frame.dataset.previewToolbarGuard === '1') return;
      frame.dataset.previewToolbarGuard = '1';

      var observer = new MutationObserver(function () {
        removeOnlyPreviewToolbar(frame);
      });
      observer.observe(frame, { childList: true, subtree: true });
    });
  }

  function blockRealLoginInPreview(frame) {
    if (!frame) return;
    var iframe = frame.querySelector('#lpLiveLandingFrame');
    if (!iframe || iframe.dataset.previewLoginGuard === '1') return;
    iframe.dataset.previewLoginGuard = '1';

    iframe.addEventListener('load', function () {
      try {
        var doc = iframe.contentDocument || iframe.contentWindow.document;
        if (!doc) return;

        doc.querySelectorAll('form').forEach(function (form) {
          if (form.dataset.previewLoginBlocked === '1') return;
          form.dataset.previewLoginBlocked = '1';
          form.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
          }, true);
        });
      } catch (error) {
        // Abaikan jika browser membatasi akses dokumen iframe.
      }
    });
  }

  function init() {
    setupLandingPreview();
    document.querySelectorAll('.lp-browser-frame').forEach(blockRealLoginInPreview);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  var rootObserver = new MutationObserver(init);
  rootObserver.observe(document.body, { childList: true, subtree: true });
  window.setTimeout(function () { rootObserver.disconnect(); }, 5000);
})();
</script>
