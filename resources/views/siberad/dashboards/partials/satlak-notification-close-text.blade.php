<style>
  #notifDropdown .notif-head {
    justify-content: space-between !important;
  }
  #notifDropdown .siberad-notif-close-text {
    border: 0;
    background: transparent;
    color: var(--gold-bright);
    font: inherit;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 2px;
    cursor: pointer;
    line-height: 1;
  }
  #notifDropdown .siberad-notif-close-text:hover {
    text-decoration: underline;
  }
  #notifDropdown .siberad-notif-head-actions {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  #notifDropdown .siberad-notif-hapus-semua-text {
    border: 0;
    background: transparent;
    color: var(--red, #c83b3b);
    font: inherit;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 2px;
    cursor: pointer;
    line-height: 1;
  }
  #notifDropdown .siberad-notif-hapus-semua-text:hover {
    text-decoration: underline;
  }
</style>
<script>
(function () {
  function initNotificationCloseText() {
    var dropdown = document.getElementById('notifDropdown');
    var header = dropdown && (dropdown.querySelector('.notif-head') || dropdown.querySelector('.profile-dropdown-head'));
    if (!dropdown || !header || header.querySelector('.siberad-notif-close-text')) return;

    // Kedua tombol dibungkus satu wrapper (bukan langsung anak header) biar
    // tetap NEMPEL bersebelahan di kanan, walau header-nya pakai
    // justify-content:space-between (title di kiri, grup tombol ini di
    // kanan) -- kalau di-append terpisah, "Hapus Semua" bakal kedorong ke
    // tengah header, jauh dari "Tutup".
    var actionsWrap = document.createElement('div');
    actionsWrap.className = 'siberad-notif-head-actions';

    var hapusSemuaButton = document.createElement('button');
    hapusSemuaButton.type = 'button';
    hapusSemuaButton.className = 'siberad-notif-hapus-semua-text';
    hapusSemuaButton.textContent = 'Hapus Semua';
    hapusSemuaButton.setAttribute('aria-label', 'Hapus semua notifikasi');
    hapusSemuaButton.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      if (typeof window.siberadHapusSemuaNotifikasi === 'function') window.siberadHapusSemuaNotifikasi();
    });

    var closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'siberad-notif-close-text';
    closeButton.textContent = 'Tutup';
    closeButton.setAttribute('aria-label', 'Tutup notifikasi');
    closeButton.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      dropdown.classList.remove('open');
      var notifButton = document.getElementById('notifBtn');
      if (notifButton) {
        notifButton.classList.remove('open');
        notifButton.setAttribute('aria-expanded', 'false');
      }
    });
    actionsWrap.appendChild(hapusSemuaButton);
    actionsWrap.appendChild(closeButton);
    header.appendChild(actionsWrap);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotificationCloseText);
  } else {
    initNotificationCloseText();
  }

  // Notifikasi dibuat oleh partial lain; cek kembali setelah seluruh shell selesai.
  setTimeout(initNotificationCloseText, 100);
  setTimeout(initNotificationCloseText, 400);
  setTimeout(initNotificationCloseText, 1000);
})();
</script>
