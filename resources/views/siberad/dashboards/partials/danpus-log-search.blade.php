<style>
.danpus-log-search{display:flex;align-items:center;gap:10px;margin:0 0 14px;padding:10px 12px;background:var(--p-surface-2);border:1px solid var(--p-border);border-radius:11px}.danpus-log-search .search-icon{width:17px;height:17px;flex:0 0 auto;color:var(--p-muted)}.danpus-log-search input{width:100%;border:0;outline:0;background:transparent;color:var(--p-text);font-family:var(--body);font-size:12px}.danpus-log-search input::placeholder{color:var(--p-muted)}.danpus-log-search .search-count{font-size:10px;color:var(--p-muted);white-space:nowrap}.danpus-log-empty{display:none;text-align:center;padding:22px 12px;color:var(--p-muted);font-size:12px;border:1px dashed var(--p-border);border-radius:10px;margin-top:2px}
@media(max-width:700px){.danpus-log-search{padding:9px 10px}.danpus-log-search .search-count{font-size:9px}}
</style>
<script>
(function(){
  function initDanpusLogSearch(){
    document.querySelectorAll('section[id^="satlak-"] .section-block').forEach(function(block){
      if(block.dataset.searchReady==='1')return;
      var tableWrap=block.querySelector('.clean-table-wrap');
      var header=block.querySelector('.section-head-clean');
      if(!tableWrap||!header)return;
      var table=tableWrap.querySelector('table');
      var tbody=table?.querySelector('tbody');
      if(!tbody)return;

      var rows=Array.from(tbody.querySelectorAll('tr')).filter(function(row){return row.querySelector('td') && row.querySelector('td[colspan]')===null;});
      var searchWrap=document.createElement('div');
      searchWrap.className='danpus-log-search';
      searchWrap.innerHTML='<svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" aria-label="Cari laporan pada log aktivitas" placeholder="Cari perihal laporan..." autocomplete="off"><span class="search-count"></span>';
      header.insertAdjacentElement('afterend',searchWrap);

      var input=searchWrap.querySelector('input');
      var count=searchWrap.querySelector('.search-count');
      var empty=document.createElement('div');
      empty.className='danpus-log-empty';
      empty.textContent='Tidak ada laporan yang sesuai dengan pencarian.';
      tableWrap.insertAdjacentElement('afterend',empty);

      function filter(){
        var q=(input.value||'').trim().toLowerCase();
        var visible=0;
        rows.forEach(function(row){
          var subject=row.querySelector('.subject');
          var text=(subject?.textContent||row.textContent||'').toLowerCase();
          var match=!q || text.includes(q);
          row.style.display=match?'':'none';
          if(match)visible++;
        });
        count.textContent=q ? visible+' laporan' : rows.length+' laporan';
        empty.style.display=visible===0?'block':'none';
        tableWrap.style.display=visible===0?'none':'';
      }
      input.addEventListener('input',filter);
      filter();
      block.dataset.searchReady='1';
    });
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initDanpusLogSearch);else initDanpusLogSearch();
})();
</script>
