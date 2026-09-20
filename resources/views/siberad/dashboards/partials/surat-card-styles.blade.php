<style>
/* Kartu Surat gaya "file/dokumen" (niru referensi surat1.png) -- dipakai
   ganti tabel di panel Surat Keluar. Sengaja partial TERPISAH & di-include
   di kedua dashboard (Satuan & Pimpinan) biar gak dobel-tulis kayak yang
   sempat kejadian pas nge-redesain modal Buat Surat (banyak properti
   ketinggalan di salah satu file). */
.surat-file-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px}
/* Animasi kartu hilang (realtime, lihat surat-terkirim-realtime.blade.php)
   -- niru persis .siberad-card-leaving punya Permintaan Laporan
   (laporan-role-realtime-sync.blade.php), tapi didefinisikan sendiri di
   sini (nama keyframe beda) karena file itu cuma di-include shell Satuan,
   sedangkan grid Surat dipakai bareng Satuan & Pimpinan. */
.surat-file-card.siberad-card-leaving{animation:siberadSuratCardOut .3s ease forwards;pointer-events:none}
@keyframes siberadSuratCardOut{to{opacity:0;transform:translateY(-6px) scale(.97)}}
@media(prefers-reduced-motion:reduce){.surat-file-card.siberad-card-leaving{animation:none!important}}
.surat-file-card{position:relative;display:flex;flex-direction:column;background:var(--panel);border:1px solid var(--border-soft);border-radius:14px;padding:22px;box-sizing:border-box;box-shadow:0 10px 30px rgba(0,0,0,.15)}
.surat-file-card-icon{flex-shrink:0;width:68px;height:68px;border-radius:16px;background:var(--gold-dim);color:var(--gold-bright);display:flex;align-items:center;justify-content:center;margin-bottom:16px}
.surat-file-card-icon svg{width:32px;height:32px}
.surat-file-card[data-prioritas="Rendah"] .surat-file-card-icon{background:color-mix(in srgb,#8b5cf6 16%,var(--panel));color:#8b5cf6}
.surat-file-card[data-prioritas="Sedang"] .surat-file-card-icon{background:color-mix(in srgb,#a855f7 16%,var(--panel));color:#a855f7}
.surat-file-card[data-prioritas="Tinggi"] .surat-file-card-icon{background:color-mix(in srgb,#6d28d9 16%,var(--panel));color:#6d28d9}
/* Fondasi alur surat Danpus -- prioritas Biasa/Kilat/Rahasia (lihat
   LaporanSurat::PRIORITAS_DANPUS), TERPISAH dari Rendah/Sedang/Tinggi di
   atas yang tetap dipakai satuan lain. */
.surat-file-card[data-prioritas="Biasa"] .surat-file-card-icon{background:color-mix(in srgb,#3b82f6 16%,var(--panel));color:#3b82f6}
.surat-file-card[data-prioritas="Kilat"] .surat-file-card-icon{background:color-mix(in srgb,#f59e0b 16%,var(--panel));color:#f59e0b}
.surat-file-card[data-prioritas="Rahasia"] .surat-file-card-icon{background:color-mix(in srgb,#dc2626 16%,var(--panel));color:#dc2626}
.surat-file-card-badge{align-self:flex-start;margin-bottom:14px}
.status-badge.status-menunggu.surat-file-card-badge,
#suratDetailModal .status-badge.status-menunggu{color:#2476ad;background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.25)}
.surat-file-card-title{font-family:var(--display);font-size:17px;font-weight:700;line-height:1.35;color:var(--text);margin:0 0 14px}
.surat-file-card-dari-label{font-size:12px;color:var(--text-muted)}
.surat-file-card-dari-value{display:flex;align-items:center;flex-wrap:wrap;gap:8px;font-size:13.5px;font-weight:700;color:var(--text);margin-top:5px}
/* Alur Tujuan (workflow) -- dipakai saat surat sudah diteruskan berkali-kali
   (mis. Danpus > Wadan > Satlak) supaya tiap hop tampil sebagai chip
   terpisah dihubungkan panah, bukan teks nyambung jadi satu baris. Dipakai
   baik di kartu grid (versi -sm, kompak) maupun di modal detail (versi
   biasa, lebih lega). */
.surat-tujuan-flow{display:flex;flex-wrap:wrap;align-items:center;gap:6px;row-gap:8px}
.surat-tujuan-flow-step{display:inline-flex;align-items:center;padding:5px 11px;border-radius:999px;background:var(--panel-soft,rgba(255,255,255,.05));border:1px solid var(--border-soft);font-size:12.5px;font-weight:700;color:var(--text-muted);line-height:1.3}
.surat-tujuan-flow-step.is-final{color:var(--gold-bright);background:var(--gold-dim);border-color:var(--gold-bright)}
.surat-tujuan-flow-arrow{flex-shrink:0;display:inline-flex;align-items:center;color:var(--text-muted);opacity:.6}
.surat-tujuan-flow-arrow svg{width:13px;height:13px}
.surat-tujuan-flow-sm .surat-tujuan-flow-step{padding:3px 9px;font-size:12px}
.surat-tujuan-flow-sm .surat-tujuan-flow-arrow svg{width:11px;height:11px}
.surat-file-card-divider{border-top:1px solid var(--border-soft);margin:16px 0}
.surat-file-card-meta{display:flex;align-items:center;gap:12px}
.surat-file-card-meta-icon{flex-shrink:0;width:36px;height:36px;border-radius:10px;background:var(--gold-dim);color:var(--gold-bright);display:flex;align-items:center;justify-content:center}
.surat-file-card-meta-icon svg{width:17px;height:17px}
.surat-file-card-meta-label{font-size:12px;color:var(--text-muted)}
.surat-file-card-meta-value{font-size:13.5px;font-weight:700;color:var(--text);margin-top:5px}
.surat-file-card-btn{width:100%;box-sizing:border-box;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px;border-radius:10px;border:1px solid var(--border);background:var(--gold-dim);color:var(--gold-bright);font-family:var(--body);font-weight:700;font-size:13.5px;cursor:pointer;transition:background-color .15s ease,transform .15s ease}
.surat-file-card-btn:hover{background:color-mix(in srgb,var(--gold-bright) 22%,var(--gold-dim));transform:translateY(-1px)}
.surat-file-grid .empty-state{grid-column:1/-1}

/* Modal Detail Surat (niru referensi modaldetailsurat1.png) */
#suratDetailModal .report-modal-card{width:min(900px,100%)}
#suratDetailModal .report-modal-head{margin-bottom:20px}
#suratDetailModal .report-modal-head h3{font-size:22px}
/* Sudut kanan-atas/bawah kepotong scrollbar bawaan browser (Chromium tidak
   ikut membulatkan native scrollbar meski elemennya sendiri sudah
   border-radius) -- pindahkan overflow-y:auto ke wrapper .surat-detail-scroll
   DI DALAM .report-modal-card, lalu kartunya sendiri jadi overflow:hidden
   supaya scrollbar itu ikut ke-clip mengikuti border-radius kartu (pola
   standar "nested overflow clip", bukan cuma andalkan mask-image trick). */
#suratDetailModal .report-modal-card{overflow:hidden;padding:0;display:flex;flex-direction:column}
#suratDetailModal .report-modal-card>.surat-detail-scroll{overflow-y:auto;overflow-x:hidden;padding:22px;flex:1;min-height:0}
.surat-detail-body{display:grid;grid-template-columns:1.2fr 1fr;gap:0 28px}
.surat-detail-col{display:flex;flex-direction:column}
.surat-detail-col-left{border-right:1px solid var(--border-soft);padding-right:28px}
.surat-detail-item{padding:11px 13px;border:1px solid var(--border-soft);border-radius:10px;background:var(--panel-alt);margin-bottom:10px}
.surat-detail-item:last-child{margin-bottom:0}
.surat-detail-item-label{font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
.surat-detail-item-value{font-size:13.5px;font-weight:600;color:var(--text);line-height:1.55;white-space:pre-wrap}
.surat-detail-item-row{display:flex;gap:10px;margin-bottom:10px}
.surat-detail-item-row .surat-detail-item{flex:1;min-width:0;margin-bottom:0}
.surat-detail-item-value .priority-tag.prio-rendah{color:#8b5cf6;background:color-mix(in srgb,#8b5cf6 12%,transparent);border-color:color-mix(in srgb,#8b5cf6 35%,transparent)}
.surat-detail-item-value .priority-tag.prio-sedang{color:#a855f7;background:color-mix(in srgb,#a855f7 12%,transparent);border-color:color-mix(in srgb,#a855f7 35%,transparent)}
.surat-detail-item-value .priority-tag.prio-tinggi{color:#6d28d9;background:color-mix(in srgb,#6d28d9 12%,transparent);border-color:color-mix(in srgb,#6d28d9 35%,transparent)}
/* Fondasi alur surat Danpus -- prioritas Biasa/Kilat/Rahasia, TERPISAH
   dari prio-rendah/sedang/tinggi di atas yang tetap dipakai satuan lain. */
.surat-detail-item-value .priority-tag.prio-biasa{color:#3b82f6;background:color-mix(in srgb,#3b82f6 12%,transparent);border-color:color-mix(in srgb,#3b82f6 35%,transparent)}
.surat-detail-item-value .priority-tag.prio-kilat{color:#f59e0b;background:color-mix(in srgb,#f59e0b 12%,transparent);border-color:color-mix(in srgb,#f59e0b 35%,transparent)}
.surat-detail-item-value .priority-tag.prio-rahasia{color:#dc2626;background:color-mix(in srgb,#dc2626 12%,transparent);border-color:color-mix(in srgb,#dc2626 35%,transparent)}
/* Ringkasan surat Rahasia yang disembunyikan (lihat LaporanSurat::ringkasanUntuk()) */
.surat-detail-item-value.surat-ringkasan-rahasia,
#suratDetailRingkasan.surat-ringkasan-rahasia{font-style:italic;color:var(--text-muted)}
.surat-detail-panel{border:1px solid var(--border-soft);border-radius:12px;padding:16px;background:var(--panel-alt);margin-bottom:14px}
.surat-detail-panel:last-child{margin-bottom:0}
.surat-detail-panel-title{font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin:0 0 14px}
.surat-detail-timeline-item{position:relative;padding-left:26px;padding-bottom:20px}
.surat-detail-timeline-item:last-child{padding-bottom:0}
.surat-detail-timeline-dot{position:absolute;left:0;top:0;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--success-bright,#3dba7e);color:#fff;z-index:1}
.surat-detail-timeline-dot svg{width:10px;height:10px}
.surat-detail-timeline-item.is-pending .surat-detail-timeline-dot{background:var(--panel);border:2px solid var(--border);color:transparent}
.surat-detail-timeline-item:not(:last-child)::before{content:"";position:absolute;left:8px;top:18px;bottom:-9px;width:2px;background:var(--border-soft)}
.surat-detail-timeline-item:not(:last-child)::after{content:"";position:absolute;left:8px;top:18px;bottom:-9px;width:2px;background:var(--success-bright,#3dba7e);clip-path:inset(0 0 100% 0);transition:clip-path .5s ease}
.surat-detail-timeline-item.line-complete:not(:last-child)::after{clip-path:inset(0 0 0% 0)}
@keyframes suratTimelineDotPop{0%{transform:scale(.5);opacity:0}60%{transform:scale(1.2)}100%{transform:scale(1);opacity:1}}
.surat-detail-timeline-dot.just-confirmed{animation:suratTimelineDotPop .45s cubic-bezier(.34,1.56,.64,1)}
.surat-detail-timeline-title{font-size:13px;font-weight:700;color:var(--text);line-height:18px}
.surat-detail-timeline-sub{font-size:11.5px;color:var(--text-muted);margin-top:3px}
/* Riwayat Alur bisa berisi banyak step (Danpus > Wadan > Urdal > Satuan, dst),
   jadi dibatasi tingginya & dikasih scrollbar sendiri supaya tidak memaksa
   modal jadi sangat tinggi / step terpotong di layar kecil. */
.surat-detail-timeline{max-height:300px;overflow-y:auto;padding-right:8px;margin-right:-8px}

/* ===== Semua modal surat (Detail, Buat Surat, Disposisi Wadan, Teruskan) =====
   1) Sudut kartu modal tumpul semua.
   2) Scrollbar SERAGAM di kartu modal & area scroll di dalamnya (timeline, grid tindakan):
      tipis 6px, thumb bulat, tanpa tombol panah (panah bawaan browser bikin sudut
      kanan-atas/bawah modal terlihat tajam). Firefox: gaya "thin" standar. */
:is(#suratDetailModal,#kirimSuratModal,#wadanDisposisiModal,#suratTeruskanModal) .report-modal-card{border-radius:22px}
:is(#kirimSuratModal,#wadanDisposisiModal,#suratTeruskanModal) .report-modal-card,
#suratDetailModal .surat-detail-scroll,
.surat-detail-timeline,#wadanDisposisiTindakanGrid,#suratTeruskanTindakanGrid{scrollbar-width:thin;scrollbar-color:var(--border) transparent}
@supports selector(::-webkit-scrollbar){
  /* Chrome/Edge/Safari baru: pakai pseudo-element webkit (standar scrollbar-* akan menampilkan panah) */
  :is(#kirimSuratModal,#wadanDisposisiModal,#suratTeruskanModal) .report-modal-card,
  #suratDetailModal .surat-detail-scroll,
  .surat-detail-timeline,#wadanDisposisiTindakanGrid,#suratTeruskanTindakanGrid{scrollbar-width:auto;scrollbar-color:auto}
  :is(#kirimSuratModal,#wadanDisposisiModal,#suratTeruskanModal) .report-modal-card::-webkit-scrollbar,
  #suratDetailModal .surat-detail-scroll::-webkit-scrollbar,
  .surat-detail-timeline::-webkit-scrollbar,#wadanDisposisiTindakanGrid::-webkit-scrollbar,#suratTeruskanTindakanGrid::-webkit-scrollbar{width:6px;height:6px}
  :is(#kirimSuratModal,#wadanDisposisiModal,#suratTeruskanModal) .report-modal-card::-webkit-scrollbar-track,
  #suratDetailModal .surat-detail-scroll::-webkit-scrollbar-track,
  .surat-detail-timeline::-webkit-scrollbar-track,#wadanDisposisiTindakanGrid::-webkit-scrollbar-track,#suratTeruskanTindakanGrid::-webkit-scrollbar-track{background:transparent}
  :is(#kirimSuratModal,#wadanDisposisiModal,#suratTeruskanModal) .report-modal-card::-webkit-scrollbar-thumb,
  #suratDetailModal .surat-detail-scroll::-webkit-scrollbar-thumb,
  .surat-detail-timeline::-webkit-scrollbar-thumb,#wadanDisposisiTindakanGrid::-webkit-scrollbar-thumb,#suratTeruskanTindakanGrid::-webkit-scrollbar-thumb{background:var(--border);border-radius:999px}
  :is(#kirimSuratModal,#wadanDisposisiModal,#suratTeruskanModal) .report-modal-card::-webkit-scrollbar-thumb:hover,
  #suratDetailModal .surat-detail-scroll::-webkit-scrollbar-thumb:hover,
  .surat-detail-timeline::-webkit-scrollbar-thumb:hover,#wadanDisposisiTindakanGrid::-webkit-scrollbar-thumb:hover,#suratTeruskanTindakanGrid::-webkit-scrollbar-thumb:hover{background:var(--gold-bright,var(--gold))}
  :is(#kirimSuratModal,#wadanDisposisiModal,#suratTeruskanModal) .report-modal-card::-webkit-scrollbar-button,
  .surat-detail-timeline::-webkit-scrollbar-button,#wadanDisposisiTindakanGrid::-webkit-scrollbar-button,#suratTeruskanTindakanGrid::-webkit-scrollbar-button{display:none;width:0;height:0}
  /* Scrollbar UTAMA modal Detail/Arsip Surat (.surat-detail-scroll) BEDA dari
     yang lain di atas: dikasih tanda panah atas/bawah niru gaya scrollbar
     sidebar (lihat .side-subnav>div::-webkit-scrollbar-button di
     laporan-danpus.blade.php / laporan-wadan.blade.php) -- BUKAN
     .surat-detail-timeline (Riwayat Alur) yang tetap tanpa panah seperti semula. */
  #suratDetailModal .surat-detail-scroll::-webkit-scrollbar-button{display:block;width:6px;height:14px;background-color:transparent;background-repeat:no-repeat;background-position:center;background-size:8px 8px;transition:background-color .15s ease}
  #suratDetailModal .surat-detail-scroll::-webkit-scrollbar-button:vertical:start:increment,
  #suratDetailModal .surat-detail-scroll::-webkit-scrollbar-button:vertical:end:decrement{display:none}
  #suratDetailModal .surat-detail-scroll::-webkit-scrollbar-button:vertical:start:decrement{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 15 12 9 18 15'/%3E%3C/svg%3E")}
  #suratDetailModal .surat-detail-scroll::-webkit-scrollbar-button:vertical:end:increment{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E")}
  #suratDetailModal .surat-detail-scroll::-webkit-scrollbar-button:vertical:start:decrement:hover{background-color:var(--hover-tint);background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23FF9800' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 15 12 9 18 15'/%3E%3C/svg%3E")}
  #suratDetailModal .surat-detail-scroll::-webkit-scrollbar-button:vertical:end:increment:hover{background-color:var(--hover-tint);background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23FF9800' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E")}
  /* Jarak ujung track dari sudut bulat kartu modal supaya thumb tidak menabrak lengkungan.
     #suratDetailModal beda: scroll-nya di wrapper .surat-detail-scroll (bukan .report-modal-card
     langsung) yang sudut-sudutnya sendiri memang kotak -- tombol panah di atas sudah otomatis
     menyisakan jarak 14px, jadi track-nya sendiri tidak perlu margin tambahan lagi. */
  :is(#kirimSuratModal,#wadanDisposisiModal,#suratTeruskanModal) .report-modal-card::-webkit-scrollbar-track{margin:22px 0}
  #suratDetailModal .surat-detail-scroll::-webkit-scrollbar-track{margin:0}
}

/* ── Step-by-step "kartu bernomor" (redesain alur riwayat) ──
   Tiap langkah alur (Dibuat -> Konfirmasi Wadan -> Diteruskan -> ...)
   ditampilkan sebagai baris: lingkaran nomor besar di kolom kiri
   (terhubung garis vertikal ke nomor berikutnya) + kartu berisi ikon
   jenis aksi, judul, "Oleh ..." dan jam/tanggal -- niru gaya referensi
   "RIWAYAT ALUR" (nomor besar terpisah dari kartu, bukan digabung jadi
   badge kecil di pojok ikon). */
.surat-detail-timeline-item.timeline-card{display:flex;align-items:stretch;gap:10px;padding-left:0;padding-bottom:10px}
.surat-detail-timeline-item.timeline-card:not(:last-child){padding-bottom:0}
/* Garis lama (dot-style ::before/::after di atas) dirancang buat dot 18px
   (left:8px = pas tengah dot lama), jadi kalau dipakai bareng kartu step
   bernomor 30px hasilnya offside/nyerong masuk ke lingkaran. Matikan
   khusus untuk mode kartu step ini -- konektornya dipasang ulang di
   .timeline-step-line yang otomatis presisi di tengah kolom (lihat bawah). */
.surat-detail-timeline-item.timeline-card::before,
.surat-detail-timeline-item.timeline-card::after{content:none}
.timeline-step-col{flex-shrink:0;width:30px;display:flex;flex-direction:column;align-items:center}
.timeline-step-num{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--success-bright,#3dba7e);color:#fff;font-weight:800;font-size:13px;line-height:1;flex-shrink:0;position:relative;z-index:1}
/* Konektor step presisi: 2px, di tengah kolom 30px (align-items:center pada
   .timeline-step-col otomatis menengahkannya persis di bawah lingkaran,
   nempel rapat tanpa offset ke kiri/kanan). */
.timeline-step-line{width:2px;flex:1;min-height:16px;background:var(--border-soft);margin:0;border-radius:1px;transition:background .5s ease}
.timeline-step-line.line-complete{background:var(--success-bright,#3dba7e)}
.timeline-card-inner{flex:1;min-width:0;display:flex;gap:8px;align-items:flex-start;border:1.5px solid var(--border-soft);border-radius:10px;padding:8px 10px;background:var(--panel-alt);margin-bottom:10px}
.timeline-card-badge{flex-shrink:0;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--success-bright,#3dba7e);color:#fff;font-weight:800;font-size:12px}
.timeline-card-badge svg{width:13px;height:13px}
.surat-detail-timeline-item.timeline-card.is-pending .timeline-step-num{background:var(--panel);border:2px solid var(--border);color:var(--text-muted)}
.surat-detail-timeline-item.timeline-card.is-pending .timeline-card-badge{background:var(--panel);border:2px solid var(--border);color:var(--text-muted)}
.surat-detail-timeline-item.timeline-card.is-pending .timeline-card-inner{border-style:dashed}
.timeline-card-body{min-width:0;flex:1}
.timeline-card-body .surat-detail-timeline-title{font-size:12px;line-height:16px}
.timeline-card-body .surat-detail-timeline-sub{font-size:10.5px;margin-top:2px}
.timeline-card-meta{display:flex;align-items:center;gap:5px;font-size:10.5px;color:var(--text-muted);margin-top:2px}
.timeline-card-meta svg{width:10px;height:10px;flex-shrink:0}
/* Step Wadan (step 2): blok "Diteruskan ke / Disposisi" yang rapi + catatan View Only Urdal */
.timeline-forward-block{margin-top:8px;padding-top:8px;border-top:1px dashed rgba(61,186,126,.35);display:flex;flex-direction:column;gap:4px}
.timeline-forward-row{display:flex;align-items:baseline;gap:8px;min-width:0}
.timeline-forward-label{flex:0 0 96px;white-space:nowrap;font-size:9.5px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted)}
.timeline-forward-value{flex:1;min-width:0;font-size:11.5px;font-weight:700;line-height:15px;color:var(--text);overflow-wrap:anywhere}
.timeline-viewonly-note{display:flex;align-items:flex-start;gap:8px;margin-top:8px;padding:7px 9px;border-radius:8px;border:1px dashed rgba(99,102,241,.4);background:rgba(99,102,241,.07)}
.timeline-viewonly-icon{flex-shrink:0;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(99,102,241,.16);color:#6366f1}
.timeline-viewonly-icon svg{width:12px;height:12px}
.timeline-viewonly-text{min-width:0;flex:1}
.timeline-viewonly-title{font-size:11px;font-weight:700;line-height:15px;color:var(--text)}
.timeline-viewonly-tag{display:inline-flex;align-items:center;margin-left:4px;padding:0 7px;border-radius:999px;font-size:9.5px;font-weight:800;letter-spacing:.02em;vertical-align:middle;background:rgba(99,102,241,.14);color:#6366f1;border:1px solid rgba(99,102,241,.32)}
.timeline-viewonly-desc{margin-top:2px;font-size:10.5px;line-height:14px;color:var(--text-muted)}
/* Tint kartu per jenis aksi -- konsisten sama warna pill status yang sudah ada,
   dipakai juga buat warna lingkaran nomor di kolom kiri */
.timeline-card-inner.aksi-buat,.timeline-card-inner.aksi-keluar{background:rgba(52,152,219,.06);border-color:rgba(52,152,219,.25)}
.timeline-card-inner.aksi-buat .timeline-card-badge,.timeline-card-inner.aksi-keluar .timeline-card-badge{background:#2476ad}
.surat-detail-timeline-item.timeline-card:has(.timeline-card-inner.aksi-buat) .timeline-step-num,
.surat-detail-timeline-item.timeline-card:has(.timeline-card-inner.aksi-keluar) .timeline-step-num{background:#2476ad}
.timeline-card-inner.aksi-konfirmasi{background:rgba(61,186,126,.07);border-color:rgba(61,186,126,.28)}
.timeline-card-inner.aksi-konfirmasi .timeline-card-badge{background:#2e9e68}
.surat-detail-timeline-item.timeline-card:has(.timeline-card-inner.aksi-konfirmasi) .timeline-step-num{background:#2e9e68}
.timeline-card-inner.aksi-teruskan{background:rgba(99,102,241,.06);border-color:rgba(99,102,241,.25)}
.timeline-card-inner.aksi-teruskan .timeline-card-badge{background:#6366f1}
.surat-detail-timeline-item.timeline-card:has(.timeline-card-inner.aksi-teruskan) .timeline-step-num{background:#6366f1}
.timeline-card-inner.aksi-selesai{background:rgba(61,186,126,.1);border-color:rgba(61,186,126,.35)}
.timeline-card-inner.aksi-selesai .timeline-card-badge{background:#1f7a4f}
.surat-detail-timeline-item.timeline-card:has(.timeline-card-inner.aksi-selesai) .timeline-step-num{background:#1f7a4f}
.timeline-card-badge.just-confirmed,.timeline-step-num.just-confirmed{animation:suratTimelineDotPop .45s cubic-bezier(.34,1.56,.64,1)}

/* ── Branch / Parallel Timeline ── */
.surat-timeline-branch-root{padding-bottom:16px}
.surat-timeline-branch-wrap{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap}
.surat-timeline-branch-node{flex:1;min-width:110px;border-radius:8px;padding:8px 10px;transition:box-shadow .15s}
.surat-timeline-branch-utama{border-color:var(--primary)!important;background:var(--primary-subtle,rgba(59,130,246,.07))!important}
.surat-timeline-branch-node:hover{box-shadow:0 2px 8px rgba(0,0,0,.08)}
/* Panah cabang (satu masuk, mekar ke 2+ arah) buat kartu bernomor */
.timeline-branch-fork{display:flex;flex-direction:column;align-items:center;margin:2px 0 8px}
.timeline-branch-fork svg{width:60px;height:20px;color:var(--border)}
.timeline-branch-fork.line-complete svg{color:var(--success-bright,#3dba7e)}
.surat-detail-dokumen-row{display:flex;align-items:center;gap:12px}
.surat-detail-dokumen-icon{flex-shrink:0;width:38px;height:38px;border-radius:8px;background:#d64545;color:#fff;display:flex;align-items:center;justify-content:center;font-size:8.5px;font-weight:800;letter-spacing:.02em}
.surat-detail-dokumen-info{flex:1;min-width:0}
.surat-detail-dokumen-name{display:block;font-size:13px;font-weight:700;color:var(--text);word-break:break-all;text-decoration:none}
.surat-detail-dokumen-name:hover{color:var(--gold-bright);text-decoration:underline}
.surat-detail-dokumen-size{font-size:11px;color:var(--text-muted);margin-top:2px}
.surat-detail-dokumen-download{flex-shrink:0;display:inline-flex;align-items:center;gap:6px;color:var(--gold-bright);font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap}
.surat-detail-dokumen-download:hover{text-decoration:underline}
.surat-detail-dokumen-download svg{width:15px;height:15px}
#suratDetailModal .modal-actions{gap:8px;margin-top:20px}
#suratDetailKonfirmasi{background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border-color:transparent}
/* PENTING: .btn{display:inline-flex} (dash-styles) menang atas atribut
   [hidden] bawaan browser karena origin author > user-agent, jadi TIAP
   tombol aksi modal Detail Surat butuh override eksplisit ini -- kalau
   nambah tombol baru di surat-detail-modal.blade.php, tambahkan juga
   id-nya di selector bawah ini. */
#suratDetailModal .modal-actions .btn[hidden]{display:none!important}
@media(max-width:700px){.surat-detail-body{grid-template-columns:1fr}.surat-detail-col-left{border-right:none;padding-right:0;padding-bottom:20px;border-bottom:1px solid var(--border-soft);margin-bottom:20px}}
/* Filter tambahan (Tanggal dari/sampai, Jenis Surat, Satuan) khusus grid
   Arsip Surat -- numpang gaya input .rpt-filter-search input yang sama
   biar nyatu sama search+sort bar yang udah ada (lihat initSuratCardSearch
   di bawah), TAPI cuma dipasang di panggilan 'arsip-surat', bukan di
   Surat Keluar/Surat Masuk (dua grid itu tetap search+sort polos). */
.rpt-filter-date-wrap{display:flex;align-items:center;gap:6px;flex:0 0 auto}
.rpt-filter-date-wrap label{font-size:11px;color:var(--text-muted);white-space:nowrap}
.rpt-filter-date-wrap input[type="date"]{box-sizing:border-box;height:38px;border:1px solid var(--border-soft);border-radius:9px;background:var(--panel-alt);color:var(--text);font:inherit;font-size:12px;padding:8px 10px;color-scheme:light dark}
.rpt-filter-date-wrap input[type="date"]:focus{outline:0;border-color:var(--gold-bright);box-shadow:0 0 0 3px rgba(201,122,0,.10)}
@media(max-width:700px){.rpt-filter-date-wrap{width:100%}.rpt-filter-date-wrap input[type="date"]{flex:1;min-width:0}}
</style>
<script>
(function(){
  // Cari + urutkan kartu grid Surat (Surat Keluar & Surat Masuk) -- niru pola
  // .rpt-filter-* yang sama dipakai initReportFilter()
  // (danpus-report-table-filter.blade.php) & initCardSearch()
  // (permintaan-laporan-deadline.blade.php), tapi versi lebih simpel karena
  // tiap grid cuma butuh cari + Terbaru/Terlama, gak butuh dropdown filter
  // status. Dipanggil sekali per grid (Surat Keluar & Surat Masuk) lewat
  // initAllSuratCardSearch() di bawah -- parametrized biar gak dobel-tulis.
  function initSuratCardSearch(sectionId,gridId,searchPlaceholder,extraFilters){
    var section=document.getElementById(sectionId);
    if(!section||section.dataset.searchReady==='1')return;
    var grid=document.getElementById(gridId);
    var panel=section.querySelector('.panel');
    if(!grid||!panel)return;
    var initialCards=grid.querySelectorAll(':scope > .surat-file-card');
    if(!initialCards.length)return;
    section.dataset.searchReady='1';

    var bar=document.createElement('div');
    bar.className='rpt-filter-bar';
    var extraHtml='';
    if(extraFilters){
      // Jenis Surat & Satuan: opsinya dibangun dari nilai yang BENERAN ada
      // di kartu yang ke-render (data-jenis-filter / data-satuan-kode),
      // bukan hardcode -- jadi otomatis nyesuain kalau suatu satuan ternyata
      // gak punya semua kombinasi jenis/lawan di arsipnya. data-jenis-filter
      // SENGAJA beda dari data-prioritas (yang masih dipakai buat warna
      // ikon kartu) -- utk surat Rahasia yang isinya disembunyikan dari
      // satuan ini (lihat $sembunyikanIsiRahasia di surat-arsip-row.blade.php),
      // data-jenis-filter dikosongin biar "Rahasia" gak nongol/bisa
      // difilter sama sekali dari sisi satuan yang emang gak berhak tahu.
      var jenisSet={},satuanMap={};
      initialCards.forEach(function(c){
        var p=c.dataset.jenisFilter;
        if(p)jenisSet[p]=true;
        var sk=c.dataset.satuanKode,sn=c.dataset.satuanNama;
        if(sk)satuanMap[sk]=sn||sk;
      });
      var jenisOpts=Object.keys(jenisSet).sort().map(function(p){
        return '<option value="'+p+'">'+p+'</option>';
      }).join('');
      var satuanKeys=Object.keys(satuanMap);
      var satuanOpts=satuanKeys.sort().map(function(sk){
        return '<option value="'+sk+'">'+satuanMap[sk]+'</option>';
      }).join('');
      var satuanSelectHtml=satuanKeys.length?('<select class="rpt-filter-select" aria-label="Filter satuan">'+(satuanKeys.length>1?'<option value="all">Semua Satuan</option>':'')+satuanOpts+'</select>'):'';
      extraHtml='<div class="rpt-filter-date-wrap"><label for="'+gridId+'DariDate">Dari</label><input type="date" id="'+gridId+'DariDate" aria-label="Tanggal dari"></div>'+
        '<div class="rpt-filter-date-wrap"><label for="'+gridId+'SampaiDate">Sampai</label><input type="date" id="'+gridId+'SampaiDate" aria-label="Tanggal sampai"></div>'+
        '<select class="rpt-filter-select" aria-label="Filter jenis surat"><option value="all">Semua Jenis Surat</option>'+jenisOpts+'</select>'+
        satuanSelectHtml;
    }
    bar.innerHTML='<div class="rpt-filter-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" autocomplete="off" placeholder="'+searchPlaceholder+'" aria-label="'+searchPlaceholder+'"></div>'+extraHtml+'<select class="rpt-filter-select" aria-label="Urutkan"><option value="newest">Terbaru</option><option value="oldest">Terlama</option></select><span class="rpt-filter-count"></span>';
    // .panel di grid Surat cuma bungkus panel-head (judul+deskripsi+tombol)
    // -- grid kartunya sendiri SENGAJA di luar .panel (lihat markup
    // #kirim-surat/#surat-masuk). Bar cari+urut ikut di DALAM .panel
    // (nempel di bawah panel-head, satu kotak sama header), niru persis
    // pola card grid "Permintaan Laporan" (search+filter+counter satu
    // kotak sama header, kartunya sendiri baru di luar/bawah kotak) --
    // BUKAN pola danpus-log-search.blade.php yang taruh bar di luar list.
    panel.appendChild(bar);

    var searchEmpty=document.createElement('div');
    searchEmpty.className='empty-state';
    searchEmpty.style.display='none';
    searchEmpty.innerHTML='<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><div class="empty-state-title">Tidak ada surat yang sesuai dengan pencarian.</div>';
    grid.parentNode.insertBefore(searchEmpty,grid.nextSibling);

    var input=bar.querySelector('input[type="search"]');
    var selects=bar.querySelectorAll('select');
    var sortSelect=selects[selects.length-1];
    var jenisSelect=extraFilters?bar.querySelector('select[aria-label="Filter jenis surat"]'):null;
    var satuanSelect=extraFilters?bar.querySelector('select[aria-label="Filter satuan"]'):null;
    var dariInput=extraFilters?bar.querySelector('input[aria-label="Tanggal dari"]'):null;
    var sampaiInput=extraFilters?bar.querySelector('input[aria-label="Tanggal sampai"]'):null;
    var count=bar.querySelector('.rpt-filter-count');
    var applying=false;
    var raf=0;

    // Tanggal "sampai" diinterpretasikan SAMPAI AKHIR hari itu (23:59:59),
    // biar surat yang dibuat siang/sore di tanggal yang dipilih tetap ikut
    // kehitung -- bukan cuma tepat jam 00:00.
    function endOfDaySeconds(dateStr){
      var d=new Date(dateStr+'T23:59:59');
      return Math.floor(d.getTime()/1000);
    }
    function startOfDaySeconds(dateStr){
      var d=new Date(dateStr+'T00:00:00');
      return Math.floor(d.getTime()/1000);
    }

    function apply(){
      applying=true;
      var items=Array.prototype.slice.call(grid.querySelectorAll(':scope > .surat-file-card'));
      var srvEmpty=grid.querySelector(':scope > .empty-state');
      if(items.length===0){
        bar.style.display='none';
        searchEmpty.style.display='none';
        applying=false;
        return;
      }
      bar.style.display='';

      items.sort(function(a,b){
        var diff=Number(a.dataset.createdAt)-Number(b.dataset.createdAt);
        return sortSelect.value==='oldest'?diff:-diff;
      });
      // Reorder DOM cuma kalau urutannya BENERAN beda -- insertBefore
      // tanpa syarat bikin MutationObserver ke bawah kepicu ulang terus
      // (apply() manggil dirinya sendiri lewat observer) walau urutan
      // sebenarnya udah pas, sama kayak needsReorder di initReportFilter.
      var needsReorder=items.some(function(item,i){
        return item.nextElementSibling!==(items[i+1]||srvEmpty||null);
      });
      if(needsReorder){
        items.forEach(function(item){ grid.insertBefore(item,srvEmpty||null); });
      }

      var q=(input.value||'').trim().toLowerCase();
      var jenisVal=jenisSelect?jenisSelect.value:'all';
      var satuanVal=satuanSelect?satuanSelect.value:'all';
      var dariSec=(dariInput&&dariInput.value)?startOfDaySeconds(dariInput.value):null;
      var sampaiSec=(sampaiInput&&sampaiInput.value)?endOfDaySeconds(sampaiInput.value):null;
      var visible=0;
      items.forEach(function(item){
        var match=!q||(item.dataset.search||'').indexOf(q)!==-1;
        if(match&&jenisVal!=='all')match=item.dataset.jenisFilter===jenisVal;
        if(match&&satuanVal&&satuanVal!=='all')match=item.dataset.satuanKode===satuanVal;
        if(match&&(dariSec!==null||sampaiSec!==null)){
          var createdAt=Number(item.dataset.createdAt);
          if(dariSec!==null&&createdAt<dariSec)match=false;
          if(match&&sampaiSec!==null&&createdAt>sampaiSec)match=false;
        }
        item.style.display=match?'':'none';
        if(match)visible++;
      });
      count.textContent=visible+' dari '+items.length+' surat';
      searchEmpty.style.display=visible===0?'':'none';
      applying=false;
    }

    function scheduleApply(){
      if(raf)return;
      raf=requestAnimationFrame(function(){raf=0;if(!applying)apply();});
    }

    input.addEventListener('input',apply);
    sortSelect.addEventListener('change',apply);
    if(jenisSelect)jenisSelect.addEventListener('change',apply);
    if(satuanSelect)satuanSelect.addEventListener('change',apply);
    if(dariInput)dariInput.addEventListener('change',apply);
    if(sampaiInput)sampaiInput.addEventListener('change',apply);

    var observer=new MutationObserver(function(){ if(!applying) scheduleApply(); });
    observer.observe(grid,{childList:true});

    apply();
  }
  function initAllSuratCardSearch(){
    initSuratCardSearch('kirim-surat','suratTerkirimGrid','Cari perihal atau tujuan...');
    initSuratCardSearch('surat-masuk','suratMasukGrid','Cari perihal atau pengirim...');
    // Cuma Arsip Surat yang dapet filter tambahan (Tanggal dari/sampai,
    // Jenis Surat, Satuan) -- Surat Keluar & Surat Masuk tetap search+sort
    // polos seperti semula.
    initSuratCardSearch('arsip-surat','suratArsipGrid','Cari perihal atau dari/tujuan...',true);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initAllSuratCardSearch);else initAllSuratCardSearch();
})();
</script>
<style>
/* ── Tombol aksi tambahan untuk alur surat ────────────────────────── */
.btn-success{background:rgba(22,163,74,.14);color:#16a34a;border-color:rgba(22,163,74,.28);font-weight:700}
.btn-success:hover{background:rgba(22,163,74,.24);border-color:rgba(22,163,74,.45)}
.btn-warning{background:rgba(217,119,6,.13);color:#b45309;border-color:rgba(217,119,6,.28);font-weight:700}
.btn-warning:hover{background:rgba(217,119,6,.22);border-color:rgba(217,119,6,.45)}
/* Badge warna biru muda untuk jenis Tembusan Info */
.status-badge.status-info{background:rgba(59,130,246,.11);color:#2563eb;border-color:rgba(59,130,246,.24)}
/* ── Form fields di modal Teruskan ───────────────────────────────── */
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:7px}
.form-select,.form-input{width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--surface,var(--panel));color:var(--text);font:inherit;font-size:13.5px;transition:border-color .15s}
.form-select:focus,.form-input:focus{outline:none;border-color:var(--primary,#6d5bff)}
.form-textarea{width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--surface,var(--panel));color:var(--text);font:inherit;font-size:13.5px;min-height:80px;transition:border-color .15s}
.form-textarea:focus{outline:none;border-color:var(--primary,#6d5bff)}
/* ── Tombol close X (icon-only) di modal teruskan ────────────────── */
.btn-icon-close{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;flex-shrink:0;transition:background .15s,color .15s}
.btn-icon-close:hover{background:var(--border-soft);color:var(--text)}
/* ── Modal Teruskan layout ───────────────────────────────────────── */
#suratTeruskanModal .report-modal-head{flex-wrap:nowrap;align-items:flex-start;gap:12px}
/* Label hover tindakan checklist */
.surat-tindakan-check-label:hover{background:var(--gold-dim)}
/* Tombol aksi di modal detail -- flex-wrap agar tidak overflow */
#suratDetailActions{flex-wrap:wrap;gap:8px}
#suratDetailActions .btn{flex-shrink:0}
</style>
