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

  // Kenapa TIDAK cukup cuma membandingkan outerHTML mentah (sekalipun sudah
  // dibuang whitespace-nya): render pertama halaman (Blade, langsung di
  // laporan-role.blade.php) dan render endpoint realtime() ini SAMA-SAMA
  // pakai partial kendala-terkirim-row.blade.php, tapi begitu card sempat
  // di-replace sekali oleh polling ini, DOM-nya jadi punya sisa atribut/
  // class yang TIDAK PERNAH ada di HTML dari server (class animasi
  // siberad-card-updated/siberad-card-in yang cuma ditambah di sisi
  // client). Kalau dibandingkan mentah-mentah, itu bikin server dianggap
  // "berbeda" terus -> SEMUA card ikut di-replace & kedip lagi tiap
  // polling (3 detik), tanpa henti. Pola yang sama persis sudah pernah
  // dibereskan untuk kartu Permintaan Laporan lewat cardSig() di
  // laporan-role-realtime-sync.blade.php -- signature() di bawah ini
  // meniru pendekatan itu: clone node, buang SEMUA hal yang transient
  // (class animasi, style inline, atribut *bound* yang ditempel JS lain),
  // baru dibandingkan.
  function signature(el){
    var c=el.cloneNode(true);
    c.classList.remove('siberad-card-updated','siberad-card-in');
    if(c.getAttribute('class')==='') c.removeAttribute('class');
    c.removeAttribute('style');
    Array.prototype.slice.call(c.querySelectorAll('[style]')).forEach(function(n){n.removeAttribute('style');});
    Array.prototype.slice.call(c.querySelectorAll('*')).forEach(function(n){
      Array.prototype.slice.call(n.attributes||[]).forEach(function(a){
        if(/bound/i.test(a.name)) n.removeAttribute(a.name);
      });
    });
    return c.outerHTML.replace(/>\s+</g,'><').trim();
  }

  // Poll PERTAMA setelah halaman dibuka dipakai buat "menyamakan" baseline
  // TANPA animasi -- kalau render statis awal (Blade langsung) beda format
  // kecil (bukan perubahan data beneran) dari render endpoint realtime ini,
  // jangan sampai keliru kelihatan kayak SEMUA card "berubah" & ikut kedip
  // bersamaan pas dashboard baru dibuka. Sama seperti pola animateSync di
  // laporan-role-realtime-sync.blade.php.
  var animateSync=false;

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
        if(signature(existing)!==signature(fresh)){
          if(animateSync) fresh.classList.add('siberad-card-updated');
          existing.replaceWith(fresh);
        }
        prevEl=container.querySelector('['+idAttr+'="'+id+'"]');
      }else{
        if(animateSync) fresh.classList.add('siberad-card-in');
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
        animateSync=true;
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
