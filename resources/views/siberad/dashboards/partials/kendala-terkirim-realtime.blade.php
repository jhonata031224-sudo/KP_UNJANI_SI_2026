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

    // Sanity-check: kalau ada elemen di freshCards yang tidak punya
    // data-kendala-id sama sekali, berarti server kirim HTML malformed
    // (mis. partial error, atau wrapper div ikut terparsing). Jangan
    // lanjut -- card yang sudah ada di DOM jangan sampai ikut dihapus
    // hanya karena satu siklus polling hasilnya tidak valid.
    if(freshIds.some(function(id){return !id;})) return;

    // Hapus card yang sudah tidak ada (pindah ke arsip atau dihapus)
    currentCards.forEach(function(card){
      if(freshIds.indexOf(card.getAttribute(idAttr))===-1) card.remove();
    });

    // Buang empty-state jika sekarang ada data.
    // PENTING: harus dicari cuma di ANAK LANGSUNG container, bukan lewat
    // container.querySelector(':not([idAttr])') -- selector itu mencari ke
    // SEMUA turunan (bukan cuma anak langsung), dan karena isi tiap kartu
    // (.kcard-header, .kcard-body, .kcard-tembusan, dst) juga sama-sama
    // tidak punya atribut data-kendala-id (cuma .kcard paling luar yang
    // punya), querySelector itu jatuhnya nemu elemen PALING ATAS di dalam
    // kartu PERTAMA, bukan placeholder-nya -- lalu elemen itu ikut
    // dihapus. Tiap polling (3 detik) elemen "tanpa atribut" paling atas
    // berikutnya berpindah ke elemen selanjutnya di kartu yang sama, jadi
    // kartu pertama kepotong isinya sedikit demi sedikit dari atas ke
    // bawah tiap siklus polling. Fix: cek anak langsung saja.
    var placeholder=null;
    Array.prototype.slice.call(container.children).some(function(child){
      if(!child.hasAttribute(idAttr)){ placeholder=child; return true; }
      return false;
    });
    if(placeholder && freshCards.length>0) placeholder.remove();

    // Kumpulkan ID card yang saat ini sudah ada di DOM (sebelum di-replace),
    // untuk mendeteksi mana yang benar-benar "baru" dan perlu animasi masuk.
    var existingIds={};
    currentCards.forEach(function(c){ existingIds[c.getAttribute(idAttr)]=true; });

    // Tandai card yang benar-benar baru (belum ada di DOM sebelumnya)
    // dengan kelas animasi SEBELUM di-insert, supaya animasi jalan begitu
    // card tampil di layar.
    if(animateSync){
      freshCards.forEach(function(fresh){
        var id=fresh.getAttribute(idAttr);
        if(!existingIds[id]) fresh.classList.add('siberad-card-in');
      });
    }

    // Deteksi card yang datanya berubah (status, catatan, dsb) lalu tandai
    // dengan animasi glow -- dilakukan sebelum innerHTML di-replace.
    freshCards.forEach(function(fresh){
      var id=fresh.getAttribute(idAttr);
      var freshSig=normalize(fresh.outerHTML);
      var prevSig=lastFreshHtml[id];
      if(animateSync && prevSig!==undefined && prevSig!==freshSig && existingIds[id]){
        fresh.classList.add('siberad-card-updated');
      }
      lastFreshHtml[id]=freshSig;
    });

    // Ganti seluruh isi container sekaligus dengan freshHtml dari server.
    // Server sudah mengirim data dalam urutan DESC (.latest()) -- terbaru
    // di index 0 = paling kiri di grid. Dengan mengganti innerHTML sekaligus,
    // urutan selalu 100% sesuai server tanpa bisa salah oleh logika insert
    // manual. Ini jauh lebih aman daripada insert card satu-satu dengan
    // prevEl yang rawan bug posisi ketika ada campuran existing + card baru.
    container.innerHTML='';
    freshCards.forEach(function(fresh){ container.appendChild(fresh); });
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
