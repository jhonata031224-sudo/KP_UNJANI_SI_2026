<script>
(function(){
    var listSelector='#permintaan-laporan .deadline-sender-list';
    var endpoint='{{ route('permintaan-laporan.realtime') }}';
    var lastSeen=0;
    var polling=false;

    function existingLatestId(){
        var ids=[];
        document.querySelectorAll('#permintaan-laporan [data-realtime-permintaan-id], #permintaan-laporan .use-permintaan[data-request-id]').forEach(function(el){
            var id=parseInt(el.getAttribute('data-realtime-permintaan-id')||el.getAttribute('data-request-id')||'0',10);
            if(id) ids.push(id);
        });
        return ids.length?Math.max.apply(Math,ids):0;
    }

    function poll(initial){
        if(polling) return;
        polling=true;
        var since=initial?0:lastSeen;
        fetch(endpoint+'?since='+encodeURIComponent(since),{
            method:'GET',
            credentials:'same-origin',
            cache:'no-store',
            headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
        }).then(function(response){
            if(response.status===401||response.status===419){return null;}
            if(!response.ok) throw new Error('Realtime request failed');
            return response.json();
        }).then(function(data){
            if(!data) return;
            if(initial){
                lastSeen=Math.max(existingLatestId(),parseInt(data.latest_id||0,10));
                return;
            }
            var list=document.querySelector(listSelector);
            if(!list||!data.items_html) {
                lastSeen=Math.max(lastSeen,parseInt(data.latest_id||0,10));
                return;
            }
            var temp=document.createElement('div');
            temp.innerHTML=data.items_html;
            var items=Array.prototype.slice.call(temp.children);
            if(items.length){
                var existing={};
                list.querySelectorAll('[data-realtime-permintaan-id], .use-permintaan[data-request-id]').forEach(function(el){
                    var id=el.getAttribute('data-realtime-permintaan-id')||el.getAttribute('data-request-id');
                    if(id) existing[id]=true;
                });
                items.reverse().forEach(function(item){
                    var id=item.getAttribute('data-realtime-permintaan-id');
                    if(!id||existing[id]) return;
                    list.insertBefore(item,list.firstChild);
                    existing[id]=true;
                });
                if(window.siberadShowToast){
                    window.siberadShowToast('success','Permintaan laporan baru masuk.');
                }
            }
            lastSeen=Math.max(lastSeen,parseInt(data.latest_id||0,10));
        }).catch(function(){
            // Realtime bersifat tambahan; kegagalan polling tidak mengganggu dashboard.
        }).finally(function(){
            polling=false;
        });
    }

    function start(){
        if(!document.querySelector(listSelector)) return;
        poll(true);
        window.setInterval(function(){poll(false);},3000);
    }

    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',start);
    else start();
})();
</script>
