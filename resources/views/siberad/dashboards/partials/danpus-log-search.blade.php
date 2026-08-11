<style>
/* Danpus: pencarian dan pengurutan perihal pada Log Aktivitas. */
.danpus-log-search{display:flex;align-items:center;gap:8px;margin:0 0 14px}
.danpus-log-search-box{position:relative;width:330px;max-width:100%}
.danpus-log-search .search-icon{position:absolute;left:11px;top:50%;width:16px;height:16px;transform:translateY(-50%);color:var(--p-muted);pointer-events:none}
.danpus-log-search input{box-sizing:border-box;width:100%;height:38px;border:1px solid var(--p-border);border-radius:9px;outline:0;background:var(--p-surface-2);color:var(--p-text);font:inherit;font-size:12px;padding:8px 11px 8px 35px}
.danpus-log-search input:focus{border-color:#c97a00;box-shadow:0 0 0 3px rgba(201,122,0,.10)}
.danpus-log-search input::placeholder{color:var(--p-muted)}
.danpus-log-sort{height:38px;border:1px solid var(--p-border);border-radius:9px;background:var(--p-surface-2);color:var(--p-text);font:inherit;font-size:11px;padding:0 30px 0 11px;outline:0;cursor:pointer}
.danpus-log-sort:focus{border-color:#c97a00;box-shadow:0 0 0 3px rgba(201,122,0,.10)}
.danpus-log-search .search-count{font-size:10px;color:var(--p-muted);white-space:nowrap}
.danpus-log-empty{display:none;text-align:center;padding:22px 12px;color:var(--p-muted);font-size:12px;border:1px dashed var(--p-border);border-radius:10px;margin-top:2px}
@media(max-width:700px){.danpus-log-search{display:flex;flex-wrap:wrap}.danpus-log-search-box{width:100%}.danpus-log-sort{flex:0 0 auto}.danpus-log-search .search-count{margin-left:auto;align-self:center}}
</style>
<script>
(function(){
  function getItemDate(item){
    var dated=item.querySelector('[data-tanggal]')?.getAttribute('data-tanggal');
    if(dated){var d=new Date(dated);if(!isNaN(d.getTime()))return d.getTime();}
    var time=item.querySelector('time[datetime]')?.getAttribute('datetime');
    if(time){var t=new Date(time);if(!isNaN(t.getTime()))return t.getTime();}
    return 0;
  }

  function initDanpusLogSearch(){
    document.querySelectorAll('section[id^="satlak-"] .section-block').forEach(function(block){
      if(block.dataset.searchReady==='1')return;
      var list=block.querySelector('.danpus-report-dropdown-list');
      var header=block.querySelector('.section-head-clean');
      if(!list||!header)return;

      var searchWrap=document.createElement('div');
      searchWrap.className='danpus-log-search';
      searchWrap.innerHTML='<div class="danpus-log-search-box"><svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" aria-label="Cari perihal laporan" placeholder="Cari perihal laporan..." autocomplete="off"></div><select class="danpus-log-sort" aria-label="Urutkan laporan"><option value="newest">Terbaru</option><option value="oldest">Terlama</option></select><span class="search-count"></span>';
      header.insertAdjacentElement('afterend',searchWrap);

      var input=searchWrap.querySelector('input');
      var sort=searchWrap.querySelector('.danpus-log-sort');
      var count=searchWrap.querySelector('.search-count');
      var empty=document.createElement('div');
      empty.className='danpus-log-empty';
      empty.textContent='Tidak ada perihal laporan yang sesuai dengan pencarian.';
      list.insertAdjacentElement('afterend',empty);

      function filterAndSort(){
        var q=(input.value||'').trim().toLowerCase();
        var items=Array.from(list.querySelectorAll(':scope > .danpus-report-dropdown'));
        items.sort(function(a,b){
          var diff=getItemDate(b)-getItemDate(a);
          return sort.value==='oldest' ? -diff : diff;
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

      input.addEventListener('input',filterAndSort);
      sort.addEventListener('change',filterAndSort);
      filterAndSort();
      block.dataset.searchReady='1';
    });
  }
  function boot(){setTimeout(initDanpusLogSearch,60);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
</script>
