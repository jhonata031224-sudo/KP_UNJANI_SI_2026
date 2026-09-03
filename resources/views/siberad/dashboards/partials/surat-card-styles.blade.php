<style>
/* Kartu Surat gaya "file/dokumen" (niru referensi surat1.png) -- dipakai
   ganti tabel di panel Kirim Surat. Sengaja partial TERPISAH & di-include
   di kedua dashboard (Satuan & Pimpinan) biar gak dobel-tulis kayak yang
   sempat kejadian pas nge-redesain modal Buat Surat (banyak properti
   ketinggalan di salah satu file). */
.surat-file-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px}
.surat-file-card{position:relative;display:flex;flex-direction:column;background:var(--panel);border:1px solid var(--border-soft);border-radius:14px;padding:22px;box-sizing:border-box;box-shadow:0 10px 30px rgba(0,0,0,.15)}
.surat-file-card-icon{flex-shrink:0;width:68px;height:68px;border-radius:16px;background:var(--gold-dim);color:var(--gold-bright);display:flex;align-items:center;justify-content:center;margin-bottom:16px}
.surat-file-card-icon svg{width:32px;height:32px}
.surat-file-card[data-prioritas="Rendah"] .surat-file-card-icon{background:color-mix(in srgb,#8b5cf6 16%,var(--panel));color:#8b5cf6}
.surat-file-card[data-prioritas="Sedang"] .surat-file-card-icon{background:color-mix(in srgb,#a855f7 16%,var(--panel));color:#a855f7}
.surat-file-card[data-prioritas="Tinggi"] .surat-file-card-icon{background:color-mix(in srgb,#6d28d9 16%,var(--panel));color:#6d28d9}
.surat-file-card-badge{align-self:flex-start;margin-bottom:14px}
.status-badge.status-menunggu.surat-file-card-badge,
#suratDetailModal .status-badge.status-menunggu{color:#2476ad;background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.25)}
.surat-file-card-title{font-family:var(--display);font-size:17px;font-weight:700;line-height:1.35;color:var(--text);margin:0 0 14px}
.surat-file-card-dari-label{font-size:12px;color:var(--text-muted)}
.surat-file-card-dari-value{display:flex;align-items:center;flex-wrap:wrap;gap:8px;font-size:13.5px;font-weight:700;color:var(--text);margin-top:5px}
.surat-file-card-divider{border-top:1px solid var(--border-soft);margin:16px 0}
.surat-file-card-meta{display:flex;align-items:center;gap:12px}
.surat-file-card-meta-icon{flex-shrink:0;width:36px;height:36px;border-radius:10px;background:var(--gold-dim);color:var(--gold-bright);display:flex;align-items:center;justify-content:center}
.surat-file-card-meta-icon svg{width:17px;height:17px}
.surat-file-card-meta-label{font-size:12px;color:var(--text-muted)}
.surat-file-card-meta-value{font-size:13.5px;font-weight:700;color:var(--text);margin-top:5px}
.surat-file-card-btn{width:100%;box-sizing:border-box;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px;border-radius:10px;border:1px solid var(--border);background:var(--gold-dim);color:var(--gold-bright);font-family:var(--body);font-weight:700;font-size:13.5px;cursor:pointer;transition:background-color .15s ease,transform .15s ease}
.surat-file-card-btn:hover{background:color-mix(in srgb,var(--gold-bright) 22%,var(--gold-dim));transform:translateY(-1px)}
.surat-file-grid .empty-state{grid-column:1/-1}

/* Modal Detail Surat (niru referensi modaldetailsurat1.png) */
#suratDetailModal .report-modal-card{width:min(900px,100%)}
#suratDetailModal .report-modal-head{margin-bottom:20px}
#suratDetailModal .report-modal-head h3{font-size:22px}
.surat-detail-body{display:grid;grid-template-columns:1.2fr 1fr;gap:0 28px}
.surat-detail-col{display:flex;flex-direction:column}
.surat-detail-col-left{border-right:1px solid var(--border-soft);padding-right:28px}
.surat-detail-item{padding:11px 13px;border:1px solid var(--border-soft);border-radius:10px;background:var(--panel-alt);margin-bottom:10px}
.surat-detail-item:last-child{margin-bottom:0}
.surat-detail-item-label{font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
.surat-detail-item-value{font-size:13.5px;font-weight:600;color:var(--text);line-height:1.55;white-space:pre-wrap}
.surat-detail-item-row{display:flex;gap:10px;margin-bottom:10px}
.surat-detail-item-row .surat-detail-item{flex:1;min-width:0;margin-bottom:0}
.surat-detail-item-value .priority-tag.prio-rendah{color:#8b5cf6;background:color-mix(in srgb,#8b5cf6 12%,transparent);border-color:color-mix(in srgb,#8b5cf6 35%,transparent)}
.surat-detail-item-value .priority-tag.prio-sedang{color:#a855f7;background:color-mix(in srgb,#a855f7 12%,transparent);border-color:color-mix(in srgb,#a855f7 35%,transparent)}
.surat-detail-item-value .priority-tag.prio-tinggi{color:#6d28d9;background:color-mix(in srgb,#6d28d9 12%,transparent);border-color:color-mix(in srgb,#6d28d9 35%,transparent)}
.surat-detail-panel{border:1px solid var(--border-soft);border-radius:12px;padding:16px;background:var(--panel-alt);margin-bottom:14px}
.surat-detail-panel:last-child{margin-bottom:0}
.surat-detail-panel-title{font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin:0 0 14px}
.surat-detail-timeline-item{position:relative;padding-left:26px;padding-bottom:20px}
.surat-detail-timeline-item:last-child{padding-bottom:0}
.surat-detail-timeline-dot{position:absolute;left:0;top:0;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--success-bright,#3dba7e);color:#fff;z-index:1}
.surat-detail-timeline-dot svg{width:10px;height:10px}
.surat-detail-timeline-item.is-pending .surat-detail-timeline-dot{background:var(--panel);border:2px solid var(--border);color:transparent}
.surat-detail-timeline-item:not(:last-child)::before{content:"";position:absolute;left:8px;top:18px;bottom:-20px;width:2px;background:var(--border-soft)}
.surat-detail-timeline-title{font-size:13px;font-weight:700;color:var(--text);line-height:18px}
.surat-detail-timeline-sub{font-size:11.5px;color:var(--text-muted);margin-top:3px}
.surat-detail-dokumen-row{display:flex;align-items:center;gap:12px}
.surat-detail-dokumen-icon{flex-shrink:0;width:38px;height:38px;border-radius:8px;background:#d64545;color:#fff;display:flex;align-items:center;justify-content:center;font-size:8.5px;font-weight:800;letter-spacing:.02em}
.surat-detail-dokumen-info{flex:1;min-width:0}
.surat-detail-dokumen-name{display:block;font-size:13px;font-weight:700;color:var(--text);word-break:break-all;text-decoration:none}
.surat-detail-dokumen-name:hover{color:var(--gold-bright);text-decoration:underline}
.surat-detail-dokumen-size{font-size:11px;color:var(--text-muted);margin-top:2px}
.surat-detail-dokumen-download{flex-shrink:0;display:inline-flex;align-items:center;gap:6px;color:var(--gold-bright);font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap}
.surat-detail-dokumen-download:hover{text-decoration:underline}
.surat-detail-dokumen-download svg{width:15px;height:15px}
#suratDetailModal .modal-actions{margin-top:20px}
#suratDetailKonfirmasi{background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border-color:transparent}
#suratDetailKonfirmasi[hidden]{display:none}
@media(max-width:700px){.surat-detail-body{grid-template-columns:1fr}.surat-detail-col-left{border-right:none;padding-right:0;padding-bottom:20px;border-bottom:1px solid var(--border-soft);margin-bottom:20px}}
</style>
<script>
(function(){
  // Cari + urutkan kartu Kirim Surat -- niru pola .rpt-filter-* yang sama
  // dipakai initReportFilter() (danpus-report-table-filter.blade.php) &
  // initCardSearch() (permintaan-laporan-deadline.blade.php), tapi versi
  // lebih simpel karena grid ini cuma 1 status ("Menunggu Konfirmasi") --
  // gak butuh dropdown filter status, cuma cari + Terbaru/Terlama.
  function initSuratCardSearch(){
    var section=document.getElementById('kirim-surat');
    if(!section||section.dataset.searchReady==='1')return;
    var grid=document.getElementById('suratTerkirimGrid');
    var panel=section.querySelector('.panel');
    if(!grid||!panel)return;
    var initialCards=grid.querySelectorAll(':scope > .surat-file-card');
    if(!initialCards.length)return;
    section.dataset.searchReady='1';

    var bar=document.createElement('div');
    bar.className='rpt-filter-bar';
    bar.innerHTML='<div class="rpt-filter-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" autocomplete="off" placeholder="Cari perihal atau tujuan..." aria-label="Cari perihal atau tujuan"></div><select aria-label="Urutkan"><option value="newest">Terbaru</option><option value="oldest">Terlama</option></select><span class="rpt-filter-count"></span>';
    panel.appendChild(bar);

    var searchEmpty=document.createElement('div');
    searchEmpty.className='empty-state';
    searchEmpty.style.display='none';
    searchEmpty.innerHTML='<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Tidak ada surat yang sesuai dengan pencarian.</div>';
    grid.parentNode.insertBefore(searchEmpty,grid.nextSibling);

    var input=bar.querySelector('input');
    var sortSelect=bar.querySelector('select');
    var count=bar.querySelector('.rpt-filter-count');
    var applying=false;
    var raf=0;

    function apply(){
      applying=true;
      var items=Array.prototype.slice.call(grid.querySelectorAll(':scope > .surat-file-card'));
      var srvEmpty=grid.querySelector(':scope > .empty-state');
      if(items.length===0){
        bar.style.display='none';
        searchEmpty.style.display='none';
        applying=false;
        return;
      }
      bar.style.display='';

      items.sort(function(a,b){
        var diff=Number(a.dataset.createdAt)-Number(b.dataset.createdAt);
        return sortSelect.value==='oldest'?diff:-diff;
      });
      // Reorder DOM cuma kalau urutannya BENERAN beda -- insertBefore
      // tanpa syarat bikin MutationObserver ke bawah kepicu ulang terus
      // (apply() manggil dirinya sendiri lewat observer) walau urutan
      // sebenarnya udah pas, sama kayak needsReorder di initReportFilter.
      var needsReorder=items.some(function(item,i){
        return item.nextElementSibling!==(items[i+1]||srvEmpty||null);
      });
      if(needsReorder){
        items.forEach(function(item){ grid.insertBefore(item,srvEmpty||null); });
      }

      var q=(input.value||'').trim().toLowerCase();
      var visible=0;
      items.forEach(function(item){
        var match=!q||(item.dataset.search||'').indexOf(q)!==-1;
        item.style.display=match?'':'none';
        if(match)visible++;
      });
      count.textContent=visible+' dari '+items.length+' surat';
      searchEmpty.style.display=visible===0?'':'none';
      applying=false;
    }

    function scheduleApply(){
      if(raf)return;
      raf=requestAnimationFrame(function(){raf=0;if(!applying)apply();});
    }

    input.addEventListener('input',apply);
    sortSelect.addEventListener('change',apply);

    var observer=new MutationObserver(function(){ if(!applying) scheduleApply(); });
    observer.observe(grid,{childList:true});

    apply();
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initSuratCardSearch);else initSuratCardSearch();
})();
</script>
