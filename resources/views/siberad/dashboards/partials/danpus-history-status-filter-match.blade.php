<script>
(function(){
  'use strict';

  const STATUS_OPTIONS=[
    {value:'all',label:'Semua Status'},
    {value:'Sedang diproses',label:'Sedang diproses'},
    {value:'Menunggu',label:'Menunggu'},
    {value:'Revisi',label:'Revisi'},
    {value:'Terlambat',label:'Terlambat'},
    {value:'Dibatalkan',label:'Dibatalkan'},
    {value:'Selesai · Ditolak',label:'Selesai · Ditolak'},
    {value:'Selesai · Disetujui',label:'Selesai · Disetujui'}
  ];

  function mapStatus(raw){
    const text=String(raw||'').replace(/\s+/g,' ').trim().toLowerCase();
    if(text.includes('terlambat')) return 'Terlambat';
    if(text.includes('batal')) return 'Dibatalkan';
    if(text.includes('tolak')) return 'Selesai · Ditolak';
    if(text.includes('setuj') || text.includes('diterima')) return 'Selesai · Disetujui';
    if(text.includes('revisi')) return 'Revisi';
    if(text.includes('menunggu')) return 'Menunggu';
    if(text.includes('progres') || text.includes('proses')) return 'Sedang diproses';
    return 'Sedang diproses';
  }

  function syncRows(){
    document.querySelectorAll('#riwayat .dtbl tbody > tr[data-search]').forEach(function(row){
      const statusCell=row.querySelector('td:nth-child(4)');
      if(statusCell) row.dataset.reportStatus=mapStatus(statusCell.textContent);
    });
  }

  function syncFilter(){
    const section=document.getElementById('riwayat');
    if(!section) return false;

    const select=section.querySelector('.rpt-filter-bar .rpt-filter-select');
    if(!select) return false;

    const alreadyMatched=select.options.length===STATUS_OPTIONS.length && STATUS_OPTIONS.every(function(opt,index){
      const current=select.options[index];
      return current && current.value===opt.value && current.textContent===opt.label;
    });

    if(alreadyMatched){
      syncRows();
      return true;
    }

    const current=select.value;
    select.innerHTML='';
    STATUS_OPTIONS.forEach(function(opt){
      const option=document.createElement('option');
      option.value=opt.value;
      option.textContent=opt.label;
      select.appendChild(option);
    });
    select.value=STATUS_OPTIONS.some(function(opt){return opt.value===current;}) ? current : 'all';
    select.setAttribute('aria-label','Filter status');

    syncRows();
    select.dispatchEvent(new Event('change',{bubbles:true}));
    return true;
  }

  function boot(){
    syncRows();
    if(syncFilter()) return;
    setTimeout(boot,80);
  }

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',boot);
  else boot();

  const observer=new MutationObserver(function(){
    syncRows();
    syncFilter();
  });
  function observe(){
    if(document.body) observer.observe(document.body,{childList:true,subtree:true});
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',observe);
  else observe();
})();
</script>
