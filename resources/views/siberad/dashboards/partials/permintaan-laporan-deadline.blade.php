<style>
.deadline-section,.deadline-sender-section{position:relative;overflow:hidden}.deadline-primary,.deadline-secondary{border:1px solid transparent;border-radius:9px;padding:9px 14px;font-size:11px;font-weight:700;cursor:pointer;transition:.15s ease}.deadline-primary{background:var(--p-accent,var(--gold-bright));color:#fff}.deadline-primary:hover{filter:brightness(1.06);transform:translateY(-1px)}.deadline-secondary{background:var(--p-surface-2,var(--panel-alt));border-color:var(--p-border,var(--border));color:var(--p-text,var(--text))}.deadline-secondary:hover{border-color:var(--p-accent,var(--gold-bright));transform:translateY(-1px)}.deadline-primary:active,.deadline-secondary:active{transform:scale(.96)}.deadline-secondary.confirm-btn{background:var(--success,#16834b);border-color:var(--success,#16834b);color:#fff}.deadline-secondary.confirm-btn:hover{filter:brightness(1.1);border-color:var(--success,#16834b)}.deadline-primary.small,.deadline-secondary.small{padding:7px 10px;font-size:10px}.kirim-laporan-btn{letter-spacing:.03em;box-shadow:0 8px 18px -8px rgba(212,175,55,.55)}.kirim-laporan-btn:hover{box-shadow:0 10px 22px -6px rgba(212,175,55,.65)}.deadline-form-wrap{margin:0 0 18px;padding:16px;border:1px solid var(--p-border,var(--border-soft));border-radius:12px;background:var(--p-surface-2,var(--panel-alt))}.deadline-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.deadline-field{display:flex;flex-direction:column;gap:6px}.deadline-field.full{grid-column:1/-1}.deadline-field label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--p-muted,var(--text-muted))}.deadline-field input,.deadline-field select,.deadline-field textarea{width:100%;box-sizing:border-box;border:1px solid var(--p-border,var(--border));border-radius:8px;background:var(--p-surface,var(--panel));color:var(--p-text,var(--text));padding:9px 10px;font:inherit;font-size:12px}.deadline-field textarea{min-height:100px;resize:vertical}.deadline-check-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}.deadline-check{display:grid;grid-template-columns:auto 1fr;column-gap:7px;align-items:center;padding:9px;border:1px solid var(--p-border,var(--border));border-radius:9px;background:var(--p-surface,var(--panel));cursor:pointer}.deadline-check input{width:auto}.deadline-check span{font-family:var(--mono);font-size:10px;font-weight:800;color:var(--p-accent,var(--gold-bright))}.deadline-check small{grid-column:2;font-size:9px;color:var(--p-muted,var(--text-muted));line-height:1.35}.deadline-form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}.deadline-table-wrap{overflow-x:auto}.deadline-table{width:100%;border-collapse:collapse;min-width:720px}.deadline-table th{font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:var(--p-muted,var(--text-muted));text-align:left;padding:10px;border-bottom:1px solid var(--p-border,var(--border))}.deadline-table td{padding:11px 10px;border-bottom:1px solid var(--p-border,var(--border));font-size:11px;vertical-align:middle}.deadline-table td strong{display:block}.deadline-table td small{display:block;color:var(--p-muted,var(--text-muted));margin-top:3px;max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.deadline-pill{display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:9px;font-weight:800;border:1px solid transparent;white-space:nowrap}.deadline-pill.wait{color:var(--p-orange);background:var(--p-orange-bg);border-color:var(--p-orange-border)}.deadline-pill.ok{color:#16834b;background:rgba(63,194,125,.12);border-color:rgba(63,194,125,.28)}.deadline-pill.bad{color:#c83b3b;background:rgba(181,52,47,.08);border-color:rgba(198,40,40,.28)}.deadline-pill.blue{color:#2476ad;background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.25)}.deadline-pill.revisi{color:var(--gold-solid);background:rgba(217,146,11,.14);border-color:rgba(217,146,11,.4)}.deadline-revisi{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid))!important;color:var(--on-gold)!important;border-color:transparent!important;box-shadow:0 8px 22px -8px rgba(217,146,11,.5)}.deadline-revisi:hover{color:var(--on-gold)!important;box-shadow:0 10px 26px -6px rgba(217,146,11,.6);filter:none;transform:translateY(-1px)}.deadline-progress-badge{font-family:var(--mono);font-size:10px;font-weight:800;color:var(--text-muted);white-space:nowrap}.deadline-sender-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}.deadline-sender-head h3{margin:0 0 4px}.deadline-sender-head p{margin:0;font-size:11px;color:var(--text-muted);line-height:1.5}.deadline-count{font-family:var(--mono);font-size:10px;color:var(--gold-bright);white-space:nowrap}.deadline-sender-list{display:grid;gap:10px}.deadline-sender-item{display:flex;justify-content:space-between;gap:16px;padding:13px;border:1px solid var(--border-soft);border-radius:10px;background:var(--panel-alt)}.deadline-sender-item.near{border-color:rgba(224,168,58,.45)}.deadline-sender-item.bad{border-color:rgba(198,40,40,.35)}.deadline-sender-main{min-width:0}.deadline-sender-title{font-size:13px;font-weight:800}.deadline-sender-meta{font-size:10px;color:var(--text-muted);margin-top:4px}.deadline-sender-instruction{font-size:11px;line-height:1.55;color:var(--text-muted);margin-top:8px;white-space:pre-wrap}.deadline-sender-side{display:flex;flex-direction:column;align-items:flex-end;justify-content:space-between;gap:8px;flex-shrink:0}.deadline-actions{display:flex;gap:6px;align-items:center;justify-content:flex-end}.deadline-complete{font-size:10px;font-weight:700;color:var(--green)}.deadline-complete.cancelled{color:#c83b3b}
@media(max-width:850px){.deadline-check-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.deadline-form-grid{grid-template-columns:1fr}.deadline-field.full{grid-column:auto}.deadline-sender-item{flex-direction:column}.deadline-sender-side{align-items:flex-start}.deadline-actions{justify-content:flex-start}}@media(max-width:550px){.deadline-check-grid{grid-template-columns:1fr}.deadline-sender-head{display:block}.deadline-count{display:inline-block;margin-top:8px}}
.deadline-task-track{padding:14px 18px 16px 34px;background:var(--p-surface-2,var(--panel-alt));border-radius:12px;display:flex;flex-wrap:wrap;row-gap:10px;margin:8px 0;justify-content:flex-end}
.deadline-task-step{position:relative;display:flex;align-items:center;gap:6px;border:0;cursor:pointer;padding:7px 20px 7px 18px;margin-right:-11px;font:inherit;font-size:10.5px;font-weight:700;white-space:nowrap;color:var(--p-muted,var(--text-muted));transition:color .15s ease,filter .15s ease;z-index:1}
.deadline-task-step::before{content:"";position:absolute;inset:0;z-index:-1;background:var(--p-surface-2,var(--panel-alt));clip-path:polygon(0 0,calc(100% - 13px) 0,100% 50%,calc(100% - 13px) 100%,0 100%,13px 50%);transition:background .15s ease}
.deadline-task-step:first-child::before{clip-path:polygon(0 0,calc(100% - 13px) 0,100% 50%,calc(100% - 13px) 100%,0 100%)}
.deadline-task-step:last-child::before{clip-path:polygon(0 0,100% 0,100% 100%,0 100%,13px 50%)}
.deadline-task-step:first-child:last-child::before{clip-path:none;border-radius:8px}
.deadline-task-step:hover{filter:brightness(1.08)}
.deadline-task-step:disabled{cursor:not-allowed;opacity:.6;filter:none}
.deadline-task-step:focus-visible{outline:2px solid #0ea5e9;outline-offset:2px}
.deadline-task-num{flex-shrink:0;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9.5px;font-weight:800;background:var(--p-surface,var(--panel));color:inherit}
.deadline-task-label{overflow:hidden;text-overflow:ellipsis;max-width:170px}
.deadline-task-step.active{color:#fff;z-index:2}
.deadline-task-step.active::before{background:#38bdf8;box-shadow:0 4px 12px -4px rgba(56,189,248,.55)}
.deadline-task-step.active .deadline-task-num{background:#fff;color:#0ea5e9}
.deadline-task-step.done{color:#8a7245}
.deadline-task-step.done::before{background:#efe6d2}
.deadline-task-step.done .deadline-task-num{background:var(--green,#16834b);color:#fff}
@media(max-width:640px){.deadline-task-step{margin-right:0;padding:7px 12px}.deadline-task-step::before{clip-path:none!important;border-radius:8px}}
</style>

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

    // Dipanggil ulang oleh polling realtime (permintaan-laporan-realtime.blade.php,
    // laporan-role-realtime-sync.blade.php) setiap kali kartu permintaan diganti/
    // ditambah, supaya tombol Update Progres/Revisi/Edit yang baru tetap bisa diklik.
    window.siberadRebindPermintaanActions=function(){initUsePermintaanButtons();initEditProgresButtons();};

    // Pencarian daftar Permintaan Laporan -- reuse gaya .rpt-filter-* yang
    // sama dengan tabel lain (1 sistem), tapi logikanya custom karena isinya
    // kartu <article> bukan baris <tr>.
    function initPermintaanSearch(){
        var section=document.getElementById('permintaan-laporan');
        if(!section||section.dataset.searchReady==='1') return;
        var list=section.querySelector('.deadline-sender-list');
        if(!list) return;
        var items=Array.prototype.slice.call(list.querySelectorAll(':scope > article.deadline-sender-item'));
        if(!items.length) return;
        section.dataset.searchReady='1';

        // Simpan urutan asli (backend sudah pakai latest(), jadi index 0 =
        // terbaru) -- sama persis pola sortable di initReportFilter
        // (danpus-report-table-filter.blade.php), cuma di sini kartu
        // <article>, bukan baris <tr>.
        items.forEach(function(item,i){item.dataset.rptOrder=String(i)});

        var bar=document.createElement('div');
        bar.className='rpt-filter-bar';
        bar.innerHTML='<div class="rpt-filter-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" autocomplete="off" placeholder="Cari perihal..." aria-label="Cari perihal"></div><select class="rpt-filter-select" aria-label="Urutkan"><option value="newest">Terbaru</option><option value="oldest">Terlama</option></select><span class="rpt-filter-count"></span>';
        list.parentNode.insertBefore(bar,list);

        var emptyBox=document.createElement('div');
        emptyBox.className='rpt-filter-empty';
        emptyBox.style.display='none';
        emptyBox.innerHTML='<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 8px;display:block;opacity:.7"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>Tidak ada permintaan laporan yang sesuai dengan pencarian.';
        list.parentNode.insertBefore(emptyBox,list.nextSibling);

        var input=bar.querySelector('input');
        var sortSelect=bar.querySelector('select');
        var count=bar.querySelector('.rpt-filter-count');

        function apply(){
            items.sort(function(a,b){
                var diff=Number(a.dataset.rptOrder)-Number(b.dataset.rptOrder);
                return sortSelect.value==='oldest'?-diff:diff;
            });
            // Cuma reorder DOM kalau urutannya beneran berubah -- appendChild
            // tanpa syarat di tiap keystroke boros & berisiko (pola yang sama
            // yang bikin loop di danpus-report-table-filter.blade.php).
            var needsReorder=items.some(function(item,i){return item.nextElementSibling!==(items[i+1]||null)});
            if(needsReorder)items.forEach(function(item){list.appendChild(item)});
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
        apply();
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initPermintaanSearch); else initPermintaanSearch();
})();
</script>
