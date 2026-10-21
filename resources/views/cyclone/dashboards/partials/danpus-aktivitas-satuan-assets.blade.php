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
@media(max-width:700px){.akt-search{max-width:100%;flex-basis:100%}}
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
