{{-- Gaya + JS tab/filter Riwayat Aktivitas DANPUS (Surat & Kendala per satuan).
     Semua selector diawali "akt-"/data-akt agar tidak bersentuhan dengan skrip
     timeline & pencarian laporan yang menarget section[id^="satlak-"]
     .section-block / .clean-table-wrap. --}}
<style>
.akt-tabs{display:flex;gap:6px;flex-wrap:wrap;margin:0 0 14px;padding:5px;border:1px solid var(--p-border);border-radius:12px;background:var(--p-surface-2);width:fit-content;max-width:100%}
.akt-tab{display:inline-flex;align-items:center;gap:7px;border:0;background:transparent;color:var(--p-muted);font:inherit;font-size:12px;font-weight:700;padding:8px 14px;border-radius:9px;cursor:pointer}
.akt-tab:hover{color:var(--p-text)}
.akt-tab.is-active{background:var(--p-surface);color:var(--p-text);box-shadow:0 1px 3px rgba(0,0,0,.12)}
.akt-tab-count{font-size:10px;font-weight:800;padding:1px 7px;border-radius:999px;background:var(--p-border);color:var(--p-muted)}
.akt-tab-alert{font-size:10px;font-weight:800;min-width:18px;text-align:center;padding:1px 6px;border-radius:999px;background:#d97706;color:#fff}
.akt-view[hidden]{display:none!important}
.akt-kpi-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin:0 0 12px}
.akt-kpi{border:1px solid var(--p-border);background:var(--p-surface-2);border-radius:12px;padding:11px 14px;display:flex;flex-direction:column;gap:4px}
.akt-kpi span{font-size:10.5px;color:var(--p-muted);font-weight:600}
.akt-kpi b{font-size:20px;font-weight:800;color:var(--p-text);line-height:1.1}
.akt-kpi b.tone-blue{color:#2476ad}.akt-kpi b.tone-amber{color:#d97706}.akt-kpi b.tone-green{color:var(--p-green,#16a34a)}
.akt-note{margin:0 0 12px;font-size:11.5px;color:var(--p-muted)}
.akt-toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 8px}
.akt-search{position:relative;flex:1 1 240px;max-width:340px}
.akt-search svg{position:absolute;left:11px;top:50%;width:16px;height:16px;transform:translateY(-50%);color:var(--p-muted);pointer-events:none}
.akt-search input{box-sizing:border-box;width:100%;height:38px;border:1px solid var(--p-border);border-radius:9px;outline:0;background:var(--p-surface-2);color:var(--p-text);font:inherit;font-size:12px;padding:8px 11px 8px 35px}
.akt-search input:focus{border-color:#FF9800;box-shadow:0 0 0 3px rgba(201,122,0,.10)}
.akt-count{font-size:11px;color:var(--p-muted);margin:0 0 12px}
.akt-tag{display:inline-flex;align-items:center;font-size:9.5px;font-weight:700;padding:3px 8px;border-radius:999px;background:var(--p-surface-2);color:var(--p-muted);border:1px solid var(--p-border)}
.akt-tag-arsip{color:#2476ad;border-color:rgba(36,118,173,.35);background:rgba(52,152,219,.08)}
.akt-tag-perlu{color:#d97706;border-color:rgba(217,119,6,.4);background:rgba(217,119,6,.1)}
.akt-card-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.akt-link-tindak{font-size:11.5px;font-weight:700;color:#d97706;text-decoration:none}
.akt-link-tindak:hover{text-decoration:underline}
.akt-kcard-info{display:flex;flex-wrap:wrap;gap:4px;margin-top:8px;font-size:10.5px;color:var(--p-muted)}
.akt-card-chips{display:flex;flex-wrap:wrap;gap:6px;margin:10px 0 2px}
.akt-chip{font-size:10px;font-weight:700;padding:3px 9px;border-radius:999px;background:var(--p-surface-2);color:var(--p-muted);border:1px solid var(--p-border)}
.akt-chip-perlu{color:#fff;background:#d97706;border-color:#d97706}
.akt-side-badge{margin-left:auto;font-size:9.5px;font-weight:800;min-width:17px;text-align:center;padding:1px 6px;border-radius:999px;background:#d97706;color:#fff}
.akt-toolbar .styled-select-wrap{width:auto;min-width:150px;max-width:200px;flex:0 0 auto}
/* ===== Daftar Surat ringkas + alur (tab Surat) ===== */
.akt-list{display:flex;flex-direction:column;gap:12px}
.akt-srow{border:1px solid var(--p-border);border-radius:14px;background:var(--p-surface);overflow:hidden;transition:border-color .15s ease,box-shadow .15s ease}
.akt-srow:hover{border-color:#FF9800}
.akt-srow[open]{border-color:#FF9800;box-shadow:0 4px 18px rgba(0,0,0,.06)}
.akt-srow-sum{list-style:none;cursor:pointer;display:flex;align-items:center;gap:14px;padding:16px 18px}
.akt-srow-sum::-webkit-details-marker{display:none}
.akt-srow-chev{width:9px;height:9px;flex:0 0 auto;border-right:2.5px solid var(--text);border-bottom:2.5px solid var(--text);transform:rotate(-45deg);transition:transform .18s ease}
.akt-srow[open] .akt-srow-chev{transform:rotate(45deg)}
.akt-srow-main{display:flex;flex-direction:column;gap:5px;min-width:0;flex:1}
.akt-srow-title{font-family:var(--display);font-size:16px;font-weight:700;color:var(--text);line-height:1.35;word-break:break-word}
.akt-srow-meta{font-size:12.5px;font-weight:600;color:var(--text-muted);line-height:1.4}
.akt-srow-tags{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end;flex:0 1 auto;max-width:48%}
.akt-st{display:inline-flex;align-items:center;font-size:11.5px;font-weight:800;padding:4px 12px;border-radius:999px;border:1px solid transparent;white-space:nowrap}
.akt-st-wait{color:#b45309;background:rgba(217,119,6,.14);border-color:rgba(217,119,6,.4)}
.akt-st-ok{color:#1f7a4f;background:rgba(61,186,126,.16);border-color:rgba(61,186,126,.45)}
.akt-st-info{color:#2476ad;background:rgba(52,152,219,.12);border-color:rgba(52,152,219,.35)}
.akt-srow-sum .akt-tag{font-size:11px;font-weight:700}
.akt-srow-body{padding:6px 22px 18px 46px;border-top:1px solid var(--p-border)}
.akt-flow{list-style:none;margin:16px 0 4px;padding:0}
.akt-flow-step{position:relative;display:grid;grid-template-columns:18px minmax(0,1fr);column-gap:16px;padding:0 0 22px}
.akt-flow-step:last-child{padding-bottom:4px}
.akt-flow-step::before{content:"";position:absolute;left:8px;top:22px;bottom:-4px;width:0;border-left:2px dotted var(--p-border)}
.akt-flow-step:last-child::before{display:none}
.akt-flow-dot{width:18px;height:18px;border-radius:50%;box-sizing:border-box;margin-top:1px;border:2px solid var(--p-border);background:var(--p-surface)}
.akt-flow-step.is-done .akt-flow-dot{background:var(--p-green,#16a34a);border-color:var(--p-green,#16a34a)}
.akt-flow-step.is-current .akt-flow-dot{border-color:#d97706;background:var(--p-surface);box-shadow:0 0 0 4px rgba(217,119,6,.2)}
.akt-flow-head{display:flex;justify-content:space-between;align-items:baseline;gap:12px;flex-wrap:wrap}
.akt-flow-title{font-family:var(--display);font-size:14.5px;font-weight:700;color:var(--text);line-height:1.35}
.akt-flow-step.is-current .akt-flow-title{color:#b45309}
.akt-flow-date{font-size:11.5px;font-weight:700;font-family:var(--mono,monospace);color:var(--text-muted);white-space:nowrap}
.akt-flow-route{font-size:13px;font-weight:700;color:var(--text);margin-top:4px;line-height:1.45}
.akt-flow-note{font-size:12.5px;font-weight:500;color:var(--text);opacity:.88;margin-top:5px;line-height:1.55;word-break:break-word}
.akt-flow-note b{font-weight:800;opacity:1;color:var(--text)}
.akt-flow-self{display:inline-block;margin-top:8px;font-size:11.5px;font-weight:800;color:#b45309;background:rgba(217,119,6,.14);border:1px solid rgba(217,119,6,.35);border-radius:999px;padding:3px 11px}
.akt-srow-actions{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-top:16px;padding-top:14px;border-top:1px dashed var(--p-border)}
.akt-btn{font:inherit;font-size:13px;font-weight:700;color:var(--text);background:var(--p-surface-2);border:1px solid var(--p-border);border-radius:10px;padding:9px 18px;cursor:pointer}
.akt-btn:hover{border-color:#FF9800}
@media(max-width:700px){.akt-srow-sum{flex-wrap:wrap;padding:14px}.akt-srow-tags{max-width:100%;justify-content:flex-start;flex-basis:100%;padding-left:23px}.akt-srow-body{padding-left:16px;padding-right:14px}}

@media(max-width:700px){.akt-search{max-width:100%;flex-basis:100%}.akt-toolbar .styled-select-wrap{flex:1 1 calc(50% - 5px);min-width:0;max-width:none}}
</style>
<script>
(function(){
  function initTabs(bar){
    var section=bar.closest('section');if(!section||bar.dataset.aktReady==='1')return;
    var tabs=Array.prototype.slice.call(bar.querySelectorAll('[data-akt-tab]'));
    function show(name){
      tabs.forEach(function(b){var on=b.dataset.aktTab===name;b.classList.toggle('is-active',on);b.setAttribute('aria-selected',on?'true':'false');});
      section.querySelectorAll('[data-akt-view]').forEach(function(v){v.hidden=v.dataset.aktView!==name;});
    }
    tabs.forEach(function(b){b.addEventListener('click',function(){show(b.dataset.aktTab);});});
    bar.dataset.aktReady='1';
  }
  function initFilter(view){
    var grid=view.querySelector('[data-akt-grid]');if(!grid||view.dataset.aktFilterReady==='1')return;
    var sel=grid.dataset.aktCard;
    var cards=Array.prototype.slice.call(grid.querySelectorAll(':scope > '+sel));
    var tb=view.querySelector('[data-akt-toolbar]');if(!cards.length||!tb)return;
    var count=view.querySelector('[data-akt-count]');var nomatch=view.querySelector('[data-akt-nomatch]');
    var unit=count?count.dataset.aktUnit:'';var total=cards.length;
    function f(n){return tb.querySelector('[data-akt-f="'+n+'"]');}
    function val(n){var el=f(n);return el?el.value:'';}
    function apply(){
      var q=(val('q')||'').trim().toLowerCase(),ar=val('arah'),st=val('status'),as=val('arsip'),oldest=val('sort')==='oldest';
      var sorted=cards.slice().sort(function(a,b){var d=Number(a.dataset.createdAt)-Number(b.dataset.createdAt);return oldest?d:-d;});
      var shown=0;
      sorted.forEach(function(c){
        var ok=(!q||(c.dataset.search||'').indexOf(q)!==-1)&&(!ar||c.dataset.arah===ar)&&(!st||c.dataset.status===st)&&(!as||c.dataset.arsip===as);
        c.style.display=ok?'':'none';if(ok)shown++;grid.appendChild(c);
      });
      if(count)count.textContent='Menampilkan '+shown+' dari '+total+' '+unit;
      if(nomatch)nomatch.style.display=shown===0?'block':'none';
    }
    tb.addEventListener('input',apply);tb.addEventListener('change',apply);
    apply();view.dataset.aktFilterReady='1';
  }
  function boot(){
    document.querySelectorAll('[data-akt-tabs]').forEach(initTabs);
    document.querySelectorAll('[data-akt-view]').forEach(initFilter);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
</script>
