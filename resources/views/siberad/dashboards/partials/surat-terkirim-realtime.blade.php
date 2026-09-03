<script>
(function(){
  // Surat BARU yang dikirim sendiri sudah langsung kelihatan (form native
  // submit -> redirect -> halaman fresh) -- polling ini cuma buat surat yang
  // SUDAH terkirim dan baru saja dikonfirmasi penerima selagi Kasansi masih
  // buka tab ini. Begitu status jadi Dikonfirmasi, kartu harus pindah dari
  // tab "Kirim Surat" (atau "Surat Masuk") ke tab "Arsip Surat" tanpa
  // reload -- pola sinkron sama seperti kendala-terkirim-realtime.blade.php.
  //
  // Kirim Surat, Surat Masuk, DAN Arsip Surat SEKARANG SEMUANYA kartu (grid
  // <div>, #suratTerkirimGrid/#suratMasukGrid/#suratArsipGrid) -- gak ada
  // lagi tabel <table>/<tbody> di alur Surat sama sekali, jadi syncContainer
  // di bawah gak butuh percabangan useTable lagi (versi lama sempat ada,
  // sebelum Arsip Surat ikut dirombak jadi kartu).
  //
  // Ketiga grid dapet snapshot PENUH dari backend
  // (LaporanSuratController::realtime()) tiap poll, di-diff di sini --
  // item yang gak ada lagi di snapshot otomatis di-treat sebagai "hilang"
  // (lihat removeCard()), item baru yang belum ada di DOM di-treat sebagai
  // "kartu baru" (dapet animasi .dcard-enter).
  //
  // Dipakai bareng oleh dashboard Satuan & Pimpinan (surat-*-shell.blade.php),
  // karena id container (#suratTerkirimGrid/#suratMasukGrid/#suratArsipGrid)
  // sama persis di kedua dashboard.
  //
  // Animasi kartu (masuk/keluar/geser) & toast notifikasi SENGAJA niru
  // persis pola permintaan-laporan-realtime.blade.php (insertItems/
  // pruneActiveList) biar kerasa "1 sistem" -- .dcard-enter buat kartu baru
  // (zoom-in, sudah global lewat permintaan-laporan-deadline-styles.blade.php
  // yang di-include kedua dashboard), FLIP (flipCapture/flipPlay) buat kartu
  // lain yang kegeser posisinya, .siberad-card-leaving buat kartu yang
  // hilang total (didefinisikan sendiri di surat-card-styles.blade.php,
  // BUKAN numpang punya laporan-role-realtime-sync.blade.php yang cuma
  // di-include di shell Satuan -- Pimpinan butuh yang sama juga). Toast
  // "Surat baru masuk." SENGAJA cuma buat Surat Masuk (info baru yang
  // pantas diberitahukan) -- Kirim Surat/Arsip Surat gak ditoast begitu ada
  // perubahan realtime, niru konvensi Kirim Surat yang emang dari awal gak
  // pernah ditoast juga.
  var endpoint='{{ route('laporan-surat.realtime') }}';
  var idAttr='data-surat-id';
  var busy=false;

  function emptyMarkupOf(container){
    var first=container.firstElementChild;
    return (first && !first.hasAttribute(idAttr)) ? first.outerHTML : '';
  }

  function reduceMotion(){
    return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  // FLIP (First-Last-Invert-Play) -- kartu LAMA yang kegeser posisinya
  // gara-gara kartu baru disisipkan/dihapus ikut animasi geser mulus, bukan
  // "loncat" instan ke posisi barunya.
  function flipCapture(container){
    if(reduceMotion())return null;
    var cards=Array.prototype.slice.call(container.querySelectorAll(':scope > .surat-file-card'));
    var rects=new Map();
    cards.forEach(function(c){rects.set(c,c.getBoundingClientRect());});
    return rects;
  }
  function flipPlay(rects){
    if(!rects)return;
    rects.forEach(function(prev,card){
      if(!card.isConnected)return;
      var next=card.getBoundingClientRect();
      var dx=prev.left-next.left,dy=prev.top-next.top;
      if(Math.abs(dx)<1&&Math.abs(dy)<1)return;
      card.style.transition='none';
      card.style.transform='translate('+dx+'px,'+dy+'px)';
      card.getBoundingClientRect();
      // dua rAF -- state "dari" wajib kepaint dulu sebelum transisi ke "ke"
      // dipicu, biar geser-nya nggak ke-skip/kepotong di akhir.
      requestAnimationFrame(function(){requestAnimationFrame(function(){
        card.style.transition='transform .58s cubic-bezier(.16,1,.3,1)';
        card.style.transform='';
      });});
      card.addEventListener('transitionend',function handler(e){if(e.propertyName!=='transform')return;card.style.transition='';card.removeEventListener('transitionend',handler);});
    });
  }

  // Kartu yang hilang total (mis. Kirim Surat pindah ke Arsip Surat begitu
  // dikonfirmasi) di-fade-out dulu (.siberad-card-leaving) sebelum BENERAN
  // di-remove dari DOM -- JANGAN item.remove() mendadak, itu yang bikin
  // "kaku". Guard dataset.leaving biar poll berikutnya nggak dobel proses.
  function removeCard(item){
    if(reduceMotion()){ item.remove(); return; }
    if(item.dataset.leaving==='1')return;
    item.dataset.leaving='1';
    item.classList.add('siberad-card-leaving');
    var fin=function(){ window.clearTimeout(t); item.remove(); };
    var t=window.setTimeout(fin,340);
    item.addEventListener('animationend',fin,{once:true});
  }

  // Baris partial (surat-terkirim-row/surat-masuk-row/surat-arsip-row.blade.php,
  // semuanya kartu <div>) ditulis multi-baris. Kalau di-parsing ke wadah
  // yang berdiri sendiri dengan konteks BEDA dari tempat aslinya dirender,
  // browser bisa memperlakukan whitespace antar-tag beda -- akibatnya item
  // dianggap "berubah" terus tiap poll walau datanya sama persis, jadi
  // kelihatan kedip-kedip. Dibandingkan dalam bentuk whitespace-dirapikan
  // juga sebagai jaga-jaga ganda.
  function norm(html){ return html.replace(/>\s+</g,'><').trim(); }

  function parseFresh(freshHtml){
    var temp=document.createElement('div');
    temp.innerHTML=freshHtml;
    return Array.prototype.slice.call(temp.children);
  }

  // Cache per container: id -> HTML fresh TERAKHIR yang beneran dari server
  // (bukan dari DOM live). Perbandingan "berubah apa nggak" WAJIB pakai
  // fresh-vs-cached-fresh ini, JANGAN fresh-vs-live-DOM (existing.outerHTML)
  // -- script LAIN di halaman ini (mis. pencarian/sort kartu) ikut nyentuh
  // DOM live yang sama (style.display, urutan posisi), jadi outerHTML
  // live-nya gampang "kelihatan beda" dari fresh walau datanya sebenarnya
  // sama persis -- itu yang bikin kartu keganti ulang (kena animasi
  // .siberad-row-updated) tiap poll = kedip-kedip terus-terusan. Pola sama
  // persis kayak gotcha yang udah pernah kejadian di tabel lain.
  var freshCache=new WeakMap();

  // Balikin JUMLAH kartu yang BENERAN baru disisipkan (bukan update-in-place)
  // -- dipakai poll() buat mutusin kapan toast "Surat baru masuk." pantas
  // muncul (cuma buat #suratMasukGrid, dan cuma kalau bukan poll pertama).
  function syncContainer(container,freshHtml,emptyMarkup){
    if(!container)return 0;
    var currentItems=Array.prototype.slice.call(container.querySelectorAll('['+idAttr+']'));
    var freshItems=parseFresh(freshHtml);
    var cache=freshCache.get(container);
    if(!cache){ cache={}; freshCache.set(container,cache); }

    if(freshItems.length===0){
      if(currentItems.length>0){
        currentItems.forEach(function(item){removeCard(item);});
        window.setTimeout(function(){
          if(!container.querySelector('['+idAttr+']')) container.innerHTML=emptyMarkup||'';
        },380);
        freshCache.set(container,{});
      }
      return 0;
    }

    var freshIds=freshItems.map(function(f){return f.getAttribute(idAttr);});

    currentItems.forEach(function(item){
      var id=item.getAttribute(idAttr);
      if(freshIds.indexOf(id)===-1){
        delete cache[id];
        removeCard(item);
      }
    });

    var placeholder=Array.prototype.slice.call(container.children).find(function(el){return !el.hasAttribute(idAttr);});
    if(placeholder) placeholder.remove();

    var flipRects=flipCapture(container);
    var inserted=0;
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
        cache[id]=freshNorm;
        inserted++;
        fresh.classList.add('dcard-enter');
        fresh.addEventListener('animationend',function handler(){fresh.classList.remove('dcard-enter');fresh.removeEventListener('animationend',handler);});
        if(prevEl && prevEl.nextSibling) container.insertBefore(fresh,prevEl.nextSibling);
        else if(prevEl) container.appendChild(fresh);
        else container.insertBefore(fresh,container.firstChild);
        prevEl=fresh;
      }
    });
    flipPlay(flipRects);
    return inserted;
  }

  // initialPoll: sama kayak pola permintaan-laporan-realtime.blade.php --
  // JANGAN toast-in surat yang sebenarnya udah dirender server pas page
  // load (poll pertama seed dulu, gak toast walau "inserted" > 0).
  var initialPoll=true;

  function poll(terkirimEl,arsipEl,masukEl,emptyTerkirim,emptyArsip,emptyMasuk){
    if(busy)return;busy=true;
    fetch(endpoint+'?_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.ok?r.json():null;})
      .then(function(data){
        if(!data)return;
        if(typeof data.terkirim_items_html==='string') syncContainer(terkirimEl,data.terkirim_items_html,emptyTerkirim);
        if(typeof data.arsip_items_html==='string') syncContainer(arsipEl,data.arsip_items_html,emptyArsip);
        var masukInserted=0;
        if(typeof data.masuk_items_html==='string') masukInserted=syncContainer(masukEl,data.masuk_items_html,emptyMasuk);
        if(!initialPoll && masukInserted>0 && window.siberadShowToast) window.siberadShowToast('success','Surat baru masuk.');
        initialPoll=false;
        window.siberadRefreshSuratDetailIfOpen&&window.siberadRefreshSuratDetailIfOpen();
      }).catch(function(){}).finally(function(){busy=false;});
  }

  // Isi cache dari kartu yang UDAH dirender server pas page load -- sebelum
  // poll pertama sempat jalan. Tanpa ini, cache kosong bikin poll pertama
  // nganggep SEMUA item "baru berubah" (undefined !== freshNorm), jadi
  // seluruh grid kena animasi .siberad-row-updated sekali begitu halaman
  // baru dibuka -- kelihatan kedip sesaat pas refresh. Karena item yang
  // server-rendered SUDAH ada di DOM, syncContainer juga gak bakal
  // nganggep mereka "inserted" (existing check duluan), jadi poll pertama
  // juga otomatis gak nge-toast apa-apa walau initialPoll dilewati.
  function seedCache(container){
    if(!container)return;
    var cache={};
    Array.prototype.slice.call(container.querySelectorAll('['+idAttr+']')).forEach(function(item){
      cache[item.getAttribute(idAttr)]=norm(item.outerHTML);
    });
    freshCache.set(container,cache);
  }

  // emptyMarkupOf() cuma nangkep placeholder "belum ada surat" kalau
  // grid-nya UDAH kosong pas halaman dimuat -- kalau awalnya masih ada isi
  // lalu item TERAKHIRnya baru hilang belakangan lewat polling (mis. surat
  // dikonfirmasi selagi tab ini masih dibuka), gak ada placeholder yang
  // ke-capture sama sekali sehingga grid jadi kosong blank tanpa pesan
  // apa-apa. Fallback di bawah jaga-jaga skenario itu.
  var fallbackEmptyTerkirim='<div class="empty-state"><div class="empty-state-title">Tidak ada surat yang menunggu konfirmasi</div><div class="empty-state-sub">Semua surat yang terkirim sudah dikonfirmasi penerima dan masuk ke Arsip Surat.</div></div>';
  var fallbackEmptyArsip='<div class="empty-state"><div class="empty-state-title">Belum ada surat yang diarsipkan</div><div class="empty-state-sub">Surat pindah ke sini setelah dikonfirmasi.</div></div>';
  var fallbackEmptyMasuk='<div class="empty-state"><div class="empty-state-title">Belum ada surat masuk</div><div class="empty-state-sub">Surat yang ditujukan ke satuan ini akan muncul di sini.</div></div>';

  function start(){
    var terkirimEl=document.getElementById('suratTerkirimGrid');
    var masukEl=document.getElementById('suratMasukGrid');
    var arsipEl=document.getElementById('suratArsipGrid');
    if(!terkirimEl&&!masukEl&&!arsipEl)return;
    var emptyTerkirim=terkirimEl?(emptyMarkupOf(terkirimEl)||fallbackEmptyTerkirim):'';
    var emptyArsip=arsipEl?(emptyMarkupOf(arsipEl)||fallbackEmptyArsip):'';
    var emptyMasuk=masukEl?(emptyMarkupOf(masukEl)||fallbackEmptyMasuk):'';
    seedCache(terkirimEl);
    seedCache(arsipEl);
    seedCache(masukEl);
    poll(terkirimEl,arsipEl,masukEl,emptyTerkirim,emptyArsip,emptyMasuk);
    window.setInterval(function(){poll(terkirimEl,arsipEl,masukEl,emptyTerkirim,emptyArsip,emptyMasuk);},3000);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
</script>
