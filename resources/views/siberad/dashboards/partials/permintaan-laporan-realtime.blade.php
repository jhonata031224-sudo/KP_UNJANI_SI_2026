<style>
#permintaanLaporanDetailView{display:none;position:fixed;inset:0;z-index:100200;align-items:center;justify-content:center;padding:24px;box-sizing:border-box;background:rgba(15,23,42,.28);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);opacity:0;transition:opacity .22s ease;}
#permintaanLaporanDetailView.is-visible{opacity:1;}
#permintaanLaporanDetailView .permintaan-detail-dialog{width:min(760px,100%);max-height:min(86vh,760px);overflow:auto;background:var(--card-bg,#fff);border:1px solid rgba(148,163,184,.22);border-radius:18px;box-shadow:0 24px 70px rgba(15,23,42,.24);transform:translateY(10px) scale(.985);transition:transform .22s ease;}
#permintaanLaporanDetailView.is-visible .permintaan-detail-dialog{transform:translateY(0) scale(1);}
@media (max-width:700px){#permintaanLaporanDetailView{padding:14px;}#permintaanLaporanDetailView .permintaan-detail-dialog{max-height:90vh;border-radius:16px;}}
@media (prefers-reduced-motion: reduce){#permintaanLaporanDetailView,#permintaanLaporanDetailView .permintaan-detail-dialog,#permintaanLaporanDetailView.is-visible .permintaan-detail-dialog{transition:none!important;transform:none!important;}}
#permintaanDetailConfirmBtn{font-family:var(--mono);font-weight:600;font-size:11.5px;letter-spacing:.04em;text-transform:uppercase;padding:9px 15px;border-radius:8px;border:1px solid transparent;background:var(--success,#16834b);color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:6px;box-shadow:0 6px 16px -6px rgba(22,131,75,.5);transition:transform .15s ease,filter .15s ease;}
#permintaanDetailConfirmBtn:hover{filter:brightness(1.08);transform:translateY(-1px);}
</style>
<script>
(function(){
    var listSelector='#permintaan-laporan .deadline-sender-list';
    // Riwayat Laporan (kartu arsip) numpang mekanisme file ini juga --
    // modal Lihat Detail, animasi kartu masuk, dan endpoint fetch-nya sama
    // persis, cuma daftar & selector-nya beda. Lihat syncRiwayatList().
    var riwayatListSelector='#riwayat .deadline-sender-list';
    var endpoint='{{ route('permintaan-laporan.realtime') }}';
    var lastSeen=0;
    var polling=false;
    var initialPoll=true;
    var detailViewId='permintaanLaporanDetailView';
    function existingLatestId(){var ids=[];document.querySelectorAll('#permintaan-laporan [data-realtime-permintaan-id], #permintaan-laporan .use-permintaan[data-request-id], #permintaan-laporan form[action*="/permintaan-laporan/"]').forEach(function(el){var raw=el.getAttribute('data-realtime-permintaan-id')||el.getAttribute('data-request-id')||'';if(!raw){var action=el.getAttribute('action')||'';var match=action.match(/permintaan-laporan\/(\d+)\/(?:mulai|)/);if(match)raw=match[1];}var id=parseInt(raw||'0',10);if(id)ids.push(id);});return ids.length?Math.max.apply(Math,ids):0;}
    function syncIncomingReportCount(data){if(typeof data.laporan_masuk_count==='undefined')return;var value=String(parseInt(data.laporan_masuk_count||0,10));document.querySelectorAll('.stat-card .lbl').forEach(function(label){if((label.textContent||'').trim().toLowerCase()!=='laporan masuk')return;var card=label.closest('.stat-card');var valueEl=card&&card.querySelector('.val');if(valueEl)valueEl.textContent=value;});}
    // Empty-state Riwayat & Permintaan Laporan sekarang SAMA-SAMA <div
    // class="empty-state"> anak langsung .deadline-sender-list (blok
    // fallback forelse-kosong di laporan-role.blade.php) -- cukup dicari
    // langsung lewat selector, gak perlu lagi nebak-nebak dari isi teksnya.
    function syncEmptyState(sectionId){var section=document.getElementById(sectionId);var list=section&&section.querySelector('.deadline-sender-list');if(!section||!list)return;var hasItems=!!list.querySelector(':scope > article.deadline-sender-item, :scope > [data-realtime-permintaan-id]');var emptyNode=list.querySelector(':scope > .empty-state');if(!emptyNode)return;emptyNode.style.display=hasItems?'none':'';emptyNode.setAttribute('aria-hidden',hasItems?'true':'false');}
    function insertItems(itemsHtml,selector){var list=document.querySelector(selector);if(!list||!itemsHtml)return false;var temp=document.createElement('div');temp.innerHTML=itemsHtml;var items=Array.prototype.slice.call(temp.children);if(!items.length)return false;var existing={};list.querySelectorAll('[data-realtime-permintaan-id], .use-permintaan[data-request-id]').forEach(function(el){var id=el.getAttribute('data-realtime-permintaan-id')||el.getAttribute('data-request-id');if(id)existing[id]=true;});
        var toInsert=[];
        items.reverse().forEach(function(item){var id=item.getAttribute('data-realtime-permintaan-id');if(!id||existing[id])return;toInsert.push(item);existing[id]=true;});
        if(!toInsert.length)return false;
        var reduceMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        // FLIP -- catat posisi SEMUA kartu yang SUDAH ada SEBELUM kartu baru
        // disisipkan, biar begitu grid-nya reflow (kartu lama kegeser buat
        // ngasih tempat kartu baru), selisih posisi lama->baru bisa
        // dianimasikan pakai transform (geser mulus), bukan cuma "loncat"
        // instan ke posisi barunya. Dilewati kalau prefers-reduced-motion.
        var oldCards=reduceMotion?[]:Array.prototype.slice.call(list.querySelectorAll(':scope > article.deadline-sender-item'));
        var prevRects=new Map();
        oldCards.forEach(function(card){prevRects.set(card,card.getBoundingClientRect());});
        toInsert.forEach(function(item){
            list.insertBefore(item,list.firstChild);
            // Kartu BARU cukup animasi zoom-in (scale+fade) di posisi akhirnya
            // sendiri -- gak butuh FLIP karena dia gak punya "posisi lama".
            if(reduceMotion)return;
            item.classList.add('dcard-enter');
            item.addEventListener('animationend',function handler(){item.classList.remove('dcard-enter');item.removeEventListener('animationend',handler);});
        });
        oldCards.forEach(function(card){
            var prev=prevRects.get(card);if(!prev)return;
            var next=card.getBoundingClientRect();
            var dx=prev.left-next.left,dy=prev.top-next.top;
            if(Math.abs(dx)<1&&Math.abs(dy)<1)return;
            card.style.transition='none';
            card.style.transform='translate('+dx+'px,'+dy+'px)';
            card.getBoundingClientRect();
            // dua rAF -- state "dari" wajib kepaint dulu sebelum transisi ke
            // "ke" dipicu, biar geser-nya nggak ke-skip/kepotong di akhir.
            (function(el){requestAnimationFrame(function(){requestAnimationFrame(function(){
                el.style.transition='transform .58s cubic-bezier(.16,1,.3,1)';
                el.style.transform='';
            });});})(card);
            card.addEventListener('transitionend',function handler(e){if(e.propertyName!=='transform')return;card.style.transition='';card.removeEventListener('transitionend',handler);});
        });
        return true;}
    function ensureDetailView(){var existing=document.getElementById(detailViewId);if(existing)return existing;var view=document.createElement('div');view.id=detailViewId;view.innerHTML='<div class="permintaan-detail-dialog"><div class="report-card" style="max-width:900px;margin:0 auto;box-shadow:none;border:0;">'+'<div class="panel-head" style="display:flex;align-items:center;gap:10px;justify-content:space-between;flex-wrap:wrap;"><div><h2 style="margin:0;">Detail Permintaan Laporan</h2><p style="margin:4px 0 0;color:var(--text-muted);">Detail permintaan laporan dari Danpus/Wadan sebelum konfirmasi.</p></div></div>'+'<div class="detail-grid" style="margin-top:18px;">'+'<div class="detail-item"><div class="detail-label">Pengirim</div><div class="detail-value" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;"><span id="permintaanDetailPengirim">-</span><span class="satuan-pill" id="permintaanDetailPengirimKode" style="display:none;"></span></div></div>'+'<div class="detail-item"><div class="detail-label">Deadline</div><div class="detail-value request-deadline"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg><span id="permintaanDetailDeadline">-</span></div></div>'+'<div class="detail-item"><div class="detail-label">Perihal</div><div class="detail-value" id="permintaanDetailPerihal">-</div></div>'+'<div class="detail-item"><div class="detail-label">Kategori</div><div class="detail-value" id="permintaanDetailKategori">-</div></div>'+'<div class="detail-item"><div class="detail-label">Prioritas</div><div class="detail-value"><span class="priority-tag" id="permintaanDetailPrioritas">-</span></div></div>'+'<div class="detail-item"><div class="detail-label">Status</div><div class="detail-value"><span class="deadline-pill" id="permintaanDetailStatus">-</span></div></div>'+'<div class="detail-item full"><div class="detail-label">Instruksi Danpus/Wadan</div><div class="detail-value" id="permintaanDetailInstruksi">-</div></div>'+'<div class="detail-item full" id="permintaanDetailCatatanWrap" style="display:none;"><div class="detail-label">Catatan / Keterangan</div><div class="detail-value" id="permintaanDetailCatatan" style="white-space:pre-line;">-</div></div>'+'</div>'+'<div class="modal-actions" style="justify-content:flex-end;flex-wrap:wrap;"><button type="button" class="btn" id="permintaanDetailCloseBtn">Tutup</button><form method="POST" id="permintaanDetailConfirmForm"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><button type="submit" id="permintaanDetailConfirmBtn">Konfirmasi</button></form></div>'+'</div></div>';
        // Ditempel ke document.body, BUKAN ke dalam #permintaan-laporan --
        // overlay ini position:fixed;inset:0 (harus nutup SELURUH viewport),
        // tapi kalau ancestor-nya (section ini/wrapper dashboard) kebetulan
        // punya transform/animation aktif (mis. .tab-panel.active{animation:
        // fadeIn...} di dash-styles.blade.php), itu jadi containing block
        // BARU buat elemen fixed di dalamnya -- overlay-nya jadi cuma nutup
        // area section itu, bukan viewport asli, sidebar kelihatan ngintip
        // di sisi yang gak ketutup. document.body nyaris pasti aman dari
        // masalah ini.
        document.body.appendChild(view);
        // Nutupnya SENGAJA cuma lewat tombol "Tutup" -- klik di area backdrop
        // di luar dialog TIDAK menutup modal ini, samain sama modal lain di
        // sistem (mis. #kirimLaporanModal/#reportDetailModal).
        view.querySelector('#permintaanDetailCloseBtn').addEventListener('click',function(){showList(true);});return view;}
    function showList(animate){var view=document.getElementById(detailViewId);if(view){view.classList.remove('is-visible');window.setTimeout(function(){view.style.display='none';},220);}}
    function openDetail(item){var view=ensureDetailView();if(!view)return;var title=item.querySelector('.deadline-sender-title');var meta=item.querySelector('.deadline-sender-meta');var instruction=item.querySelector('.deadline-sender-instruction');var pill=item.querySelector('.deadline-pill');var actionForm=item.querySelector('form[action*="/permintaan-laporan/"]');var target=item.querySelector('.use-permintaan');var sender=(meta&&meta.textContent||'').replace(/^Dari\s*/i,'').split('·')[0].trim();var deadline=(meta&&meta.textContent||'').split('· Deadline').slice(1).join('· Deadline').trim();var category=item.getAttribute('data-kategori')||(target&&target.getAttribute('data-kategori'))||'-';var priority=item.getAttribute('data-prioritas')||(target&&target.getAttribute('data-prioritas'))||'-';var instr=target&&target.getAttribute('data-instruksi')||((instruction&&instruction.textContent)||'-');var formAction=actionForm&&actionForm.getAttribute('action')||'';if(!formAction){var id=item.getAttribute('data-realtime-permintaan-id')||'';if(id)formAction='{{ url('/permintaan-laporan') }}/'+id+'/mulai';}view.querySelector('#permintaanDetailPengirim').textContent=sender||'Pimpinan';
        // Kode satuan pengirim (singkatan, mis. "DANPUS") -- reuse gaya
        // .satuan-pill yang sudah dipakai di tabel Riwayat/Masuk. Sumbernya
        // data-pengirim-kode di <article> (lihat permintaan-laporan-item.
        // blade.php), bukan hasil parsing teks -- kode-nya nggak nempel di
        // .deadline-sender-meta sama sekali.
        var kodeEl=view.querySelector('#permintaanDetailPengirimKode');
        var kode=item.getAttribute('data-pengirim-kode')||'';
        if(kodeEl){ if(kode){kodeEl.textContent=kode;kodeEl.style.display='inline-flex';} else {kodeEl.style.display='none';} }
        view.querySelector('#permintaanDetailDeadline').textContent=deadline||'-';
        view.querySelector('#permintaanDetailPerihal').textContent=(title&&title.textContent)||'-';
        view.querySelector('#permintaanDetailKategori').textContent=category||'-';
        // Prioritas: badge .priority-tag.prio-* yang sama persis dipakai di
        // tabel Riwayat/Masuk & dashboard Pimpinan, bukan teks polos lagi.
        var prioEl=view.querySelector('#permintaanDetailPrioritas');
        prioEl.textContent=priority||'-';
        prioEl.className='priority-tag pl-prio-violet'+(priority&&priority!=='-'?' prio-'+priority.toLowerCase():'');
        // Status: kloning class PERSIS dari .deadline-pill yang sudah
        // dirender di kartu (sudah bawa warna wait/blue/ok/bad/revisi yang
        // benar dari PHP) -- daripada nebak ulang mapping warnanya di JS.
        var statusEl=view.querySelector('#permintaanDetailStatus');
        statusEl.textContent=(pill&&pill.textContent)||'-';
        statusEl.className=pill?pill.className:'deadline-pill';
        view.querySelector('#permintaanDetailInstruksi').textContent=instr||'-';
        // Catatan/keterangan penolakan dari Pimpinan (data-catatan-penolakan di
        // <article>, lihat permintaan-laporan-item.blade.php). Cuma muncul kalau
        // ada isinya -- yaitu pas permintaan pernah/sedang ditolak.
        var catatanPenolakan=(item.getAttribute('data-catatan-penolakan')||'').trim();
        var catatanWrap=view.querySelector('#permintaanDetailCatatanWrap');
        var catatanEl=view.querySelector('#permintaanDetailCatatan');
        if(catatanWrap&&catatanEl){
            if(catatanPenolakan){catatanEl.textContent=catatanPenolakan;catatanWrap.style.display='';}
            else{catatanEl.textContent='-';catatanWrap.style.display='none';}
        }
        var confirmForm=view.querySelector('#permintaanDetailConfirmForm');confirmForm.setAttribute('action',formAction);view.querySelector('#permintaanDetailConfirmBtn').disabled=!formAction;
        // "Lihat Detail" sekarang tampil di SEMUA status (dulu cuma pas
        // "Belum dikerjakan"), jadi modal ini bisa kebuka juga buat
        // permintaan yang SUDAH dikonfirmasi -- tombol Konfirmasi (yang
        // nge-PATCH ke .../mulai) cuma relevan SEBELUM dikonfirmasi, jadi
        // disembunyikan begitu data-belum-dikerjakan bukan "1" lagi, biar
        // gak ada tombol "Konfirmasi" nongol lagi buat yang udah jalan.
        confirmForm.style.display=item.getAttribute('data-belum-dikerjakan')==='1'?'':'none';
        // .report-card sekarang cuma bungkus panel-head+search bar (bukan
        // seluruh section lagi, lihat laporan-role.blade.php) -- overlay
        // detail ini sendiri sudah position:fixed;inset:0 nutup SELURUH
        // layar (termasuk grid kartu di luar .report-card), jadi gak perlu
        // lagi fade/hide .report-card secara terpisah di sini. Kalau tetap
        // dilakukan, judul+search bar-nya doang yang keliatan "menghilang"
        // sesaat (grid kartunya baru ketutup overlay belakangan) -- ganjil.
        view.style.display='flex';window.requestAnimationFrame(function(){view.classList.add('is-visible');});}
    function bindDetailButtons(){[listSelector,riwayatListSelector].forEach(function(sel){var list=document.querySelector(sel);if(!list)return;list.querySelectorAll('.permintaan-lihat-detail-btn').forEach(function(button){var item=button.closest('.deadline-sender-item');if(!item||button.dataset.detailBound==='1')return;button.dataset.detailBound='1';button.addEventListener('click',function(e){e.preventDefault();openDetail(item);});});});}
    window.siberadBindPermintaanDetailButtons=bindDetailButtons;
    // Riwayat Laporan: item BISA hilang lagi dari daftar ini kalau Pimpinan
    // perpanjang deadline-nya (archived_at balik null, pindah lagi ke
    // #permintaan-laporan) -- beda dari #permintaan-laporan yang cukup
    // nambah item baru (insertItems), di sini daftarnya perlu di-diff PENUH
    // (hapus yang udah nggak ada, baru tambah yang baru) tiap siklus poll.
    function syncRiwayatList(itemsHtml){
        if(typeof itemsHtml!=='string')return;
        var list=document.querySelector(riwayatListSelector);
        if(!list)return;
        var incoming=document.createElement('div');incoming.innerHTML=itemsHtml;
        var freshIds={};Array.prototype.slice.call(incoming.children).forEach(function(el){var id=el.getAttribute('data-realtime-permintaan-id');if(id)freshIds[id]=true;});
        var reduceMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        Array.prototype.slice.call(list.querySelectorAll(':scope > article[data-realtime-permintaan-id]')).forEach(function(item){
            var id=item.getAttribute('data-realtime-permintaan-id');
            if(id&&freshIds[id]&&item.dataset.removing==='1'){
                // Muncul lagi di server sebelum fade-out kelar (mis. revisi lalu
                // cepat diarsip ulang) -- batalin penghapusan, balikin normal.
                item.dataset.removing='';item.classList.remove('siberad-card-leaving');return;
            }
            if(!id||freshIds[id])return;
            // Kartu ini keluar dari Riwayat (mis. Pimpinan klik Revisi ->
            // archived_at balik null, permintaannya pindah lagi ke daftar
            // aktif). Fade-out halus dulu (.siberad-card-leaving), JANGAN
            // item.remove() mendadak -- itu yang bikin "kaku". Guard
            // data-removing biar poll 3 detik berikutnya nggak dobel proses.
            if(reduceMotion){item.remove();return;}
            if(item.dataset.removing==='1')return;
            item.dataset.removing='1';
            var fin=function(){window.clearTimeout(t);if(item.dataset.removing!=='1')return;item.remove();syncEmptyState('riwayat');};
            var t=window.setTimeout(fin,360);
            item.addEventListener('animationend',fin,{once:true});
            item.classList.add('siberad-card-leaving');
        });
        insertItems(itemsHtml,riwayatListSelector);
        syncEmptyState('riwayat');
        return Object.keys(freshIds);
    }
    // Kartu yang BARU pindah ke Riwayat harus LANGSUNG hilang dari daftar aktif
    // di siklus poll yang SAMA (3 dtk) -- JANGAN nunggu syncRequestList()
    // (interval TERPISAH 6 dtk di laporan-role-realtime-sync.blade.php). Tanpa
    // ini kartunya sempat nyangkut kelihatan di DUA list sekaligus 3-6 dtk ->
    // itu yang bikin Riwayat satuan kerasa "nge-lag" dibanding Pimpinan (yang
    // udah beres: loadHistory() nambah ke Riwayat + removeArchivedRows dari
    // daftar aktif dalam 1 fetch). Flag .siberad-card-leaving / dataset.leaving
    // sengaja disamain sama syncRequestList biar dua-duanya gak dobel animasi.
    function pruneActiveList(archivedIds){
        if(!archivedIds||!archivedIds.length)return;
        var list=document.querySelector(listSelector);
        if(!list)return;
        var reduceMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var set={};archivedIds.forEach(function(id){set[String(id)]=true;});
        Array.prototype.slice.call(list.querySelectorAll(':scope > article[data-realtime-permintaan-id]')).forEach(function(item){
            var id=String(item.getAttribute('data-realtime-permintaan-id')||'');
            if(!set[id]||item.dataset.leaving==='1')return;
            if(reduceMotion){item.remove();syncEmptyState('permintaan-laporan');return;}
            item.dataset.leaving='1';
            var fin=function(){window.clearTimeout(t);item.remove();syncEmptyState('permintaan-laporan');window.siberadRebindPermintaanActions&&window.siberadRebindPermintaanActions();};
            var t=window.setTimeout(fin,340);
            item.addEventListener('animationend',fin,{once:true});
            item.classList.add('siberad-card-leaving');
        });
    }
    function poll(initial){if(polling)return;polling=true;var since=initial?0:lastSeen;fetch(endpoint+'?since='+encodeURIComponent(since),{method:'GET',credentials:'same-origin',cache:'no-store',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}}).then(function(response){if(response.status===401){if(window.siberadTampilkanSesiBerakhir)window.siberadTampilkanSesiBerakhir();return null;}if(response.status===419)return null;if(!response.ok)throw new Error('Realtime request failed');return response.json();}).then(function(data){if(!data)return;syncIncomingReportCount(data);var inserted=insertItems(data.items_html,listSelector);var archivedIds=syncRiwayatList(data.riwayat_items_html);pruneActiveList(archivedIds);bindDetailButtons();syncEmptyState('permintaan-laporan');window.siberadRebindPermintaanActions&&window.siberadRebindPermintaanActions();if(!initial&&inserted&&window.siberadShowToast)window.siberadShowToast('success','Permintaan laporan baru masuk.');lastSeen=Math.max(lastSeen,parseInt(data.latest_id||0,10));if(initial)lastSeen=Math.max(lastSeen,existingLatestId());initialPoll=false;}).catch(function(){}).finally(function(){polling=false;});}
    function start(){if(!document.querySelector(listSelector)&&!document.querySelector(riwayatListSelector))return;bindDetailButtons();syncEmptyState('permintaan-laporan');syncEmptyState('riwayat');poll(true);window.setInterval(function(){poll(false);},3000);}
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
</script>