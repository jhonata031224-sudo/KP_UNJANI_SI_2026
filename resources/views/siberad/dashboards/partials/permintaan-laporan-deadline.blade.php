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
    function computeLaporanTexts(mode,progresVal,reason){
        var isFinal=progresVal>=100;
        if(mode==='view'){
            return {
                title:'Lihat Progres',
                desc: reason==='locked'
                    ? 'Permintaan ini sudah tidak bisa diisi progres baru.'
                    : 'Checkpoint ini sudah final dan sedang menunggu pemeriksaan Pimpinan.',
                submit:'',
                confirmTitle:'',
                confirmBody:'',
                confirmYes:''
            };
        }
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
    function applyLaporanTexts(mode,progresVal,reason){
        var t=computeLaporanTexts(mode,parseInt(progresVal,10)||0,reason);
        var title=document.getElementById('kirimLaporanTitle'); if(title) title.textContent=t.title;
        var desc=document.getElementById('kirimLaporanDesc'); if(desc) desc.textContent=t.desc;
        var submit=document.getElementById('kirimLaporanSubmitBtn'); if(submit) submit.textContent=t.submit;
        var ct=document.getElementById('konfirmasiKirimTitle'); if(ct) ct.textContent=t.confirmTitle;
        var cb=document.getElementById('konfirmasiKirimBody'); if(cb) cb.textContent=t.confirmBody;
        var cy=document.getElementById('konfirmasiKirimYa'); if(cy) cy.textContent=t.confirmYes;
    }
    // "Lihat Progres" (checkpoint permintaan sudah final, menunggu
    // pemeriksaan Pimpinan) numpang modal #kirimLaporanModal yang sama
    // persis, cuma di-toggle jadi mode baca-doang: deskripsi/kendala gak
    // bisa diketik, tombol submit disembunyikan (cuma "Tutup" yang
    // ketinggalan). Dipanggil dari initEditProgresButtons berdasarkan
    // data-readonly di tombolnya, dan di-reset balik ke false setiap kali
    // modal dibuka lewat initUsePermintaanButtons (create) biar gak
    // "nyangkut" readonly dari sesi Lihat Progres sebelumnya.
    function setKirimLaporanReadonly(form,readonly){
        var deskripsi=form.querySelector('[name="deskripsi"]'); if(deskripsi) deskripsi.readOnly=readonly;
        var kendala=form.querySelector('[name="kendala"]'); if(kendala) kendala.readOnly=readonly;
        var submitBtn=document.getElementById('kirimLaporanSubmitBtn'); if(submitBtn) submitBtn.hidden=readonly;
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
            applyLaporanTexts(form.dataset.mode||'create',progresInput.value,form.dataset.readonlyReason);
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
    // "Detail Task" di modal Update Progres: tombol di pojok kanan header
    // (#taskDetailBtn) yang buka sub-modal #taskDetailModal berisi instruksi
    // rinci task dari Pimpinan. Isinya diambil dari data-task-detail tombol
    // step task (lihat permintaan-laporan-item.blade.php). Tombol disembunyikan
    // buat permintaan lama yang task-nya belum punya detail (kolom nullable).
    function applyTaskDetail(btn){
        var btnEl=document.getElementById('taskDetailBtn');
        var body=document.getElementById('taskDetailModalBody');
        var val=(btn&&btn.dataset.taskDetail)||'';
        if(body) body.textContent=val||'Detail task tidak tersedia.';
        if(btnEl) btnEl.hidden=!val;
        // Pindah task / buka modal ulang -> pastikan sub-modal detail ketutup.
        var m=document.getElementById('taskDetailModal');
        if(m) m.classList.remove('open');
    }
    function initTaskDetailModal(){
        var m=document.getElementById('taskDetailModal');
        if(!m||m.dataset.bound==='1') return;
        m.dataset.bound='1';
        var openBtn=document.getElementById('taskDetailBtn');
        var closeBtn=document.getElementById('taskDetailModalClose');
        function close(){ m.classList.remove('open'); }
        openBtn&&openBtn.addEventListener('click',function(){ m.classList.add('open'); });
        closeBtn&&closeBtn.addEventListener('click',close);
        // Klik area luar (backdrop) SENGAJA tidak menutup -- konsisten dengan
        // modal lain di app ini. Tutup lewat tombol "Tutup" / Esc saja.
        document.addEventListener('keydown',function(e){ if(e.key==='Escape'&&m.classList.contains('open')) close(); });
        // Kalau wizard-nya ditutup sementara sub-modal detail masih kebuka,
        // ikut tutup biar nggak nyangkut ngambang di atas halaman.
        var wizardCancel=document.getElementById('kirimLaporanCancel');
        wizardCancel&&wizardCancel.addEventListener('click',close);
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initTaskDetailModal); else initTaskDetailModal();

    // ---- Draft per-task (isi laporan / kendala / lampiran yang BELUM disubmit) ----
    // Form #kirimLaporanForm cuma SATU dipakai ulang buat semua step task.
    // Tanpa ini, tiap klik step (pindah task ATAU balik ke task yang sama)
    // nge-reset field ke kosong -> ketikan & lampiran user hilang. Draft
    // disimpan di memori (bukan localStorage, karena ada File object), dikunci
    // per task_id, dan dibuang begitu checkpoint task itu berhasil dikirim.
    var kirimLaporanDrafts={};
    function captureCurrentDraft(){
        var form=document.getElementById('kirimLaporanForm');
        if(!form)return;
        var tid=form.dataset.draftTaskId||'';
        if(!tid)return;
        if(form.dataset.mode==='view')return; // "Lihat Progres" -- read-only, gak ada yang diketik
        var de=form.querySelector('[name="deskripsi"]');
        var ke=form.querySelector('[name="kendala"]');
        var zone=document.getElementById('lampiranDropzone');
        var staged=(zone&&typeof lampiranZoneState==='function')?lampiranZoneState(zone).staged.slice():[];
        var deskripsi=de?de.value:'';
        var kendala=ke?ke.value:'';
        if(!deskripsi && !kendala && !staged.length){ delete kirimLaporanDrafts[tid]; return; }
        kirimLaporanDrafts[tid]={deskripsi:deskripsi,kendala:kendala,staged:staged};
    }
    function restoreDraftFor(taskId){
        var d=taskId?kirimLaporanDrafts[taskId]:null;
        if(!d)return false;
        var form=document.getElementById('kirimLaporanForm');
        if(!form)return false;
        var de=form.querySelector('[name="deskripsi"]');
        var ke=form.querySelector('[name="kendala"]');
        if(de) de.value=d.deskripsi||'';
        if(ke) ke.value=d.kendala||'';
        if(d.staged && d.staged.length){
            var zone=document.getElementById('lampiranDropzone');
            if(zone && typeof lampiranZoneState==='function'){
                lampiranZoneState(zone).staged=d.staged.slice();
                syncLampiranInputFiles(zone);
                renderLampiranFileList(zone);
            }
        }
        return true;
    }
    function clearDraftFor(taskId){ if(taskId) delete kirimLaporanDrafts[taskId]; }
    // Tutup modal (tombol "Tutup" / Esc) = batal keluar dari wizard, BUKAN
    // pindah checkpoint -> BUANG semua draft yang belum disubmit. Jadi pas
    // modal dibuka lagi field balik kosong (mode create) atau balik ke data
    // tersimpan dari server (mode edit). Pindah antar checkpoint (klik step /
    // Prev / Next) tetap nyimpen draft lewat captureCurrentDraft() -- itu
    // gestur yang beda.
    function discardAllDrafts(){
        kirimLaporanDrafts={};
        // WAJIB kosongkan draftTaskId juga. Modal ditutup TANPA nge-reset DOM
        // form-nya, jadi teks yang tadi diketik masih nyangkut di <textarea>
        // & draftTaskId masih nunjuk ke task terakhir. Tanpa baris ini,
        // captureCurrentDraft() yang jalan di baris awal handler buka-modal
        // berikutnya nangkep teks sisa itu jadi draft baru -- cuma kejadian
        // di checkpoint yang isinya non-kosong (yang kosong ke-skip guard di
        // captureCurrentDraft), makanya "yang kosong kereset, yang ada isinya
        // nggak". Field DOM-nya sendiri selalu ditimpa ulang oleh handler
        // buka-modal (create -> '', edit -> data server), jadi cukup putus
        // rantai re-capture-nya di sini.
        var form=document.getElementById('kirimLaporanForm');
        if(form) form.dataset.draftTaskId='';
    }
    document.getElementById('kirimLaporanCancel')?.addEventListener('click',discardAllDrafts);
    document.addEventListener('keydown',function(e){
        if(e.key!=='Escape')return;
        var m=document.getElementById('kirimLaporanModal');
        if(m&&m.classList.contains('open')) discardAllDrafts();
    },true);

    function initUsePermintaanButtons(){
        document.querySelectorAll('.use-permintaan').forEach(function(btn){
            if(btn.dataset.useBound === '1') return;
            btn.dataset.useBound = '1';
            btn.addEventListener('click',function(){
                var form=document.getElementById('kirimLaporanForm');
                var modal=document.getElementById('kirimLaporanModal');
                if(!form || !modal) return;
                captureCurrentDraft(); // simpan isian task yang lagi dibuka SEBELUM di-reset
                form.dataset.mode='create';
                if(form.dataset.storeAction) form.action=form.dataset.storeAction;
                setFormMethod(form,null);
                // Modal yang sama bisa aja abis dipakai buat "Lihat Progres"
                // (mode readonly) sebelum ini -- pastikan balik ke mode bisa
                // diedit lagi, jangan sampai "nyangkut" readonly.
                setKirimLaporanReadonly(form,false);
                form.dataset.readonlyReason='';
                form.dataset.editingTaskId='';
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
                applyTaskDetail(btn);
                // Mode create belum punya lampiran apa pun buat ditunjukin --
                // daftar file (termasuk sisa staged dari mode edit sebelumnya
                // kalau modal yang sama dipakai ulang) wajib direset ke kosong
                // di sini.
                var lampiranZone=document.getElementById('lampiranDropzone'); if(lampiranZone) setLampiranExisting(lampiranZone,[]);
                var progresInput=form.querySelector('[name="progres"]');
                var progresHint=document.getElementById('progresHint');
                var progresField=document.getElementById('progresField');
                // Permintaan dengan checklist task (mayoritas/semua permintaan
                // sekarang, lihat PermintaanLaporanController::store yang
                // mewajibkan minimal 1 task) tidak butuh input Progres manual
                // sama sekali -- progres-nya SELALU dihitung ulang dari
                // checklist di server (lihat LaporanController::store), jadi
                // field ini cuma nyampah di form. Disembunyikan total di sini,
                // tapi TETAP ditampilkan buat permintaan lama tanpa checklist
                // (hasTasks==0) yang masih butuh checkpoint manual.
                if(progresField) progresField.hidden = btn.dataset.hasTasks==='1';
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
                // Kembalikan isian yang belum disubmit buat task ini (kalau ada)
                // -- ditaruh SETELAH semua reset di atas biar nggak ketimpa lagi.
                form.dataset.draftTaskId=btn.dataset.taskId||'';
                restoreDraftFor(btn.dataset.taskId||'');
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
                captureCurrentDraft(); // simpan isian task yang lagi dibuka SEBELUM di-reset/prefill
                // Checkpoint yang permintaan-nya udah final ("Menunggu
                // pemeriksaan" Pimpinan, data-readonly="1" -- lihat
                // permintaan-laporan-item.blade.php) numpang modal edit yang
                // sama tapi jadi mode LIHAT SAJA (form.dataset.mode='view'):
                // field gak bisa diketik, tombol submit disembunyikan.
                var isReadonly=btn.dataset.readonly==='1';
                form.dataset.mode=isReadonly?'view':'edit';
                form.dataset.readonlyReason=btn.dataset.readonlyReason||'';
                // Dipakai handleWizardSubmitSuccess buat tau task mana yang
                // lagi diedit -- abis "Simpan Perubahan" berhasil, modal
                // wajib TETAP di task ini (bukan lompat ke task "active"
                // berikutnya, itu cuma buat mode create/"Update Progres").
                form.dataset.editingTaskId=btn.dataset.taskId||'';
                form.action=btn.dataset.updateUrl;
                setFormMethod(form,'PATCH');
                setKirimLaporanReadonly(form,isReadonly);
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
                applyTaskDetail(btn);
                // Riwayat lampiran yang PERNAH dikirim buat checkpoint ini --
                // input file gak bisa di-prefill (browser gak izinin), jadi
                // satu-satunya cara nunjukin "ini yang udah ada" adalah render
                // manual ke kotak daftar file (#lampiranFileList), lengkap
                // dengan tombol hapus per-item (lihat setLampiranExisting).
                // Biarin kosong kalau checkpoint-nya memang belum pernah
                // dilampiri apa-apa.
                var lampiranZone=document.getElementById('lampiranDropzone');
                if(lampiranZone){
                    var existingLampiran=[];
                    try{ existingLampiran=btn.dataset.lampiran?JSON.parse(btn.dataset.lampiran):[]; }catch(e){ existingLampiran=[]; }
                    setLampiranExisting(lampiranZone,existingLampiran,isReadonly);
                }
                // Mode edit cuma buat ngoreksi teks checkpoint yang sudah
                // dikirim -- gak pernah nyentuh status task, jadi task_id
                // lama (kalau ada nyangkut dari klik step sebelumnya) wajib
                // dikosongkan lagi di sini.
                var taskIdHidden=form.querySelector('input[name="task_id"]');
                if(taskIdHidden) taskIdHidden.value='';
                var progresInput=form.querySelector('[name="progres"]');
                var progresHint=document.getElementById('progresHint');
                var progresField=document.getElementById('progresField');
                if(progresField) progresField.hidden = btn.dataset.hasTasks==='1';
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
                    // Mode "Lihat Progres" gak pernah boleh diketik ulang,
                    // apapun juga hasTasks-nya (override 2 cabang di atas).
                    if(isReadonly) progresInput.readOnly=true;
                }
                bindProgresLiveText(form);
                applyLaporanTexts(isReadonly?'view':'edit',progresInput?progresInput.value:0,btn.dataset.readonlyReason);
                // Mode edit (bukan "Lihat Progres" read-only): kembalikan isian
                // yang belum disubmit buat task ini kalau ada, override prefill
                // dari server. Mode view SENGAJA tidak (form read-only).
                if(!isReadonly){
                    form.dataset.draftTaskId=btn.dataset.taskId||'';
                    restoreDraftFor(btn.dataset.taskId||'');
                }else{
                    form.dataset.draftTaskId='';
                }
                modal.classList.add('open');
                if(!isReadonly) deskripsi?.focus();
            });
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initEditProgresButtons); else initEditProgresButtons();

    // Task/permintaan yang belum pernah diisi SAMA SEKALI tapi udah
    // Terlambat/Dibatalkan ($isLocked, lihat permintaan-laporan-item.
    // blade.php) -- gak ada checkpoint buat "diedit", jadi buka modal yang
    // sama dalam mode LIHAT dengan field KOSONG (bukan create, bukan edit).
    function initLockedTaskSteps(){
        document.querySelectorAll('.locked-view').forEach(function(btn){
            if(btn.dataset.lockedBound === '1') return;
            btn.dataset.lockedBound = '1';
            btn.addEventListener('click',function(){
                var form=document.getElementById('kirimLaporanForm');
                var modal=document.getElementById('kirimLaporanModal');
                if(!form || !modal) return;
                captureCurrentDraft(); // simpan isian task sebelumnya sebelum pindah ke view terkunci
                form.dataset.mode='view';
                form.dataset.draftTaskId='';
                form.dataset.readonlyReason='locked';
                setKirimLaporanReadonly(form,true);
                lockIdentityFields(form,true);
                var tujuan=form.querySelector('select[name="tujuan_satuan_id"]'); if(tujuan && btn.dataset.targetId) tujuan.value=btn.dataset.targetId;
                lockSelectWithShadow(tujuan,true);
                var prioritas=form.querySelector('select[name="prioritas"]'); if(prioritas) prioritas.value=btn.dataset.prioritas||'';
                lockSelectWithShadow(prioritas,true);
                var perihal=form.querySelector('[name="perihal"]'); if(perihal) perihal.value=btn.dataset.perihal||'';
                var kategori=form.querySelector('[name="proyek"]'); if(kategori) kategori.value=btn.dataset.kategori||'';
                var deskripsi=form.querySelector('[name="deskripsi"]'); if(deskripsi) deskripsi.value='';
                var kendala=form.querySelector('[name="kendala"]'); if(kendala) kendala.value='';
                applyTaskDetail(btn);
                var lampiranZone=document.getElementById('lampiranDropzone');
                if(lampiranZone) setLampiranExisting(lampiranZone,[],true);
                var taskIdHidden=form.querySelector('input[name="task_id"]'); if(taskIdHidden) taskIdHidden.value='';
                var progresInput=form.querySelector('[name="progres"]');
                var progresField=document.getElementById('progresField');
                if(progresField) progresField.hidden = btn.dataset.hasTasks==='1';
                if(progresInput){ progresInput.value=btn.dataset.progres||'0'; progresInput.readOnly=true; }
                bindProgresLiveText(form);
                applyLaporanTexts('view',progresInput?progresInput.value:0,'locked');
                modal.classList.add('open');
            });
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initLockedTaskSteps); else initLockedTaskSteps();

    // ---- Wizard step topbar (#kirimLaporanModal) ----------------------------
    // Topbar horizontal di modal Update Progres/Edit itu BUKAN sumber data
    // sendiri -- dia cuma "tampilan lain" dari .deadline-task-track yang
    // sudah dihitung PHP persis seperti semula (lihat komentar di
    // permintaan-laporan-item.blade.php), tapi disembunyikan dari kartu.
    // Setiap kali salah satu step/tombol yang membuka modal ini diklik, kita
    // cari track task milik kartu yang sama lalu kloning statenya jadi <li>
    // di topbar. Klik satu step di topbar cuma manggil .click() ke tombol
    // ASLI di track tersembunyi itu -- supaya initUsePermintaanButtons/
    // initEditProgresButtons di atas (yang isi form + buka modal) jalan
    // persis sama, tanpa logic baru yang mesti dijaga sinkron manual.
    function resetWizardTopbar(){
        var modal=document.getElementById('kirimLaporanModal');
        var topbar=document.getElementById('kirimLaporanWizardTopbar');
        var stepsList=document.getElementById('kirimLaporanWizardSteps');
        var prevBtn=document.getElementById('kirimLaporanWizardPrev');
        var nextBtn=document.getElementById('kirimLaporanWizardNext');
        if(stepsList) stepsList.innerHTML='';
        if(prevBtn) prevBtn.hidden=true;
        if(nextBtn) nextBtn.hidden=true;
        if(topbar){ topbar.classList.remove('wizard-topbar-visible'); topbar.hidden=true; }
        if(modal) modal.classList.remove('wizard-active');
    }
    // Navigasi panah ("sebelumnya"/"selanjutnya") di topbar checklist --
    // dipakai kalau task-nya kebanyakan sampai gak muat 1 baris. Sengaja
    // BUKAN scrollbar drag polos yang gak jelas ada lanjutannya atau
    // enggak -- tombol ini cuma tampil kalau beneran overflow (lihat
    // refreshWizardTopbarNav), dan otomatis disabled pas udah mentok ujung.
    function refreshWizardTopbarNav(){
        var stepsList=document.getElementById('kirimLaporanWizardSteps');
        var prevBtn=document.getElementById('kirimLaporanWizardPrev');
        var nextBtn=document.getElementById('kirimLaporanWizardNext');
        if(!stepsList||!prevBtn||!nextBtn) return;
        var hasOverflow=stepsList.scrollWidth>stepsList.clientWidth+1;
        prevBtn.hidden=!hasOverflow;
        nextBtn.hidden=!hasOverflow;
        if(!hasOverflow) return;
        prevBtn.disabled=stepsList.scrollLeft<=0;
        nextBtn.disabled=stepsList.scrollLeft+stepsList.clientWidth>=stepsList.scrollWidth-1;
    }
    function initWizardTopbarNav(){
        var stepsList=document.getElementById('kirimLaporanWizardSteps');
        var prevBtn=document.getElementById('kirimLaporanWizardPrev');
        var nextBtn=document.getElementById('kirimLaporanWizardNext');
        if(!stepsList||!prevBtn||!nextBtn||prevBtn.dataset.navBound==='1') return;
        prevBtn.dataset.navBound='1';
        // Geser per "halaman" (lebar tampilan topbar dikurangi sedikit
        // biar item di tepi kelihatan kepotong dikit -- kasih clue visual
        // ada lanjutan lagi), bukan geser per-item -- gak perlu tau lebar
        // tiap item satu-satu (nama task panjang-pendeknya beda-beda).
        function scrollByPage(dir){
            stepsList.scrollBy({left:dir*Math.max(stepsList.clientWidth-60,120),behavior:'smooth'});
        }
        prevBtn.addEventListener('click',function(){ scrollByPage(-1); });
        nextBtn.addEventListener('click',function(){ scrollByPage(1); });
        stepsList.addEventListener('scroll',refreshWizardTopbarNav);
        window.addEventListener('resize',refreshWizardTopbarNav);
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initWizardTopbarNav); else initWizardTopbarNav();
    function buildWizardTopbar(triggerBtn){
        var modal=document.getElementById('kirimLaporanModal');
        var topbar=document.getElementById('kirimLaporanWizardTopbar');
        var stepsList=document.getElementById('kirimLaporanWizardSteps');
        if(!modal||!topbar||!stepsList) return;
        // Dicatat SEBELUM topbar.hidden diubah -- buat tau ini pertama kali
        // topbar kebuka (dari kondisi hidden) atau cuma pindah antar step
        // yang topbar-nya udah kebuka (lihat pemakaiannya di bawah).
        var wasHidden=topbar.hidden;
        var card=triggerBtn.closest('.deadline-sender-item');
        var track=card?card.querySelector('.deadline-task-track'):null;
        var steps=track?Array.prototype.slice.call(track.querySelectorAll('.deadline-task-step')):[];
        stepsList.innerHTML='';
        if(!steps.length){ resetWizardTopbar(); return; }
        steps.forEach(function(step){
            var state=step.classList.contains('done')?'done':(step.classList.contains('active')?'active':'pending');
            var li=document.createElement('li');
            li.className='wizard-step wizard-step-'+state;
            // Step yang beneran lagi dibuka form-nya sekarang (btn yang
            // diklik buat munculin modal ini) -- niru tab "Hiring stages" di
            // referensiTask2.png (card putih terangkat + warna accent),
            // ditandai TERPISAH dari status done/active/pending asli
            // (checkmark/nomornya tetap ikut status apa adanya), soalnya
            // user bisa buka form buat step MANA PUN termasuk ngedit step
            // yang sudah "Selesai".
            if(step===triggerBtn) li.classList.add('wizard-step-current');
            // Task yang belum selesai TAPI permintaannya udah "mati" -- entah
            // lewat deadline (data-terlambat, dari $permintaan->isTerlambat())
            // ATAU dibatalkan Pimpinan (class .locked, dari $isLocked di
            // permintaan-laporan-item.blade.php) -- ditandai MERAH, bukan
            // oranye "Sedang dikerjakan" biasa. Task yang sudah "Selesai"
            // TETAP hijau apa adanya.
            if(state!=='done' && (step.dataset.terlambat==='1' || step.classList.contains('locked'))) li.classList.add('wizard-step-late');
            var dot=document.createElement('span');
            dot.className='wizard-step-dot';
            if(state==='done'){
                // Ikon checkmark SVG (bukan karakter teks "✓") -- niru
                // referensiTask.png, bentuknya konsisten di semua font/OS.
                dot.innerHTML='<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';
            }else{
                dot.textContent=step.querySelector('.deadline-task-num')?step.querySelector('.deadline-task-num').textContent:'';
            }
            // Cuma 1 baris teks (nama task doang) -- niru referensiTask2.png
            // yang topbar-nya gak punya caption/sub-teks status kedua, beda
            // dari desain sidebar vertikal sebelumnya.
            var label=document.createElement('span');
            label.className='wizard-step-label';
            var srcLabel=step.querySelector('.deadline-task-label');
            label.textContent=srcLabel?srcLabel.textContent:'';
            li.appendChild(dot);
            li.appendChild(label);
            if(step.disabled){
                li.setAttribute('aria-disabled','true');
            }else{
                li.addEventListener('click',function(){ step.click(); });
            }
            stepsList.appendChild(li);
        });
        topbar.hidden=false;
        modal.classList.add('wizard-active');
        initWizardTopbarNav();
        // Posisikan scroll ke step current SEKARANG JUGA, sinkron (bukan
        // ditunda ke requestAnimationFrame) -- list barusan dibangun ulang
        // dari kosong (innerHTML='' di atas) jadi scrollLeft-nya otomatis
        // 0. Kalau nunggu rAF buat baru mindahin ke posisi current, browser
        // sempat ngecat 1 frame dengan scroll di posisi 0 (keliatan
        // "kedip" balik ke step pertama) sebelum lompat ke step yang
        // beneran diklik -- itu yang kerasa "kaku" tiap pindah task.
        // Dengan ngerjain ini sebelum ada paint sama sekali, gak ada
        // frame perantara yang keliatan.
        refreshWizardTopbarNav();
        var currentLi=stepsList.querySelector('.wizard-step-current');
        if(currentLi) currentLi.scrollIntoView({inline:'center',block:'nearest'});
        // Fade+slide-in topbar-nya CUMA pas pertama kali kebuka (dari
        // kondisi hidden) -- kalau di-replay ulang tiap kali pindah step
        // (topbar-nya udah kebuka duluan), transisi 220ms itu yang bikin
        // kerasa "kaku"/nge-lag tiap klik pindah task, padahal harusnya
        // instan pindah aja tanpa animasi masuk lagi.
        if(wasHidden){
            topbar.classList.remove('wizard-topbar-visible');
            window.requestAnimationFrame(function(){ topbar.classList.add('wizard-topbar-visible'); });
        }else{
            topbar.classList.add('wizard-topbar-visible');
        }
        // Garis penanda + dot current TUMBUH dari kosong/kecil ke penuh
        // (lihat .wizard-step-current::after & .wizard-step-dot di CSS) --
        // class-nya sengaja BELUM dipasang di forEach di atas, baru
        // ditambahin di sini 2 frame kemudian (double rAF), supaya browser
        // sempat ngecat dulu state AWALnya (scaleX(0)/scale(.75)) sebelum
        // transisi ke state akhir kepicu -- kalau langsung dipasang bareng
        // pas elemennya baru dibikin, gak ada transisi yang kelihatan
        // (browser cuma nongolin state akhirnya langsung, gak sempat "dari").
        // Beda dari animasi fade topbar di atas, ini SENGAJA main ulang
        // tiap kali pindah task -- itu justru intinya, jadi keliatan jelas
        // task mana yang baru aja "mendarat" jadi current.
        if(currentLi){
            window.requestAnimationFrame(function(){
                window.requestAnimationFrame(function(){ currentLi.classList.add('wizard-step-marker-in'); });
            });
        }
    }
    function initWizardStepTopbar(){
        document.querySelectorAll('.use-permintaan, .edit-progres-btn, .locked-view').forEach(function(btn){
            if(btn.dataset.wizardTopbarBound==='1') return;
            btn.dataset.wizardTopbarBound='1';
            btn.addEventListener('click',function(){
                if(btn.classList.contains('deadline-task-step')){
                    buildWizardTopbar(btn);
                }else{
                    resetWizardTopbar();
                }
            });
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initWizardStepTopbar); else initWizardStepTopbar();

    // Dipanggil realtime (laporan-role-realtime-sync.blade.php) tiap kartu
    // di-replace -- kalau wizard #kirimLaporanModal lagi kebuka buat permintaan
    // itu, topbar step-nya DIBANGUN ULANG dari kartu FRESH biar state-nya ikut
    // update tanpa tutup-buka: task belum selesai + permintaan lewat deadline
    // -> merah (wizard-step-late), dst. Isi form (deskripsi/kendala/lampiran)
    // gak disentuh -- cuma strip step-nya.
    window.siberadRefreshWizardTopbar=function(){
        var modal=document.getElementById('kirimLaporanModal');
        var form=document.getElementById('kirimLaporanForm');
        if(!modal||!form||!modal.classList.contains('open')||!modal.classList.contains('wizard-active')) return;
        var pidEl=form.querySelector('input[name="permintaan_laporan_id"]');
        var tidEl=form.querySelector('input[name="task_id"]');
        var pid=pidEl?String(pidEl.value||''):'';
        if(!pid) return;
        var card=document.querySelector('#permintaan-laporan .deadline-sender-item[data-realtime-permintaan-id="'+pid+'"], #riwayat .deadline-sender-item[data-realtime-permintaan-id="'+pid+'"]');
        if(!card) return;
        var track=card.querySelector('.deadline-task-track');
        var steps=track?Array.prototype.slice.call(track.querySelectorAll('.deadline-task-step')):[];
        if(!steps.length) return;
        var tid=tidEl?String(tidEl.value||''):'';
        var target=null;
        if(tid) target=steps.filter(function(s){return String(s.dataset.taskId||'')===tid;})[0]||null;
        if(!target){
            var lis=Array.prototype.slice.call(document.querySelectorAll('#kirimLaporanWizardSteps .wizard-step'));
            var curIdx=lis.findIndex(function(li){return li.classList.contains('wizard-step-current');});
            if(curIdx>=0 && steps[curIdx]) target=steps[curIdx];
        }
        target=target||steps[0];
        buildWizardTopbar(target);

        // Kartu barusan jadi "mati" (Terlambat/Dibatalkan -> step .locked-view)
        // sementara wizard lagi mode create/edit -> alihin ke mode VIEW
        // read-only, SAMA kayak initLockedTaskSteps(): textarea readonly,
        // tombol "Kirim Laporan" disembunyiin, dropzone lampiran ditutup.
        // Teks yang udah diketik user SENGAJA gak dihapus (biar bisa disalin),
        // cuma dikunci.
        //
        // Penanda "wizard lagi terkunci" pakai form.dataset.readonlyReason===
        // 'locked' (BUKAN form.dataset.mode!=='view') supaya transisinya kebaca
        // 2 arah: pas Pimpinan buka lagi permintaannya (Edit Deadline / kasih
        // deadline baru), step kartu balik non-locked -> reason di-reset ke ''
        // -> kalau nanti Terlambat/Dibatalkan LAGI, blok kunci di bawah nyala
        // lagi & toast-nya muncul lagi (dulu nyangkut: sekali kekunci,
        // form.dataset.mode 'view' selamanya -> toast cuma sekali seumur modal).
        // "Terkunci" = SELURUH permintaan-nya mati (Terlambat / Dibatalkan
        // Pimpinan). Dibaca dari <article data-locked> di kartu, BUKAN dari
        // kelas .locked-view di satu step: step yang sudah "done"
        // (edit-progres-btn) TIDAK pernah dapat kelas .locked-view walau
        // permintaan-nya dibatalkan (lihat permintaan-laporan-item.blade.php).
        // Jadi kalau step yang lagi dibuka kebetulan "done", target.classList
        // salah baca "nggak terkunci" -> toast "dibuka lagi" nongol sendiri &
        // lock/unlock modal nggak sinkron sama state Pimpinan.
        var lockedNow=card.dataset.locked==='1';
        var wizardLocked=form.dataset.readonlyReason==='locked';
        if(lockedNow && !wizardLocked){
            form.dataset.mode='view';
            form.dataset.readonlyReason='locked';
            setKirimLaporanReadonly(form,true);
            var lampiranZone=document.getElementById('lampiranDropzone');
            if(lampiranZone) setLampiranExisting(lampiranZone,[],true);
            var progresInput=form.querySelector('[name="progres"]');
            applyLaporanTexts('view',progresInput?progresInput.value:0,'locked');
            var alasan=card.dataset.terlambat==='1'
                ? 'Batas waktu permintaan ini sudah lewat.'
                : 'Permintaan ini dibatalkan oleh Pimpinan.';
            window.siberadShowToast&&window.siberadShowToast('info',alasan+' Formulir dikunci, laporan tidak bisa dikirim lagi.');
        }else if(!lockedNow && wizardLocked){
            // Pimpinan buka lagi permintaan ini -> lepas kunci wizard, balikin
            // ke mode isi (default "create"/Update Progres -- jalur paling umum
            // buat permintaan aktif), tanpa perlu tutup-buka modal. Teks yang
            // tadi keketik TETAP dibiarin, cuma dibuka lagi supaya bisa diedit.
            form.dataset.mode='create';
            form.dataset.readonlyReason='';
            form.dataset.editingTaskId='';
            if(form.dataset.storeAction) form.action=form.dataset.storeAction;
            setFormMethod(form,null);
            setKirimLaporanReadonly(form,false);
            var taskIdReopen=form.querySelector('input[name="task_id"]');
            if(taskIdReopen) taskIdReopen.value=target.dataset.taskId||'';
            var lampiranZoneReopen=document.getElementById('lampiranDropzone');
            if(lampiranZoneReopen) setLampiranExisting(lampiranZoneReopen,[],false);
            var progresReopen=form.querySelector('[name="progres"]');
            applyLaporanTexts('create',progresReopen?progresReopen.value:0);
            window.siberadShowToast&&window.siberadShowToast('success','Permintaan laporan ini dibuka lagi oleh Pimpinan. Kamu bisa lanjut isi laporannya.');
        }
    };

    // Tombol "Update Progres" baru di kartu (permintaan dengan checklist task,
    // bukan revisi) -- proxy klik ke step "active" (atau "done" kalau semua
    // task kebetulan sudah selesai) di track tersembunyi milik kartu yang
    // sama, supaya modal + topbar wizard terbuka lewat alur yang sama persis
    // seperti klik step secara langsung.
    function initWizardEntryButtons(){
        document.querySelectorAll('.deadline-wizard-entry-btn').forEach(function(btn){
            if(btn.dataset.wizardEntryBound==='1') return;
            btn.dataset.wizardEntryBound='1';
            btn.addEventListener('click',function(){
                var card=btn.closest('.deadline-sender-item');
                var track=card?card.querySelector('.deadline-task-track'):null;
                if(!track) return;
                // :not([disabled]) -- task "active" yang terkunci
                // (Terlambat/Dibatalkan, lihat $isLocked di
                // permintaan-laporan-item.blade.php) gak boleh kepilih di
                // sini, jatuhkan ke step "done" TERAKHIR (bukan pertama) --
                // itu checkpoint paling relevan: buat "Lihat Progres" biasa
                // maupun buat "Update Progres" permintaan yang lagi Revisi
                // (semua task udah "done", yang perlu dibenerin & dikirim
                // ulang ya laporan final di step terakhir).
                // Fallback ke step manapun (locked/✕ termasuk) kalau semua
                // task terkunci & belum ada satu pun yang "done" -- biar
                // "Lihat Progres" tetap kebuka nampilin checklist ✕ semua.
                var doneSteps=track.querySelectorAll('.deadline-task-step.done');
                var target=track.querySelector('.deadline-task-step.active:not([disabled])')||doneSteps[doneSteps.length-1]||track.querySelector('.deadline-task-step');
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

        // Lampiran WAJIB (min. 1 file) untuk setiap kirim/update checkpoint --
        // kecuali mode "Lihat Progres" (data-mode="view"). Input file-nya
        // di-drive dropzone kustom (state.existing + state.staged), jadi
        // atribut `required` bawaan HTML sengaja TIDAK dipasang (bakal salah
        // nolak mode edit yang cuma mempertahankan lampiran lama tanpa upload
        // baru). Dicek di sini saat submit: capture phase + stopImmediate-
        // Propagation supaya overlay konfirmasi "Kirim Laporan?" di
        // laporan-role.blade.php TIDAK kebuka kalau lampiran masih kosong.
        // Native constraint validation (field `required` lain) sudah jalan
        // lebih dulu -- kalau ada yang kosong, event 'submit' ini tidak
        // pernah nembak, jadi urutannya aman.
        form.addEventListener('submit',function(e){
            if(form.dataset.mode==='view') return;
            var zone=document.getElementById('lampiranDropzone');
            if(!zone) return;
            var st=(typeof lampiranZoneState==='function')?lampiranZoneState(zone):null;
            var count=st?(st.existing.length+st.staged.length):0;
            var errEl=lampiranRequiredErrorEl(zone);
            if(count>0){
                if(errEl) errEl.style.display='none';
                zone.classList.remove('field-invalid');
                return;
            }
            e.preventDefault();
            e.stopImmediatePropagation();
            zone.classList.add('field-invalid');
            if(errEl){ errEl.textContent='Lampiran wajib diisi, minimal 1 file.'; errEl.style.display='flex'; }
            zone.scrollIntoView({block:'center',behavior:'smooth'});
        },true);
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initKirimLaporanValidation); else initKirimLaporanValidation();

    // Dropzone Lampiran -- input aslinya jadi overlay transparan penuh 1
    // kotak (lihat CSS .lampiran-dropzone-input), jadi klik ATAU drag&drop
    // file ke mana pun di kotak ini otomatis kena input-nya (perilaku
    // native browser buat file input, gak butuh JS drop-handler manual).
    // Kotak daftar file (#lampiranFileList) TERPISAH dari kotak dropzone --
    // nampilin baris utk tiap lampiran, baik yang LAMA (sudah tersimpan di
    // server, dari mode edit) maupun yang BARU dipilih/di-drop (belum
    // dikirim). FileList bawaan browser itu IMMUTABLE (gak bisa hapus 1
    // item doang), jadi file baru yang dipilih ditampung sendiri di array
    // JS (state.staged) lalu di-assign ulang ke input.files lewat
    // DataTransfer tiap kali isinya berubah -- itulah satu-satunya cara
    // dukung "pilih file lagi" (nambah) maupun "hapus salah satu" tanpa
    // kehilangan file lain yang sudah dipilih sebelumnya.
    // Lampiran lama yang dihapus dari daftar (sebelum disubmit) dicatat di
    // state.removedIds lalu disinkronkan jadi input hidden
    // removed_lampiran_ids[] -- itu yang dibaca LaporanController::
    // updateProgres() buat tahu lampiran lama mana yang SENGAJA tidak mau
    // dibawa lagi ke checkpoint baru.
    function formatLampiranSize(bytes){
        if(bytes<1024*1024) return Math.max(1,Math.round(bytes/1024))+' KB';
        return (bytes/1024/1024).toFixed(1)+' MB';
    }
    function lampiranZoneState(zone){
        if(!zone._lampiranState) zone._lampiranState={ existing:[], removedIds:[], staged:[], stagedUrls:[], readonly:false };
        return zone._lampiranState;
    }
    function lampiranFileExtBadge(nama){
        var dot=(nama||'').lastIndexOf('.');
        var ext=dot>-1?nama.slice(dot+1):'';
        return (ext||'file').toUpperCase().slice(0,4);
    }
    function lampiranRowRemoveSvg(){
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>';
    }
    function syncLampiranRemovedInputs(zone){
        var form=zone.closest('form');
        if(!form) return;
        form.querySelectorAll('input[data-lampiran-removed-input="1"]').forEach(function(el){ el.remove(); });
        lampiranZoneState(zone).removedIds.forEach(function(id){
            var el=document.createElement('input');
            el.type='hidden'; el.name='removed_lampiran_ids[]'; el.value=id;
            el.setAttribute('data-lampiran-removed-input','1');
            form.appendChild(el);
        });
    }
    function syncLampiranInputFiles(zone){
        var input=zone.querySelector('.lampiran-dropzone-input');
        if(!input) return;
        var dt=new DataTransfer();
        lampiranZoneState(zone).staged.forEach(function(file){ dt.items.add(file); });
        input.files=dt.files;
    }
    function buildLampiranRow(zone,data){
        var row=document.createElement('div');
        row.className='lampiran-file-row';
        var icon=document.createElement('span');
        var extBadge=(window.siberadLampiranBadge&&window.siberadLampiranBadge(data.nama))||{text:lampiranFileExtBadge(data.nama),cls:'lfx-other'};
        icon.className='lampiran-file-row-icon '+extBadge.cls;
        icon.textContent=extBadge.text;
        var info=document.createElement('span');
        info.className='lampiran-file-row-info';
        // Nama file selalu jadi link yang bisa diklik buat buka/preview file
        // aslinya di tab baru -- lampiran LAMA (kind:'existing') pakai URL
        // asli di server, lampiran BARU yang baru dipilih/di-drop (belum
        // sempat kekirim ke server) pakai blob: URL sementara dari
        // URL.createObjectURL() yang dibuat di renderLampiranFileList,
        // supaya bisa dibuka/dipreview browser TANPA harus disubmit dulu.
        var name=document.createElement(data.url?'a':'span');
        name.className='lampiran-file-row-name';
        name.textContent=data.nama||'Lampiran';
        if(data.url){ name.href=data.url; name.target='_blank'; name.rel='noopener'; }
        var size=document.createElement('span');
        size.className='lampiran-file-row-size';
        size.textContent=data.kind==='existing'?'Tersimpan':(data.size||'');
        info.appendChild(name); info.appendChild(size);
        row.appendChild(icon); row.appendChild(info);
        // Mode "Lihat Progres" (checkpoint sudah final, lihat
        // initEditProgresButtons) gak boleh hapus lampiran -- tombol hapus
        // sengaja gak dirender sama sekali, bukan cuma disembunyikan CSS,
        // biar gak ada cara nyentuh state via keyboard/devtools juga.
        if(!lampiranZoneState(zone).readonly){
            var removeBtn=document.createElement('button');
            removeBtn.type='button';
            removeBtn.className='lampiran-file-row-remove';
            removeBtn.setAttribute('aria-label','Hapus file');
            removeBtn.innerHTML=lampiranRowRemoveSvg();
            removeBtn.addEventListener('click',function(){
                var state=lampiranZoneState(zone);
                if(data.kind==='existing'){
                    state.existing=state.existing.filter(function(x){ return x.id!==data.id; });
                    state.removedIds.push(data.id);
                    syncLampiranRemovedInputs(zone);
                }else{
                    state.staged.splice(data.idx,1);
                    syncLampiranInputFiles(zone);
                }
                renderLampiranFileList(zone);
            });
            row.appendChild(removeBtn);
        }
        return row;
    }
    function renderLampiranFileList(zone){
        // Kotak daftar file ada di BAWAH kotak dropzone (bukan di dalamnya
        // lagi -- lihat markup di laporan-role.blade.php), jadi dicari
        // lewat ID global, bukan relatif dari induk zone.
        var listBox=document.getElementById('lampiranFileList');
        if(!listBox) return;
        var state=lampiranZoneState(zone);
        listBox.querySelectorAll('.lampiran-file-row').forEach(function(el){ el.remove(); });
        // blob: URL punya file baru dibuat ULANG tiap render (revoke yang
        // lama dulu biar gak numpuk/leak) -- lebih simpel daripada nge-track
        // umur tiap URL satu-satu, karena renderLampiranFileList emang udah
        // selalu dipanggil ulang tiap kali state.staged berubah (nambah/
        // hapus file) ATAU modal dibuka lagi (lihat setLampiranExisting).
        state.stagedUrls.forEach(function(url){ URL.revokeObjectURL(url); });
        state.stagedUrls=[];
        var emptyEl=listBox.querySelector('.lampiran-file-list-empty');
        var total=state.existing.length+state.staged.length;
        state.existing.forEach(function(item){
            listBox.appendChild(buildLampiranRow(zone,{ kind:'existing', id:item.id, nama:item.nama, url:item.url }));
        });
        state.staged.forEach(function(file,idx){
            var url=URL.createObjectURL(file);
            state.stagedUrls.push(url);
            listBox.appendChild(buildLampiranRow(zone,{ kind:'staged', idx:idx, nama:file.name, size:formatLampiranSize(file.size), url:url }));
        });
        if(emptyEl) emptyEl.hidden=total>0;
        // Lampiran WAJIB: begitu ada minimal 1 file, hapus penanda error yang
        // sempat muncul dari percobaan submit sebelumnya. Error-nya cuma
        // DIMUNCULKAN oleh guard submit (di initKirimLaporanValidation), nggak
        // dari sini -- biar nggak nongol duluan pas modal baru kebuka & masih
        // kosong wajar.
        if(total>0){
            var reqEl=document.querySelector('.kirim-laporan-error[data-lampiran-required-error="1"]');
            if(reqEl) reqEl.style.display='none';
            var dz=document.getElementById('lampiranDropzone');
            if(dz) dz.classList.remove('field-invalid');
        }
    }
    function lampiranRequiredErrorEl(zone){
        var anchor=zone.parentElement;
        if(!anchor) return null;
        var msg=anchor.querySelector(':scope > .kirim-laporan-error[data-lampiran-required-error="1"]');
        if(!msg){
            msg=document.createElement('span');
            msg.className='kirim-laporan-error';
            msg.setAttribute('data-lampiran-required-error','1');
            msg.style.display='none';
            zone.insertAdjacentElement('afterend',msg);
        }
        return msg;
    }
    // Dipanggil tiap kali modal dibuka (create ATAU edit) buat reset daftar
    // ke kondisi checkpoint yang lagi dibuka -- create selalu [] (belum ada
    // lampiran apa-apa), edit diisi dari data-lampiran tombolnya (lihat
    // initEditProgresButtons).
    // 10 MB, SAMA PERSIS sama batas 'max:10240' (KB) di validasi
    // LaporanController::store()/updateProgres() -- kalau limitnya diubah,
    // ubah juga angka di sana biar pesan client-side ini gak bohong.
    var LAMPIRAN_MAX_BYTES=10*1024*1024;
    function lampiranSizeErrorEl(zone){
        var anchor=zone.parentElement;
        if(!anchor) return null;
        var msg=anchor.querySelector(':scope > .kirim-laporan-error[data-lampiran-size-error="1"]');
        if(!msg){
            msg=document.createElement('span');
            msg.className='kirim-laporan-error';
            msg.setAttribute('data-lampiran-size-error','1');
            msg.style.display='none';
            zone.insertAdjacentElement('afterend',msg);
        }
        return msg;
    }
    function setLampiranExisting(zone,list,readonly){
        var state=lampiranZoneState(zone);
        state.existing=(list||[]).slice();
        state.removedIds=[];
        state.staged=[];
        state.readonly=!!readonly;
        var input=zone.querySelector('.lampiran-dropzone-input');
        if(input) input.value='';
        // Mode "Lihat Progres" gak boleh nambah file baru sama sekali --
        // kotak dropzone-nya sendiri disembunyikan, sisain kotak daftar file
        // doang (baca-baca aja, tombol hapus per-baris juga udah gak
        // dirender, lihat buildLampiranRow).
        zone.hidden=!!readonly;
        var msg=lampiranSizeErrorEl(zone);
        if(msg) msg.style.display='none';
        syncLampiranRemovedInputs(zone);
        renderLampiranFileList(zone);
    }
    function initLampiranDropzone(){
        document.querySelectorAll('.lampiran-dropzone').forEach(function(zone){
            if(zone.dataset.dzBound==='1') return;
            zone.dataset.dzBound='1';
            var input=zone.querySelector('.lampiran-dropzone-input');
            if(!input) return;
            input.addEventListener('change',function(){
                var state=lampiranZoneState(zone);
                var msg=lampiranSizeErrorEl(zone);
                var ditolak=[];
                if(input.files&&input.files.length){
                    for(var i=0;i<input.files.length;i++){
                        var file=input.files[i];
                        // Batas ukuran dicek di SINI (bukan cuma nunggu
                        // response error dari server abis submit) supaya
                        // file kegedean langsung ketolak & ketauan alasannya
                        // saat itu juga, gak baru nyampe pas klik Kirim.
                        if(file.size>LAMPIRAN_MAX_BYTES){ ditolak.push(file.name); continue; }
                        state.staged.push(file);
                    }
                }
                if(msg){
                    if(ditolak.length){
                        msg.textContent=(ditolak.length===1?('"'+ditolak[0]+'"'):(ditolak.length+' file'))+' melebihi batas 10 MB, tidak ditambahkan.';
                        msg.style.display='flex';
                    }else{
                        msg.style.display='none';
                    }
                }
                syncLampiranInputFiles(zone);
                renderLampiranFileList(zone);
            });
            ['dragenter','dragover'].forEach(function(evt){
                zone.addEventListener(evt,function(){ zone.classList.add('is-dragover'); });
            });
            ['dragleave','drop'].forEach(function(evt){
                zone.addEventListener(evt,function(){ zone.classList.remove('is-dragover'); });
            });
            renderLampiranFileList(zone);
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initLampiranDropzone); else initLampiranDropzone();

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
        document.querySelectorAll('#permintaan-laporan .deadline-sender-item, #riwayat .deadline-sender-item').forEach(function(card){
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
                // Refresh HANYA filter list tempat kartu ini berada -- persis
                // pola Pimpinan (bindPinButtons vs bindRiwayatPinButtons yang
                // scoped, masing-masing manggil 1 filter). Dulu handler ini cuma
                // manggil filter #permintaan-laporan, jadi pin di kartu Riwayat
                // baru pindah pas poll 3 dtk berikutnya ("lemot"); sempat diganti
                // manggil DUA-DUANYA sekaligus, tapi apply() #permintaan-laporan
                // (beda tab) jalan duluan bikin reorder Riwayat kerasa "loncat".
                if(card.closest('#riwayat')){
                    window.siberadRefreshRiwayatFilter&&window.siberadRefreshRiwayatFilter();
                }else{
                    window.siberadRefreshPermintaanFilter&&window.siberadRefreshPermintaanFilter();
                }
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
    window.siberadRebindPermintaanActions=function(){initUsePermintaanButtons();initEditProgresButtons();initLockedTaskSteps();initWizardStepTopbar();initWizardEntryButtons();initDcardMenus();initDcardPinButtons();initPermintaanSearch();window.siberadRefreshPermintaanFilter&&window.siberadRefreshPermintaanFilter();window.siberadRefreshRiwayatFilter&&window.siberadRefreshRiwayatFilter();};

    // Submit #kirimLaporanForm (Update Progres/Edit Progres) lewat AJAX,
    // BUKAN native form submit -- native submit selalu bikin full-page
    // reload yang otomatis "menutup" modal (state open cuma class CSS,
    // ke-reset abis reload). Dipanggil dari tombol "Ya, Kirim" di
    // laporan-role.blade.php (window.siberadSubmitKirimLaporanForm).
    function handleWizardSubmitSuccess(data){
        var modal=document.getElementById('kirimLaporanModal');
        var form=document.getElementById('kirimLaporanForm');
        // Simpan SEBELUM kartu diganti/direbind -- "Simpan Perubahan" (edit
        // checkpoint yang SUDAH ada) wajib tetap di task yang SAMA, BUKAN
        // lompat ke task "active" berikutnya (itu cuma buat "Update
        // Progres"/create, nandain task baru selesai).
        var wasEdit=form&&form.dataset.mode==='edit';
        var editingTaskId=form?form.dataset.editingTaskId:'';
        // Checkpoint task ini sukses terkirim -> buang draft-nya + kosongkan
        // penanda, biar nextStep.click() di bawah nggak nyeret balik isian yang
        // barusan disubmit sebagai "draft" task itu.
        if(form){
            clearDraftFor(form.dataset.draftTaskId||'');
            clearDraftFor(editingTaskId||'');
            form.dataset.draftTaskId='';
        }
        window.siberadShowToast&&window.siberadShowToast('success',data.message||'Berhasil dikirim.');
        var permintaanId=data.permintaan_id;
        var oldCard=permintaanId?document.querySelector('.deadline-sender-item[data-realtime-permintaan-id="'+permintaanId+'"]'):null;
        if(oldCard&&data.item_html){
            var wrap=document.createElement('div');
            wrap.innerHTML=data.item_html.trim();
            var newCard=wrap.firstElementChild;
            if(newCard) oldCard.replaceWith(newCard);
        }
        window.siberadRebindPermintaanActions&&window.siberadRebindPermintaanActions();
        // Kartu barusan diganti (kalau ada) sudah nyerminin checklist task
        // TERKINI -- KLIK BENERAN step yang tepat (bukan panggil fungsi isi
        // form manual) supaya semua listener yang udah ada (isi form,
        // rebuild topbar wizard) jalan persis kayak user ngeklik sendiri.
        var freshCard=permintaanId?document.querySelector('.deadline-sender-item[data-realtime-permintaan-id="'+permintaanId+'"]'):null;
        var nextStep=null;
        if(wasEdit&&editingTaskId){
            nextStep=freshCard?freshCard.querySelector('.deadline-task-track .deadline-task-step[data-task-id="'+editingTaskId+'"]'):null;
        }else{
            nextStep=freshCard?freshCard.querySelector('.deadline-task-track .deadline-task-step.active:not([disabled])'):null;
        }
        if(nextStep){
            nextStep.click();
        }else if(modal){
            modal.classList.remove('open');
        }
    }
    window.siberadSubmitKirimLaporanForm=async function(form){
        var submitBtn=document.getElementById('kirimLaporanSubmitBtn');
        if(submitBtn) submitBtn.disabled=true;
        try{
            var formData=new FormData(form);
            var response=await fetch(form.action,{
                method:'POST',
                body:formData,
                headers:{'Accept':'application/json'},
                credentials:'same-origin'
            });
            var data=null;
            try{ data=await response.json(); }catch(e){}
            if(response.ok&&data){
                handleWizardSubmitSuccess(data);
            }else{
                var msg=(data&&(data.message||(data.errors&&Object.values(data.errors).flat()[0])))||'Gagal mengirim, coba lagi.';
                window.siberadShowToast&&window.siberadShowToast('error',msg);
            }
        }catch(err){
            window.siberadShowToast&&window.siberadShowToast('error','Gagal terhubung ke server, coba lagi.');
        }finally{
            if(submitBtn) submitBtn.disabled=false;
        }
    };

    // Pencarian+sort kartu -- reuse gaya .rpt-filter-* yang sama dengan
    // tabel lain (1 sistem), tapi logikanya custom karena isinya kartu
    // <article> bukan baris <tr>. Dipakai bareng buat Permintaan Laporan &
    // Riwayat Laporan lewat config.sectionId/sortField/ascValue yang beda.
    function initCardSearch(config){
        var section=document.getElementById(config.sectionId);
        if(!section||section.dataset.searchReady==='1') return;
        var list=section.querySelector('.deadline-sender-list');
        if(!list) return;
        var initialItems=Array.prototype.slice.call(list.querySelectorAll(':scope > article.deadline-sender-item'));
        if(!initialItems.length) return;
        section.dataset.searchReady='1';

        var bar=document.createElement('div');
        bar.className='rpt-filter-bar';
        // Dropdown sort beda per section: "Deadline Terdekat/Terjauh"
        // (data-deadline-at) buat Permintaan, "Arsip Terbaru/Terlama"
        // (data-archived-at) buat Riwayat -- lihat initPermintaanSearch()
        // di bawah buat config lengkapnya per section.
        bar.innerHTML='<div class="rpt-filter-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" autocomplete="off" placeholder="Cari perihal..." aria-label="Cari perihal"></div><select class="rpt-filter-select" aria-label="Urutkan">'+config.sortOptionsHtml+'</select><span class="rpt-filter-count"></span>';
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
        emptyBox.innerHTML='<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">'+config.emptyText+'</div>';
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
            // Section KOSONG (nol kartu) -- sembunyikan bar cari/filter/hitungan
            // & tampilkan empty-state "Belum ada ..." biar keterangannya SAMA
            // dgn sisi Pimpinan, bukan bar pencarian + "tidak ada yang sesuai
            // pencarian". Terjadi pas data habis dihapus lalu list dikosongkan
            // realtime TANPA reload -- kalau reload, initCardSearch() sudah bail
            // sebelum bikin bar (initialItems.length === 0).
            var srvEmpty=list.querySelector(':scope > .empty-state');
            if(items.length===0){
                bar.style.display='none';
                emptyBox.style.display='none';
                if(!srvEmpty){
                    srvEmpty=document.createElement('div');
                    srvEmpty.className='empty-state';
                    srvEmpty.innerHTML='<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">'+(config.emptyTextNone||'Belum ada data')+'</div>';
                    list.appendChild(srvEmpty);
                }
                srvEmpty.style.display='';
                return;
            }
            bar.style.display='';
            if(srvEmpty) srvEmpty.style.display='none';
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
                // Prioritas urutan: (1) Ditandai manual (tombol bulat pojok
                // kiri-atas) paling atas. (2) Kalau config.statusTier ada
                // (#permintaan-laporan aktif) -> tier per-status dari sudut
                // pandang SATUAN: yang butuh aksi satuan dulu (Revisi -> benerin
                // & kirim ulang, "Terbaru" -> konfirmasi/mulai, "Sedang
                // diproses" -> lagi jalan), lalu "Menunggu" (nunggu Pimpinan,
                // no aksi), lalu grup mandek (Terlambat + Dibatalkan). Selaras
                // sama comparator Pimpinan (danpus-permintaan-arsip-mode.blade.php)
                // tapi urutannya beda karena aksinya kebalik. (3) #riwayat tetap
                // pakai bump "belum dikerjakan" lama (praktis no-op di situ).
                // Deadline Terdekat/Terjauh cuma jadi urutan di DALAM tiap tier.
                var aPin=pinnedIds.has(a.getAttribute('data-realtime-permintaan-id'));
                var bPin=pinnedIds.has(b.getAttribute('data-realtime-permintaan-id'));
                if(aPin!==bPin) return aPin?-1:1;
                if(typeof config.statusTier==='function'){
                    var at=config.statusTier(a.dataset.status),bt=config.statusTier(b.dataset.status);
                    if(at!==bt) return at-bt;
                }else{
                    var aBaru=a.dataset.belumDikerjakan==='1',bBaru=b.dataset.belumDikerjakan==='1';
                    if(aBaru!==bBaru) return aBaru?-1:1;
                }
                var diff=Number(a.dataset[config.sortField])-Number(b.dataset[config.sortField]);
                return sortSelect.value===config.ascValue?diff:-diff;
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
                        // dua rAF -- pastikan state "dari" (transform di atas)
                        // benar-benar kepaint dulu sebelum transisi ke "ke"
                        // dipicu, biar geser-nya nggak ke-skip / kepotong di
                        // akhir (kesannya "ngebut pas berhenti"). Easing halus
                        // + deselerasi panjang: cubic-bezier(.16,1,.3,1).
                        (function(el){
                            requestAnimationFrame(function(){requestAnimationFrame(function(){
                                el.style.transition='transform .58s cubic-bezier(.16,1,.3,1)';
                                el.style.transform='';
                            });});
                        })(item);
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
        window[config.refreshGlobalName]=apply;
        apply();
    }
    function initPermintaanSearch(){
        initCardSearch({
            sectionId:'permintaan-laporan',
            sortField:'deadlineAt',
            ascValue:'terdekat',
            sortOptionsHtml:'<option value="terdekat">Deadline Terdekat</option><option value="terjauh">Deadline Terjauh</option>',
            emptyText:'Tidak ada permintaan laporan yang sesuai dengan pencarian.',
            emptyTextNone:'Belum ada permintaan laporan',
            // Tier per-status (sudut pandang SATUAN). Deadline cuma urutan di
            // DALAM tiap tier. Disepakati sama user, selaras sama comparator
            // Pimpinan (danpus-permintaan-arsip-mode.blade.php) -- cuma
            // urutannya beda karena aksi satuan vs Pimpinan kebalik:
            //   Ditandai > Revisi > Terbaru > Sedang diproses > Menunggu
            //   > Terlambat > Dibatalkan > Disetujui/Ditolak
            // (label dari $statusDisplay di permintaan-laporan-item.blade.php,
            // "Terbaru" = raw status "Belum dikerjakan").
            statusTier:function(s){
                switch(s){
                    case 'Revisi': return 1;           // benerin & kirim ulang -- paling mendesak buat satuan
                    case 'Terbaru': return 2;          // konfirmasi/mulai kerjain
                    case 'Sedang diproses': return 3;  // lagi jalan (+ fallback status tak dikenal)
                    case 'Menunggu': return 4;         // nunggu keputusan Pimpinan, no aksi satuan
                    case 'Terlambat': return 5;        // mandek/terkunci, archive-eligible
                    case 'Dibatalkan': return 6;       // parkir/terkunci
                    case 'Disetujui':
                    case 'Ditolak': return 7;          // selesai (biasanya sudah di Arsip Laporan)
                    default: return 3;
                }
            },
            refreshGlobalName:'siberadRefreshPermintaanFilter'
        });
        // Riwayat: sort berdasar data-archived-at, arsip terbaru duluan
        // sebagai default (senada sama urutan query di DashboardController).
        initCardSearch({
            sectionId:'riwayat',
            sortField:'archivedAt',
            ascValue:'terlama',
            sortOptionsHtml:'<option value="terbaru">Arsip Terbaru</option><option value="terlama">Arsip Terlama</option>',
            emptyText:'Tidak ada arsip laporan yang sesuai dengan pencarian.',
            emptyTextNone:'Belum ada arsip laporan',
            refreshGlobalName:'siberadRefreshRiwayatFilter'
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initPermintaanSearch); else initPermintaanSearch();
})();
</script>
