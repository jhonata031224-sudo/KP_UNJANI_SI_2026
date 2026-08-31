<style>
.deadline-section,.deadline-sender-section{position:relative;overflow:hidden}.deadline-primary,.deadline-secondary{border:1px solid transparent;border-radius:9px;padding:9px 14px;font-size:11px;font-weight:700;cursor:pointer;transition:.15s ease}.deadline-primary{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid));color:var(--on-gold);box-shadow:0 8px 22px -8px rgba(217,146,11,.5)}.deadline-primary:hover{filter:brightness(1.06);transform:translateY(-1px)}.deadline-secondary{background:var(--p-surface-2,var(--panel-alt));border-color:var(--p-border,var(--border));color:var(--p-text,var(--text))}.deadline-secondary:hover{border-color:var(--p-accent,var(--gold-bright));transform:translateY(-1px)}.deadline-primary:active,.deadline-secondary:active{transform:scale(.96)}.deadline-primary:disabled,.deadline-secondary:disabled{opacity:.4;cursor:not-allowed;filter:none!important;transform:none!important;box-shadow:none!important}.deadline-secondary.confirm-btn{background:var(--success,#16834b);border-color:var(--success,#16834b);color:#fff}.deadline-secondary.confirm-btn:hover{filter:brightness(1.1);border-color:var(--success,#16834b)}.deadline-primary.small,.deadline-secondary.small{padding:7px 10px;font-size:10px}.kirim-laporan-btn{letter-spacing:.03em;background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid));color:var(--on-gold);border-color:transparent;box-shadow:0 8px 18px -8px rgba(212,175,55,.55)}.kirim-laporan-btn:hover{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid));color:var(--on-gold);border-color:transparent;box-shadow:0 10px 22px -6px rgba(212,175,55,.65)}.deadline-form-wrap{margin:0 0 18px;padding:16px;border:1px solid var(--p-border,var(--border-soft));border-radius:12px;background:var(--p-surface-2,var(--panel-alt))}.deadline-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.deadline-field{display:flex;flex-direction:column;gap:6px}.deadline-field.full{grid-column:1/-1}.deadline-field label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--p-muted,var(--text-muted))}.deadline-field input,.deadline-field select,.deadline-field textarea{width:100%;box-sizing:border-box;border:1px solid var(--p-border,var(--border));border-radius:8px;background:var(--p-surface,var(--panel));color:var(--p-text,var(--text));padding:9px 10px;font:inherit;font-size:12px}.deadline-field textarea{min-height:100px;resize:none}.deadline-check-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}.deadline-check{display:grid;grid-template-columns:auto 1fr;column-gap:7px;align-items:center;padding:9px;border:1px solid var(--p-border,var(--border));border-radius:9px;background:var(--p-surface,var(--panel));cursor:pointer}.deadline-check input{width:auto}.deadline-check span{font-family:var(--mono);font-size:10px;font-weight:800;color:var(--p-accent,var(--gold-bright))}.deadline-check small{grid-column:2;font-size:9px;color:var(--p-muted,var(--text-muted));line-height:1.35}.deadline-form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}.deadline-table-wrap{overflow-x:auto}.deadline-table{width:100%;border-collapse:collapse;min-width:720px}.deadline-table th{font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:var(--p-muted,var(--text-muted));text-align:left;padding:10px;border-bottom:1px solid var(--p-border,var(--border))}.deadline-table td{padding:11px 10px;border-bottom:1px solid var(--p-border,var(--border));font-size:11px;vertical-align:middle}.deadline-table td strong{display:block}.deadline-table td small{display:block;color:var(--p-muted,var(--text-muted));margin-top:3px;max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.deadline-pill{display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:9px;font-weight:800;border:1px solid transparent;white-space:nowrap}.deadline-pill.wait{color:var(--p-orange);background:var(--p-orange-bg);border-color:var(--p-orange-border)}.deadline-pill.ok{color:#16834b;background:rgba(63,194,125,.12);border-color:rgba(63,194,125,.28)}.deadline-pill.bad{color:#c83b3b;background:rgba(181,52,47,.08);border-color:rgba(198,40,40,.28)}.deadline-pill.blue{color:#2476ad;background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.25)}.deadline-pill.revisi{color:var(--gold-solid);background:rgba(217,146,11,.14);border-color:rgba(217,146,11,.4)}.deadline-pill.new{color:#0d9488;background:rgba(13,148,136,.13);border-color:rgba(13,148,136,.35)}.deadline-revisi{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid))!important;color:var(--on-gold)!important;border-color:transparent!important;box-shadow:0 8px 22px -8px rgba(217,146,11,.5)}.deadline-revisi:hover{color:var(--on-gold)!important;box-shadow:0 10px 26px -6px rgba(217,146,11,.6);filter:none;transform:translateY(-1px)}.deadline-sender-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}.deadline-sender-head h3{margin:0 0 4px}.deadline-sender-head p{margin:0;font-size:11px;color:var(--text-muted);line-height:1.5}.deadline-count{font-family:var(--mono);font-size:10px;color:var(--gold-bright);white-space:nowrap}.deadline-complete{font-size:10px;font-weight:700;color:var(--green)}.deadline-complete.cancelled{color:#c83b3b}
@media(max-width:850px){.deadline-check-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.deadline-form-grid{grid-template-columns:1fr}.deadline-field.full{grid-column:auto}}@media(max-width:550px){.deadline-check-grid{grid-template-columns:1fr}.deadline-sender-head{display:block}.deadline-count{display:inline-block;margin-top:8px}}

/* ---- Grid kartu "project card" -- ganti .deadline-sender-list dari list
   vertikal penuh-lebar jadi grid responsif, tiap .deadline-sender-item jadi
   kartu ringkas (kotak ikon, judul, subjudul, progress bar, meta footer).
   .deadline-sender-list sekarang SIBLING dari .report-card (bukan anaknya
   lagi, lihat laporan-role.blade.php) -- panel judul "Permintaan Laporan"
   tetap jadi box seperti panel lain, kartu-kartunya ngambang sendiri-sendiri
   di luar box itu (shadow per kartu, bukan nempel jadi satu kotak besar). ---- */
{{-- .deadline-sender-section (dash-styles.blade.php) default-nya
     overflow:hidden -- itu bikin box-shadow "tumpukan kartu" yang bocor
     dikit di bawah tiap kartu (lihat .deadline-sender-item di bawah) kepotong
     kalau baris kartunya kebetulan cuma 1 (section-nya jadi mepet pas di
     bawah baris itu). Dimatikan khusus di section ini, nggak sentuh
     .deadline-section (dipakai konteks lain) yang masih butuh overflow:hidden. --}}
.deadline-sender-section{overflow:visible}
.deadline-sender-section > .report-card{margin-bottom:40px}
.deadline-sender-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));column-gap:18px;row-gap:64px}
/* .empty-state jadi satu-satunya isi .deadline-sender-list pas daftarnya
   kosong (lihat forelse-empty di laporan-role.blade.php) -- tanpa
   grid-column:1/-1, dia cuma numpang 1 kolom grid (~280px, ikut minmax di
   atas) kayak kartu biasa, jadi keliatan sempit/nempel kiri, beda sama
   empty-state versi tabel (td colspan, lihat Laporan Tembusan) yang
   otomatis selebar panel. */
.deadline-sender-list > .empty-state{grid-column:1/-1}
{{-- Animasi kartu baru (zoom-in) + kartu lama yang kegeser (FLIP, lihat
     insertItems() di permintaan-laporan-realtime.blade.php) pas ada
     permintaan baru masuk lewat polling realtime. --}}
@keyframes dcardZoomIn{0%{opacity:0;transform:scale(.82)}100%{opacity:1;transform:scale(1)}}
.dcard-enter{animation:dcardZoomIn .38s cubic-bezier(.34,1.3,.64,1) both}
@media(prefers-reduced-motion:reduce){.dcard-enter{animation:none}}
{{-- Border kartu SENGAJA netral tipis buat semua state (near/bad TIDAK lagi
     dikasih border-color amber/merah) -- di referensi kartunya nyaris tanpa
     border sama sekali, cuma dibedain shadow. Urgensi deadline tetap
     kebaca dari warna .dcard-deadline-pill di footer, jadi info-nya nggak
     hilang, cuma dipindah bukan lewat border lagi. --}}
.deadline-sender-item{position:relative;display:flex;flex-direction:column;gap:14px;padding:14px 18px 18px;border:1px solid var(--border-soft);border-radius:18px;background:var(--panel-alt);box-shadow:0 12px 28px -16px rgba(0,0,0,.28);min-width:0}
{{-- Efek "tumpukan kartu" niru referensiArsip2.png -- dilihat lebih teliti,
     ghost-nya SATU lapis aja yang kelihatan jelas, agak sempit (diinset dari
     tepi kartu, bukan full-width), putih polos sama kayak kartu, definisinya
     dari shadow tipis bukan garis border tegas. ::before dipatok dari
     top:100% (tepi bawah kartu asli, tanpa overlap ke kartu). --}}
.deadline-sender-item::before{content:"";position:absolute;top:100%;left:18px;right:18px;height:10px;border-radius:0 0 16px 16px;background:var(--panel-alt);box-shadow:0 4px 8px -3px rgba(0,0,0,.18);z-index:-1}
.dcard-head{display:flex;align-items:center;justify-content:center;margin-top:-42px}
.dcard-icon{width:64px;height:64px;border-radius:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;box-shadow:0 12px 22px -8px rgba(0,0,0,.4)}
.dcard-icon svg{width:30px;height:30px;stroke:currentColor;fill:none;stroke-width:1.7}
{{-- Warna ikon niru PERSIS .priority-tag.prio-* di dashboard Pimpinan
     (laporan-pimpinan.blade.php) -- skema ungu/violet, BUKAN merah/amber
     punya tabel Riwayat di halaman satuan. Ini hex tetap (bukan var CSS)
     karena badge aslinya di Pimpinan juga hardcode hex yang sama, disamain
     biar warna prioritas kebaca konsisten dari sisi Pimpinan maupun satuan.
     "Rendah" ikon teksnya ungu tua (bukan putih) niru kontras badge aslinya
     yang bg lavender muda + teks ungu tua. --}}
.dcard-icon.prio-tinggi{background:#6d28d9;color:#fff}
.dcard-icon.prio-sedang{background:#a855f7;color:#fff}
.dcard-icon.prio-rendah{background:#e9d5ff;color:#6b21a8}
{{-- Badge prioritas di modal "Lihat Detail" (permintaan-laporan-realtime.
     blade.php) SENGAJA pakai class terpisah dari .priority-tag.prio-* biasa
     -- .priority-tag di halaman satuan ini sendiri warnanya merah/amber
     (buat tabel Riwayat), beda sama skema ungu yang dipakai Pimpinan. Biar
     konsisten sama ikon kartu di atas (juga niru Pimpinan), badge ini pakai
     hex yang SAMA PERSIS, bukan warna .priority-tag bawaan halaman ini. --}}
.priority-tag.pl-prio-violet.prio-tinggi{color:#fff;background:#6d28d9;border-color:#6d28d9}
.priority-tag.pl-prio-violet.prio-sedang{color:#fff;background:#a855f7;border-color:#a855f7}
.priority-tag.pl-prio-violet.prio-rendah{color:#6b21a8;background:#e9d5ff;border-color:#e9d5ff}
.dcard-body{min-width:0;display:flex;flex-direction:column;align-items:center;text-align:center;gap:10px}
.deadline-sender-title{font-size:13.5px;font-weight:800;line-height:1.4;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.dcard-status-pill{margin:0 auto}
.dcard-meta-hidden{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
{{-- Tombol tanda manual (checkbox bulat) di pojok kiri-atas kartu, cermin
     posisi menu titik-3 di pojok kanan-atas -- warna emas (aksen utama app)
     pas ditandai, biar beda jelas dari teal badge "Terbaru". --}}
.dcard-pin-btn{position:absolute;top:14px;left:14px;width:22px;height:22px;border-radius:50%;border:1.5px solid var(--border-strong,var(--border-soft));background:var(--panel);display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:5;padding:0;transition:background .15s ease,border-color .15s ease,transform .15s ease}
.dcard-pin-btn svg{width:12px;height:12px;stroke:#fff;fill:none;stroke-width:3;opacity:0;transition:opacity .15s ease}
.dcard-pin-btn:hover{border-color:var(--gold-solid-bright,#F2B94B);transform:scale(1.08)}
.dcard-pin-btn[aria-pressed="true"]{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid));border-color:transparent;box-shadow:0 4px 10px -3px rgba(217,146,11,.6)}
.dcard-pin-btn[aria-pressed="true"] svg{opacity:1}
.dcard-menu-wrap{position:absolute;top:14px;right:14px;width:26px;z-index:5}
.dcard-menu-btn{width:26px;height:26px;border-radius:8px;border:0;background:transparent;color:var(--text-muted);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s ease,color .15s ease}
.dcard-menu-btn svg{width:16px;height:16px}
.dcard-menu-btn:hover{background:var(--panel-2,var(--panel));color:var(--text)}
{{-- Dropdown menu titik-3 -- dulu toggle atribut hidden doang (langsung
     muncul/ilang instan, kaku), sekarang opacity+transform bertransisi kayak
     modal Detail (#permintaanLaporanDetailView), dipicu class .open bukan
     hidden lagi (lihat closeAllDcardMenus/initDcardMenus). --}}
.dcard-menu{position:absolute;top:30px;right:0;min-width:150px;background:var(--panel);border:1px solid var(--border-soft);border-radius:10px;box-shadow:0 14px 32px -12px rgba(0,0,0,.35);padding:5px;z-index:30;opacity:0;transform:scale(.92) translateY(-6px);transform-origin:top right;pointer-events:none;transition:opacity .15s ease,transform .15s ease}
.dcard-menu.open{opacity:1;transform:scale(1) translateY(0);pointer-events:auto}
@media(prefers-reduced-motion:reduce){.dcard-menu{transition:none}}
.dcard-menu-item{display:flex;align-items:center;gap:8px;width:100%;box-sizing:border-box;padding:8px 9px;border:0;background:transparent;border-radius:7px;color:var(--text);font-size:11.5px;font-weight:600;cursor:pointer;text-align:left}
.dcard-menu-item:hover{background:var(--panel-2,var(--panel-alt))}
.dcard-menu-item svg{width:14px;height:14px;flex-shrink:0;opacity:.75}
.dcard-progress{display:flex;flex-direction:column;gap:8px}
.dcard-progress-head{display:flex;align-items:center;justify-content:space-between;font-size:11.5px}
.dcard-progress-label{font-weight:800;color:var(--text)}
.dcard-progress-track{height:8px;border-radius:999px;background:var(--panel-2,var(--panel));border:1px solid var(--border-soft);overflow:hidden}
.dcard-progress-fill{height:100%;border-radius:999px;background:var(--success,var(--green,#16834b));transition:width .25s ease}
.dcard-progress-value{font-family:var(--mono);font-size:11px;font-weight:800;color:var(--text-muted);white-space:nowrap}
.dcard-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:10.5px;color:var(--text-muted)}
.dcard-tasks-summary{display:inline-flex;align-items:center;gap:5px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dcard-tasks-summary svg{width:13px;height:13px;flex-shrink:0;opacity:.7}
.dcard-deadline-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:999px;font-weight:800;white-space:nowrap;background:var(--panel-2,var(--panel));border:1px solid var(--border-soft);color:var(--text-muted)}
.dcard-deadline-pill svg{width:12px;height:12px;flex-shrink:0}
.dcard-deadline-pill.near{color:var(--amber,#b77900);background:var(--amber-dim,rgba(183,121,0,.14));border-color:rgba(224,168,58,.4)}
.dcard-deadline-pill.bad{color:var(--red,#c83b3b);background:var(--red-dim,rgba(200,59,59,.12));border-color:rgba(198,40,40,.3)}
/* margin-top:auto -- .deadline-sender-item flex-column, .deadline-sender-list
   grid nyeragamin TINGGI semua kartu dalam 1 baris (default align-items:
   stretch), tapi isi di atasnya (judul, task-track, deadline pill) panjangnya
   beda-beda tiap kartu -- tanpa auto, baris tombol jadi nempel abis konten
   TERPENDEK-nya sendiri, bukan nempel di dasar kartu, jadi ga sejajar sama
   kartu sebelah yang kontennya lebih panjang. */
.dcard-status-area{margin-top:auto;padding-top:2px}
.deadline-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
@media(max-width:650px){.deadline-sender-list{grid-template-columns:1fr}}

.deadline-task-track{padding:14px 18px 16px 34px;background:var(--p-surface-2,var(--panel-alt));border-radius:12px;display:none;flex-wrap:wrap;row-gap:10px;margin:8px 0;justify-content:flex-end}
.deadline-task-step{position:relative;display:flex;align-items:center;gap:6px;border:0;background:transparent;appearance:none;-webkit-appearance:none;box-shadow:none;border-radius:0;margin:0;cursor:pointer;padding:7px 20px 7px 18px;margin-right:-11px;font:inherit;font-size:10.5px;font-weight:700;white-space:nowrap;color:var(--p-muted,var(--text-muted));transition:color .15s ease,filter .15s ease;z-index:1}
.deadline-task-step::before{content:"";position:absolute;inset:0;z-index:-1;background:var(--p-surface-2,var(--panel-alt));clip-path:polygon(0 0,calc(100% - 13px) 0,100% 50%,calc(100% - 13px) 100%,0 100%,13px 50%);transition:background .15s ease}
.deadline-task-step:first-child::before{clip-path:polygon(0 0,calc(100% - 13px) 0,100% 50%,calc(100% - 13px) 100%,0 100%)}
.deadline-task-step:last-child::before{clip-path:polygon(0 0,100% 0,100% 100%,0 100%,13px 50%)}
.deadline-task-step:first-child:last-child::before{clip-path:none;border-radius:8px}
.deadline-task-step:hover{filter:brightness(1.08)}
.deadline-task-step:disabled{cursor:not-allowed;opacity:.6;filter:none}
.deadline-task-step:focus-visible{outline:2px solid var(--gold-bright,#e0a83a);outline-offset:2px}
.deadline-task-num{flex-shrink:0;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9.5px;font-weight:800;background:var(--p-surface,var(--panel));color:inherit}
.deadline-task-label{overflow:hidden;text-overflow:ellipsis;max-width:170px}
.deadline-task-step.active{color:#1a1206;z-index:2}
.deadline-task-step.active::before{background:var(--gold-bright,#e0a83a);box-shadow:0 4px 12px -4px rgba(224,168,58,.6)}
.deadline-task-step.active .deadline-task-num{background:#1a1206;color:var(--gold-bright,#e0a83a)}
.deadline-task-step.done{color:#8a7245}
.deadline-task-step.done::before{background:#efe6d2}
.deadline-task-step.done .deadline-task-num{background:var(--green,#16834b);color:#fff}
@media(max-width:640px){.deadline-task-step{margin-right:0;padding:7px 12px}.deadline-task-step::before{clip-path:none!important;border-radius:8px}}

/* ---- Wizard step (topbar horizontal checklist + form) di dalam #kirimLaporanModal ----
   Rombak total dari desain sebelumnya (sidebar vertikal kiri + garis
   penghubung antar dot) niru referensiTask2.png: checklist sekarang jadi
   BAR HORIZONTAL di ATAS form, item-nya cuma icon+teks tanpa garis
   penghubung sama sekali -- itu sekaligus ngilangin semua masalah "nabrak
   garis" yang sempat muncul di desain vertikal (highlight/ring/dot gede
   yang keserempet garis ::after), karena emang gak ada garis lagi buat
   diserempet. */
{{-- BUG yang barusan bikin transisi buka modal ini kaku: rule di bawah
     (selector lebih spesifik pakai #id) nimpa TOTAL properti "transition"
     bawaan .report-modal-card (yang aslinya transition:transform, buat efek
     scale+fade pas modal kebuka) -- bukan digabung, jadi transform-nya
     kehilangan transisi sama sekali, cuma width doang yang animasi. Sekarang
     transform DIIKUTSERTAKAN lagi di list transition yang sama. --}}
#kirimLaporanModal .report-modal-card{width:min(720px,100%);transition:width .2s ease,transform .2s ease}
#kirimLaporanModal.wizard-active .report-modal-card{width:min(940px,100%)}
.kirim-laporan-wizard-body{display:flex;flex-direction:column;gap:18px;min-width:0}
{{-- Topbar wizard -- dulu langsung nongol instan (hidden=false doang,
     kaku), sekarang fade+slide dikit kayak modal Detail, dipicu class
     .wizard-topbar-visible yang ditambahkan sefetelah frame berikutnya
     (lihat buildWizardTopbar()), pola yang sama persis kayak .is-visible
     di permintaan-laporan-realtime.blade.php. --}}
.kirim-laporan-wizard-topbar{display:flex;align-items:center;gap:6px;border-bottom:1px solid var(--border-soft);padding-bottom:16px;opacity:0;transform:translateY(-6px);transition:opacity .22s ease,transform .22s ease}
.kirim-laporan-wizard-topbar.wizard-topbar-visible{opacity:1;transform:translateY(0)}
@media(prefers-reduced-motion:reduce){.kirim-laporan-wizard-topbar{transition:none}}
/* referensiTaskDetail2.png (crop lebih jelas dari referensiTask2.png)
   nunjukin ini SATU bar nyambung -- 1 border rounded di keliling SELURUH
   list, dipisah garis vertikal tipis antar item di DALAMNYA, BUKAN
   kotak-kotak lepas yang ada jarak/gap kayak percobaan sebelumnya (itu
   yang dimaksud "putus" -- bukan sudut rounded-nya glitch, tapi task-nya
   literally kepisah-pisah gak nyambung). overflow:hidden di sini penting
   supaya background item pertama/terakhir ke-crop ngikutin border-radius
   punya list, gak usah diatur manual per item.
   flex:0 1 auto + min-width:0 sengaja -- biar LEBAR bar ngikutin
   BANYAKNYA task (dikit task = bar pendek, gak maksa selebar modal),
   tapi tetap bisa NYUSUT (min-width:0 buka jalan flex-shrink, default
   flex item nolak nyusut di bawah lebar kontennya sendiri) begitu total
   lebar task ngelewatin ruang yang ada, baru overflow-x:auto kepake buat
   scroll horizontal internal-nya. flex-wrap:nowrap + tombol panah
   sebelumnya/selanjutnya (lihat kirimLaporanWizardPrev/Next + JS
   refreshWizardTopbarNav) jaga-jaga kalau task-nya kebanyakan sampai gak
   muat -- SENGAJA bukan wrap ke baris ke-2 (bakal ngerusak tampilan "1
   bar nyambung rounded" kalau kepotong wrap). */
.wizard-step-list{list-style:none;margin:0;padding:0;display:flex;flex:0 1 auto;min-width:0;flex-wrap:nowrap;overflow-x:auto;overflow-y:hidden;border:1px solid var(--border-soft);border-radius:12px;scrollbar-width:none}
.wizard-step-list::-webkit-scrollbar{display:none}
.wizard-step{position:relative;display:flex;align-items:center;gap:9px;padding:10px 16px;flex-shrink:0;cursor:pointer;border-right:1px solid var(--border-soft)}
.wizard-step:last-child{border-right:none}
.wizard-step-dot{box-sizing:border-box;flex-shrink:0;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11.5px;font-weight:800;background:var(--panel);border:2px solid var(--border-soft);color:var(--text-muted);transition:background .15s ease,border-color .15s ease,color .15s ease}
.wizard-step-dot svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-linecap:round;stroke-linejoin:round}
/* 1 baris teks doang (nama task) -- topbar di referensi gak punya
   caption/sub-teks status kedua kayak desain sidebar vertikal sebelumnya. */
.wizard-step-label{font-size:12.5px;font-weight:600;color:var(--text);line-height:1.3;white-space:nowrap}
/* Checkmark SOLID hijau (bukan pastel/light-tint) + teks ikut warna hijau
   -- niru persis "About"/"Details"/"Application form" di referensi yang
   teksnya juga kehijauan, bukan hitam polos. */
.wizard-step-done .wizard-step-dot{background:var(--success,var(--green,#16834b));border-color:var(--success,var(--green,#16834b));color:#fff}
.wizard-step-done .wizard-step-label{color:var(--success,var(--green,#16834b))}
.wizard-step-pending{cursor:not-allowed}
.wizard-step-pending .wizard-step-label{color:var(--text-muted)}
/* Task yang lagi berjalan (BUKAN cuma yang "current"/lagi dibuka -- SEMUA
   step berstatus "active", biar warna oranye "Sedang dikerjakan" kebaca
   di topbar walau kamu lagi ngedit step LAIN yang sudah "Selesai"). */
.wizard-step-active .wizard-step-dot{background:var(--p-orange,#ea580c);border-color:var(--p-orange,#ea580c);color:#fff}
.wizard-step-active .wizard-step-label{color:var(--p-orange,#ea580c)}
/* Task yang belum selesai TAPI permintaannya udah kepalang lewat deadline
   (data-terlambat dari $permintaan->isTerlambat(), lihat item.blade.php +
   buildWizardTopbar()) -- merah, niru warna "Terlambat" yang sudah dipakai
   di badge/pill status permintaan lain (status-pill.bad, deadline-pill --
   var(--red)), BUKAN warna asing baru. Diletakkan SESUDAH .wizard-step-active
   di atas (specificity sama) biar menang kalau task-nya kebetulan active
   DAN terlambat sekaligus. */
.wizard-step-late .wizard-step-dot{background:var(--red,#c83b3b);border-color:var(--red,#c83b3b);color:#fff}
.wizard-step-late .wizard-step-label{color:var(--red,#c83b3b)}
/* Step yang lagi dibuka form-nya sekarang (BUKAN semua task, cuma yang
   ini) -- SENGAJA gak nimpa warna dot/label lagi (dulu dipaksa oranye
   apapun statusnya -- salah, soalnya ngedit step yang sudah "Selesai"
   jadi keliatan kayak "Sedang dikerjakan"). Warna tetap murni ikut status
   asli (hijau/oranye/merah dari rule di atas), current cuma nambah teks
   bold + garis penanda di BAWAH item ini (nempel di border bawah BAR,
   bottom:-1px, nutupin border abu-abu tapi CUMA selebar item ini -- bukan
   kotak/card terpisah, biar tetap 1 bar nyambung). Garis defaultnya oranye
   (kasus paling umum: current = task yang "Sedang dikerjakan"), tapi
   ke-override hijau/merah kalau current-nya kebetulan step "Selesai"/
   terlambat -- selector 2-class di bawah spesifisitasnya lebih tinggi
   jadi otomatis menang tanpa peduli urutan. */
.wizard-step-current .wizard-step-label{font-weight:800}
/* Garis penanda TUMBUH dari kosong ke penuh (bukan langsung nongol full)
   tiap kali pindah task -- transform:scaleX bukan width, biar animasinya
   pakai GPU (compositor), gak numpuk reflow kalau user klak-klik cepat.
   Mulai dari scaleX(0) (nempel di kiri, transform-origin:left), baru
   ditarik ke scaleX(1) begitu class .wizard-step-marker-in nempel (lihat
   buildWizardTopbar() -- ditambahin 2 frame kemudian lewat double rAF,
   BUKAN bareng pas elemennya baru dibikin, soalnya elemen yang baru
   ke-insert langsung nongol di state akhirnya kalau transition-nya gak
   sempat "kepaint" dulu di state awal). Dot current ikut fade-in bareng,
   kasih efek "landing" pas mendarat di step baru. Ini SENGAJA beda dari
   animasi fade topbar (yang cuma sekali pas modal pertama kebuka) --
   animasi marker+dot ini justru harus muncul ULANG tiap kali pindah
   task, itu intinya sebagai feedback perpindahan.
   Easing SENGAJA cubic-bezier(.16,1,.3,1) ("ease-out" halus, plain
   deceleration) -- percobaan awal pakai kurva "back"/overshoot
   (cubic-bezier(...,1.56,...), lewatin dikit baru mantul balik ke posisi
   akhir) itu yang bikin ujung animasinya kerasa "nyentak"/gak mulus,
   soalnya ada gerakan balik kecil pas mendekati akhir. Kurva ini
   monoton (gak pernah lewatin nilai akhirnya), jadi berhenti mulus.
   Dot SENGAJA fade doang (opacity), BUKAN transform:scale lagi kayak
   percobaan sebelumnya -- scale di elemen yang punya border (dot ini
   border:2px solid) bikin browser ngerender ulang ketebalan border tiap
   frame selama transisi (rasterisasi ulang, bukan cuma compositor kayak
   opacity/transform:translate), keliatan "gerigi"/gak semulus animasi
   garis penanda yang cuma scaleX polos tanpa border. Opacity SELALU mulus
   di compositor GPU apapun bentuk elemennya. */
.wizard-step-current::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:var(--p-orange,#ea580c);transform:scaleX(0);transform-origin:left;transition:transform 1.1s cubic-bezier(.16,1,.3,1)}
.wizard-step-current.wizard-step-marker-in::after{transform:scaleX(1)}
.wizard-step-current .wizard-step-dot{position:relative;opacity:.35;transition:background .15s ease,border-color .15s ease,color .15s ease,opacity 1.1s cubic-bezier(.16,1,.3,1)}
.wizard-step-current.wizard-step-marker-in .wizard-step-dot{opacity:1}
/* Cincin "ripple" yang meletup keluar dari dot lalu ilang -- efek yang
   lebih "berkesan" dibanding fade doang, TAPI tetap gak nyentuh transform
   di dot ASLI-nya (yang punya border, rawan gerigi kalau di-scale, lihat
   catatan di atas). Ini pseudo-element TERPISAH (::before punya dot-nya,
   bukan ::after yang udah dipakai buat garis penanda di .wizard-step),
   lingkaran solid tanpa border jadi scale-nya mulus di compositor. Paint
   order CSS: background dot dulu, baru ::before (cincin ini), baru
   konten asli (angka/centang) -- jadi angka/centang tetap kelihatan jelas
   di ATAS cincin yang sedang meletup, gak ketutup. */
.wizard-step-current .wizard-step-dot::before{content:"";position:absolute;inset:-5px;border-radius:50%;background:var(--p-orange,#ea580c);opacity:.45;transform:scale(.4);pointer-events:none}
.wizard-step-current.wizard-step-marker-in .wizard-step-dot::before{opacity:0;transform:scale(1.7);transition:opacity 1.1s cubic-bezier(.16,1,.3,1),transform 1.1s cubic-bezier(.16,1,.3,1)}
.wizard-step-current.wizard-step-done::after{background:var(--success,var(--green,#16834b))}
.wizard-step-current.wizard-step-late::after{background:var(--red,#c83b3b)}
.wizard-step-current.wizard-step-done .wizard-step-dot::before{background:var(--success,var(--green,#16834b))}
.wizard-step-current.wizard-step-late .wizard-step-dot::before{background:var(--red,#c83b3b)}
@media(prefers-reduced-motion:reduce){.wizard-step-current .wizard-step-dot::before{display:none}.wizard-step-current::after,.wizard-step-current .wizard-step-dot{transition:none}}
/* Navigasi panah sebelumnya/selanjutnya -- cuma tampil kalau topbar-nya
   beneran overflow (di-toggle JS lewat atribut hidden, lihat
   refreshWizardTopbarNav di permintaan-laporan-deadline.blade.php), jadi
   gak makan tempat pas task-nya dikit dan muat semua.
   [hidden] override EKSPLISIT sengaja -- .wizard-topbar-nav punya
   display:flex sendiri yang MENANG dibanding default browser buat atribut
   hidden ([hidden]{display:none} cuma aturan user-agent, kalah sama style
   bawaan halaman biarpun specificity-nya sama, lihat kasus sama persis di
   .form-field[hidden] pas rombak form Kirim Laporan) -- tanpa ini tombolnya
   tetap kelihatan padahal hidden=true. */
.wizard-topbar-nav{flex-shrink:0;width:28px;height:28px;border-radius:50%;border:1px solid var(--border-soft);background:var(--panel);display:flex;align-items:center;justify-content:center;color:var(--text-muted);cursor:pointer;transition:border-color .15s ease,color .15s ease}
.wizard-topbar-nav[hidden]{display:none}
.wizard-topbar-nav:hover:not(:disabled){border-color:var(--p-orange,#ea580c);color:var(--p-orange,#ea580c)}
.wizard-topbar-nav:disabled{opacity:.35;cursor:not-allowed}
.wizard-topbar-nav svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round}

{{-- Bungkus field-field form (bukan baris tombol aksi di bawahnya) dalam 1
     kartu bertepi -- niru kotak "Gross Earnings NYC Division" di referensi
     (referensiTask.png), yang misahin area isi form dari chrome modal di
     sekitarnya. grid-column:1/-1 supaya kartu ini ngambil penuh lebar 2 kolom
     .form-grid di luarnya, lalu di DALAM kartu dibikin grid 2 kolom baru lagi
     buat field-fieldnya sendiri (gap dilebarin dikit biar kesan "lega" kayak
     referensi, bukan cuma reuse gap 16px punya .form-grid global). --}}
#kirimLaporanModal .kirim-laporan-form-card{grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:18px 20px;padding:20px;border:1px solid var(--border-soft);border-radius:14px;background:var(--panel-alt)}
@media(max-width:640px){#kirimLaporanModal .kirim-laporan-form-card{grid-template-columns:1fr;padding:16px}}
/* .form-field punya display:flex sendiri (lihat <style> global di
   laporan-role.blade.php) yang menang dibanding default browser buat
   atribut hidden ([hidden]{display:none} cuma aturan user-agent, KALAH
   sama style bawaan halaman biarpun specificity-nya sama) -- makanya
   field yang disembunyikan (Tujuan Laporan/Progres/Prioritas/Kategori/
   Perihal, lihat kirimLaporanModal di laporan-role.blade.php) tetap
   kelihatan tanpa override eksplisit ini. */
#kirimLaporanModal .form-field[hidden]{display:none!important}

/* Header modal (icon badge + judul/subjudul) -- pola "icon + heading" biar
   modal ini kerasa lebih "berisi", bukan judul teks polos doang. */
.kirim-laporan-modal-head{display:flex;align-items:flex-start;gap:12px;margin-bottom:18px}
.kirim-laporan-modal-icon{flex-shrink:0;width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:var(--gold-dim);color:var(--gold-bright)}
.kirim-laporan-modal-icon svg{width:19px;height:19px}

/* Icon kecil di tiap label field (Isi Laporan/Kendala/Lampiran) -- biar
   field-nya gampang dibedain sekilas pandang, bukan teks label polos. */
#kirimLaporanModal .form-field label{display:inline-flex;align-items:center;gap:6px}
.form-field-icon{width:13px;height:13px;flex-shrink:0;color:var(--text-dim,var(--text-muted))}

/* Isi Laporan & Kendala/Alasan SEKARANG sebelah-sebelahan (dihapus class
   .full-nya di laporan-role.blade.php) -- card ini kebetulan udah punya
   slot 2 kolom (dulu kepakai field Tujuan/Progres/Prioritas/Kategori/
   Perihal yang sekarang semua disembunyikan), mubazir kalau 2 field ini
   malah ditumpuk ke bawah. Ini yang motong tinggi modal paling
   signifikan (modal jadi lebar+pendek, bukan sempit+panjang). */

/* Dropzone Lampiran -- upgrade dari tombol "Pilih File" polos bawaan
   siberadEnhanceFileInputs() (dash-styles.blade.php, dipakai situs-wide)
   jadi kotak dropzone SENDIRI khusus field ini (input dikasih
   data-file-picker-ready="1" di HTML biar enhancement global itu skip
   elemen ini, lihat kondisi skip-nya di fungsi tsb). Input asli jadi
   overlay TRANSPARAN PENUH SATU KOTAK (bukan disembunyikan clip:rect
   kayak enhancement global) -- sengaja, supaya drag&drop native browser
   ke file input nempel di SELURUH kotak, bukan cuma di titik kecil
   tombol kecil. Visual prompt/chip di baliknya pointer-events:none
   (biar klik/drop tetap tembus ke input asli di atasnya), KECUALI tombol
   hapus yang di-pointer-events:auto lagi. */
/* Niru referensiLampiran2.png: kotak dropzone SATU baris penuh (bukan
   dibagi 2 kolom lagi), border putus-putus TIPIS+SAMAR, icon cloud-upload
   outline + kalimat "Tarik & lepas file di sini, atau [Pilih File]" --
   "Pilih File" cuma teks warna accent inline (bukan tombol pil solid
   lagi), SEMUANYA pointer-events:none karena cuma dekorasi -- input asli
   yang transparan di atasnya (.lampiran-dropzone-input) yang beneran
   nangkep klik/drop di SELURUH kotak, termasuk pas "ngeklik" teks Pilih
   File itu. Daftar file di bawahnya (.lampiran-file-list) full width
   juga, ditumpuk vertikal. */
.lampiran-dropzone{position:relative;border:1.5px dashed var(--border-soft,var(--border));border-radius:12px;padding:22px 16px;background:var(--panel);text-align:center;transition:border-color .15s ease,background-color .15s ease}
.lampiran-dropzone:hover,.lampiran-dropzone.is-dragover{border-color:var(--gold-bright);background:var(--gold-dim)}
.lampiran-dropzone-input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;margin:0}
.lampiran-dropzone-prompt{display:flex;flex-direction:column;align-items:center;gap:8px;pointer-events:none}
.lampiran-dropzone-icon{color:var(--gold-bright)}
.lampiran-dropzone-icon svg{width:22px;height:22px}
.lampiran-dropzone-text{font-size:12.5px;color:var(--text-muted)}
/* Kotak daftar file di BAWAH dropzone (bukan disamping lagi) -- niru
   referensiLampiran2.png: tiap baris badge warna solid berisi teks
   ekstensi file + nama + ukuran + tombol hapus (icon tempat sampah).
   Dirender manual lewat JS (lihat renderLampiranFileList di
   permintaan-laporan-deadline.blade.php), baik lampiran LAMA (sudah
   tersimpan di server, muncul pas mode edit) maupun yang BARU dipilih/
   di-drop (belum dikirim). Pesan "Belum ada file yang diupload"
   nge-declare display:block eksplisit, jadi butuh override [hidden]
   EKSPLISIT juga (pola sama kayak .form-field[hidden] &
   .wizard-topbar-nav[hidden] sebelumnya -- style penulis SELALU menang
   dibanding [hidden]{display:none} bawaan browser, apapun juga value
   display-nya). */
.lampiran-file-list{margin-top:10px;display:flex;flex-direction:column;gap:6px;max-height:260px;overflow-y:auto}
.lampiran-file-list-empty{display:block;padding:14px 10px;text-align:center;font-size:12px;color:var(--text-muted);border:1px dashed var(--border-soft,var(--border));border-radius:12px}
.lampiran-file-list-empty[hidden]{display:none}
/* [hidden] override EKSPLISIT sengaja -- .btn punya display:inline-flex
   sendiri (dash-styles.blade.php) yang MENANG dibanding [hidden]{display:
   none} bawaan browser (pola sama kayak beberapa elemen lain di file ini).
   Dipakai buat nyembunyiin tombol submit pas mode "Lihat Progres"
   (setKirimLaporanReadonly di permintaan-laporan-deadline.blade.php). */
#kirimLaporanSubmitBtn[hidden]{display:none}
.lampiran-file-row{display:flex;align-items:center;gap:11px;padding:9px 10px;border-radius:10px;background:var(--panel-alt)}
.lampiran-file-row-icon{flex-shrink:0;width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#64748b;color:#fff;font-size:8.5px;font-weight:800;letter-spacing:.02em}
/* Warna badge ikut format file -- konvensi umum: PDF merah, Word biru,
   Excel hijau, PowerPoint oranye, gambar ungu, arsip amber, teks abu-tua. */
.lampiran-file-row-icon.lfx-pdf{background:#d64545}
.lampiran-file-row-icon.lfx-doc{background:#2b579a}
.lampiran-file-row-icon.lfx-xls{background:#217346}
.lampiran-file-row-icon.lfx-ppt{background:#c43e1c}
.lampiran-file-row-icon.lfx-img{background:#7c3aed}
.lampiran-file-row-icon.lfx-zip{background:#d97706}
.lampiran-file-row-icon.lfx-txt{background:#475569}
.lampiran-file-row-icon.lfx-other{background:#64748b}
.lampiran-file-row-info{flex:1;min-width:0;display:flex;flex-direction:column;gap:1px}
.lampiran-file-row-name{font-size:12.5px;color:var(--text);font-weight:700;word-break:break-all;text-decoration:none}
a.lampiran-file-row-name:hover{color:var(--gold-bright);text-decoration:underline}
.lampiran-file-row-size{font-size:10.5px;color:var(--text-muted)}
.lampiran-file-row-remove{flex-shrink:0;width:26px;height:26px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:transparent;color:var(--text-muted);border:none;cursor:pointer;transition:background-color .15s ease,color .15s ease}
.lampiran-file-row-remove:hover{background:var(--gold-dim);color:var(--gold-bright)}
.lampiran-file-row-remove svg{width:14px;height:14px}
</style>
<script>
/* Teks + kelas warna badge lampiran berdasarkan ekstensi file (konvensi
   umum). Dipakai openPimpinanProgres() di laporan-pimpinan.blade.php &
   buildLampiranRow() di permintaan-laporan-deadline.blade.php. Partial ini
   ke-load di head kedua halaman jadi helper-nya siap sebelum dipakai. */
window.siberadLampiranBadge=function(nameOrUrl){
  var s=String(nameOrUrl||''),q=s.search(/[?#]/);
  if(q>-1)s=s.slice(0,q);
  var dot=s.lastIndexOf('.'),ext=dot>-1?s.slice(dot+1).toLowerCase():'';
  var m={
    pdf:['PDF','lfx-pdf'],
    doc:['DOC','lfx-doc'],docx:['DOCX','lfx-doc'],rtf:['RTF','lfx-doc'],odt:['ODT','lfx-doc'],
    xls:['XLS','lfx-xls'],xlsx:['XLSX','lfx-xls'],csv:['CSV','lfx-xls'],ods:['ODS','lfx-xls'],
    ppt:['PPT','lfx-ppt'],pptx:['PPTX','lfx-ppt'],odp:['ODP','lfx-ppt'],
    jpg:['JPG','lfx-img'],jpeg:['JPG','lfx-img'],png:['PNG','lfx-img'],gif:['GIF','lfx-img'],webp:['WEBP','lfx-img'],bmp:['BMP','lfx-img'],svg:['SVG','lfx-img'],heic:['HEIC','lfx-img'],
    zip:['ZIP','lfx-zip'],rar:['RAR','lfx-zip'],'7z':['7Z','lfx-zip'],tar:['TAR','lfx-zip'],gz:['GZ','lfx-zip'],
    txt:['TXT','lfx-txt'],md:['MD','lfx-txt'],log:['LOG','lfx-txt']
  };
  if(m[ext])return {text:m[ext][0],cls:m[ext][1]};
  return {text:ext?ext.toUpperCase().slice(0,4):'FILE',cls:'lfx-other'};
};
</script>
