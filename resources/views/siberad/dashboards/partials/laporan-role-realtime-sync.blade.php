<style>
/* Baris tabel yang disisipkan/diperbarui live lewat syncBody() -- fade+slide
   halus buat baris BARU, kedip warna gold sebentar buat baris yang cuma
   BERUBAH (mis. status laporan), senada sama animasi .tab-panel.active
   (fadeIn .25s) yang sudah dipakai di seluruh dashboard. */
@keyframes siberadRowIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
@keyframes siberadRowUpdate{0%{background:var(--gold-dim)}100%{background:transparent}}
.siberad-row-in{animation:siberadRowIn .35s ease}
.siberad-row-updated{animation:siberadRowUpdate 1.2s ease}
/* Kartu Permintaan Laporan yang hilang dari daftar aktif (selesai/dibatalkan)
   -- fade-out halus, bukan lenyap mendadak. Perubahan nilai (progres/angka/
   status) dianimasikan langsung di elemennya, lihat animateCardDelta(). */
@keyframes siberadCardOut{to{opacity:0;transform:translateY(-6px) scale(.97)}}
#permintaan-laporan .deadline-sender-item.siberad-card-leaving,#riwayat .deadline-sender-item.siberad-card-leaving{animation:siberadCardOut .3s ease forwards;pointer-events:none}
@media(prefers-reduced-motion:reduce){#permintaan-laporan .deadline-sender-item.siberad-card-leaving,#riwayat .deadline-sender-item.siberad-card-leaving{animation:none!important}}
</style>
<script>
(function(){
    const endpoint='{{ route('laporan.log-aktivitas.realtime') }}';
    const requestEndpoint='{{ route('permintaan-laporan.realtime') }}';
    let busy=false;
    let timer=null;
    // Render awal tabel Riwayat/Masuk/Monitoring pakai markup inline di
    // laporan-role.blade.php (bukan partial yang sama persis dengan yang
    // dirender endpoint realtime) -- poll PERTAMA dipakai buat "menyamakan"
    // baseline tanpa animasi, biar beda format HTML kecil (bukan perubahan
    // data beneran) tidak keliru kelihatan kayak semua baris "berubah" &
    // ikut kedip pas dashboard baru dibuka.
    let animateSync=false;

    // window.siberadOpenEditProgres/bindInitialEditButtons yang dulu di sini
    // SUDAH DIHAPUS -- #riwayat sekarang kartu (permintaan-laporan-item.
    // blade.php, sama kayak #permintaan-laporan), tombol Edit/Lihat
    // Progres-nya udah ditangani lengkap oleh initEditProgresButtons/
    // initLockedTaskSteps (permintaan-laporan-deadline.blade.php, dipanggil
    // ulang lewat window.siberadRebindPermintaanActions di bawah). Fungsi
    // lama itu gak tau soal mode readonly/locked -- kalau tetap dipanggil,
    // dia bakal ikut masang listener KEDUA yang bentrok di tombol yang sama.

    function stat(label,value){
        document.querySelectorAll('#dashboard .stat-card .lbl').forEach(function(el){
            if((el.textContent||'').trim().toLowerCase()!==label.toLowerCase())return;
            const valueEl=el.closest('.stat-card')?.querySelector('.val');
            if(valueEl)valueEl.textContent=String(value ?? 0);
        });
    }

    function syncChart(stats){
        const canvas=document.getElementById('laporanChart');
        if(!canvas||!window.Chart)return;
        const chart=Chart.getChart(canvas);
        if(!chart)return;
        const data=chart.data?.datasets?.[0]?.data;
        if(!data)return;
        data[0]=Number(stats.disetujui||0);data[1]=Number(stats.ditolak||0);data[2]=Number(stats.terlambat||0);data[3]=Number(stats.dibatalkan||0);
        chart.update('none');
    }

    function rowKey(row){
        const cells=Array.from(row.cells||[]).slice(0,5).map(function(cell){return (cell.textContent||'').replace(/\s+/g,' ').trim();}).join('|');
        return [row.dataset.search||'',row.dataset.prioritas||'',cells].join('§');
    }

    function buildRows(html){
        const holder=document.createElement('tbody');
        holder.innerHTML=String(html||'').trim();
        return Array.from(holder.children);
    }

    function syncBody(selector,html){
        const body=document.querySelector(selector);
        if(!body||typeof html!=='string')return;

        const freshRows=buildRows(html);
        const existingRows=Array.from(body.children);
        const existingById=new Map();
        const existingByKey=new Map();
        const used=new Set();

        existingRows.forEach(function(row){
            if(row.matches('tr[data-laporan-id]')){
                const id=String(row.dataset.laporanId||'');
                if(id)existingById.set(id,row);
            }
            if(row.matches('tr[data-search]')){
                const key=rowKey(row);
                if(!existingByKey.has(key))existingByKey.set(key,[]);
                existingByKey.get(key).push(row);
            }
        });

        const ordered=[];
        freshRows.forEach(function(fresh){
            let current=null;
            const id=fresh.dataset?.laporanId ? String(fresh.dataset.laporanId) : '';
            if(id)current=existingById.get(id)||null;

            if(!current && fresh.matches('tr[data-search]')){
                const bucket=existingByKey.get(rowKey(fresh));
                while(bucket?.length){
                    const candidate=bucket.shift();
                    if(!used.has(candidate)){current=candidate;break;}
                }
            }

            if(current){
                used.add(current);
                if(current.outerHTML!==fresh.outerHTML){
                    if(animateSync)fresh.classList.add('siberad-row-updated');
                    current.replaceWith(fresh);
                    current=fresh;
                }
                ordered.push(current);
            }else{
                if(animateSync)fresh.classList.add('siberad-row-in');
                ordered.push(fresh);
            }
        });

        existingRows.forEach(function(row){
            if(!used.has(row) && !ordered.includes(row))row.remove();
        });

        ordered.forEach(function(row,index){
            const target=body.children[index];
            if(target!==row)body.insertBefore(row,target||null);
        });
    }

    function syncReports(data){
        // #riwayat BUKAN tabel .dtbl lagi (sekarang kartu, sama kayak
        // #permintaan-laporan) -- sent_html dari endpoint ini sengaja gak
        // dipakai lagi di sini, Riwayat Laporan cukup nyegerin diri sendiri
        // pas halaman di-reload (kejadiannya jarang: cuma pas Pimpinan
        // putuskan/arsipkan sesuatu).
        if(data.incoming_html!==undefined)syncBody('#masuk .dtbl tbody',data.incoming_html);
        if(data.monitoring_html)syncBody('#monitoring .dtbl tbody',data.monitoring_html);
        const stats=data.role_stats||{};
        stat('Laporan Masuk',stats.masuk);stat('Disetujui',stats.disetujui);stat('Ditolak',stats.ditolak);stat('Terlambat',stats.terlambat);stat('Dibatalkan',stats.dibatalkan);syncChart(stats);
        animateSync=true;
    }

    // Item BARU (belum pernah tampil) sengaja BUKAN tugas fungsi ini --
    // partials/permintaan-laporan-realtime.blade.php yang polling & nge-insert
    // item baru itu (sekalian nge-toast "Permintaan laporan baru masuk.").
    // Kalau di sini juga ikut nge-insert item baru, dua poller yang jalan
    // independen (2500ms vs 3000ms) jadi rebutan siapa duluan yang nganggap
    // suatu ID "baru" -- akibatnya toast itu jadi tidak bisa diandalkan
    // (kadang kepicu, kadang enggak, tergantung timing). Fungsi ini HANYA
    // menyegarkan (update konten) & menghapus (kalau sudah tidak lagi masuk
    // hasil query aktif, misal permintaannya selesai/dibatalkan) item yang
    // SUDAH ada di DOM.
    // ── Helper animasi delta kartu (sama persis dengan sisi Pimpinan,
    //    danpus-permintaan-arsip-mode.blade.php) -- progres bar keisi
    //    perlahan, angka % & "x/y tugas" nge-count, status pill crossfade. ──
    function plReduce(){return window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;}
    function cardSig(el){
        const c=el.cloneNode(true);
        c.removeAttribute('style');c.removeAttribute('data-ditandai');
        c.classList.remove('siberad-card-leaving','siberad-row-in','siberad-row-updated');
        c.querySelectorAll('.dcard-pin-btn[aria-pressed]').forEach(function(n){n.setAttribute('aria-pressed','false');});
        c.querySelectorAll('.dcard-menu.open').forEach(function(n){n.classList.remove('open');});
        c.querySelectorAll('[aria-expanded="true"]').forEach(function(n){n.setAttribute('aria-expanded','false');});
        // Buang SEMUA atribut flag "*bound*" (dipasang JS saat bind listener,
        // bukan dari server) supaya signature gak beda gara-gara itu.
        c.querySelectorAll('*').forEach(function(n){
            Array.prototype.slice.call(n.attributes||[]).forEach(function(a){if(/bound/i.test(a.name))n.removeAttribute(a.name);});
        });
        c.querySelectorAll('[style]').forEach(function(n){
            ['transition','-webkit-transition','opacity','transform'].forEach(function(p){n.style.removeProperty(p);});
            const cs=n.style.cssText;if(cs)n.setAttribute('style',cs);else n.removeAttribute('style');
        });
        return c.outerHTML.replace(/>\s+/g,'>').replace(/\s+</g,'<');
    }
    function cardSnap(card){
        const val=card.querySelector('.dcard-progress-value');
        const fill=card.querySelector('.dcard-progress-fill');
        const tasks=card.querySelector('.dcard-tasks-summary');
        const pill=card.querySelector('.dcard-status-pill');
        const tt=tasks?tasks.textContent.replace(/\s+/g,' ').trim():'';
        return {pct:val?(parseInt((val.textContent||'').replace(/\D/g,''),10)||0):null,fillW:fill?(fill.style.width||''):null,tasksText:tt,tasksNums:tt.match(/^(\d+)\s*\/\s*(\d+)/),statusText:pill?pill.textContent.replace(/\s+/g,' ').trim():null,statusClass:pill?pill.className:null};
    }
    function tweenNum(from,to,ms,onStep){from=Number(from)||0;to=Number(to)||0;if(from===to){onStep(to);return;}const t0=performance.now();(function frame(now){const p=Math.min(1,(now-t0)/ms);const e=1-Math.pow(1-p,3);onStep(p>=1?to:(from+(to-from)*e));if(p<1)requestAnimationFrame(frame);})(performance.now());}
    function setTasksText(el,text){Array.prototype.slice.call(el.childNodes).forEach(function(n){if(n.nodeType===3)n.remove();});el.appendChild(document.createTextNode(text));}
    function crossfadeText(el,oldText,newText,isTasks){isTasks?setTasksText(el,oldText):(el.textContent=oldText);el.style.transition='none';el.style.opacity='1';void el.offsetWidth;el.style.transition='opacity .16s ease';el.style.opacity='0';setTimeout(function(){isTasks?setTasksText(el,newText):(el.textContent=newText);el.style.opacity='0';void el.offsetWidth;el.style.opacity='1';setTimeout(function(){el.style.transition='';el.style.opacity='';},200);},170);}
    function animateCardDelta(freshCard,old){
        if(plReduce())return;
        const fill=freshCard.querySelector('.dcard-progress-fill');
        const val=freshCard.querySelector('.dcard-progress-value');
        const tasks=freshCard.querySelector('.dcard-tasks-summary');
        const pill=freshCard.querySelector('.dcard-status-pill');
        if(fill&&old.fillW!=null){const target=fill.style.width||'';if(target!==old.fillW){fill.style.transition='none';fill.style.width=old.fillW;void fill.offsetWidth;fill.style.transition='width .7s cubic-bezier(.4,0,.2,1)';requestAnimationFrame(function(){fill.style.width=target;});setTimeout(function(){fill.style.transition='';},820);}}
        if(val&&old.pct!=null){const target=parseInt((val.textContent||'').replace(/\D/g,''),10)||0;if(target!==old.pct)tweenNum(old.pct,target,700,function(v){val.textContent=Math.round(v)+'%';});}
        if(tasks&&old.tasksText){const now=tasks.textContent.replace(/\s+/g,' ').trim();if(now!==old.tasksText){const m=now.match(/^(\d+)\s*\/\s*(\d+)/);if(m&&old.tasksNums&&m[2]===old.tasksNums[2]){const y=m[2];tweenNum(parseInt(old.tasksNums[1],10),parseInt(m[1],10),700,function(v){setTasksText(tasks,Math.round(v)+'/'+y+' tugas selesai');});}else{crossfadeText(tasks,old.tasksText,now,true);}}}
        if(pill&&old.statusText!=null){const newText=pill.textContent.replace(/\s+/g,' ').trim();const newClass=pill.className;if(newText!==old.statusText||newClass!==old.statusClass){pill.textContent=old.statusText;pill.className=old.statusClass;pill.style.transition='none';pill.style.opacity='1';pill.style.transform='none';void pill.offsetWidth;pill.style.transition='opacity .17s ease,transform .17s ease';pill.style.opacity='0';pill.style.transform='translateY(-3px)';setTimeout(function(){pill.textContent=newText;pill.className=newClass;pill.style.opacity='0';pill.style.transform='translateY(3px)';void pill.offsetWidth;pill.style.opacity='1';pill.style.transform='none';setTimeout(function(){pill.style.transition='';pill.style.transform='';pill.style.opacity='';},240);},180);}}
    }

    function syncRequestList(){
        fetch(requestEndpoint+'?since=0&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r=>r.ok?r.json():null).then(data=>{
                if(!data)return;
                const list=document.querySelector('#permintaan-laporan .deadline-sender-list');if(!list||typeof data.items_html!=='string')return;
                const incoming=document.createElement('div');incoming.innerHTML=data.items_html;
                const fresh=[...incoming.querySelectorAll(':scope > article.deadline-sender-item')];
                const freshById=new Map(fresh.map(el=>[String(el.dataset.realtimePermintaanId||''),el]));
                const existing=[...list.querySelectorAll(':scope > article[data-realtime-permintaan-id]')];
                const existingIds=new Set(existing.map(el=>String(el.dataset.realtimePermintaanId||'')));
                const reduce=plReduce();
                let touched=false;
                existing.forEach(function(item){
                    const id=String(item.dataset.realtimePermintaanId||'');
                    const replacement=freshById.get(id);
                    if(replacement){
                        // Diff-check dulu -- cuma replace kalau data server-nya
                        // beneran beda, biar gak bikin node baru tiap 6 dtk
                        // (dulu selalu replace -> reset state transient tiap siklus).
                        if(cardSig(item)===cardSig(replacement))return;
                        // data-ditandai (tanda manual, localStorage) dibawa manual
                        // ke node baru supaya tidak "lupa" balik ke "0".
                        if(item.dataset.ditandai!==undefined) replacement.dataset.ditandai=item.dataset.ditandai;
                        const snap=cardSnap(item);
                        item.replaceWith(replacement);
                        animateCardDelta(replacement,snap);
                        touched=true;
                    }else if(id){
                        if(reduce||item.dataset.leaving==='1'){item.remove();touched=true;return;}
                        item.dataset.leaving='1';
                        const fin=function(){clearTimeout(t);item.remove();window.siberadBindPermintaanDetailButtons&&window.siberadBindPermintaanDetailButtons();window.siberadRebindPermintaanActions&&window.siberadRebindPermintaanActions();};
                        const t=setTimeout(fin,340);
                        item.addEventListener('animationend',fin,{once:true});
                        item.classList.add('siberad-card-leaving');
                        touched=true;
                    }
                });
                // Kartu yang MUNCUL LAGI di daftar aktif tapi belum ada di DOM --
                // mis. permintaan yang tadinya Selesai/Terlambat/Dibatalkan-
                // terarsip lalu dibuka ulang Pimpinan (Revisi dari Riwayat / Edit
                // Deadline). id-nya lama, jadi gak kejaring poll incremental
                // (?since=lastSeen) di permintaan-laporan-realtime.blade.php --
                // syncRequestList (?since=0) yang harus nyisipin.
                fresh.forEach(function(fEl){
                    const id=String(fEl.dataset.realtimePermintaanId||'');
                    if(!id||existingIds.has(id))return;
                    list.insertBefore(fEl,list.firstChild);
                    if(!reduce){
                        fEl.classList.add('dcard-enter');
                        fEl.addEventListener('animationend',function h(){fEl.classList.remove('dcard-enter');fEl.removeEventListener('animationend',h);});
                    }
                    existingIds.add(id);
                    touched=true;
                });
                if(touched){
                    const emptyNode=list.querySelector(':scope > .empty-state');
                    if(emptyNode)emptyNode.style.display=list.querySelector(':scope > article[data-realtime-permintaan-id]')?'none':'';
                    window.siberadBindPermintaanDetailButtons&&window.siberadBindPermintaanDetailButtons();
                    window.siberadRebindPermintaanActions&&window.siberadRebindPermintaanActions();
                    // Wizard #kirimLaporanModal yang lagi kebuka -> bangun ulang
                    // strip step-nya dari kartu fresh (mis. baru jadi Terlambat
                    // -> step belum selesai jadi merah), tanpa tutup-buka modal.
                    window.siberadRefreshWizardTopbar&&window.siberadRefreshWizardTopbar();
                }
            }).catch(function(){});
    }

    function poll(){
        if(busy)return;busy=true;
        fetch(endpoint+'?reports=1&requests=0&since=0&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}})
            .then(r=>r.ok?r.json():null).then(data=>{if(data)syncReports(data);}).catch(function(){}).finally(function(){busy=false;});
    }

    function start(){
        if(!document.getElementById('riwayat')&&!document.getElementById('masuk'))return;
        poll();timer=window.setInterval(poll,2500);
        // syncRequestList() sengaja DIPISAH dari siklus poll() 2500ms di atas
        // (dulu dipanggil bareng di situ) -- endpoint yang sama JUGA sudah
        // dipoll independen tiap 3000ms oleh permintaan-laporan-realtime.blade.php
        // (yang nanganin item BARU+toast). Kalau syncRequestList() (yang cuma
        // nanganin update/hapus item LAMA, lihat komentar di atas) ikut nempel
        // di 2500ms, query ke endpoint yang sama jadi 2x lipat buat data yang
        // tumpang-tindih. Interval lebih santai di sini masih cukup responsif
        // buat update status (bukan insert-baru yang butuh terasa instan).
        syncRequestList();window.setInterval(syncRequestList,6000);
        document.addEventListener('visibilitychange',function(){if(!document.hidden)poll();});window.addEventListener('focus',poll);
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
</script>
