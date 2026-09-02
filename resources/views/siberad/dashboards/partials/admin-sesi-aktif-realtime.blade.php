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

        // "Terakhir Aktif" (kolom .js-terakhir-aktif) pakai teks relatif
        // ("1 detik yang lalu") yang bergeser tiap detik semata karena
        // waktu jalan -- BUKAN berarti datanya beneran berubah. Kalau ini
        // ikut dibandingkan mentah-mentah lewat outerHTML, baris akan
        // "kelihatan beda" tiap poll (tiap 4 detik) SELAMANYA, bikin baris
        // kedip kuning terus-menerus walau tidak ada login/logout baru.
        // signature() nge-strip teks kolom itu dulu sebelum dibandingkan,
        // supaya animasi cuma nyala kalau ada perubahan beneran (nama,
        // IP, perangkat, atau sesi baru/hilang).
        function signature(el) {
          var clone = el.cloneNode(true);
          var cell = clone.querySelector('.js-terakhir-aktif');
          if (cell) cell.textContent = '';
          return clone.outerHTML;
        }

        // Field selain "Terakhir Aktif" (mis. IP Address) SEHARUSNYA stabil
        // antar-poll buat sesi yang sama, tapi di hosting production kadang
        // masih bisa "nyasar" sesaat (glitch jaringan/proxy sekali lewat).
        // Biar animasi kedip cuma nyala buat perubahan yang BENERAN
        // (persisten), nilai baru yang beda harus muncul 2x poll BERTURUT-
        // TURUT (lewat data-pending-sig) sebelum baris diganti+dianimasikan.
        // Kalau di poll berikutnya nilainya balik lagi/beda lagi, dianggap
        // gangguan sesaat dan diabaikan -- baris lama tetap dipertahankan.
        Array.prototype.slice.call(tbody.querySelectorAll('[data-session-id]')).forEach(function (row) {
          var id = row.getAttribute('data-session-id');
          var replacement = freshById[id];
          if (!replacement) { row.remove(); return; }
          seen[id] = true;

          var newSig = signature(replacement);
          if (newSig === signature(row)) {
            // Tidak ada perubahan beneran -- reset status "pending" (kalau
            // ada) & cuma update teks "Terakhir Aktif" di tempat.
            delete row.dataset.pendingSig;
            var oldCell = row.querySelector('.js-terakhir-aktif');
            var newCell = replacement.querySelector('.js-terakhir-aktif');
            if (oldCell && newCell && oldCell.textContent !== newCell.textContent) {
              oldCell.textContent = newCell.textContent;
            }
            return;
          }

          if (row.dataset.pendingSig === newSig) {
            // Nilai baru ini sudah konsisten muncul 2x berturut-turut ->
            // beneran berubah, baru diganti & dianimasikan.
            if (animate) replacement.classList.add('siberad-row-updated');
            row.replaceWith(replacement);
          } else {
            // Baru pertama kali kelihatan beda -- tunggu 1 poll lagi dulu
            // buat konfirmasi sebelum dianggap perubahan beneran.
            row.dataset.pendingSig = newSig;
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
