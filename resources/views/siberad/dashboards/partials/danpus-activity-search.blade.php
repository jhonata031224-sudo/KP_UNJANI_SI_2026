<style>
  /* Danpus: pencarian cepat pada daftar perihal di Log Aktivitas. */
  .danpus-activity-search{display:flex;align-items:center;gap:10px;margin:0 0 14px}
  .danpus-activity-search-box{position:relative;flex:1;max-width:560px}
  .danpus-activity-search-box svg{position:absolute;left:12px;top:50%;width:17px;height:17px;transform:translateY(-50%);color:var(--p-muted);pointer-events:none}
  .danpus-activity-search-input{width:100%;box-sizing:border-box;border:1px solid var(--p-border);border-radius:10px;background:var(--p-surface-2);color:var(--p-text);font:inherit;font-size:12px;padding:11px 38px 11px 38px;outline:none;transition:border-color .15s ease,box-shadow .15s ease}
  .danpus-activity-search-input:focus{border-color:#c97a00;box-shadow:0 0 0 3px rgba(201,122,0,.10)}
  .danpus-activity-search-input::placeholder{color:var(--p-muted)}
  .danpus-activity-search-clear{position:absolute;right:8px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:var(--p-muted);cursor:pointer;font-size:17px;line-height:1;padding:5px;display:none}
  .danpus-activity-search-count{font-size:11px;color:var(--p-muted);white-space:nowrap}
  .danpus-activity-search-empty{display:none;padding:18px;border:1px dashed var(--p-border);border-radius:10px;color:var(--p-muted);font-size:12px;text-align:center}
  @media(max-width:700px){.danpus-activity-search{display:block}.danpus-activity-search-box{max-width:none}.danpus-activity-search-count{display:block;margin-top:7px}}
</style>
<script>
(function(){
  function initSearch(section){
    if(section.dataset.searchReady==='1') return;
    var list=section.querySelector('.danpus-report-dropdown-list');
    if(!list) return;

    var head=section.querySelector('.section-head-clean');
    if(!head) return;

    var wrap=document.createElement('div');
    wrap.className='danpus-activity-search';
    wrap.innerHTML='<div class="danpus-activity-search-box">'+
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>'+
      '<input class="danpus-activity-search-input" type="search" autocomplete="off" placeholder="Cari perihal laporan..." aria-label="Cari perihal laporan">'+
      '<button type="button" class="danpus-activity-search-clear" aria-label="Hapus pencarian">×</button>'+
      '</div><span class="danpus-activity-search-count"></span>'+
      '<div class="danpus-activity-search-empty">Tidak ada perihal laporan yang sesuai dengan pencarian.</div>';

    head.insertAdjacentElement('afterend',wrap);
    var input=wrap.querySelector('.danpus-activity-search-input');
    var clear=wrap.querySelector('.danpus-activity-search-clear');
    var count=wrap.querySelector('.danpus-activity-search-count');
    var empty=wrap.querySelector('.danpus-activity-search-empty');

    function filter(){
      var q=(input.value||'').trim().toLowerCase();
      var items=Array.from(list.querySelectorAll(':scope > .danpus-report-dropdown'));
      var visible=0;
      items.forEach(function(item){
        var subject=item.querySelector('.danpus-report-subject')?.textContent.trim().toLowerCase()||'';
        var match=!q||subject.indexOf(q)!==-1;
        item.style.display=match?'':'none';
        if(match) visible++;
      });
      clear.style.display=q?'block':'none';
      count.textContent=q ? (visible+' perihal ditemukan') : (items.length+' perihal');
      empty.style.display=(items.length>0&&visible===0)?'block':'none';
    }

    input.addEventListener('input',filter);
    clear.addEventListener('click',function(){input.value='';input.focus();filter()});
    filter();
    section.dataset.searchReady='1';
  }

  function apply(){
    document.querySelectorAll('section[id^="satlak-"]').forEach(initSearch);
  }

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',function(){setTimeout(apply,20)});
  else setTimeout(apply,20);
})();
</script>
