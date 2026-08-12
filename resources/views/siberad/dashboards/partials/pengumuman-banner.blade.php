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
