<style>
  .side-nav-group .side-dropdown-menu{padding:0!important;margin:0!important}
  .side-nav-group .side-sub-link{display:flex!important;align-items:center!important;gap:10px!important;margin:0!important;padding:9px 12px 9px 17px!important;min-height:0!important;height:auto!important;box-sizing:border-box!important;text-transform:none!important;line-height:1.4!important}
  .side-nav-group .side-sub-link .sub-dot{flex-shrink:0!important}
  .side-nav-group .side-dropdown-menu .side-sub-link:before,.side-nav-group .side-dropdown-menu .side-sub-link:after{display:none!important;content:none!important}

  /* Log aktivitas Danpus -- warna per status: hijau = tahap selesai/
     hasil disetujui, oranye tegas (var(--p-orange), BUKAN var(--p-yellow)/
     gold) = tahap yang sedang berjalan, merah = hasil ditolak. */
  .danpus-activity-log{display:flex;flex-direction:column;gap:0;margin-top:6px}
  .danpus-activity-project{font-size:13px;font-weight:800;color:var(--p-text);margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--p-border)}
  .danpus-activity-item{position:relative;display:grid;grid-template-columns:22px minmax(0,1fr);column-gap:14px;padding:0 0 18px}
  .danpus-activity-item:last-child{padding-bottom:0}
  .danpus-activity-line{position:absolute;left:9px;top:28px;bottom:6px;width:0;border-left:4px dotted var(--p-border)}
  /* Isian konektor: overlay titik-titik (bukan garis solid) yang TUMBUH dari
     atas ke bawah lewat height 0 -> 100%. Karena border-left-nya juga "dotted",
     pas tingginya nambah, titik-titiknya keisi satu per satu -- persis "ngisi
     titik-titik". Base .danpus-activity-line tetap abu-abu di belakangnya.
     left:-4px biar border-left overlay pas nindih border-left base. */
  .danpus-activity-line::after{content:"";position:absolute;left:-4px;top:0;width:0;height:0;border-left:4px dotted var(--p-green);transition:height .55s cubic-bezier(.22,1,.36,1)}
  .danpus-activity-item.is-done .danpus-activity-line::after,.danpus-activity-item.is-approved .danpus-activity-line::after{height:100%}
  .danpus-activity-item.is-rejected .danpus-activity-line::after{height:100%;border-left-color:var(--p-red)}
  .danpus-activity-item:last-child .danpus-activity-line{display:none}
  @media(prefers-reduced-motion:reduce){.danpus-activity-line::after{transition:none}}
  .danpus-activity-dot{position:relative;z-index:2;width:22px;height:22px;border-radius:50%;background:var(--p-surface);border:2px solid var(--p-border);box-sizing:border-box;display:flex;align-items:center;justify-content:center;transition:background .25s ease,border-color .25s ease}
  .danpus-activity-dot svg{width:11px;height:11px;stroke:#fff;stroke-width:3;fill:none;opacity:0;transform:scale(.6);transition:opacity .2s ease,transform .2s ease}
  .danpus-activity-item.is-done .danpus-activity-dot,.danpus-activity-item.is-approved .danpus-activity-dot{background:var(--p-green);border-color:var(--p-green)}
  .danpus-activity-item.is-done .danpus-activity-dot svg,.danpus-activity-item.is-approved .danpus-activity-dot svg{opacity:1;transform:scale(1)}
  .danpus-activity-item.is-rejected .danpus-activity-dot{background:var(--p-red);border-color:var(--p-red)}
  .danpus-activity-item.is-rejected .danpus-activity-dot svg{opacity:1;transform:scale(1)}
  .danpus-activity-item.is-current .danpus-activity-dot{border-color:var(--p-orange)}
  .danpus-activity-item.is-current .danpus-activity-dot::after{content:"";position:absolute;inset:3px;border-radius:50%;border:2px solid var(--p-orange-bg);border-top-color:var(--p-orange);border-right-color:var(--p-orange);animation:danpusDotSpin 2s linear infinite}
  @keyframes danpusDotSpin{to{transform:rotate(360deg)}}
  .danpus-activity-card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:12px;padding:13px 16px;min-width:0;box-shadow:0 1px 2px rgba(0,0,0,.04);transition:border-color .25s ease,box-shadow .25s ease}
  .danpus-activity-item.is-done .danpus-activity-card,.danpus-activity-item.is-approved .danpus-activity-card{border-color:color-mix(in srgb,var(--p-green) 25%,var(--p-border))}
  .danpus-activity-item.is-current .danpus-activity-card{border-color:color-mix(in srgb,var(--p-orange) 40%,var(--p-border));box-shadow:0 4px 14px -6px color-mix(in srgb,var(--p-orange) 30%,transparent)}
  .danpus-activity-item.is-rejected .danpus-activity-card{border-color:color-mix(in srgb,var(--p-red) 30%,var(--p-border))}
  .danpus-activity-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
  .danpus-activity-stage{font-size:12.5px;font-weight:700;color:var(--p-text);line-height:1.4}
  .danpus-activity-description{margin-top:4px;font-size:11px;line-height:1.55;color:var(--p-muted)}
  .danpus-activity-date{flex:0 0 auto;font-size:10px;font-family:var(--mono);color:var(--p-muted);white-space:nowrap;padding-top:1px}
  .danpus-activity-state{display:inline-flex;align-items:center;gap:5px;margin-top:10px;padding:4px 10px;border-radius:999px;font-size:9.5px;font-weight:700;letter-spacing:.02em;background:var(--p-surface-2);color:var(--p-muted);border:1px solid var(--p-border)}
  .danpus-activity-state::before{content:"";width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0}
  .danpus-activity-item.is-done .danpus-activity-state,.danpus-activity-item.is-approved .danpus-activity-state{color:var(--p-green);background:color-mix(in srgb,var(--p-green) 10%,transparent);border-color:color-mix(in srgb,var(--p-green) 28%,transparent)}
  .danpus-activity-item.is-current .danpus-activity-state{color:var(--p-orange);background:var(--p-orange-bg);border-color:var(--p-orange-border)}
  /* Checkpoint yang lagi nunggu satuan kirim ulang setelah di-Revisi --
     gold, sama persis kayak status-pill/tombol Revisi di tab Permintaan
     Laporan (--gold-solid), BUKAN oranye "Sedang diproses" biasa. */
  .danpus-activity-item.is-revisi .danpus-activity-dot{border-color:var(--gold-solid)}
  .danpus-activity-item.is-revisi .danpus-activity-dot::after{content:"";position:absolute;inset:3px;border-radius:50%;border:2px solid rgba(217,146,11,.14);border-top-color:var(--gold-solid);border-right-color:var(--gold-solid);animation:danpusDotSpin 2s linear infinite}
  .danpus-activity-item.is-revisi .danpus-activity-card{border-color:color-mix(in srgb,var(--gold-solid) 40%,var(--p-border));box-shadow:0 4px 14px -6px color-mix(in srgb,var(--gold-solid) 30%,transparent)}
  .danpus-activity-item.is-revisi .danpus-activity-state{color:var(--gold-solid);background:rgba(217,146,11,.14);border-color:rgba(217,146,11,.4)}
  /* Checkpoint yang lagi nunggu diperiksa Pimpinan -- biru, sama persis
     kayak pill "Menunggu pemeriksaan" (.deadline-pill.blue) di dashboard
     satuan, BUKAN oranye "Sedang diproses" biasa. */
  .danpus-activity-item.is-menunggu .danpus-activity-dot{border-color:#2476ad}
  .danpus-activity-item.is-menunggu .danpus-activity-dot::after{content:"";position:absolute;inset:3px;border-radius:50%;border:2px solid rgba(52,152,219,.1);border-top-color:#2476ad;border-right-color:#2476ad;animation:danpusDotSpin 2s linear infinite}
  .danpus-activity-item.is-menunggu .danpus-activity-card{border-color:color-mix(in srgb,#2476ad 40%,var(--p-border));box-shadow:0 4px 14px -6px color-mix(in srgb,#2476ad 30%,transparent)}
  .danpus-activity-item.is-menunggu .danpus-activity-state{color:#2476ad;background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.25)}
  .danpus-activity-item.is-rejected .danpus-activity-state{color:var(--p-red);background:color-mix(in srgb,var(--p-red) 10%,transparent);border-color:color-mix(in srgb,var(--p-red) 28%,transparent)}
  @media(max-width:700px){
    .danpus-activity-head{display:block}
    .danpus-activity-date{margin-top:5px}
  }
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
    if(text.includes('revisi')) return 'revisi';
    if(text.includes('ditolak')) return 'ditolak';
    if(text.includes('diterima')||text.includes('disetujui')||text.includes('selesai')) return 'selesai';
    if(text.includes('menunggu')) return 'menunggu';
    if(text.includes('ditinjau')||text.includes('proses')||text.includes('diproses')) return 'ditinjau';
    return 'dikirim';
  }
  // rows: array baris Laporan MILIK SATU permintaan yang sama (atau satu
  // baris tunggal untuk laporan ad-hoc tanpa permintaan), urutan terbaru
  // dulu (mengikuti latest() dari backend). Progres 10%->25%->60%->100%
  // untuk satu permintaan jadi SATU pipeline dengan riwayat bercabang di
  // tahap "Laporan Dibuat", bukan kartu terpisah per baris.
  function buildProcessLog(rows){
    var permintaanId=rows[0].dataset.permintaanId||'';
    var cells0=rows[0].querySelectorAll('td');
    var subject=cells0[0]?.querySelector('.subject')?.textContent.trim()||getCellText(cells0,0)||'Laporan aktivitas';
    var permintaanCreated=rows[0].dataset.permintaanCreated||'';
    var permintaanDitinjau=rows[0].dataset.permintaanDitinjau||'';
    var hasPermintaan=!!permintaanCreated;

    var finalRows=rows.filter(function(r){return (r.dataset.progres||'100')==='100'});
    var finalRow=finalRows[0]||null;
    var finalCells=finalRow?finalRow.querySelectorAll('td'):null;
    var finalStatus=finalRow?getStatus(finalCells):null;
    var refCells=finalRow?finalCells:cells0;
    var laporanDate=getCellText(refCells,4)||getCellText(refCells,3)||'-';
    // Keputusan final HARUS juga dicek ke status PermintaanLaporan-nya
    // sendiri (bukan cuma teks status laporan) -- soalnya kalau Pimpinan
    // buka lagi permintaan yang tadinya Ditolak (lewat tombol Edit
    // deadline), laporan lama yang ditolak itu TETAP ada di riwayat
    // (progres 100, status text masih "Ditolak ..."), tapi permintaannya
    // sendiri udah balik "Sedang dikerjakan" -- jadi jangan dianggap
    // keputusan final lagi. Laporan ad-hoc tanpa permintaan (!hasPermintaan)
    // gak punya status ini, jadi tetap pakai cara lama.
    var decided=(finalStatus==='ditolak'||finalStatus==='selesai')&&(!hasPermintaan||rows[0].dataset.permintaanStatus==='Selesai');
    // Permintaan yang dibatalkan Pimpinan sebelum ada keputusan akhir --
    // checkpoint yang SUDAH lewat (index<progress) tetap hijau/ceklis apa
    // adanya, checkpoint yang lagi berjalan & semua checkpoint SESUDAHNYA
    // dipaksa jadi merah/silang X (bukan cuma checkpoint terakhir).
    var isDibatalkan=!decided&&rows[0].dataset.permintaanStatus==='Dibatalkan';
    var dibatalkanAt=rows[0].dataset.permintaanDibatalkan||'';
    // Checkpoint yang lagi aktif tapi udah lewat deadline_at & belum ada
    // keputusan/pembatalan -- ganti tampilan spinner oranye "Sedang
    // diproses" jadi bulat merah+silang X "Terlambat" (cuma checkpoint
    // yang aktif SEKARANG, checkpoint sebelum/sesudahnya tetap normal).
    var isTerlambat=!isDibatalkan&&!decided&&rows[0].dataset.permintaanTerlambat==='1';
    // Checkpoint 100% yang baru DIBALIKIN Revisi (belum dikirim ulang sama
    // satuan) sengaja TETAP nempel jadi node terakhir di percabangan
    // "Laporan Dibuat" -- tapi begitu satuan udah kirim ulang (statusnya
    // balik "Menunggu", nunggu diperiksa Pimpinan lagi), itu laporan BARU
    // dianggap benar-benar "terkirim", jadi lanjut ke kartu "Laporan
    // Terkirim" sendiri (BUKAN nempel lagi di Laporan Dibuat).
    var isPendingRevisi=finalRow&&!decided&&finalStatus==='revisi';

    var stages=hasPermintaan?[
      {key:'permintaan_dikirim',title:'Permintaan Terkirim',desc:'Danpus/Pimpinan mengirimkan permintaan laporan kepada satuan.',date:permintaanCreated},
      {key:'permintaan_ditinjau',title:'Permintaan Ditinjau',desc:'Satuan telah melihat dan menindaklanjuti permintaan tersebut.',date:permintaanDitinjau||laporanDate},
      {key:'laporan_dibuat',title:'Laporan Dibuat',desc:'Satuan menyusun laporan, termasuk update progres berkala sebelum laporan final dikirim.',date:laporanDate},
      {key:'laporan_dikirim',title:'Laporan Terkirim',desc:'Laporan final dikirim oleh satuan untuk diperiksa Pimpinan/Danpus.',date:(finalRow&&!isPendingRevisi)?laporanDate:''},
      {key:'laporan_selesai',title:'Laporan Selesai',desc:'Laporan telah mendapatkan hasil akhir (disetujui/ditolak).',date:decided?laporanDate:''}
    ]:[
      {key:'laporan_dibuat',title:'Laporan Dibuat',desc:'Satuan menyusun laporan, termasuk update progres berkala sebelum laporan final dikirim.',date:laporanDate},
      {key:'laporan_dikirim',title:'Laporan Terkirim',desc:'Laporan final dikirim oleh satuan untuk diperiksa Pimpinan/Danpus.',date:(finalRow&&!isPendingRevisi)?laporanDate:''},
      {key:'laporan_selesai',title:'Laporan Selesai',desc:'Laporan telah mendapatkan hasil akhir (disetujui/ditolak).',date:decided?laporanDate:''}
    ];

    var finalIndex=stages.length-1;
    var dibuatIndex=finalIndex-2;
    var progress=!finalRow?dibuatIndex:(decided?stages.length:(isPendingRevisi?dibuatIndex:dibuatIndex+1));
    var log=document.createElement('div');log.className='danpus-activity-log';
    stages.forEach(function(stage,index){
      var item=document.createElement('article');item.className='danpus-activity-item';
      var isFinal=index===finalIndex;
      var isRejectedFinal=isFinal&&decided&&finalStatus==='ditolak';
      var isApprovedFinal=isFinal&&decided&&finalStatus==='selesai';
      var isCancelledStage=isDibatalkan&&index>=progress;
      // Terlambat = keadaan TERMINAL (deadline lewat, laporan gak pernah
      // masuk) -- cat merah/silang X-nya diterusin dari tahap berjalan
      // sampai "Laporan Selesai", sama kayak Dibatalkan (index>=progress),
      // bukan cuma nandain 1 tahap terus sisanya keliatan "Menunggu".
      var isLateStage=isTerlambat&&index>=progress;
      var isRevisiStage=index===progress&&isPendingRevisi&&!isCancelledStage&&!isLateStage;
      var isMenungguStage=index===progress&&finalRow&&!decided&&!isPendingRevisi&&!isCancelledStage&&!isLateStage;
      if(index<progress&&!isFinal)item.classList.add('is-done');
      if(index===progress&&!isCancelledStage&&!isLateStage)item.classList.add('is-current');
      if(isRevisiStage)item.classList.add('is-revisi');
      if(isMenungguStage)item.classList.add('is-menunggu');
      if(isRejectedFinal||isCancelledStage||isLateStage)item.classList.add('is-rejected');
      if(isApprovedFinal)item.classList.add('is-approved');
      var dot=document.createElement('div');dot.className='danpus-activity-dot';
      dot.innerHTML=(isRejectedFinal||isCancelledStage||isLateStage)
        ?'<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>'
        :'<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';
      item.appendChild(dot);
      if(index<stages.length-1){var line=document.createElement('div');line.className='danpus-activity-line';item.appendChild(line)}
      var card=document.createElement('div');card.className='danpus-activity-card';
      var head=document.createElement('div');head.className='danpus-activity-head';
      var stageEl=document.createElement('div');stageEl.className='danpus-activity-stage';stageEl.textContent=stage.title;
      head.appendChild(stageEl);
      var dateEl=document.createElement('div');dateEl.className='danpus-activity-date';dateEl.textContent=isCancelledStage?(dibatalkanAt||stage.date||''):(stage.date||'');
      head.appendChild(dateEl);
      card.appendChild(head);
      var desc=document.createElement('div');desc.className='danpus-activity-description';desc.textContent=stage.desc;card.appendChild(desc);
      var state=document.createElement('span');state.className='danpus-activity-state';
      if(isCancelledStage){
        state.textContent='Dibatalkan';
      }else if(isLateStage){
        state.textContent='Terlambat';
      }else if(isFinal){
        if(decided&&finalStatus==='selesai') state.textContent='Disetujui';
        else if(decided&&finalStatus==='ditolak') state.textContent='Ditolak';
        else state.textContent='Menunggu';
      }else if(index===progress&&finalRow&&!decided){
        state.textContent=isPendingRevisi?'Revisi':'Menunggu';
      }else if(index<progress) state.textContent='Selesai';
      else if(index===progress) state.textContent='Sedang diproses';
      else state.textContent='Menunggu';
      card.appendChild(state);item.appendChild(card);log.appendChild(item);
    });
    var title=document.createElement('div');title.className='danpus-activity-project';title.textContent=subject;log.prepend(title);
    log.dataset.permintaanId=permintaanId;
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
    var isDibatalkan=!!p.dibatalkan;
    var dibatalkanAt=p.dibatalkanAt||'';
    var isTerlambat=!isDibatalkan&&!!p.terlambat;
    var log=document.createElement('div');log.className='danpus-activity-log';
    stages.forEach(function(stage,index){
      var item=document.createElement('article');item.className='danpus-activity-item';
      var isCancelledStage=isDibatalkan&&index>=progress;
      // Terlambat = keadaan TERMINAL (deadline lewat, laporan gak pernah
      // masuk) -- cat merah/silang X-nya diterusin dari tahap berjalan
      // sampai "Laporan Selesai", sama kayak Dibatalkan (index>=progress),
      // bukan cuma nandain 1 tahap terus sisanya keliatan "Menunggu".
      var isLateStage=isTerlambat&&index>=progress;
      if(index<progress)item.classList.add('is-done');
      if(index===progress&&!isCancelledStage&&!isLateStage)item.classList.add('is-current');
      if(isCancelledStage||isLateStage)item.classList.add('is-rejected');
      var dot=document.createElement('div');dot.className='danpus-activity-dot';
      dot.innerHTML=(isCancelledStage||isLateStage)
        ?'<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>'
        :'<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';
      item.appendChild(dot);
      if(index<stages.length-1){var line=document.createElement('div');line.className='danpus-activity-line';item.appendChild(line)}
      var card=document.createElement('div');card.className='danpus-activity-card';
      var head=document.createElement('div');head.className='danpus-activity-head';
      var stageEl=document.createElement('div');stageEl.className='danpus-activity-stage';stageEl.textContent=stage.title;
      var dateEl=document.createElement('div');dateEl.className='danpus-activity-date';dateEl.textContent=isCancelledStage?(dibatalkanAt||stage.date||''):(stage.date||'');
      head.appendChild(stageEl);head.appendChild(dateEl);card.appendChild(head);
      var desc=document.createElement('div');desc.className='danpus-activity-description';desc.textContent=stage.desc;card.appendChild(desc);
      var state=document.createElement('span');state.className='danpus-activity-state';
      if(isCancelledStage) state.textContent='Dibatalkan';
      else if(isLateStage) state.textContent='Terlambat';
      else if(index<progress) state.textContent='Selesai';
      else if(index===progress) state.textContent='Sedang diproses';
      else state.textContent='Menunggu';
      card.appendChild(state);item.appendChild(card);log.appendChild(item);
    });
    var title=document.createElement('div');title.className='danpus-activity-project';title.textContent=p.subject||'Permintaan laporan';log.prepend(title);
    log.dataset.permintaanId=p.id?String(p.id):'';
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
      if(!groups.length&&!pending.length){var empty=document.createElement('div');empty.className='empty-state';empty.innerHTML='<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada aktivitas dari satuan ini</div>';wrapper.appendChild(empty)}
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

<style>
/* Popup Riwayat Aktivitas Pimpinan pada sidebar ciutkan:
   tampilannya mengikuti item submenu saat sidebar lebar. Selector dibatasi
   hanya ke grup #monitorGroup agar bagian/sidebar lain tidak ikut berubah. */
@media(min-width:901px){
  .sidebar.collapsed #monitorGroup.open .side-subnav>div{gap:2px!important;}
  .sidebar.collapsed #monitorGroup.open .side-sub-link{
    gap:10px!important;
    padding:9px 12px 9px 17px!important;
    border-radius:0 9px 9px 0!important;
    color:var(--text-muted)!important;
    font-family:var(--body)!important;
    font-size:13px!important;
    font-weight:500!important;
    line-height:1.4!important;
    text-decoration:none!important;
  }
  .sidebar.collapsed #monitorGroup.open .side-sub-link:hover{
    background:var(--hover-tint)!important;
    color:var(--text)!important;
  }
  .sidebar.collapsed #monitorGroup.open .side-sub-link.active{
    background:var(--gold-dim,rgba(201,122,0,.1))!important;
    color:var(--p-accent)!important;
    font-weight:600!important;
  }
  .sidebar.collapsed #monitorGroup.open .side-sub-link.active:before{
    left:-1px;
    top:8px;
    bottom:8px;
    width:2px;
    border-radius:2px;
    background:var(--p-accent);
  }
  .sidebar.collapsed #monitorGroup.open .side-sub-link .sub-dot{
    width:5px;
    height:5px;
    border-radius:50%;
    opacity:.5;
    background:currentColor;
  }
  .sidebar.collapsed #monitorGroup.open .side-sub-link.active .sub-dot{
    background:var(--p-accent)!important;
    opacity:1!important;
    box-shadow:0 0 0 3px rgba(201,122,0,.15)!important;
  }
}
</style>
