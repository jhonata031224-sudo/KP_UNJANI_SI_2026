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

{{-- Pengelompokan menu Laporan untuk dashboard Satlak. --}}
<script>
(function () {
  function setupLaporanDropdown() {
    var sidebar = document.getElementById('sidebar');
    var kirim = sidebar && sidebar.querySelector('a.side-link[href="#kirim"]');
    var riwayat = sidebar && sidebar.querySelector('a.side-link[href="#riwayat"]');

    // Hanya jalankan pada halaman yang memang memiliki Kirim Laporan dan Riwayat Laporan.
    if (!sidebar || !kirim || !riwayat || sidebar.querySelector('.laporan-menu-group')) return;

    var group = document.createElement('div');
    group.className = 'laporan-menu-group';

    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'side-link laporan-menu-toggle';
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = '<span class="dot"></span><span class="laporan-menu-label">Laporan</span><span class="laporan-menu-chevron">⌄</span>';

    var submenu = document.createElement('div');
    submenu.className = 'laporan-submenu';
    submenu.hidden = true;

    function moveToSubmenu(link, label) {
      link.classList.add('laporan-sub-link');
      var dot = link.querySelector('.dot');
      if (dot) dot.remove();
      link.textContent = label;
      submenu.appendChild(link);
    }

    moveToSubmenu(kirim, 'Kirim Laporan');
    moveToSubmenu(riwayat, 'Riwayat Laporan');

    group.appendChild(toggle);
    group.appendChild(submenu);

    // Sisipkan tepat di posisi menu Kirim Laporan sebelumnya.
    sidebar.querySelector('.side-nav').insertBefore(group, kirim.parentNode || kirim);

    toggle.addEventListener('click', function () {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!expanded));
      submenu.hidden = expanded;
      toggle.classList.toggle('open', !expanded);
    });

    // Klik submenu tetap menggunakan mekanisme tab/hash yang sudah ada.
    submenu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        toggle.setAttribute('aria-expanded', 'true');
        submenu.hidden = false;
        toggle.classList.add('open');
      });
    });

    // Jika halaman dibuka langsung dengan #kirim atau #riwayat, buka dropdown otomatis.
    if (window.location.hash === '#kirim' || window.location.hash === '#riwayat') {
      toggle.setAttribute('aria-expanded', 'true');
      submenu.hidden = false;
      toggle.classList.add('open');
    }
  }

  function injectLaporanDropdownStyles() {
    if (document.getElementById('laporan-dropdown-styles')) return;
    var style = document.createElement('style');
    style.id = 'laporan-dropdown-styles';
    style.textContent = `
      .laporan-menu-group{margin:0;padding:0;}
      .laporan-menu-toggle{width:100%;border:0;cursor:pointer;text-align:left;font:inherit;position:relative;}
      .laporan-menu-toggle .laporan-menu-label{flex:1;}
      .laporan-menu-chevron{margin-left:auto;font-size:16px;line-height:1;transition:transform .18s ease;color:var(--text-muted);}
      .laporan-menu-toggle.open .laporan-menu-chevron{transform:rotate(180deg);}
      .laporan-submenu{display:flex;flex-direction:column;padding:2px 0 4px 28px;}
      .laporan-submenu[hidden]{display:none;}
      .laporan-sub-link{min-height:34px!important;height:34px!important;padding:6px 12px!important;margin:0!important;border-radius:7px;display:flex!important;align-items:center!important;color:var(--text-muted)!important;text-decoration:none!important;font-size:12px!important;}
      .laporan-sub-link:hover{background:var(--panel-alt);color:var(--text)!important;}
      .laporan-sub-link.active{color:var(--gold-bright)!important;background:var(--panel-alt);}
    `;
    document.head.appendChild(style);
  }

  function init() {
    injectLaporanDropdownStyles();
    setupLaporanDropdown();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>
