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

  /* Riwayat progres hanya ditampilkan di dalam Laporan Dibuat. */
  .danpus-report-content .danpus-inline-progress-history{
    position:relative;
    margin:14px 0 4px;
    padding:14px 14px 15px;
    border:1px solid color-mix(in srgb,var(--p-accent) 18%,var(--p-border));
    border-radius:14px;
    background:linear-gradient(135deg,color-mix(in srgb,var(--p-surface) 96%,var(--p-accent)),var(--p-surface-2));
    overflow:hidden;
  }
  .danpus-report-content .danpus-inline-progress-history::before{
    content:"";
    position:absolute;
    left:-30%;
    top:0;
    width:30%;
    height:2px;
    background:linear-gradient(90deg,transparent,var(--p-accent),transparent);
    opacity:.75;
    animation:danpusProgressFlow 3.2s linear infinite;
  }
  .danpus-inline-progress-label{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:13px;font-size:11px;font-weight:800;color:var(--p-muted);letter-spacing:.02em}
  .danpus-inline-progress-label span:first-child{display:flex;align-items:center;gap:7px}
  .danpus-inline-progress-label span:first-child::before{content:"";width:7px;height:7px;border-radius:50%;background:var(--p-green);box-shadow:0 0 0 4px color-mix(in srgb,var(--p-green) 12%,transparent);animation:danpusLivePulse 1.8s ease-in-out infinite}
  .danpus-inline-progress-count{font-weight:700;opacity:.72}
  .danpus-inline-progress-list{display:flex;align-items:center;gap:0;overflow-x:auto;padding:4px 2px 6px;scrollbar-width:thin}
  .danpus-inline-progress-item{position:relative;z-index:2;display:inline-flex;align-items:center;justify-content:center;min-width:58px;padding:7px 11px;border-radius:999px;font-size:11px;font-weight:800;border:1px solid color-mix(in srgb,var(--p-muted) 18%,var(--p-border));background:var(--p-surface);color:var(--p-text);white-space:nowrap;box-shadow:0 2px 8px rgba(15,23,42,.05);transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}
  .danpus-inline-progress-item.latest{border-color:color-mix(in srgb,var(--p-green) 55%,var(--p-border));box-shadow:0 0 0 3px color-mix(in srgb,var(--p-green) 9%,transparent),0 5px 15px color-mix(in srgb,var(--p-green) 12%,transparent)}
  .danpus-inline-progress-item.latest::after{content:"";position:absolute;inset:-4px;border-radius:999px;border:1px solid color-mix(in srgb,var(--p-green) 30%,transparent);opacity:0;animation:danpusLatestRing 2s ease-out infinite}
  .danpus-inline-progress-arrow{position:relative;flex:1 0 24px;min-width:24px;height:2px;margin:0 2px;background:color-mix(in srgb,var(--p-muted) 24%,transparent);font-size:0;overflow:hidden}
  .danpus-inline-progress-arrow::after{content:"";position:absolute;left:-45%;top:0;width:45%;height:100%;background:linear-gradient(90deg,transparent,var(--p-accent),transparent);animation:danpusArrowFlow 2.4s linear infinite}
  .danpus-inline-progress-item.is-progress-added{animation:danpusProgressAdded .8s cubic-bezier(.2,.8,.2,1)}
  .danpus-inline-progress-history.realtime-history{animation:danpusHistoryRefresh .9s ease}
  @keyframes danpusProgressFlow{0%{transform:translateX(0)}100%{transform:translateX(440%)} }
  @keyframes danpusLivePulse{0%,100%{transform:scale(1);opacity:.7}50%{transform:scale(1.25);opacity:1}}
  @keyframes danpusLatestRing{0%{transform:scale(.92);opacity:.65}100%{transform:scale(1.15);opacity:0}}
  @keyframes danpusArrowFlow{0%{transform:translateX(0)}100%{transform:translateX(330%)} }
  @keyframes danpusProgressAdded{0%{transform:translateX(18px) scale(.72);opacity:0;filter:blur(2px)}55%{transform:translateX(0) scale(1.08);opacity:1;filter:blur(0)}100%{transform:translateX(0) scale(1);opacity:1}}
  @keyframes danpusHistoryRefresh{0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--p-accent) 18%,transparent)}100%{box-shadow:0 0 0 10px transparent}}
  @media(prefers-reduced-motion:reduce){.danpus-report-content .danpus-inline-progress-history::before,.danpus-inline-progress-label span:first-child::before,.danpus-inline-progress-arrow::after,.danpus-inline-progress-item.latest::after{animation:none}.danpus-inline-progress-item.is-progress-added,.danpus-inline-progress-history.realtime-history{animation:none}}
  @media(max-width:700px){.danpus-report-dropdown summary{align-items:flex-start}.danpus-report-summary-main{flex-wrap:wrap}.danpus-inline-progress-list{padding-bottom:10px}}
</style>
<script>
(function(){
  function logId(log){return Number(log.dataset.laporanId || log.dataset.id || 0) || 0;}
  function sortedLogs(logs){return logs.slice().sort(function(a,b){return logId(a)-logId(b);});}
  function progressValues(logs){
    var seen={};
    return sortedLogs(logs).map(function(log){return log.dataset.progres || log.querySelector('[data-progres]')?.dataset.progres || null;}).filter(function(value){
      if(value===null||value==='') return false;
      var key=String(value); if(seen[key]) return false; seen[key]=true; return true;
    });
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
      var ordered=sortedLogs(group);var latest=ordered[ordered.length-1];
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
      /* Jangan buat history kedua di atas. History milik Laporan Dibuat ada di row/timeline. */
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