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

  // Kenapa TIDAK dibandingkan lewat DOM (baik mentah maupun "dibersihkan"
  // dulu pakai clone+strip class/style/atribut *bound*): elemen kcard yang
  // sudah sempat dirender di halaman bisa kena tempelan macam-macam dari
  // script LAIN yang tidak kita tahu semuanya di sini (mis. lightbox
  // lampiran, listener openReportDetail yang menandai tombol, dsb) --
  // daftar strip manual gampang ketinggalan satu atribut baru dan begitu
  // itu terjadi, card itu dianggap "beda terus" dibanding HTML asli server
  // -> ikut di-replace & kedip tiap 3 detik tanpa henti, walau datanya
  // tidak pernah berubah. Solusinya samain persis dengan pola yang sudah
  // terbukti di syncExistingRequests() (log-aktivitas-realtime.blade.php):
  // JANGAN pernah baca dari DOM yang sedang tampil sama sekali -- simpan
  // HTML MURNI dari server per siklus fetch ke Map `lastFreshHtml`, lalu
  // bandingkan fresh-vs-fresh (siklus sekarang vs siklus sebelumnya).
  // Dengan begitu, mau ada script lain yang nempelin apa pun ke DOM, itu
  // tidak pernah ikut terbaca / tidak pernah memengaruhi hasil banding.
  var lastFreshHtml={};

  function normalize(html){
    return html.replace(/>\s+</g,'><').trim();
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
      var freshSig=normalize(fresh.outerHTML);
      var existing=container.querySelector('['+idAttr+'="'+id+'"]');
      if(existing){
        var prevSig=lastFreshHtml[id];
        // undefined = ID ini baru pertama kali kelihatan di siklus fresh
        // (mis. baru saja disisipkan sebagai card baru di siklus
        // sebelumnya) -- jangan dianggap "berubah" & jangan ikut kena
        // animasi glow, cukup catat baseline-nya dulu.
        if(prevSig!==undefined && prevSig!==freshSig){
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
      lastFreshHtml[id]=freshSig;
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
