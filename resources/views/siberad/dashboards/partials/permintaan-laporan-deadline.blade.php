{{-- CSS untuk section ini dipindah ke permintaan-laporan-deadline-styles.
     blade.php (di-include duluan di <head>, lihat laporan-role.blade.php)
     -- file INI (permintaan-laporan-deadline.blade.php) sebelumnya
     di-include lewat laporan-role-shell.blade.php SETELAH seluruh halaman
     utama (termasuk </html>), jadi ada jeda sesaat pas render pertama/
     refresh di mana kartu udah muncul di DOM tapi CSS-nya (termasuk
     opacity:0 buat dropdown/sidebar yang HARUSNYA nyembunyiin elemen
     sebelum dipicu buka) belum kepasang -- flash of unstyled content yang
     bikin elemen kelihatan "glitch" sesaat abis login/refresh. Script di
     bawah TETAP di sini (aman di-load belakangan, gak ada FOUC-risk buat
     JS). --}}

<script>
(function(){
    // "Kirim Laporan" sekarang berupa modal (#kirimLaporanModal) yang dibuka
    // dari section #permintaan-laporan, bukan tab tersendiri lagi -- jadi
    // tombol "Buat Laporan" di sini tinggal membuka modal itu sambil
    // mengisi form-nya, tidak perlu pindah tab.
    //
    // Modal yang sama juga dipakai buat 3 alur berbeda, dibedakan lewat
    // form.dataset.mode + progres yang akan dikirim (bukan cuma "Kirim
    // Laporan" generik lagi supaya tidak ambigu):
    //  - create + progres<100 -> checkpoint progres baru ("Update Progres")
    //  - create + progres=100 -> laporan final ("Kirim Laporan Final")
    //  - edit (checkpoint progres lama yang belum final)   ("Edit Update Progres")
    function computeLaporanTexts(mode,progresVal){
        var isFinal=progresVal>=100;
        if(mode==='edit'){
            return isFinal?{
                title:'Edit & Finalisasi Laporan',
                desc:'Perbarui data checkpoint ini sekaligus jadikan sebagai laporan final ke Pimpinan.',
                submit:'Simpan & Kirim Final',
                confirmTitle:'Kirim Laporan Final?',
                confirmBody:'Pastikan data yang kamu isi sudah benar. Laporan final yang sudah terkirim tidak dapat diedit lagi.',
                confirmYes:'Ya, Kirim'
            }:{
                title:'Edit Progres',
                desc:'Perbarui data checkpoint progres yang sudah kamu kirim.',
                submit:'Simpan Perubahan',
                confirmTitle:'Simpan Perubahan?',
                confirmBody:'Pastikan data yang kamu ubah sudah benar. Progres ini masih bisa kamu edit lagi nanti selama belum final.',
                confirmYes:'Ya, Simpan'
            };
        }
        return isFinal?{
            title:'Kirim Laporan Final',
            desc:'Ini akan dikirim sebagai laporan final kepada Pimpinan untuk diperiksa dan tidak bisa diedit lagi setelahnya.',
            submit:'Kirim Laporan Final',
            confirmTitle:'Kirim Laporan Final?',
            confirmBody:'Pastikan data yang kamu isi sudah benar. Laporan final yang sudah terkirim tidak dapat diedit lagi.',
            confirmYes:'Ya, Kirim'
        }:{
            title:'Update Progres',
            desc:'Kirim update progres untuk permintaan ini.',
            submit:'Update Progres',
            confirmTitle:'Kirim Update Progres?',
            confirmBody:'Pastikan data yang kamu isi sudah benar. Progres ini masih bisa kamu edit lagi nanti lewat tombol Edit sebelum laporan final dikirim.',
            confirmYes:'Ya, Kirim'
        };
    }
    function applyLaporanTexts(mode,progresVal){
        var t=computeLaporanTexts(mode,parseInt(progresVal,10)||0);
        var title=document.getElementById('kirimLaporanTitle'); if(title) title.textContent=t.title;
        var desc=document.getElementById('kirimLaporanDesc'); if(desc) desc.textContent=t.desc;
        var submit=document.getElementById('kirimLaporanSubmitBtn'); if(submit) submit.textContent=t.submit;
        var ct=document.getElementById('konfirmasiKirimTitle'); if(ct) ct.textContent=t.confirmTitle;
        var cb=document.getElementById('konfirmasiKirimBody'); if(cb) cb.textContent=t.confirmBody;
        var cy=document.getElementById('konfirmasiKirimYa'); if(cy) cy.textContent=t.confirmYes;
    }
    function setFormMethod(form,method){
        var m=form.querySelector('input[name="_method"]');
        if(method){
            if(!m){m=document.createElement('input');m.type='hidden';m.name='_method';form.appendChild(m)}
            m.value=method;
        }else if(m){ m.remove(); }
    }
    function bindProgresLiveText(form){
        var progresInput=form.querySelector('[name="progres"]');
        if(!progresInput||progresInput.dataset.textBound==='1') return;
        progresInput.dataset.textBound='1';
        progresInput.addEventListener('input',function(){
            applyLaporanTexts(form.dataset.mode||'create',progresInput.value);
        });
    }
    // Edit checkpoint progres cuma boleh ubah deskripsi/kendala/progres/
    // prioritas/lampiran (lihat LaporanController::updateProgres) -- perihal,
    // kategori, & tujuan laporan dikunci read-only di mode edit supaya
    // satuan tidak mengira field itu ikut tersimpan padahal backend
    // mengabaikannya.
    function lockIdentityFields(form,locked){
        ['perihal','proyek'].forEach(function(name){
            var el=form.querySelector('[name="'+name+'"]');
            if(el) el.readOnly=locked;
        });
        var tujuan=form.querySelector('[name="tujuan_satuan_id"]');
        if(tujuan) tujuan.disabled=locked;
    }
    // <select> tidak punya "readonly" asli di HTML -- satu-satunya cara
    // ngunci interaksinya adalah disabled, tapi field yang disabled tidak
    // ikut terkirim saat submit form. Jadi kalau select ini mau dikunci
    // (misal Tujuan Laporan/Prioritas pas mode "Update Progres" yang wajib
    // dikirim ke LaporanController::store), sisipkan input hidden kembar
    // yang bawa nilainya. Panggil dengan locked=false untuk lepas kuncinya
    // lagi (dipakai saat modal yang sama dibuka ulang buat mode Edit).
    function lockSelectWithShadow(select,locked){
        if(!select) return;
        select.disabled=locked;
        var form=select.form; if(!form) return;
        var shadow=form.querySelector('input[type="hidden"][data-shadow-for="'+select.name+'"]');
        if(locked){
            if(!shadow){shadow=document.createElement('input');shadow.type='hidden';shadow.setAttribute('data-shadow-for',select.name);form.appendChild(shadow)}
            shadow.name=select.name;
            shadow.value=select.value;
        } else if(shadow){
            shadow.removeAttribute('name');
        }
    }
    function initUsePermintaanButtons(){
        document.querySelectorAll('.use-permintaan').forEach(function(btn){
            if(btn.dataset.useBound === '1') return;
            btn.dataset.useBound = '1';
            btn.addEventListener('click',function(){
                var form=document.getElementById('kirimLaporanForm');
                var modal=document.getElementById('kirimLaporanModal');
                if(!form || !modal) return;
                form.dataset.mode='create';
                if(form.dataset.storeAction) form.action=form.dataset.storeAction;
                setFormMethod(form,null);
                lockIdentityFields(form,true);
                var hidden=form.querySelector('input[name="permintaan_laporan_id"]');
                if(!hidden){hidden=document.createElement('input');hidden.type='hidden';hidden.name='permintaan_laporan_id';form.appendChild(hidden)}
                hidden.value=btn.dataset.requestId||'';
                // Kalau tombolnya adalah satu step checklist task (lihat
                // permintaan-laporan-item.blade.php), sertakan task_id supaya
                // LaporanController::store() tahu task mana yang mau
                // ditandai selesai/dibatalkan begitu checkpoint ini disubmit
                // -- task-nya BARU berubah status setelah form ini terkirim,
                // bukan langsung pas diklik.
                var taskIdHidden=form.querySelector('input[name="task_id"]');
                if(!taskIdHidden){taskIdHidden=document.createElement('input');taskIdHidden.type='hidden';taskIdHidden.name='task_id';form.appendChild(taskIdHidden)}
                taskIdHidden.value=btn.dataset.taskId||'';
                var tujuan=form.querySelector('select[name="tujuan_satuan_id"]'); if(tujuan && btn.dataset.targetId) tujuan.value=btn.dataset.targetId;
                var perihal=form.querySelector('[name="perihal"]'); if(perihal && btn.dataset.perihal) perihal.value=btn.dataset.perihal;
                var kategori=form.querySelector('[name="proyek"]'); if(kategori && btn.dataset.kategori) kategori.value=btn.dataset.kategori;
                var prioritas=form.querySelector('select[name="prioritas"]'); if(prioritas && btn.dataset.prioritas) prioritas.value=btn.dataset.prioritas;
                lockSelectWithShadow(tujuan,true);
                lockSelectWithShadow(prioritas,true);
                var deskripsi=form.querySelector('[name="deskripsi"]'); if(deskripsi) deskripsi.value='';
                var kendala=form.querySelector('[name="kendala"]'); if(kendala) kendala.value='';
                var lampiran=form.querySelector('[name="lampiran"]'); if(lampiran) lampiran.value='';
                var lampiranClearBtn=document.getElementById('lampiranClearBtn'); if(lampiranClearBtn) lampiranClearBtn.style.display='none';
                // Mode create belum punya lampiran apa pun buat ditunjukin --
                // link "Lampiran saat ini" (dari mode edit) wajib disembunyikan
                // lagi di sini, jaga-jaga modal yang sama abis dipakai edit.
                var lampiranExisting=document.getElementById('lampiranExistingLink'); if(lampiranExisting) lampiranExisting.style.display='none';
                var progresInput=form.querySelector('[name="progres"]');
                var progresHint=document.getElementById('progresHint');
                if(progresInput){
                    var current=parseInt(btn.dataset.progres||'0',10);
                    if(btn.dataset.hasTasks==='1'){
                        // Permintaan ini punya checklist task -- progres di
                        // sini SENGAJA nunjukin angka SAAT INI (sama kayak
                        // tombol "Update Progres" biasa), bukan prediksi hasil
                        // abis toggle -- biar gak membingungkan (mis. kelihatan
                        // 0% pas mau batalin 1 dari 5 task yang udah selesai).
                        // Task-nya sendiri baru benar-benar berubah status di
                        // server setelah form ini disubmit, angka barunya baru
                        // kelihatan abis itu.
                        progresInput.value=current;
                        progresInput.readOnly=true;
                        if(progresHint){
                            progresHint.textContent=btn.dataset.taskId
                                ? 'Mengirim checkpoint ini akan menandai "'+(btn.dataset.taskLabel||'task ini')+'" '+(btn.dataset.taskAction||'selesaikan')+'. Progres saat ini: '+current+'%.'
                                : 'Progres otomatis mengikuti checklist task yang sudah dicentang.';
                        }
                    }else{
                        // Permintaan lama tanpa checklist task -- tetap pakai
                        // alur manual seperti semula (harus naik dari progres
                        // terakhir, kecuali resubmit Revisi yang boleh sama).
                        progresInput.readOnly=false;
                        var isRevisi=btn.classList.contains('deadline-revisi');
                        var minAllowed=isRevisi?current:Math.min(current+1,100);
                        progresInput.min=minAllowed;
                        if(!progresInput.value || parseInt(progresInput.value,10) < minAllowed) progresInput.value=minAllowed;
                        if(progresHint) progresHint.textContent=isRevisi
                            ? 'Minimal '+current+'%, atau 100% kalau sudah final.'
                            : 'Harus lebih besar dari '+current+'%, atau 100% kalau sudah final.';
                    }
                }
                bindProgresLiveText(form);
                applyLaporanTexts('create',progresInput?progresInput.value:0);
                modal.classList.add('open');
                deskripsi?.focus();
            });
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initUsePermintaanButtons); else initUsePermintaanButtons();

    // Tombol "Edit" pada checkpoint progres yang masih Laporan::STATUS_PROGRES
    // (belum final) -- buka modal yang sama tapi dalam mode edit: form
    // di-prefill dari data checkpoint terpilih, method di-spoof jadi PATCH
    // ke route laporan.update-progres milik baris itu (UPDATE row yang sama,
    // bukan bikin checkpoint baru).
    function initEditProgresButtons(){
        document.querySelectorAll('.edit-progres-btn').forEach(function(btn){
            if(btn.dataset.editBound === '1') return;
            btn.dataset.editBound = '1';
            btn.addEventListener('click',function(){
                var form=document.getElementById('kirimLaporanForm');
                var modal=document.getElementById('kirimLaporanModal');
                if(!form || !modal || !btn.dataset.updateUrl) return;
                form.dataset.mode='edit';
                form.action=btn.dataset.updateUrl;
                setFormMethod(form,'PATCH');
                var tujuan=form.querySelector('select[name="tujuan_satuan_id"]'); if(tujuan && btn.dataset.tujuanSatuanId) tujuan.value=btn.dataset.tujuanSatuanId;
                lockSelectWithShadow(tujuan,false);
                // Prioritas ikut dikunci di mode edit -- itu nilai dari
                // permintaan yang ditentukan Pimpinan, bukan sesuatu yang
                // seharusnya bisa diubah-ubah lewat form checkpoint per task.
                var prioritas=form.querySelector('select[name="prioritas"]'); if(prioritas) prioritas.value=btn.dataset.prioritas||'';
                lockSelectWithShadow(prioritas,true);
                lockIdentityFields(form,true);
                var perihal=form.querySelector('[name="perihal"]'); if(perihal) perihal.value=btn.dataset.perihal||'';
                var kategori=form.querySelector('[name="proyek"]'); if(kategori) kategori.value=btn.dataset.proyek||'';
                var deskripsi=form.querySelector('[name="deskripsi"]'); if(deskripsi) deskripsi.value=btn.dataset.deskripsi||'';
                var kendala=form.querySelector('[name="kendala"]'); if(kendala) kendala.value=btn.dataset.kendala||'';
                var lampiran=form.querySelector('[name="lampiran"]'); if(lampiran) lampiran.value='';
                var lampiranClearBtn=document.getElementById('lampiranClearBtn'); if(lampiranClearBtn) lampiranClearBtn.style.display='none';
                // Riwayat lampiran yang PERNAH dikirim buat checkpoint ini --
                // input file gak bisa di-prefill (browser gak izinin), jadi
                // satu-satunya cara nunjukin "ini yang udah ada" adalah link
                // terpisah. Biarin kosong/tersembunyi kalau checkpoint-nya
                // memang belum pernah dilampiri apa-apa.
                var lampiranExisting=document.getElementById('lampiranExistingLink');
                var lampiranExistingName=document.getElementById('lampiranExistingName');
                if(lampiranExisting){
                    if(btn.dataset.lampiran){
                        lampiranExisting.href=btn.dataset.lampiran;
                        if(lampiranExistingName) lampiranExistingName.textContent=btn.dataset.lampiranNama||'Lampiran';
                        lampiranExisting.style.display='inline-flex';
                    }else{
                        lampiranExisting.style.display='none';
                    }
                }
                // Mode edit cuma buat ngoreksi teks checkpoint yang sudah
                // dikirim -- gak pernah nyentuh status task, jadi task_id
                // lama (kalau ada nyangkut dari klik step sebelumnya) wajib
                // dikosongkan lagi di sini.
                var taskIdHidden=form.querySelector('input[name="task_id"]');
                if(taskIdHidden) taskIdHidden.value='';
                var progresInput=form.querySelector('[name="progres"]');
                var progresHint=document.getElementById('progresHint');
                if(progresInput){
                    progresInput.value=btn.dataset.progres||'0';
                    if(btn.dataset.hasTasks==='1'){
                        progresInput.readOnly=true;
                        if(progresHint) progresHint.textContent='Progres otomatis mengikuti checklist task yang sudah dicentang.';
                    }else{
                        progresInput.readOnly=false;
                        progresInput.min=0;
                        if(progresHint) progresHint.textContent='Mengedit checkpoint yang sudah dikirim.';
                    }
                }
                bindProgresLiveText(form);
                applyLaporanTexts('edit',progresInput?progresInput.value:0);
                modal.classList.add('open');
                deskripsi?.focus();
            });
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initEditProgresButtons); else initEditProgresButtons();

    // ---- Wizard step sidebar (#kirimLaporanModal) --------------------------
    // Sidebar vertikal di modal Update Progres/Edit itu BUKAN sumber data
    // sendiri -- dia cuma "tampilan lain" dari .deadline-task-track yang
    // sudah dihitung PHP persis seperti semula (lihat komentar di
    // permintaan-laporan-item.blade.php), tapi disembunyikan dari kartu.
    // Setiap kali salah satu step/tombol yang membuka modal ini diklik, kita
    // cari track task milik kartu yang sama lalu kloning statenya jadi <li>
    // di sidebar. Klik satu step di sidebar cuma manggil .click() ke tombol
    // ASLI di track tersembunyi itu -- supaya initUsePermintaanButtons/
    // initEditProgresButtons di atas (yang isi form + buka modal) jalan
    // persis sama, tanpa logic baru yang mesti dijaga sinkron manual.
    function resetWizardSidebar(){
        var modal=document.getElementById('kirimLaporanModal');
        var body=document.getElementById('kirimLaporanWizardBody');
        var sidebar=document.getElementById('kirimLaporanWizardSidebar');
        var stepsList=document.getElementById('kirimLaporanWizardSteps');
        if(stepsList) stepsList.innerHTML='';
        if(sidebar){ sidebar.classList.remove('wizard-sidebar-visible'); sidebar.hidden=true; }
        if(body) body.classList.remove('has-sidebar');
        if(modal) modal.classList.remove('wizard-active');
    }
    function buildWizardSidebar(triggerBtn){
        var modal=document.getElementById('kirimLaporanModal');
        var body=document.getElementById('kirimLaporanWizardBody');
        var sidebar=document.getElementById('kirimLaporanWizardSidebar');
        var stepsList=document.getElementById('kirimLaporanWizardSteps');
        if(!modal||!body||!sidebar||!stepsList) return;
        var card=triggerBtn.closest('.deadline-sender-item');
        var track=card?card.querySelector('.deadline-task-track'):null;
        var steps=track?Array.prototype.slice.call(track.querySelectorAll('.deadline-task-step')):[];
        stepsList.innerHTML='';
        if(!steps.length){ resetWizardSidebar(); return; }
        // Caption baris kedua di bawah label -- niru pola step wizard di
        // referensi (tiap step punya judul + sub-teks kecil, bukan cuma 1
        // baris), teksnya dari state done/active/pending yang SAMA yang
        // sudah dihitung PHP (bukan data baru).
        var captions={done:'Selesai',active:'Sedang dikerjakan',pending:'Menunggu giliran'};
        steps.forEach(function(step){
            var state=step.classList.contains('done')?'done':(step.classList.contains('active')?'active':'pending');
            var li=document.createElement('li');
            li.className='wizard-step wizard-step-'+state;
            var dot=document.createElement('span');
            dot.className='wizard-step-dot';
            if(state==='done'){
                // Ikon checkmark SVG (bukan karakter teks "✓") -- niru
                // referensiTask.png, bentuknya konsisten di semua font/OS.
                dot.innerHTML='<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';
            }else{
                dot.textContent=step.querySelector('.deadline-task-num')?step.querySelector('.deadline-task-num').textContent:'';
            }
            var body=document.createElement('span');
            body.className='wizard-step-body';
            var label=document.createElement('span');
            label.className='wizard-step-label';
            var srcLabel=step.querySelector('.deadline-task-label');
            label.textContent=srcLabel?srcLabel.textContent:'';
            var caption=document.createElement('span');
            caption.className='wizard-step-caption';
            caption.textContent=captions[state];
            body.appendChild(label);
            body.appendChild(caption);
            li.appendChild(dot);
            li.appendChild(body);
            if(step.disabled){
                li.setAttribute('aria-disabled','true');
            }else{
                li.addEventListener('click',function(){ step.click(); });
            }
            stepsList.appendChild(li);
        });
        sidebar.hidden=false;
        body.classList.add('has-sidebar');
        modal.classList.add('wizard-active');
        // Fade+slide-in sidebar-nya -- dilepas dari class dulu (kalau
        // kepanggil ulang buat kartu lain) baru dipasang lagi 1 frame
        // kemudian, biar transition-nya SELALU kepicu ulang (bukan cuma di
        // klik pertama).
        sidebar.classList.remove('wizard-sidebar-visible');
        window.requestAnimationFrame(function(){ sidebar.classList.add('wizard-sidebar-visible'); });
    }
    function initWizardStepSidebar(){
        document.querySelectorAll('.use-permintaan, .edit-progres-btn').forEach(function(btn){
            if(btn.dataset.wizardSidebarBound==='1') return;
            btn.dataset.wizardSidebarBound='1';
            btn.addEventListener('click',function(){
                if(btn.classList.contains('deadline-task-step')){
                    buildWizardSidebar(btn);
                }else{
                    resetWizardSidebar();
                }
            });
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initWizardStepSidebar); else initWizardStepSidebar();

    // Tombol "Update Progres" baru di kartu (permintaan dengan checklist task,
    // bukan revisi) -- proxy klik ke step "active" (atau "done" kalau semua
    // task kebetulan sudah selesai) di track tersembunyi milik kartu yang
    // sama, supaya modal + sidebar wizard terbuka lewat alur yang sama persis
    // seperti klik step secara langsung.
    function initWizardEntryButtons(){
        document.querySelectorAll('.deadline-wizard-entry-btn').forEach(function(btn){
            if(btn.dataset.wizardEntryBound==='1') return;
            btn.dataset.wizardEntryBound='1';
            btn.addEventListener('click',function(){
                var card=btn.closest('.deadline-sender-item');
                var track=card?card.querySelector('.deadline-task-track'):null;
                if(!track) return;
                var target=track.querySelector('.deadline-task-step.active')||track.querySelector('.deadline-task-step.done');
                if(target) target.click();
            });
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initWizardEntryButtons); else initWizardEntryButtons();

    // Validasi wajib-diisi custom untuk form Update Progres/Kirim Laporan --
    // niru pola yang sama dipakai di modal "Buat Permintaan Laporan" pimpinan:
    // ganti tooltip bawaan browser jadi pesan merah di bawah kolom + border
    // merah, otomatis ke-reset begitu field-nya diisi/diubah lagi.
    function initKirimLaporanValidation(){
        var form=document.getElementById('kirimLaporanForm');
        if(!form||form.dataset.validationReady==='1') return;
        form.dataset.validationReady='1';
        var messages={
            tujuan_satuan_id:'Tujuan laporan wajib dipilih.',
            progres:'Progres wajib diisi.',
            prioritas:'Pilih salah satu prioritas.',
            perihal:'Perihal wajib diisi.',
            deskripsi:'Isi laporan wajib diisi.'
        };
        form.querySelectorAll('input[required],select[required],textarea[required]').forEach(function(input){
            var anchor=input.closest('.form-field')||input;
            var msg=anchor.querySelector(':scope > .kirim-laporan-error');
            if(!msg){
                msg=document.createElement('span');
                msg.className='kirim-laporan-error';
                msg.style.display='none';
                anchor.appendChild(msg);
            }
            input.addEventListener('invalid',function(e){
                e.preventDefault();
                input.classList.add('field-invalid');
                var text=messages[input.name]||'Kolom ini wajib diisi.';
                if(input.name==='progres'&&input.validity.rangeUnderflow){
                    text='Progres tidak boleh kurang dari '+input.min+'%.';
                }
                msg.textContent=text;
                msg.style.display='flex';
            });
            function clearInvalid(){
                input.classList.remove('field-invalid');
                msg.style.display='none';
            }
            input.addEventListener('input',clearInvalid);
            input.addEventListener('change',clearInvalid);
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initKirimLaporanValidation); else initKirimLaporanValidation();

    // Tombol silang di sebelah input Lampiran -- muncul begitu ada file
    // terpilih, klik buat ngosongin input-nya lagi (browser gak ngasih cara
    // native buat clear file input selain reset value ke '').
    function initLampiranClear(){
        var wrap=document.querySelector('.lampiran-input-wrap');
        var btn=document.getElementById('lampiranClearBtn');
        if(!wrap||!btn||wrap.dataset.clearBound==='1') return;
        wrap.dataset.clearBound='1';
        function bindChange(input){
            input.addEventListener('change',function(){
                btn.style.display=(input.files&&input.files.length)?'flex':'none';
            });
        }
        bindChange(document.getElementById('lampiran'));
        btn.addEventListener('click',function(){
            // Input ini juga "dipercantik" sama siberadEnhanceFileInputs()
            // (dash-styles.blade.php) yang bikin tombol "Pilih File" + teks
            // nama file SENDIRI di luar input aslinya. Reset value doang
            // nggak nyentuh teks itu -- makanya harus nembak event 'change'
            // biar listener punya enhancement itu juga ikut update tampilan
            // balik ke "Tidak ada file yang dipilih".
            var input=document.getElementById('lampiran');
            input.value='';
            input.dispatchEvent(new Event('change',{bubbles:true}));
            btn.style.display='none';
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initLampiranClear); else initLampiranClear();

    // Tanda manual kartu (checkbox bulat pojok kiri-atas) -- statusnya PURE
    // client-side (localStorage per-browser), karena kita nggak boleh nambah
    // kolom DB baru buat ini. Kartu yang ditandai di-pin paling atas pas
    // sorting (lihat apply() di bawah), di atas bahkan kartu "Terbaru".
    var DCARD_PIN_KEY='siberadPermintaanDitandai';
    function getPinnedIds(){
        try{ var raw=localStorage.getItem(DCARD_PIN_KEY); return raw?new Set(JSON.parse(raw)):new Set(); }
        catch(e){ return new Set(); }
    }
    function savePinnedIds(set){
        // BUKAN Array.prototype.slice.call(set) -- Set BUKAN array-like
        // (gak punya index numerik + .length kayak NodeList/arguments),
        // itu selalu balikin array kosong diam-diam (gak throw error, jadi
        // gak ketauan dari try/catch). Array.from() yang bener buat Set.
        try{ localStorage.setItem(DCARD_PIN_KEY,JSON.stringify(Array.from(set))); }catch(e){}
    }
    function applyPinnedState(card,pinned){
        card.dataset.ditandai=pinned?'1':'0';
        var btn=card.querySelector('.dcard-pin-btn');
        if(btn) btn.setAttribute('aria-pressed',pinned?'true':'false');
    }
    function initDcardPinButtons(){
        var pinned=getPinnedIds();
        document.querySelectorAll('#permintaan-laporan .deadline-sender-item').forEach(function(card){
            var id=card.getAttribute('data-realtime-permintaan-id');
            if(id) applyPinnedState(card,pinned.has(id));
        });
        document.querySelectorAll('.dcard-pin-btn').forEach(function(btn){
            if(btn.dataset.pinBound==='1') return;
            btn.dataset.pinBound='1';
            btn.addEventListener('click',function(e){
                e.stopPropagation();
                var card=btn.closest('.deadline-sender-item');
                var id=card&&card.getAttribute('data-realtime-permintaan-id');
                if(!id) return;
                var set=getPinnedIds();
                var nowPinned=!set.has(id);
                if(nowPinned) set.add(id); else set.delete(id);
                savePinnedIds(set);
                applyPinnedState(card,nowPinned);
                window.siberadRefreshPermintaanFilter&&window.siberadRefreshPermintaanFilter();
            });
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initDcardPinButtons); else initDcardPinButtons();

    // Menu titik-3 di pojok kartu -- cuma UI dulu (dropdown buka/tutup),
    // "Arsipkan" belum disambungkan ke route apa pun (fungsinya nanti).
    function closeAllDcardMenus(except){
        document.querySelectorAll('.dcard-menu').forEach(function(menu){
            if(menu===except||!menu.classList.contains('open')) return;
            menu.classList.remove('open');
            var btn=menu.previousElementSibling;
            if(btn&&btn.classList.contains('dcard-menu-toggle')) btn.setAttribute('aria-expanded','false');
        });
    }
    function initDcardMenus(){
        document.querySelectorAll('.dcard-menu-toggle').forEach(function(btn){
            if(btn.dataset.menuBound==='1') return;
            btn.dataset.menuBound='1';
            btn.addEventListener('click',function(e){
                e.stopPropagation();
                var menu=btn.nextElementSibling;
                if(!menu) return;
                var willOpen=!menu.classList.contains('open');
                closeAllDcardMenus();
                if(willOpen) menu.classList.add('open');
                btn.setAttribute('aria-expanded',willOpen?'true':'false');
            });
        });
        document.querySelectorAll('.dcard-archive-btn').forEach(function(btn){
            if(btn.dataset.archiveBound==='1') return;
            btn.dataset.archiveBound='1';
            btn.addEventListener('click',function(){
                // TODO: fungsi Arsipkan menyusul -- belum ada route/aksi di sini.
                closeAllDcardMenus();
            });
        });
    }
    if(!window.siberadDcardMenuOutsideBound){
        window.siberadDcardMenuOutsideBound=true;
        document.addEventListener('click',function(){ closeAllDcardMenus(); });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initDcardMenus); else initDcardMenus();

    // Dipanggil ulang oleh polling realtime (permintaan-laporan-realtime.blade.php,
    // laporan-role-realtime-sync.blade.php) setiap kali kartu permintaan diganti/
    // ditambah, supaya tombol Update Progres/Revisi/Edit yang baru tetap bisa diklik.
    window.siberadRebindPermintaanActions=function(){initUsePermintaanButtons();initEditProgresButtons();initWizardStepSidebar();initWizardEntryButtons();initDcardMenus();initDcardPinButtons();window.siberadRefreshPermintaanFilter&&window.siberadRefreshPermintaanFilter();};

    // Pencarian daftar Permintaan Laporan -- reuse gaya .rpt-filter-* yang
    // sama dengan tabel lain (1 sistem), tapi logikanya custom karena isinya
    // kartu <article> bukan baris <tr>.
    function initPermintaanSearch(){
        var section=document.getElementById('permintaan-laporan');
        if(!section||section.dataset.searchReady==='1') return;
        var list=section.querySelector('.deadline-sender-list');
        if(!list) return;
        var initialItems=Array.prototype.slice.call(list.querySelectorAll(':scope > article.deadline-sender-item'));
        if(!initialItems.length) return;
        section.dataset.searchReady='1';

        var bar=document.createElement('div');
        bar.className='rpt-filter-bar';
        // Urutan "Terbaru"/"Terlama" DULU berdasar kapan permintaan-nya
        // dibuat (created_at) -- membingungkan buat section yang isinya soal
        // DEADLINE (permintaan lama wajar deadline-nya udah lewat/terlambat,
        // permintaan baru wajar deadline-nya masih jauh, tapi kelihatannya
        // kayak "terlambat = terlama" padahal itu cuma korelasi, bukan
        // urutan yang berguna). Sekarang diganti sort berdasar deadline_at
        // beneran (data-deadline-at, timestamp) -- "Deadline Terdekat" jadi
        // default biar permintaan paling mendesak nongol duluan, sesuai
        // tujuan section ini (nge-track deadline, bukan riwayat pembuatan).
        bar.innerHTML='<div class="rpt-filter-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" autocomplete="off" placeholder="Cari perihal..." aria-label="Cari perihal"></div><select class="rpt-filter-select" aria-label="Urutkan"><option value="terdekat">Deadline Terdekat</option><option value="terjauh">Deadline Terjauh</option></select><span class="rpt-filter-count"></span>';
        // Bar pencarian+sort+hitungan ini bagian dari PANEL judul ("Permintaan
        // Laporan"), bukan nempel ke grid kartu -- .report-card sekarang cuma
        // bungkus panel-head (lihat laporan-role.blade.php), grid kartunya
        // sendiri sudah jadi sibling di luar box itu supaya ngambang bebas.
        var panel=section.querySelector('.report-card');
        if(panel){ panel.appendChild(bar); } else { list.parentNode.insertBefore(bar,list); }

        // Pakai .empty-state -- kelas "1 sistem" yang sama dipakai tabel lain
        // (lihat ensureEmptyRow() di danpus-report-table-filter.blade.php dan
        // blok fallback grid ini sendiri di laporan-role.blade.php). Sebelumnya
        // kotak ini pakai kelas rpt-filter-empty yang TIDAK PERNAH ada CSS-nya
        // sama sekali (typo/bukan kelas asli sistem) -- makanya tampil polos
        // tanpa kotak dotted/padding/center kayak semua empty-state lain.
        var emptyBox=document.createElement('div');
        emptyBox.className='empty-state';
        emptyBox.style.display='none';
        emptyBox.innerHTML='<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Tidak ada permintaan laporan yang sesuai dengan pencarian.</div>';
        list.parentNode.insertBefore(emptyBox,list.nextSibling);

        var input=bar.querySelector('input');
        var sortSelect=bar.querySelector('select');
        var count=bar.querySelector('.rpt-filter-count');

        function apply(){
            // Query ulang LANGSUNG dari DOM tiap dipanggil -- SENGAJA nggak
            // pakai array yang disimpan di closure. Kartu yang sudah diganti
            // replaceWith()/dihapus oleh syncRequestList() otomatis nggak
            // ikut kebawa lagi (beda dari sebelumnya, yang bisa nyeret balik
            // elemen "yatim" lewat appendChild pas reorder -- itu penyebab
            // bug kartu kelihatan dobel begitu search/sort dipakai).
            var items=Array.prototype.slice.call(list.querySelectorAll(':scope > article.deadline-sender-item'));
            // Sort berdasar data-deadline-at (timestamp, dipasang PHP di
            // permintaan-laporan-item.blade.php) -- selalu ada & akurat di
            // SETIAP kartu (baik render awal maupun realtime), jadi gak perlu
            // lagi nyimpan/nebak "urutan" kayak rptOrder dulu.
            //
            // Status "ditandai" DIBACA LANGSUNG dari localStorage di sini
            // (bukan dari atribut data-ditandai di kartu) -- kartu bisa
            // diganti node DOM barunya kapan aja lewat beberapa jalur
            // realtime berbeda (insertItems/syncRequestList), dan menjaga
            // satu atribut tetap "nempel" konsisten lewat semua jalur itu
            // ternyata rawan lolos. localStorage jadi satu-satunya sumber
            // kebenaran yang gak mungkin "lupa" gara-gara DOM diganti.
            var pinnedIds=getPinnedIds();
            items.sort(function(a,b){
                // 3 tingkat prioritas urutan: (1) Ditandai manual (tombol
                // bulat pojok kiri-atas) paling atas duluan, (2) "Terbaru"
                // (belum dikonfirmasi/dikerjakan) kedua, (3) sisanya. Deadline
                // Terdekat/Terjauh cuma jadi urutan di DALAM masing-masing
                // kelompok itu, bukan prioritas utama -- biar permintaan yang
                // ditandai/belum disentuh nggak kekubur di antara ratusan
                // data lain cuma karena deadline-nya masih jauh.
                var aPin=pinnedIds.has(a.getAttribute('data-realtime-permintaan-id'));
                var bPin=pinnedIds.has(b.getAttribute('data-realtime-permintaan-id'));
                if(aPin!==bPin) return aPin?-1:1;
                var aBaru=a.dataset.belumDikerjakan==='1',bBaru=b.dataset.belumDikerjakan==='1';
                if(aBaru!==bBaru) return aBaru?-1:1;
                var diff=Number(a.dataset.deadlineAt)-Number(b.dataset.deadlineAt);
                return sortSelect.value==='terjauh'?-diff:diff;
            });
            // Cuma reorder DOM kalau urutannya beneran berubah -- appendChild
            // tanpa syarat di tiap keystroke boros & berisiko (pola yang sama
            // yang bikin loop di danpus-report-table-filter.blade.php).
            var needsReorder=items.some(function(item,i){return item.nextElementSibling!==(items[i+1]||null)});
            if(needsReorder){
                // FLIP -- sama kayak animasi kartu baru di insertItems()
                // (permintaan-laporan-realtime.blade.php), supaya reorder
                // gara-gara search/sort/nandai/realtime JUGA geser mulus,
                // bukan "loncat" instan kayak sebelumnya (kesannya kaku).
                // Cuma kartu yang lagi KELIHATAN (bukan display:none dari
                // filter search) yang dianimasikan -- posisi kartu
                // tersembunyi gak berarti buat dianimasikan.
                var reduceMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                var prevRects=reduceMotion?null:new Map();
                if(prevRects){
                    items.forEach(function(item){
                        if(item.style.display!=='none') prevRects.set(item,item.getBoundingClientRect());
                    });
                }
                items.forEach(function(item){list.appendChild(item)});
                if(prevRects){
                    items.forEach(function(item){
                        var prev=prevRects.get(item);if(!prev)return;
                        var next=item.getBoundingClientRect();
                        var dx=prev.left-next.left,dy=prev.top-next.top;
                        if(Math.abs(dx)<1&&Math.abs(dy)<1)return;
                        item.style.transition='none';
                        item.style.transform='translate('+dx+'px,'+dy+'px)';
                        item.getBoundingClientRect();
                        item.style.transition='transform .35s cubic-bezier(.4,0,.2,1)';
                        item.style.transform='';
                        item.addEventListener('transitionend',function handler(e){if(e.propertyName!=='transform')return;item.style.transition='';item.removeEventListener('transitionend',handler);});
                    });
                }
            }
            var q=(input.value||'').trim().toLowerCase();
            var visible=0;
            items.forEach(function(item){
                var match=!q||(item.dataset.search||'').indexOf(q)!==-1;
                item.style.display=match?'':'none';
                if(match)visible++;
            });
            count.textContent=visible+' dari '+items.length+' data';
            emptyBox.style.display=visible===0?'block':'none';
        }
        input.addEventListener('input',apply);
        sortSelect.addEventListener('change',apply);
        // Dipanggil lagi dari siberadRebindPermintaanActions setiap kali
        // polling realtime nambah/ganti/hapus kartu -- biar filter & hitungan
        // "x dari x data" ikut nyegerin diri sendiri tanpa nunggu user ngetik
        // ulang di kolom cari.
        window.siberadRefreshPermintaanFilter=apply;
        apply();
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initPermintaanSearch); else initPermintaanSearch();
})();
</script>
