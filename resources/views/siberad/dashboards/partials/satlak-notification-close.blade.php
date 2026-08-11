@if(in_array(strtoupper(trim($satuan->kode ?? '')), ['SATLAKKAL','SATLAKSISOS','SATLAKDAK','SATLAKDUKTEK'], true))
<style>
  /* Tombol tutup notifikasi Satlak — disamakan dengan kontrol notifikasi Danpus. */
  #notifDropdown .satlak-notif-close {
    width:24px;
    height:24px;
    flex:0 0 24px;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:4px;
    border:0;
    border-radius:5px;
    background:transparent;
    color:var(--text-dim);
    cursor:pointer;
    transition:background .15s ease,color .15s ease,transform .15s ease;
  }
  #notifDropdown .satlak-notif-close:hover {
    background:var(--hover-tint);
    color:var(--text);
  }
  #notifDropdown .satlak-notif-close:active { transform:scale(.94); }
  #notifDropdown .satlak-notif-close svg {
    width:16px;
    height:16px;
    display:block;
    stroke:currentColor;
    fill:none;
  }
  #notifDropdown .profile-dropdown-head.satlak-notif-head {
    display:flex !important;
    align-items:center !important;
    justify-content:space-between !important;
    gap:8px !important;
  }
</style>
<script>
(function () {
  'use strict';

  function initSatlakNotificationClose() {
    var dropdown = document.getElementById('notifDropdown');
    if (!dropdown || dropdown.dataset.satlakCloseReady === '1') return;

    var header = dropdown.querySelector('.profile-dropdown-head');
    if (!header) return;

    header.classList.add('satlak-notif-head');

    if (!header.querySelector('.satlak-notif-close')) {
      var closeButton = document.createElement('button');
      closeButton.type = 'button';
      closeButton.className = 'satlak-notif-close';
      closeButton.setAttribute('aria-label', 'Tutup notifikasi');
      closeButton.setAttribute('title', 'Tutup');
      closeButton.innerHTML = '<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>';
      closeButton.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var button = document.getElementById('notifBtn');
        dropdown.classList.remove('open');
        if (button) {
          button.classList.remove('open');
          button.setAttribute('aria-expanded', 'false');
        }
      });
      header.appendChild(closeButton);
    }

    dropdown.dataset.satlakCloseReady = '1';
  }

  function boot() {
    initSatlakNotificationClose();
    // global-shell-enhancements dapat membuat dropdown sesudah script pertama berjalan.
    setTimeout(initSatlakNotificationClose, 80);
    setTimeout(initSatlakNotificationClose, 250);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
</script>
@endif
