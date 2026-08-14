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

    function syncIncomingReportCount(data){
        if(typeof data.laporan_masuk_count==='undefined') return;
        var value=String(parseInt(data.laporan_masuk_count||0,10));
        var labels=document.querySelectorAll('.stat-card .lbl');
        labels.forEach(function(label){
            if((label.textContent||'').trim().toLowerCase()!=='laporan masuk') return;
            var card=label.closest('.stat-card');
            var valueEl=card&&card.querySelector('.val');
            if(valueEl) valueEl.textContent=value;
        });
    }

    function syncNotifications(data){
        var button=document.getElementById('notifBtn');
        var dropdown=document.getElementById('notifDropdown');
        if(!button||!dropdown) return;

        var count=parseInt(data.unread_count||0,10);
        var dot=button.querySelector('.siberad-realtime-notif-dot');
        if(count>0){
            if(!dot){
                dot=document.createElement('span');
                dot.className='siberad-realtime-notif-dot';
                dot.style.cssText='position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:var(--red);box-shadow:0 0 0 2px var(--panel,#0c2417);';
                button.appendChild(dot);
            }
        }else if(dot){
            dot.remove();
        }

        var notifications=Array.isArray(data.notifications)?data.notifications:[];
        var head=dropdown.querySelector('.profile-dropdown-head');
        if(!head) return;

        // Hapus seluruh isi lama setelah header. Ini penting karena markup awal
        // server-side bisa berisi "Belum ada notifikasi saat ini." walaupun
        // polling berikutnya sudah menerima notifikasi baru.
        while(dropdown.lastElementChild && dropdown.lastElementChild!==head){
            dropdown.removeChild(dropdown.lastElementChild);
        }

        var body=document.createElement('div');
        body.className='siberad-realtime-notif-body';
        if(notifications.length){
            body.innerHTML=notifications.map(function(n){
                var message=String(n.message||'').replace(/[&<>"']/g,function(ch){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch];});
                var time=String(n.time||'').replace(/[&<>"']/g,function(ch){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch];});
                return '<div class="profile-dropdown-item" style="align-items:flex-start;white-space:normal;cursor:default;">'+
                    '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--gold-bright)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:2px;"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>'+
                    '<div><div style="font-size:12.5px;line-height:1.5;color:var(--text);">'+message+'</div>'+
                    '<div style="font-size:11px;color:var(--text-dim);margin-top:2px;">'+time+'</div></div></div>';
            }).join('');
        }else{
            body.innerHTML='<div class="siberad-notif-empty" style="text-align:center;padding:20px 6px 8px;"><p style="margin:0;font-size:12.5px;line-height:1.6;color:var(--text-muted);">Belum ada notifikasi saat ini.</p></div>';
        }
        dropdown.appendChild(body);
    }

    function insertItems(itemsHtml){
        var list=document.querySelector(listSelector);
        if(!list||!itemsHtml) return false;
        var temp=document.createElement('div');
        temp.innerHTML=itemsHtml;
        var items=Array.prototype.slice.call(temp.children);
        if(!items.length) return false;
        var existing={};
        list.querySelectorAll('[data-realtime-permintaan-id], .use-permintaan[data-request-id]').forEach(function(el){
            var id=el.getAttribute('data-realtime-permintaan-id')||el.getAttribute('data-request-id');
            if(id) existing[id]=true;
        });
        var inserted=false;
        items.reverse().forEach(function(item){
            var id=item.getAttribute('data-realtime-permintaan-id');
            if(!id||existing[id]) return;
            list.insertBefore(item,list.firstChild);
            existing[id]=true;
            inserted=true;
        });
        return inserted;
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
            syncIncomingReportCount(data);
            syncNotifications(data);
            var inserted=insertItems(data.items_html);
            if(inserted && window.siberadShowToast){
                window.siberadShowToast('success','Permintaan laporan baru masuk.');
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
