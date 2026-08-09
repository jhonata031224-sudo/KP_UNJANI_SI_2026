<script>
  // Toggle sidebar di mobile
  const menuBtn = document.getElementById('menuBtn');
  const sidebar = document.getElementById('sidebar');
  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (window.innerWidth <= 900 && sidebar.classList.contains('open') &&
          !sidebar.contains(e.target) && e.target !== menuBtn) {
        sidebar.classList.remove('open');
      }
    });
  }

  // Batasi tinggi tabel supaya hanya menampilkan N baris pertama secara utuh;
  // sisa datanya bisa di-scroll di dalam card-nya sendiri (bukan scroll halaman).
  // Aktif hanya pada tabel yang diberi atribut data-row-limit="N".
  //
  // PENTING: offsetHeight hanya bisa dibaca dengan benar kalau elemen sedang
  // terlihat (display != none). Panel tab yang belum aktif itu display:none,
  // jadi kalau fungsi ini dipanggil saat panel-nya masih tersembunyi, semua
  // offsetHeight akan kebaca 0 dan tabel jadi "collapse" ke tinggi 0px
  // (keliatan seperti kotak kosong). Makanya fungsi ini dipanggil ulang
  // setiap kali sebuah tab diaktifkan, bukan cuma sekali di awal load.
  function terapkanRowLimitWrap(wrap) {
    if (!wrap) return;
    var limit = parseInt(wrap.getAttribute('data-row-limit'), 10) || 5;
    var table = wrap.querySelector('table');
    if (!table) return;

    // Reset dulu supaya perhitungan ulang tidak kena batas lama.
    wrap.style.maxHeight = '';
    wrap.classList.remove('tbl-scroll');

    var thead = table.querySelector('thead');
    // Baris yang lagi disembunyikan (mis. hasil pencarian/filter tidak cocok)
    // tidak dihitung sama sekali, biar batas 5 barisnya selalu berdasarkan
    // baris yang sedang benar-benar tampil.
    var rows = Array.prototype.filter.call(table.querySelectorAll('tbody tr'), function (r) {
      return r.style.display !== 'none';
    });
    if (rows.length <= limit) return;

    var height = thead ? thead.offsetHeight : 0;
    for (var i = 0; i < limit; i++) {
      height += rows[i].offsetHeight;
    }

    // Jaga-jaga: kalau ternyata masih kehitung 0 (panel belum sempat
    // ke-render penuh), jangan dipaksa set max-height 0 — biarkan tabel
    // tampil normal daripada hilang sama sekali.
    if (height <= 0) return;

    wrap.style.maxHeight = height + 'px';
    wrap.classList.add('tbl-scroll');
  }
  // Diekspos supaya bisa dipanggil langsung untuk satu tabel spesifik
  // (mis. dari skrip searching/filter tabel), tanpa perlu hitung ulang
  // seluruh tabel lain di panel yang sama.
  window.terapkanRowLimitWrap = terapkanRowLimitWrap;

  function terapkanRowLimit(panel) {
    if (!panel) return;
    panel.querySelectorAll('[data-row-limit]').forEach(terapkanRowLimitWrap);
  }

  // Tab switching sederhana (prototype — belum tersambung ke backend laporan)
  const links = document.querySelectorAll('[data-tab-link]');
  const panels = document.querySelectorAll('[data-tab-panel]');
  links.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const target = link.getAttribute('data-tab-link');
      links.forEach(l => l.classList.remove('active'));
      panels.forEach(p => p.classList.remove('active'));
      link.classList.add('active');
      const targetPanel = document.querySelector(`[data-tab-panel="${target}"]`);
      targetPanel.classList.add('active');
      terapkanRowLimit(targetPanel);
      if (window.innerWidth <= 900) sidebar.classList.remove('open');
      window.scrollTo({top:0, behavior:'smooth'});
    });
  });

  // Hitung juga untuk panel yang aktif di awal (mis. "Ringkasan").
  document.querySelectorAll('.tab-panel.active').forEach(terapkanRowLimit);

  // Ganti tema (dark / light) — 1 tombol, tersimpan di localStorage, berlaku di semua halaman.
  // Ikon matahari/bulan berganti otomatis lewat CSS berdasar atribut data-theme.
  (function(){
    var THEME_KEY = 'siberad-theme';
    var btn = document.getElementById('themeToggleBtn');

    function applyTheme(theme){
      if (theme === 'light') {
        document.documentElement.setAttribute('data-theme', 'light');
      } else {
        document.documentElement.removeAttribute('data-theme');
      }
      if (btn) {
        btn.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
      }
    }

    var saved = 'dark';
    try { saved = localStorage.getItem(THEME_KEY) || 'dark'; } catch (e) {}
    applyTheme(saved);

    if (btn) {
      btn.addEventListener('click', function(){
        var current = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
        var next = current === 'light' ? 'dark' : 'light';
        try { localStorage.setItem(THEME_KEY, next); } catch (e) {}
        applyTheme(next);
      });
    }
  })();

  // Bersihkan pemisah dekoratif dari teks yang tampil di seluruh dashboard role.
  // Tanda hubung di dalam kata seperti "antar-satuan" tidak diubah.
  (function(){
    function cleanDecorativeSeparators(root) {
      if (!root) return;
      var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
      var nodes = [];
      var node;
      while (node = walker.nextNode()) nodes.push(node);
      nodes.forEach(function(textNode){
        if (!textNode.nodeValue || !textNode.nodeValue.trim()) return;
        var value = textNode.nodeValue;
        value = value.replace(/\s*\/\/\s*/g, ' ');
        value = value.replace(/(^|\s)[-—](?=\s|$)/g, '$1');
        textNode.nodeValue = value.replace(/ {2,}/g, ' ');
      });
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function(){ cleanDecorativeSeparators(document.body); });
    } else {
      cleanDecorativeSeparators(document.body);
    }
  })();
</script>