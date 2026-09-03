<style>
@keyframes siberadKendalaCardUpdate{0%{outline-color:var(--gold-bright);box-shadow:0 0 0 3px var(--gold-dim)}100%{outline-color:transparent;box-shadow:none}}
.siberad-card-updated{animation:siberadKendalaCardUpdate 1.4s ease;outline:2px solid transparent}
@keyframes siberadKendalaCardIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.siberad-card-in{animation:siberadKendalaCardIn .35s ease}
</style>
<script>
(function(){
  // Polling untuk update status kendala yang sudah terkirim (Kasansi side).
  // Begitu Danpus konfirmasi, card pindah dari #kcard-grid-terkirim ke
  // #kcard-grid-arsip-kasansi berdasarkan snapshot terkirim_items_html &
  // arsip_items_html dari LaporanKendalaController::realtime().
  var endpoint='{{ route('laporan-kendala.realtime') }}';
  var idAttr='data-kendala-id';
  var busy=false;

  function emptyMarkupOf(container){
    var first=container.firstElementChild;
    return (first && !first.hasAttribute(idAttr)) ? first.outerHTML : '';
  }

  // PENTING: buang class animasi transient (siberad-card-updated /
  // siberad-card-in) sebelum membandingkan outerHTML. Tanpa ini, card yang
  // baru saja di-update masih membawa class siberad-card-updated di DOM,
  // sedangkan HTML segar dari server TIDAK PERNAH punya class itu (class
  // ini murni ditambahkan di sisi client). Akibatnya outerHTML dua-duanya
  // selalu dianggap "berbeda" walau datanya sama persis -> card dianggap
  // berubah, class ditambah lagi, kedip lagi, setiap polling (3 detik)
  // tanpa henti. Ini penyebab bug "kartu kedip kuning terus" di Kirim
  // Kendala & Arsip Kendala.
  function norm(html){
    return html
      .replace(/>\s+</g,'><')
      .replace(/\s*\b(siberad-card-updated|siberad-card-in)\b/g,'')
      .trim();
  }

  function syncGrid(container, freshHtml, emptyMarkup){
    if(!container) return;
    var currentCards=Array.prototype.slice.call(container.querySelectorAll('['+idAttr+']'));

    var temp=document.createElement('div');
    temp.innerHTML=freshHtml;
    var freshCards=Array.prototype.slice.call(temp.children);

    if(freshCards.length===0){
      if(currentCards.length>0) container.innerHTML=emptyMarkup||'';
      return;
    }

    var freshIds=freshCards.map(function(c){return c.getAttribute(idAttr);});

    // Hapus card yang sudah tidak ada (pindah ke arsip atau dihapus)
    currentCards.forEach(function(card){
      if(freshIds.indexOf(card.getAttribute(idAttr))===-1) card.remove();
    });

    // Buang empty-state jika sekarang ada data
    var placeholder=container.querySelector(':not(['+idAttr+'])');
    if(placeholder && freshCards.length>0) placeholder.remove();

    // Update card yang berubah + sisipkan card baru
    var prevEl=null;
    freshCards.forEach(function(fresh){
      var id=fresh.getAttribute(idAttr);
      var existing=container.querySelector('['+idAttr+'="'+id+'"]');
      if(existing){
        if(norm(existing.outerHTML)!==norm(fresh.outerHTML)){
          fresh.classList.add('siberad-card-updated');
          existing.replaceWith(fresh);
        }
        prevEl=container.querySelector('['+idAttr+'="'+id+'"]');
      }else{
        fresh.classList.add('siberad-card-in');
        if(prevEl && prevEl.nextSibling) container.insertBefore(fresh,prevEl.nextSibling);
        else if(prevEl) container.appendChild(fresh);
        else container.insertBefore(fresh,container.firstChild);
        prevEl=fresh;
      }
    });
  }

  function poll(gridTerkirim,gridArsip,emptyTerkirim,emptyArsip){
    if(busy)return;busy=true;
    fetch(endpoint+'?_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.ok?r.json():null;})
      .then(function(data){
        if(!data)return;
        if(typeof data.terkirim_items_html==='string') syncGrid(gridTerkirim,data.terkirim_items_html,emptyTerkirim);
        if(typeof data.arsip_items_html==='string') syncGrid(gridArsip,data.arsip_items_html,emptyArsip);
      }).catch(function(){}).finally(function(){busy=false;});
  }

  function start(){
    var gridTerkirim=document.getElementById('kcard-grid-terkirim');
    if(!gridTerkirim)return;
    var gridArsip=document.getElementById('kcard-grid-arsip-kasansi');
    var emptyTerkirim=emptyMarkupOf(gridTerkirim);
    var emptyArsip=gridArsip?emptyMarkupOf(gridArsip):'';
    poll(gridTerkirim,gridArsip,emptyTerkirim,emptyArsip);
    window.setInterval(function(){poll(gridTerkirim,gridArsip,emptyTerkirim,emptyArsip);},3000);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
</script>
