<style>
  .side-nav-group .side-dropdown-menu{padding:0!important;margin:0!important}
  .side-nav-group .side-sub-link{display:flex!important;align-items:center!important;gap:8px!important;margin:0!important;padding:2px 0 2px 17px!important;min-height:32px!important;height:32px!important;box-sizing:border-box!important;text-transform:none!important}
  .side-nav-group .side-dropdown-menu .side-sub-link:first-child{margin-top:2px!important}
  .side-nav-group .side-dropdown-menu .side-sub-link+.side-sub-link{margin-top:0!important}
  .side-nav-group .side-dropdown-menu .side-sub-link:before,.side-nav-group .side-dropdown-menu .side-sub-link:after{display:none!important;content:none!important}

  /* Log aktivitas Danpus dibuat sebagai alur proses laporan, seperti tracking paket. */
  .danpus-activity-log{display:flex;flex-direction:column;gap:0;margin-top:4px}
  .danpus-activity-item{position:relative;display:grid;grid-template-columns:22px minmax(0,1fr);column-gap:12px;padding:0 0 16px}
  .danpus-activity-item:last-child{padding-bottom:0}
  .danpus-activity-line{position:absolute;left:8px;top:18px;bottom:0;width:2px;background:var(--p-border)}
  .danpus-activity-item.is-done .danpus-activity-line{background:var(--p-accent)}
  .danpus-activity-item:last-child .danpus-activity-line{display:none}
  .danpus-activity-dot{position:relative;z-index:2;width:18px;height:18px;margin-top:0;border-radius:50%;background:var(--p-surface);border:2px solid var(--p-border);box-sizing:border-box}
  .danpus-activity-item.is-done .danpus-activity-dot{background:var(--p-accent);border-color:var(--p-accent)}
  .danpus-activity-item.is-current .danpus-activity-dot{border-color:var(--p-accent);box-shadow:0 0 0 4px color-mix(in srgb,var(--p-accent) 15%,transparent)}
  .danpus-activity-card{background:var(--p-surface-2);border:1px solid var(--p-border);border-radius:12px;padding:11px 14px;min-width:0}
  .danpus-activity-item.is-done .danpus-activity-card{border-color:color-mix(in srgb,var(--p-accent) 35%,var(--p-border))}
  .danpus-activity-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
  .danpus-activity-stage{font-size:12px;font-weight:800;color:var(--p-text);line-height:1.4}
  .danpus-activity-description{margin-top:3px;font-size:10px;line-height:1.45;color:var(--p-muted)}
  .danpus-activity-date{flex:0 0 auto;font-size:9px;color:var(--p-muted);white-space:nowrap}
  .danpus-activity-state{display:inline-flex;margin-top:7px;padding:3px 7px;border-radius:999px;font-size:9px;font-weight:700;background:var(--p-surface);color:var(--p-muted);border:1px solid var(--p-border)}
  .danpus-activity-item.is-done .danpus-activity-state{color:var(--p-accent);border-color:color-mix(in srgb,var(--p-accent) 35%,var(--p-border))}
  .danpus-activity-item.is-current .danpus-activity-state{color:var(--p-accent)}
  .danpus-activity-empty{padding:25px 12px;text-align:center;color:var(--p-muted);font-size:12px;border:1px dashed var(--p-border);border-radius:12px}
  @media(max-width:700px){.danpus-activity-head{display:block}.danpus-activity-date{margin-top:5px}}
</style>
<script>
(function(){
  function normalizeDanpusSubmenuText(){
    var labels={'SATLAKKAL':'Satlakkal','SATLAKSISOS':'Satlaksisos','SATLAKDAK':'Satlakdak','SATLAKDUKTEK':'Satlakduktek','BINFUNG':'Binfung','BINUM':'Binum','BINMAT':'Binmat','DIKLAT':'Diklat'};
    document.querySelectorAll('.side-nav-group .side-sub-link').forEach(function(link){
      var text=link.textContent.trim();
      if(labels[text]) Array.prototype.slice.call(link.childNodes).forEach(function(node){
        if(node.nodeType===Node.TEXT_NODE&&node.nodeValue.trim()) node.nodeValue=node.nodeValue.replace(text,labels[text]);
      });
    });
  }
  function removeDanpusProfileHelpText(){var el=document.querySelector('#profilePhotoView .profile-help-text');if(el)el.remove()}

  function getCellText(cells,index){return cells[index]?cells[index].textContent.trim():''}
  function getStatus(cells){
    var text=Array.from(cells).map(function(c){return c.textContent.trim().toLowerCase()}).join(' ');
    if(text.includes('ditolak')) return 'ditolak';
    if(text.includes('diterima')||text.includes('disetujui')||text.includes('selesai')) return 'selesai';
    if(text.includes('ditinjau')||text.includes('proses')||text.includes('diproses')) return 'ditinjau';
    return 'dikirim';
  }
  function buildProcessLog(row){
    var cells=row.querySelectorAll('td');
    var subject=cells[0]?.querySelector('.subject')?.textContent.trim()||getCellText(cells,0)||'Laporan aktivitas';
    var laporanDate=getCellText(cells,4)||getCellText(cells,3)||'-';
    var status=getStatus(cells);
    var permintaanCreated=row.dataset.permintaanCreated||'';
    var permintaanDitinjau=row.dataset.permintaanDitinjau||'';
    var hasPermintaan=!!permintaanCreated;
    var decided=status==='ditolak'||status==='selesai';

    var stages=hasPermintaan?[
      {key:'permintaan_dikirim',title:'Permintaan Dikirim',desc:'Danpus/Pimpinan mengirimkan permintaan laporan kepada satuan.',date:permintaanCreated},
      {key:'permintaan_ditinjau',title:'Permintaan Ditinjau',desc:'Satuan telah melihat dan menindaklanjuti permintaan tersebut.',date:permintaanDitinjau||laporanDate},
      {key:'laporan_dibuat',title:'Laporan Dibuat',desc:'Satuan menyiapkan dan menyusun laporan.',date:laporanDate},
      {key:'laporan_dikirim',title:'Laporan Dikirim',desc:'Laporan dikirim oleh satuan untuk diperiksa Pimpinan/Danpus.',date:laporanDate},
      {key:'laporan_selesai',title:'Laporan Selesai',desc:'Laporan telah mendapatkan hasil akhir (disetujui/ditolak).',date:decided?laporanDate:''}
    ]:[
      {key:'laporan_dibuat',title:'Laporan Dibuat',desc:'Satuan menyiapkan dan menyusun laporan.',date:laporanDate},
      {key:'laporan_dikirim',title:'Laporan Dikirim',desc:'Laporan dikirim oleh satuan untuk diperiksa Pimpinan/Danpus.',date:laporanDate},
      {key:'laporan_selesai',title:'Laporan Selesai',desc:'Laporan telah mendapatkan hasil akhir (disetujui/ditolak).',date:decided?laporanDate:''}
    ];

    var finalIndex=stages.length-1;
    var progress=decided?stages.length:finalIndex;
    var log=document.createElement('div');log.className='danpus-activity-log';
    stages.forEach(function(stage,index){
      var item=document.createElement('article');item.className='danpus-activity-item';
      if(index<progress)item.classList.add('is-done');
      if(index===progress)item.classList.add('is-current');
      var dot=document.createElement('div');dot.className='danpus-activity-dot';item.appendChild(dot);
      if(index<stages.length-1){var line=document.createElement('div');line.className='danpus-activity-line';item.appendChild(line)}
      var card=document.createElement('div');card.className='danpus-activity-card';
      var head=document.createElement('div');head.className='danpus-activity-head';
      var stageEl=document.createElement('div');stageEl.className='danpus-activity-stage';stageEl.textContent=stage.title;
      var dateEl=document.createElement('div');dateEl.className='danpus-activity-date';dateEl.textContent=stage.date||'';
      head.appendChild(stageEl);head.appendChild(dateEl);card.appendChild(head);
      var desc=document.createElement('div');desc.className='danpus-activity-description';desc.textContent=stage.desc;card.appendChild(desc);
      var state=document.createElement('span');state.className='danpus-activity-state';
      if(index<progress) state.textContent='Selesai';
      else if(index===progress) state.textContent=status==='ditolak'?'Ditolak':'Sedang diproses';
      else state.textContent='Menunggu';
      if(stage.key==='laporan_selesai'&&status==='ditolak') state.textContent='Ditolak';
      card.appendChild(state);item.appendChild(card);log.appendChild(item);
    });
    var title=document.createElement('div');title.className='danpus-activity-project';title.textContent=subject;log.prepend(title);
    return log;
  }

  function buildPendingPermintaanLog(p){
    var stages=[
      {key:'permintaan_dikirim',title:'Permintaan Dikirim',desc:'Danpus/Pimpinan mengirimkan permintaan laporan kepada satuan.',date:p.created||''},
      {key:'permintaan_ditinjau',title:'Permintaan Ditinjau',desc:'Satuan telah melihat dan menindaklanjuti permintaan tersebut.',date:p.ditinjau||''},
      {key:'laporan_dibuat',title:'Laporan Dibuat',desc:'Satuan menyiapkan dan menyusun laporan.',date:''},
      {key:'laporan_dikirim',title:'Laporan Dikirim',desc:'Laporan dikirim oleh satuan untuk diperiksa Pimpinan/Danpus.',date:''},
      {key:'laporan_selesai',title:'Laporan Selesai',desc:'Laporan telah mendapatkan hasil akhir (disetujui/ditolak).',date:''}
    ];
    var progress=p.ditinjau?2:1;
    var log=document.createElement('div');log.className='danpus-activity-log';
    stages.forEach(function(stage,index){
      var item=document.createElement('article');item.className='danpus-activity-item';
      if(index<progress)item.classList.add('is-done');
      if(index===progress)item.classList.add('is-current');
      var dot=document.createElement('div');dot.className='danpus-activity-dot';item.appendChild(dot);
      if(index<stages.length-1){var line=document.createElement('div');line.className='danpus-activity-line';item.appendChild(line)}
      var card=document.createElement('div');card.className='danpus-activity-card';
      var head=document.createElement('div');head.className='danpus-activity-head';
      var stageEl=document.createElement('div');stageEl.className='danpus-activity-stage';stageEl.textContent=stage.title;
      var dateEl=document.createElement('div');dateEl.className='danpus-activity-date';dateEl.textContent=stage.date||'';
      head.appendChild(stageEl);head.appendChild(dateEl);card.appendChild(head);
      var desc=document.createElement('div');desc.className='danpus-activity-description';desc.textContent=stage.desc;card.appendChild(desc);
      var state=document.createElement('span');state.className='danpus-activity-state';
      if(index<progress) state.textContent='Selesai';
      else if(index===progress) state.textContent='Sedang diproses';
      else state.textContent='Menunggu';
      card.appendChild(state);item.appendChild(card);log.appendChild(item);
    });
    var title=document.createElement('div');title.className='danpus-activity-project';title.textContent=p.subject||'Permintaan laporan';log.prepend(title);
    return log;
  }

  function buildActivityTimeline(){
    @if(!in_array(strtoupper((string) ($satuan->kode ?? '')), ['DANPUS', 'WADAN'], true)) return; @endif
    document.querySelectorAll('section[id^="satlak-"] .clean-table-wrap').forEach(function(wrapper){
      if(wrapper.dataset.timelineReady==='1')return;
      var table=wrapper.querySelector('table');if(!table)return;
      var rows=Array.from(table.querySelectorAll('tbody tr')).filter(function(row){return row.querySelectorAll('td').length>1});
      var pending=[];
      try{ pending=JSON.parse(wrapper.dataset.pendingPermintaan||'[]'); }catch(e){}
      wrapper.innerHTML='';
      if(!rows.length&&!pending.length){var empty=document.createElement('div');empty.className='danpus-activity-empty';empty.textContent='Belum ada aktivitas dari satuan ini.';wrapper.appendChild(empty)}
      else{
        rows.forEach(function(row){wrapper.appendChild(buildProcessLog(row))});
        pending.forEach(function(p){wrapper.appendChild(buildPendingPermintaanLog(p))});
      }
      wrapper.dataset.timelineReady='1';
    });
  }
  function applyDanpusSubmenuFix(){normalizeDanpusSubmenuText();removeDanpusProfileHelpText();buildActivityTimeline()}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',applyDanpusSubmenuFix);else applyDanpusSubmenuFix();
})();
</script>
