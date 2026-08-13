<style>
/* Pencarian & filter tabel laporan -- 1 sistem dipakai bareng oleh Pimpinan
   (Laporan Masuk, Status Laporan, Permintaan Laporan) dan halaman role lain
   (Riwayat Laporan, Laporan Masuk, Monitoring 3 Satlak) lewat initReportFilter(). */
.rpt-filter-bar{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:0 0 14px}
.rpt-filter-search{position:relative;flex:1 1 240px;min-width:200px;max-width:360px}
.rpt-filter-search svg{position:absolute;left:11px;top:50%;width:15px;height:15px;transform:translateY(-50%);color:var(--p-muted,var(--text-muted));pointer-events:none}
.rpt-filter-search input{box-sizing:border-box;width:100%;height:38px;border:1px solid var(--p-border,var(--border));border-radius:9px;outline:0;background:var(--p-surface-2,var(--panel-alt));color:var(--p-text,var(--text));font:inherit;font-size:12px;padding:8px 11px 8px 34px}
.rpt-filter-search input:focus{border-color:var(--p-accent,var(--gold-bright));box-shadow:0 0 0 3px rgba(201,122,0,.10)}
.rpt-filter-search input::placeholder{color:var(--p-muted,var(--text-muted))}
.rpt-filter-select{height:38px;border:1px solid var(--p-border,var(--border));border-radius:9px;background-color:var(--p-surface-2,var(--panel-alt));color:var(--p-text,var(--text));font:inherit;font-size:11px;padding:0 28px 0 11px;outline:0;cursor:pointer;appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='m6 9 6 6 6-6'/></svg>");background-repeat:no-repeat;background-position:right 9px center;background-size:13px}
.rpt-filter-select:focus{border-color:var(--p-accent,var(--gold-bright));box-shadow:0 0 0 3px rgba(201,122,0,.10)}
.rpt-filter-count{font-size:10px;color:var(--p-muted,var(--text-muted));white-space:nowrap;margin-left:auto}
.rpt-filter-empty{padding:24px 12px;text-align:center;color:var(--p-muted,var(--text-muted));font-size:12px;border:2px dotted var(--p-border,var(--border));border-radius:10px}
@media(max-width:700px){.rpt-filter-bar{gap:7px}.rpt-filter-search{flex:1 1 100%;max-width:none}.rpt-filter-count{width:100%;margin-left:0}}
</style>
<script>
(function(){
  function buildSelect(filterDef){
    var select=document.createElement('select');
    select.className='rpt-filter-select';
    select.setAttribute('aria-label',filterDef.label);
    filterDef.options.forEach(function(opt){
      var o=document.createElement('option');
      o.value=opt.value;
      o.textContent=opt.label;
      select.appendChild(o);
    });
    return select;
  }

  function initReportFilter(cfg){
    var section=document.getElementById(cfg.sectionId);
    if(!section||section.dataset.rptFilterReady==='1')return;
    var table=section.querySelector(cfg.tableSelector);
    var tbody=table?table.querySelector('tbody'):null;
    var anchor=section.querySelector(cfg.anchorSelector);
    if(!table||!tbody||!anchor)return;

    var rows=Array.from(tbody.querySelectorAll(':scope > tr')).filter(function(tr){
      return tr.hasAttribute('data-search');
    });
    if(!rows.length){section.dataset.rptFilterReady='1';return;}

    var colCount=table.querySelectorAll('thead th').length||1;

    var bar=document.createElement('div');
    bar.className='rpt-filter-bar';

    var searchWrap=document.createElement('div');
    searchWrap.className='rpt-filter-search';
    searchWrap.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" autocomplete="off">';
    var input=searchWrap.querySelector('input');
    input.placeholder=cfg.searchPlaceholder;
    input.setAttribute('aria-label',cfg.searchPlaceholder);
    bar.appendChild(searchWrap);

    var selects=(cfg.filters||[]).map(function(filterDef){
      var select=buildSelect(filterDef);
      bar.appendChild(select);
      return {el:select,attr:filterDef.attr};
    });

    var count=document.createElement('span');
    count.className='rpt-filter-count';
    bar.appendChild(count);

    anchor.insertAdjacentElement('afterend',bar);

    var emptyRow=document.createElement('tr');
    emptyRow.className='rpt-filter-empty-row';
    emptyRow.style.display='none';
    var emptyTd=document.createElement('td');
    emptyTd.colSpan=colCount;
    var emptyBox=document.createElement('div');
    emptyBox.className='rpt-filter-empty';
    emptyBox.innerHTML='<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 8px;display:block;opacity:.7"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>'+cfg.emptyText;
    emptyTd.appendChild(emptyBox);
    emptyRow.appendChild(emptyTd);
    tbody.appendChild(emptyRow);

    function apply(){
      var q=(input.value||'').trim().toLowerCase();
      var visible=0;
      rows.forEach(function(row){
        var matchesSearch=!q||(row.dataset.search||'').indexOf(q)!==-1;
        var matchesFilters=selects.every(function(s){
          return s.el.value==='all'||row.dataset[s.attr]===s.el.value;
        });
        var match=matchesSearch&&matchesFilters;
        row.style.display=match?'':'none';
        if(match)visible++;
      });
      count.textContent=visible+' dari '+rows.length+' data';
      emptyRow.style.display=visible===0?'':'none';
    }

    input.addEventListener('input',apply);
    selects.forEach(function(s){s.el.addEventListener('change',apply)});
    apply();
    section.dataset.rptFilterReady='1';
  }

  function boot(){
    initReportFilter({
      sectionId:'masuk',
      tableSelector:'.clean-table',
      anchorSelector:'.section-head-clean',
      searchPlaceholder:'Cari pengirim atau perihal...',
      emptyText:'Tidak ada laporan masuk yang sesuai dengan pencarian/filter.',
      filters:[
        {label:'Filter prioritas',attr:'prioritas',options:[{value:'all',label:'Semua Prioritas'},{value:'Tinggi',label:'Tinggi'},{value:'Sedang',label:'Sedang'},{value:'Rendah',label:'Rendah'}]}
      ]
    });
    initReportFilter({
      sectionId:'status',
      tableSelector:'.clean-table',
      anchorSelector:'.section-head-clean',
      searchPlaceholder:'Cari satuan atau perihal...',
      emptyText:'Tidak ada laporan yang sesuai dengan pencarian/filter.',
      filters:[
        {label:'Filter status',attr:'outcome',options:[{value:'all',label:'Semua Status'},{value:'disetujui',label:'Disetujui'},{value:'ditolak',label:'Ditolak'}]}
      ]
    });
    initReportFilter({
      sectionId:'permintaan-laporan',
      tableSelector:'.request-table',
      anchorSelector:'.request-stats',
      searchPlaceholder:'Cari perihal atau satuan tujuan...',
      emptyText:'Tidak ada permintaan laporan yang sesuai dengan pencarian/filter.',
      filters:[
        {label:'Filter status',attr:'statusTampilan',options:[{value:'all',label:'Semua Status'},{value:'Belum dikerjakan',label:'Belum Dikerjakan'},{value:'Sedang dikerjakan',label:'Sedang Dikerjakan'},{value:'Menunggu pemeriksaan',label:'Menunggu Pemeriksaan'},{value:'Selesai',label:'Selesai'},{value:'Terlambat',label:'Terlambat'}]},
        {label:'Filter prioritas',attr:'prioritas',options:[{value:'all',label:'Semua Prioritas'},{value:'Tinggi',label:'Tinggi'},{value:'Sedang',label:'Sedang'},{value:'Rendah',label:'Rendah'}]}
      ]
    });

    // Halaman role (Satlak/Binum/Diklat/Binmat/Binfung) -- section-nya pakai
    // .section-head biasa (bukan -clean) dan tabelnya .dtbl (bukan .clean-table),
    // makanya konfigurasi terpisah dari punya Pimpinan di atas. Kalau section-id
    // yang sama nggak ada di halaman ini (mis. lagi buka Pimpinan), initReportFilter
    // otomatis no-op karena elemen-elemennya nggak ketemu.
    var filterPrioritas=[{label:'Filter prioritas',attr:'prioritas',options:[{value:'all',label:'Semua Prioritas'},{value:'Tinggi',label:'Tinggi'},{value:'Sedang',label:'Sedang'},{value:'Rendah',label:'Rendah'}]}];
    initReportFilter({
      sectionId:'riwayat',
      tableSelector:'.dtbl',
      anchorSelector:'.section-head',
      searchPlaceholder:'Cari perihal atau tujuan...',
      emptyText:'Tidak ada laporan yang sesuai dengan pencarian/filter.',
      filters:filterPrioritas
    });
    initReportFilter({
      sectionId:'masuk',
      tableSelector:'.dtbl',
      anchorSelector:'.section-head',
      searchPlaceholder:'Cari pengirim atau perihal...',
      emptyText:'Tidak ada laporan masuk yang sesuai dengan pencarian/filter.',
      filters:filterPrioritas
    });
    initReportFilter({
      sectionId:'monitoring',
      tableSelector:'.dtbl',
      anchorSelector:'.section-head',
      searchPlaceholder:'Cari satlak atau perihal...',
      emptyText:'Tidak ada laporan dari 3 Satlak yang sesuai dengan pencarian/filter.',
      filters:filterPrioritas
    });
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
</script>
