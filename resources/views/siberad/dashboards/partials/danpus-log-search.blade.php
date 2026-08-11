<style>
/* Danpus: pencarian dan pengurutan perihal pada Log Aktivitas. */
.danpus-log-search{display:flex;align-items:center;gap:8px;margin:0 0 14px}
.danpus-log-search-box{position:relative;width:330px;max-width:100%}
.danpus-log-search .search-icon{position:absolute;left:11px;top:50%;width:16px;height:16px;transform:translateY(-50%);color:var(--p-muted);pointer-events:none}
.danpus-log-search input{box-sizing:border-box;width:100%;height:38px;border:1px solid var(--p-border);border-radius:9px;outline:0;background:var(--p-surface-2);color:var(--p-text);font:inherit;font-size:12px;padding:8px 11px 8px 35px}
.danpus-log-search input:focus{border-color:#c97a00;box-shadow:0 0 0 3px rgba(201,122,0,.10)}
.danpus-log-search input::placeholder{color:var(--p-muted)}
.danpus-log-sort-wrap{position:relative;flex:0 0 auto}
.danpus-log-sort-trigger{height:38px;min-width:130px;border:1px solid var(--p-border);border-radius:10px;background:var(--p-surface-2);color:var(--p-text);font:inherit;font-size:11px;padding:0 11px;outline:0;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:9px;transition:border-color .15s ease,box-shadow .15s ease,background .15s ease}
.danpus-log-sort-trigger:hover,.danpus-log-sort-wrap.open .danpus-log-sort-trigger{border-color:#c97a00;background:var(--p-surface);box-shadow:0 3px 12px rgba(15,23,42,.06)}
.danpus-log-sort-trigger .sort-chevron{width:14px;height:14px;flex:0 0 auto;transition:transform .2s ease}
.danpus-log-sort-wrap.open .sort-chevron{transform:rotate(180deg)}
.danpus-log-sort-menu{position:absolute;top:calc(100% + 7px);left:50%;transform:translateX(-50%) translateY(-4px);width:150px;padding:6px;background:var(--p-surface);border:1px solid var(--p-border);border-radius:12px;box-shadow:0 12px 28px rgba(15,23,42,.14);z-index:100050;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .15s ease,transform .15s ease,visibility .15s ease}
.danpus-log-sort-wrap.open .danpus-log-sort-menu{opacity:1;visibility:visible;pointer-events:auto;transform:translateX(-50%) translateY(0)}
.danpus-log-sort-option{width:100%;border:0;background:transparent;color:var(--p-text);border-radius:8px;padding:9px 11px;display:flex;align-items:center;gap:9px;font:inherit;font-size:11px;cursor:pointer;text-align:left;transition:background .15s ease,color .15s ease}
.danpus-log-sort-option:hover{background:var(--p-surface-2)}
.danpus-log-sort-option.active{background:rgba(201,122,0,.10);color:#b56d00;font-weight:700}
.danpus-log-sort-option .option-icon{width:15px;height:15px;flex:0 0 auto}
.danpus-log-search .search-count{font-size:10px;color:var(--p-muted);white-space:nowrap}
.danpus-log-empty{display:none;text-align:center;padding:22px 12px;color:var(--p-muted);font-size:12px;border:1px dashed var(--p-border);border-radius:10px;margin-top:2px}
@media(max-width:700px){.danpus-log-search{display:flex;flex-wrap:wrap}.danpus-log-search-box{width:100%}.danpus-log-sort-wrap{flex:0 0 auto}.danpus-log-search .search-count{margin-left:auto;align-self:center}}
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
      searchWrap.innerHTML='<div class="danpus-log-search-box"><svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" aria-label="Cari perihal laporan" placeholder="Cari perihal laporan..." autocomplete="off"></div><div class="danpus-log-sort-wrap"><button type="button" class="danpus-log-sort-trigger" aria-haspopup="true" aria-expanded="false"><span class="sort-label">Terbaru</span><svg class="sort-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg></button><div class="danpus-log-sort-menu" role="menu"><button type="button" class="danpus-log-sort-option active" data-sort="newest" role="menuitem"><svg class="option-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg><span>Terbaru</span></button><button type="button" class="danpus-log-sort-option" data-sort="oldest" role="menuitem"><svg class="option-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg><span>Terlama</span></button></div></div><span class="search-count"></span>';
      header.insertAdjacentElement('afterend',searchWrap);

      var input=searchWrap.querySelector('input');
      var sortWrap=searchWrap.querySelector('.danpus-log-sort-wrap');
      var sortTrigger=searchWrap.querySelector('.danpus-log-sort-trigger');
      var sortLabel=searchWrap.querySelector('.sort-label');
      var sortOptions=Array.from(searchWrap.querySelectorAll('.danpus-log-sort-option'));
      var count=searchWrap.querySelector('.search-count');
      var sortValue='newest';
      var empty=document.createElement('div');
      empty.className='danpus-log-empty';
      empty.textContent='Tidak ada perihal laporan yang sesuai dengan pencarian.';
      list.insertAdjacentElement('afterend',empty);

      function filterAndSort(){
        var q=(input.value||'').trim().toLowerCase();
        var items=Array.from(list.querySelectorAll(':scope > .danpus-report-dropdown'));
        items.sort(function(a,b){
          var diff=getItemDate(b)-getItemDate(a);
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

      sortTrigger.addEventListener('click',function(e){
        e.stopPropagation();
        var open=sortWrap.classList.toggle('open');
        sortTrigger.setAttribute('aria-expanded',open?'true':'false');
      });
      sortOptions.forEach(function(option){
        option.addEventListener('click',function(e){
          e.stopPropagation();
          sortValue=option.dataset.sort||'newest';
          sortLabel.textContent=sortValue==='oldest'?'Terlama':'Terbaru';
          sortOptions.forEach(function(opt){opt.classList.toggle('active',opt===option)});
          sortWrap.classList.remove('open');
          sortTrigger.setAttribute('aria-expanded','false');
          filterAndSort();
        });
      });
      document.addEventListener('click',function(e){
        if(!sortWrap.contains(e.target)){
          sortWrap.classList.remove('open');
          sortTrigger.setAttribute('aria-expanded','false');
        }
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
