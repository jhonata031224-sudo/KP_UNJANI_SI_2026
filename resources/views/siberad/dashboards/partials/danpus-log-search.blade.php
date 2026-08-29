<style>
/* Danpus: pencarian dan pengurutan perihal pada Log Aktivitas. */
.danpus-log-search{display:flex;align-items:center;gap:8px;margin:0 0 14px}
.danpus-log-search-box{position:relative;width:330px;max-width:100%}
.danpus-log-search .search-icon{position:absolute;left:11px;top:50%;width:16px;height:16px;transform:translateY(-50%);color:var(--p-muted);pointer-events:none}
.danpus-log-search input{box-sizing:border-box;width:100%;height:38px;border:1px solid var(--p-border);border-radius:9px;outline:0;background:var(--p-surface-2);color:var(--p-text);font:inherit;font-size:12px;padding:8px 11px 8px 35px}
.danpus-log-search input:focus{border-color:#c97a00;box-shadow:0 0 0 3px rgba(201,122,0,.10)}
.danpus-log-search input::placeholder{color:var(--p-muted)}
/* Dropdown "Terbaru/Terlama"-nya sekarang <select> asli yang di-enhance
   otomatis sama styled-select.blade.php -- biar konsisten (posisi menu,
   ceklis opsi aktif, dst) sama semua dropdown filter lain di aplikasi,
   bukan widget kustom sendiri lagi. */
.danpus-log-search .styled-select-wrap{width:auto;min-width:130px;flex-shrink:0}
.danpus-log-search .search-count{font-size:10px;color:var(--p-muted);white-space:nowrap;margin-left:auto}
@media(max-width:700px){.danpus-log-search{display:flex;flex-wrap:wrap}.danpus-log-search-box{width:100%}.danpus-log-search .styled-select-wrap{flex:0 0 auto}.danpus-log-search .search-count{margin-left:auto;align-self:center}}
</style>
<script>
(function(){
  /*
   * Data laporan di halaman pimpinan sudah dikirim dari DashboardController
   * memakai latest(). Setelah timeline/dropdown dibentuk, urutan DOM tersebut
   * adalah urutan database: index 0 = laporan paling baru. Jadi kita simpan
   * index awal tiap perihal sebagai acuan sort yang stabil, bukan membaca teks
   * tanggal yang sudah diformat ke bahasa Indonesia.
   */
  function getItemOrder(item){
    var value=Number(item.dataset.originalLogOrder);
    return Number.isFinite(value)?value:999999999;
  }

  function initDanpusLogSearch(){
    document.querySelectorAll('section[id^="satlak-"] .section-block').forEach(function(block){
      if(block.dataset.searchReady==='1')return;
      var list=block.querySelector('.danpus-report-dropdown-list');
      var header=block.querySelector('.section-head-clean');
      if(!list||!header)return;

      /* Simpan urutan awal yang berasal dari latest() di backend. */
      Array.from(list.querySelectorAll(':scope > .danpus-report-dropdown')).forEach(function(item,index){
        item.dataset.originalLogOrder=String(index);
      });

      var searchWrap=document.createElement('div');
      searchWrap.className='danpus-log-search';
      searchWrap.innerHTML='<div class="danpus-log-search-box"><svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" aria-label="Cari perihal laporan" placeholder="Cari perihal laporan..." autocomplete="off"></div><select aria-label="Urutkan perihal laporan"><option value="newest">Terbaru</option><option value="oldest">Terlama</option></select><span class="search-count"></span>';
      header.insertAdjacentElement('afterend',searchWrap);

      var input=searchWrap.querySelector('input');
      var sortSelect=searchWrap.querySelector('select');
      var count=searchWrap.querySelector('.search-count');
      var sortValue='newest';
      // .empty-state -- kelas "1 sistem" yang sama dipakai empty-state
      // lain di seluruh dashboard (lihat dash-styles.blade.php), bukan
      // kotak dotted custom sendiri lagi biar konsisten persis.
      var empty=document.createElement('div');
      empty.className='empty-state';
      empty.style.display='none';
      empty.innerHTML='<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Tidak ada perihal laporan yang sesuai dengan pencarian.</div>';
      list.insertAdjacentElement('afterend',empty);

      function filterAndSort(){
        var q=(input.value||'').trim().toLowerCase();
        var items=Array.from(list.querySelectorAll(':scope > .danpus-report-dropdown'));

        items.sort(function(a,b){
          var diff=getItemOrder(a)-getItemOrder(b);
          return sortValue==='oldest' ? -diff : diff;
        });
        items.forEach(function(item){list.appendChild(item)});

        var visible=0;
        items.forEach(function(item){
          var subject=(item.querySelector('.danpus-report-subject')?.textContent||'').trim().toLowerCase();
          var match=!q||subject.includes(q);
          item.style.display=match?'':'none';
          if(match)visible++;
        });
        count.textContent=q?visible+' perihal ditemukan':items.length+' perihal';
        empty.style.display=visible===0?'block':'none';
      }

      sortSelect.addEventListener('change',function(){
        sortValue=sortSelect.value;
        filterAndSort();
      });

      input.addEventListener('input',filterAndSort);
      filterAndSort();
      block.dataset.searchReady='1';
    });
  }
  function boot(){setTimeout(initDanpusLogSearch,60);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
</script>
