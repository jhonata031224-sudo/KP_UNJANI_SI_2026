<script>
(function () {
  var tbody = document.querySelector('[data-tab-panel="reset-password"] #tblResetPassword tbody');
  if (!tbody) return;
  var url = @json(route('admin.permintaan-reset-password.realtime'));
  var lastSeen = 0, polling = false, initial = true;

  // DULU ada gate tabIniAktif() (poll cuma jalan kalau tab "Permintaan
  // Ganti Password" lagi aktif) -- niatnya hemat resource, tapi efeknya
  // toast "Ada permintaan ganti password baru" SAMA SEKALI gak muncul
  // selama admin buka tab LAIN (mis. Dashboard), dan begitu pindah ke tab
  // ini pun harus nunggu sampai 1 siklus interval penuh (dulu 4 detik)
  // sebelum data+toast-nya nongol -- notifikasi jadi kerasa "delay"/gak
  // reliable. Dihapus total -- poller ini sekarang jalan terus di
  // background APAPUN tab yang lagi aktif, sama kayak mayoritas poller
  // lain (syncAdminKpis dkk), biar toast beneran realtime kapan pun admin
  // lagi di halaman manapun (dilaporkan user 2026-09-14).
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

  // Poll pertama LANGSUNG jalan (dulu nunggu interval pertama, nambah delay
  // start-up) -- initial=true di sini masih menjaga poll pertama ini gak
  // nge-toast buat request LAMA yang udah ada sebelum halaman dibuka, cuma
  // buat nyamain baseline lastSeen. Interval 3 detik (dulu 4) biar sejalan
  // sama standar poll fitur lain (audit polling 2026-09-14).
  poll();
  setInterval(poll, 3000);
})();
</script>
