<style>
/* Arsip mode hanya menyentuh tabel Permintaan Laporan Danpus. */
#permintaan-laporan .danpus-archive-actions{
  display:inline-flex;
  align-items:center;
  justify-content:flex-end;
  gap:8px;
  flex:0 0 auto;
}
#permintaan-laporan .danpus-archive-toggle{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:7px;
  min-height:36px;
  padding:8px 13px;
  border:1px solid var(--p-border);
  border-radius:8px;
  background:var(--p-surface);
  color:var(--p-text);
  font:inherit;
  font-size:11px;
  font-weight:800;
  cursor:pointer;
  transition:background .15s ease,border-color .15s ease,color .15s ease,transform .15s ease,box-shadow .15s ease;
}
#permintaan-laporan .danpus-archive-toggle:hover{
  border-color:var(--p-accent);
  background:var(--p-surface-2);
  color:var(--p-accent);
  transform:translateY(-1px);
}
#permintaan-laporan .danpus-archive-toggle.is-active{
  background:var(--p-accent);
  border-color:var(--p-accent);
  color:#fff;
  box-shadow:0 6px 16px -8px color-mix(in srgb,var(--p-accent) 75%,transparent);
}
#permintaan-laporan .danpus-archive-toggle svg{
  width:15px;
  height:15px;
  stroke:currentColor;
  fill:none;
  stroke-width:1.9;
  flex-shrink:0;
}
#permintaan-laporan .danpus-archive-count{
  display:none;
  align-items:center;
  justify-content:center;
  min-width:22px;
  height:22px;
  padding:0 7px;
  border-radius:999px;
  background:var(--gold-dim);
  border:1px solid var(--border);
  color:var(--gold-bright);
  font-family:var(--mono);
  font-size:10px;
  font-weight:800;
}
#permintaan-laporan .danpus-archive-count.is-visible{display:inline-flex}
#permintaan-laporan .danpus-archive-head-cell,
#permintaan-laporan .danpus-archive-row-cell{
  display:flex;
  align-items:center;
}
#permintaan-laporan .danpus-archive-head-cell{
  justify-content:flex-start;
  gap:8px;
  transform:none;
}
#permintaan-laporan .danpus-archive-row-cell{
  justify-content:flex-start;
  gap:9px;
  width:100%;
  min-width:0;
  transform:none;
}
#permintaan-laporan .danpus-archive-row-content{
  min-width:0;
  display:inline-flex;
  align-items:center;
}
#permintaan-laporan .danpus-archive-checkbox{
  width:16px;
  height:16px;
  flex:0 0 16px;
  margin:0;
  padding:0;
  accent-color:var(--p-accent);
  cursor:pointer;
}
#permintaan-laporan .danpus-archive-checkbox:focus-visible{
  outline:2px solid color-mix(in srgb,var(--p-accent) 55%,transparent);
  outline-offset:2px;
  border-radius:4px;
}
#permintaan-laporan .danpus-archive-checkbox[disabled]{
  cursor:not-allowed;
  opacity:.45;
}
/* Saat mode arsip aktif, semua isi kolom Ditujukan diberi titik awal yang sama.
   Baris yang punya checkbox memakai ruang checkbox + gap sebagai penggeser alami;
   baris tanpa checkbox diberi padding kiri setara agar badge/nama tetap satu garis. */
#permintaan-laporan.danpus-archive-mode .request-table tbody tr:not(.danpus-archive-eligible-row) td:first-child{
  padding-left:37px;
}
#permintaan-laporan.danpus-archive-mode .request-table tbody tr.danpus-archive-eligible-row td:first-child{
  padding-left:12px;
}
#permintaan-laporan .request-table tbody tr.danpus-archive-selected td{
  background:color-mix(in srgb,var(--p-accent) 5%,transparent);
}
#permintaan-laporan .request-table tbody tr.danpus-archive-selected td:first-child{
  box-shadow:inset 3px 0 0 var(--p-accent);
}
#permintaan-laporan.danpus-archive-mode .request-table tbody tr.danpus-archive-eligible-row{
  cursor:default;
}
@media(max-width:700px){
  #permintaan-laporan .danpus-archive-actions{justify-content:flex-start}
}
</style>
<script>
(function(){
  'use strict';

  function initDanpusArchiveMode(){
    const panel=document.getElementById('permintaan-laporan');
    if(!panel || panel.dataset.danpusArchiveModeBound==='1')return;
    const head=panel.querySelector('.request-head');
    const table=panel.querySelector('.request-table');
    const tbody=table?.querySelector('tbody');
    if(!head||!table||!tbody)return;

    panel.dataset.danpusArchiveModeBound='1';

    let active=false;
    let refreshQueued=false;
    const selectedIds=new Set();

    const eligibleStatus=status=>{
      const normalized=String(status||'').replace(/\s+/g,' ').trim().toLowerCase();
      return normalized==='terlambat'
        || normalized==='dibatalkan'
        || normalized==='selesai · disetujui'
        || normalized==='selesai · ditolak';
    };

    const rowStatus=row=>row?.dataset?.status||'';
    const rowId=row=>String(row?.querySelector('[data-permintaan-id]')?.dataset?.permintaanId||'');
    const eligibleRows=()=>Array.from(tbody.querySelectorAll('tr[data-status]')).filter(row=>eligibleStatus(rowStatus(row)));

    /* Tombol Arsip ditempatkan tepat di sebelah kiri tombol Buat Permintaan,
       tetapi keduanya tetap berada di group kanan yang sama agar posisi
       tombol Buat Permintaan tidak berubah dari layout sebelumnya. */
    const createButton=head.querySelector('#danpusOpenRequestForm');
    if(!createButton)return;
    let actions=head.querySelector('.danpus-archive-actions');
    if(!actions){
      actions=document.createElement('div');
      actions.className='danpus-archive-actions';
      createButton.parentNode?.insertBefore(actions,createButton);
      actions.appendChild(createButton);
    }

    const archiveButton=document.createElement('button');
    archiveButton.type='button';
    archiveButton.className='danpus-archive-toggle';
    archiveButton.id='danpusArchiveToggle';
    archiveButton.setAttribute('aria-pressed','false');
    archiveButton.innerHTML=`
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5h16"></path><path d="M6.5 7.5v10.5A2 2 0 0 0 8.5 20h7a2 2 0 0 0 2-2V7.5"></path><path d="M9 7.5V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5v2"></path><path d="M10 11v5"></path><path d="M14 11v5"></path></svg>
      <span class="danpus-archive-toggle-label">Arsip</span>`;
    actions.insertBefore(archiveButton,createButton);

    const toggleLabel=archiveButton.querySelector('.danpus-archive-toggle-label');
    const countBadge=document.createElement('span');
    countBadge.className='danpus-archive-count';
    countBadge.id='danpusArchiveSelectedCount';
    countBadge.setAttribute('aria-live','polite');
    countBadge.textContent='0';
    actions.insertBefore(countBadge,archiveButton);

    function ensureHeaderCheckbox(){
      const firstTh=table.querySelector('thead tr th:first-child');
      if(!firstTh)return null;
      let wrap=firstTh.querySelector('.danpus-archive-head-cell');
      if(!wrap){
        const original=document.createElement('span');
        original.className='danpus-archive-head-label';
        while(firstTh.firstChild)original.appendChild(firstTh.firstChild);
        wrap=document.createElement('span');
        wrap.className='danpus-archive-head-cell';
        wrap.appendChild(original);
        firstTh.appendChild(wrap);
      }
      let checkbox=wrap.querySelector('.danpus-archive-select-all');
      if(!checkbox){
        checkbox=document.createElement('input');
        checkbox.type='checkbox';
        checkbox.className='danpus-archive-checkbox danpus-archive-select-all';
        checkbox.setAttribute('aria-label','Pilih semua baris yang dapat diarsipkan');
        wrap.insertBefore(checkbox,wrap.firstChild);
        checkbox.addEventListener('change',()=>{
          const rows=eligibleRows();
          rows.forEach(row=>{
            const id=rowId(row);
            if(!id)return;
            if(checkbox.checked)selectedIds.add(id);else selectedIds.delete(id);
          });
          syncRows();
          syncHeaderCheckbox();
        });
      }
      return checkbox;
    }

    function ensureRowCheckbox(row){
      const firstTd=row.querySelector('td:first-child');
      if(!firstTd)return null;
      let wrap=firstTd.querySelector('.danpus-archive-row-cell');
      if(!wrap){
        const existing=document.createElement('span');
        existing.className='danpus-archive-row-content';
        while(firstTd.firstChild)existing.appendChild(firstTd.firstChild);
        wrap=document.createElement('span');
        wrap.className='danpus-archive-row-cell';
        wrap.appendChild(existing);
        firstTd.appendChild(wrap);
      }
      let checkbox=wrap.querySelector('.danpus-archive-row-checkbox');
      if(!checkbox){
        checkbox=document.createElement('input');
        checkbox.type='checkbox';
        checkbox.className='danpus-archive-checkbox danpus-archive-row-checkbox';
        checkbox.setAttribute('aria-label','Pilih permintaan laporan ini untuk diarsipkan');
        const id=rowId(row);
        checkbox.dataset.permintaanId=id;
        wrap.insertBefore(checkbox,wrap.firstChild);
        checkbox.addEventListener('click',e=>e.stopPropagation());
        checkbox.addEventListener('change',()=>{
          if(id){
            if(checkbox.checked)selectedIds.add(id);else selectedIds.delete(id);
          }
          row.classList.toggle('danpus-archive-selected',checkbox.checked);
          syncHeaderCheckbox();
          syncCount();
        });
      }
      return checkbox;
    }

    function removeRowArchiveUI(row){
      row.classList.remove('danpus-archive-eligible-row','danpus-archive-selected');
      const wrap=row.querySelector('.danpus-archive-row-cell');
      if(!wrap)return;
      const content=wrap.querySelector('.danpus-archive-row-content');
      const firstTd=row.querySelector('td:first-child');
      if(content&&firstTd){
        while(content.firstChild)firstTd.appendChild(content.firstChild);
      }
      wrap.remove();
    }

    function removeHeaderArchiveUI(){
      const wrap=table.querySelector('thead tr th:first-child .danpus-archive-head-cell');
      if(!wrap)return;
      const label=wrap.querySelector('.danpus-archive-head-label');
      const th=wrap.closest('th');
      if(label&&th){while(label.firstChild)th.appendChild(label.firstChild)}
      wrap.remove();
    }

    function syncRows(){
      const rows=Array.from(tbody.querySelectorAll('tr[data-status]'));
      rows.forEach(row=>{
        const eligible=eligibleStatus(rowStatus(row));
        if(!active || !eligible){
          removeRowArchiveUI(row);
          return;
        }
        row.classList.add('danpus-archive-eligible-row');
        const checkbox=ensureRowCheckbox(row);
        const id=rowId(row);
        if(checkbox){
          checkbox.checked=!!(id&&selectedIds.has(id));
          row.classList.toggle('danpus-archive-selected',checkbox.checked);
        }
      });
      if(active)ensureHeaderCheckbox();
      else removeHeaderArchiveUI();
      syncHeaderCheckbox();
      syncCount();
    }

    function syncHeaderCheckbox(){
      const headerCheckbox=table.querySelector('thead .danpus-archive-select-all');
      if(!headerCheckbox)return;
      const rows=eligibleRows();
      const boxes=rows.map(row=>row.querySelector('.danpus-archive-row-checkbox')).filter(Boolean);
      const checked=boxes.length>0&&boxes.every(box=>box.checked);
      const some=boxes.some(box=>box.checked);
      headerCheckbox.checked=checked;
      headerCheckbox.indeterminate=!checked&&some;
      headerCheckbox.disabled=boxes.length===0;
    }

    function syncCount(){
      if(!countBadge)return;
      for(const id of Array.from(selectedIds)){
        if(!eligibleRows().some(row=>rowId(row)===id))selectedIds.delete(id);
      }
      countBadge.textContent=String(selectedIds.size);
      countBadge.classList.toggle('is-visible',active&&selectedIds.size>0);
    }

    function setMode(next){
      active=!!next;
      panel.classList.toggle('danpus-archive-mode',active);
      archiveButton.classList.toggle('is-active',active);
      archiveButton.setAttribute('aria-pressed',active?'true':'false');
      if(toggleLabel)toggleLabel.textContent=active?'Selesai':'Arsip';
      if(!active)selectedIds.clear();
      syncRows();
    }

    archiveButton.addEventListener('click',()=>setMode(!active));

    const scheduleRefresh=()=>{
      if(refreshQueued)return;
      refreshQueued=true;
      requestAnimationFrame(()=>{refreshQueued=false;syncRows();});
    };
    const observer=new MutationObserver(scheduleRefresh);
    observer.observe(tbody,{childList:true,subtree:true});

    syncRows();
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initDanpusArchiveMode,{once:true});
  else initDanpusArchiveMode();
})();
</script>
