<style>
@keyframes siberadTembusanCardIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.siberad-card-in{animation:siberadTembusanCardIn .35s ease}
@keyframes siberadTembusanCardUpdate{0%{outline-color:var(--gold-bright);box-shadow:0 0 0 3px var(--gold-dim)}100%{outline-color:transparent;box-shadow:none}}
.siberad-card-updated{animation:siberadTembusanCardUpdate 1.4s ease;outline:2px solid transparent}
</style>
<script>
(function(){
  // Polling buat sisi PENERIMA tembusan (4 Satlak/4 Sdir, mis. Duktek) --
  // sebelum ini #kcard-grid-tembusan cuma dirender sekali pas load halaman
  // (beda dari lonceng notifikasi yang sudah realtime), jadi kendala baru
  // dari Kasansi baru kelihatan setelah reload manual & tidak ada toast
  // pop-up sama sekali. Pola sync-nya SAMA PERSIS dengan
  // kendala-terkirim-realtime.blade.php (fresh-vs-fresh, bukan baca DOM
  // yang sedang tampil) supaya tidak kedip terus tanpa perubahan data.
  var endpoint='{{ route('laporan-kendala-tembusan.realtime') }}';
  var idAttr='data-tembusan-id';
  var busy=false;
  var lastFreshHtml={};
  var animateSync=false;

  function emptyMarkupOf(container){
    var first=container.firstElementChild;
    return (first && !first.hasAttribute(idAttr)) ? first.outerHTML : '';
  }

  function normalize(html){
    return html.replace(/>\s+</g,'><').trim();
  }

  function syncGrid(container, freshHtml, emptyMarkup){
    if(!container) return 0;
    var currentCards=Array.prototype.slice.call(container.querySelectorAll('['+idAttr+']'));

    var temp=document.createElement('div');
    temp.innerHTML=freshHtml;
    var freshCards=Array.prototype.slice.call(temp.children);

    if(freshCards.length===0){
      if(currentCards.length>0) container.innerHTML=emptyMarkup||'';
      return 0;
    }

    var freshIds=freshCards.map(function(c){return c.getAttribute(idAttr);});
    if(freshIds.some(function(id){return !id;})) return 0;

    var existingIds={};
    currentCards.forEach(function(c){ existingIds[c.getAttribute(idAttr)]=true; });

    var newCount=0;
    freshCards.forEach(function(fresh){
      var id=fresh.getAttribute(idAttr);
      if(!existingIds[id]) newCount++;
    });

    if(animateSync){
      freshCards.forEach(function(fresh){
        var id=fresh.getAttribute(idAttr);
        if(!existingIds[id]) fresh.classList.add('siberad-card-in');
      });
    }

    freshCards.forEach(function(fresh){
      var id=fresh.getAttribute(idAttr);
      var freshSig=normalize(fresh.outerHTML);
      var prevSig=lastFreshHtml[id];
      if(animateSync && prevSig!==undefined && prevSig!==freshSig && existingIds[id]){
        fresh.classList.add('siberad-card-updated');
      }
      lastFreshHtml[id]=freshSig;
    });

    container.innerHTML='';
    freshCards.forEach(function(fresh){ container.appendChild(fresh); });
    return newCount;
  }

  function poll(gridMasuk,gridArsip,emptyMasuk,emptyArsip){
    if(busy)return;busy=true;
    fetch(endpoint+'?_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.ok?r.json():null;})
      .then(function(data){
        if(!data)return;
        var newMasuk=0;
        if(typeof data.masuk_items_html==='string') newMasuk=syncGrid(gridMasuk,data.masuk_items_html,emptyMasuk);
        if(typeof data.arsip_items_html==='string') syncGrid(gridArsip,data.arsip_items_html,emptyArsip);
        if(animateSync && newMasuk>0 && window.siberadShowToast){
          window.siberadShowToast('success', newMasuk===1?'Ada 1 kendala baru masuk dari Kasansi.':'Ada '+newMasuk+' kendala baru masuk dari Kasansi.');
        }
        animateSync=true;
      }).catch(function(){}).finally(function(){busy=false;});
  }

  function start(){
    var gridMasuk=document.getElementById('kcard-grid-tembusan');
    if(!gridMasuk)return;
    var gridArsip=document.getElementById('kcard-grid-tembusan-arsip');
    var emptyMasuk=emptyMarkupOf(gridMasuk);
    var emptyArsip=gridArsip?emptyMarkupOf(gridArsip):'';
    poll(gridMasuk,gridArsip,emptyMasuk,emptyArsip);
    window.setInterval(function(){poll(gridMasuk,gridArsip,emptyMasuk,emptyArsip);},3000);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
</script>
