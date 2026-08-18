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
  .danpus-report-content{padding:0 14px 14px;border-top:1px solid var(--p-border)}
  .danpus-report-content .danpus-activity-log{margin-top:12px}
  .danpus-report-content .danpus-activity-project{display:none}
  .danpus-inline-progress-history{margin:12px 0 2px;padding:10px 12px;border:1px solid rgba(59,130,246,.16);border-radius:10px;background:rgba(59,130,246,.035)}
  .danpus-inline-progress-label{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px;font-size:11px;font-weight:800;color:var(--p-muted);letter-spacing:.02em}
  .danpus-inline-progress-count{font-weight:700;opacity:.75}
  .danpus-inline-progress-list{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
  .danpus-inline-progress-item{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800;border:1px solid rgba(100,116,139,.18);background:var(--p-surface);color:var(--p-text);white-space:nowrap}
  .danpus-inline-progress-item.latest{border-color:rgba(16,185,129,.45);box-shadow:0 0 0 1px rgba(16,185,129,.06)}
  .danpus-inline-progress-arrow{color:var(--p-muted);opacity:.55;font-weight:800}
  .danpus-inline-progress-item.is-progress-added{animation:danpusInlineProgressAdded .55s ease}
  @keyframes danpusInlineProgressAdded{0%{transform:scale(.82);opacity:.2}65%{transform:scale(1.08);opacity:1}100%{transform:scale(1);opacity:1}}
  @media(max-width:700px){.danpus-report-dropdown summary{align-items:flex-start}.danpus-report-summary-main{flex-wrap:wrap}}
</style>
<script>
(function(){
  function logId(log){return Number(log.dataset.laporanId || log.dataset.id || 0) || 0;}
  function sortedLogs(logs){return logs.slice().sort(function(a,b){return logId(a)-logId(b);});}
  function progressValues(logs){
    var seen={};
    return sortedLogs(logs).map(function(log){
      return log.dataset.progres || log.querySelector('[data-progres]')?.dataset.progres || null;
    }).filter(function(value){
      if(value===null||value==='') return false;
      var key=String(value);
      if(seen[key]) return false;
      seen[key]=true;
      return true;
    });
  }
  function makeInlineHistory(logs, previousValues){
    var history=document.createElement('div');
    history.className='danpus-inline-progress-history';
    history.setAttribute('data-danpus-inline-progress-history','1');
    var values=Array.isArray(logs)?progressValues(logs):uniqueProgress(logs||[]);
    var oldLast=Array.isArray(previousValues)&&previousValues.length?String(previousValues[previousValues.length-1]):null;
    var label=document.createElement('div');
    label.className='danpus-inline-progress-label';
    var title=document.createElement('span');
    title.textContent='Riwayat progres';
    var count=document.createElement('span');
    count.className='danpus-inline-progress-count';
    count.textContent='('+values.length+')';
    label.appendChild(title);label.appendChild(count);history.appendChild(label);
    var list=document.createElement('div');list.className='danpus-inline-progress-list';
    values.forEach(function(value,index){
      var item=document.createElement('span');
      item.className='danpus-inline-progress-item'+(index===values.length-1?' latest':'');
      item.setAttribute('data-progress',String(value));
      item.textContent='Progres · '+String(value)+'%';
      if(oldLast!==null && String(value)!==oldLast && index===values.length-1)item.classList.add('is-progress-added');
      list.appendChild(item);
      if(index<values.length-1){var arrow=document.createElement('span');arrow.className='danpus-inline-progress-arrow';arrow.textContent='→';list.appendChild(arrow);}
    });
    history.appendChild(list);
    return history;
  }
  function uniqueProgress(values){
    var seen={};
    return (values||[]).map(function(v){return v==null?'':String(v);}).filter(function(v){if(v===''||seen[v])return false;seen[v]=true;return true;});
  }
  function wrapLogsInDropdown(wrapper){
    if(wrapper.dataset.dropdownReady==='1') return;
    var logs=Array.from(wrapper.querySelectorAll(':scope > .danpus-activity-log'));
    if(!logs.length) return;
    var groups=[];var byRequest={};
    logs.forEach(function(log){
      var key=log.dataset.permintaanId || ('laporan-'+(log.dataset.laporanId||Math.random()));
      if(!byRequest[key]){byRequest[key]=[];groups.push(byRequest[key]);}
      byRequest[key].push(log);
    });
    var list=document.createElement('div');list.className='danpus-report-dropdown-list';
    groups.forEach(function(group){
      var ordered=sortedLogs(group);
      var latest=ordered[ordered.length-1];
      var subjectText=latest.querySelector('.danpus-activity-project')?.textContent.trim() || 'Laporan tanpa perihal';
      var details=document.createElement('details');details.className='danpus-report-dropdown';
      if(latest.dataset.permintaanId) details.dataset.permintaanId=latest.dataset.permintaanId;
      if(latest.dataset.laporanId) details.dataset.laporanId=latest.dataset.laporanId;
      var summary=document.createElement('summary');
      var main=document.createElement('div');main.className='danpus-report-summary-main';
      var chevron=document.createElement('span');chevron.className='danpus-report-chevron';
      var subject=document.createElement('span');subject.className='danpus-report-subject';subject.textContent=subjectText;
      main.appendChild(chevron);main.appendChild(subject);summary.appendChild(main);details.appendChild(summary);
      var content=document.createElement('div');content.className='danpus-report-content';
      content.appendChild(makeInlineHistory(ordered));
      content.appendChild(latest);
      details.appendChild(content);list.appendChild(details);
    });
    wrapper.innerHTML='';wrapper.appendChild(list);wrapper.dataset.dropdownReady='1';
  }
  function applyDanpusActivityDropdown(){
    if(!document.body)return;
    document.querySelectorAll('section[id^="satlak-"] .clean-table-wrap').forEach(wrapLogsInDropdown);
  }
  window.siberadRefreshDanpusActivityDropdown=applyDanpusActivityDropdown;
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',function(){setTimeout(applyDanpusActivityDropdown,0)});
  else setTimeout(applyDanpusActivityDropdown,0);
})();
</script>
