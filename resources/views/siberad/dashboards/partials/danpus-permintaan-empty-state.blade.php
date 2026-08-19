<style>
#permintaan-laporan .danpus-request-empty-row td{padding:0!important;border-bottom:0!important}
#permintaan-laporan .danpus-request-empty-state{margin:12px 0 0;padding:34px 18px;text-align:center;border:2px dotted var(--p-border);border-radius:12px;background:var(--p-surface-2);color:var(--p-muted)}
#permintaan-laporan .danpus-request-empty-state svg{width:34px;height:34px;display:block;margin:0 auto 10px;opacity:.72}
#permintaan-laporan .danpus-request-empty-title{font-size:13px;font-weight:800;color:var(--p-text);margin-bottom:4px}
#permintaan-laporan .danpus-request-empty-sub{font-size:11px;line-height:1.55;color:var(--p-muted)}
</style>
<script>
(function(){
'use strict';
function initDanpusRequestEmptyState(){
  const section=document.getElementById('permintaan-laporan');
  const table=section?.querySelector('.request-table');
  const tbody=table?.querySelector('tbody');
  if(!section||!table||!tbody||section.dataset.danpusEmptyStateBound==='1')return;
  section.dataset.danpusEmptyStateBound='1';

  function dataRows(){
    return Array.from(tbody.querySelectorAll(':scope > tr')).filter(row=>
      row.dataset.permintaanId || row.dataset.search || row.dataset.status || row.querySelector('[data-permintaan-id]')
    );
  }

  function sync(){
    const rows=dataRows();
    let empty=tbody.querySelector(':scope > tr.danpus-request-empty-row');
    const filterEmpty=tbody.querySelector(':scope > tr.rpt-filter-empty-row');

    if(rows.length===0){
      if(filterEmpty)filterEmpty.style.display='none';
      if(!empty){
        empty=document.createElement('tr');
        empty.className='danpus-request-empty-row';
        empty.innerHTML='<td colspan="5"><div class="danpus-request-empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="danpus-request-empty-title">Belum ada permintaan laporan</div><div class="danpus-request-empty-sub">Semua permintaan laporan sudah diarsipkan. Permintaan baru dari Danpus akan muncul di sini.</div></div></td>';
        tbody.appendChild(empty);
      }

      const count=section.querySelector('.rpt-filter-count');
      if(count)count.textContent='0 dari 0 data';
      return;
    }

    if(empty)empty.remove();
  }

  const observer=new MutationObserver(sync);
  observer.observe(tbody,{childList:true,subtree:true});
  sync();
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initDanpusRequestEmptyState,{once:true});else initDanpusRequestEmptyState();
})();
</script>