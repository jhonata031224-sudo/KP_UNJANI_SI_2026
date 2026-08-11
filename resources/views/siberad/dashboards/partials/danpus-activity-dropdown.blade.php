<style>
  /* Danpus: setiap perihal laporan menjadi item dropdown agar satu Satlak dapat memiliki banyak laporan. */
  .danpus-report-dropdown-list{display:flex;flex-direction:column;gap:9px}
  .danpus-report-dropdown{border:1px solid var(--p-border);border-radius:12px;background:var(--p-surface-2);overflow:hidden}
  .danpus-report-dropdown summary{list-style:none;cursor:pointer;padding:13px 15px;display:flex;align-items:center;justify-content:space-between;gap:14px}
  .danpus-report-dropdown summary::-webkit-details-marker{display:none}
  .danpus-report-dropdown summary:hover{background:var(--hover-tint)}
  .danpus-report-summary-main{min-width:0;display:flex;align-items:center;gap:10px}
  .danpus-report-chevron{width:8px;height:8px;border-right:2px solid var(--p-muted);border-bottom:2px solid var(--p-muted);transform:rotate(-45deg);transition:transform .18s ease;flex:0 0 auto}
  .danpus-report-dropdown[open] .danpus-report-chevron{transform:rotate(45deg)}
  .danpus-report-subject{font-size:12px;font-weight:800;color:var(--p-text);line-height:1.45;word-break:break-word}
  .danpus-report-meta{display:flex;align-items:center;gap:8px;flex:0 0 auto}
  .danpus-report-count{font-size:9px;color:var(--p-muted);white-space:nowrap}
  .danpus-report-content{padding:0 14px 14px;border-top:1px solid var(--p-border)}
  .danpus-report-content .danpus-activity-log{margin-top:12px}
  .danpus-report-content .danpus-activity-project{display:none}
  @media(max-width:700px){.danpus-report-dropdown summary{align-items:flex-start}.danpus-report-meta{display:block;text-align:right}.danpus-report-count{display:block;margin-top:4px}}
</style>
<script>
(function(){
  function getLatestState(log){
    var states=Array.from(log.querySelectorAll('.danpus-activity-state')).map(function(el){return el.textContent.trim()});
    return states.find(function(s){return s==='Ditolak'||s==='Sedang diproses'}) || (states.length ? states[states.length-1] : 'Menunggu');
  }

  function groupLogsBySubject(wrapper){
    if(wrapper.dataset.dropdownReady==='1') return;
    var logs=Array.from(wrapper.querySelectorAll(':scope > .danpus-activity-log'));
    if(!logs.length) return;

    var groups=new Map();
    logs.forEach(function(log){
      var subject=log.querySelector('.danpus-activity-project')?.textContent.trim() || 'Laporan tanpa perihal';
      var key=subject.toLowerCase().replace(/\s+/g,' ');
      if(!groups.has(key)) groups.set(key,{subject:subject,logs:[]});
      groups.get(key).logs.push(log);
    });

    var list=document.createElement('div');
    list.className='danpus-report-dropdown-list';

    groups.forEach(function(group){
      var details=document.createElement('details');
      details.className='danpus-report-dropdown';

      var summary=document.createElement('summary');
      var main=document.createElement('div');main.className='danpus-report-summary-main';
      var chevron=document.createElement('span');chevron.className='danpus-report-chevron';
      var subject=document.createElement('span');subject.className='danpus-report-subject';subject.textContent=group.subject;
      main.appendChild(chevron);main.appendChild(subject);

      var meta=document.createElement('div');meta.className='danpus-report-meta';
      var count=document.createElement('span');count.className='danpus-report-count';count.textContent=group.logs.length+' laporan';
      meta.appendChild(count);
      summary.appendChild(main);summary.appendChild(meta);
      details.appendChild(summary);

      var content=document.createElement('div');content.className='danpus-report-content';
      group.logs.forEach(function(log){content.appendChild(log)});
      details.appendChild(content);
      list.appendChild(details);
    });

    wrapper.innerHTML='';
    wrapper.appendChild(list);
    wrapper.dataset.dropdownReady='1';
  }

  function applyDanpusActivityDropdown(){
    if(!document.body) return;
    document.querySelectorAll('section[id^="satlak-"] .clean-table-wrap').forEach(groupLogsBySubject);
  }

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',function(){setTimeout(applyDanpusActivityDropdown,0)});
  else setTimeout(applyDanpusActivityDropdown,0);
})();
</script>
