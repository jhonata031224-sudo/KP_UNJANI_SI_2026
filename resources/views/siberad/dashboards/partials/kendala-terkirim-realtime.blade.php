<style>
@keyframes siberadKendalaRowUpdate{0%{background:var(--gold-dim)}100%{background:transparent}}
.siberad-row-updated{animation:siberadKendalaRowUpdate 1.2s ease}
</style>
<script>
(function(){
  // Kendala BARU yang Kasansi kirim sendiri sudah langsung kelihatan (form
  // native submit -> redirect -> halaman fresh) -- polling ini cuma buat
  // status kendala yang SUDAH terkirim (Ditindaklanjuti/Ditolak/Selesai oleh
  // Danpus/Wadan selagi Kasansi masih buka tab ini), pola sama seperti
  // syncRequestList() di laporan-role-realtime-sync.blade.php.
  //
  // Begitu Danpus menekan "Konfirmasi & Arsipkan", status kendala jadi
  // Dikonfirmasi -- baris itu HARUS pindah dari tab "Kirim Kendala" ke tab
  // "Arsip Kendala" tanpa reload, bukan cuma update teks status di tempat.
  // syncTable() di bawah menangani pindah/tambah/hapus baris di kedua tbody
  // sekaligus berdasarkan snapshot terkirim_items_html & arsip_items_html
  // dari LaporanKendalaController::realtime().
  var endpoint='{{ route('laporan-kendala.realtime') }}';
  var idAttr='data-kendala-id';
  var busy=false;

  function emptyMarkupOf(tbody){
    var first=tbody.firstElementChild;
    return (first && !first.hasAttribute(idAttr)) ? first.outerHTML : '';
  }

  // Parsing lewat <table><tbody> (bukan <tbody> berdiri sendiri) supaya
  // konteks parsing whitespace-nya identik dengan tabel aslinya di halaman
  // -- lihat catatan sama di surat-terkirim-realtime.blade.php. Dibandingkan
  // dalam bentuk whitespace-dirapikan sebagai jaga-jaga ganda.
  function norm(html){ return html.replace(/>\s+</g,'><').trim(); }

  function syncTable(tbody,freshHtml,emptyMarkup){
    if(!tbody)return;
    var currentRows=Array.prototype.slice.call(tbody.querySelectorAll('['+idAttr+']'));

    var temp=document.createElement('table');temp.innerHTML='<tbody>'+freshHtml+'</tbody>';
    var freshRows=Array.prototype.slice.call(temp.querySelector('tbody').children);

    if(freshRows.length===0){
      // Kalau memang sudah tidak ada data untuk tabel ini, kosongkan (kembali
      // ke tampilan empty-state) -- tapi jangan sentuh apa pun kalau memang
      // dari awal sudah kosong, supaya tidak mengganggu placeholder.
      if(currentRows.length>0) tbody.innerHTML=emptyMarkup||'';
      return;
    }

    var freshIds=freshRows.map(function(f){return f.getAttribute(idAttr);});

    // Baris lama yang sudah tidak ada lagi di snapshot ini (pindah ke tabel
    // lain, mis. Kirim Kendala -> Arsip Kendala, atau dihapus).
    currentRows.forEach(function(row){
      if(freshIds.indexOf(row.getAttribute(idAttr))===-1) row.remove();
    });

    // Buang placeholder empty-state kalau sekarang sudah ada data masuk.
    var placeholder=tbody.querySelector('tr:not(['+idAttr+'])');
    if(placeholder) placeholder.remove();

    // Update baris yang berubah + sisipkan baris baru, jaga urutan terbaru dulu.
    var prevEl=null;
    freshRows.forEach(function(fresh){
      var id=fresh.getAttribute(idAttr);
      var existing=tbody.querySelector('['+idAttr+'="'+id+'"]');
      if(existing){
        if(norm(existing.outerHTML)!==norm(fresh.outerHTML)){
          fresh.classList.add('siberad-row-updated');
          existing.replaceWith(fresh);
        }
        prevEl=tbody.querySelector('['+idAttr+'="'+id+'"]');
      }else{
        fresh.classList.add('siberad-row-updated');
        if(prevEl && prevEl.nextSibling) tbody.insertBefore(fresh,prevEl.nextSibling);
        else if(prevEl) tbody.appendChild(fresh);
        else tbody.insertBefore(fresh,tbody.firstChild);
        prevEl=fresh;
      }
    });
  }

  function poll(tbodyTerkirim,tbodyArsip,emptyTerkirim,emptyArsip){
    if(busy)return;busy=true;
    fetch(endpoint+'?_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.ok?r.json():null;})
      .then(function(data){
        if(!data)return;
        if(typeof data.terkirim_items_html==='string') syncTable(tbodyTerkirim,data.terkirim_items_html,emptyTerkirim);
        if(typeof data.arsip_items_html==='string') syncTable(tbodyArsip,data.arsip_items_html,emptyArsip);
      }).catch(function(){}).finally(function(){busy=false;});
  }

  function start(){
    var tbodyTerkirim=document.querySelector('#kirim-laporan-kendala .dtbl tbody');
    if(!tbodyTerkirim)return;
    var tbodyArsip=document.querySelector('#arsip-kendala-kasansi .dtbl tbody');
    var emptyTerkirim=emptyMarkupOf(tbodyTerkirim);
    var emptyArsip=tbodyArsip?emptyMarkupOf(tbodyArsip):'';
    poll(tbodyTerkirim,tbodyArsip,emptyTerkirim,emptyArsip);
    window.setInterval(function(){poll(tbodyTerkirim,tbodyArsip,emptyTerkirim,emptyArsip);},3000);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
</script>
