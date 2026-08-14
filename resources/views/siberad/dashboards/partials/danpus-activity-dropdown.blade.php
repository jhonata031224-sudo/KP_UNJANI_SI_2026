<style>
  /* Danpus: tiap laporan/permintaan jadi item dropdown sendiri-sendiri
     (biar daftar Log Aktivitas Satlak nggak jadi panjang menumpuk),
     TANPA digabung sama laporan lain -- walau perihalnya kebetulan sama,
     itu tetap 2 permintaan/thread yang beda dan nggak berhubungan. */
  .danpus-report-dropdown-list{display:flex;flex-direction:column;gap:9px}
  .danpus-report-dropdown{border:1px solid var(--p-border);border-radius:12px;background:var(--p-surface-2);overflow:hidden}
  .danpus-report-dropdown summary{list-style:none;cursor:pointer;padding:13px 15px;display:flex;align-items:center;justify-content:space-between;gap:14px}
  .danpus-report-dropdown summary::-webkit-details-marker{display:none}
  .danpus-report-dropdown summary:hover{background:var(--hover-tint)}
  .danpus-report-summary-main{min-width:0;display:flex;align-items:center;gap:10px}
  .danpus-report-chevron{width:8px;height:8px;border-right:2px solid var(--p-muted);border-bottom:2px solid var(--p-muted);transform:rotate(-45deg);transition:transform .18s ease;flex:0 0 auto}
  .danpus-report-dropdown[open] .danpus-report-chevron{transform:rotate(45deg)}
  .danpus-report-subject{font-size:12px;font-weight:800;color:var(--p-text);line-height:1.45;word-break:break-word}
  .danpus-report-content{padding:0 14px 14px;border-top:1px solid var(--p-border)}
  .danpus-report-content .danpus-activity-log{margin-top:12px}
  .danpus-report-content .danpus-activity-project{display:none}
</style>
<script>
(function(){
  function wrapLogsInDropdown(wrapper){
    if(wrapper.dataset.dropdownReady==='1') return;
    var logs=Array.from(wrapper.querySelectorAll(':scope > .danpus-activity-log'));
    if(!logs.length) return;

    var list=document.createElement('div');
    list.className='danpus-report-dropdown-list';

    logs.forEach(function(log){
      var subjectText=log.querySelector('.danpus-activity-project')?.textContent.trim() || 'Laporan tanpa perihal';

      var details=document.createElement('details');
      details.className='danpus-report-dropdown';
      if(log.dataset.permintaanId) details.dataset.permintaanId=log.dataset.permintaanId;

      var summary=document.createElement('summary');
      var main=document.createElement('div');main.className='danpus-report-summary-main';
      var chevron=document.createElement('span');chevron.className='danpus-report-chevron';
      var subject=document.createElement('span');subject.className='danpus-report-subject';subject.textContent=subjectText;
      main.appendChild(chevron);main.appendChild(subject);
      summary.appendChild(main);
      details.appendChild(summary);

      var content=document.createElement('div');content.className='danpus-report-content';
      content.appendChild(log);
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

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',function(){setTimeout(applyDanpusActivityDropdown,0)});
  else setTimeout(applyDanpusActivityDropdown,0);
})();
</script>
