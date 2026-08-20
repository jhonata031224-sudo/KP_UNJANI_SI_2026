<script>
(function(){
  'use strict';

  // Tombol Detail pada baris Riwayat Laporan dibuat secara dinamis oleh
  // danpus-permintaan-arsip-mode, sehingga onclick dari tabel statis tidak ada.
  // Pakai event delegation agar tombol tetap berfungsi setelah tabel di-refresh.
  document.addEventListener('click', function(event){
    const button = event.target.closest('.archive-detail-btn');
    if(!button) return;

    event.preventDefault();
    event.stopPropagation();

    if(typeof window.openReportDetail === 'function'){
      window.openReportDetail(button);
    }
  });
})();
</script>
