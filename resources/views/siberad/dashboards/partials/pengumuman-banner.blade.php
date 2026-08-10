@if(($pengumumanAktif ?? collect())->isNotEmpty())
  <div class="pengumuman-banner-wrap" id="pengumumanBannerWrap">
    @foreach($pengumumanAktif as $p)
      <div class="pengumuman-banner" data-pengumuman-id="{{ $p->id }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 3v6c0 5-3.4 8.4-8 11-4.6-2.6-8-6-8-11V5l8-3z"/><path d="M12 8v5"/><path d="M12 16.5h.01"/></svg>
        <div class="pengumuman-banner-body">
          <b>{{ $p->judul }}</b>
          <span>{{ $p->isi }}</span>
        </div>
        <button type="button" class="pengumuman-banner-close" aria-label="Tutup pengumuman" onclick="this.closest('.pengumuman-banner').remove()">
          <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>
        </button>
      </div>
    @endforeach
  </div>

  <style>
    .pengumuman-banner-wrap{display:flex;flex-direction:column;gap:10px;margin-bottom:20px;}
    .pengumuman-banner{display:flex;align-items:flex-start;gap:12px;background:var(--gold-dim);border:1px solid var(--border);border-radius:10px;padding:13px 16px;}
    .pengumuman-banner svg{width:19px;height:19px;color:var(--gold-bright);flex-shrink:0;margin-top:2px;}
    .pengumuman-banner-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;}
    .pengumuman-banner-body b{font-family:var(--display);font-size:13.5px;color:var(--text);}
    .pengumuman-banner-body span{font-size:12.5px;color:var(--text-muted);line-height:1.55;}
    .pengumuman-banner-close{background:none;border:none;cursor:pointer;color:var(--text-dim);flex-shrink:0;padding:2px;line-height:0;}
    .pengumuman-banner-close:hover{color:var(--gold-bright);}
    .pengumuman-banner-close svg{width:15px;height:15px;margin:0;}
  </style>
@endif

@include('siberad.dashboards.partials.profile-enhancements')
@include('siberad.dashboards.partials.notification-controls')

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

    var links = document.querySelectorAll('.side-link[href^="#"]');
    var panels = document.querySelectorAll('.tab-panel');
    links.forEach(function (link) {
      if (link.dataset.tabBound) return;
      link.dataset.tabBound = '1';
      link.addEventListener('click', function (e) {
        var id = link.getAttribute('href').slice(1);
        var panel = document.getElementById(id);
        if (!panel) return;
        e.preventDefault();
        panels.forEach(function (p) { p.classList.remove('active'); });
        panel.classList.add('active');
        links.forEach(function (l) { l.classList.remove('active'); });
        link.classList.add('active');
        try { sessionStorage.setItem('siberad-role-active-tab', id); } catch (err) {}
        if (sidebar && window.innerWidth <= 900) sidebar.classList.remove('open');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    });

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

    var savedTab = null;
    try { savedTab = sessionStorage.getItem('siberad-role-active-tab'); } catch (err) {}
    if (savedTab) {
      var savedPanel = document.getElementById(savedTab);
      var savedLink = document.querySelector('.side-link[href="#' + savedTab + '"]');
      if (savedPanel && savedLink) {
        panels.forEach(function (p) { p.classList.remove('active'); });
        savedPanel.classList.add('active');
        links.forEach(function (l) { l.classList.remove('active'); });
        savedLink.classList.add('active');
      }
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initRoleUi);
  else initRoleUi();
})();
</script>
