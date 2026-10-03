<script>
(function () {
  function updateDanpusMonitoringText() {
    var replacements = {
      'Ringkasan 4 Satlak': 'Ringkasan Seluruh Unit',
      'Komposisi status seluruh laporan dari 4 Satlak.': 'Komposisi status seluruh laporan dari seluruh unit pelaporan.',
      'Aktivitas per Satlak': 'Aktivitas per Unit',
      'Perbandingan jumlah laporan yang dibuat oleh masing-masing Satlak.': 'Perbandingan jumlah laporan yang dibuat oleh masing-masing unit.',
      'Ringkasan 4 Satlak. Pilih “Lihat Aktivitas” untuk membuka daftar laporan secara detail.': 'Ringkasan seluruh unit pelaporan. Pilih “Lihat Aktivitas” untuk membuka daftar laporan secara detail.',
      'Rekap laporan dari 4 Satlak yang sudah mendapat keputusan akhir (diterima/disetujui atau ditolak).': 'Rekap laporan dari seluruh unit pelaporan yang sudah mendapat keputusan akhir (diterima/disetujui atau ditolak).'
    };

    var root = document.querySelector('.pimp-page');
    if (!root) return;

    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    var nodes = [];
    var node;
    while ((node = walker.nextNode())) nodes.push(node);

    nodes.forEach(function (textNode) {
      var value = textNode.nodeValue;
      Object.keys(replacements).forEach(function (source) {
        if (value.indexOf(source) !== -1) value = value.split(source).join(replacements[source]);
      });
      textNode.nodeValue = value;
    });

    root.querySelectorAll('th').forEach(function (cell) {
      if (cell.textContent.trim() === 'Satlak') cell.textContent = 'Unit';
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateDanpusMonitoringText);
  } else {
    updateDanpusMonitoringText();
  }
})();
</script>
