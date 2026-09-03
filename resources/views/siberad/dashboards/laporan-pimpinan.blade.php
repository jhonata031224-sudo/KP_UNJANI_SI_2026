<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $satuan->nama ?? 'Pimpinan' }} — {{ ($pengaturan->hero_judul_awal ?? '') . ($pengaturan->hero_judul_aksen ?? 'SIBERAD') }}</title>
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo-pussiberad.jpg') }}">
@include('siberad.dashboards.partials.dash-styles')
@include('siberad.dashboards.partials.permintaan-laporan-deadline-styles')
@include('siberad.dashboards.partials.surat-card-styles')
{{-- Tema terang bawaan dash-styles.blade.php krem (cream) -- kartu Permintaan
     Laporan (deadline-sender-item/dcard-*) numpang variabel warna generik itu
     (--panel-alt dkk), bukan token --p-* punya Pimpinan sendiri, jadi warnanya
     ikut cream, beda sama chrome Pimpinan yang lain (putih/abu netral) dan
     beda sama kartu yang sama persis di satuan. laporan-role.blade.php sudah
     punya override serupa buat halamannya sendiri -- disamakan di sini biar
     kartunya konsisten warnanya di kedua halaman. --}}
<style>
/* Sisakan ruang scrollbar permanen -- pas modal "Buat Permintaan" mengunci
   scroll <body> (overflow:hidden), lebar viewport tidak berubah, jadi
   ResizeObserver Chart.js tidak ke-trigger & tidak ada hitch pas modal buka. */
html{scrollbar-gutter:stable}
:root[data-theme="light"]{--bg:#f5f7f9;--bg-deep:#ffffff;--panel:#ffffff;--panel-2:#f8fafc;--panel-alt:#f8fafc;--border:#e2e8f0;--border-soft:#e2e8f0;--border-strong:#cbd5e1;--gold:#FF9800;--gold-bright:#FF9800;--gold-dim:rgba(201,122,0,.12);--green:#16834b;--green-bright:#16834b;--green-dim:rgba(22,131,75,.12);--amber:#b77900;--amber-dim:rgba(183,121,0,.14);--red:#c83b3b;--red-dim:rgba(200,59,59,.12);--text:#17212b;--text-muted:#64748b;--text-dim:#64748b;--surface:rgba(255,255,255,.9);--hover-tint:rgba(15,23,42,.035)}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
:root{--p-bg:#f5f7f9;--p-surface:#fff;--p-surface-2:#f8fafc;--p-border:#e2e8f0;--p-text:#17212b;--p-muted:#64748b;--p-accent:#FF9800;--p-green:#16834b;--p-red:#c83b3b;--p-yellow:#b77900;--p-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25)}
:root:not([data-theme="light"]){--p-bg:var(--bg);--p-surface:var(--panel);--p-surface-2:var(--panel-alt);--p-border:var(--border);--p-text:var(--text);--p-muted:var(--text-muted);--p-accent:var(--gold-bright);--p-green:var(--success-bright);--p-red:var(--red);--p-yellow:var(--amber);--p-shadow:0 10px 30px rgba(0,0,0,.18)}
body{background:var(--p-bg)!important;color:var(--p-text)}.content{background:var(--p-bg)!important;padding-bottom:40px}.pimp-page{max-width:1500px;margin:0 auto}.pimp-hero{position:relative;background:linear-gradient(180deg, rgba(255,255,255,.02), transparent), var(--p-surface);border:1px solid var(--p-border);border-radius:12px;padding:24px 26px;margin-bottom:20px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25)}.pimp-hero::before{content:"";position:absolute;top:0;left:14px;right:14px;height:1px;background:linear-gradient(90deg, transparent, var(--p-border), transparent)}.pimp-eyebrow{font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--p-accent);margin-bottom:7px}.pimp-hero h1{margin:0;font-family:var(--display);font-size:30px;line-height:1.15;color:var(--p-text)}.pimp-hero p{margin:8px 0 0;color:var(--p-muted);font-size:13px;line-height:1.6}.pimp-kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:20px}.pimp-kpi{background:var(--p-surface);border:1px solid var(--p-border);border-radius:14px;padding:18px 20px;box-shadow:var(--p-shadow);min-width:0}.pimp-kpi .label{font-size:11px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--p-muted)}.pimp-kpi .value{font-family:var(--mono);font-size:30px;font-weight:700;margin-top:8px;color:var(--p-text)}.pimp-kpi .sub{font-size:11px;color:var(--p-muted);margin-top:4px}.pimp-kpi.wait .value{color:#f59e0b}.pimp-kpi.ok .value{color:#22c55e}.pimp-kpi.bad .value{color:#ef4444}.pimp-kpi.late .value{color:#ff6b6b}.pimp-kpi.cancelled .value{color:#c1121f}.section-block{background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px;padding:20px;box-shadow:var(--p-shadow);margin-bottom:20px}.section-head-clean{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;margin-bottom:16px}.section-head-clean h2{font-family:var(--display);font-size:19px;margin:0;color:var(--p-text)}.section-head-clean p{margin:5px 0 0;font-size:12px;color:var(--p-muted);line-height:1.5}.chart-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,1.95fr);gap:14px;margin-bottom:20px}.chart-card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px;padding:18px 20px;box-shadow:var(--p-shadow);min-width:0}.chart-card h3{font-family:var(--display);font-size:16px;margin:0;color:var(--p-text)}.chart-card p{font-size:11px;color:var(--p-muted);margin:5px 0 14px;line-height:1.5}.chart-box{position:relative;height:280px}.chart-card.compact .chart-box{height:215px}.chart-box-scroll{overflow-x:auto;overflow-y:hidden}.chart-box-scroll .chart-box-inner{position:relative;height:100%}.chart-box-scroll::-webkit-scrollbar{height:7px}.chart-box-scroll::-webkit-scrollbar-thumb{background:var(--p-border);border-radius:99px}.chart-grid.solo{grid-template-columns:minmax(0,min(420px,100%))}.chart-grid.balanced{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}.status-summary-list{display:flex;flex-direction:column;gap:10px;margin-top:2px}.status-summary-row{display:flex;align-items:center;gap:10px;padding:11px 13px;border:1px solid var(--p-border);border-radius:10px;background:var(--p-surface-2)}.status-summary-dot{width:10px;height:10px;border-radius:50%;flex:0 0 auto}.status-summary-label{flex:1;min-width:0;font-size:12.5px;font-weight:700;color:var(--p-text)}.status-summary-value{font-family:var(--mono);font-weight:700;font-size:14px;color:var(--p-text);white-space:nowrap}.status-summary-value small{font-weight:600;color:var(--p-muted);margin-left:3px;font-size:10.5px}.chart-box-scroll-y{height:520px;overflow-y:auto;overflow-x:hidden}.chart-box-scroll-y .chart-box-inner{position:relative;width:100%}.chart-box-scroll-y::-webkit-scrollbar{width:7px}.chart-box-scroll-y::-webkit-scrollbar-thumb{background:var(--p-border);border-radius:99px}.chart-legend{display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:8px 14px;margin-top:14px}.chart-legend-item{display:flex;align-items:center;gap:6px;font-size:11.5px;font-weight:600;color:var(--p-muted);white-space:nowrap;cursor:pointer;user-select:none}.chart-legend-item.is-hidden{text-decoration:line-through;opacity:.5}.chart-legend-dot{width:9px;height:9px;border-radius:50%;flex:0 0 auto}.satlak-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.satlak-card{border:1px solid var(--p-border);background:var(--p-surface-2);border-radius:12px;padding:18px;transition:.15s ease}.satlak-card:hover{border-color:color-mix(in srgb,var(--p-accent) 30%,var(--p-border));box-shadow:0 6px 16px rgba(15,23,42,.07)}.satlak-card .code{font-family:var(--mono);font-size:10px;color:var(--p-accent);font-weight:800;letter-spacing:.08em}.satlak-card .name{font-weight:700;font-size:14px;line-height:1.35;margin-top:8px;min-height:38px;color:var(--p-text)}.satlak-card .total{font-family:var(--mono);font-size:28px;font-weight:700;margin-top:6px;color:var(--p-text)}.satlak-card .caption{font-size:11px;color:var(--p-muted)}.mini-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:0;margin-top:14px;padding-top:12px;border-top:1px solid var(--p-border)}.mini-stat{text-align:center;border-left:1px solid var(--p-border)}.mini-stat:first-child{border-left:0}.mini-stat strong{display:block;font-family:var(--mono);font-size:15px;font-weight:700}.mini-stat span{display:block;font-size:9px;color:var(--p-muted);margin-top:2px;text-transform:uppercase;letter-spacing:.04em}.mini-stat.wait strong{color:#f59e0b}.mini-stat.ok strong{color:#22c55e}.mini-stat.bad strong{color:#ef4444}.card-link{display:flex;align-items:center;justify-content:center;gap:5px;margin-top:14px;padding:8px;border-radius:8px;border:1px solid var(--p-border);background:var(--p-surface);font-size:11px;font-weight:600;color:var(--p-muted);text-decoration:none;transition:.15s ease}.card-link:hover{border-color:var(--p-accent);color:var(--p-accent);background:var(--p-surface-2)}.status-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:10px;font-weight:800;border:1px solid transparent}.status-pill:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}.status-pill.wait{color:var(--p-yellow);background:rgba(224,168,58,.12);border-color:rgba(224,168,58,.35)}
.status-pill.revisi{color:var(--gold-solid);background:rgba(217,146,11,.14);border-color:rgba(217,146,11,.4)}.status-pill.blue{color:#2476ad;background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.25)}.status-pill.ok{color:var(--p-green);background:rgba(63,194,125,.12);border-color:rgba(63,194,125,.28)}.status-pill.bad{color:var(--p-red);background:rgba(181,52,47,.12);border-color:rgba(198,40,40,.3)}.status-pill.proses{color:var(--p-orange);background:var(--p-orange-bg);border-color:var(--p-orange-border)}.clean-table-wrap{overflow-x:auto}.clean-table{width:100%;border-collapse:collapse;min-width:780px}.clean-table th{font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:var(--p-muted);text-align:left;padding:11px 12px;border-bottom:1px solid var(--p-border);white-space:nowrap}.clean-table td{padding:13px 12px;border-bottom:1px solid var(--p-border);font-size:12px;color:var(--p-text);vertical-align:middle}#kendala-kasansi .clean-table th:nth-child(2),#kendala-kasansi .clean-table th:nth-child(3),#kendala-kasansi .clean-table th:nth-child(4),#kendala-kasansi .clean-table th:nth-child(5),#kendala-kasansi .clean-table th:nth-child(6),#kendala-kasansi .clean-table td:nth-child(2),#kendala-kasansi .clean-table td:nth-child(3),#kendala-kasansi .clean-table td:nth-child(4),#kendala-kasansi .clean-table td:nth-child(5),#kendala-kasansi .clean-table td:nth-child(6){text-align:center}#kendala-kasansi .clean-table .action-row{justify-content:center}#arsip-kendala-kasansi .clean-table th:nth-child(2),#arsip-kendala-kasansi .clean-table th:nth-child(3),#arsip-kendala-kasansi .clean-table th:nth-child(4),#arsip-kendala-kasansi .clean-table th:nth-child(5),#arsip-kendala-kasansi .clean-table th:nth-child(6),#arsip-kendala-kasansi .clean-table td:nth-child(2),#arsip-kendala-kasansi .clean-table td:nth-child(3),#arsip-kendala-kasansi .clean-table td:nth-child(4),#arsip-kendala-kasansi .clean-table td:nth-child(5),#arsip-kendala-kasansi .clean-table td:nth-child(6){text-align:center}#arsip-kendala-kasansi .clean-table .action-row{justify-content:center}.clean-table tbody tr:hover{background:var(--hover-tint)}.clean-table tbody tr:last-child td{border-bottom:0}.sender{font-weight:800;color:var(--p-text)}.subject{font-weight:700;color:var(--p-text)}.muted{font-size:10px;color:var(--p-muted);margin-top:3px}.detail-btn{border:1px solid var(--p-border);background:var(--p-surface);color:var(--p-text);border-radius:8px;padding:7px 10px;font-size:10px;font-weight:700;cursor:pointer;transition:border-color .15s ease,background .15s ease,transform .15s ease}.detail-btn:hover{border-color:var(--p-accent);background:var(--p-surface-2);transform:translateY(-1px)}.detail-btn:active{transform:translateY(0) scale(.97)}.action-row{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.action-row form{display:inline-flex;margin:0}.action-row button{border:1px solid transparent;border-radius:8px;padding:8px 14px;font-size:11px;font-weight:700;cursor:pointer;transition:filter .15s ease,transform .15s ease,background .15s ease,color .15s ease}
.action-row .detail-btn{border-color:var(--p-border);padding:7px 10px;font-size:10px}
.action-row .detail-btn:hover{border-color:var(--p-accent);background:var(--p-surface-2)}.action-row .approve{background:var(--p-green);color:#fff;box-shadow:0 6px 16px -6px rgba(22,131,75,.5)}.btn.approve:hover{border-color:var(--p-green);color:var(--p-green);transform:translateY(-1px)}
#konfirmasiSuratYa:hover{border-color:var(--success-bright);color:var(--success-bright);transform:translateY(-1px)}
.action-row .approve:hover{filter:brightness(1.08);transform:translateY(-1px);color:#fff;border-color:transparent}.action-row .revise{background:rgba(224,168,58,.12);color:var(--p-yellow);border-color:rgba(224,168,58,.35)}.action-row .revise:hover{background:var(--p-yellow);color:#fff;border-color:var(--p-yellow);transform:translateY(-1px)}.action-row .reject{background:var(--p-red);color:#fff;box-shadow:0 6px 16px -6px rgba(200,59,59,.5)}.btn.reject:hover{border-color:var(--p-red);color:var(--p-red);transform:translateY(-1px)}
.action-row .reject:hover{filter:brightness(1.08);transform:translateY(-1px);color:#fff;border-color:transparent}
.action-row .confirm-archive{background:transparent;border-color:var(--p-accent,var(--gold-bright));color:var(--p-accent,var(--gold-bright))}
.action-row .confirm-archive:hover{background:var(--gold-dim);filter:none;transform:translateY(-1px)}
.action-row button:active{transform:scale(.96)}.btn-batalkan-permintaan{background:rgba(198,40,40,.1)!important;border-color:rgba(198,40,40,.35)!important;color:var(--p-red)}.btn-batalkan-permintaan:hover{background:var(--p-red)!important;color:#fff!important;border-color:var(--p-red)!important;filter:brightness(1.08)}.btn-edit-permintaan{background:rgba(22,131,75,.1)!important;border-color:rgba(22,131,75,.35)!important;color:var(--p-green)}.btn-edit-permintaan:hover{background:var(--p-green)!important;color:#fff!important;border-color:var(--p-green)!important;filter:brightness(1.08)}
.btn-revisi-permintaan{background:rgba(217,146,11,.14)!important;border-color:rgba(217,146,11,.4)!important;color:var(--gold-solid)}
.btn-revisi-permintaan:hover{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid))!important;color:var(--on-gold)!important;border-color:transparent!important;filter:brightness(1.08)}#permintaan-laporan .request-table .action-row .detail-btn{min-width:58px;text-align:center}.danpus-request-panel{background:var(--p-surface);border:1px solid var(--p-border);border-radius:12px;padding:20px;margin:0 0 40px;box-shadow:var(--p-shadow)}.danpus-request-panel .request-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:16px}.danpus-request-panel .request-head h2{font-family:var(--display);font-size:19px;margin:0;color:var(--p-text)}.danpus-request-panel .request-head p{margin:5px 0 0;font-size:12px;color:var(--p-muted);line-height:1.5}.danpus-request-panel .request-table-wrap{overflow-x:auto}.danpus-request-panel .request-table{width:100%;border-collapse:collapse;min-width:760px;table-layout:fixed}.danpus-request-panel .request-subject{white-space:normal;word-break:break-word}.danpus-request-panel .request-muted{white-space:normal;word-break:break-word}.danpus-request-panel .request-table th:not(:nth-child(2)),.danpus-request-panel .request-table td:not(:nth-child(2)){text-align:center}.danpus-request-panel .request-table .action-row{justify-content:center}.danpus-request-panel .request-table th{font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:var(--p-muted);text-align:left;padding:11px 12px;border-bottom:1px solid var(--p-border);white-space:nowrap}.danpus-request-panel .request-table td{padding:13px 12px;border-bottom:1px solid var(--p-border);font-size:12px;color:var(--p-text);vertical-align:middle}.danpus-request-panel .request-table tbody tr.request-row:last-of-type td{border-bottom:0}.danpus-request-panel .request-table tbody tr.request-task-row:last-child td{border-bottom:0}.danpus-request-panel .request-subject{font-weight:800}.danpus-request-panel .request-muted{font-size:10px;color:var(--p-muted);margin-top:3px}.danpus-request-panel .request-deadline{font-weight:700}.danpus-request-panel .request-deadline.late{color:var(--p-red)}.danpus-request-panel .request-deadline.soon{color:var(--p-yellow)}/* backdrop-filter:blur() SENGAJA dihapus di overlay ini. Halaman di
   belakang berat (2 canvas Chart.js + tabel per Satlak + grid kartu, plus
   .sidebar/.topbar yang sendirinya blur:12px) -- blur di atas semua itu
   dihitung ulang tiap frame begitu .danpus-request-form-card di-scroll,
   jadi biang lag pas scroll modal. Diganti backdrop gelap solid saja;
   kontras tetap terjaga, scroll jadi mulus. */
.danpus-request-modal{position:fixed;inset:0;background:rgba(2,4,6,.72);display:flex;align-items:center;justify-content:center;padding:24px;z-index:100070;box-sizing:border-box;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease}.danpus-request-modal.open{opacity:1;visibility:visible;pointer-events:auto}.danpus-request-form-card{width:min(620px,calc(100vw - 40px));max-height:88vh;display:flex;flex-direction:column;overflow:hidden;position:relative;background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px;padding:24px 24px 0;box-shadow:0 1px 0 rgba(255,255,255,.02) inset,0 32px 80px rgba(0,0,0,.5);box-sizing:border-box;transform:translateY(14px) scale(.97);transition:transform .2s ease}.danpus-request-modal.open .danpus-request-form-card{transform:translateY(0) scale(1)}.danpus-request-form-card>form{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}.danpus-request-form-card>.danpus-request-form-actions{flex-shrink:0}.danpus-request-form-body{flex:1;min-height:0;overflow-y:auto;overflow-x:hidden;overscroll-behavior:contain;padding-bottom:6px}.danpus-request-form-head{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:18px;flex-shrink:0}.danpus-request-form-head h3{font-family:var(--display);font-size:18px;margin:0;color:var(--p-text)}.danpus-request-close{flex-shrink:0;display:flex;align-items:center;justify-content:center;border:1px solid var(--p-border);background:transparent;color:var(--p-muted);width:36px;height:36px;border-radius:9px;cursor:pointer;line-height:1;transition:border-color .2s ease,color .2s ease,transform .2s ease}.danpus-request-close:hover{border-color:var(--p-red);color:var(--p-red);transform:rotate(90deg)}.danpus-request-close svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;}.danpus-request-form-grid{display:flex;flex-direction:column;flex:1;min-height:0}.danpus-request-field{display:flex;flex-direction:column;gap:5px}.danpus-request-field.full{grid-column:1/-1}.danpus-request-field label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--p-muted)}.danpus-request-field input,.danpus-request-field textarea,.danpus-request-field select{width:100%;box-sizing:border-box;border:1px solid var(--p-border);border-radius:10px;background:var(--p-surface-2);color:var(--p-text);padding:9px 10px;font:inherit;font-size:12px;outline:none}.danpus-request-field textarea{min-height:82px;resize:none}.danpus-request-field input:focus,.danpus-request-field textarea:focus,.danpus-request-field select:focus{border-color:var(--p-accent);box-shadow:0 0 0 3px rgba(201,122,0,.1)}
.danpus-deadline-split{display:flex;gap:12px}
.danpus-deadline-part{flex:1;min-width:0;display:flex;flex-direction:column;gap:5px}
.danpus-deadline-sublabel{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--p-muted)}
.danpus-picker{position:relative}
.danpus-picker-input{padding-right:34px!important;cursor:pointer;caret-color:transparent;min-height:38px;box-sizing:border-box}
.danpus-picker-input::placeholder{color:var(--p-muted);opacity:.8}
.danpus-picker-icon{position:absolute;top:50%;right:5px;transform:translateY(-50%);width:26px;height:26px;display:flex;align-items:center;justify-content:center;border:0;background:transparent;color:var(--p-muted);cursor:pointer;border-radius:6px;transition:color .15s ease,background .15s ease}
.danpus-picker-icon svg{width:15px;height:15px}
.danpus-picker-icon:hover{color:var(--p-accent);background:var(--p-surface)}
.danpus-picker-backdrop{position:fixed;inset:0;z-index:100075;background:rgba(2,4,6,.55);opacity:0;visibility:hidden;transition:opacity .15s ease,visibility .15s ease}
.danpus-picker-backdrop.open{opacity:1;visibility:visible}
.danpus-calendar{position:fixed;top:50%;left:50%;z-index:100080;background:var(--p-surface);border:1px solid var(--p-border);border-radius:14px;box-shadow:0 20px 50px -8px rgba(0,0,0,.45);opacity:0;visibility:hidden;transform:translate(-50%,-50%) scale(.94);transition:opacity .15s ease,transform .15s ease,visibility .15s ease}
.danpus-calendar.open{opacity:1;visibility:visible;transform:translate(-50%,-50%) scale(1)}
.danpus-picker-actions{display:flex;gap:8px;margin-top:14px}
.danpus-picker-close{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;border:1px solid var(--p-border);border-radius:10px;background:var(--p-surface-2);color:var(--p-text);padding:9px 13px;font-size:11px;font-weight:700;cursor:pointer;transition:background .15s ease,border-color .15s ease,transform .15s ease}
.danpus-picker-close:hover{background:var(--p-surface);border-color:var(--p-accent);transform:translateY(-1px)}
.danpus-picker-confirm{flex:1;min-width:0;display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:10px;background:var(--p-accent);color:#fff;padding:9px 13px;font-size:11px;font-weight:700;cursor:pointer;transition:filter .15s ease,transform .15s ease}
.danpus-picker-confirm:hover:not(:disabled){filter:brightness(1.06);transform:translateY(-1px)}
.danpus-picker-confirm:active:not(:disabled){transform:scale(.97)}
.danpus-picker-confirm:disabled{opacity:.4;cursor:not-allowed}
.danpus-calendar{width:240px;padding:16px 14px 14px}
.danpus-calendar-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.danpus-calendar-title{font-size:11.5px;font-weight:800;color:var(--p-text)}
.danpus-calendar-nav{border:0;background:var(--p-surface-2);color:var(--p-muted);width:24px;height:24px;border-radius:7px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s ease,color .15s ease}
.danpus-calendar-nav svg{width:12px;height:12px}
.danpus-calendar-nav:hover{background:var(--p-accent);color:#fff}
.danpus-calendar-weekdays{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:4px}
.danpus-calendar-weekdays span{text-align:center;font-size:9px;font-weight:700;color:var(--p-muted);text-transform:uppercase}
.danpus-calendar-days{display:grid;grid-template-columns:repeat(7,1fr);gap:2px}
.danpus-calendar-day{display:flex;align-items:center;justify-content:center;height:28px;border-radius:8px;font-size:11px;color:var(--p-text);cursor:pointer;border:1px solid transparent;transition:background .15s ease,color .15s ease,border-color .15s ease}
.danpus-calendar-day:hover{background:var(--p-surface-2);border-color:var(--p-border)}
.danpus-calendar-day.is-muted{color:var(--p-muted);opacity:.35;pointer-events:none}
.danpus-calendar-day.is-disabled{color:var(--p-muted);opacity:.3;cursor:not-allowed;pointer-events:none}
.danpus-calendar-day.is-today{border-color:var(--p-accent);font-weight:800}
.danpus-calendar-day.is-selected{background:var(--p-accent);color:#fff;font-weight:800;border-color:var(--p-accent)}
.danpus-clock-panel{position:fixed;top:50%;left:50%;z-index:100080;background:var(--p-surface);border:1px solid var(--p-border);border-radius:14px;box-shadow:0 20px 50px -8px rgba(0,0,0,.45);opacity:0;visibility:hidden;transform:translate(-50%,-50%) scale(.94);transition:opacity .15s ease,transform .15s ease,visibility .15s ease;width:210px;padding:16px 14px 14px}
.danpus-clock-panel.open{opacity:1;visibility:visible;transform:translate(-50%,-50%) scale(1)}
.danpus-wheel-row{position:relative;display:flex;align-items:center;justify-content:center;gap:4px;height:180px}
.danpus-wheel-highlight{position:absolute;top:50%;left:0;right:0;height:36px;transform:translateY(-50%);border-top:1px solid var(--p-border);border-bottom:1px solid var(--p-border);background:var(--p-surface-2);border-radius:8px;pointer-events:none;z-index:0}
.danpus-wheel{position:relative;z-index:1;width:56px;height:180px;overflow-y:auto;scroll-snap-type:y mandatory;scrollbar-width:none;-ms-overflow-style:none}
.danpus-wheel::-webkit-scrollbar{display:none}
.danpus-wheel-pad{height:72px}
.danpus-wheel-item{height:36px;display:flex;align-items:center;justify-content:center;scroll-snap-align:center;font-size:14px;font-weight:700;font-variant-numeric:tabular-nums;color:var(--p-muted);cursor:pointer;transition:color .15s ease,font-size .15s ease}
.danpus-wheel-item.is-center{color:var(--p-text);font-size:20px;font-weight:800}
.danpus-wheel-colon{position:relative;z-index:1;font-size:18px;font-weight:800;color:var(--p-muted)}
/* Validasi wajib-diisi custom (senada sama form login) -- ganti tooltip
   bawaan browser jadi pesan Bahasa Indonesia + border merah, dan otomatis
   ke-reset begitu penggunanya mulai isi ulang field itu lagi. */
.danpus-request-field input.field-invalid,.danpus-request-field textarea.field-invalid,.danpus-deadline-part input.field-invalid{border-color:var(--p-red)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--p-red) 15%,transparent)}
.danpus-picker:has(.field-invalid) .danpus-picker-input{border-color:var(--p-red)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--p-red) 15%,transparent)}
.danpus-request-field:has(#danpusRequestSatuanProxy.field-invalid) .danpus-request-check{border-color:var(--p-red);box-shadow:0 0 0 2px color-mix(in srgb,var(--p-red) 12%,transparent)}
.danpus-request-field:has(#danpusRequestSatuanProxy.field-invalid) .danpus-request-kategori-chip{border-color:var(--p-red)}
.danpus-request-error{display:flex;align-items:center;gap:6px;margin-top:5px;font-size:10.5px;color:var(--p-red);animation:danpusErrorIn .2s ease}
.danpus-request-error::before{content:"";width:13px;height:13px;flex-shrink:0;border-radius:50%;background:var(--p-red);-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='12' y1='8' x2='12' y2='13'/%3E%3Ccircle cx='12' cy='16.5' r='.6' fill='%23000' stroke='none'/%3E%3Ccircle cx='12' cy='12' r='9.3'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='12' y1='8' x2='12' y2='13'/%3E%3Ccircle cx='12' cy='16.5' r='.6' fill='%23000' stroke='none'/%3E%3Ccircle cx='12' cy='12' r='9.3'/%3E%3C/svg%3E") center/contain no-repeat}
@keyframes danpusRowFlash{0%{background:color-mix(in srgb,var(--gold-solid-bright) 22%,transparent)}100%{background:transparent}}.danpus-report-dropdown.row-flash{animation:danpusRowFlash 2.2s ease}
@keyframes danpusErrorIn{from{opacity:0;transform:translateY(-3px)}to{opacity:1;transform:translateY(0)}}.request-eyebrow{font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--p-accent);margin-bottom:6px}.satuan-pill{display:inline-flex;align-items:center;border-radius:8px;padding:4px 9px;font-size:10px;font-weight:800;letter-spacing:.03em;color:var(--p-accent);background:rgba(201,122,0,.1);border:1px solid rgba(201,122,0,.22);white-space:nowrap}.request-deadline{display:inline-flex;align-items:center;gap:5px;font-weight:700}.request-deadline svg{width:13px;height:13px;flex-shrink:0;opacity:.75}.priority-tag{display:inline-flex;align-items:center;border-radius:999px;padding:5px 10px;font-size:10px;font-weight:800;border:1px solid transparent;white-space:nowrap}.status-badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 10px;font-size:10px;font-weight:800;border:1px solid transparent;white-space:nowrap;letter-spacing:.02em}.status-badge.status-menunggu{color:#FF9800;background:rgba(224,168,58,.13);border-color:rgba(224,168,58,.35)}.status-badge.status-dikonfirmasi{color:#2e9e68;background:rgba(61,186,126,.12);border-color:rgba(61,186,126,.35)}.danpus-request-form-head p{margin:5px 0 0;font-size:12px;color:var(--p-muted);line-height:1.5}.danpus-request-field-headrow{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:1px}.danpus-request-selectall{border:1px solid var(--gold-solid-bright);background:color-mix(in srgb,var(--gold-solid-bright) 10%,transparent);color:var(--gold-solid-bright);font-size:10px;font-weight:800;letter-spacing:.03em;cursor:pointer;padding:6px 12px;border-radius:8px}.danpus-request-selectall:hover{background:color-mix(in srgb,var(--gold-solid-bright) 18%,transparent)}.danpus-request-check-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}.danpus-request-kategori-quickselect{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}.danpus-request-kategori-chip{border:1px solid var(--p-border);background:var(--p-surface-2);color:var(--p-muted);border-radius:999px;padding:6px 12px;font-size:10.5px;font-weight:700;cursor:pointer;transition:border-color .15s ease,background .15s ease,color .15s ease}.danpus-request-kategori-chip:hover{border-color:color-mix(in srgb,var(--gold-solid-bright) 35%,transparent)}.danpus-request-kategori-chip.is-active{border-color:var(--gold-solid-bright);background:color-mix(in srgb,var(--gold-solid-bright) 14%,transparent);color:var(--gold-solid-bright)}.danpus-request-satuan-groups{display:flex;flex-direction:column;gap:14px;margin-top:11px}.danpus-request-satuan-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.danpus-request-satuan-row .danpus-request-check-grid{grid-template-columns:1fr}.danpus-request-satuan-group-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:7px}.danpus-request-satuan-group-title{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--p-muted)}.danpus-request-check{position:relative;display:flex;align-items:center;gap:9px;padding:9px 10px;border:1px solid var(--p-border);border-radius:10px;background:var(--p-surface-2);font-size:11px;color:var(--p-text);cursor:pointer;transition:border-color .15s ease,background .15s ease}.danpus-request-check:hover{border-color:color-mix(in srgb,var(--gold-solid-bright) 35%,transparent);background:color-mix(in srgb,var(--gold-solid-bright) 4%,transparent)}.danpus-request-check input{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none}.danpus-request-check .check-mark{display:flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:5px;border:1.5px solid var(--p-border);background:var(--p-surface);color:transparent;flex-shrink:0;transition:background .15s ease,border-color .15s ease,color .15s ease}.danpus-request-check .check-mark svg{width:11px;height:11px}.danpus-request-check .check-label{font-size:11px;line-height:1.3}.danpus-request-check:has(input:checked){border-color:var(--gold-solid-bright);background:color-mix(in srgb,var(--gold-solid-bright) 12%,transparent)}.danpus-request-check:has(input:checked) .check-mark{background:var(--gold-solid-bright);border-color:var(--gold-solid-bright);color:var(--on-gold)}.priority-toggle{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}.priority-option{position:relative;display:flex;align-items:center;justify-content:center;border:1px solid var(--p-border);border-radius:10px;padding:9px 6px;font-size:11px;font-weight:700;color:var(--p-muted);background:var(--p-surface-2);cursor:pointer;transition:border-color .15s ease,background .15s ease,color .15s ease}.priority-option input{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none}.priority-option.prio-rendah:hover{border-color:#8b5cf6;color:#8b5cf6}.priority-option.prio-sedang:hover{border-color:#a855f7;color:#a855f7}.priority-option.prio-tinggi:hover{border-color:#6d28d9;color:#6d28d9}.priority-option.prio-rendah:has(input:checked){border-color:#8b5cf6;background:#8b5cf6;color:#fff}.priority-option.prio-sedang:has(input:checked){border-color:#a855f7;background:#a855f7;color:#fff}.priority-option.prio-tinggi:has(input:checked){border-color:#6d28d9;background:#6d28d9;color:#fff}.danpus-request-form-actions{display:flex;justify-content:flex-end;align-items:center;gap:8px;margin-top:0;padding:14px 0 20px;border-top:1px solid var(--p-border);background:var(--p-surface);flex-shrink:0}.danpus-request-form-actions-spacer{flex:1 1 auto}.danpus-wizard-indicator{display:flex;align-items:stretch;margin-bottom:18px;flex-shrink:0;width:100%;border-radius:14px;overflow:hidden;background:var(--p-surface-2)}.danpus-request-cat-icon{display:none}.danpus-request-kategori-chip{display:inline-flex;align-items:center;gap:6px}.danpus-request-satuan-group-head{display:flex;align-items:center;gap:8px}.danpus-request-check .check-icon{display:none}.danpus-request-check .check-mark{margin-left:auto}.danpus-request-selectall{display:inline-flex;align-items:center;gap:4px}.danpus-request-selectall svg{display:none}.danpus-wizard-indicator{--wiz-notch:18px}.danpus-wizard-step-item{display:flex;flex-direction:row;align-items:center;gap:8px;flex:1 1 0;min-width:0;cursor:default;padding:15px 16px 15px calc(var(--wiz-notch) + 14px);background:var(--p-surface-2);clip-path:polygon(0 0,calc(100% - var(--wiz-notch)) 0,100% 50%,calc(100% - var(--wiz-notch)) 100%,0 100%,var(--wiz-notch) 50%);margin-left:calc(var(--wiz-notch) * -1);transition:background .2s ease}.danpus-wizard-step-item:first-child{margin-left:0;padding-left:20px;clip-path:polygon(0 0,calc(100% - var(--wiz-notch)) 0,100% 50%,calc(100% - var(--wiz-notch)) 100%,0 100%)}.danpus-wizard-step-item:last-child{clip-path:polygon(0 0,100% 0,100% 100%,0 100%,var(--wiz-notch) 50%)}.danpus-wizard-step-item[data-step-indicator]{transition:opacity .15s ease}.danpus-wizard-step-text{display:flex;flex-direction:column;gap:2px;min-width:0}.danpus-wizard-step-title{font-size:13px;font-weight:800;color:var(--p-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:color .2s ease}.danpus-wizard-step-desc{font-size:10.5px;color:var(--p-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:color .2s ease}.danpus-wizard-circle{position:relative;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;border:1.5px solid var(--p-border);color:var(--p-muted);background:var(--p-surface);transition:background .2s ease,border-color .2s ease,color .2s ease;flex-shrink:0}.danpus-wizard-circle svg{width:14px;height:14px;display:none}.danpus-wizard-line{display:none}.danpus-wizard-step-item.is-active{background:linear-gradient(135deg,#ffd88a,var(--gold-solid-bright));z-index:1}.danpus-wizard-step-item.is-active .danpus-wizard-circle{border-color:transparent;color:var(--on-gold);background:color-mix(in srgb,#000 18%,var(--gold-solid-bright))}.danpus-wizard-step-item.is-active .danpus-wizard-step-title{color:#241503}.danpus-wizard-step-item.is-active .danpus-wizard-step-desc{color:color-mix(in srgb,#241503 70%,transparent)}.danpus-wizard-step-item.is-done{cursor:pointer;background:color-mix(in srgb,var(--p-green) 10%,var(--p-surface-2))}.danpus-wizard-step-item.is-done .danpus-wizard-circle{border-color:var(--p-green);background:var(--p-green);color:#fff}.danpus-wizard-step-item.is-done .danpus-wizard-circle .danpus-wizard-num{display:none}.danpus-wizard-step-item.is-done .danpus-wizard-circle svg{display:block}.danpus-wizard-step-item.is-done .danpus-wizard-step-title{color:var(--p-text)}.danpus-step{display:none;width:100%}.danpus-step.is-active{display:flex;flex-direction:column;gap:11px;width:100%}@media(max-width:700px){.danpus-request-panel{padding:15px}.danpus-request-panel .request-head{display:block}.danpus-request-panel #danpusOpenRequestForm{margin-top:12px}.danpus-wizard-indicator{--wiz-notch:10px;border-radius:12px}.danpus-wizard-step-item{gap:6px;padding-top:11px;padding-bottom:11px;padding-right:8px}.danpus-wizard-step-item:first-child{padding-left:12px}.danpus-wizard-circle{width:24px;height:24px;font-size:10.5px;border-width:1.5px;flex-shrink:0}.danpus-wizard-step-text{gap:0}.danpus-wizard-step-title{font-size:10px;line-height:1.25}.danpus-wizard-step-desc{display:none}.danpus-request-check-grid{grid-template-columns:1fr}.danpus-request-satuan-row{grid-template-columns:1fr}.priority-toggle{grid-template-columns:1fr}.danpus-request-form-card{width:min(100%,calc(100vw - 24px));max-height:86vh;padding:17px 17px 0;border-radius:18px}.danpus-request-modal{padding:18px 12px}}
.danpus-request-form-card{width:min(900px,calc(100vw - 40px))}
.danpus-request-form-head-main{display:flex;align-items:flex-start;gap:13px;min-width:0}
.danpus-modal-icon{flex-shrink:0;width:44px;height:44px;border-radius:12px;background:color-mix(in srgb,var(--gold-solid-bright) 16%,transparent);color:var(--gold-solid-bright);display:flex;align-items:center;justify-content:center}
.danpus-modal-icon svg{width:21px;height:21px}
.danpus-request-form-head h3{font-size:19px}
.danpus-section-panel{border:1px solid var(--p-border);border-radius:14px;background:var(--p-surface-2);padding:16px;display:flex;flex-direction:column;gap:13px}
.danpus-step .danpus-section-panel+.danpus-section-panel{margin-top:14px}
.danpus-section-head{display:flex;align-items:flex-start;gap:12px}
.danpus-section-icon{flex-shrink:0;width:38px;height:38px;border-radius:10px;background:color-mix(in srgb,var(--gold-solid-bright) 16%,transparent);color:var(--gold-solid-bright);display:flex;align-items:center;justify-content:center}
.danpus-section-icon svg{width:18px;height:18px}
.danpus-section-title{margin:0;font-size:14px;font-weight:800;color:var(--p-text)}
.danpus-section-desc{margin:3px 0 0;font-size:11.5px;color:var(--p-muted);line-height:1.4}
.danpus-section-body{display:flex;flex-direction:column;gap:12px}
.danpus-section-empty{margin:0;font-size:11.5px;color:var(--p-muted)}
.danpus-request-field .danpus-request-check{font-weight:400;text-transform:none;letter-spacing:normal;color:var(--p-text)}
.danpus-request-field .danpus-request-check .check-label{font-weight:400;font-size:11.5px;text-transform:none}
.danpus-request-satuan-group-title{font-weight:800}
#danpusStepNext{display:inline-flex;align-items:center;gap:6px}
@media(max-width:700px){
.danpus-section-panel{padding:13px}
}

.side-nav-group{margin:0}.side-nav-group-title{width:100%;display:flex;align-items:center;gap:10px;padding:10px 12px;margin:2px 0;border:1px solid transparent;border-radius:9px;background:transparent;color:var(--text-muted);font-family:var(--body);font-size:13.5px;font-weight:600;letter-spacing:normal;text-transform:none;line-height:normal;cursor:pointer;text-align:left;box-sizing:border-box;transition:background .15s ease,color .15s ease}.side-nav-group-title:hover{background:var(--hover-tint);color:var(--text)}.side-nav-group.open .side-nav-group-title{color:var(--text)}.side-nav-group-title .side-text{flex:1;}.side-nav-group-title .chevron{margin-left:auto;width:15px;height:15px;flex-shrink:0;opacity:.6;transition:transform .25s cubic-bezier(.4,0,.2,1),opacity .2s ease}.side-nav-group.open .chevron{transform:rotate(180deg);opacity:1}.side-subnav{display:grid;grid-template-rows:0fr;opacity:0;transition:grid-template-rows .3s cubic-bezier(.4,0,.2,1),opacity .25s ease;overflow:hidden}.side-subnav>div{min-height:0;padding:3px 0;margin-left:18px;border-left:1px solid var(--p-border,var(--border-soft));display:flex;flex-direction:column;gap:2px}.side-nav-group.open .side-subnav{grid-template-rows:1fr;opacity:1}.side-sub-link{position:relative;display:flex;align-items:center;gap:10px;padding:9px 12px 9px 17px;border-radius:0 9px 9px 0;color:var(--text-muted);font-family:var(--body);font-size:13px;font-weight:500;line-height:1.4;text-decoration:none;margin:0;box-sizing:border-box;transition:background .15s ease,color .15s ease}.side-sub-link:hover{background:var(--hover-tint);color:var(--text)}.side-sub-link .sub-dot{width:5px;height:5px;border-radius:50%;background:currentColor;opacity:.5;flex:0 0 auto;transition:opacity .15s ease,background .15s ease,box-shadow .15s ease}.side-sub-link.active{background:var(--gold-dim,rgba(201,122,0,.1));color:var(--p-accent);font-weight:600}.side-sub-link.active:before{content:"";position:absolute;left:-1px;top:8px;bottom:8px;width:2px;border-radius:2px;background:var(--p-accent)}.side-sub-link.active .sub-dot{background:var(--p-accent);opacity:1;box-shadow:0 0 0 3px rgba(201,122,0,.15)}
.side-subnav-label{display:none;}
/* Sidebar ciutkan: submenu grup (Log Aktivitas/Pelaporan) tidak lagi
   disembunyikan total — ditampilkan sebagai flyout mengambang di sisi
   kanan ikon grup, diposisikan lewat JS (position:fixed, lihat
   positionGroupFlyout()) karena .side-nav punya overflow-x:hidden yang
   akan memotong flyout kalau cuma pakai position:absolute biasa. */
.sidebar.collapsed .side-subnav{display:none;}
.sidebar.collapsed .side-nav-group.open .side-subnav{display:block;position:fixed;min-width:216px;background:var(--p-surface);border:1px solid var(--p-border);border-radius:12px;box-shadow:0 14px 34px rgba(0,0,0,.22);padding:8px;z-index:100020;max-height:min(420px,calc(100vh - 80px));overflow:hidden;}
/* Scroll dipindah ke <div> di dalamnya (bukan di .side-subnav langsung) supaya
   scrollbar tidak "menabrak" sudut kanan-atas/kanan-bawah kartu -- kalau
   overflow-y:auto dipasang langsung di elemen yang punya border-radius,
   scrollbar-nya kepotong lurus dan bikin sudut itu keliatan siku, bukan bulat. */
.sidebar.collapsed .side-nav-group.open .side-subnav>div{max-height:calc(min(420px,calc(100vh - 80px)) - 16px);overflow-y:auto;overflow-x:hidden;}
@media(min-width:901px){
.sidebar.collapsed .side-nav-group.open .side-subnav>div{border-left:none;margin-left:0;padding:0;}
.sidebar.collapsed .side-sub-link{padding:9px 10px;border-radius:8px;}
/* ===== Flyout Riwayat Aktivitas (#monitorGroup) -- DIROMBAK TOTAL =====
   Dulu cuma "dikecualikan" dari reset umum di atas. Sekarang seluruh
   tampilan baris submenu-nya didefinisikan ulang secara eksplisit &
   mandiri di sini (bukan sekadar mewarisi), supaya dijamin SAMA PERSIS
   dengan tampilan menu/submenu saat sidebar terbuka: ukuran font, warna,
   ketebalan+warna aksen item aktif, dan spasi antar baris. Grup lain
   (Pelaporan/Surat/Kendala Kasansi) TIDAK disentuh, tetap pakai reset
   padding:0/border-radius:8px seperti semula. */
#monitorGroup.side-nav-group.open .side-subnav>div{
  border-left:1px solid var(--p-border,var(--border-soft)); /* garis pemandu kiri, sama seperti sidebar terbuka */
  margin-left:18px;
  padding:3px 0;
  display:flex;
  flex-direction:column;
  gap:2px; /* jarak antar baris submenu -- identik dengan sidebar terbuka */
}
#monitorGroup .side-sub-link{
  padding:9px 12px 9px 17px;
  border-radius:0 9px 9px 0;
  font-family:var(--body);
  font-size:13px;       /* identik dengan submenu sidebar terbuka */
  font-weight:500;
  line-height:1.4;
  color:var(--text-muted);
}
#monitorGroup .side-sub-link:hover{background:var(--hover-tint);color:var(--text);}
#monitorGroup .side-sub-link .sub-dot{width:5px;height:5px;border-radius:50%;background:currentColor;opacity:.5;}
#monitorGroup .side-sub-link.active{background:var(--gold-dim,rgba(201,122,0,.1));color:var(--p-accent);font-weight:600;}
#monitorGroup .side-sub-link.active:before{content:"";position:absolute;left:-1px;top:8px;bottom:8px;width:2px;border-radius:2px;background:var(--p-accent);} /* aksen: tebal 2px, warna gold -- sama seperti sidebar terbuka */
#monitorGroup .side-sub-link.active .sub-dot{background:var(--p-accent);opacity:1;box-shadow:0 0 0 3px rgba(201,122,0,.15);}
}
/* Scrollbar flyout -- thumb custom + tombol panah naik/turun di ujung atas & bawah */
.sidebar.collapsed .side-nav-group.open .side-subnav>div::-webkit-scrollbar{width:11px;}
.sidebar.collapsed .side-nav-group.open .side-subnav>div::-webkit-scrollbar-track{background:var(--hover-tint,rgba(0,0,0,.05));border-radius:99px;margin:14px 1px;}
.sidebar.collapsed .side-nav-group.open .side-subnav>div::-webkit-scrollbar-thumb{background:var(--p-muted,var(--border-soft));border-radius:99px;border:3px solid var(--p-surface);background-clip:padding-box;}
.sidebar.collapsed .side-nav-group.open .side-subnav>div::-webkit-scrollbar-thumb:hover{background:var(--p-accent);}
.sidebar.collapsed .side-nav-group.open .side-subnav>div::-webkit-scrollbar-button{display:block;height:14px;background-color:transparent;background-repeat:no-repeat;background-position:center;background-size:9px 9px;transition:background-color .15s ease;}
.sidebar.collapsed .side-nav-group.open .side-subnav>div::-webkit-scrollbar-button:vertical:start:increment,
.sidebar.collapsed .side-nav-group.open .side-subnav>div::-webkit-scrollbar-button:vertical:end:decrement{display:none;}
.sidebar.collapsed .side-nav-group.open .side-subnav>div::-webkit-scrollbar-button:vertical:start:decrement{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 15 12 9 18 15'/%3E%3C/svg%3E");}
.sidebar.collapsed .side-nav-group.open .side-subnav>div::-webkit-scrollbar-button:vertical:end:increment{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");}
.sidebar.collapsed .side-nav-group.open .side-subnav>div::-webkit-scrollbar-button:vertical:start:decrement:hover{background-color:var(--hover-tint);background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23FF9800' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 15 12 9 18 15'/%3E%3C/svg%3E");}
.sidebar.collapsed .side-nav-group.open .side-subnav>div::-webkit-scrollbar-button:vertical:end:increment:hover{background-color:var(--hover-tint);background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23FF9800' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");}
/* Preferensi "sidebar ciutkan" disimpan di localStorage dan tetap kepasang
   walau jendela sekarang sudah sempit (sidebar berubah jadi off-canvas via
   hamburger, .collapsed itu fitur desktop doang) -- tanpa override ini,
   flyout submenu position:fixed z-index:100020 di atas bisa nyangkut
   ketimpa di sudut kiri-atas layar (karena posisinya dihitung dari sidebar
   yang lagi translateX(-100%), jadi hitungannya ngaco) dan NUTUPIN tombol
   hamburger (.menu-btn) sampai kelihatan kayak gak merespons klik sama
   sekali. Balikin ke tampilan accordion normal (bukan flyout) di lebar
   ini, apapun status .collapsed-nya. */
@media(max-width:900px){
  .sidebar.collapsed .side-subnav{display:grid}
  .sidebar.collapsed .side-nav-group.open .side-subnav{position:static;top:auto!important;left:auto!important;min-width:0;background:none;border:0;box-shadow:none;padding:0;z-index:auto}
  .sidebar.collapsed .side-subnav>div{margin-left:18px;border-left:1px solid var(--p-border,var(--border-soft));padding:3px 0;display:flex;flex-direction:column;gap:2px}
  .sidebar.collapsed .side-subnav-label{display:none}
}
.sidebar.collapsed .side-subnav-label{display:block;font-family:var(--mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--p-muted);padding:4px 10px 8px;}
.sidebar.collapsed .side-nav-group.has-active-child .side-nav-group-title{color:var(--p-accent);background:var(--gold-dim,rgba(201,122,0,.1));}
.sidebar.collapsed .side-foot{padding:14px 10px 20px;}
.side-nav-label,.side-text,.chevron{transition:opacity .15s ease;}
.side-foot form.logout button{text-transform:none;font-family:var(--body);font-size:13.5px;font-weight:500;letter-spacing:normal;}
.report-modal{position:fixed;inset:0;background:rgba(15,23,42,.48);display:flex;align-items:center;justify-content:center;padding:20px;z-index:1000;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease}.report-modal.open{opacity:1;visibility:visible;pointer-events:auto}.report-modal-card{width:min(760px,100%);max-height:90vh;overflow:auto;background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px;padding:22px;box-shadow:0 25px 70px rgba(15,23,42,.22);box-sizing:border-box;transform:translateY(14px) scale(.97);transition:transform .2s ease}#editDeadlinePermintaanModal .report-modal-card{max-height:98vh}.report-modal.open .report-modal-card{transform:translateY(0) scale(1)}.report-modal-head{display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:18px}.report-modal-head h3{margin:0;font-family:var(--display);font-size:20px}.report-modal-close{flex-shrink:0;width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:1px solid var(--p-border);background:transparent;color:var(--p-muted);cursor:pointer;transition:border-color .2s ease,color .2s ease,transform .2s ease}.report-modal-close:hover{border-color:var(--p-red);color:var(--p-red);transform:rotate(90deg);}.report-modal-close svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;}.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.detail-item{padding:12px;border:1px solid var(--p-border);border-radius:9px;background:var(--p-surface-2)}.detail-item.full{grid-column:1/-1}.detail-label{font-size:9px;text-transform:uppercase;color:var(--p-muted);font-weight:800;letter-spacing:.06em;margin-bottom:5px}.detail-value{font-size:12px;line-height:1.65;white-space:pre-wrap;color:var(--p-text)}.modal-actions{display:flex;justify-content:flex-end;margin-top:16px}.readonly-note{font-size:10px;color:var(--p-muted);background:var(--p-surface-2);border:1px solid var(--p-border);border-radius:8px;padding:8px 10px}.lampiran-btn{display:inline-flex;align-items:center;gap:7px;border:1px solid color-mix(in srgb,var(--p-accent) 45%,var(--p-border));background:color-mix(in srgb,var(--p-accent) 10%,var(--p-surface));color:var(--p-accent);border-radius:8px;padding:8px 12px;font-size:11.5px;font-weight:700;text-decoration:none;cursor:pointer;transition:background .15s ease,transform .15s ease;margin-bottom:6px}.lampiran-btn:last-child{margin-bottom:0}.lampiran-btn:hover{background:color-mix(in srgb,var(--p-accent) 20%,var(--p-surface));transform:translateY(-1px)}.lampiran-btn svg{width:15px;height:15px;flex-shrink:0}
.btn-ghost-gold{color:var(--p-accent,var(--gold-bright));border-color:rgba(217,146,11,.4);}
.btn-ghost-gold:hover{border-color:var(--p-accent,var(--gold-bright));color:var(--p-accent,var(--gold-bright));background:rgba(217,146,11,.08);}
.confirm-actions-fit{justify-content:center;}
.confirm-actions-fit .btn{flex:none;padding:9px 20px;white-space:normal;}
@media(max-width:1150px){.pimp-kpis{grid-template-columns:repeat(2,1fr)}.satlak-grid{grid-template-columns:repeat(2,1fr)}.chart-grid{grid-template-columns:1fr}.chart-grid.balanced{grid-template-columns:1fr}}@media(max-width:700px){.pimp-kpis,.satlak-grid,.detail-grid{grid-template-columns:1fr}.section-block{padding:15px}.pimp-hero{padding:20px}.pimp-hero h1{font-size:25px}.section-head-clean{display:block}.detail-item.full{grid-column:auto}.chart-card{padding:15px}.chart-box,.chart-card.compact .chart-box{height:230px}.chart-box-scroll-y{height:400px}}
.request-table tbody tr.request-row{cursor:pointer}
.request-row-caret{display:inline-block;margin-right:6px;font-size:10px;color:var(--p-muted);transition:transform .15s ease}
.request-row.open .request-row-caret,.archive-request-row.open .request-row-caret{transform:rotate(90deg)}
.request-task-row td{padding:0!important;border-top:none!important}
.request-task-track{padding:14px 18px 16px 34px;background:var(--p-surface-2);display:flex;flex-wrap:wrap;row-gap:10px}
.request-task-step{position:relative;display:flex;align-items:center;gap:6px;border:0;cursor:default;padding:7px 20px 7px 18px;margin-right:-11px;font:inherit;font-size:10.5px;font-weight:700;white-space:nowrap;color:var(--p-muted);transition:color .15s ease;z-index:1}
.request-task-step::before{content:"";position:absolute;inset:0;z-index:-1;background:var(--p-border);clip-path:polygon(0 0,calc(100% - 13px) 0,100% 50%,calc(100% - 13px) 100%,0 100%,13px 50%);transition:background .15s ease}
.request-task-step:first-child::before{clip-path:polygon(0 0,calc(100% - 13px) 0,100% 50%,calc(100% - 13px) 100%,0 100%)}
.request-task-step:last-child::before{clip-path:polygon(0 0,100% 0,100% 100%,0 100%,13px 50%)}
.request-task-step:first-child:last-child::before{clip-path:none;border-radius:8px}
.request-task-num{flex-shrink:0;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9.5px;font-weight:800;background:var(--p-surface);color:inherit}
.request-task-label{overflow:hidden;text-overflow:ellipsis;max-width:170px}
.request-task-step.active{color:#fff;z-index:2}
.request-task-step.active::before{background:var(--p-accent);box-shadow:0 4px 12px -4px rgba(201,122,0,.55)}
.request-task-step.active .request-task-num{background:#fff;color:var(--p-accent)}
.request-task-step.done{color:#8a7245}
.request-task-step.done::before{background:#efe6d2}
.request-task-step.done .request-task-num{background:var(--p-green);color:#fff}
.request-task-step.clickable{cursor:pointer}
.request-task-step.clickable:hover::before{filter:brightness(1.06)}
.request-task-step.clickable:focus-visible{outline:2px solid var(--p-accent);outline-offset:2px}
.request-task-step.is-selected .request-task-num{box-shadow:0 0 0 2px var(--p-accent)}
@media(max-width:640px){.request-task-step{margin-right:0;padding:7px 12px}.request-task-step::before{clip-path:none!important;border-radius:8px}}
#danpusRequestTaskList{display:flex;flex-direction:column;gap:8px}
.danpus-task-row{display:flex;flex-direction:column;gap:7px}.danpus-task-row-inputs{display:flex;gap:8px;align-items:center}
.danpus-task-row+.danpus-task-row{margin-top:14px;padding-top:14px;border-top:1px dashed var(--p-border)}
.danpus-task-row input{flex:1}
.danpus-task-detail{min-height:52px}
.danpus-task-remove{flex-shrink:0;width:30px;height:30px;border-radius:8px;border:1px solid var(--p-border);background:transparent;color:var(--p-muted);cursor:pointer;font-size:16px;line-height:1}
.danpus-task-remove:hover{border-color:var(--p-red);color:var(--p-red)}

/* ─── KCARD: Card-based Kendala Kasansi (sisi penerima / pimpinan) ────── */
.kcard-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;padding:4px 0 12px;}
.kcard{background:var(--p-surface);border:1px solid var(--p-border);border-radius:14px;padding:22px;display:flex;flex-direction:column;gap:16px;min-height:200px;box-shadow:var(--p-shadow);transition:border-color .2s ease,box-shadow .2s ease,transform .15s ease;}
.kcard:hover{border-color:var(--p-accent,#c97a00);box-shadow:0 4px 20px rgba(0,0,0,.1);transform:translateY(-2px);}
.kcard-header{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;}
.kcard-meta{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.kcard-body{display:flex;flex-direction:column;gap:4px;flex:1;justify-content:center;}
.kcard-icon{width:42px;height:42px;border-radius:12px;}
.kcard-sender{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--p-muted);}
.kcard-perihal{font-size:16px;font-weight:800;color:var(--p-text);line-height:1.4;}
.kcard-kategori{font-size:11px;color:var(--p-muted);font-style:italic;}
.kcard-confirm-info{display:flex;align-items:flex-start;gap:7px;font-size:11px;color:var(--p-muted);background:var(--p-surface-2,var(--p-surface));border:1px solid var(--p-border);border-radius:9px;padding:8px 11px;line-height:1.4;}
.kcard-confirm-info svg{width:13px;height:13px;flex-shrink:0;margin-top:1px;stroke:var(--p-green,#16834b);}
.kcard-confirm-info strong{color:var(--p-text);font-weight:700;}
.kcard-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:2px;padding-top:14px;border-top:1px solid var(--p-border);}
.kcard-date{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--p-muted);white-space:nowrap;}
.kcard-date svg{width:12px;height:12px;flex-shrink:0;opacity:.7;}
.kcard-actions{display:flex;align-items:center;gap:7px;flex-wrap:wrap;}
.kcard-btn{display:inline-flex;align-items:center;gap:5px;border-radius:8px;padding:7px 11px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;border:1px solid var(--p-border);background:var(--p-surface);color:var(--p-text);transition:border-color .15s ease,background .15s ease,transform .12s ease;}
.kcard-btn svg{width:13px;height:13px;flex-shrink:0;}
.kcard-btn:hover{transform:translateY(-1px);}
.kcard-btn:active{transform:scale(.97);}
.kcard-btn-detail:hover{border-color:var(--p-accent,#c97a00);color:var(--p-accent,#c97a00);}
.kcard-btn-approve{border-color:var(--p-green,#16834b);color:var(--p-green,#16834b);}
.kcard-btn-approve:hover{background:rgba(22,131,75,.1);}
.kcard-btn-reject{border-color:var(--p-red,#c83b3b);color:var(--p-red,#c83b3b);}
.kcard-btn-reject:hover{background:rgba(200,59,59,.1);}
.kcard-btn-archive{border-color:var(--p-green,var(--success));color:var(--p-green,var(--success));}
.kcard-btn-archive:hover{background:var(--success-dim,rgba(63,194,125,.14));}
.kcard-empty{grid-column:1/-1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:48px 20px;text-align:center;color:var(--p-muted);}
.kcard-empty-title{font-size:14px;font-weight:700;color:var(--p-muted);}
.kcard-empty-sub{font-size:12px;color:var(--p-muted);line-height:1.5;max-width:320px;opacity:.7;}
@media(max-width:640px){
  .kcard-grid{grid-template-columns:1fr;}
  .kcard-footer{flex-direction:column;align-items:flex-start;}
  .kcard-actions{width:100%;}
  .kcard-btn{flex:1;justify-content:center;}
}
</style>
</head>
<body>
<div class="profile-modal-overlay" id="profileModalOverlay"><div class="profile-modal-card" id="profileModalCard" role="dialog" aria-modal="true" aria-label="Detail profil"><button type="button" class="profile-modal-close" id="profileModalCloseBtn" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button><div class="profile-dropdown-view" id="profileSettingsView" style="display:none;"><div class="profile-modal-title">Pengaturan Akun</div><div class="profile-subtabs" role="tablist"><button type="button" class="profile-subtab-btn active" data-subtab-target="profilePhotoView" role="tab" aria-selected="true"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8.5A1.5 1.5 0 0 1 5.5 7h2l1-2h7l1 2h2A1.5 1.5 0 0 1 20 8.5v9A1.5 1.5 0 0 1 18.5 19h-13A1.5 1.5 0 0 1 4 17.5Z"></path><circle cx="12" cy="13" r="3.4"></circle></svg>Foto Profil</button><button type="button" class="profile-subtab-btn" data-subtab-target="profilePasswordView" role="tab" aria-selected="false"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2.2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>Ganti Password</button></div><div class="profile-subtab-panel active" id="profilePhotoView" role="tabpanel"><div class="profile-dropdown-head-lg"><div class="profile-dropdown-avatar-lg"><span class="profile-initial" id="profileInitialLarge" style="display:{{ $user->foto_path ? 'none' : '' }};">{{ strtoupper(mb_substr($user->name ?? 'U',0,1)) }}</span><img class="profile-photo" id="profilePhotoLarge" alt="Foto profil {{ $user->name }}" @if($user->foto_path) src="{{ asset('storage/'.$user->foto_path) }}" style="display:block;" @endif></div><div class="profile-dropdown-name">{{ $user->name }}</div><div class="profile-dropdown-role">{{ $user->jabatan ?? 'Pimpinan' }}</div></div><div class="profile-photo-actions"><form method="POST" action="{{ route('profil-foto.update') }}" enctype="multipart/form-data" id="formGantiFoto">@csrf<button type="button" class="profile-btn profile-btn-primary" id="gantiFotoBtn"><span id="gantiFotoLabel">Ganti Foto</span></button><input type="file" name="foto" id="fotoProfilInput" accept="image/png,image/jpeg,image/webp" hidden></form><button type="button" class="profile-btn profile-btn-outline" id="hapusFotoBtn" style="display:{{ $user->foto_path ? '' : 'none' }};"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>Hapus</button></div><p class="profile-photo-hint">Format JPG, PNG, atau WEBP — ukuran maksimal 10 MB.</p></div><div class="profile-subtab-panel" id="profilePasswordView" role="tabpanel">@if($permintaanGantiPasswordPending)<div class="profile-pending-state"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg><h4>Permintaan Sedang Diproses</h4><p>Permintaan ganti password kamu sudah diajukan pada {{ $permintaanGantiPasswordPending->created_at->translatedFormat('d M Y H:i') }}. Silakan tunggu keputusan Admin -- kamu bisa mengajukan permintaan baru setelah ini diputuskan.</p></div>@else<div class="profile-form-notice">Perubahan kata sandi tidak langsung berlaku. Permintaan akan dikirim ke <b>Admin</b> untuk diverifikasi terlebih dahulu.</div><form class="profile-form" id="formGantiPassword" method="POST" action="{{ route('permintaan-reset-password.store') }}">@csrf<div class="profile-form-field"><label for="passBaru">Kata Sandi Baru</label><div class="profile-field-toggle-wrap"><input type="password" id="passBaru" name="password_baru" required placeholder="Kata sandi baru"><button class="field-toggle" type="button" data-target="passBaru" aria-label="Tampilkan Password"><svg class="icon-eye" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"></path><circle cx="12" cy="12" r="3.2"></circle></svg><svg class="icon-eye-off" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"></path><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c7 0 10.5 7 10.5 7a13.6 13.6 0 0 1-3.2 4.1M6.6 6.6C3.5 8.5 1.5 12 1.5 12s3.5 7 10.5 7a10.6 10.6 0 0 0 4.2-.85"></path><path d="M9.5 9.7a3.2 3.2 0 0 0 4.5 4.5"></path></svg></button></div></div><div class="profile-form-field"><label for="passKonfirmasi">Konfirmasi Kata Sandi Baru</label><div class="profile-field-toggle-wrap"><input type="password" id="passKonfirmasi" name="password_baru_confirmation" required placeholder="Ulangi kata sandi baru"><button class="field-toggle" type="button" data-target="passKonfirmasi" aria-label="Tampilkan Password"><svg class="icon-eye" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"></path><circle cx="12" cy="12" r="3.2"></circle></svg><svg class="icon-eye-off" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"></path><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c7 0 10.5 7 10.5 7a13.6 13.6 0 0 1-3.2 4.1M6.6 6.6C3.5 8.5 1.5 12 1.5 12s3.5 7 10.5 7a10.6 10.6 0 0 0 4.2-.85"></path><path d="M9.5 9.7a3.2 3.2 0 0 0 4.5 4.5"></path></svg></button></div></div><div class="profile-form-field"><label for="passCatatan">Catatan untuk Admin (opsional)</label><textarea id="passCatatan" name="catatan" rows="2" placeholder="Contoh: lupa kata sandi lama"></textarea></div><button type="submit" class="btn btn-primary">Kirim Permintaan ke Admin</button></form>@endif</div></div><div class="profile-dropdown-view" id="profileHelpView" style="display:none;"><div class="profile-modal-title">Bantuan &amp; Panduan</div><p class="help-intro">Ringkasan singkat menu utama di dashboard Pimpinan.</p><div class="help-topics"><div class="help-topic"><div class="help-topic-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></div><div class="help-topic-body"><div class="help-topic-title">Riwayat Aktivitas</div><div class="help-topic-desc">Pantau ringkasan aktivitas laporan dari seluruh satuan, atau pilih satu satuan di sidebar untuk melihat detail laporannya.</div></div></div><div class="help-topic"><div class="help-topic-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"></path></svg></div><div class="help-topic-body"><div class="help-topic-title">Pelaporan</div><div class="help-topic-desc">Tinjau laporan masuk (terima/revisi/tolak), cek status laporan yang sudah diputuskan, dan kirim permintaan laporan baru ke satuan.</div></div></div></div><div class="help-footer"><div class="help-footer-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 6-10 7L2 6"></path></svg></div><p>Butuh bantuan lebih lanjut? Hubungi <b>Admin Pussiberad</b> melalui jalur koordinasi internal.</p></div></div></div></div>
<div class="report-modal" id="reportDetailModal"><div class="report-modal-card"><div class="report-modal-head"><h3>Detail Aktivitas Laporan</h3><button type="button" class="report-modal-close" id="reportDetailClose" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div><div class="detail-grid"><div class="detail-item"><div class="detail-label">Pengirim</div><div class="detail-value" id="detailPengirim">-</div></div><div class="detail-item"><div class="detail-label">Tujuan</div><div class="detail-value" id="detailTujuan">-</div></div><div class="detail-item"><div class="detail-label">Perihal</div><div class="detail-value" id="detailPerihal">-</div></div><div class="detail-item"><div class="detail-label">Prioritas</div><div class="detail-value" id="detailPrioritas">-</div></div><div class="detail-item" id="detailProgresWrap"><div class="detail-label">Progres</div><div class="detail-value" id="detailProgres">-</div></div><div class="detail-item"><div class="detail-label">Kategori/Kegiatan</div><div class="detail-value" id="detailProyek">-</div></div><div class="detail-item"><div class="detail-label">Tanggal</div><div class="detail-value" id="detailTanggal">-</div></div><div class="detail-item full"><div class="detail-label">Isi Laporan</div><div class="detail-value" id="detailDeskripsi">-</div></div><div class="detail-item full" id="detailKendalaWrap" style="display:none"><div class="detail-label">Kendala/Alasan</div><div class="detail-value" id="detailKendala">-</div></div><div class="detail-item full" id="detailLampiranWrap" style="display:none"><div class="detail-label">Lampiran</div><div class="detail-value" id="detailLampiran"></div></div></div><div class="modal-actions" id="detailActions"><span class="readonly-note">Mode pimpinan: aktivitas Satlak bersifat view-only.</span></div></div></div>
{{-- "Detail Permintaan Laporan" -- sama persis gaya modal satuan (permintaan-
     laporan-realtime.blade.php, ensureDetailView()) buat kartu Permintaan
     Laporan Pimpinan SELAIN status Menunggu (yang tetap numpang
     #reportDetailModal di atas, karena field-nya beda: itu buat review ISI
     laporan yang sudah dikirim, ini buat lihat data PERMINTAAN-nya sendiri). --}}
<div class="report-modal" id="permintaanDetailModal"><div class="report-modal-card"><div class="report-modal-head"><div><h3>Detail Permintaan Laporan</h3><p style="margin:4px 0 0;font-size:12px;color:var(--p-muted);font-weight:400;">Detail permintaan laporan yang dikirim kepada satuan.</p></div></div><div class="detail-grid"><div class="detail-item"><div class="detail-label">Tujuan</div><div class="detail-value" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;"><span id="permintaanDetailTujuan">-</span><span class="satuan-pill" id="permintaanDetailTujuanKode" style="display:none;"></span></div></div><div class="detail-item"><div class="detail-label">Deadline</div><div class="detail-value request-deadline"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg><span id="permintaanDetailDeadline">-</span></div></div><div class="detail-item"><div class="detail-label">Perihal</div><div class="detail-value" id="permintaanDetailPerihal">-</div></div><div class="detail-item"><div class="detail-label">Kategori</div><div class="detail-value" id="permintaanDetailKategori">-</div></div><div class="detail-item"><div class="detail-label">Prioritas</div><div class="detail-value"><span class="priority-tag" id="permintaanDetailPrioritas">-</span></div></div><div class="detail-item"><div class="detail-label">Status</div><div class="detail-value"><span class="deadline-pill" id="permintaanDetailStatus">-</span></div></div><div class="detail-item full"><div class="detail-label">Instruksi</div><div class="detail-value" id="permintaanDetailInstruksi">-</div></div><div class="detail-item full" id="permintaanDetailCatatanWrap" style="display:none;"><div class="detail-label">Catatan / Keterangan</div><div class="detail-value" id="permintaanDetailCatatan" style="white-space:pre-line;">-</div></div></div><div class="modal-actions" id="permintaanDetailActions"></div></div></div>
{{-- Skin #permintaanDetailModal disamakan PERSIS dengan modal "Detail
     Permintaan Laporan" milik satuan (#permintaanLaporanDetailView di
     permintaan-laporan-realtime.blade.php): backdrop ber-blur + dim lebih
     terang, sudut 18px, z-index tinggi, judul 22px, kartu field sedikit
     lebih rapat. Skin .report-modal generik halaman ini (dim gelap tanpa
     blur, sudut 16px, z-index 1000) SENGAJA ditimpa di sini saja -- modal
     lain yang pakai .report-modal tidak terpengaruh. --}}
<style>
/* Pakai display none<->flex (BUKAN cuma visibility:hidden bawaan .report-modal).
   Dengan visibility:hidden, kartu di dalamnya gak ter-render pas modal tertutup,
   jadi "from"-state transform-nya gak ke-capture browser -> pas dibuka, scale/
   slide-nya nyeplak (kaku) walau CSS transition-nya bener. Mekanisme ini persis
   kayak modal detail satuan (#permintaanLaporanDetailView: display none->flex +
   requestAnimationFrame). .pl-mounted = "ada di DOM & ter-render tapi belum
   tampil"; .open = tampil (fade + scale in). */
#permintaanDetailModal{display:none;visibility:visible;background:rgba(15,23,42,.28);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);z-index:100200;padding:24px;transition:opacity .22s ease}
#permintaanDetailModal.pl-mounted{display:flex}
#permintaanDetailModal .report-modal-card{max-height:min(86vh,760px);padding:20px;border-radius:18px;box-shadow:0 24px 70px rgba(15,23,42,.24);transform:translateY(10px) scale(.985);transition:transform .22s ease;will-change:transform}
#permintaanDetailModal.open .report-modal-card{transform:translateY(0) scale(1)}
#permintaanDetailModal .report-modal-head h3{font-size:22px;font-weight:700;letter-spacing:.01em}
#permintaanDetailModal .detail-grid{gap:12px}
#permintaanDetailModal .detail-item{padding:11px;border-radius:8px}
#permintaanDetailModal .detail-label{font-size:10px;font-weight:700;letter-spacing:.05em}
#permintaanDetailModal .detail-value{font-size:13px;line-height:1.6}
#permintaanDetailModal .modal-actions{gap:8px;flex-wrap:wrap;margin-top:18px}
#permintaanDetailModal .modal-actions .action-row{gap:8px;justify-content:flex-end}
/* Tombol polos di footer (Tutup / Lihat Aktivitas): rule `.action-row button`
   halaman ini nge-set border:1px solid transparent -- garis kotaknya jadi
   ilang. Balikin ke gaya .btn bawaan dash-styles (bordered, mono, uppercase)
   biar sama persis kayak tombol "Tutup" di modal satuan. Tombol berwarna
   (Edit Deadline/Batalkan/Revisi) nggak kena karena mereka punya class
   modifier sendiri + !important, bukan .pl-btn-ghost. */
#permintaanDetailModal .action-row .pl-btn-ghost{border:1px solid var(--p-border);background:transparent;color:var(--p-text);font-family:var(--mono);font-weight:600;font-size:11.5px;letter-spacing:.04em;text-transform:uppercase;padding:9px 15px;border-radius:8px;transition:border-color .15s ease,color .15s ease,transform .15s ease}
#permintaanDetailModal .action-row .pl-btn-ghost:hover{border-color:var(--p-accent);color:var(--p-accent);transform:translateY(-1px)}
/* Hormati "reduce motion" -- sama kayak modal satuan (yang juga matiin
   transition + transform-nya di media query ini). */
@media (prefers-reduced-motion: reduce){
  #permintaanDetailModal,#permintaanDetailModal .report-modal-card{transition:none!important}
  #permintaanDetailModal .report-modal-card{transform:none!important}
}
</style>
{{-- "Lihat Progres" -- numpang PERSIS markup wizard #kirimLaporanModal punya
     satuan (permintaan-laporan-deadline.blade.php, gak dimuat di halaman
     Pimpinan jadi ditulis ulang di sini): topbar step .wizard-step-list,
     kartu form .kirim-laporan-form-card/.form-field, file lampiran
     .lampiran-file-list/.lampiran-file-row -- SEMUA field readonly, gak ada
     form/submit sama sekali (Pimpinan cuma lihat, gak bisa isi progres
     satuan). Reuse class yang sama persis (sudah ke-load lewat
     permintaan-laporan-deadline-styles.blade.php di head), jadi gak perlu
     CSS baru buat modal ini. Sama kayak punya satuan, modal ini juga sengaja
     TANPA tombol X -- nutupnya cuma lewat "Tutup" di bawah. --}}
<style>
/* #pimpinanProgresModal ("Lihat Progres") niru markup wizard #kirimLaporanModal
   milik satuan, TAPI beberapa rule yang dikira bare ternyata di-scope ke
   #kirimLaporanModal (.kirim-laporan-form-card, layout label), dan
   .form-field/.form-grid/textarea di halaman ini jatuh ke versi generik
   dash-styles yang beda dari versi satuan (laporan-role.blade.php). Override
   di sini biar identik. */
/* Entrance: pakai display none<->flex (.pl-mounted) + reflow biar transisi
   scale/slide-nya main, bukan nyeplak ("kaku") -- sama mekanisme kayak
   #permintaanDetailModal. Lebar 940px kalau ada checklist (.pl-progres-has-
   steps), niru #kirimLaporanModal.wizard-active di satuan. */
#pimpinanProgresModal{display:none;visibility:visible;background:rgba(0,0,0,.55);transition:opacity .22s ease}
#pimpinanProgresModal.pl-mounted{display:flex}
#pimpinanProgresModal .report-modal-card{width:min(720px,100%);border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);transform:translateY(10px) scale(.985);transition:width .2s ease,transform .22s ease}
#pimpinanProgresModal.pl-progres-has-steps .report-modal-card{width:min(940px,100%)}
#pimpinanProgresModal.open .report-modal-card{transform:translateY(0) scale(1)}
#pimpinanProgresModal .kirim-laporan-modal-head{align-items:center}
#pimpinanProgresModal .task-detail-btn{margin-left:auto;flex-shrink:0;display:inline-flex;align-items:center;gap:6px;border:1px solid color-mix(in srgb,var(--p-accent) 45%,var(--p-border));background:color-mix(in srgb,var(--p-accent) 10%,var(--p-surface-2));color:var(--p-accent);border-radius:9px;padding:8px 12px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .15s ease,transform .15s ease,border-color .15s ease}
#pimpinanProgresModal .task-detail-btn:hover{background:color-mix(in srgb,var(--p-accent) 20%,var(--p-surface-2));transform:translateY(-1px)}
#pimpinanProgresModal .task-detail-btn svg{width:14px;height:14px;flex-shrink:0}
#pimpinanProgresModal .task-detail-btn[hidden]{display:none}
#pimpinanTaskDetailModal .report-modal-card{width:min(480px,100%)}
#pimpinanTaskDetailModal .task-detail-modal-sub{margin:2px 0 12px;font-size:12px;color:var(--p-muted);line-height:1.55}
#pimpinanTaskDetailModal .task-detail-modal-body{font-size:13px;line-height:1.7;white-space:pre-wrap;color:var(--p-text);border:1px solid var(--p-border);border-radius:10px;background:var(--p-surface-2);padding:13px 15px;max-height:56vh;overflow-y:auto}
#pimpinanProgresModal .form-grid{gap:14px}
#pimpinanProgresModal .form-field{gap:7px}
#pimpinanProgresModal .form-field label{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono);font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em}
#pimpinanProgresModal .form-field textarea{width:100%;box-sizing:border-box;background:var(--panel-alt);border:1px solid var(--border);border-radius:7px;color:var(--text);padding:10px 11px;font:inherit;font-size:13px;resize:none;min-height:120px}
#pimpinanProgresModal .kirim-laporan-form-card{grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:18px 20px;padding:20px;border:1px solid var(--border-soft);border-radius:14px;background:var(--panel-alt)}
@media(max-width:640px){#pimpinanProgresModal .kirim-laporan-form-card{grid-template-columns:1fr;padding:16px}}
/* Di sini SEMUA step checklist bisa diklik buat lihat isinya (read-only),
   jadi step "pending" tetap pointer, bukan not-allowed -- yang dipertahankan
   cuma label abu-nya sebagai penanda "belum kebuka". */
#pimpinanProgresModal .wizard-step-pending{cursor:pointer}
/* Tombol Tolak/Terima buat status "Menunggu" -- dipindah ke sini dari
   #reportDetailModal ("Detail Aktivitas Laporan"). Skin-nya niru
   .action-row .approve / .reject di modal itu (hijau/merah solid), tapi
   pakai kelas sendiri biar gak nabrak rule .approve/.reject yang lama. */
#pimpinanProgresModal .pl-progres-reject{background:var(--p-red);border-color:transparent;color:#fff;box-shadow:0 6px 16px -6px rgba(200,59,59,.5)}
#pimpinanProgresModal .pl-progres-approve{background:var(--p-green);border-color:transparent;color:#fff;box-shadow:0 6px 16px -6px rgba(22,131,75,.5)}
#pimpinanProgresModal .pl-progres-reject:hover,#pimpinanProgresModal .pl-progres-approve:hover{filter:brightness(1.07);transform:translateY(-1px)}
.surat-modal-icon{flex-shrink:0;width:44px;height:44px;border-radius:12px;background:color-mix(in srgb,var(--gold-solid-bright) 16%,transparent);color:var(--gold-solid-bright);display:flex;align-items:center;justify-content:center}
.surat-modal-icon svg{width:21px;height:21px}
#kirimSuratModal .field-invalid{border-color:var(--red)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--red) 15%,transparent)}
#kirimSuratModal .kirim-laporan-error{display:flex;align-items:center;gap:6px;font-size:10.5px;color:var(--red)}
#kirimSuratModal .form-field label:not(.priority-option){font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em}
#kirimSuratModal .form-field input,#kirimSuratModal .form-field select,#kirimSuratModal .form-field textarea{width:100%;box-sizing:border-box;background:var(--panel-alt);border:1px solid var(--border);border-radius:7px;color:var(--text);padding:10px 11px;font:inherit;font-size:13px}
#kirimSuratModal .form-field textarea{min-height:120px}
#kirimSuratModal .report-modal-head{margin-bottom:28px}
#kirimSuratModal .report-modal-head h3{font-size:19px}
#kirimSuratModal .modal-actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;border-top:1px solid var(--border-soft);padding-top:18px;margin-top:24px}
.surat-combobox{position:relative;width:100%}
.surat-combobox input[type="text"]{width:100%;box-sizing:border-box}
.surat-form-grid{display:grid;grid-template-columns:1.3fr 1fr;gap:0 26px}
.surat-form-col{display:flex;flex-direction:column;gap:14px}
.surat-form-col-lampiran{border-left:1px solid var(--border-soft);padding-left:26px}
.surat-section-label{font-family:var(--mono);font-size:10.5px;letter-spacing:.08em;text-transform:uppercase;color:var(--gold-bright);font-weight:700}
.surat-lampiran-zone{position:relative;border:1.5px dashed var(--border-soft,var(--border));border-radius:12px;padding:26px 16px;background:var(--panel);text-align:center;transition:border-color .15s ease,background-color .15s ease}
.surat-lampiran-zone:hover,.surat-lampiran-zone.is-dragover{border-color:var(--gold-bright);background:var(--gold-dim)}
.surat-lampiran-zone-input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;margin:0}
.surat-lampiran-zone-prompt{display:flex;flex-direction:column;align-items:center;gap:8px;pointer-events:none}
.surat-lampiran-zone-icon{color:var(--gold-bright)}
.surat-lampiran-zone-icon svg{width:24px;height:24px}
.surat-lampiran-zone-text{display:flex;flex-direction:column;gap:8px}
.surat-lampiran-zone-text-main{font-size:13px;font-weight:700;color:var(--text)}
.surat-lampiran-zone-text-sub{font-size:11.5px;color:var(--text-muted)}
.surat-lampiran-preview{margin-top:10px;flex:1;min-height:0;display:flex}
.surat-lampiran-preview[hidden]{display:none}
.surat-lampiran-preview-frame{position:relative;width:100%;flex:1;min-height:90px;border-radius:9px;overflow:hidden;border:1px solid var(--border-soft);background:var(--panel-alt);display:flex;align-items:center;justify-content:center}
.surat-lampiran-preview-fallback{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:var(--text-muted);padding:10px;height:100%;box-sizing:border-box}
.surat-lampiran-preview-fallback-badge{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;letter-spacing:.02em;color:#fff}
.surat-lampiran-preview-fallback-text{font-size:10.5px;text-align:center;line-height:1.4}
@media(max-width:640px){.surat-form-grid{grid-template-columns:1fr}.surat-form-col-lampiran{border-left:none;padding-left:0;border-top:1px solid var(--border-soft);padding-top:16px;margin-top:2px}.surat-lampiran-preview{flex:none;height:170px}.surat-lampiran-preview-frame{flex:none;height:100%}}
</style>
<div class="report-modal" id="kirimSuratModal"><div class="report-modal-card" style="width:min(860px,100%)"><div class="report-modal-head"><div style="display:flex;align-items:flex-start;gap:12px;min-width:0"><span class="surat-modal-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M12 12v6"></path><path d="M9 15h6"></path></svg></span><span style="min-width:0"><h3 style="margin:0 0 4px">Buat Surat Baru</h3><p style="margin:0;font-size:12px;color:var(--text-muted)">Dari {{ $satuan->nama }}</p></span></div><button type="button" class="report-modal-close" id="kirimSuratClose" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div><form method="POST" action="{{ route('laporan-surat.store') }}" enctype="multipart/form-data" id="kirimSuratForm">@csrf<div class="surat-form-grid"><div class="surat-form-col"><div class="surat-section-label">Informasi Surat</div><div class="form-field full"><label for="surat_tujuan_search">Tujuan</label><div class="surat-combobox" id="suratTujuanCombobox"><input type="hidden" id="surat_tujuan" name="tujuan_satuan_id"><input type="text" id="surat_tujuan_search" autocomplete="off" placeholder="Ketik nama atau satuan tujuan..."></div><script>window.__suratTujuanOptions=@json($satuanSuratTujuanPilihan->map(fn($st)=>['id'=>(string)$st->id,'name'=>$st->nama,'kode'=>$st->kode])->values());</script></div><div class="form-field full"><label for="surat_perihal">Perihal</label><input id="surat_perihal" name="perihal" maxlength="255" required autocomplete="off" placeholder="Judul singkat surat"></div><div class="form-field full"><label for="surat_kategori">Kategori</label><input id="surat_kategori" name="kategori" maxlength="255" required autocomplete="off" placeholder="Contoh: Undangan, Pemberitahuan, Koordinasi"></div><div class="form-field full"><label>Prioritas</label><div class="priority-toggle"><label class="priority-option prio-rendah"><input type="radio" name="prioritas" value="Rendah" required><span>Rendah</span></label><label class="priority-option prio-sedang"><input type="radio" name="prioritas" value="Sedang" required><span>Sedang</span></label><label class="priority-option prio-tinggi"><input type="radio" name="prioritas" value="Tinggi" required><span>Tinggi</span></label></div></div><div class="form-field full"><label for="surat_deskripsi">Isi Ringkasan Surat</label><textarea id="surat_deskripsi" name="deskripsi" required placeholder="Tuliskan isi surat yang ingin disampaikan..."></textarea></div></div><div class="surat-form-col surat-form-col-lampiran"><div class="surat-section-label">Lampiran</div><div class="surat-lampiran-zone" id="suratLampiranZone"><input id="surat_lampiran" type="file" class="surat-lampiran-zone-input" name="lampiran" data-file-picker-ready="1"><div class="surat-lampiran-zone-prompt"><span class="surat-lampiran-zone-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 17l4-4 4 4"></path><path d="M12 13v9"></path><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path></svg></span><span class="surat-lampiran-zone-text"><span class="surat-lampiran-zone-text-main">Klik untuk pilih file atau drag &amp; drop</span><span class="surat-lampiran-zone-text-sub">Maksimal 10 MB</span></span></div></div><div class="lampiran-file-list" id="suratLampiranFileList"><div class="lampiran-file-list-empty" id="suratLampiranFileListEmpty">Belum ada file dipilih</div></div><div class="surat-lampiran-preview" id="suratLampiranPreview" hidden></div><span class="kirim-laporan-error" id="suratLampiranError" style="display:none"></span></div></div><div class="modal-actions"><button type="button" class="btn" id="kirimSuratCancel">Batal</button><button class="btn btn-primary" type="submit" id="kirimSuratSubmitBtn">Buat Surat</button></div></form></div></div><div class="confirm-overlay" id="konfirmasiBuatSuratOverlay"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="konfirmasiBuatSuratTitle"><div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg></div><h3 id="konfirmasiBuatSuratTitle">Kirim Surat Baru?</h3><p>Pastikan data yang kamu isi sudah benar. Surat yang sudah dikirim tidak dapat diedit lagi.</p><div class="confirm-actions"><button type="button" class="btn" id="konfirmasiBuatSuratBatal">Batal</button><button type="button" class="btn btn-primary" id="konfirmasiBuatSuratYa">Ya, Kirim</button></div></div></div>
@include('siberad.dashboards.partials.surat-detail-modal')
<div class="report-modal" id="pimpinanProgresModal"><div class="report-modal-card"><div class="kirim-laporan-wizard-body"><div class="kirim-laporan-wizard-topbar wizard-topbar-visible"><button type="button" class="wizard-topbar-nav wizard-topbar-nav-prev" id="pimpinanProgresPrev" aria-label="Task sebelumnya" hidden><svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"></path></svg></button><ol class="wizard-step-list" id="pimpinanProgresSteps"></ol><button type="button" class="wizard-topbar-nav wizard-topbar-nav-next" id="pimpinanProgresNext" aria-label="Task selanjutnya" hidden><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg></button></div><div class="kirim-laporan-wizard-panel"><div class="kirim-laporan-modal-head"><span class="kirim-laporan-modal-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect><path d="m9 14 2 2 4-4"></path></svg></span><span><h3 id="pimpinanProgresTitle" style="margin:0 0 4px;">Lihat Progres</h3><p id="pimpinanProgresDesc" style="margin:0;font-size:12px;color:var(--p-muted);line-height:1.5;">Checklist tugas untuk permintaan ini.</p></span><button type="button" id="pimpinanTaskDetailBtn" class="task-detail-btn" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>Detail Task</button></div><div class="form-grid"><div class="kirim-laporan-form-card"><div class="form-field"><label><svg class="form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>Isi Laporan</label><textarea id="pimpinanProgresDeskripsi" readonly></textarea></div><div class="form-field"><label><svg class="form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>Kendala/Alasan</label><textarea id="pimpinanProgresKendala" readonly></textarea></div><div class="form-field full"><label><svg class="form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>Lampiran</label><div class="lampiran-file-list" id="pimpinanProgresLampiran"><div class="lampiran-file-list-empty">Belum ada file yang diupload</div></div></div></div><div class="form-field full" id="pimpinanProgresActions" style="display:flex;flex-direction:row;justify-content:flex-end;gap:8px;margin-top:4px;"><button type="button" class="btn" id="pimpinanProgresTutupBtn">Tutup</button></div></div></div></div></div></div>
{{-- Sub-modal "Detail Task" (Pimpinan): dibuka dari #pimpinanTaskDetailBtn di
     pojok kanan header modal "Lihat Progres". Isi teksnya di-set showTask() di
     openPimpinanProgres dari task.detail. Pola & perilaku sama dgn sisi Satuan
     (#taskDetailModal): tanpa tombol X, tutup lewat tombol "Tutup"/Esc, klik
     backdrop TIDAK menutup. --}}
<div class="report-modal" id="pimpinanTaskDetailModal" style="z-index:100300"><div class="report-modal-card"><div class="report-modal-head"><h3>Detail Task</h3></div><p class="task-detail-modal-sub">Instruksi rinci dari Pimpinan untuk task yang dikerjakan satuan.</p><div class="task-detail-modal-body" id="pimpinanTaskDetailModalBody">-</div><div class="modal-actions"><button type="button" class="btn" id="pimpinanTaskDetailModalClose">Tutup</button></div></div></div>
<div class="report-modal" id="hapusRiwayatModal"><div class="report-modal-card" style="width:min(420px,100%);"><div class="report-modal-head"><h3>Hapus Arsip Laporan</h3><button type="button" class="report-modal-close" id="hapusRiwayatClose" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div><p style="font-size:12.5px;color:var(--p-muted);margin:0 0 18px;line-height:1.65;">Laporan yang dihapus tidak dapat dikembalikan. Yakin ingin menghapus laporan ini dari riwayat?</p><form id="formHapusRiwayatPimpinan" method="POST" action="">@csrf @method('DELETE')<div class="action-row" style="justify-content:flex-end;"><button type="button" class="detail-btn" onclick="tutupHapusRiwayatPimpinan()">Batal</button><button type="submit" class="reject">Ya, Hapus</button></div></form></div></div>
<div class="confirm-overlay" id="batalkanPermintaanOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="batalkanPermintaanTitle"><div class="confirm-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><circle cx="12" cy="12" r="9"></circle><path d="M15 9l-6 6"></path><path d="M9 9l6 6"></path></svg></div><h3 id="batalkanPermintaanTitle">Batalkan Permintaan Laporan?</h3><p>Satuan tujuan tidak akan bisa melanjutkan pengerjaan <strong id="batalkanPermintaanPerihal">ini</strong> sampai Anda membuka kembali permintaannya lewat tombol Edit.</p><form id="formBatalkanPermintaan" method="POST" action="">@csrf @method('PATCH')<div class="confirm-actions"><button type="button" class="btn" id="batalkanPermintaanTutup">Tidak</button><button type="submit" class="btn btn-ghost-red">Ya, Batalkan</button></div></form></div></div>
<div class="confirm-overlay" id="terimaLaporanOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="terimaLaporanTitle"><div class="confirm-icon" style="background:var(--success-dim);color:var(--success-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M20 6 9 17l-5-5"></path></svg></div><h3 id="terimaLaporanTitle">Setujui Laporan Ini?</h3><p>Laporan akan ditandai selesai dan disetujui. Satuan tujuan akan melihat keputusan ini sebagai hasil akhir.</p><form id="formTerimaLaporanPimpinan" method="POST" action="">@csrf @method('PATCH')<input type="hidden" name="status" value="Diterima"><div class="confirm-actions"><button type="button" class="btn" id="terimaLaporanBatal">Batal</button><button type="submit" class="btn approve">Ya, Setujui</button></div></form></div></div>
<div class="confirm-overlay" id="tolakLaporanOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="tolakLaporanTitle"><div class="confirm-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M6 6l12 12M18 6L6 18"></path></svg></div><h3 id="tolakLaporanTitle">Tolak Laporan Ini?</h3><p>Berikan catatan alasan penolakan, satuan tujuan akan melihat catatan ini.</p><form id="formTolakLaporanPimpinan" method="POST" action="">@csrf @method('PATCH')<input type="hidden" name="status" value="Ditolak"><label for="tolakLaporanCatatan" style="display:block;text-align:left;font-size:11px;font-weight:800;color:var(--p-muted);text-transform:uppercase;letter-spacing:.05em;margin:14px 0 7px">Catatan / Keterangan</label><textarea id="tolakLaporanCatatan" name="catatan" required maxlength="5000" placeholder="Tuliskan alasan penolakan..." style="width:100%;min-height:64px;box-sizing:border-box;resize:none;padding:10px 11px;border:1px solid var(--p-border);border-radius:8px;background:var(--p-surface-2);color:var(--p-text);font:inherit;font-size:13px"></textarea><div class="confirm-actions"><button type="button" class="btn" id="tolakLaporanBatal">Batal</button><button type="submit" class="btn reject">Ya, Tolak</button></div></form></div></div>
<div class="confirm-overlay" id="revisiLaporanOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="revisiLaporanTitle"><div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg></div><h3 id="revisiLaporanTitle">Kirim Ulang untuk Revisi?</h3><p>Satuan tujuan akan bisa mengirim laporan baru untuk menggantikan laporan yang sebelumnya ditolak.</p><form id="formRevisiLaporanPimpinan" method="POST" action="">@csrf @method('PATCH')<input type="hidden" name="status" value="Revisi"><div class="confirm-actions"><button type="button" class="btn" id="revisiLaporanBatal">Batal</button><button type="submit" class="btn btn-primary">Ya</button></div></form></div></div>
<div class="confirm-overlay" id="konfirmasiArsipkanKendalaOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="konfirmasiArsipkanKendalaTitle"><div class="confirm-icon" style="background:var(--success-dim);color:var(--success-bright)"><svg viewBox="0 0 32 32" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M4.5 14.5V27h23V14.5"></path><path d="M3.5 13h25"></path><path d="M6 13V8.5h20V13"></path><path d="M17 18.5h9v7.5a4 4 0 0 1-4 4H10a5.5 5.5 0 0 1-5.5-5.5V14.5"></path><path d="M16 19h7"></path><path d="M18 23h5"></path></svg></div><h3 id="konfirmasiArsipkanKendalaTitle">Konfirmasi dan Arsipkan Kendala Ini?</h3><p>Kendala <strong id="konfirmasiArsipkanKendalaPerihal">ini</strong> akan dipindahkan ke Arsip Kendala Kasansi. Setelah dikonfirmasi, laporan tidak dapat ditindaklanjuti lagi dari daftar masuk.</p><form id="formKonfirmasiArsipkanKendala" method="POST" action="">@csrf @method("PATCH")<input type="hidden" name="status" value="Dikonfirmasi"><div class="confirm-actions confirm-actions-fit"><button type="button" class="btn btn-ghost-gold" id="konfirmasiArsipkanKendalaBatal">Batal</button><button type="submit" class="btn approve">Konfirmasi</button></div></form></div></div>
<div class="confirm-overlay" id="konfirmasiSuratOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="konfirmasiSuratTitle"><div class="confirm-icon" style="background:var(--success-dim);color:var(--success-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M20 6 9 17l-5-5"></path></svg></div><h3 id="konfirmasiSuratTitle">Konfirmasi Surat?</h3><p id="konfirmasiSuratBody">Konfirmasi surat ini? Pengirim akan mengetahui bahwa surat sudah diterima.</p><form id="formKonfirmasiSurat" method="POST" action="">@csrf @method('PATCH')<div class="confirm-actions"><button type="button" class="btn" id="konfirmasiSuratBatal">Batal</button><button type="submit" class="btn" id="konfirmasiSuratYa">Ya, Konfirmasi</button></div></form></div></div><div class="confirm-overlay" id="hapusArsipKendalaOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="hapusArsipKendalaTitle"><div class="confirm-icon" style="background:rgba(181,52,47,.12);color:var(--p-red)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg></div><h3 id="hapusArsipKendalaTitle">Hapus Arsip Kendala?</h3><p>Arsip kendala <strong id="hapusArsipKendalaPerihal">ini</strong> akan dihapus permanen dan tidak dapat dikembalikan.</p><form id="formHapusArsipKendala" method="POST" action="">@csrf @method('DELETE')<div class="confirm-actions"><button type="button" class="btn" id="hapusArsipKendalaBatal">Batal</button><button type="submit" class="btn btn-ghost-red">Ya, Hapus</button></div></form></div></div>
<style>
/* Entrance halus #editDeadlinePermintaanModal (dipakai Edit Deadline daftar
   aktif & "Revisi" dari Riwayat). .report-modal generik nutup pakai
   visibility:hidden -> kartu di dalamnya gak ter-render pas ketutup, jadi
   "from"-state transform-nya gak ke-capture -> pas dibuka nyeplak ("kasar").
   display none<->flex + visibility:visible + reflow + rAF (pola sama kayak
   #permintaanDetailModal) bikin transisi scale/slide-nya benar-benar main.
   Tombol X (report-modal-close) dibuang -- tombol Batal sudah cukup. */
/* z-index dinaikin dari 1000 (.report-modal) -> 100070 supaya kalau dibuka
   dari tombol "Edit Deadline" DI DALAM modal "Lihat Progres" (#pimpinanProgresModal,
   z-index 1000) dia nongol DI ATAS-nya. Tetap di bawah picker tanggal/jam
   (backdrop 100075 / panel 100080) & overlay konfirmasi (100090). */
#editDeadlinePermintaanModal{display:none;visibility:visible;transition:opacity .2s ease;z-index:100070}
#editDeadlinePermintaanModal.pl-mounted{display:flex}
#editDeadlinePermintaanModal .report-modal-card{transform:translateY(12px) scale(.98);transition:transform .24s cubic-bezier(.2,.7,.2,1);will-change:transform}
#editDeadlinePermintaanModal.open .report-modal-card{transform:translateY(0) scale(1)}
@media(prefers-reduced-motion:reduce){#editDeadlinePermintaanModal .report-modal-card{transition:none}}
</style>
<div class="report-modal" id="editDeadlinePermintaanModal"><div class="report-modal-card" style="width:min(520px,100%);"><div class="report-modal-head"><h3 id="editDeadlineModalTitle">Edit Deadline Permintaan</h3></div><div id="editDeadlineEditableView"><p id="editDeadlineModalIntro" style="font-size:12.5px;color:var(--p-muted);margin:0 0 16px;line-height:1.65;">Tentukan deadline baru untuk <strong id="editDeadlinePerihal">permintaan ini</strong>.</p><form id="formEditDeadlinePermintaan" method="POST" action="" novalidate>@csrf @method('PATCH')<div class="danpus-request-field full"><div class="danpus-deadline-split"><div class="danpus-deadline-part"><span class="danpus-deadline-sublabel">Tanggal Baru</span><div class="danpus-picker" id="editDeadlineDatePicker"><input type="text" id="editDeadlineDateInput" class="danpus-picker-input" readonly autocomplete="off" placeholder="Pilih tanggal"><input type="text" id="editDeadlineDateProxy" required style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none"><button type="button" class="danpus-picker-icon" aria-label="Pilih tanggal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg></button></div></div><div class="danpus-deadline-part"><span class="danpus-deadline-sublabel">Jam Baru</span><div class="danpus-picker" id="editDeadlineTimePicker"><input type="text" id="editDeadlineTimeInput" class="danpus-picker-input" readonly autocomplete="off" placeholder="Pilih jam"><input type="text" id="editDeadlineTimeProxy" required style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none"><button type="button" class="danpus-picker-icon" aria-label="Pilih jam"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg></button></div></div></div><input type="hidden" id="editDeadlineHidden" name="deadline_at"></div><div class="danpus-request-form-actions"><button type="button" class="btn" onclick="tutupEditDeadlinePermintaan()">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div></form></div><div id="editDeadlineBlockedView" style="display:none;"><p id="editDeadlineBlockedReason" style="font-size:12.5px;color:var(--p-muted);margin:0;line-height:1.65;"></p><div class="danpus-request-form-actions"><button type="button" class="btn" onclick="tutupEditDeadlinePermintaan()">Tutup</button></div></div></div></div>
<div class="danpus-calendar" id="editDeadlineCalendar" data-min="{{ now()->format('Y-m-d') }}"><div class="danpus-calendar-head"><button type="button" class="danpus-calendar-nav" id="editDeadlineCalendarPrev" aria-label="Bulan sebelumnya"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button><span class="danpus-calendar-title" id="editDeadlineCalendarTitle"></span><button type="button" class="danpus-calendar-nav" id="editDeadlineCalendarNext" aria-label="Bulan berikutnya"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button></div><div class="danpus-calendar-weekdays"><span>M</span><span>S</span><span>S</span><span>R</span><span>K</span><span>J</span><span>S</span></div><div class="danpus-calendar-days" id="editDeadlineCalendarDays"></div><div class="danpus-picker-actions"><button type="button" class="danpus-picker-close" id="editDeadlineCalendarClose">Tutup</button><button type="button" class="danpus-picker-confirm" id="editDeadlineCalendarConfirm" disabled>Konfirmasi</button></div></div>
<div class="danpus-clock-panel" id="editDeadlineClock"><div class="danpus-wheel-row"><div class="danpus-wheel-highlight"></div><div class="danpus-wheel" id="editDeadlineWheelHour"></div><span class="danpus-wheel-colon">:</span><div class="danpus-wheel" id="editDeadlineWheelMinute"></div></div><div class="danpus-picker-actions"><button type="button" class="danpus-picker-close" id="editDeadlineClockClose">Tutup</button><button type="button" class="danpus-picker-confirm" id="editDeadlineClockConfirm">Konfirmasi</button></div></div>
<div class="confirm-overlay" id="editDeadlineKonfirmasiOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="editDeadlineKonfirmasiTitle"><div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg></div><h3 id="editDeadlineKonfirmasiTitle">Simpan Deadline Baru?</h3><p>Satuan tujuan akan bisa langsung melanjutkan atau mengirim ulang laporannya setelah deadline ini disimpan.</p><div class="confirm-actions"><button type="button" class="btn" id="editDeadlineKonfirmasiBatal">Batal</button><button type="button" class="btn btn-primary" id="editDeadlineKonfirmasiYa">Ya, Simpan</button></div></div></div>
<div class="shell"><aside class="sidebar" id="sidebar"><div class="side-brand"><img src="{{ asset('images/logo-pussiberad.jpg') }}" alt="Lambang Pussiberad"><div class="logo">{{ $pengaturan->hero_judul_awal }}<span>{{ $pengaturan->hero_judul_aksen }}</span></div><button type="button" class="side-collapse-btn" id="sideCollapseBtn" aria-label="Ciutkan sidebar" title="Ciutkan sidebar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg></button></div><nav class="side-nav"><a href="#dashboard" class="side-link active" title="Dashboard"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/></svg></span><span class="side-text">Dashboard</span></a>@if($modulAktif['laporan'] ?? true)<div class="side-nav-group open" id="reportGroup"><button type="button" class="side-nav-group-title" id="reportGroupBtn" title="Pelaporan"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"/></svg></span><span class="side-text">Pelaporan</span> <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button><div class="side-subnav"><div><span class="side-subnav-label">Pelaporan</span><a href="#permintaan-laporan" class="side-sub-link"><span class="sub-dot"></span>Permintaan Laporan</a><a href="#riwayat" class="side-sub-link"><span class="sub-dot"></span>Arsip Laporan</a></div></div></div>@endif <div class="side-nav-group" id="suratGroup"><button type="button" class="side-nav-group-title" id="suratGroupBtn" title="Surat"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg></span><span class="side-text">Surat</span> <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button><div class="side-subnav"><div><span class="side-subnav-label">Surat</span><a href="#kirim-surat" class="side-sub-link" title="Kirim Surat"><span class="sub-dot"></span>Kirim Surat</a><a href="#surat-masuk" class="side-sub-link" title="Surat Masuk"><span class="sub-dot"></span>Surat Masuk</a><a href="#arsip-surat" class="side-sub-link" title="Arsip Surat"><span class="sub-dot"></span>Arsip Surat</a></div></div></div>@if($modulAktif['laporan'] ?? true)<div class="side-nav-group" id="kendalaKasansiGroup"><button type="button" class="side-nav-group-title" id="kendalaKasansiGroupBtn" title="Kendala Kasansi"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></span><span class="side-text">Kendala Kasansi</span> <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button><div class="side-subnav"><div><span class="side-subnav-label">Kendala Kasansi</span><a href="#kendala-kasansi" class="side-sub-link" title="Kendala Kasansi"><span class="sub-dot"></span>Kendala Kasansi</a><a href="#arsip-kendala-kasansi" class="side-sub-link" title="Arsip Kendala Kasansi"><span class="sub-dot"></span>Arsip Kendala Kasansi</a></div></div></div>@endif @if($modulAktif['monitoring'] ?? true)<div class="side-nav-group open" id="monitorGroup"><button type="button" class="side-nav-group-title" id="monitorGroupBtn" title="Riwayat Aktivitas"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span><span class="side-text">Riwayat Aktivitas</span> <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button><div class="side-subnav"><div><span class="side-subnav-label">Riwayat Aktivitas</span><a href="#monitoring" class="side-sub-link"><span class="sub-dot"></span>Ringkasan Seluruh Satuan</a>@foreach($monitoringPimpinanSatlak as $m)<a href="#satlak-{{ $m['id'] }}" class="side-sub-link"><span class="sub-dot"></span>{{ $m['nama'] }}</a>@endforeach</div></div></div>@endif</nav><div class="side-foot"><form class="logout logout-form" method="POST" action="{{ route('logout') }}">@csrf<button type="submit" title="Keluar"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span><span class="side-text">Keluar</span></button></form></div></aside>
<script>try{if(localStorage.getItem('siberad-sidebar-collapsed')==='1'){document.getElementById('sidebar').classList.add('collapsed');document.querySelectorAll('.side-nav-group.open').forEach(function(g){g.classList.remove('open')});}}catch(e){}</script>
<main class="main"><div class="topbar"><div style="display:flex;align-items:center;gap:12px"><button class="menu-btn" id="menuBtn" type="button">☰</button></div><div class="topbar-actions"><button type="button" class="btn-icon-toggle" id="themeToggleBtn" aria-pressed="false" aria-label="Ganti tema"><svg class="icon-moon" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path></svg><svg class="icon-sun" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.2"></circle><path d="M12 2.5v2.4M12 19.1v2.4M4.4 4.4l1.7 1.7M17.9 17.9l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.4 19.6l1.7-1.7M17.9 6.1l1.7-1.7"></path></svg></button><div class="profile-menu" id="profileMenu"><button type="button" class="profile-menu-btn" id="profileMenuBtn" aria-haspopup="menu" aria-expanded="false" aria-label="Menu profil"><span class="profile-initial" id="profileInitial" style="display:{{ $user->foto_path ? 'none' : '' }};">{{ strtoupper(mb_substr($user->name ?? 'U',0,1)) }}</span><img class="profile-photo" id="profilePhotoBtn" alt="Foto profil {{ $user->name }}" @if($user->foto_path) src="{{ asset('storage/'.$user->foto_path) }}" style="display:block;" @endif></button><div class="profile-dropdown" id="profileDropdown" role="menu" aria-label="Menu profil"><div class="profile-dropdown-head"><div class="profile-dropdown-avatar"><span class="profile-initial" id="profileInitialDropdown" style="display:{{ $user->foto_path ? 'none' : '' }};">{{ strtoupper(mb_substr($user->name ?? 'U',0,1)) }}</span><img class="profile-photo" id="profilePhotoDropdown" alt="Foto profil {{ $user->name }}" @if($user->foto_path) src="{{ asset('storage/'.$user->foto_path) }}" style="display:block;" @endif></div><div><div class="profile-dropdown-name">{{ $user->name }}</div><div class="profile-dropdown-role">{{ $user->jabatan ?? 'Pimpinan' }}</div></div></div><button type="button" class="profile-dropdown-item" id="openPengaturanBtn" role="menuitem">Pengaturan Akun</button><button type="button" class="profile-dropdown-item" id="openBantuanBtn" role="menuitem">Bantuan &amp; Panduan</button><div class="profile-dropdown-divider"></div><form class="logout-form" method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="profile-dropdown-item danger" role="menuitem">Keluar</button></form></div></div></div></div>
<div class="content">@include('siberad.dashboards.partials.pengumuman-banner')
@if(session('status'))<script>document.addEventListener('DOMContentLoaded',function(){window.siberadShowToast?window.siberadShowToast('success',{!! json_encode(session('status')) !!}):null});</script>@endif
@if(session('error'))<script>document.addEventListener('DOMContentLoaded',function(){window.siberadShowToast?window.siberadShowToast('error',{!! json_encode(session('error')) !!}):null});</script>@endif
@if($errors->any())<script>document.addEventListener('DOMContentLoaded',function(){window.siberadShowToast?window.siberadShowToast('error',{!! json_encode($errors->first()) !!}):null});</script>@endif
<div class="pimp-page"><section id="dashboard" class="tab-panel active"><div class="pimp-hero"><div class="pimp-eyebrow">SIBERAD // {{ $satuan->kode }}</div><h1>{{ $satuan->nama }}</h1><p>{{ now()->translatedFormat('l, d F Y') }}</p></div><div class="pimp-kpis"><div class="pimp-kpi"><div class="label">Total Laporan</div><div class="value">{{ $laporanPimpinanSatlak->filter(fn($l)=>$l->semuaLampiran->isNotEmpty())->count() }}</div><div class="sub">Aktivitas yang tercatat</div></div><div class="pimp-kpi ok"><div class="label">Disetujui</div><div class="value">{{ $laporanPimpinanSatlak->filter(fn($l)=>str_contains(strtolower((string)$l->status),'setuj') || str_contains(strtolower((string)$l->status),'diterima'))->count() }}</div><div class="sub">Laporan yang disetujui</div></div><div class="pimp-kpi bad"><div class="label">Ditolak</div><div class="value">{{ $laporanPimpinanSatlak->filter(fn($l)=>str_contains(strtolower((string)$l->status),'tolak'))->count() }}</div><div class="sub">Laporan yang ditolak</div></div><div class="pimp-kpi late"><div class="label">Terlambat</div><div class="value">{{ $permintaanLaporan->filter(fn($p)=>$p->isTerlambat())->count() }}</div><div class="sub">Permintaan lewat tenggat</div></div><div class="pimp-kpi cancelled"><div class="label">Dibatalkan</div><div class="value">{{ $permintaanLaporan->where('status',\App\Models\PermintaanLaporan::STATUS_DIBATALKAN)->count() }}</div><div class="sub">Permintaan yang dibatalkan</div></div></div>@php
  $pimpTotalDisetujui = $laporanPimpinanSatlak->filter(fn($l)=>str_contains(strtolower((string)$l->status),'setuj') || str_contains(strtolower((string)$l->status),'diterima'))->count();
  $pimpTotalDitolak = $laporanPimpinanSatlak->filter(fn($l)=>str_contains(strtolower((string)$l->status),'tolak'))->count();
  $pimpTotalTerlambat = $permintaanLaporan->filter(fn($p)=>$p->isTerlambat())->count();
  $pimpTotalDibatalkan = $permintaanLaporan->where('status',\App\Models\PermintaanLaporan::STATUS_DIBATALKAN)->count();
  $pimpTotalStatus = $pimpTotalDisetujui + $pimpTotalDitolak + $pimpTotalTerlambat + $pimpTotalDibatalkan;
  $pimpPersen = fn($n) => $pimpTotalStatus > 0 ? round($n / $pimpTotalStatus * 100) : 0;
@endphp
<div class="chart-grid balanced"><div class="chart-card compact"><h3>Distribusi Status</h3><p>Komposisi status seluruh laporan dari seluruh Satlak dan unit terkait.</p><div class="chart-box"><canvas id="statusChart"></canvas></div><div class="chart-legend" id="statusChartLegend"></div></div><div class="chart-card compact status-summary-card"><h3>Ringkasan Persentase</h3><p>Sebaran status seluruh laporan dalam angka &amp; persen.</p><div class="status-summary-list"><div class="status-summary-row"><span class="status-summary-dot" style="background:#22c55e"></span><span class="status-summary-label">Disetujui</span><span class="status-summary-value">{{ $pimpTotalDisetujui }} <small>({{ $pimpPersen($pimpTotalDisetujui) }}%)</small></span></div><div class="status-summary-row"><span class="status-summary-dot" style="background:#ef4444"></span><span class="status-summary-label">Ditolak</span><span class="status-summary-value">{{ $pimpTotalDitolak }} <small>({{ $pimpPersen($pimpTotalDitolak) }}%)</small></span></div><div class="status-summary-row"><span class="status-summary-dot" style="background:#ff6b6b"></span><span class="status-summary-label">Terlambat</span><span class="status-summary-value">{{ $pimpTotalTerlambat }} <small>({{ $pimpPersen($pimpTotalTerlambat) }}%)</small></span></div><div class="status-summary-row"><span class="status-summary-dot" style="background:#c1121f"></span><span class="status-summary-label">Dibatalkan</span><span class="status-summary-value">{{ $pimpTotalDibatalkan }} <small>({{ $pimpPersen($pimpTotalDibatalkan) }}%)</small></span></div></div></div></div><div class="section-block"><div class="section-head-clean"><div><h2>Laporan per Satuan</h2><p>Perbandingan jumlah laporan yang dibuat oleh seluruh satuan (Satlak, Direktorat, dan 21 Kasansi). Scroll ke bawah untuk lihat semua satuan.</p></div></div><div class="chart-box chart-box-scroll-y"><div class="chart-box-inner" id="satlakChartInner"><canvas id="satlakChart"></canvas></div></div></div><div class="section-block"><div class="section-head-clean"><div><h2>Ringkasan Aktivitas Seluruh Satuan</h2><p>Ikhtisar cepat sebelum membuka detail aktivitas masing-masing satuan.</p></div></div><div class="satlak-grid">@forelse($monitoringPimpinanSatlak as $m)<article class="satlak-card"><div class="code">{{ $m['kode'] ?? 'SATLAK' }}</div><div class="name">{{ $m['nama'] }}</div><div class="total">{{ $m['total_permintaan'] }}</div><div class="caption">Total permintaan ditugaskan</div><a class="card-link" href="#satlak-{{ $m['id'] }}">Lihat Aktivitas</a></article>@empty<div class="muted">Belum ada data Satlak.</div>@endforelse</div></div></section>
<section id="monitoring" class="tab-panel"><div class="section-block"><div class="section-head-clean"><div><h2>Riwayat Aktivitas</h2><p>Ringkasan Seluruh Satuan. Pilih “Lihat Aktivitas” untuk membuka daftar laporan secara detail.</p></div></div><div class="satlak-grid">@forelse($monitoringPimpinanSatlak as $m)<article class="satlak-card"><div class="code">{{ $m['kode'] ?? 'SATLAK' }}</div><div class="name">{{ $m['nama'] }}</div><div class="total">{{ $m['total_permintaan'] }}</div><div class="caption">Total permintaan ditugaskan</div><div class="mini-stats"><div class="mini-stat ok"><strong>{{ $m['diterima'] }}</strong><span>Disetujui</span></div><div class="mini-stat bad"><strong>{{ $m['ditolak'] }}</strong><span>Ditolak</span></div><div class="mini-stat wait"><strong>{{ $m['menunggu'] }}</strong><span>Menunggu</span></div></div><a class="card-link" href="#satlak-{{ $m['id'] }}">Lihat Aktivitas</a></article>@empty<div class="muted">Belum ada data Satlak.</div>@endforelse</div></div></section>
@foreach($monitoringPimpinanSatlak as $m)<section id="satlak-{{ $m['id'] }}" class="tab-panel"><div class="section-block"><div class="section-head-clean"><div><h2>Aktivitas {{ $m['nama'] }}</h2><p>Daftar laporan yang dibuat satuan ini. Data bersifat view-only untuk pimpinan.</p></div></div><div class="clean-table-wrap" data-pending-permintaan="{{ $permintaanLaporan->where('tujuan_satuan_id',$m['id'])->whereNull('laporan_id')->filter(fn($p) => $p->laporans->isEmpty())->concat($riwayatLaporanPimpinan->where('tujuan_satuan_id',$m['id'])->whereNull('laporan_id')->filter(fn($p) => $p->laporans->isEmpty()))->map(fn($p) => ['id' => $p->id, 'subject' => $p->perihal, 'created' => $p->created_at?->translatedFormat('d M Y H:i'), 'ditinjau' => $p->dikerjakan_at?->translatedFormat('d M Y H:i'), 'dibatalkan' => $p->status === \App\Models\PermintaanLaporan::STATUS_DIBATALKAN, 'dibatalkanAt' => $p->dibatalkan_at?->translatedFormat('d M Y H:i'), 'terlambat' => $p->isTerlambat()])->values()->toJson() }}"><table class="clean-table"><thead><tr><th>Perihal</th><th>Tujuan</th><th>Prioritas</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead><tbody>@forelse($laporanPimpinanSatlak->where('satuan_id',$m['id']) as $l)<tr data-permintaan-created="{{ $l->permintaanLaporan?->created_at?->translatedFormat('d M Y H:i') }}" data-permintaan-ditinjau="{{ $l->permintaanLaporan?->dikerjakan_at?->translatedFormat('d M Y H:i') }}" data-permintaan-status="{{ $l->permintaanLaporan?->status }}" data-permintaan-dibatalkan="{{ $l->permintaanLaporan?->dibatalkan_at?->translatedFormat('d M Y H:i') }}" data-permintaan-terlambat="{{ $l->permintaanLaporan?->isTerlambat() ? '1' : '' }}" data-progres="{{ $l->progres }}" data-updated="{{ $l->updated_at->translatedFormat('d M Y H:i') }}" data-kendala="{{ e($l->kendala ?? '') }}" data-permintaan-id="{{ $l->permintaan_laporan_id }}" data-laporan-id="{{ $l->id }}" data-laporan-status="{{ e($l->status) }}"><td><div class="subject">{{ $l->perihal }}</div><div class="muted">{{ $l->proyek ?? 'Laporan kegiatan' }}</div></td><td>{{ $l->tujuanSatuan->nama ?? '-' }}</td><td>{{ $l->prioritas }}</td><td><span class="status-pill {{ $l->status === \App\Models\Laporan::STATUS_PROGRES ? 'blue' : (str_contains(strtolower($l->status),'tolak') ? 'bad' : ((str_contains(strtolower($l->status),'setuj') || str_contains(strtolower($l->status),'diterima')) ? 'ok' : ((str_contains(strtolower($l->status),'revisi')) ? 'revisi' : 'wait'))) }}">{{ $l->status === \App\Models\Laporan::STATUS_PROGRES ? 'Progres · '.$l->progres.'%' : $l->status }}</span></td><td>{{ $l->created_at->translatedFormat('d M Y H:i') }}</td><td><button type="button" class="detail-btn" onclick="openReportDetail(this)" data-pengirim="{{ e($l->satuan->nama ?? '-') }}" data-tujuan="{{ e($l->tujuanSatuan->nama ?? '-') }}" data-perihal="{{ e($l->perihal) }}" data-prioritas="{{ e($l->prioritas) }}" data-progres="{{ $l->progres }}" data-kendala="{{ e($l->kendala ?? '') }}" data-proyek="{{ e($l->proyek ?? '-') }}" data-tanggal="{{ e($l->created_at->translatedFormat('d M Y H:i')) }}" data-deskripsi="{{ e($l->deskripsi) }}" data-lampiran="{{ $l->semuaLampiran->map(fn($x) => ['url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values()->toJson() }}" data-readonly="1">Detail</button></td></tr>@empty<tr><td colspan="6"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada laporan dari satuan ini</div></div></td></tr>@endforelse</tbody></table></div></div></section>@endforeach
<section id="kendala-kasansi" class="tab-panel deadline-sender-section"><div class="danpus-request-panel"><div class="request-head"><div><h2>Kendala Kasansi</h2><p>Laporan rutin/kendala yang dikirim langsung oleh satuan Kasansi (21 Sansidam) kepada Danpus, tanpa lewat Permintaan Laporan.</p></div></div></div><div class="kcard-grid" id="kcard-grid-masuk">@forelse($kendalaMasuk as $k)@include('siberad.dashboards.partials.kendala-kasansi-row', ['k' => $k])@empty<div class="kcard-empty"><svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="var(--p-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><div class="kcard-empty-title">Belum ada laporan kendala</div><div class="kcard-empty-sub">Kendala dari satuan Kasansi akan muncul di sini.</div></div>@endforelse</div></section>
<section id="arsip-kendala-kasansi" class="tab-panel deadline-sender-section"><div class="danpus-request-panel"><div class="request-head"><div><h2>Arsip Kendala Kasansi</h2><p>Laporan kendala Kasansi yang sudah dikonfirmasi Danpus dan diarsipkan — tidak lagi tampil di daftar Kendala Kasansi yang masih aktif.</p></div></div></div><div class="kcard-grid" id="kcard-grid-arsip-pimpinan">@forelse($kendalaArsip as $k)<div class="kcard" data-search="{{ strtolower(($k->satuan->nama ?? '').' '.$k->perihal) }}" data-prioritas="{{ $k->prioritas }}"><div class="kcard-header"><div class="kcard-meta"><span class="satuan-pill">{{ $k->satuan->kode ?? $k->satuan->nama ?? '-' }}</span></div><span class="status-pill ok">Dikonfirmasi</span></div><div class="kcard-body"><div class="kcard-body-row"><span class="kcard-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span><div class="kcard-perihal">{{ $k->perihal }}</div></div></div><div class="kcard-confirm-info"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M23 3H1v5h22V3z"/><path d="M10 12h4"/></svg><span>Dikonfirmasi {{ $k->confirmed_at?->translatedFormat('d M Y, H:i') ?? '-' }} oleh <strong>{{ $k->confirmedBy->name ?? '-' }}</strong></span></div><div class="kcard-footer"><div class="kcard-actions"><button type="button" class="kcard-btn kcard-btn-detail" onclick="openReportDetail(this)" data-pengirim="{{ e($k->satuan->nama ?? '-') }}" data-tujuan="{{ e($satuan->nama) }}" data-perihal="{{ e($k->perihal) }}" data-prioritas="{{ e($k->prioritas) }}" data-proyek="{{ e($k->kategori ?? '-') }}" data-tanggal="{{ e($k->created_at->translatedFormat('d M Y H:i')) }}" data-deskripsi="{{ e($k->deskripsi) }}" data-kendala="{{ e($k->catatan ?? '') }}" data-lampiran="{{ $k->semuaLampiran->map(fn($x) => ['url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values()->toJson() }}" data-kendala-report="1" data-readonly="1" data-readonly-text="Kendala ini sudah dikonfirmasi &amp; diarsipkan pada {{ e($k->confirmed_at?->translatedFormat('d M Y H:i')) }}."><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>Lihat Detail</button></div></div></div>@empty<div class="kcard-empty"><svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="var(--p-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M23 3H1v5h22V3z"/><path d="M10 12h4"/></svg><div class="kcard-empty-title">Belum ada kendala yang diarsipkan</div><div class="kcard-empty-sub">Kendala Kasansi yang sudah dikonfirmasi Danpus akan muncul di sini.</div></div>@endforelse</div></section>
<section id="kirim-surat" class="tab-panel"><div class="panel"><div class="panel-head"><div><h2>Buat Surat</h2><p>Surat yang sudah dikirim dan <strong>belum dikonfirmasi</strong> oleh penerima. Setelah dikonfirmasi, surat otomatis pindah ke Arsip Surat.</p></div><button type="button" class="btn btn-primary" id="kirimSuratOpen">Buat Surat</button></div></div><div class="surat-file-grid" id="suratTerkirimGrid">@forelse($suratTerkirim as $s)@include('siberad.dashboards.partials.surat-terkirim-row', ['s' => $s, 'satuan' => $satuan])@empty<div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Tidak ada surat yang menunggu konfirmasi</div><div class="empty-state-sub">Semua surat yang terkirim sudah dikonfirmasi penerima dan masuk ke Arsip Surat.</div></div>@endforelse</div></section>
<section id="arsip-surat" class="tab-panel"><div class="panel"><div class="panel-head"><div><h2>Arsip Surat</h2><p>Riwayat surat yang sudah <strong>dikonfirmasi</strong>, terkirim maupun masuk.</p></div></div></div><div class="surat-file-grid" id="suratArsipGrid">@forelse($suratArsip as $s)@include('siberad.dashboards.partials.surat-arsip-row', ['s' => $s, 'satuan' => $satuan])@empty<div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada surat yang diarsipkan</div><div class="empty-state-sub">Surat pindah ke sini setelah dikonfirmasi.</div></div>@endforelse</div></section>
<section id="surat-masuk" class="tab-panel"><div class="panel"><div class="panel-head"><div><h2>Surat Masuk</h2><p>Surat masuk yang <strong>menunggu konfirmasi</strong>.</p></div></div></div><div class="surat-file-grid" id="suratMasukGrid">@forelse($suratMasuk as $s)@include('siberad.dashboards.partials.surat-masuk-row', ['s' => $s])@empty<div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada surat masuk</div><div class="empty-state-sub">Surat yang ditujukan ke {{ $satuan->nama }} akan muncul di sini.</div></div>@endforelse</div></section>
<section id="riwayat" class="tab-panel deadline-sender-section"><div class="danpus-request-panel"><div class="request-head"><div><h2>Arsip Laporan</h2><p>Permintaan laporan dari seluruh satuan yang sudah mendapat keputusan akhir (disetujui/ditolak) atau diarsipkan (Terlambat/Dibatalkan).</p></div></div></div><div class="deadline-sender-list">@forelse($riwayatLaporanPimpinan as $item)@include('siberad.dashboards.partials.permintaan-laporan-pimpinan-card', ['item' => $item, 'riwayatMode' => true])@empty<div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada arsip laporan</div><div class="empty-state-sub">Permintaan yang sudah diputuskan atau diarsipkan akan muncul di sini.</div></div>@endforelse</div></section><section id="permintaan-laporan" class="tab-panel deadline-sender-section" aria-label="Permintaan Laporan"><div class="danpus-request-panel"><div class="request-head"><div><h2>Permintaan Laporan</h2><p>Berikan tugas pelaporan kepada satu atau beberapa satuan, lengkap dengan instruksi dan batas waktu.</p></div><button class="btn btn-primary" type="button" id="danpusOpenRequestForm">Buat Permintaan</button></div></div><div class="deadline-sender-list">@forelse($permintaanLaporan as $item)@include('siberad.dashboards.partials.permintaan-laporan-pimpinan-card', ['item' => $item])@empty<div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada permintaan laporan</div><div class="empty-state-sub">Klik <strong>+ Buat Permintaan</strong> untuk memberikan tugas pelaporan kepada satuan.</div></div>@endforelse</div></section></div></div></main></div><div class="danpus-request-modal" id="danpusRequestModal"><div class="danpus-picker-backdrop" id="danpusPickerBackdrop"></div><div class="danpus-request-form-card"><div class="danpus-request-form-head"><div class="danpus-request-form-head-main"><div class="danpus-modal-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M12 12v6"></path><path d="M9 15h6"></path></svg></div><div><h3>Permintaan Laporan</h3><p>Ajukan permintaan laporan kepada satuan terkait dengan mudah dan terstruktur.</p></div></div><button type="button" class="danpus-request-close" id="danpusCloseRequestForm" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div><form method="POST" action="{{ route('permintaan-laporan.store') }}" novalidate>@csrf<div class="danpus-request-form-grid">@php $danpusSatuanKategoriMap=[\App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN=>'Unsur Pelayanan',\App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN=>'Unsur Pembantu Pimpinan',\App\Models\Satuan::KATEGORI_DIREKTORAT=>'Direktorat',\App\Models\Satuan::KATEGORI_SATLAK=>'Satlak',\App\Models\Satuan::KATEGORI_KOTAMA=>'Kasansi']; $danpusSatuanByKategori=$satuanPermintaanLaporan->groupBy('kategori'); $danpusKategoriIconPaths=[\App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN=>'<path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3ZM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3Z"></path>',\App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN=>'<rect x="2" y="7" width="20" height="14" rx="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>',\App\Models\Satuan::KATEGORI_DIREKTORAT=>'<rect x="4" y="2" width="16" height="20" rx="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M8 10h.01"></path><path d="M16 10h.01"></path><path d="M8 14h.01"></path><path d="M16 14h.01"></path>',\App\Models\Satuan::KATEGORI_SATLAK=>'<path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6Z"></path>',\App\Models\Satuan::KATEGORI_KOTAMA=>'<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1Z"></path><line x1="4" y1="22" x2="4" y2="15"></line>']; $danpusKategoriIcon=function($key)use($danpusKategoriIconPaths){return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.($danpusKategoriIconPaths[$key]??'<circle cx="12" cy="12" r="9"></circle>').'</svg>';}; @endphp<div class="danpus-wizard-indicator" id="danpusWizardIndicator"><div class="danpus-wizard-step-item" data-step-indicator="1"><div class="danpus-wizard-circle"><span class="danpus-wizard-num">1</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg></div><div class="danpus-wizard-step-text"><span class="danpus-wizard-step-title">Satuan &amp; Tujuan</span><span class="danpus-wizard-step-desc">Pilih satuan tujuan dan bagian terkait.</span></div></div><div class="danpus-wizard-line"></div><div class="danpus-wizard-step-item" data-step-indicator="2"><div class="danpus-wizard-circle"><span class="danpus-wizard-num">2</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg></div><div class="danpus-wizard-step-text"><span class="danpus-wizard-step-title">Detail Permintaan</span><span class="danpus-wizard-step-desc">Lengkapi informasi permintaan laporan.</span></div></div><div class="danpus-wizard-line"></div><div class="danpus-wizard-step-item" data-step-indicator="3"><div class="danpus-wizard-circle"><span class="danpus-wizard-num">3</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg></div><div class="danpus-wizard-step-text"><span class="danpus-wizard-step-title">Daftar Task</span><span class="danpus-wizard-step-desc">Pilih task untuk satuan.</span></div></div></div><div class="danpus-request-form-body"><div class="danpus-step is-active" data-step="1"><div class="danpus-section-panel"><div class="danpus-section-head"><div class="danpus-section-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="21" x2="21" y2="21"></line><path d="M4 21V9l8-5 8 5v12"></path><line x1="9" y1="21" x2="9" y2="13"></line><line x1="15" y1="21" x2="15" y2="13"></line></svg></div><div><h4 class="danpus-section-title">Satuan &amp; Tujuan</h4><p class="danpus-section-desc">Tentukan satuan tujuan dan bagian yang akan menerima laporan.</p></div></div><div class="danpus-section-body"><div class="danpus-request-field full"><div class="danpus-request-field-headrow"><label>Satuan Tujuan</label><button type="button" class="danpus-request-selectall" id="danpusSelectAllSatuan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span class="danpus-request-selectall-label">Pilih Semua</span></button></div><div class="danpus-request-kategori-quickselect">@foreach($danpusSatuanKategoriMap as $kategoriKey=>$kategoriLabel)@continue($danpusSatuanByKategori->get($kategoriKey,collect())->isEmpty())<button type="button" class="danpus-request-kategori-chip" data-kategori="{{ $kategoriKey }}"><span class="danpus-request-cat-icon">{!! $danpusKategoriIcon($kategoriKey) !!}</span><span>{{ $kategoriLabel }}</span></button>@endforeach</div><div class="danpus-request-satuan-groups">@php $danpusPairKategori=array_slice($danpusSatuanKategoriMap,0,2,true); $danpusRestKategori=array_slice($danpusSatuanKategoriMap,2,null,true); @endphp<div class="danpus-request-satuan-row">@foreach($danpusPairKategori as $kategoriKey=>$kategoriLabel)@continue($danpusSatuanByKategori->get($kategoriKey,collect())->isEmpty())<div class="danpus-request-satuan-group"><div class="danpus-request-satuan-group-head"><span class="danpus-request-cat-icon">{!! $danpusKategoriIcon($kategoriKey) !!}</span><span class="danpus-request-satuan-group-title">{{ $kategoriLabel }}</span></div><div class="danpus-request-check-grid">@foreach($danpusSatuanByKategori->get($kategoriKey) as $tujuan)<label class="danpus-request-check"><input type="checkbox" name="tujuan_satuan_ids[]" value="{{ $tujuan->id }}" data-kategori="{{ $kategoriKey }}"><span class="check-icon">{!! $danpusKategoriIcon($kategoriKey) !!}</span><span class="check-label">{{ $tujuan->nama }}</span><span class="check-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg></span></label>@endforeach</div></div>@endforeach</div>@foreach($danpusRestKategori as $kategoriKey=>$kategoriLabel)@continue($danpusSatuanByKategori->get($kategoriKey,collect())->isEmpty())<div class="danpus-request-satuan-group"><div class="danpus-request-satuan-group-head"><span class="danpus-request-cat-icon">{!! $danpusKategoriIcon($kategoriKey) !!}</span><span class="danpus-request-satuan-group-title">{{ $kategoriLabel }}</span></div><div class="danpus-request-check-grid">@foreach($danpusSatuanByKategori->get($kategoriKey) as $tujuan)<label class="danpus-request-check"><input type="checkbox" name="tujuan_satuan_ids[]" value="{{ $tujuan->id }}" data-kategori="{{ $kategoriKey }}"><span class="check-icon">{!! $danpusKategoriIcon($kategoriKey) !!}</span><span class="check-label">{{ $tujuan->nama }}</span><span class="check-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg></span></label>@endforeach</div></div>@endforeach
</div><input type="checkbox" id="danpusRequestSatuanProxy" required style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none"></div></div></div></div><div class="danpus-step" data-step="2"><div class="danpus-section-panel"><div class="danpus-section-head"><div class="danpus-section-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg></div><div><h4 class="danpus-section-title">Detail Permintaan</h4><p class="danpus-section-desc">Lengkapi informasi permintaan laporan.</p></div></div><div class="danpus-section-body"><div class="danpus-request-field full"><label for="danpusRequestPerihal">Perihal</label><input id="danpusRequestPerihal" name="perihal" required autocomplete="off" maxlength="255" placeholder="Judul singkat laporan"></div><div class="danpus-request-field full"><label for="danpusRequestKategori">Kategori</label><input id="danpusRequestKategori" name="kategori" required autocomplete="off" maxlength="255" placeholder="Contoh: kegiatan, koordinasi, temuan"></div><div class="danpus-request-field full"><label for="danpusRequestInstruksi">Instruksi</label><textarea id="danpusRequestInstruksi" name="instruksi" required autocomplete="off" maxlength="5000" placeholder="Jelaskan informasi yang perlu dilaporkan..."></textarea></div><div class="danpus-request-field full"><div class="danpus-deadline-split"><div class="danpus-deadline-part"><span class="danpus-deadline-sublabel">Tanggal</span><div class="danpus-picker" id="danpusDatePicker"><input type="text" id="danpusRequestDeadlineDate" class="danpus-picker-input" readonly autocomplete="off" placeholder="Pilih tanggal"><input type="text" id="danpusRequestDeadlineDateProxy" required style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none"><button type="button" class="danpus-picker-icon" aria-label="Pilih tanggal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg></button></div></div><div class="danpus-deadline-part"><span class="danpus-deadline-sublabel">Jam</span><div class="danpus-picker" id="danpusTimePicker"><input type="text" id="danpusRequestDeadlineTime" class="danpus-picker-input" readonly autocomplete="off" placeholder="Pilih jam"><input type="text" id="danpusRequestDeadlineTimeProxy" required style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none"><button type="button" class="danpus-picker-icon" aria-label="Pilih jam"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg></button></div></div></div><input type="hidden" id="danpusRequestDeadline" name="deadline_at"></div><div class="danpus-request-field full"><label>Prioritas</label><div class="priority-toggle"><label class="priority-option prio-rendah"><input type="radio" name="prioritas" value="Rendah" required><span>Rendah</span></label><label class="priority-option prio-sedang"><input type="radio" name="prioritas" value="Sedang" required><span>Sedang</span></label><label class="priority-option prio-tinggi"><input type="radio" name="prioritas" value="Tinggi" required><span>Tinggi</span></label></div></div></div></div></div><div class="danpus-step" data-step="3"><div class="danpus-section-panel"><div class="danpus-section-head"><div class="danpus-section-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6h11"></path><path d="M9 12h11"></path><path d="M9 18h11"></path><path d="M4 6h.01"></path><path d="M4 12h.01"></path><path d="M4 18h.01"></path></svg></div><div><h4 class="danpus-section-title">Daftar Task</h4><p class="danpus-section-desc">Pilih task untuk satuan.</p></div></div><div class="danpus-section-body"><div class="danpus-request-field full"><label>Daftar Task untuk Satuan</label><div id="danpusRequestTaskList"><div class="danpus-task-row"><div class="danpus-task-row-inputs"><input type="text" name="tasks[]" required autocomplete="off" maxlength="255" placeholder="Judul task, cth: Kumpulkan data insiden minggu ini"><button type="button" class="danpus-task-remove" aria-label="Hapus task">&times;</button></div><textarea name="task_details[]" class="danpus-task-detail" required autocomplete="off" maxlength="2000" placeholder="Detail task: jelaskan apa yang harus dikerjakan satuan untuk task ini..."></textarea></div></div><button type="button" id="danpusAddTaskBtn" class="btn" style="align-self:flex-start;margin-top:8px">+ Tambah Task</button></div></div></div></div></div></div><button type="submit" id="danpusStepSubmitHidden" style="display:none" aria-hidden="true"></button></form><div class="danpus-request-form-actions"><button type="button" class="btn" id="danpusCancelRequestForm">Batal</button><div class="danpus-request-form-actions-spacer"></div><button type="button" class="btn" id="danpusStepBack" style="display:none">Kembali</button><button type="button" class="btn btn-primary" id="danpusStepNext">Selanjutnya</button><button type="button" class="btn btn-primary" id="danpusStepSubmit" style="display:none" onclick="document.getElementById('danpusStepSubmitHidden').click()">Kirim Permintaan</button></div></div></div>{{-- Panel kalender & jam SENGAJA di luar .danpus-request-form-card (mirror
     pola #editDeadlineCalendar/#editDeadlineClock di atas). position:fixed di
     dalam kontainer overflow:auto memaksa Chrome scroll non-composited =
     repaint tiap frame = lag pas scroll modal. Di sini mereka jadi sibling
     modal; initDanpusDatePicker/Time cari elemennya via getElementById jadi
     lokasinya bebas. --}}<div class="danpus-calendar" id="danpusDeadlineCalendar" data-min="{{ now()->format('Y-m-d') }}"><div class="danpus-calendar-head"><button type="button" class="danpus-calendar-nav" id="danpusCalendarPrev" aria-label="Bulan sebelumnya"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button><span class="danpus-calendar-title" id="danpusCalendarTitle"></span><button type="button" class="danpus-calendar-nav" id="danpusCalendarNext" aria-label="Bulan berikutnya"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button></div><div class="danpus-calendar-weekdays"><span>M</span><span>S</span><span>S</span><span>R</span><span>K</span><span>J</span><span>S</span></div><div class="danpus-calendar-days" id="danpusCalendarDays"></div><div class="danpus-picker-actions"><button type="button" class="danpus-picker-close" id="danpusCalendarClose">Tutup</button><button type="button" class="danpus-picker-confirm" id="danpusCalendarConfirm" disabled>Konfirmasi</button></div></div><div class="danpus-clock-panel" id="danpusDeadlineClock"><div class="danpus-wheel-row"><div class="danpus-wheel-highlight"></div><div class="danpus-wheel" id="danpusWheelHour"></div><span class="danpus-wheel-colon">:</span><div class="danpus-wheel" id="danpusWheelMinute"></div></div><div class="danpus-picker-actions"><button type="button" class="danpus-picker-close" id="danpusClockClose">Tutup</button><button type="button" class="danpus-picker-confirm" id="danpusClockConfirm">Konfirmasi</button></div></div><div class="confirm-overlay" id="danpusKirimPermintaanOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="danpusKirimPermintaanTitle"><div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg></div><h3 id="danpusKirimPermintaanTitle">Kirim Permintaan Laporan?</h3><p>Pastikan satuan tujuan, perihal, dan deadline sudah sesuai. Permintaan yang sudah terkirim tidak dapat diedit lagi.</p><div class="confirm-actions"><button type="button" class="btn" id="danpusKirimPermintaanBatal">Batal</button><button type="button" class="btn btn-primary" id="danpusKirimPermintaanYa">Ya, Kirim</button></div></div></div>
<div class="crop-modal" id="aturFotoOverlay" style="z-index:100090"><div class="crop-modal-card"><div class="crop-modal-head"><h3>Atur Foto Profil</h3></div><div class="crop-stage" id="cropStage"><img id="cropImage" alt="Pratinjau foto profil" draggable="false"><div class="crop-mask"></div></div><div class="crop-zoom-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="6"></circle><path d="m21 21-4.35-4.35"></path></svg><input type="range" id="cropZoomRange" min="100" max="300" value="100" step="1" aria-label="Perbesar foto"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path><path d="M11 8v6"></path><path d="M8 11h6"></path></svg></div><p class="crop-modal-hint">Geser foto buat atur posisi, geser slider buat zoom.</p><div class="crop-modal-actions"><button type="button" class="btn" id="aturFotoBatal">Batal</button><button type="button" class="btn btn-primary" id="aturFotoSimpan">Ganti Foto</button></div></div></div>
<div class="confirm-overlay" id="hapusFotoOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="hapusFotoTitle"><div class="confirm-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg></div><h3 id="hapusFotoTitle">Hapus Foto Profil?</h3><p>Foto profil kamu akan dihapus dan kembali menampilkan inisial nama.</p><form id="formHapusFoto" method="POST" action="{{ route('profil-foto.destroy') }}">@csrf @method('DELETE')<div class="confirm-actions"><button type="button" class="btn" id="hapusFotoBatal">Batal</button><button type="submit" class="btn btn-ghost-red">Ya, Hapus</button></div></form></div></div>
<div class="confirm-overlay" id="kirimGantiPasswordOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="kirimGantiPasswordTitle"><div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg></div><h3 id="kirimGantiPasswordTitle">Kirim Permintaan Ganti Password?</h3><p>Password baru akan aktif setelah disetujui Admin. Permintaan yang sudah terkirim tidak dapat diedit lagi.</p><div class="confirm-actions"><button type="button" class="btn" id="kirimGantiPasswordBatal">Batal</button><button type="button" class="btn btn-primary" id="kirimGantiPasswordYa">Ya, Kirim</button></div></div></div>
<script>
(function(){
  const sidebar=document.getElementById('sidebar');
  // Sama seperti tema & profil: toggle klik tombol menu (hamburger) untuk
  // sidebar di mobile sudah ditangani lebih dulu oleh initRoleUi() di
  // pengumuman-banner.blade.php. Tanpa guard ini, dua listener klik akan
  // sama-sama memanggil classList.toggle('open') dalam satu tap, sehingga
  // saling membatalkan dan sidebar terlihat seperti tidak merespons sama
  // sekali saat tombolnya diklik di HP.
  const menuBtn=document.getElementById('menuBtn');
  if (menuBtn && !menuBtn.dataset.uiBound) {
    menuBtn.dataset.uiBound = '1';
    menuBtn.addEventListener('click',()=>sidebar?.classList.toggle('open'));
  }
  // Toggle collapse sidebar (termasuk resize .content/.pimp-page) ditangani
  // bareng oleh script siberadInitSidebarCollapse di dash-styles.blade.php.

  // Toggle & penerapan tema awal untuk tombol ini normalnya sudah ditangani
  // oleh initRoleUi() di partials/pengumuman-banner.blade.php (dijalankan
  // lebih dulu, ditandai lewat dataset.uiBound). Kalau dua listener klik
  // terpasang di tombol yang sama, keduanya saling membatalkan dalam satu
  // klik (ganti tema lalu langsung balik lagi) — makanya di-guard di sini.
  const themeBtn=document.getElementById('themeToggleBtn');
  if (themeBtn && !themeBtn.dataset.uiBound) {
    themeBtn.dataset.uiBound = '1';
    const THEME_KEY='siberad-theme';
    function applyTheme(theme){
      if(theme==='light') document.documentElement.setAttribute('data-theme','light');
      else document.documentElement.removeAttribute('data-theme');
      themeBtn.setAttribute('aria-pressed',theme==='light'?'true':'false');
    }
    let savedTheme='dark';
    try{savedTheme=localStorage.getItem(THEME_KEY)||'dark';}catch(e){}
    applyTheme(savedTheme);
    themeBtn.addEventListener('click',()=>{
      const current=document.documentElement.getAttribute('data-theme')==='light'?'light':'dark';
      const next=current==='light'?'dark':'light';
      try{localStorage.setItem(THEME_KEY,next);}catch(e){}
      applyTheme(next);
    });
  }

  const profileBtn=document.getElementById('profileMenuBtn'),drop=document.getElementById('profileDropdown'),profileMenu=document.getElementById('profileMenu');
  function closeProfile(){drop?.classList.remove('open');profileBtn?.classList.remove('open');profileBtn?.setAttribute('aria-expanded','false');}
  function openProfile(){drop?.classList.add('open');profileBtn?.classList.add('open');profileBtn?.setAttribute('aria-expanded','true');}
  // Sama seperti tema: toggle klik tombol profil juga sudah ditangani oleh
  // initRoleUi() di pengumuman-banner.blade.php. Guard dengan flag yang sama
  // supaya tidak dobel-bind.
  if (profileBtn && !profileBtn.dataset.uiBound) {
    profileBtn.dataset.uiBound = '1';
    profileBtn.addEventListener('click',e=>{e.stopPropagation();drop?.classList.contains('open')?closeProfile():openProfile()});
  }
  document.addEventListener('click',e=>{if(profileMenu&&!profileMenu.contains(e.target))closeProfile()});
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closeProfile()});

  window.openProfileModal=function(id){
    document.querySelectorAll('#profileModalOverlay .profile-dropdown-view').forEach(v=>v.style.display=v.id===id?'block':'none');
    document.getElementById('profileModalOverlay')?.classList.add('open');
    document.body.style.overflow='hidden';
  };
  document.getElementById('openPengaturanBtn')?.addEventListener('click',e=>{e.stopPropagation();closeProfile();window.openProfileModal('profileSettingsView')});
  document.getElementById('openBantuanBtn')?.addEventListener('click',e=>{e.stopPropagation();closeProfile();window.openProfileModal('profileHelpView')});
  function closeProfileModal(){document.getElementById('profileModalOverlay')?.classList.remove('open');document.body.style.overflow='';}
  document.getElementById('profileModalCloseBtn')?.addEventListener('click',closeProfileModal);
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('profileModalOverlay')?.classList.contains('open'))closeProfileModal()});

  const GROUP_STATE_KEY='siberad-pimpinan-group-';

  // Sidebar ciutkan: submenu grup dirender sebagai flyout (position:fixed)
  // supaya tidak kepotong overflow-x:hidden milik .side-nav. Posisinya
  // dihitung dari posisi tombol grup di layar setiap kali flyout dibuka,
  // sidebar diciutkan/dilebarkan, atau ukuran jendela berubah.
  function positionGroupFlyout(g){
    const subnav=g.querySelector('.side-subnav');
    const btn=g.querySelector('.side-nav-group-title');
    if(!subnav||!btn)return;
    if(window.innerWidth<=900||!sidebar?.classList.contains('collapsed')||!g.classList.contains('open')){
      subnav.style.top='';subnav.style.left='';return;
    }
    const r=btn.getBoundingClientRect();
    subnav.style.top=r.top+'px';
    subnav.style.left=(r.right+8)+'px';
  }
  window.siberadRepositionSubnavFlyouts=function(){
    document.querySelectorAll('.side-nav-group').forEach(positionGroupFlyout);
  };
  window.addEventListener('resize',()=>window.siberadRepositionSubnavFlyouts());

  function restorePimpinanGroupState(){
    ['monitorGroup','reportGroup','kendalaKasansiGroup','suratGroup'].forEach(id=>{
      const g=document.getElementById(id);
      if(!g)return;
      let saved=null;try{saved=sessionStorage.getItem(GROUP_STATE_KEY+id)}catch(e){}
      // Sidebar lagi ciutkan menang duluan atas status tersimpan -- kalau
      // "saved==='open'" tetap dipaksa buka di sini, grup itu sempat nongol
      // sekilas sebagai flyout pas refresh (baru ditutup belakangan oleh
      // siberadInitSidebarCollapse di dash-styles.blade.php), keliatan
      // seperti kedip.
      if(saved==='closed'||sidebar?.classList.contains('collapsed')){
        g.classList.remove('open');
      }else if(saved==='open'){
        g.classList.add('open');
      }
      positionGroupFlyout(g);
    });
  }
  window.siberadRestoreGroupState=restorePimpinanGroupState;
  restorePimpinanGroupState();

  ['monitorGroup','reportGroup','kendalaKasansiGroup','suratGroup'].forEach(id=>{
    const g=document.getElementById(id),b=g?.querySelector('.side-nav-group-title');
    if(!g)return;
    b?.addEventListener('click',e=>{
      e.stopPropagation();
      const willOpen=!g.classList.contains('open');
      // Mode ciutkan: cuma satu flyout yang tampil, tutup grup lain dulu.
      if(willOpen&&sidebar?.classList.contains('collapsed')){
        document.querySelectorAll('.side-nav-group.open').forEach(other=>{
          if(other===g)return;
          other.classList.remove('open');
          try{sessionStorage.setItem(GROUP_STATE_KEY+other.id,'closed')}catch(err){}
          positionGroupFlyout(other);
        });
      }
      g.classList.toggle('open');
      try{sessionStorage.setItem(GROUP_STATE_KEY+id,g.classList.contains('open')?'open':'closed')}catch(e){}
      positionGroupFlyout(g);
    });
  });

  // Flyout submenu (mode ciutkan) ditutup kalau klik di luar, tekan Escape,
  // atau memilih salah satu link di dalamnya.
  document.addEventListener('click',e=>{
    if(!sidebar?.classList.contains('collapsed'))return;
    if(e.target.closest('#sideCollapseBtn'))return;
    document.querySelectorAll('.side-nav-group.open').forEach(g=>{
      if(g.contains(e.target))return;
      g.classList.remove('open');
      try{sessionStorage.setItem(GROUP_STATE_KEY+g.id,'closed')}catch(err){}
      positionGroupFlyout(g);
    });
  });
  document.addEventListener('keydown',e=>{
    if(e.key!=='Escape'||!sidebar?.classList.contains('collapsed'))return;
    document.querySelectorAll('.side-nav-group.open').forEach(g=>{
      g.classList.remove('open');
      try{sessionStorage.setItem(GROUP_STATE_KEY+g.id,'closed')}catch(err){}
      positionGroupFlyout(g);
    });
  });
  document.querySelectorAll('.side-nav-group .side-sub-link').forEach(a=>a.addEventListener('click',()=>{
    if(!sidebar?.classList.contains('collapsed'))return;
    const g=a.closest('.side-nav-group');
    if(!g)return;
    g.classList.remove('open');
    try{sessionStorage.setItem(GROUP_STATE_KEY+g.id,'closed')}catch(err){}
    positionGroupFlyout(g);
  }));

  const ACTIVE_TAB_KEY='siberad-pimpinan-active-tab';
  function showSection(id,link,skipSave){
    document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
    const el=document.getElementById(id);if(el)el.classList.add('active');
    document.querySelectorAll('.side-sub-link,.side-link').forEach(a=>a.classList.remove('active'));
    if(link)link.classList.add('active');
    document.querySelectorAll('.side-nav-group').forEach(g=>g.classList.remove('has-active-child'));
    const activeGroup=link?.closest('.side-nav-group');
    if(activeGroup){
      activeGroup.classList.add('has-active-child');
      if(activeGroup.id)try{sessionStorage.setItem(GROUP_STATE_KEY+activeGroup.id,'open')}catch(e){}
      if(!sidebar?.classList.contains('collapsed')&&!activeGroup.classList.contains('open')){
        activeGroup.classList.add('open');
        if(typeof positionGroupFlyout==='function')positionGroupFlyout(activeGroup);
      }
    }
    sidebar?.classList.remove('open');
    window.scrollTo({top:0,behavior:'smooth'});
    if(!skipSave){try{sessionStorage.setItem(ACTIVE_TAB_KEY,id)}catch(e){}}
  }
  document.querySelectorAll('.side-link[href^="#"],.side-sub-link[href^="#"],.card-link[href^="#"]').forEach(a=>a.addEventListener('click',e=>{const id=a.getAttribute('href').slice(1);if(document.getElementById(id)){e.preventDefault();const navLink=document.querySelector('.side-link[href="#'+id+'"],.side-sub-link[href="#'+id+'"]')||a;showSection(id,navLink)}}));

  // Tombol "Lihat" di tabel Permintaan Laporan -- bawa ke tab Log Aktivitas
  // satuan tujuan permintaan itu, terus kalau laporannya udah pernah dikirim
  // (ketemu baris dengan data-permintaan-id yang sama), disorot & di-scroll
  // ke situ. Kalau satuannya belum kirim laporan sama sekali, ya tetap
  // kebawa ke tab satuannya aja -- itu sudah "log aktivitas"-nya.
  window.danpusLihatAktivitas=function(btn){
    const satuanId=btn?.dataset.satuanId;
    const permintaanId=btn?.dataset.permintaanId;
    if(!satuanId)return;
    const targetId='satlak-'+satuanId;
    if(!document.getElementById(targetId))return;
    const link=document.querySelector('.side-sub-link[href="#'+targetId+'"]');
    showSection(targetId,link);
    const group=link?.closest('.side-nav-group');
    if(group){group.classList.add('open');if(group.id)try{sessionStorage.setItem(GROUP_STATE_KEY+group.id,'open')}catch(e){}positionGroupFlyout(group);}
    setTimeout(()=>{
      // Tabel mentah di section satlak dibongkar & digulung ulang jadi
      // dropdown <details> (lihat danpus-sidebar-submenu-cleanup.blade.php
      // & danpus-activity-dropdown.blade.php) -- data-permintaan-id dari
      // baris asli sengaja diikutkan lewat rangkaian itu supaya masih bisa
      // dicari di sini, di elemen <details> hasil akhirnya.
      const item=document.querySelector('#'+targetId+' .danpus-report-dropdown[data-permintaan-id="'+permintaanId+'"]');
      if(!item)return;
      item.open=true;
      item.classList.add('row-flash');
      setTimeout(()=>item.classList.remove('row-flash'),2200);
      item.scrollIntoView({behavior:'smooth',block:'center'});
    },160);
  };

  (function restoreActiveTab(){
    let savedId=null;
    try{savedId=sessionStorage.getItem(ACTIVE_TAB_KEY)}catch(e){}
    if(!savedId)return;
    // Beberapa section (mis. #permintaan-laporan) baru dibuat belakangan oleh
    // script partial lain setelah script ini jalan -- kalau langsung dicek
    // sekali dan elemennya belum ada, restore diam-diam gagal dan tab balik
    // ke Dashboard. Coba ulang sebentar sampai elemennya benar-benar ada.
    function attempt(tries){
      if(!document.getElementById(savedId)){
        if(tries<20)setTimeout(()=>attempt(tries+1),50);
        return;
      }
      const link=document.querySelector('.side-link[href="#'+savedId+'"],.side-sub-link[href="#'+savedId+'"]');
      showSection(savedId,link,true);
      if(link){
        const group=link.closest('.side-nav-group');
        if(group){group.classList.add('open');if(group.id)try{sessionStorage.setItem(GROUP_STATE_KEY+group.id,'open')}catch(e){}positionGroupFlyout(group);}
      }
    }
    attempt(0);
  })();

  document.querySelectorAll('.logout-form').forEach(form=>form.addEventListener('submit',e=>{if(!window.confirm('Keluar dari akun SIBERAD?'))e.preventDefault()}));

  window.openReportDetail=function(button){const modal=document.getElementById('reportDetailModal');document.getElementById('detailPengirim').textContent=button.dataset.pengirim||'-';document.getElementById('detailTujuan').textContent=button.dataset.tujuan||'-';document.getElementById('detailPerihal').textContent=button.dataset.perihal||'-';document.getElementById('detailPrioritas').textContent=button.dataset.prioritas||'-';document.getElementById('detailProgres').textContent=button.dataset.progres?button.dataset.progres+'%':'-';const progresWrap=document.getElementById('detailProgresWrap');if(progresWrap)progresWrap.style.display=button.dataset.kendalaReport==='1'?'none':'block';document.getElementById('detailProyek').textContent=button.dataset.proyek||'-';document.getElementById('detailTanggal').textContent=button.dataset.tanggal||'-';document.getElementById('detailDeskripsi').textContent=button.dataset.deskripsi||'-';const kendala=button.dataset.kendala||'',kendalaWrap=document.getElementById('detailKendalaWrap');if(kendala){document.getElementById('detailKendala').textContent=kendala;kendalaWrap.style.display='block'}else{kendalaWrap.style.display='none'}const wrap=document.getElementById('detailLampiranWrap');const linkBox=document.getElementById('detailLampiran');let daftarLampiran=[];try{daftarLampiran=button.dataset.lampiran?JSON.parse(button.dataset.lampiran):[]}catch(e){daftarLampiran=[]}linkBox.innerHTML='';if(daftarLampiran.length){daftarLampiran.forEach(function(x,i){const a=document.createElement('a');a.href=x.url;a.target='_blank';a.rel='noopener';a.className='lampiran-btn';a.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg><span>'+(daftarLampiran.length>1?(x.nama||'Lihat Lampiran '+(i+1)):'Lihat Lampiran')+'</span>';linkBox.appendChild(a)});wrap.style.display='block'}else{wrap.style.display='none'}const actionsEl=document.getElementById('detailActions')||modal?.querySelector('.modal-actions');if(actionsEl){if(button.dataset.suratMasuk==='1'){actionsEl.innerHTML='';const note=document.createElement('span');note.style.cssText='font-size:11px;color:var(--p-muted);margin-right:auto';note.textContent=button.dataset.readonlyText||'-';actionsEl.appendChild(note);if(button.dataset.konfirmasiAction){const kbtn=document.createElement('button');kbtn.type='button';kbtn.className='detail-btn';kbtn.style.cssText='background:var(--clr-accent,#1a7a4a);color:#fff;border-color:var(--clr-accent,#1a7a4a)';kbtn.textContent='Konfirmasi';kbtn.onclick=function(){if(typeof window.bukaKonfirmasiSurat==='function')window.bukaKonfirmasiSurat(button.dataset.konfirmasiAction,button.dataset.konfirmasiToken,button.dataset.konfirmasiPengirim)};actionsEl.appendChild(kbtn)}}else if(button.dataset.pimpinanReview==='1'){const lid=button.dataset.laporanId||'';actionsEl.innerHTML='<div class="action-row"><button type="button" class="btn reject" onclick="bukaTolakLaporanPimpinan(\''+lid+'\')">Tolak</button><button type="button" class="btn approve" onclick="bukaTerimaLaporanPimpinan(\''+lid+'\')">Terima</button></div>'}else if(button.dataset.deletable==='1'){actionsEl.innerHTML='<div class="action-row"><button type="button" class="reject" onclick="bukaHapusRiwayatPimpinan(\''+(button.dataset.id||'')+'\')">Hapus dari Riwayat</button></div>'}else if(button.dataset.kendalaReport==='1'){actionsEl.innerHTML=''}else if(button.dataset.readonly==='1'){actionsEl.innerHTML='<span class="report-detail-note" style="margin-right:auto;font-size:11px;color:var(--p-muted);">'+(button.dataset.readonlyText||'Mode pimpinan: aktivitas Satlak bersifat view-only.')+'</span>'}else{actionsEl.innerHTML='<span class="readonly-note">Mode pimpinan: aktivitas Satlak bersifat view-only.</span>'}}modal?.classList.add('open')};
  document.getElementById('reportDetailClose')?.addEventListener('click',()=>document.getElementById('reportDetailModal')?.classList.remove('open'));
  document.getElementById('kirimSuratOpen')?.addEventListener('click',()=>{const m=document.getElementById('kirimSuratModal');if(!m)return;void m.offsetHeight;m.classList.add('open');});
  document.getElementById('kirimSuratClose')?.addEventListener('click',()=>document.getElementById('kirimSuratModal')?.classList.remove('open'));
  document.getElementById('kirimSuratCancel')?.addEventListener('click',()=>document.getElementById('kirimSuratModal')?.classList.remove('open'));
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('kirimSuratModal')?.classList.contains('open'))document.getElementById('kirimSuratModal').classList.remove('open')});
  (function(){
  // Tujuan surat: input teks + saran otomatis (autocomplete), niru tampilan
  // floating-menu yang sama dipakai .styled-select-menu (dropdown lain di
  // sistem ini) -- input hidden #surat_tujuan nyimpen ID satuan yang BENERAN
  // kepilih dari daftar; ngetik doang tanpa milih dari saran nggak dianggap
  // valid (hidden value dikosongin lagi tiap ngetik), jadi backend selalu
  // nerima ID satuan yang valid, bukan teks bebas.
  const wrap=document.getElementById('suratTujuanCombobox');
  const search=document.getElementById('surat_tujuan_search');
  const hidden=document.getElementById('surat_tujuan');
  const options=window.__suratTujuanOptions||[];
  if(!wrap||!search||!hidden)return;
  const menu=document.createElement('div');
  menu.className='styled-select-menu';
  const inner=document.createElement('div');
  inner.className='styled-select-menu-inner';
  menu.appendChild(inner);
  document.body.appendChild(menu);
  function clearInvalid(){
    search.classList.remove('field-invalid');
    const anchor=search.closest('.form-field');
    const msg=anchor&&anchor.querySelector(':scope > .kirim-laporan-error');
    if(msg)msg.style.display='none';
  }
  function renderOptions(){
    inner.innerHTML='';
    const q=search.value.trim().toLowerCase();
    const filtered=options.filter(function(o){return !q||o.name.toLowerCase().indexOf(q)>-1||(o.kode&&o.kode.toLowerCase().indexOf(q)>-1)});
    if(!filtered.length){
      const empty=document.createElement('div');
      empty.className='styled-select-option';
      empty.style.cssText='cursor:default;opacity:.6';
      empty.textContent='Tidak ada satuan yang cocok.';
      inner.appendChild(empty);
      return;
    }
    filtered.forEach(function(o){
      const item=document.createElement('button');
      item.type='button';
      item.className='styled-select-option'+(hidden.value===o.id?' active':'');
      const txt=document.createElement('span');
      txt.className='ss-opt-text';
      txt.textContent=o.name;
      item.appendChild(txt);
      item.addEventListener('mousedown',function(e){
        e.preventDefault();
        hidden.value=o.id;
        search.value=o.name;
        clearInvalid();
        hidden.dispatchEvent(new Event('change',{bubbles:true}));
        close();
      });
      inner.appendChild(item);
    });
  }
  function position(){
    const r=search.getBoundingClientRect();
    menu.style.minWidth=r.width+'px';
    let left=r.left,top=r.bottom+6;
    const vw=window.innerWidth;
    menu.style.left=left+'px';
    menu.style.top=top+'px';
    const mr=menu.getBoundingClientRect();
    if(mr.right>vw-8)left=Math.max(8,vw-8-mr.width);
    menu.style.left=left+'px';
  }
  function close(){
    menu.classList.remove('open');
  }
  search.addEventListener('input',function(){
    hidden.value='';
    if(!search.value.trim()){close();return}
    renderOptions();
    position();
    menu.classList.add('open');
  });
  document.addEventListener('click',function(e){
    if(!wrap.contains(e.target)&&!menu.contains(e.target))close();
  });
  window.addEventListener('scroll',function(e){
    if(menu.contains(e.target))return;
    if(menu.classList.contains('open'))close();
  },true);
  window.addEventListener('resize',function(){
    if(menu.classList.contains('open'))position();
  });
  })();
  (function(){
  const zone=document.getElementById('suratLampiranZone');
  const input=document.getElementById('surat_lampiran');
  const list=document.getElementById('suratLampiranFileList');
  const emptyEl=document.getElementById('suratLampiranFileListEmpty');
  const errEl=document.getElementById('suratLampiranError');
  const previewEl=document.getElementById('suratLampiranPreview');
  const form=document.getElementById('kirimSuratForm');
  const LAMPIRAN_MAX_BYTES=10*1024*1024;
  let fileObjectUrl=null;
  let previewGeneration=0;
  if(!zone||!input||!list)return;
  function loadPdfJs(){
    if(!window.__pdfjsLibPromise){
      window.__pdfjsLibPromise=import('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/6.3.289/pdf.min.mjs').then(function(lib){
        lib.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/6.3.289/pdf.worker.min.mjs';
        return lib;
      });
    }
    return window.__pdfjsLibPromise;
  }
  function renderPdfPreview(file,frame,gen){
    const canvas=document.createElement('canvas');
    canvas.style.cssText='position:absolute;top:0;left:0;width:100%;height:auto;display:block';
    loadPdfJs().then(function(lib){
      return file.arrayBuffer().then(function(buf){
        return lib.getDocument({data:buf}).promise;
      });
    }).then(function(pdf){
      return pdf.getPage(1);
    }).then(function(page){
      if(gen!==previewGeneration)return;
      const frameWidth=frame.clientWidth||96;
      const dpr=window.devicePixelRatio||1;
      const unscaled=page.getViewport({scale:1});
      const viewport=page.getViewport({scale:(frameWidth/unscaled.width)*dpr});
      canvas.width=Math.ceil(viewport.width);
      canvas.height=Math.ceil(viewport.height);
      canvas.style.width=frameWidth+'px';
      return page.render({canvasContext:canvas.getContext('2d'),viewport:viewport}).promise;
    }).then(function(){
      if(gen!==previewGeneration)return;
      frame.appendChild(canvas);
    }).catch(function(){
      if(gen!==previewGeneration)return;
      renderFallback(file,frame);
    });
  }
  function renderFallback(file,frame){
    frame.innerHTML='';
    const badge=(window.siberadLampiranBadge&&window.siberadLampiranBadge(file.name))||{text:'FILE',cls:'lfx-other'};
    const fb=document.createElement('div');
    fb.className='surat-lampiran-preview-fallback';
    const box=document.createElement('span');
    box.className='surat-lampiran-preview-fallback-badge lampiran-file-row-icon '+badge.cls;
    box.textContent=badge.text;
    const txt=document.createElement('span');
    txt.className='surat-lampiran-preview-fallback-text';
    txt.textContent='Tanpa pratinjau';
    fb.appendChild(box);fb.appendChild(txt);
    frame.appendChild(fb);
  }
  function renderPreview(file,fileUrl){
    if(!previewEl)return;
    previewGeneration++;
    previewEl.innerHTML='';
    if(!file){previewEl.hidden=true;return}
    previewEl.hidden=false;
    const frame=document.createElement('div');
    frame.className='surat-lampiran-preview-frame';
    const isPdf=file.type==='application/pdf'||/\.pdf$/i.test(file.name);
    if(isPdf){
      renderPdfPreview(file,frame,previewGeneration);
    }else{
      renderFallback(file,frame);
    }
    previewEl.appendChild(frame);
  }
  function showLampiranError(text){
    zone.classList.add('field-invalid');
    if(errEl){errEl.textContent=text;errEl.style.display='flex';}
  }
  function clearLampiranError(){
    zone.classList.remove('field-invalid');
    if(errEl)errEl.style.display='none';
  }
  function formatSize(bytes){
    if(bytes<1024*1024) return Math.max(1,Math.round(bytes/1024))+' KB';
    return (bytes/1024/1024).toFixed(1)+' MB';
  }
  function render(){
    list.querySelectorAll('.lampiran-file-row').forEach(function(el){el.remove()});
    if(fileObjectUrl){URL.revokeObjectURL(fileObjectUrl);fileObjectUrl=null;}
    const file=input.files&&input.files[0];
    if(!file){if(emptyEl)emptyEl.hidden=false;renderPreview(null);return}
    if(emptyEl)emptyEl.hidden=true;
    fileObjectUrl=URL.createObjectURL(file);
    renderPreview(file,fileObjectUrl);
    const badge=(window.siberadLampiranBadge&&window.siberadLampiranBadge(file.name))||{text:'PDF',cls:'lfx-pdf'};
    const row=document.createElement('div');
    row.className='lampiran-file-row';
    const icon=document.createElement('span');
    icon.className='lampiran-file-row-icon '+badge.cls;
    icon.textContent=badge.text;
    const info=document.createElement('span');
    info.className='lampiran-file-row-info';
    const name=document.createElement('a');
    name.className='lampiran-file-row-name';
    name.href=fileObjectUrl;
    name.target='_blank';
    name.rel='noopener';
    name.textContent=file.name;
    const size=document.createElement('span');
    size.className='lampiran-file-row-size';
    size.textContent=formatSize(file.size);
    info.appendChild(name);info.appendChild(size);
    const removeBtn=document.createElement('button');
    removeBtn.type='button';
    removeBtn.className='lampiran-file-row-remove';
    removeBtn.setAttribute('aria-label','Hapus file');
    removeBtn.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>';
    removeBtn.addEventListener('click',function(){input.value='';render()});
    row.appendChild(icon);row.appendChild(info);row.appendChild(removeBtn);
    list.appendChild(row);
  }
  input.addEventListener('change',function(){
    const file=input.files&&input.files[0];
    if(file&&file.size>LAMPIRAN_MAX_BYTES){
      input.value='';
      showLampiranError('Ukuran file melebihi 10 MB, tidak bisa dipilih.');
      render();
      return;
    }
    if(file)clearLampiranError();
    render();
  });
  ['dragenter','dragover'].forEach(function(evt){zone.addEventListener(evt,function(){zone.classList.add('is-dragover')})});
  ['dragleave','drop'].forEach(function(evt){zone.addEventListener(evt,function(){zone.classList.remove('is-dragover')})});
  if(form)form.addEventListener('submit',function(e){
    if(!input.files||input.files.length===0){
      e.preventDefault();
      showLampiranError('Lampiran wajib diisi.');
      zone.scrollIntoView({block:'center',behavior:'smooth'});
    }
  });
  render();
  })();
  (function(){
  const form=document.getElementById('kirimSuratForm');
  if(!form)return;
  const suratMessages={
    tujuan_satuan_id:'Tujuan surat wajib dipilih.',
    perihal:'Perihal wajib diisi.',
    kategori:'Kategori wajib diisi.',
    prioritas:'Pilih salah satu prioritas.',
    deskripsi:'Isi ringkasan surat wajib diisi.'
  };
  form.querySelectorAll('select[required],input[required],textarea[required]').forEach(function(input){
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
      msg.textContent=suratMessages[input.name]||'Kolom ini wajib diisi.';
      msg.style.display='flex';
    });
    function clearInvalid(){
      input.classList.remove('field-invalid');
      msg.style.display='none';
    }
    input.addEventListener('input',clearInvalid);
    input.addEventListener('change',clearInvalid);
  });
  const tujuanSearch=document.getElementById('surat_tujuan_search');
  const tujuanHidden=document.getElementById('surat_tujuan');
  if(tujuanSearch&&tujuanHidden){
    form.addEventListener('submit',function(e){
      if(tujuanHidden.value)return;
      e.preventDefault();
      var anchor=tujuanSearch.closest('.form-field');
      var msg=anchor&&anchor.querySelector(':scope > .kirim-laporan-error');
      if(!msg&&anchor){
        msg=document.createElement('span');
        msg.className='kirim-laporan-error';
        anchor.appendChild(msg);
      }
      tujuanSearch.classList.add('field-invalid');
      if(msg){msg.textContent=suratMessages.tujuan_satuan_id;msg.style.display='flex';}
      tujuanSearch.scrollIntoView({block:'center',behavior:'smooth'});
    });
    tujuanSearch.addEventListener('input',function(){
      tujuanSearch.classList.remove('field-invalid');
      var anchor=tujuanSearch.closest('.form-field');
      var msg=anchor&&anchor.querySelector(':scope > .kirim-laporan-error');
      if(msg)msg.style.display='none';
    });
  }
  })();
  (function(){
  const form=document.getElementById('kirimSuratForm');
  const konfirmOverlay=document.getElementById('konfirmasiBuatSuratOverlay');
  if(!form||!konfirmOverlay)return;
  function closeKonfirm(){konfirmOverlay.classList.remove('open')}
  form.addEventListener('submit',function(e){
    if(form.dataset.confirmed==='1'){form.dataset.confirmed='';return}
    if(e.defaultPrevented)return;
    e.preventDefault();
    konfirmOverlay.classList.add('open');
  });
  document.getElementById('konfirmasiBuatSuratYa')?.addEventListener('click',function(){
    closeKonfirm();
    form.dataset.confirmed='1';
    form.requestSubmit?form.requestSubmit():form.submit();
  });
  document.getElementById('konfirmasiBuatSuratBatal')?.addEventListener('click',closeKonfirm);
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape'&&konfirmOverlay.classList.contains('open'))closeKonfirm();
  });
  })();
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('reportDetailModal')?.classList.contains('open'))document.getElementById('reportDetailModal').classList.remove('open')});

  // "Detail Permintaan Laporan" -- dipakai tombol "Lihat Detail" kartu
  // Permintaan Laporan Pimpinan SELAIN status Menunggu. SENGAJA read-only:
  // footernya cuma "Tutup" + "Lihat Aktivitas" (dibangun via DOM API, bukan
  // string HTML). Tombol kelola pindah ke modal "Lihat Progres".
  window.openPermintaanDetailModal=function(button){
    const modal=document.getElementById('permintaanDetailModal');
    document.getElementById('permintaanDetailTujuan').textContent=button.dataset.tujuan||'-';
    const tujuanKodeEl=document.getElementById('permintaanDetailTujuanKode');
    if(button.dataset.tujuanKode){tujuanKodeEl.textContent=button.dataset.tujuanKode;tujuanKodeEl.style.display='inline-flex';}else{tujuanKodeEl.style.display='none';}
    document.getElementById('permintaanDetailDeadline').textContent=button.dataset.deadline||'-';
    document.getElementById('permintaanDetailPerihal').textContent=button.dataset.perihal||'-';
    document.getElementById('permintaanDetailKategori').textContent=button.dataset.kategori||'-';
    const prioEl=document.getElementById('permintaanDetailPrioritas');
    prioEl.textContent=button.dataset.prioritas||'-';
    prioEl.className='priority-tag pl-prio-violet'+(button.dataset.prioritas?' prio-'+button.dataset.prioritas.toLowerCase():'');
    const statusEl=document.getElementById('permintaanDetailStatus');
    statusEl.textContent=button.dataset.status||'-';
    statusEl.className='deadline-pill '+(button.dataset.statusClass||'');
    document.getElementById('permintaanDetailInstruksi').textContent=button.dataset.instruksi||'-';
    // Catatan/keterangan penolakan (data-catatan dari kartu -- lihat
    // permintaan-laporan-pimpinan-card.blade.php). Cuma nongol kalau ada isinya,
    // yaitu pas permintaan pernah/sedang ditolak. Sama persis mekanismenya
    // dengan modal detail satuan (#permintaanLaporanDetailView).
    const catatanPenolakan=(button.dataset.catatan||'').trim();
    const catatanWrap=document.getElementById('permintaanDetailCatatanWrap');
    const catatanEl=document.getElementById('permintaanDetailCatatan');
    if(catatanWrap&&catatanEl){
      if(catatanPenolakan){catatanEl.textContent=catatanPenolakan;catatanWrap.style.display='';}
      else{catatanEl.textContent='-';catatanWrap.style.display='none';}
    }
    const actionsEl=document.getElementById('permintaanDetailActions');
    const row=document.createElement('div');row.className='action-row';
    // Modal ini SENGAJA read-only: footernya cuma "Tutup" + "Lihat Aktivitas".
    // Tombol kelola permintaan (Edit Deadline / Batalkan / Revisi) dipindah ke
    // modal "Lihat Progres" -- lihat openPimpinanProgres() di bawah.
    const tutupBtn=document.createElement('button');tutupBtn.type='button';tutupBtn.className='btn pl-btn-ghost';tutupBtn.textContent='Tutup';
    tutupBtn.addEventListener('click',function(){tutupPermintaanDetailModal();});
    row.appendChild(tutupBtn);
    const lihatBtn=document.createElement('button');lihatBtn.type='button';lihatBtn.className='btn pl-btn-ghost';lihatBtn.textContent='Lihat Aktivitas';
    lihatBtn.dataset.satuanId=button.dataset.satuanId||'';lihatBtn.dataset.permintaanId=button.dataset.permintaanId||'';
    lihatBtn.addEventListener('click',function(){tutupPermintaanDetailModal();window.danpusLihatAktivitas&&window.danpusLihatAktivitas(lihatBtn);});
    row.appendChild(lihatBtn);
    actionsEl.innerHTML='';actionsEl.appendChild(row);
    // Entrance disamain sama modal detail satuan: mount (display:flex, tapi
    // masih opacity:0 + kartu di posisi awal) -> paksa reflow biar state awal
    // itu ke-commit -> baru .open, jadi transisi fade + scale-nya benar-benar
    // main mulus, bukan nyeplak ("kaku").
    if(modal){
      modal.classList.remove('open');
      modal.classList.add('pl-mounted');
      void modal.offsetWidth;
      requestAnimationFrame(function(){modal.classList.add('open');});
    }
  };
  window.tutupPermintaanDetailModal=function(){
    const m=document.getElementById('permintaanDetailModal');
    if(!m)return;
    m.classList.remove('open');
    // Balik ke display:none setelah transisi .22s kelar -- biar layer
    // backdrop-filter + will-change gak nyangkut, dan isinya keluar dari
    // accessibility tree lagi.
    setTimeout(function(){if(!m.classList.contains('open'))m.classList.remove('pl-mounted');},240);
  };
  // Modal ini nutupnya lewat tombol "Tutup" yang dibangun di footer oleh
  // openPermintaanDetailModal() + tombol Esc di bawah -- sama kayak modal
  // satuan (klik backdrop sengaja TIDAK menutup).
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('permintaanDetailModal')?.classList.contains('open'))tutupPermintaanDetailModal()});

  // "Lihat Progres" -- checklist step read-only. Klik satu step nampilin
  // isi checkpoint-nya (deskripsi/kendala/lampiran) di bawah, gak ada form/
  // submit -- Pimpinan cuma bisa lihat, gak bisa isi progress satuan.
  window.openPimpinanProgres=function(button,refresh){
    const modal=document.getElementById('pimpinanProgresModal');
    let tasks=[],ppItems=null,ppDefault=0;
    try{tasks=button.dataset.tasks?JSON.parse(button.dataset.tasks):[];}catch(e){tasks=[];}
    if(modal)modal.dataset.plPid=button.dataset.permintaanId||'';
    const stepsEl=document.getElementById('pimpinanProgresSteps');
    const descEl=document.getElementById('pimpinanProgresDesc');
    const detailBtnEl=document.getElementById('pimpinanTaskDetailBtn');
    const detailBodyEl=document.getElementById('pimpinanTaskDetailModalBody');
    const deskTa=document.getElementById('pimpinanProgresDeskripsi');
    const kendalaTa=document.getElementById('pimpinanProgresKendala');
    const lampiranEl=document.getElementById('pimpinanProgresLampiran');
    const stepPrev=document.getElementById('pimpinanProgresPrev');
    const stepNext=document.getElementById('pimpinanProgresNext');
    // Panah nav topbar checklist -- muncul cuma kalau step-nya overflow 1 baris,
    // auto-disabled pas mentok ujung. Sama kayak refreshWizardTopbarNav() di
    // permintaan-laporan-deadline.blade.php.
    function refreshStepNav(){
      if(!stepPrev||!stepNext)return;
      const of=stepsEl.scrollWidth>stepsEl.clientWidth+1;
      stepPrev.hidden=!of;stepNext.hidden=!of;
      if(!of)return;
      stepPrev.disabled=stepsEl.scrollLeft<=0;
      stepNext.disabled=stepsEl.scrollLeft+stepsEl.clientWidth>=stepsEl.scrollWidth-1;
    }
    if(stepPrev&&stepPrev.dataset.navBound!=='1'){
      stepPrev.dataset.navBound='1';
      const pageScroll=function(d){stepsEl.scrollBy({left:d*Math.max(stepsEl.clientWidth-60,120),behavior:'smooth'});};
      stepPrev.addEventListener('click',function(){pageScroll(-1);});
      stepNext.addEventListener('click',function(){pageScroll(1);});
      stepsEl.addEventListener('scroll',refreshStepNav);
      window.addEventListener('resize',refreshStepNav);
    }
    function showTask(idx,items){
      // Garis penanda + dot current TUMBUH dari kosong ke penuh (transisi
      // 1.1s di CSS) TIAP pindah task -- sama persis dengan buildWizardTopbar()
      // di permintaan-laporan-deadline.blade.php. marker-in dilepas dari semua
      // step dulu, dipasang lagi ke step current 2 frame kemudian (double rAF)
      // biar transisinya beneran main dari state awalnya.
      if(modal)modal.dataset.plStep=idx;
      items.forEach(function(el,i){el.classList.toggle('wizard-step-current',i===idx);el.classList.remove('wizard-step-marker-in');});
      const cur=items[idx];
      if(cur){
        requestAnimationFrame(function(){requestAnimationFrame(function(){if(cur.classList.contains('wizard-step-current'))cur.classList.add('wizard-step-marker-in');});});
        // Scroll horizontal MINIMAL di dalam list doang (inline:'nearest' =
        // no-op kalau step-nya udah kelihatan), gak nyentuh ancestor lain.
        cur.scrollIntoView({inline:'nearest',block:'nearest'});
      }
      const t=tasks[idx];
      const lap=t&&t.laporan;
      const td=(t&&t.detail)||'';
      if(detailBodyEl)detailBodyEl.textContent=td||'Detail task tidak tersedia.';
      if(detailBtnEl)detailBtnEl.hidden=!td;
      document.getElementById('pimpinanTaskDetailModal')?.classList.remove('open');
      descEl.textContent=lap?'Checkpoint ini sudah dikerjakan satuan.':'Task ini belum dikerjakan satuan.';
      deskTa.value=lap?(lap.deskripsi||''):'';
      kendalaTa.value=lap?(lap.kendala||''):'';
      lampiranEl.innerHTML='';
      const lampiran=lap?(lap.lampiran||[]):[];
      if(lampiran.length){
        lampiran.forEach(function(x){
          const row=document.createElement('div');row.className='lampiran-file-row';
          const b=(window.siberadLampiranBadge&&window.siberadLampiranBadge(x.nama||x.url))||{text:'FILE',cls:'lfx-other'};
          const icon=document.createElement('span');icon.className='lampiran-file-row-icon '+b.cls;icon.textContent=b.text;
          const info=document.createElement('span');info.className='lampiran-file-row-info';
          const a=document.createElement('a');a.className='lampiran-file-row-name';a.href=x.url;a.target='_blank';a.rel='noopener';a.textContent=x.nama||'Lihat lampiran';
          const size=document.createElement('span');size.className='lampiran-file-row-size';size.textContent='Tersimpan';
          info.appendChild(a);info.appendChild(size);
          row.appendChild(icon);row.appendChild(info);
          lampiranEl.appendChild(row);
        });
      }else{
        const empty=document.createElement('div');empty.className='lampiran-file-list-empty';empty.textContent='Belum ada file yang diupload';
        lampiranEl.appendChild(empty);
      }
    }
    stepsEl.innerHTML='';
    if(!tasks.length){
      descEl.textContent='Tidak ada task untuk permintaan ini.';
      if(detailBodyEl)detailBodyEl.textContent='Detail task tidak tersedia.';
      if(detailBtnEl)detailBtnEl.hidden=true;
      document.getElementById('pimpinanTaskDetailModal')?.classList.remove('open');
      deskTa.value='';kendalaTa.value='';lampiranEl.innerHTML='';
      if(stepPrev)stepPrev.hidden=true;
      if(stepNext)stepNext.hidden=true;
    }else{
      let activeAssigned=false,defaultIdx=tasks.length-1;
      const items=tasks.map(function(t,i){
        let isActiveTask=false;
        if(t.selesai){}else if(!activeAssigned){activeAssigned=true;defaultIdx=i;isActiveTask=true;}
        // 3 state sama kayak buildWizardTopbar(): done / active (task berjalan
        // pertama) / pending (sisanya) -- pending bikin label abu + cursor
        // not-allowed, jadi step depan keliatan "belum kebuka".
        const state=t.selesai?'done':(isActiveTask?'active':'pending');
        const li=document.createElement('li');
        li.className='wizard-step wizard-step-'+state;
        li.title=t.deskripsi||'';
        const dot=document.createElement('span');dot.className='wizard-step-dot';
        if(t.selesai){dot.innerHTML='<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';}
        else{dot.textContent=String(i+1);}
        // Label = NAMA TASK (bukan angka) -- sama kayak topbar satuan.
        const label=document.createElement('span');label.className='wizard-step-label';label.textContent=t.deskripsi||('Task '+(i+1));
        li.appendChild(dot);li.appendChild(label);
        li.setAttribute('role','button');li.tabIndex=0;
        stepsEl.appendChild(li);
        return li;
      });
      items.forEach(function(li,i){
        li.addEventListener('click',function(){showTask(i,items)});
        li.addEventListener('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();showTask(i,items)}});
      });
      // showTask() ditunda ke SETELAH modal kelihatan (di blok mount di bawah)
      // -- kalau dipanggil sekarang (modal masih display:none), double-rAF
      // marker-in-nya jatuh pas belum ke-render jadi animasi tumbuhnya keskip.
      // refresh (realtime, modal udah kebuka) -> pertahanin step yang lagi
      // dibuka, bukan lompat balik ke defaultIdx.
      let startIdx=defaultIdx;
      if(refresh){const p=parseInt((modal&&modal.dataset.plStep)||'',10);if(!isNaN(p))startIdx=Math.max(0,Math.min(p,items.length-1));}
      ppItems=items;ppDefault=startIdx;
    }
    // Tombol kelola permintaan (Edit Deadline / Batalkan / Revisi) -- dipindah
    // ke sini dari modal "Detail Permintaan Laporan" biar modal itu murni
    // read-only. Disisipin sebelum tombol "Tutup" yang sudah ada; kalau modal
    // dibuka lagi, tombol lama dibersihin dulu (.pl-progres-action).
    const progresActions=document.getElementById('pimpinanProgresActions');
    if(progresActions){
      progresActions.querySelectorAll('.pl-progres-action').forEach(function(b){b.remove();});
      const statusP=button.dataset.status||'';
      let actionBtn=null;
      if(button.dataset.riwayat==='1'){
        // Kartu sudah di Riwayat Laporan -> modal "Lihat Progres" read-only
        // total, cuma tombol "Tutup". Aksi (Edit Deadline/Batalkan/Revisi/
        // Tolak/Terima) nggak relevan di sini; "Revisi" ada di menu titik-3
        // kartu Riwayat, sisanya di daftar Permintaan Laporan yang masih aktif.
      }else if(statusP==='Dibatalkan'||statusP==='Terlambat'){
        actionBtn=document.createElement('button');actionBtn.type='button';actionBtn.className='btn btn-edit-permintaan pl-progres-action';actionBtn.textContent='Edit Deadline';
        actionBtn.dataset.permintaanId=button.dataset.permintaanId||'';actionBtn.dataset.perihal=button.dataset.perihal||'';actionBtn.dataset.deadline=button.dataset.deadlineRaw||'';actionBtn.dataset.editable=button.dataset.editable||'0';actionBtn.dataset.alasan=button.dataset.alasan||'';
        // JANGAN tutup modal "Lihat Progres" -- konfirmasi (Edit Deadline /
        // Batalkan / Tolak / Terima) muncul DI ATAS-nya; kalau user batal,
        // dia balik ke Lihat Progres, bukan ke daftar kartu.
        actionBtn.addEventListener('click',function(){bukaEditDeadlinePermintaan(actionBtn);});
      }else if(statusP==='Sedang diproses'||statusP==='Terbaru'){
        // "Terbaru" (belum dikonfirmasi satuan) juga masih bisa dibatalkan
        // Pimpinan -- isDapatDibatalkan() di model ngizinin STATUS_BELUM.
        actionBtn=document.createElement('button');actionBtn.type='button';actionBtn.className='btn btn-batalkan-permintaan pl-progres-action';actionBtn.textContent='Batalkan';
        actionBtn.dataset.permintaanId=button.dataset.permintaanId||'';actionBtn.dataset.perihal=button.dataset.perihal||'';
        actionBtn.addEventListener('click',function(){bukaBatalkanPermintaan(actionBtn);});
      }else if(statusP==='Ditolak'){
        actionBtn=document.createElement('button');actionBtn.type='button';actionBtn.className='btn btn-revisi-permintaan pl-progres-action';actionBtn.textContent='Revisi';
        actionBtn.dataset.laporanId=button.dataset.laporanId||'';
        actionBtn.addEventListener('click',function(){bukaRevisiLaporanPimpinan(actionBtn);});
      }else if(statusP==='Menunggu'){
        // Laporan udah dikirim satuan & nunggu keputusan Pimpinan. Tombol
        // Tolak/Terima dulu numpang di #reportDetailModal ("Detail Aktivitas
        // Laporan") lewat data-pimpinan-review; sekarang ikut pindah ke sini
        // bareng tombol kelola lain, jadi "Lihat Detail" murni read-only.
        const lid=button.dataset.laporanId||'';
        const tolakBtn=document.createElement('button');tolakBtn.type='button';tolakBtn.className='btn pl-progres-action pl-progres-reject';tolakBtn.textContent='Tolak';
        tolakBtn.addEventListener('click',function(){bukaTolakLaporanPimpinan(lid);});
        const terimaBtn=document.createElement('button');terimaBtn.type='button';terimaBtn.className='btn pl-progres-action pl-progres-approve';terimaBtn.textContent='Terima';
        terimaBtn.addEventListener('click',function(){bukaTerimaLaporanPimpinan(lid);});
        progresActions.appendChild(tolakBtn);progresActions.appendChild(terimaBtn);
      }
      if(actionBtn)progresActions.appendChild(actionBtn);
    }
    // Mount pakai display (bukan cuma .open) + reflow -> transisi scale/slide
    // masuknya benar-benar main (gak nyeplak "kaku"), sama mekanisme kayak
    // #permintaanDetailModal. .pl-progres-has-steps -> kartu melebar 940px
    // kalau ada checklist, niru #kirimLaporanModal.wizard-active di satuan.
    if(modal){
      modal.classList.toggle('pl-progres-has-steps',tasks.length>0);
      if(refresh){
        // Modal udah kebuka (dipanggil ulang oleh realtime) -- cukup render
        // ulang isinya, TANPA animasi mount lagi.
        if(ppItems&&ppItems.length)showTask(ppDefault,ppItems);
        else{modal.dataset.plStep='';}
        refreshStepNav();
      }else{
        modal.classList.remove('open');
        modal.classList.add('pl-mounted');
        void modal.offsetWidth;
        requestAnimationFrame(function(){
          modal.classList.add('open');
          // Modal udah kelihatan & punya dimensi -> baru pilih step default
          // (biar animasi "tumbuh" marker-nya beneran main) + hitung overflow
          // panah nav. Waktu build tadi modal masih display:none (dimensi 0).
          requestAnimationFrame(function(){
            if(ppItems&&ppItems.length)showTask(ppDefault,ppItems);
            refreshStepNav();
          });
        });
      }
    }
  };
  // Dipanggil oleh syncPimpinanCards() (danpus-permintaan-arsip-mode.blade.php)
  // tiap kartu di-replace realtime -- kalau modal "Lihat Progres" lagi kebuka
  // buat permintaan itu, checklist + panel-nya ikut ke-refresh live.
  window.siberadRefreshPimpinanProgres=function(freshCard){
    const modal=document.getElementById('pimpinanProgresModal');
    if(!modal||!modal.classList.contains('open'))return;
    const id=(freshCard&&freshCard.getAttribute('data-realtime-permintaan-id'))||'';
    if(!id||id!==(modal.dataset.plPid||''))return;
    const btn=freshCard.querySelector('[onclick*="openPimpinanProgres"]');
    if(btn)window.openPimpinanProgres(btn,true);
  };
  window.tutupPimpinanProgres=function(){
    const m=document.getElementById('pimpinanProgresModal');
    if(!m)return;
    m.classList.remove('open');
    setTimeout(function(){if(!m.classList.contains('open'))m.classList.remove('pl-mounted');},240);
  };
  document.getElementById('pimpinanProgresTutupBtn')?.addEventListener('click',()=>tutupPimpinanProgres());
  document.addEventListener('keydown',e=>{
    if(e.key!=='Escape')return;
    if(!document.getElementById('pimpinanProgresModal')?.classList.contains('open'))return;
    // Ada konfirmasi (Batalkan/Tolak/Terima/Edit Deadline dll) numpuk di atas
    // "Lihat Progres"? Escape nutup yang paling atas dulu, jangan ikut nutup
    // modal ini juga.
    if(document.querySelector('.confirm-overlay.open'))return;
    if(document.getElementById('editDeadlinePermintaanModal')?.classList.contains('open'))return;
    // Sub-modal "Detail Task" numpuk di atas -> Escape tutup itu dulu.
    if(document.getElementById('pimpinanTaskDetailModal')?.classList.contains('open'))return;
    tutupPimpinanProgres();
  });

  // Sub-modal "Detail Task" (Pimpinan) -- tombol di header modal "Lihat
  // Progres". Sama perilaku dgn sisi Satuan: tanpa X, tutup lewat "Tutup"/Esc,
  // klik backdrop TIDAK menutup.
  (function(){
    const tdm=document.getElementById('pimpinanTaskDetailModal');
    if(!tdm)return;
    const close=()=>tdm.classList.remove('open');
    document.getElementById('pimpinanTaskDetailBtn')?.addEventListener('click',()=>tdm.classList.add('open'));
    document.getElementById('pimpinanTaskDetailModalClose')?.addEventListener('click',close);
    document.getElementById('pimpinanProgresTutupBtn')?.addEventListener('click',close);
    document.addEventListener('keydown',e=>{if(e.key==='Escape'&&tdm.classList.contains('open'))close();});
  })();

  window.bukaHapusRiwayatPimpinan=function(id){if(!id)return;document.getElementById('formHapusRiwayatPimpinan').action='{{ url('/laporan') }}/'+id;document.getElementById('reportDetailModal')?.classList.remove('open');document.getElementById('hapusRiwayatModal')?.classList.add('open')};
  window.tutupHapusRiwayatPimpinan=function(){document.getElementById('hapusRiwayatModal')?.classList.remove('open')};
  document.getElementById('hapusRiwayatClose')?.addEventListener('click',()=>tutupHapusRiwayatPimpinan());
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('hapusRiwayatModal')?.classList.contains('open'))tutupHapusRiwayatPimpinan()});

  window.bukaBatalkanPermintaan=function(button){const id=button.dataset.permintaanId;if(!id)return;document.getElementById('formBatalkanPermintaan').action='{{ url('/permintaan-laporan') }}/'+id+'/batal';document.getElementById('batalkanPermintaanPerihal').textContent=button.dataset.perihal?'"'+button.dataset.perihal+'"':'ini';document.getElementById('reportDetailModal')?.classList.remove('open');document.getElementById('batalkanPermintaanOverlay')?.classList.add('open')};
  window.tutupBatalkanPermintaan=function(){document.getElementById('batalkanPermintaanOverlay')?.classList.remove('open')};

  // ── AJAX submit buat aksi yang TIDAK boleh nutup modal "Lihat Progres" ──
  // Batalkan & Edit Deadline: submit biasa = full reload = modal "Lihat
  // Progres" ilang. Di sini fetch() PATCH, lalu cukup tutup overlay
  // konfirmasi-nya + segerin kartu & isi modal (via realtime helper).
  // Tolak/Terima SENGAJA tetap reload -- abis diputuskan, permintaannya
  // pindah ke Riwayat, kartunya emang hilang dari daftar aktif.
  window.siberadSubmitPermintaanAksiAjax=function(form,opts){
    opts=opts||{};
    if(!form||!form.getAttribute('action'))return;
    const btns=form.querySelectorAll('button[type="submit"]');
    btns.forEach(b=>b.disabled=true);
    const token=document.querySelector('input[name="_token"]')?.value||document.querySelector('meta[name="csrf-token"]')?.content||'';
    fetch(form.getAttribute('action'),{method:'POST',credentials:'same-origin',body:new FormData(form),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest',...(token?{'X-CSRF-TOKEN':token}:{})}})
      .then(async r=>{const data=await r.json().catch(()=>({}));if(!r.ok)throw new Error(data.message||(data.errors?Object.values(data.errors).flat()[0]:'')||'Aksi gagal diproses.');return data;})
      .then(data=>{
        opts.onSuccess&&opts.onSuccess(data);
        window.siberadShowToast&&window.siberadShowToast('success',data.status||opts.successMsg||'Berhasil.');
        window.siberadSyncPimpinanCardsNow&&window.siberadSyncPimpinanCardsNow();
        window.siberadLoadHistoryNow&&window.siberadLoadHistoryNow();
      })
      .catch(err=>{window.siberadShowToast&&window.siberadShowToast('error',err.message||'Aksi gagal diproses.');opts.onError&&opts.onError(err);})
      .finally(()=>{btns.forEach(b=>b.disabled=false);});
  };
  document.getElementById('formBatalkanPermintaan')?.addEventListener('submit',function(e){
    e.preventDefault();
    window.siberadSubmitPermintaanAksiAjax(this,{onSuccess:()=>tutupBatalkanPermintaan()});
  });
  window.bukaKonfirmasiArsipkanKendala=function(button){const action=button.dataset.action;if(!action)return;document.getElementById('formKonfirmasiArsipkanKendala').action=action;document.getElementById('konfirmasiArsipkanKendalaPerihal').textContent=button.dataset.perihal?'"'+button.dataset.perihal+'"':'ini';document.getElementById('reportDetailModal')?.classList.remove('open');document.getElementById('konfirmasiArsipkanKendalaOverlay')?.classList.add('open')};
  document.getElementById('konfirmasiArsipkanKendalaBatal')?.addEventListener('click',()=>document.getElementById('konfirmasiArsipkanKendalaOverlay')?.classList.remove('open'));
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('konfirmasiArsipkanKendalaOverlay')?.classList.contains('open'))document.getElementById('konfirmasiArsipkanKendalaOverlay').classList.remove('open')});
  window.bukaKonfirmasiSurat=function(action,token,pengirim){const form=document.getElementById('formKonfirmasiSurat');if(!form||!action)return;form.action=action;const tokenInput=form.querySelector('input[name="_token"]');if(tokenInput)tokenInput.value=token||'';const body=document.getElementById('konfirmasiSuratBody');if(body)body.textContent='Konfirmasi surat ini dari '+(pengirim||'-')+'? Pengirim akan mengetahui bahwa surat sudah diterima.';document.getElementById('reportDetailModal')?.classList.remove('open');document.getElementById('konfirmasiSuratOverlay')?.classList.add('open')};
  document.getElementById('konfirmasiSuratBatal')?.addEventListener('click',()=>document.getElementById('konfirmasiSuratOverlay')?.classList.remove('open'));
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('konfirmasiSuratOverlay')?.classList.contains('open'))document.getElementById('konfirmasiSuratOverlay').classList.remove('open')});
  window.bukaHapusArsipKendala=function(action,token,perihal){var overlay=document.getElementById('hapusArsipKendalaOverlay');if(!overlay)return;var form=document.getElementById('formHapusArsipKendala');form.action=action;form.querySelector('input[name="_token"]').value=token;var el=document.getElementById('hapusArsipKendalaPerihal');if(el)el.textContent=perihal?'"'+perihal+'"':'ini';overlay.classList.add('open');};
  document.getElementById('hapusArsipKendalaBatal')?.addEventListener('click',()=>document.getElementById('hapusArsipKendalaOverlay')?.classList.remove('open'));
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('hapusArsipKendalaOverlay')?.classList.contains('open'))document.getElementById('hapusArsipKendalaOverlay').classList.remove('open')});
  document.getElementById('batalkanPermintaanTutup')?.addEventListener('click',()=>tutupBatalkanPermintaan());
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('batalkanPermintaanOverlay')?.classList.contains('open'))tutupBatalkanPermintaan()});

  window.bukaTerimaLaporanPimpinan=function(laporanId){if(!laporanId)return;document.getElementById('formTerimaLaporanPimpinan').action='{{ url('/laporan') }}/'+laporanId+'/status';document.getElementById('reportDetailModal')?.classList.remove('open');document.getElementById('terimaLaporanOverlay')?.classList.add('open')};
  window.bukaTolakLaporanPimpinan=function(laporanId){if(!laporanId)return;document.getElementById('formTolakLaporanPimpinan').action='{{ url('/laporan') }}/'+laporanId+'/status';const catatan=document.getElementById('tolakLaporanCatatan');if(catatan)catatan.value='';document.getElementById('reportDetailModal')?.classList.remove('open');document.getElementById('tolakLaporanOverlay')?.classList.add('open')};
  document.getElementById('terimaLaporanBatal')?.addEventListener('click',()=>document.getElementById('terimaLaporanOverlay')?.classList.remove('open'));
  document.getElementById('tolakLaporanBatal')?.addEventListener('click',()=>document.getElementById('tolakLaporanOverlay')?.classList.remove('open'));
  document.addEventListener('keydown',e=>{if(e.key!=='Escape')return;document.getElementById('terimaLaporanOverlay')?.classList.remove('open');document.getElementById('tolakLaporanOverlay')?.classList.remove('open');document.getElementById('revisiLaporanOverlay')?.classList.remove('open')});

  window.bukaRevisiLaporanPimpinan=function(button){const laporanId=button?.dataset?.laporanId;if(!laporanId)return;document.getElementById('formRevisiLaporanPimpinan').action='{{ url('/laporan') }}/'+laporanId+'/status';document.getElementById('reportDetailModal')?.classList.remove('open');document.getElementById('revisiLaporanOverlay')?.classList.add('open')};
  document.getElementById('revisiLaporanBatal')?.addEventListener('click',()=>document.getElementById('revisiLaporanOverlay')?.classList.remove('open'));

  // Mount pakai display none<->flex + reflow + rAF -> transisi kartu masuk
  // benar-benar main (gak "kasar"), sama mekanisme kayak #permintaanDetailModal.
  function mountEditDeadlineModal(){
    const m=document.getElementById('editDeadlinePermintaanModal');
    if(!m)return;
    m.classList.remove('open');
    m.classList.add('pl-mounted');
    void m.offsetWidth;
    requestAnimationFrame(function(){m.classList.add('open');});
  }
  window.bukaEditDeadlinePermintaan=function(button){
    const id=button.dataset.permintaanId;if(!id)return;
    const editableView=document.getElementById('editDeadlineEditableView');
    const blockedView=document.getElementById('editDeadlineBlockedView');
    const titleEl=document.getElementById('editDeadlineModalTitle');
    const introEl=document.getElementById('editDeadlineModalIntro');
    const konfTitleEl=document.getElementById('editDeadlineKonfirmasiTitle');
    // Prefill "Tanggal Baru" / "Jam Baru" dari deadline lama -- sama persis
    // kayak Edit Deadline daftar aktif. Buat status Terlambat deadline lama
    // sudah lewat -> checkEditDeadlineValidity() langsung nge-flag invalid
    // (border merah) sampai Pimpinan geser ke waktu depan; itu memang perilaku
    // yang diinginkan.
    const [datePart,timePart]=(button.dataset.deadline||'').split('T');
    // Mode "revisi": dipanggil dari menu titik-3 kartu Riwayat Laporan.
    // Modal + picker + overlay konfirmasi yang SAMA, cuma: judul beda, form
    // di-POST ke .../revisi (bukan .../deadline), dan SELALU editable (item
    // arsip nggak pernah "blocked"). Backend-nya revisiDariRiwayat() yang
    // nge-reset archived_at + status -> DIKERJAKAN.
    if(button.dataset.mode==='revisi'){
      document.getElementById('formEditDeadlinePermintaan').action='{{ url('/permintaan-laporan') }}/'+id+'/revisi';
      if(titleEl)titleEl.textContent='Kirim Ulang untuk Revisi';
      if(konfTitleEl)konfTitleEl.textContent='Kirim Ulang untuk Revisi?';
      if(introEl)introEl.innerHTML='Beri deadline baru untuk <strong id="editDeadlinePerihal">'+(button.dataset.perihal?('"'+button.dataset.perihal+'"'):'permintaan ini')+'</strong>. Setelah disimpan, permintaan keluar dari Riwayat dan aktif kembali untuk satuan.';
      editDatePicker?.setPicked(datePart||null);
      editTimePicker?.setPicked(timePart||null);
      if(window.checkEditDeadlineValidity)window.checkEditDeadlineValidity();
      editableView.style.display='';blockedView.style.display='none';
      mountEditDeadlineModal();
      return;
    }
    if(titleEl)titleEl.textContent='Edit Deadline Permintaan';
    if(konfTitleEl)konfTitleEl.textContent='Simpan Deadline Baru?';
    if(introEl)introEl.innerHTML='Tentukan deadline baru untuk <strong id="editDeadlinePerihal">permintaan ini</strong>.';
    if(button.dataset.editable==='1'){
      document.getElementById('formEditDeadlinePermintaan').action='{{ url('/permintaan-laporan') }}/'+id+'/deadline';
      document.getElementById('editDeadlinePerihal').textContent=button.dataset.perihal?'"'+button.dataset.perihal+'"':'ini';
      editDatePicker?.setPicked(datePart||null);
      editTimePicker?.setPicked(timePart||null);
      editableView.style.display='';blockedView.style.display='none';
    }else{
      document.getElementById('editDeadlineBlockedReason').textContent=button.dataset.alasan||'Deadline permintaan ini tidak dapat diubah saat ini.';
      editableView.style.display='none';blockedView.style.display='';
    }
    mountEditDeadlineModal();
  };
  window.tutupEditDeadlinePermintaan=function(){
    const m=document.getElementById('editDeadlinePermintaanModal');
    if(!m)return;
    m.classList.remove('open');
    setTimeout(function(){if(!m.classList.contains('open'))m.classList.remove('pl-mounted');},240);
  };
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('editDeadlinePermintaanModal')?.classList.contains('open'))tutupEditDeadlinePermintaan()});

  (function(){
    const modal=document.getElementById('danpusRequestModal');
    const requestForm=modal?.querySelector('form');
    // Kunci scroll <body> selama modal buka -- sama pola dengan modal Profil
    // (document.body.style.overflow='hidden'). Tanpa ini, scroll di kartu
    // modal nyambung (scroll chaining) ke halaman berat di belakang begitu
    // mentok atas/bawah, jadi ikut repaint & nyendat. Guard `wasOpen` biar
    // Escape nggak nge-reset overflow kalau modal ini memang lagi ketutup
    // (mis. Escape ditekan buat modal lain).
    const openRequestModal=()=>{if(!modal)return;modal.classList.add('open');document.body.style.overflow='hidden'};
    const close=()=>{if(!modal)return;const wasOpen=modal.classList.contains('open');modal.classList.remove('open');resetTaskList();if(wasOpen)document.body.style.overflow=''};
    document.getElementById('danpusOpenRequestForm')?.addEventListener('click',openRequestModal);
    document.getElementById('danpusCloseRequestForm')?.addEventListener('click',close);
    document.getElementById('danpusCancelRequestForm')?.addEventListener('click',close);
    document.addEventListener('keydown',e=>{if(e.key==='Escape')close()});

    // Daftar task dinamis di form "Buat Permintaan" -- tiap satuan tujuan
    // dapat clone task yang sama persis (lihat PermintaanLaporanController::store).
    function makeTaskRow(){
      const row=document.createElement('div');
      row.className='danpus-task-row';
      row.innerHTML='<div class="danpus-task-row-inputs"><input type="text" name="tasks[]" required autocomplete="off" maxlength="255" placeholder="Judul task, cth: Kumpulkan data insiden minggu ini"><button type="button" class="danpus-task-remove" aria-label="Hapus task">&times;</button></div><textarea name="task_details[]" class="danpus-task-detail" required autocomplete="off" maxlength="2000" placeholder="Detail task: jelaskan apa yang harus dikerjakan satuan untuk task ini..."></textarea>';
      row.querySelectorAll('input,textarea').forEach(wireRequiredValidation);
      return row;
    }
    function resetTaskList(){
      const list=document.getElementById('danpusRequestTaskList');
      if(!list)return;
      list.innerHTML='';
      list.appendChild(makeTaskRow());
    }
    document.getElementById('danpusAddTaskBtn')?.addEventListener('click',()=>{
      document.getElementById('danpusRequestTaskList')?.appendChild(makeTaskRow());
    });
    document.getElementById('danpusRequestTaskList')?.addEventListener('click',e=>{
      const btn=e.target.closest('.danpus-task-remove');
      if(!btn)return;
      const list=document.getElementById('danpusRequestTaskList');
      if(list && list.children.length>1)btn.closest('.danpus-task-row')?.remove();
    });

    // ---- Wizard 3-step form "Buat Permintaan Laporan" ----
    // Step 1: Satuan Tujuan | Step 2: Perihal/Kategori/Instruksi/Deadline/
    // Prioritas | Step 3: Daftar Task untuk Satuan. Field yang required
    // TETAP required biar validasi native browser (checkValidity/'invalid'
    // event -> wireRequiredValidation) jalan sama persis kayak form biasa,
    // cuma AKTIF divalidasi pas step-nya lagi ditampilin (display:block) --
    // step yang display:none otomatis "barred" dari constraint validation,
    // jadi gak ganggu step lain.
    (function(){
      if(!modal)return;
      const steps=Array.from(modal.querySelectorAll('.danpus-step'));
      if(!steps.length)return;
      const indicatorItems=Array.from(modal.querySelectorAll('#danpusWizardIndicator .danpus-wizard-step-item'));
      const indicatorLines=Array.from(modal.querySelectorAll('#danpusWizardIndicator .danpus-wizard-line'));
      const nextBtn=document.getElementById('danpusStepNext');
      const backBtn=document.getElementById('danpusStepBack');
      const submitBtn=document.getElementById('danpusStepSubmit');
      let currentStep=1;

      function requiredFieldsOfStep(n){
        const stepEl=steps.find(s=>Number(s.dataset.step)===n);
        return stepEl?Array.from(stepEl.querySelectorAll('input[required],textarea[required]')):[];
      }

      function goToStep(n){
        currentStep=n;
        steps.forEach(s=>s.classList.toggle('is-active',Number(s.dataset.step)===n));
        indicatorItems.forEach((item,idx)=>{
          const num=idx+1;
          item.classList.toggle('is-done',num<n);
          item.classList.toggle('is-active',num===n);
        });
        indicatorLines.forEach((line,idx)=>{line.classList.toggle('is-done',idx+1<n)});
        if(backBtn)backBtn.style.display=n>1?'':'none';
        if(nextBtn)nextBtn.style.display=n<steps.length?'':'none';
        if(submitBtn)submitBtn.style.display=n===steps.length?'':'none';
        refreshStep3Indicator();
      }

      // Indikator step 3 ("Daftar Task"): centang hijau otomatis begitu SEMUA
      // baris task lengkap (judul + detail terisi) -- SELAGI user memang lagi
      // di step 3. Pencet "+ Tambah Task" (nambah baris kosong) -> centang
      // balik jadi nomor "3"; baris kosong itu dihapus lagi -> hijau lagi.
      function step3TasksComplete(){
        var rows=Array.prototype.slice.call(document.querySelectorAll('#danpusRequestTaskList .danpus-task-row'));
        if(!rows.length)return false;
        return rows.every(function(r){
          var t=r.querySelector('input[name="tasks[]"]');
          var d=r.querySelector('textarea[name="task_details[]"]');
          return t&&d&&t.value.trim()!==''&&d.value.trim()!=='';
        });
      }
      function refreshStep3Indicator(){
        var item=indicatorItems[2];
        if(!item)return;
        var done=currentStep===3&&step3TasksComplete();
        item.classList.toggle('is-done',done);
        item.classList.toggle('is-active',currentStep===3&&!done);
      }
      var danpusTaskListEl=document.getElementById('danpusRequestTaskList');
      danpusTaskListEl&&danpusTaskListEl.addEventListener('input',refreshStep3Indicator);
      danpusTaskListEl&&danpusTaskListEl.addEventListener('click',function(e){
        if(e.target.closest('.danpus-task-remove'))setTimeout(refreshStep3Indicator,0);
      });
      var danpusAddTaskBtnEl=document.getElementById('danpusAddTaskBtn');
      danpusAddTaskBtnEl&&danpusAddTaskBtnEl.addEventListener('click',function(){setTimeout(refreshStep3Indicator,0)});

      // Cek validitas SEMUA field required di step -- dipanggil manual
      // (bukan submit native) supaya nembak event 'invalid' yang sudah
      // di-wire wireRequiredValidation (border merah + pesan Indonesia),
      // TANPA nongolin validation bubble bawaan browser.
      function validateStep(n){
        let ok=true;
        requiredFieldsOfStep(n).forEach(el=>{if(!el.checkValidity())ok=false});
        return ok;
      }

      nextBtn?.addEventListener('click',()=>{
        if(!validateStep(currentStep))return;
        if(currentStep<steps.length)goToStep(currentStep+1);
      });
      backBtn?.addEventListener('click',()=>{if(currentStep>1)goToStep(currentStep-1)});

      // Klik lingkaran step yang udah "selesai" (is-done) buat balik edit
      // langsung tanpa pencet "Kembali" berulang kali.
      indicatorItems.forEach((item,idx)=>{
        item.addEventListener('click',()=>{
          const num=idx+1;
          if(num<currentStep)goToStep(num);
        });
      });

      // Validasi ulang MENYELURUH pas submit final -- jaga-jaga kalau user
      // balik ke step awal terus ngosongin isian yang tadinya udah valid.
      // Dicek step demi step SECARA URUT lewat goToStep() (biar field-nya
      // "aktif"/gak barred pas di-checkValidity()); begitu nemu step yang
      // gagal, wizard ditinggal nunjuk ke step itu (user liat pesan
      // errornya) dan submit dibatalin. Listener ini didaftarkan DUAL
      // sebelum listener confirm-overlay ("Kirim Permintaan Laporan?") di
      // bawah, jadi kalau ada step yang invalid, stopImmediatePropagation()
      // nahan biar dialog konfirmasi itu gak sempat muncul. Semua
      // perpindahan step di loop ini terjadi SINKRON dalam satu tick JS,
      // jadi browser cuma repaint sekali di akhir -- gak ada kedip visual
      // pindah-pindah step yang kelihatan user.
      requestForm?.addEventListener('submit',e=>{
        for(let n=1;n<=steps.length;n++){
          goToStep(n);
          if(!validateStep(n)){
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
          }
        }
        goToStep(steps.length);
      });

      // Reset wizard ke step 1 tiap modal ditutup, biar next kali dibuka
      // selalu mulai bersih dari awal (senada sama resetTaskList()).
      document.getElementById('danpusCloseRequestForm')?.addEventListener('click',()=>goToStep(1));
      document.getElementById('danpusCancelRequestForm')?.addEventListener('click',()=>goToStep(1));

      goToStep(1);
    })();

    // Klik baris tabel Permintaan Laporan (atau baris arsip di Riwayat
    // Laporan) buat expand/collapse checklist task milik satuan itu --
    // klik yang berasal dari tombol aksi (Lihat/Edit/Batal/Detail/dst)
    // sengaja diabaikan biar nggak ke-trigger dobel.
    //
    // Pasangan baris (tr -> baris task-nya) di-cache di properti JS
    // (tr._danpusTaskRow), BUKAN dibaca ulang dari nextElementSibling tiap
    // klik -- soalnya baris di tabel Riwayat/Status ikut diatur ulang
    // posisinya sama script sort/filter tabel (danpus-report-table-filter)
    // tiap ada data baru masuk lewat polling. Kalau reorder itu kebetulan
    // nyelip DI ANTARA klik buka & klik tutup, baca ulang nextElementSibling
    // bisa nunjuk ke baris yang salah (atau gak ketemu sama sekali) --
    // makanya begitu pasangannya ketemu pertama kali, disimpan permanen
    // biar toggle berikutnya selalu tepat sasaran walau posisinya geser.
    // Klik kadang kekirim dobel buat elemen yang sama (kejadian serupa juga
    // ditemukan & sengaja di-debounce di danpus-history-detail-fix.blade.php
    // buat tombol Detail) -- tanpa penjagaan ini, toggle bisa jalan 2x dalam
    // satu klik (kebuka lalu langsung ketutup lagi, atau sebaliknya) yang
    // kelihatannya kayak "gak bisa dibuka/ditutup".
    const danpusTaskRowLastToggle=new WeakMap();
    window.danpusToggleTaskRow=function(tr,event){
      if(!tr||event?.target?.closest('button'))return;
      const now=Date.now();
      if(now-(danpusTaskRowLastToggle.get(tr)||0)<300)return;
      danpusTaskRowLastToggle.set(tr,now);
      let taskRow=tr._danpusTaskRow;
      if(!taskRow||!taskRow.isConnected){
        taskRow=tr.nextElementSibling;
        if(!taskRow||!taskRow.classList.contains('request-task-row'))return;
        tr._danpusTaskRow=taskRow;
      }
      const isOpen=!taskRow.hasAttribute('hidden');
      if(isOpen){taskRow.setAttribute('hidden','')}else{taskRow.removeAttribute('hidden')}
      tr.classList.toggle('open',!isOpen);
    };

    // Perihal & Kategori otomatis huruf kapital semua begitu diketik --
    // posisi kursor dijaga biar nggak lompat ke akhir tiap kali ngetik di
    // tengah teks.
    function forceUppercase(el){
      el?.addEventListener('input',()=>{
        const pos=el.selectionStart;
        el.value=el.value.toUpperCase();
        el.setSelectionRange(pos,pos);
      });
    }
    forceUppercase(document.getElementById('danpusRequestPerihal'));
    forceUppercase(document.getElementById('danpusRequestKategori'));
    forceUppercase(document.getElementById('surat_perihal'));
    forceUppercase(document.getElementById('surat_kategori'));

    // Deadline dipecah jadi picker tanggal & jam kustom (bukan input
    // datetime-local/date/time bawaan browser) -- formatnya jadi konsisten
    // sama gaya tanggal Indonesia yang dipakai di tempat lain ("14 Agustus
    // 2026"), dan cuma bisa DIPILIH lewat kalender/daftar jam (readonly,
    // nggak bisa diketik manual). Nilai aslinya disimpan di
    // danpusPickedDate/danpusPickedTime, digabung ke field hidden
    // deadline_at pas form disubmit -- backend nggak perlu diubah.
    // Input readonly nggak reliable buat validasi `required` bawaan browser
    // (banyak browser skip validasi readonly), makanya masing-masing dikawal
    // input proxy tak-kasatmata yang di-sync tiap kali tanggal/jam dipilih.
    function pad2(n){return String(n).padStart(2,'0')}

    // Konfirmasi sebelum kirim -- pola & warna sama kayak "Kirim Laporan?"
    // di halaman Satuan (.confirm-overlay/.confirm-box bawaan dash-styles).
    // Submit asli di-tahan (preventDefault) sekali buat munculin dialog;
    // begitu "Ya, Kirim" diklik, form ditandai confirmed terus di-submit
    // ulang lewat requestSubmit() -- baru submit KEDUA ini yang lolos.
    (function(){
      const overlay=document.getElementById('danpusKirimPermintaanOverlay');
      if(!requestForm||!overlay)return;
      function closeConfirm(){overlay.classList.remove('open')}
      requestForm.addEventListener('submit',e=>{
        if(requestForm.dataset.confirmed==='1'){requestForm.dataset.confirmed='';return}
        e.preventDefault();
        overlay.classList.add('open');
      });
      document.getElementById('danpusKirimPermintaanYa')?.addEventListener('click',()=>{
        closeConfirm();
        requestForm.dataset.confirmed='1';
        requestForm.requestSubmit?requestForm.requestSubmit():requestForm.submit();
      });
      document.getElementById('danpusKirimPermintaanBatal')?.addEventListener('click',closeConfirm);
      document.addEventListener('keydown',e=>{if(e.key==='Escape'&&overlay.classList.contains('open'))closeConfirm()});
    })();

    // ---- Kalender tanggal -- fungsi generik dipakai ulang buat modal mana
    // pun yang butuh picker tanggal (Buat Permintaan & Edit Deadline),
    // dibedain lewat `ids` (semua id elemen terkait) biar widgetnya beneran
    // identik satu sistem, bukan disalin manual per modal. ----
    function initDanpusDatePicker(ids){
      const input=document.getElementById(ids.input);
      const proxy=document.getElementById(ids.proxy);
      const picker=document.getElementById(ids.picker);
      const panel=document.getElementById(ids.panel);
      const daysEl=document.getElementById(ids.days);
      const titleEl=document.getElementById(ids.title);
      const confirmBtn=document.getElementById(ids.confirm);
      if(!panel||!input)return null;
      const monthNames=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
      const [minY,minM,minD]=(panel.dataset.min||'').split('-').map(Number);
      const today=new Date();
      let viewY=today.getFullYear(),viewM=today.getMonth();
      // stagedDate = tanggal yang lagi disorot/dipilih SEMENTARA di dalam
      // panel (belum tentu final) -- baru dipindah ke pickedDate (nilai
      // beneran yang masuk ke input & lolos validasi) begitu tombol
      // Konfirmasi diklik. Jadi klik satu tanggal doang belum "keinput".
      let stagedDate=null;
      let pickedDate=null;
      function isBeforeMin(y,m,d){
        if(!panel.dataset.min)return false;
        if(y!==minY)return y<minY;
        if(m+1!==minM)return m+1<minM;
        return d<minD;
      }
      // Nyetel .value doang lewat JS nggak nge-trigger event 'input'/'change'
      // bawaan browser, padahal validator umum (di bawah) ngedengerin DUA
      // event itu buat nyembunyiin lagi pesan wajib-diisi begitu field-nya
      // udah keisi. Dispatch manual 'change' di sini biar clearInvalid()
      // punya validator umum itu yang jalanin -- dia yang paling tau di
      // mana persis elemen pesannya nempel (closure-nya udah nyimpen
      // referensinya), daripada nebak-nebak lewat nextElementSibling yang
      // gampang meleset kalau struktur DOM-nya berubah.
      function markSelected(){
        if(!proxy)return;
        proxy.value='x';
        proxy.dispatchEvent(new Event('change',{bubbles:true}));
      }
      function render(){
        titleEl.textContent=monthNames[viewM]+' '+viewY;
        daysEl.innerHTML='';
        const firstDow=new Date(viewY,viewM,1).getDay();
        const daysInMonth=new Date(viewY,viewM+1,0).getDate();
        const daysInPrev=new Date(viewY,viewM,0).getDate();
        for(let i=0;i<firstDow;i++){
          const cell=document.createElement('div');cell.className='danpus-calendar-day is-muted';cell.textContent=daysInPrev-firstDow+1+i;daysEl.appendChild(cell);
        }
        for(let d=1;d<=daysInMonth;d++){
          const cell=document.createElement('div');cell.className='danpus-calendar-day';cell.textContent=d;
          const isToday=viewY===today.getFullYear()&&viewM===today.getMonth()&&d===today.getDate();
          if(isToday)cell.classList.add('is-today');
          if(isBeforeMin(viewY,viewM,d))cell.classList.add('is-disabled');
          if(stagedDate===viewY+'-'+pad2(viewM+1)+'-'+pad2(d))cell.classList.add('is-selected');
          cell.addEventListener('click',()=>{
            stagedDate=viewY+'-'+pad2(viewM+1)+'-'+pad2(d);
            if(confirmBtn)confirmBtn.disabled=false;
            render();
          });
          daysEl.appendChild(cell);
        }
        const trailing=(7-((firstDow+daysInMonth)%7))%7;
        for(let i=1;i<=trailing;i++){
          const cell=document.createElement('div');cell.className='danpus-calendar-day is-muted';cell.textContent=i;daysEl.appendChild(cell);
        }
      }
      const backdrop=document.getElementById(ids.backdrop);
      // .danpus-request-form-card/.report-modal-card punya `transform` buat
      // animasi buka/tutup modal -- itu bikin SEMUA descendant position:fixed
      // di dalamnya (termasuk panel ini) jadi nempel relatif ke card itu,
      // BUKAN ke viewport (aturan CSS: ancestor ber-transform jadi containing
      // block buat fixed descendant). Akibatnya panel ke-skip ke-stacking-
      // context card sendiri dan kalah tampil di bawah backdrop, jadi nggak
      // kesentuh. "Dipindah" jadi anak langsung <body> pas dibuka supaya
      // benar-benar lepas dari jebakan itu.
      function open(){
        document.body.appendChild(panel);panel.classList.add('open');backdrop?.classList.add('open');
        stagedDate=pickedDate;if(confirmBtn)confirmBtn.disabled=!stagedDate;
        if(pickedDate){const [y,m]=pickedDate.split('-').map(Number);viewY=y;viewM=m-1}
        render();
      }
      function close(){panel.classList.remove('open');backdrop?.classList.remove('open')}
      function toggle(e){e.stopPropagation();panel.classList.contains('open')?close():open()}
      input.addEventListener('click',toggle);
      picker.querySelector('.danpus-picker-icon')?.addEventListener('click',toggle);
      confirmBtn?.addEventListener('click',e=>{
        e.stopPropagation();
        if(!stagedDate)return;
        pickedDate=stagedDate;
        const [y,m,d]=stagedDate.split('-').map(Number);
        input.value=d+' '+monthNames[m-1]+' '+y;
        markSelected();
        close();
        ids.onPick?.();
      });
      document.getElementById(ids.prev)?.addEventListener('click',e=>{e.stopPropagation();viewM--;if(viewM<0){viewM=11;viewY--}render()});
      document.getElementById(ids.next)?.addEventListener('click',e=>{e.stopPropagation();viewM++;if(viewM>11){viewM=0;viewY++}render()});
      document.getElementById(ids.close)?.addEventListener('click',e=>{e.stopPropagation();close()});
      panel.addEventListener('click',e=>e.stopPropagation());
      document.addEventListener('click',close);
      return {
        setPicked(dateStr){
          pickedDate=dateStr||null;
          if(dateStr){const [y,m,d]=dateStr.split('-').map(Number);input.value=d+' '+monthNames[m-1]+' '+y;markSelected()}
          else{input.value=''}
          ids.onPick?.();
        },
        getPicked(){return pickedDate}
      };
    }

    // ---- Jam: wheel picker digital (scroll-snap), konsep sama kayak
    // kalender tanggal -- klik ikon, panel kebuka di tengah, geser wheel
    // Jam & Menit (otomatis snap ke tengah), baru "keinput" beneran begitu
    // Konfirmasi diklik. Ganti total dari jam analog (klik+drag lingkaran,
    // susah dipakai) maupun dropdown <select> biasa (kesannya kayak filter,
    // kurang pas buat jam) -- wheel angka digital lebih cepat & familiar.
    // ---- Jam -- fungsi generik, pola sama kayak initDanpusDatePicker di atas ----
    function initDanpusTimePicker(ids){
      const input=document.getElementById(ids.input);
      const proxy=document.getElementById(ids.proxy);
      const picker=document.getElementById(ids.picker);
      const panel=document.getElementById(ids.panel);
      const hourWheel=document.getElementById(ids.hourWheel);
      const minuteWheel=document.getElementById(ids.minuteWheel);
      const confirmBtn=document.getElementById(ids.confirm);
      if(!panel||!input||!hourWheel||!minuteWheel)return null;
      const ITEM_H=36;
      // Wheel-nya dibuat "muter" tanpa ujung/pangkal -- daftar angkanya
      // digandain COPIES kali (0..23 atau 0..59 diulang berkali-kali),
      // mulai digulir dari copy paling TENGAH. Begitu penggunanya gulir
      // sampai nyasar ke copy sebelah, langsung digeser instan (bukan
      // animasi) balik ke copy tengah -- karena isinya identik di semua
      // copy, lompatannya nggak keliatan sama sekali. Efeknya: dari 23
      // bisa langsung nyambung ke 0 (atau sebaliknya) tanpa harus gulir
      // balik ke ujung.
      const COPIES=7;
      const MID=Math.floor(COPIES/2);
      let stagedHour=null,stagedMinute=null;
      let pickedTime=null;

      function buildWheel(container,count){
        container.innerHTML='';
        container.appendChild(Object.assign(document.createElement('div'),{className:'danpus-wheel-pad'}));
        const items=[];
        for(let c=0;c<COPIES;c++){
          for(let i=0;i<count;i++){
            const el=document.createElement('div');
            el.className='danpus-wheel-item';
            el.textContent=pad2(i);
            container.appendChild(el);
            items.push(el);
          }
        }
        container.appendChild(Object.assign(document.createElement('div'),{className:'danpus-wheel-pad'}));
        return items;
      }
      function setupWheel(container,items,count,setStaged){
        function liveAbsIndex(){
          return Math.max(0,Math.min(items.length-1,Math.round(container.scrollTop/ITEM_H)));
        }
        function applyCenter(absIdx){
          items.forEach((el,i)=>el.classList.toggle('is-center',i===absIdx));
        }
        // select() nyimpen nilai staged & nge-highlight TERPISAH dari
        // scrollTo() -- sengaja urutannya gitu (select dulu baru animasi
        // snap) biar nilai yang kepilih tetap ke-simpan walau animasi
        // scroll-nya gagal/nggak didukung browser tertentu.
        function select(absIdx){
          applyCenter(absIdx);
          setStaged(pad2(absIdx%count));
        }
        function recenter(absIdx){
          const copy=Math.floor(absIdx/count);
          if(copy===MID)return;
          container.scrollTop=container.scrollTop+(MID-copy)*count*ITEM_H;
          applyCenter(MID*count+(absIdx%count));
        }
        items.forEach((el,absIdx)=>{
          el.addEventListener('click',()=>{
            select(absIdx);
            container.scrollTo({top:absIdx*ITEM_H,behavior:'smooth'});
          });
        });
        let t=null;
        container.addEventListener('scroll',()=>{
          applyCenter(liveAbsIndex());
          clearTimeout(t);
          t=setTimeout(()=>{
            const settled=liveAbsIndex();
            select(settled);
            recenter(settled);
          },120);
        });
        return {
          setValue(valueIdx){
            const absIdx=MID*count+valueIdx;
            container.scrollTop=absIdx*ITEM_H;
            select(absIdx);
          },
        };
      }
      const hourItems=buildWheel(hourWheel,24);
      const minuteItems=buildWheel(minuteWheel,60);
      const hourCtl=setupWheel(hourWheel,hourItems,24,v=>{stagedHour=v});
      const minuteCtl=setupWheel(minuteWheel,minuteItems,60,v=>{stagedMinute=v});

      // Nyetel .value doang lewat JS nggak nge-trigger event 'input'/'change'
      // bawaan browser, padahal validator umum (di bawah) ngedengerin DUA
      // event itu buat nyembunyiin lagi pesan wajib-diisi begitu field-nya
      // udah keisi. Dispatch manual 'change' di sini biar clearInvalid()
      // punya validator umum itu yang jalanin -- dia yang paling tau di
      // mana persis elemen pesannya nempel (closure-nya udah nyimpen
      // referensinya), daripada nebak-nebak lewat nextElementSibling yang
      // gampang meleset kalau struktur DOM-nya berubah.
      function markSelected(){
        if(!proxy)return;
        proxy.value='x';
        proxy.dispatchEvent(new Event('change',{bubbles:true}));
      }
      const backdrop=document.getElementById(ids.backdrop);
      function open(){
        document.body.appendChild(panel);
        panel.classList.add('open');
        backdrop?.classList.add('open');
        const now=new Date();
        const [hh,mm]=(pickedTime||'').split(':');
        const initHour=hh?parseInt(hh,10):now.getHours();
        const initMinute=mm?parseInt(mm,10):now.getMinutes();
        hourCtl.setValue(initHour);
        minuteCtl.setValue(initMinute);
      }
      function close(){panel.classList.remove('open');backdrop?.classList.remove('open')}
      function toggle(e){e.stopPropagation();panel.classList.contains('open')?close():open()}
      input.addEventListener('click',toggle);
      picker.querySelector('.danpus-picker-icon')?.addEventListener('click',toggle);
      confirmBtn?.addEventListener('click',e=>{
        e.stopPropagation();
        if(!stagedHour||!stagedMinute)return;
        pickedTime=stagedHour+':'+stagedMinute;
        input.value=stagedHour+':'+stagedMinute;
        markSelected();
        close();
        ids.onPick?.();
      });
      document.getElementById(ids.close)?.addEventListener('click',e=>{e.stopPropagation();close()});
      panel.addEventListener('click',e=>e.stopPropagation());
      document.addEventListener('click',close);
      return {
        setPicked(timeStr){
          pickedTime=timeStr||null;
          input.value=timeStr||'';
          if(timeStr)markSelected();
          ids.onPick?.();
        },
        getPicked(){return pickedTime}
      };
    }

    const buatDatePicker=initDanpusDatePicker({input:'danpusRequestDeadlineDate',proxy:'danpusRequestDeadlineDateProxy',picker:'danpusDatePicker',panel:'danpusDeadlineCalendar',days:'danpusCalendarDays',title:'danpusCalendarTitle',confirm:'danpusCalendarConfirm',prev:'danpusCalendarPrev',next:'danpusCalendarNext',close:'danpusCalendarClose',backdrop:'danpusPickerBackdrop'});
    const buatTimePicker=initDanpusTimePicker({input:'danpusRequestDeadlineTime',proxy:'danpusRequestDeadlineTimeProxy',picker:'danpusTimePicker',panel:'danpusDeadlineClock',hourWheel:'danpusWheelHour',minuteWheel:'danpusWheelMinute',confirm:'danpusClockConfirm',close:'danpusClockClose',backdrop:'danpusPickerBackdrop'});
    const danpusDeadlineHidden=document.getElementById('danpusRequestDeadline');
    requestForm?.addEventListener('submit',()=>{
      const d=buatDatePicker?.getPicked(),t=buatTimePicker?.getPicked();
      if(danpusDeadlineHidden&&d&&t)danpusDeadlineHidden.value=d+'T'+t;
    });

    // window.* -- bukaEditDeadlinePermintaan() didefinisikan di scope LUAR
    // IIFE ini (satu blok sama kayak bukaBatalkanPermintaan dkk), jadi
    // nggak bisa lihat const biasa di sini lewat closure. Ditaruh di
    // window biar bisa dijangkau dari sana, sama kayak fungsi buka/tutup
    // modal lainnya di file ini.
    //
    // Validasi "deadline baru harus lebih besar dari sekarang" ditempel
    // lewat setCustomValidity() ke proxy tanggal -- BUKAN dengan nge-disable
    // tanggal/jam di kalender/wheel. Efeknya: begitu tombol Simpan diklik
    // dan kombinasinya masih melanggar, field ini otomatis kena 'invalid'
    // native browser & nongol pesan merah lewat validator umum yang sama
    // kayak field wajib-diisi lainnya di form ini (bukan validasi baru yang
    // beda gaya). Sengaja CUMA dibanding ke sekarang, bukan ke deadline
    // lama -- deadline lama bisa aja masih di masa depan (kasus
    // Ditolak/Dibatalkan sebelum deadline lewat), dan nggak ada alasan
    // Pimpinan wajib kasih waktu lebih lama dari deadline aslinya.
    window.checkEditDeadlineValidity=function(){
      const dateProxy=document.getElementById('editDeadlineDateProxy');
      const timeProxy=document.getElementById('editDeadlineTimeProxy');
      if(!dateProxy||!timeProxy)return;
      const d=window.editDatePicker?.getPicked(),t=window.editTimePicker?.getPicked();
      // Belum lengkap -> serahkan ke validasi "wajib diisi" biasa.
      if(!d||!t){dateProxy.setCustomValidity('');timeProxy.setCustomValidity('');return}
      const now=new Date();
      const pad=n=>String(n).padStart(2,'0');
      const todayStr=now.getFullYear()+'-'+pad(now.getMonth()+1)+'-'+pad(now.getDate());
      const nowStr=todayStr+'T'+pad(now.getHours())+':'+pad(now.getMinutes());
      const combined=d+'T'+t;
      // Arahkan pesan "sudah lewat" ke KOLOM yang benar: kalau tanggalnya
      // sendiri hari lampau -> kolom Tanggal Baru yang nyala merah; kalau
      // tanggalnya hari ini/masih depan tapi kombinasi tanggal+jam <= sekarang
      // -> berarti JAM-nya yang bikin lewat, jadi kolom Jam Baru yang merah.
      let dateMsg='',timeMsg='';
      if(d<todayStr){
        dateMsg='Tanggal deadline sudah lewat.';
      }else if(combined<=nowStr){
        timeMsg='Jam deadline sudah lewat untuk hari ini.';
      }
      dateProxy.setCustomValidity(dateMsg);
      timeProxy.setCustomValidity(timeMsg);
      // Border merah + pesan muncul LANGSUNG di kolom yang salah (bukan nunggu
      // tombol Simpan): checkValidity() nembak event 'invalid' yang sudah
      // dipasang wireRequiredValidation (nambah .field-invalid + pesan) tanpa
      // bubble native (di-preventDefault di listener-nya). Kolom yang valid
      // ke-clear sendiri lewat event 'change' dari markSelected() tiap re-pick.
      if(dateMsg)dateProxy.checkValidity();
      if(timeMsg)timeProxy.checkValidity();
    };
    window.editDatePicker=initDanpusDatePicker({input:'editDeadlineDateInput',proxy:'editDeadlineDateProxy',picker:'editDeadlineDatePicker',panel:'editDeadlineCalendar',days:'editDeadlineCalendarDays',title:'editDeadlineCalendarTitle',confirm:'editDeadlineCalendarConfirm',prev:'editDeadlineCalendarPrev',next:'editDeadlineCalendarNext',close:'editDeadlineCalendarClose',backdrop:'danpusPickerBackdrop',onPick:checkEditDeadlineValidity});
    window.editTimePicker=initDanpusTimePicker({input:'editDeadlineTimeInput',proxy:'editDeadlineTimeProxy',picker:'editDeadlineTimePicker',panel:'editDeadlineClock',hourWheel:'editDeadlineWheelHour',minuteWheel:'editDeadlineWheelMinute',confirm:'editDeadlineClockConfirm',close:'editDeadlineClockClose',backdrop:'danpusPickerBackdrop',onPick:checkEditDeadlineValidity});
    const formEditDeadline=document.getElementById('formEditDeadlinePermintaan');
    formEditDeadline?.addEventListener('submit',()=>{
      const d=editDatePicker?.getPicked(),t=editTimePicker?.getPicked();
      const editDeadlineHidden=document.getElementById('editDeadlineHidden');
      if(editDeadlineHidden&&d&&t)editDeadlineHidden.value=d+'T'+t;
    });

    // Konfirmasi sebelum simpan -- pola & warna sama kayak "Kirim
    // Permintaan Laporan?" (.confirm-overlay/.confirm-box bawaan
    // dash-styles). Submit asli ditahan (preventDefault) sekali buat
    // munculin dialog; begitu "Ya, Simpan" diklik, form ditandai confirmed
    // terus di-submit ulang lewat requestSubmit() -- baru submit KEDUA
    // ini yang lolos (nggak lolos lagi kalau ternyata masih invalid,
    // native browser validation tetap jalan duluan di submit kedua ini).
    (function(){
      const overlay=document.getElementById('editDeadlineKonfirmasiOverlay');
      if(!formEditDeadline||!overlay)return;
      function closeConfirm(){overlay.classList.remove('open')}
      // Submit "Simpan" -> native validation jalan duluan (kalau invalid,
      // submit gak nembak, border merah nongol). Kalau valid -> tahan submit,
      // munculin dialog konfirmasi.
      formEditDeadline.addEventListener('submit',e=>{
        e.preventDefault();
        overlay.classList.add('open');
      });
      document.getElementById('editDeadlineKonfirmasiYa')?.addEventListener('click',()=>{
        closeConfirm();
        // Isi hidden deadline_at (biasanya di-set listener submit form).
        const d=window.editDatePicker?.getPicked(),t=window.editTimePicker?.getPicked();
        const hidden=document.getElementById('editDeadlineHidden');
        if(hidden&&d&&t)hidden.value=d+'T'+t;
        // AJAX -> modal "Lihat Progres" (kalau lagi kebuka) TETAP terbuka;
        // cuma modal Edit Deadline-nya yang ditutup + kartu/isi disegerin.
        window.siberadSubmitPermintaanAksiAjax(formEditDeadline,{onSuccess:()=>tutupEditDeadlinePermintaan()});
      });
      document.getElementById('editDeadlineKonfirmasiBatal')?.addEventListener('click',closeConfirm);
      document.addEventListener('keydown',e=>{if(e.key==='Escape'&&overlay.classList.contains('open'))closeConfirm()});
    })();

    // Validasi wajib-diisi custom (senada sama form login di landing page):
    // ganti tooltip bawaan browser jadi pesan Bahasa Indonesia + border
    // merah, dan otomatis ke-reset begitu penggunanya klik/isi ulang field
    // itu lagi.
    const requestErrorMessages={
      danpusRequestPerihal:'Perihal wajib diisi.',
      danpusRequestKategori:'Kategori wajib diisi.',
      danpusRequestInstruksi:'Instruksi wajib diisi.',
      danpusRequestDeadlineDateProxy:'Tanggal deadline wajib diisi.',
      danpusRequestDeadlineTimeProxy:'Jam deadline wajib diisi.',
      danpusRequestSatuanProxy:'Pilih minimal satu satuan tujuan.',
      editDeadlineDateProxy:'Tanggal deadline wajib diisi.',
      editDeadlineTimeProxy:'Jam deadline wajib diisi.',
    };
    function requestErrorFor(input){
      // Field yang divalidasi lewat setCustomValidity() (mis. deadline
      // baru harus lebih besar dari deadline lama) bawa pesannya sendiri
      // di validationMessage -- diprioritaskan di atas pesan generik per-id.
      if(input.validity?.customError&&input.validationMessage)return input.validationMessage;
      if(requestErrorMessages[input.id])return requestErrorMessages[input.id];
      if(input.name==='prioritas')return 'Pilih salah satu prioritas.';
      if(input.name==='tasks[]')return 'Judul task wajib diisi.';
      if(input.name==='task_details[]')return 'Detail task wajib diisi.';
      return 'Kolom ini wajib diisi.';
    }
    // Checkbox/radio group nggak bisa dianggap satu "field" tunggal --
    // semuanya diarahkan ke wadah bersama (.priority-toggle / proxy
    // checkbox satuan) supaya cuma satu pesan error yang muncul per grup,
    // bukan numpuk di tiap opsi. Query dari kedua form (Buat Permintaan &
    // Edit Deadline) sekaligus -- widget tanggal/jam-nya sama, jadi validasi
    // custom-nya juga disamain, bukan cuma tampilannya doang.
    // Diekstrak jadi function bernama (bukan cuma callback forEach inline)
    // supaya bisa dipanggil ULANG buat baris task BARU yang dibuat belakangan
    // lewat "+ Tambah Task" (lihat makeTaskRow) -- tanpa ini, cuma baris
    // task pertama (yang statis dari Blade, ke-jaring loop querySelectorAll
    // di bawah pas load) yang dapet border merah + pesan "wajib diisi" yang
    // sama kayak field lain; baris tambahan cuma dapet tooltip bawaan browser.
    function wireRequiredValidation(input){
      if(!input||input.dataset.requiredWired==='1')return;
      input.dataset.requiredWired='1';
      // Tanggal & jam masing-masing diarahkan ke wadah SENDIRI-SENDIRI
      // (.danpus-deadline-part), bukan wadah gabungan (.danpus-deadline-split)
      // -- supaya pesan wajib-diisinya kepisah jelas per field. Tapi
      // .danpus-deadline-part sendiri adalah kolom di DALAM baris flex
      // horizontal (.danpus-deadline-split) -- kalau pesannya ditaruh
      // sebagai SIBLING (insertAdjacentElement afterend) dia malah jadi
      // kolom ke-3 di baris yang sama, geser ke samping, bukan di bawah.
      // Makanya khusus kasus ini pesannya di-append jadi ANAK di dalam
      // .danpus-deadline-part (yang sendiri flex-direction:column), biar
      // nempel di bawah kolom tanggal/jam itu masing-masing, kayak field lain.
      //
      // Pengecualian: pesan "deadline baru harus lebih besar dari deadline
      // lama" (setCustomValidity di editDeadlineDateProxy) itu soal
      // KOMBINASI tanggal+jam, bukan spesifik kolom Tanggal doang -- jadi
      // anchor-nya dinaikkan ke .danpus-request-field (bungkus SELURUH
      // baris tanggal+jam), biar pesannya lebar penuh & nongol di bawah
      // kedua kolom sekaligus, bukan keselip sempit di bawah kolom Tanggal
      // Baru doang kayak pesan wajib-diisi biasa.
      const deadlinePart=input.id==='editDeadlineDateProxy'
        ? input.closest('.danpus-request-field')
        : input.closest('.danpus-deadline-part');
      // Task row (.danpus-task-row) itu flex-column: [.danpus-task-row-inputs
      // (judul + tombol hapus)] lalu [textarea .danpus-task-detail]. DUA field
      // wajib itu (tasks[] & task_details[]) masing-masing dapat pesan error
      // SENDIRI yang diselipin PERSIS di bawah field-nya (judul -> setelah
      // .danpus-task-row-inputs; detail -> setelah textarea), tapi tetap di
      // DALAM .danpus-task-row. Jadi: (1) ke-hapus bareng pas "Hapus task"
      // diklik (nggak ninggalin pesan "yatim"), dan (2) nggak nambah
      // #danpusRequestTaskList.children.length (dipakai cek "minimal 1 baris"
      // di tombol Hapus), karena pesannya anak .danpus-task-row bukan anak
      // list. Pesannya dibedain per-field biar nggak saling nimpa (judul
      // kelar diisi tapi pesan detail ikut ke-hide padahal masih kosong).
      const taskRefEl=input.name==='tasks[]'
        ? input.closest('.danpus-task-row-inputs')
        : (input.name==='task_details[]' ? input : null);
      const deadlineAnchor=deadlinePart||input.closest('.priority-toggle')||input;
      const appendAsChild=!!deadlinePart;
      let msg;
      if(taskRefEl){
        msg=taskRefEl.nextElementSibling;
        if(!msg||!msg.classList.contains('danpus-request-error')){
          msg=document.createElement('span');
          msg.className='danpus-request-error';
          msg.style.display='none';
          taskRefEl.insertAdjacentElement('afterend',msg);
        }
      }else{
        msg=appendAsChild?deadlineAnchor.querySelector(':scope > .danpus-request-error'):deadlineAnchor.nextElementSibling;
        if(!msg||!msg.classList.contains('danpus-request-error')){
          msg=document.createElement('span');
          msg.className='danpus-request-error';
          msg.style.display='none';
          if(appendAsChild)deadlineAnchor.appendChild(msg);
          else deadlineAnchor.insertAdjacentElement('afterend',msg);
        }
      }
      input.addEventListener('invalid',e=>{
        e.preventDefault();
        input.classList.add('field-invalid');
        msg.textContent=requestErrorFor(input);
        msg.style.display='flex';
      });
      function clearInvalid(){
        input.classList.remove('field-invalid');
        msg.style.display='none';
      }
      input.addEventListener('input',clearInvalid);
      // Checkbox/radio (satuan proxy, prioritas) cuma nembak 'change' pas
      // di-toggle, nggak konsisten nembak 'input' di semua browser --
      // makanya clear-nya digantung ke dua-duanya.
      input.addEventListener('change',clearInvalid);
    }
    document.querySelectorAll('#danpusRequestModal input[required],#danpusRequestModal textarea[required],#editDeadlinePermintaanModal input[required]').forEach(wireRequiredValidation);

    const selectAllBtn=document.getElementById('danpusSelectAllSatuan');
    const satuanChecks=(kategori)=>Array.from(modal?.querySelectorAll(kategori?'input[name="tujuan_satuan_ids[]"][data-kategori="'+kategori+'"]':'input[name="tujuan_satuan_ids[]"]')||[]);
    // Chip kategori (Unsur Pelayanan/Unsur Pembantu Pimpinan/Direktorat/
    // Satlak/Kasansi) di bawah label "Satuan Tujuan" itu tombol pilih-semua
    // KHUSUS kategori itu -- namanya sendiri jadi labelnya (gak pernah
    // berubah teks), status "semua tercentang" ditandai lewat class
    // .is-active (disorot emas), bukan ganti teks kayak tombol global.
    const kategoriChips=()=>Array.from(modal?.querySelectorAll('.danpus-request-kategori-chip')||[]);
    function syncSelectAllLabel(){
      if(!selectAllBtn)return;
      const boxes=satuanChecks();
      const allChecked=boxes.length>0&&boxes.every(b=>b.checked);
      const label=selectAllBtn.querySelector('.danpus-request-selectall-label');
      if(label)label.textContent=allChecked?'Batalkan Semua':'Pilih Semua';else selectAllBtn.textContent=allChecked?'Batalkan Semua':'Pilih Semua';
    }
    function syncKategoriChips(){
      kategoriChips().forEach(chip=>{
        const boxes=satuanChecks(chip.dataset.kategori);
        const allChecked=boxes.length>0&&boxes.every(b=>b.checked);
        chip.classList.toggle('is-active',allChecked);
      });
    }
    selectAllBtn?.addEventListener('click',()=>{
      const boxes=satuanChecks();
      const allChecked=boxes.length>0&&boxes.every(b=>b.checked);
      boxes.forEach(b=>{b.checked=!allChecked});
      syncSelectAllLabel();syncKategoriChips();syncSatuanProxy();
    });
    modal?.addEventListener('click',e=>{
      const chip=e.target.closest('.danpus-request-kategori-chip');
      if(!chip)return;
      const boxes=satuanChecks(chip.dataset.kategori);
      const allChecked=boxes.length>0&&boxes.every(b=>b.checked);
      boxes.forEach(b=>{b.checked=!allChecked});
      syncSelectAllLabel();syncKategoriChips();syncSatuanProxy();
    });
    // Satuan Tujuan checkbox nggak bisa dipasangi atribut `required` bawaan
    // HTML per-checkbox (itu bakal maksa SEMUA dicentang, padahal maksudnya
    // minimal SATU). Disiasati pakai checkbox proxy tersembunyi yang di-sync
    // ke "minimal satu tercentang" -- proxy inilah yang punya `required`,
    // jadi ikut divalidasi BARENGAN semua field lain pas submit pertama kali
    // (bukan baru ke-cek belakangan setelah field lain diperbaiki dulu).
    const satuanProxy=document.getElementById('danpusRequestSatuanProxy');
    function syncSatuanProxy(){
      if(!satuanProxy)return;
      satuanProxy.checked=satuanChecks().some(b=>b.checked);
      if(satuanProxy.checked){
        satuanProxy.classList.remove('field-invalid');
        if(satuanProxy.nextElementSibling)satuanProxy.nextElementSibling.style.display='none';
      }
    }
    modal?.addEventListener('change',e=>{
      if(e.target.name==='tujuan_satuan_ids[]'){syncSelectAllLabel();syncKategoriChips();syncSatuanProxy()}
    });
  })();

  const satlakLabels=@json($monitoringPimpinanSatlak->pluck('kode')->values());
  const satlakTotals=@json($monitoringPimpinanSatlak->pluck('total')->values());
  const statusData={disetujui:{{ $laporanPimpinanSatlak->filter(fn($l)=>str_contains(strtolower((string)$l->status),'setuj') || str_contains(strtolower((string)$l->status),'diterima'))->count() }},ditolak:{{ $laporanPimpinanSatlak->filter(fn($l)=>str_contains(strtolower((string)$l->status),'tolak'))->count() }},terlambat:{{ $permintaanLaporan->filter(fn($p)=>$p->isTerlambat())->count() }},dibatalkan:{{ $permintaanLaporan->where('status',\App\Models\PermintaanLaporan::STATUS_DIBATALKAN)->count() }}};
  window.siberadCharts = window.siberadCharts || [];
  function makeStatusChart(id){const el=document.getElementById(id);if(!el||typeof Chart==='undefined')return;const labels=['Disetujui','Ditolak','Terlambat','Dibatalkan'];const colors=['#22c55e','#ef4444','#ff6b6b','#c1121f'];const chart=new Chart(el,{type:'doughnut',data:{labels:labels,datasets:[{data:[statusData.disetujui,statusData.ditolak,statusData.terlambat,statusData.dibatalkan],backgroundColor:colors,borderColor:'transparent',borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}}}});window.siberadCharts.push(chart);const legendBox=document.getElementById('statusChartLegend');if(legendBox){legendBox.innerHTML='';labels.forEach((l,i)=>{const item=document.createElement('span');item.className='chart-legend-item';item.innerHTML='<span class="chart-legend-dot" style="background:'+colors[i]+'"></span>'+l;item.addEventListener('click',function(){chart.toggleDataVisibility(i);chart.update();item.classList.toggle('is-hidden',!chart.getDataVisibility(i));});legendBox.appendChild(item);});}}
  function makeSatlakChart(id){const el=document.getElementById(id);if(!el||typeof Chart==='undefined')return;const inner=document.getElementById('satlakChartInner');if(inner){inner.style.height=Math.max(satlakLabels.length*26,260)+'px'}const barCtx=el.getContext('2d');const gradient=barCtx.createLinearGradient(0,0,el.width||600,0);gradient.addColorStop(0,'#6366f1');gradient.addColorStop(1,'#3b82f6');const maxSatlakTotal=satlakTotals.reduce(function(m,v){return Math.max(m,v||0)},0);const xMaxSatlak=Math.max(100,Math.ceil((maxSatlakTotal+1)/10)*10);window.siberadCharts.push(new Chart(el,{type:'bar',data:{labels:satlakLabels,datasets:[{label:'Jumlah laporan',data:satlakTotals,backgroundColor:gradient,hoverBackgroundColor:'#4f46e5',borderRadius:6,maxBarThickness:20}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,scales:{x:{beginAtZero:true,max:xMaxSatlak,ticks:{precision:0,stepSize:10},grid:{color:'rgba(127,127,127,.15)'}},y:{grid:{display:false},ticks:{autoSkip:false,font:{size:11}}}},plugins:{legend:{display:false}}}}))}
  makeStatusChart('statusChart');makeSatlakChart('satlakChart');
})();
</script>
</body>
</html>

