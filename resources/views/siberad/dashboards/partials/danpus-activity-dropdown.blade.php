<style>
  .danpus-report-dropdown-list{display:flex;flex-direction:column;gap:9px}
  .danpus-report-dropdown{border:1px solid var(--p-border);border-radius:12px;background:var(--p-surface-2);overflow:hidden}
  .danpus-report-dropdown summary{list-style:none;cursor:pointer;padding:13px 15px;display:flex;align-items:center;justify-content:space-between;gap:14px}
  .danpus-report-dropdown summary::-webkit-details-marker{display:none}
  .danpus-report-dropdown summary:hover{background:var(--hover-tint)}
  .danpus-report-summary-main{min-width:0;display:flex;align-items:center;gap:10px;flex:1}
  .danpus-report-chevron{width:8px;height:8px;border-right:2px solid var(--p-muted);border-bottom:2px solid var(--p-muted);transform:rotate(-45deg);transition:transform .18s ease;flex:0 0 auto}
  .danpus-report-dropdown[open] .danpus-report-chevron{transform:rotate(45deg)}
  .danpus-report-subject{font-size:12px;font-weight:800;color:var(--p-text);line-height:1.45;word-break:break-word;min-width:0}
  .danpus-progress-history{display:flex;align-items:center;justify-content:flex-end;gap:5px;flex-wrap:wrap;flex:0 0 auto}
  .danpus-progress-chip{display:inline-flex;align-items:center;border:1px solid rgba(52,152,219,.25);background:rgba(52,152,219,.1);color:#2476ad;border-radius:999px;padding:4px 7px;font-size:9px;font-weight:800;line-height:1;white-space:nowrap}
  .danpus-progress-chip.latest{border-color:rgba(22,131,75,.28);background:rgba(63,194,125,.12);color:var(--p-green)}
  .danpus-report-content{padding:0 14px 14px;border-top:1px solid var(--p-border)}
  .danpus-report-content .danpus-activity-log{margin-top:12px}
  .danpus-report-content .danpus-activity-project{display:none}
  @media(max-width:700px){.danpus-report-dropdown summary{align-items:flex-start}.danpus-report-summary-main{flex-wrap:wrap}.danpus-progress-history{width:100%;justify-content:flex-start;margin-left:18px}}
</style>
<script>
(function(){
  function progressValues(logs){
    var seen={};
    return logs.map(function(log){
      return log.dataset.progres || log.querySelector('[data-progres]')?.dataset.progres || null;
    }).filter(function(value){
      if(value===null||value==='') return false;
      var key=String(value);
      if(seen[key]) return false;
      seen[key]=true;
      return true;
    });
  }

  function makeHistory(logs){
    var history=document.createElement('div');
    history.className='danpus-progress-history';
    progressValues(logs).forEach(function(value,index,values){
      var chip=document.createElement('span');
      chip.className='danpus-progress-chip'+(index===values.length-1?' latest':'');
      chip.textContent=String(value)+'%';
      history.appendChild(chip);
    });
    return history;
  }

  function wrapLogsInDropdown(wrapper){
    if(wrapper.dataset.dropdownReady==='1') return;
    var logs=Array.from(wrapper.querySelectorAll(':scope > .danpus-activity-log'));
    if(!logs.length) return;

    var groups=[];
    var byRequest={};
    logs.forEach(function(log){
      var key=log.dataset.permintaanId || ('laporan-'+(log.dataset.laporanId||Math.random()));
      if(!byRequest[key]){byRequest[key]=[];groups.push(byRequest[key]);}
      byRequest[key].push(log);
    });

    var list=document.createElement('div');
    list.className='danpus-report-dropdown-list';

    groups.forEach(function(group){
      var latest=group[group.length-1];
      var subjectText=latest.querySelector('.danpus-activity-project')?.textContent.trim() || 'Laporan tanpa perihal';
      var details=document.createElement('details');
      details.className='danpus-report-dropdown';
      if(latest.dataset.permintaanId) details.dataset.permintaanId=latest.dataset.permintaanId;
      if(latest.dataset.laporanId) details.dataset.laporanId=latest.dataset.laporanId;

      var summary=document.createElement('summary');
      var main=document.createElement('div');main.className='danpus-report-summary-main';
      var chevron=document.createElement('span');chevron.className='danpus-report-chevron';
      var subject=document.createElement('span');subject.className='danpus-report-subject';subject.textContent=subjectText;
      main.appendChild(chevron);main.appendChild(subject);
      summary.appendChild(main);
      summary.appendChild(makeHistory(group));
      details.appendChild(summary);

      var content=document.createElement('div');content.className='danpus-report-content';
      content.appendChild(latest);
      details.appendChild(content);
      list.appendChild(details);
    });

    wrapper.innerHTML='';
    wrapper.appendChild(list);
    wrapper.dataset.dropdownReady='1';
  }

  function applyDanpusActivityDropdown(){
    if(!document.body) return;
    document.querySelectorAll('section[id^="satlak-"] .clean-table-wrap').forEach(wrapLogsInDropdown);
  }

  window.siberadRefreshDanpusActivityDropdown=applyDanpusActivityDropdown;
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',function(){setTimeout(applyDanpusActivityDropdown,0)});
  else setTimeout(applyDanpusActivityDropdown,0);
})();
</script>
