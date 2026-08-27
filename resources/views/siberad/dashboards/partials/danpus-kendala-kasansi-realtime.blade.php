<style>
/* Baris kendala baru yang disisipkan live -- fade+slide halus, senada sama
   animasi .tab-panel.active (fadeIn .25s) yang sudah ada di dash-styles. */
@keyframes siberadKendalaRowIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
.siberad-row-in{animation:siberadKendalaRowIn .35s ease}
</style>
<script>
(function(){
  var endpoint='{{ route('laporan-kendala.realtime') }}';
  var lastSeen=0,polling=false,initial=true;
  function poll(){
    if(polling)return;polling=true;
    fetch(endpoint+'?since='+(initial?0:lastSeen)+'&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.ok?r.json():null;})
      .then(function(data){
        if(!data)return;
        var tbody=document.querySelector('#kendala-kasansi .clean-table tbody');
        var inserted=0;
        if(tbody&&typeof data.items_html==='string'&&data.items_html){
          var temp=document.createElement('tbody');temp.innerHTML=data.items_html;
          var rows=Array.prototype.slice.call(temp.children);
          if(rows.length){
            Array.prototype.slice.call(tbody.querySelectorAll('tr')).forEach(function(r){if(r.querySelector('.empty-state'))r.remove();});
            rows.reverse().forEach(function(row){
              var id=row.getAttribute('data-kendala-id');
              if(!id||tbody.querySelector('[data-kendala-id="'+id+'"]'))return;
              row.classList.add('siberad-row-in');
              tbody.insertBefore(row,tbody.firstChild);
              inserted++;
            });
          }
        }
        if(!initial&&inserted>0&&window.siberadShowToast){
          window.siberadShowToast('success', inserted===1?'Ada 1 kendala baru masuk dari Kasansi.':'Ada '+inserted+' kendala baru masuk dari Kasansi.');
        }
        if(typeof data.latest_id==='number')lastSeen=Math.max(lastSeen,data.latest_id);
        initial=false;
      }).catch(function(){}).finally(function(){polling=false;});
  }
  function start(){ if(!document.getElementById('kendala-kasansi'))return; poll(); window.setInterval(poll,3000); }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
</script>
