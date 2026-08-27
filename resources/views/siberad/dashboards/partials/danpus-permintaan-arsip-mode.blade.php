<style>
/* Tombol Arsip sekarang pakai kelas .btn/.btn-primary global (sama kayak
   tombol "Buat Permintaan"), style di sini cuma nambahin yang belum ada di situ. */
#permintaan-laporan .danpus-archive-toggle.is-busy{opacity:.72;pointer-events:none}
#permintaan-laporan .danpus-archive-actions{display:inline-flex;align-items:center;justify-content:flex-end;gap:8px;flex:0 0 auto;flex-wrap:wrap}
@media(max-width:700px){
  #permintaan-laporan .danpus-archive-actions{display:flex;flex-wrap:wrap;width:100%;justify-content:flex-start}
  #permintaan-laporan #danpusOpenRequestForm{margin-top:0}
}
#permintaan-laporan .danpus-archive-icon{width:18px;height:18px;flex:0 0 18px;display:block;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
#permintaan-laporan .danpus-archive-count{display:none;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 7px;border-radius:999px;background:var(--gold-dim);border:1px solid var(--border);color:var(--gold-bright);font-family:var(--mono);font-size:10px;font-weight:800}
#permintaan-laporan .danpus-archive-count.is-visible{display:inline-flex}
#permintaan-laporan .danpus-archive-head-cell,#permintaan-laporan .danpus-archive-row-cell,#permintaan-laporan .danpus-archive-row-content,#permintaan-laporan .danpus-archive-head-label{display:contents}
/* Ruang buat checkbox disediakan PERMANEN (bukan cuma pas mode Arsip aktif),
   supaya posisi badge satuan tidak geser saat mode Arsip dinyalakan/dimatikan --
   checkbox-nya sendiri baru muncul saat mode aktif, tapi ruangnya sudah ada dari awal. */
#permintaan-laporan .request-table thead tr th:first-child,#permintaan-laporan .request-table tbody tr td:first-child{padding-left:31px;position:relative}
#permintaan-laporan .danpus-archive-checkbox{width:13px;height:13px;margin:0;padding:0;accent-color:var(--gold-solid-bright,#c97a00);cursor:pointer;position:absolute;left:12px;top:50%;transform:translateY(-50%)}
#permintaan-laporan .request-table tbody tr td:first-child .satuan-pill{max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;vertical-align:middle}
#permintaan-laporan .request-table tbody tr.danpus-archive-selected td{background:color-mix(in srgb,var(--gold-solid-bright,#c97a00) 5%,transparent)}
#permintaan-laporan .request-table tbody tr.danpus-archive-selected td:first-child{box-shadow:none}
/* Arsip pada Riwayat/Status memakai layout tabel yang SAMA persis dengan baris laporan biasa. */
#status .archive-request-row td{vertical-align:middle}
#status .archive-request-row .archive-request-unit,#status .archive-request-row .archive-request-target{text-align:center}
#status .archive-request-row .archive-request-sub{margin-top:3px;font-size:10px;color:var(--p-muted)}
#status .archive-request-row .archive-request-date{justify-content:center}
#status .archive-request-row .archive-request-status{white-space:nowrap}
@media(max-width:700px){#status .archive-request-row .archive-request-status{white-space:normal}}
</style>
<script>
(function(){
'use strict';
function initDanpusArchiveMode(){
 const panel=document.getElementById('permintaan-laporan');if(!panel||panel.dataset.danpusArchiveModeBound==='1')return;
 const head=panel.querySelector('.request-head'),table=panel.querySelector('.request-table'),tbody=table?.querySelector('tbody');if(!head||!table||!tbody)return;
 panel.dataset.danpusArchiveModeBound='1';let active=false,busy=false,refreshQueued=false;const selectedIds=new Set();
 const archiveEndpoint='{{ route('permintaan-laporan.store') }}';const historyEndpoint='{{ route('permintaan-laporan.realtime') }}?history=1&_=';
 const eligibleStatus=status=>{const n=String(status||'').replace(/\s+/g,' ').trim().toLowerCase();return n==='terlambat'||n==='dibatalkan'||n==='selesai · disetujui'||n==='selesai · ditolak'};
 const rowStatus=row=>row?.dataset?.status||'';const rowId=row=>String(row?.querySelector('[data-permintaan-id]')?.dataset?.permintaanId||'');const eligibleRows=()=>Array.from(tbody.querySelectorAll('tr[data-status]')).filter(r=>eligibleStatus(rowStatus(r)));
 const createButton=head.querySelector('#danpusOpenRequestForm');if(!createButton)return;let actions=head.querySelector('.danpus-archive-actions');
 if(!actions){actions=document.createElement('div');actions.className='danpus-archive-actions';createButton.parentNode?.insertBefore(actions,createButton);actions.appendChild(createButton)}
 const archiveButton=document.createElement('button');archiveButton.type='button';archiveButton.className='btn danpus-archive-toggle';archiveButton.id='danpusArchiveToggle';archiveButton.setAttribute('aria-pressed','false');archiveButton.title='Mode Arsip / Arsipkan permintaan terpilih';
 archiveButton.innerHTML='<svg class="danpus-archive-icon" viewBox="0 0 32 32" aria-hidden="true"><path d="M4.5 14.5V27h23V14.5"></path><path d="M3.5 13h25"></path><path d="M6 13V8.5h20V13"></path><path d="M8 8.5V5.5h17l2 3"></path><path d="M10 13l7 5.5h9V13"></path><path d="M17 18.5h9v7.5a4 4 0 0 1-4 4H10a5.5 5.5 0 0 1-5.5-5.5V14.5"></path><path d="M16 19h7"></path><path d="M18 23h5"></path></svg><span>Arsip</span>';actions.insertBefore(archiveButton,createButton);
 const countBadge=document.createElement('span');countBadge.className='danpus-archive-count';countBadge.id='danpusArchiveSelectedCount';countBadge.setAttribute('aria-live','polite');countBadge.textContent='0';actions.insertBefore(countBadge,archiveButton);
 function csrf(){return document.querySelector('input[name="_token"]')?.value||document.querySelector('meta[name="csrf-token"]')?.content||''}
 function historyBodies(){return Array.from(document.querySelectorAll('#riwayat .dtbl tbody,#riwayat .clean-table tbody,#status .clean-table tbody,#status .dtbl tbody')).filter(Boolean)}
 function esc(v){return String(v??'').replace(/[&<>\"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[c]))}
 function normalizeStatus(status){return String(status||'').replace(/\s+/g,' ').trim()}
 function archiveStatusClass(status){const n=normalizeStatus(status).toLowerCase();if(n.includes('setuj')||n.includes('diterima'))return'ok';if(n.includes('tolak')||n.includes('batal')||n.includes('terlambat'))return'bad';return'wait'}
 function archiveStatusLabel(status){const n=normalizeStatus(status);if(!n)return'Arsip';if(n==='Dibatalkan')return'Dibatalkan';if(n==='Terlambat')return'Terlambat';if(n==='Selesai · Disetujui')return'Selesai · Disetujui';if(n==='Selesai · Ditolak')return'Selesai · Ditolak';return n}
 function isHistoryStatusTable(table){const headers=Array.from(table?.querySelectorAll('thead th')||[]).map(h=>h.textContent.trim().toLowerCase());return headers.length===6&&(headers[0]==='unit'||headers[0]==='satlak')&&headers[1]==='perihal'&&headers[2]==='tujuan'&&headers[3]==='status'&&headers[4]==='tanggal'&&headers[5]==='aksi'}
 // Checklist task ikut dibawa ke Riwayat Laporan pas diarsipkan, dirender
 // pakai class .request-task-* yang SAMA dengan tab Permintaan Laporan
 // (sudah ke-load dari laporan-pimpinan.blade.php di shell yang sama) --
 // biar datanya (dan tampilannya) konsisten antara kedua tab.
 function buildArchiveTaskTrack(tasks,ctx){
   if(!tasks||!tasks.length)return '<div class="request-muted">Tidak ada task untuk permintaan ini.</div>';
   let activeAssigned=false;
   const steps=tasks.map(function(t,i){
     var state;
     if(t.selesai){state='done'}else if(!activeAssigned){state='active';activeAssigned=true}else{state='pending'}
     var title=esc(t.deskripsi)+(t.selesai_at?' · Selesai '+esc(t.selesai_at):'');
     var num=t.selesai?'✓':(i+1);
     var lap=t.laporan;
     var cls='request-task-step '+state;
     var attrs='';
     // Task yang udah ada laporannya bisa diklik buat buka modal "Detail
     // Aktivitas Laporan" yang sama persis kayak di tab Permintaan Laporan
     // (lihat onclick="openReportDetail(this)" di request-task-step versi
     // Blade-nya) -- data-readonly="1" soalnya arsip cuma buat dilihat.
     if(lap){
       cls+=' clickable';
       attrs=' role="button" tabindex="0" onclick="openReportDetail(this)" onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();openReportDetail(this)}"'+
         ' data-pengirim="'+esc(ctx.pengirim)+'" data-tujuan="'+esc(ctx.tujuan)+'" data-perihal="'+esc(lap.perihal)+'" data-prioritas="'+esc(lap.prioritas)+'"'+
         ' data-progres="'+esc(lap.progres)+'" data-kendala="'+esc(lap.kendala||'')+'" data-proyek="'+esc(lap.proyek||'-')+'" data-tanggal="'+esc(lap.tanggal)+'"'+
         ' data-deskripsi="'+esc(lap.deskripsi)+'" data-lampiran="'+esc(lap.lampiran||'')+'" data-readonly="1"';
     }
     return '<div class="'+cls+'" title="'+title+'"'+attrs+'><span class="request-task-num">'+num+'</span><span class="request-task-label">'+esc(t.deskripsi)+'</span></div>';
   }).join('');
   return '<div class="request-task-track">'+steps+'</div>';
 }
 function renderArchivedItem(tb,item,pimpinanNama){
   const key='archive-'+item.id;if(tb.querySelector('[data-archive-key="'+key+'"]'))return;
   const table=tb.closest('table');
   if(isHistoryStatusTable(table)){
     const status=archiveStatusLabel(item.status);
     const statusClass=archiveStatusClass(status);
     const unit=item.tujuan||item.tujuan_nama||'-';
     const unitNama=item.tujuan_nama||unit;
     const subject=item.perihal||'-';
     const target=item.pengirim||item.pembuat_nama||'DANPUS';
     const date=item.archived_at||item.created_at||'-';
     const tr=document.createElement('tr');
     tr.className='archive-request-row';
     tr.style.cursor='pointer';
     tr.setAttribute('onclick','danpusToggleTaskRow(this,event)');
     tr.dataset.archiveKey=key;
     tr.dataset.search=(String(unit)+' '+String(subject)+' '+String(target)).toLowerCase();
     tr.dataset.outcome=status.toLowerCase().includes('setuj')||status.toLowerCase().includes('diterima')?'disetujui':status.toLowerCase().includes('tolak')?'ditolak':'';
     tr.innerHTML='<td class="archive-request-unit" style="text-align:center"><span class="request-row-caret" aria-hidden="true">▸</span><span class="satuan-pill">'+esc(unit)+'</span></td>'+
       '<td class="subject"><div>'+esc(subject)+'</div><div class="archive-request-sub">Arsip permintaan laporan</div></td>'+
       '<td class="archive-request-target" style="text-align:center"><span class="satuan-pill">'+esc(target)+'</span></td>'+
       '<td style="text-align:center"><span class="status-pill archive-request-status '+statusClass+'">'+esc(status)+'</span></td>'+
       '<td style="text-align:center"><div class="request-deadline archive-request-date"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>'+esc(date)+'</div></td>'+
       // "Lihat" di sini sama persis kayak tombol "Lihat" tab Permintaan
       // Laporan (window.danpusLihatAktivitas, didefinisikan di
       // laporan-pimpinan.blade.php) -- bawa ke Log Aktivitas satuan tujuan
       // & sorot baris laporannya, bukan modal arsip generik kayak sebelumnya.
       '<td style="text-align:center"><button type="button" class="detail-btn" onclick="window.danpusLihatAktivitas&&window.danpusLihatAktivitas(this)" data-satuan-id="'+esc(item.tujuan_satuan_id||'')+'" data-permintaan-id="'+esc(item.id)+'">Lihat</button></td>';
     tb.prepend(tr);
     const taskTr=document.createElement('tr');
     taskTr.className='request-task-row rpt-filter-detail-row';
     taskTr.hidden=true;
     taskTr.dataset.archiveKey=key+'-tasks';
     const taskTd=document.createElement('td');
     taskTd.colSpan=6;
     taskTd.innerHTML=buildArchiveTaskTrack(item.tasks,{pengirim:unitNama,tujuan:pimpinanNama||'DANPUS'});
     taskTr.appendChild(taskTd);
     tr.after(taskTr);
     return;
   }
   /* Tabel lain dipertahankan apa adanya; arsip hanya ditambahkan jika tabel memang cocok dengan struktur history. */
 }
 // items dari backend udah terurut TERBARU dulu (->latest('archived_at')),
 // tapi renderArchivedItem prepend tiap baris ke ATAS satu-satu -- kalau
 // di-iterate apa adanya (terbaru dulu), baris terbaru itu keprepend duluan
 // lalu ketimpa ke bawah sama baris-baris berikutnya yang di-prepend
 // belakangan, hasil akhirnya malah kebalik (terlama di atas, terbaru di
 // bawah). Di-reverse dulu biar prepend TERAKHIR (yang beneran nempel
 // paling atas) jatuh ke item TERBARU.
 // Bersihin baris "kosong" (empty-state) SEKALI per tabel per siklus sync --
 // sebelumnya ini dilakuin di dalam renderArchivedItem() (jadi nge-scan ULANG
 // SEMUA baris tabel, tiap ITEM, di tiap tabel) yang berat kalau arsipnya
 // sudah menumpuk banyak. Cukup jalan sekali sebelum item-nya dirender.
 function syncHistory(items,pimpinanNama){historyBodies().forEach(tb=>{tb.querySelectorAll('tr').forEach(r=>{if(r.querySelector('.empty-state'))r.remove()});items.slice().reverse().forEach(item=>renderArchivedItem(tb,item,pimpinanNama))})}
 async function loadHistory(){try{const r=await fetch(historyEndpoint+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});if(!r.ok)return;const data=await r.json();const items=Array.isArray(data.items)?data.items:[];syncHistory(items,data.pimpinan_satuan_nama);removeArchivedRows(items.map(i=>i.id));}catch(e){}}
 function removeArchivedRows(ids){const set=new Set((ids||[]).map(String));Array.from(tbody.querySelectorAll('tr[data-status]')).forEach(row=>{if(set.has(rowId(row)))row.remove()});selectedIds.forEach(id=>{if(set.has(String(id)))selectedIds.delete(id)});syncRows()}
 function ensureHeaderCheckbox(){const th=table.querySelector('thead tr th:first-child');if(!th)return null;let wrap=th.querySelector('.danpus-archive-head-cell');if(!wrap){const label=document.createElement('span');label.className='danpus-archive-head-label';while(th.firstChild)label.appendChild(th.firstChild);wrap=document.createElement('span');wrap.className='danpus-archive-head-cell';wrap.appendChild(label);th.appendChild(wrap)}let cb=wrap.querySelector('.danpus-archive-select-all');if(!cb){cb=document.createElement('input');cb.type='checkbox';cb.className='danpus-archive-checkbox danpus-archive-select-all';cb.setAttribute('aria-label','Pilih semua baris yang dapat diarsipkan');wrap.insertBefore(cb,wrap.firstChild);cb.addEventListener('change',()=>{eligibleRows().forEach(row=>{const id=rowId(row);if(!id)return;cb.checked?selectedIds.add(id):selectedIds.delete(id)});syncRows();syncHeaderCheckbox()})}return cb}
 function ensureRowCheckbox(row){const td=row.querySelector('td:first-child');if(!td)return null;let wrap=td.querySelector('.danpus-archive-row-cell');if(!wrap){const content=document.createElement('span');content.className='danpus-archive-row-content';while(td.firstChild)content.appendChild(td.firstChild);wrap=document.createElement('span');wrap.className='danpus-archive-row-cell';wrap.appendChild(content);td.appendChild(wrap)}let cb=wrap.querySelector('.danpus-archive-row-checkbox');if(!cb){cb=document.createElement('input');cb.type='checkbox';cb.className='danpus-archive-checkbox danpus-archive-row-checkbox';cb.setAttribute('aria-label','Pilih permintaan laporan ini untuk diarsipkan');const id=rowId(row);cb.dataset.permintaanId=id;wrap.insertBefore(cb,wrap.firstChild);cb.addEventListener('click',e=>e.stopPropagation());cb.addEventListener('change',()=>{if(id)(cb.checked?selectedIds.add(id):selectedIds.delete(id));row.classList.toggle('danpus-archive-selected',cb.checked);syncHeaderCheckbox();syncCount()})}return cb}
 function removeRowArchiveUI(row){row.classList.remove('danpus-archive-eligible-row','danpus-archive-selected');const wrap=row.querySelector('.danpus-archive-row-cell');if(!wrap)return;const content=wrap.querySelector('.danpus-archive-row-content'),td=row.querySelector('td:first-child');if(content&&td)while(content.firstChild)td.appendChild(content.firstChild);wrap.remove()}
 function removeHeaderArchiveUI(){const wrap=table.querySelector('thead tr th:first-child .danpus-archive-head-cell');if(!wrap)return;const label=wrap.querySelector('.danpus-archive-head-label'),th=wrap.closest('th');if(label&&th)while(label.firstChild)th.appendChild(label.firstChild);wrap.remove()}
 function syncRows(){Array.from(tbody.querySelectorAll('tr[data-status]')).forEach(row=>{if(!active||!eligibleStatus(rowStatus(row))){removeRowArchiveUI(row);return}row.classList.add('danpus-archive-eligible-row');const cb=ensureRowCheckbox(row),id=rowId(row);if(cb){cb.checked=!!(id&&selectedIds.has(id));row.classList.toggle('danpus-archive-selected',cb.checked)}});if(active)ensureHeaderCheckbox();else removeHeaderArchiveUI();syncHeaderCheckbox();syncCount()}
 function syncHeaderCheckbox(){const cb=table.querySelector('thead .danpus-archive-select-all');if(!cb)return;const boxes=eligibleRows().map(r=>r.querySelector('.danpus-archive-row-checkbox')).filter(Boolean);const all=boxes.length>0&&boxes.every(b=>b.checked);cb.checked=all;cb.indeterminate=!all&&boxes.some(b=>b.checked);cb.disabled=boxes.length===0}
 function syncCount(){for(const id of Array.from(selectedIds))if(!eligibleRows().some(r=>rowId(r)===id))selectedIds.delete(id);countBadge.textContent=String(selectedIds.size);countBadge.classList.toggle('is-visible',active&&selectedIds.size>0)}
 function setMode(next){active=!!next;panel.classList.toggle('danpus-archive-mode',active);archiveButton.classList.toggle('is-active',active);archiveButton.classList.toggle('btn-primary',active);archiveButton.setAttribute('aria-pressed',active?'true':'false');if(!active)selectedIds.clear();syncRows()}
 async function archiveSelected(){if(busy||selectedIds.size===0)return;busy=true;archiveButton.classList.add('is-busy');try{const form=new FormData();form.append('archive_mode','1');selectedIds.forEach(id=>form.append('permintaan_laporan_ids[]',id));const token=csrf();const r=await fetch(archiveEndpoint,{method:'POST',credentials:'same-origin',body:form,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest',...(token?{'X-CSRF-TOKEN':token}:{})}});const data=await r.json().catch(()=>({}));if(!r.ok)throw new Error(data.message||'Permintaan gagal diarsipkan.');const items=Array.isArray(data.items)?data.items:[];syncHistory(items,data.pimpinan_satuan_nama);removeArchivedRows(data.archived_ids||items.map(i=>i.id));selectedIds.clear();setMode(false);window.siberadShowToast?.('success',data.message||'Permintaan berhasil dipindahkan ke Riwayat Laporan.')}catch(error){window.siberadShowToast?.('error',error.message||'Permintaan gagal diarsipkan.')}finally{busy=false;archiveButton.classList.remove('is-busy');syncRows()}}
 function ensureArchiveConfirm(){let overlay=document.getElementById('danpusArchiveConfirmOverlay');if(overlay)return overlay;overlay=document.createElement('div');overlay.className='confirm-overlay';overlay.id='danpusArchiveConfirmOverlay';overlay.innerHTML='<div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="danpusArchiveConfirmTitle"><div class="confirm-icon"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M4.5 14.5V27h23V14.5"></path><path d="M3.5 13h25"></path><path d="M6 13V8.5h20V13"></path><path d="M8 8.5V5.5h17l2 3"></path><path d="M10 13l7 5.5h9V13"></path><path d="M17 18.5h9v7.5a4 4 0 0 1-4 4H10a5.5 5.5 0 0 1-5.5-5.5V14.5"></path><path d="M16 19h7"></path><path d="M18 23h5"></path></svg></div><h3 id="danpusArchiveConfirmTitle">Arsipkan Permintaan Terpilih?</h3><p id="danpusArchiveConfirmText"></p><div class="confirm-actions"><button type="button" class="btn" id="danpusArchiveConfirmBatal">Batal</button><button type="button" class="btn btn-primary" id="danpusArchiveConfirmYa">Ya, Arsipkan</button></div></div>';document.body.appendChild(overlay);overlay.addEventListener('click',e=>{if(e.target===overlay)closeArchiveConfirm()});overlay.querySelector('#danpusArchiveConfirmBatal').addEventListener('click',closeArchiveConfirm);overlay.querySelector('#danpusArchiveConfirmYa').addEventListener('click',()=>{closeArchiveConfirm();archiveSelected()});return overlay}
 function openArchiveConfirm(){const overlay=ensureArchiveConfirm();const n=selectedIds.size;overlay.querySelector('#danpusArchiveConfirmText').textContent=n+' permintaan laporan yang dipilih akan dipindahkan ke Riwayat Laporan.';overlay.classList.add('open')}
 function closeArchiveConfirm(){document.getElementById('danpusArchiveConfirmOverlay')?.classList.remove('open')}
 document.addEventListener('keydown',e=>{if(e.key==='Escape')closeArchiveConfirm()});
 archiveButton.addEventListener('click',()=>{if(active&&selectedIds.size>0){openArchiveConfirm();return}setMode(!active)});
 const scheduleRefresh=()=>{if(refreshQueued)return;refreshQueued=true;requestAnimationFrame(()=>{refreshQueued=false;syncRows()})};new MutationObserver(scheduleRefresh).observe(tbody,{childList:true,subtree:true});syncRows();loadHistory();window.setInterval(()=>{if(!document.hidden&&!busy)loadHistory()},5000)
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initDanpusArchiveMode,{once:true});else initDanpusArchiveMode();
})();
</script>