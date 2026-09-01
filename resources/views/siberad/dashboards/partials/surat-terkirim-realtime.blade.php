<script>
(function(){
  // Surat BARU yang dikirim sendiri sudah langsung kelihatan (form native
  // submit -> redirect -> halaman fresh) -- polling ini cuma buat surat yang
  // SUDAH terkirim dan baru saja dikonfirmasi penerima selagi Kasansi masih
  // buka tab ini. Begitu status jadi Dikonfirmasi, baris harus pindah dari
  // tab "Kirim Surat" ke tab "Arsip Surat" tanpa reload -- pola sinkron sama
  // seperti kendala-terkirim-realtime.blade.php.
  var endpoint='{{ route('laporan-surat.realtime') }}';
  var idAttr='data-surat-id';
  var busy=false;

  function emptyMarkupOf(tbody){
    var first=tbody.firstElementChild;
    return (first && !first.hasAttribute(idAttr)) ? first.outerHTML : '';
  }

  function syncTable(tbody,freshHtml,emptyMarkup){
    if(!tbody)return;
    var currentRows=Array.prototype.slice.call(tbody.querySelectorAll('['+idAttr+']'));

    var temp=document.createElement('tbody');temp.innerHTML=freshHtml;
    var freshRows=Array.prototype.slice.call(temp.children);

    if(freshRows.length===0){
      if(currentRows.length>0) tbody.innerHTML=emptyMarkup||'';
      return;
    }

    var freshIds=freshRows.map(function(f){return f.getAttribute(idAttr);});

    currentRows.forEach(function(row){
      if(freshIds.indexOf(row.getAttribute(idAttr))===-1) row.remove();
    });

    var placeholder=tbody.querySelector('tr:not(['+idAttr+'])');
    if(placeholder) placeholder.remove();

    var prevEl=null;
    freshRows.forEach(function(fresh){
      var id=fresh.getAttribute(idAttr);
      var existing=tbody.querySelector('['+idAttr+'="'+id+'"]');
      if(existing){
        if(existing.outerHTML!==fresh.outerHTML){
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
    var tbodyTerkirim=document.getElementById('suratTerkirimBody');
    if(!tbodyTerkirim)return;
    var tbodyArsip=document.getElementById('suratArsipBody');
    var emptyTerkirim=emptyMarkupOf(tbodyTerkirim);
    var emptyArsip=tbodyArsip?emptyMarkupOf(tbodyArsip):'';
    poll(tbodyTerkirim,tbodyArsip,emptyTerkirim,emptyArsip);
    window.setInterval(function(){poll(tbodyTerkirim,tbodyArsip,emptyTerkirim,emptyArsip);},3000);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
</script>
