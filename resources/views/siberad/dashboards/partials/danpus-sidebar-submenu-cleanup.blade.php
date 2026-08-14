<style>
  .side-nav-group .side-dropdown-menu{padding:0!important;margin:0!important}
  .side-nav-group .side-sub-link{display:flex!important;align-items:center!important;gap:8px!important;margin:0!important;padding:2px 0 2px 17px!important;min-height:32px!important;height:32px!important;box-sizing:border-box!important;text-transform:none!important}
  .side-nav-group .side-dropdown-menu .side-sub-link:first-child{margin-top:2px!important}
  .side-nav-group .side-dropdown-menu .side-sub-link+.side-sub-link{margin-top:0!important}
  .side-nav-group .side-dropdown-menu .side-sub-link:before,.side-nav-group .side-dropdown-menu .side-sub-link:after{display:none!important;content:none!important}

  /* Log aktivitas Danpus -- timeline satu warna aksen (gold/brand) buat
     tahap yang sudah dilalui, bukan campuran hijau/merah/kuning/biru
     sekaligus. Hijau & merah cuma dipakai buat keputusan akhir (disetujui/
     ditolak), biru cuma buat elemen progres -- konsisten sama status-pill/
     satuan-pill/priority-tag yang sudah dipakai di tempat lain. */
  .danpus-activity-log{display:flex;flex-direction:column;gap:0;margin-top:6px}
  .danpus-activity-project{font-size:13px;font-weight:800;color:var(--p-text);margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--p-border)}
  .danpus-activity-item{position:relative;display:grid;grid-template-columns:22px minmax(0,1fr);column-gap:14px;padding:0 0 18px}
  .danpus-activity-item:last-child{padding-bottom:0}
  .danpus-activity-line{position:absolute;left:10px;top:22px;bottom:-2px;width:2px;background:var(--p-border);transition:background .3s ease}
  .danpus-activity-item.is-done .danpus-activity-line,.danpus-activity-item.is-approved .danpus-activity-line{background:var(--p-accent)}
  .danpus-activity-item.is-rejected .danpus-activity-line{background:var(--p-red)}
  .danpus-activity-item:last-child .danpus-activity-line{display:none}
  .danpus-activity-dot{position:relative;z-index:2;width:22px;height:22px;border-radius:50%;background:var(--p-surface);border:2px solid var(--p-border);box-sizing:border-box;display:flex;align-items:center;justify-content:center;transition:background .25s ease,border-color .25s ease}
  .danpus-activity-dot svg{width:11px;height:11px;stroke:#fff;stroke-width:3;fill:none;opacity:0;transform:scale(.6);transition:opacity .2s ease,transform .2s ease}
  .danpus-activity-item.is-done .danpus-activity-dot,.danpus-activity-item.is-approved .danpus-activity-dot{background:var(--p-accent);border-color:var(--p-accent)}
  .danpus-activity-item.is-done .danpus-activity-dot svg,.danpus-activity-item.is-approved .danpus-activity-dot svg{opacity:1;transform:scale(1)}
  .danpus-activity-item.is-rejected .danpus-activity-dot{background:var(--p-red);border-color:var(--p-red)}
  .danpus-activity-item.is-rejected .danpus-activity-dot svg{opacity:1;transform:scale(1)}
  .danpus-activity-item.is-current .danpus-activity-dot{border-color:var(--p-accent);box-shadow:0 0 0 4px color-mix(in srgb,var(--p-accent) 16%,transparent)}
  .danpus-activity-item.is-current .danpus-activity-dot::after{content:"";width:8px;height:8px;border-radius:50%;background:var(--p-accent);animation:danpusDotPing 1.6s ease-in-out infinite}
  @keyframes danpusDotPing{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.45;transform:scale(1.35)}}
  .danpus-activity-card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:12px;padding:13px 16px;min-width:0;box-shadow:0 1px 2px rgba(0,0,0,.04);transition:border-color .25s ease,box-shadow .25s ease}
  .danpus-activity-item.is-done .danpus-activity-card,.danpus-activity-item.is-approved .danpus-activity-card{border-color:color-mix(in srgb,var(--p-accent) 25%,var(--p-border))}
  .danpus-activity-item.is-current .danpus-activity-card{border-color:color-mix(in srgb,var(--p-accent) 40%,var(--p-border));box-shadow:0 4px 14px -6px color-mix(in srgb,var(--p-accent) 30%,transparent)}
  .danpus-activity-item.is-rejected .danpus-activity-card{border-color:color-mix(in srgb,var(--p-red) 30%,var(--p-border))}
  .danpus-activity-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
  .danpus-activity-stage{font-size:12.5px;font-weight:700;color:var(--p-text);line-height:1.4}
  .danpus-activity-description{margin-top:4px;font-size:11px;line-height:1.55;color:var(--p-muted)}
  .danpus-activity-date{flex:0 0 auto;font-size:10px;font-family:var(--mono);color:var(--p-muted);white-space:nowrap;padding-top:1px}
  .danpus-activity-state{display:inline-flex;align-items:center;gap:5px;margin-top:10px;padding:4px 10px;border-radius:999px;font-size:9.5px;font-weight:700;letter-spacing:.02em;background:var(--p-surface-2);color:var(--p-muted);border:1px solid var(--p-border)}
  .danpus-activity-state::before{content:"";width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0}
  .danpus-activity-item.is-done .danpus-activity-state,.danpus-activity-item.is-current .danpus-activity-state{color:var(--p-accent);background:color-mix(in srgb,var(--p-accent) 10%,transparent);border-color:color-mix(in srgb,var(--p-accent) 28%,transparent)}
  .danpus-activity-item.is-approved .danpus-activity-state{color:var(--p-green);background:color-mix(in srgb,var(--p-green) 10%,transparent);border-color:color-mix(in srgb,var(--p-green) 28%,transparent)}
  .danpus-activity-item.is-rejected .danpus-activity-state{color:var(--p-red);background:color-mix(in srgb,var(--p-red) 10%,transparent);border-color:color-mix(in srgb,var(--p-red) 28%,transparent)}
  .danpus-activity-empty{padding:28px 12px;text-align:center;color:var(--p-muted);font-size:12px;border:2px dotted var(--p-border);border-radius:12px}
  .danpus-progress-dropdown{margin-top:12px}
  .danpus-progress-dropdown summary{cursor:pointer;font-size:11px;font-weight:700;color:#2476ad;list-style:none;display:inline-flex;align-items:center;gap:6px;padding:6px 11px;border-radius:8px;background:rgba(52,152,219,.08);border:1px solid rgba(52,152,219,.22);transition:background .15s ease}
  .danpus-progress-dropdown summary:hover{background:rgba(52,152,219,.14)}
  .danpus-progress-dropdown summary::-webkit-details-marker{display:none}
  .danpus-progress-dropdown summary .chevron{width:11px;height:11px;stroke:currentColor;stroke-width:2.5;fill:none;flex-shrink:0;transition:transform .2s ease}
  .danpus-progress-dropdown[open] summary .chevron{transform:rotate(180deg)}
  .danpus-progress-list{display:flex;flex-direction:column;gap:6px;margin-top:10px}
  .danpus-progress-entry{display:flex;align-items:center;gap:10px;padding:9px 11px;border-radius:9px;background:var(--p-surface-2);border:1px solid var(--p-border)}
  .danpus-progress-percent{font-family:var(--mono);font-size:11px;font-weight:800;color:#2476ad;min-width:38px;flex-shrink:0}
  .danpus-progress-date{color:var(--p-muted);font-size:10.5px;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .danpus-progress-entry .detail-btn{flex-shrink:0}
  .danpus-progress-detail-wrap{margin-top:12px}
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
    if(text.includes('progres')) return 'progres';
    if(text.includes('ditolak')) return 'ditolak';
    if(text.includes('diterima')||text.includes('disetujui')||text.includes('selesai')) return 'selesai';
    if(text.includes('ditinjau')||text.includes('proses')||text.includes('diproses')) return 'ditinjau';
    return 'dikirim';
  }
  // Riwayat progres (dropdown) di tahap "Laporan Dibuat" -- tiap entry
  // pakai tombol .detail-btn ASLI yang dipindah (bukan diklon) dari baris
  // tabelnya, biar semua data-* (termasuk lampiran) & handler openReportDetail
  // ikut terbawa persis tanpa perlu ditulis ulang.
  function buildProgressDropdown(rows){
    var details=document.createElement('details');details.className='danpus-progress-dropdown';
    var summary=document.createElement('summary');
    summary.innerHTML='<span>Riwayat progres ('+rows.length+')</span><svg class="chevron" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>';
    details.appendChild(summary);
    var list=document.createElement('div');list.className='danpus-progress-list';
    rows.forEach(function(row){
      var cells=row.querySelectorAll('td');
      var progres=row.dataset.progres||'0';
      var date=getCellText(cells,4)||getCellText(cells,3)||'-';
      var entry=document.createElement('div');entry.className='danpus-progress-entry';
      var pct=document.createElement('span');pct.className='danpus-progress-percent';pct.textContent=progres+'%';
      var d=document.createElement('span');d.className='danpus-progress-date';d.textContent=date;
      entry.appendChild(pct);entry.appendChild(d);
      var btn=row.querySelector('.detail-btn');
      if(btn) entry.appendChild(btn);
      list.appendChild(entry);
    });
    details.appendChild(list);
    return details;
  }

  // rows: array baris Laporan MILIK SATU permintaan yang sama (atau satu
  // baris tunggal untuk laporan ad-hoc tanpa permintaan), urutan terbaru
  // dulu (mengikuti latest() dari backend). Progres 10%->25%->60%->100%
  // untuk satu permintaan jadi SATU pipeline dengan riwayat bercabang di
  // tahap "Laporan Dibuat", bukan kartu terpisah per baris.
  function buildProcessLog(rows){
    var cells0=rows[0].querySelectorAll('td');
    var subject=cells0[0]?.querySelector('.subject')?.textContent.trim()||getCellText(cells0,0)||'Laporan aktivitas';
    var permintaanCreated=rows[0].dataset.permintaanCreated||'';
    var permintaanDitinjau=rows[0].dataset.permintaanDitinjau||'';
    var hasPermintaan=!!permintaanCreated;

    var finalRows=rows.filter(function(r){return (r.dataset.progres||'100')==='100'});
    var progressRows=rows.filter(function(r){return (r.dataset.progres||'100')!=='100'}).concat(finalRows.slice(1));
    var finalRow=finalRows[0]||null;
    var finalCells=finalRow?finalRow.querySelectorAll('td'):null;
    var finalStatus=finalRow?getStatus(finalCells):null;
    var refCells=finalRow?finalCells:cells0;
    var laporanDate=getCellText(refCells,4)||getCellText(refCells,3)||'-';
    var decided=finalStatus==='ditolak'||finalStatus==='selesai';

    var stages=hasPermintaan?[
      {key:'permintaan_dikirim',title:'Permintaan Terkirim',desc:'Danpus/Pimpinan mengirimkan permintaan laporan kepada satuan.',date:permintaanCreated},
      {key:'permintaan_ditinjau',title:'Permintaan Ditinjau',desc:'Satuan telah melihat dan menindaklanjuti permintaan tersebut.',date:permintaanDitinjau||laporanDate},
      {key:'laporan_dibuat',title:'Laporan Dibuat',desc:'Satuan menyusun laporan, termasuk update progres berkala sebelum laporan final dikirim.',date:laporanDate},
      {key:'laporan_dikirim',title:'Laporan Terkirim',desc:'Laporan final dikirim oleh satuan untuk diperiksa Pimpinan/Danpus.',date:finalRow?laporanDate:''},
      {key:'laporan_selesai',title:'Laporan Selesai',desc:'Laporan telah mendapatkan hasil akhir (disetujui/ditolak).',date:decided?laporanDate:''}
    ]:[
      {key:'laporan_dibuat',title:'Laporan Dibuat',desc:'Satuan menyusun laporan, termasuk update progres berkala sebelum laporan final dikirim.',date:laporanDate},
      {key:'laporan_dikirim',title:'Laporan Terkirim',desc:'Laporan final dikirim oleh satuan untuk diperiksa Pimpinan/Danpus.',date:finalRow?laporanDate:''},
      {key:'laporan_selesai',title:'Laporan Selesai',desc:'Laporan telah mendapatkan hasil akhir (disetujui/ditolak).',date:decided?laporanDate:''}
    ];

    var finalIndex=stages.length-1;
    var dibuatIndex=finalIndex-2;
    var terkirimIndex=finalIndex-1;
    var progress=!finalRow?dibuatIndex:(decided?stages.length:terkirimIndex);
    var log=document.createElement('div');log.className='danpus-activity-log';
    stages.forEach(function(stage,index){
      var item=document.createElement('article');item.className='danpus-activity-item';
      var isFinal=index===finalIndex;
      var isRejectedFinal=isFinal&&decided&&finalStatus==='ditolak';
      var isApprovedFinal=isFinal&&decided&&finalStatus==='selesai';
      if(index<progress&&!isFinal)item.classList.add('is-done');
      if(index===progress)item.classList.add('is-current');
      if(isRejectedFinal)item.classList.add('is-rejected');
      if(isApprovedFinal)item.classList.add('is-approved');
      var dot=document.createElement('div');dot.className='danpus-activity-dot';
      dot.innerHTML=isRejectedFinal
        ?'<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>'
        :'<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';
      item.appendChild(dot);
      if(index<stages.length-1){var line=document.createElement('div');line.className='danpus-activity-line';item.appendChild(line)}
      var card=document.createElement('div');card.className='danpus-activity-card';
      var head=document.createElement('div');head.className='danpus-activity-head';
      var stageEl=document.createElement('div');stageEl.className='danpus-activity-stage';stageEl.textContent=stage.title;
      var dateEl=document.createElement('div');dateEl.className='danpus-activity-date';dateEl.textContent=stage.date||'';
      head.appendChild(stageEl);head.appendChild(dateEl);card.appendChild(head);
      var desc=document.createElement('div');desc.className='danpus-activity-description';desc.textContent=stage.desc;card.appendChild(desc);
      if(stage.key==='laporan_dibuat'&&progressRows.length) card.appendChild(buildProgressDropdown(progressRows));
      if(finalRow&&stage.key===(decided?'laporan_selesai':'laporan_dikirim')){
        var finalBtn=finalRow.querySelector('.detail-btn');
        if(finalBtn){var wrap=document.createElement('div');wrap.className='danpus-progress-detail-wrap';wrap.appendChild(finalBtn);card.appendChild(wrap)}
      }
      var state=document.createElement('span');state.className='danpus-activity-state';
      if(isFinal){
        if(finalStatus==='selesai') state.textContent='Selesai · Disetujui';
        else if(finalStatus==='ditolak') state.textContent='Selesai · Ditolak';
        else if(finalRow) state.textContent='Sedang diproses';
        else state.textContent='Menunggu';
      }else if(index<progress) state.textContent='Selesai';
      else if(index===progress) state.textContent='Sedang diproses';
      else state.textContent='Menunggu';
      card.appendChild(state);item.appendChild(card);log.appendChild(item);
    });
    var title=document.createElement('div');title.className='danpus-activity-project';title.textContent=subject;log.prepend(title);
    return log;
  }

  function buildPendingPermintaanLog(p){
    var stages=[
      {key:'permintaan_dikirim',title:'Permintaan Terkirim',desc:'Danpus/Pimpinan mengirimkan permintaan laporan kepada satuan.',date:p.created||''},
      {key:'permintaan_ditinjau',title:'Permintaan Ditinjau',desc:'Satuan telah melihat dan menindaklanjuti permintaan tersebut.',date:p.ditinjau||''},
      {key:'laporan_dibuat',title:'Laporan Dibuat',desc:'Satuan menyiapkan dan menyusun laporan.',date:''},
      {key:'laporan_dikirim',title:'Laporan Terkirim',desc:'Laporan dikirim oleh satuan untuk diperiksa Pimpinan/Danpus.',date:''},
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

      // Kelompokin baris Laporan berdasar permintaan_laporan_id -- satu
      // permintaan yang diisi progres bertahap (banyak baris Laporan)
      // dirender jadi SATU pipeline, bukan pipeline berulang per baris.
      var groups=[];var groupMap={};
      rows.forEach(function(row){
        var key=row.dataset.permintaanId||('laporan-'+(row.dataset.laporanId||Math.random()));
        if(!groupMap[key]){groupMap[key]={rows:[]};groups.push(groupMap[key])}
        groupMap[key].rows.push(row);
      });

      wrapper.innerHTML='';
      if(!groups.length&&!pending.length){var empty=document.createElement('div');empty.className='danpus-activity-empty';empty.innerHTML='<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 8px;display:block;"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg>Belum ada aktivitas dari satuan ini.';wrapper.appendChild(empty)}
      else{
        groups.forEach(function(g){wrapper.appendChild(buildProcessLog(g.rows))});
        pending.forEach(function(p){wrapper.appendChild(buildPendingPermintaanLog(p))});
      }
      wrapper.dataset.timelineReady='1';
    });
  }
  function applyDanpusSubmenuFix(){normalizeDanpusSubmenuText();removeDanpusProfileHelpText();buildActivityTimeline()}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',applyDanpusSubmenuFix);else applyDanpusSubmenuFix();
})();
</script>
