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

  .danpus-report-content .danpus-inline-progress-history{
    position:relative;margin:14px 0 4px;padding:14px 14px 16px;
    border:1px solid color-mix(in srgb,var(--p-accent) 18%,var(--p-border));
    border-radius:14px;
    background:linear-gradient(135deg,color-mix(in srgb,var(--p-surface) 97%,var(--p-accent)),var(--p-surface-2));
    overflow:visible;
  }
  .danpus-inline-progress-label{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;font-size:11px;font-weight:800;color:var(--p-muted);letter-spacing:.02em}
  .danpus-inline-progress-label span:first-child{display:flex;align-items:center;gap:7px}
  .danpus-inline-progress-label span:first-child::before{content:"";width:7px;height:7px;border-radius:50%;background:var(--p-green);box-shadow:0 0 0 4px color-mix(in srgb,var(--p-green) 12%,transparent)}
  .danpus-inline-progress-count{font-weight:700;opacity:.72}

  .danpus-inline-progress-list{display:flex;flex-direction:column;gap:0;overflow:visible;padding:0 2px}
  .danpus-inline-progress-row{position:relative;display:flex;align-items:center;gap:10px;width:100%;min-width:0}
  .danpus-inline-progress-row.reverse{flex-direction:row-reverse}
  .danpus-inline-progress-item{
    position:relative;z-index:2;box-sizing:border-box;flex:1 1 0;min-width:0;min-height:74px;
    display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:3px;
    padding:10px 8px;border-radius:16px;font-size:11px;font-weight:800;
    border:1px solid color-mix(in srgb,var(--p-muted) 18%,var(--p-border));
    background:var(--p-surface);color:var(--p-text);white-space:normal;
    box-shadow:0 4px 14px rgba(15,23,42,.06);transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease;
  }
  .danpus-inline-progress-item::before{content:"";width:10px;height:10px;border-radius:50%;background:var(--p-green);box-shadow:0 0 0 4px color-mix(in srgb,var(--p-green) 11%,transparent);flex:0 0 auto}
  .danpus-inline-progress-value{font-size:13px;font-weight:900;line-height:1.1}
  .danpus-inline-progress-meta{font-size:9px;font-weight:600;color:var(--p-muted);line-height:1.2}
  .danpus-inline-progress-detail{margin-top:5px;border:1px solid var(--p-border);background:var(--p-surface-2);color:var(--p-text);border-radius:7px;padding:4px 9px;font-size:9px;font-weight:800;line-height:1;cursor:pointer;transition:background .15s ease,border-color .15s ease,color .15s ease,transform .15s ease}
  .danpus-inline-progress-detail:hover{border-color:var(--p-accent);background:var(--p-surface);color:var(--p-accent);transform:translateY(-1px)}
  .danpus-inline-progress-detail:active{transform:scale(.96)}
  .danpus-inline-progress-item.latest{border-color:color-mix(in srgb,var(--p-green) 55%,var(--p-border));box-shadow:0 0 0 3px color-mix(in srgb,var(--p-green) 9%,transparent),0 6px 18px color-mix(in srgb,var(--p-green) 12%,transparent)}
  .danpus-inline-progress-item.latest::after{content:"TERBARU";position:absolute;top:6px;right:7px;font-size:7px;letter-spacing:.05em;color:var(--p-green);font-weight:900}
  .danpus-inline-progress-connector{position:relative;flex:0 0 18px;height:2px;background:color-mix(in srgb,var(--p-green) 45%,var(--p-border));border-radius:999px}
  .danpus-inline-progress-connector::after{content:"";position:absolute;right:-1px;top:50%;width:5px;height:5px;border-top:1.5px solid var(--p-green);border-right:1.5px solid var(--p-green);transform:translateY(-50%) rotate(45deg)}
  .danpus-inline-progress-row.reverse .danpus-inline-progress-connector::after{left:-1px;right:auto;transform:translateY(-50%) rotate(-135deg)}

  /* Hanya ujung baris yang memiliki baris lanjutan yang mendapat garis turun.
     Garis dimulai tepat dari tengah bawah card ujung dan berhenti di atas card berikutnya. */
  .danpus-inline-progress-turn{position:absolute;top:100%;width:2px;height:14px;margin:0;padding:0;background:color-mix(in srgb,var(--p-green) 45%,var(--p-border));border:0;border-radius:999px;pointer-events:none;z-index:1}
  .danpus-inline-progress-turn.left{left:50%;transform:translateX(-50%)}
  .danpus-inline-progress-turn.right{right:50%;transform:translateX(50%)}

  /* Tidak ada animasi kuning/gelombang pada container riwayat. */
  .danpus-inline-progress-item.is-progress-added{animation:danpusProgressAdded .55s cubic-bezier(.2,.8,.2,1)}
  .danpus-inline-progress-history.realtime-history{animation:none!important;box-shadow:none!important}
  @keyframes danpusProgressAdded{0%{transform:translateY(10px) scale(.92);opacity:0}70%{transform:translateY(-1px) scale(1.01);opacity:1}100%{transform:translateY(0) scale(1);opacity:1}}
  @media(prefers-reduced-motion:reduce){.danpus-inline-progress-item.is-progress-added{animation:none}}
  @media(max-width:900px){.danpus-inline-progress-row{gap:6px}.danpus-inline-progress-item{min-height:68px;padding:8px 5px}.danpus-inline-progress-connector{flex-basis:10px}.danpus-inline-progress-detail{padding:4px 7px}}
  @media(max-width:640px){.danpus-inline-progress-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.danpus-inline-progress-row.reverse{direction:rtl}.danpus-inline-progress-row.reverse .danpus-inline-progress-item{direction:ltr}.danpus-inline-progress-connector{display:none}.danpus-inline-progress-turn{display:none}}
</style>
<script>
(function(){
  function logId(log){return Number(log.dataset.laporanId || log.dataset.id || 0) || 0;}
  function sortedLogs(logs){return logs.slice().sort(function(a,b){return logId(a)-logId(b);});}
  function addProgressDetails(history, logs){
    var items=Array.from(history.querySelectorAll('.danpus-inline-progress-item'));
    if(!items.length)return;
    items.forEach(function(item){
      var value=item.dataset.progres || item.getAttribute('data-progres') || '';
      if(!value){var text=item.textContent.match(/(\d{1,3})\s*%/);if(text)value=text[1];}
      var source=null;
      if(logs&&logs.length){source=sortedLogs(logs).find(function(log){return String(log.dataset.progres||'')===String(value)})||null;}
      item.dataset.progres=value;
      if(source){item.dataset.laporanId=source.dataset.laporanId||'';item.dataset.permintaanId=source.dataset.permintaanId||'';}
      if(item.querySelector('.danpus-inline-progress-detail'))return;
      var btn=document.createElement('button');btn.type='button';btn.className='danpus-inline-progress-detail';btn.textContent='Detail';
      btn.addEventListener('click',function(e){
        e.preventDefault();e.stopPropagation();
        var targetId=item.dataset.laporanId||'';
        var candidates=Array.from(document.querySelectorAll('.danpus-report-content .danpus-activity-log[data-laporan-id], tr[data-laporan-id]'));
        var target=candidates.find(function(log){return String(log.dataset.laporanId||'')===String(targetId)})||source;
        var detail=target&&target.querySelector('.detail-btn');
        if(detail&&typeof window.openReportDetail==='function'){window.openReportDetail(detail);return;}
        if(detail)detail.click();
      });
      item.appendChild(btn);
    });
  }
  function arrangeProgressHistory(history,logs){
    if(!history)return;
    var original=Array.from(history.querySelectorAll('.danpus-inline-progress-item'));
    if(!original.length)return;
    var list=history.querySelector('.danpus-inline-progress-list');
    if(!list)return;
    var previousLayout=history.dataset.layoutReady==='1';
    list.innerHTML='';
    original.forEach(function(item){
      item.classList.remove('latest');
      item.querySelectorAll('.danpus-inline-progress-detail').forEach(function(b){b.remove()});
    });
    var rows=[];
    original.forEach(function(item,index){
      var rowIndex=Math.floor(index/7),position=index%7;
      if(!rows[rowIndex]){
        var row=document.createElement('div');
        row.className='danpus-inline-progress-row'+(rowIndex%2?' reverse':'');
        row.dataset.progressRow=rowIndex;
        rows[rowIndex]=row;
        list.appendChild(row);
      }
      var row=rows[rowIndex];
      if(position>0){var connector=document.createElement('span');connector.className='danpus-inline-progress-connector';row.appendChild(connector);}
      row.appendChild(item);
    });
    rows.forEach(function(row,rowIndex){
      if(rowIndex>=rows.length-1)return;
      var edgeItems=Array.from(row.querySelectorAll('.danpus-inline-progress-item'));
      if(!edgeItems.length)return;
      var edgeItem=rowIndex%2===0?edgeItems[edgeItems.length-1]:edgeItems[0];
      var turn=document.createElement('span');
      turn.className='danpus-inline-progress-turn '+(rowIndex%2===0?'left':'right');
      edgeItem.appendChild(turn);
    });
    var last=original[original.length-1];if(last)last.classList.add('latest');
    history.dataset.layoutReady='1';
    addProgressDetails(history,logs);
    if(previousLayout)history.classList.remove('realtime-history');
  }
  function enhanceHistories(root){
    (root||document).querySelectorAll('.danpus-inline-progress-history').forEach(function(history){
      var details=history.closest('.danpus-report-dropdown');
      var logs=details?Array.from(details.querySelectorAll('.danpus-activity-log[data-history-source="1"]')):[];
      arrangeProgressHistory(history,logs);
    });
  }
  function wrapLogsInDropdown(wrapper){
    if(wrapper.dataset.dropdownReady==='1')return;
    var logs=Array.from(wrapper.querySelectorAll(':scope > .danpus-activity-log'));
    if(!logs.length)return;
    var groups=[],byRequest={};
    logs.forEach(function(log){
      var key=log.dataset.permintaanId||('laporan-'+(log.dataset.laporanId||Math.random()));
      if(!byRequest[key]){byRequest[key]=[];groups.push(byRequest[key]);}
      byRequest[key].push(log);
    });
    var list=document.createElement('div');list.className='danpus-report-dropdown-list';
    groups.forEach(function(group){
      var ordered=sortedLogs(group),latest=ordered[ordered.length-1];
      var subjectText=latest.querySelector('.danpus-activity-project')?.textContent.trim()||'Laporan tanpa perihal';
      var details=document.createElement('details');details.className='danpus-report-dropdown';
      if(latest.dataset.permintaanId)details.dataset.permintaanId=latest.dataset.permintaanId;
      if(latest.dataset.laporanId)details.dataset.laporanId=latest.dataset.laporanId;
      var summary=document.createElement('summary');var main=document.createElement('div');main.className='danpus-report-summary-main';
      var chevron=document.createElement('span');chevron.className='danpus-report-chevron';
      var subject=document.createElement('span');subject.className='danpus-report-subject';subject.textContent=subjectText;
      main.appendChild(chevron);main.appendChild(subject);summary.appendChild(main);details.appendChild(summary);
      var content=document.createElement('div');content.className='danpus-report-content';
      ordered.forEach(function(log){log.dataset.historySource='1';log.hidden=log!==latest;content.appendChild(log)});
      details.appendChild(content);list.appendChild(details);
    });
    wrapper.innerHTML='';wrapper.appendChild(list);wrapper.dataset.dropdownReady='1';
    setTimeout(function(){enhanceHistories(wrapper)},0);
  }
  function applyDanpusActivityDropdown(){
    if(!document.body)return;
    document.querySelectorAll('section[id^="satlak-"] .clean-table-wrap').forEach(wrapLogsInDropdown);
    enhanceHistories(document);
  }
  window.siberadRefreshDanpusActivityDropdown=applyDanpusActivityDropdown;
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){setTimeout(applyDanpusActivityDropdown,0)});
  else setTimeout(applyDanpusActivityDropdown,0);
})();
</script>
