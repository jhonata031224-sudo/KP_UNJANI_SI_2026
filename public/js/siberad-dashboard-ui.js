(function () {
  'use strict';

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>\'\"]/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[ch];
    });
  }

  function addStyles() {
    if (document.getElementById('siberad-global-ui-style')) return;

    var style = document.createElement('style');
    style.id = 'siberad-global-ui-style';
    style.textContent = `
      .siberad-notif-dot{position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:var(--red);box-shadow:0 0 0 2px var(--panel,#0c2417);}
      .siberad-notif-head{border-bottom:1px solid var(--border-soft);display:flex;justify-content:space-between;align-items:center;gap:8px;}
      .siberad-mark-read{font-size:11px;color:var(--gold-bright);background:none;border:none;cursor:pointer;padding:0;font:inherit;}
      .siberad-notif-item svg{color:var(--gold-bright);flex-shrink:0;margin-top:2px;}
      .siberad-notif-message{font-size:12.5px;line-height:1.5;color:var(--text);}
      .siberad-notif-time{font-size:11px;color:var(--text-dim);margin-top:2px;}
      .siberad-notif-empty{text-align:center;padding:20px 6px 8px;color:var(--text-dim);}
      .siberad-notif-empty svg{margin:0 auto 14px;display:block;}
      .siberad-notif-empty p{margin:0;font-size:12.5px;line-height:1.6;color:var(--text-muted);}

      /* Rapikan submenu sidebar Laporan agar konsisten dengan dropdown referensi Danpus. */
      .side-dropdown .side-sublink{display:flex!important;align-items:center!important;gap:0!important;min-height:32px!important;height:32px!important;padding:7px 12px 7px 32px!important;margin:0!important;font-size:12.5px!important;font-weight:500!important;line-height:1.2!important;text-transform:none!important;}
      .side-dropdown .side-sublink .dot{display:none!important;}
      .side-dropdown .side-dropdown-menu{gap:1px!important;padding:0!important;margin:0!important;}
      .side-dropdown.open .side-dropdown-menu{margin-top:3px!important;}
      .side-dropdown .side-sublink:hover{color:var(--text)!important;background:var(--hover-tint)!important;}
      .side-dropdown .side-sublink.active{color:var(--gold-bright)!important;background:var(--gold-dim)!important;border-color:var(--border)!important;font-weight:600!important;}
      .side-dropdown-toggle{font-size:13.5px!important;font-weight:500!important;}
      .side-dropdown.open .side-dropdown-toggle{color:var(--text)!important;}

      .siberad-logout-overlay{position:fixed;inset:0;z-index:10000;display:none;align-items:center;justify-content:center;padding:24px;background:rgba(18,20,15,.42);backdrop-filter:blur(8px);}
      .siberad-logout-overlay.open{display:flex;}
      .siberad-logout-card{width:min(420px,calc(100vw - 32px));background:var(--panel);border:1px solid var(--border-soft);border-radius:16px;padding:30px 30px 28px;box-shadow:0 24px 70px rgba(0,0,0,.28);text-align:center;}
      .siberad-logout-icon{width:58px;height:58px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;background:var(--red-dim);color:var(--red);}
      .siberad-logout-icon svg{width:28px;height:28px;}
      .siberad-logout-card h3{font-family:var(--display);font-size:21px;font-weight:700;color:var(--text);margin:0 0 10px;}
      .siberad-logout-card p{font-size:13px;line-height:1.65;color:var(--text-muted);margin:0 0 24px;}
      .siberad-logout-actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
      .siberad-logout-actions button{height:42px;border-radius:8px;padding:0 14px;font-family:var(--mono);font-size:11px;font-weight:700;letter-spacing:.06em;cursor:pointer;}
      .siberad-logout-cancel{background:var(--panel-alt);border:1px solid var(--border);color:var(--text);}
      .siberad-logout-confirm{background:transparent;border:1px solid var(--red);color:var(--red);}
      @media(max-width:520px){.siberad-logout-actions{grid-template-columns:1fr;}.siberad-logout-card{padding:25px 20px 22px;}}
    `;
    document.head.appendChild(style);
  }

  function initNotifications() {
    var actions = document.querySelector('.topbar-actions');
    if (!actions || document.getElementById('notifMenu')) return;

    var profileMenu = actions.querySelector('#profileMenu');
    if (!profileMenu) return;

    var notifications = Array.isArray(window.__SIBERAD_NOTIFICATIONS__) ? window.__SIBERAD_NOTIFICATIONS__ : [];
    var wrapper = document.createElement('div');
    wrapper.className = 'profile-menu';
    wrapper.id = 'notifMenu';
    wrapper.innerHTML = `
      <button type="button" class="btn-icon-toggle" id="notifBtn" aria-label="Notifikasi" aria-haspopup="menu" aria-expanded="false" style="position:relative;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        ${notifications.length ? '<span class="siberad-notif-dot"></span>' : ''}
      </button>
      <div class="profile-dropdown" id="notifDropdown" role="menu" aria-label="Notifikasi">
        <div class="profile-dropdown-head siberad-notif-head">
          <div class="profile-dropdown-name" style="font-size:14px;">Notifikasi</div>
          ${notifications.length ? '<form method="POST" action="/notifikasi/baca-semua" class="siberad-mark-read-form"><input type="hidden" name="_token" value="' + escapeHtml(window.__SIBERAD_CSRF__) + '"><button type="submit" class="siberad-mark-read">Tandai dibaca</button></form>' : ''}
        </div>
        <div class="siberad-notif-list">
          ${notifications.length ? notifications.map(function (n) {
            return '<div class="profile-dropdown-item siberad-notif-item" style="align-items:flex-start;white-space:normal;cursor:default;">' +
              '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>' +
              '<div><div class="siberad-notif-message">' + escapeHtml(n.message || 'Laporan baru masuk.') + '</div><div class="siberad-notif-time">' + escapeHtml(n.time || '') + '</div></div>' +
              '</div>';
          }).join('') : '<div class="siberad-notif-empty"><svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg><p>Belum ada notifikasi saat ini.</p></div>'}
        </div>
      </div>`;

    actions.insertBefore(wrapper, profileMenu);

    var btn = wrapper.querySelector('#notifBtn');
    var dropdown = wrapper.querySelector('#notifDropdown');
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      document.querySelectorAll('.profile-dropdown.open').forEach(function (el) {
        if (el !== dropdown) el.classList.remove('open');
      });
      var open = dropdown.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!wrapper.contains(e.target)) {
        dropdown.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function initLogoutConfirmation() {
    var forms = document.querySelectorAll('form.logout-form, form[action$="/logout"]');
    if (!forms.length) return;

    var modal = document.getElementById('siberadLogoutModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'siberadLogoutModal';
      modal.className = 'siberad-logout-overlay';
      modal.innerHTML = `
        <div class="siberad-logout-card" role="dialog" aria-modal="true" aria-labelledby="siberadLogoutTitle">
          <div class="siberad-logout-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg></div>
          <h3 id="siberadLogoutTitle">Keluar dari akun?</h3>
          <p>Sesi kamu akan diakhiri dan kamu perlu login kembali untuk mengakses SIBERAD.</p>
          <div class="siberad-logout-actions"><button type="button" class="siberad-logout-cancel">BATAL</button><button type="button" class="siberad-logout-confirm">YA, KELUAR</button></div>
        </div>`;
      document.body.appendChild(modal);
    }

    var pendingForm = null;
    function close() {
      modal.classList.remove('open');
      pendingForm = null;
      document.body.style.overflow = '';
    }
    function open(form) {
      pendingForm = form;
      modal.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    modal.querySelector('.siberad-logout-cancel').onclick = close;
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('open')) close(); });
    modal.querySelector('.siberad-logout-confirm').onclick = function () {
      if (!pendingForm) return close();
      var form = pendingForm;
      pendingForm = null;
      form.dataset.skipLogoutConfirm = '1';
      form.submit();
    };

    forms.forEach(function (form) {
      if (form.dataset.globalLogoutBound === '1') return;
      form.dataset.globalLogoutBound = '1';
      form.addEventListener('submit', function (e) {
        if (form.dataset.skipLogoutConfirm === '1') return;
        e.preventDefault();
        open(form);
      });
    });
  }

  function boot() {
    addStyles();
    initNotifications();
    initLogoutConfirmation();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();