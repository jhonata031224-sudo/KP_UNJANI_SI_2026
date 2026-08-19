<style>
/* Fitur arsip hanya menyentuh daftar Permintaan Laporan Danpus. */
#permintaan-laporan .danpus-archive-actions{display:inline-flex;align-items:center;justify-content:flex-end;gap:8px;flex:0 0 auto}
#permintaan-laporan .danpus-archive-toggle{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:36px;padding:8px 13px;border:1px solid var(--p-border);border-radius:8px;background:var(--p-surface);color:var(--p-text);font:inherit;font-size:11px;font-weight:800;cursor:pointer;transition:background .15s ease,border-color .15s ease,color .15s ease,box-shadow .15s ease}
#permintaan-laporan .danpus-archive-toggle:hover{border-color:var(--p-accent);background:var(--p-surface-2)}
#permintaan-laporan .danpus-archive-toggle.is-active{background:var(--gold-solid-bright,#c97a00);border-color:var(--gold-solid-bright,#c97a00);color:var(--on-gold,#fff);box-shadow:0 7px 18px -9px rgba(201,122,0,.75)}
#permintaan-laporan .danpus-archive-toggle.is-busy{opacity:.72;pointer-events:none}
#permintaan-laporan .danpus-archive-count{display:none;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 7px;border-radius:999px;background:var(--gold-dim);border:1px solid var(--border);color:var(--gold-bright);font-family:var(--mono);font-size:10px;font-weight:800}
#permintaan-laporan .danpus-archive-count.is-visible{display:inline-flex}
#permintaan-laporan .danpus-archive-head-cell,#permintaan-laporan .danpus-archive-row-cell,#permintaan-laporan .danpus-archive-row-content{display:contents}
#permintaan-laporan .danpus-archive-checkbox{width:13px;height:13px;flex:0 0 13px;margin:0;padding:0;accent-color:var(--gold-solid-bright,#c97a00);cursor:pointer}
#permintaan-laporan.danpus-archive-mode .request-table thead tr th:first-child,
#permintaan-laporan.danpus-archive-mode .request-table tbody tr td:first-child{position:relative}
#permintaan-laporan.danpus-archive-mode .danpus-archive-select-all,
#permintaan-laporan.danpus-archive-mode .danpus-archive-row-checkbox{position:absolute;left:4px;top:50%;transform:translateY(-50%);z-index:2}
#permintaan-laporan .request-table tbody tr.danpus-archive-selected td{background:color-mix(in srgb,var(--gold-solid-bright,#c97a00) 5%,transparent)}
/* Sengaja tidak ada inset box-shadow/garis kiri pada row terpilih. */

#riwayat-laporan .archive-history-wrap{margin-top:2px;overflow:auto;border:1px solid var(--p-border);border-radius:12px;background:var(--p-surface)}
#riwayat-laporan .archive-history-table{width:100%;border-collapse:collapse;table-layout:fixed}
#riwayat-laporan .archive-history-table th{padding:12px 14px;text-align:left;font-size:10px;letter-spacing:.06em;text-transform:uppercase;color:var(--p-muted);border-bottom:1px solid var(--p-border);white-space:nowrap}
#riwayat-laporan .archive-history-table td{padding:13px 14px;border-bottom:1px solid var(--p-border);font-size:12px;color:var(--p-text);vertical-align:middle}
#riwayat-laporan .archive-history-table tr:last-child td{border-bottom:0}
#riwayat-laporan .archive-history-sub{margin-top:3px;font-size:10.5px;color:var(--p-muted)}
#riwayat-laporan .archive-history-status{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800;border:1px solid transparent;white-space:nowrap}
#riwayat-laporan .archive-history-status.ok{color:var(--success-bright);background:var(--success-dim);border-color:color-mix(in srgb,var(--success-bright) 28%,transparent)}
#riwayat-laporan .archive-history-status.bad{color:var(--p-red);background:color-mix(in srgb,var(--p-red) 10%,transparent);border-color:color-mix(in srgb,var(--p-red) 28%,transparent)}
#riwayat-laporan .archive-history-status.wait{color:var(--p-muted);background:var(--p-surface-2);border-color:var(--p-border)}
#riwayat-laporan .archive-history-empty{padding:36px 18px;text-align:center;color:var(--p-muted);font-size:12px}
@media(max-width:820px){#riwayat-laporan .archive-history-table{min-width:720px}}
</style>
<script>
(function(){
'use strict';

function initDanpusArchiveMode(){
 const panel=document.getElementById('permintaan-laporan');
 if(!panel||panel.dataset.danpusArchiveModeBound==='1')return;
 const head=panel.querySelector('.request-head'),table=panel.querySelector('.request-table'),tbody=table?.querySelector('tbody');
 if(!head||!table||!tbody)return;
 panel.dataset.danpusArchiveModeBound='1';
 let active=false,busy=false,refreshQueued=false;
 const selectedIds=new Set();
 const historyEndpoint='{{ route('permintaan-laporan.realtime') }}?history=1&_=';
 const archiveEndpoint='{{ route('permintaan-laporan.store') }}';
 const eligibleStatus=status=>{const n=String(status||'').replace(/\s+/g,' ').trim().toLowerCase();return n==='terlambat'||n==='dibatalkan'||n==='selesai · disetujui'||n==='selesai · ditolak'};
 const rowStatus=row=>row?.dataset?.status||'';
 const rowId=row=>String(row?.querySelector('[data-permintaan-id]')?.dataset?.permintaanId||'');
 const eligibleRows=()=>Array.from(tbody.querySelectorAll('tr[data-status]')).filter(r=>eligibleStatus(rowStatus(r)));

 const createButton=head.querySelector('#danpusOpenRequestForm');
 if(!createButton)return;
 let actions=head.querySelector('.danpus-archive-actions');
 if(!actions){actions=document.createElement('div');actions.className='danpus-archive-actions';createButton.parentNode?.insertBefore(actions,createButton);actions.appendChild(createButton)}
 const archiveButton=document.createElement('button');
 archiveButton.type='button';archiveButton.className='danpus-archive-toggle';archiveButton.id='danpusArchiveToggle';archiveButton.setAttribute('aria-pressed','false');
 archiveButton.innerHTML='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5h16"></path><path d="M6.5 7.5v10.5A2 2 0 0 0 8.5 20h7a2 2 0 0 0 2-2V7.5"></path><path d="M9 7.5V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5v2"></path><path d="M10 11v5"></path><path d="M14 11v5"></path></svg><span>Arsip</span>';
 actions.insertBefore(archiveButton,createButton);
 const countBadge=document.createElement('span');countBadge.className='danpus-archive-count';countBadge.id='danpusArchiveSelectedCount';countBadge.setAttribute('aria-live','polite');countBadge.textContent='0';actions.insertBefore(countBadge,archiveButton);

 function ensureHistoryView(){
   let section=document.getElementById('riwayat-laporan');
   if(!section){
      const container=document.querySelector('.pimp-page');
      if(!container)return null;
      section=document.createElement('section');section.id='riwayat-laporan';section.className='tab-panel';
      section.innerHTML='<div class="section-block"><div class="section-head-clean"><div><h2>Riwayat Laporan</h2><p>Permintaan laporan yang sudah Anda arsipkan dari daftar aktif.</p></div></div><div class="archive-history-wrap"><table class="archive-history-table"><colgroup><col style="width:15%"><col style="width:27%"><col style="width:13%"><col style="width:18%"><col style="width:27%"></colgroup><thead><tr><th>Ditujukan</th><th>Perihal</th><th>Prioritas</th><th>Deadline</th><th>Status / Diarsipkan</th></tr></thead><tbody id="danpusArchiveHistoryBody"></tbody></table></div></div>';
      container.appendChild(section);
   }
   let link=document.querySelector('.side-sub-link[href="#riwayat-laporan"]');
   if(!link){
      const group=document.getElementById('reportGroup');
      const sub=group?.querySelector('.side-subnav > div');
      if(sub){link=document.createElement('a');link.href='#riwayat-laporan';link.className='side-sub-link';link.innerHTML='<span class="sub-dot"></span>Riwayat Laporan';sub.appendChild(link);}
   }
   if(link&&!link.dataset.archiveHistoryBound){
      link.dataset.archiveHistoryBound='1';
      link.addEventListener('click',function(e){e.preventDefault();
         if(typeof window.showSection==='function'){window.showSection('riwayat-laporan',link);return;}
         document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));section.classList.add('active');
         document.querySelectorAll('.side-sub-link,.side-link').forEach(a=>a.classList.remove('active'));link.classList.add('active');
         window.scrollTo({top:0,behavior:'smooth'});
      });
   }
   return section;
 }

 function escapeHtml(value){return String(value??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));}
 function renderHistory(items){
    const section=ensureHistoryView();const body=section?.querySelector('#danpusArchiveHistoryBody');if(!body)return;
    if(!items.length){body.innerHTML='<tr><td colspan="5"><div class="archive-history-empty">Belum ada laporan yang diarsipkan.</div></td></tr>';return;}
    body.innerHTML=items.map(item=>{
      const status=String(item.status||'');
      const kind=status.includes('Disetujui')?'ok':(status.includes('Ditolak')||status==='Dibatalkan'||status==='Terlambat'?'bad':'wait');
      return '<tr data-archive-history-id="'+escapeHtml(item.id)+'"><td><span class="satuan-pill">'+escapeHtml(item.tujuan||'-')+'</span></td><td><strong>'+escapeHtml(item.perihal||'-')+'</strong><div class="archive-history-sub">'+escapeHtml(item.kategori||'Laporan kegiatan')+'</div></td><td>'+escapeHtml(item.prioritas||'-')+'</td><td>'+escapeHtml(item.deadline||'-')+'</td><td><span class="archive-history-status '+kind+'">'+escapeHtml(status)+'</span><div class="archive-history-sub">Diarsipkan '+escapeHtml(item.archived_at||'-')+'</div></td></tr>';
    }).join('');
 }
 function removeArchivedFromActive(ids){
    const idSet=new Set((ids||[]).map(String));
    Array.from(tbody.querySelectorAll('tr[data-status]')).forEach(row=>{if(idSet.has(rowId(row)))row.remove();});
    selectedIds.forEach(id=>{if(idSet.has(String(id)))selectedIds.delete(id)});
    syncRows();
 }
 function fetchHistory(openAfter=false){
   return fetch(historyEndpoint+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
      .then(r=>r.ok?r.json():null)
      .then(data=>{if(!data)return;const items=Array.isArray(data.items)?data.items:[];renderHistory(items);removeArchivedFromActive(items.map(i=>i.id));if(openAfter&&items.length){const link=document.querySelector('.side-sub-link[href="#riwayat-laporan"]');if(link&&typeof window.showSection==='function')window.showSection('riwayat-laporan',link);}})
      .catch(()=>{});
 }
 function ensureHeaderCheckbox(){
   const th=table.querySelector('thead tr th:first-child');if(!th)return null;
   let wrap=th.querySelector('.danpus-archive-head-cell');
   if(!wrap){const label=document.createElement('span');label.className='danpus-archive-head-label';while(th.firstChild)label.appendChild(th.firstChild);wrap=document.createElement('span');wrap.className='danpus-archive-head-cell';wrap.appendChild(label);th.appendChild(wrap)}
   let cb=wrap.querySelector('.danpus-archive-select-all');
   if(!cb){cb=document.createElement('input');cb.type='checkbox';cb.className='danpus-archive-checkbox danpus-archive-select-all';cb.setAttribute('aria-label','Pilih semua baris yang dapat diarsipkan');wrap.insertBefore(cb,wrap.firstChild);cb.addEventListener('change',()=>{eligibleRows().forEach(row=>{const id=rowId(row);if(!id)return;cb.checked?selectedIds.add(id):selectedIds.delete(id)});syncRows();syncHeaderCheckbox();});}
   return cb;
 }
 function ensureRowCheckbox(row){
   const td=row.querySelector('td:first-child');if(!td)return null;
   let wrap=td.querySelector('.danpus-archive-row-cell');
   if(!wrap){const content=document.createElement('span');content.className='danpus-archive-row-content';while(td.firstChild)content.appendChild(td.firstChild);wrap=document.createElement('span');wrap.className='danpus-archive-row-cell';wrap.appendChild(content);td.appendChild(wrap);}
   let cb=wrap.querySelector('.danpus-archive-row-checkbox');
   if(!cb){cb=document.createElement('input');cb.type='checkbox';cb.className='danpus-archive-checkbox danpus-archive-row-checkbox';cb.setAttribute('aria-label','Pilih permintaan laporan ini untuk diarsipkan');const id=rowId(row);cb.dataset.permintaanId=id;wrap.insertBefore(cb,wrap.firstChild);cb.addEventListener('click',e=>e.stopPropagation());cb.addEventListener('change',()=>{if(id)(cb.checked?selectedIds.add(id):selectedIds.delete(id));row.classList.toggle('danpus-archive-selected',cb.checked);syncHeaderCheckbox();syncCount();});}
   return cb;
 }
 function removeRowArchiveUI(row){row.classList.remove('danpus-archive-eligible-row','danpus-archive-selected');const wrap=row.querySelector('.danpus-archive-row-cell');if(!wrap)return;const content=wrap.querySelector('.danpus-archive-row-content'),td=row.querySelector('td:first-child');if(content&&td)while(content.firstChild)td.appendChild(content.firstChild);wrap.remove();}
 function removeHeaderArchiveUI(){const wrap=table.querySelector('thead tr th:first-child .danpus-archive-head-cell');if(!wrap)return;const label=wrap.querySelector('.danpus-archive-head-label'),th=wrap.closest('th');if(label&&th)while(label.firstChild)th.appendChild(label.firstChild);wrap.remove();}
 function syncRows(){Array.from(tbody.querySelectorAll('tr[data-status]')).forEach(row=>{if(!active||!eligibleStatus(rowStatus(row))){removeRowArchiveUI(row);return;}row.classList.add('danpus-archive-eligible-row');const cb=ensureRowCheckbox(row),id=rowId(row);if(cb){cb.checked=!!(id&&selectedIds.has(id));row.classList.toggle('danpus-archive-selected',cb.checked);}});if(active)ensureHeaderCheckbox();else removeHeaderArchiveUI();syncHeaderCheckbox();syncCount();}
 function syncHeaderCheckbox(){const cb=table.querySelector('thead .danpus-archive-select-all');if(!cb)return;const boxes=eligibleRows().map(r=>r.querySelector('.danpus-archive-row-checkbox')).filter(Boolean);const all=boxes.length>0&&boxes.every(b=>b.checked);cb.checked=all;cb.indeterminate=!all&&boxes.some(b=>b.checked);cb.disabled=boxes.length===0;}
 function syncCount(){for(const id of Array.from(selectedIds))if(!eligibleRows().some(r=>rowId(r)===id))selectedIds.delete(id);countBadge.textContent=String(selectedIds.size);countBadge.classList.toggle('is-visible',active&&selectedIds.size>0);}
 function setMode(next){active=!!next;panel.classList.toggle('danpus-archive-mode',active);archiveButton.classList.toggle('is-active',active);archiveButton.setAttribute('aria-pressed',active?'true':'false');if(!active)selectedIds.clear();syncRows();}
 function archiveSelected(){
   if(busy||selectedIds.size===0)return;
   busy=true;archiveButton.classList.add('is-busy');
   const form=new FormData();form.append('archive_mode','1');
   const token=document.querySelector('input[name="_token"]')?.value||document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
   selectedIds.forEach(id=>form.append('permintaan_laporan_ids[]',id));
   fetch(archiveEndpoint,{method:'POST',credentials:'same-origin',body:form,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest',...(token?{'X-CSRF-TOKEN':token}:{})}})
      .then(async r=>{const data=await r.json().catch(()=>null);if(!r.ok){throw new Error(data?.message||'Permintaan gagal diarsipkan.');}return data;})
      .then(data=>{
        const items=Array.isArray(data?.items)?data.items:[];
        const ids=Array.isArray(data?.archived_ids)?data.archived_ids:items.map(i=>i.id);
        removeArchivedFromActive(ids);renderHistory(items.length?items:[]);setMode(false);selectedIds.clear();
        const link=document.querySelector('.side-sub-link[href="#riwayat-laporan"]');
        if(link&&typeof window.showSection==='function')window.showSection('riwayat-laporan',link);
        window.siberadShowToast?.('success',data?.message||'Permintaan berhasil diarsipkan.');
      })
      .catch(err=>window.siberadShowToast?.('error',err.message||'Permintaan gagal diarsipkan.'))
      .finally(()=>{busy=false;archiveButton.classList.remove('is-busy');syncRows();});
 }
 archiveButton.addEventListener('click',()=>{if(active&&selectedIds.size>0){archiveSelected();return;}setMode(!active);});
 const scheduleRefresh=()=>{if(refreshQueued)return;refreshQueued=true;requestAnimationFrame(()=>{refreshQueued=false;syncRows();});};
 new MutationObserver(scheduleRefresh).observe(tbody,{childList:true,subtree:true});
 ensureHistoryView();
 fetchHistory(false);
 window.setInterval(()=>{if(!document.hidden&&!busy)fetchHistory(false);},5000);
 syncRows();
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initDanpusArchiveMode,{once:true});else initDanpusArchiveMode();
})();
</script>