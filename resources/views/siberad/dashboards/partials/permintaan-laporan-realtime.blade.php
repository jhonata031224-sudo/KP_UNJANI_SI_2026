<script>
(function(){
    var listSelector='#permintaan-laporan .deadline-sender-list';
    var endpoint='{{ route('permintaan-laporan.realtime') }}';
    var lastSeen=0;
    var polling=false;
    var initialPoll=true;
    var detailViewId='permintaanLaporanDetailView';

    function existingLatestId(){
        var ids=[];
        document.querySelectorAll('#permintaan-laporan [data-realtime-permintaan-id], #permintaan-laporan .use-permintaan[data-request-id], #permintaan-laporan form[action*="/permintaan-laporan/"]').forEach(function(el){
            var raw=el.getAttribute('data-realtime-permintaan-id')||el.getAttribute('data-request-id')||'';
            if(!raw){
                var action=el.getAttribute('action')||'';
                var match=action.match(/permintaan-laporan\/(\d+)\/(?:mulai|)/);
                if(match)raw=match[1];
            }
            var id=parseInt(raw||'0',10);
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

    function escapeHtml(value){
        return String(value||'').replace(/[&<>"']/g,function(ch){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch];});
    }

    function ensureDetailView(){
        var existing=document.getElementById(detailViewId);
        if(existing)return existing;
        var section=document.getElementById('permintaan-laporan');
        if(!section)return null;
        var view=document.createElement('div');
        view.id=detailViewId;
        view.style.display='none';
        view.innerHTML='<div class="report-card" style="max-width:900px;margin:0 auto;">'+
            '<div class="panel-head" style="display:flex;align-items:center;gap:10px;justify-content:space-between;">'+
                '<div><h2 style="margin:0;">Detail Permintaan Laporan</h2><p style="margin:4px 0 0;color:var(--text-muted);">Detail permintaan laporan dari Danpus/Wadan sebelum konfirmasi.</p></div>'+
                '<button type="button" class="detail-btn" id="permintaanDetailBack">Kembali</button>'+
            '</div>'+
            '<div class="detail-grid" style="margin-top:18px;">'+
                '<div class="detail-item"><div class="detail-label">Pengirim</div><div class="detail-value" id="permintaanDetailPengirim">-</div></div>'+
                '<div class="detail-item"><div class="detail-label">Deadline</div><div class="detail-value" id="permintaanDetailDeadline">-</div></div>'+
                '<div class="detail-item"><div class="detail-label">Perihal</div><div class="detail-value" id="permintaanDetailPerihal">-</div></div>'+
                '<div class="detail-item"><div class="detail-label">Kategori</div><div class="detail-value" id="permintaanDetailKategori">-</div></div>'+
                '<div class="detail-item"><div class="detail-label">Prioritas</div><div class="detail-value" id="permintaanDetailPrioritas">-</div></div>'+
                '<div class="detail-item"><div class="detail-label">Status</div><div class="detail-value" id="permintaanDetailStatus">-</div></div>'+
                '<div class="detail-item full"><div class="detail-label">Instruksi Danpus/Wadan</div><div class="detail-value" id="permintaanDetailInstruksi">-</div></div>'+
            '</div>'+
            '<div class="modal-actions" style="justify-content:flex-end;"><form method="POST" id="permintaanDetailConfirmForm"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><button type="submit" class="deadline-primary small" id="permintaanDetailConfirmBtn">Konfirmasi</button></form></div>'+
        '</div>';
        section.appendChild(view);
        view.querySelector('#permintaanDetailBack').addEventListener('click',function(){showList();});
        return view;
    }

    function showList(){
        var section=document.getElementById('permintaan-laporan');
        var view=document.getElementById(detailViewId);
        var list=section&&section.querySelector('.report-card');
        if(view)view.style.display='none';
        if(list)list.style.display='';
    }

    function openDetail(item){
        var section=document.getElementById('permintaan-laporan');
        var view=ensureDetailView();
        if(!section||!view)return;
        var main=item.querySelector('.deadline-sender-main');
        var side=item.querySelector('.deadline-sender-side');
        var title=item.querySelector('.deadline-sender-title');
        var meta=item.querySelector('.deadline-sender-meta');
        var instruction=item.querySelector('.deadline-sender-instruction');
        var pill=item.querySelector('.deadline-pill');
        var actionForm=item.querySelector('form[action*="/permintaan-laporan/"]');
        var target=item.querySelector('.use-permintaan');
        var sender=(meta&&meta.textContent||'').replace(/^Dari\s*/i,'').split('·')[0].trim();
        var deadline=(meta&&meta.textContent||'').split('· Deadline').slice(1).join('· Deadline').trim();
        var category=target&&target.getAttribute('data-kategori')||'-';
        var priority=target&&target.getAttribute('data-prioritas')||'-';
        var instr=target&&target.getAttribute('data-instruksi')||((instruction&&instruction.textContent)||'-');
        var formAction=actionForm&&actionForm.getAttribute('action')||'';
        if(!formAction){
            var id=item.getAttribute('data-realtime-permintaan-id')||'';
            if(id)formAction='{{ url('/permintaan-laporan') }}/'+id+'/mulai';
        }
        view.querySelector('#permintaanDetailPengirim').textContent=sender||'Pimpinan';
        view.querySelector('#permintaanDetailDeadline').textContent=deadline||'-';
        view.querySelector('#permintaanDetailPerihal').textContent=(title&&title.textContent)||'-';
        view.querySelector('#permintaanDetailKategori').textContent=category||'-';
        view.querySelector('#permintaanDetailPrioritas').textContent=priority||'-';
        view.querySelector('#permintaanDetailStatus').textContent=(pill&&pill.textContent)||'-';
        view.querySelector('#permintaanDetailInstruksi').textContent=instr||'-';
        var confirmForm=view.querySelector('#permintaanDetailConfirmForm');
        confirmForm.setAttribute('action',formAction);
        var confirmBtn=view.querySelector('#permintaanDetailConfirmBtn');
        confirmBtn.disabled=!formAction;
        var card=section.querySelector('.report-card');
        if(card)card.style.display='none';
        view.style.display='block';
        window.scrollTo({top:0,behavior:'smooth'});
    }

    function bindDetailButtons(){
        var list=document.querySelector(listSelector);
        if(!list)return;
        list.querySelectorAll('.confirm-btn').forEach(function(button){
            var form=button.closest('form');
            var item=button.closest('.deadline-sender-item');
            if(!form||!item||button.dataset.detailBound==='1')return;
            button.dataset.detailBound='1';
            button.textContent='Lihat Detail';
            button.classList.remove('confirm-btn');
            button.type='button';
            button.addEventListener('click',function(e){e.preventDefault();openDetail(item);});
        });
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
            bindDetailButtons();
            if(!initial&&inserted&&window.siberadShowToast){
                window.siberadShowToast('success','Permintaan laporan baru masuk.');
            }
            lastSeen=Math.max(lastSeen,parseInt(data.latest_id||0,10));
            if(initial)lastSeen=Math.max(lastSeen,existingLatestId());
            initialPoll=false;
        }).catch(function(){}).finally(function(){polling=false;});
    }

    function start(){
        if(!document.querySelector(listSelector))return;
        bindDetailButtons();
        poll(true);
        window.setInterval(function(){poll(false);},3000);
    }

    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);
    else start();
})();
</script>
