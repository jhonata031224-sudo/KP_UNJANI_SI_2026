<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $satuan->nama ?? 'Pimpinan' }} — SIBERAD</title>
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo-pussiberad.jpg') }}">
@include('siberad.dashboards.partials.dash-styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
:root{--p-bg:#f5f7f9;--p-surface:#fff;--p-surface-2:#f8fafc;--p-border:#e2e8f0;--p-text:#17212b;--p-muted:#64748b;--p-accent:#c97a00;--p-green:#16834b;--p-red:#c83b3b;--p-yellow:#b77900;--p-shadow:0 10px 30px rgba(15,23,42,.07)}
:root:not([data-theme="light"]){--p-bg:var(--bg);--p-surface:var(--panel);--p-surface-2:var(--panel-alt);--p-border:var(--border);--p-text:var(--text);--p-muted:var(--text-muted);--p-accent:var(--gold-bright);--p-green:var(--success-bright);--p-red:var(--red);--p-yellow:var(--amber);--p-shadow:0 10px 30px rgba(0,0,0,.18)}
body{background:var(--p-bg)!important;color:var(--p-text)}.content{background:var(--p-bg)!important;padding-bottom:40px}.pimp-page{max-width:1500px;margin:0 auto}.pimp-hero{position:relative;background:linear-gradient(180deg, rgba(255,255,255,.02), transparent), var(--p-surface);border:1px solid var(--p-border);border-radius:12px;padding:24px 26px;margin-bottom:20px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25)}.pimp-hero::before{content:"";position:absolute;top:0;left:14px;right:14px;height:1px;background:linear-gradient(90deg, transparent, var(--p-border), transparent)}.pimp-eyebrow{font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--p-accent);margin-bottom:7px}.pimp-hero h1{margin:0;font-family:var(--display);font-size:30px;line-height:1.15;color:var(--p-text)}.pimp-hero p{margin:8px 0 0;color:var(--p-muted);font-size:13px;line-height:1.6}.pimp-kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:20px}.pimp-kpi{background:var(--p-surface);border:1px solid var(--p-border);border-radius:14px;padding:18px 20px;box-shadow:var(--p-shadow);min-width:0}.pimp-kpi .label{font-size:11px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--p-muted)}.pimp-kpi .value{font-family:var(--mono);font-size:30px;font-weight:700;margin-top:8px;color:var(--p-text)}.pimp-kpi .sub{font-size:11px;color:var(--p-muted);margin-top:4px}.pimp-kpi.wait .value{color:#f59e0b}.pimp-kpi.ok .value{color:#22c55e}.pimp-kpi.bad .value{color:#ef4444}.pimp-kpi.late .value{color:#ff6b6b}.pimp-kpi.cancelled .value{color:#c1121f}.section-block{background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px;padding:20px;box-shadow:var(--p-shadow);margin-bottom:20px}.section-head-clean{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;margin-bottom:16px}.section-head-clean h2{font-family:var(--display);font-size:19px;margin:0;color:var(--p-text)}.section-head-clean p{margin:5px 0 0;font-size:12px;color:var(--p-muted);line-height:1.5}.chart-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,1.95fr);gap:14px;margin-bottom:20px}.chart-card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px;padding:18px 20px;box-shadow:var(--p-shadow);min-width:0}.chart-card h3{font-family:var(--display);font-size:16px;margin:0;color:var(--p-text)}.chart-card p{font-size:11px;color:var(--p-muted);margin:5px 0 14px;line-height:1.5}.chart-box{position:relative;height:280px}.chart-card.compact .chart-box{height:215px}.chart-legend{display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:8px 14px;margin-top:14px}.chart-legend-item{display:flex;align-items:center;gap:6px;font-size:11.5px;font-weight:600;color:var(--p-muted);white-space:nowrap;cursor:pointer;user-select:none}.chart-legend-item.is-hidden{text-decoration:line-through;opacity:.5}.chart-legend-dot{width:9px;height:9px;border-radius:50%;flex:0 0 auto}.satlak-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.satlak-card{border:1px solid var(--p-border);background:var(--p-surface-2);border-radius:12px;padding:18px;transition:.15s ease}.satlak-card:hover{border-color:color-mix(in srgb,var(--p-accent) 30%,var(--p-border));box-shadow:0 6px 16px rgba(15,23,42,.07)}.satlak-card .code{font-family:var(--mono);font-size:10px;color:var(--p-accent);font-weight:800;letter-spacing:.08em}.satlak-card .name{font-weight:700;font-size:14px;line-height:1.35;margin-top:8px;min-height:38px;color:var(--p-text)}.satlak-card .total{font-family:var(--mono);font-size:28px;font-weight:700;margin-top:6px;color:var(--p-text)}.satlak-card .caption{font-size:11px;color:var(--p-muted)}.mini-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:0;margin-top:14px;padding-top:12px;border-top:1px solid var(--p-border)}.mini-stat{text-align:center;border-left:1px solid var(--p-border)}.mini-stat:first-child{border-left:0}.mini-stat strong{display:block;font-family:var(--mono);font-size:15px;font-weight:700}.mini-stat span{display:block;font-size:9px;color:var(--p-muted);margin-top:2px;text-transform:uppercase;letter-spacing:.04em}.mini-stat.wait strong{color:#f59e0b}.mini-stat.ok strong{color:#22c55e}.mini-stat.bad strong{color:#ef4444}.card-link{display:flex;align-items:center;justify-content:center;gap:5px;margin-top:14px;padding:8px;border-radius:8px;border:1px solid var(--p-border);background:var(--p-surface);font-size:11px;font-weight:600;color:var(--p-muted);text-decoration:none;transition:.15s ease}.card-link:hover{border-color:var(--p-accent);color:var(--p-accent);background:var(--p-surface-2)}.status-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:10px;font-weight:800;border:1px solid transparent}.status-pill:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}.status-pill.wait{color:var(--p-yellow);background:rgba(224,168,58,.12);border-color:rgba(224,168,58,.35)}
.status-pill.revisi{color:var(--gold-solid);background:rgba(217,146,11,.14);border-color:rgba(217,146,11,.4)}.status-pill.blue{color:#2476ad;background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.25)}.status-pill.ok{color:var(--p-green);background:rgba(63,194,125,.12);border-color:rgba(63,194,125,.28)}.status-pill.bad{color:var(--p-red);background:rgba(181,52,47,.12);border-color:rgba(198,40,40,.3)}.status-pill.proses{color:var(--p-orange);background:var(--p-orange-bg);border-color:var(--p-orange-border)}.clean-table-wrap{overflow-x:auto}.clean-table{width:100%;border-collapse:collapse;min-width:780px}.clean-table th{font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:var(--p-muted);text-align:left;padding:11px 12px;border-bottom:1px solid var(--p-border);white-space:nowrap}.clean-table td{padding:13px 12px;border-bottom:1px solid var(--p-border);font-size:12px;color:var(--p-text);vertical-align:middle}#kendala-kasansi .clean-table th:nth-child(2),#kendala-kasansi .clean-table th:nth-child(3),#kendala-kasansi .clean-table th:nth-child(4),#kendala-kasansi .clean-table th:nth-child(5),#kendala-kasansi .clean-table th:nth-child(6),#kendala-kasansi .clean-table td:nth-child(4),#kendala-kasansi .clean-table td:nth-child(5),#kendala-kasansi .clean-table td:nth-child(6){text-align:center}#kendala-kasansi .clean-table .action-row{justify-content:center}.clean-table tbody tr:hover{background:var(--hover-tint)}.clean-table tbody tr:last-child td{border-bottom:0}.sender{font-weight:800;color:var(--p-text)}.subject{font-weight:700;color:var(--p-text)}.muted{font-size:10px;color:var(--p-muted);margin-top:3px}.detail-btn{border:1px solid var(--p-border);background:var(--p-surface);color:var(--p-text);border-radius:8px;padding:7px 10px;font-size:10px;font-weight:700;cursor:pointer;transition:border-color .15s ease,background .15s ease,transform .15s ease}.detail-btn:hover{border-color:var(--p-accent);background:var(--p-surface-2);transform:translateY(-1px)}.detail-btn:active{transform:translateY(0) scale(.97)}.action-row{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.action-row form{display:inline-flex;margin:0}.action-row button{border:1px solid transparent;border-radius:8px;padding:8px 14px;font-size:11px;font-weight:700;cursor:pointer;transition:filter .15s ease,transform .15s ease,background .15s ease,color .15s ease}
.action-row .detail-btn{border-color:var(--p-border);padding:7px 10px;font-size:10px}
.action-row .detail-btn:hover{border-color:var(--p-accent);background:var(--p-surface-2)}.action-row .approve{background:var(--p-green);color:#fff;box-shadow:0 6px 16px -6px rgba(22,131,75,.5)}.btn.approve:hover{border-color:var(--p-green);color:var(--p-green);transform:translateY(-1px)}
.action-row .approve:hover{filter:brightness(1.08);transform:translateY(-1px);color:#fff;border-color:transparent}.action-row .revise{background:rgba(224,168,58,.12);color:var(--p-yellow);border-color:rgba(224,168,58,.35)}.action-row .revise:hover{background:var(--p-yellow);color:#fff;border-color:var(--p-yellow);transform:translateY(-1px)}.action-row .reject{background:var(--p-red);color:#fff;box-shadow:0 6px 16px -6px rgba(200,59,59,.5)}.btn.reject:hover{border-color:var(--p-red);color:var(--p-red);transform:translateY(-1px)}
.action-row .reject:hover{filter:brightness(1.08);transform:translateY(-1px);color:#fff;border-color:transparent}
.action-row button:active{transform:scale(.96)}.btn-batalkan-permintaan{background:rgba(198,40,40,.1)!important;border-color:rgba(198,40,40,.35)!important;color:var(--p-red)}.btn-batalkan-permintaan:hover{background:var(--p-red)!important;color:#fff!important;border-color:var(--p-red)!important;filter:brightness(1.08)}.btn-edit-permintaan{background:rgba(22,131,75,.1)!important;border-color:rgba(22,131,75,.35)!important;color:var(--p-green)}.btn-edit-permintaan:hover{background:var(--p-green)!important;color:#fff!important;border-color:var(--p-green)!important;filter:brightness(1.08)}
.btn-revisi-permintaan{background:rgba(217,146,11,.14)!important;border-color:rgba(217,146,11,.4)!important;color:var(--gold-solid)}
.btn-revisi-permintaan:hover{background:linear-gradient(135deg,var(--gold-solid-bright),var(--gold-solid))!important;color:var(--on-gold)!important;border-color:transparent!important;filter:brightness(1.08)}#permintaan-laporan .request-table .action-row .detail-btn{min-width:58px;text-align:center}#permintaan-laporan.danpus-request-panel{background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px;padding:20px;box-shadow:var(--p-shadow);margin:0}.danpus-request-panel .request-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:16px}.danpus-request-panel .request-head h2{font-family:var(--display);font-size:19px;margin:0;color:var(--p-text)}.danpus-request-panel .request-head p{margin:5px 0 0;font-size:12px;color:var(--p-muted);line-height:1.5}.danpus-request-panel .request-table-wrap{overflow-x:auto}.danpus-request-panel .request-table{width:100%;border-collapse:collapse;min-width:760px;table-layout:fixed}.danpus-request-panel .request-subject{white-space:normal;word-break:break-word}.danpus-request-panel .request-muted{white-space:normal;word-break:break-word}.danpus-request-panel .request-table th:not(:nth-child(2)),.danpus-request-panel .request-table td:not(:nth-child(2)){text-align:center}.danpus-request-panel .request-table .action-row{justify-content:center}.danpus-request-panel .request-table th{font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:var(--p-muted);text-align:left;padding:11px 12px;border-bottom:1px solid var(--p-border);white-space:nowrap}.danpus-request-panel .request-table td{padding:13px 12px;border-bottom:1px solid var(--p-border);font-size:12px;color:var(--p-text);vertical-align:middle}.danpus-request-panel .request-table tbody tr.request-row:last-of-type td{border-bottom:0}.danpus-request-panel .request-table tbody tr.request-task-row:last-child td{border-bottom:0}.danpus-request-panel .request-subject{font-weight:800}.danpus-request-panel .request-muted{font-size:10px;color:var(--p-muted);margin-top:3px}.danpus-request-panel .request-deadline{font-weight:700}.danpus-request-panel .request-deadline.late{color:var(--p-red)}.danpus-request-panel .request-deadline.soon{color:var(--p-yellow)}.danpus-request-modal{position:fixed;inset:0;background:rgba(2,4,6,.6);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:24px;z-index:100070;box-sizing:border-box;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease}.danpus-request-modal.open{opacity:1;visibility:visible;pointer-events:auto}.danpus-request-form-card{width:min(620px,calc(100vw - 40px));max-height:88vh;overflow-y:auto;overflow-x:hidden;position:relative;background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px;padding:24px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset,0 32px 80px rgba(0,0,0,.5);box-sizing:border-box;transform:translateY(14px) scale(.97);transition:transform .2s ease}.danpus-request-modal.open .danpus-request-form-card{transform:translateY(0) scale(1)}.danpus-request-form-head{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:18px}.danpus-request-form-head h3{font-family:var(--display);font-size:18px;margin:0;color:var(--p-text)}.danpus-request-close{flex-shrink:0;display:flex;align-items:center;justify-content:center;border:1px solid var(--p-border);background:transparent;color:var(--p-muted);width:36px;height:36px;border-radius:9px;cursor:pointer;line-height:1;transition:border-color .2s ease,color .2s ease,transform .2s ease}.danpus-request-close:hover{border-color:var(--p-red);color:var(--p-red);transform:rotate(90deg)}.danpus-request-close svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;}.danpus-request-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:11px}.danpus-request-field{display:flex;flex-direction:column;gap:5px}.danpus-request-field.full{grid-column:1/-1}.danpus-request-field label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--p-muted)}.danpus-request-field input,.danpus-request-field textarea,.danpus-request-field select{width:100%;box-sizing:border-box;border:1px solid var(--p-border);border-radius:10px;background:var(--p-surface-2);color:var(--p-text);padding:9px 10px;font:inherit;font-size:12px;outline:none}.danpus-request-field textarea{min-height:82px;resize:vertical}.danpus-request-field input:focus,.danpus-request-field textarea:focus,.danpus-request-field select:focus{border-color:var(--p-accent);box-shadow:0 0 0 3px rgba(201,122,0,.1)}
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
.danpus-request-field input.field-invalid,.danpus-deadline-part input.field-invalid{border-color:var(--p-red)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--p-red) 15%,transparent)}
.danpus-picker:has(.field-invalid) .danpus-picker-input{border-color:var(--p-red)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--p-red) 15%,transparent)}
.danpus-request-error{display:flex;align-items:center;gap:6px;margin-top:5px;font-size:10.5px;color:var(--p-red);animation:danpusErrorIn .2s ease}
.danpus-request-error::before{content:"";width:13px;height:13px;flex-shrink:0;border-radius:50%;background:var(--p-red);-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='12' y1='8' x2='12' y2='13'/%3E%3Ccircle cx='12' cy='16.5' r='.6' fill='%23000' stroke='none'/%3E%3Ccircle cx='12' cy='12' r='9.3'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='12' y1='8' x2='12' y2='13'/%3E%3Ccircle cx='12' cy='16.5' r='.6' fill='%23000' stroke='none'/%3E%3Ccircle cx='12' cy='12' r='9.3'/%3E%3C/svg%3E") center/contain no-repeat}
@keyframes danpusRowFlash{0%{background:color-mix(in srgb,var(--gold-solid-bright) 22%,transparent)}100%{background:transparent}}.danpus-report-dropdown.row-flash{animation:danpusRowFlash 2.2s ease}
@keyframes danpusErrorIn{from{opacity:0;transform:translateY(-3px)}to{opacity:1;transform:translateY(0)}}.request-eyebrow{font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--p-accent);margin-bottom:6px}.satuan-pill{display:inline-flex;align-items:center;border-radius:8px;padding:4px 9px;font-size:10px;font-weight:800;letter-spacing:.03em;color:var(--p-accent);background:rgba(201,122,0,.1);border:1px solid rgba(201,122,0,.22);white-space:nowrap}.request-deadline{display:inline-flex;align-items:center;gap:5px;font-weight:700}.request-deadline svg{width:13px;height:13px;flex-shrink:0;opacity:.75}.priority-tag{display:inline-flex;align-items:center;border-radius:999px;padding:5px 10px;font-size:10px;font-weight:800;border:1px solid transparent;white-space:nowrap}.priority-tag.prio-tinggi{color:#fff;background:#6d28d9;border-color:#6d28d9}.priority-tag.prio-sedang{color:#fff;background:#a855f7;border-color:#a855f7}.priority-tag.prio-rendah{color:#6b21a8;background:#e9d5ff;border-color:#e9d5ff}.danpus-request-form-head p{margin:5px 0 0;font-size:12px;color:var(--p-muted);line-height:1.5}.danpus-request-field-headrow{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:1px}.danpus-request-selectall{border:0;background:transparent;color:var(--gold-solid-bright);font-size:10px;font-weight:800;letter-spacing:.03em;cursor:pointer;padding:2px 0}.danpus-request-selectall:hover{text-decoration:underline}.danpus-request-check-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}.danpus-request-satuan-groups{display:flex;flex-direction:column;gap:14px}.danpus-request-satuan-group-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:7px}.danpus-request-satuan-group-title{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--p-muted)}.danpus-request-check{position:relative;display:flex;align-items:center;gap:9px;padding:9px 10px;border:1px solid var(--p-border);border-radius:10px;background:var(--p-surface-2);font-size:11px;color:var(--p-text);cursor:pointer;transition:border-color .15s ease,background .15s ease}.danpus-request-check:hover{border-color:color-mix(in srgb,var(--gold-solid-bright) 35%,transparent);background:color-mix(in srgb,var(--gold-solid-bright) 4%,transparent)}.danpus-request-check input{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none}.danpus-request-check .check-mark{display:flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:5px;border:1.5px solid var(--p-border);background:var(--p-surface);color:transparent;flex-shrink:0;transition:background .15s ease,border-color .15s ease,color .15s ease}.danpus-request-check .check-mark svg{width:11px;height:11px}.danpus-request-check .check-label{font-size:11px;line-height:1.3}.danpus-request-check:has(input:checked){border-color:var(--gold-solid-bright);background:color-mix(in srgb,var(--gold-solid-bright) 12%,transparent)}.danpus-request-check:has(input:checked) .check-mark{background:var(--gold-solid-bright);border-color:var(--gold-solid-bright);color:var(--on-gold)}.priority-toggle{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}.priority-option{position:relative;display:flex;align-items:center;justify-content:center;border:1px solid var(--p-border);border-radius:10px;padding:9px 6px;font-size:11px;font-weight:700;color:var(--p-muted);background:var(--p-surface-2);cursor:pointer;transition:border-color .15s ease,background .15s ease,color .15s ease}.priority-option input{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none}.priority-option.prio-rendah:hover{border-color:#8b5cf6;color:#8b5cf6}.priority-option.prio-sedang:hover{border-color:#a855f7;color:#a855f7}.priority-option.prio-tinggi:hover{border-color:#6d28d9;color:#6d28d9}.priority-option.prio-rendah:has(input:checked){border-color:#8b5cf6;background:#8b5cf6;color:#fff}.priority-option.prio-sedang:has(input:checked){border-color:#a855f7;background:#a855f7;color:#fff}.priority-option.prio-tinggi:has(input:checked){border-color:#6d28d9;background:#6d28d9;color:#fff}.danpus-request-form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px}@media(max-width:700px){.danpus-request-panel{padding:15px}.danpus-request-panel .request-head{display:block}.danpus-request-panel #danpusOpenRequestForm{margin-top:12px}.danpus-request-form-grid{grid-template-columns:1fr}.danpus-request-field.full{grid-column:auto}.danpus-request-check-grid{grid-template-columns:1fr}.priority-toggle{grid-template-columns:1fr}.danpus-request-form-card{width:min(100%,calc(100vw - 24px));max-height:86vh;padding:17px;border-radius:18px}.danpus-request-modal{padding:18px 12px}}
.side-nav-group{margin:0}.side-nav-group-title{width:100%;display:flex;align-items:center;gap:10px;padding:10px 12px;margin:2px 0;border:1px solid transparent;border-radius:9px;background:transparent;color:var(--text-muted);font-family:var(--body);font-size:13.5px;font-weight:600;letter-spacing:normal;text-transform:none;line-height:normal;cursor:pointer;text-align:left;box-sizing:border-box;transition:background .15s ease,color .15s ease}.side-nav-group-title:hover{background:var(--hover-tint);color:var(--text)}.side-nav-group.open .side-nav-group-title{color:var(--text)}.side-nav-group-title .side-text{flex:1;}.side-nav-group-title .chevron{margin-left:auto;width:15px;height:15px;flex-shrink:0;opacity:.6;transition:transform .25s cubic-bezier(.4,0,.2,1),opacity .2s ease}.side-nav-group.open .chevron{transform:rotate(180deg);opacity:1}.side-subnav{display:grid;grid-template-rows:0fr;opacity:0;transition:grid-template-rows .3s cubic-bezier(.4,0,.2,1),opacity .25s ease;overflow:hidden}.side-subnav>div{min-height:0;padding:3px 0;margin-left:18px;border-left:1px solid var(--p-border,var(--border-soft));display:flex;flex-direction:column;gap:2px}.side-nav-group.open .side-subnav{grid-template-rows:1fr;opacity:1}.side-sub-link{position:relative;display:flex;align-items:flex-start;gap:10px;padding:9px 12px 9px 17px;border-radius:0 9px 9px 0;color:var(--text-muted);font-family:var(--body);font-size:13px;font-weight:500;line-height:1.4;text-decoration:none;margin:0;box-sizing:border-box;transition:background .15s ease,color .15s ease}.side-sub-link:hover{background:var(--hover-tint);color:var(--text)}.side-sub-link .sub-dot{width:5px;height:5px;border-radius:50%;background:currentColor;opacity:.5;flex:0 0 auto;margin-top:4px;transition:opacity .15s ease,background .15s ease,box-shadow .15s ease}.side-sub-link.active{background:var(--gold-dim,rgba(201,122,0,.1));color:var(--p-accent);font-weight:600}.side-sub-link.active:before{content:"";position:absolute;left:-1px;top:8px;bottom:8px;width:2px;border-radius:2px;background:var(--p-accent)}.side-sub-link.active .sub-dot{background:var(--p-accent);opacity:1;box-shadow:0 0 0 3px rgba(201,122,0,.15)}
.side-subnav-label{display:none;}
/* Sidebar ciutkan: submenu grup (Log Aktivitas/Pelaporan) tidak lagi
   disembunyikan total — ditampilkan sebagai flyout mengambang di sisi
   kanan ikon grup, diposisikan lewat JS (position:fixed, lihat
   positionGroupFlyout()) karena .side-nav punya overflow-x:hidden yang
   akan memotong flyout kalau cuma pakai position:absolute biasa. */
.sidebar.collapsed .side-subnav{display:none;}
.sidebar.collapsed .side-nav-group.open .side-subnav{display:block;position:fixed;min-width:216px;background:var(--p-surface);border:1px solid var(--p-border);border-radius:12px;box-shadow:0 14px 34px rgba(0,0,0,.22);padding:8px;z-index:100020;}
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
.sidebar.collapsed .side-subnav>div{margin-left:0;border-left:none;padding:0;}
.sidebar.collapsed .side-subnav-label{display:block;font-family:var(--mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--p-muted);padding:4px 10px 8px;}
.sidebar.collapsed .side-sub-link{padding:9px 10px;border-radius:8px;}
.sidebar.collapsed .side-nav-group.has-active-child .side-nav-group-title{color:var(--p-accent);background:var(--gold-dim,rgba(201,122,0,.1));}
.sidebar.collapsed .side-foot{padding:14px 10px 20px;}
.side-nav-label,.side-text,.chevron{transition:opacity .15s ease;}
.side-foot form.logout button{text-transform:none;font-family:var(--body);font-size:13.5px;font-weight:500;letter-spacing:normal;}
.report-modal{position:fixed;inset:0;background:rgba(15,23,42,.48);display:flex;align-items:center;justify-content:center;padding:20px;z-index:1000;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease}.report-modal.open{opacity:1;visibility:visible;pointer-events:auto}.report-modal-card{width:min(760px,100%);max-height:90vh;overflow:auto;background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px;padding:22px;box-shadow:0 25px 70px rgba(15,23,42,.22);box-sizing:border-box;transform:translateY(14px) scale(.97);transition:transform .2s ease}#editDeadlinePermintaanModal .report-modal-card{max-height:98vh}.report-modal.open .report-modal-card{transform:translateY(0) scale(1)}.report-modal-head{display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:18px}.report-modal-head h3{margin:0;font-family:var(--display);font-size:20px}.report-modal-close{flex-shrink:0;width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:1px solid var(--p-border);background:transparent;color:var(--p-muted);cursor:pointer;transition:border-color .2s ease,color .2s ease,transform .2s ease}.report-modal-close:hover{border-color:var(--p-red);color:var(--p-red);transform:rotate(90deg);}.report-modal-close svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;}.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.detail-item{padding:12px;border:1px solid var(--p-border);border-radius:9px;background:var(--p-surface-2)}.detail-item.full{grid-column:1/-1}.detail-label{font-size:9px;text-transform:uppercase;color:var(--p-muted);font-weight:800;letter-spacing:.06em;margin-bottom:5px}.detail-value{font-size:12px;line-height:1.65;white-space:pre-wrap;color:var(--p-text)}.modal-actions{display:flex;justify-content:flex-end;margin-top:16px}.readonly-note{font-size:10px;color:var(--p-muted);background:var(--p-surface-2);border:1px solid var(--p-border);border-radius:8px;padding:8px 10px}
@media(max-width:1150px){.pimp-kpis{grid-template-columns:repeat(2,1fr)}.satlak-grid{grid-template-columns:repeat(2,1fr)}.chart-grid{grid-template-columns:1fr}}@media(max-width:700px){.pimp-kpis,.satlak-grid,.detail-grid{grid-template-columns:1fr}.section-block{padding:15px}.pimp-hero{padding:20px}.pimp-hero h1{font-size:25px}.section-head-clean{display:block}.detail-item.full{grid-column:auto}.chart-card{padding:15px}.chart-box,.chart-card.compact .chart-box{height:230px}}
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
@media(max-width:640px){.request-task-step{margin-right:0;padding:7px 12px}.request-task-step::before{clip-path:none!important;border-radius:8px}}
#danpusRequestTaskList{display:flex;flex-direction:column;gap:8px}
.danpus-task-row{display:flex;gap:8px;align-items:center}
.danpus-task-row input{flex:1}
.danpus-task-remove{flex-shrink:0;width:30px;height:30px;border-radius:8px;border:1px solid var(--p-border);background:transparent;color:var(--p-muted);cursor:pointer;font-size:16px;line-height:1}
.danpus-task-remove:hover{border-color:var(--p-red);color:var(--p-red)}
</style>
</head>
<body>
<div class="profile-modal-overlay" id="profileModalOverlay"><div class="profile-modal-card" id="profileModalCard" role="dialog" aria-modal="true" aria-label="Detail profil"><button type="button" class="profile-modal-close" id="profileModalCloseBtn" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button><div class="profile-dropdown-view" id="profileSettingsView" style="display:none;"><div class="profile-modal-title">Pengaturan Akun</div><div class="profile-subtabs" role="tablist"><button type="button" class="profile-subtab-btn active" data-subtab-target="profilePhotoView" role="tab" aria-selected="true"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8.5A1.5 1.5 0 0 1 5.5 7h2l1-2h7l1 2h2A1.5 1.5 0 0 1 20 8.5v9A1.5 1.5 0 0 1 18.5 19h-13A1.5 1.5 0 0 1 4 17.5Z"></path><circle cx="12" cy="13" r="3.4"></circle></svg>Foto Profil</button><button type="button" class="profile-subtab-btn" data-subtab-target="profilePasswordView" role="tab" aria-selected="false"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2.2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>Ganti Password</button></div><div class="profile-subtab-panel active" id="profilePhotoView" role="tabpanel"><div class="profile-dropdown-head-lg"><div class="profile-dropdown-avatar-lg"><span class="profile-initial" id="profileInitialLarge" style="display:{{ $user->foto_path ? 'none' : '' }};">{{ strtoupper(mb_substr($user->name ?? 'U',0,1)) }}</span><img class="profile-photo" id="profilePhotoLarge" alt="Foto profil {{ $user->name }}" @if($user->foto_path) src="{{ asset('storage/'.$user->foto_path) }}" style="display:block;" @endif></div><div class="profile-dropdown-name">{{ $user->name }}</div><div class="profile-dropdown-role">{{ $user->jabatan ?? 'Pimpinan' }}</div></div><div class="profile-photo-actions"><form method="POST" action="{{ route('profil-foto.update') }}" enctype="multipart/form-data" id="formGantiFoto">@csrf<button type="button" class="profile-btn profile-btn-primary" id="gantiFotoBtn"><span id="gantiFotoLabel">Ganti Foto</span></button><input type="file" name="foto" id="fotoProfilInput" accept="image/png,image/jpeg,image/webp" hidden></form><button type="button" class="profile-btn profile-btn-outline" id="hapusFotoBtn" style="display:{{ $user->foto_path ? '' : 'none' }};"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>Hapus</button></div><p class="profile-photo-hint">Format JPG, PNG, atau WEBP — ukuran maksimal 10 MB.</p></div><div class="profile-subtab-panel" id="profilePasswordView" role="tabpanel">@if($permintaanGantiPasswordPending)<div class="profile-pending-state"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg><h4>Permintaan Sedang Diproses</h4><p>Permintaan ganti password kamu sudah diajukan pada {{ $permintaanGantiPasswordPending->created_at->translatedFormat('d M Y H:i') }}. Silakan tunggu keputusan Admin -- kamu bisa mengajukan permintaan baru setelah ini diputuskan.</p></div>@else<div class="profile-form-notice">Perubahan kata sandi tidak langsung berlaku. Permintaan akan dikirim ke <b>Admin</b> untuk diverifikasi terlebih dahulu.</div><form class="profile-form" id="formGantiPassword" method="POST" action="{{ route('permintaan-reset-password.store') }}">@csrf<div class="profile-form-field"><label for="passBaru">Kata Sandi Baru</label><div class="profile-field-toggle-wrap"><input type="password" id="passBaru" name="password_baru" required placeholder="Kata sandi baru"><button class="field-toggle" type="button" data-target="passBaru" aria-label="Tampilkan Password"><svg class="icon-eye" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"></path><circle cx="12" cy="12" r="3.2"></circle></svg><svg class="icon-eye-off" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"></path><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c7 0 10.5 7 10.5 7a13.6 13.6 0 0 1-3.2 4.1M6.6 6.6C3.5 8.5 1.5 12 1.5 12s3.5 7 10.5 7a10.6 10.6 0 0 0 4.2-.85"></path><path d="M9.5 9.7a3.2 3.2 0 0 0 4.5 4.5"></path></svg></button></div></div><div class="profile-form-field"><label for="passKonfirmasi">Konfirmasi Kata Sandi Baru</label><div class="profile-field-toggle-wrap"><input type="password" id="passKonfirmasi" name="password_baru_confirmation" required placeholder="Ulangi kata sandi baru"><button class="field-toggle" type="button" data-target="passKonfirmasi" aria-label="Tampilkan Password"><svg class="icon-eye" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"></path><circle cx="12" cy="12" r="3.2"></circle></svg><svg class="icon-eye-off" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"></path><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c7 0 10.5 7 10.5 7a13.6 13.6 0 0 1-3.2 4.1M6.6 6.6C3.5 8.5 1.5 12 1.5 12s3.5 7 10.5 7a10.6 10.6 0 0 0 4.2-.85"></path><path d="M9.5 9.7a3.2 3.2 0 0 0 4.5 4.5"></path></svg></button></div></div><div class="profile-form-field"><label for="passCatatan">Catatan untuk Admin (opsional)</label><textarea id="passCatatan" name="catatan" rows="2" placeholder="Contoh: lupa kata sandi lama"></textarea></div><button type="submit" class="btn btn-primary">Kirim Permintaan ke Admin</button></form>@endif</div></div><div class="profile-dropdown-view" id="profileHelpView" style="display:none;"><div class="profile-modal-title">Bantuan &amp; Panduan</div><p class="help-intro">Ringkasan singkat menu utama di dashboard Pimpinan.</p><div class="help-topics"><div class="help-topic"><div class="help-topic-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></div><div class="help-topic-body"><div class="help-topic-title">Riwayat Aktivitas</div><div class="help-topic-desc">Pantau ringkasan aktivitas laporan dari seluruh satuan, atau pilih satu satuan di sidebar untuk melihat detail laporannya.</div></div></div><div class="help-topic"><div class="help-topic-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"></path></svg></div><div class="help-topic-body"><div class="help-topic-title">Pelaporan</div><div class="help-topic-desc">Tinjau laporan masuk (terima/revisi/tolak), cek status laporan yang sudah diputuskan, dan kirim permintaan laporan baru ke satuan.</div></div></div></div><div class="help-footer"><div class="help-footer-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 6-10 7L2 6"></path></svg></div><p>Butuh bantuan lebih lanjut? Hubungi <b>Admin Pussiberad</b> melalui jalur koordinasi internal.</p></div></div></div></div>
<div class="report-modal" id="reportDetailModal"><div class="report-modal-card"><div class="report-modal-head"><h3>Detail Aktivitas Laporan</h3><button type="button" class="report-modal-close" id="reportDetailClose" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div><div class="detail-grid"><div class="detail-item"><div class="detail-label">Pengirim</div><div class="detail-value" id="detailPengirim">-</div></div><div class="detail-item"><div class="detail-label">Tujuan</div><div class="detail-value" id="detailTujuan">-</div></div><div class="detail-item"><div class="detail-label">Perihal</div><div class="detail-value" id="detailPerihal">-</div></div><div class="detail-item"><div class="detail-label">Prioritas</div><div class="detail-value" id="detailPrioritas">-</div></div><div class="detail-item"><div class="detail-label">Progres</div><div class="detail-value" id="detailProgres">-</div></div><div class="detail-item"><div class="detail-label">Kategori/Kegiatan</div><div class="detail-value" id="detailProyek">-</div></div><div class="detail-item"><div class="detail-label">Tanggal</div><div class="detail-value" id="detailTanggal">-</div></div><div class="detail-item full"><div class="detail-label">Isi Laporan</div><div class="detail-value" id="detailDeskripsi">-</div></div><div class="detail-item full" id="detailKendalaWrap" style="display:none"><div class="detail-label">Kendala/Alasan</div><div class="detail-value" id="detailKendala">-</div></div><div class="detail-item full" id="detailLampiranWrap" style="display:none"><div class="detail-label">Lampiran</div><div class="detail-value"><a id="detailLampiran" href="#" target="_blank" rel="noopener">Lihat lampiran PDF</a></div></div></div><div class="modal-actions" id="detailActions"><span class="readonly-note">Mode pimpinan: aktivitas Satlak bersifat view-only.</span></div></div></div>
<div class="report-modal" id="hapusRiwayatModal"><div class="report-modal-card" style="width:min(420px,100%);"><div class="report-modal-head"><h3>Hapus Riwayat Laporan</h3><button type="button" class="report-modal-close" id="hapusRiwayatClose" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div><p style="font-size:12.5px;color:var(--p-muted);margin:0 0 18px;line-height:1.65;">Laporan yang dihapus tidak dapat dikembalikan. Yakin ingin menghapus laporan ini dari riwayat?</p><form id="formHapusRiwayatPimpinan" method="POST" action="">@csrf @method('DELETE')<div class="action-row" style="justify-content:flex-end;"><button type="button" class="detail-btn" onclick="tutupHapusRiwayatPimpinan()">Batal</button><button type="submit" class="reject">Ya, Hapus</button></div></form></div></div>
<div class="confirm-overlay" id="batalkanPermintaanOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="batalkanPermintaanTitle"><div class="confirm-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><circle cx="12" cy="12" r="9"></circle><path d="M15 9l-6 6"></path><path d="M9 9l6 6"></path></svg></div><h3 id="batalkanPermintaanTitle">Batalkan Permintaan Laporan?</h3><p>Satuan tujuan tidak akan bisa melanjutkan pengerjaan <strong id="batalkanPermintaanPerihal">ini</strong> sampai Anda membuka kembali permintaannya lewat tombol Edit.</p><form id="formBatalkanPermintaan" method="POST" action="">@csrf @method('PATCH')<div class="confirm-actions"><button type="button" class="btn" id="batalkanPermintaanTutup">Tidak</button><button type="submit" class="btn btn-ghost-red">Ya, Batalkan</button></div></form></div></div>
<div class="confirm-overlay" id="terimaLaporanOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="terimaLaporanTitle"><div class="confirm-icon" style="background:var(--success-dim);color:var(--success-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M20 6 9 17l-5-5"></path></svg></div><h3 id="terimaLaporanTitle">Setujui Laporan Ini?</h3><p>Laporan akan ditandai selesai dan disetujui. Satuan tujuan akan melihat keputusan ini sebagai hasil akhir.</p><form id="formTerimaLaporanPimpinan" method="POST" action="">@csrf @method('PATCH')<input type="hidden" name="status" value="Diterima"><div class="confirm-actions"><button type="button" class="btn" id="terimaLaporanBatal">Batal</button><button type="submit" class="btn approve">Ya, Setujui</button></div></form></div></div>
<div class="confirm-overlay" id="tolakLaporanOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="tolakLaporanTitle"><div class="confirm-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M6 6l12 12M18 6L6 18"></path></svg></div><h3 id="tolakLaporanTitle">Tolak Laporan Ini?</h3><p>Berikan catatan alasan penolakan, satuan tujuan akan melihat catatan ini.</p><form id="formTolakLaporanPimpinan" method="POST" action="">@csrf @method('PATCH')<input type="hidden" name="status" value="Ditolak"><label for="tolakLaporanCatatan" style="display:block;text-align:left;font-size:11px;font-weight:800;color:var(--p-muted);text-transform:uppercase;letter-spacing:.05em;margin:14px 0 7px">Catatan / Keterangan</label><textarea id="tolakLaporanCatatan" name="catatan" required maxlength="5000" placeholder="Tuliskan alasan penolakan..." style="width:100%;min-height:64px;box-sizing:border-box;resize:vertical;padding:10px 11px;border:1px solid var(--p-border);border-radius:8px;background:var(--p-surface-2);color:var(--p-text);font:inherit;font-size:13px"></textarea><div class="confirm-actions"><button type="button" class="btn" id="tolakLaporanBatal">Batal</button><button type="submit" class="btn reject">Ya, Tolak</button></div></form></div></div>
<div class="confirm-overlay" id="revisiLaporanOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="revisiLaporanTitle"><div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg></div><h3 id="revisiLaporanTitle">Kirim Ulang untuk Revisi?</h3><p>Satuan tujuan akan bisa mengirim laporan baru untuk menggantikan laporan yang sebelumnya ditolak.</p><form id="formRevisiLaporanPimpinan" method="POST" action="">@csrf @method('PATCH')<input type="hidden" name="status" value="Revisi"><div class="confirm-actions"><button type="button" class="btn" id="revisiLaporanBatal">Batal</button><button type="submit" class="btn btn-primary">Ya</button></div></form></div></div>
<div class="report-modal" id="editDeadlinePermintaanModal"><div class="report-modal-card" style="width:min(520px,100%);"><div class="report-modal-head"><h3>Edit Deadline Permintaan</h3><button type="button" class="report-modal-close" onclick="tutupEditDeadlinePermintaan()" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div><div id="editDeadlineEditableView"><p style="font-size:12.5px;color:var(--p-muted);margin:0 0 16px;line-height:1.65;">Tentukan deadline baru untuk <strong id="editDeadlinePerihal">permintaan ini</strong>.</p><form id="formEditDeadlinePermintaan" method="POST" action="">@csrf @method('PATCH')<div class="danpus-request-field full"><div class="danpus-deadline-split"><div class="danpus-deadline-part"><span class="danpus-deadline-sublabel">Tanggal Baru</span><div class="danpus-picker" id="editDeadlineDatePicker"><input type="text" id="editDeadlineDateInput" class="danpus-picker-input" readonly autocomplete="off" placeholder="Pilih tanggal"><input type="text" id="editDeadlineDateProxy" required style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none"><button type="button" class="danpus-picker-icon" aria-label="Pilih tanggal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg></button></div></div><div class="danpus-deadline-part"><span class="danpus-deadline-sublabel">Jam Baru</span><div class="danpus-picker" id="editDeadlineTimePicker"><input type="text" id="editDeadlineTimeInput" class="danpus-picker-input" readonly autocomplete="off" placeholder="Pilih jam"><input type="text" id="editDeadlineTimeProxy" required style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none"><button type="button" class="danpus-picker-icon" aria-label="Pilih jam"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg></button></div></div></div><input type="hidden" id="editDeadlineHidden" name="deadline_at"></div><div class="danpus-request-form-actions"><button type="button" class="btn" onclick="tutupEditDeadlinePermintaan()">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div></form></div><div id="editDeadlineBlockedView" style="display:none;"><p id="editDeadlineBlockedReason" style="font-size:12.5px;color:var(--p-muted);margin:0;line-height:1.65;"></p><div class="danpus-request-form-actions"><button type="button" class="btn" onclick="tutupEditDeadlinePermintaan()">Tutup</button></div></div></div></div>
<div class="danpus-calendar" id="editDeadlineCalendar" data-min="{{ now()->format('Y-m-d') }}"><div class="danpus-calendar-head"><button type="button" class="danpus-calendar-nav" id="editDeadlineCalendarPrev" aria-label="Bulan sebelumnya"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button><span class="danpus-calendar-title" id="editDeadlineCalendarTitle"></span><button type="button" class="danpus-calendar-nav" id="editDeadlineCalendarNext" aria-label="Bulan berikutnya"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button></div><div class="danpus-calendar-weekdays"><span>M</span><span>S</span><span>S</span><span>R</span><span>K</span><span>J</span><span>S</span></div><div class="danpus-calendar-days" id="editDeadlineCalendarDays"></div><div class="danpus-picker-actions"><button type="button" class="danpus-picker-close" id="editDeadlineCalendarClose">Tutup</button><button type="button" class="danpus-picker-confirm" id="editDeadlineCalendarConfirm" disabled>Konfirmasi</button></div></div>
<div class="danpus-clock-panel" id="editDeadlineClock"><div class="danpus-wheel-row"><div class="danpus-wheel-highlight"></div><div class="danpus-wheel" id="editDeadlineWheelHour"></div><span class="danpus-wheel-colon">:</span><div class="danpus-wheel" id="editDeadlineWheelMinute"></div></div><div class="danpus-picker-actions"><button type="button" class="danpus-picker-close" id="editDeadlineClockClose">Tutup</button><button type="button" class="danpus-picker-confirm" id="editDeadlineClockConfirm">Konfirmasi</button></div></div>
<div class="confirm-overlay" id="editDeadlineKonfirmasiOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="editDeadlineKonfirmasiTitle"><div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg></div><h3 id="editDeadlineKonfirmasiTitle">Simpan Deadline Baru?</h3><p>Satuan tujuan akan bisa langsung melanjutkan atau mengirim ulang laporannya setelah deadline ini disimpan.</p><div class="confirm-actions"><button type="button" class="btn" id="editDeadlineKonfirmasiBatal">Batal</button><button type="button" class="btn btn-primary" id="editDeadlineKonfirmasiYa">Ya, Simpan</button></div></div></div>
<div class="shell"><aside class="sidebar" id="sidebar"><div class="side-brand"><img src="{{ asset('images/logo-pussiberad.jpg') }}" alt="Lambang Pussiberad"><div class="logo">DT-PHATRAM-<span>2639</span></div><button type="button" class="side-collapse-btn" id="sideCollapseBtn" aria-label="Ciutkan sidebar" title="Ciutkan sidebar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg></button></div><nav class="side-nav"><a href="#dashboard" class="side-link active" title="Dashboard"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/></svg></span><span class="side-text">Dashboard</span></a>@if($modulAktif['laporan'] ?? true)<div class="side-nav-group open" id="reportGroup"><button type="button" class="side-nav-group-title" id="reportGroupBtn" title="Pelaporan"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"/></svg></span><span class="side-text">Pelaporan</span> <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button><div class="side-subnav"><div><span class="side-subnav-label">Pelaporan</span><a href="#permintaan-laporan" class="side-sub-link"><span class="sub-dot"></span>Permintaan Laporan</a><a href="#kendala-kasansi" class="side-sub-link" title="Kendala Kasansi"><span class="sub-dot"></span>Kendala Kasansi</a><a href="#status" class="side-sub-link"><span class="sub-dot"></span>Status Laporan</a></div></div></div>@endif @if($modulAktif['monitoring'] ?? true)<div class="side-nav-group open" id="monitorGroup"><button type="button" class="side-nav-group-title" id="monitorGroupBtn" title="Riwayat Aktivitas"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span><span class="side-text">Riwayat Aktivitas</span> <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button><div class="side-subnav"><div><span class="side-subnav-label">Riwayat Aktivitas</span><a href="#monitoring" class="side-sub-link"><span class="sub-dot"></span>Ringkasan Seluruh Satuan</a>@foreach($monitoringPimpinanSatlak as $m)<a href="#satlak-{{ $m['id'] }}" class="side-sub-link"><span class="sub-dot"></span>{{ $m['nama'] }}</a>@endforeach</div></div></div>@endif</nav><div class="side-foot"><form class="logout logout-form" method="POST" action="{{ route('logout') }}">@csrf<button type="submit" title="Keluar"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span><span class="side-text">Keluar</span></button></form></div></aside>
<script>try{if(localStorage.getItem('siberad-sidebar-collapsed')==='1'){document.getElementById('sidebar').classList.add('collapsed');document.querySelectorAll('.side-nav-group.open').forEach(function(g){g.classList.remove('open')});}}catch(e){}</script>
<main class="main"><div class="topbar"><div style="display:flex;align-items:center;gap:12px"><button class="menu-btn" id="menuBtn" type="button">☰</button></div><div class="topbar-actions"><button type="button" class="btn-icon-toggle" id="themeToggleBtn" aria-pressed="false" aria-label="Ganti tema"><svg class="icon-moon" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path></svg><svg class="icon-sun" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.2"></circle><path d="M12 2.5v2.4M12 19.1v2.4M4.4 4.4l1.7 1.7M17.9 17.9l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.4 19.6l1.7-1.7M17.9 6.1l1.7-1.7"></path></svg></button><div class="profile-menu" id="profileMenu"><button type="button" class="profile-menu-btn" id="profileMenuBtn" aria-haspopup="menu" aria-expanded="false" aria-label="Menu profil"><span class="profile-initial" id="profileInitial" style="display:{{ $user->foto_path ? 'none' : '' }};">{{ strtoupper(mb_substr($user->name ?? 'U',0,1)) }}</span><img class="profile-photo" id="profilePhotoBtn" alt="Foto profil {{ $user->name }}" @if($user->foto_path) src="{{ asset('storage/'.$user->foto_path) }}" style="display:block;" @endif></button><div class="profile-dropdown" id="profileDropdown" role="menu" aria-label="Menu profil"><div class="profile-dropdown-head"><div class="profile-dropdown-avatar"><span class="profile-initial" id="profileInitialDropdown" style="display:{{ $user->foto_path ? 'none' : '' }};">{{ strtoupper(mb_substr($user->name ?? 'U',0,1)) }}</span><img class="profile-photo" id="profilePhotoDropdown" alt="Foto profil {{ $user->name }}" @if($user->foto_path) src="{{ asset('storage/'.$user->foto_path) }}" style="display:block;" @endif></div><div><div class="profile-dropdown-name">{{ $user->name }}</div><div class="profile-dropdown-role">{{ $user->jabatan ?? 'Pimpinan' }}</div></div></div><button type="button" class="profile-dropdown-item" id="openPengaturanBtn" role="menuitem">Pengaturan Akun</button><button type="button" class="profile-dropdown-item" id="openBantuanBtn" role="menuitem">Bantuan &amp; Panduan</button><div class="profile-dropdown-divider"></div><form class="logout-form" method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="profile-dropdown-item danger" role="menuitem">Keluar</button></form></div></div></div></div>
<div class="content">@include('siberad.dashboards.partials.pengumuman-banner')
@if(session('status'))<script>document.addEventListener('DOMContentLoaded',function(){window.siberadShowToast?window.siberadShowToast('success',{!! json_encode(session('status')) !!}):null});</script>@endif
@if($errors->any())<div class="alert alert-danger" style="margin-bottom:16px"><ul style="margin:0;padding-left:18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="pimp-page"><section id="dashboard" class="tab-panel active"><div class="pimp-hero"><div class="pimp-eyebrow">SIBERAD // {{ $satuan->kode }}</div><h1>{{ $satuan->nama }}</h1><p>{{ now()->translatedFormat('l, d F Y') }}</p></div><div class="pimp-kpis"><div class="pimp-kpi"><div class="label">Total Laporan</div><div class="value">{{ $laporanPimpinanSatlak->filter(fn($l)=>filled($l->lampiran_path))->count() }}</div><div class="sub">Aktivitas yang tercatat</div></div><div class="pimp-kpi ok"><div class="label">Disetujui</div><div class="value">{{ $laporanPimpinanSatlak->filter(fn($l)=>str_contains(strtolower((string)$l->status),'setuj') || str_contains(strtolower((string)$l->status),'diterima'))->count() }}</div><div class="sub">Laporan yang disetujui</div></div><div class="pimp-kpi bad"><div class="label">Ditolak</div><div class="value">{{ $laporanPimpinanSatlak->filter(fn($l)=>str_contains(strtolower((string)$l->status),'tolak'))->count() }}</div><div class="sub">Laporan yang ditolak</div></div><div class="pimp-kpi late"><div class="label">Terlambat</div><div class="value">{{ $permintaanLaporan->filter(fn($p)=>$p->isTerlambat())->count() }}</div><div class="sub">Permintaan lewat tenggat</div></div><div class="pimp-kpi cancelled"><div class="label">Dibatalkan</div><div class="value">{{ $permintaanLaporan->where('status',\App\Models\PermintaanLaporan::STATUS_DIBATALKAN)->count() }}</div><div class="sub">Permintaan yang dibatalkan</div></div></div><div class="chart-grid"><div class="chart-card compact"><h3>Distribusi Status</h3><p>Komposisi status seluruh laporan dari seluruh Satlak dan unit terkait.</p><div class="chart-box"><canvas id="statusChart"></canvas></div><div class="chart-legend" id="statusChartLegend"></div></div><div class="chart-card"><h3>Laporan per Satuan</h3><p>Perbandingan jumlah laporan yang dibuat oleh masing-masing Satlak.</p><div class="chart-box"><canvas id="satlakChart"></canvas></div></div></div><div class="section-block"><div class="section-head-clean"><div><h2>Ringkasan Aktivitas Seluruh Satuan</h2><p>Ikhtisar cepat sebelum membuka detail aktivitas masing-masing satuan.</p></div></div><div class="satlak-grid">@forelse($monitoringPimpinanSatlak as $m)<article class="satlak-card"><div class="code">{{ $m['kode'] ?? 'SATLAK' }}</div><div class="name">{{ $m['nama'] }}</div><div class="total">{{ $m['total_permintaan'] }}</div><div class="caption">Total permintaan ditugaskan</div><a class="card-link" href="#satlak-{{ $m['id'] }}">Lihat Aktivitas</a></article>@empty<div class="muted">Belum ada data Satlak.</div>@endforelse</div></div></section>
<section id="monitoring" class="tab-panel"><div class="section-block"><div class="section-head-clean"><div><h2>Riwayat Aktivitas</h2><p>Ringkasan Seluruh Satuan. Pilih “Lihat Aktivitas” untuk membuka daftar laporan secara detail.</p></div></div><div class="satlak-grid">@forelse($monitoringPimpinanSatlak as $m)<article class="satlak-card"><div class="code">{{ $m['kode'] ?? 'SATLAK' }}</div><div class="name">{{ $m['nama'] }}</div><div class="total">{{ $m['total_permintaan'] }}</div><div class="caption">Total permintaan ditugaskan</div><div class="mini-stats"><div class="mini-stat ok"><strong>{{ $m['diterima'] }}</strong><span>Disetujui</span></div><div class="mini-stat bad"><strong>{{ $m['ditolak'] }}</strong><span>Ditolak</span></div><div class="mini-stat wait"><strong>{{ $m['menunggu'] }}</strong><span>Menunggu</span></div></div><a class="card-link" href="#satlak-{{ $m['id'] }}">Lihat Aktivitas</a></article>@empty<div class="muted">Belum ada data Satlak.</div>@endforelse</div></div></section>
@foreach($monitoringPimpinanSatlak as $m)<section id="satlak-{{ $m['id'] }}" class="tab-panel"><div class="section-block"><div class="section-head-clean"><div><h2>Aktivitas {{ $m['nama'] }}</h2><p>Daftar laporan yang dibuat satuan ini. Data bersifat view-only untuk pimpinan.</p></div></div><div class="clean-table-wrap" data-pending-permintaan="{{ $permintaanLaporan->where('tujuan_satuan_id',$m['id'])->whereNull('laporan_id')->filter(fn($p) => $p->laporans->isEmpty())->map(fn($p) => ['id' => $p->id, 'subject' => $p->perihal, 'created' => $p->created_at?->translatedFormat('d M Y H:i'), 'ditinjau' => $p->dikerjakan_at?->translatedFormat('d M Y H:i'), 'dibatalkan' => $p->status === \App\Models\PermintaanLaporan::STATUS_DIBATALKAN, 'dibatalkanAt' => $p->dibatalkan_at?->translatedFormat('d M Y H:i'), 'terlambat' => $p->isTerlambat()])->values()->toJson() }}"><table class="clean-table"><thead><tr><th>Perihal</th><th>Tujuan</th><th>Prioritas</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead><tbody>@forelse($laporanPimpinanSatlak->where('satuan_id',$m['id']) as $l)<tr data-permintaan-created="{{ $l->permintaanLaporan?->created_at?->translatedFormat('d M Y H:i') }}" data-permintaan-ditinjau="{{ $l->permintaanLaporan?->dikerjakan_at?->translatedFormat('d M Y H:i') }}" data-permintaan-status="{{ $l->permintaanLaporan?->status }}" data-permintaan-dibatalkan="{{ $l->permintaanLaporan?->dibatalkan_at?->translatedFormat('d M Y H:i') }}" data-permintaan-terlambat="{{ $l->permintaanLaporan?->isTerlambat() ? '1' : '' }}" data-progres="{{ $l->progres }}" data-updated="{{ $l->updated_at->translatedFormat('d M Y H:i') }}" data-kendala="{{ e($l->kendala ?? '') }}" data-permintaan-id="{{ $l->permintaan_laporan_id }}" data-laporan-id="{{ $l->id }}" data-laporan-status="{{ e($l->status) }}"><td><div class="subject">{{ $l->perihal }}</div><div class="muted">{{ $l->proyek ?? 'Laporan kegiatan' }}</div></td><td>{{ $l->tujuanSatuan->nama ?? '-' }}</td><td>{{ $l->prioritas }}</td><td><span class="status-pill {{ $l->status === \App\Models\Laporan::STATUS_PROGRES ? 'blue' : (str_contains(strtolower($l->status),'tolak') ? 'bad' : ((str_contains(strtolower($l->status),'setuj') || str_contains(strtolower($l->status),'diterima')) ? 'ok' : ((str_contains(strtolower($l->status),'revisi')) ? 'revisi' : 'wait'))) }}">{{ $l->status === \App\Models\Laporan::STATUS_PROGRES ? 'Progres · '.$l->progres.'%' : $l->status }}</span></td><td>{{ $l->created_at->translatedFormat('d M Y H:i') }}</td><td><button type="button" class="detail-btn" onclick="openReportDetail(this)" data-pengirim="{{ e($l->satuan->nama ?? '-') }}" data-tujuan="{{ e($l->tujuanSatuan->nama ?? '-') }}" data-perihal="{{ e($l->perihal) }}" data-prioritas="{{ e($l->prioritas) }}" data-progres="{{ $l->progres }}" data-kendala="{{ e($l->kendala ?? '') }}" data-proyek="{{ e($l->proyek ?? '-') }}" data-tanggal="{{ e($l->created_at->translatedFormat('d M Y H:i')) }}" data-deskripsi="{{ e($l->deskripsi) }}" data-lampiran="{{ $l->lampiran_path ? e(asset('storage/'.$l->lampiran_path)) : '' }}" data-readonly="1">Detail</button></td></tr>@empty<tr><td colspan="6"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada laporan dari satuan ini</div></div></td></tr>@endforelse</tbody></table></div></div></section>@endforeach
<section id="kendala-kasansi" class="tab-panel"><div class="section-block"><div class="section-head-clean"><div><h2>Kendala Kasansi</h2><p>Laporan rutin/kendala yang dikirim langsung oleh satuan Kasansi (21 Sansidam) kepada Danpus, tanpa lewat Permintaan Laporan.</p></div></div><div class="clean-table-wrap"><table class="clean-table"><thead><tr><th>Dari</th><th>Perihal</th><th>Prioritas</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead><tbody>@forelse($kendalaMasuk as $k)<tr data-search="{{ strtolower(($k->satuan->nama ?? '').' '.$k->perihal) }}" data-prioritas="{{ $k->prioritas }}"><td><div class="sender">{{ $k->satuan->nama ?? '-' }}</div></td><td><div class="subject">{{ $k->perihal }}</div></td><td>{{ $k->prioritas }}</td><td><span class="status-pill {{ in_array($k->status, ['Ditindaklanjuti','Selesai'], true) ? 'ok' : ($k->status === 'Ditolak' ? 'bad' : 'wait') }}">{{ $k->status }}</span></td><td>{{ $k->created_at->translatedFormat('d M Y H:i') }}</td><td><div class="action-row"><button type="button" class="detail-btn" onclick="openReportDetail(this)" data-pengirim="{{ e($k->satuan->nama ?? '-') }}" data-tujuan="{{ e($satuan->nama) }}" data-perihal="{{ e($k->perihal) }}" data-prioritas="{{ e($k->prioritas) }}" data-tanggal="{{ e($k->created_at->translatedFormat('d M Y H:i')) }}" data-deskripsi="{{ e($k->deskripsi) }}" data-kendala="{{ e($k->catatan ?? '') }}" data-lampiran="{{ $k->lampiran_path ? e(asset('storage/'.$k->lampiran_path)) : '' }}" data-readonly="1"@if($k->status !== 'Menunggu') data-readonly-text="Kendala ini sudah ditindaklanjuti — status: {{ $k->status }}."@endif>Detail</button>@if($k->status === 'Menunggu')<form method="POST" action="{{ route('laporan-kendala.status', $k) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="Ditindaklanjuti"><button class="approve" type="submit">Tindak Lanjuti</button></form><form method="POST" action="{{ route('laporan-kendala.status', $k) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="Ditolak"><button class="reject" type="submit">Tolak</button></form>@elseif($k->status === 'Ditindaklanjuti')<form method="POST" action="{{ route('laporan-kendala.status', $k) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="Selesai"><button class="approve" type="submit">Tandai Selesai</button></form>@endif</div></td></tr>@empty<tr><td colspan="6"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada laporan kendala</div><div class="empty-state-sub">Kendala dari satuan Kasansi akan muncul di sini.</div></div></td></tr>@endforelse</tbody></table></div></div></section>
<section id="status" class="tab-panel"><div class="section-block"><div class="section-head-clean"><div><h2>Status Laporan</h2><p>Rekap laporan dari seluruh satuan yang sudah mendapat keputusan akhir (disetujui atau ditolak).</p></div></div><div class="clean-table-wrap"><table class="clean-table"><thead><tr><th style="text-align:center">Satlak</th><th>Perihal</th><th style="text-align:center">Tujuan</th><th style="text-align:center">Status</th><th style="text-align:center">Tanggal</th><th style="text-align:center">Aksi</th></tr></thead><tbody></tbody></table></div></div></section><section id="permintaan-laporan" class="tab-panel danpus-request-panel" aria-label="Permintaan Laporan"><div class="request-head"><div><h2>Permintaan Laporan</h2><p>Berikan tugas pelaporan kepada satu atau beberapa satuan, lengkap dengan instruksi dan batas waktu.</p></div><button class="btn btn-primary" type="button" id="danpusOpenRequestForm">Buat Permintaan</button></div><div class="request-table-wrap"><table class="request-table"><colgroup><col style="width:17%"><col style="width:29%"><col style="width:8%"><col style="width:22%"><col style="width:24%"></colgroup><thead><tr><th>Ditujukan</th><th>Perihal</th><th>Prioritas</th><th>Deadline</th><th>Aksi</th></tr></thead><tbody>@forelse($permintaanLaporan as $item)@php
// Status & tombol aksi di tab ini sengaja lebih spesifik dari checkpoint
// polos "Sedang diproses" di Log Aktivitas -- pimpinan perlu sinyal kapan
// harus bertindak (Menunggu), dan kapan laporan lagi diulang satuan
// (Revisi) setelah pimpinan klik tombol Revisi dari baris Selesai·Ditolak.
// Status Laporan/PermintaanLaporan tidak punya kolom pembeda "asal Revisi",
// jadi dideteksi dari laporan TERAKHIR yang nempel ke permintaan ini.
$laporanTerakhir=$item->laporans->sortByDesc('id')->first();
$sedangRevisi=$item->status===\App\Models\PermintaanLaporan::STATUS_DIKERJAKAN
    && $laporanTerakhir
    && str_contains(strtolower($laporanTerakhir->status),'revisi');
if($item->status===\App\Models\PermintaanLaporan::STATUS_DIBATALKAN){$statusPimpinan='Dibatalkan';$statusPimpinanClass='bad';}
elseif($item->status===\App\Models\PermintaanLaporan::STATUS_PEMERIKSAAN){$statusPimpinan='Menunggu';$statusPimpinanClass='blue';}
elseif($item->status===\App\Models\PermintaanLaporan::STATUS_SELESAI){
    $hasilAkhir=strtolower($item->laporan->status??'');
    if(str_contains($hasilAkhir,'tolak')){$statusPimpinan='Selesai · Ditolak';$statusPimpinanClass='bad';}
    else{$statusPimpinan='Selesai · Disetujui';$statusPimpinanClass='ok';}
}elseif($sedangRevisi){$statusPimpinan='Revisi';$statusPimpinanClass='revisi';}
elseif($item->isTerlambat()){$statusPimpinan='Terlambat';$statusPimpinanClass='bad';}
else{$statusPimpinan='Sedang diproses';$statusPimpinanClass='proses';}
$bisaEditDeadline=$item->isDapatEditDeadline();
$alasanTidakBisaEdit=$bisaEditDeadline?'':$item->alasanTidakBisaEditDeadline();
@endphp<tr class="request-row" data-search="{{ strtolower($item->perihal.' '.($item->instruksi ?? '').' '.($item->tujuanSatuan->nama ?? '').' '.($item->tujuanSatuan->kode ?? '')) }}" data-status="{{ $statusPimpinan }}" onclick="danpusToggleTaskRow(this,event)"><td><span class="request-row-caret" aria-hidden="true">▸</span><span class="satuan-pill">{{ $item->tujuanSatuan->kode ?? $item->tujuanSatuan->nama ?? '-' }}</span></td><td><div class="request-subject">{{ $item->perihal }}</div>@if($item->instruksi)<div class="request-muted">{{ \Illuminate\Support\Str::limit($item->instruksi,90) }}</div>@endif</td><td><span class="priority-tag prio-{{ strtolower($item->prioritas) }}">{{ $item->prioritas }}</span></td><td><div class="request-deadline" @if(in_array($statusPimpinan,['Menunggu','Revisi'],true)) style="text-decoration:line-through;opacity:.6" @endif><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>{{ $item->deadline_at?->format('d M Y, H:i')??'-' }}</div><div class="request-muted"><span class="status-pill {{ $statusPimpinanClass }}">{{ $statusPimpinan }}</span></div></td><td><div style="display:flex;align-items:center;justify-content:center;gap:7px;flex-wrap:wrap"><div class="action-row" style="display:contents"><button type="button" class="detail-btn" onclick="danpusLihatAktivitas(this)" data-satuan-id="{{ $item->tujuan_satuan_id }}" data-permintaan-id="{{ $item->id }}">Lihat</button>@if($statusPimpinan==='Dibatalkan' || $statusPimpinan==='Terlambat')<button type="button" class="detail-btn btn-edit-permintaan" onclick="bukaEditDeadlinePermintaan(this)" data-permintaan-id="{{ $item->id }}" data-perihal="{{ e($item->perihal) }}" data-deadline="{{ $item->deadline_at?->format('Y-m-d\TH:i') }}" data-editable="{{ $bisaEditDeadline ? '1' : '0' }}" data-alasan="{{ e($alasanTidakBisaEdit) }}">Edit</button>
@elseif($statusPimpinan==='Sedang diproses')<button type="button" class="detail-btn btn-batalkan-permintaan" onclick="bukaBatalkanPermintaan(this)" data-permintaan-id="{{ $item->id }}" data-perihal="{{ e($item->perihal) }}">Batal</button>
@endif</div>@if($statusPimpinan==='Menunggu')<button type="button" class="detail-btn" onclick="openReportDetail(this)" data-pengirim="{{ e($item->tujuanSatuan->nama ?? '-') }}" data-tujuan="{{ e($satuan->nama) }}" data-perihal="{{ e($item->laporan->perihal ?? $item->perihal) }}" data-prioritas="{{ e($item->laporan->prioritas ?? $item->prioritas) }}" data-progres="{{ $item->laporan->progres ?? 100 }}" data-kendala="{{ e($item->laporan->kendala ?? '') }}" data-proyek="{{ e($item->laporan->proyek ?? '-') }}" data-tanggal="{{ e($item->laporan?->created_at?->translatedFormat('d M Y H:i')) }}" data-deskripsi="{{ e($item->laporan->deskripsi ?? '') }}" data-lampiran="{{ $item->laporan?->lampiran_path ? e(asset('storage/'.$item->laporan->lampiran_path)) : '' }}" data-pimpinan-review="1" data-laporan-id="{{ $item->laporan_id }}">Detail</button>@endif @if($statusPimpinan==='Selesai · Ditolak')<button type="button" class="detail-btn btn-revisi-permintaan" onclick="bukaRevisiLaporanPimpinan(this)" data-laporan-id="{{ $item->laporan_id }}">Revisi</button>@endif</div></td></tr><tr class="request-task-row rpt-filter-detail-row" hidden><td colspan="5"><div class="request-task-track">@php $rtActive = false; @endphp@forelse($item->tasks as $task)@php
    $rtState = $task->selesai ? 'done' : ($rtActive ? 'pending' : 'active');
    if (!$task->selesai) { $rtActive = true; }
    $rtTitle = $task->deskripsi.($task->selesai_at ? ' · Selesai '.$task->selesai_at->translatedFormat('d M Y H:i') : '');
    $rtLaporan = $task->laporans->sortByDesc('id')->first();
@endphp<div class="request-task-step {{ $rtState }} {{ $rtLaporan ? 'clickable' : '' }}" title="{{ $rtTitle }}" @if($rtLaporan) role="button" tabindex="0" onclick="openReportDetail(this)" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openReportDetail(this)}" data-pengirim="{{ e($item->tujuanSatuan->nama ?? '-') }}" data-tujuan="{{ e($satuan->nama) }}" data-perihal="{{ e($rtLaporan->perihal) }}" data-prioritas="{{ e($rtLaporan->prioritas) }}" data-progres="{{ $rtLaporan->progres }}" data-kendala="{{ e($rtLaporan->kendala ?? '') }}" data-proyek="{{ e($rtLaporan->proyek ?? '-') }}" data-tanggal="{{ e($rtLaporan->created_at?->translatedFormat('d M Y H:i')) }}" data-deskripsi="{{ e($rtLaporan->deskripsi) }}" data-lampiran="{{ $rtLaporan->lampiran_path ? e(asset('storage/'.$rtLaporan->lampiran_path)) : '' }}" data-readonly="1" @endif><span class="request-task-num">{{ $task->selesai ? '✓' : $loop->iteration }}</span><span class="request-task-label">{{ $task->deskripsi }}</span></div>@empty<div class="request-muted">Tidak ada task untuk permintaan ini.</div>@endforelse</div></td></tr>@empty<tr><td colspan="5"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--p-muted)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada permintaan laporan</div><div class="empty-state-sub">Klik <strong>+ Buat Permintaan</strong> untuk memberikan tugas pelaporan kepada satuan.</div></div></td></tr>@endforelse</tbody></table></div></section></div></div></main></div><div class="danpus-request-modal" id="danpusRequestModal"><div class="danpus-picker-backdrop" id="danpusPickerBackdrop"></div><div class="danpus-request-form-card"><div class="danpus-request-form-head"><div><h3>Buat Permintaan Laporan</h3><p>Tugaskan satuan untuk membuat laporan sebelum batas waktu tertentu.</p></div><button type="button" class="danpus-request-close" id="danpusCloseRequestForm" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div><form method="POST" action="{{ route('permintaan-laporan.store') }}">@csrf<div class="danpus-request-form-grid">@php $danpusSatuanKategoriMap=[\App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN=>'Unsur Pelayanan',\App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN=>'Unsur Pembantu Pimpinan',\App\Models\Satuan::KATEGORI_DIREKTORAT=>'Direktorat',\App\Models\Satuan::KATEGORI_SATLAK=>'Satlak',\App\Models\Satuan::KATEGORI_KOTAMA=>'Kasansi']; $danpusSatuanByKategori=$satuanPermintaanLaporan->groupBy('kategori'); @endphp<div class="danpus-request-field full"><div class="danpus-request-field-headrow"><label>Satuan Tujuan</label><button type="button" class="danpus-request-selectall" id="danpusSelectAllSatuan">Pilih Semua</button></div><div class="danpus-request-satuan-groups">@foreach($danpusSatuanKategoriMap as $kategoriKey=>$kategoriLabel)@continue($danpusSatuanByKategori->get($kategoriKey,collect())->isEmpty())<div class="danpus-request-satuan-group"><div class="danpus-request-satuan-group-head"><span class="danpus-request-satuan-group-title">{{ $kategoriLabel }}</span></div><div class="danpus-request-check-grid">@foreach($danpusSatuanByKategori->get($kategoriKey) as $tujuan)<label class="danpus-request-check"><input type="checkbox" name="tujuan_satuan_ids[]" value="{{ $tujuan->id }}"><span class="check-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg></span><span class="check-label">{{ $tujuan->nama }}</span></label>@endforeach</div></div>@endforeach</div><input type="checkbox" id="danpusRequestSatuanProxy" required style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none"></div><div class="danpus-request-field full"><label for="danpusRequestPerihal">Perihal</label><input id="danpusRequestPerihal" name="perihal" required autocomplete="off" maxlength="255" placeholder="Judul singkat laporan"></div><div class="danpus-request-field full"><label for="danpusRequestKategori">Kategori</label><input id="danpusRequestKategori" name="kategori" required autocomplete="off" maxlength="255" placeholder="Contoh: kegiatan, koordinasi, temuan"></div><div class="danpus-request-field full"><label for="danpusRequestInstruksi">Instruksi</label><textarea id="danpusRequestInstruksi" name="instruksi" required autocomplete="off" maxlength="5000" placeholder="Jelaskan informasi yang perlu dilaporkan..."></textarea></div><div class="danpus-request-field full"><label>Daftar Task untuk Satuan</label><div id="danpusRequestTaskList"><div class="danpus-task-row"><input type="text" name="tasks[]" required autocomplete="off" maxlength="255" placeholder="Contoh: Kumpulkan data insiden minggu ini"><button type="button" class="danpus-task-remove" aria-label="Hapus task">&times;</button></div></div><button type="button" id="danpusAddTaskBtn" class="btn" style="align-self:flex-start;margin-top:8px">+ Tambah Task</button></div><div class="danpus-request-field full"><div class="danpus-deadline-split"><div class="danpus-deadline-part"><span class="danpus-deadline-sublabel">Tanggal</span><div class="danpus-picker" id="danpusDatePicker"><input type="text" id="danpusRequestDeadlineDate" class="danpus-picker-input" readonly autocomplete="off" placeholder="Pilih tanggal"><input type="text" id="danpusRequestDeadlineDateProxy" required style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none"><button type="button" class="danpus-picker-icon" aria-label="Pilih tanggal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg></button><div class="danpus-calendar" id="danpusDeadlineCalendar" data-min="{{ now()->format('Y-m-d') }}"><div class="danpus-calendar-head"><button type="button" class="danpus-calendar-nav" id="danpusCalendarPrev" aria-label="Bulan sebelumnya"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button><span class="danpus-calendar-title" id="danpusCalendarTitle"></span><button type="button" class="danpus-calendar-nav" id="danpusCalendarNext" aria-label="Bulan berikutnya"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button></div><div class="danpus-calendar-weekdays"><span>M</span><span>S</span><span>S</span><span>R</span><span>K</span><span>J</span><span>S</span></div><div class="danpus-calendar-days" id="danpusCalendarDays"></div><div class="danpus-picker-actions"><button type="button" class="danpus-picker-close" id="danpusCalendarClose">Tutup</button><button type="button" class="danpus-picker-confirm" id="danpusCalendarConfirm" disabled>Konfirmasi</button></div></div></div></div><div class="danpus-deadline-part"><span class="danpus-deadline-sublabel">Jam</span><div class="danpus-picker" id="danpusTimePicker"><input type="text" id="danpusRequestDeadlineTime" class="danpus-picker-input" readonly autocomplete="off" placeholder="Pilih jam"><input type="text" id="danpusRequestDeadlineTimeProxy" required style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none"><button type="button" class="danpus-picker-icon" aria-label="Pilih jam"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg></button><div class="danpus-clock-panel" id="danpusDeadlineClock"><div class="danpus-wheel-row"><div class="danpus-wheel-highlight"></div><div class="danpus-wheel" id="danpusWheelHour"></div><span class="danpus-wheel-colon">:</span><div class="danpus-wheel" id="danpusWheelMinute"></div></div><div class="danpus-picker-actions"><button type="button" class="danpus-picker-close" id="danpusClockClose">Tutup</button><button type="button" class="danpus-picker-confirm" id="danpusClockConfirm">Konfirmasi</button></div></div></div></div></div><input type="hidden" id="danpusRequestDeadline" name="deadline_at"></div><div class="danpus-request-field full"><label>Prioritas</label><div class="priority-toggle"><label class="priority-option prio-rendah"><input type="radio" name="prioritas" value="Rendah" required><span>Rendah</span></label><label class="priority-option prio-sedang"><input type="radio" name="prioritas" value="Sedang" required><span>Sedang</span></label><label class="priority-option prio-tinggi"><input type="radio" name="prioritas" value="Tinggi" required><span>Tinggi</span></label></div></div></div><div class="danpus-request-form-actions"><button type="button" class="btn" id="danpusCancelRequestForm">Batal</button><button type="submit" class="btn btn-primary">Kirim Permintaan</button></div></form></div></div><div class="confirm-overlay" id="danpusKirimPermintaanOverlay" style="z-index:100090"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="danpusKirimPermintaanTitle"><div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg></div><h3 id="danpusKirimPermintaanTitle">Kirim Permintaan Laporan?</h3><p>Pastikan satuan tujuan, perihal, dan deadline sudah sesuai. Permintaan yang sudah terkirim tidak dapat diedit lagi.</p><div class="confirm-actions"><button type="button" class="btn" id="danpusKirimPermintaanBatal">Batal</button><button type="button" class="btn btn-primary" id="danpusKirimPermintaanYa">Ya, Kirim</button></div></div></div>
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
    ['monitorGroup','reportGroup'].forEach(id=>{
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

  ['monitorGroup','reportGroup'].forEach(id=>{
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

  window.openReportDetail=function(button){const modal=document.getElementById('reportDetailModal');document.getElementById('detailPengirim').textContent=button.dataset.pengirim||'-';document.getElementById('detailTujuan').textContent=button.dataset.tujuan||'-';document.getElementById('detailPerihal').textContent=button.dataset.perihal||'-';document.getElementById('detailPrioritas').textContent=button.dataset.prioritas||'-';document.getElementById('detailProgres').textContent=button.dataset.progres?button.dataset.progres+'%':'-';document.getElementById('detailProyek').textContent=button.dataset.proyek||'-';document.getElementById('detailTanggal').textContent=button.dataset.tanggal||'-';document.getElementById('detailDeskripsi').textContent=button.dataset.deskripsi||'-';const kendala=button.dataset.kendala||'',kendalaWrap=document.getElementById('detailKendalaWrap');if(kendala){document.getElementById('detailKendala').textContent=kendala;kendalaWrap.style.display='block'}else{kendalaWrap.style.display='none'}const lampiran=button.dataset.lampiran||'',wrap=document.getElementById('detailLampiranWrap'),link=document.getElementById('detailLampiran');if(lampiran){link.href=lampiran;wrap.style.display='block'}else{wrap.style.display='none'}const actionsEl=document.getElementById('detailActions')||modal?.querySelector('.modal-actions');if(actionsEl){if(button.dataset.pimpinanReview==='1'){const lid=button.dataset.laporanId||'';actionsEl.innerHTML='<div class="action-row"><button type="button" class="btn reject" onclick="bukaTolakLaporanPimpinan(\''+lid+'\')">Tolak</button><button type="button" class="btn approve" onclick="bukaTerimaLaporanPimpinan(\''+lid+'\')">Terima</button></div>'}else if(button.dataset.deletable==='1'){actionsEl.innerHTML='<div class="action-row"><button type="button" class="reject" onclick="bukaHapusRiwayatPimpinan(\''+(button.dataset.id||'')+'\')">Hapus dari Riwayat</button></div>'}else if(button.dataset.readonly==='1'){actionsEl.innerHTML='<span class="report-detail-note" style="margin-right:auto;font-size:11px;color:var(--p-muted);">'+(button.dataset.readonlyText||'Mode pimpinan: aktivitas Satlak bersifat view-only.')+'</span>'}else{actionsEl.innerHTML='<span class="readonly-note">Mode pimpinan: aktivitas Satlak bersifat view-only.</span>'}}modal?.classList.add('open')};
  document.getElementById('reportDetailClose')?.addEventListener('click',()=>document.getElementById('reportDetailModal')?.classList.remove('open'));
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('reportDetailModal')?.classList.contains('open'))document.getElementById('reportDetailModal').classList.remove('open')});

  window.bukaHapusRiwayatPimpinan=function(id){if(!id)return;document.getElementById('formHapusRiwayatPimpinan').action='{{ url('/laporan') }}/'+id;document.getElementById('reportDetailModal')?.classList.remove('open');document.getElementById('hapusRiwayatModal')?.classList.add('open')};
  window.tutupHapusRiwayatPimpinan=function(){document.getElementById('hapusRiwayatModal')?.classList.remove('open')};
  document.getElementById('hapusRiwayatClose')?.addEventListener('click',()=>tutupHapusRiwayatPimpinan());
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('hapusRiwayatModal')?.classList.contains('open'))tutupHapusRiwayatPimpinan()});

  window.bukaBatalkanPermintaan=function(button){const id=button.dataset.permintaanId;if(!id)return;document.getElementById('formBatalkanPermintaan').action='{{ url('/permintaan-laporan') }}/'+id+'/batal';document.getElementById('batalkanPermintaanPerihal').textContent=button.dataset.perihal?'"'+button.dataset.perihal+'"':'ini';document.getElementById('reportDetailModal')?.classList.remove('open');document.getElementById('batalkanPermintaanOverlay')?.classList.add('open')};
  window.tutupBatalkanPermintaan=function(){document.getElementById('batalkanPermintaanOverlay')?.classList.remove('open')};
  document.getElementById('batalkanPermintaanTutup')?.addEventListener('click',()=>tutupBatalkanPermintaan());
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('batalkanPermintaanOverlay')?.classList.contains('open'))tutupBatalkanPermintaan()});

  window.bukaTerimaLaporanPimpinan=function(laporanId){if(!laporanId)return;document.getElementById('formTerimaLaporanPimpinan').action='{{ url('/laporan') }}/'+laporanId+'/status';document.getElementById('reportDetailModal')?.classList.remove('open');document.getElementById('terimaLaporanOverlay')?.classList.add('open')};
  window.bukaTolakLaporanPimpinan=function(laporanId){if(!laporanId)return;document.getElementById('formTolakLaporanPimpinan').action='{{ url('/laporan') }}/'+laporanId+'/status';const catatan=document.getElementById('tolakLaporanCatatan');if(catatan)catatan.value='';document.getElementById('reportDetailModal')?.classList.remove('open');document.getElementById('tolakLaporanOverlay')?.classList.add('open')};
  document.getElementById('terimaLaporanBatal')?.addEventListener('click',()=>document.getElementById('terimaLaporanOverlay')?.classList.remove('open'));
  document.getElementById('tolakLaporanBatal')?.addEventListener('click',()=>document.getElementById('tolakLaporanOverlay')?.classList.remove('open'));
  document.addEventListener('keydown',e=>{if(e.key!=='Escape')return;document.getElementById('terimaLaporanOverlay')?.classList.remove('open');document.getElementById('tolakLaporanOverlay')?.classList.remove('open');document.getElementById('revisiLaporanOverlay')?.classList.remove('open')});

  window.bukaRevisiLaporanPimpinan=function(button){const laporanId=button?.dataset?.laporanId;if(!laporanId)return;document.getElementById('formRevisiLaporanPimpinan').action='{{ url('/laporan') }}/'+laporanId+'/status';document.getElementById('reportDetailModal')?.classList.remove('open');document.getElementById('revisiLaporanOverlay')?.classList.add('open')};
  document.getElementById('revisiLaporanBatal')?.addEventListener('click',()=>document.getElementById('revisiLaporanOverlay')?.classList.remove('open'));

  window.bukaEditDeadlinePermintaan=function(button){
    const id=button.dataset.permintaanId;if(!id)return;
    const editableView=document.getElementById('editDeadlineEditableView');
    const blockedView=document.getElementById('editDeadlineBlockedView');
    if(button.dataset.editable==='1'){
      document.getElementById('formEditDeadlinePermintaan').action='{{ url('/permintaan-laporan') }}/'+id+'/deadline';
      document.getElementById('editDeadlinePerihal').textContent=button.dataset.perihal?'"'+button.dataset.perihal+'"':'ini';
      const [datePart,timePart]=(button.dataset.deadline||'').split('T');
      editDatePicker?.setPicked(datePart||null);
      editTimePicker?.setPicked(timePart||null);
      editableView.style.display='';blockedView.style.display='none';
    }else{
      document.getElementById('editDeadlineBlockedReason').textContent=button.dataset.alasan||'Deadline permintaan ini tidak dapat diubah saat ini.';
      editableView.style.display='none';blockedView.style.display='';
    }
    document.getElementById('editDeadlinePermintaanModal')?.classList.add('open');
  };
  window.tutupEditDeadlinePermintaan=function(){document.getElementById('editDeadlinePermintaanModal')?.classList.remove('open')};
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('editDeadlinePermintaanModal')?.classList.contains('open'))tutupEditDeadlinePermintaan()});

  (function(){
    const modal=document.getElementById('danpusRequestModal');
    const requestForm=modal?.querySelector('form');
    const close=()=>{modal?.classList.remove('open');resetTaskList()};
    document.getElementById('danpusOpenRequestForm')?.addEventListener('click',()=>modal?.classList.add('open'));
    document.getElementById('danpusCloseRequestForm')?.addEventListener('click',close);
    document.getElementById('danpusCancelRequestForm')?.addEventListener('click',close);
    document.addEventListener('keydown',e=>{if(e.key==='Escape')close()});

    // Daftar task dinamis di form "Buat Permintaan" -- tiap satuan tujuan
    // dapat clone task yang sama persis (lihat PermintaanLaporanController::store).
    function makeTaskRow(){
      const row=document.createElement('div');
      row.className='danpus-task-row';
      row.innerHTML='<input type="text" name="tasks[]" required autocomplete="off" maxlength="255" placeholder="Contoh: Kumpulkan data insiden minggu ini"><button type="button" class="danpus-task-remove" aria-label="Hapus task">&times;</button>';
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
      const proxy=document.getElementById('editDeadlineDateProxy');
      if(!proxy)return;
      const d=window.editDatePicker?.getPicked(),t=window.editTimePicker?.getPicked();
      if(!d||!t){proxy.setCustomValidity('');return}
      const combined=d+'T'+t;
      const now=new Date();
      const nowStr=now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+String(now.getDate()).padStart(2,'0')+'T'+String(now.getHours()).padStart(2,'0')+':'+String(now.getMinutes()).padStart(2,'0');
      proxy.setCustomValidity(combined<=nowStr?'Deadline baru harus lebih besar dari waktu sekarang.':'');
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
      formEditDeadline.addEventListener('submit',e=>{
        if(formEditDeadline.dataset.confirmed==='1'){formEditDeadline.dataset.confirmed='';return}
        e.preventDefault();
        overlay.classList.add('open');
      });
      document.getElementById('editDeadlineKonfirmasiYa')?.addEventListener('click',()=>{
        closeConfirm();
        formEditDeadline.dataset.confirmed='1';
        formEditDeadline.requestSubmit?formEditDeadline.requestSubmit():formEditDeadline.submit();
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
      return 'Kolom ini wajib diisi.';
    }
    // Checkbox/radio group nggak bisa dianggap satu "field" tunggal --
    // semuanya diarahkan ke wadah bersama (.priority-toggle / proxy
    // checkbox satuan) supaya cuma satu pesan error yang muncul per grup,
    // bukan numpuk di tiap opsi. Query dari kedua form (Buat Permintaan &
    // Edit Deadline) sekaligus -- widget tanggal/jam-nya sama, jadi validasi
    // custom-nya juga disamain, bukan cuma tampilannya doang.
    document.querySelectorAll('#danpusRequestModal input[required],#danpusRequestModal textarea[required],#editDeadlinePermintaanModal input[required]').forEach(input=>{
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
      const anchor=deadlinePart||input.closest('.priority-toggle')||input;
      let msg=deadlinePart?anchor.querySelector(':scope > .danpus-request-error'):anchor.nextElementSibling;
      if(!msg||!msg.classList.contains('danpus-request-error')){
        msg=document.createElement('span');
        msg.className='danpus-request-error';
        msg.style.display='none';
        if(deadlinePart)anchor.appendChild(msg);
        else anchor.insertAdjacentElement('afterend',msg);
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
    });

    const selectAllBtn=document.getElementById('danpusSelectAllSatuan');
    const satuanChecks=()=>Array.from(modal?.querySelectorAll('input[name="tujuan_satuan_ids[]"]')||[]);
    function syncSelectAllLabel(){
      if(!selectAllBtn)return;
      const boxes=satuanChecks();
      const allChecked=boxes.length>0&&boxes.every(b=>b.checked);
      selectAllBtn.textContent=allChecked?'Batalkan Semua':'Pilih Semua';
    }
    selectAllBtn?.addEventListener('click',()=>{
      const boxes=satuanChecks();
      const allChecked=boxes.length>0&&boxes.every(b=>b.checked);
      boxes.forEach(b=>{b.checked=!allChecked});
      syncSelectAllLabel();
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
      if(e.target.name==='tujuan_satuan_ids[]'){syncSelectAllLabel();syncSatuanProxy()}
    });
  })();

  const satlakLabels=@json($monitoringPimpinanSatlak->pluck('kode')->values());
  const satlakTotals=@json($monitoringPimpinanSatlak->pluck('total')->values());
  const statusData={disetujui:{{ $laporanPimpinanSatlak->filter(fn($l)=>str_contains(strtolower((string)$l->status),'setuj') || str_contains(strtolower((string)$l->status),'diterima'))->count() }},ditolak:{{ $laporanPimpinanSatlak->filter(fn($l)=>str_contains(strtolower((string)$l->status),'tolak'))->count() }},terlambat:{{ $permintaanLaporan->filter(fn($p)=>$p->isTerlambat())->count() }},dibatalkan:{{ $permintaanLaporan->where('status',\App\Models\PermintaanLaporan::STATUS_DIBATALKAN)->count() }}};
  window.siberadCharts = window.siberadCharts || [];
  function makeStatusChart(id){const el=document.getElementById(id);if(!el||typeof Chart==='undefined')return;const labels=['Disetujui','Ditolak','Terlambat','Dibatalkan'];const colors=['#22c55e','#ef4444','#ff6b6b','#c1121f'];const chart=new Chart(el,{type:'doughnut',data:{labels:labels,datasets:[{data:[statusData.disetujui,statusData.ditolak,statusData.terlambat,statusData.dibatalkan],backgroundColor:colors,borderColor:'transparent',borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}}}});window.siberadCharts.push(chart);const legendBox=document.getElementById('statusChartLegend');if(legendBox){legendBox.innerHTML='';labels.forEach((l,i)=>{const item=document.createElement('span');item.className='chart-legend-item';item.innerHTML='<span class="chart-legend-dot" style="background:'+colors[i]+'"></span>'+l;item.addEventListener('click',function(){chart.toggleDataVisibility(i);chart.update();item.classList.toggle('is-hidden',!chart.getDataVisibility(i));});legendBox.appendChild(item);});}}
  function makeSatlakChart(id){const el=document.getElementById(id);if(!el||typeof Chart==='undefined')return;const barCtx=el.getContext('2d');const gradient=barCtx.createLinearGradient(0,0,0,el.height||260);gradient.addColorStop(0,'#6366f1');gradient.addColorStop(1,'#3b82f6');window.siberadCharts.push(new Chart(el,{type:'bar',data:{labels:satlakLabels,datasets:[{label:'Jumlah laporan',data:satlakTotals,backgroundColor:gradient,hoverBackgroundColor:'#4f46e5',borderRadius:7,maxBarThickness:42}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{precision:0},grid:{color:'rgba(127,127,127,.15)'}},x:{grid:{display:false}}},plugins:{legend:{display:false}}}}))}
  makeStatusChart('statusChart');makeSatlakChart('satlakChart');
})();
</script>
</body>
</html>

