<style>
/* Animasi realtime kartu Permintaan Laporan & Riwayat Laporan Pimpinan --
   kartu baru masuk halus (slide-in), kartu hilang fade-out. Perubahan nilai
   (progres/angka/status) dianimasikan langsung di elemennya, tanpa kilau
   kartu. Hormati prefers-reduced-motion. */
@keyframes siberadPimpinanCardIn{from{opacity:0;transform:translateY(-10px) scale(.98)}to{opacity:1;transform:none}}
@keyframes siberadPimpinanCardOut{to{opacity:0;transform:translateY(-6px) scale(.96)}}
#permintaan-laporan .deadline-sender-item.siberad-pimpinan-card-in,#riwayat .deadline-sender-item.siberad-pimpinan-card-in{animation:siberadPimpinanCardIn .42s cubic-bezier(.2,.82,.2,1)}
#permintaan-laporan .deadline-sender-item.siberad-pimpinan-card-out,#riwayat .deadline-sender-item.siberad-pimpinan-card-out{animation:siberadPimpinanCardOut .3s ease forwards;pointer-events:none}
@media(prefers-reduced-motion:reduce){#permintaan-laporan .deadline-sender-item.siberad-pimpinan-card-in,#permintaan-laporan .deadline-sender-item.siberad-pimpinan-card-out,#riwayat .deadline-sender-item.siberad-pimpinan-card-in,#riwayat .deadline-sender-item.siberad-pimpinan-card-out{animation:none!important}}
</style>
<script>
(function(){
'use strict';
function initDanpusArchiveMode(){
 const panel=document.getElementById('permintaan-laporan');if(!panel||panel.dataset.danpusArchiveModeBound==='1')return;
 const list=panel.querySelector('.deadline-sender-list');if(!list)return;
 panel.dataset.danpusArchiveModeBound='1';let busy=false;
 const archiveEndpoint='{{ route('permintaan-laporan.store') }}';const historyEndpoint='{{ route('permintaan-laporan.realtime') }}?history=1&_=';
 function csrf(){return document.querySelector('input[name="_token"]')?.value||document.querySelector('meta[name="csrf-token"]')?.content||''}
 // ══════════════════════════════════════════════════════════════════════
 // Riwayat Laporan Pimpinan (#riwayat) -- KARTU read-only (bukan tabel lagi).
 // Sejajar dengan Riwayat Laporan Satuan: kartu dari
 // permintaan-laporan-pimpinan-card (riwayatMode), realtime full-diff dari
 // endpoint ?history=1 (items_html), plus search + filter status + sort.
 // ══════════════════════════════════════════════════════════════════════
 const riwayatPanel=document.getElementById('riwayat');
 const riwayatList=riwayatPanel&&riwayatPanel.querySelector('.deadline-sender-list');

 function syncRiwayatEmptyState(){
   if(!riwayatList)return;
   const hasItems=!!riwayatList.querySelector(':scope > article[data-realtime-permintaan-id]');
   const emptyNode=riwayatList.querySelector(':scope > .empty-state');
   if(!emptyNode)return;
   emptyNode.style.display=hasItems?'none':'';
   emptyNode.setAttribute('aria-hidden',hasItems?'true':'false');
 }

 // Menu titik-3 kartu Riwayat: cuma "Revisi" (status Ditolak/Terlambat/
 // Dibatalkan). Klik -> modal Edit Deadline mode "revisi" (deadline baru) ->
 // revisiDariRiwayat() reset archived_at + status. Numpang mekanisme
 // .dcard-menu-toggle yang sama; closeAllMenus() di bawah dibikin sadar
 // #riwayat juga.
 function bindRiwayatMenus(){
   if(!riwayatList)return;
   riwayatList.querySelectorAll('.dcard-menu-toggle').forEach(function(btn){
     if(btn.dataset.menuBound==='1')return;
     btn.dataset.menuBound='1';
     btn.addEventListener('click',function(e){
       e.stopPropagation();
       const menu=btn.nextElementSibling;if(!menu)return;
       const willOpen=!menu.classList.contains('open');
       closeAllMenus();
       if(willOpen){menu.classList.add('open');btn.setAttribute('aria-expanded','true');}
     });
   });
   riwayatList.querySelectorAll('.dcard-riwayat-revisi-btn').forEach(function(btn){
     if(btn.dataset.riwayatBound==='1')return;
     btn.dataset.riwayatBound='1';
     btn.addEventListener('click',function(e){e.stopPropagation();closeAllMenus();window.bukaEditDeadlinePermintaan&&window.bukaEditDeadlinePermintaan(btn);});
   });
 }

 // Tanda manual (pin) kartu Riwayat -- kembar bindPinButtons() daftar aktif,
 // cuma scope-nya riwayatList & refresh filter Riwayat.
 function bindRiwayatPinButtons(){
   if(!riwayatList)return;
   const pinned=getPinnedIds();
   riwayatList.querySelectorAll(':scope > article.deadline-sender-item').forEach(function(card){
     const id=card.getAttribute('data-realtime-permintaan-id');
     if(id)applyPinnedState(card,pinned.has(id));
   });
   riwayatList.querySelectorAll('.dcard-pin-btn').forEach(function(btn){
     if(btn.dataset.pinBound==='1')return;
     btn.dataset.pinBound='1';
     btn.addEventListener('click',function(e){
       e.stopPropagation();
       const card=btn.closest('.deadline-sender-item');
       const id=card&&card.getAttribute('data-realtime-permintaan-id');
       if(!id)return;
       const set=getPinnedIds();
       const nowPinned=!set.has(id);
       if(nowPinned)set.add(id);else set.delete(id);
       savePinnedIds(set);
       applyPinnedState(card,nowPinned);
       window.siberadRefreshPimpinanRiwayatFilter&&window.siberadRefreshPimpinanRiwayatFilter();
     });
   });
 }

 // Search + filter status + sort untuk #riwayat -- kembar initCardFilter()
 // (daftar aktif) tapi sort-nya "Arsip Terbaru/Terlama" (data-archived-at) &
 // opsi status hanya yang relevan buat riwayat.
 function initRiwayatCardFilter(){
   if(!riwayatPanel||!riwayatList||riwayatPanel.dataset.riwayatFilterReady==='1')return;
   if(!riwayatList.querySelector(':scope > article.deadline-sender-item'))return;
   riwayatPanel.dataset.riwayatFilterReady='1';

   const bar=document.createElement('div');bar.className='rpt-filter-bar';
   bar.innerHTML='<div class="rpt-filter-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" autocomplete="off" placeholder="Cari perihal atau satuan tujuan..." aria-label="Cari perihal atau satuan tujuan..."></div><select class="rpt-filter-select" aria-label="Filter status"><option value="all">Semua Status</option><option value="Disetujui">Disetujui</option><option value="Ditolak">Ditolak</option><option value="Terlambat">Terlambat</option><option value="Dibatalkan">Dibatalkan</option></select><select class="rpt-filter-select" aria-label="Urutkan"><option value="terbaru">Arsip Terbaru</option><option value="terlama">Arsip Terlama</option></select><span class="rpt-filter-count"></span>';
   // Sisipin bar tepat setelah .request-head (di DALAM .danpus-request-panel),
   // pola sama persis dengan initCardFilter() daftar aktif.
   const head=riwayatPanel.querySelector('.request-head');
   if(head){head.insertAdjacentElement('afterend',bar);}else{riwayatList.parentNode.insertBefore(bar,riwayatList);}

   const emptyBox=document.createElement('div');
   emptyBox.className='empty-state';emptyBox.style.display='none';
   emptyBox.innerHTML='<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Tidak ada arsip laporan yang sesuai dengan pencarian/filter.</div>';
   riwayatList.parentNode.insertBefore(emptyBox,riwayatList.nextSibling);

   const input=bar.querySelector('input');
   const selects=bar.querySelectorAll('select');
   const statusSelect=selects[0],sortSelect=selects[1];
   const count=bar.querySelector('.rpt-filter-count');

   function apply(){
     // Kartu yang lagi fade-out (data-removing) DIKECUALIKAN -- kalau ikut
     // diproses di sini dia bisa kena display:none atau ke-FLIP-reorder di
     // tengah animasi keluarnya -> kelihatan "kaku"/loncat pas hilang dari
     // Riwayat (mis. Pimpinan klik Revisi). Count juga jadi salah hitung.
     const items=Array.prototype.slice.call(riwayatList.querySelectorAll(':scope > article.deadline-sender-item'))
       .filter(function(el){return el.dataset.removing!=='1';});
     const pinnedIds=getPinnedIds();
     items.sort(function(a,b){
       const aPin=pinnedIds.has(a.getAttribute('data-realtime-permintaan-id'));
       const bPin=pinnedIds.has(b.getAttribute('data-realtime-permintaan-id'));
       if(aPin!==bPin)return aPin?-1:1;
       const diff=Number(a.dataset.archivedAt)-Number(b.dataset.archivedAt);
       return sortSelect.value==='terlama'?diff:-diff;
     });
     const needsReorder=items.some(function(item,i){return item.nextElementSibling!==(items[i+1]||null)});
     if(needsReorder){
       const reduceMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
       const prevRects=reduceMotion?null:new Map();
       if(prevRects){items.forEach(function(item){if(item.style.display!=='none')prevRects.set(item,item.getBoundingClientRect());});}
       items.forEach(function(item){riwayatList.appendChild(item);});
       if(prevRects){
         items.forEach(function(item){
           const prev=prevRects.get(item);if(!prev)return;
           const next=item.getBoundingClientRect();
           const dx=prev.left-next.left,dy=prev.top-next.top;
           if(Math.abs(dx)<1&&Math.abs(dy)<1)return;
           item.style.transition='none';item.style.transform='translate('+dx+'px,'+dy+'px)';
           item.getBoundingClientRect();
           // dua rAF -- state "dari" wajib kepaint dulu sebelum transisi ke
           // "ke" dipicu, biar geser-nya nggak ke-skip/kepotong di akhir.
           (function(el){requestAnimationFrame(function(){requestAnimationFrame(function(){
             el.style.transition='transform .58s cubic-bezier(.16,1,.3,1)';el.style.transform='';
           });});})(item);
           item.addEventListener('transitionend',function handler(e){if(e.propertyName!=='transform')return;item.style.transition='';item.removeEventListener('transitionend',handler);});
         });
       }
     }
     const q=(input.value||'').trim().toLowerCase();
     const statusFilter=statusSelect.value;
     let visible=0;
     items.forEach(function(item){
       const matchesSearch=!q||(item.dataset.search||'').indexOf(q)!==-1;
       const matchesStatus=statusFilter==='all'||item.dataset.status===statusFilter;
       const match=matchesSearch&&matchesStatus;
       item.style.display=match?'':'none';
       if(match)visible++;
     });
     count.textContent=visible+' dari '+items.length+' data';
     emptyBox.style.display=visible===0?'block':'none';
   }
   input.addEventListener('input',apply);
   statusSelect.addEventListener('change',apply);
   sortSelect.addEventListener('change',apply);
   window.siberadRefreshPimpinanRiwayatFilter=apply;
   apply();
 }

 // Full-diff kartu #riwayat dari HTML server. Kembar syncPimpinanCards() tapi:
 // (a) full-diff (hapus yg hilang), bukan incremental; (b) tanpa tween nilai
 // (kartu final gak berubah nilainya); (c) target #riwayat.
 function syncRiwayatCards(itemsHtml){
   if(!riwayatList||typeof itemsHtml!=='string')return;
   if(riwayatList.querySelector('.dcard-menu.open'))return;
   const holder=document.createElement('div');holder.innerHTML=itemsHtml.trim();
   const fresh=Array.prototype.slice.call(holder.querySelectorAll(':scope > article.deadline-sender-item'));
   const freshIds={};fresh.forEach(function(c){const id=c.getAttribute('data-realtime-permintaan-id');if(id)freshIds[id]=true;});
   const pinned=getPinnedIds();
   const reduce=prefersReduce();
   let changed=false;
   Array.prototype.slice.call(riwayatList.querySelectorAll(':scope > article[data-realtime-permintaan-id]')).forEach(function(card){
     const id=card.dataset.realtimePermintaanId;
     if(freshIds[id])return;
     changed=true;
     if(reduce){card.remove();return;}
     if(card.dataset.removing==='1')return;
     card.dataset.removing='1';
     const fin=function(){card.remove();clearTimeout(t);syncRiwayatEmptyState();window.siberadRefreshPimpinanRiwayatFilter&&window.siberadRefreshPimpinanRiwayatFilter();};
     const t=setTimeout(fin,380);
     card.addEventListener('animationend',fin,{once:true});
     card.classList.add('siberad-pimpinan-card-out');
   });
   fresh.forEach(function(freshCard){
     const id=freshCard.getAttribute('data-realtime-permintaan-id');if(!id)return;
     const current=riwayatList.querySelector(':scope > article[data-realtime-permintaan-id="'+id+'"]');
     if(current){
       if(cardSignature(current)===cardSignature(freshCard))return;
       const wasHidden=current.style.display==='none';
       current.replaceWith(freshCard);
       applyPinnedState(freshCard,pinned.has(id));
       if(wasHidden)freshCard.style.display='none';
       changed=true;
     }else{
       riwayatList.insertBefore(freshCard,riwayatList.firstChild);
       applyPinnedState(freshCard,pinned.has(id));
       if(!reduce)enterCard(freshCard);
       changed=true;
     }
   });
   if(changed){
     bindRiwayatMenus();
     bindRiwayatPinButtons();
     syncRiwayatEmptyState();
     if(riwayatPanel.dataset.riwayatFilterReady!=='1')initRiwayatCardFilter();
     window.siberadRefreshPimpinanRiwayatFilter&&window.siberadRefreshPimpinanRiwayatFilter();
   }
 }

 // ?history=1 sekarang balikin { items_html, archived_ids }. items_html ->
 // kartu Riwayat (#riwayat); archived_ids -> singkirkan kartu yang barusan
 // diputuskan dari daftar Permintaan Laporan yang masih aktif (tanpa reload).
 async function loadHistory(){
   try{
     const r=await fetch(historyEndpoint+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
     if(!r.ok)return;
     const data=await r.json();
     syncRiwayatCards(data.items_html);
     removeArchivedRows(Array.isArray(data.archived_ids)?data.archived_ids:[]);
   }catch(e){}
 }
 function removeArchivedRows(ids){
   const set=new Set((ids||[]).map(String));
   const reduce=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
   Array.from(list.querySelectorAll(':scope > article[data-realtime-permintaan-id]')).forEach(function(card){
     if(!set.has(String(card.dataset.realtimePermintaanId)))return;
     if(reduce){card.remove();return;}
     if(card.dataset.removing==='1')return;
     card.dataset.removing='1';
     const fin=function(){
       card.remove();clearTimeout(t);
       syncEmptyState();
       window.siberadRefreshPimpinanPermintaanFilter&&window.siberadRefreshPimpinanPermintaanFilter();
     };
     const t=setTimeout(fin,380);
     card.addEventListener('animationend',fin,{once:true});
     card.classList.add('siberad-pimpinan-card-out');
   });
   syncEmptyState();
   window.siberadRefreshPimpinanPermintaanFilter&&window.siberadRefreshPimpinanPermintaanFilter();
 }
 function syncEmptyState(){
   const hasItems=!!list.querySelector(':scope > article[data-realtime-permintaan-id]');
   const emptyNode=list.querySelector(':scope > .empty-state');
   if(!emptyNode)return;
   emptyNode.style.display=hasItems?'none':'';
   emptyNode.setAttribute('aria-hidden',hasItems?'true':'false');
 }

 // Tanda manual kartu (tombol bulat pojok kiri-atas) -- mekanisme & key
 // localStorage SAMA PERSIS dengan initDcardPinButtons() di
 // permintaan-laporan-deadline.blade.php (satuan), cuma diduplikasi di sini
 // karena file itu gak dimuat di halaman Pimpinan. Kartu yang ditandai
 // di-pin paling atas pas sorting, lihat apply() di initCardFilter().
 const DCARD_PIN_KEY='siberadPermintaanDitandai';
 function getPinnedIds(){
   try{ const raw=localStorage.getItem(DCARD_PIN_KEY); return raw?new Set(JSON.parse(raw)):new Set(); }
   catch(e){ return new Set(); }
 }
 function savePinnedIds(set){
   try{ localStorage.setItem(DCARD_PIN_KEY,JSON.stringify(Array.from(set))); }catch(e){}
 }
 function applyPinnedState(card,pinned){
   card.dataset.ditandai=pinned?'1':'0';
   const btn=card.querySelector('.dcard-pin-btn');
   if(btn)btn.setAttribute('aria-pressed',pinned?'true':'false');
 }
 function bindPinButtons(){
   const pinned=getPinnedIds();
   list.querySelectorAll(':scope > article.deadline-sender-item').forEach(function(card){
     const id=card.getAttribute('data-realtime-permintaan-id');
     if(id)applyPinnedState(card,pinned.has(id));
   });
   list.querySelectorAll('.dcard-pin-btn').forEach(function(btn){
     if(btn.dataset.pinBound==='1')return;
     btn.dataset.pinBound='1';
     btn.addEventListener('click',function(e){
       e.stopPropagation();
       const card=btn.closest('.deadline-sender-item');
       const id=card&&card.getAttribute('data-realtime-permintaan-id');
       if(!id)return;
       const set=getPinnedIds();
       const nowPinned=!set.has(id);
       if(nowPinned)set.add(id);else set.delete(id);
       savePinnedIds(set);
       applyPinnedState(card,nowPinned);
       window.siberadRefreshPimpinanPermintaanFilter&&window.siberadRefreshPimpinanPermintaanFilter();
     });
   });
 }

 // Menu titik-3 di pojok kartu -- daftar aktif (#permintaan-laporan) nampung
 // "Arsipkan", Riwayat (#riwayat) nampung "Lihat Aktivitas" + "Revisi".
 // closeAllMenus() menyapu KEDUA list biar klik di luar nutup menu mana pun.
 function closeAllMenus(except){
   const scopes=[list];if(riwayatList)scopes.push(riwayatList);
   scopes.forEach(function(sc){
     sc.querySelectorAll('.dcard-menu').forEach(function(menu){
       if(menu===except||!menu.classList.contains('open'))return;
       menu.classList.remove('open');
       const btn=menu.previousElementSibling;
       if(btn)btn.setAttribute('aria-expanded','false');
     });
   });
 }
 function bindMenus(){
   list.querySelectorAll('.dcard-menu-toggle').forEach(function(btn){
     if(btn.dataset.menuBound==='1')return;
     btn.dataset.menuBound='1';
     btn.addEventListener('click',function(e){
       e.stopPropagation();
       const menu=btn.nextElementSibling;if(!menu)return;
       const willOpen=!menu.classList.contains('open');
       closeAllMenus();
       if(willOpen){menu.classList.add('open');btn.setAttribute('aria-expanded','true');}
     });
   });
 }
 document.addEventListener('click',function(){closeAllMenus();});

 // Konfirmasi arsip SATU permintaan sekaligus (bukan bulk-select kayak
 // sebelumnya) -- "gapapa harus satu-satu juga" per instruksi user, ganti
 // checkbox+tombol toolbar dengan aksi per-kartu lewat menu titik-3.
 function ensureArchiveConfirm(){
   let overlay=document.getElementById('danpusArchiveConfirmOverlay');if(overlay)return overlay;
   overlay=document.createElement('div');overlay.className='confirm-overlay';overlay.id='danpusArchiveConfirmOverlay';
   overlay.innerHTML='<div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="danpusArchiveConfirmTitle"><div class="confirm-icon"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M4.5 14.5V27h23V14.5"></path><path d="M3.5 13h25"></path><path d="M6 13V8.5h20V13"></path><path d="M8 8.5V5.5h17l2 3"></path><path d="M10 13l7 5.5h9V13"></path><path d="M17 18.5h9v7.5a4 4 0 0 1-4 4H10a5.5 5.5 0 0 1-5.5-5.5V14.5"></path><path d="M16 19h7"></path><path d="M18 23h5"></path></svg></div><h3 id="danpusArchiveConfirmTitle">Arsipkan Permintaan Ini?</h3><p id="danpusArchiveConfirmText"></p><div class="confirm-actions"><button type="button" class="btn" id="danpusArchiveConfirmBatal">Batal</button><button type="button" class="btn btn-primary" id="danpusArchiveConfirmYa">Ya, Arsipkan</button></div></div>';
   document.body.appendChild(overlay);
   overlay.addEventListener('click',function(e){if(e.target===overlay)closeArchiveConfirm();});
   overlay.querySelector('#danpusArchiveConfirmBatal').addEventListener('click',closeArchiveConfirm);
   return overlay;
 }
 function openArchiveConfirm(id,perihal){
   const overlay=ensureArchiveConfirm();
   overlay.querySelector('#danpusArchiveConfirmText').textContent=(perihal?'"'+perihal+'"':'Permintaan ini')+' akan dipindahkan ke Arsip Laporan.';
   // Ganti tombol "Ya" dengan clone baru tiap buka -- biar listener lama
   // (nempel ke ID permintaan SEBELUMNYA) gak ikut kebawa & numpuk.
   const yaBtn=overlay.querySelector('#danpusArchiveConfirmYa');
   const freshYa=yaBtn.cloneNode(true);
   yaBtn.replaceWith(freshYa);
   freshYa.addEventListener('click',function(){closeArchiveConfirm();archiveOne(id);});
   overlay.classList.add('open');
 }
 function closeArchiveConfirm(){document.getElementById('danpusArchiveConfirmOverlay')?.classList.remove('open');}
 document.addEventListener('keydown',function(e){if(e.key==='Escape')closeArchiveConfirm();});

 async function archiveOne(id){
   if(busy||!id)return;busy=true;
   const btn=list.querySelector('.dcard-archive-btn[data-permintaan-id="'+id+'"]');
   if(btn)btn.disabled=true;
   try{
     const form=new FormData();form.append('archive_mode','1');form.append('permintaan_laporan_ids[]',id);
     const token=csrf();
     const r=await fetch(archiveEndpoint,{method:'POST',credentials:'same-origin',body:form,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest',...(token?{'X-CSRF-TOKEN':token}:{})}});
     const data=await r.json().catch(()=>({}));
     if(!r.ok)throw new Error(data.message||'Permintaan gagal diarsipkan.');
     // archive() sekarang cuma balikin archived_ids -- kartu ilang dari daftar
     // aktif via animasi, lalu kartu Riwayat ditarik ulang lewat loadHistory().
     removeArchivedRows(Array.isArray(data.archived_ids)?data.archived_ids:[]);
     loadHistory();
     window.siberadShowToast?.('success',data.message||'Permintaan berhasil dipindahkan ke Arsip Laporan.');
   }catch(error){
     window.siberadShowToast?.('error',error.message||'Permintaan gagal diarsipkan.');
     if(btn)btn.disabled=false;
   }finally{
     busy=false;
   }
 }
 function bindArchiveButtons(){
   list.querySelectorAll('.dcard-archive-btn').forEach(function(btn){
     if(btn.dataset.archiveBound==='1')return;
     btn.dataset.archiveBound='1';
     btn.addEventListener('click',function(){
       closeAllMenus();
       const id=btn.dataset.permintaanId,perihal=btn.dataset.perihal||'';
       if(!id)return;
       openArchiveConfirm(id,perihal);
     });
   });
 }

 // Pencarian+filter status+sort kartu -- pengganti initReportFilter({
 // sectionId:'permintaan-laporan', tableSelector:'.request-table', ...})
 // di danpus-report-table-filter.blade.php yang otomatis berhenti kerja
 // (no-op) begitu tabelnya diganti jadi grid kartu ini.
 function initCardFilter(){
   if(panel.dataset.cardFilterReady==='1')return;
   const initialItems=Array.prototype.slice.call(list.querySelectorAll(':scope > article.deadline-sender-item'));
   if(!initialItems.length)return;
   panel.dataset.cardFilterReady='1';

   const bar=document.createElement('div');bar.className='rpt-filter-bar';
   bar.innerHTML='<div class="rpt-filter-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" autocomplete="off" placeholder="Cari perihal atau satuan tujuan..." aria-label="Cari perihal atau satuan tujuan..."></div><select class="rpt-filter-select" aria-label="Filter status"><option value="all">Semua Status</option><option value="Terbaru">Terbaru</option><option value="Sedang diproses">Sedang diproses</option><option value="Menunggu">Menunggu</option><option value="Revisi">Revisi</option><option value="Terlambat">Terlambat</option><option value="Dibatalkan">Dibatalkan</option></select><select class="rpt-filter-select" aria-label="Urutkan"><option value="terdekat">Deadline Terdekat</option><option value="terjauh">Deadline Terjauh</option></select><span class="rpt-filter-count"></span>';
   const head=panel.querySelector('.request-head');
   if(head){head.insertAdjacentElement('afterend',bar);}else{list.parentNode.insertBefore(bar,list);}

   const emptyBox=document.createElement('div');
   emptyBox.className='empty-state';emptyBox.style.display='none';
   emptyBox.innerHTML='<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Tidak ada permintaan laporan yang sesuai dengan pencarian/filter.</div>';
   list.parentNode.insertBefore(emptyBox,list.nextSibling);

   const input=bar.querySelector('input');
   const selects=bar.querySelectorAll('select');
   const statusSelect=selects[0],sortSelect=selects[1];
   const count=bar.querySelector('.rpt-filter-count');

   function apply(){
     // Kartu yang lagi fade-out (data-removing) dikecualikan biar animasi
     // keluarnya nggak keganggu display:none / FLIP-reorder (lihat catatan
     // sama di apply() Riwayat).
     const items=Array.prototype.slice.call(list.querySelectorAll(':scope > article.deadline-sender-item'))
       .filter(function(el){return el.dataset.removing!=='1';});
     const pinnedIds=getPinnedIds();
     // Prioritas urutan kartu (tier). Deadline (Terdekat/Terjauh) cuma jadi
     // urutan DI DALAM tiap tier -- bukan penentu utama -- biar kartu yang
     // butuh perhatian Pimpinan nggak ke-kubur cuma gara-gara deadline-nya
     // masih jauh (pill deadline sebagian status ini malah disembunyiin, jadi
     // alasan urutannya nggak keliatan). Urutan disepakati sama user: yang
     // butuh perhatian Pimpinan dulu, lalu yang lagi jalan di satuan, lalu
     // grup "mandek" (Terlambat + Dibatalkan -- dua-duanya archive-eligible &
     // nggak ada progres), lalu yang selesai:
     //   Ditandai > Menunggu > Terbaru > Revisi > Sedang diproses > Terlambat
     //   > Dibatalkan > Disetujui/Ditolak
     // KHUSUS sisi Pimpinan -- di satuan "Menunggu pemeriksaan" justru nggak
     // ada aksi, comparator-nya (permintaan-laporan-deadline.blade.php) sengaja
     // beda (Ditandai > belum-dikerjakan > deadline).
     const statusTier=function(s){
       switch(s){
         case 'Menunggu': return 1;         // laporan masuk, nunggu Terima/Tolak Pimpinan
         case 'Terbaru': return 2;          // baru dibuat, biar request baru nggak kekubur
         case 'Revisi': return 3;           // ditolak-untuk-revisi, masih hidup, bola di satuan
         case 'Sedang diproses': return 4;  // satuan lagi kerja, no aksi Pimpinan
         case 'Terlambat': return 5;        // deadline jebol, laporan nggak masuk -- mandek, archive-eligible
         case 'Dibatalkan': return 6;       // dibatalin Pimpinan sendiri, parkir, archive-eligible
         case 'Disetujui':
         case 'Ditolak': return 7;          // selesai (biasanya sudah pindah ke Arsip)
         default: return 4;
       }
     };
     items.sort(function(a,b){
       const aPin=pinnedIds.has(a.getAttribute('data-realtime-permintaan-id'));
       const bPin=pinnedIds.has(b.getAttribute('data-realtime-permintaan-id'));
       if(aPin!==bPin)return aPin?-1:1;
       const at=statusTier(a.dataset.status),bt=statusTier(b.dataset.status);
       if(at!==bt)return at-bt;
       const diff=Number(a.dataset.deadlineAt)-Number(b.dataset.deadlineAt);
       return sortSelect.value==='terjauh'?-diff:diff;
     });
     const needsReorder=items.some(function(item,i){return item.nextElementSibling!==(items[i+1]||null)});
     if(needsReorder){
       const reduceMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
       const prevRects=reduceMotion?null:new Map();
       if(prevRects){items.forEach(function(item){if(item.style.display!=='none')prevRects.set(item,item.getBoundingClientRect());});}
       items.forEach(function(item){list.appendChild(item);});
       if(prevRects){
         items.forEach(function(item){
           const prev=prevRects.get(item);if(!prev)return;
           const next=item.getBoundingClientRect();
           const dx=prev.left-next.left,dy=prev.top-next.top;
           if(Math.abs(dx)<1&&Math.abs(dy)<1)return;
           item.style.transition='none';item.style.transform='translate('+dx+'px,'+dy+'px)';
           item.getBoundingClientRect();
           // dua rAF -- state "dari" wajib kepaint dulu sebelum transisi ke
           // "ke" dipicu, biar geser-nya nggak ke-skip/kepotong di akhir.
           (function(el){requestAnimationFrame(function(){requestAnimationFrame(function(){
             el.style.transition='transform .58s cubic-bezier(.16,1,.3,1)';el.style.transform='';
           });});})(item);
           item.addEventListener('transitionend',function handler(e){if(e.propertyName!=='transform')return;item.style.transition='';item.removeEventListener('transitionend',handler);});
         });
       }
     }
     const q=(input.value||'').trim().toLowerCase();
     const statusFilter=statusSelect.value;
     let visible=0;
     items.forEach(function(item){
       const matchesSearch=!q||(item.dataset.search||'').indexOf(q)!==-1;
       const matchesStatus=statusFilter==='all'||item.dataset.status===statusFilter;
       const match=matchesSearch&&matchesStatus;
       item.style.display=match?'':'none';
       if(match)visible++;
     });
     count.textContent=visible+' dari '+items.length+' data';
     emptyBox.style.display=visible===0?'block':'none';
   }
   input.addEventListener('input',apply);
   statusSelect.addEventListener('change',apply);
   sortSelect.addEventListener('change',apply);
   window.siberadRefreshPimpinanPermintaanFilter=apply;
   apply();
 }

 function rebind(){bindMenus();bindArchiveButtons();bindPinButtons();}

 // ── Realtime kartu (status / progres bar / "x/y tugas selesai" / tombol) ──
 // Kartu Pimpinan dulu cuma di-render server sekali. Di sini kita poll
 // perubahan tiap 4 dtk: kartu yang datanya berubah di-replaceWith() HTML
 // fresh dari server, kartu baru disisipin, semua dengan animasi halus.
 // Pola sama dengan sisi satuan (laporan-role-realtime-sync.blade.php).
 // Kartu yang HILANG (diarsip/diputuskan) tetap ditangani loadHistory().
 //
 // Selalu kirim since=0 (minta daftar aktif LENGKAP tiap poll), PERSIS
 // kayak syncRequestList() di satuan. Dulu di sini pakai cursor
 // since=server_time (incremental) -- tapi teks hitung mundur deadline &
 // warna urgensi (near/bad) itu properti TERHITUNG dari deadline_at vs
 // now(), updated_at-nya gak ikut berubah. Jadi kartu yang cuma "makin
 // lama terlambat" gak pernah dikirim ulang -> pill deadline-nya beku di
 // Pimpinan (di satuan normal karena selalu full). Diff cardSignature()
 // di bawah tetap nyaring: kartu yang teksnya gak berubah gak di-replace.
 const cardsEndpoint='{{ route('permintaan-laporan.realtime') }}?pimpinan=1&since=0';
 let cardsBusy=false;
 function prefersReduce(){return window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;}
 // Bandingkan kartu tanpa atribut/kelas yang dikelola klien (pin, menu
 // kebuka, flag *-bound, style sisa FLIP, kelas animasi) -- biar cuma
 // ke-replace kalau data server-nya yang beneran beda.
 function cardSignature(el){
   const c=el.cloneNode(true);
   c.removeAttribute('style');c.removeAttribute('data-ditandai');
   c.classList.remove('siberad-pimpinan-card-in','siberad-pimpinan-card-out');
   c.querySelectorAll('[data-menu-bound],[data-archive-bound],[data-pin-bound],[data-riwayat-bound]').forEach(function(n){n.removeAttribute('data-menu-bound');n.removeAttribute('data-archive-bound');n.removeAttribute('data-pin-bound');n.removeAttribute('data-riwayat-bound');});
   c.querySelectorAll('.dcard-pin-btn[aria-pressed]').forEach(function(n){n.setAttribute('aria-pressed','false');});
   c.querySelectorAll('.dcard-menu.open').forEach(function(n){n.classList.remove('open');});
   c.querySelectorAll('[aria-expanded="true"]').forEach(function(n){n.setAttribute('aria-expanded','false');});
   // Buang style transient sisa animasi (transition/opacity/transform), lalu
   // NORMALISASI serialisasi style yang tersisa (mis. width:X% -> "width: X%;")
   // biar sama antara kartu yang habis dianimasikan & HTML fresh dari server.
   c.querySelectorAll('[style]').forEach(function(n){
     ['transition','-webkit-transition','opacity','transform'].forEach(function(p){n.style.removeProperty(p);});
     const cs=n.style.cssText;
     if(cs)n.setAttribute('style',cs);else n.removeAttribute('style');
   });
   // Rapikan whitespace text-node di sekitar tag (indentasi Blade) supaya
   // teks yang di-set via JS (tanpa indentasi) tetap kebandingin sama.
   return c.outerHTML.replace(/>\s+/g,'>').replace(/\s+</g,'<');
 }
 function playOnce(card,cls,ms){
   card.classList.add(cls);
   const done=function(){card.classList.remove(cls);card.removeEventListener('animationend',done);clearTimeout(t);};
   // animationend + fallback timeout -- kalau kartu lagi di tab tersembunyi,
   // animasinya gak jalan & animationend gak pernah nembak.
   const t=setTimeout(done,ms);
   card.addEventListener('animationend',done);
 }
 function enterCard(card){
   if(prefersReduce())return;
   playOnce(card,'siberad-pimpinan-card-in',700);
 }

 // ── Animasi DELTA nilai kartu (progres bar / % / "x/y tugas" / status) ──
 // replaceWith() bikin nilai baru "nyeplak" karena elemennya benar-benar
 // baru. Jadi: rekam nilai LAMA sebelum replace, lalu seed nilai lama ke
 // kartu baru & animasikan naik/berubah perlahan ke nilai barunya.
 function cardSnapshot(card){
   const val=card.querySelector('.dcard-progress-value');
   const fill=card.querySelector('.dcard-progress-fill');
   const tasks=card.querySelector('.dcard-tasks-summary');
   const pill=card.querySelector('.dcard-status-pill');
   const tasksText=tasks?tasks.textContent.replace(/\s+/g,' ').trim():'';
   return {
     pct: val?(parseInt((val.textContent||'').replace(/\D/g,''),10)||0):null,
     fillW: fill?(fill.style.width||''):null,
     tasksText: tasksText,
     tasksNums: tasksText.match(/^(\d+)\s*\/\s*(\d+)/),
     statusText: pill?pill.textContent.replace(/\s+/g,' ').trim():null,
     statusClass: pill?pill.className:null
   };
 }
 function tweenNum(from,to,ms,onStep){
   from=Number(from)||0;to=Number(to)||0;
   if(from===to){onStep(to);return;}
   const t0=performance.now();
   (function frame(now){
     const p=Math.min(1,(now-t0)/ms);
     const e=1-Math.pow(1-p,3);
     onStep(p>=1?to:(from+(to-from)*e));
     if(p<1)requestAnimationFrame(frame);
   })(performance.now());
 }
 function setTasksText(el,text){
   Array.prototype.slice.call(el.childNodes).forEach(function(n){if(n.nodeType===3)n.remove();});
   el.appendChild(document.createTextNode(text));
 }
 function crossfadeText(el,oldText,newText,isTasks){
   isTasks?setTasksText(el,oldText):(el.textContent=oldText);
   el.style.transition='none';el.style.opacity='1';
   void el.offsetWidth;
   el.style.transition='opacity .16s ease';
   el.style.opacity='0';
   setTimeout(function(){
     isTasks?setTasksText(el,newText):(el.textContent=newText);
     el.style.opacity='0';void el.offsetWidth;el.style.opacity='1';
     setTimeout(function(){el.style.transition='';el.style.opacity='';},200);
   },170);
 }
 function animateCardDelta(freshCard,old){
   if(prefersReduce())return;
   const fill=freshCard.querySelector('.dcard-progress-fill');
   const val=freshCard.querySelector('.dcard-progress-value');
   const tasks=freshCard.querySelector('.dcard-tasks-summary');
   const pill=freshCard.querySelector('.dcard-status-pill');
   // Progres bar: seed lebar lama -> transisi mulus ke lebar baru.
   if(fill&&old.fillW!=null){
     const target=fill.style.width||'';
     if(target!==old.fillW){
       fill.style.transition='none';fill.style.width=old.fillW;
       void fill.offsetWidth;
       fill.style.transition='width .7s cubic-bezier(.4,0,.2,1)';
       requestAnimationFrame(function(){fill.style.width=target;});
       setTimeout(function(){fill.style.transition='';},820);
     }
   }
   // Angka persen: hitung naik/turun perlahan.
   if(val&&old.pct!=null){
     const target=parseInt((val.textContent||'').replace(/\D/g,''),10)||0;
     if(target!==old.pct)tweenNum(old.pct,target,700,function(v){val.textContent=Math.round(v)+'%';});
   }
   // "x/y tugas selesai": tween angka X kalau Y sama, selain itu crossfade.
   if(tasks&&old.tasksText){
     const now=tasks.textContent.replace(/\s+/g,' ').trim();
     if(now!==old.tasksText){
       const m=now.match(/^(\d+)\s*\/\s*(\d+)/);
       if(m&&old.tasksNums&&m[2]===old.tasksNums[2]){
         const y=m[2];
         tweenNum(parseInt(old.tasksNums[1],10),parseInt(m[1],10),700,function(v){setTasksText(tasks,Math.round(v)+'/'+y+' tugas selesai');});
       }else{
         crossfadeText(tasks,old.tasksText,now,true);
       }
     }
   }
   // Status pill: crossfade teks + ganti kelas warna (naik lalu turun).
   if(pill&&old.statusText!=null){
     const newText=pill.textContent.replace(/\s+/g,' ').trim();
     const newClass=pill.className;
     if(newText!==old.statusText||newClass!==old.statusClass){
       pill.textContent=old.statusText;pill.className=old.statusClass;
       pill.style.transition='none';pill.style.opacity='1';pill.style.transform='none';
       void pill.offsetWidth;
       pill.style.transition='opacity .17s ease,transform .17s ease';
       pill.style.opacity='0';pill.style.transform='translateY(-3px)';
       setTimeout(function(){
         pill.textContent=newText;pill.className=newClass;
         pill.style.opacity='0';pill.style.transform='translateY(3px)';
         void pill.offsetWidth;
         pill.style.opacity='1';pill.style.transform='none';
         setTimeout(function(){pill.style.transition='';pill.style.transform='';pill.style.opacity='';},240);
       },180);
     }
   }
 }
 async function syncPimpinanCards(){
   if(cardsBusy||busy||document.hidden)return;
   if(list.querySelector('.dcard-menu.open'))return; // jangan ganggu menu titik-3 yang lagi kebuka
   cardsBusy=true;
   try{
     const r=await fetch(cardsEndpoint+'&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}});
     if(!r.ok)return;
     const data=await r.json();
     if(typeof data.items_html!=='string')return;
     const holder=document.createElement('div');holder.innerHTML=data.items_html.trim();
     const fresh=Array.prototype.slice.call(holder.querySelectorAll(':scope > article.deadline-sender-item'));
     if(!fresh.length)return;
     const pinned=getPinnedIds();
     let changed=false,insertedAny=false;
     fresh.forEach(function(freshCard){
       const id=freshCard.getAttribute('data-realtime-permintaan-id');if(!id)return;
       const current=list.querySelector(':scope > article[data-realtime-permintaan-id="'+id+'"]');
       if(current){
         if(cardSignature(current)===cardSignature(freshCard))return;
         const wasHidden=current.style.display==='none';
         const snap=cardSnapshot(current);
         current.replaceWith(freshCard);
         applyPinnedState(freshCard,pinned.has(id));
         if(wasHidden)freshCard.style.display='none';
         animateCardDelta(freshCard,snap);
         // Kalau modal "Lihat Progres" lagi kebuka buat permintaan ini,
         // checklist-nya ikut ke-refresh live (bukan cuma pas dibuka ulang).
         window.siberadRefreshPimpinanProgres&&window.siberadRefreshPimpinanProgres(freshCard);
         changed=true;
       }else{
         list.insertBefore(freshCard,list.firstChild);
         applyPinnedState(freshCard,pinned.has(id));
         enterCard(freshCard);
         changed=true;insertedAny=true;
       }
     });
     if(changed){
       rebind();
       syncEmptyState();
       if(insertedAny&&panel.dataset.cardFilterReady!=='1')initCardFilter();
       window.siberadRefreshPimpinanPermintaanFilter&&window.siberadRefreshPimpinanPermintaanFilter();
     }
   }catch(e){}
   finally{cardsBusy=false;}
 }

 new MutationObserver(rebind).observe(list,{childList:true});
 rebind();syncEmptyState();initCardFilter();

 // Riwayat Laporan (#riwayat) -- kartu awal dari server: bind menu/pin + filter,
 // lalu ikut disegarkan tiap siklus loadHistory() (interval 5 dtk di bawah).
 if(riwayatList){
   bindRiwayatMenus();bindRiwayatPinButtons();initRiwayatCardFilter();syncRiwayatEmptyState();
   new MutationObserver(function(){bindRiwayatMenus();bindRiwayatPinButtons();}).observe(riwayatList,{childList:true});
 }

 loadHistory();window.setInterval(()=>{if(!document.hidden&&!busy)loadHistory()},5000);
 syncPimpinanCards();window.setInterval(syncPimpinanCards,4000);
 document.addEventListener('visibilitychange',function(){if(!document.hidden)syncPimpinanCards();});

 // Dipakai setelah aksi AJAX (Batalkan / Edit Deadline dari modal Lihat
 // Progres) buat nyegerin kartu + isi modal SEGERA, tanpa nunggu poll 4-5 dtk.
 window.siberadSyncPimpinanCardsNow=function(){syncPimpinanCards();setTimeout(syncPimpinanCards,500);};
 window.siberadLoadHistoryNow=loadHistory;
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initDanpusArchiveMode,{once:true});else initDanpusArchiveMode();
})();
</script>
