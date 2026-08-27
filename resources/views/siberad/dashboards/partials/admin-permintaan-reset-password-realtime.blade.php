<script>
(function () {
  var tbody = document.querySelector('[data-tab-panel="reset-password"] #tblResetPassword tbody');
  if (!tbody) return;
  var url = @json(route('admin.permintaan-reset-password.realtime'));
  var lastSeen = 0, polling = false, initial = true;

  function poll() {
    if (polling) return;
    polling = true;
    fetch(url + '?since=' + (initial ? 0 : lastSeen) + '&_=' + Date.now(), { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (!data) return;
        var inserted = 0;
        if (typeof data.items_html === 'string' && data.items_html) {
          var temp = document.createElement('tbody');
          temp.innerHTML = data.items_html;
          var rows = Array.prototype.slice.call(temp.children);
          if (rows.length) {
            Array.prototype.slice.call(tbody.querySelectorAll('tr')).forEach(function (r) { if (r.querySelector('.empty-state')) r.remove(); });
            rows.reverse().forEach(function (row) {
              var id = row.getAttribute('data-reset-id');
              if (!id || tbody.querySelector('[data-reset-id="' + id + '"]')) return;
              row.classList.add('siberad-row-in');
              tbody.insertBefore(row, tbody.firstChild);
              inserted++;
            });
          }
        }
        if (typeof data.latest_id === 'number') lastSeen = Math.max(lastSeen, data.latest_id);
        if (!initial && inserted > 0) {
          // Ikutin urutan sort yang lagi aktif (Terbaru/Terlama) -- tanpa ini,
          // baris baru selalu nempel di atas walau admin lagi milih "Terlama".
          var sortSelect = document.getElementById('tblResetPasswordSort');
          if (sortSelect && window.terapkanTabelSort) window.terapkanTabelSort('tblResetPassword', sortSelect.value);
          else if (window.terapkanTabelFilter) window.terapkanTabelFilter('tblResetPassword');
          if (window.siberadShowToast) {
            window.siberadShowToast('success', inserted === 1 ? 'Ada 1 permintaan ganti password baru.' : 'Ada ' + inserted + ' permintaan ganti password baru.');
          }
        }
        initial = false;
      })
      .catch(function () {})
      .finally(function () { polling = false; });
  }

  setInterval(poll, 4000);
})();
</script>
