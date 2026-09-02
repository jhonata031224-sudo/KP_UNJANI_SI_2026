<style>
/* Poles tampilan Arsip tanpa mengubah alur data/endpoint existing. */
#permintaan-laporan .danpus-archive-toggle{
  position:relative;
  overflow:hidden;
  isolation:isolate;
  border-color:rgba(201,122,0,.28);
  background:linear-gradient(135deg,rgba(201,122,0,.08),var(--p-surface));
  box-shadow:0 5px 14px -10px rgba(201,122,0,.55);
}
#permintaan-laporan .danpus-archive-toggle::after{
  content:"";
  position:absolute;
  inset:0;
  z-index:-1;
  opacity:.42;
  background:repeating-linear-gradient(135deg,transparent 0 8px,rgba(201,122,0,.065) 8px 10px);
  pointer-events:none;
}
#permintaan-laporan .danpus-archive-toggle svg{
  width:16px;
  height:16px;
  filter:drop-shadow(0 1px 1px rgba(0,0,0,.08));
}
#permintaan-laporan .danpus-archive-toggle:hover{
  border-color:var(--gold-solid-bright,#EEA23A);
  box-shadow:0 8px 18px -10px rgba(201,122,0,.7);
  transform:translateY(-1px);
}
#permintaan-laporan .danpus-archive-toggle.is-active{
  background:linear-gradient(135deg,#EEA23A,#EEA23A);
  border-color:#EEA23A;
  color:#fff;
  box-shadow:0 9px 20px -10px rgba(201,122,0,.85);
}
#permintaan-laporan .danpus-archive-toggle.is-active::after{
  opacity:.75;
  background:repeating-linear-gradient(135deg,transparent 0 8px,rgba(255,255,255,.09) 8px 10px);
}

/* Arsip yang sudah dipindahkan tampil di tabel Riwayat/Status yang memang sudah ada. */
#status .request-archived-row td{background:var(--p-surface);}
#status .request-archived-row:hover td{background:var(--p-surface-2);}
#status .request-archived-unit,
#status .request-archived-target{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-height:28px;
  padding:5px 10px;
  border-radius:9px;
  border:1px solid rgba(201,122,0,.22);
  background:rgba(201,122,0,.09);
  color:var(--gold-bright);
  font-size:10px;
  font-weight:800;
  letter-spacing:.02em;
  white-space:nowrap;
  box-sizing:border-box;
}
#status .request-archived-sub{
  margin-top:3px;
  font-size:10px;
  color:var(--p-muted);
}
#status .request-archived-status{
  display:inline-flex;
  align-items:center;
  gap:6px;
  border-radius:999px;
  padding:5px 10px;
  font-size:10px;
  font-weight:800;
  border:1px solid transparent;
  white-space:nowrap;
}
#status .request-archived-status::before{
  content:"";
  width:6px;
  height:6px;
  border-radius:50%;
  background:currentColor;
  flex:0 0 auto;
}
#status .request-archived-status.ok{
  color:var(--p-green);
  background:rgba(63,194,125,.12);
  border-color:rgba(63,194,125,.28);
}
#status .request-archived-status.bad{
  color:var(--p-red);
  background:rgba(181,52,47,.10);
  border-color:rgba(198,40,40,.28);
}
#status .request-archived-status.late{
  color:var(--p-yellow);
  background:rgba(224,168,58,.12);
  border-color:rgba(224,168,58,.32);
}
#status .request-archived-detail{
  min-width:58px;
  text-align:center;
}
</style>
<script>
(function(){
  'use strict';

  function escapeHtml(value){
    return String(value ?? '').replace(/[&<>\"']/g,function(c){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[c];
    });
  }

  function statusKind(status){
    const n=String(status||'').toLowerCase();
    if(n.includes('setuj')||n.includes('diterima')||n.includes('selesai')) return 'ok';
    if(n.includes('tolak')||n.includes('batal')) return 'bad';
    if(n.includes('terlambat')) return 'late';
    return 'late';
  }

  function normalizeArchiveRow(row){
    if(!row || row.dataset.archiveNormalized==='1') return;
    const key=row.getAttribute('data-archive-key');
    if(!key) return;

    const detail=row.querySelector('.archive-detail-btn');
    if(!detail) return;

    const subject=detail.dataset.perihal || '-';
    const target=detail.dataset.tujuan || '-';
    const priority=detail.dataset.prioritas || '-';
    const archivedAt=detail.dataset.tanggal || '-';
    const status=row.dataset.archiveStatus || row.querySelector('.status-pill,.status-dot')?.textContent?.trim() || 'Arsip';
    const unit=target;
    const destination='DANPUS';
    const kind=statusKind(status);

    row.classList.add('request-archived-row');
    row.innerHTML =
      '<td style="text-align:center">'
        +'<span class="request-archived-unit">'+escapeHtml(unit)+'</span>'
      +'</td>'
      +'<td class="subject">'
        +'<div style="font-weight:800;color:var(--p-text)">'+escapeHtml(subject)+'</div>'
        +'<div class="request-archived-sub">Arsip permintaan laporan · '+escapeHtml(priority)+'</div>'
      +'</td>'
      +'<td style="text-align:center">'
        +'<span class="request-archived-target">'+escapeHtml(destination)+'</span>'
      +'</td>'
      +'<td style="text-align:center">'
        +'<span class="request-archived-status '+kind+'">'+escapeHtml(status)+'</span>'
      +'</td>'
      +'<td style="text-align:center">'
        +'<div class="request-deadline" style="justify-content:center">'
          +'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">'
            +'<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path>'
          +'</svg>'
          +escapeHtml(archivedAt)
        +'</div>'
      +'</td>'
      +'<td style="text-align:center">'
        +'<button type="button" class="detail-btn request-archived-detail archive-detail-btn" '
          +'data-perihal="'+escapeHtml(subject)+'" '
          +'data-tujuan="'+escapeHtml(target)+'" '
          +'data-prioritas="'+escapeHtml(priority)+'" '
          +'data-tanggal="'+escapeHtml(archivedAt)+'">Detail</button>'
      +'</td>';
    row.dataset.archiveNormalized='1';
    row.dataset.archiveStatus=status;
  }

  function scan(){
    document.querySelectorAll('#status tbody tr[data-archive-key]').forEach(normalizeArchiveRow);
  }

  function bind(){
    scan();
    const statusBody=document.querySelector('#status tbody');
    if(statusBody && !statusBody.dataset.archivePolishObserver){
      statusBody.dataset.archivePolishObserver='1';
      new MutationObserver(function(){requestAnimationFrame(scan)}).observe(statusBody,{childList:true,subtree:true});
    }
  }

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',bind,{once:true});
  else bind();
})();
</script>