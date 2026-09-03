<style>
.deadline-section,.deadline-sender-section{position:relative;overflow:hidden}.deadline-primary,.deadline-secondary{border:1px solid transparent;border-radius:9px;padding:9px 14px;font-size:11px;font-weight:700;cursor:pointer;transition:.15s ease}.deadline-primary{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid));color:var(--on-gold);box-shadow:0 8px 22px -8px rgba(217,146,11,.5)}.deadline-primary:hover{filter:brightness(1.06);transform:translateY(-1px)}.deadline-secondary{background:var(--p-surface-2,var(--panel-alt));border-color:var(--p-border,var(--border));color:var(--p-text,var(--text))}.deadline-secondary:hover{border-color:var(--p-accent,var(--gold-bright));transform:translateY(-1px)}.deadline-primary:active,.deadline-secondary:active{transform:scale(.96)}.deadline-primary:disabled,.deadline-secondary:disabled{opacity:.4;cursor:not-allowed;filter:none!important;transform:none!important;box-shadow:none!important}.deadline-secondary.confirm-btn{background:var(--success,#16834b);border-color:var(--success,#16834b);color:#fff}.deadline-secondary.confirm-btn:hover{filter:brightness(1.1);border-color:var(--success,#16834b)}.deadline-primary.small,.deadline-secondary.small{padding:7px 10px;font-size:10px}.detail-btn.kirim-laporan-btn{letter-spacing:.03em;background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid));color:var(--on-gold);border-color:transparent;box-shadow:0 8px 18px -8px rgba(212,175,55,.55)}.detail-btn.kirim-laporan-btn:hover{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid));color:var(--on-gold);border-color:transparent;box-shadow:0 10px 22px -6px rgba(212,175,55,.65)}.deadline-form-wrap{margin:0 0 18px;padding:16px;border:1px solid var(--p-border,var(--border-soft));border-radius:12px;background:var(--p-surface-2,var(--panel-alt))}.deadline-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.deadline-field{display:flex;flex-direction:column;gap:6px}.deadline-field.full{grid-column:1/-1}.deadline-field label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--p-muted,var(--text-muted))}.deadline-field input,.deadline-field select,.deadline-field textarea{width:100%;box-sizing:border-box;border:1px solid var(--p-border,var(--border));border-radius:8px;background:var(--p-surface,var(--panel));color:var(--p-text,var(--text));padding:9px 10px;font:inherit;font-size:12px}.deadline-field textarea{min-height:100px;resize:none}.deadline-check-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}.deadline-check{display:grid;grid-template-columns:auto 1fr;column-gap:7px;align-items:center;padding:9px;border:1px solid var(--p-border,var(--border));border-radius:9px;background:var(--p-surface,var(--panel));cursor:pointer}.deadline-check input{width:auto}.deadline-check span{font-family:var(--mono);font-size:10px;font-weight:800;color:var(--p-accent,var(--gold-bright))}.deadline-check small{grid-column:2;font-size:9px;color:var(--p-muted,var(--text-muted));line-height:1.35}.deadline-form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}.deadline-table-wrap{overflow-x:auto}.deadline-table{width:100%;border-collapse:collapse;min-width:720px}.deadline-table th{font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:var(--p-muted,var(--text-muted));text-align:left;padding:10px;border-bottom:1px solid var(--p-border,var(--border))}.deadline-table td{padding:11px 10px;border-bottom:1px solid var(--p-border,var(--border));font-size:11px;vertical-align:middle}.deadline-table td strong{display:block}.deadline-table td small{display:block;color:var(--p-muted,var(--text-muted));margin-top:3px;max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.deadline-pill{display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:9px;font-weight:800;border:1px solid transparent;white-space:nowrap}.deadline-pill.wait{color:var(--p-orange);background:var(--p-orange-bg);border-color:var(--p-orange-border)}.deadline-pill.ok{color:#16834b;background:rgba(63,194,125,.12);border-color:rgba(63,194,125,.28)}.deadline-pill.bad{color:#c83b3b;background:rgba(181,52,47,.08);border-color:rgba(198,40,40,.28)}.deadline-pill.blue{color:#2476ad;background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.25)}.deadline-pill.revisi{color:var(--gold-solid);background:rgba(217,146,11,.14);border-color:rgba(217,146,11,.4)}.deadline-pill.new{color:#0d9488;background:rgba(13,148,136,.13);border-color:rgba(13,148,136,.35)}.deadline-revisi{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid))!important;color:var(--on-gold)!important;border-color:transparent!important;box-shadow:0 8px 22px -8px rgba(217,146,11,.5)}.deadline-revisi:hover{color:var(--on-gold)!important;box-shadow:0 10px 26px -6px rgba(217,146,11,.6);filter:none;transform:translateY(-1px)}.deadline-sender-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}.deadline-sender-head h3{margin:0 0 4px}.deadline-sender-head p{margin:0;font-size:11px;color:var(--text-muted);line-height:1.5}.deadline-count{font-family:var(--mono);font-size:10px;color:var(--gold-bright);white-space:nowrap}.deadline-complete{font-size:10px;font-weight:700;color:var(--green)}.deadline-complete.cancelled{color:#c83b3b}
@media(max-width:850px){.deadline-check-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.deadline-form-grid{grid-template-columns:1fr}.deadline-field.full{grid-column:auto}}@media(max-width:550px){.deadline-check-grid{grid-template-columns:1fr}.deadline-sender-head{display:block}.deadline-count{display:inline-block;margin-top:8px}}

/* ---- Grid kartu "project card" -- ganti .deadline-sender-list dari list
   vertikal penuh-lebar jadi grid responsif, tiap .deadline-sender-item jadi
   kartu ringkas (kotak ikon, judul, subjudul, progress bar, meta footer).
   .deadline-sender-list sekarang SIBLING dari .report-card (bukan anaknya
   lagi, lihat laporan-role.blade.php) -- panel judul "Permintaan Laporan"
   tetap jadi box seperti panel lain, kartu-kartunya ngambang sendiri-sendiri
   di luar box itu (shadow per kartu, bukan nempel jadi satu kotak besar). ---- */
.deadline-sender-section{overflow:visible}
.deadline-sender-section > .report-card{margin-bottom:40px}
.deadline-sender-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));column-gap:18px;row-gap:64px}
.deadline-sender-list > .empty-state{grid-column:1/-1}
@keyframes dcardZoomIn{0%{opacity:0;transform:scale(.82)}100%{opacity:1;transform:scale(1)}}
.dcard-enter{animation:dcardZoomIn .38s cubic-bezier(.34,1.3,.64,1) both}
@media(prefers-reduced-motion:reduce){.dcard-enter{animation:none}}
.deadline-sender-item{position:relative;display:flex;flex-direction:column;gap:14px;padding:14px 18px 18px;border:1px solid var(--border-soft);border-radius:18px;background:var(--panel-alt);box-shadow:0 12px 28px -16px rgba(0,0,0,.28);min-width:0}
.deadline-sender-item::before{content:"";position:absolute;top:100%;left:18px;right:18px;height:10px;border-radius:0 0 16px 16px;background:var(--panel-alt);box-shadow:0 4px 8px -3px rgba(0,0,0,.18);z-index:-1}
.dcard-head{display:flex;align-items:center;justify-content:center;margin-top:-42px}
.dcard-icon{width:64px;height:64px;border-radius:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;box-shadow:0 12px 22px -8px rgba(0,0,0,.4)}
.dcard-icon svg{width:30px;height:30px;stroke:currentColor;fill:none;stroke-width:1.7}
.dcard-icon.prio-tinggi{background:#6d28d9;color:#fff}
.dcard-icon.prio-sedang{background:#a855f7;color:#fff}
.dcard-icon.prio-rendah{background:#e9d5ff;color:#6b21a8}
.priority-tag.pl-prio-violet.prio-tinggi{color:#fff;background:#6d28d9;border-color:#6d28d9}
.priority-tag.pl-prio-violet.prio-sedang{color:#fff;background:#a855f7;border-color:#a855f7}
.priority-tag.pl-prio-violet.prio-rendah{color:#6b21a8;background:#e9d5ff;border-color:#e9d5ff}
.dcard-body{min-width:0;display:flex;flex-direction:column;align-items:center;text-align:center;gap:10px}
.deadline-sender-title{font-size:13.5px;font-weight:800;line-height:1.4;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.dcard-status-pill{margin:0 auto}
.dcard-meta-hidden{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
.dcard-pin-btn{position:absolute;top:14px;left:14px;width:22px;height:22px;border-radius:50%;border:1.5px solid var(--border-strong,var(--border-soft));background:var(--panel);display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:5;padding:0;transition:background .15s ease,border-color .15s ease,transform .15s ease}
.dcard-pin-btn svg{width:12px;height:12px;stroke:#fff;fill:none;stroke-width:3;opacity:0;transition:opacity .15s ease}
.dcard-pin-btn:hover{border-color:var(--gold-solid-bright,#FF9800);transform:scale(1.08)}
.dcard-pin-btn[aria-pressed="true"]{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid));border-color:transparent;box-shadow:0 4px 10px -3px rgba(217,146,11,.6)}
.dcard-pin-btn[aria-pressed="true"] svg{opacity:1}
.dcard-menu-wrap{position:absolute;top:14px;right:14px;width:26px;z-index:5}
.dcard-menu-btn{width:26px;height:26px;border-radius:8px;border:0;background:transparent;color:var(--text-muted);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s ease,color .15s ease}
.dcard-menu-btn svg{width:16px;height:16px}
.dcard-menu-btn:hover{background:var(--panel-2,var(--panel));color:var(--text)}
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
#kirimLaporanModal .report-modal-card{width:min(720px,100%);transition:width .2s ease,transform .2s ease}
#kirimLaporanModal.wizard-active .report-modal-card{width:min(940px,100%)}
.kirim-laporan-wizard-body{display:flex;flex-direction:column;gap:18px;min-width:0}
.kirim-laporan-wizard-topbar{display:flex;align-items:center;gap:6px;border-bottom:1px solid var(--border-soft);padding-bottom:16px;opacity:0;transform:translateY(-6px);transition:opacity .22s ease,transform .22s ease}
.kirim-laporan-wizard-topbar.wizard-topbar-visible{opacity:1;transform:translateY(0)}
@media(prefers-reduced-motion:reduce){.kirim-laporan-wizard-topbar{transition:none}}
.wizard-step-list{list-style:none;margin:0;padding:0;display:flex;flex:0 1 auto;min-width:0;flex-wrap:nowrap;overflow-x:auto;overflow-y:hidden;border:1px solid var(--border-soft);border-radius:12px;scrollbar-width:none}
.wizard-step-list::-webkit-scrollbar{display:none}
.wizard-step{position:relative;display:flex;align-items:center;gap:9px;padding:10px 16px;flex-shrink:0;cursor:pointer;border-right:1px solid var(--border-soft)}
.wizard-step:last-child{border-right:none}
.wizard-step-dot{box-sizing:border-box;flex-shrink:0;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11.5px;font-weight:800;background:var(--panel);border:2px solid var(--border-soft);color:var(--text-muted);transition:background .15s ease,border-color .15s ease,color .15s ease}
.wizard-step-dot svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-linecap:round;stroke-linejoin:round}
.wizard-step-label{font-size:12.5px;font-weight:600;color:var(--text);line-height:1.3;white-space:nowrap}
.wizard-step-done .wizard-step-dot{background:var(--success,var(--green,#16834b));border-color:var(--success,var(--green,#16834b));color:#fff}
.wizard-step-done .wizard-step-label{color:var(--success,var(--green,#16834b))}
.wizard-step-pending{cursor:not-allowed}
.wizard-step-pending .wizard-step-label{color:var(--text-muted)}
.wizard-step-active .wizard-step-dot{background:var(--p-orange,#ea580c);border-color:var(--p-orange,#ea580c);color:#fff}
.wizard-step-active .wizard-step-label{color:var(--p-orange,#ea580c)}
.wizard-step-late .wizard-step-dot{background:var(--red,#c83b3b);border-color:var(--red,#c83b3b);color:#fff}
.wizard-step-late .wizard-step-label{color:var(--red,#c83b3b)}
.wizard-step-current .wizard-step-label{font-weight:800}
.wizard-step-current::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:var(--p-orange,#ea580c);transform:scaleX(0);transform-origin:left;transition:transform 1.1s cubic-bezier(.16,1,.3,1)}
.wizard-step-current.wizard-step-marker-in::after{transform:scaleX(1)}
.wizard-step-current .wizard-step-dot{position:relative;opacity:.35;transition:background .15s ease,border-color .15s ease,color .15s ease,opacity 1.1s cubic-bezier(.16,1,.3,1)}
.wizard-step-current.wizard-step-marker-in .wizard-step-dot{opacity:1}
.wizard-step-current .wizard-step-dot::before{content:"";position:absolute;inset:-5px;border-radius:50%;background:var(--p-orange,#ea580c);opacity:.45;transform:scale(.4);pointer-events:none}
.wizard-step-current.wizard-step-marker-in .wizard-step-dot::before{opacity:0;transform:scale(1.7);transition:opacity 1.1s cubic-bezier(.16,1,.3,1),transform 1.1s cubic-bezier(.16,1,.3,1)}
.wizard-step-current.wizard-step-done::after{background:var(--success,var(--green,#16834b))}
.wizard-step-current.wizard-step-late::after{background:var(--red,#c83b3b)}
.wizard-step-current.wizard-step-done .wizard-step-dot::before{background:var(--success,var(--green,#16834b))}
.wizard-step-current.wizard-step-late .wizard-step-dot::before{background:var(--red,#c83b3b)}
@media(prefers-reduced-motion:reduce){.wizard-step-current .wizard-step-dot::before{display:none}.wizard-step-current::after,.wizard-step-current .wizard-step-dot{transition:none}}
.wizard-topbar-nav{flex-shrink:0;width:28px;height:28px;border-radius:50%;border:1px solid var(--border-soft);background:var(--panel);display:flex;align-items:center;justify-content:center;color:var(--text-muted);cursor:pointer;transition:border-color .15s ease,color .15s ease}
.wizard-topbar-nav[hidden]{display:none}
.wizard-topbar-nav:hover:not(:disabled){border-color:var(--p-orange,#ea580c);color:var(--p-orange,#ea580c)}
.wizard-topbar-nav:disabled{opacity:.35;cursor:not-allowed}
.wizard-topbar-nav svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round}
#kirimLaporanModal .kirim-laporan-form-card{grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:18px 20px;padding:20px;border:1px solid var(--border-soft);border-radius:14px;background:var(--panel-alt)}
@media(max-width:640px){#kirimLaporanModal .kirim-laporan-form-card{grid-template-columns:1fr;padding:16px}}
#kirimLaporanModal .form-field[hidden]{display:none!important}
.kirim-laporan-modal-head{display:flex;align-items:flex-start;gap:12px;margin-bottom:18px}
.kirim-laporan-modal-icon{flex-shrink:0;width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:var(--gold-dim);color:var(--gold-bright)}
.kirim-laporan-modal-icon svg{width:19px;height:19px}
#kirimLaporanModal .form-field label{display:inline-flex;align-items:center;gap:6px}
.form-field-icon{width:13px;height:13px;flex-shrink:0;color:var(--text-dim,var(--text-muted))}
.lampiran-dropzone{position:relative;border:1.5px dashed var(--border-soft,var(--border));border-radius:12px;padding:22px 16px;background:var(--panel);text-align:center;transition:border-color .15s ease,background-color .15s ease}
.lampiran-dropzone:hover,.lampiran-dropzone.is-dragover{border-color:var(--gold-bright);background:var(--gold-dim)}
.lampiran-dropzone-input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;margin:0}
.lampiran-dropzone-prompt{display:flex;flex-direction:column;align-items:center;gap:8px;pointer-events:none}
.lampiran-dropzone-icon{color:var(--gold-bright)}
.lampiran-dropzone-icon svg{width:22px;height:22px}
.lampiran-dropzone-text{display:flex;flex-direction:column;gap:8px}
.lampiran-dropzone-text-main{font-size:13px;font-weight:700;color:var(--text)}
.lampiran-dropzone-text-sub{font-size:11.5px;color:var(--text-muted)}
.lampiran-file-list{margin-top:10px;display:flex;flex-direction:column;gap:6px;max-height:260px;overflow-y:auto}
.lampiran-file-list-empty{display:block;padding:14px 10px;text-align:center;font-size:12px;color:var(--text-muted);border:1px dashed var(--border-soft,var(--border));border-radius:12px}
.lampiran-file-list-empty[hidden]{display:none}
#kirimLaporanSubmitBtn[hidden]{display:none}
.lampiran-file-row{display:flex;align-items:center;gap:11px;padding:9px 10px;border-radius:10px;background:var(--panel-alt)}
.lampiran-file-row-icon{flex-shrink:0;width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#64748b;color:#fff;font-size:8.5px;font-weight:800;letter-spacing:.02em}
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

<style>
/* Flyout submenu Riwayat Aktivitas Pimpinan saat sidebar diciutkan.
   Partial ini pasti dimuat oleh halaman Pimpinan; selector dibatasi ke
   #monitorGroup supaya Pelaporan, Surat, dan Kendala Kasansi tidak ikut.
   Bentuknya mengikuti submenu saat sidebar lebar: teks normal, jarak baris
   tetap lega, item aktif tetap aksen, dan scrollbar tetap rapi di dalam box. */
@media(min-width:901px){
  .sidebar.collapsed #monitorGroup.open .side-subnav>div{gap:2px!important;}
  .sidebar.collapsed #monitorGroup.open .side-sub-link{
    display:flex!important;
    align-items:center!important;
    gap:10px!important;
    margin:0!important;
    padding:9px 12px 9px 17px!important;
    min-height:0!important;
    height:auto!important;
    border-radius:0 9px 9px 0!important;
    color:var(--text-muted)!important;
    font-family:var(--body)!important;
    font-size:13px!important;
    font-weight:500!important;
    line-height:1.4!important;
    text-decoration:none!important;
  }
  .sidebar.collapsed #monitorGroup.open .side-sub-link:hover{
    background:var(--hover-tint)!important;
    color:var(--text)!important;
  }
  .sidebar.collapsed #monitorGroup.open .side-sub-link.active{
    background:var(--gold-dim,rgba(201,122,0,.1))!important;
    color:var(--p-accent)!important;
    font-weight:600!important;
  }
  .sidebar.collapsed #monitorGroup.open .side-sub-link.active:before{
    content:"";
    left:-1px;
    top:8px;
    bottom:8px;
    width:2px;
    border-radius:2px;
    background:var(--p-accent);
  }
  .sidebar.collapsed #monitorGroup.open .side-sub-link .sub-dot{
    width:5px!important;
    height:5px!important;
    border-radius:50%!important;
    opacity:.5!important;
    background:currentColor!important;
    flex:0 0 auto!important;
  }
  .sidebar.collapsed #monitorGroup.open .side-sub-link.active .sub-dot{
    background:var(--p-accent)!important;
    opacity:1!important;
    box-shadow:0 0 0 3px rgba(201,122,0,.15)!important;
  }
}
</style>
