<style>
/* Danpus: pencarian perihal pada Log Aktivitas, dipasang setelah dropdown selesai dirender. */
.danpus-log-search{display:flex;align-items:center;gap:10px;margin:0 0 14px}.danpus-log-search-box{position:relative;flex:1;max-width:560px}.danpus-log-search .search-icon{position:absolute;left:12px;top:50%;width:17px;height:17px;transform:translateY(-50%);color:var(--p-muted);pointer-events:none}.danpus-log-search input{box-sizing:border-box;width:100%;border:1px solid var(--p-border);border-radius:10px;outline:0;background:var(--p-surface-2);color:var(--p-text);font:inherit;font-size:12px;padding:11px 12px 11px 38px}.danpus-log-search input:focus{border-color:#c97a00;box-shadow:0 0 0 3px rgba(201,122,0,.10)}.danpus-log-search input::placeholder{color:var(--p-muted)}.danpus-log-search .search-count{font-size:10px;color:var(--p-muted);white-space:nowrap}.danpus-log-empty{display:none;text-align:center;padding:22px 12px;color:var(--p-muted);font-size:12px;border:1px dashed var(--p-border);border-radius:10px;margin-top:2px}
@media(max-width:700px){.danpus-log-search{display:block}.danpus-log-search-box{max-width:none}.danpus-log-search .search-count{display:block;margin-top:7px}}
</style>
<script>
(function(){
  function initDanpusLogSearch(){
    document.querySelectorAll('section[id^="satlak-"] .section-block').forEach(function(block){
      if(block.dataset.searchReady==='1')return;
      var list=block.querySelector('.danpus-report-dropdown-list');
      var header=block.querySelector('.section-head-clean');
      if(!list||!header)return;

      var searchWrap=document.createElement('div');
      searchWrap.className='danpus-log-search';
      searchWrap.innerHTML='<div class="danpus-log-search-box"><svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" aria-label="Cari perihal laporan" placeholder="Cari perihal laporan..." autocomplete="off"></div><span class="search-count"></span>';
      header.insertAdjacentElement('afterend',searchWrap);

      var input=searchWrap.querySelector('input');
      var count=searchWrap.querySelector('.search-count');
      var empty=document.createElement('div');
      empty.className='danpus-log-empty';
      empty.textContent='Tidak ada perihal laporan yang sesuai dengan pencarian.';
      list.insertAdjacentElement('afterend',empty);

      function filter(){
        var q=(input.value||'').trim().toLowerCase();
        var items=Array.from(list.querySelectorAll(':scope > .danpus-report-dropdown'));
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
      input.addEventListener('input',filter);
      filter();
      block.dataset.searchReady='1';
    });
  }
  function boot(){setTimeout(initDanpusLogSearch,60);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
</script>
