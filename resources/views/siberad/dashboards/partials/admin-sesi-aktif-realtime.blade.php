<script>
(function () {
  var tbody = document.querySelector('[data-tab-panel="sesi-aktif"] table.dtbl tbody');
  if (!tbody) return;
  var url = @json(route('admin.sessions.realtime'));
  var busy = false;
  // "Terakhir Aktif" pakai teks relatif ("X detik yang lalu") yang berubah
  // tiap detik -- poll PERTAMA dipakai buat baseline TANPA animasi, biar
  // pergeseran teks sekecil itu (bukan sesi yang beneran baru/hilang) tidak
  // bikin semua baris ikut kedip pas dashboard baru dibuka.
  var animate = false;

  // Tab admin lain (display:none, BUKAN di-unmount) tidak perlu ikut nge-
  // fetch data yang tidak kelihatan -- poll otomatis lanjut lagi begitu
  // admin balik ke tab ini.
  function tabIniAktif() {
    var panel = document.querySelector('[data-tab-panel="sesi-aktif"]');
    return !panel || panel.classList.contains('active');
  }

  function poll() {
    if (busy || !tabIniAktif()) return;
    busy = true;
    fetch(url + '?_=' + Date.now(), { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (!data || typeof data.items_html !== 'string') return;
        var temp = document.createElement('tbody');
        temp.innerHTML = data.items_html;
        var fresh = Array.prototype.slice.call(temp.children);
        var freshById = {};
        fresh.forEach(function (el) { var id = el.getAttribute('data-session-id'); if (id) freshById[id] = el; });
        var seen = {};

        Array.prototype.slice.call(tbody.querySelectorAll('[data-session-id]')).forEach(function (row) {
          var id = row.getAttribute('data-session-id');
          var replacement = freshById[id];
          if (replacement) {
            seen[id] = true;
            if (replacement.outerHTML !== row.outerHTML) {
              if (animate) replacement.classList.add('siberad-row-updated');
              row.replaceWith(replacement);
            }
          } else {
            row.remove();
          }
        });

        // Sesi baru (login baru selagi tab ini terbuka) diselipkan sesuai
        // urutan dari server (terbaru duluan berdasarkan last_activity).
        var anchor = tbody.firstChild;
        fresh.forEach(function (el) {
          var id = el.getAttribute('data-session-id');
          if (id && seen[id]) return;
          if (animate) el.classList.add('siberad-row-in');
          tbody.insertBefore(el, anchor);
        });

        Array.prototype.slice.call(tbody.querySelectorAll('tr')).forEach(function (r) { if (r.querySelector('.empty-state')) r.remove(); });
        animate = true;
      })
      .catch(function () {})
      .finally(function () { busy = false; });
  }

  poll();
  setInterval(poll, 4000);
})();
</script>
