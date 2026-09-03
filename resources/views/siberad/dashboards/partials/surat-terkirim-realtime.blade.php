<script>
(function(){
  // Surat BARU yang dikirim sendiri sudah langsung kelihatan (form native
  // submit -> redirect -> halaman fresh) -- polling ini cuma buat surat yang
  // SUDAH terkirim dan baru saja dikonfirmasi penerima selagi Kasansi masih
  // buka tab ini. Begitu status jadi Dikonfirmasi, kartu harus pindah dari
  // tab "Kirim Surat" ke tab "Arsip Surat" tanpa reload -- pola sinkron sama
  // seperti kendala-terkirim-realtime.blade.php.
  //
  // Kirim Surat sekarang kartu (grid <div>), Arsip Surat masih tabel
  // (<tbody>) -- syncContainer di bawah generik buat dua-duanya, parsing
  // fresh HTML-nya beda konteks tergantung useTable (lihat catatan di norm()).
  var endpoint='{{ route('laporan-surat.realtime') }}';
  var idAttr='data-surat-id';
  var busy=false;

  function emptyMarkupOf(container){
    var first=container.firstElementChild;
    return (first && !first.hasAttribute(idAttr)) ? first.outerHTML : '';
  }

  // Baris partial (surat-terkirim-row.blade.php dulu <tr>, sekarang kartu
  // <div>) ditulis multi-baris. Kalau di-parsing ke wadah yang berdiri
  // sendiri dengan konteks BEDA dari tempat aslinya dirender, browser bisa
  // memperlakukan whitespace antar-tag beda -- akibatnya item dianggap
  // "berubah" terus tiap poll walau datanya sama persis, jadi kelihatan
  // kedip-kedip. Makanya parsing tabel lewat <table><tbody> (biar identik
  // sama tabel Arsip Surat aslinya), sedangkan kartu cukup lewat <div> biasa
  // (sama kayak wadah aslinya, #suratTerkirimGrid). Dibandingkan dalam
  // bentuk whitespace-dirapikan juga sebagai jaga-jaga ganda.
  function norm(html){ return html.replace(/>\s+</g,'><').trim(); }

  function parseFresh(freshHtml,useTable){
    var temp=document.createElement(useTable?'table':'div');
    temp.innerHTML=useTable?('<tbody>'+freshHtml+'</tbody>'):freshHtml;
    var host=useTable?temp.querySelector('tbody'):temp;
    return Array.prototype.slice.call(host.children);
  }

  // Cache per container: id -> HTML fresh TERAKHIR yang beneran dari server
  // (bukan dari DOM live). Perbandingan "berubah apa nggak" WAJIB pakai
  // fresh-vs-cached-fresh ini, JANGAN fresh-vs-live-DOM (existing.outerHTML)
  // -- script LAIN di halaman ini (mis. pencarian/sort kartu Kirim Surat)
  // ikut nyentuh DOM live yang sama (style.display, urutan posisi), jadi
  // outerHTML live-nya gampang "kelihatan beda" dari fresh walau datanya
  // sebenarnya sama persis -- itu yang bikin kartu keganti ulang (kena
  // animasi .siberad-row-updated) tiap poll = kedip-kedip terus-terusan.
  // Pola sama persis kayak gotcha yang udah pernah kejadian di tabel lain.
  var freshCache=new WeakMap();

  function syncContainer(container,freshHtml,emptyMarkup,useTable){
    if(!container)return;
    var currentItems=Array.prototype.slice.call(container.querySelectorAll('['+idAttr+']'));
    var freshItems=parseFresh(freshHtml,useTable);
    var cache=freshCache.get(container);
    if(!cache){ cache={}; freshCache.set(container,cache); }

    if(freshItems.length===0){
      if(currentItems.length>0){ container.innerHTML=emptyMarkup||''; freshCache.set(container,{}); }
      return;
    }

    var freshIds=freshItems.map(function(f){return f.getAttribute(idAttr);});

    currentItems.forEach(function(item){
      var id=item.getAttribute(idAttr);
      if(freshIds.indexOf(id)===-1){ item.remove(); delete cache[id]; }
    });

    var placeholder=Array.prototype.slice.call(container.children).find(function(el){return !el.hasAttribute(idAttr);});
    if(placeholder) placeholder.remove();

    var prevEl=null;
    freshItems.forEach(function(fresh){
      var id=fresh.getAttribute(idAttr);
      var freshNorm=norm(fresh.outerHTML);
      var existing=container.querySelector('['+idAttr+'="'+id+'"]');
      if(existing){
        if(cache[id]!==freshNorm){
          fresh.classList.add('siberad-row-updated');
          existing.replaceWith(fresh);
        }
        // Cache-nya tetap di-update walau kontennya sama (no-op harmless)
        // supaya perbandingan berikutnya tetap akurat.
        cache[id]=freshNorm;
        prevEl=container.querySelector('['+idAttr+'="'+id+'"]');
      }else{
        fresh.classList.add('siberad-row-updated');
        cache[id]=freshNorm;
        if(prevEl && prevEl.nextSibling) container.insertBefore(fresh,prevEl.nextSibling);
        else if(prevEl) container.appendChild(fresh);
        else container.insertBefore(fresh,container.firstChild);
        prevEl=fresh;
      }
    });
  }

  function poll(terkirimEl,arsipEl,emptyTerkirim,emptyArsip){
    if(busy)return;busy=true;
    fetch(endpoint+'?_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.ok?r.json():null;})
      .then(function(data){
        if(!data)return;
        if(typeof data.terkirim_items_html==='string') syncContainer(terkirimEl,data.terkirim_items_html,emptyTerkirim,false);
        if(typeof data.arsip_items_html==='string') syncContainer(arsipEl,data.arsip_items_html,emptyArsip,true);
      }).catch(function(){}).finally(function(){busy=false;});
  }

  // Isi cache dari kartu/baris yang UDAH dirender server pas page load --
  // sebelum poll pertama sempat jalan. Tanpa ini, cache kosong bikin poll
  // pertama nganggep SEMUA item "baru berubah" (undefined !== freshNorm),
  // jadi seluruh grid kena animasi .siberad-row-updated sekali begitu
  // halaman baru dibuka -- kelihatan kedip sesaat pas refresh.
  function seedCache(container){
    if(!container)return;
    var cache={};
    Array.prototype.slice.call(container.querySelectorAll('['+idAttr+']')).forEach(function(item){
      cache[item.getAttribute(idAttr)]=norm(item.outerHTML);
    });
    freshCache.set(container,cache);
  }

  function start(){
    var terkirimEl=document.getElementById('suratTerkirimGrid');
    if(!terkirimEl)return;
    var arsipEl=document.getElementById('suratArsipBody');
    var emptyTerkirim=emptyMarkupOf(terkirimEl);
    var emptyArsip=arsipEl?emptyMarkupOf(arsipEl):'';
    seedCache(terkirimEl);
    seedCache(arsipEl);
    poll(terkirimEl,arsipEl,emptyTerkirim,emptyArsip);
    window.setInterval(function(){poll(terkirimEl,arsipEl,emptyTerkirim,emptyArsip);},3000);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
</script>
