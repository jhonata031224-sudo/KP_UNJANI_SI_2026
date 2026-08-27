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
  var endpoint='{{ route('laporan-kendala.realtime') }}';
  var busy=false;
  function poll(){
    if(busy)return;busy=true;
    fetch(endpoint+'?_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.ok?r.json():null;})
      .then(function(data){
        if(!data||typeof data.items_html!=='string')return;
        var tbody=document.querySelector('#kirim-laporan-kendala .dtbl tbody');
        if(!tbody)return;
        var temp=document.createElement('tbody');temp.innerHTML=data.items_html;
        var fresh=Array.prototype.slice.call(temp.children);
        var freshById={};
        fresh.forEach(function(el){var id=el.getAttribute('data-kendala-id');if(id)freshById[id]=el;});
        Array.prototype.slice.call(tbody.querySelectorAll('[data-kendala-id]')).forEach(function(row){
          var id=row.getAttribute('data-kendala-id');
          var replacement=freshById[id];
          if(replacement&&replacement.outerHTML!==row.outerHTML){
            replacement.classList.add('siberad-row-updated');
            row.replaceWith(replacement);
          }
        });
      }).catch(function(){}).finally(function(){busy=false;});
  }
  function start(){ if(!document.getElementById('kirim-laporan-kendala'))return; poll(); window.setInterval(poll,3000); }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
</script>
