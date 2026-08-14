<script>
(function(){
    var listSelector='#permintaan-laporan .deadline-sender-list';
    var endpoint='{{ route('permintaan-laporan.realtime') }}';
    var lastSeen=0;
    var polling=false;
    var initialPoll=true;

    function existingLatestId(){
        var ids=[];
        document.querySelectorAll('#permintaan-laporan [data-realtime-permintaan-id], #permintaan-laporan .use-permintaan[data-request-id]').forEach(function(el){
            var id=parseInt(el.getAttribute('data-realtime-permintaan-id')||el.getAttribute('data-request-id')||'0',10);
            if(id)ids.push(id);
        });
        return ids.length?Math.max.apply(Math,ids):0;
    }

    function syncIncomingReportCount(data){
        if(typeof data.laporan_masuk_count==='undefined')return;
        var value=String(parseInt(data.laporan_masuk_count||0,10));
        var labels=document.querySelectorAll('.stat-card .lbl');
        labels.forEach(function(label){
            if((label.textContent||'').trim().toLowerCase()!=='laporan masuk')return;
            var card=label.closest('.stat-card');
            var valueEl=card&&card.querySelector('.val');
            if(valueEl)valueEl.textContent=value;
        });
    }

    function insertItems(itemsHtml){
        var list=document.querySelector(listSelector);
        if(!list||!itemsHtml)return false;
        var temp=document.createElement('div');
        temp.innerHTML=itemsHtml;
        var items=Array.prototype.slice.call(temp.children);
        if(!items.length)return false;
        var existing={};
        list.querySelectorAll('[data-realtime-permintaan-id], .use-permintaan[data-request-id]').forEach(function(el){
            var id=el.getAttribute('data-realtime-permintaan-id')||el.getAttribute('data-request-id');
            if(id)existing[id]=true;
        });
        var inserted=false;
        items.reverse().forEach(function(item){
            var id=item.getAttribute('data-realtime-permintaan-id');
            if(!id||existing[id])return;
            list.insertBefore(item,list.firstChild);
            existing[id]=true;
            inserted=true;
        });
        return inserted;
    }

    function poll(initial){
        if(polling)return;
        polling=true;
        var since=initial?0:lastSeen;
        fetch(endpoint+'?since='+encodeURIComponent(since),{
            method:'GET',credentials:'same-origin',cache:'no-store',
            headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
        }).then(function(response){
            if(response.status===401||response.status===419)return null;
            if(!response.ok)throw new Error('Realtime request failed');
            return response.json();
        }).then(function(data){
            if(!data)return;
            syncIncomingReportCount(data);
            var inserted=insertItems(data.items_html);
            // Data yang sudah ada saat halaman pertama dibuka bukan "baru".
            // Popup hanya boleh muncul dari polling setelah initial sync.
            if(!initial&&inserted&&window.siberadShowToast){
                window.siberadShowToast('success','Permintaan laporan baru masuk.');
            }
            lastSeen=Math.max(lastSeen,parseInt(data.latest_id||0,10));
            if(initial){
                // Tandai posisi terakhir dari server agar refresh tidak mengulang
                // seluruh permintaan lama sebagai event baru.
                lastSeen=Math.max(lastSeen,existingLatestId());
            }
            initialPoll=false;
        }).catch(function(){}).finally(function(){polling=false;});
    }

    function start(){
        if(!document.querySelector(listSelector))return;
        poll(true);
        window.setInterval(function(){poll(false);},3000);
    }

    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);
    else start();
})();
</script>
