<style>
/*
 * Riwayat Laporan DANPUS: pastikan area aksi benar-benar menerima pointer.
 * Baris arsip dibuat dinamis, dan beberapa enhancement tabel juga memakai
 * stacking context sendiri. Tombol Detail harus selalu berada di atasnya.
 */
#status .archive-request-row td:last-child,
#status .archive-request-row .archive-detail-cell{
  position:relative;
  z-index:100120 !important;
  isolation:isolate;
  pointer-events:auto !important;
}
#status .archive-request-row .archive-detail-btn{
  position:relative !important;
  z-index:100121 !important;
  display:inline-flex !important;
  align-items:center;
  justify-content:center;
  pointer-events:auto !important;
  touch-action:manipulation !important;
  cursor:pointer !important;
  user-select:none;
}

/* Modal detail harus berada di atas seluruh modal/overlay enhancement Danpus. */
#reportDetailModal{
  z-index:100500 !important;
  pointer-events:none !important;
}
#reportDetailModal.open{
  pointer-events:auto !important;
}
#reportDetailModal .report-modal-card{
  position:relative;
  z-index:100501 !important;
  pointer-events:auto !important;
}
</style>

<script>
(function(){
  'use strict';

  function openDetail(button){
    if(!button) return false;
    if(typeof window.openReportDetail !== 'function') return false;
    window.openReportDetail(button);
    return true;
  }

  function getDetailButtonFromNode(node){
    if(!(node instanceof Element)) return null;
    return node.closest('.archive-detail-btn');
  }

  function findDetailButtonAtPoint(clientX, clientY){
    if(typeof clientX !== 'number' || typeof clientY !== 'number') return null;

    const buttons = Array.from(document.querySelectorAll('#status .archive-detail-btn'));
    let best = null;
    let bestArea = Infinity;

    for(const button of buttons){
      const rect = button.getBoundingClientRect();
      if(rect.width <= 0 || rect.height <= 0) continue;
      if(clientX < rect.left || clientX > rect.right || clientY < rect.top || clientY > rect.bottom) continue;

      const area = rect.width * rect.height;
      if(area < bestArea){
        best = button;
        bestArea = area;
      }
    }

    return best;
  }

  /*
   * Lapisan pertama: event delegation normal.
   * Ini menangani tombol walaupun baris Riwayat dibuat ulang via AJAX.
   */
  document.addEventListener('click', function(event){
    const button = getDetailButtonFromNode(event.target);
    if(!button) return;

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    openDetail(button);
  }, true);

  /*
   * Lapisan kedua: event-capture berbasis posisi.
   * Jika ada elemen transparan/overlay yang benar-benar berada di atas tombol,
   * target event bukan tombol Detail. Pada fase capture document kita tetap
   * bisa melihat koordinat klik dan mencocokkannya dengan bounding rect tombol.
   */
  document.addEventListener('click', function(event){
    if(event.__danpusDetailHandled) return;

    const directButton = getDetailButtonFromNode(event.target);
    const button = directButton || findDetailButtonAtPoint(event.clientX, event.clientY);
    if(!button) return;

    event.__danpusDetailHandled = true;
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    openDetail(button);
  }, true);

  /* Pointer fallback untuk browser/input yang tidak mengirim click dengan cara biasa. */
  document.addEventListener('pointerup', function(event){
    const button = getDetailButtonFromNode(event.target) || findDetailButtonAtPoint(event.clientX, event.clientY);
    if(!button) return;

    openDetail(button);
  }, true);

  /*
   * Saat tabel riwayat dirender ulang, pastikan sel aksi dan tombol mendapat
   * class/layer yang benar tanpa perlu mengubah renderer arsip yang sudah ada.
   */
  function enhanceArchiveDetailButtons(root){
    (root || document).querySelectorAll('#status .archive-request-row').forEach(function(row){
      const button = row.querySelector('.archive-detail-btn');
      if(!button) return;

      const cell = button.closest('td');
      if(cell) cell.classList.add('archive-detail-cell');
      button.setAttribute('aria-label', 'Lihat detail laporan');
      button.setAttribute('title', 'Lihat detail laporan');
    });
  }

  window.siberadEnhanceDanpusHistoryDetail = enhanceArchiveDetailButtons;

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', function(){
      enhanceArchiveDetailButtons(document);
      setTimeout(function(){enhanceArchiveDetailButtons(document);}, 0);
      setTimeout(function(){enhanceArchiveDetailButtons(document);}, 300);
    });
  }else{
    enhanceArchiveDetailButtons(document);
    setTimeout(function(){enhanceArchiveDetailButtons(document);}, 0);
    setTimeout(function(){enhanceArchiveDetailButtons(document);}, 300);
  }

  /* Observer supaya tombol tetap aman setelah Riwayat diisi/di-refresh realtime. */
  const observer = new MutationObserver(function(mutations){
    for(const mutation of mutations){
      if(mutation.addedNodes && mutation.addedNodes.length){
        enhanceArchiveDetailButtons(document);
        break;
      }
    }
  });

  if(document.body){
    observer.observe(document.body, {childList:true, subtree:true});
  }else{
    document.addEventListener('DOMContentLoaded', function(){
      if(document.body) observer.observe(document.body, {childList:true, subtree:true});
    });
  }
})();
</script>
